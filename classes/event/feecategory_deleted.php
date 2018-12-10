<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


/**
 * Fee Category Add Event
 *
 * @package    local_feeheadmanagement
 * @copyright  2016 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_feeheadmanagement\event;
defined('MOODLE_INTERNAL') || die();

/**
 * Cohort updated event class.
 *
 * @package    core
 * @since      Moodle 2.6
 * @copyright  2013 Dan Poltawski <dan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class feecategory_deleted extends \core\event\base {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'fee_category';
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('deletefeecategory', 'local_feeheadmanagement');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' deleted the fee category with id '$this->objectid'.";
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/local/feeheadmanagement/feecategory.php', array('id' => $this->objectid));
    }

    /**
     * Return legacy event name.
     *
     * @return string legacy event name.
     */
    public static function get_legacy_eventname() {
        return 'Fee category deleted';
    }

    /**
     * Return legacy event data.
     *
     * @return \stdClass
     */
    protected function get_legacy_eventdata() {
        return $this->get_record_snapshot('fee_category', $this->objectid);
    }
}
