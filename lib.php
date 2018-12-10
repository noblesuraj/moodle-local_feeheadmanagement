<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/* Creates Fee category as per the form details
 *  @param object data : category data
 */

function create_category($data) {
    global $DB;
    $data->timecreated = time();
    $data->timemodified = time();
    $id = $DB->insert_record('fee_category', $data);
    return $id;
}

/* Updates Fee category details as per form details
 * @param catid : category id
 * @param object data : category data to be updated
 */

function update_category($catid, $data) {
    global $DB;
    $data->id = $catid;
    $data->timemodified = time();
    $DB->update_record('fee_category', $data);
}

/* Creates Fee Head for a fee category as per form details
 *  @param object data : fee head data
 */

function create_feehead($data) {
    global $DB;
    $data->timecreated = time();
    $data->timemodified = time();
    if (empty($data->refundable) || !isset($data->refundable)) {
        $data->refundable = 0;
    }
    $id = $DB->insert_record('fee_head', $data);
    return $id;
}

/* Updates fee Head for a fee category as per form details
 *  @param int feeheadid : feehead id
 *  @param object data : fee head data
 */

function update_feehead($feeheadid, $data) {
    global $DB;
    $data->id = $feeheadid;
    $data->timemodified = time();
    if (empty($data->refundable) || !isset($data->refundable)) {
        $data->refundable = 0;
    }
    $DB->update_record('fee_head', $data);
}

/* Returns List of fee categories as per the filter
 *  @param array filter_params : filter
 *  @param : int page : page number
 *  @param : int perpage : enteries per page
 *  @return array feecategories : fee category data as per filter
 */

function get_feecategory_listing($filter_params = array(), $page, $perpage) {
    global $DB;
    $params = array();
    $select = '';
    $sort = '';
    $list = '';
    $limit = $page * $perpage;
    if (is_array($filter_params) && count($filter_params) > 0) {
        $select_params = array();
        // filters query searching
        foreach ($filter_params as $field => $value) {
            if ($field == 'name') {
                $value = strtolower($value);
                $select_params[] = "name like '%$value%'";
            } else if ($field == 'short_name') {
                $value = strtolower($value);
                $select_params[] = "short_name like '%$value%'";
            } else if ($field == 'list') {
                $select_params[] = "organization IN (" . $value . ")";
            } else {
                $select_params[] = $field . '=:' . $field;
                $params[$field] = $value;
            }
        }//ends here
        $select .= "AND " . implode(" AND ", $select_params);
    }
    // gets fee categories
    $feecategories = $DB->get_records_sql("SELECT * FROM {fee_category}  
                                  WHERE 1 AND deleted = 0 $select
                                  $sort order by id desc", $params, $limit, $perpage);
    return $feecategories;
}

/* Returns count of fee categories as per the filter
 *  @param array filter_params : filter
 *  @return int count : fee category count as per filter
 */
function get_feecategory_counts($filter_params = array()) {
    global $DB;
    $select = '';
    $sort = '';
    $list = '';
    $params = array();
    if (is_array($filter_params) && count($filter_params) > 0) {
        $select_params = array();
        // filter params searching
        foreach ($filter_params as $field => $value) {
            if ($field == 'name') {
                $value = strtolower($value);
                $select_params[] = "name like '%$value%'";
            } else if ($field == 'short_name') {
                $value = strtolower($value);
                $select_params[] = "short_name like '%$value%'";
            } else if ($field == 'list') {
                $select_params[] = "organization IN (" . $value . ")";
            } else {
                $select_params[] = $field . '=:' . $field;
                $params[$field] = $value;
            }
        }// ends here
        $select .= "AND " . implode(" AND ", $select_params);
    }
    // count of distinct id of fee category
    $count = $DB->count_records_sql("SELECT COUNT(DISTINCT id) FROM {fee_category} WHERE 1 AND deleted = 0 $select
                                  $sort order by id desc", $params);
    return $count;
}

/* Returns List of fee head as per the filter
 *  @param array filter_params : filter
 *  @param : int page : page number
 *  @param : int perpage : enteries per page
 *  @return array feeheads : fee head data as per filter
 */

function get_feehead_listing($filter_params = array(), $page, $perpage) {
    global $DB;
    $params = array();
    $select = '';
    $sort = '';
    $list = '';
    $limit = $page * $perpage;
    if (is_array($filter_params) && count($filter_params) > 0) {
        $select_params = array();
        // filter params searching
        foreach ($filter_params as $field => $value) {
            if ($field == 'name') {
                $value = strtolower($value);
                $select_params[] = "name like '%$value%'";
            } else if ($field == 'short_name') {
                $value = strtolower($value);
                $select_params[] = "short_name like '%$value%'";
            } else if ($field == 'list') {
                $select_params[] = "organization IN (" . $value . ")";
            } else {
                $select_params[] = $field . '=:' . $field;
                $params[$field] = $value;
            }
        }
        $select .= "AND " . implode(" AND ", $select_params);
    }
    // gets fee heads data
    $feeheads = $DB->get_records_sql("SELECT * FROM {fee_head}  
                                  WHERE 1 AND deleted = 0 $select
                                  $sort order by id desc", $params, $limit, $perpage);
    return $feeheads;
}

/* Returns count of fee heads as per the filter
 *  @param array filter_params : filter
 *  @return int count : fee head count as per filter
 */
function get_feehead_counts($filter_params = array()) {
    global $DB;
    $select = '';
    $sort = '';
    $list = '';
    $params = array();
    if (is_array($filter_params) && count($filter_params) > 0) {
        $select_params = array();
        foreach ($filter_params as $field => $value) {
            if ($field == 'name') {
                $value = strtolower($value);
                $select_params[] = "name like '%$value%'";
            } else if ($field == 'short_name') {
                $value = strtolower($value);
                $select_params[] = "short_name like '%$value%'";
            } else if ($field == 'list') {
                $select_params[] = "organization IN (" . $value . ")";
            } else {
                $select_params[] = $field . '=:' . $field;
                $params[$field] = $value;
            }
        }
        $select .= "AND " . implode(" AND ", $select_params);
    }
    // gets count of distinct fee heads
    $count = $DB->count_records_sql("SELECT COUNT(DISTINCT id) FROM {fee_head} WHERE 1 AND deleted = 0 $select
                                  $sort order by id desc", $params);
    return $count;
}

/* return parent categories ie organizations whose parent = 0
 * @return array organization_list : list of organization with id and name
 */
function get_parent_categories() {
    global $DB;
    $organization_list = array();
    $organization_comp = $DB->get_records('course_categories', array('parent' => 0));
    foreach ($organization_comp as $key => $org) {
        $organization_list[$org->id] = $org->name;
    }
    return $organization_list;
}

/*
 * Extend Navigation Settings
 * @param nav object : settings_navigation
 * @param context object
 */

function local_feeheadmanagement_extends_settings_navigation(settings_navigation $nav, context $context) {
    global $CFG;
    require_once($CFG->libdir . '/coursecatlib.php');
    // link for fee head and category as per capabilities
    if ((has_capability_in_organization('local/feeheadmanagement:managefeecategory') && has_capability_in_organization('local/feeheadmanagement:viewfeecategorylist')) || (has_capability_in_organization('local/feeheadmanagement:managefeecategory'))) {
        $displaylist = coursecat::make_categories_list('local/feeheadmanagement:managefeecategory');
        if ($displaylist) {
            if (!($apeejaynode = $nav->find('nav_apeejayadministration', navigation_node::TYPE_CONTAINER))) {
                $apeejaynode = $nav->prepend(new lang_string('apeejayadministration', 'local_feeheadmanagement'), null, navigation_node::TYPE_CONTAINER, null, 'nav_apeejayadministration');
            }
            $catnode = $apeejaynode->add(get_string('feeheadmanagement', 'local_feeheadmanagement'), null, navigation_node::TYPE_CONTAINER, null, 'nav_feeheadmanagement');
            $leafnode = $catnode->add(get_string('addfeecategory', 'local_feeheadmanagement'), new moodle_url('/local/feeheadmanagement/addfeecategory.php'), navigation_node::TYPE_CONTAINER, null, 'nav_addfeecategory');
            $leafnode = $catnode->add(get_string('feecategory', 'local_feeheadmanagement'), new moodle_url('/local/feeheadmanagement/feecategory.php'), navigation_node::TYPE_CONTAINER, null, 'nav_feecategory');
        }
    } else if (has_capability_in_organization('local/feeheadmanagement:viewfeecategorylist')) {
        $displaylist = coursecat::make_categories_list('local/feeheadmanagement:viewfeecategorylist');
        if ($displaylist) {
            if (!($apeejaynode = $nav->find('nav_apeejayadministration', navigation_node::TYPE_CONTAINER))) {
                $apeejaynode = $nav->prepend(new lang_string('apeejayadministration', 'local_feeheadmanagement'), null, navigation_node::TYPE_CONTAINER, null, 'nav_apeejayadministration');
            }
            $catnode = $apeejaynode->add(get_string('feeheadmanagement', 'local_feeheadmanagement'), null, navigation_node::TYPE_CONTAINER, null, 'nav_feeheadmanagement');
            $leafnode = $catnode->add(get_string('feecategory', 'local_feeheadmanagement'), new moodle_url('/local/feeheadmanagement/feecategory.php'), navigation_node::TYPE_CONTAINER, null, 'nav_feecategory');
        }
    }
}
