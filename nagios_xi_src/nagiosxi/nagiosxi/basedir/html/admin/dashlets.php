<?php
//
// Copyright (c) 2008-2020 Nagios Enterprises, LLC. All rights reserved.
//

define("SKIPDASHLETS", 1);

require_once(dirname(__FILE__) . '/../includes/common.inc.php');

// Initialization stuff
pre_init();
init_session();

// Grab GET or POST variables and check pre-reqs
grab_request_vars();
check_prereqs();
check_authentication(false);

// Only admins can access this page
if (is_admin() == false) {
    echo _("You are not authorized to access this feature. Contact your system administrator for more information, or to obtain access to this feature.");
    exit();
}

route_request();

function route_request()
{
    global $request;

    if (isset($request["download"]))
        do_download();
    else if (isset($request["upload"]))
        do_upload();
    else if (isset($request["delete"]))
        do_delete();
    else
        show_dashlets();

    exit;
}


function show_dashlets($error = false, $msg = "")
{
    global $dashlets;
    $non_system_dashlets = array();
    $dashlets = array();

    reset($dashlets);
    $p = dirname(__FILE__) . "/../includes/dashlets/";
    $subdirs = scandir($p);
    foreach ($subdirs as $sd) {
        if ($sd == "." || $sd == "..")
            continue;
        $d = $p . $sd;
        if (is_dir($d)) {
            $cf = $d . "/$sd.inc.php";
            if (file_exists($cf)) {
                $dashlets_copy = $dashlets;
                include_once($cf);
                $non_system_dashlets[basename($d)] = array_diff_key($dashlets, $dashlets_copy);
            }
        }
    }
    ksort($dashlets);
    ksort($non_system_dashlets);

    do_page_start(array("page_title" => _('Manage Dashlets')), true);
?>

<script type="text/javascript">
$(document).ready(function() {
    $('.about').each(function() {

        // Verify height of object to fit into the info section
        var h = $(this).parent().height();
        var mh = $(this).parent().find('h2').outerHeight() + $(this).parent().find('.thedashlet').outerHeight();
        var nh = h-mh-10;

        console.log($(this).parent().find('.thedashlet').height());

        $(this).css('height', nh+'px');

    });
});
</script>
    <h1><?php echo _('Manage Dashlets'); ?></h1>

    <p class="neptune-subtext">
        <?php if (!is_neptune()) { ?>
            <?php echo _("Manage the dashlets that are installed on this system and available to users.") . "" . _(" Need a custom dashlet created for your organization?  <a href='https://www.nagios.com/contact/' target='_blank'>Contact us</a> for pricing information."); 
            if (!custom_branding()) { ?>
            <br>
            <?php echo _(' You can find additional dashlets for Nagios XI at'); ?> 
            <a href="https://exchange.nagios.org/directory/Addons/Dashlets" target="_blank" rel="noreferrer"><?php echo _('Nagios Exchange'); ?><i class="fa fa-external-link fa-ml"></i></a>.
            <?php } ?>
        <?php } else { ?>
            <?php echo _("Manage the dashlets that are installed on this system and available to users.") . "<br/><br/>" . _(" Need a custom dashlet created for your organization?  <a href='https://www.nagios.com/contact/' target='_blank'>Contact us</a> for pricing information."); 
            if (!custom_branding()) { ?>
            <br/>
            <?php echo _("You can find additional dashlets for Nagios XI at"); ?>
            <a href='https://exchange.nagios.org/directory/Addons/Dashlets' target='_blank' rel="noreferrer"><?php echo _('Nagios Exchange'); ?></a>.<br/>
            <?php } ?>
        <?php } ?>
    </p>

    <?php display_message($error, false, "");
    
    echo neptune_section_spacer();
    
    if (!is_neptune()) { ?>
        <div class="well">
    <?php } else {?> 
        <div>
    <?php } ?>
        <form enctype="multipart/form-data" action="dashlets.php" method="post" style="margin: 0;">
            <?php echo get_nagios_session_protector(); ?>
            <input type="hidden" name="upload" value="1">
            <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo get_php_upload_max_filesize(); ?>">

            <?php if (!is_neptune()) { ?>
            <div class="fl upload-title"><?php echo _('Upload a Dashlet'); ?></div>
            <?php } else { ?>
            <h5 class="neptune-flex-center"><span class="material-symbols-outlined">upload</span><?php echo _('Upload a Dashlet'); ?></h5>
            <?php } ?>

            <?php if (!is_neptune()) { ?>
            <div class="fl" style="margin-right: 10px;">
                <div class="input-group" style="width: 240px;">
                    <span class="input-group-btn">
                        <span class="btn btn-sm btn-default btn-file">
                            <?php echo _('Browse'); ?>&hellip; <input type="file" name="uploadedfile">
                        </span>
                    </span>
                    <input type="text" class="form-control" style="width: 200px;" readonly>
                </div>
            </div>
            <div class="fl">
                <button type="submit" class="btn btn-sm btn-primary"><?php echo _('Upload &amp; Install'); ?></button>
            </div>
            <div class="fr">
                <?php if (!custom_branding()) { ?>
                    <a href="https://exchange.nagios.org/directory/Addons/Dashlets" class="btn btn-sm btn-default"><?php echo _('More Dashlets'); ?> <i class="fa fa-external-link r"></i></a>
                <?php } ?>
            </div>
            <?php } else { ?>
                <div class="neptune-flex-center-spacebetween">
                    <div class="input-group" style="width: 100%;">
                        <span class="input-group-btn neptune-se-input-group-btn">
                            <span class="btn btn-sm btn-default btn-file btn-icon">
                                <?php echo _('Browse'); ?>&hellip; <input type="file" class="tt-bind" title="" data-placement="right" name="uploadedfile">
                            </span>
                        </span>
                        <div class="neptune-flex-nowrap">
                            <input type="text" class="form-control neptune-browse-box" readonly neptune-readonly placeholder="No file selected" style="width: auto;">
                            <button type="submit" class="material-symbols-outlined tt-bind md-400 btn btn-sm btn-default btn-file btn-icon form-control-left-open" style="padding: 10px;" data-original-title="<?php echo _("Upload &amp; Install"); ?>">upload</button>
                        </div>
                    </div>
                    <div class="fr neptune-flex-center-spacebetween">
                        <?php if (!custom_branding()) { ?>
                            <a href="https://exchange.nagios.org/directory/Addons/Dashlets" class="material-symbols-outlined tt-bind md-middle md-button md-action md-400 md-20" data-placement="left" data-original-title="<?php echo _("More Dashlets"); ?>">open_in_new</a>
                        <?php } ?>
                    </div>
                </div>
                <?php echo neptune_section_spacer(); ?>
            <?php } ?>
            <div class="clear"></div>
        </form>
    </div>

    <?php if (!is_neptune()) { ?>
        <?php
        foreach ($non_system_dashlets as $d => $non_dashlets) {
            $dashlet_dir = $d;
            foreach ($non_dashlets as $name => $darray) {
                show_dashlet($dashlet_dir, $name, $darray);
            }
        }
        ?>
    <?php } else { ?>
        <div class="neptune-dashlets-parent-container">
            <?php
            foreach ($non_system_dashlets as $d => $non_dashlets) {
                $dashlet_dir = $d;
                foreach ($non_dashlets as $name => $darray) {
                    show_dashlet($dashlet_dir, $name, $darray);
                }
            }
            ?>
        </div>
    <?php } /*neptune */?>
    <div class="clear"></div>

<?php
    do_page_end(true);
    exit();
}

/**
 * @param $dashlet_dir
 * @param $dashlet_name
 * @param $darray
 * @param $x
 */
function show_dashlet($dashlet_dir, $dashlet_name, $darray)
{
    
    if (!array_key_exists('preview_class', $darray)) { return; }

    echo "<div class='fl md-box'>";
    display_dashlet_preview($dashlet_name, $darray);
    echo "<div class='md-buttons neptune-md-buttons'><a href='?download=" . $dashlet_dir . "' class='material-symbols-outlined tt-bind md-middle md-button md-action md-400 md-20' title='" . _('Download') . "'>download</a>";
    echo "<a href='?delete=" . $dashlet_dir . "&nsp=" . get_nagios_session_protector_id() . "' class='material-symbols-outlined tt-bind md-middle md-button md-action md-400 md-20' title='" . _('Delete') . "'>delete</a></div>";
    echo "<div class='clear'></div></div>";
}


function do_download()
{
    if (in_demo_mode()) {
        show_dashlets(true, _("Changes are disabled while in demo mode."));
    }

    $dashlet_dir = grab_request_var("download");
    if (have_value($dashlet_dir) == false) {
        show_dashlets();
    }

    // clean the name
    $dashlet_dir = str_replace("..", "", $dashlet_dir);
    $dashlet_dir = str_replace("/", "", $dashlet_dir);
    $dashlet_dir = str_replace("\\", "", $dashlet_dir);

    $id = submit_command(COMMAND_PACKAGE_DASHLET, $dashlet_dir);
    if ($id <= 0)
        show_dashlets(true, _("Error submitting command."));
    else {
        for ($x = 0; $x < 40; $x++) {
            $status_code = -1;
            $result_code = -1;
            $args = array(
                "command_id" => $id
            );
            $xml = get_command_status_xml($args);
            if ($xml) {
                if ($xml->command[0]) {
                    $status_code = intval($xml->command[0]->status_code);
                    $result_code = intval($xml->command[0]->result_code);
                }
            }
            if ($status_code == 2) {
                if ($result_code == 0) {

                    // wizard was packaged, send it to user
                    $dir = get_tmp_dir();
                    $thefile = $dir . "/dashlet-" . $dashlet_dir . ".zip";

                    //chdir($dir);

                    $mime_type = "";
                    header('Content-type: ' . $mime_type);
                    header("Content-length: " . filesize($thefile));
                    header('Content-Disposition: attachment; filename="' . basename($thefile) . '"');
                    readfile($thefile);
                } else
                    show_dashlets(true, _("Dashlet packaging timed out."));
                exit();
            }
            usleep(500000);
        }
    }

    exit();
}


function do_upload()
{
    if (in_demo_mode()) {
        show_dashlets(true, _("Changes are disabled while in demo mode."));
    }

    check_nagios_session_protector();

    $target_path = get_tmp_dir() . "/";
    $dashlet_file = basename($_FILES['uploadedfile']['name']);
    $target_path .= "dashlet-" . $dashlet_file;

    send_to_audit_log("User installed dashlet '" . $dashlet_file . "'", AUDITLOGTYPE_CHANGE);

    if (move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $target_path)) {

        chmod($target_path, 0770);
        chgrp($target_path, "nagios");

        $id = submit_command(COMMAND_INSTALL_DASHLET, $dashlet_file);
        if ($id <= 0)
            show_dashlets(true, _("Error submitting command."));
        else {
            for ($x = 0; $x < 20; $x++) {
                $status_code = -1;
                $result_code = -1;
                $args = array(
                    "command_id" => $id
                );
                $xml = get_command_status_xml($args);
                if ($xml) {
                    if ($xml->command[0]) {
                        $status_code = intval($xml->command[0]->status_code);
                        $result_code = intval($xml->command[0]->result_code);
                        $result_text = strval($xml->command[0]->result);
                    }
                }
                if ($status_code == 2) {
                    if ($result_code == 0)
                        show_dashlets(false, _("Dashlet installed."));
                    else {
                        $emsg = "";
                        if ($result_text != "")
                            $emsg .= " " . $result_text . "";
                        show_dashlets(true, _("Dashlet installation failed.") . $emsg);
                    }
                    exit();
                }
                usleep(500000);
            }
        }
        show_dashlets(false, _("Dashlet scheduled for installation."));
    } else {
        show_dashlets(true, _("Dashlet upload failed."));
    }

    exit();
}

function do_delete()
{
    if (in_demo_mode()) {
        show_dashlets(true, _("Changes are disabled while in demo mode."));
    }

    // check session
    check_nagios_session_protector();

    $dir = grab_request_var("delete", "");

    // clean the filename
    $dir = str_replace("..", "", $dir);
    $dir = str_replace("/", "", $dir);
    $dir = str_replace("\\", "", $dir);

    if (empty($dir)) {
        show_dashlets();
    }

    // log it
    send_to_audit_log("User deleted dashlet '" . $dir . "'", AUDITLOGTYPE_DELETE);

    $id = submit_command(COMMAND_DELETE_DASHLET, $dir);
    if ($id <= 0)
        show_dashlets(true, _("Error submitting command."));
    else {
        for ($x = 0; $x < 14; $x++) {
            $status_code = -1;
            $args = array(
                "command_id" => $id
            );
            $xml = get_command_status_xml($args);
            if ($xml) {
                if ($xml->command[0]) {
                    $status_code = intval($xml->command[0]->status_code);
                }
            }
            if ($status_code == 2) {
                show_dashlets(false, _("Dashlet deleted."));
                exit();
            }
            usleep(500000);
        }
    }
    show_dashlets(false, _("Dashlet scheduled for deletion."));
    exit();
}
