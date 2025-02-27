<?php
//
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


route_request();


function route_request()
{
    global $request;

    if (isset($request['update']))
        do_update_tool();
    if (isset($request['delete']))
        do_delete_tool();
    else if (isset($request['edit']))
        show_edit_tool();
    else if (isset($request['go']))
        visit_tool();
    else
        route_to_neptune();
        show_tools();
}


/**
 * @param bool   $error
 * @param string $msg
 */
function show_tools($error = false, $msg = "")
{
    global $request;

    do_page_start(array("page_title" => _('My Tools')), true);

    if ((isset($request['update']) || isset($request['delete'])) && !$error) {
    ?>
    <script>
    $(document).ready(function() {
        var data = get_ajax_data("getmytoolsmenu", "");
        data = JSON.parse(data);
        $('#leftnav ul.menusection', window.parent.document)[0].innerHTML = data.html;
    });
    </script>
    <?php } ?>

    <h1><?php echo _('My Tools'); ?></h1>

    <p><?php echo _("Your personal tools that you have defined are available only to you.") ?></p>
    <p><a href="?edit=1" class="btn btn-sm btn-primary"><i class="fa fa-plus l"></i> <?php echo _("Add a Tool") ?></a> </p>

    <?php display_message($error, false, $msg); ?>

    <table class="table table-condensed table-striped table-auto-width">
        <thead>
            <tr>
                <th><?php echo _("Tool Name") ?></th>
                <th><?php echo _("URL") ?></th>
                <th><?php echo _("Actions") ?></th>
            </tr>
        </thead>
        <tbody>

        <?php
            $mr = get_mytools(0);
            foreach ($mr as $id => $r): ?>
                <tr>
                    <td><?php echo encode_form_val($r['name']); ?></td>
                    <td><a href="<?php echo urlencode(encode_form_val($r['url'])); ?>" target="_blank"><?php echo encode_form_val($r['url']); ?></a></td>
                    <td>
                        <a href="?edit=1&id=<?php echo $id; ?>&nsp=<?php echo get_nagios_session_protector_id(); ?>"><span title="<?php echo _('Edit'); ?>" class='material-symbols-outlined tt-bind md-action md-button md-400 md-20'>edit</span></a>
                        <a href="?delete=1&id=<?php echo $id; ?>&nsp=<?php echo get_nagios_session_protector_id(); ?>"><span title="<?php echo _('Delete'); ?>" class='material-symbols-outlined tt-bind md-action md-button md-400 md-20'>delete</span></a>
                        <a href="?go=1&id=<?php echo $id; ?>&nsp=<?php echo get_nagios_session_protector_id(); ?>"><span title="<?php echo _('View'); ?>" class='material-symbols-outlined tt-bind md-action md-button md-400 md-20'>slideshow</span></a>
                    </td>
                </tr>
            <?php endforeach;
            if (count($mr) == 0): ?>
                    <tr><td colspan='3'><?php echo _("You haven't defined any tools yet."); ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php
    do_page_end(true);
    exit();
}

function visit_tool()
{
    $id = grab_request_var("id", 0);
    $url = get_mytool_url(0, $id);

    if ($url == "") {
        show_tools(true, _("Invalid tool. Please select a tool from the list below."));
    }

    header("Location: " . $url);
}


/**
 * @param bool   $error
 * @param string $msg
 */
function show_edit_tool($error = false, $msg = "")
{

    $name = "New Tool";
    $url = "";

    // Grab variables
    $id = grab_request_var("id", -1);

    $add = false;
    if ($id == -1)
        $add = true;

    if ($add == true) {
        $pagetitle = _('Add Tool');
        $pageheader = _('Add Tool');
    } else {
        $pagetitle = _('Edit Tool');
        $pageheader = _('Edit Tool');

        // Load old values
        $mytool = get_mytool_id(0, $id);
        $name = grab_array_var($mytool, "name", $name);
        $url = grab_array_var($mytool, "url", $url);
    }

    // Get posted variables
    $name = grab_request_var("name", $name);
    $url = grab_request_var("url", $url);

    do_page_start(array("page_title" => $pagetitle), true);
    ?>
    <h1><?php echo $pageheader; ?></h1>

    <?php display_message($error, false, $msg); ?>

    <form id="manageOptionsForm" method="post" action="<?php echo encode_form_val($_SERVER['PHP_SELF']); ?>">
        <?php echo get_nagios_session_protector(); ?>
        <input type="hidden" name="update" value="1">
        <input type="hidden" name="id" value="<?php echo encode_form_val($id); ?>">

        <?php
        if ($add == true) {
            echo "<p>" . _("Use this form to define a new tool that you can quickly access from Nagios.") . "</p>";
        }
        ?>

        <table class="table table-condensed table-no-border table-auto-width">
            <tr>
                <td class="vt">
                    <label for="nameBox"><?php echo _("Tool Name"); ?>:</label>
                </td>
                <td>
                    <input type="text" size="30" name="name" id="nameBox" value="<?php echo encode_form_val($name); ?>" class="form-control">
                    <div class="subtext"><?php echo _("The name you want to use for this tool"); ?>.</div>
                </td>
            <tr>
            <tr>
                <td class="vt">
                    <label for="urlBox"><?php echo _("URL"); ?>:</label>
                </td>
                <td>
                    <input type="text" size="60" name="url" id="urlBox" value="<?php echo encode_form_val($url); ?>" class="form-control">
                    <div class="subtext"><?php echo _("The URL used to access this tool"); ?>.</div>
                </td>
            <tr>
        </table>

        <div id="formButtons">
            <button type="submit" class="btn btn-sm btn-primary" name="updateButton" id="updateButton"><?php echo _('Save'); ?></button>
            <button type="submit" class="btn btn-sm btn-default" name="cancelButton" id="cancelButton"><?php echo _('Cancel'); ?></button>
        </div>
    </form>
    <?php
    do_page_end(true);
    exit();
}


function do_delete_tool()
{
    check_nagios_session_protector();

    // Grab variables
    $id = grab_request_var("id", -1);

    $errmsg = array();
    $errors = 0;
    
    $readonly_user = get_user_meta(0, "readonly_user", 1);

    // Check for errors
    if (in_demo_mode() == true)
        $errmsg[$errors++] = _("Changes are disabled while in demo mode.");
    if ($id == -1)
        $errmsg[$errors++] = _("Invalid tool.");
    if ($readonly_user == 1)
        $errmsg[$errors++] = _("You are a read only user.");


    if ($errors > 0) {
        show_tools(true, $errmsg);
    }

    delete_mytool(0, $id);

    show_tools(false, _("Tool deleted."));
}


function do_update_tool()
{
    global $request;

    // Grab variables
    $id = grab_request_var("id", -1);
    $name = grab_request_var("name", _("New Tool"));
    $url = grab_request_var("url", "");


    $readonly_user = get_user_meta(0, "readonly_user", 1);

    // User pressed the cancel button
    if (isset($request["cancelButton"])) {
        header("Location: mytools.php");
        exit();
    }

    check_nagios_session_protector();

    $errmsg = array();
    $errors = 0;

    // Check for errors
    if (in_demo_mode() == true)
        $errmsg[$errors++] = _("Changes are disabled while in demo mode.");
    if (have_value($url) == false)
        $errmsg[$errors++] = _("Invalid tool URL.");
    if (have_value($name) == false)
        $errmsg[$errors++] = _("No tool name specified.");
    if ($readonly_user == 1)
        $errmsg[$errors++] = _("You are a read only user.");

    if ($errors > 0) {
        show_edit_tool(true, $errmsg);
    }

    update_mytool(0, $id, $name, $url);

    show_tools(false, _("Tool saved."));
}