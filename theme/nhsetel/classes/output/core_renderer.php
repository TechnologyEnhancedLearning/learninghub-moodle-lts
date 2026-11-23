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

namespace theme_nhsetel\output;

use context_course;
use navigation_node;

defined('MOODLE_INTERNAL') || die;

/**
 * Renderers to align Moodle's HTML with that expected by Bootstrap
 *
 * @package    theme_nhsetel
 * @copyright  NHS England
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_renderer extends \theme_boost\output\core_renderer
{

    public function __construct(\moodle_page $page, $target) {
        parent::__construct($page, $target);
    }

    /**
     * Returns the data context for the global theme header, fetching from API.
     * This method is called by {{# output.get_global_header_data }} in Mustache.
     *
     * @return \stdClass An object containing data for the header.
     */
    public function get_global_header_data() {
        global $PAGE; 
        global $DB, $USER; 
        global $SESSION;

        $context = new \stdClass();
        $context->customnavigation = []; 
        $context->notification_count = 0; 
        $context->loggedinasotheruser = \core\session\manager::is_loggedinas();

        if ($context->loggedinasotheruser) {
            $context->impersonatedusername = fullname($USER);
        } else {
            $context->impersonatedusername = '';
        }

        $token = null; 
        $tokenNew = null; 
        $accesstoken  = null; 
        $response_content = false; 

        $dotnet_base_url = get_config('theme_nhsetel', 'dotnet_base_url');
        if (!empty($dotnet_base_url) && substr($dotnet_base_url, -1) !== '/') {
            $dotnet_base_url .= '/';
        }

        $context->dotnet_base_url = $dotnet_base_url;
        error_log("DEBUG NHSE: get_global_header_data: dotnet_base_url = " . $context->dotnet_base_url); 
        
        $context->sessionKey = sesskey();

        $api_base_url = get_config('theme_nhsetel', 'api_base_url');
        if (!empty($api_base_url) && substr($api_base_url, -1) !== '/') {
            $api_base_url .= '/';
        }
        
        $can_call_api = true;
        if (empty($api_base_url)) {
            error_log("theme_nhsetel: ERROR: LH OpenAPI Base URL is not configured in theme settings. Cannot fetch navigation data.");
            $can_call_api = false; 
        }

        $context->is_user_logged_in = isloggedin();

        if (isloggedin()) { 
            $token = $DB->get_record('auth_oidc_token', ['username' => $USER->username]);          
            if ($token) {
                error_log("theme_nhsetel: Found OIDC token for user {$USER->username}.");
                 $accesstoken = $token->token;
                 error_log("theme_nhsetel: accesstoken {$accesstoken}");
            } else {
                error_log("theme_nhsetel: No OIDC token found for user {$USER->username}.");
            }
        } else {
            error_log("theme_nhsetel: User not logged in, skipping OIDC token fetch.");
        }

        if (empty($accesstoken)) {
             error_log("theme_nhsetel: Access token is missing, skipping all protected API calls.");
             $can_call_api = false;
        }

        $api_admin_link = null;

        if ($can_call_api) {
            $curloptions = [
                'HTTPHEADER' => [
                    'Authorization: Bearer ' . $accesstoken,
                    'Accept: application/json'
                ]
            ];

            $navigation_api_path = 'User/GetLHUserNavigation';
            $navigation_url = $api_base_url . $navigation_api_path;
            $navigation_result = null;

             try {
                $curl = new \curl();
                $response = $curl->get($navigation_url, null, $curloptions);
                $navigation_result = json_decode($response, true);
                
                error_log("theme_nhsetel: API Response (raw): " . $response);
                error_log("theme_nhsetel: API Response (decoded): " . print_r($navigation_result, true));
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("theme_nhsetel: Navigation API JSON decoding error: " . json_last_error_msg());
                }
              } catch (\Exception $e) {          
                debugging('CURL error (Navigation): ' . $e->getMessage(), DEBUG_DEVELOPER);
                error_log("theme_nhsetel: CURL Exception caught for Navigation URL: " . $navigation_url . " Message: " . $e->getMessage());
              }

            if (is_array($navigation_result) && !empty($navigation_result)) {
                error_log("theme_nhsetel: JSON decoded successfully. Processing " . count($navigation_result) . " items.");
                $processed_links = [];
                foreach ($navigation_result as $item) {
                    
                    if (isset($item['title']) && trim($item['title']) === 'Sign Out') {
                        error_log("theme_nhsetel: Skipping 'Sign Out' link from API response.");
                        continue; 
                    }

                    if (!isset($item['visible']) || $item['visible'] === true) {
                        $processed_item = new \stdClass();
                        $processed_item->title = $item['title'] ?? 'Untitled'; 
                        $processed_item->openInNewTab = $item['openInNewTab'] ?? false; 
                        
                        if (isset($item['url']) && !empty($item['url'])) {
                            $item_url_path = $item['url']; 
                            
                            if (trim($processed_item->title) === 'Admin') {
                                $admin_url = get_config('theme_nhsetel', 'admin_url');
                                if (!empty($admin_url)) {
                                    $processed_item->url = $admin_url;
                                    $processed_item->openInNewTab = true;
                                    error_log("theme_nhsetel: Overriding 'Admin' URL with theme setting: {$processed_item->url}");
                                } else {
                                    error_log("theme_nhsetel: Admin URL theme setting not found, falling back to API URL.");
                                }
                            } else if (strpos($item_url_path, 'http') === 0 || strpos($item_url_path, '//') === 0) {
                                $processed_item->url = $item_url_path; 
                                error_log("theme_nhsetel: Processing absolute URL: {$processed_item->url}");
                            } else if (!empty($dotnet_base_url)) {
                                $processed_item->url = $dotnet_base_url . ltrim($item_url_path, '/'); 
                                error_log("theme_nhsetel: Redirecting relative link '{$item_url_path}' to .NET domain: {$processed_item->url}");
                            } else {
                                $processed_item->url = new \moodle_url($item_url_path);
                                error_log("theme_nhsetel: .NET base URL not configured, processing relative link '{$item_url_path}' as Moodle internal.");
                            }
                        } else {
                            $processed_item->url = new \moodle_url('/');
                            error_log("theme_nhsetel: Item has empty or missing URL, defaulting to Moodle home.");
                        }
                        $processed_item->hasnotification = $item['hasNotification'] ?? false;
                        $processed_item->notificationcount = $item['notificationCount'] ?? 0;                    

                        // Extract the Admin link to correctly position it later in the navigation structure
                        if (trim($processed_item->title) === 'Admin') {
                            $api_admin_link = $processed_item;
                        } else {
                            $processed_links[] = $processed_item;
                        }
                        
                    } else {
                        error_log("theme_nhsetel: Item not visible and filtered out: " . ($item['title'] ?? 'N/A') . " (visible: " . ($item['visible'] ? 'true' : 'false') . ")");
                    }
                }

                $context->customnavigation = array_merge($context->customnavigation, $processed_links);
                error_log("theme_nhsetel: Processed links for display: " . print_r($processed_links, true));
            } else {
                $json_error_msg = (json_last_error() !== JSON_ERROR_NONE) ? json_last_error_msg() : 'N/A';
                error_log("theme_nhsetel: Failed to process API response. Response was empty, not an array, or an error occurred. JSON Error: " . $json_error_msg);
                error_log("theme_nhsetel: Raw API response (if available): " . ($response ?: 'No response content'));
            }

            $notification_api_path = 'UserNotification/GetUserUnreadNotificationCount/' . $USER->id; 
            $notification_url = $api_base_url . $notification_api_path;
            $notification_result = null;
             
            try {
                $response = $curl->get($notification_url, null, $curloptions);
                $decoded_response = json_decode($response);
                
                if (is_numeric($decoded_response)) {
                    $notification_count = (int) $decoded_response;
                } else if (is_object($decoded_response) && isset($decoded_response->count) && is_numeric($decoded_response->count)) {
                     $notification_count = (int) $decoded_response->count;
                } else if (is_object($decoded_response) && isset($decoded_response->unreadCount) && is_numeric($decoded_response->unreadCount)) {
                    $notification_count = (int) $decoded_response->unreadCount;
                } else if (is_numeric(trim($response))) {
                    $notification_count = (int) trim($response);
                } else {
                    $notification_count = 0;
                    error_log("theme_nhsetel: Notification API returned unexpected format for URL: " . $notification_url . " Raw Response: " . $response);
                }

                $display_notification_count = $notification_count;
                if ($notification_count > 9) {
                    $display_notification_count = '9+';
                }
                
                $context->notification_count = $notification_count;
                $context->display_notification_count = $display_notification_count; 
                
                error_log("theme_nhsetel: Fetched unread notification count: {$context->notification_count}");
                
             } catch (\Exception $e) {
                 debugging('CURL error (Notifications): ' . $e->getMessage(), DEBUG_DEVELOPER);
                 error_log("theme_nhsetel: CURL Exception caught for Notification URL: " . $notification_url . " Message: " . $e->getMessage());
             }
        }

        if (isloggedin()) {
            
            $show_mycourses = get_config('theme_nhsetel', 'mycourses_toggle');
            if ($show_mycourses) {
                $mycourses_text = get_config('theme_nhsetel', 'mycourses_text');
                $mycourses_title = !empty(trim($mycourses_text)) ? trim($mycourses_text) : "My courses";

                $mycourses_link = new \stdClass();
                $mycourses_link->title = $mycourses_title;
                $mycourses_link->url = new \moodle_url('/my/courses.php');
                $mycourses_link->hasnotification = false;
                $mycourses_link->notificationcount = 0;
                $mycourses_link->openInNewTab = false;
                $context->customnavigation[] = $mycourses_link;
            }

            $show_calendar = get_config('theme_nhsetel', 'calendar_toggle');
            if ($show_calendar) {
                $calendar_text = get_config('theme_nhsetel', 'calendar_text');
                $calendar_title = !empty(trim($calendar_text)) ? trim($calendar_text) : "Calendar";

                $calendar_link = new \stdClass();
                $calendar_link->title = $calendar_title;
                $calendar_link->url = new \moodle_url('/calendar/view.php');
                $calendar_link->hasnotification = false;
                $calendar_link->notificationcount = 0;
                $calendar_link->openInNewTab = false;
                $context->customnavigation[] = $calendar_link;
            }
        }

        if (isset($api_admin_link)) {
            $context->customnavigation[] = $api_admin_link;
            error_log("theme_nhsetel: Appended .NET Admin link to custom navigation.");
        }

        if (has_capability('moodle/site:config', \context_system::instance())) {
            $admin_link = new \stdClass();            
            $admin_link->title = "Site administration";
            $admin_link->url = new \moodle_url('/admin/search.php'); 
            $admin_link->hasnotification = false;
            $admin_link->notificationcount = 0;
            $admin_link->openInNewTab = false; 

            $context->customnavigation[] = $admin_link;
            error_log("theme_nhsetel: Added Site Administration link to custom navigation.");
        }
        
        $this->page->requires->js_call_amd('theme_nhsetel/autosuggest', 'init');        
        
        return $context; 
    }

    public function get_footer_data(): \stdClass {
        $context = new \stdClass();

        $dotnet_base_url = get_config('theme_nhsetel', 'dotnet_base_url');
        if (!empty($dotnet_base_url) && substr($dotnet_base_url, -1) !== '/') {
            $dotnet_base_url .= '/';
        }

        $context->dotnet_base_url = $dotnet_base_url;

        return $context;

    }
    
    public function standard_head_html() {
        $output = parent::standard_head_html();

        $dotnet_base_url = get_config('theme_nhsetel', 'dotnet_base_url');

        if (!empty($dotnet_base_url)) {
            $output .= '<script>';
            $output .= 'M.cfg.dotnet_base_url = ' . json_encode($dotnet_base_url) . ';';
            $output .= '</script>';
        }

        return $output;
    }

    /**
     * Wrapper for header elements.
     *
     * @return string HTML to display the main header.
     */
    public function full_header()
    {
        return parent::full_header();
    }

    /**
     * @return array|string|string[]
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function header()
    {
        $html = parent::header();
        $navbarstyle = get_config( 'theme_nhsetel', 'navbarstyle');
        return $html;
    }

    /**
     * Returns standard main content placeholder.
     * Designed to be called in theme layout.php files.
     *
     * @return string HTML fragment.
     */
    public function main_content() {
        return '<div role="main">'.$this->unique_main_content_token.'</div>';
    }

    /**
     * @return array|string|string[]
     * @throws \dml_exception
     */
    public function footer()
    {
        $html = parent::footer();
        $html = str_replace('YYYY', date('Y'), $html);
        return $html;
    }

    public function other_info()
    {
        if (debugging(null, DEBUG_DEVELOPER) and has_capability('moodle/site:config', \context_system::instance())) {
            $layout   = $this->get_page()->pagelayout;
            $pagetype = $this->get_page()->pagetype;
            $title    = $this->page_title();

            return "<span>Page title: {$title}<span><br>
                    <span>Page layout: {$layout}</span><br>
                    <span>Page type: {$pagetype}</span>";
        } else {
            return '';
        }
    }

    /**
     * Renders the breadcrumbs
     * @return string
     * @throws moodle_exception
     */
    public function breadcrumbs()
    {
        $showcategories = true;

        $breadcrumb_cat_toggle = get_config( 'theme_nhsetel', 'bc_cats' );
        if ((($this->page->pagelayout == 'course') || ($this->page->pagelayout == 'incourse')) && ($breadcrumb_cat_toggle === 'no')) {
            $showcategories = false;
        }

        $breadcrumbs = [];
        foreach ($this->page->navbar->get_items() as $item) { 
            if ((strlen($item->text) == 1) && ($item->text[0] == ' ')) {
                continue;
            }
            if ((!$showcategories) && ($item->type == navigation_node::TYPE_CATEGORY)) {
                continue;
            }
            $item->hideicon = true;
            if (($item->text == 'Home') || ($item->text == 'Courses') || ($item->text == 'Dashboard')) { 
                continue;
            }
            if (is_object($item->action) && get_class($item->action)!='moodle_url') {
                continue;
            }
            $breadcrumbs[] = [
                'action' => $item->action,
                'text' => $item->text,
            ];
        }

        $context = new \stdClass();
        $context->breadcrumbs = $breadcrumbs;
        $context->home_url = new \moodle_url('/');

        return $this->render_from_template('theme_nhsetel/breadcrumbs', $context);
    }

    /**
     * Renders the login form.
     *
     * @param \core_auth\output\login $form The renderable.
     * @return string
     */
    public function render_login(\core_auth\output\login $form) {
        global $CFG, $SITE, $OUTPUT;

        $context = $form->export_for_template($this);

        if ($CFG->rememberusername == 0) {
            $context->cookieshelpiconformatted = $this->help_icon('cookiesenabledonlysession');
        } else {
            $context->cookieshelpiconformatted = $this->help_icon('cookiesenabled');
        }
        $context->errorformatted = $this->error_text($context->error);
        $url = $this->get_logo_url();
        if ($url) {
            $url = $url->out(false);
        }
        $context->logourl = $url;
        $context->sitename = format_string($SITE->fullname, true,
            ['context' => context_course::instance(SITEID), "escape" => false]);
        $context->login_page_toggle = (boolean) get_config( 'theme_nhsetel', 'login_page_toggle' );
        $context->oauth_login_button_icon = (boolean) get_config( 'theme_nhsetel', 'oauth_login_button_icon' );
        $context->login_expand_text = get_config( 'theme_nhsetel', 'login_expand_text');
        $context->login_header_text_default = get_config( 'theme_nhsetel', 'login_header_text_default');
        $context->login_header_text = get_config( 'theme_nhsetel', 'login_header_text');

        return $this->render_from_template('core/loginform', $context);
    }

    /**
     * @param  \preferences_groups  $renderable
     *
     * @return bool|string
     * @throws \moodle_exception
     */
    public function render_preferences_groups(\preferences_groups $renderable) {
        return $this->render_from_template('theme_nhsetel/core/preferences_groups', $renderable);
    }
}