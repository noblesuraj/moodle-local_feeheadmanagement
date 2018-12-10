<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
//require_once('constantslib.php');
require_once($CFG->dirroot . '/user/lib.php');

// global variable to store current user's organization specific roles wise capabilities
global $MYORG_ROLES_AND_CAPS;
$MYORG_ROLES_AND_CAPS = new stdClass();


/* Returns parent categories listing
 */

function get_parent_categories_listing() {
    global $DB;
    $organization_list = array();
    $organization_comp = $DB->get_records('course_categories', array('parent' => 0));
    foreach ($organization_comp as $key => $org) {
        $organization_list[$org->id] = $org->name;
    }
    return $organization_list;
}

/*  Return the affiliated organizations
 *  @param void
 *  @return affiliated_universities array
 */

function get_affiliated_orgs() {
    global $DB;
    $affiliated = get_config('local_programmanagement', 'program_management_settings');
    $affiliated_universities = explode('<br>', $affiliated);
    if (!empty($affiliated_universities)) {
        $affiliated_universities = explode('<p>', $affiliated);
    }
    $affiliated_universities = array('' => 'Select Affiliated') + $affiliated_universities;
    return $affiliated_universities;
}

/* Returns the child categories
 * @param categoryid int
 * @return organization_list array
 */

function get_children_categories($categoryid) {
    global $DB;
    $organization_list = array();
    $organization_comp = $DB->get_records('course_categories', array('parent' => $categoryid));
    foreach ($organization_comp as $key => $org) {
        $organization_list[$org->id] = $org->name;
    }
    $organization_list = array('' => 'Select Department') + $organization_list;
    return $organization_list;
}

/*
 * Get all the departments
 * @param organization string
 * @return dept_list array
 */

function get_depts($organization = '') {
    global $DB;
    $dept_list = array();
    $dept_comp = $DB->get_records('course_categories', array('depth' => 2, 'parent' => $organization));
    foreach ($dept_comp as $key => $org) {
        $dept_list[$org->id] = $org->name;
    }
    $dept_list = array('' => 'Select Department') + $dept_list;
    return $dept_list;
}

/*
 *  Creates a program as per the data provided in the form.
 * @param data object
 * @return programid int
 */

function create_program($data) {
    global $DB, $USER;
    $data->timemodified = time();
    $programid = $DB->insert_record('program', $data);
    return $programid;
}

/*  Updates program details as per the data provided in the form.
 *  @param progid int : program id
 *  @param data object       
 */

function update_program($progid, $data) {
    global $DB, $USER;
    $data->id = $progid;
    $data->timemodified = time();
    $DB->update_record('program', $data);
}

/* returns the program count as per the filters on the program list page
 * @param filter_params array : filters
 * @param exactfilter string : program name filter
 * @return count int
 */

function get_program_count($filter_params = array(), $exactfilter) {
    global $DB;
    $select = '';
    $sort = '';
    $params = array();
    if (is_array($filter_params) && count($filter_params) > 0) {
        $select_params = array();
        foreach ($filter_params as $field => $value) {
            if ($field == 'programname' && empty($exactfilter)) {
                $select_params[] = "name like '%$value%'";
            } else if ($field == 'programname' && !empty($exactfilter)) {
                $select_params[] = "name = '$value'";
            } else if ($field == 'list') {
                $select_params[] = "organization IN (" . $value . ")";
            } else if ($field == 'orgid') {
                $select_params[] = "organization = $value ";
            } else {
                $select_params[] = $field . '=:' . $field;
                $params[$field] = $value;
            }
        }
        $select .= "AND " . implode(" AND ", $select_params);
    }
    $count = $DB->count_records_sql("SELECT COUNT(DISTINCT id) FROM {program} WHERE 1 AND deleted = 0 $select
                                  $sort order by id desc", $params);
    return $count;
}

/*  Fetches all programs as per the filter 
 * @param filter_params array : filters 
 * @param page int : page number 
 * @param perpage : entries per page 
 * @param exactfilter string : program name filter
 * @return programs object 
 */

function get_programs($filter_params = array(), $page, $perpage, $exactfilter) {
    global $DB;
    $params = array();
    $select = '';
    $sort = '';
    $limit = $page * $perpage;
    if (is_array($filter_params) && count($filter_params) > 0) {
        $select_params = array();
        foreach ($filter_params as $field => $value) {
            if ($field == 'programname' && empty($exactfilter)) {
                $select_params[] = "p.name like '%$value%'";
            } else if ($field == 'programname' && !empty($exactfilter)) {
                $select_params[] = "p.name = '$value'";
            } else if ($field == 'list') {
                $select_params[] = "organization IN (" . $value . ")";
            } else if ($field == 'orgid') {
                $select_params[] = "organization = $value ";
            } else {
                $select_params[] = $field . '=:' . $field;
                $params[$field] = $value;
            }
        }
        $select .= "AND " . implode(" AND ", $select_params);
    }
    $programs = $DB->get_records_sql("SELECT p.* , cc.name as orgname , dept.name as deptname FROM {program} p JOIN (SELECT * from {course_categories}) dept ON dept.id = p.department JOIN {course_categories} cc "
            . "ON cc.id = p.organization WHERE 1 AND p.deleted = 0 $select
                                  $sort order by id desc", $params, $limit, $perpage);
    return $programs;
}

/*  Fetches all cohort users as per the filter 
 * @param filter_params array : filters 
 * @param page int : page number 
 * @param perpage : entries per page 
 * @return programs object 
 */

function get_cohortusers($filter_params = array(), $page = 0, $perpage = 0) {
    global $DB;
    $params = array();
    $select = '';
    $sort = '';
    $limit = $page * $perpage;
    if (is_array($filter_params) && count($filter_params) > 0) {
        $select_params = array();
        foreach ($filter_params as $field => $value) {
            if ($field == 'firstname') {
                $select_params[] = "(u.firstname like '%$value%' OR u.lastname like '%$value%')";
            } else if ($field == 'cohort_id') {
                $select_params[] = 'cohm.cohortid =' . $value;
            }
        }
        $select .= "AND " . implode(" AND ", $select_params);
    }
    if (empty($perpage)) {
        $programs = $DB->get_records_sql("SELECT u.*, cohm.cohortid, cohm.userid  FROM {cohort_members} as cohm join {user} as u "
                . "on u.id = cohm.userid WHERE 1 $select AND u.suspended = 0 AND u.deleted = 0 "
                . "$sort order by cohm.id desc", $params);
    } else {
        $programs = $DB->get_records_sql("SELECT u.*, cohm.cohortid, cohm.userid  FROM {cohort_members} as cohm join {user} as u "
                . "on u.id = cohm.userid WHERE 1 $select AND u.suspended = 0 AND u.deleted = 0 "
                . "$sort order by cohm.id desc", $params, $limit, $perpage);
    }
    return $programs;
}

/* returns the program count users as per the filters on the program list page
 * @param filter_params array : filters
 * @return count int
 */

function get_program_cohortuser_count($filter_params = array()) {
    global $DB;
    $params = array();
    $select = '';
    $sort = '';
    $select_params = array();
    if (is_array($filter_params) && count($filter_params) > 0) {
        foreach ($filter_params as $field => $value) {
            if ($field == 'firstname') {
                $select_params[] = "(u.firstname like '%$value%' OR u.lastname like '%$value%')";
            } else if ($field == 'cohort_id') {
                $select_params[] = 'cohm.cohortid =' . $value;
            }
        }
        $select .= "AND " . implode(" AND ", $select_params);
    }
    $count = $DB->count_records_sql("SELECT COUNT(DISTINCT cohm.id) FROM {cohort_members} as cohm join {user} as u "
            . "on u.id = cohm.userid  WHERE 1 $select AND u.suspended = 0 AND u.deleted = 0 $sort order by cohm.id desc", $params);
    return $count;
}

/* Return programs of an organization
 * @param organization int
 * @return programs_list
 */

function get_progs($organization = '') {
    global $DB;
    $programs_list = array();
    $progs = $DB->get_records('program', array('organization' => $organization, 'deleted' => 0));
    foreach ($progs as $programs) {
        $programs_list[$programs->id] = $programs->name;
    }
    return $programs_list;
}

/* Return programs of an organization
 * @param organization int
 * @return programs_list
 */

function get_progs_org($organization = '') {
    global $DB;
    $programs_list = array();
    $progs = $DB->get_records('program', array('organization' => $organization, 'deleted' => 0));
    foreach ($progs as $programs) {
        $programs_list[$programs->id] = $programs->name;
    }
    return $programs_list;
}

/*
 *  Returns List of prepayment plans as per the filter
 *  @param: filter_params - filter , perpage - enteries per page
 *  page - page number
 *  @return : list of prepayment plans
 */

function get_prepayment_plans($filter_params = array(), $page, $perpage) {
    global $DB;
    $params = array();
    $select = '';
    $sort = '';
    $limit = $page * $perpage;
    if (is_array($filter_params) && count($filter_params) > 0) {
        $select_params = array();
        foreach ($filter_params as $field => $value) {
            if ($field == 'plan_type') {
                $value = strtolower($value);
                $select_params[] = "plan_type like '%$value%'";
            } else {
                $select_params[] = $field . '=:' . $field;
                $params[$field] = $value;
            }
        }
        $select .= "AND " . implode(" AND ", $select_params);
    }
    $feecategories = $DB->get_records_sql("SELECT * FROM {prepayment_plans}  
                                  WHERE 1 AND deleted = 0 $select
                                  $sort order by id desc", $params, $limit, $perpage);
    return $feecategories;
}

/* Returns the count of prepayment plans as per filter
 * @param: filter_params : filter for plan_type and organization
 * @return: prepayment plan count
 */

function get_prepayment_plans_counts($filter_params = array()) {
    global $DB;
    $select = '';
    $sort = '';
    $params = array();
    if (is_array($filter_params) && count($filter_params) > 0) {
        $select_params = array();
        foreach ($filter_params as $field => $value) {
            if ($field == 'plan_type') {
                $value = strtolower($value);
                $select_params[] = "plan_type like '%$value%'";
            } else {
                $select_params[] = $field . '=:' . $field;
                $params[$field] = $value;
            }
        }
        $select .= "AND " . implode(" AND ", $select_params);
    }
    $count = $DB->count_records_sql("SELECT COUNT(DISTINCT id) FROM {prepayment_plans} WHERE 1 AND deleted = 0 $select
                                  $sort order by id desc", $params);
    return $count;
}

/*
 *  Reurns the fee category in a program or all fee categories 
 *  @param : optional orgid - organization id
 *  @return: list of feecategories
 */

function get_feecategories($orgid = '') {
    global $DB;
    $feecat_list = array();
    $feecat_comp = $DB->get_records('fee_category', array('organization' => $orgid, 'deleted' => 0));
    foreach ($feecat_comp as $key => $feecat) {
        $feecat_list[$feecat->id] = $feecat->name;
    }
    $feecat_list = array('' => 'Select Fee Category') + $feecat_list;
    return $feecat_list;
}

/*
 *  Reurns the fee heads of a category in a program or all fee categories 
 *  @param : optional feecatid - fee category id
 *  @return: list of feeheads 
 */

function get_feeheads($feecatid = '') {
    global $DB;
    $feehead_list = array();
    $feehead_comp = $DB->get_records('fee_head', array('feecategory' => $feecatid, 'deleted' => 0));
    foreach ($feehead_comp as $key => $feehead) {
        $feehead_list[$feehead->id] = $feehead->name;
    }
    $feehead_list = array('' => 'Select Fee Head') + $feehead_list;
    return $feehead_list;
}

/*
 *  Creates a program as per the data provided in the form.
 *  @param : data from form data
 *  @return : adds a fee raise date for the program
 */

function add_feeraisedate($data) {
    global $DB, $USER;
    $feeraisedateid = $DB->insert_record('fee_raise_dates', $data);
    $program = $DB->get_record('program', array('id' => $data->pid));
    $organization = $DB->get_record('course_categories', array('id' => $program->organization));
    $eventorg = isset($organization->id) ? $organization->id : 0;
    $eventcontext = $eventorg ? CONTEXT_COURSECAT::instance($eventorg) : CONTEXT_SYSTEM::instance();
    $event = \local_programmanagement\event\fee_raisedate_added::create(
                    array('context' => $eventcontext, 'userid' => $USER->id,
                        'objectid' => $feeraisedateid,
                        'other' => array('progname' => $program->name,
                            'organizationname' => $organization->name)
                    )
    );
    $event->trigger();
}

/*  Updates fee raise dates details as per the data provided in the form.
 *  @param : data from the form data
 *  @return : updates the fee raise dates for the program
 */

function update_feeraisedate($data) {
    global $DB, $USER;
    $DB->update_record('fee_raise_dates', $data);
    $program = $DB->get_record('program', array('id' => $data->pid));
    $organization = $DB->get_record('course_categories', array('id' => $program->organization));
    $eventorg = isset($organization->id) ? $organization->id : 0;
    $eventcontext = $eventorg ? CONTEXT_COURSECAT::instance($eventorg) : CONTEXT_SYSTEM::instance();
    $event = \local_programmanagement\event\fee_raisedate_updated::create(
                    array('context' => $eventcontext, 'userid' => $USER->id,
                        'objectid' => $data->id,
                        'other' => array('progname' => $program->name,
                            'organizationname' => $organization->name)
                    )
    );
    $event->trigger();
}

/*  Returns List of fee raise dates as per the filter
 *  @param: filter_params - filter , perpage - enteries per page
 *  page - page number
 *  @return : list of fee raise dates
 */

function get_feeraisedates($filter_params = array(), $page, $perpage) {
    global $DB;
    $params = array();
    $select = '';
    $sort = '';
    $startdate = '';
    $enddate = '';
    $date_filter = '';
    $limit = $page * $perpage;
    if (is_array($filter_params) && count($filter_params) > 0) {
        $select_params = array();
        foreach ($filter_params as $field => $value) {
            if ($field == 'fee_category') {
                $select_params[] = "cat.name like '%$value%'";
            } else if ($field == 'fee_head') {
                $select_params[] = "head.name like '%$value%'";
            } else if ($field == 'program') {
                $select_params[] = "raise.program =" . $value;
            } else if ($field == 'date_from') {
                $startdate = $value;
            } else if ($field == 'date_to') {
                $enddate = $value;
            }
        }
        $select .= "AND " . implode(" AND ", $select_params);
    }
    if (!empty($startdate) && !empty($enddate)) {
        $date_filter = 'AND (raise.raise_date >= ' . "'" . strtotime($startdate) . "' AND raise.raise_date <= " . "'" . strtotime($enddate) . "'" . ")";
    } else if (!empty($startdate) && empty($enddate)) {
        $date_filter = 'AND (raise.raise_date >= ' . "'" . strtotime($startdate) . "' AND raise.raise_date <=  " . "'" . time() . "'" . ")";
    }
    $feecategories = $DB->get_records_sql("SELECT raise.* , head.name , cat.name FROM {fee_raise_dates} as raise join {fee_head} as head on raise.fee_head = head.id
                                  join {fee_category} as cat on cat.id = raise.fee_category WHERE 1 AND head.deleted = 0 AND cat.deleted = 0 $select $date_filter
                                  $sort order by raise.raise_date asc", $params, $limit, $perpage);
    return $feecategories;
}

/* Returns the count of prepayment plans as per filter
 * @param: filter_params : filter for plan_type and organization
 * @return: prepayment plan count
 */

function get_feeraisedate_count($filter_params = array()) {
    global $DB;
    $select = '';
    $sort = '';
    $params = array();
    $startdate = '';
    $enddate = '';
    $date_filter = '';
    if (is_array($filter_params) && count($filter_params) > 0) {
        $select_params = array();
        foreach ($filter_params as $field => $value) {
            if ($field == 'fee_category') {
                $select_params[] = "cat.name like '%$value%'";
            } else if ($field == 'fee_head') {
                $select_params[] = "head.name like '%$value%'";
            } else if ($field == 'program') {
                $select_params[] = "raise.program =" . $value;
            } else if ($field == 'date_from') {
                $startdate = $value;
            } else if ($field == 'date_to') {
                $enddate = $value;
            }
        }
        $select .= "AND " . implode(" AND ", $select_params);
    }
    if (!empty($startdate) && !empty($enddate)) {
        $date_filter = 'AND (raise.raise_date BETWEEN' . "'" . strtotime($startdate) . "' AND " . "'" . strtotime($enddate) . "'" . ")";
    } else if (!empty($startdate) && empty($enddate)) {
        $date_filter = 'AND (raise.raise_date BETWEEN' . "'" . strtotime($startdate) . "' AND " . "'" . time() . "'" . ")";
    }
    $count = $DB->count_records_sql("SELECT COUNT(DISTINCT raise.id) FROM {fee_raise_dates} as raise join {fee_head} as head on raise.fee_head = head.id
                                  join {fee_category} as cat on cat.id = raise.fee_category WHERE 1 AND head.deleted = 0 AND cat.deleted = 0 $select $date_filter
                                  $sort order by raise.raise_date asc", $params);
    return $count;
}

/*
 *  Creates a fee cycle as per the data provided in the form.
 *  @param : data from form data
 *  @return : adds a fee cycle for the program
 */

function add_cycle($data) {
    global $DB;
    $instanceobj = new stdClass();
    $instanceobj->program = $data->pid;
    $instanceobj->pre_paymentplan_id = $data->planid;
    $instanceobj->instance_name = $data->name;
    $instanceobj->from_feeraisedate = $data->from_feeraisedate;
    $instanceobj->to_feeraisedate = $data->to_feeraisedate;
    $instanceid = $DB->insert_record('prepayment_plan_instance', $instanceobj);

    $billtemplateobj = new stdClass();
    if (!isset($data->type)) {
        $billtemplateobj->type = 0;
    } else {
        $billtemplateobj->type = $data->type;
    }
    $billtemplateobj->name = $data->name;
    $billtemplateobj->short_name = $data->short_name;
    $billtemplateobj->bill_date = $data->bill_date;
    $billtemplateobj->due_date = $data->due_date;
    $billtemplateobj->roll_struck_off = isset($data->roll_struck_off) ? $data->roll_struck_off : 0;
    if (!empty($data->roll_struck_off_date)) {
        $billtemplateobj->roll_struck_off_date = $data->roll_struck_off_date;
    } else {
        $billtemplateobj->roll_struck_off_date = 0;
    }
    if (!empty($data->readmission_charge)) {
        $billtemplateobj->readmission_charge = $data->readmission_charge;
    } else {
        $billtemplateobj->readmission_charge = 0;
    }
    $billtemplateobj->fine = $data->fine;
    if (!empty($data->comment)) {
        $billtemplateobj->comment = $data->comment;
    } else {
        $billtemplateobj->comment = '';
    }
    $billtemplateobj->program = $data->pid;
    $billtemplateid = $DB->insert_record('fee_bill_templates', $billtemplateobj);
    $billobj = new stdClass();
    $billobj->id = $instanceid;
    $billobj->bill_template_id = $billtemplateid;
    $DB->update_record('prepayment_plan_instance', $billobj);

    //add and trigger event 
    $instanceobj->id = $instanceid;
    $instanceobj->bill_template_id = $billobj->bill_template_id;
    $programdata = $DB->get_record('program', array('id' => $data->pid));
    $eventorg = isset($programdata->organization) ? $programdata->organization : 0;

    $org = $DB->get_record('course_categories', array('id' => $programdata->organization));
    if ($org) {
        $instanceobj->orgid = $org->id;
        $instanceobj->orgname = $org->name;
    }

    if ($programdata) {
        $instanceobj->progname = $programdata->name;
        $instanceobj->progshortname = $programdata->short_name;
    }

    $eventcontext = $eventorg ? CONTEXT_COURSECAT::instance($eventorg) : CONTEXT_SYSTEM::instance();
    $event = \local_programmanagement\event\prepayment_plancycle_added::create(
                    array('context' => $eventcontext,
                        'objectid' => $instanceid,
                        'other' => (array) $instanceobj,
                        'relateduserid' => ''
                    )
    );
    $event->trigger();

    return $instanceid;
}

/*  Updates cycle instance and template details as per the data provided in the form.
 *  @param : data from the form data
 *  @return : updates the fee raise dates for the program
 */

function update_cycle($data) {
    global $DB;
    $plan_instance_data = $DB->get_record('prepayment_plan_instance', array('id' => $data->id, 'pre_paymentplan_id' => $data->planid));
    $bill_instance_data = $DB->get_record('fee_bill_templates', array('id' => $plan_instance_data->bill_template_id));
    $instanceobj = new stdClass();
    $instanceobj->id = $plan_instance_data->id;
    $instanceobj->program = $data->pid;
    $instanceobj->pre_paymentplan_id = $data->planid;
    $instanceobj->instance_name = $data->name;
    $instanceobj->from_feeraisedate = $data->from_feeraisedate;
    $instanceobj->to_feeraisedate = $data->to_feeraisedate;
    $instanceobj->bill_template_id = $plan_instance_data->bill_template_id;
    $DB->update_record('prepayment_plan_instance', $instanceobj);
    $billtemplateobj = new stdClass();
    $billtemplateobj->id = $plan_instance_data->bill_template_id;
    if (!isset($data->type)) {
        $billtemplateobj->type = 0;
    } else {
        $billtemplateobj->type = $data->type;
    }
    $billtemplateobj->name = $data->name;
    $billtemplateobj->short_name = $data->short_name;
    $billtemplateobj->bill_date = $data->bill_date;
    $billtemplateobj->due_date = $data->due_date;
    $billtemplateobj->roll_struck_off = isset($data->roll_struck_off) ? $data->roll_struck_off : 0;
    if (!empty($data->roll_struck_off_date)) {
        $billtemplateobj->roll_struck_off_date = $data->roll_struck_off_date;
    } else {
        $billtemplateobj->roll_struck_off_date = 0;
    }
    if (!empty($data->readmission_charge)) {
        $billtemplateobj->readmission_charge = $data->readmission_charge;
    } else {
        $billtemplateobj->readmission_charge = 0;
    }
    $billtemplateobj->fine = $data->fine;
    if (!empty($data->comment)) {
        $billtemplateobj->comment = $data->comment;
    } else {
        $billtemplateobj->comment = '';
    }
    $billtemplateobj->program = $data->pid;
    $DB->update_record('fee_bill_templates', $billtemplateobj);

    //update and trigger event 

    $programdata = $DB->get_record('program', array('id' => $instanceobj->program));
    $eventorg = isset($programdata->organization) ? $programdata->organization : 0;
    $org = $DB->get_record('course_categories', array('id' => $programdata->organization));
    if ($org) {
        $instanceobj->orgid = $org->id;
        $instanceobj->orgname = $org->name;
    }

    if ($programdata) {
        $instanceobj->progname = $programdata->name;
        $instanceobj->progshortname = $programdata->short_name;
    }

    $eventcontext = $eventorg ? CONTEXT_COURSECAT::instance($eventorg) : CONTEXT_SYSTEM::instance();
    $event = \local_programmanagement\event\prepayment_plancycle_modified::create(
                    array('context' => $eventcontext,
                        'objectid' => $instanceobj->id,
                        'other' => array('instance_name' => $instanceobj->instance_name,
                            'progname' => $programdata->name,
                            'progshortname' => $programdata->short_name,
                            'program' => $programdata->id,
                            'pre_paymentplan_id' => $instanceobj->pre_paymentplan_id),
                        'relateduserid' => ''
                    )
    );
    $event->trigger();
}

/*  Returns List of fee raise dates as per the filter
 *  @param: filter_params - filter , perpage - enteries per page
 *  page - page number
 *  @return : list of fee raise dates
 */

function get_billcycles($filter_params = array(), $page, $perpage) {
    global $DB;
    $params = array();
    $select = '';
    $sort = '';
    $limit = $page * $perpage;
    if (is_array($filter_params) && count($filter_params) > 0) {
        $select_params = array();
        foreach ($filter_params as $field => $value) {
            if ($field == 'instance_name') {
                $value = strtolower($value);
                $select_params[] = "plan.instance_name like '%$value%'";
            } else if ($field == 'short_name') {
                $value = strtolower($value);
                $select_params[] = "bill.short_name like '%$value%'";
            } else if ($field == 'pre_paymentplan_id') {
                $select_params[] = 'plan.pre_paymentplan_id = ' . $value;
            } else if ($field == 'type') {
                $select_params[] = 'bill.type = ' . $value;
            } else if ($field == 'program') {
                $select_params[] = 'plan.program = ' . $value;
            }
        }
        $select .= "AND " . implode(" AND ", $select_params);
    }
    $feecategories = $DB->get_records_sql("SELECT plan.*,bill.short_name FROM {prepayment_plan_instance} as plan join {fee_bill_templates} as bill 
                                  on plan.bill_template_id = bill.id
                                  WHERE 1 AND plan.deleted = 0 AND bill.deleted = 0 $select
                                  $sort order by bill.id desc", $params, $limit, $perpage);
    return $feecategories;
}

/* Returns the count of prepayment plans as per filter
 * @param: filter_params : filter for plan_type and organization
 * @return: prepayment plan count
 */

function get_billcycle_count($filter_params = array()) {
    global $DB;
    $select = '';
    $sort = '';
    $params = array();
    if (is_array($filter_params) && count($filter_params) > 0) {
        $select_params = array();
        foreach ($filter_params as $field => $value) {
            if ($field == 'instance_name') {
                $value = strtolower($value);
                $select_params[] = "plan.instance_name like '%$value%'";
            } else if ($field == 'short_name') {
                $value = strtolower($value);
                $select_params[] = "bill.short_name like '%$value%'";
            } else if ($field == 'pre_paymentplan_id') {
                $select_params[] = 'plan.pre_paymentplan_id = ' . $value;
            } else if ($field == 'type') {
                $select_params[] = 'bill.type = ' . $value;
            } else if ($field == 'program') {
                $select_params[] = 'plan.program = ' . $value;
            }
        }
        $select .= "AND " . implode(" AND ", $select_params);
    }
    $count = $DB->count_records_sql("SELECT COUNT(DISTINCT bill.id) FROM {prepayment_plan_instance} as plan join {fee_bill_templates} as bill 
                                  on plan.bill_template_id = bill.id
                                  WHERE 1 AND plan.deleted = 0 AND bill.deleted = 0 $select
                                  $sort order by bill.id desc", $params);
    return $count;
}

/*
 *  Creates a fine for a bill template id as per the data provided in the form.
 * @param data object
 * @return fine object
 */

function add_finerule($data) {
    global $DB;
    $fineid = $DB->insert_record('bill_template_finerules', $data);


    //  log and trigger event
    $instanceobj = new stdClass();
    $instanceobj = clone $data;
    $instanceobj->id = $fineid;


    $billtemplateid = $data->bill_template_id;
    $billtemplate = $DB->get_record('fee_bill_templates', array('id' => $billtemplateid));

    $programdata = $DB->get_record('program', array('id' => $billtemplate->program));
    $eventorg = isset($programdata->organization) ? $programdata->organization : 0;
    $org = $DB->get_record('course_categories', array('id' => $programdata->organization));

    if ($org) {
        $instanceobj->orgid = $org->id;
        $instanceobj->orgname = $org->name;
    }

    if ($programdata) {
        $instanceobj->pid = $programdata->id;
        $instanceobj->progname = $programdata->name;
        $instanceobj->progshortname = $programdata->short_name;
    }

    $instanceobj->bill_template_id = $billtemplate->id;
    $instanceobj->bill_cycle_name = $billtemplate->name; //bill template name            

    $eventcontext = $eventorg ? CONTEXT_COURSECAT::instance($eventorg) : CONTEXT_SYSTEM::instance();
    $event = \local_programmanagement\event\plancycle_fine_added::create(
                    array('context' => $eventcontext,
                        'objectid' => $instanceobj->id,
                        'other' => (array) $instanceobj,
                        'relateduserid' => ''
                    )
    );
    $event->trigger();


    return $fineid;
}

/*
 *  Updates program details as per the data provided in the form.
 *  @param data object
 */

function update_finerule($data) {
    global $DB;
    $DB->update_record('bill_template_finerules', $data);

    //  log and trigger event
    $instanceobj = new stdClass();
    $instanceobj = clone $data;

    $billtemplateid = $data->bill_template_id;
    $billtemplate = $DB->get_record('fee_bill_templates', array('id' => $billtemplateid));

    $programdata = $DB->get_record('program', array('id' => $billtemplate->program));
    $eventorg = isset($programdata->organization) ? $programdata->organization : 0;
    $org = $DB->get_record('course_categories', array('id' => $programdata->organization));

    if ($org) {
        $instanceobj->orgid = $org->id;
        $instanceobj->orgname = $org->name;
    }

    if ($programdata) {
        $instanceobj->pid = $programdata->id;
        $instanceobj->progname = $programdata->name;
        $instanceobj->progshortname = $programdata->short_name;
    }

    $instanceobj->bill_template_id = $billtemplate->id;
    $instanceobj->bill_cycle_name = $billtemplate->name; //bill template name

    $eventcontext = $eventorg ? CONTEXT_COURSECAT::instance($eventorg) : CONTEXT_SYSTEM::instance();
    $event = \local_programmanagement\event\plancycle_fine_modified::create(
                    array('context' => $eventcontext,
                        'objectid' => $instanceobj->id,
                        'other' => (array) $instanceobj,
                        'relateduserid' => ''
                    )
    );
    $event->trigger();
}

/*  Returns List of fine rules as per the filter
 *  @param: filter_params - filter , perpage - enteries per page
 *  @param page int : page number
 *  @param page int : enteries per page
 *  @return finerules object : list of fine rules
 */

function get_finerules($filter_params = array(), $page, $perpage) {
    global $DB;
    $params = array();
    $select = '';
    $sort = '';
    $startdate = '';
    $enddate = '';
    $date_filter = '';
    $limit = $page * $perpage;
    if (is_array($filter_params) && count($filter_params) > 0) {
        $select_params = array();
        foreach ($filter_params as $field => $value) {
            if ($field == 'datefrom') {
                $startdate = $value;
            } else if ($field == 'dateto') {
                $enddate = $value;
            } else {
                $select_params[] = $field . '=:' . $field;
                $params[$field] = $value;
            }
        }
        $select .= "AND " . implode(" AND ", $select_params);
    }
    if (!empty($startdate) && !empty($enddate)) {
        $date_filter = 'AND date_from >= ' . strtotime($startdate) . ' AND date_to <= ' . strtotime($enddate);
    } else if (!empty($startdate) && empty($enddate)) {
        $date_filter = 'AND date_from >= ' . strtotime($startdate) . ' AND date_to <= ' . time();
    }
    $finerules = $DB->get_records_sql("SELECT * FROM {bill_template_finerules}   
                                  WHERE user_fee_bill_id = 0  $select $date_filter
                                  $sort ", $params, $limit, $perpage);
    return $finerules;
}

/* Returns the count of fine rules as per filter
 * @param: filter_params : filter for fine type
 * @return: count of enteries of fine rules
 */

function get_finerules_count($filter_params = array()) {
    global $DB;
    $select = '';
    $sort = '';
    $startdate = '';
    $enddate = '';
    $date_filter = '';
    $params = array();
    if (is_array($filter_params) && count($filter_params) > 0) {
        $select_params = array();
        foreach ($filter_params as $field => $value) {
            if ($field == 'datefrom') {
                $startdate = $value;
            } else if ($field == 'dateto') {
                $enddate = $value;
            } else {
                $select_params[] = $field . '=:' . $field;
                $params[$field] = $value;
            }
        }
        $select .= "AND " . implode(" AND ", $select_params);
    }
    if (!empty($startdate) && !empty($enddate)) {
        $date_filter = 'AND date_from >= ' . $startdate . ' AND date_to <= ' . $enddate;
    } else if (!empty($startdate) && empty($enddate)) {
        $date_filter = 'AND date_from >= ' . $startdate . ' AND date_to <= ' . time();
    }
    $count = $DB->count_records_sql("SELECT COUNT(DISTINCT id) FROM {bill_template_finerules} WHERE user_fee_bill_id = 0 $select $date_filter
                                  $sort ", $params);
    return $count;
}

/* Returns scholarship list of an organization
 * @param orgid int : organization id
 * @return scholarship_list object
 */

function get_orgscholarships($orgid) {
    global $DB;
    $scholarship_list = array();
    $scholarship_comp = $DB->get_records('scholarship', array('organization' => $orgid, 'deleted' => 0));
    foreach ($scholarship_comp as $key => $sch) {
        $scholarship_list[$sch->id] = $sch->name;
    }
    $sharable = $DB->get_records('scholarship', array('organization' => 0, 'deleted' => 0));
    foreach ($sharable as $key => $val) {
        $scholarship_list[$val->id] = $val->name;
    }
    $scholarship_list = array('' => 'Select Scholarship') + $scholarship_list;
    return $scholarship_list;
}

/* Returns scholarship category list of an organization
 * @param orgid int : organization id
 * @return scholarshipcategory_list object
 */

function get_orgscholarshipcategory($orgid) {
    global $DB;
    $scholarshipcategory_list = array();
    $scholarshipcategory_comp = $DB->get_records('scholarship_category', array('organization' => $orgid, 'deleted' => 0));
    foreach ($scholarshipcategory_comp as $key => $schc) {
        $scholarshipcategory_list[$schc->id] = $schc->name;
    }
    $sharable = $DB->get_records('scholarship_category', array('organization' => 0, 'deleted' => 0));
    foreach ($sharable as $key => $val) {
        $scholarshipcategory_list[$val->id] = $val->name;
    }
    $scholarshipcategory_list = array('' => 'Select Scholarship Category') + $scholarshipcategory_list;
    return $scholarshipcategory_list;
}

/* Returns applicable heads of an organization
 * @param orgid int : organization id
 * @return applicable_list object
 */

function get_applicableheads($orgid) {
    global $DB;
    $feecat_cat_data = $DB->get_records('fee_category', array('organization' => $orgid, 'deleted' => 0));
    $applicable_heads = array();
    foreach ($feecat_cat_data as $feecat) {
        $feehead_data = get_feehead_list($feecat->id);
        $applicable_heads[$feecat->name] = $feehead_data;
    }
    return $applicable_heads;
}

/* Returns fee head list of an organization
 * @param feecatid int : fee category id
 * @return feehead_list object
 */

function get_feehead_list($feecatid) {
    global $DB;
    $feehead_list = array();
    $feehead_comp = $DB->get_records('fee_head', array('feecategory' => $feecatid, 'deleted' => 0));
    foreach ($feehead_comp as $key => $feehead) {
        $feehead_list[$feehead->id] = $feehead->name;
    }
    return $feehead_list;
}

/* Adds a program scholarship criteria as per the data provided in the form.
 * @param : data from form
 * @return : id of insertion in table
 */

function add_scholarshipcriteria($data) {
    global $DB, $USER;
    $data->timemodified = time();
    $criteriaid = $DB->insert_record('program_scholarships', $data);
    $program = $DB->get_record('program', array('id' => $data->program));
    $organization = $DB->get_record('course_categories', array('id' => $program->organization));
    $eventorg = isset($organization->id) ? $organization->id : 0;
    $eventcontext = $eventorg ? CONTEXT_COURSECAT::instance($eventorg) : CONTEXT_SYSTEM::instance();
    $event = \local_programmanagement\event\programscholarshipcriteria_added::create(
                    array('context' => $eventcontext, 'userid' => $USER->id,
                        'objectid' => $criteriaid,
                        'other' => array('progname' => $program->name,
                            'organizationname' => $organization->name,
                            'criteria' => $data->criteria)
                    )
    );
    $event->trigger();
    return $criteriaid;
}

/*  Updates program details as per the data provided in the form.
 *  @param data object
 */

function update_scholarshipcriteria($data) {
    global $DB, $USER;
    $data->timemodified = time();
    $DB->update_record('program_scholarships', $data);
    $program = $DB->get_record('program', array('id' => $data->program));
    $organization = $DB->get_record('course_categories', array('id' => $program->organization));
    $eventorg = isset($organization->id) ? $organization->id : 0;
    $eventcontext = $eventorg ? CONTEXT_COURSECAT::instance($eventorg) : CONTEXT_SYSTEM::instance();
    $event = \local_programmanagement\event\programscholarshipcriteria_updated::create(
                    array('context' => $eventcontext, 'userid' => $USER->id,
                        'objectid' => $data->id,
                        'other' => array('progname' => $program->name,
                            'organizationname' => $organization->name,
                            'criteria' => $data->criteria)
                    )
    );
    $event->trigger();
}

/*  Returns List of scholarship criteria of a program as per the filter
 *  @param filter_params array : filter 
 *  @param perpage : enteries per page
 *  @param page int : page number
 *  @return schcriteria object : scholarship criteria of a program
 */

function get_scholarshipcriterias($filter_params = array(), $page, $perpage) {
    global $DB;
    $params = array();
    $select = '';
    $sort = '';
    $startdate = '';
    $enddate = '';
    $date_filter = '';
    $limit = $page * $perpage;
    if (is_array($filter_params) && count($filter_params) > 0) {
        $select_params = array();
        foreach ($filter_params as $field => $value) {
            if ($field == 'applicable_from') {
                $startdate = $value;
            } else if ($field == 'applicable_to') {
                $enddate = $value;
            } else if ($field == 'criteria') {
                $select_params[] = $field . " like '%" . $value . "%'";
            } else {
                $select_params[] = $field . '=:' . $field;
                $params[$field] = $value;
            }
        }
        $select .= "AND " . implode(" AND ", $select_params);
    }
    if (!empty($startdate) && !empty($enddate)) {
        $date_filter = 'AND applicable_from >= ' . strtotime($startdate) . ' AND applicable_to <= ' . strtotime($enddate);
    } else if (!empty($startdate) && empty($enddate)) {
        $date_filter = 'AND applicable_from >= ' . strtotime($startdate) . ' AND applicable_to <= ' . time();
    }
    $schcriterias = $DB->get_records_sql("SELECT * FROM {program_scholarships}  
                                  WHERE 1 AND deleted = 0 $select $date_filter
                                  $sort order by id desc", $params, $limit, $perpage);
    return $schcriterias;
}

/* Returns the count of program scholarship criteria as per filter
 * @param: filter_params : filter for scholarship criteria
 * @return: count of enteries of scholarship criteria
 */

function get_scholarshipcriteria_count($filter_params = array()) {
    global $DB;
    $select = '';
    $sort = '';
    $startdate = '';
    $enddate = '';
    $date_filter = '';
    $params = array();
    if (is_array($filter_params) && count($filter_params) > 0) {
        $select_params = array();
        foreach ($filter_params as $field => $value) {
            if ($field == 'applicable_from') {
                $startdate = $value;
            } else if ($field == 'applicable_to') {
                $enddate = $value;
            } else if ($field == 'criteria') {
                $select_params[] = $field . " like '%" . $value . "%'";
            } else {
                $select_params[] = $field . '=:' . $field;
                $params[$field] = $value;
            }
        }
        $select .= "AND " . implode(" AND ", $select_params);
    }
    if (!empty($startdate) && !empty($enddate)) {
        $date_filter = 'AND applicable_from >= ' . strtotime($startdate) . ' AND applicable_to <= ' . strtotime($enddate);
    } else if (!empty($startdate) && empty($enddate)) {
        $date_filter = 'AND applicable_from >= ' . strtotime($startdate) . ' AND applicable_to <= ' . time();
    }
    $count = $DB->count_records_sql("SELECT COUNT(DISTINCT id) FROM {program_scholarships} WHERE 1 AND deleted = 0 $select $date_filter
                                  $sort order by id desc", $params);
    return $count;
}

/* Add program scholarship raise date as per the data provided in the form.
 * @param data object : data from form
 * @return criteriaid int : ids of insertion in table
 */

function add_scholarshipraisedate($data) {
    global $DB, $USER;
    $data->timemodified = time();
    $raisedateid = $DB->insert_record('scholarship_raisedates', $data);
    $program_scholarship = $DB->get_record('program_scholarships', array('id' => $data->program_scholarship_id));
    $program = $DB->get_record('program', array('id' => $program_scholarship->program));
    $organization = $DB->get_record('course_categories', array('id' => $program->organization));
    $eventorg = isset($organization->id) ? $organization->id : 0;
    $eventcontext = $eventorg ? CONTEXT_COURSECAT::instance($eventorg) : CONTEXT_SYSTEM::instance();
    $event = \local_programmanagement\event\programscholarship_raisedate_added::create(
                    array('context' => $eventcontext, 'userid' => $USER->id,
                        'objectid' => $raisedateid,
                        'other' => array('progname' => $program->name,
                            'organizationname' => $organization->name,
                            'raisedate' => userdate(($data->raise_date), get_string('strftimerecentfull', 'langconfig')))
                    )
    );
    $event->trigger();
    return $raisedateid;
}

/* Updates program details as per the data provided in the form.
 * @param data object
 */

function update_scholarshipraisedate($data) {
    global $DB, $USER;
    $data->timemodified = time();
    $DB->update_record('scholarship_raisedates', $data);
    $program_scholarship = $DB->get_record('program_scholarships', array('id' => $data->program_scholarship_id));
    $program = $DB->get_record('program', array('id' => $program_scholarship->program));
    $organization = $DB->get_record('course_categories', array('id' => $program->organization));
    $eventorg = isset($organization->id) ? $organization->id : 0;
    $eventcontext = $eventorg ? CONTEXT_COURSECAT::instance($eventorg) : CONTEXT_SYSTEM::instance();
    $event = \local_programmanagement\event\programscholarship_raisedate_updated::create(
                    array('context' => $eventcontext, 'userid' => $USER->id,
                        'objectid' => $data->id,
                        'other' => array('progname' => $program->name,
                            'organizationname' => $organization->name,
                            'raisedate' => userdate(($data->raise_date), get_string('strftimerecentfull', 'langconfig')))
                    )
    );
    $event->trigger();
}

/*  Returns List of scholarship criteria of a program as per the filter
 *  @param: filter_params - filter 
 *  @param perpage int : enteries per page
 *  @param page int : page number
 *  @return scholarship_raisedates object : scholarship raise dates of a program
 */

function get_scholarshipraisedates($filter_params = array(), $page, $perpage) {
    global $DB;
    $params = array();
    $select = '';
    $sort = '';
    $startdate = '';
    $enddate = '';
    $date_filter = '';
    $limit = $page * $perpage;
    if (is_array($filter_params) && count($filter_params) > 0) {
        $select_params = array();
        foreach ($filter_params as $field => $value) {
            if ($field == 'date_from') {
                $startdate = $value;
            } else if ($field == 'date_to') {
                $enddate = $value;
            } else {
                $select_params[] = $field . '=:' . $field;
                $params[$field] = $value;
            }
        }
        $select .= "AND " . implode(" AND ", $select_params);
    }
    if (!empty($startdate) && !empty($enddate)) {
        $date_filter = 'AND (raise_date >= ' . "'" . strtotime($startdate) . "' AND raise_date <= " . "'" . strtotime($enddate) . "'" . ")";
    } else if (!empty($startdate) && empty($enddate)) {
        $date_filter = 'AND (raise_date >=' . "'" . strtotime($startdate) . "' AND raise_date <= " . "'" . time() . "'" . ")";
    } else if (empty($startdate) && !empty($enddate)) {
        $date_filter = 'AND raise_date <= ' . strtotime($enddate);
    }
    $scholarship_raisedates = $DB->get_records_sql("SELECT * FROM {scholarship_raisedates}  
                                  WHERE 1 $select $date_filter
                                  $sort order by id desc", $params, $limit, $perpage);
    return $scholarship_raisedates;
}

/* Returns the count of program scholarship raise date as per filter
 * @param: filter_params : filter for scholarship raise date
 * @return: count of enteries of scholarship raise dates
 */

function get_scholarshipraisedate_count($filter_params = array()) {
    global $DB;
    $select = '';
    $sort = '';
    $params = array();
    $startdate = '';
    $enddate = '';
    $date_filter = '';
    if (is_array($filter_params) && count($filter_params) > 0) {
        $select_params = array();
        foreach ($filter_params as $field => $value) {
            if ($field == 'date_from') {
                $startdate = $value;
            } else if ($field == 'date_to') {
                $enddate = $value;
            } else {
                $select_params[] = $field . '=:' . $field;
                $params[$field] = $value;
            }
        }
        $select .= "AND " . implode(" AND ", $select_params);
    }
    if (!empty($startdate) && !empty($enddate)) {
        $date_filter = 'AND (raise_date >= ' . "'" . strtotime($startdate) . "' AND raise_date <= " . "'" . strtotime($enddate) . "'" . ")";
    } else if (!empty($startdate) && empty($enddate)) {
        $date_filter = 'AND (raise_date >=' . "'" . strtotime($startdate) . "' AND raise_date <= " . "'" . time() . "'" . ")";
    } else if (empty($startdate) && !empty($enddate)) {
        $date_filter = 'AND raise_date <= ' . strtotime($enddate);
    }
    $count = $DB->count_records_sql("SELECT COUNT(DISTINCT id) FROM {scholarship_raisedates} WHERE 1 $select $date_filter
                                  $sort order by id desc", $params);
    return $count;
}

/* Creates manual bill template as per the data provided in the form.
 * @param : data from form
 * @return : ids of insertion in table
 */

function add_manualbilldata($data) {
    global $DB;
    $billtemplateid = $DB->insert_record('fee_bill_templates', $data);
    return $billtemplateid;
}

/*  Updates manual bill template as per the data provided in the form.
 * @param : data from form
 * @return : ids of insertion in table 
 */

function update_manualbilldata($data) {
    global $DB;
    $DB->update_record('fee_bill_templates', $data);
}

/* Deletes manual bill template as per the data provided in the form.
 * @param : data from form
 */

function delete_manualbilldata($data) {
    global $DB;
    $DB->delete_records('bill_template_feeheads', array('bill_template_id' => $data->id));
    $DB->delete_records('bill_template_finerules', array('bill_template_id' => $data->id));
    $data->deleted = 1;
    $DB->update_record('fee_bill_templates', $data);
}

/*  Returns List of manual fee bill templates of a program as per the filter
 *  @param filter_params - filter 
 *  @param perpage - enteries per page
 *  @param page - page number
 *  @return : manual fee bill templates
 */

function get_manualbilltemplates($filter_params = array(), $page, $perpage) {
    global $DB;
    $params = array();
    $select = '';
    $sort = '';
    $limit = $page * $perpage;
    if (is_array($filter_params) && count($filter_params) > 0) {
        $select_params = array();
        foreach ($filter_params as $field => $value) {
            if ($field == 'name') {
                $select_params[] = "name like '%" . $value . "%'";
            } else if ($field == 'short_name') {
                $select_params[] = "short_name like '%" . $value . "%'";
            } else {
                $select_params[] = $field . '=:' . $field;
                $params[$field] = $value;
            }
        }
        $select .= "AND " . implode(" AND ", $select_params);
    }
    $manualbilltemplates = $DB->get_records_sql("SELECT * FROM {fee_bill_templates}  
                                  WHERE 1 AND deleted = 0 $select
                                  $sort order by id desc", $params, $limit, $perpage);
    return $manualbilltemplates;
}

/* Returns the count of manual fee bill templates as per filter
 * @param: filter_params : filter for manual fee bill templates
 * @return: count of enteries of manual fee bill templates
 */

function get_manualbilltemplates_count($filter_params = array()) {
    global $DB;
    $select = '';
    $sort = '';
    $params = array();
    if (is_array($filter_params) && count($filter_params) > 0) {
        $select_params = array();
        foreach ($filter_params as $field => $value) {
            if ($field == 'name') {
                $select_params[] = "name like '%" . $value . "%'";
            } else if ($field == 'short_name') {
                $select_params[] = "short_name like '%" . $value . "%'";
            } else {
                $select_params[] = $field . '=:' . $field;
                $params[$field] = $value;
            }
        }
        $select .= "AND " . implode(" AND ", $select_params);
    }
    $count = $DB->count_records_sql("SELECT COUNT(DISTINCT id) FROM {fee_bill_templates} WHERE 1 AND deleted = 0 $select
                                  $sort order by id desc", $params);
    return $count;
}

/* Creates a bill_template_feeheads as per the data provided in the form.
 * @param data int : data from form
 * @return manualfeeheadid int : id of insertion in table
 */

function add_manualfeehead($data) {
    global $DB, $USER;
    $manualfeeheadid = $DB->insert_record('bill_template_feeheads', $data);
    $sql = "SELECT fbt.id, fbt.name as billname,cc.id as organizationid, cc.name as organization, p.name as progname "
            . "FROM {fee_bill_templates} fbt INNER JOIN {program} p "
            . "ON p.id = fbt.program "
            . "INNER JOIN {course_categories} cc ON cc.id = p.organization "
            . "WHERE fbt.id = $data->bill_template_id";
    $details = $DB->get_record_sql($sql);
    $eventorg = isset($details->organizationid) ? $details->organizationid : 0;
    $eventcontext = $eventorg ? CONTEXT_COURSECAT::instance($eventorg) : CONTEXT_SYSTEM::instance();
    $event = \local_programmanagement\event\manualbill_feehead_added::create(
                    array('context' => $eventcontext, 'userid' => $USER->id,
                        'objectid' => $manualfeeheadid,
                        'other' => array('progname' => $details->progname,
                            'organizationname' => $details->organization,
                            'billname' => $details->billname)
                    )
    );
    $event->trigger();
    return $manualfeeheadid;
}

/*  Updates bill_template_feeheads as per the data provided in the form.
 * @param data object : data from form 
 */

function update_manualfeehead($data) {
    global $DB, $USER;
    $DB->update_record('bill_template_feeheads', $data);
    $sql = "SELECT fbt.id, fbt.name as billname,cc.name as organization,cc.id as organizationid, p.name as progname "
            . "FROM {fee_bill_templates} fbt INNER JOIN {program} p "
            . "ON p.id = fbt.program "
            . "INNER JOIN {course_categories} cc ON cc.id = p.organization "
            . "WHERE fbt.id = $data->bill_template_id";
    $details = $DB->get_record_sql($sql);
    $eventorg = isset($details->organizationid) ? $details->organizationid : 0;
    $eventcontext = $eventorg ? CONTEXT_COURSECAT::instance($eventorg) : CONTEXT_SYSTEM::instance();
    $event = \local_programmanagement\event\manualbill_feehead_updated::create(
                    array('context' => $eventcontext, 'userid' => $USER->id,
                        'objectid' => $data->id,
                        'other' => array('progname' => $details->progname,
                            'organizationname' => $details->organization,
                            'billname' => $details->billname)
                    )
    );
    $event->trigger();
}

/* Deletes manual bill as per data
 * @param data object : data from form
 */

function delete_manualfeehead($data) {
    global $DB, $USER;
    $DB->delete_records('bill_template_feeheads', array('id' => $data->id));
    $sql = "SELECT fbt.id, fbt.name as billname,cc.name as organization,cc.id as organizationid, p.name as progname "
            . "FROM {fee_bill_templates} fbt INNER JOIN {program} p "
            . "ON p.id = fbt.program "
            . "INNER JOIN {course_categories} cc ON cc.id = p.organization "
            . "WHERE fbt.id = $data->bill_template_id";
    $details = $DB->get_record_sql($sql);
    $eventorg = isset($details->organizationid) ? $details->organizationid : 0;
    $eventcontext = $eventorg ? CONTEXT_COURSECAT::instance($eventorg) : CONTEXT_SYSTEM::instance();
    $event = \local_programmanagement\event\manualbill_feehead_deleted::create(
                    array('context' => $eventcontext, 'userid' => $USER->id,
                        'objectid' => $data->id,
                        'other' => array('progname' => $details->progname,
                            'organizationname' => $details->organization,
                            'billname' => $details->billname)
                    )
    );
    $event->trigger();
}

/*  Returns List of bill_template_feeheads of a program as per the filter
 *  @param filter_params : filter 
 *  @param perpage : enteries per page
 *  @param page int : page number
 *  @return manualfeeheads object : manual bill_template_feeheads
 */

function get_manualfeeheads($filter_params = array(), $page, $perpage) {
    global $DB;
    $params = array();
    $select = '';
    $sort = '';
    $limit = $page * $perpage;
    if (is_array($filter_params) && count($filter_params) > 0) {
        $select_params = array();
        foreach ($filter_params as $field => $value) {
            if ($field == 'type') {
                $select_params[] = 'bill.type = ' . $value;
            } else if ($field == 'fee_head') {
                $select_params[] = "feehead_data.name like '%$value%'";
            } else if ($field == 'fee_category') {
                $select_params[] = "cat.name like '%$value%'";
            } else if ($field == 'billid') {
                $select_params[] = 'bill.id = ' . $value;
            }
        }
        $select .= "AND " . implode(" AND ", $select_params);
    }
    $manualfeeheads = $DB->get_records_sql("SELECT (@i:=@i+1) sno,feehead.* FROM {bill_template_feeheads} as feehead join {fee_bill_templates} as bill
                                  on bill.id = feehead.bill_template_id join {fee_head} as feehead_data 
                                  on feehead_data.id = feehead.fee_head join {fee_category} as cat on cat.id = feehead.fee_category join (select @i := 0) serial
                                  WHERE 1 AND bill.deleted = 0 AND feehead_data.deleted = 0 AND cat.deleted = 0 $select
                                  $sort order by feehead.id desc", $params, $limit, $perpage);
    return $manualfeeheads;
}

/* Returns the count of bill_template_feeheads as per filter
 * @param filter_params array : filter for manual fee bill templates
 * @return count int : count of enteries of bill_template_feeheads
 */

function get_manualfeeheads_count($filter_params = array()) {
    global $DB, $USER;
    $select = '';
    $sort = '';
    $params = array();
    if (is_array($filter_params) && count($filter_params) > 0) {
        $select_params = array();
        foreach ($filter_params as $field => $value) {
            if ($field == 'type') {
                $select_params[] = 'bill.type = ' . $value;
            } else if ($field == 'fee_head') {
                $select_params[] = "feehead_data.name like '%$value%'";
            } else if ($field == 'billid') {
                $select_params[] = 'bill.id = ' . $value;
            }
        }
        $select .= "AND " . implode(" AND ", $select_params);
    }
    $count = $DB->count_records_sql("SELECT COUNT(DISTINCT feehead.id) FROM {bill_template_feeheads} as feehead join {fee_bill_templates} as bill
                                  on bill.id = feehead.bill_template_id join {fee_head} as feehead_data 
                                  on feehead_data.id = feehead.fee_head join {fee_category} as cat on cat.id = feehead.fee_category join (select @i := 0) serial
                                  WHERE 1 AND bill.deleted = 0 AND feehead_data.deleted = 0 AND cat.deleted = 0 $select
                                  $sort order by feehead.id desc", $params);
    return $count;
}

/* Creates a bill_template_finerules as per the data provided in the form.
 * @param : data from form
 * @return : ids of insertion in table
 */

function add_manualbillfine($data) {
    global $DB, $USER;
    $billtemplatefineid = $DB->insert_record('bill_template_finerules', $data);
    $sql = "SELECT fbt.id, fbt.name as billname,cc.name as organization,cc.id as organizationid, p.name as progname "
            . "FROM {fee_bill_templates} fbt INNER JOIN {program} p "
            . "ON p.id = fbt.program "
            . "INNER JOIN {course_categories} cc ON cc.id = p.organization "
            . "WHERE fbt.id = $data->bill_template_id";
    $details = $DB->get_record_sql($sql);
    $eventorg = isset($details->organizationid) ? $details->organizationid : 0;
    $eventcontext = $eventorg ? CONTEXT_COURSECAT::instance($eventorg) : CONTEXT_SYSTEM::instance();
    $event = \local_programmanagement\event\manualbill_fine_added::create(
                    array('context' => $eventcontext, 'userid' => $USER->id,
                        'objectid' => $billtemplatefineid,
                        'other' => array('progname' => $details->progname,
                            'organizationname' => $details->organization,
                            'billname' => $details->billname)
                    )
    );
    $event->trigger();
    return $billtemplatefineid;
}

/*  Updates bill_template_finerules as per the data provided in the form.
 * @param : data from form
 * @return : ids of insertion in table 
 */

function update_manualbillfine($data) {
    global $DB, $USER;
    $DB->update_record('bill_template_finerules', $data);
    $sql = "SELECT fbt.id, fbt.name as billname,cc.name as organization,cc.id as organizationid, p.name as progname "
            . "FROM {fee_bill_templates} fbt INNER JOIN {program} p "
            . "ON p.id = fbt.program "
            . "INNER JOIN {course_categories} cc ON cc.id = p.organization "
            . "WHERE fbt.id = $data->bill_template_id";
    $details = $DB->get_record_sql($sql);
    $eventorg = isset($details->organizationid) ? $details->organizationid : 0;
    $eventcontext = $eventorg ? CONTEXT_COURSECAT::instance($eventorg) : CONTEXT_SYSTEM::instance();
    $event = \local_programmanagement\event\manualbill_fine_updated::create(
                    array('context' => $eventcontext, 'userid' => $USER->id,
                        'objectid' => $data->id,
                        'other' => array('progname' => $details->progname,
                            'organizationname' => $details->organization,
                            'billname' => $details->billname)
                    )
    );
    $event->trigger();
}

/* Deletes manual bill_template_finerules as per data provided in the form 
 */

function delete_manualbillfine($data) {
    global $DB, $USER;
    $DB->delete_records('bill_template_finerules', array('id' => $data->id));
    $sql = "SELECT fbt.id, fbt.name as billname,cc.name as organization,cc.id as organizationid, p.name as progname "
            . "FROM {fee_bill_templates} fbt INNER JOIN {program} p "
            . "ON p.id = fbt.program "
            . "INNER JOIN {course_categories} cc ON cc.id = p.organization "
            . "WHERE fbt.id = $data->bill_template_id";
    $details = $DB->get_record_sql($sql);
    $eventorg = isset($details->organizationid) ? $details->organizationid : 0;
    $eventcontext = $eventorg ? CONTEXT_COURSECAT::instance($eventorg) : CONTEXT_SYSTEM::instance();
    $event = \local_programmanagement\event\manualbill_fine_deleted::create(
                    array('context' => $eventcontext, 'userid' => $USER->id,
                        'objectid' => $data->id,
                        'other' => array('progname' => $details->progname,
                            'organizationname' => $details->organization,
                            'billname' => $details->billname)
                    )
    );
    $event->trigger();
}

/*  Returns List of bill_template_finerules of a program as per the filter
 *  @param filter_params array : filters
 *  @param perpage int : enteries per page
 *  @param page int : page number
 *  @return manualfinerules object : manual bill_template_finerules
 */

function get_manualfines($filter_params = array(), $page, $perpage) {
    global $DB;
    $params = array();
    $select = '';
    $sort = '';
    $startdate = '';
    $enddate = '';
    $date_filter = '';
    $limit = $page * $perpage;
    if (is_array($filter_params) && count($filter_params) > 0) {
        $select_params = array();
        foreach ($filter_params as $field => $value) {
            if ($field == 'type') {
                $select_params[] = 'bill.type = ' . $value;
            } else if ($field == 'fine_type') {
                $select_params[] = "fine.fine_type = " . $value;
            } else if ($field == 'billid') {
                $select_params[] = 'bill.id = ' . $value;
            } else if ($field == 'date_from') {
                $startdate = $value;
            } else if ($field == 'date_to') {
                $enddate = $value;
            }
        }
        $select .= "AND " . implode(" AND ", $select_params);
    }
    if (!empty($startdate) && !empty($enddate)) {
        $date_filter = 'AND fine.date_from >= ' . strtotime($startdate) . ' AND fine.date_to <= ' . strtotime($enddate);
    } else if (!empty($startdate) && empty($enddate)) {
        $date_filter = 'AND fine.date_from >= ' . strtotime($startdate) . ' AND fine.date_to <= ' . time();
    }
    $manualfinerules = $DB->get_records_sql("SELECT (@i:=@i+1) sno,fine.* FROM {bill_template_finerules} as fine join {fee_bill_templates} as bill
                                  on bill.id = fine.bill_template_id join (select @i := 0) serial
                                  WHERE 1 $select $date_filter
                                  $sort order by fine.id desc", $params, $limit, $perpage);
    return $manualfinerules;
}

/* Returns the count of bill_template_finerules as per filter
 * @param filter_params array : filter for bill_template_finerules
 * @return count int : count of enteries of bill_template_finerules
 */

function get_manualfines_count($filter_params = array()) {
    global $DB;
    $select = '';
    $sort = '';
    $startdate = '';
    $enddate = '';
    $date_filter = '';
    $params = array();
    if (is_array($filter_params) && count($filter_params) > 0) {
        $select_params = array();
        foreach ($filter_params as $field => $value) {
            if ($field == 'type') {
                $select_params[] = 'bill.type = ' . $value;
            } else if ($field == 'fine_type') {
                $select_params[] = "fine.fine_type = " . $value;
            } else if ($field == 'billid') {
                $select_params[] = 'bill.id = ' . $value;
            } else if ($field == 'date_from') {
                $startdate = $value;
            } else if ($field == 'date_to') {
                $enddate = $value;
            }
        }
        $select .= "AND " . implode(" AND ", $select_params);
    }
    if (!empty($startdate) && !empty($enddate)) {
        $date_filter = 'AND fine.date_from >= ' . strtotime($startdate) . ' AND fine.date_to <= ' . strtotime($enddate);
    } else if (!empty($startdate) && empty($enddate)) {
        $date_filter = 'AND fine.date_from >= ' . strtotime($startdate) . ' AND fine.date_to <= ' . time();
    }
    $count = $DB->count_records_sql("SELECT COUNT(DISTINCT fine.id) FROM {bill_template_finerules} as fine join {fee_bill_templates} as bill
                                  on bill.id = fine.bill_template_id join (select @i := 0) serial
                                  WHERE 1 $select $date_filter
                                  $sort order by fine.id desc", $params);
    return $count;
}

/* Extend navigation settings
 * @param nav object
 * @param context object
 */

function nouse_local_programmanagement_extends_settings_navigation(settings_navigation $nav, context $context) {
    global $CFG;
    require_once($CFG->libdir . '/coursecatlib.php');
    if (has_capability_in_organization('local/programmanagement:manageprogram') ||
            has_capability_in_organization('local/programmanagement:viewprogramlist')) {
        if (!($apeejaynode = $nav->find('nav_apeejayadministration', navigation_node::TYPE_CONTAINER))) {
            $apeejaynode = $nav->prepend(new lang_string('apeejayadministration', 'local_programmanagement'), null, navigation_node::TYPE_CONTAINER, null, 'nav_apeejayadministration');
        }
        $catnode = $apeejaynode->add(get_string('programmanagement', 'local_programmanagement'), null, navigation_node::TYPE_CONTAINER, null, 'nav_programmanagement');
        if (has_capability_in_organization('local/programmanagement:manageprogram')) {
            $leafnode = $catnode->add(get_string('addprogram', 'local_programmanagement'), new moodle_url('/local/programmanagement/addprogram.php'), navigation_node::TYPE_CONTAINER, null, 'nav_addprogrammanagement');
        }
        $leafnode = $catnode->add(get_string('programlist', 'local_programmanagement'), new moodle_url('/local/programmanagement/programlist.php'), navigation_node::TYPE_CONTAINER, null, 'nav-programmanagementlist');

        if (has_capability_in_organization('local/programmanagement:managerefund')) {
            $catnode1 = $apeejaynode->add(get_string('refundmanagement', 'local_programmanagement'), null, navigation_node::TYPE_CONTAINER, null, 'nav_refundmanagement');
            $leafnode1 = $catnode1->add(get_string('refund', 'local_programmanagement'), new moodle_url($CFG->wwwroot . '/local/programmanagement/refund/index.php'), navigation_node::TYPE_CONTAINER, null, 'nav_refund');
            $leafnode2 = $catnode1->add(get_string('refundhistory', 'local_programmanagement'), new moodle_url($CFG->wwwroot . '/local/programmanagement/refund/history.php'), navigation_node::TYPE_CONTAINER, null, 'nav_refundhistory');
            $leafnode2 = $catnode1->add(get_string('refundtouser', 'local_programmanagement'), new moodle_url($CFG->wwwroot . '/local/programmanagement/refund/refunduser.php'), navigation_node::TYPE_CONTAINER, null, 'nav_refunduser');
        }
    }
}

/* Returns all organizations/categories after checking capabilities role wise
 * @param capability string
 * @return organization_list array
 */

function get_all_organization_cat($capability = '', $userid = '') {
    global $DB, $CFG, $USER, $MYORG_ROLES_AND_CAPS;
    $userid = empty($userid) ? $USER->id : $userid;
    $organization_list = array();

    if (!empty($capability)) {
        if (is_siteadmin($userid)) {
            $sql = "SELECT cc.id, cc.name FROM {course_categories} cc WHERE cc.parent = :parent";
            $organization_list = $DB->get_records_sql_menu($sql, array('parent' => 0));
        } else {
            if (!isset($MYORG_ROLES_AND_CAPS->myorgs[$userid])) {
                get_or_set_my_organizational_capabilities($userid);
            }

            if (!empty($MYORG_ROLES_AND_CAPS->myorgs[$userid])) {

                foreach ($MYORG_ROLES_AND_CAPS->myorgs[$userid] as $orgid => $myorg) {
                    if (!empty($MYORG_ROLES_AND_CAPS->myorgs[$userid][$myorg->id]->rolesandcaps)) {
                        foreach ($MYORG_ROLES_AND_CAPS->myorgs[$userid][$myorg->id]->rolesandcaps as $roleid => $roleandcaps) {
                            $caps = array_keys($roleandcaps);
                            if (in_array($capability, $caps) && ($roleandcaps[$capability] >= 0)) {
                                $organization_list[$myorg->id] = $myorg->name;
                            }
                        }
                    }
                }
            }
        }
    }

    return $organization_list;
}

/*
 * if I am a user and assigned in program of organization - but not directly into the context of organization
 * Get all organizations of my enrolled programs where I am a student
 * and put this information session
 * @param userid int
 * @return array of organizations
 * 
 */

function get_my_programs_organizations($userid = '', $keysonly = false) {
    global $DB, $USER;

    $userid = empty($userid) ? $USER->id : $userid;
    $orgs = array();

    $sql = "SELECT DISTINCT cc1.id, cc1.name FROM 
            (SELECT SUBSTRING_INDEX( SUBSTRING_INDEX( cc.path, '/', 3 ) , '/', -1 ) orgid
                FROM {cohort}  ch
                INNER JOIN {program} p ON ch.id = p.cohort_id
                INNER JOIN {cohort_members} chm ON ch.id = chm.cohortid
                INNER JOIN {context} ctx ON ctx.id = ch.contextid AND ctx.contextlevel = 40
                INNER JOIN {course_categories} cc ON cc.id = ctx.instanceid
                WHERE chm.userid = :userid) org 
            INNER JOIN {course_categories} cc1 ON cc1.id = org.orgid";

    $params = array('userid' => $userid);

    $rec = $DB->get_records_sql($sql, $params);
    if ($rec) {
        if ($keysonly) {
            $orgs = array_keys($rec);
        } else {
            $orgs = $rec;
        }
    }
    return $orgs;
}

/*
 * Get my enrolled organizations: Return course categories where user is enrolled directly into the context of course category
 * @param int userid
 * @return array of objects / NULL
 */

function get_my_enrolled_organizations_and_roles($userid = '') {
    global $DB, $USER;

    $userid = empty($userid) ? $USER->id : $userid;
    //roles assigned on org level
    $sql = "SELECT DISTINCT cc1.id, cc1.name, org.roleid FROM 
            (SELECT ra.userid, ra.roleid, SUBSTRING_INDEX( SUBSTRING_INDEX( cc.path, '/', 3 ) , '/', -1 ) orgid 
                FROM {role_assignments} ra
                INNER JOIN {context} ctx ON ra.contextid = ctx.id AND ctx.contextlevel = 40
                INNER JOIN {course_categories} cc ON cc.id = ctx.instanceid WHERE ra.userid = :userid) org 
            INNER JOIN {course_categories} cc1 ON cc1.id = org.orgid";

    $params = array('userid' => $userid);
    $orgroles = $DB->get_records_sql($sql, $params);
    return $orgroles;
}

/*
 * SET Organization wise roles capabilities
 * for the current user into $USER global object i,e. $USER->myorgs[orgid]->rolesandcaps[roleid] = array(capability1=>1, .......)
 * @param userid int
 * @return SET myorgs into $USER global object
 * 
 */

function get_or_set_my_organizational_capabilities($userid = '') {
    global $DB, $USER, $MYORG_ROLES_AND_CAPS;

    $userid = empty($userid) ? $USER->id : $userid;

    $MYORG_ROLES_AND_CAPS->myorgs[$userid] = array();

    //organizations where user is enrolled directly inot the context of organization
    $orgroles = get_my_enrolled_organizations_and_roles($userid);

    if (!empty($orgroles)) {

        foreach ($orgroles as $orgrole) {

            if (!in_array($orgrole->id, $MYORG_ROLES_AND_CAPS->myorgs[$userid])) {
                $myorg = new stdClass();
                $myorg->id = $orgrole->id;
                $myorg->name = $orgrole->name;
                $myorg->rolesandcaps = array();
                $MYORG_ROLES_AND_CAPS->myorgs[$userid][$myorg->id] = $myorg;
            }

            if (!in_array($orgrole->roleid, $MYORG_ROLES_AND_CAPS->myorgs[$userid][$orgrole->id]->rolesandcaps)) {
                $context = CONTEXT_COURSECAT::instance($orgrole->id);
                $caps = role_context_capabilities($orgrole->roleid, $context);
                $MYORG_ROLES_AND_CAPS->myorgs[$userid][$orgrole->id]->rolesandcaps[$orgrole->roleid] = $caps;
            }
        }
    }

    //program organizations, where user is enrolled through programs
    $progorgs = get_my_programs_organizations($userid, FALSE);

    if (!empty($progorgs)) {
        $roles = get_all_roles();

        $default = '';
        foreach ($roles as $role) {
            if ($role->shortname == 'student') {
                $default = $role->id;
            }
        }
        $programroleid = get_config('local_programmanagement', 'program_users_role');
        $programroleid = empty($programroleid) ? $default : $programroleid;

        foreach ($progorgs as $orgrole) {

            if (!in_array($orgrole->id, $MYORG_ROLES_AND_CAPS->myorgs[$userid])) {
                $myorg = new stdClass();
                $myorg->id = $orgrole->id;
                $myorg->name = $orgrole->name;
                $myorg->rolesandcaps = array();
                $MYORG_ROLES_AND_CAPS->myorgs[$userid][$myorg->id] = $myorg;
            }

            if (!in_array($programroleid, $MYORG_ROLES_AND_CAPS->myorgs[$userid][$orgrole->id]->rolesandcaps)) {
                $context = CONTEXT_COURSECAT::instance($orgrole->id);
                $caps = role_context_capabilities($programroleid, $context);
                $MYORG_ROLES_AND_CAPS->myorgs[$userid][$myorg->id]->rolesandcaps[$programroleid] = $caps;
            }
        }
    }
}

/* Return true if any of the capability are true in case of array and true/false as per string
 *  @param capability array of capability or string
 *  @param categoryid int
 *  @return TRUE / FALSE
 */

function has_capability_in_organization($capability = '', $categoryid = '', $userid = '') {
    global $DB;
    if (is_array($capability)) {
        $count = 0;
        foreach ($capability as $cap) {
            $orgs = get_all_organization_cat($cap, $userid);
            if (!empty($orgs)) {
                $keys = array_keys($orgs);
                if (!empty($categoryid) && $categoryid != 0) {
                    if (in_array($categoryid, $keys)) {
                        $count++;
                    }
                } else {
                    $count++;
                }
            }
        }
        if ($count > 0) {
            return TRUE;
        } else {
            return FALSE;
        }
    } else {
        $orgs = get_all_organization_cat($capability, $userid);
        if (!empty($orgs)) {
            $keys = array_keys($orgs);
            if (!empty($categoryid) && $categoryid != 0) {
                if (in_array($categoryid, $keys)) {
                    return true;
                }
            } else {
                return true;
            }
        } else {
            return false;
        }
    }
}

/* Return true if all of the capability are true
 * @param capability : array of capability or string
 * @param categoryid int
 * @return TRUE / FALSE
 */

function has_allcapability_in_organization($capability = '', $categoryid = '', $userid = '') {
    global $DB;
    if (is_array($capability)) {
        $count = 0;
        foreach ($capability as $cap) {
            $orgs = get_all_organization_cat($cap, $userid);
            if (!empty($orgs)) {
                $keys = array_keys($orgs);
                if (!empty($categoryid) && $categoryid != 0) {
                    if (in_array($categoryid, $keys)) {
                        $count++;
                    }
                } else {
                    $count++;
                }
            }
        }
        if ($count == COUNT($capability)) {
            return TRUE;
        } else {
            return FALSE;
        }
    } else {
        $orgs = get_all_organization_cat($capability, $userid);
        if (!empty($orgs)) {
            $keys = array_keys($orgs);
            if (!empty($categoryid) && $categoryid != 0) {
                if (in_array($categoryid, $keys)) {
                    return true;
                }
            } else {
                return true;
            }
        } else {
            return false;
        }
    }
}

/* Gets all the plans as per the plan type for a program id
 *  @param pid int : Program id
 *  @return plans_list array : array of plans list
 */

function get_plans($pid = 0) {
    global $DB;
    $plan_list = array();
    $sql = 'select plan.id,plan.plan_type from {prepayment_plans} as plan '
            . 'INNER JOIN {program} as p ON p.organization = plan.organization '
            . 'WHERE p.deleted = 0 AND plan.deleted = 0 AND p.id =' . $pid;
    $plan_comp = $DB->get_records_sql($sql);
    foreach ($plan_comp as $key => $plan) {
        $plan_list[$plan->id] = $plan->plan_type;
    }
    $plan_list = array('' => 'Select Plan Type') + $plan_list;
    return $plan_list;
}

/* Returns the cycles for the program and plan id
 * @param programid int : program id
 * @param planid int : plan id
 * @return cycle_list array
 */

function get_cycles($programid, $planid) {
    global $DB;
    $cycle_list = array();
    $sql = "SELECT fbt.id as id ,ppi.instance_name FROM {prepayment_plan_instance} ppi "
            . "INNER JOIN {fee_bill_templates} fbt ON fbt.id = ppi.bill_template_id "
            . "WHERE ppi.deleted = 0 AND fbt.deleted = 0 AND ppi.program = $programid AND ppi.pre_paymentplan_id = $planid";
    $cycle_comp = $DB->get_records_sql($sql);
    foreach ($cycle_comp as $key => $cycle) {
        $cycle_list[$cycle->id] = $cycle->instance_name;
    }
    $cycle_list = array('' => 'Select Bill Cycle') + $cycle_list;
    return $cycle_list;
}

/* Returns cycles of bill template for a program and plantype
 * @param pid int : program id
 * @param planid int : plan id
 * @return cycle_list array
 */

function get_cycles_billtemplate($pid, $planid) {
    global $DB;
    $cycle_list = array();
    $cycle_comp = $DB->get_records('prepayment_plan_instance', array('program' => $pid, 'pre_paymentplan_id' => $planid, 'deleted' => 0));
    foreach ($cycle_comp as $key => $cycle) {
        $cycle_list[$cycle->bill_template_id] = $cycle->instance_name;
    }
    $cycle_list = array('' => 'Select Bill Cycle') + $cycle_list;
    return $cycle_list;
}

/* Returns cohort users with count for a list of programs
 * @param programlist array
 * @return cohort_user_count array
 */

function get_cohort_users($programlist) {
    global $DB;
    $cohort_user_count = array();
    foreach ($programlist as $key => $program) {
        $count = $DB->count_records_sql("SELECT COUNT(id) FROM {cohort_members} WHERE cohortid = $program->cohort_id");
        $cohort_user_count[$program->id] = $count;
    }
    return $cohort_user_count;
}

/* Print all tabs required for 
 * Program Management 
 * @param int $pid Program Id
 * @return null renders the tab object
 */

function print_program_tabs($pid) {
    global $PAGE, $DB;
    $tabs = array();

    $program_data = $DB->get_record('program', array('id' => $pid));
    if (empty($program_data)) {
        print_error(get_string('programnotfound', 'local_programmanagement'));
    }

    $tab_settings_cap = array('local/programmanagement:manageprogram');
    $tab_enrolledusers_cap = array('local/programmanagement:manageuserenrollment');
    $tab_bulkenrolment_cap = array('local/programmanagement:managebulkenrollment');
    $tab_prepaymentplan_cap = array('local/programmanagement:manageprogram', 'local/programmanagement:viewprogramlist', 'local/prepaymentplan:manageprepaymentplan');
    $tab_prepaymentplanassociation_cap = array('local/programmanagement:manageplanassociation', 'local/programmanagement:viewplanassociation', 'local/programmanagement:viewplanassociationhistory');
    $tab_feeheadassociation_cap = array('local/programmanagement:managefeeheadnassociation', 'local/programmanagement:viewfeeheadnassociation');
    $tab_feeraisedates_cap = array('local/programmanagement:managefeeraisedate');
    $tab_scholarship_cap = array('local/programmanagement:manageprogramscholarship');
    $tab_scholarshipassociation_cap = array('local/programmanagement:managescholarshipassociation', 'local/programmanagement:viewscholarshipassociation');
    $tab_plancycleassociation_cap = array('local/programmanagement:manageplancycleassociation', 'local/programmanagement:viewplancycleassociation', 'local/programmanagement:savedraftstatusplancycle', 'local/programmanagement:generateregularbills', 'local/programmanagement:cancelregularbills', 'local/programmanagement:overrideplancycleassociation', 'local/programmanagement:reloadscholarship', 'local/programmanagement:savedraftstatusplancycle');
    $tab_manualfeebill_cap = array('local/programmanagement:managemanualbill', 'local/programmanagement:viewmanualbill', 'local/programmanagement:manageprogram');
    $tab_manualfeebillassociation_cap = array('local/programmanagement:managemanualassociation', 'local/programmanagement:viewmanualassociation', 'local/programmanagement:savedraftstatusmanualbill', 'local/programmanagement:generatemanualbills', 'local/programmanagement:cancelmanualbills', 'local/programmanagement:overridemanualassociation');
    $tab_generatedbills_cap = array('local/programmanagement:viewbillsothers', 'local/programmanagement:manageprogram', 'local/programmanagement:viewfeebills', 'local/programmanagement:adjustbill', 'local/programmanagement:manualadjustbill');
    $tab_struckoffstudents_cap = array('local/programmanagement:manageprogram', 'local/programmanagement:viewrollstruckusers');
    $tab_rollovertransition_cap = array('local/programmanagement:managerollovertransition');
    //define program management tabs
    $programtabs = array(
        'tab_settings' => new moodle_url('/local/programmanagement/addprogram.php', array('id' => $pid)),
        'tab_enrolledusers' => new moodle_url('/local/programmanagement/userenrollmentlist.php', array('pid' => $pid)),
        'tab_bulkenrolment' => new moodle_url('/local/programmanagement/uploadusers/index.php', array('pid' => $pid)),
        'tab_prepaymentplan' => new moodle_url('/local/programmanagement/prepaymentplan/prepaymentplan_list.php', array('pid' => $pid)),
        'tab_prepaymentplanassociation' => new moodle_url('/local/programmanagement/prepaymentplan/planassociation.php', array('pid' => $pid)),
        'tab_feeheadassociation' => new moodle_url('/local/programmanagement/prepaymentplan/feeheadassociation.php', array('pid' => $pid)),
        'tab_feeraisedates' => new moodle_url('/local/programmanagement/feeraisedate/feeraise_date.php', array('pid' => $pid)),
        'tab_scholarship' => new moodle_url('/local/programmanagement/scholarship/scholarshipcriteria.php', array('pid' => $pid)),
        'tab_scholarshipassociation' => new moodle_url('/local/programmanagement/scholarship/association.php', array('pid' => $pid)),
        'tab_plancycleassociation' => new moodle_url('/local/programmanagement/prepaymentplan/plancycleassociation.php', array('pid' => $pid)),
        'tab_manualfeebill' => new moodle_url('/local/programmanagement/manualbill/manualbill.php', array('pid' => $pid)),
        'tab_manualfeebillassociation' => new moodle_url('/local/programmanagement/manualbill/manualbillassociation.php', array('pid' => $pid)),
        'tab_generatedbills' => new moodle_url('/local/programmanagement/feebill/feebilllist.php', array('pid' => $pid)),
        'tab_struckoffstudents' => new moodle_url('/local/programmanagement/rollstruckoff.php', array('pid' => $pid)),
        'tab_rollovertransition' => new moodle_url('/local/programmanagement/rollovertransition.php', array('pid' => $pid)),
    );

    //define additional links for common tabs
    $programtabs_extralinks = array(
        'tab_scholarship_extralinks' => array(
            new moodle_url('/local/programmanagement/scholarship/add_scholarshipcriteria.php'),
            new moodle_url('/local/programmanagement/scholarship/addscholarship_raisedate.php')
        ),
        'tab_prepaymentplan_extralinks' => array(
            new moodle_url('/local/programmanagement/prepaymentplan/addcycle.php'),
            new moodle_url('/local/programmanagement/prepaymentplan/addfine.php'),
            new moodle_url('local/programmanagement/prepaymentplan/cyclelist.php'),
        ),
        'tab_manualfeebill_extralinks' => array(
            new moodle_url('/local/programmanagement/manualbill/manualfine.php'),
            new moodle_url('/local/programmanagement/manualbill/manualfeeheads.php'),
            new moodle_url('/local/programmanagement/manualbill/addmanualfeehead.php'),
            new moodle_url('/local/programmanagement/manualbill/addmanualfine.php'),
            new moodle_url('/local/programmanagement/manualbill/addmanualbill.php'),
        ),
        'tab_bulkenrolment_extralinks' => array(
            new moodle_url('/local/programmanagement/uploadusers/')
        ),
        'tab_generatedbills_extralinks' => array(
            new moodle_url('/local/programmanagement/feebill/'),
            new moodle_url('/local/programmanagement/adjust_user_fee_bill.php')
        ),
        'tab_prepaymentplanassociation_extralinks' => array(
            new moodle_url('local/programmanagement/prepaymentplan/plan_historyassociation.php'),
        )
    );

    $strextralinks = "_extralinks";


    $selected = '';
    foreach ($programtabs as $tabkey => $tablink) {
        if (has_capability_in_organization(${$tabkey . "_cap"}, $program_data->organization)) {
            $tabs[] = new tabobject($tabkey, $tablink, get_string($tabkey, 'local_programmanagement'));
        }
    }
    //check the active tab
    foreach ($programtabs as $tabkey => $tablink) {
        $pattern = '/' . preg_quote(parse_url($tablink)['path'], '/') . '/';
        if (preg_match($pattern, $PAGE->url)) {
            $selected = $tabkey;
            break;
        }
        //check for the additional links that may lies within active tabs
        if (isset($programtabs_extralinks[$tabkey . $strextralinks]) && empty($selected)) {
            foreach ($programtabs_extralinks[$tabkey . $strextralinks] as $extralinks) {
                if (!empty($extralinks)) {
                    $patternextra = '/' . preg_quote(parse_url($extralinks)['path'], '/') . '/';
                    if (preg_match($patternextra, $PAGE->url)) {
                        $selected = $tabkey;
                        break;
                    }
                }
            }
        }
    }
    $output = '';

    $output .= html_writer::start_div('program-tabs');
    $output .= print_tabs(array($tabs), $selected, array('tab_regularfeebill'), null, $return = true);
    $output .= html_writer::end_div();

    echo $output;
}

/*
 * define and association header class
 * @name PARAM_ALPHAEXT Name of Header
 * @id PARAM_INT  id of the header string
 * @return a unique class name
 */

function get_association_header_css_class($name, $id = '') {
    $classname = '';
    $classname = clean_param($name, PARAM_ALPHANUMEXT);
    if ($id) {
        $classname .= $id;
    }
    return $classname;
}

/*
 * To print override buttion bill association page
 */

//check can override
function get_options_for_override_button() {
    global $PAGE, $USER;

    $options = new stdClass();

    $override = optional_param('override', '', PARAM_TEXT);   // can overide
    $isoverride = optional_param('isoverride', '', PARAM_INT);   // can overide
    $reloadscholarship = optional_param('reloadscholarship', '', PARAM_TEXT);   // can overide
    $plansubmit = optional_param('plansubmit', '', PARAM_TEXT);   // can overide
    $update = optional_param('update', '', PARAM_TEXT);   // can overide



    $ifoverride = 0;

    $txtoverrideon = get_string('overrideon', 'local_programmanagement');
    $txtoverrideoff = get_string('overrideoff', 'local_programmanagement');


    if (!empty($override)) {
        if ($override == $txtoverrideon) {
            $ifoverride = 1;
            $txtoverride = $txtoverrideoff;
        } else {
            $ifoverride = 0;
            $txtoverride = $txtoverrideon;
        }
    } else {
        $ifoverride = $isoverride;
        $txtoverride = $ifoverride ? $txtoverrideoff : $txtoverrideon;
    }
    if ($plansubmit || $update) {
        $reloadscholarship = 0;
    } else {
        $reloadscholarship = 1;
    }

    $options->ifoverride = $ifoverride;
    $options->txtoverride = $txtoverride;
    $options->reloadscholarship = $reloadscholarship;
    $options->plansubmit = $plansubmit;
    $options->update = $update;

    if (strpos($PAGE->url, '/local/programmanagement/manualbill/manualbillassociation.php') > 0) {
        if (has_capability_in_organization('local/programmanagement:overridemanualassociation')) {
            $options->ifoverride = $ifoverride;
            $options->txtoverride = $txtoverride;
        }
        if (has_capability_in_organization('local/programmanagement:managemanualassociation')) {
            $options->plansubmit = $plansubmit;
            $options->update = $update;
        } else {
            $options->plansubmit = 0;
            $options->update = 0;
        }
    } else if (strpos($PAGE->url, '/local/programmanagement/prepaymentplan/plancycleassociation.php') > 0) {
        if (has_capability_in_organization('local/programmanagement:overrideplancycleassociation')) {
            $options->ifoverride = $ifoverride;
            $options->txtoverride = $txtoverride;
        }
        if (has_capability_in_organization('local/programmanagement:reloadscholarship')) {
            $options->reloadscholarship = $reloadscholarship;
        } else {
            $options->reloadscholarship = 0;
        }
        if (has_capability_in_organization('local/programmanagement:viewplancycleassociation')) {
            $options->plansubmit = $plansubmit;
            $options->update = $update;
        } else {
            $options->plansubmit = 0;
            $options->update = 0;
        }
    }

    return $options;
}

/* Return the default amount of a feehead
 * @param feeheadid int
 * @return amount itn : fee head default amount
 */

function get_default_amount($feeheadid) {
    global $DB;
    $amount = 0;
    if (!empty($feeheadid)) {
        $feehead_data = $DB->get_record_sql('SELECT defaultamount FROM {fee_head} WHERE deleted = 0 AND id = ' . $feeheadid);
        $amount = $feehead_data->defaultamount;
    }
    return $amount;
}

/**
 * Get the bill details by id
 *
 * @param $id bill generated id
 * @param $fields this is optional param to get field which not defined
 * @global $DB database object to access records
 * @return array()
 */
function get_generated_bill_details($id, $fields = '') {
    global $DB;
    if (!empty($fields)) {
        $fields .= ', ' . $fields;
    }
    // added serial sno and ignore multiple to avoid multiple records issue 
    // Need to be verified and checked
    $sql = 'select (@i:=@i+1) sno,user.id as enrolno , user.idnumber as uid,user.email,user_fee_bill_id, ufbd.bill_date, ufbd.due_date,'
            . 'ufbd.cancelledtime,ufbd.cancelledby,ufbd.cancel_comment,ufbd.parentbillid,ufbd.discardfine,ufbd.comment,ufb.modifierid, '
            . 'fbt.name , fbt.type,ufb.bill_template_id,fbt.program as pid, ppi.id as planid,p.organization, '
            . 'concat(user.firstname, " ", user.lastname) as user_name,draft,'
            . 'cancelstatus, ufb.billpaid, ufbd.parentbillid,bill_number, ufbd.readmission_charge,ufbd.roll_struck_off, ufbd.roll_struck_off_date ' . $fields
            . 'from {user_fee_bill_details} ufbd '
            . 'inner join  {user_fee_bill} ufb on  ufbd.user_fee_bill_id = ufb.id '
            . 'inner join {user} user on user.id = ufb.userid '
            . 'inner join {fee_bill_templates} fbt on fbt.id = ufb.bill_template_id '
            . 'inner join {program} p ON fbt.program = p.id '
            . 'LEFT join {prepayment_plan_instance} ppi ON ppi.bill_template_id = fbt.id '
            . 'join (select @i := 0) serial '
            . 'where ufbd.user_fee_bill_id =:user_fee_bill_id';
    $adjustbilldata = $DB->get_record_sql($sql, array('user_fee_bill_id' => $id), IGNORE_MULTIPLE);
    return $adjustbilldata;
}

/**
 * Get the bill amount, refundable amount, head name and comments.
 *
 * @param $user_fee_bill_id bill generated id
 * @param $fields this is optional param to get field which not defined
 * @global $DB database object to access records
 * @return array()
 */
function get_generated_bill_fee_heads($user_fee_bill_id, $fields = '') {
    global $DB;
    if (!empty($fields)) {
        $fields .= ', ' . $fields;
    }
    $billheads = $DB->get_records_sql('select ubfh.id, ubfh.amount, ubfh.refundable, fh.name, '
            . 'ubfh.comment,fh.feecategory,fc.name as feecatname ' . $fields
            . 'from {user_bill_fee_heads} ubfh '
            . 'inner join {fee_head} fh on fh.id = ubfh.fee_head '
            . 'inner join {fee_category} fc on fc.id = fh.feecategory '
            . 'where user_fee_bill_id =:user_fee_bill_id ORDER BY fh.feecategory', array('user_fee_bill_id' => $user_fee_bill_id));
    return $billheads;
}

/**
 * Get the scholarship of given bill generated id
 *
 * @param $user_fee_bill_id bill generated id
 * @param $fields this is optional param to get field which not defined
 * @global $DB database object to access records
 * @return array()
 */
function get_generated_bill_scholarship($user_fee_bill_id, $fields = '') {
    global $DB;
    if (!empty($fields)) {
        $fields .= ', ' . $fields;
    }
    $billscholarship = $DB->get_record('user_fee_bill_scholarship', array('user_fee_bill_id' => $user_fee_bill_id), 'scholarshipdetails, scholarship_amount, amount_overridden, comment' . $fields);
    return $billscholarship;
}

/**
 * Get the fine of generated bill
 *
 * @param $user_fee_bill_id bill generated id
 * @param $fields this is optional param to get field which not defined
 * @global $DB database object to access records
 * @return array()
 */
function get_generated_bill_fine($user_fee_bill_id, $fields = '') {
    global $DB;
    if (!empty($fields)) {
        $fields .= ', ' . $fields;
    }
    $billfine = $DB->get_record('user_fee_bill_fine', array('user_fee_bill_id' => $user_fee_bill_id));
    return $billfine;
}

/**
 * Get the fine rules of generated bill
 *
 * @param $bill_template_id bill generated id
 * @param $fields this is optional param to get field which not defined
 * @global $DB database object to access records
 * @return array()
 */
function get_generated_bill_fine_rules($bill_template_id, $id = 0, $fields = '') {
    global $DB;
    if (!empty($fields)) {
        $fields .= ', ' . $fields;
    }
    $finerules = $DB->get_records('bill_template_finerules', array('bill_template_id' => $bill_template_id, 'user_fee_bill_id' => $id), 'date_from, date_to, fine_type, amount, include_previous' . $fields);
    if (!empty($finerules)) {
        foreach ($finerules as $key => $finerule) {
            $finerules[$key]->fine_type_text = get_fine_fine_type_text($finerule->fine_type);
        }
    } else {
        $sql = "SELECT btf.date_from, btf.date_to, btf.fine_type, btf.amount, btf.include_previous "
                . $fields
                . " FROM {bill_template_finerules} btf"
                . " WHERE btf.bill_template_id = :bill_template_id AND btf.user_fee_bill_id = 0";

        $finerules = $DB->get_records_sql($sql, array('bill_template_id' => $bill_template_id));
        foreach ($finerules as $key => $finerule) {
            $finerules[$key]->fine_type_text = get_fine_fine_type_text($finerule->fine_type);
        }
    }
    return $finerules;
}

/**
 * Get the fine type text
 *
 * @param $finetype bill generated fine head type id
 * @global $DB database object to access records
 * @return fine head type name/flag
 */
function get_fine_fine_type_text($finetype) {
    switch ($finetype) {
        case FINE_RULE_PERDAY : {
                return get_string('perday', 'local_programmanagement');
            }
        case FINE_RULE_PERWEEK : {
                return get_string('perweek', 'local_programmanagement');
            }
        case FINE_RULE_PERMONTH : {
                return get_string('permonth', 'local_programmanagement');
            }
        case FINE_RULE_LUMPSUM : {
                return get_string('lumpsum', 'local_programmanagement');
            }
            deafult : {
                return;
            }
    }
}

/**
 * Get the fine type text
 *
 * @param $finetype bill generated fine head type id
 * @global $DB database object to access records
 * @return fine head type name/flag
 */
function get_fine_type_array() {
    $finetype_array = array(FINE_RULE_PERDAY => 'Per Day', FINE_RULE_PERWEEK => 'Per Week', FINE_RULE_PERMONTH => 'Per Month', FINE_RULE_LUMPSUM => 'Lumpsum');
    return $finetype_array;
}

/**
 * Get the program id,program name , organization id and organization name
 *
 * @param $user_fee_bill_id bill generated id
 * @global $DB database object to access records
 * @return int
 */
function get_generated_bill_programid($user_fee_bill_id) {
    global $DB;
    $data = $DB->get_record_sql("SELECT fbt.program as pid , p.name as progname, cc.id as orgid , cc.name as orgname"
            . " FROM {user_fee_bill} as ufb"
            . " JOIN {fee_bill_templates} fbt ON fbt.id = ufb.bill_template_id"
            . " JOIN {program} p ON p.id = fbt.program"
            . " JOIN {course_categories} cc ON cc.id = p.organization"
            . " WHERE ufb.id =$user_fee_bill_id");
    return $data;
}

/*
 *  Count of all fee bills as per filter
 * @param filter_params array : filters
 * @return count int
 */

function get_feebills_count($filter_params = array()) {
    global $DB;
    $params = array();
    $select = '';
    $sort = '';
    $where = '';
    $datefilter = '';
    if (is_array($filter_params) && count($filter_params) > 0) {
        $select_params = array();
        foreach ($filter_params as $field => $value) {
            if ($field == 'type') {
                $select_params[] = "fbt.type = $value";
            } else if ($field == 'pid') {
                $select_params[] = "fbt.program = $value";
            } else if ($field == 'cid') {
                $select_params[] = "fbt.id = $value";
            } else if ($field == 'username') {
                $select_params[] = "(user.username like '%$value%' OR (concat_ws(' ',user.firstname,user.lastname) LIKE '%$value%'))";
            } else if ($field == 'cancelstatus') {
                $select_params[] = "ufb.cancelstatus = $value";
            } else if ($field == 'bill_number') {
                $select_params[] = "ufb.bill_number = '$value'";
            } else if ($field == 'list') {
                $select_params[] = "p.organization IN (" . $value . ")";
            } else if ($field == 'billpaid') {
                $select_params[] = "ufb.billpaid = $value";
            }
            if ($value == 'billdate') {
                if (isset($filter_params['todate']) && isset($filter_params['fromdate'])) {
                    $where .= " AND (ufbd.bill_date >= " . $filter_params['fromdate'] . " AND ufbd.bill_date <= " . $filter_params['todate'] . ") ";
                    $params['todate'] = $filter_params['todate'];
                    $params['fromdate'] = $filter_params['fromdate'];
                } else if (isset($filter_params['todate'])) {
                    $where .= " AND ufbd.bill_date <= " . $filter_params['todate'];
                    $params['todate'] = $filter_params['todate'];
                } else if (isset($filter_params['fromdate'])) {
                    $where .= " AND ufbd.bill_date >= " . $filter_params['fromdate'];
                    $params['fromdate'] = $filter_params['fromdate'];
                }
            } else if ($value == 'duedate') {
                if (isset($filter_params['todate']) && isset($filter_params['fromdate'])) {
                    $where .= " AND (ufbd.due_date >= " . $filter_params['fromdate'] . " AND ufbd.due_date <= :todate) ";
                    $params['todate'] = $filter_params['todate'];
                    $params['fromdate'] = $filter_params['fromdate'];
                } else if (isset($filter_params['todate'])) {
                    $where .= " AND ufbd.due_date <= " . $filter_params['todate'];
                    $params['todate'] = $filter_params['todate'];
                } else if (isset($filter_params['fromdate'])) {
                    $where .= " AND ufbd.due_date >= " . $filter_params['fromdate'];
                    $params['fromdate'] = $filter_params['fromdate'];
                }
            } else if ($value == 'canceldate') {
                if (isset($filter_params['todate']) && isset($filter_params['fromdate'])) {
                    $where .= " AND (ufbd.cancelledtime >= " . $filter_params['fromdate'] . " AND ufbd.cancelledtime <= :todate) ";
                    $params['todate'] = $filter_params['todate'];
                    $params['fromdate'] = $filter_params['fromdate'];
                } else if (isset($filter_params['todate'])) {
                    $where .= " AND ufbd.cancelledtime <= " . $filter_params['todate'];
                    $params['todate'] = $filter_params['todate'];
                } else if (isset($filter_params['fromdate'])) {
                    $where .= " AND ufbd.cancelledtime >= " . $filter_params['fromdate'];
                    $params['fromdate'] = $filter_params['fromdate'];
                }
            } else if ($value == 'receiptdate') {

                if (isset($filter_params['todate']) && isset($filter_params['fromdate'])) {
                    $where .= " AND (tr.transactiondate >= " . $filter_params['fromdate'] . " AND tr.transactiondate <= " . $filter_params['todate'] . ") ";
                    $params['todate'] = $filter_params['todate'];
                    $params['fromdate'] = $filter_params['fromdate'];
                } else if (isset($filter_params['todate'])) {
                    $where .= " AND tr.transactiondate <= " . $filter_params['todate'];
                    $params['todate'] = $filter_params['todate'];
                } else if (isset($filter_params['fromdate'])) {
                    $where .= " AND tr.transactiondate >= " . $filter_params['fromdate'];
                    $params['fromdate'] = $filter_params['fromdate'];
                }
            }
        }
        $select .= "AND " . implode(" AND ", $select_params);
    }
    $count = $DB->count_records_sql("select COUNT(user_fee_bill_id) "
            . "FROM {fee_bill_templates} as fbt "
            . "INNER JOIN {user_fee_bill} as ufb "
            . "ON fbt.id = ufb.bill_template_id "
            . "INNER JOIN {user_fee_bill_details} as ufbd ON ufbd.user_fee_bill_id = ufb.id "
            . "INNER JOIN {program} p ON p.id = fbt.program "
            . "INNER JOIN {user} user ON user.id = ufb.userid "
            . "where 1 AND p.deleted = 0 AND fbt.deleted = 0 $select $where $sort order by user_fee_bill_id desc", $params);
    return $count;
}

/*
 *  Fetches all feebills as per the filter 
 * @param filter_params array : filters
 * @param page int : page number
 * @param perpage int : enteries per page
 * @return feebills object
 */

function get_feebills($filter_params = array(), $page, $perpage) {
    global $DB;
    $params = array();
    $select = '';
    $sort = '';
    $where = '';
    $datefilter = '';
    $limit = $page * $perpage;
    if (is_array($filter_params) && count($filter_params) > 0) {
        $select_params = array();
        foreach ($filter_params as $field => $value) {
            if ($field == 'type') {
                $select_params[] = "fbt.type = $value";
            } else if ($field == 'pid') {
                $select_params[] = "fbt.program = $value";
            } else if ($field == 'cid') {
                $select_params[] = "fbt.id = $value";
            } else if ($field == 'username') {
                $select_params[] = "(user.username like '%$value%' OR (concat_ws(' ',user.firstname,user.lastname) LIKE '%$value%'))";
            } else if ($field == 'cancelstatus') {
                $select_params[] = "ufb.cancelstatus = $value";
            } else if ($field == 'bill_number') {
                $select_params[] = "ufb.bill_number = '$value'";
            } else if ($field == 'list') {
                $select_params[] = "p.organization IN (" . $value . ")";
            } else if ($field == 'billpaid') {
                $select_params[] = "ufb.billpaid = $value";
            }
            if ($value == 'billdate') {
                if (isset($filter_params['todate']) && isset($filter_params['fromdate'])) {
                    $where .= " AND (ufbd.bill_date >= " . $filter_params['fromdate'] . " AND ufbd.bill_date <= " . $filter_params['todate'] . ") ";
                    $params['todate'] = $filter_params['todate'];
                    $params['fromdate'] = $filter_params['fromdate'];
                } else if (isset($filter_params['todate'])) {
                    $where .= " AND ufbd.bill_date <= " . $filter_params['todate'];
                    $params['todate'] = $filter_params['todate'];
                } else if (isset($filter_params['fromdate'])) {
                    $where .= " AND ufbd.bill_date >= " . $filter_params['fromdate'];
                    $params['fromdate'] = $filter_params['fromdate'];
                }
            } else if ($value == 'duedate') {
                if (isset($filter_params['todate']) && isset($filter_params['fromdate'])) {
                    $where .= " AND (ufbd.due_date >= " . $filter_params['fromdate'] . " AND ufbd.due_date <= :todate) ";
                    $params['todate'] = $filter_params['todate'];
                    $params['fromdate'] = $filter_params['fromdate'];
                } else if (isset($filter_params['todate'])) {
                    $where .= " AND ufbd.due_date <= " . $filter_params['todate'];
                    $params['todate'] = $filter_params['todate'];
                } else if (isset($filter_params['fromdate'])) {
                    $where .= " AND ufbd.due_date >= " . $filter_params['fromdate'];
                    $params['fromdate'] = $filter_params['fromdate'];
                }
            } else if ($value == 'canceldate') {
                if (isset($filter_params['todate']) && isset($filter_params['fromdate'])) {
                    $where .= " AND (ufbd.cancelledtime >= " . $filter_params['fromdate'] . " AND ufbd.cancelledtime <= :todate) ";
                    $params['todate'] = $filter_params['todate'];
                    $params['fromdate'] = $filter_params['fromdate'];
                } else if (isset($filter_params['todate'])) {
                    $where .= " AND ufbd.cancelledtime <= " . $filter_params['todate'];
                    $params['todate'] = $filter_params['todate'];
                } else if (isset($filter_params['fromdate'])) {
                    $where .= " AND ufbd.cancelledtime >= " . $filter_params['fromdate'];
                    $params['fromdate'] = $filter_params['fromdate'];
                }
            } else if ($value == 'receiptdate') {

                if (isset($filter_params['todate']) && isset($filter_params['fromdate'])) {
                    $where .= " AND (tr.transactiondate >= " . $filter_params['fromdate'] . " AND tr.transactiondate <= " . $filter_params['todate'] . ") ";
                    $params['todate'] = $filter_params['todate'];
                    $params['fromdate'] = $filter_params['fromdate'];
                } else if (isset($filter_params['todate'])) {
                    $where .= " AND tr.transactiondate <= " . $filter_params['todate'];
                    $params['todate'] = $filter_params['todate'];
                } else if (isset($filter_params['fromdate'])) {
                    $where .= " AND tr.transactiondate >= " . $filter_params['fromdate'];
                    $params['fromdate'] = $filter_params['fromdate'];
                }
            }
        }
        $select .= "AND " . implode(" AND ", $select_params);
    }
    $sql = "select (@i:=@i+1) sno,fbt.name as instance_name ,user.id as enrolno,user_fee_bill_id,ufb.cancelstatus,user.email, ufbd.bill_date, ufb.billpaid , fbt.type,ufbd.due_date, fbt.name , ufb.bill_template_id, "
            . "concat(user.firstname, ' ', user.lastname) as fullname, ufb.bill_number, ufbd.roll_struck_off, ufbd.roll_struck_off_date "
            . "FROM {fee_bill_templates} as fbt "
            . "INNER JOIN {user_fee_bill} as ufb "
            . "ON fbt.id = ufb.bill_template_id "
            . "INNER JOIN {user_fee_bill_details} as ufbd ON ufbd.user_fee_bill_id = ufb.id "
            . "INNER JOIN {program} p ON p.id = fbt.program "
            . "INNER JOIN (select @i := 0) serial "
            . "INNER JOIN {user} user ON user.id = ufb.userid "
            . "where 1 AND fbt.deleted = 0 AND p.deleted = 0 $select $where $sort order by user_fee_bill_id desc";
    $feebills = $DB->get_records_sql($sql, $params, $limit, $perpage);
    return $feebills;
}

/* Creates a user fee bill object
 * @param adjustbilldata object
 * @return userfeebill_obj object
 */

function create_userfeebill_obj($adjustbilldata) {
    if (!empty($adjustbilldata)) {
        $userfeebill_obj = new stdClass();
        $userfeebill_obj->userid = $adjustbilldata->enrolno;
        $userfeebill_obj->bill_template_id = $adjustbilldata->bill_template_id;
        $userfeebill_obj->bill_number = $adjustbilldata->bill_number;
        $userfeebill_obj->draft = $adjustbilldata->draft;
        $userfeebill_obj->cancelstatus = $adjustbilldata->cancelstatus;
    }
    return $userfeebill_obj;
}

/* Creates a detailed object for user fee bill
 * @param adjustbilldata object
 * @param post_data object
 * @return userfeebilldetails_obj object
 */

function create_userfeebill_obj_details($adjustbilldata, $post_data) {
    global $DB;
    $user_fee_bill_details = $DB->get_record('user_fee_bill_details', array('user_fee_bill_id' => $adjustbilldata->user_fee_bill_id));
    if (!empty($user_fee_bill_details) && !empty($adjustbilldata)) {
        $userfeebilldetails_obj = new stdClass();
        $userfeebilldetails_obj->bill_template_id = $adjustbilldata->bill_template_id;
        $userfeebilldetails_obj->bill_date = strtotime($post_data->bill_date);
        $userfeebilldetails_obj->due_date = strtotime($post_data->due_date);
        if (!empty($post_data->roll_struck_off_override)) {
            $userfeebilldetails_obj->roll_struck_off = 1;
            $userfeebilldetails_obj->roll_struck_off_date = strtotime($post_data->roll_struck_off_date);
            $userfeebilldetails_obj->readmission_charge = $post_data->readmission_charge;
        } else {
            $userfeebilldetails_obj->roll_struck_off = $adjustbilldata->roll_struck_off;
            $userfeebilldetails_obj->roll_struck_off_date = $adjustbilldata->roll_struck_off_date;
            $userfeebilldetails_obj->readmission_charge = $adjustbilldata->readmission_charge;
        }
        $userfeebilldetails_obj->finevalue = $user_fee_bill_details->finevalue;
        $userfeebilldetails_obj->comment = isset($post_data->comment) ? clean_param($post_data->comment, PARAM_NOTAGS) : $user_fee_bill_details->comment;
        $userfeebilldetails_obj->billpath = $user_fee_bill_details->billpath;
        $userfeebilldetails_obj->parentbillid = $user_fee_bill_details->parentbillid;
        $userfeebilldetails_obj->cancel_comment = '';
    }
    return $userfeebilldetails_obj;
}

/* Creates a scholarship object for a user fee bill
 * @param billscholarship object
 * @param post_data object
 * @return billscholarship_obj object
 */

function create_userfeebill_obj_scholarship($billscholarship, $post_data) {
    foreach ($post_data as $obj => $value) {
        if ($obj == 'amount_overridden') {
            $billscholarship->amount_overridden = $value;
        } else if ($obj == 'scholarshipcomment') {
            $billscholarship->comment = $value;
        }
    }
    return $billscholarship;
}

/* Creates a feeheads object for a user fee bill
 * @param billheads object
 * @param post_data object
 * @return userfeebilldetails_obj object
 */

function create_userfeebill_obj_feeheads($billheads, $post_data) {
    global $DB;
    $billheadamount_array = array();
    $billheadcomment_array = array();
    foreach ($post_data as $obj => $value) {
        if (strpos($obj, 'feeheadamount') !== FALSE) {
            $data = explode('/', $obj);
            $billheadamount_array[$data[1]] = $value;
        }
    }
    foreach ($post_data as $obj => $value) {
        if (strpos($obj, 'feeheadcomment') !== FALSE) {
            $data = explode('/', $obj);
            $billheadcomment_array[$data[1]] = $value;
        }
    }
    $userfeebilldetails_obj = array();
    foreach ($billheads as $head) {
        $feebillhead_data = $DB->get_record('user_bill_fee_heads', array('id' => $head->id));
        $data_obj = new stdClass();
        $data_obj->user_fee_bill_id = $feebillhead_data->user_fee_bill_id;
        $data_obj->fee_head = $feebillhead_data->fee_head;
        $data_obj->fee_category = $feebillhead_data->fee_category;
        if (array_key_exists($head->id, $billheadamount_array)) {
            $data_obj->amount = (float) $billheadamount_array[$head->id];
        }
        if (array_key_exists($head->id, $billheadcomment_array)) {
            $data_obj->comment = $billheadcomment_array[$head->id];
        }
        $userfeebilldetails_obj[$head->id] = $data_obj;
    }

    return $userfeebilldetails_obj;
}

/* Creates a fines rules object for a user fee bill
 * @param finerules object
 * @param post_data object
 * @return finerules_data object
 */

function create_userfeebill_obj_finerules($finerules, $post_data) {
    global $DB;
    $finerules_data = array();
    if (isset($post_data->discardfine)) {
        return $finerules_data;
    } else {
        $fine_data = $DB->get_records_sql('SELECT ufbf.* FROM {bill_template_finerules} as ufbf '
                . 'WHERE ufbf.user_fee_bill_id = ' . $post_data->id);
        if (empty($fine_data)) {
            if (empty($finerules)) {
                $finerules_data = $DB->get_records_sql('SELECT btf.* FROM {bill_template_finerules} as btf JOIN {user_fee_bill} ufb '
                        . 'ON ufb.bill_template_id = btf.bill_template_id WHERE ufb.id = ' . $post_data->id);
            } else {
                $finerules_data = $finerules;
            }
        } else {
            $finerules_data = $fine_data;
        }
    }
    return $finerules_data;
}

/*
 * programmanagement_filtering is the class that would hold the current program management filter data into users session
 * so those filters would be automatically available to the users on revisit of that page
 * until he/she reset those filters on that page OR user logs out to the system
 */

class programmanagement_filtering {

    protected $myfilter;

    /*
     * constructor class
     */

    public function __construct($filtername) {
        $this->myfilter = $filtername;   //get_class($this);
    }

    /*
     * @param $filterobject key->value parameter of filter parameters
     */

    public function set_programlist_filtering($filterobject) {
        global $SESSION;

        if ($filterobject) {
            $this->reset_programlist_filtering();
            $SESSION->{$this->myfilter} = new stdClass();
            $SESSION->{$this->myfilter} = $filterobject;
        }
    }

    /*
     * @return object
     */

    public function get_programlist_filtering() {
        global $SESSION;
        $filterobject = new stdClass();
        if (isset($SESSION->{$this->myfilter})) {
            $filterobject = $SESSION->{$this->myfilter};
        }
        return $filterobject;
    }

    /*
     * clear all program listing filter variable
     */

    public function reset_programlist_filtering() {
        global $SESSION;
        if (isset($SESSION->{$this->myfilter})) {
            unset($SESSION->{$this->myfilter});
        }
    }

}

/*
 * create child class to inherit 
 * and to define session variable with name of concerned filter child class
 * and set the variables of that respective filter in the object
 */

class programlist_filtering extends programmanagement_filtering {
    
}

/* Print all tabs required for 
 * Program Management 
 * @param int $pid Program Id
 * @return null renders the tab object
 */

function print_bill_tabs() {
    global $PAGE, $CFG, $USER;
    $pageparams = array();
    $wardid = optional_param('wardid', '', PARAM_INT);
    $userid = optional_param('userid', '', PARAM_INT);
    $params = $PAGE->url->get_query_string();
    $ward = '';
    $user = '';
    if ($params) {
        $pageparams = explode(';', $params);
        foreach ($pageparams as $params => $param) {
            if (strpos($param, 'wardid') > -1) {
                $ward = $param;
            } else if (strpos($param, 'wardid') > -1) {
                $user = $param;
            }
        }
    }
    if ($wardid) {
        $ward = 'wardid=' . $wardid;
    }
    if ($userid && empty($ward)) {
        $user = 'userid=' . $userid;
    } else if ((empty($userid) && empty($ward))) {
        $user = 'userid=' . $USER->id;
    }
    $tabs = array();
    $credit_url = '';
    $credit_capabilities = array('local/programmanagement:viewallusercredit', 'local/programmanagement:viewmycredit');
    if (has_allcapability_in_organization($credit_capabilities) || has_capability_in_organization('local/programmanagement:viewallusercredit')) {
        $credit_url = $CFG->wwwroot . '/local/programmanagement/credit/creditaccountlist.php';
    } else if (has_capability_in_organization('local/programmanagement:viewmycredit') && !has_capability_in_organization('local/programmanagement:viewallusercredit')) {
        $credit_url = $CFG->wwwroot . '/local/programmanagement/credit/usercreditaccount.php' . $ward . $userid;
    }
    //define program management tabs
    $programtabs = array(
        'tab_bill_duebills' => new moodle_url('/local/programmanagement/feebill/duebills.php' . '?' . $ward . $userid),
        'tab_bill_paidbills' => new moodle_url('/local/programmanagement/feebill/paidbills.php' . '?' . $ward . $userid),
        'tab_bill_cancellbills' => new moodle_url('/local/programmanagement/feebill/cancelbills.php' . '?' . $ward . $userid),
        'tab_bill_myduebills' => new moodle_url('/local/programmanagement/feebill/myduebills.php' . '?' . $ward . $userid),
        'tab_creditaccount' => $credit_url . '?' . $ward . $userid,
    );

    $tab_bill_duebills_cap = array('local/programmanagement:viewduebills', 'local/programmanagement:viewduebillsothers');
    $tab_bill_paidbills_cap = array('local/programmanagement:viewpaidbills', 'local/programmanagement:viewpaidbillsothers');
    $tab_bill_cancellbills_cap = array('local/programmanagement:viewcancelledbills', 'local/programmanagement:viewcancelledbillsothers	
');
    $tab_bill_myduebills_cap = array('local/programmanagement:payonlinemyduebills');
    $tab_creditaccount_cap = array('local/programmanagement:viewallusercredit', 'local/programmanagement:viewmycredit');

    //define additional links for common tabs
    $programtabs_extralinks = array(
        'tab_creditaccount_extralinks' => array(
            new moodle_url('/local/programmanagement/credit/usercreditaccount.php'),
        )
    );

    $strextralinks = "_extralinks";


    $selected = '';
    foreach ($programtabs as $tabkey => $tablink) {
        if (has_capability_in_organization(${$tabkey . "_cap"})) {
            $tabs[] = new tabobject($tabkey, $tablink, get_string($tabkey, 'local_programmanagement'));
        }
    }
    //check the active tab
    foreach ($programtabs as $tabkey => $tablink) {
        $pattern = '/' . preg_quote(parse_url($tablink)['path'], '/') . '/';
        if (preg_match($pattern, $PAGE->url)) {
            $selected = $tabkey;
            break;
        }
        //check for the additional links that may lies within active tabs
        if (isset($programtabs_extralinks[$tabkey . $strextralinks]) && empty($selected)) {
            foreach ($programtabs_extralinks[$tabkey . $strextralinks] as $extralinks) {
                if (!empty($extralinks)) {
                    $patternextra = '/' . preg_quote(parse_url($extralinks)['path'], '/') . '/';
                    if (preg_match($patternextra, $PAGE->url)) {
                        $selected = $tabkey;
                        break;
                    }
                }
            }
        }
    }
    $output = '';

    $output .= html_writer::start_div('b-tabs');
    $output .= print_tabs(array($tabs), $selected, array('tab_regularfeebill'), null, $return = true);
    $output .= html_writer::end_div();
    echo $output;
}

/* Rollover transition association mapping ie plan association and feehead association
 * using the previous pid association data
 * @param : pid int
 * @param : program_id int
 * @param : userid int
 */

function rollover_association_mapping($pid, $program_id, $userid, $rollovertstatus = 0) {
    global $DB, $USER;
    if (empty($program_id) || empty($pid) || empty($userid)) {
        return;
    }
    $time = time();
    $planassociation_data = get_user_prepaymentplan($pid, $userid);
    $feeheadassociation_data = get_user_feeheadplan($pid, $userid);
    $scholarshipassociation_data = get_user_scholarshipassoc($pid, $program_id, $userid);
    if (!empty($planassociation_data)) {
        foreach ($planassociation_data as $planassoc) {
            $oldprepaymentplaninfo = $DB->get_record('prepayment_plans', array('id' => $planassoc->plan_type_id));
            $sql = "SELECT pp.id FROM {prepayment_plans} pp "
                    . "INNER JOIN {program} p "
                    . "ON p.organization = pp.organization "
                    . "WHERE p.id = $program_id AND (pp.plan_type = '" . strtolower($oldprepaymentplaninfo->plan_type) . "' OR pp.plan_type ='" . strtoupper($oldprepaymentplaninfo->plan_type) . "')";
            $newprepaymentplan = $DB->get_record_sql($sql);
            $data = new stdclass();
            $data->id = $planassoc->id;
            $data->timemodified = $time;
            $data->modifierid = $USER->id;
            $DB->update_record('user_prepayment_plans', $data);
            unset($planassoc->id);
            $obj = new stdClass();
            $obj->user_id = $userid;
            $obj->program_id = $program_id;
            $obj->plan_type_id = !empty($newprepaymentplan) ? $newprepaymentplan->id : 0;
            $obj->timemodifed = $time;
            $obj->timecreated = $time;
            $DB->insert_record('user_prepayment_plans', $obj);
        }
    }

    if (!empty($feeheadassociation_data)) {
        foreach ($feeheadassociation_data as $feeheadassoc) {
            $oldfeeheadinfo = $DB->get_record('fee_head', array('id' => $feeheadassoc->fee_head_id));
            $sql = "SELECT fh.id FROM {fee_head} fh "
                    . "INNER JOIN {program} p "
                    . "ON p.organization = fh.organization "
                    . "WHERE p.id = $program_id AND (fh.name = '" . strtolower($oldfeeheadinfo->name) . "' OR fh.name ='" . strtoupper($oldfeeheadinfo->name) . "')";
            $newfeeheadinfo = $DB->get_record_sql($sql);
            $data = new stdclass();
            $data->id = $feeheadassoc->id;
            $data->timemodified = $time;
            $data->modifierid = $USER->id;
            $DB->update_record('user_program_feehead', $data);
            unset($feeheadassoc->id);
            $obj = new stdClass();
            $obj->user_id = $userid;
            $obj->fee_head_id = !empty($newfeeheadinfo) ? $newfeeheadinfo->id : 0;
            $obj->status = $feeheadassoc->status;
            $obj->program_id = $program_id;
            $obj->timemodifed = $time;
            $obj->timecreated = $time;
            $DB->insert_record('user_program_feehead', $obj);
        }
    }


    if (!empty($scholarshipassociation_data) && !empty($scholarshipassociation_data->data)) {
        foreach ($scholarshipassociation_data->data as $key => $scholarshipassoc) {
            $data = new stdclass();
            $data->id = $scholarshipassoc->id;
            $data->timemodified = $time;
            $data->modifierid = $USER->id;
            $DB->update_record('user_program_scholarship', $data);
            unset($scholarshipassoc->id);
            $obj = new stdClass();
            $obj->user_id = $userid;
            $obj->program_scholarship_id = $key;
            $obj->status = $scholarshipassoc->status;
            $obj->timemodifed = $time;
            $obj->timecreated = $time;
            $DB->insert_record('user_program_scholarship', $obj);
        }
    }

    // save the data into rollovertransition data
    $rolloverobj = new stdClass();
    $rolloverobj->userid = $userid;
    $rolloverobj->fromprogram = $pid;
    $rolloverobj->toprogram = $program_id;
    $rolloverobj->rollovertransitiondate = usergetmidnight($time);
    $rolloverobj->rolloverstatus = $rollovertstatus;
    $DB->insert_record('rollovertransition', $rolloverobj);

    $oldprogram = $DB->get_record('program', array('id' => $pid));
    $newprogram = $DB->get_record('program', array('id' => $program_id));
    $eventorg = isset($newprogram->organization) ? $newprogram->organization : 0;
    $eventcontext = $eventorg ? CONTEXT_COURSECAT::instance($eventorg) : CONTEXT_SYSTEM::instance();
    $event = \local_programmanagement\event\program_rollovertransition::create(
                    array('context' => $eventcontext, 'userid' => $USER->id,
                        'objectid' => $pid,
                        'other' => array('oldprogname' => $oldprogram->name,
                            'rollovertransition_userid' => $userid,
                            'newprogname' => $newprogram->name,
                            'pid' => $program_id)
                    )
    );
    $event->trigger();
}

/*  Returns user prepayment plan association for a program
 *  @param : pid int
 *  @param : userid int
 *  return : prepaymentplan_obj object
 */

function get_user_prepaymentplan($pid, $userid) {
    global $DB;
    $prepaymentplan_obj = new stdClass();
    $prepaymentsql = "SELECT * from {user_prepayment_plans} WHERE program_id = $pid AND user_id = $userid";
    $prepaymentplan_obj = $DB->get_records_sql($prepaymentsql);
    return $prepaymentplan_obj;
}

/*  Returns user feehead association for a program
 *  @param : pid int
 *  @param : userid int
 *  return : feeheadassociation_obj object
 */

function get_user_feeheadplan($pid, $userid) {
    global $DB;
    $feeheadassociation_obj = new stdClass();
    $feeheadsql = "SELECT * from {user_program_feehead} WHERE program_id = $pid AND user_id = $userid";
    $feeheadassociation_obj = $DB->get_records_sql($feeheadsql);
    return $feeheadassociation_obj;
}

/*  Returns user scholarship association for a program
 *  @param : pid int
 *  @param : userid int
 *  return : scholarshipassociation_obj object
 */

function get_user_scholarshipassoc($pid, $program_id, $userid) {
    global $DB;
    $scholarshipassociation_obj = new stdClass();
    $scholarshipsql = "SELECT ups.* from {user_program_scholarship} ups "
            . " INNER JOIN {program_scholarships} ps ON ps.id = ups.program_scholarship_id "
            . "WHERE ps.program = $pid AND ups.user_id = $userid";
    $obj = $DB->get_records_sql($scholarshipsql);

    $criteria = '';
    $progcategory = '';
    foreach ($obj as $key => $schassocobj) {
        $progschid = $DB->get_record('program_scholarships', array('id' => $schassocobj->program_scholarship_id));

        if (!empty($progschid)) {
            $criteria = $progschid->criteria;
            $sch = $DB->get_record('scholarship', array('id' => $progschid->scholarship_id));
            $schc = $DB->get_record('scholarship_category', array('id' => $progschid->scholarship_category));
            $scholarship = $DB->get_record_sql("SELECT sc.* FROM {scholarship} sc INNER JOIN {program} p "
                    . "ON p.organization = sc.organization WHERE sc.name = '$sch->name' AND p.id=$program_id");
            $schcsql = "SELECT sch.* FROM {scholarship_category} sch INNER JOIN {program} p "
                    . "ON p.organization = sch.organization WHERE sch.name = '$schc->name' AND p.id=$program_id";
            $schcategory = $DB->get_record_sql($schcsql);
            $ups_data = new stdClass();
            if (!empty($schcategory) && !empty($scholarship) && !empty($criteria)) {
                $sql = "SELECT ps.* "
                        . " FROM {program_scholarships} ps "
                        . "INNER JOIN {scholarship} sch ON sch.id = ps.scholarship_id "
                        . "INNER JOIN {scholarship_category} schc ON schc.id = ps.scholarship_category "
                        . "WHERE ps.program = $program_id  AND schc.id = $schcategory->id AND sch.id=$scholarship->id "
                        . "AND ps.criteria ='$criteria'";
                $ups_data = $DB->get_record_sql($sql);
                if (!empty($ups_data)) {
                    $ups_data->status = $schassocobj->status;
                    $scholarshipassociation_obj->data[$ups_data->id] = $ups_data;
                }
            }
        }
    }
    return $scholarshipassociation_obj;
}

/* Returns program name and organization name
 *  @param : pid int : porogram id
 *  @return data object
 */

function get_program_organizationname($pid) {
    global $DB;
    $sql = "SELECT p.name as programname, cc.name as organizationname FROM {program} p "
            . "JOIN {course_categories} cc ON cc.id = p.organizaton "
            . "WHERE p.id = $pid";
    $data = $DB->get_record_sql($sql);
    return $data;
}

/* Deletes user plan and fee head association after removal from program
 * @param pid int
 * @param userid int
 */

function delete_user_program_associations($userid, $pid) {
    global $DB;
    $feeheaddata = get_user_feeheadplan($pid, $userid);
    $plandata = get_user_prepaymentplan($pid, $userid);
    $DB->delete_records($feeheaddata);
    $DB->delete_records($plandata);
}

/* Validates override postdata and returns array of errors
 * @param data object
 * @return info array
 */

function validate_override_postdata($data) {
    global $DB;
    $info = array();
    if (!empty($data->fine_array)) {
        if (!empty($data->id)) {
            $sql = "SELECT fbt.* FROM {fee_bill_templates} fbt JOIN {user_fee_bill} ufb "
                    . "ON ufb.bill_template_id = fbt.id WHERE ufb.id=" . $data->id;
            $bill_data = $DB->get_record_sql($sql);
        }
        $due_date = 0;
        $from_date = 0;
        foreach ($data->fine_array as $row => $value) {
            $errors = array();
            $duedt = new DateTime($value['date_to']);
            $fromdt = new DateTime($value['date_from']);
            $due_date = $duedt->format('U');
            $from_date = $fromdt->format('U');
            if ($due_date <= $from_date) {
                $errors['date_to'] = 'Cannot be same or before ' . get_string('date_from', 'local_programmanagement');
            } else if ($from_date < $bill_data->due_date) {
                $errors['date_from'] = 'Fine rule date from cannot be before Due Date';
            } else if ($due_date < $bill_data->due_date) {
                $errors['date_from'] = 'Fine rule date to cannot be before Due Date';
            }
            if (!is_numeric($value['amount'])) {
                $errors['amount'] = 'Must be numeric';
            }
            if (!empty($errors)) {
                $info[$row] = $errors;
            }
        }
    }
    return $info;
}

/* Creates fine rule object
 * @param bill_template_id int 
 * @param fine_array array
 * @return fineobject object
 */

function create_finerule_object($bill_template_id, $fine_array) {
    $fineobject = new stdclass();
    foreach ($fine_array as $key => $finerule) {
        $fine_obj = new stdClass();
        $datefrm = new DateTime($finerule['date_from']);
        $dateto = new DateTime($finerule['date_to']);
        $date_from = $datefrm->format('U');
        $date_to = $dateto->format('U');
        $fine_obj->bill_template_id = $bill_template_id;
        $fine_obj->date_from = $date_from;
        $fine_obj->date_to = $date_to;
        $fine_obj->fine_type = $finerule['fine_type'];
        $fine_obj->amount = $finerule['amount'];
        $fine_obj->include_previous = 0;
        $fineobject->$key = $fine_obj;
    }
    return $fineobject;
}

/*
 * 
 * CRON job for plugin programmanagement
 */

function local_programmanagement_cron() {
    
}

/* Function to fetch the rollstruck off users from the table
 *  @param : filter_params array
 *  @param : perpage int
 *  @param : page int
 * @return rollstruckoff_users object
 */

function get_struckoff_users($filter_params, $page = 0, $perpage = 0) {
    global $DB;
    $params = array();
    $select = '';
    $sort = '';
    $limit = $page * $perpage;
    if (is_array($filter_params) && count($filter_params) > 0) {
        $select_params = array();
        foreach ($filter_params as $field => $value) {
            if ($field == 'username') {
                $select_params[] = "u.firstname like '%$value%' OR u.lastname like '%$value%' OR u.username like '%$value%' OR u.idnumber like '%$value%'";
            } else if ($field == 'programid') {
                $select_params[] = "psu.programid = $value";
            } else {
                $select_params[] = $field . '=:' . $field;
                $params[$field] = $value;
            }
        }
        $select .= "AND " . implode(" AND ", $select_params);
    }
    //gets roll struck users as per filters
    $sql = "SELECT ufb.id, ufb.userid, "
            . "u.firstname, u.lastname, fbt.name 'billname', ufb.bill_number, ufbd.bill_date, ufbd.due_date, "
            . "ufbd.roll_struck_off_date, ufb.billpaid, p.name, p.id 'programid', psu.id as program_struckoff_id , psu.strucked_off_date, "
            . "psu.rollstruckoff_status, psu.reenroll_date "
            . "FROM {program_struckoff_users} psu "
            . "INNER JOIN {user_fee_bill} ufb ON ufb.id = psu.user_fee_bill_id "
            . "INNER JOIN {user_fee_bill_details} ufbd ON ufb.id = ufbd.user_fee_bill_id "
            . "AND ufb.draft =0 "
            . "AND ufb.billpaid =0 "
            . "AND ufb.cancelstatus =0 "
            . "INNER JOIN {fee_bill_templates} fbt ON fbt.id = ufb.bill_template_id "
            . "INNER JOIN {program} p ON p.id = fbt.program "
            . "INNER JOIN {user} u ON u.id = ufb.userid "
            . "WHERE 1 $select AND psu.rollstruckoff_status = 1 $sort order by psu.id desc";
    $rollstruckoff_users = $DB->get_records_sql($sql, $params, $limit, $perpage);
    return $rollstruckoff_users;
}

/* Re-enrolls and unsuspends the user who has been rollstruck off in a program
 *  @param : userid int
 *  @param : user_fee_bill_id int
 */

function reenrol_user($id) {
    global $DB, $CFG, $USER;
    $rollstruck_data = $DB->get_record('program_struckoff_users', array('id' => $id));
    $program = $DB->get_record('program', array('id' => $rollstruck_data->programid));
    $sitecontext = context_system::instance();
    $data = new stdClass();
    $data->id = $id;
    $data->rollstruckoff_status = 0;
    $data->reenroll_date = time();
    $DB->update_record('program_struckoff_users', $data);
    require_capability('moodle/user:update', $sitecontext);
    $eventorg = isset($program->organization) ? $program->organization : 0;
    $eventcontext = $eventorg ? CONTEXT_COURSECAT::instance($eventorg) : CONTEXT_SYSTEM::instance();
    $event = \local_programmanagement\event\program_reenrolusers::create(
                    array('context' => $eventcontext, 'userid' => $USER->id,
                        'objectid' => $id,
                        'other' => array('progname' => $program->name,
                            'reenrol_userid' => $rollstruck_data->userid)
                    )
    );
    $event->trigger();

    if ($user = $DB->get_record('user', array('id' => $rollstruck_data->userid, 'mnethostid' => $CFG->mnet_localhost_id, 'deleted' => 0))) {
        if ($user->suspended != 0) {
            $user->suspended = 0;
            user_update_user($user, false);
        }
    }
}

/* Function to fetch the rollstruck off information for a particular user
 *  @param : userid int
 *  @return rollstruckoof_users object
 */

function get_user_struckoff_details($userid = 0) {
    global $DB;
    //gets user fee bills details , userdetails which have been rollstruck off
    $sql = "SELECT ufb.id, ufb.userid, "
            . "u.firstname, u.lastname, fbt.name 'billname', ufb.bill_number, ufbd.bill_date, ufbd.due_date, "
            . "ufbd.roll_struck_off_date, ufb.billpaid, p.name, p.id 'programid', psu.id as program_struckoff_id , psu.strucked_off_date, "
            . "psu.rollstruckoff_status, psu.reenroll_date "
            . "FROM {program_struckoff_users} psu "
            . "INNER JOIN {user_fee_bill} ufb ON ufb.id = psu.user_fee_bill_id "
            . "INNER JOIN {user_fee_bill_details} ufbd ON ufb.id = ufbd.user_fee_bill_id "
            . "AND ufb.draft =0 "
            . "AND ufb.billpaid =0 "
            . "AND ufb.cancelstatus =0 "
            . "INNER JOIN {fee_bill_templates} fbt ON fbt.id = ufb.bill_template_id "
            . "INNER JOIN {program} p ON p.id = fbt.program "
            . "INNER JOIN {user} u ON u.id = ufb.userid "
            . "WHERE 1 AND psu.rollstruckoff_status = 1 AND u.id = $userid";
    $rollstruckoff_users = $DB->get_records_sql($sql);
    return $rollstruckoff_users;
}

/* Returns all user fee bill ids whose lastnotificationsent is 0 and then send mail to them with cron
 * @param void
 * @return null
 */

function send_mail_to_newgeneratedbills() {
    global $DB;
    $contactdetails = array();
    // gets all user fee bills whose lastnotification has not been sent before
    $sql = "SELECT * FROM {user_fee_bill} WHERE lastnotificationsent = 0";
    $userfeebills = $DB->get_records_sql($sql);
    if ($userfeebills) {
        $feebills = new fee_bills();
        foreach ($userfeebills as $key => $userfeebill) {
            $sql = "SELECT id,name FROM {fee_bill_templates} WHERE id = " . $userfeebill->bill_template_id;
            $feebilldetails = $DB->get_record_sql($sql);
            if ($feebilldetails) {
                $contactsql = "SELECT id,contactemail,contactphone FROM {bill_receipt_convention} "
                        . "WHERE organization = $feebilldetails->organization ";
                $contactdetails = $DB->get_record_sql($contactsql);
            }
            $userfeebill->feebilltemplatename = $feebilldetails->name;
            $userfeebill->contactemail = isset($contactdetails->contactemail) ? $contactdetails->contactemail : '';
            $userfeebill->contactphone = isset($contactdetails->contactphone) ? $contactdetails->contactphone : '';
            $feebills->send_bill_action_mail($userfeebill, $userfeebill->userid, 'ACTION_BILL_GENERATED');
        }
    }
}

/* Returns all user fee bill ids whose bill payment is due and then send mail to them with cron
 * @param void
 * @return null
 */

function send_duebills_reminders() {
    global $DB, $CFG;
    $threshold = 0;
    $thresholddate = $CFG->notificationthreshold;
    if ($thresholddate) {
        $threshold = $thresholddate * DAYSECS;
    }
    // gets all user fee bills whose due bills are not paid and not roll struck off and current time is more than due date
    $sql = "SELECT ufb.id as user_fee_bill_id , ufb.bill_number , u.id as userid,ufbd.due_date  
        FROM {user_fee_bill} ufb 
        INNER JOIN {user_fee_bill_details} ufbd ON ufbd.user_fee_bill_id = ufb.id 
        INNER JOIN {user} u ON u.id = ufb.userid
        LEFT JOIN {program_struckoff_users} psu ON psu.user_fee_bill_id = ufb.id
        WHERE ufb.cancelstatus = 0 AND ufb.billpaid = 0 AND CURRENT_TIMESTAMP > (ufbd.due_date - $threshold) AND psu.user_fee_bill_id IS NULL";
    $userfeebills = $DB->get_records_sql($sql);
    if ($userfeebills) {
        $feebills = new fee_bills();
        foreach ($userfeebills as $key => $userfeebill) {
            $feebills->send_bill_action_mail($userfeebill, $userfeebill->userid, 'ACTION_BILL_DUE_REMINDER');
        }
    }
}
