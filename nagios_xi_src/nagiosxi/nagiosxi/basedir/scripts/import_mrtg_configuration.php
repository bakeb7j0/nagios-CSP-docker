#!/usr/bin/php -q
<?php

//
// Import all current MRTG files into the CCM for management. Link them to host definitions when applicable.
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

$mrtg_directory = "/etc/mrtg/conf.d/";
$mrtg_directory_query = "SELECT mrtgconfig from ".$db_tables[DB_NAGIOSQL]['domain']." LIMIT 1";
$result = exec_sql_query(DB_NAGIOSQL, $mrtg_directory_query);
if ($result) {
	$result = $result->GetArray();
	if (!empty($result) && !empty($result['mrtgconfig'])) {
		$mrtg_directory = $result['mrtgconfig'];
	}
}

$mrtg_files = preg_grep('/^([^.])/', scandir($mrtg_directory));
$mrtg_import_path = '/usr/local/nagios/etc/import/mrtg_import.cfg';

file_put_contents($mrtg_import_path, '');

foreach ($mrtg_files as $file_name) {

	// Is there a host with the same IP address in the CCM?
	$ip_address = preg_replace('/.cfg$/', '', $file_name);
	$host_name_query = "SELECT host_name FROM ".$db_tables[DB_NAGIOSQL]['host']." WHERE address = ".escape_sql_param($ip_address, DB_NAGIOSQL, true) . " LIMIT 1";
	$result = exec_sql_query(DB_NAGIOSQL, $host_name_query);
	$host_name = false;
	if (!empty($result)) {
		$result = $result->GetArray();
		if (!empty($result)) {
			$host_name = $result[0]['host_name'];
		}
	}

	$object_configuration = "define mrtgfile {\n\tfile_name\t\t$file_name\n";
	if (!empty($host_name)) {
		$object_configuration .= "\thost_name\t\t$host_name\n";
	}
	$object_configuration .= "}\n\n";

	file_put_contents($mrtg_import_path, $object_configuration, FILE_APPEND);
}

exec('/usr/local/nagiosxi/scripts/reconfigure_nagios.sh');

