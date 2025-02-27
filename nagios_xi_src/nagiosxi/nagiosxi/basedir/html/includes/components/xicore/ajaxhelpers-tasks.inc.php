<?php
//
// XI Core Ajax Helper Functions
// Copyright (c) 2008-2018 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../componenthelper.inc.php');


////////////////////////////////////////////////////////////////////////
// TASK AJAX FUNCTIONS
////////////////////////////////////////////////////////////////////////


/**
 * Get admin task list HTML
 *
 * @param   array   $args   Arguments
 * @return  string          HTML output
 */
function xicore_ajax_get_admin_tasks_html($args = null)
{
    $output = '';

    if (!is_admin()) {
        return _("You are not authorized to access this feature. Contact your system administrator for more information, or to obtain access to this feature.");
    }

    $output .= '<div class="infotable_title">' . _('Administrative Tasks') . '</div>';

    $neptune = 0;

    if (is_neptune()) { /* I dont like this but im going to leave it like this for performance since $neptune is used 9 times below */
        $neptune = 1;
    }
    
    $tableclass = 'infotable table table-condensed table-striped table-bordered';
    if ($neptune) {
        $tableclass = 'table dashlettable-in table-condensed';
    }
    $output .= '
    <table class="' . $tableclass . '">';
    if (!$neptune) {
        $output .= '
        <thead>
        <tr><th>' . _('Task') . '</th></tr>
        </thead>';
    }
    $output .= '
    <tbody>
    ';

    $base_url = get_base_url();
    $admin_base = $base_url . "admin/";
    $config_base = $base_url . "config/";

    // Check for problems
    $problemoutput = "";

    nagiosql_check_setuid_files($scripts_ok, $goodscripts, $badscripts);
    nagiosql_check_file_perms($config_ok, $goodfiles, $badfiles);
    if ($scripts_ok == false || $config_ok == false) {
        $problemoutput .= "<li><a href='" . $admin_base . "?xiwindow=configpermscheck.php' target='_top'><b>" . _('Fix permissions problems') . "</b></a><br>" . _('One or more configuration files or scripts has incorrect settings, which will cause configuration changes to fail.') . "</li>";
    }

    if (!empty($problemoutput)) {
        $output .= "<tr><td><span class='infotable_subtitle'><img src='" . theme_image("error_small.png") . "'> " . _('Problems Needing Attention:') . "</span></td></tr>";
        $output .= "<tr><td>";
        $output .= "<ul>";
        $output .= $problemoutput;
        $output .= "</ul>";
        $output .= "</td></tr>";
    }

    // Check for setup tasks that need to be done
    $setupoutput = "";

    $opt = get_option("system_settings_configured");
    if ($opt != 1) {
        if ($neptune) {
            $setupoutput .= "<a class='btn btn-admin' href='" . $admin_base . "?xiwindow=globalconfig.php' target='_top'><span class='material-symbols-outlined md-18 md-400' title=" . _('Configure system settings') . ">settings</span>" . _('Configure system settings') . "</a>";
            $setupoutput .= "<span class='dashlet-subtext'>" . _('Configure basic settings for your system.') . '</span>';
        }
        else {
            $setupoutput .= "<li><a href='" . $admin_base . "?xiwindow=globalconfig.php' target='_top'><b>" . _('Configure system settings') . "</b></a><br>" . _('Configure basic settings for your system.') . "</li>";
        }
    }

    $opt = get_option("mail_settings_configured");
    if ($opt != 1) {
        if ($neptune) {
            $setupoutput .= "<a class='btn btn-admin' href='" . $admin_base . "?xiwindow=mailsettings.php' target='_top'><span class='material-symbols-outlined md-18 md-400' title=" . _('Configure system settings') . ">mail</span>" . _('Configure mail settings') . "</a>";
            $setupoutput .= "<span class='dashlet-subtext'>" . _('Configure email settings for your  system') . '</span>';
        }
        else {
            $setupoutput .= "<li><a href='" . $admin_base . "?xiwindow=mailsettings.php' target='_top'><b>" . _('Configure mail settings') . "</b></a><br>" . _('Configure email settings for your  system') . "</li>";
        }
    }

    if (!empty($setupoutput)) {
        $output .= "<tr><td><span class='infotable_subtitle'>" . _('Initial Setup Tasks') . ":</span></td></tr>";
        $output .= "<tr><td>";
        $output .= $neptune ? "" : "<ul>";
        $output .= $setupoutput;
        $output .= $neptune ? "" : "</ul>";
        $output .= "</td></tr>";
    }

    // Check for important tasks that need to be done
    $alertoutput = "";

    // Check for custom branding and if they can do updates
    $hide_updates = false;
    if (custom_branding()) {
        global $bcfg;
        if ($bcfg['hide_updates']) {
            $hide_updates = true;
        }
    }

    if (!is_v2_license() && !$hide_updates) {
        $update_info = array("last_update_check_succeeded" => get_option("last_update_check_succeeded"),
                             "update_available" => get_option("update_available"));
        $updateurl = get_base_url() . "admin/?xiwindow=updates.php";
        if ($update_info["last_update_check_succeeded"] == 0) {
            $alertoutput .= "<span class='material-symbols-outlined md-16 md-unknown md-400 md-middle'>help</span><span>" . _('The last') . " </span><a href='" . $updateurl . "' target='_top'>" . _('update check failed') . "</a>";
        } else if ($update_info["update_available"] == 1) {
            $alertoutput .= "<span class='material-symbols-outlined md-16 md-critical md-400 md-middle md-padding'>error</span><span class='material-icon-va'>" . _('A new Nagios XI') . " <a href='" . $updateurl . "' target='_top'>" . _('update is available') . "</a></span>";
        }
    }

    if ($alertoutput != "") {
        $output .= "<tr><td><span class='infotable_subtitle'>" . _('Important Tasks') . ":</span></td></tr>";
        $output .= "<tr><td>";
        $output .= $neptune ? "" : "<ul style='list-style-type: none;'>";
        $output .= $alertoutput;
        $output .= $neptune ? "" : "</ul>";
        $output .= "</td></tr>";
    }

    $output .= "<tr><td><span class='infotable_subtitle'>" . _('Ongoing Tasks') . ":</span></td></tr>";
    $output .= "<tr><td>";
    if ($neptune) {
        $output.= "<span class='dashlet-subtext'>" . _('Add or modify items to be monitored') . '</span>';
        $output .= "<a class='btn btn-admin' href='" . $config_base . "' target='_top'><span class='material-symbols-outlined md-18 md-400' title=" . _('Configure your monitoring setup') . ">computer</span>" . _('Configure your monitoring setup') . "</a>";
        $output .= "<span class='dashlet-subtext'>" . sprintf(_('Setup new users with access to %s'), get_product_name()) . '</span>';
        $output .= "<a class='btn btn-admin' href='" . $admin_base . "?xiwindow=users.php' target='_top'><span class='material-symbols-outlined md-18 md-400' title=" . _('Add new user accounts') . ">person</span>" . _('Add new user accounts') . "</a>";
    }
    else {
        $output .= "<ul>";
        $output .= "<li><a href='" . $config_base . "' target='_top'><b>" . _('Configure your monitoring setup') . "</b></a><br>" . _('Add or modify items to be monitored') . "</li>";
        $output .= "<li><a href='" . $admin_base . "?xiwindow=users.php' target='_top'><b>" . _('Add new user accounts') . "</b></a><br>" . sprintf(_('Setup new users with access to %s'), get_product_name()) . "</li>";
        $output .= "</ul>";
    }
    $output .= "</td></tr>";

    $output .= '
    </tbody>
    </table>
    ';

    return $output;
}


/**
 * Get the getting started tasks HTML
 *
 * @param   array   $args   Arguments
 * @return  string          HTML output
 */
function xicore_ajax_get_getting_started_html($args = null)
{
    $output = '<div class="infotable_title">' . _('Getting Started Guide') . '</div>';

    $tableclass = "infotable table table-condensed table-striped table-bordered";
    if (is_neptune()) {
        $tableclass = "table dashlettable-in table-condensed";
    }
    $output .= '
    <table class="' . $tableclass . '">
    <tbody>';

    $base_url = get_base_url();
    $account_base = $base_url . "account/";
    $config_base = $base_url . "config/";

    $product_url = get_product_portal_backend_url();

    $output .= "<tr><td><span class='infotable_subtitle'>" . _('Common Tasks') . ":</span></td></tr>";
    $output .= "<tr><td>";

    if (is_neptune()) {
        $output .= "<span class='dashlet-subtext'>" . _('Change your account password and general preferences') . '</span>';
        $output .= "<a class='btn btn-admin' href='" . $account_base . "' target='_top'><span class='material-symbols-outlined md-18 md-400' title=" . _('Change you account settings') . ">person</span>" . _('Change your account settings') . "</a>";
        $output .= "<span class='dashlet-subtext'>" . _('Change how and when you receive alert notifications') . '</span>';
        $output .= "<a class='btn btn-admin' href='" . $account_base . "?xiwindow=notifyprefs.php' target='_top'><span class='material-symbols-outlined md-18 md-400' title=" . _('Change your notification settings') . ">notifications</span>" . _('Change your notifications settings') . "</a>";
        $output .= "<span class='dashlet-subtext'>" . _('Add or modify items to be monitored with easy-to-use wizards') . '</span>';
        $output .= "<a class='btn btn-admin' href='" . $config_base . "' target='_top'><span class='material-symbols-outlined md-18 md-400' title=" . _('Configure your monitoring setup') . ">computer</span>" . _('Configure your monitoring setup') . "</a>";
    }
    else {
        $output .= "<ul>";
        $output .= "<li><a href='" . $account_base . "' target='_top'>" . _('Change your account settings') . "</a><br>" . _('Change your account password and general preferences') . "</li>";
        $output .= "<li><a href='" . $account_base . "?xiwindow=notifyprefs.php' target='_top'>" . _('Change your notifications settings') . "</a><br>" . _('Change how and when you receive alert notifications') . "</li>";
        $output .= "<li><a href='" . $config_base . "' target='_top'>" . _('Configure your monitoring setup') . "</a><br>" . _('Add or modify items to be monitored with easy-to-use wizards') . "</li>";
        $output .= "</ul>";
    }
    $output .= "</td></tr>";

    if (!custom_branding()) {
        $output .= "<tr><td><span class='infotable_subtitle'>" . _('Getting Started') . ":</span></td></tr>";
        $output .= "<tr><td>";
        if (is_neptune()) {
            $output .= "<span class='dashlet-subtext'>" . _('Learn more about XI and its capabilities') . '</span>';
            $output .= "<a class='btn btn-admin' href='https://www.nagios.com/products/nagios-xi/?utm_source=XI+Getting+Started&utm_medium=XI+Product&utm_campaign=XI_CTA' target='_blank' rel='nofollow'><span class='material-symbols-outlined md-18 md-400' title=" . _('Learn about XI') . ">school</span>" . _('Learn about XI') . "</a>";
        } else {
            $output .= "<ul>";
            $output .= "<li><a href='https://www.nagios.com/products/nagios-xi/?utm_source=XI+Getting+Started&utm_medium=XI+Product&utm_campaign=XI_CTA' target='_blank' rel='nofollow'><b>" . _('Learn about XI') . "</b></a><br>" . _('Learn more about XI and its capabilities') . "</li>";
            $output .= "</ul>";
        }
        $output .= "</td></tr>";
    }

    $output .= '</tbody>
    </table>';

    return $output;
}
