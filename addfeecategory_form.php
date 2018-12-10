<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * This is moodle mform page to create a form 
 * that will be used on addfeecategory.php while adding/updating 
 * feecategory detail.
 */

require_once $CFG->libdir . '/formslib.php';
require_once('lib.php');
require_once($CFG->libdir . '/coursecatlib.php');
require_once('programmanagement/lib.php');

class addfeecategory_form extends moodleform {

    /**
     * Form definition.
     */
    function definition() {
        global $CFG, $PAGE, $DB;

        $mform = $this->_form;
        // Form definition 
        $category_data = $this->_customdata['data'];
        $categories = get_all_organization_cat('local/feeheadmanagement:managefeecategory');
        $categories = array('' => 'Select Organization') + $categories;
        $mform->addElement('select', 'organization', get_string('organization', 'local_feeheadmanagement'), $categories);
        $mform->addRule('organization', get_string('organization', 'local_feeheadmanagement'), 'required', null, 'client');
        $mform->addElement('text', 'name', get_string('feecategoryname', 'local_feeheadmanagement'), 'maxlength="254" size="50"');
        $mform->addRule('name', get_string('feecategoryname', 'local_feeheadmanagement'), 'required', null, 'client');
        $mform->setType('name', PARAM_TEXT);
        $mform->addElement('text', 'short_name', get_string('shortname', 'local_feeheadmanagement'), 'maxlength="254" size="50"');
        $mform->addRule('short_name', get_string('shortname', 'local_feeheadmanagement'), 'required', null, 'client');
        $mform->setType('short_name', PARAM_TEXT);
        $mform->addElement('textarea', 'description', get_string('description', 'local_feeheadmanagement'), 'wrap="virtual" rows="5" cols="50"');
        $mform->setType('description', PARAM_TEXT);
        $this->add_action_buttons(True);

        $mform->addElement('hidden', 'id', null);
        $mform->setType('id', PARAM_INT);
        // Finally set the current form data
        $this->set_data($category_data);
    }
   /* Validates data for duplicate category and short name and empty organization
    * @param data object
    * @param files object
    * @return errors array
    */
    function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);
        if (empty($data['name'])) {
            $errors['name'] = get_string('feecategoryname', 'local_feeheadmanagement') . ' cannot be empty';
        } else {
            if (!empty($data['organization'])) {
                $category_name = strtolower(trim($data['name']));
                $categorysnames_array = $DB->get_records_sql('select * from {fee_category} where organization = ' . $data['organization'].' and id != '.$data['id'].' and deleted = 0');
                foreach ($categorysnames_array as $category_val) {
                    $db_categoryname = strtolower(trim($category_val->name));
                    if ($db_categoryname === $category_name) {
                        $errors['name'] = get_string('feecategoryalreadyexists','local_feeheadmanagement');
                        break;
                    }
                }
            }
        }
        if (empty($data['short_name'])) {
            $errors['short_name'] = get_string('shortname', 'local_feeheadmanagement') . ' cannot be empty';
        } else {
                $shortcategory_name = strtolower(trim($data['short_name']));
                $shortcategorysnames_array = $DB->get_records_sql('select * from {fee_category} where id != '.$data['id'].' and deleted = 0');
                foreach ($shortcategorysnames_array as $shortcategory_val) {
                    $db_shortcategoryname = strtolower(trim($shortcategory_val->short_name));
                    if ($db_shortcategoryname === $shortcategory_name) {
                        $errors['short_name'] = get_string('shortnamealreadyexists','local_feeheadmanagement');
                        break;
                    }
                }
        }
        if (empty($data['organization']) || $data['organization'] == 0) {
            $errors['organization'] = 'Please select ' . get_string('organization', 'local_feeheadmanagement');
        }
        return $errors;
    }

}
