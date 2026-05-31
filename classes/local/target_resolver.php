<?php
// This file is part of Moodle - http://moodle.org/

namespace local_unittours\local;

defined('MOODLE_INTERNAL') || die();

final class target_resolver {

    public static function describe(\stdClass $step, \stdClass $course): \stdClass {
        switch ($step->targettype) {
            case target::UNATTACHED:
                return self::result(true, get_string('target_unattached', 'local_unittours'));

            case target::COURSE_MODULE:
                return self::course_module($step, $course);

            case target::SECTION:
                return self::section($step, $course);

            case target::BLOCK:
                return self::block($step, $course);

            case target::COURSE_INDEX:
                return self::course_index($step, $course);

            case target::PAGE_REGION:
                return self::page_region($step);

            case target::SELECTOR:
                return self::selector($step);

            default:
                return self::result(false, get_string('target_unknown', 'local_unittours'));
        }
    }

    private static function course_module(\stdClass $step, \stdClass $course): \stdClass {
        $cmid = clean_param($step->targetref, PARAM_INT);
        if (!$cmid) {
            return self::result(false, get_string('target_missingref', 'local_unittours'));
        }

        try {
            $modinfo = get_fast_modinfo($course);
            $cm = $modinfo->get_cm($cmid);
        } catch (\Exception $exception) {
            return self::result(false, get_string('target_missing', 'local_unittours'));
        }

        return self::result(
            true,
            format_string($cm->name, true, ['context' => \context_module::instance($cm->id)]),
            get_string('target_course_module', 'local_unittours')
        );
    }

    private static function section(\stdClass $step, \stdClass $course): \stdClass {
        $sectionref = clean_param($step->targetref, PARAM_INT);
        $modinfo = get_fast_modinfo($course);
        $sectioninfo = null;

        foreach ($modinfo->get_section_info_all() as $candidate) {
            if ((int) $candidate->id === $sectionref) {
                $sectioninfo = $candidate;
                break;
            }
        }

        if (!$sectioninfo) {
            $sectioninfo = $modinfo->get_section_info($sectionref, IGNORE_MISSING);
        }

        if (!$sectioninfo) {
            return self::result(false, get_string('target_missing', 'local_unittours'));
        }

        $name = get_section_name($course, $sectioninfo);
        return self::result(true, $name, get_string('target_section', 'local_unittours'));
    }

    private static function block(\stdClass $step, \stdClass $course): \stdClass {
        global $DB;

        if (empty($step->targetref)) {
            return self::result(false, get_string('target_missingref', 'local_unittours'));
        }

        $context = \context_course::instance($course->id);
        $exists = $DB->record_exists('block_instances', [
            'blockname' => $step->targetref,
            'parentcontextid' => $context->id,
        ]);

        return self::result(
            $exists,
            $step->targetref,
            get_string('target_block', 'local_unittours'),
            $exists ? '' : get_string('target_missing', 'local_unittours')
        );
    }

    private static function course_index(\stdClass $step, \stdClass $course): \stdClass {
        if (empty($step->targetref)) {
            return self::result(false, get_string('target_missingref', 'local_unittours'));
        }

        $fake = clone $step;
        if (is_numeric($step->targetref)) {
            $fake->targetref = $step->targetref;
            return self::course_module($fake, $course);
        }

        return self::result(true, $step->targetref, get_string('target_course_index', 'local_unittours'));
    }

    private static function page_region(\stdClass $step): \stdClass {
        if (empty($step->targetref)) {
            return self::result(false, get_string('target_missingref', 'local_unittours'));
        }

        return self::result(true, $step->targetref, get_string('target_page_region', 'local_unittours'));
    }

    private static function selector(\stdClass $step): \stdClass {
        if (empty($step->targetref) && empty($step->fallbackselector)) {
            return self::result(false, get_string('target_missingref', 'local_unittours'));
        }

        return self::result(
            null,
            $step->targetref ?: $step->fallbackselector,
            get_string('target_selector', 'local_unittours'),
            get_string('target_selectorunchecked', 'local_unittours')
        );
    }

    private static function result(?bool $found, string $label, string $type = '', string $detail = ''): \stdClass {
        return (object) [
            'found' => $found,
            'label' => $label,
            'type' => $type,
            'detail' => $detail,
        ];
    }
}
