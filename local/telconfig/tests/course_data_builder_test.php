<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

// File: local/telconfig/tests/course_data_builder_test.php

namespace local_telconfig\tests;

use advanced_testcase;
use context_course;
use core_tag_tag;
use local_telconfig\course_data_builder;

defined('MOODLE_INTERNAL') || die();

class course_data_builder_test extends advanced_testcase {

    
    public function test_build_course_metadata_returns_expected_array() {
        global $DB;

        $this->resetAfterTest(true);

        // Create a course using Moodle's generator
        $course = $this->getDataGenerator()->create_course([
            'fullname' => 'Test Course',
            'summary' => 'Test summary',
            'startdate' => strtotime('2022-01-01')
        ]);

        // Enrol a teacher in the course
        $teacher = $this->getDataGenerator()->create_user(['firstname' => 'Alice', 'lastname' => 'Teacher']);
        $roleid = 3; // editingteacher
        $context = context_course::instance($course->id);
        role_assign($roleid, $teacher->id, $context->id);

        // Add tags to course
        core_tag_tag::set_item_tags('core', 'course', $course->id, context_course::instance($course->id), ['tag1', 'tag2']);

        // Call the method
        $result = course_data_builder::build_course_metadata($course);

        // Assertions
        $this->assertIsArray($result);
        $this->assertSame('M' . $course->id, $result['_id']);
        $this->assertSame($course->id, $result['course_id']);
        $this->assertSame(date('Y-m-d', $course->startdate), $result['authored_date']);
        $this->assertContains(fullname($teacher), $result['authors']);
        $this->assertEquals(['1'], $result['catalogue_ids']);
        $this->assertSame(format_text($course->summary, FORMAT_HTML), $result['description']);
        $this->assertEquals(['tag1', 'tag2'], $result['keywords']);
        $this->assertIsArray($result['location_paths']);
        $this->assertSame(date('Y-m-d', $course->startdate), $result['publication_date']);
        $this->assertSame(0, $result['rating']);
        $this->assertSame(0, $result['resource_reference_id']);
        $this->assertSame('Course', $result['resource_type']);
        $this->assertSame($course->fullname, $result['title']);
    }
}
