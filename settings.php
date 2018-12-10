<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


if ($hassiteconfig) {
    if(!$ADMIN->locate('apeejayadministration')){
        $ADMIN->add('root', new admin_category('apeejayadministration', get_string('apeejayadministration','local_feeheadmanagement')), 'users');
    }  
    $ADMIN->add('apeejayadministration', new admin_category('feeheadmanagement', get_string('feeheadmanagement', 'local_feeheadmanagement')) );
    $ADMIN->add('feeheadmanagement', new admin_externalpage('addfeecategory', get_string('addfeecategory', 'local_feeheadmanagement'), $CFG->wwwroot . '/local/feeheadmanagement/addfeecategory.php', array('moodle/site:approvecourse')));
    $ADMIN->add('feeheadmanagement', new admin_externalpage('feecategory', get_string('feecategory', 'local_feeheadmanagement'), $CFG->wwwroot . '/local/feeheadmanagement/feecategory.php', array('moodle/site:approvecourse')));

}