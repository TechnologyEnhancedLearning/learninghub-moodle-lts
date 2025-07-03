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

use advanced_testcase;
use local_telconfig\helper;
use local_telconfig\api_client;

class telconfig_helper_test extends advanced_testcase {

    public function test_send_findwise_api_makes_api_call() {
        define('LOCAL_TELCONFIG_DEV_TEST', true); // Flag to enable strict config check
        $this->resetAfterTest();

        // Set fake plugin config values.
        set_config('findwiseindexurl', 'http://fake.local/api', 'local_telconfig');
        set_config('findwiseindexmethod', 'index', 'local_telconfig');
        set_config('findwisecollection', 'courses', 'local_telconfig');
        set_config('findwiseapitoken', 'faketoken', 'local_telconfig');

        // Create a mock API client.
        $mock = $this->createMock(api_client::class);
        $mock->expects($this->once())
             ->method('post')
             ->with(
                 $this->stringContains('http://fake.local/api/index?token='),
                 $this->equalTo(['courseid' => 123])
             )
             ->willReturn('{"status":"ok"}');

        // Call the method with the mock client.
        helper::send_findwise_api(['courseid' => 123], 'POST', $mock);
    }
}
