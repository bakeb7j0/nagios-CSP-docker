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

route_to_neptune();

draw_page("main.php");

/**
 * @param $frameurl
 */
function draw_page($frameurl)
{

    do_page_start(array("page_title" => _("Tools")), false);
    ?>
    
    <div id="leftnav">
        <?php print_menu(MENU_TOOLS); ?>
    </div>

    <div id="maincontent">
        <iframe src="<?php echo get_window_frame_url($frameurl); ?>" width="100%" frameborder="0" id="maincontentframe" name="maincontentframe" allowfullscreen>
            [<?php echo _('Your user agent does not support frames or is currently configured not to display frames'); ?>.]
        </iframe>
    </div>

    <?php
    do_page_end(false);
}