<?php
//
// Copyright (c) 2008-2020 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/common.inc.php');


route_request();

function route_request()
{
    show_page();
}

/**
 * @param bool   $error
 * @param string $msg
 */
function show_page($error = false, $msg = "")
{

    // page start
    do_page_start(_("Missing Page"));
    ?>
    <p>
        <?php echo _("The page you requested seems to be missing."); ?>
    </p>
    <p>
        <?php echo _("The page that went missing was: "); ?>: <?php echo encode_form_val(grab_request_var("page")); ?>
    </p>
    <?php
    do_page_end();
}