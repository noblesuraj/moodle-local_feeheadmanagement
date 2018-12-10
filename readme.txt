
/local/feeheadmanagement  Plugin
==========================================


This plugin has been made to manage Fee Head Categories and Fee Heads under specific category within an organization.

|-- feeheadmanagement
|   |-- addfeecategory_form.php
		This is moodle mform page to create a form that will be used on addfeecategory.php while adding/updating feecategory detail.
|   |-- addfeecategory.php
		This page is to get details of feecategory on a form and to process and save the data.
|   |-- addfeehead_form.php
		This is moodle mform page to create a form that will be used on addfeecategory.php while adding/updating feehead detail.
|   |-- addfeehead.php
		This page is to get details of feehead on a form and to process and save the data.
|   |-- classes
|   |   -- event
|   |       |-- feecategory_added.php
|   |       |-- feecategory_deleted.php
|   |       |-- feecategory_updated.php
|   |       |-- feehead_added.php
|   |       |-- feehead_deleted.php
|   |       -- feehead_updated.php
|   |-- commonvalidation.js
|   |-- db
|   |   |-- access.php
|   |   |-- install.xml
|   |   -- upgrade.php
|   |-- feecategory.php
		This is main page of feecategory management (add/edit/delete) Listing of feecategories, filter 
|   |-- feehead.php
		This is main page of feehead management (add/edit/delete).Listing of feeheads of specific feecategory of specific organization
|   |-- lang
|   |   -- en
|   |       -- local_feeheadmanagement.php
|   |-- lib.php
|   |-- settings.php
|   |-- styles.css
|   -- version.php
