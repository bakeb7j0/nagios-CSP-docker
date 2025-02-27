#!/usr/bin/php -q
<?php

//
// Mark an update as completed in the database. Note: do not invoke this script manually.
// Copyright (c) 2024 Nagios Enterprises, LLC. All rights reserved.
//

define("SUBSYSTEM", 1);

// Include XI codebase
require_once(dirname(__FILE__) . '/../html/includes/constants.inc.php');
require_once(dirname(__FILE__) . '/../html/config.inc.php');
require_once(dirname(__FILE__) . '/../html/includes/common.inc.php');

$db_connection = db_connect_all();
if ($db_connection == false) {
	echo "\nCOULD NOT CONNECT TO DATABASE!\n";
	exit;
}

// Mark the command as being completed
$sql = "UPDATE ".$db_tables[DB_NAGIOSXI]["commands"]." SET status_code='".escape_sql_param(COMMAND_STATUS_COMPLETED,DB_NAGIOSXI)."', result_code='0', result='".escape_sql_param('Marked as completed by mark_update_command_as_complete.php', DB_NAGIOSXI)."', processing_time=NOW() WHERE command='".escape_sql_param(COMMAND_UPDATE_XI_TO_LATEST, DB_NAGIOSXI)."'";
exec_sql_query(DB_NAGIOSXI, $sql);

?>
