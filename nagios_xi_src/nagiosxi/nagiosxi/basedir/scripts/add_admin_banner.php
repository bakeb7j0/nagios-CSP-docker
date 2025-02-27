<?php

define("SUBSYSTEM", 1);

require_once(dirname(__FILE__) . '/../html/config.inc.php');
require_once(dirname(__FILE__) . '/../html/includes/utils.inc.php');

// Make database connections
$dbok = db_connect_all();
if ($dbok == false) {
    print_timestamp();
    echo "ERROR CONNECTING TO DATABASES!\n";
    exit();
}

$usage = "Script for adding banner messages for Admins\n\n\t-m, --message <message>\t\tMessage in the banner\n\t-t, --type <info | warn | crit | success>\t\tDefault: info\n\n";

// Valid message types
$types = [
    'info' => 'banner_message_banner_info',
    'warn' => 'banner_message_banner_warning',
    'crit' => 'banner_message_banner_critical',
    'success' => 'banner_message_banner_success',
];

$options = getopt('m:t:h', ['message:', 'type::', 'help']);

// Print help
if (array_key_exists('h', $options) || array_key_exists('help', $options)) {
    echo $usage;
    exit(0);
}

// Need a message
if (!array_key_exists('m', $options) && !array_key_exists('message', $options)) {
    echo "Message required\n";
    echo $usage;
    exit(1);
}

if (array_key_exists('m', $options)) {
    $message = $options['m'];
} else {
    $message = $options['message'];
}

// Type not required, Info is default
if (array_key_exists('t', $options)) {
    $type = $options['t'];
} elseif (array_key_exists('type', $options)) {
    $type = $options['type'];
} else {
    $type = "info";
}

if(!array_key_exists($type, $types)) {
    echo "Invalid type\n";
    echo $usage;
    exit(1);
}

send_banner_message($message, 0, 1, 1, 1, $types[$type], 0, '0001-01-01', '0001-01-01', 1);

$last_insert_id = get_sql_insert_id(DB_NAGIOSXI);
$users_list = retrieve_users_list();

$error = 0;
foreach($users_list as $user) {
    $specified = 0;
    if(is_admin($user)) {
        $specified = 1;
    }
    $result_users = insert_users_messages_table($last_insert_id, $user, $specified);

    if (!$result_users) {
        $error++;
    }
}

if ($error) {
    echo "Errors encountered!\n";
    exit(1);
}

echo "Banner sent!\n";
exit(0);