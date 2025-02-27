<?php 
//
// Copyright (c) 2018-2019 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/configwizardhelper.inc.php');

// Initialization stuff
pre_init();
init_session();

// Grab GET or POST variables and do prereq and auth checks
grab_request_vars();
check_prereqs();
check_authentication();

get_hostconfig();

function get_hostconfig()
{
    global $request;

    $keyname = grab_request_var("keyname", "");
    $hostname = grab_request_var("hostname", "");

    $keyname = nagiosccm_replace_user_macros($keyname);
    $hostname = nagiosccm_replace_user_macros($hostname);

    $step1Config = get_configwizard_config_step1($keyname, $hostname);
error_log("step1Config ".var_export($step1Config, true));

    header('Content-Type: application/json; charset=utf-8');
    echo(json_encode($step1Config));
}