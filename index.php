<?php
// This file is part of Moodle - http://moodle.org/

require_once(__DIR__ . '/../../config.php');

$courseid = required_param('id', PARAM_INT);

redirect(new moodle_url('/local/unittours/manage.php', ['id' => $courseid]));
