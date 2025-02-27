<?php
ob_start();

require_once(dirname(__FILE__) . '/common.inc.php');

// Initialization stuff
pre_init();
init_session();

// Grab GET or POST variables and check pre-reqs
grab_request_vars();
check_prereqs();
check_authentication(false);
//check_nagios_session_protector();

// Process variables and submit to dashboard 
$title = grab_request_var('dashletName');
$board = grab_request_var('boardName');
$graph = grab_request_var('graphChoice');
$sort = grab_request_var('sortChoice');
$host = grab_request_var('hostChoice');
$hostgroup = grab_request_var('hostgroupChoice');
$servicegroup = grab_request_var('servicegroupChoice');
$divId = uniqid();
$dargs = array('divId' => $divId, 'graphChoiceSelect' => $graph, 'sortChoiceSelect' => $sort, 'hostChoiceSelect' => $host, 'hostgroupChoiceSelect' => $hostgroup, 'servicegroupChoiceSelect' => $servicegroup);

add_dashlet_to_dashboard(0, $board, 'highchart-dashlet', $title, null, $dargs);

print json_encode(array('success' => 1));

ob_end_flush(); 
