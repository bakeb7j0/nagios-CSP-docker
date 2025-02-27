<?php
//
// XI Core Ajax Helper Functions
// Copyright (c) 2008-2018 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../componenthelper.inc.php');


////////////////////////////////////////////////////////////////////////
// TAC AJAX FUNCTIONS
////////////////////////////////////////////////////////////////////////


/**
 * @param   array   $args
 * @return  string
 */
function xicore_ajax_get_network_outages_summary_html($args = null)
{
    $mode = grab_array_var($args, "mode");
    $admin = is_admin();

    $output = '';

    $url = "outages-xml.cgi";
    $cgioutput = coreui_get_raw_cgi_output($url, array());
    $xml = simplexml_load_string($cgioutput);

    $neptune_icon = "";
    $tableclass = 'table table-condensed table-striped table-bordered table-no-margin';
    if (is_neptune()) {
        $tableclass = 'table-condensed table-no-margin';
        $neptune_icon = '<span class="material-symbols-outlined md-16 md-pending md-400 md-middle md-padding tt-bind" title=' . _('Network Outages') . '>wifi_tethering_off</span>';
    }

    if (!$xml) {
        $output .= '
        <table class="' . $tableclass . '">
        <thead>
        <tr><th>' . $neptune_icon . _('Network Outages') . '</th></tr>
        </thead>
        <tbody>
        ';
        $text = "";
        $text .= _("Monitoring engine may be stopped.");
        if ($admin == true)
            $text .= "<br><a href='" . get_base_url() . "admin/sysstat.php'>" . _('Check engine') . "</a>";
        $output .= "<tr><td class='tacoutageImportantProblem'><b>" . _('Error: Unable to parse XML output!') . "</b><br>" . $text . "</td></tr>";
        $output .= '
        </tbody>
        </table>
        ';
    } else {
        $output .= '
        <table class="' . $tableclass . '">
        <thead>
        <tr><th>' . $neptune_icon . _('Network Outages') . '</th></tr>
        </thead>
        <tbody>
        ';

        $total = 0;
        foreach ($xml->hostoutage as $ho) {
            $total++;
        }

        $url = get_base_url() . "includes/components/xicore/status.php?show=outages";

        $output .= '<tr class="tacSubHeader"><td><a href="' . $url . '">' . $total . ' ' . _('Outages') . '</a></td></tr>';

        if ($total == 0)
            $output .= '<tr class="tacSubHeader-network no-outages"><td>' . _('No Blocking Outages') . '</td></tr>';
        else {
            $output .= '<tr class="tacSubHeader-network outages"><td><div class="tacoutageImportantProblem"><a href="' . $url . '">' . $total . ' ' . _('Blocking Outages') . '</a></div></td></tr>';
        }

        $output .= '
        </tbody>
        </table>
        ';
    }

    if ($mode == DASHLET_MODE_INBOARD) {
        $output .= '
        <div class="ajax_date">' . _('Last Updated') . ': ' . get_datetime_string(time()) . '</div>
        ';
    }

    return $output;
}


/**
 * @param   array   $args
 * @return  string
 */
function xicore_ajax_get_network_health_html($args = null)
{
    $ignore_soft_states = grab_array_var($args, "ignore_soft_states");
    $host_warning_threshold = grab_array_var($args, "host_warning_threshold");
    $host_critical_threshold = grab_array_var($args, "host_critical_threshold");
    $service_warning_threshold = grab_array_var($args, "service_warning_threshold");
    $service_critical_threshold = grab_array_var($args, "service_critical_threshold");
    $theme = get_theme();

    $output = '';

    // Get host status
    $total_hosts = 0;

    // Get total number of hosts
    $backendargs = array();
    $backendargs["cmd"] = "gethoststatus";
    $backendargs["totals"] = 1;
    $xml = get_xml_host_status($backendargs);

    if ($xml) {
        $total_hosts = intval($xml->recordcount);
    }

    // Get the total number of problems
    $backendargs["current_state"] = "in:1,2";
    if ($ignore_soft_states == 1) {
        $backendargs["state_type"] = 1;
    }
    $xml = get_xml_host_status($backendargs);

    if ($xml) {
        $total_notok = intval($xml->recordcount);
    }

    $hosts_ok = $total_hosts - $total_notok;

    if ($total_hosts == 0) {
        $health_percent = 0;
    } else {
        $health_percent = ($hosts_ok / $total_hosts) * 100;
    }

    $val = intval($health_percent);
    $val_bg = $val;
    $tableclass = 'table table-condensed table-striped table-bordered table-no-margin';
    $statbar_class = "statbar";
    $unit = "px";
    $neptune_icon = "";
    if (is_neptune()) {
        $statbar_class = "statbar-neptune";
        $val_bg = $health_percent;
        $tableclass = 'table-condensed table-no-margin';
        $unit = "%";
        $neptune_icon = '<span class="material-symbols-outlined md-16 md-pending md-400 md-middle md-padding tt-bind" title=' . _('Network Health') . '>cardiology</span>';
    }

    // Begin html build
    $output .= '
    <table class="' . $tableclass . '" style="margin: 0 0 5px 0;">
        <thead>
            <tr><th colspan="2">' . $neptune_icon . _('Network Health') . '</th></tr>
        </thead>
    <tbody>';

    $url = get_base_url() . "includes/components/xicore/status.php?show=hosts";
    if (is_neptune()) {
        $content_in = '';
        $content_out = "<a class='hc-text' href='" . $url . "'>&nbsp" . $val . "%</a>";
    }
    else {
        $content_in = "<a class='hc-text' href='" . $url . "'>" . $val . "%</a>";
        $content_out = '';
    }
    
    $spanval = '';
    $okay = COMMONCOLOR_GREEN;
    $warning = COMMONCOLOR_YELLOW;
    $critical = COMMONCOLOR_RED;
    if ($theme == 'colorblind'){
        $okay = COLORBLIND_OKAY;
        $warning = COLORBLIND_WARNING;
        $critical = COLORBLIND_CRITICAL;
    }else if (is_neptune()){
        $okay = NEPTUNE_GREEN;
        $warning = NEPTUNE_YELLOW;
        $critical = NEPTUNE_RED;
    }
    if ($health_percent < $host_critical_threshold) {
        $spanval = "<span><span class='statbar-background'><div class='statbar-neptune network-health' style='height: 100%; width: " . $val_bg . $unit . "; background-color:  " . $critical . ";'>" . $content_in . "</div></span>" . $content_out . "</span>";
    } else if ($health_percent < $host_warning_threshold) {
        $spanval = "<span><span class='statbar-background'><div class='statbar-neptune network-health' style='height: 100%; width: " . $val_bg . $unit . "; background-color:  " . $warning . ";'>" . $content_in . "</div></span>" . $content_out . "</span>";
    } else {
        $spanval = "<span><span class='statbar-background'><div class='statbar-neptune network-health' style='height: 100%; width: " . $val_bg . $unit . "; background-color:  " . $okay . ";'>" . $content_in . "</div></span>" . $content_out . "</span>";
    }

    $output .= '<tr><td>' . _('Host Health') . '</td><td width="100px"><span class="' . $statbar_class . '">' . $spanval . '</span></td></tr>';

    // Get total number of hosts
    $backendargs = array();
    $backendargs["cmd"] = "getservicestatus";
    $backendargs["totals"] = 1;
    $xml = get_xml_service_status($backendargs);

    if ($xml) {
        $total_services = intval($xml->recordcount);
    }

    // Get the total number of problems
    $backendargs["current_state"] = "in:1,2,3";
    if ($ignore_soft_states == 1) {
        $backendargs["state_type"] = 1;
    }
    $xml = get_xml_service_status($backendargs);

    if ($xml) {
        $total_notok = intval($xml->recordcount);
    }

    $services_ok = $total_services - $total_notok;

    // Calculate percentage
    if ($total_services == 0) {
        $health_percent = 0;
    } else {
        $health_percent = ($services_ok / $total_services) * 100;
    }

    $val = intval($health_percent);
    $val_bg = $val;
    if (is_neptune()) {
        $val_bg = $health_percent;
    }

    $url = get_base_url() . "includes/components/xicore/status.php?show=services";
    
    if (is_neptune()) {
        $content_in = '';
        $content_out = "<a class='hc-text' href='" . $url . "'>&nbsp" . $val . "%</a>";
    }
    else {
        $content_in = "<a class='hc-text' href='" . $url . "'>" . $val . "%</a>";
        $content_out = '';
    }

    if ($health_percent < $service_critical_threshold) {
        $spanval = "<span><span class='statbar-background'><div class='statbar-neptune network-health' style='height: 100%; width: " . $val_bg . $unit . "; background-color:  " . $critical . ";'>" . $content_in . "</div></span>" . $content_out . "</span>";
    } else if ($health_percent < $service_warning_threshold) {
        $spanval = "<span><span class='statbar-background'><div class='statbar-neptune network-health' style='height: 100%; width: " . $val_bg . $unit . "; background-color:  " . $warning . ";'>" . $content_in . "</div></span>" . $content_out . "</span>";
    } else {
        $spanval = "<span><span class='statbar-background'><div class='statbar-neptune network-health' style='height: 100%; width: " . $val_bg . $unit . "; background-color:  " . $okay . ";'>" . $content_in . "</div></span>" . $content_out . "</span>";
    }

    $output .= '<tr><td>' . _('Service Health') . '</td><td width="100px"><span class="' . $statbar_class . '">' . $spanval . '</span></td></tr>';

    $output .= '
    </tbody>
    </table>';

    $output .= '<div class="ajax_date">' . _('Last Updated') . ': ' . get_datetime_string(time()) . '</div>';

    return $output;
}


/**
 * @param   array   $args
 * @return  string
 */
function xicore_ajax_get_host_status_tac_summary_html($args = null)
{
    $mode = grab_array_var($args, "mode");
    $base_url = get_base_url() . "includes/components/xicore/status.php?show=hosts";

    $info = get_host_status_tac_summary_data(false, array(), true);

    $trclass = 'tacSubHeader';
    $neptune_icon_hosts = $neptune_icon_down = $neptune_icon_unreachable = $neptune_icon_ok = $neptune_icon_pending = $neptune_hostdown = $neptune_hostup = $neptune_hostwarning = $neptune_hostunknown = $neptune_hostpending = "";
    $tableclass = 'table table-condensed table-striped table-bordered table-no-margin';
    if (is_neptune()) {
        $tableclass = 'table-condensed table-no-margin tactable-neptune';
        $trclass = 'tacSubHeader-neptune';
        $neptune_icon_hosts = '<span class="material-symbols-outlined md-16 md-pending md-400 md-middle md-padding tt-bind" title=' . _('Hosts') . '>gite</span>';
        $neptune_icon_down = '<span class="material-symbols-outlined md-16 md-critical md-400 md-middle md-padding tt-bind" title=' . _('Down') . '>arrow_downward</span>';
        $neptune_icon_unreachable = '<span class="material-symbols-outlined md-16 md-unknown md-400 md-middle md-padding tt-bind" title=' . _('Unreachable') . '>unknown_document</span>'; 
        $neptune_icon_up = '<span class="material-symbols-outlined md-16 md-ok md-400 md-middle md-padding tt-bind" title=' . _('Up') . '>arrow_upward</span>';
        $neptune_icon_pending = '<span class="material-symbols-outlined md-16 md-pending md-400 md-middle md-padding tt-bind" title=' . _('Pending') . '>pending</span>';
        $neptune_hostdown = '<span class="status-dot hostdown dot-10"></span>';
        $neptune_hostup = '<span class="status-dot hostup dot-10"></span>';
        $neptune_hostwarning = '<span class="status-dot hostwarning dot-10"></span>';
        $neptune_hostunknown = '<span class="status-dot hostunknown dot-10"></span>';
        $neptune_hostpending = '<span class="status-dot hostpending dot-10"></span>';
    }

    $output = '
    <table class="' . $tableclass . '">
    <thead>
    <tr><th colspan="4">' . $neptune_icon_hosts . _("Hosts") . '</th></tr>
    </thead>
    <tbody>';

    $output .= '<tr class="' . $trclass . '">';
    $output .= '<td width="135">' . $neptune_icon_down . '<a class="hc-text" href="' . $base_url . '&hoststatustypes=' . HOSTSTATE_DOWN . '">' . $info["down"]["total"] . ' ' . _('Down') . '</a></td>';
    $output .= '<td width="135">' . $neptune_icon_unreachable . '<a class="hc-text" href="' . $base_url . '&hoststatustypes=' . HOSTSTATE_UNREACHABLE . '">' . $info["unreachable"]["total"] . ' ' . _('Unreachable') . '</a></td>';
    $output .= '<td width="135">' . $neptune_icon_up . '<a class="hc-text" href="' . $base_url . '&hoststatustypes=' . HOSTSTATE_UP . '">' . $info["up"]["total"] . ' ' . _('Up') . '</a></td>';
    $output .= '<td width="135">' . $neptune_icon_pending . '<a class="hc-text" href="' . $base_url . '&hoststatustypes=' . HOSTSTATE_PENDING . '">' . $info["pending"]["total"] . ' ' . _('Pending') . '</a></td>';
    $output .= '</tr>';

    $output .= '<tr>';

    // Down
    $output .= '<td><div class="neptune-center-content">';
    if ($info["down"]["unhandled"]) {
        $output .= '<div class="tachostImportantProblem">' . $neptune_hostdown . '<a class="hc-text" href="' . $base_url . '&hoststatustypes=' . HOSTSTATE_DOWN . '&hostattr=' . (HOSTSTATUSATTR_NOTINDOWNTIME | HOSTSTATUSATTR_NOTACKNOWLEDGED) . '">' . $info["down"]["unhandled"] . ' ' . _('Unhandled Problems') . '</a></div>';
    }
    if ($info["down"]["acknowledged"]) {
        $output .= '<div class="tachostProblem">' . $neptune_hostdown . '<a class="hc-text" href="' . $base_url . '&hoststatustypes=' . HOSTSTATE_DOWN . '&hostattr=4">' . $info["down"]["acknowledged"] . '' . _(' Acknowledged') . '</a></div>';
    }
    if ($info["down"]["scheduled"]) {
        $output .= '<div class="tachostProblem">' . $neptune_hostdown . '<a class="hc-text" href="' . $base_url . '&hoststatustypes=' . HOSTSTATE_DOWN . '&hostattr=1">' . $info["down"]["scheduled"] . ' ' . _('Scheduled') . '</a></div>';
    }
    if ($info["down"]["active"]) {
        $output .= '<div class="tachostProblem">' . $neptune_hostdown . '<a class="hc-text" href="' . $base_url . '&hoststatustypes=' . HOSTSTATE_DOWN . '&hostattr=' . (HOSTSTATUSATTR_CHECKSENABLED) . '">' . $info["down"]["active"] . ' ' . _('Active') . '</a></div>';
    }
    if ($info["down"]["disabled"]) {
        $output .= '<div class="tachostProblem">' . $neptune_hostdown . '<a class="hc-text" href="' . $base_url . '&hoststatustypes=' . HOSTSTATE_DOWN . '&hostattr=' . (HOSTSTATUSATTR_CHECKSDISABLED | HOSTSTATUSATTR_PASSIVECHECKSENABLED) . '">' . $info["down"]["disabled"] . ' ' . _('Passive') . '</a></div>';
    }
    if ($info["down"]["soft"]) {
        $output .= '<div class="tachostProblem">' . $neptune_hostdown . '<a class="hc-text" href="' . $base_url . '&hoststatustypes=' . HOSTSTATE_DOWN . '&hostattr=524288">' . $info["down"]["soft"] . ' ' . _('Soft Problems') . '</a></div>';
    }
    $output .= '</div></td>';

    // Unreachable
    $output .= '<td><div class="neptune-center-content">';
    if ($info["unreachable"]["unhandled"]) {
        $output .= '<div class="tachostImportantProblem">' . $neptune_hostunknown . '<a class="hc-text" class="hc-text" href="' . $base_url . '&hoststatustypes=' . HOSTSTATE_UNREACHABLE . '&hostattr=' . (HOSTSTATUSATTR_NOTINDOWNTIME | HOSTSTATUSATTR_NOTACKNOWLEDGED) . '">' . $info["unreachable"]["unhandled"] . ' ' . _('Unhandled Problems') . '</a></div>';
    }
    if ($info["unreachable"]["acknowledged"]) {
        $output .= '<div class="tachostProblem">' . $neptune_hostunknown . '<a class="hc-text" href="' . $base_url . '&hoststatustypes=' . HOSTSTATE_UNREACHABLE . '&hostattr=4">' . $info["unreachable"]["acknowledged"] . ' ' . _('Acknowledged') . '</a></div>';
    }
    if ($info["unreachable"]["scheduled"]) {
        $output .= '<div class="tachostProblem">' . $neptune_hostunknown . '<a class="hc-text" href="' . $base_url . '&hoststatustypes=' . HOSTSTATE_UNREACHABLE . '&hostattr=1">' . $info["unreachable"]["scheduled"] . ' ' . _('Scheduled') . '</a></div>';
    }
    if ($info["unreachable"]["active"]) {
        $output .= '<div class="tachostProblem">' . $neptune_hostunknown . '<a class="hc-text" href="' . $base_url . '&hoststatustypes=' . HOSTSTATE_UNREACHABLE . '&hostattr=' . (HOSTSTATUSATTR_CHECKSENABLED) . '">' . $info["unreachable"]["active"] . ' ' . _('Active') . '</a></div>';
    }
    if ($info["unreachable"]["disabled"]) {
        $output .= '<div class="tachostProblem">' . $neptune_hostunknown . '<a class="hc-text" href="' . $base_url . '&hoststatustypes=' . HOSTSTATE_UNREACHABLE . '&hostattr=' . (HOSTSTATUSATTR_CHECKSDISABLED | HOSTSTATUSATTR_PASSIVECHECKSENABLED) . '">' . $info["unreachable"]["disabled"] . ' ' . _('Passive') . '</a></div>';
    }
    if ($info["unreachable"]["soft"]) {
        $output .= '<div class="tachostProblem">' . $neptune_hostunknown . '<a class="hc-text" href="' . $base_url . '&hoststatustypes=' . HOSTSTATE_UNREACHABLE . '&hostattr=524288">' . $info["unreachable"]["soft"] . ' ' . _('Soft Problems') . '</a></div>';
    }
    $output .= '</div></td>';

    // Up
    $output .= '<td><div class="neptune-center-content">';
    if ($info["up"]["scheduled"]) {
        $output .= '<div class="tachostNoProblem">' . $neptune_hostup . '<a class="hc-text" href="' . $base_url . '&hoststatustypes=' . HOSTSTATE_UP . '&hostattr=1">' . $info["up"]["scheduled"] . ' ' . _('Scheduled') . '</a></div>';
    }
    if ($info["up"]["active"]) {
        $output .= '<div class="tachostNoProblem">' . $neptune_hostup . '<a class="hc-text" href="' . $base_url . '&hoststatustypes=' . HOSTSTATE_UP . '&hostattr=' . (HOSTSTATUSATTR_CHECKSENABLED) . '">' . $info["up"]["active"] . ' ' . _('Active') . '</a></div>';
    }
    if ($info["up"]["disabled"]) {
        $output .= '<div class="tachostNoProblem">' . $neptune_hostup . '</span><a class="hc-text" href="' . $base_url . '&hoststatustypes=' . HOSTSTATE_UP . '&hostattr=' . (HOSTSTATUSATTR_CHECKSDISABLED | HOSTSTATUSATTR_PASSIVECHECKSENABLED) . '">' . $info["up"]["disabled"] . ' ' . _('Passive') . '</a></div>';
    }
    $output .= '</div></td>';

    // Pending
    $output .= '<td><div class="neptune-center-content">';
    if ($info["pending"]["scheduled"]) {
        $output .= '<div class="tachostNoProblem">' . $neptune_hostpending . '<a class="hc-text" href="' . $base_url . '&hoststatustypes=' . HOSTSTATE_PENDING . '&hostattr=1">' . $info["pending"]["scheduled"] . ' ' . _('Scheduled') . '</a></div>';
    }
    if ($info["pending"]["active"]) {
        $output .= '<div class="tachostNoProblem">' . $neptune_hostpending . '<a class="hc-text" href="' . $base_url . '&hoststatustypes=' . HOSTSTATE_PENDING . '&hostattr=' . (HOSTSTATUSATTR_CHECKSENABLED) . '">' . $info["pending"]["active"] . ' ' . _('Active') . '</a></div>';
    }
    if ($info["pending"]["disabled"]) {
        $output .= '<div class="tachostNoProblem">' . $neptune_hostpending . '<a class="hc-text" href="' . $base_url . '&hoststatustypes=' . HOSTSTATE_PENDING . '&hostattr=' . (HOSTSTATUSATTR_CHECKSDISABLED | HOSTSTATUSATTR_PASSIVECHECKSENABLED) . '">' . $info["pending"]["disabled"] . ' ' . _('Passive') . '</a></div>';
    }
    $output .= '</div></td>';

    $output .= '</tr>';

    $output .= '
    </tbody>
    </table>';

    if ($mode == DASHLET_MODE_INBOARD) {
        $output .= '
        <div class="ajax_date">' . _('Last Updated') . ': ' . get_datetime_string(time()) . '</div>
        ';
    }

    return $output;
}


/**
 * @param   array   $args
 * @return  string
 */
function xicore_ajax_get_service_status_tac_summary_html($args = null)
{
    $mode = grab_array_var($args, "mode");
    $base_url = get_base_url() . "includes/components/xicore/status.php?show=services";

    $info = get_service_status_tac_summary_data(false, array(), true);

    $tableclass = 'table table-condensed table-striped table-bordered table-no-margin';
    $trclass = 'tacSubHeader';
    $neptune_icon_services = $neptune_icon_critical = $neptune_icon_warning = $neptune_icon_unknown = $neptune_icon_ok = $neptune_icon_pending = $neptune_hostdown = $neptune_hostup = $neptune_hostwarning = $neptune_hostunknown = $neptune_hostpending = "";
    if (is_neptune()) {
        $tableclass = 'table-condensed table-no-margin tactable-neptune';
        $trclass = 'tacSubHeader-neptune';
        $neptune_icon_services = '<span class="material-symbols-outlined md-16 md-pending md-400 md-middle md-padding tt-bind" title=' . _('Services') . '>gite</span>';
        $neptune_icon_critical = '<span class="material-symbols-outlined md-16 md-critical md-400 md-middle md-padding tt-bind" title=' . _('Critical') . '>priority_high</span>';
        $neptune_icon_warning = '<span class="material-symbols-outlined md-16 md-warning md-400 md-middle md-padding tt-bind" title=' . _('Warning') . '>warning</span>';
        $neptune_icon_unknown = '<span class="material-symbols-outlined md-16 md-unknown md-400 md-middle md-padding tt-bind" title=' . _('Unknown') . '>question_mark</span>';
        $neptune_icon_ok = '<span class="material-symbols-outlined md-16 md-ok md-400 md-middle md-padding tt-bind" title=' . _('Ok') . '>check_circle</span>';
        $neptune_icon_pending = '<span class="material-symbols-outlined md-16 md-pending md-400 md-middle md-padding tt-bind" title=' . _('Pending') . '>pending</span>';
        $neptune_hostdown = '<span class="status-dot hostdown dot-10"></span>';
        $neptune_hostup = '<span class="status-dot hostup dot-10"></span>';
        $neptune_hostwarning = '<span class="status-dot hostwarning dot-10"></span>';
        $neptune_hostunknown = '<span class="status-dot hostunknown dot-10"></span>';
        $neptune_hostpending = '<span class="status-dot hostpending dot-10"></span>';
    }

    $output = '
    <table class="' . $tableclass . '">
    <thead>
    <tr><th colspan="5">' . $neptune_icon_services . _('Services') . '</th></tr>
    </thead>
    <tbody>';

    $output .= '<tr class="' . $trclass . '">';
    $output .= '<td width="135">' . $neptune_icon_critical . '<a href="' . $base_url . '&servicestatustypes=' . SERVICESTATE_CRITICAL . '">' . $info["critical"]["total"] . ' ' . _('Critical') . '</a></td>';
    $output .= '<td width="135">' . $neptune_icon_warning . '<a href="' . $base_url . '&servicestatustypes=' . SERVICESTATE_WARNING . '">' . $info["warning"]["total"] . ' ' . _('Warning') . '</a></td>';
    $output .= '<td width="135">' . $neptune_icon_unknown . '<a href="' . $base_url . '&servicestatustypes=' . SERVICESTATE_UNKNOWN . '">' . $info["unknown"]["total"] . ' ' . _('Unknown') . '</a></td>';
    $output .= '<td width="135">' . $neptune_icon_ok . '<a href="' . $base_url . '&servicestatustypes=' . SERVICESTATE_OK . '">' . $info["ok"]["total"] . ' ' . _('Ok') . '</a></td>';
    $output .= '<td width="135">' . $neptune_icon_pending . '<a href="' . $base_url . '&servicestatustypes=' . SERVICESTATE_PENDING . '">' . $info["pending"]["total"] . ' ' . _('Pending') . '</a></td>';
    $output .= '</tr>';

    $output .= '<tr>';

    // Critical
    $output .= '<td><div class="neptune-center-content">';
    if ($info["critical"]["unhandled"]) {
        $output .= '<div class="tacserviceImportantProblem">' . $neptune_hostdown . '<a class="hc-text" href="' . $base_url . '&servicestatustypes=' . SERVICESTATE_CRITICAL . '&serviceattr=' . (SERVICESTATUSATTR_NOTINDOWNTIME | SERVICESTATUSATTR_NOTACKNOWLEDGED) . '&hoststatustypes=3">' . $info["critical"]["unhandled"] . ' ' . _('Unhandled Problems') . '</a></div>';
    }
    if ($info["critical"]["hostproblem"]) {
        $output .= '<div class="tacserviceProblem">' . $neptune_hostdown . '<a class="hc-text" href="' . $base_url . '&servicestatustypes=' . SERVICESTATE_CRITICAL . '&hoststatustypes=12">' . $info["critical"]["hostproblem"] . ' ' . _('On Problem Hosts') . '</a></div>';
    }
    if ($info["critical"]["acknowledged"]) {
        $output .= '<div class="tacserviceProblem">' . $neptune_hostdown . '<a class="hc-text" href="' . $base_url . '&servicestatustypes=' . SERVICESTATE_CRITICAL . '&serviceattr=4">' . $info["critical"]["acknowledged"] . ' ' . _('Acknowledged') . '</a></div>';
    }
    if ($info["critical"]["scheduled"]) {
        $output .= '<div class="tacserviceProblem">' . $neptune_hostdown . '<a class="hc-text" href="' . $base_url . '&servicestatustypes=' . SERVICESTATE_CRITICAL . '&serviceattr=1">' . $info["critical"]["scheduled"] . ' ' . _('Scheduled') . '</a></div>';
    }
    if ($info["critical"]["active"]) {
        $output .= '<div class="tacserviceProblem">' . $neptune_hostdown . '<a class="hc-text" href="' . $base_url . '&servicestatustypes=' . SERVICESTATE_CRITICAL . '&serviceattr=' . (SERVICESTATUSATTR_CHECKSENABLED) . '">' . $info["critical"]["active"] . ' ' . _('Active') . '</a></div>';
    }
    if ($info["critical"]["disabled"]) {
        $output .= '<div class="tacserviceProblem">' . $neptune_hostdown . '<a class="hc-text" href="' . $base_url . '&servicestatustypes=' . SERVICESTATE_CRITICAL . '&serviceattr=' . (SERVICESTATUSATTR_CHECKSDISABLED | SERVICESTATUSATTR_PASSIVECHECKSENABLED) . '">' . $info["critical"]["disabled"] . ' ' . _('Passive') . '</a></div>';
    }
    if ($info["critical"]["soft"]) {
        $output .= '<div class="tacserviceProblem">' . $neptune_hostdown . '<a class="hc-text" href="' . $base_url . '&servicestatustypes=' . SERVICESTATE_CRITICAL . '&serviceattr=524288">' . $info["critical"]["soft"] . ' ' . _('Soft Problems') . '</a></div>';
    }
    $output .= '</div></td>';

    // Warning
    $output .= '<td><div class="neptune-center-content">';
    if ($info["warning"]["unhandled"]) {
        $output .= '<div class="tacserviceImportantWarning">' . $neptune_hostwarning . '<a class="hc-text" href="' . $base_url . '&servicestatustypes=' . SERVICESTATE_WARNING . '&serviceattr=' . (SERVICESTATUSATTR_NOTINDOWNTIME | SERVICESTATUSATTR_NOTACKNOWLEDGED) . '&hoststatustypes=3">' . $info["warning"]["unhandled"] . ' ' . _('Unhandled Problems') . '</a></div>';
    }
    if ($info["warning"]["hostproblem"]) {
        $output .= '<div class="tacserviceProblemWarning">' . $neptune_hostwarning . '<a class="hc-text" href="' . $base_url . '&servicestatustypes=' . SERVICESTATE_WARNING . '&hoststatustypes=12">' . $info["warning"]["hostproblem"] . ' ' . _('On Problem Hosts') . '</a></div>';
    }
    if ($info["warning"]["acknowledged"]) {
        $output .= '<div class="tacserviceProblemWarning">' . $neptune_hostwarning . '<a class="hc-text" href="' . $base_url . '&servicestatustypes=' . SERVICESTATE_WARNING . '&serviceattr=4">' . $info["warning"]["acknowledged"] . ' ' . _('Acknowledged') . '</a></div>';
    }
    if ($info["warning"]["scheduled"]) {
        $output .= '<div class="tacserviceProblemWarning">' . $neptune_hostwarning . '<a class="hc-text" href="' . $base_url . '&servicestatustypes=' . SERVICESTATE_WARNING . '&serviceattr=1">' . $info["warning"]["scheduled"] . ' ' . _('Scheduled') . '</a></div>';
    }
    if ($info["warning"]["active"]) {
        $output .= '<div class="tacserviceProblemWarning">' . $neptune_hostwarning . '<a class="hc-text" href="' . $base_url . '&servicestatustypes=' . SERVICESTATE_WARNING . '&serviceattr=' . (SERVICESTATUSATTR_CHECKSENABLED) . '">' . $info["warning"]["active"] . ' ' . _('Active') . '</a></div>';
    }
    if ($info["warning"]["disabled"]) {
        $output .= '<div class="tacserviceProblemWarning">' . $neptune_hostwarning . '<a class="hc-text" href="' . $base_url . '&servicestatustypes=' . SERVICESTATE_WARNING . '&serviceattr=' . (SERVICESTATUSATTR_CHECKSDISABLED | SERVICESTATUSATTR_PASSIVECHECKSENABLED) . '">' . $info["warning"]["disabled"] . ' ' . _('Passive') . '</a></div>';
    }
    if ($info["warning"]["soft"]) {
        $output .= '<div class="tacserviceProblemWarning">' . $neptune_hostwarning . '<a class="hc-text" href="' . $base_url . '&servicestatustypes=' . SERVICESTATE_WARNING . '&serviceattr=524288">' . $info["warning"]["soft"] . ' ' . _('Soft Problems') . '</a></div>';
    }
    $output .= '</div></td>';

    // Unknown
    $output .= '<td><div class="neptune-center-content">';
    if ($info["unknown"]["unhandled"]) {
        $output .= '<div class="tacserviceImportantUnknown">' . $neptune_hostunknown . '<a class="hc-text" href="' . $base_url . '&servicestatustypes=' . SERVICESTATE_UNKNOWN . '&serviceattr=' . (SERVICESTATUSATTR_NOTINDOWNTIME | SERVICESTATUSATTR_NOTACKNOWLEDGED) . '&hoststatustypes=3">' . $info["unknown"]["unhandled"] . ' ' . _('Unhandled Problems') . '</a></div>';
    }
    if ($info["unknown"]["hostproblem"]) {
        $output .= '<div class="tacserviceProblemUnknown">' . $neptune_hostunknown . '<a class="hc-text" href="' . $base_url . '&servicestatustypes=' . SERVICESTATE_UNKNOWN . '&hoststatustypes=12">' . $info["unknown"]["hostproblem"] . ' ' . _('On Problem Hosts') . '</a></div>';
    }
    if ($info["unknown"]["acknowledged"]) {
        $output .= '<div class="tacserviceProblemUnknown">' . $neptune_hostunknown . '<a class="hc-text" href="' . $base_url . '&servicestatustypes=' . SERVICESTATE_UNKNOWN . '&serviceattr=4">' . $info["unknown"]["acknowledged"] . ' ' . _('Acknowledged') . '</a></div>';
    }
    if ($info["unknown"]["scheduled"]) {
        $output .= '<div class="tacserviceProblemUnknown">' . $neptune_hostunknown . '<a class="hc-text" href="' . $base_url . '&servicestatustypes=' . SERVICESTATE_UNKNOWN . '&serviceattr=1">' . $info["unknown"]["scheduled"] . ' ' . _('Scheduled') . '</a></div>';
    }
    if ($info["unknown"]["active"]) {
        $output .= '<div class="tacserviceProblemUnknown">' . $neptune_hostunknown . '<a class="hc-text" href="' . $base_url . '&servicestatustypes=' . SERVICESTATE_UNKNOWN . '&serviceattr=' . (SERVICESTATUSATTR_CHECKSENABLED) . '">' . $info["unknown"]["active"] . ' ' . _('Active') . '</a></div>';
    }
    if ($info["unknown"]["disabled"]) {
        $output .= '<div class="tacserviceProblemUnknown">' . $neptune_hostunknown . '<a class="hc-text" href="' . $base_url . '&servicestatustypes=' . SERVICESTATE_UNKNOWN . '&serviceattr=' . (SERVICESTATUSATTR_CHECKSDISABLED | SERVICESTATUSATTR_PASSIVECHECKSENABLED) . '">' . $info["unknown"]["disabled"] . ' ' . _('Passive') . '</a></div>';
    }
    if ($info["unknown"]["soft"]) {
        $output .= '<div class="tacserviceProblemUnknown">' . $neptune_hostunknown . '<a class="hc-text" href="' . $base_url . '&servicestatustypes=' . SERVICESTATE_UNKNOWN . '&serviceattr=524288">' . $info["unknown"]["soft"] . ' ' . _('Soft Problems') . '</a></div>';
    }
    $output .= '</div></td>';

    // Ok
    $output .= '<td><div class="neptune-center-content">';
    if ($info["ok"]["scheduled"]) {
        $output .= '<div class="tacserviceNoProblem">' . $neptune_hostup . '<a class="hc-text" href="' . $base_url . '&servicestatustypes=' . SERVICESTATE_OK . '&serviceattr=1">' . $info["ok"]["scheduled"] . ' ' . _('Scheduled') . '</a></div>';
    }
    if ($info["ok"]["active"]) {
        $output .= '<div class="tacserviceNoProblem">' . $neptune_hostup . '<a class="hc-text" href="' . $base_url . '&servicestatustypes=' . SERVICESTATE_OK . '&serviceattr=' . (SERVICESTATUSATTR_CHECKSENABLED) . '">' . $info["ok"]["active"] . ' ' . _('Active') . '</a></div>';
    }
    if ($info["ok"]["disabled"]) {
        $output .= '<div class="tacserviceNoProblem">' . $neptune_hostup . '<a class="hc-text" href="' . $base_url . '&servicestatustypes=' . SERVICESTATE_OK . '&serviceattr=' . (SERVICESTATUSATTR_CHECKSDISABLED | SERVICESTATUSATTR_PASSIVECHECKSENABLED) . '">' . $info["ok"]["disabled"] . ' ' . _('Passive') . '</a></div>';
    }
    $output .= '</div></td>';

    // Pending
    $output .= '<td><div class="neptune-center-content">';
    if ($info["pending"]["scheduled"]) {
        $output .= '<div class="tacserviceNoProblem">' . $neptune_hostpending . '<a class="hc-text" href="' . $base_url . '&servicestatustypes=' . SERVICESTATE_PENDING . '&serviceattr=1">' . $info["pending"]["scheduled"] . ' ' . _('Scheduled') . '</a></div>';
    }
    if ($info["pending"]["active"]) {
        $output .= '<div class="tacserviceNoProblem">' . $neptune_hostpending . '<a class="hc-text" href="' . $base_url . '&servicestatustypes=' . SERVICESTATE_PENDING . '&serviceattr=' . (SERVICESTATUSATTR_CHECKSENABLED) . '">' . $info["pending"]["active"] . ' ' . _('Active') . '</a></div>';
    }
    if ($info["pending"]["disabled"]) {
        $output .= '<div class="tacserviceNoProblem">' . $neptune_hostpending . '<a class="hc-text" href="' . $base_url . '&servicestatustypes=' . SERVICESTATE_PENDING . '&serviceattr=' . (SERVICESTATUSATTR_CHECKSDISABLED | SERVICESTATUSATTR_PASSIVECHECKSENABLED) . '">' . $info["pending"]["disabled"] . ' ' . _('Passive') . '</a></div>';
    }
    $output .= '</div></td>';

    $output .= '</tr>';

    $output .= '
    </tbody>
    </table>
    ';

    if ($mode == DASHLET_MODE_INBOARD) {
        $output .= '
        <div class="ajax_date">' . _('Last Updated:') . ' ' . get_datetime_string(time()) . '</div>
        ';
    }

    return $output;
}


/**
 * @param   array   $args
 * @return  string
 */
function xicore_ajax_get_feature_status_tac_summary_html($args = null)
{
    $mode = grab_array_var($args, "mode");

    $flap_detection_enabled = 0;
    $active_checks_enabled = 0;
    $passive_checks_enabled = 0;
    $notifications_enabled = 0;
    $event_handlers_enabled = 0;

    // Get program status
    $xml = get_xml_program_status();
    if ($xml) {
        $flap_detection_enabled = intval($xml->programstatus->flap_detection_enabled);
        $active_checks_enabled = intval($xml->programstatus->active_service_checks_enabled);
        $passive_checks_enabled = intval($xml->programstatus->passive_service_checks_enabled);
        $notifications_enabled = intval($xml->programstatus->notifications_enabled);
        $event_handlers_enabled = intval($xml->programstatus->event_handlers_enabled);
    }

    // Get service status totals
    $backendargs = array(
        "totals" => 1,
        "is_active" => 1
    );

    // Flap detection?
    $backendargs["flap_detection_enabled"] = 0;
    $xml = get_xml_service_status($backendargs);
    $services_flap_detection_disabled = ($xml) ? intval($xml->recordcount) : 0;
    unset($backendargs["flap_detection_enabled"]);

    // Services flapping
    $backendargs["is_flapping"] = 1;
    $xml = get_xml_service_status($backendargs);
    $services_flapping = ($xml) ? intval($xml->recordcount) : 0;
    unset($backendargs["is_flapping"]);

    // Services notifications disabled
    $backendargs["notifications_enabled"] = 0;
    $xml = get_xml_service_status($backendargs);
    $services_notifications_disabled = ($xml) ? intval($xml->recordcount) : 0;
    unset($backendargs["notifications_enabled"]);

    // Services event handler disabled
    $backendargs["event_handler_enabled"] = 0;
    $xml = get_xml_service_status($backendargs);
    $services_event_handlers_disabled = ($xml) ? intval($xml->recordcount) : 0;
    unset($backendargs["event_handler_enabled"]);

    // Services active checks disabled
    $backendargs["active_checks_enabled"] = 0;
    $xml = get_xml_service_status($backendargs);
    $services_active_checks_disabled = ($xml) ? intval($xml->recordcount) : 0;
    unset($backendargs["active_checks_enabled"]);

    // Services passive checks disabled
    $backendargs["passive_checks_enabled"] = 0;
    $xml = get_xml_service_status($backendargs);
    $services_passive_checks_disabled = ($xml) ? intval($xml->recordcount) : 0;
    unset($backendargs["passive_checks_enabled"]);

    // Get host status totals
    $backendargs = array(
        "totals" => 1,
        "is_active" => 1
    );

    // Flap detection?
    $backendargs["flap_detection_enabled"] = 0;
    $xml = get_xml_host_status($backendargs);
    $hosts_flap_detection_disabled = ($xml) ? intval($xml->recordcount) : 0;
    unset($backendargs["flap_detection_enabled"]);

    // Hosts flapping
    $backendargs["is_flapping"] = 1;
    $xml = get_xml_host_status($backendargs);
    $hosts_flapping = ($xml) ? intval($xml->recordcount) : 0;
    unset($backendargs["is_flapping"]);

    // Hosts notifications disabled
    $backendargs["notifications_enabled"] = 0;
    $xml = get_xml_host_status($backendargs);
    $hosts_notifications_disabled = ($xml) ? intval($xml->recordcount) : 0;
    unset($backendargs["notifications_enabled"]);

    // Hosts event handler disabled
    $backendargs["event_handler_enabled"] = 0;
    $xml = get_xml_host_status($backendargs);
    $hosts_event_handlers_disabled = ($xml) ? intval($xml->recordcount) : 0;
    unset($backendargs["event_handler_enabled"]);

    // Hosts active checks disabled
    $backendargs["active_checks_enabled"] = 0;
    $xml = get_xml_host_status($backendargs);
    $hosts_active_checks_disabled = ($xml) ? intval($xml->recordcount) : 0;
    unset($backendargs["active_checks_enabled"]);

    // Hosts passive checks disabled
    $backendargs["passive_checks_enabled"] = 0;
    $xml = get_xml_host_status($backendargs);
    $hosts_passive_checks_disabled = ($xml) ? intval($xml->recordcount) : 0;
    unset($backendargs["passive_checks_enabled"]);

    // Begin building html output
    $output = '';

    $neptune = 0;
    if (is_neptune()) { /* I dont like this but im going to leave it like this for performance since $neptune is used 11 times below */
        $neptune = 1;
    }

    $trclass = 'tacSubHeader';
    $tableclass = 'table table-condensed table-striped table-bordered table-no-margin';
    $neptune_icon_features = '';
    $neptune_hostdown = $neptune_hostup = $neptune_hostwarning = $neptune_hostunknown = $neptune_hostpending = "";
    if ($neptune) {
        $tableclass = 'table-condensed table-no-margin tactable-neptune';
        $trclass = 'tacSubHeader-neptune';
        $neptune_icon_features = '<span class="material-symbols-outlined md-16 md-pending md-400 md-middle md-padding tt-bind" title=' . _('Features') . '>gite</span>';
        $neptune_hostdown = '<span class="status-dot hostdown dot-10"></span>';
        $neptune_hostup = '<span class="status-dot hostup dot-10"></span>';
        $neptune_hostwarning = '<span class="status-dot hostwarning dot-10"></span>';
        $neptune_hostunknown = '<span class="status-dot hostunknown dot-10"></span>';
        $neptune_hostpending = '<span class="status-dot hostpending dot-10"></span>';
    }

    $output .= '
    <table class="' . $tableclass . '">
    <thead>
    <tr><th colspan="10">' . $neptune_icon_features . _('Features') . '</th></tr>
    </thead>
    <tbody>
    ';

    $process_status_url = get_base_url() . "includes/components/xicore/status.php?show=process";

    $status_url = get_base_url() . "includes/components/xicore/status.php";

    $output .= '<tr class="' . $trclass . '">';
    $output .= '<td colspan="2"><span>' . _('Flap Detection') . '&nbsp<a href="' . $process_status_url . '"><div class="statbar-neptune-features ' . (($flap_detection_enabled == 0) ? "disabled" : "enabled") . '">' . (($flap_detection_enabled == 0) ? "Disabled" : "Enabled") . '</div></a>';
    $output .= '</span></td><td colspan="2"><span>' . _('Notifications') . '&nbsp<a href="' . $process_status_url . '"><div class="statbar-neptune-features ' . (($notifications_enabled == 0) ? "disabled" : "enabled") . '">' . (($notifications_enabled == 0) ? "Disabled" : "Enabled") . '</div></a>';
    $output .= '</span></td><td colspan="2"><span>' . _('Event Handlers') . '&nbsp<a href="' . $process_status_url . '"><div class="statbar-neptune-features ' . (($event_handlers_enabled == 0) ? "disabled" : "enabled") . '">' . (($event_handlers_enabled == 0) ? "Disabled" : "Enabled") . '</div></a>';
    $output .= '</span></td><td colspan="2"><span>' . _('Active Checks') . '&nbsp<a href="' . $process_status_url . '"><div class="statbar-neptune-features ' . (($active_checks_enabled == 0) ? "disabled" : "enabled") . '">' . (($active_checks_enabled == 0) ? "Disabled" : "Enabled") . '</div></a>';
    $output .= '</span></td><td colspan="2"><span>' . _('Passive Checks') . '&nbsp<a href="' . $process_status_url . '"><div class="statbar-neptune-features ' . (($passive_checks_enabled == 0) ? "disabled" : "enabled") . '">' . (($passive_checks_enabled == 0) ? "Disabled" : "Enabled") . '</div></a></span></td></tr>';
    $output .= '<tr class="neptune-center-content">';

    // Flap detection
    if ($neptune) {
        $output .= '<td colspan="2"><div class="neptune-center-content">';
    }
    else {
        $output .= '<td><a href="' . $process_status_url . '"><img src="' . theme_image(($flap_detection_enabled == 0) ? "tacdisabled.png" : "tacenabled.png") . '"></a></td>';
        $output .= '<td width="135">';
    }
    if ($flap_detection_enabled == 0) {
        $output .= '<div class="tacfeatureNoProblem">N/A</div>';
    } else {
        if ($services_flap_detection_disabled > 0)
            $output .= '<div class="tacfeatureNoProblem">' . $neptune_hostdown . '<a href="' . $status_url . '?show=services&serviceattr=256">' . $services_flap_detection_disabled . ' ' . _('Services Disabled') . '</a></div>';
        else
            $output .= '<div class="tacfeatureNoProblem">' . $neptune_hostup . _('All Services Enabled') . '</div>';
        if ($services_flapping > 0)
            $output .= '<div class="tacfeatureProblem">' . $neptune_hostdown . '<a href="' . $status_url . '?show=services&serviceattr=1024">' . $services_flapping . ' ' . _('Services Flapping') . '</a></div>';
        if ($hosts_flap_detection_disabled > 0)
            $output .= '<div class="tacfeatureNoProblem">' . $neptune_hostdown . '<a href="' . $status_url . '?show=hosts&hostattr=256">' . $hosts_flap_detection_disabled . ' ' . _('Hosts Disabled') . '</a></div>';
        else
            $output .= '<div class="tacfeatureNoProblem">' . $neptune_hostup . _('All Hosts Enabled') . '</div>';
        if ($hosts_flapping > 0)
            $output .= '<div class="tacfeatureProblem">' . $neptune_hostdown . '<a href="' . $status_url . '?show=hosts&hostattr=1024">' . $hosts_flapping . ' ' . _('Hosts Flapping') . '</a></div>';
    }
    if ($neptune) {
        $output .= '</div>';
    }
    $output .= '</td>';

    // Notifications
    if ($neptune) {
        $output .= '<td colspan="2"><div class="neptune-center-content">';
    }
    else {
        $output .= '<td><a href="' . $process_status_url . '"><img src="' . theme_image(($notifications_enabled == 0) ? "tacdisabled.png" : "tacenabled.png") . '"></a></td>';
        $output .= '<td width="135">';
    }
    if ($notifications_enabled == 0) {
        $output .= '<div class="tacfeatureNoProblem">N/A</div>';
    } else {
        if ($services_notifications_disabled > 0)
            $output .= '<div class="tacfeatureNoProblem">' . $neptune_hostdown . '<a href="' . $status_url . '?show=services&serviceattr=4096">' . $services_notifications_disabled . ' ' . _('Services Disabled') . '</a></div>';
        else
            $output .= '<div class="tacfeatureNoProblem">' . $neptune_hostup . _('All Services Enabled') . '</div>';
        if ($hosts_notifications_disabled > 0)
            $output .= '<div class="tacfeatureNoProblem">' . $neptune_hostdown . '<a href="' . $status_url . '?show=hosts&hostattr=4096">' . $hosts_notifications_disabled . ' ' . _('Hosts Disabled') . '</a></div>';
        else
            $output .= '<div class="tacfeatureNoProblem">' . $neptune_hostup . _('All Hosts Enabled') . '</div>';
    }
    if ($neptune) {
        $output .= '</div>';
    }
    $output .= '</td>';

    // Event handlers
    if ($neptune) {
        $output .= '<td colspan="2"><div class="neptune-center-content">';
    }
    else {
        $output .= '<td><a href="' . $process_status_url . '"><img src="' . theme_image(($event_handlers_enabled == 0) ? "tacdisabled.png" : "tacenabled.png") . '"></a></td>';
        $output .= '<td width="135">';
    }
    if ($event_handlers_enabled == 0) {
        $output .= '<div class="tacfeatureNoProblem">N/A</div>';
    } else {
        if ($services_event_handlers_disabled > 0)
            $output .= '<div class="tacfeatureNoProblem">' . $neptune_hostdown . '<a href="' . $status_url . '?show=services&serviceattr=64">' . $services_event_handlers_disabled . ' ' . _('Services Disabled') . '</a></div>';
        else
            $output .= '<div class="tacfeatureNoProblem">' . $neptune_hostup . _('All Services Enabled') . '</div>';
        if ($hosts_event_handlers_disabled > 0)
            $output .= '<div class="tacfeatureNoProblem">' . $neptune_hostdown . '<a href="' . $status_url . '?show=hosts&hostattr=64">' . $hosts_event_handlers_disabled . ' ' . _('Hosts Disabled') . '</a></div>';
        else
            $output .= '<div class="tacfeatureNoProblem">' . $neptune_hostup . _('All Hosts Enabled') . '</div>';
    }
    if ($neptune) {
        $output .= '</div>';
    }
    $output .= '</td>';

    // Active checks
    if ($neptune) {
        $output .= '<td colspan="2"><div class="neptune-center-content">';
    }
    else {
        $output .= '<td><a href="' . $process_status_url . '"><img src="' . theme_image(($active_checks_enabled == 0) ? "tacdisabled.png" : "tacenabled.png") . '"></a></td>';
        $output .= '<td width="135">';
    }
    if ($active_checks_enabled == 0) {
        $output .= '<div class="tacfeatureNoProblem">N/A</div>';
    } else {
        if ($services_active_checks_disabled > 0)
            $output .= '<div class="tacfeatureNoProblem">' . $neptune_hostdown . '<a href="' . $status_url . '?show=services&serviceattr=16">' . $services_active_checks_disabled . ' ' . _('Services Disabled') . '</a></div>';
        else
            $output .= '<div class="tacfeatureNoProblem">' . $neptune_hostup . _('All Services Enabled') . '</div>';
        if ($hosts_active_checks_disabled > 0)
            $output .= '<div class="tacfeatureNoProblem">' . $neptune_hostdown . '<a href="' . $status_url . '?show=hosts&hostattr=16">' . $hosts_active_checks_disabled . ' ' . _('Hosts Disabled') . '</a></div>';
        else
            $output .= '<div class="tacfeatureNoProblem">' . $neptune_hostup . _('All Hosts Enabled') . '</div>';
    }
    if ($neptune) {
        $output .= '</div>';
    }
    $output .= '</td>';

    // Passive checks
    if ($neptune) {
        $output .= '<td colspan="2"><div class="neptune-center-content">';
    }
    else {
        $output .= '<td><a href="' . $process_status_url . '"><img src="' . theme_image(($passive_checks_enabled == 0) ? "tacdisabled.png" : "tacenabled.png") . '"></a></td>';
        $output .= '<td width="135">';
    }
    if ($passive_checks_enabled == 0) {
        $output .= '<div class="tacfeatureNoProblem">N/A</div>';
    } else {
        if ($services_passive_checks_disabled > 0)
            $output .= '<div class="tacfeatureNoProblem">' . $neptune_hostdown . '<a href="' . $status_url . '?show=services&serviceattr=16384">' . $services_passive_checks_disabled . ' ' . _('Services Disabled') . '</a></div>';
        else
            $output .= '<div class="tacfeatureNoProblem">' . $neptune_hostup . _('All Services Enabled') . '</div>';
        if ($hosts_passive_checks_disabled > 0)
            $output .= '<div class="tacfeatureNoProblem">' . $neptune_hostdown . '<a href="' . $status_url . '?show=hosts&hostattr=16384">' . $hosts_passive_checks_disabled . ' ' . _('Hosts Disabled') . '</a></div>';
        else
            $output .= '<div class="tacfeatureNoProblem">' . $neptune_hostup . _('All Hosts Enabled') . '</div>';
    }
    if ($neptune) {
        $output .= '</div>';
    }
    $output .= '</td>';

    $output .= '</tr>';

    $output .= '
    </tbody>
    </table>
    ';

    if ($mode == DASHLET_MODE_INBOARD) {
        $output .= '
        <div class="ajax_date">' . _('Last Updated') . ': ' . get_datetime_string(time()) . '</div>
        ';
    }

    return $output;
}
