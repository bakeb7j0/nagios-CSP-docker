<?php
//
// Manage (RRD) Graph Templates
// Copyright (c) 2011-2020 Nagios Enterprises, LLC. All rights reserved.
//

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
    else if (isset($request["save"]))
        do_save(false);
    else if (isset($request["apply"]))
        do_save(true);
    else if (isset($request["cancel"]))
        show_templates();
    else if (isset($request["edit"]))
        do_edit();
    else
        show_templates();
}


function show_templates($error = false, $msg = "")
{
    $templates = get_graph_templates();

    do_page_start(array("page_title" => _('Manage Graph Templates')), true);

    $users = array();
    $u = explode("\n", file_get_contents('/etc/passwd'));
    foreach ($u as $l) {
        if (!empty($l)) {
            $x = explode(":", $l);
            $users[$x[2]] = $x[0];
        }
    }

    $groups = array();
    $g = explode("\n", file_get_contents('/etc/group'));
    foreach ($g as $l) {
        if (!empty($l)) {
            $x = explode(":", $l);
            $groups[$x[2]] = $x[0];
        }
    }
?>
    <h1><?php echo _('Manage Graph Templates'); ?></h1>

    <p class="neptune-subtext">
        <?php echo _('Manage the templates used to generate RRDtool performance graphs. These templates do not affect Highcharts created graphs.'); ?>
    </p>
    
    <?php display_message($error, false, $msg); ?>

    <?php if (!is_neptune()) { ?>
        <div class="well">
    <?php } else { ?>
        <div> 
    <?php } ?>
        <form enctype="multipart/form-data" action="" method="post" style="margin: 0;">
            <input type="hidden" name="upload" value="1">
            <?php echo get_nagios_session_protector(); ?>
            <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo get_php_upload_max_filesize(); ?>">

            <?php if (!is_neptune()) { ?>
                <div class="fl upload-title"><?php echo _('Upload a Template'); ?></div>
            <?php } else { ?>
                <?php echo neptune_section_spacer(); ?>
                <h5 class="neptune-flex-center"><span class="material-symbols-outlined">upload</span><?php echo _('Upload a Template'); ?></h5>
            <?php } ?>

            <?php if (!is_neptune()) { ?>
            <div class="fl">
                <div class="input-group" style="width: 240px;">
                    <span class="input-group-btn">
                        <span class="btn btn-sm btn-default btn-file">
                            <?php echo _('Browse'); ?>&hellip; <input type="file" name="uploadedfile">
                        </span>
                    </span>
                    <input type="text" class="form-control" style="width: 200px;" readonly>
                </div>
            </div>
            <button type="submit" class="btn btn-sm btn-primary" style="margin-left: 10px;"><?php echo _('Upload Template'); ?></button>
            <?php } else { ?>
            <div class="neptune-flex-center-spacebetween">
                <div style="display: flex; gap: 5px;">
                    <div class="input-group" style="width: 240px;">
                        <span class="input-group-btn neptune-se-input-group-btn">
                            <span class="btn btn-sm btn-default btn-file btn-icon">
                                <?php echo _('Browse'); ?>&hellip; <input type="file" class="tt-bind" title="" data-placement="right" name="uploadedfile">
                            </span>
                        </span>
                        <div class="neptune-flex-nowrap">
                            <input type="text" class="form-control neptune-browse-box" readonly neptune-readonly placeholder="No file selected" style="width: auto;">
                            <button type="submit" class="material-symbols-outlined tt-bind md-400 btn btn-sm btn-default btn-file btn-icon form-control-left-open" style="padding: 10px;" data-original-title="<?php echo _("Upload Template"); ?>">upload</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php echo neptune_section_spacer(); ?>
            <?php } ?>
            <div class="clear"></div>
        </form>
    </div>

    <?php if (!is_neptune()) { ?>
        <table class="table table-condensed table-bordered table-striped table-auto-width">
        <thead>
            <tr>
    <?php } else { ?>
        <table class="table table-striped table-bordered neptune-system-extension-tables">
        <thead>
            <tr>
    <?php } ?>
                <th><?php echo _('File'); ?></th>
                <th><?php echo _('Directory'); ?></th>
                <th><?php echo _('Owner'); ?></th>
                <th><?php echo _('Group'); ?></th>
                <th><?php echo _('Permissions'); ?></th>
                <th><?php echo _('Date'); ?></th>
                <th class="center"><?php echo _('Actions'); ?></th>
            </tr>
        </thead>
        <tbody>

        <?php
        foreach ($templates as $template) {

            if (array_key_exists($template['group'], $groups)) {
                $group = $groups[$template["group"]];
                if ($group != 'nagios' && $group != 'nagcmd') {
                    $group = '<em>' . $group . '</em>';
                }
            } else {
                $group = $template['group'];
            }

            if (array_key_exists($template['owner'], $users)) {
                $user = $users[$template["owner"]];
                if ($user != 'nagios' && $user != 'nagcmd') {
                    $user = '<em>' . $user . '</em>';
                }
            } else {
                $user = $template['owner'];
            }

            echo "<tr>";
            echo "<td class='neptune-table-name'>" . $template["file"] . "</td>";
            echo "<td>" . $template["dir"] . "</td>";
            echo "<td>" . $user . "</td>";
            echo "<td>" . $group . "</td>";
            echo "<td>" . $template["permstring"] . "</td>";
            echo "<td>" . $template["date"] . "</td>";
            echo "<td class='center'>";            
            echo "<a href='?edit=" . $template["file"] . "&dir=" . $template["dir"] ."' class='material-symbols-outlined tt-bind md-20 md-400 md-middle md-action md-button' title='" . _('Edit') . "'>build</a>";
            echo "<a href='?download=" .  $template["file"] . "' class='material-symbols-outlined tt-bind md-20 md-400 md-middle md-action md-button' title='" . _('Download') . "'>download</a>";
            echo "<a href='?delete=" .  $template["file"] . "&nsp=" . get_nagios_session_protector_id() . "' class='material-symbols-outlined tt-bind md-20 md-400 md-middle md-action md-button' title='" . _('Delete') . "'>delete</a>";
            echo "</td>";
            echo "</tr>\n";
        }
        ?>

        </tbody>
    </table>

<?php
    do_page_end(true);
    exit();
}


/**
 * @param bool   $error
 * @param string $msg
 */
function do_edit($error = false, $msg = "")
{
    $file = grab_request_var("edit", "");
    $tdir = grab_request_var("dir", "templates");

    // clean the filename
    $file = str_replace("..", "", $file);
    $file = str_replace("/", "", $file);
    $file = str_replace("\\", "", $file);

    // clean the directory
    $tdir = str_replace("..", "", $tdir);
    $tdir = str_replace("/", "", $tdir);
    $tdir = str_replace("\\", "", $tdir);

    $whitelist = array("templates","templates.dist","templates.special");
    if(!in_array($tdir, $whitelist)) {
        $tdir = 'templates';
    }

    $dir = get_graph_template_dir() . "/" . $tdir;
    $thefile = $dir . "/" . $file;

    // read file
    $fc = file_get_contents($thefile);

    do_page_start(array("page_title" => _("Edit Graph Template")), true);
?>

    <h1><?php echo _("Edit Graph Template"); ?></h1>

    <?php
    display_message($error, false, $msg);
    ?>

    <form enctype="multipart/form-data" action="" method="post" class="neptune-subtext">
        <?php echo get_nagios_session_protector(); ?>
        <input type="hidden" name="dir" value="<?php echo encode_form_val($tdir); ?>">
        <input type="hidden" name="file" value="<?php echo encode_form_val($file); ?>">

        <strong><?php echo $tdir . "/" . $file; ?></strong>
        <?php if (is_neptune()) { 
        echo neptune_section_spacer(); 
        }?>
        <textarea cols="80" rows="20" name="fc" class="form-control" style="width: 100%; height: 500px; margin: 10px 0 15px 0; font-family: courier; font-size: 12px; line-height: 16px;"><?php echo encode_form_val($fc); ?></textarea><br clear="all">

        <?php if (is_neptune()) { 
        echo neptune_section_spacer();    
        ?>
        <div style="display: flex; gap: 15px;">
        <?php } ?>
            <input type="submit" class="btn btn-sm btn-primary" name="save" value="<?php echo _("Save"); ?>"/>
            <input type="submit" class="btn btn-sm btn-info" name="apply" value="<?php echo _("Apply"); ?>"/>
            <input type="submit" class="btn btn-sm btn-default" name="cancel" value="<?php echo _("Cancel"); ?>"/>
        <?php if (is_neptune()) { ?>
        </div>
        <?php } ?>
    </form>

    <?php

    do_page_end(true);
    exit();
}

/**
 * @param bool $apply
 */
function do_save($apply = false)
{


    // demo mode
    if (in_demo_mode() == true)
        show_templates(true, _("Changes are disabled while in demo mode."));

    // check session
    check_nagios_session_protector();

    $file = grab_request_var("file", "");
    $tdir = grab_request_var("dir", "templates");
    $fc = grab_request_var("fc", "");

    // clean the filename
    $file = str_replace("..", "", $file);
    $file = str_replace("/", "", $file);
    $file = str_replace("\\", "", $file);

    // clean the directory
    $tdir = str_replace("..", "", $tdir);
    $tdir = str_replace("/", "", $tdir);
    $tdir = str_replace("\\", "", $tdir);

    $dir = get_graph_template_dir() . "/" . $tdir;
    $thefile = $dir . "/" . $file;

    $result = file_put_contents($thefile, $fc);
    if ($result === FALSE) {
        $msg = _("Error writing to file.");
        $error = true;
    } else {
        $msg = _("File saved successfully.");
        $error = false;
    }

    // log it
    send_to_audit_log("User edited graph template '" . $file . "'", AUDITLOGTYPE_CHANGE);

    if ($apply == true) {
        do_edit($error, $msg);
    } else {
        show_templates($error, $msg);
    }
}


function do_download()
{

    $file = grab_request_var("download", "");
    $tdir = grab_request_var("dir", "templates");


    // clean the filename
    $file = str_replace("..", "", $file);
    $file = str_replace("/", "", $file);
    $file = str_replace("\\", "", $file);

    // clean the directory
    $tdir = str_replace("..", "", $tdir);
    $tdir = str_replace("/", "", $tdir);
    $tdir = str_replace("\\", "", $tdir);

    $dir = get_graph_template_dir() . "/" . $tdir;
    $thefile = $dir . "/" . $file;

    header('Content-type: ' . "text/plain");
    header("Content-length: " . filesize($thefile));
    header('Content-Disposition: attachment; filename="' . basename($thefile) . '"');
    readfile($thefile);
    exit();
}

function do_upload()
{

    // demo mode
    if (in_demo_mode() == true)
        show_templates(true, _("Changes are disabled while in demo mode."));

    // check session
    check_nagios_session_protector();

    //print_r($request);

    $target_path = get_graph_template_dir() . "/templates";
    $target_path .= "/";
    $target_path .= basename($_FILES['uploadedfile']['name']);

    //echo "TEMP NAME: ".$_FILES['uploadedfile']['tmp_name']."<BR>\n";

    if (move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $target_path)) {
        chmod($target_path, 0664);

        // log it
        send_to_audit_log("User uploaded graph template '" . $_FILES['uploadedfile']['name'] . "'", AUDITLOGTYPE_CHANGE);

        // success!
        show_templates(false, _("New graph template was installed successfully."));
    } else {
        // error
        show_templates(true, _("Graph template could not be installed - directory permissions may be incorrect."));
    }

    exit();
}

function do_delete()
{


    // demo mode
    if (in_demo_mode() == true)
        show_templates(true, _("Changes are disabled while in demo mode."));

    // check session
    check_nagios_session_protector();

    $file = grab_request_var("delete", "");
    $tdir = grab_request_var("dir", "templates");

    // clean the filename
    $file = str_replace("..", "", $file);
    $file = str_replace("/", "", $file);
    $file = str_replace("\\", "", $file);

    // clean the directory
    $tdir = str_replace("..", "", $tdir);
    $tdir = str_replace("/", "", $tdir);
    $tdir = str_replace("\\", "", $tdir);

    $dir = get_graph_template_dir() . "/" . $tdir;
    $thefile = $dir . "/" . $file;

    if (unlink($thefile) === TRUE) {

        // log it
        send_to_audit_log("User deleted graph template '" . $file . "'", AUDITLOGTYPE_CHANGE);

        // success!
        show_templates(false, _("Graph template deleted."));
    } else {
        // error
        show_templates(true, _("Graph template delete failed - directory permissions may be incorrect."));
    }
}

?>