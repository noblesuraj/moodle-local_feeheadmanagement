<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * This page is to get details of feehead on a form 
 * and to process and save the data.
 */

require_once('../../config.php');
require_once('lib.php');
require_once('addfeehead_form.php');
require_once('programmanagement/lib.php');

$fid = optional_param('fid', '', PARAM_INT);
$feeheadid = optional_param('feeheadid', '', PARAM_ALPHANUM);
require_login();
$feeheaddata = new stdClass();
$orgid = '';
// fetches fee head data as per the feeheadid
if (!empty($feeheadid)) {
    $feeheaddata = $DB->get_record('fee_head', array('id' => $feeheadid));
    $orgid = $feeheaddata->organization;
    if (!empty($feeheaddata)) {
        if ($feeheaddata->refundable) {
            $feeheaddata->refundable = 'yes';
        } else {
            $feeheaddata->refundable = 'no';
        }
    }
    $feecat = $DB->get_record('fee_category', array('id' => $feeheaddata->feecategory));
    $org = $DB->get_record('course_categories', array('id' => $feecat->organization));
    $feeheaddata->feecategory = $feecat->name;
    $feeheaddata->organization = $org->name;
} else if (empty($feeheadid) && !empty($fid)) {
    // fetches data from fee category id : fid
    $feecat = $DB->get_record('fee_category', array('id' => $fid));
    $org = $DB->get_record('course_categories', array('id' => $feecat->organization));
    $orgid = $org->id;
    $feeheaddata->refundable = 'no';
    $feeheaddata->feecategory = $feecat->name;
    $feeheaddata->organization = $org->name;
}//ends here
// page parameters
$pageparams = array();
if (!empty($fid)) {
    $pageparams['fid'] = $fid;
}
$context = context_system::instance();
$PAGE->set_context($context);
$returnurl = new moodle_url($CFG->wwwroot . '/local/feeheadmanagement/feehead.php', $pageparams);
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/feeheadmanagement/addfeehead.php', $pageparams);
$PAGE->requires->js('/local/feeheadmanagement/commonvalidation.js', true);
if (!has_capability_in_organization('local/feeheadmanagement:managefeeheads', $orgid)) {
    print_error('accessdenied', 'admin');
}
// First create the form.
$editform = new addfeehead_form(NULL, array('data' => $feeheaddata));
if ($editform->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $editform->get_data()) {
    $data->refundable = isset($data->refundable) ? 1 : 0;
    $data->feecategory = $fid;
    $data->organization = $feecat->organization;
    if (empty($feeheadid)) {
        $id = create_feehead($data); // creates fee head from data
        $event = \local_feeheadmanagement\event\feehead_added::create(
                        array('context' => $context,
                            'objectid' => $id));
        $event->trigger();
    } else {
        update_feehead($feeheadid, $data); // updates fee head data
        $event = \local_feeheadmanagement\event\feehead_updated::create(
                        array('context' => $context,
                            'objectid' => $feeheadid));
        $event->trigger();
    }
    // Redirect user to newly created/updated list.
    redirect($returnurl, get_string('success', 'local_feeheadmanagement'));
}
$title =get_string('addfeehead','local_feeheadmanagement');
$PAGE->navbar->add($title);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->requires->js('/local/feeheadmanagement/commonvalidation.js');
echo $OUTPUT->header();
echo $OUTPUT->heading($title);

$editform->display();

echo $OUTPUT->footer();

