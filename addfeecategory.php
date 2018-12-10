<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * This page is to get details of feecategory on a form 
 * and to process and save the data.
 */

require_once('../../config.php');
require_once('lib.php');
require_once('addfeecategory_form.php');
require_once('programmanagement/lib.php');

$id = optional_param('id', '', PARAM_ALPHANUM);
require_login();
$feecatdata = '';
$org = '';
if (!empty($id)) {
    $feecatdata = $DB->get_record('fee_category', array('id' => $id));
    $org = $feecatdata->organization;
}
$context = context_system::instance();
$PAGE->set_context($context);
$returnurl = new moodle_url($CFG->wwwroot . '/local/feeheadmanagement/feecategory.php');
$PAGE->set_pagelayout('admin');
$pageparams = array();

$PAGE->set_url('/local/feeheadmanagement/addfeecategory.php', $pageparams);
//checks for capability to manage fee category
if (!has_capability_in_organization('local/feeheadmanagement:managefeecategory', $org)) {
    print_error('accessdenied', 'admin');
}
// First create the form.
$editform = new addfeecategory_form(NULL, array('data' => $feecatdata));
if ($editform->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $editform->get_data()) {
    if (empty($data->description)) {
        $data->description = '';
    }
    if (empty($feecatdata->id)) {
        $id = create_category($data); // creates a new category
        $event = \local_feeheadmanagement\event\feecategory_added::create(
                        array('context' => $context,
                            'objectid' => $id));
        $event->trigger();
    } else {
        update_category($feecatdata->id, $data); // updates fee category and event for update is triggered
        $event = \local_feeheadmanagement\event\feecategory_updated::create(
                        array('context' => $context,
                            'objectid' => $feecatdata->id));
        $event->trigger();
    }
    // Redirect user to newly created/updated list.
    redirect($returnurl, get_string('success', 'local_feeheadmanagement'));
}
$title = get_string('addfeecategory', 'local_feeheadmanagement');
$PAGE->navbar->add($title);
$PAGE->set_title($title);
$PAGE->set_heading($title);
echo $OUTPUT->header();
echo $OUTPUT->heading($title);

$editform->display();

echo $OUTPUT->footer();

