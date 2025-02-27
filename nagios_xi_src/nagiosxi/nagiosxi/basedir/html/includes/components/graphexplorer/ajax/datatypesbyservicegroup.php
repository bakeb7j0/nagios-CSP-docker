<?php
//
// API Access for Hosts/Services
// Copyright (c) 2024 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/../../../common.inc.php');
include_once(dirname(__FILE__) . '/../dashlet.inc.php');
require_once(dirname(__FILE__) . '/../visFunctions.inc.php');

// Initialization stuff
pre_init();
init_session();

// Grab GET or POST variables and pre-reqs
grab_request_vars();
check_prereqs();

// Check authentication
check_authentication(false);

$servicegroup_name = grab_request_var('servicegroup_name');
$all = grab_request_var('all', 0);
show_servicegroup_datatypes($servicegroup_name, $all);
