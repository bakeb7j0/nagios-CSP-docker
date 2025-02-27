<?php
//
// Report Exporting Utilities
// Copyright (c) 2015-2020 Nagios Enterprises, LLC. All rights reserved.
//


define('EXPORT_PDF', 'pdf');
define('EXPORT_JPG', 'jpg');
define('EXPORT_PORTRAIT', 0);
define('EXPORT_LANDSCAPE', 1);

/*
 * Download a report as a PDF file or JPG image. Exact same as submit report but waits until command completes and returns PDF
 *
 * @param   string      $reportname         The name of the report file (i.e. 'availability')
 * @param   constant    $type               Must be EXPORT_PDF (default) or EXPORT_JPG
 * @param   constant    $orientation        Can be either EXPORT_PORTRAIT (default) or EXPORT_LANDSCAPE
 * @param   string      $report_location    Used to override the folder in which the $reportname .php resides
 * @param   string      $filename           The name of the file that runs the report (i.e. index.php or availability.php)
 */
function export_report($reportname, $type = EXPORT_PDF, $orientation = EXPORT_PORTRAIT, $report_location = "reports")
{
    global $cfg;
    $username = $_SESSION['username'];
    $language = $_SESSION['language'];

    // Grab the current URL parts
    $query = array();
    foreach($_GET as $key => $value) {
        $query[$key] = $value;
    }

    // Add in some required components to the query
    $query['token'] = user_generate_auth_token(get_user_id($username));
    $query['locale'] = $language;
    $query['records'] = 100000;
    $query['mode'] = 'getreport';
    $query['hideoptions'] = 1;
    $query['export'] = 1;

    if ($reportname == 'execsummary') {
        $query['records'] = 10;
    }

    $content_type = 'application/pdf';
    if($type == EXPORT_JPG) {
        $content_type = 'application/jpg';
    }

    $url = get_localhost_url() . $report_location . '/' . urlencode($reportname) . '.php?' . http_build_query($query);

    $id = uniqid();
    $filename = "$reportname-" . $id . ".$type";
    $tempfile = get_tmp_dir().'/'.$filename;

    $args = [
        "filename" => $filename,
        "url" => $url,
        "type" => $type,
    ];

    $command_id = submit_command(COMMAND_DOWNLOAD_REPORT, serialize($args));

    if ($command_id > 0) {
        // Wait for up to 5 minutes
        for ($x = 0; $x < 300; $x++) {
            $status_code = -1;
            $result_code = -1;
            $args = [
                "command_id" => $command_id
            ];
            $xml = get_command_status_xml($args);
            if ($xml) {
                if ($xml->command[0]) {
                    $status_code = intval($xml->command[0]->status_code);
                    $result_code = intval($xml->command[0]->result_code);
                }
            }
            if ($status_code == 2) {
                if ($result_code == 0) {
                    break;
                }
            }
            sleep(1);
        }
    } else {
        fail_download($url, $type);
    }

    if (!file_exists($tempfile)) {
        fail_download($url, $type);
    } else {
        header('Content-type: ' . $content_type);
        header('Content-Disposition: inline; filename="' . time() . '-' . $reportname . '.' . $type . '"');
        readfile($tempfile);
        unlink($tempfile);
    }
}


/*
 * Submits command to download a report as a PDF file or JPG image
 *
 * @param   string      $reportname         The name of the report file (i.e. 'availability')
 * @param   constant    $type               Must be EXPORT_PDF (default) or EXPORT_JPG
 * @param   constant    $orientation        Can be either EXPORT_PORTRAIT (default) or EXPORT_LANDSCAPE
 * @param   string      $report_location    Used to override the folder in which the $reportname .php resides
 * @param   string      $filename           The name of the file that runs the report (i.e. index.php or availability.php)
 */
function submit_report($reportname, $type = EXPORT_PDF, $orientation = EXPORT_PORTRAIT, $report_location = "reports")
{
    global $cfg;
    $username = $_SESSION['username'];
    $language = $_SESSION['language'];

    // Grab the current URL parts
    $query = array();
    foreach($_GET as $key => $value) {
        $query[$key] = $value;
    }

    // Add in some required components to the query
    $query['token'] = user_generate_auth_token(get_user_id($username));
    $query['locale'] = $language;
    $query['records'] = 100000;
    $query['mode'] = 'getreport';
    $query['hideoptions'] = 1;
    $query['export'] = 1;

    if ($reportname == 'execsummary') {
        $query['records'] = 10;
    }

    $url = get_localhost_url() . $report_location . '/' . urlencode($reportname) . '.php?' . http_build_query($query);

    $id = uniqid();
    $filename = "$reportname-" . $id . ".$type";

    $args = [
        "filename" => $filename,
        "url" => $url,
        "type" => $type,
    ];

    $result = submit_command(COMMAND_DOWNLOAD_REPORT, serialize($args));

    $retval = [
        "command_id" => $result
    ];

    header('Content-Type: application/json');
    echo json_encode($retval);
}

/**
 * @param   string      $url            URL to pass to chromium
 * @param   string      $filename       Name of the file you want to save
 * @param   string      $type           PDF or JPG
 */
function get_chromium_command($url, $filename, $type) {
    global $cfg;

    $bin = 'chromium-browser --headless=new --ignore-certificate-errors --enable-low-end-device-mode --disable-gpu --virtual-time-budget=10000 --run-all-compositor-stages-before-draw';

    $home_file = get_nagios_home().'/'.$filename;
    $tmp_file = get_tmp_dir().'/'.$filename;

    // Do specifics for each type of report
    switch ($type)
    {
        case EXPORT_PDF:
            // We have to write to home nagios and move to the tmp folder because snap chromium won't let us write anywhere else
            $opts = " --no-pdf-header-footer --desktop-window-1080p --print-to-pdf=" . escapeshellarg($home_file) . " ";
            break;
        case EXPORT_JPG:
            // We have to write to home nagios and move to the tmp folder because snap chromium won't let us write anywhere else
            $opts = " --hide-scrollbars --screenshot=" . escapeshellarg($home_file) . " ";
            break;
        default:
            die(_('ERROR: Could not export report as ') . $type . '. ' . _('This type is not defined.'));
    }

    $log = get_root_dir().'/var/chromium_report.log';

    $bash_cmd = $bin . $opts . escapeshellarg($url);
    
    if (file_exists($log) && is_writable($log)) {
        file_put_contents($log, "[".date(DATE_RFC2822)."] {$bash_cmd}\n", FILE_APPEND);
        $bash_cmd .= " &>> $log"; // This will not work with snap chromium on ubuntu
    }

    // Snap chromium only allows writing to home directory, so we gotta mv it to the tmp folder
    $bash_cmd .= " && mv " . escapeshellarg($home_file) . " " . escapeshellarg($tmp_file) . " && echo " . escapeshellarg($filename);

    // Have to do this weirdness because the shell command doesn't do && in the right order
    $cmd = "/bin/bash -c '".str_replace("'", "'\''", $bash_cmd)."'";

    clearstatcache();
    $lockfile = get_nagios_home()."/.config/chromium/SingletonLock";
    if (file_exists($lockfile) || is_link($lockfile)) {
        if (`pgrep -u $(whoami) -f -c "[c]hromium-browser"` == "0") {
            exec("rm -rf ".get_tmp_dir()."/.config/chromium");
        }
    }
    return $cmd;
}

function fail_download($url, $type) {
    $color = "#000";
    if (get_theme() == 'xi5dark') { $color = "#EEE"; }
    echo '<div style="margin: 7% auto; color: '.$color.'; max-width: 80%; text-align: center; font-family: verdana, arial; font-size: 1rem; word-wrap: break-word;">';
    echo '<div><strong>' . _('Failed to create ') . '<span style="text-transform: uppercase;">' . $type . '</span></strong></div>';
    echo '<div style="margin: 10px 0 30px 0;">' . _('Verify that your Nagios XI server can connect to the URL') . ':</div>';
    echo '<div style="font-size: 0.7rem;">' . $url . '</div>';
    echo '</div>';
    die();
}
