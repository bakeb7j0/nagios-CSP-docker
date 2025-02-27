<?php
//
// Copyright (c) 2008-2020 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/includes/common.inc.php');


// Initialization stuff
pre_init();
init_session();

// Grab GET or POST variables and check pre-reqs
grab_request_vars();
check_prereqs();

?>
<html>
    <h1><?= _("Blocked by ModSecurity!") ?></h1>
    <p><?= _("This request has been blocked by the ModSecurity Web Application Firewall") ?></p>
<?php
if (is_authenticated()) {
?>
    <p><?= _("Your Options") ?></p>
    <ul>
        <li><?= _("Contact your administrator if you believe this was made in error.") ?></li>
<?php
    if(is_admin()) {
?>
        <li><?= _("Visit Admin -> System Config -> System Settings -> Security -> ModSecurity to disable.") ?></li>
        <li><?php printf(_("Toggle on or off with %s"), $cfg['script_dir']."/toggle_modsecurity.sh") ?></li>
<?php
    }
?>
    </ul>
<?php
}
?>
</html>