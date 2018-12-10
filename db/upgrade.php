<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function xmldb_local_feeheadmanagement_upgrade($oldversion = 0) {

    global $CFG, $THEME, $DB;
    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    $result = true;

    if ($oldversion < 2015082801) {
        $feecategorytable = new xmldb_table('fee_category');

        $field = new xmldb_field('modifierid');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($feecategorytable, $field)) {
            $dbman->add_field($feecategorytable, $field);
        }

        $feeheadtable = new xmldb_table('fee_head');

        $modifierfield = new xmldb_field('modifierid');
        $modifierfield->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($feeheadtable, $modifierfield)) {
            $dbman->add_field($feeheadtable, $modifierfield);
        }

        upgrade_plugin_savepoint($result, 2015082801, 'local', 'feeheadmanagement');
    }

    if ($oldversion < 2015082802) {
        $feecategorytable1 = new xmldb_table('fee_category');
        $field1 = new xmldb_field('short_name');
        $field1->set_attributes(XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        if (!$dbman->field_exists($feecategorytable1, $field1)) {
            $dbman->add_field($feecategorytable1, $field1);
        }


        $feeheadtable1 = new xmldb_table('fee_head');

        $field2 = new xmldb_field('short_name');
        $field2->set_attributes(XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        if (!$dbman->field_exists($feeheadtable1, $field2)) {
            $dbman->add_field($feeheadtable1, $field2);
        }
        upgrade_plugin_savepoint($result, 2015082802, 'local', 'feeheadmanagement');
    }

    if ($oldversion < 2015082804) {
        $feecategorytable2 = new xmldb_table('fee_category');
        $field2 = new xmldb_field('chargableonce');
        $field2->set_attributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        if ($dbman->field_exists($feecategorytable2, $field2)) {
            $dbman->drop_field($feecategorytable2, $field2);
        }
        upgrade_plugin_savepoint($result, 2015082804, 'local', 'feeheadmanagement');
    }
    if ($oldversion < 2015082806) {
        $feeheadtable2 = new xmldb_table('fee_head');
        $field3 = new xmldb_field('defaultamount');
        $field3->set_attributes(XMLDB_TYPE_FLOAT, '10,2', null, XMLDB_NOTNULL, null, '0');
        if ($dbman->field_exists($feeheadtable2, $field3)) {
            $dbman->change_field_type($feeheadtable2, $field3);
        }
        upgrade_plugin_savepoint($result, 2015082806, 'local', 'feeheadmanagement');
    }
    if ($oldversion < 2015082807) {
        $feeheadtable3 = new xmldb_table('fee_head');
        $field4 = new xmldb_field('deleted');
        $field4->set_attributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($feeheadtable3, $field4)) {
            $dbman->add_field($feeheadtable3, $field4);
        }
        $feecattable3 = new xmldb_table('fee_category');
        $field5 = new xmldb_field('deleted');
        $field5->set_attributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($feecattable3, $field5)) {
            $dbman->add_field($feecattable3, $field5);
        }
        upgrade_plugin_savepoint($result, 2015082807, 'local', 'feeheadmanagement');
    }

    return $result;
}
