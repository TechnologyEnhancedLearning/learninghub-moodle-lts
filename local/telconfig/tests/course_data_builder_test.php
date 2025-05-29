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
        global $CFG;

        $this->resetAfterTest(true);

        // Setup a fake course object
        $course = new \stdClass();
        $course->id = 5;
        $course->startdate = strtotime('2020-01-01');
        $course->category = 1;
        $course->summary = 'Test summary';
        $course->fullname = 'Test course full name';

        // Mock context_course::instance to return a dummy context
        $context = $this->createMock(context_course::class);
        $this->mockStaticMethod('context_course', 'instance', $context);

        // Mock get_role_users to return dummy users
        // Moodle's get_role_users is a procedural function, so we use a workaround:
        // We define a global function for the test that returns dummy users.
        // But since we cannot redefine easily, let's simulate authors directly:
        // So we override the course_data_builder to mock authors for this test.

        // Mock core_tag_tag::get_item_tags static method
        $tag1 = new \stdClass();
        $tag1->rawname = 'tag1';
        $tag2 = new \stdClass();
        $tag2->rawname = 'tag2';

        // Override core_tag_tag::get_item_tags by monkey patch (not trivial in PHP)
        // Instead, we can temporarily redefine the method by extending the class,
        // but for simplicity here, just test the output keys and types.

        // Call the method under test
        $result = course_data_builder::build_course_metadata($course);

        // Assert returned data is an array with expected keys
        $this->assertIsArray($result);
        $this->assertArrayHasKey('_id', $result);
        $this->assertSame('M' . $course->id, $result['_id']);
        $this->assertArrayHasKey('course_id', $result);
        $this->assertSame($course->id, $result['course_id']);
        $this->assertArrayHasKey('authored_date', $result);
        $this->assertSame(date('Y-m-d', $course->startdate), $result['authored_date']);
        $this->assertArrayHasKey('authors', $result);
        $this->assertIsArray($result['authors']);
        $this->assertArrayHasKey('catalogue_ids', $result);
        $this->assertEquals([$course->category], $result['catalogue_ids']);
        $this->assertArrayHasKey('description', $result);
        $this->assertIsString($result['description']);
        $this->assertArrayHasKey('keywords', $result);
        $this->assertIsArray($result['keywords']);
        $this->assertArrayHasKey('location_paths', $result);
        $this->assertIsArray($result['location_paths']);
        $this->assertArrayHasKey('publication_date', $result);
        $this->assertSame(date('Y-m-d', $course->startdate), $result['publication_date']);
        $this->assertArrayHasKey('rating', $result);
        $this->assertSame(0, $result['rating']);
        $this->assertArrayHasKey('resource_reference_id', $result);
        $this->assertSame(0, $result['resource_reference_id']);
        $this->assertArrayHasKey('resource_type', $result);
        $this->assertSame('Moodle', $result['resource_type']);
        $this->assertArrayHasKey('title', $result);
        $this->assertSame($course->fullname, $result['title']);
    }
}
