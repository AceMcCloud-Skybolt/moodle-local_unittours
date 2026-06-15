<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

class restore_local_unittours_plugin extends restore_local_plugin {
    /** @var array Steps whose target references need restore mapping after activities exist. */
    protected $pendingtargetremaps = [];

    public function define_course_plugin_structure() {
        return [
            new restore_path_element(
                $this->get_namefor('tour'),
                $this->get_pathfor('/unit_tours/unit_tour')
            ),
            new restore_path_element(
                $this->get_namefor('step'),
                $this->get_pathfor('/unit_tours/unit_tour/unit_tour_steps/unit_tour_step')
            ),
            new restore_path_element(
                $this->get_namefor('group'),
                $this->get_pathfor('/unit_tours/unit_tour/unit_tour_groups/unit_tour_group')
            ),
        ];
    }

    public function process_local_unittours_tour($data): void {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        unset($data->id);
        $data->courseid = $this->get_task()->get_courseid();
        $data->timecreated = $data->timecreated ?? time();
        $data->timemodified = time();

        $newid = $DB->insert_record('local_unittours_tours', $data);
        $this->set_mapping($this->get_namefor('tour'), $oldid, $newid);
    }

    public function process_local_unittours_step($data): void {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        unset($data->id);
        $data->tourid = $this->get_new_parentid($this->get_namefor('tour'));
        $oldtargetref = $data->targetref;
        $data->timecreated = $data->timecreated ?? time();
        $data->timemodified = time();

        $newid = $DB->insert_record('local_unittours_steps', $data);
        $this->set_mapping($this->get_namefor('step'), $oldid, $newid);

        if (in_array($data->targettype, ['course_module', 'section'], true) && $oldtargetref !== '') {
            $this->pendingtargetremaps[] = [
                'stepid' => $newid,
                'targettype' => $data->targettype,
                'oldtargetref' => $oldtargetref,
            ];
        }
    }

    public function process_local_unittours_group($data): void {
        global $DB;

        $data = (object) $data;
        $newgroupid = $this->get_mappingid('group', (int) $data->groupid, null);
        if (!$newgroupid) {
            return;
        }

        $DB->insert_record('local_unittours_tour_groups', (object) [
            'tourid' => $this->get_new_parentid($this->get_namefor('tour')),
            'groupid' => $newgroupid,
        ]);
    }

    public function after_restore_course(): void {
        global $DB;

        foreach ($this->pendingtargetremaps as $remap) {
            $newtargetref = $this->remap_targetref($remap['targettype'], $remap['oldtargetref']);
            if (!$newtargetref || $newtargetref === $remap['oldtargetref']) {
                continue;
            }

            $step = $DB->get_record('local_unittours_steps', ['id' => $remap['stepid']]);
            if (!$step) {
                continue;
            }

            $step->targetref = $newtargetref;
            $step->fallbackselector = $this->remap_fallbackselector(
                $step->targettype,
                $newtargetref,
                $step->fallbackselector
            );
            $step->timemodified = time();
            $DB->update_record('local_unittours_steps', $step);
        }
    }

    private function remap_targetref(string $targettype, ?string $targetref): ?string {
        if ($targetref === null || $targetref === '') {
            return $targetref;
        }

        if ($targettype === 'course_module') {
            $newcmid = $this->get_mappingid('course_module', (int) $targetref, null);
            return $newcmid ? (string) $newcmid : $targetref;
        }

        if ($targettype === 'section') {
            $newsectionid = $this->get_mappingid('course_section', (int) $targetref, null);
            return $newsectionid ? (string) $newsectionid : $targetref;
        }

        return $targetref;
    }

    private function remap_fallbackselector(string $targettype, ?string $targetref, ?string $fallbackselector): ?string {
        if ($targettype === 'course_module' && !empty($targetref) && !empty($fallbackselector)) {
            return '#module-' . $targetref;
        }

        if ($targettype === 'section' && !empty($targetref) && !empty($fallbackselector)) {
            return '[data-sectionid="' . $targetref . '"], [data-id="' . $targetref . '"]';
        }

        return $fallbackselector;
    }
}
