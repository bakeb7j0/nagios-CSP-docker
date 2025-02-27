<?php
//
// Copyright (c) 2016-2020 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/../includes/common.inc.php');

// Initialization stuff
pre_init();
init_session();

// Grab GET or POST variables and check pre-reqs
grab_request_vars();
check_prereqs();
check_authentication();

route_request();

function route_request()
{
    $pageopt = grab_request_var("pageopt", "info");

    switch ($pageopt) {
        default:
            show_help_page();
            break;
    }
}

function show_help_page()
{
    $product_url = get_product_portal_backend_url();

    do_page_start(array("page_title" => _('Help for Nagios XI')), true);
?>

    <h1><?php echo _('Help for Nagios XI'); ?></h1>

    <div style="width: 500px; float: left;">
        <h5 class="ul"><?php echo _('Get Help Online'); ?></h5>
        <p class="neptune-subtext"><?php echo _("Get help for Nagios XI online."); ?></p>
        <ul class="neptune-form-subtext">
            <li>
                <a href='https://support.nagios.com/nagios-xi-resources/#faq' target="_blank" rel="noreferrer nofollow"><b><?php echo _("Frequently Asked Questions"); ?></b></a>
            </li>
            <li><a href='https://library.nagios.com/' target="_blank" rel="noreferrer nofollow"><b><?php echo _("Visit the Nagios Library"); ?></b></a></li>
            <li><a href='https://support.nagios.com/forum' target="_blank" rel="noreferrer nofollow"><b><?php echo _("Visit the Support Forum"); ?></b></a></li>
        </ul>
        <h5 class="ul"><?php echo _('More Options'); ?></h5>
        <ul class="neptune-form-subtext">
            <li>
                <a href="https://www.nagios.com/products/nagios-xi/?utm_source=XI+Getting+Started&utm_medium=XI+Product&utm_campaign=XI_CTA" target="_blank" rel="noreferrer nofollow"><strong><?php echo _("Learn about XI"); ?></strong></a>
                <br><?php echo _("Learn more about XI and its capabilities."); ?>
            </li>
        </ul>
    </div>

    <div style="width: 500px; float: left; margin-left: 20px;">
        <?php display_dashlet("xicore_getting_started", "", null, DASHLET_MODE_OUTBOARD); ?>
    </div>

<?php
    do_page_end(true);
}