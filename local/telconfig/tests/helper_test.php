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

class helper_test extends advanced_testcase {
    public function test_send_xx_api_makes_api_call() {
        $this->resetAfterTest();

        set_config('xxindexurl', 'http://fake.local/api', 'local_telconfig');
        set_config('xxindexmethod', 'index', 'local_telconfig');
        set_config('xxcollection', 'courses', 'local_telconfig');
        set_config('xxapitoken', 'faketoken', 'local_telconfig');

        $mock = $this->createMock(api_client::class);
        $mock->expects($this->once())
             ->method('post')
             ->willReturn('{"status":"ok"}');

        helper::send_findwise_api(['courseid' => 123], $mock);
    }
}