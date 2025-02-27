<?php
//
// Copyright (c) 2008-2020 Nagios Enterprises, LLC. All rights reserved.
//


////////////////////////////////////////////////////////////////////////
// WIZARD FUNCTIONS
////////////////////////////////////////////////////////////////////////

### START Loading Configs
# TODO: Should these functions by here or in utils-xi2024-wizards.inc.php?

/**
 * Get an array of hostnames, configured for the specified wizard.
 *
 * @param   string  $keyname    Name of the config wizard
 * @return  array               Limited array of configuration data
 */
function get_configwizard_hosts($keyname)
{
    global $db_tables;

    $dataArray = array();

    if ($keyname != "") {
        $sql = "SELECT name1 AS hostname FROM ".$db_tables[DB_NDOUTILS]['objects']." AS o LEFT JOIN ".$db_tables[DB_NDOUTILS]['customvariables']." AS c ON o.object_id = c.object_id WHERE o.objecttype_id = ".OBJECTTYPE_HOST." AND o.is_active = 1 AND c.varname = 'XIWIZARD' AND c.varvalue = '".escape_sql_param($keyname, DB_NDOUTILS)."';";

        $rows = exec_sql_query(DB_NDOUTILS, $sql);

        if ($rows) {
            $rows = $rows->GetRows();

            foreach($rows as $row) {
                $dataArray[] = $row['hostname'];
            }
        }
    }

    return $dataArray;
}

/**
 * Get the step1 configuration data, for the specified configuration wizard.
 * No services or serviceargs data.
 *
 * @param   string  $keyname    Name of the config wizard
 * @return  array               Array of configuration data
 */

function get_configwizard_config_step1($keyname, $hostname)
{
    global $db_tables;

    if ($keyname != "" && $hostname != "") {
#echo("keyname: ".$keyname." hostname: ".$hostname."<p>");
        $sql = "SELECT keyvalue FROM ".$db_tables[DB_NAGIOSXI]["meta"]." WHERE metatype_id='".escape_sql_param(METATYPE_CONFIGWIZARD, DB_NAGIOSXI)."' AND keyname = '".escape_sql_param($keyname, DB_NAGIOSXI)."__".escape_sql_param($hostname, DB_NAGIOSXI)."__'";
#echo("FINAL: SQL: ".$sql."<p>");

        $rows = exec_sql_query(DB_NAGIOSXI, $sql);

        if ($rows) {
            $rows = $rows->GetRows();
        }

        foreach($rows as $row) {
            $values = unserialize($row['keyvalue']);

            # Remove unnecessary and private data
            unset($values['services']);
            unset($values['serviceargs']);
            unset($values['password']);

#echo("Original - username: ".$values['username']." password: ".$values['password']." hostname: ".$values['hostname']."<p>");
            $values['username'] = addslashes($values['username']);
            $values['hostname'] = addslashes($values['hostname']);
#echo("Replaced - username: ".$values['username']." password: ".$values['password']." hostname: ".$values['hostname']."<p>");

            # Should only be one.
            return $values;
        }
    }

    return null;
}

/**
 * Get a specified value from the keyvalue, for the specified keyname, hostname and array key.
 *
 * @param   string  $keyname    Name of the config wizard
 * @return  array               Array of configuration data
 */

function get_configwizard_config_value($keyname, $hostname, $arraykey)
{
    global $db_tables;

    if ($keyname != "" && $hostname != "" && $arraykey != "") {
#echo("keyname: ".$keyname." hostname: ".$hostname."<p>");
        $sql = "SELECT keyvalue FROM ".$db_tables[DB_NAGIOSXI]["meta"]." WHERE metatype_id='".escape_sql_param(METATYPE_CONFIGWIZARD, DB_NAGIOSXI)."' AND keyname = '".escape_sql_param($keyname, DB_NAGIOSXI)."__".escape_sql_param($hostname, DB_NAGIOSXI)."__'";
#echo("FINAL: SQL: ".$sql."<p>");

        $rows = exec_sql_query(DB_NAGIOSXI, $sql);

        if ($rows) {
            $rows = $rows->GetRows();
        }

        foreach($rows as $row) {
            $values = unserialize($row['keyvalue']);

            # Should only be one.
            # Return the requested array value.
            return $values[$arraykey];
        }
    }

    return null;
}

/**
 * Get all the configuration data, for the specified configuration wizard
 *
 * @param   string  $keyname    Name of the config wizard
 * @return  array               Array of configuration data
 */

function get_configwizard_config($keyname, $hostname)
{
    global $db_tables;

    if ($keyname != "" && $hostname != "") {
#echo("keyname: ".$keyname." hostname: ".$hostname."<p>");

        $sql = "SELECT keyvalue FROM ".$db_tables[DB_NAGIOSXI]["meta"]." WHERE metatype_id='".escape_sql_param(METATYPE_CONFIGWIZARD, DB_NAGIOSXI)."' AND keyname = '".escape_sql_param($keyname, DB_NAGIOSXI)."__".escape_sql_param($hostname, DB_NAGIOSXI)."__'";
#echo("FINAL: SQL: ".$sql."<p>");

        $rows = exec_sql_query(DB_NAGIOSXI, $sql);

        if ($rows) {
            $rows = $rows->GetRows();
        }

        foreach($rows as $row) {
            $values = unserialize($row['keyvalue']);

#echo("Original - username: ".$values['username']." password: ".$values['password']." hostname: ".$values['hostname']."<p>");
            $values['username'] = addslashes($values['username']);
            $values['password'] = addslashes($values['password']);
            $values['hostname'] = addslashes($values['hostname']);
#echo("Replaced - username: ".$values['username']." password: ".$values['password']." hostname: ".$values['hostname']."<p>");

            # Should only be one.
            return $values;
        }
    }

    return null;
}

### END Loading Configs


/**
 * Get the configuration wizard used for a specified hostname
 *
 * @param   string  $hostname   Host name
 * @return  string              Name of config wizard
 */
function get_host_configwizard($hostname)
{
    global $db_tables;
    $wizardname = "";

    // Find the config wizard name for hostusing saved meta-data
    $keyname = get_configwizard_meta_key_name2($hostname);
    if ($keyname != "") {

        $sql = "SELECT * FROM " . $db_tables[DB_NAGIOSXI]["meta"] . " WHERE metatype_id='" . escape_sql_param(METATYPE_CONFIGWIZARD, DB_NAGIOSXI) . "' AND keyname LIKE '%" . escape_sql_param($keyname, DB_NAGIOSXI) . "'";

        if (($rs = exec_sql_query(DB_NAGIOSXI, $sql))) {

            // Only find the first match
            if ($rs->MoveFirst()) {

                $dbkeyname = $rs->fields["keyname"];

                // Get the wizard name from the key
                $pos = strpos($dbkeyname, $keyname);
                if ($pos !== false) {
                    $wizardname = substr($dbkeyname, 0, $pos);
                }
            }
        }
    }

    return $wizardname;
}


/**
 * @param $hostname
 * @param $servicename
 *
 * @return string
 */
function get_service_configwizard($hostname, $servicename)
{
    global $db_tables;

    $wizardname = "";

    // find the config wizard name for service using saved meta-data
    $keyname = get_configwizard_meta_key_name2($hostname, $servicename);
    if ($keyname != "") {

        $sql = "SELECT * FROM " . $db_tables[DB_NAGIOSXI]["meta"] . " WHERE metatype_id='" . escape_sql_param(METATYPE_CONFIGWIZARD, DB_NAGIOSXI) . "' AND keyname LIKE '%" . escape_sql_param($keyname, DB_NAGIOSXI) . "'";

        if (($rs = exec_sql_query(DB_NAGIOSXI, $sql))) {

            // only find the first match
            if ($rs->MoveFirst()) {

                $dbkeyname = $rs->fields["keyname"];

                // get the wizard name from the key
                $pos = strpos($dbkeyname, $keyname);
                if ($pos !== false) {
                    $wizardname = substr($dbkeyname, 0, $pos);
                }
            }
        }
    }

    // if no wizard was found, try the host
    if ($wizardname == "") {
        $wizardname = get_host_configwizard($hostname);
    }

    return $wizardname;
}


// generates a meta key name that can be used for saving/retrieving config wizard data for configured items
/**
 * @param        $wizardname
 * @param        $hostname
 * @param string $servicename
 *
 * @return string
 */
function get_configwizard_meta_key_name($wizardname, $hostname, $servicename = "")
{
    $keyname = "";
    $keyname .= $wizardname;
    $keyname .= get_configwizard_meta_key_name2($hostname, $servicename);
    return $keyname;
}


// generates the host/service portion of the meta key - used for saving and retreiving the config wizard data later
/**
 * @param        $hostname
 * @param string $servicename
 *
 * @return string
 */
function get_configwizard_meta_key_name2($hostname, $servicename = "")
{
    $keyname = "";
    $keyname .= "__" . $hostname;
    $keyname .= "__" . $servicename;
    return $keyname;
}


// save config wizard data for later re-entrace
/**
 * @param $wizardname
 * @param $hostname
 * @param $servicename
 * @param $meta_arr
 */
function save_configwizard_object_meta($wizardname, $hostname, $servicename, $meta_arr)
{
    $meta_ser = serialize($meta_arr);
    set_meta(METATYPE_CONFIGWIZARD, 0, get_configwizard_meta_key_name($wizardname, $hostname, $servicename), $meta_ser);
}


// retrieves config wizard data
/**
 * @param $wizardname
 * @param $hostname
 * @param $servicename
 *
 * @return array|mixed
 */
function get_configwizard_object_meta($wizardname, $hostname, $servicename)
{
    $meta_arr = array();
    $meta_ser = get_meta(METATYPE_CONFIGWIZARD, 0, get_configwizard_meta_key_name($wizardname, $hostname, $servicename));
    if ($meta_ser != null) {
        $meta_arr = unserialize($meta_ser);
    }
    return $meta_arr;
}


// determines if config wizard data exists
/**
 * @param $wizardname
 * @param $hostname
 * @param $servicename
 *
 * @return bool
 */
function configwizard_object_meta_exists($wizardname, $hostname, $servicename)
{
    $meta_ser = get_meta(METATYPE_CONFIGWIZARD, 0, get_configwizard_meta_key_name($wizardname, $hostname, $servicename));
    if ($meta_ser != null) {
        return true;
    }
    return false;
}


/**
 * @param $hostname
 */
function delete_host_configwizard_meta($hostname)
{
    $wizardname = get_host_configwizard($hostname);
    $keyname = $wizardname . get_configwizard_meta_key_name2($hostname);
    delete_meta(METATYPE_CONFIGWIZARD, 0, $keyname);
}


/**
 * @param $hostname
 * @param $servicename
 */
function delete_service_configwizard_meta($hostname, $servicename)
{
    $wizardname = get_service_configwizard($hostname, $servicename);
    $keyname = $wizardname . get_configwizard_meta_key_name2($hostname, $servicename);
    delete_meta(METATYPE_CONFIGWIZARD, 0, $keyname);
}


/**
 * grab_in_var()
 * Manages intake of input args and session, as follows:
 *     If provided in $inarg, use that, else get from session, else use $default
 *     When done, update session and return value.
 *
 * @param array    $inargs       Array of form values.
 * @param string   $name         Key of element to be extracted.
 * @param mixed    $default      Value to be used if not avaliable in $inargs or session
 * @param array    &$sess        Reference to session, or sub-array of session, e.g. $_SESSION['abc]
 *
 * @return mixed                 Value as determined above.
 */
function grab_in_var($inargs, $name, $default, &$sess) {
    $sessVal = grab_array_var($sess, $name, $default);
    $inVal = grab_array_var($inargs, $name, $sessVal);
    $sess[$name] = $inVal;

    return $inVal;
}

/**
 * clear_sess_vars()
 * Clears every element from $sess that isn't specified in $keepers
 * Useful for removing innapproprite values from session after returning to a previous page in form sequence.
 *
 * @param array    &$sess       Reference to session, or sub-array of session, e.g. $_SESSION['abc]. Passed by reference.
 * @param array    $keepers     Array of session array keys to be retained.
 *
 * @return null                 Updates &$sess in place.
 */
function clear_sess_vars(&$sess, $keepers) {
    foreach($sess as $key => $val) {
        if (!in_array($key, $keepers)) {
            unset($sess[$key]);
        }
    }
}

/**
 * Trims and encodes entities of all values in array recursively, except those specifically excluded.
 * Encodes using encode_form_val() which performs "htmlentities($rawval, ENT_COMPAT, 'UTF-8')"
 *
 * @param array   &$formValsArray    Array of arbitrary dimensions containing values to be encoded. Passed by reference.
 * @param array   $exclude           Array of array keys of elements to be excluded. All instances of key will be excluded.
 *                                   Entire sub-arrays cannot be excluded by their key, i.e., the exclude array doesn't affect
 *                                   the traversing of the hierarchy.
 *
 * @return null                     &$formValsArray is updated in place
 */
function encode_form_all(&$formValsArray, $exclude = []) {
    array_walk_recursive($formValsArray, function (&$val, $key) use ($exclude) {
        if (!in_array($key, $exclude, true)) {
            $val = encode_form_val(trim($val));
        }
    });
}

/**
 * macrosAll()
 * Replaces all specified elements of an array with user macros if they exist
 *
 * @param array    $a        Array of items to be replaced with user macro values.
 * @param array    $kList    Array of keys in $a specifying which items to be replaced.
 *
 * @return array             Array of items with values replaced with macros, as specified.
 */
function macrosAll($a, $kList) {
    foreach ($a as $k => $v) {
        if (in_array($k, $kList, true)) {
            $a[$k] = nagiosccm_replace_user_macros($a[$k]);
        }
    }
    return $a;
}

/**
 * Replace chars in string that can't be used in DOM selectors, classes etc.
 *
 * @param   string  $str    String to clean
 * @return  string          Cleaned string
 */
function clean_str_for_dom($str) {
    $clean = preg_replace('~[!@%&=,/;:><\?\+\*\^\$\[\]\\\{\}\|\(\)]~', '', $str);
    $clean = preg_replace('/~#"/', '', $clean);
    $clean = preg_replace("/'`/", '', $clean);
    $clean = preg_replace('~\.~', '_', $clean);
    $clean = preg_replace('~ ~', '_', $clean);
    return $clean;
}
