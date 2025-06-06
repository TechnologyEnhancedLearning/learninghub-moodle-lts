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
use local_telconfig\api_client;

defined('MOODLE_INTERNAL') || die();

class helper {

    /**
     * Sends structured data to an external API endpoint.
     *
     * @param array $data
     * @return void
     */
    public static function send_findwise_api(array $data, string $method = 'POST', ?api_client $client = null): void {
        $indexurl = get_config('local_telconfig', 'findwiseindexurl');
        $indexmethod = get_config('local_telconfig', 'findwiseindexmethod');
        $collection = get_config('local_telconfig', 'findwisecollection');
        $apitoken = get_config('local_telconfig', 'findwiseapitoken');

        if (empty($indexurl) || empty($apitoken)) {            
            return; 
        }

        $indexurl = rtrim($indexurl, '/') . '/' . $indexmethod . '?token=' . urlencode($apitoken);
        $apiurl = str_replace('{0}', $collection, $indexurl);       

        $client ??= new api_client();

        try {
            if ($method === 'DELETE') {
                // Add logic to construct a deletion URL with course ID
                if (isset($data['course_id'])) {
                    $deleteurl = rtrim($apiurl, '/') . '&id=M' . $data['course_id'];
                    $response = $client->delete($deleteurl);
                } else {
                    debugging('send_findwise_api: Cannot perform DELETE without course_id in $data.', DEBUG_DEVELOPER);
                    return;
                }
            } else {
                $response = $client->post($apiurl, $data); // POST or PUT
            }

            if ($response === false) {
                debugging('send_findwise_api: Failed to send data to findwise API.', DEBUG_DEVELOPER);
            } 
        } catch (\Exception $e) {
            debugging('send_findwise_api: Exception occurred while sending data: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}
