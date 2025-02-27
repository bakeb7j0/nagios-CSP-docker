#!/usr/bin/php -q
<?php
//
// Migrate Server Script
// Copyright (c) 2020 Nagios Enterprises, LLC. All rights reserved.
//
// Transfers Nagios Core system configuration and plugins to Nagios XI system.
//

define("SUBSYSTEM", 1);

// Include XI codebase
require_once(dirname(__FILE__) . '/../html/includes/constants.inc.php');
require_once(dirname(__FILE__) . '/../html/config.inc.php');

// Boostrap the CCM
require_once(dirname(__FILE__) . '/../html/includes/components/ccm/bootstrap.php');

// Connect to databases
db_connect_all();

function decode_html_entity($value)
{
    $testvalue = '';
    $newvalue = $value;

    while ($testvalue !== $newvalue) {
        $testvalue = $newvalue;
        $newvalue = html_entity_decode($newvalue, ENT_QUOTES, 'UTF-8');
    }

    return $newvalue;
}

$sql = "SELECT * FROM " . $db_tables[DB_NAGIOSXI]["users"] . " WHERE username like '%&%' or name like '%&%' or email like '%&%'";
if (($rs = exec_sql_query(DB_NAGIOSXI, $sql))) {
    $invalidusers = $rs->GetArray();
    if (!empty($invalidusers)) {
        foreach ($invalidusers as $user) {
            $oldusername = $user['username'];
            $newusername = decode_html_entity($user['username']);
            $oldname = $user['name'];
            $newname = decode_html_entity($user['name']);
            $oldemail = $user['email'];
            $newemail = decode_html_entity($user['email']);

            $user_id = $user['user_id'];

            // Update a few things when the username changes
            if ($newusername != $oldusername) {

                // Remove scheduled reports (we re-add at the end after username has changed)
                $reports = scheduledreporting_component_get_reports($user_id);
                if (!empty($reports)) {
                    foreach ($reports as $id => $t) {
                        scheduledreporting_component_delete_cron($id, $user_id);
                    }
                }

                // Actually change the username
                change_user_attr($user_id, 'username', $newusername);

                // Update Core contact
                delete_nagioscore_host_and_service_configs();
                rename_nagioscore_contact($oldusername, $newusername);

                // Update audit log username entries
                update_audit_log_entires(encode_form_val_minimal($oldusername), encode_form_val_minimal($newusername));

                // Re-add scheduled reports with new username
                if (!empty($reports)) {
                    foreach ($reports as $id => $r) {
                        scheduledreporting_component_update_cron($id, $user_id);
                    }
                }

            }

            // Update the nagioscore alias
            if ($newname != $oldname) {
                change_user_attr($user_id, 'name', $newname);
                rename_nagioscore_alias($newusername, $newname);
            }

            // Update the nagioscore contact email
            if ($newemail != $oldemail) {
                change_user_attr($user_id, 'email', $newemail);
                update_nagioscore_contact($newusername, array('email' => $newemail));
            }
        }
    }
}
