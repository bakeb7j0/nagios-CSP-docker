<?php
//
// Copyright (c) 2008-2020 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/../includes/common.inc.php');

// Initialization stuff
pre_init();
init_session();

// Grab GET or POST variables and check pre-reqs
grab_request_vars();
check_prereqs();
check_authentication();

// Only admins can access this page
if (is_admin() == false) {
    echo _("You are not authorized to access this feature. Contact your system administrator for more information, or to obtain access to this feature.");
    exit();
}


route_request();


function route_request()
{
    show_page();
    exit;
}


/**
 * @param bool   $error
 * @param string $msg
 */
function show_page($error = false, $msg = "")
{

    do_page_start(array("page_title" => _("Check Data Transfer")), true);

    if (!is_neptune()) {
    ?>

    <h1><?php echo _("Check Data Transfer"); ?></h1>

    <p><?php echo sprintf(_("Configure settings for transferring host and service check results to and from this %s server."), get_product_name()); ?></p>

    <?php
    display_message($error, false, $msg);
    ?>

    <br clear="all">
    <p>
        <a href="dtoutbound.php"><span class="material-symbols-outlined md-middle">outbound</span>
            <?php echo _("Manage Outbound Transfer Settings"); ?></a><br>
        <?php echo _("Configure outbound check transfer options.  Useful for distributed monitoring and redundant/failover setups."); ?>
    </p>

    <br clear="all">
    <p>
        <a href="dtinbound.php"><span class="material-symbols-outlined inbound-icon md-middle">outbound</span>
            <?php echo _("Manage Inbound Transfer Settings"); ?></a><br>
        <?php echo _("Configure inbound check reception options.  Useful for receiving passive checks from external hosts, applications, and third-party addons."); ?>
    </p>

    <?php } else { ?>
    
    <h1><?php echo _("Check Data Transfer"); ?></h1>

    <p class="neptune-subtext"><?php echo sprintf(_("Configure settings for transferring host and service check results to and from this %s server."), get_product_name()); ?></p>

    <?php
    display_message($error, false, $msg);
    ?>

    <?php echo neptune_section_spacer(); ?>

    <br clear="all">
    <p class="neptune-subtext">
        <a href="dtoutbound.php" class="neptune-link">
            <h4><span class="material-symbols-outlined">outbound</span>
            <?php echo _("Manage Outbound Transfer Settings"); ?></h4>
        </a>
        <p class="neptune-subtext">
            &nbsp;&nbsp;<?php echo _("Configure outbound check transfer options.  Useful for distributed monitoring and redundant/failover setups."); ?>
        </p>
    </p>

    <?php echo neptune_section_spacer(); ?>

    <br clear="all">
    <p class="neptune-subtext">
        <a href="dtinbound.php" class="neptune-link">
            <h4><span class="material-symbols-outlined inbound-icon">outbound</span>
            <?php echo _("Manage Inbound Transfer Settings"); ?></h4>
        </a>
        <p class="neptune-subtext">
            &nbsp;&nbsp;<?php echo _("Configure inbound check reception options.  Useful for receiving passive checks from external hosts, applications, and third-party addons."); ?>
        </p>
    </p>

    <?php }

    do_page_end(true);
    exit();
}

?>