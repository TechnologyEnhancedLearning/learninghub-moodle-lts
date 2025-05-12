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

/**
 * Helper class locally used.
 *
 * @package    local_telconfig
 * @copyright 
 * @license    
 */

namespace local_telconfig;

defined('MOODLE_INTERNAL') || die();

class helper {

    /**
     * Sends structured data to an external API endpoint.
     *
     * @param array $data
     * @return void
     */
    public static function send_findwise_api(array $data): void {
        $indexurl = get_config('local_telconfig', 'findwiseindexurl');
        $indexmethod = get_config('local_telconfig', 'findwiseindexmethod');
        $collection = get_config('local_telconfig', 'findwisecollection');
        $apitoken = get_config('local_telconfig', 'findwiseapitoken');

        $indexurl = rtrim($indexurl, '/').'/'.$indexmethod.'?token=' . urlencode($apitoken);
        $apiurl = str_replace("{0}", $collection, $indexurl);

        if (empty($apiurl) || empty($apitoken)) {
            debugging('send_findwise_api: API URL or token not set in plugin config.', DEBUG_DEVELOPER);
            return;
        }
           
        $options = [
            'http' => [
                'header'  => "Content-type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($data),
                'timeout' => 5,
            ],
        ];

        $context = stream_context_create($options);
        try {
            $response = @file_get_contents($apiurl, false, $context);        
            if ($response === false) {
                debugging('send_findwise_api: Failed to send data to Findwise API.', DEBUG_DEVELOPER);
            } else {
                debugging('send_findwise_api: Data sent successfully to Findwise API.', DEBUG_DEVELOPER);
            }
        } catch (\Exception $e) {
            debugging('send_findwise_api: Exception occurred while sending data: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}
