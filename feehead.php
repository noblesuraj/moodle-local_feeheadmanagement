<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * This is main page of feehead management (add/edit/delete) 
 * Listing of feeheads of specific feecategory of specific organization
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
$feeheadfilter = optional_param('feehead', '', PARAM_TEXT);
$feecategoryfilter = optional_param('feecategory', '', PARAM_TEXT);
$feecat_short_name = optional_param('feecat_short_name', '', PARAM_TEXT);
$shortnamefilter = optional_param('short_name', '', PARAM_TEXT);
$removefilter = optional_param('removefilter', '', PARAM_ALPHANUM);
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$fid = required_param('fid', PARAM_ALPHANUM);
// page parameters
$pageparams = array();
if (!empty($fid)) {
    $pageparams['fid'] = $fid;
}
if (!empty($shortnamefilter)) {
    $pageparams['short_name'] = $shortnamefilter;
}//ends here
$PAGE->set_url('/local/feeheadmanagement/feehead.php');
$returnurl = new moodle_url($CFG->wwwroot . '/local/feeheadmanagement/feehead.php', $pageparams);
$organizationfilter = optional_param('organization', '', PARAM_ALPHANUMEXT);
// validate capability to access this page else print error access denied
$organization_list = array();
if ((has_capability_in_organization('local/feeheadmanagement:managefeeheads', $organizationfilter) && has_capability_in_organization('local/feeheadmanagement:viewfeeheadlist', $organizationfilter)) || (has_capability_in_organization('local/feeheadmanagement:managefeeheads', $organizationfilter))) {
    $organization_list = get_all_organization_cat('local/feeheadmanagement:managefeeheads');
    $organization_idlist = array_keys($organization_list);
} else if (has_capability_in_organization('local/feeheadmanagement:viewfeeheadlist', $organizationfilter)) {
    $organization_list = get_all_organization_cat('local/feeheadmanagement:viewfeeheadlist');
    $organization_idlist = array_keys($organization_list);
} else {
    print_error('accessdenied', 'admin');
}// ends here
if ($removefilter) {
    redirect($PAGE->url . '?fid=' . $fid);
}
if ($organizationfilter) {
    $context = context_coursecat::instance($organizationfilter);
}
// delete fee head
if ($delete and confirm_sesskey()) {              // Delete a selected fee head, after confirmation
    $feehead_info = $DB->get_record('fee_head', array('id' => $delete,'deleted' => 0), '*', MUST_EXIST);

    if ($confirm != md5($delete)) {
        echo $OUTPUT->header();

        echo $OUTPUT->heading(get_string('deletefeehead', 'local_feeheadmanagement'));
        $optionsyes = array('delete' => $delete, 'confirm' => md5($delete), 'sesskey' => sesskey());
        echo $OUTPUT->confirm(get_string('deletecheckfull', '', " Fee Head - '$feehead_info->name'"), new moodle_url($returnurl, $optionsyes), $returnurl);
        echo $OUTPUT->footer();
        die;
    } else if (data_submitted()) {
        // Purge user preferences.
        $feehead_info->deleted = 1;
        $DB->update_record('fee_head', $feehead_info);
        $event = \local_feeheadmanagement\event\feehead_deleted::create(
                        array('context' => $context,
                            'objectid' => $feehead_info->id));
        $event->trigger();
        redirect($returnurl, get_string('successdelete', 'local_feeheadmanagement'));
    }
}//ends here

$table = new html_table();
$table->head = array();
$table->colclasses = array();
$table->attributes['class'] = 'admintable generaltable';

$table->head[] = get_string('sno', 'local_feeheadmanagement');
$table->head[] = get_string('feeheadname', 'local_feeheadmanagement');
$table->head[] = get_string('shortname', 'local_feeheadmanagement');
$table->head[] = get_string('organization', 'local_feeheadmanagement');
$table->head[] = get_string('defaultamount', 'local_feeheadmanagement');
$table->head[] = get_string('refundable', 'local_feeheadmanagement');
$table->head[] = get_string('description', 'local_feeheadmanagement');
if (has_capability('local/feeheadmanagement:managefeeheads', $context)) {
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
// filter parameters for the query
$filter_params = array();
if ($feeheadfilter != '') {
    $filter_params['name'] = $feeheadfilter;
}
if ($shortnamefilter != '') {
    $filter_params['short_name'] = $shortnamefilter;
}
if (!empty($fid)) {
    $filter_params['feecategory'] = $fid;
}
if (!empty($organization_idlist)) {
    $filter_params['list'] = implode(',', $organization_idlist);
}//ends here
$feehead_list = get_feehead_listing($filter_params, $page, $perpage); // gets fee head list
$feeheadcount = get_feehead_counts($filter_params); // gets count of fee heads
$i = 1;
$flag = 0;
if (!empty($feehead_list)) {
    foreach ($feehead_list as $feehead) {
        $orgname = $DB->get_record('course_categories', array('id' => $feehead->organization), 'name');
        if (!empty($orgname)) {
            $row = array();
            $buttons = array();
            if ($page != 0) {
                $row[] = $i + $perpage * $page;
            } else {
                $row[] = $i;
            }
            $row[] = $feehead->name;
            $row[] = $feehead->short_name ? $feehead->short_name : '';
            $row[] = $orgname->name;
            $row[] = $feehead->defaultamount;
            if ($feehead->refundable) {
                $row[] = 'Yes';
            } else {
                $row[] = 'No';
            }
            $row[] = $feehead->description;
            // link to edit and delete the fee head
            if (has_capability_in_organization('local/feeheadmanagement:managefeeheads')) {
                $buttons[] = html_writer::link(new moodle_url($securewwwroot . '/local/feeheadmanagement/addfeehead.php', array('feeheadid' => $feehead->id, 'fid' => $feehead->feecategory, 'id' => $feehead->feecategory)), $OUTPUT->pix_icon('t/edit', $stredit), array('title' => $stredit));
                $buttons[] = html_writer::link(new moodle_url($securewwwroot . '/local/feeheadmanagement/feehead.php', array('delete' => $feehead->id, 'sesskey' => sesskey(), 'fid' => $fid)), $OUTPUT->pix_icon('t/delete', $strdelete), array('title' => $strdelete));
                $row[] = implode(' ', $buttons);
            }
            $i++;
            $table->data[] = $row;
        }
    }
} else {
    // if data not found
    $row = array();
    $newrow = new html_table_row();
    $mycell = new html_table_cell();
    $mycell->text = get_string('datanotfound','local_feeheadmanagement');
    $mycell->colspan = count($table->head);
    $newrow->cells[] = $mycell;
    $rows[] = $newrow;
    $table->data = $rows;
}

if (!empty($fid)) {
    $feecat_name = $DB->get_record('fee_category', array('id' => $fid,'deleted' => 0), 'organization,name');
    $org_data = $DB->get_record('course_categories', array('id' => $feecat_name->organization), 'name');
}
$title = get_string('feeheadlist','local_feeheadmanagement');
$title_cat = get_string('feeheadlist','local_feeheadmanagement')." : " . $org_data->name;
$PAGE->navbar->add($title);
$PAGE->set_title($title);
$PAGE->set_heading($title);
echo $OUTPUT->header();
echo $OUTPUT->heading($title_cat);

$organizations = coursecat::make_categories_list();
if (!empty($fid)) {
    $feecatname = $DB->get_record('fee_category', array('id' => $fid), 'organization,name');
    $orgname = $DB->get_record('course_categories', array('id' => $feecatname->organization), 'name');
}
if (!empty($table)) {
    // form starts here
    $formcontent = html_writer::start_tag('div', array('class' => 'venue-list'));
    $formcontent .= html_writer::start_tag('form', array('action' => new moodle_url('/local/feeheadmanagement/feehead.php', $pageparams),
                'class' => 'form-inline', 'method' => 'post'));
    // add fee head link
    $formcontent .= html_writer::start_tag('div', array('class' => ' pbottom-lg clearfix'));
    $formcontent .= html_writer::start_tag('div', array('class' => 'btn-toolbar navbar-right'));
    $formcontent .= html_writer::start_tag('div', array('class' => 'btn-group'));
    $addfeehead = html_writer::tag('span', '', array('class' => 'glyphicon glyphicon-plus'));
    $addfeehead .= get_string('addfeehead', 'local_feeheadmanagement');
    if (!empty($fid)) {
        $formcontent .= html_writer::link(new moodle_url($CFG->wwwroot . '/local/feeheadmanagement/addfeehead.php?fid=' . $fid), $addfeehead, array('class' => 'btn btn-success'));
    }
    $formcontent .= html_writer::end_tag('div');
    $formcontent .= html_writer::end_tag('div');
    //ends here
    $formcontent .= html_writer::start_tag('div', array('class' => ' pbottom-lg clearfix'));
    $formcontent .= html_writer::start_tag('div', array('class' => 'btn-toolbar navbar-right'));
    $formcontent .= html_writer::end_tag('div');
    // filters for the list
    $formcontent .= html_writer::empty_tag('static', array('name' => 'org', 'value' => 'Organization :' . $orgname->name));
    $formcontent .= '<br/>';
    $formcontent .= html_writer::empty_tag('static', array('name' => 'feecat', 'value' => 'Fee Category :' . $feecatname->name));
    $formcontent .= html_writer::end_tag('div');
    $formcontent .= html_writer::start_tag('div', array('class' => 'form-group'));
    $formcontent .= html_writer::start_tag('lebel', array('class' => 'modicon'));
    $formcontent .= get_string('feehead', 'local_feeheadmanagement').' ';
    $formcontent .= html_writer::end_tag('lebel');
    $formcontent .= html_writer::empty_tag('input', array('type' => 'text', 'name' => 'feehead', 'value' => $feeheadfilter));
    $formcontent .= get_string('shortname', 'local_feeheadmanagement').' ';
    $formcontent .= html_writer::empty_tag('input', array('type' => 'text', 'name' => 'short_name', 'value' => $shortnamefilter));
    $formcontent .= html_writer::end_tag('div');
    $formcontent .= '<br/>';
    $formcontent .= html_writer::tag('input', '', array('type' => 'submit', 'name' => 'btn btn-default', 'class' => 'submitbutton', 'value' => 'Search'));
    $formcontent .= html_writer::tag('input', '', array('type' => 'submit', 'name' => 'removefilter', 'class' => 'submitbutton', 'value' => 'Reset Filter'));
    $formcontent .= '<br/>';
    //ends here
    $formcontent .= html_writer::table($table);
    $formcontent .= html_writer::start_tag('div', array('class' => 'btn-group'));
    $addfeecat = html_writer::tag('span', '', array('class' => 'glyphicon glyphicon-plus'));
    $addfeecat .= get_string('back');
    $feecat = '';
    // fee category filter for url params
    if (!empty($feecategoryfilter)) {
        if (empty($feecat)) {
            $feecat .= "?feecategory=$feecategoryfilter";
        } else {
            $feecat .= "&feecategory=$feecategoryfilter";
        }
    }//ends here
    // fee head short name for url params
    if (!empty($feecat_short_name)) {
        if (!empty($feecat)) {
            $feecat .= "&short_name=$feecat_short_name";
        } else {
            $feecat .= "?short_name=$feecat_short_name";
        }
    }//ends here
    $formcontent .= html_writer::link(new moodle_url($CFG->wwwroot . '/local/feeheadmanagement/feecategory.php' . $feecat), $addfeecat, array('class' => 'btn btn-success'));
    $formcontent .= html_writer::end_tag('div');
    $formcontent .= html_writer::end_tag('form'); // form ends here
    $formcontent .= html_writer::end_tag('div');
    echo '<div>';
    echo $formcontent;
    echo '</div>';
    echo $OUTPUT->paging_bar($feeheadcount, $page, $perpage, $PAGE->url);
}


echo $OUTPUT->footer();
