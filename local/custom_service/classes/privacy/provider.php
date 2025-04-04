<?php
namespace custom_service\privacy;
 
defined('MOODLE_INTERNAL') || die;
 
use core_privacy\local\metadata\collection;
 
class provider implements  \core_privacy\local\metadata\provider {
    /**
     * Returns metadata about this plugin’s data handling.
     * Since this plugin does not store any user data, it returns an empty collection.
     *
     * @param collection $collection The metadata collection.
     * @return collection The updated metadata collection.
     */
    public static function get_metadata(collection $collection) : collection {
        return $collection;
    }
}