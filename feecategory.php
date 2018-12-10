<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * This is main page of feecategory management (add/edit/delete) 
 * Listing of feecategories, filter  
 */

require_once('../../config.php');
require_once('lib.php');
require_once('programmanagement/lib.php');
require_login();
$delete = optional_param('delete', 0, PARAM_INT);
$confirm = optional_param('confirm', '', PARAM_ALPHANUM);   //md5 confirmation hash
$dir = optional_param('dir', 'ASC', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 10, PARAM_INT);        // how many per page
$feecategoryfilter = optional_param('feecategory', '', PARAM_TEXT);
$shortnamefilter = optional_param('short_name', '', PARAM_TEXT);
$removefilter = optional_param('removefilter', '', PARAM_ALPHANUM);
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$pageparams = array();
$PAGE->set_url('/local/feeheadmanagement/feecategory.php');
$returnurl = new moodle_url($CFG->wwwroot . '/local/feeheadmanagement/feecategory.php');
$organizationfilter = optional_param('organization', '', PARAM_ALPHANUMEXT);

//get data from session's programmanagement filtering
//define array of variable's name that are being used for filter parameters
$filtering = new programmanagement_filtering('feecategorylist');
$filterobjeject = new stdClass();
if ($data = data_submitted()) {

    if (isset($data->removefilter)) {
        $filtering->reset_programlist_filtering();
        redirect($returnurl);
    }

    if (isset($data->submitbutton)) {
        $param_var_names = array('feecategoryfilter', 'shortnamefilter', 'removefilter', 'organizationfilter');
        foreach ($param_var_names as $varname) {
            $filterobjeject->$varname = $$varname;
        }
        $filtering->set_programlist_filtering($filterobjeject);
    }
}

$filterobjeject = $filtering->get_programlist_filtering();
if ($filterobjeject) {
    foreach ($filterobjeject as $varname => $value) {
        $$varname = $filterobjeject->$varname;
    }
}
//---------------------

$organization_list = array();
if ((has_capability_in_organization('local/feeheadmanagement:managefeecategory', $organizationfilter) && has_capability_in_organization('local/feeheadmanagement:viewfeecategorylist', $organizationfilter)) || (has_capability_in_organization('local/feeheadmanagement:managefeecategory', $organizationfilter))) {
    $organization_list = get_all_organization_cat('local/feeheadmanagement:managefeecategory');
    $organization_idlist = array_keys($organization_list);
} else if (has_capability_in_organization('local/feeheadmanagement:viewfeecategorylist', $organizationfilter)) {
    $organization_list = get_all_organization_cat('local/feeheadmanagement:viewfeecategorylist');
    $organization_idlist = array_keys($organization_list);
} else {
    print_error('accessdenied', 'admin');
}
if ($removefilter) {
    //remove filter : redirect to page url
    redirect($PAGE->url, array());
}

if ($organizationfilter) {
    $context = context_coursecat::instance($organizationfilter);
}
// delete fee category and its fee head
if ($delete and confirm_sesskey()) {              // Delete a selected fee category, after confirmation
    $category = $DB->get_record('fee_category', array('id' => $delete,'deleted' => 0), '*', MUST_EXIST);

    if ($confirm != md5($delete)) {
        echo $OUTPUT->header();

        echo $OUTPUT->heading(get_string('deletecategory', 'local_feeheadmanagement'));
        $optionsyes = array('delete' => $delete, 'confirm' => md5($delete), 'sesskey' => sesskey());
        echo $OUTPUT->confirm(get_string('deletecheckfull', '', " Fee Category - '$category->name'"), new moodle_url($returnurl, $optionsyes), $returnurl);
        echo $OUTPUT->footer();
        die;
    } else if (data_submitted()) {
        // Purge user preferences.
        $category->deleted = 1;
        $DB->update_record('fee_category', $category);
        $feehead_info = $DB->get_records('fee_head', array('feecategory' => $category->id,'deleted' => 0));
        foreach ($feehead_info as $feehead) {
            $feehead->deleted = 1;
            $DB->update_record('fee_head', $feehead);
        }
        $event = \local_feeheadmanagement\event\feecategory_deleted::create(
                        array('context' => $context,
                            'objectid' => $category->id));
        $event->trigger();
        redirect($returnurl, get_string('successdelete', 'local_feeheadmanagement'));
    }
}//ends here

$table = new html_table();
$table->head = array();
$table->colclasses = array();
$table->attributes['class'] = 'admintable generaltable';

$table->head[] = get_string('sno', 'local_feeheadmanagement');
$table->head[] = get_string('feecategory', 'local_feeheadmanagement');
$table->head[] = get_string('shortname', 'local_feeheadmanagement');
$table->head[] = get_string('organization', 'local_feeheadmanagement');
$table->head[] = get_string('description', 'local_feeheadmanagement');
if (has_capability('local/feeheadmanagement:managefeecategory', $context)) {
    $table->head[] = get_string('actions', 'local_feeheadmanagement');
}
$table->colclasses[] = 'centeralign';
$table->colclasses[] = 'centeralign';


if (empty($CFG->loginhttps)) {
    $securewwwroot = $CFG->wwwroot;
} else {
    $securewwwroot = str_replace('http:', 'https:', $CFG->wwwroot);
}

$stredit = get_string('edit');
$strdelete = get_string('delete');
$strmanage = get_string('managehead', 'local_feeheadmanagement');
$filter_params = array();
// filter parameters for query
if ($feecategoryfilter != '') {
    $filter_params['name'] = $feecategoryfilter;
}
if ($shortnamefilter != '') {
    $filter_params['short_name'] = $shortnamefilter;
}
if ($organizationfilter != '') {
    $filter_params['organization'] = $organizationfilter;
}
if (!empty($organization_idlist)) {
    $filter_params['list'] = implode(',', $organization_idlist);
}
// ends her
// page parameters 
if ($feecategoryfilter != '') {
    $pageparams['name'] = $feecategoryfilter;
}
if ($shortnamefilter != '') {
    $pageparams['short_name'] = $shortnamefilter;
}
if ($organizationfilter != '') {
    $pageparams['organization'] = $organizationfilter;
}
//ends here
$url = new moodle_url('/local/feeheadmanagement/feecategory.php', $pageparams);
$feecategory_list = get_feecategory_listing($filter_params, $page, $perpage); // gets fee category listing
$feecategorycount = get_feecategory_counts($filter_params); // gets count of fee categories
$i = 1;
$flag = 0;
if (!empty($feecategory_list)) {
    foreach ($feecategory_list as $feecategory) {
        $row = array();
        $buttons = array();
        if ($page != 0) {
            $row[] = $i + $perpage * $page;
        } else {
            $row[] = $i;
        }
        $orgname = $DB->get_record('course_categories', array('id' => $feecategory->organization), 'name');
        $row[] = $feecategory->name;
        $row[] = $feecategory->short_name ? $feecategory->short_name : '';
        $row[] = $orgname->name;
        $row[] = $feecategory->description;
        //edit , delete and manage head links
        if (has_capability_in_organization('local/feeheadmanagement:managefeecategory')) {
            $buttons[] = html_writer:: link(new moodle_url($securewwwroot . '/local/feeheadmanagement/addfeecategory.php', array('id' => $feecategory->id)), $OUTPUT->pix_icon('t/edit',$stredit), array('title' => $stredit));
            $buttons[] = html_writer:: link(new moodle_url($securewwwroot . '/local/feeheadmanagement/feecategory.php', array('delete' => $feecategory->id, 'sesskey' => sesskey())), $OUTPUT->pix_icon('t/delete', $strdelete), array('title' => $strdelete));
            $buttons[] = html_writer::link(new moodle_url($securewwwroot . '/local/feeheadmanagement/feehead.php', array('fid' => $feecategory->id, 'feecategory' => $feecategoryfilter, 'feecat_short_name' => $shortnamefilter)), $strmanage);
            $row[] = implode(' ', $buttons);
        }
        $i++;
        $table->data[] = $row;
    }
} else {
    //if data not found
    $row = array();
    $newrow = new html_table_row();
    $mycell = new html_table_cell();
    $mycell->text = get_string('datanotfound','local_feeheadmanagement');
    $mycell->colspan = count($table->head);
    $newrow->cells[] = $mycell;
    $rows[] = $newrow;
    $table->data = $rows;
}


$title = get_string('feecategorylist','local_feeheadmanagement');
$PAGE->navbar->add($title);
$PAGE->set_title($title);
$PAGE->set_heading($title);
echo $OUTPUT->header();
echo $OUTPUT->heading($title);

if (!empty($table)) {
    // form starts here
    $formcontent = html_writer::start_tag('div', array('class' => 'feecategory-list'));
    $formcontent .= html_writer::start_tag('form', array('action' => new moodle_url('/local/feeheadmanagement/feecategory.php'),
                'class' => 'form-inline', 'method' => 'post'));
    $formcontent .= html_writer::start_tag('div', array('class' => 'add-feecategory-btn'));
    // add category link
    $formcontent .= html_writer::start_tag('div', array('class' => 'btn-group'));
    $addfeecat = get_string('addfeecategory', 'local_feeheadmanagement');

    $formcontent .= html_writer:: link(new moodle_url($CFG->wwwroot . '/local/feeheadmanagement/addfeecategory.php'), $addfeecat, array('class' => 'btn btn-success'));

    $formcontent .= html_writer::end_tag('div');
    $formcontent .= html_writer::end_tag('div');
    //filters for the list
    $formcontent .= html_writer::start_tag('label');
    $formcontent .= get_string('feecategory','local_feeheadmanagement').' ';
    $formcontent .= html_writer::empty_tag('input', array('type' => 'text', 'name' => 'feecategory', 'value' => $feecategoryfilter));
    $formcontent .= html_writer::end_tag('label');

    $formcontent .= get_string('shortname','local_feeheadmanagement').' ';
    $formcontent .= html_writer::empty_tag('input', array('type' => 'text', 'name' => 'short_name', 'value' => $shortnamefilter));

    $formcontent .= html_writer::start_tag('label');
    $formcontent .= get_string('organization','local_feeheadmanagement').' ';
    $formcontent .= html_writer::select($organization_list, 'organization', $organizationfilter, array('' => 'Choose Organization'));
    $formcontent .= html_writer::end_tag('label');

    $formcontent .= html_writer::tag('input', '', array('type' => 'submit', 'name' => 'submitbutton', 'class' => 'btn btn-default', 'value' => 'Search'));
    $formcontent .= html_writer::tag('input', '', array('type' => 'submit', 'name' => 'removefilter', 'class' => 'submitbutton', 'value' => 'Reset Filter'));
    //ends here
    $formcontent .= html_writer::start_tag('div', array('class' => 'feecategory-wrapper'));
    $formcontent .= html_writer::table($table);
    $formcontent .= html_writer:: end_tag('div');
    // form ends here
    $formcontent .= html_writer::end_tag('form');
    $formcontent .= html_writer:: end_tag('div');
    echo '<div>';
    echo $formcontent;
    echo '</div>';
    echo $OUTPUT->paging_bar($feecategorycount, $page, $perpage, $url);
}


echo $OUTPUT->footer();
