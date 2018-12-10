<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * This is moodle mform page to create a form 
 * that will be used on addfeecategory.php while adding/updating 
 * feehead detail.
 */

require_once $CFG->libdir . '/formslib.php';
require_once('lib.php');
require_once('programmanagement/lib.php');

class addfeehead_form extends moodleform {

    /**
     * Form definition.
     */
    function definition() {
        global $CFG, $PAGE, $DB;

        $mform = $this->_form;
        // Form definition 
        $feehead_data = $this->_customdata['data'];
        if ($feehead_data->refundable == 'no') {
            $feehead_data->refundable = '';
        }
        $feeheadid = optional_param('feeheadid', '', PARAM_ALPHANUM);
        $feecatid = optional_param('fid', '', PARAM_ALPHANUM);
        if (!empty($feeheadid)) {
            $feecat = $DB->get_record('fee_head', array('id' => $feeheadid,'deleted' => 0));
        } else if (!empty($feecatid) && empty($feeheadid)) {
            $feecat = $DB->get_record('fee_category', array('id' => $feecatid,'deleted' => 0));
        }
        if (!empty($feeheadid)) {
            $feecategory = $DB->get_record('fee_category', array('id' => $feecat->feecategory,'deleted' => 0));
        } else if (!empty($feecatid) && empty($feeheadid)) {
            $feecategory = $DB->get_record('fee_category', array('id' => $feecatid,'deleted' => 0));
        }
        $mform->addElement('static', 'organization', get_string('organization', 'local_feeheadmanagement'));
        $mform->addElement('static', 'feecategory', get_string('feecategoryname', 'local_feeheadmanagement'));
        $mform->addElement('text', 'name', get_string('feeheadname', 'local_feeheadmanagement'), 'maxlength="254" size="50"');
        $mform->addRule('name', get_string('feeheadname', 'local_feeheadmanagement'), 'required');
        $mform->setType('name', PARAM_TEXT);
        $mform->addElement('text', 'short_name', get_string('shortname', 'local_feeheadmanagement'), 'maxlength="254" size="50"');
        $mform->addRule('short_name', get_string('shortname', 'local_feeheadmanagement'), 'required');
        $mform->setType('short_name', PARAM_TEXT);
        $mform->addElement('textarea', 'description', get_string('description', 'local_feeheadmanagement'), 'wrap="virtual" rows="20" cols="50"');
        $mform->setType('description', PARAM_TEXT);
        $mform->addElement('text', 'defaultamount', get_string('defaultamount', 'local_feeheadmanagement'), 'maxlength="254" size="50" class="amount"');
        $mform->addRule('defaultamount', get_string('defaultamount', 'local_feeheadmanagement'), 'required');
        $mform->setType('defaultamount', PARAM_FLOAT);
        $mform->addElement('checkbox', 'refundable', get_string('refundable', 'local_feeheadmanagement'));
        $this->add_action_buttons(True);
        $mform->addElement('hidden', 'fid', $feecategory->id);
        $mform->setType('fid', PARAM_INT);
        $mform->addElement('hidden', 'feeheadid', $feeheadid);
        $mform->setType('feeheadid', PARAM_INT);
        // Finally set the current form data
        $this->set_data($feehead_data);
    }
   /* Validates data for duplicate fee head and short name and empty/non numeric default amount
    * @param data object
    * @param files object
    * @return errors array
    */
    function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);
        if (empty($data['name'])) {
            $errors['name'] = get_string('feeheadname', 'local_feeheadmanagement') . ' cannot be empty';
        } else {
            $feeheadname = strtolower(trim($data['name']));
            $feeheadnames_array = $DB->get_records_sql('select * from {fee_head} where feecategory = ' . $data['fid'] . ' and id != ' . $data['feeheadid'].' and deleted = 0');
            foreach ($feeheadnames_array as $feehead) {
                $db_feeheadname = strtolower(trim($feehead->name));
                if ($db_feeheadname === $feeheadname) {
                    $errors['name'] = get_string('feeheadalreadyexists', 'local_feeheadmanagement');
                }
            }
        }
        if (empty($data['short_name'])) {
            $errors['short_name'] = get_string('shortname', 'local_feeheadmanagement') . ' cannot be empty';
        } else {
            $shortfeeheadname = strtolower(trim($data['short_name']));
            $shortfeeheadnames_array = $DB->get_records_sql('select * from {fee_head} where feecategory = ' . $data['fid'] . ' and id != ' . $data['feeheadid'].' and deleted = 0');
            foreach ($shortfeeheadnames_array as $shortfeehead) {
                $db_shortfeeheadname = strtolower(trim($shortfeehead->short_name));
                if ($db_shortfeeheadname === $shortfeeheadname) {
                    $errors['short_name'] = get_string('shortnamealreadyexists', 'local_feeheadmanagement');
                }
            }
        }

        if (empty($data['defaultamount'])) {
            $errors['defaultamount'] = get_string('defaultamount', 'local_feeheadmanagement') . get_string('cannotbeempty', 'local_feeheadmanagement');
        }
        if (!is_numeric($data['defaultamount'])) {
            $errors['defaultamount'] = get_string('defaultamount', 'local_feeheadmanagement') . get_string('numericvalueonly', 'local_feeheadmanagement');
        }else if($data['defaultamount'] < 0){
            $errors['defaultamount'] = get_string('positiveonly', 'local_feeheadmanagement');
        }
        return $errors;
    }

}
