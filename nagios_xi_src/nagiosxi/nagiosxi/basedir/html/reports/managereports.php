<?php
//
// Copyright (c) 2010-2024 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/../includes/common.inc.php');

// Initialization stuff
pre_init();
init_session();

// Grab GET or POST variables and check pre-reqs
grab_request_vars();
check_prereqs();
check_authentication(false);


route_request();

function route_request() {
    global $request;

    $mode = grab_request_var("mode", "");

    switch ($mode) {
        case "download":
            do_download();
            break;
        default:
            show_page();
            break;
    }
}

function show_page() {
    http_response_code(404);
    ?>
    <html>
        <head>
            <title>404 Not Found</title>
        </head>
        <body>
        <h1>Not Found</h1>
        <p>The requested URL was not found on this server.</p>
        </body>
    </html>
    <?php
    // Show probs implement at some point
    // $title = _("Manage Generate Reports");
    // do_page_start(array("page_title" => $title), true);
    // do_page_end();
}

function do_download() {
    $command_id = grab_request_var("command_id", "");

    if ($command_id == "") {
        http_response_code(500);
        echo json_encode([ "message" => "Command ID not specified" ]);
        return;
    }

    $args = array(
        "command_id" => $command_id,
    );

    $command_status = get_command_status_xml($args, true, false);

    if(!empty($command_status)) {
        $command_status = $command_status[0];
        $filename = $command_status['result'];
        if($filename != "" && is_string($filename) && (strstr($filename, ".pdf") != false)) {
            $tempfile = get_tmp_dir()."/".$filename;
            if (!file_exists($tempfile)) {
                http_response_code(404);
                fail_report(_("Report file not found."));
                return;
            } else {
                header('Content-type: application/pdf');
                header("Content-Disposition: inline; filename=\"{$filename}\"");
                readfile($tempfile);
                // For now we will delete the file, but eventually we should have them kept track of somewhere
                unlink($tempfile);
            }
        } else {
            http_response_code(500);
            fail_report(_("Error in report generation."));
            return;
        }
    } else {
        http_response_code(404);
        fail_report(_("Command not found with supplied commmand ID."));
        return;
    }
}

function fail_report($message) {
    $color = "#000";
    if (get_theme() == 'xi5dark') { $color = "#EEE"; }
    echo '<div style="margin: 7% auto; color: '.$color.'; max-width: 80%; text-align: center; font-family: verdana, arial; font-size: 1rem; word-wrap: break-word;">';
    echo '<div><strong>' . _('Failed to create report') . '</strong></div>';
    echo '<div style="margin: 10px 0 30px 0;">' . $message . ':</div>';
    echo '</div>';
    die();
}