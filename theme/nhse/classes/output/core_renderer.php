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

namespace theme_nhse\output;

use context_course;
use navigation_node;

//use block_contents;
//use custom_menu;
//use custom_menu_item;
//use html_writer;
//use moodle_url;

defined('MOODLE_INTERNAL') || die;

/**
 * Renderers to align Moodle's HTML with that expected by Bootstrap
 *
 * @package    theme_nhse
 * @copyright  NHS England
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_renderer extends \theme_boost\output\core_renderer
{

 /**
     * Returns the data context for the global theme header, fetching from API.
     * This method is called by {{# output.get_global_header_data }} in Mustache.
     *
     * @return \stdClass An object containing data for the header.
     */
    public function get_global_header_data() {
        global $PAGE; // Keep $PAGE just in case for future use/context, though not strictly needed for this API call
        global $DB, $USER; // Declare $DB and $USER as global
        global $SESSION;

        $context = new \stdClass();
        $context->customnavigation = []; // Default to empty array in case of API issues

        // Fetch token for current user
        $token = null; // Initialize to null
        $tokenNew = null; // Initialize to null
        $accesstoken  = null; // Initialize to null
        $response_content = false; // Initialize to false

        // --- NEW: Get the configured .NET application base URL ---
        $dotnet_base_url = get_config('theme_nhse', 'dotnet_base_url');

        if (!empty($dotnet_base_url) && substr($dotnet_base_url, -1) !== '/') {
            $dotnet_base_url .= '/';
        }

        // --- NEW: Add dotnet_base_url to the context for Mustache template ---
        $context->dotnet_base_url = $dotnet_base_url;
        $context->sessionKey = sesskey();

        // --- NEW: Get the configured API Base URL ---
        $api_base_url = get_config('theme_nhse', 'api_base_url');
        // Ensure the API base URL ends with a slash if it's not empty
        if (!empty($api_base_url) && substr($api_base_url, -1) !== '/') {
            $api_base_url .= '/';
        }
        
        if (empty($api_base_url)) {
            // Log an error and return early if the API URL is not configured
            error_log("theme_nhse: ERROR: LH OpenAPI Base URL is not configured in theme settings. Cannot fetch navigation data.");
            return $context; // Return empty context if API URL is missing
        }

        // --- NEW: Add login status to the context ---
        // isloggedin() is a Moodle core function that returns true if a user is logged in.
        $context->is_user_logged_in = isloggedin();

          // --- NEW: Add 'Site Administration' link if user is an admin ---
        // Check if the user has the capability to configure the site (i.e., is an admin)
        if (has_capability('moodle/site:config', \context_system::instance())) {
            $admin_link = new \stdClass();            
            $admin_link->title = "Site administration";
            $admin_link->url = new \moodle_url('/admin/search.php'); // Use the observed URL
            $admin_link->hasnotification = false;
            $admin_link->notificationcount = 0;
            $admin_link->openInNewTab = false; // Or true, if you prefer it opens in a new tab

            // Add this admin link to your customnavigation array
            // This ensures it gets rendered by your {{#customnavigation}} block in Mustache
            $context->customnavigation[] = $admin_link;
            error_log("theme_nhse: Added Site Administration link to custom navigation.");
        }


        if (isloggedin()) { // Only try to fetch token if a user is logged in
            $token = $DB->get_record('auth_oidc_token', ['username' => $USER->username]);           
            if ($token) {
                error_log("theme_nhse: Found OIDC token for user {$USER->username}.");
                // You would then use $token->accesstoken to construct your bearer token
                 $accesstoken = $token->token;
                 error_log("theme_nhse: accesstoken {$accesstoken}");

            } else {
                error_log("theme_nhse: No OIDC token found for user {$USER->username}.");
            }
        } else {
            error_log("theme_nhse: User not logged in, skipping OIDC token fetch.");
        }

        // --- The rest of your existing API call logic ---
        $api_endpoint_path = 'User/GetLHUserNavigation';
        $url = $api_base_url . $api_endpoint_path;


        
         try 
         {
            $curl = new \curl();
             // Set Authorization header
            $options = [
                'HTTPHEADER' => [
                    'Authorization: Bearer ' . $accesstoken,
                    'Accept: application/json'
                ]
            ];
            $response = $curl->get($url, null, $options);
            $result = json_decode($response, true);
             // Log the raw response and the decoded result
            error_log("theme_nhse: API Response (raw): " . $response);
            error_log("theme_nhse: API Response (decoded): " . print_r($result, true));
            // Check for JSON decoding errors
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("theme_nhse: JSON decoding error: " . json_last_error_msg());
            }
          } catch (Exception $e) 
          {
            debugging('CURL error: ' . $e->getMessage(), DEBUG_DEVELOPER);
            error_log("theme_nhse: CURL Exception caught for URL: " . $url . " Message: " . $e->getMessage());
          }

        // --- Conditional Block for Processing Response (after try-catch) ---
        // We use $result here, which will be null if there was an API error or JSON decoding issue.
        if (is_array($result) && !empty($result)) {
            // JSON decoded successfully and is an array with content
            error_log("theme_nhse: JSON decoded successfully. Processing " . count($result) . " items.");
            $processed_links = [];
            foreach ($result as $item) {
                // Check 'visible' property from API (only include if true or not set)
                // Assuming 'visible' being absent or true means it should be shown

                 // --- NEW: Skip item if title is "Sign Out" ---
                // We use trim() to handle any potential leading/trailing whitespace.
                if (isset($item['title']) && trim($item['title']) === 'Sign Out') {
                    error_log("theme_nhse: Skipping 'Sign Out' link from API response.");
                    continue; // Skip to the next item in the loop
                }


                if (!isset($item['visible']) || $item['visible'] === true) {
                    $processed_item = new \stdClass();
                    $processed_item->title = $item['title'] ?? 'Untitled'; // Default title if missing
                    // Handle URLs - convert to moodle_url if internal, keep as string if external
                    if (isset($item['url']) && !empty($item['url'])) {
                        $item_url_path = $item['url']; // Store the raw URL from the API

                        // Check if it's an absolute URL (starts with http/s or //)
                        if (strpos($item_url_path, 'http') === 0 || strpos($item_url_path, '//') === 0) {
                            $processed_item->url = $item_url_path; // Use the absolute URL as is
                            error_log("theme_nhse: Processing absolute URL: {$processed_item->url}");
                        } 
                        // If it's a relative URL AND we have a configured .NET base URL, prepend it
                        else if (!empty($dotnet_base_url)) {
                            // Prepend .NET base URL, ensuring no double slashes by trimming leading slash from item_url_path
                            $processed_item->url = $dotnet_base_url . ltrim($item_url_path, '/'); 
                            error_log("theme_nhse: Redirecting relative link '{$item_url_path}' to .NET domain: {$processed_item->url}");
                        }
                        // Fallback: If it's a relative URL but no .NET base URL is configured,
                        // treat it as a Moodle internal URL.
                        else {
                            $processed_item->url = new \moodle_url($item_url_path);
                            error_log("theme_nhse: .NET base URL not configured, processing relative link '{$item_url_path}' as Moodle internal.");
                        }
                    } else {
                        // Default to Moodle home if URL is missing or empty
                        $processed_item->url = new \moodle_url('/');
                        error_log("theme_nhse: Item has empty or missing URL, defaulting to Moodle home.");
                    }
                    $processed_item->hasnotification = $item['hasNotification'] ?? false;
                    $processed_item->notificationcount = $item['notificationCount'] ?? 0;
                    $processed_item->openInNewTab = $item['openInNewTab'] ?? false; 

                    $processed_links[] = $processed_item;
                } else {
                    // Log items that are not visible
                    error_log("theme_nhse: Item not visible and filtered out: " . ($item['title'] ?? 'N/A') . " (visible: " . ($item['visible'] ? 'true' : 'false') . ")");
                }
            }

              // Add API processed links AFTER the static admin link, so it appears first if you want.
            // If you want admin link to appear last, change this to array_unshift or prepend it.
            $context->customnavigation = array_merge($context->customnavigation, $processed_links);
            //$context->customnavigation = array_unshift($context->customnavigation, $processed_links);

            error_log("theme_nhse: Processed links for display: " . print_r($context->customnavigation, true));

       
            // Log the final processed links (for debugging)
            error_log("theme_nhse: Processed links for display: " . print_r($processed_links, true));
        } else {
            // This block handles cases where:
            // 1. $result is null (due to JSON decoding error or CURL exception)
            // 2. $result is not an array (e.g., API returned a non-array JSON like a string or object)
            // 3. $result is an empty array
            $json_error_msg = (json_last_error() !== JSON_ERROR_NONE) ? json_last_error_msg() : 'N/A';
            error_log("theme_nhse: Failed to process API response. Response was empty, not an array, or an error occurred. JSON Error: " . $json_error_msg);
            error_log("theme_nhse: Raw API response (if available): " . ($response ?: 'No response content'));
        }
        
        return $context; 
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
        $navbarstyle = get_config( 'theme_nhse', 'navbarstyle');
//        if ($navbarstyle) {
//            $html = str_replace('nhsuk-header--default', 'nhsuk-header__' . $navbarstyle, $html);
//            $html = str_replace('navbar__default', 'navbar__light', $html);
//        }
//        $html = str_replace('nhsuk-header--default', 'nhsuk-header--white', $html);
//        $html = str_replace('navbar__default', 'navbar__light', $html);

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

        // Activate only if we want white style footer
        //$navbarstyle = get_config( 'theme_nhse', 'navbarstyle');
        //$navbarstyle = 'white';
        //if ($navbarstyle) {
        //    $html = str_replace('nhsuk-header--default', 'nhsuk-header--' . $navbarstyle, $html);
        //}
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

        $breadcrumb_cat_toggle = get_config( 'theme_nhse', 'bc_cats' );
        if ((($this->page->pagelayout == 'course') || ($this->page->pagelayout == 'incourse')) && ($breadcrumb_cat_toggle === 'no')) {
            $showcategories = false;
        }

        $breadcrumbs = [];
        foreach ($this->page->navbar->get_items() as $item) { //loop through each item in the cascade
            // Test for single space hide section name trick.
            if ((strlen($item->text) == 1) && ($item->text[0] == ' ')) {
                continue;
            }
            if ((!$showcategories) && ($item->type == navigation_node::TYPE_CATEGORY)) {
                continue;
            }
            $item->hideicon = true;
            if (($item->text == 'Home') || ($item->text == 'Courses') || ($item->text == 'Dashboard')) { // remove superfluous links in string
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

        return $this->render_from_template('theme_nhse/breadcrumbs', $context);
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

        // Override because rendering is not supported in template yet.
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
        $context->login_page_toggle = (boolean) get_config( 'theme_nhse', 'login_page_toggle' );
        $context->oauth_login_button_icon = (boolean) get_config( 'theme_nhse', 'oauth_login_button_icon' );
        $context->login_expand_text = get_config( 'theme_nhse', 'login_expand_text');
        $context->login_header_text_default = get_config( 'theme_nhse', 'login_header_text_default');
        $context->login_header_text = get_config( 'theme_nhse', 'login_header_text');

        return $this->render_from_template('core/loginform', $context);
    }

    /**
     * @param  \preferences_groups  $renderable
     *
     * @return bool|string
     * @throws \moodle_exception
     */
    public function render_preferences_groups(\preferences_groups $renderable) {
        return $this->render_from_template('theme_nhse/core/preferences_groups', $renderable);
    }
}
