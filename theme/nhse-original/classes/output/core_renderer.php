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
