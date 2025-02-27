<?php
//
// XI Core Ajax Helper Functions
// Copyright (c) 2008-2018 Nagios Enterprises, LLC. All rights reserved.
//


include_once(dirname(__FILE__) . '/../componenthelper.inc.php');
include_once(dirname(__FILE__) . '/../nagioscore/coreuiproxy.inc.php');


////////////////////////////////////////////////////////////////////////
// GENERAL STATUS AJAX FUNCTIONS
////////////////////////////////////////////////////////////////////////


/**
 * Get the network outages table HTML
 *
 * @param   array   $args   Arguments
 * @return  string          HTML output
 */
function xicore_ajax_get_network_outages_html($args = null)
{
    $output = '';

    $output .= '<div class="infotable_title">' . _('Network Outages') . '</div>';

    $url = "outages-xml.cgi";
    $cgioutput = coreui_get_raw_cgi_output($url, array());

    $xml = simplexml_load_string($cgioutput);

    if (!$xml) {
        $output .= _("Error: Unable to parse XML output");
    } else {
        $tableclass = "hoststatustable table table-condensed table-striped table-bordered";
        if (is_neptune()) {
            $tableclass = "table dashlettable-in table-condensed";
        }
        $output .= '
        <table class="' . $tableclass . '">
            <thead>
                <tr>
                    <th>' . _('Severity') . '</th>
                    <th>' . _('Host') . '</th>
                    <th>' . _('State') . '</th>
                    <th>' . _('Duration') . '</th>
                    <th>' . _('Hosts Affected') . '</th>
                    <th>' . _('Services Affected') . '</th>
                </tr>
            </thead>
            <tbody>';

        $total = 0;
        foreach ($xml->hostoutage as $ho) {

            $total++;

            $hostname = strval($ho->host);
            $severity = intval($ho->severity);
            $hostsaffected = intval($ho->affectedhosts);
            $state = intval($ho->state);
            $servicesaffected = intval($ho->affectedservices);
            $duration = intval($ho->duration);

            $durationstr = get_duration_string($duration, "0s", "0s");

            $stateclass = "";
            switch ($state) {
                case HOSTSTATE_DOWN:
                    $statestr = _("Down");
                    $stateclass = "hostdown";
                    break;
                case HOSTSTATE_UNREACHABLE:
                    $statestr = _("Unreachable");
                    $stateclass = "hostunreachable";
                    break;
                case 0:
                default:
                    $statestr = _("Up");
                    $stateclass = "hostup";

                    break;
            }

            $url = get_base_url() . "includes/components/xicore/status.php?host=" . urlencode($hostname);

            if(is_neptune()){
                $output .= '<tr><td>' . $severity . '</td><td><a href="' . $url . '">' . $hostname . '</a></td><td><span class="status-dot ' . $stateclass . ' dot-10"></span> ' . $statestr . '
                </td><td>' . $durationstr . '</td><td>' . $hostsaffected . '</td><td>' . $servicesaffected . '</td></tr>';
            } else {
                $output .= '<tr><td>' . $severity . '</td><td><a href="' . $url . '">' . $hostname . '</a></td><td class="' . $stateclass . '">' . $statestr . '</td><td>' . $durationstr . '</td><td>' . $hostsaffected . '</td><td>' . $servicesaffected . '</td>';
            }
        }

        if ($total == 0) {
            $output .= '<tr><td colspan="6"><p style="font-size:14px">' . _('There are no blocking outages at this time.') . '</p></td></tr>';
        }

        $output .= '
            </tbody>
        </table>';
    }

    $output .= '<div class="ajax_date">' . _('Last Updated') . ': ' . get_datetime_string(time()) . '</div>';

    return $output;
}


/**
 * Get a Nagios Core CGI's HTML
 *
 * @param   array   $args   Arguments (url)
 * @return  string          HTML output
 */
function xicore_ajax_get_nagioscore_cgi_html($args = null)
{
    $url = $args["url"];
    $output = coreuiproxy_get_embedded_cgi_output($url);
    return $output;
}


/**
 * Get the host and service summary HTML
 *
 * @param   array   $args   Arguments
 * @return  string          HTML output
 */
function xicore_ajax_get_host_status_summary_html($args = null)
{
    $output = '';
    $show = grab_array_var($args, "show", "hosts");
    $host = grab_array_var($args, "host", "");
    $hostgroup = grab_array_var($args, "hostgroup", "");
    $servicegroup = grab_array_var($args, "servicegroup", "");
    $servicestatustypes = grab_array_var($args, "servicestatustypes", 0);

    if ($hostgroup == "all")
        $hostgroup = "";
    if ($servicegroup == "all")
        $servicegroup = "";
    if ($host == "all")
        $host = "";

    // Limit hosts by hostgroup or host
    $host_ids = array();
    if (!empty($hostgroup)) {
        $host_ids = get_hostgroup_member_ids($hostgroup);
    } else if (!empty($servicegroup)) {
        $host_ids = get_servicegroup_host_member_ids($servicegroup);
    } else if (!empty($host)) {
        $host_ids[] = get_host_id($host);
    }

    // PREP TO GET TOTAL RECORD COUNTS FROM BACKEND...

    $backendargs = array();
    $backendargs["cmd"] = "gethoststatus";
    $backendargs["limitrecords"] = false;
    $backendargs["totals"] = 1;

    // Host ID limiters
    if (!empty($host_ids)) {
        $backendargs["host_id"] = "in:" . implode(',', $host_ids);
    }

    // Get total hosts
    $xml = get_xml_host_status($backendargs);
    $total_records = 0;
    if ($xml) {
        $total_records = intval($xml->recordcount);
    }

    // Get host totals (up/pending checked later)
    $state_totals = array();
    for ($x = 1; $x <= 2; $x++) {
        $backendargs["current_state"] = $x;
        $xml = get_xml_host_status($backendargs);
        $state_totals[$x] = 0;
        if ($xml) {
            $state_totals[$x] = intval($xml->recordcount);
        }
    }

    // Get up (non-pending)
    $backendargs["current_state"] = 0;
    $backendargs["has_been_checked"] = 1;
    $xml = get_xml_host_status($backendargs);
    $state_totals[0] = 0;
    if ($xml) {
        $state_totals[0] = intval($xml->recordcount);
    }

    // Get pending
    $backendargs["current_state"] = 0;
    $backendargs["has_been_checked"] = 0;
    $xml = get_xml_host_status($backendargs);
    $state_totals[3] = 0;
    if ($xml) {
        $state_totals[3] = intval($xml->recordcount);
    }

    // Total problems
    $total_problems = $state_totals[1] + $state_totals[2];

    // Unhandled problems
    $backendargs["current_state"] = "in:1,2";
    unset($backendargs["has_been_checked"]);
    $backendargs["problem_acknowledged"] = 0;
    $backendargs["scheduled_downtime_depth"] = 0;
    $xml = get_xml_host_status($backendargs);
    $unhandled_problems = 0;
    if ($xml) {
        $unhandled_problems = intval($xml->recordcount);
    }

    $output .= '<div class="infotable_title">' . _('Host Status Summary') . '</div>';

    if ($show == "hostproblems" || $show == "hosts") {
        $show = "hosts";
    } else {
        $show = "services";
    }

    // URLs
    $baseurl = get_base_url() . "includes/components/xicore/status.php?";
    if (!empty($hostgroup)) {
        $baseurl .= "&hostgroup=" . urlencode($hostgroup);
    }
    if (!empty($servicegroup)) {
        $baseurl .= "&servicegroup=" . urlencode($servicegroup);
    }
    if (!empty($host)) {
        $baseurl .= "&host=" . urlencode($host);
    }
    $state_text = array();

    $state_text[0] = "<div class='hostup";
    if ($state_totals[0] > 0) {
        $state_text[0] .= " havehostup";
    }
    $state_text[0] .= "'>";
    $state_text[0] .= "<a class='hc-text' href='" . $baseurl . "&show=" . $show . "&hoststatustypes=" . HOSTSTATE_UP . "&servicestatustypes=" . $servicestatustypes . "'>" . $state_totals[0] . "</a>";
    $state_text[0] .= "</div>";

    $state_text[1] = "<div class='hostdown";
    if ($state_totals[1] > 0) {
        $state_text[1] .= " havehostdown";
    }
    $state_text[1] .= "'>";
    $state_text[1] .= "<a class='hc-text' href='" . $baseurl . "&show=" . $show . "&hoststatustypes=" . HOSTSTATE_DOWN . "&servicestatustypes=" . $servicestatustypes . "'>" . $state_totals[1] . "</a>";
    $state_text[1] .= "</div>";

    $state_text[2] = "<div class='hostunreachable";
    if ($state_totals[2] > 0) {
        $state_text[2] .= " havehostunreachable";
    }
    $state_text[2] .= "'>";
    $state_text[2] .= "<a class='hc-text' href='" . $baseurl . "&show=" . $show . "&hoststatustypes=" . HOSTSTATE_UNREACHABLE . "&servicestatustypes=" . $servicestatustypes . "'>" . $state_totals[2] . "</a>";
    $state_text[2] .= "</div>";

    $state_text[3] = "<div class='hostpending";
    if ($state_totals[3] > 0) {
        $state_text[3] .= " havehostpending";
    }
    $state_text[3] .= "'>";
    $state_text[3] .= "<a class='hc-text' href='" . $baseurl . "&show=" . $show . "&hoststatustypes=" . HOSTSTATE_PENDING . "&servicestatustypes=" . $servicestatustypes . "'>" . $state_totals[3] . "</a>";
    $state_text[3] .= "</div>";

    $unhandled_problems_text = "<div class='unhandledhostproblems";
    if ($unhandled_problems > 0) {
        $unhandled_problems_text .= " haveunhandledhostproblems";
    }
    $unhandled_problems_text .= "'>";
    $unhandled_problems_text .= "<a class='hc-text' href='" . $baseurl . "&show=" . $show . "&servicestatustypes=" . $servicestatustypes . "&hoststatustypes=" . (HOSTSTATE_DOWN | HOSTSTATE_UNREACHABLE) . "&hostattr=" . (HOSTSTATUSATTR_NOTACKNOWLEDGED | HOSTSTATUSATTR_NOTINDOWNTIME) . "'>" . $unhandled_problems . "</a>";
    $unhandled_problems_text .= "</div>";

    $total_problems_text = "<div class='hostproblems";
    if ($total_problems > 0) {
        $total_problems_text .= " havehostproblems";
    }
    $total_problems_text .= "'>";
    $total_problems_text .= "<a class='hc-text' href='" . $baseurl . "&show=" . $show . "&hoststatustypes=" . (HOSTSTATE_DOWN | HOSTSTATE_UNREACHABLE) . "&servicestatustypes=" . $servicestatustypes . "'>" . $total_problems . "</a>";
    $total_problems_text .= "</div>";

    $total_records_text = "<div class='allhosts";
    if ($total_records > 0) {
        $total_records_text .= " haveallhosts";
    }
    $total_records_text .= "'>";
    $total_records_text .= "<a class='hc-text' href='" . $baseurl . "&show=" . $show . "&servicestatustypes=" . $servicestatustypes . "'>" . $total_records . "</a>";
    $total_records_text .= "</div>";

    if (is_neptune()) {
        $host_icon_up = '<span class="status-dot hostup dot-10" title=' . _('Up') . '></span>';
        $host_icon_down = '<span class="status-dot hostdown dot-10" title=' . _('Down') . '></span>';
        $host_icon_unreachable = '<span class="status-dot hostunknown dot-10" title=' . _('Unreachable') . '></span>';
        $host_icon_pending = '<span class="status-dot hostpending dot-10" title=' . _('Pending') . '></span>';
        $host_icon_unhandled = '<span class="status-dot hostunknown dot-10" title=' . _('Unhandled') . '></span>';
        $host_icon_problems = '<span class="status-dot hostwarning dot-10" title=' . _('Problems') . '></span>';
        $host_icon_all = '<span class="status-dot hostpending dot-10" title=' . _('All') . '></span>';
    
        $output .= '
        <table class="table infotable-neptune table-condensed">
        <tbody>';
        $output .= '
        <tr><td>' . $host_icon_up . _("Up") . $state_text[0] . '</td><td>' . $host_icon_problems . _("Problems") . $total_problems_text . '</td></tr>';
        $output .= '
        <tr><td>' . $host_icon_down . _("Down") . $state_text[1] . '</td><td>' . $host_icon_unhandled . _("Unhandled Problems") . $unhandled_problems_text . '</td></tr>';
        $output .= '
        <tr><td>' . $host_icon_unreachable . _("Unreachable") . $state_text[2] . '</td><td>' . $host_icon_all . _("All") . $total_records_text . '</td></tr>';
        $output .= '
        <tr><td>' . $host_icon_pending . _("Pending") . $state_text[3] . '</td></tr>';
        $output .= '
        </tbody>
        </table>';
    }
    else {
        $output .= '
        <table class="infotable table table-condensed table-striped table-bordered">
        <thead>
        <tr><th>' . _("Up") . '</th><th>' . _("Down") . '</th><th>' . _("Unreachable") . '</th><th>' . _("Pending") . '</th></tr>
        </thead>
        ';
    
        $output .= '
        <tbody>
        <tr><td>' . $state_text[0] . '</td><td>' . $state_text[1] . '</td><td>' . $state_text[2] . '</td><td>' . $state_text[3] . '</td></tr>
        </tbody>
        ';
    
        $output .= '
        <thead>
        <tr><th colspan="2">' . _('Unhandled') . '</th><th>' . _('Problems') . '</th><th>' . _('All') . '</th></tr>
        </thead>
        ';
    
        $output .= '
        <tbody>
        <tr><td colspan="2">' . $unhandled_problems_text . '</td><td>' . $total_problems_text . '</td><td>' . $total_records_text . '</td></tr>
        </tbody>
        ';
    
        $output .= '</table>';
    }
    $output .= '<div class="ajax_date">' . _('Last Updated') . ': ' . get_datetime_string(time()) . '</div>';


    return $output;
}

/**
 * Get the host and service summary HTML dashboard
 *
 * @param   array   $args   Arguments
 * @return  string          HTML output
 */
function xicore_ajax_get_host_and_service_status_summary_dashboard_html($args = null)
{
    $output = '';
    $show = grab_array_var($args, "show", "hosts");
    $host = grab_array_var($args, "host", "");
    $hostgroup = grab_array_var($args, "hostgroup", "");
    $servicegroup = grab_array_var($args, "servicegroup", "");
    $servicestatustypes = grab_array_var($args, "servicestatustypes", 0);

    if ($hostgroup == "all")
        $hostgroup = "";
    if ($servicegroup == "all")
        $servicegroup = "";
    if ($host == "all")
        $host = "";

    // Limit hosts by hostgroup or host
    $host_ids = array();
    if (!empty($hostgroup)) {
        $host_ids = get_hostgroup_member_ids($hostgroup);
    } else if (!empty($servicegroup)) {
        $host_ids = get_servicegroup_host_member_ids($servicegroup);
    } else if (!empty($host)) {
        $host_ids[] = get_host_id($host);
    }

    // PREP TO GET TOTAL RECORD COUNTS FROM BACKEND...

    $backendargs = array();
    $backendargs["cmd"] = "gethoststatus";
    $backendargs["limitrecords"] = false;
    $backendargs["totals"] = 1;

    // Host ID limiters
    if (!empty($host_ids)) {
        $backendargs["host_id"] = "in:" . implode(',', $host_ids);
    }

    // Get total hosts
    $xml = get_xml_host_status($backendargs);
    $total_records = 0;
    if ($xml) {
        $total_records = intval($xml->recordcount);
    }

    // Get host totals (up/pending checked later)
    $host_state_totals = array();
    for ($x = 1; $x <= 2; $x++) {
        $backendargs["current_state"] = $x;
        $xml = get_xml_host_status($backendargs);
        $host_state_totals[$x] = 0;
        if ($xml) {
            $host_state_totals[$x] = intval($xml->recordcount);
        }
    }

    // Get up (non-pending)
    $backendargs["current_state"] = 0;
    $backendargs["has_been_checked"] = 1;
    $xml = get_xml_host_status($backendargs);
    $host_state_totals[0] = 0;
    if ($xml) {
        $host_state_totals[0] = intval($xml->recordcount);
    }

    // Get pending
    $backendargs["current_state"] = 0;
    $backendargs["has_been_checked"] = 0;
    $xml = get_xml_host_status($backendargs);
    $state_totals[3] = 0;
    if ($xml) {
        $host_state_totals[3] = intval($xml->recordcount);
    }

    // Total problems
    $host_total_problems = $host_state_totals[1] + $host_state_totals[2];

    // Unhandled problems
    $backendargs["current_state"] = "in:1,2";
    unset($backendargs["has_been_checked"]);
    $backendargs["problem_acknowledged"] = 0;
    $backendargs["scheduled_downtime_depth"] = 0;
    $xml = get_xml_host_status($backendargs);
    $host_unhandled_problems = 0;
    if ($xml) {
        $host_unhandled_problems = intval($xml->recordcount);
    }

    if ($show == "hostproblems" || $show == "hosts") {
        $show = "hosts";
    } else {
        $show = "services";
    }

    // URLs
    $baseurl = get_base_url() . "includes/components/xicore/status.php?";
    if (!empty($hostgroup)) {
        $baseurl .= "&hostgroup=" . urlencode($hostgroup);
    }
    if (!empty($servicegroup)) {
        $baseurl .= "&servicegroup=" . urlencode($servicegroup);
    }
    if (!empty($host)) {
        $baseurl .= "&host=" . urlencode($host);
    }

    $state_text = array();

    function build_state_text($dotClass, $textClass, $title, $baseurl, $show, $host_status_type, $service_status_types, $host_status_total, $include_host_status_type = true, $special_conditions = null,$isLastElement = false) {
        $host_status_query = $include_host_status_type ? "&hoststatustypes=" . $host_status_type : "";
        $special_query = is_array($special_conditions) ? http_build_query($special_conditions) : '';
        $href = $baseurl . "&show=" . $show . $host_status_query . "&servicestatustypes=" . $service_status_types . ($special_query ? '&' . $special_query : '');
        $borderClass = $isLastElement ? "neptune-last-numeric-radius" : "border-b";
        
        return '
        <div class="neptune-status-numeric-entry ' . $borderClass . ' border-l neptune-tab-selectable" onclick="window.location.href=\'' . $href . '\'">
            <div style="margin-bottom:5px" class="neptune-status-numeric-title flex justify-start items-center">
                <span class="status-dot ' . $dotClass . ' dot-10"></span> ' . $title . '
            </div>
            <div class="neptune-status-numeric-info">
                <span class="neptune-total-status-number '. $textClass . '">'. $host_status_total . '</span> <span class="neptune-total-status-number-subtext">hosts</span>
            </div>
        </div>';
    }

    // Using the modified function to generate the neptune-status-numeric-entry elements
    $host_icon_up = build_state_text("hostup", "hostup-text", "Up", $baseurl, $show, HOSTSTATE_UP, $servicestatustypes, $host_state_totals[0]);
    $host_icon_down = build_state_text("hostdown", "hostdown-text", "Down", $baseurl, $show, HOSTSTATE_DOWN, $servicestatustypes, $host_state_totals[1]);
    $host_icon_unreachable = build_state_text("hostunreachable-dashboard", "hostunreachable-dashboard-text", "Unreachable", $baseurl, $show, HOSTSTATE_UNREACHABLE, $servicestatustypes, $host_state_totals[2]);
    $host_icon_pending = build_state_text("hostpending-dashboard", "hostpending-dashboard-text", "Pending", $baseurl, $show, HOSTSTATE_PENDING, $servicestatustypes, $host_state_totals[3], true, null, true);
    $host_icon_problem = build_state_text(
        "hostproblem", "hostproblem-text", "Problems", $baseurl, $show, 
        HOSTSTATE_DOWN | HOSTSTATE_UNREACHABLE, $servicestatustypes, $host_total_problems
    );
        $host_icon_unhandled = build_state_text(
        "hostunhandled", "hostunhandled-text", "Unhandled", $baseurl, $show, 
        HOSTSTATE_DOWN | HOSTSTATE_UNREACHABLE, $servicestatustypes, $host_unhandled_problems, true,
        ['hostattr' => HOSTSTATUSATTR_NOTACKNOWLEDGED | HOSTSTATUSATTR_NOTINDOWNTIME], false
    );

    // We're doing service now

    $host = grab_array_var($args, "host", "");
    $hostgroup = grab_array_var($args, "hostgroup", "");
    $servicegroup = grab_array_var($args, "servicegroup", "");
    $hoststatustypes = grab_array_var($args, "hoststatustypes", HOSTSTATE_ANY);

    if ($hostgroup == "all")
        $hostgroup = "";
    if ($servicegroup == "all")
        $servicegroup = "";
    if ($host == "all")
        $host = "";

    // Limit hosts by hostgroup or host
    $host_ids = array();
    if (!empty($hostgroup)) {
        $host_ids = get_hostgroup_member_ids($hostgroup);
    } else if (!empty($host)) {
        $host_ids[] = get_host_id($host);
    }

    // Limit service by servicegroup
    $service_ids = array();
    if (!empty($servicegroup)) {
        $service_ids = get_servicegroup_member_ids($servicegroup);
    }

    // PREP TO GET TOTAL RECORD COUNTS FROM BACKEND...

    $backendargs = array();
    $backendargs["cmd"] = "getservicestatus";
    $backendargs["limitrecords"] = false;
    $backendargs["totals"] = 1;
    $backendargs["combinedhost"] = true;

    // Host ID limiters
    if (!empty($host_ids)) {
        $backendargs["host_id"] = "in:" . implode(',', $host_ids);
    }

    // Service ID limiters
    if (!empty($service_ids)) {
        $backendargs["service_id"] = "in:" . implode(',', $service_ids);
    }

    // Get total services
    $xml = get_xml_service_status($backendargs);
    $service_total_records = 0;
    if ($xml) {
        $service_total_records = intval($xml->recordcount);
    }

    // Get state totals (ok/pending checked later)
    $service_state_totals = array();
    for ($x = 1; $x <= 3; $x++) {
        $backendargs["current_state"] = $x;
        $xml = get_xml_service_status($backendargs);
        $service_state_totals[$x] = 0;
        if ($xml) {
            $service_state_totals[$x] = intval($xml->recordcount);
        }
    }

    // Get ok (non-pending)
    $backendargs["current_state"] = 0;
    $backendargs["has_been_checked"] = 1;
    $xml = get_xml_service_status($backendargs);
    $service_state_totals[0] = 0;
    if ($xml) {
        $service_state_totals[0] = intval($xml->recordcount);
    }

    // Get pending
    $backendargs["current_state"] = 0;
    $backendargs["has_been_checked"] = 0;
    $xml = get_xml_service_status($backendargs);
    $service_state_totals[4] = 0;
    if ($xml) {
        $service_state_totals[4] = intval($xml->recordcount);
    }

    // Total problems
    $service_total_problems = $service_state_totals[1] + $service_state_totals[2] + $service_state_totals[3];

    // Unhandled problems
    $backendargs["current_state"] = "in:1,2,3";
    unset($backendargs["has_been_checked"]);
    $backendargs["problem_acknowledged"] = 0;
    $backendargs["scheduled_downtime_depth"] = 0;
    $xml = get_xml_service_status($backendargs);
    $service_unhandled_problems = 0;
    if ($xml) {
        $service_unhandled_problems = intval($xml->recordcount);
    }

    $show = "services";

    // URLs
    $baseurl = get_base_url() . "includes/components/xicore/status.php?";
    if (!empty($hostgroup)) {
        $baseurl .= "&hostgroup=" . urlencode($hostgroup);
    }
    if (!empty($servicegroup)) {
        $baseurl .= "&servicegroup=" . urlencode($servicegroup);
    }
    if (!empty($host)) {
        $baseurl .= "&host=" . urlencode($host);
    }
    
    $service_state_text = array();

    
    function build_service_state_text($dotClass, $textClass, $title, $baseurl, $show, $host_status_types, $service_status_type, $service_status_total, $include_service_status_type = true, $special_conditions = null, $isLastElement = false) {
        $service_status_query = $include_service_status_type ? "&servicestatustypes=" . $service_status_type : "";
        $special_query = is_array($special_conditions) ? http_build_query($special_conditions) : '';
        
        $href = $baseurl . "&show=" . $show . "&hoststatustypes=" . $host_status_types . $service_status_query . ($special_query ? '&' . $special_query : '');
        
        // Conditional class to remove the bottom border if this is the last element
        $borderClass = $isLastElement ? "neptune-last-numeric-radius" : "border-b";
        
        return '
        <div class="neptune-status-numeric-entry-service ' . $borderClass . ' border-l neptune-tab-selectable" onclick="window.location.href=\'' . $href . '\'">
            <div style="margin-bottom:5px" class="neptune-status-numeric-title flex justify-start items-center">
                <span class="status-dot ' . $dotClass . ' dot-10"></span> ' . $title . '
            </div>
            <div class="neptune-status-numeric-info">
                <span class="neptune-total-status-number '. $textClass . '">'. $service_status_total . '</span> <span class="neptune-total-status-number-subtext">services</span>
            </div>
        </div>';
    }
    

    $service_icon_ok = build_service_state_text("serviceok", "serviceok-text", "OK", $baseurl, $show, $hoststatustypes, SERVICESTATE_OK, $service_state_totals[0]);
    $service_icon_warning = build_service_state_text("servicewarning-dashboard", "servicewarning-dashboard-text", "Warning", $baseurl, $show, $hoststatustypes, SERVICESTATE_WARNING, $service_state_totals[1]);
    $service_icon_critical = build_service_state_text("servicecritical-dashboard", "servicecritical-dashboard-text", "Critical", $baseurl, $show, $hoststatustypes, SERVICESTATE_CRITICAL, $service_state_totals[2]);
    $service_icon_unknown = build_service_state_text("serviceunknown-dashboard", "serviceunknown-dashboard-text", "Unknown", $baseurl, $show, $hoststatustypes, SERVICESTATE_UNKNOWN, $service_state_totals[3]);
    $service_icon_pending = build_service_state_text("servicepending-dashboard", "servicepending-dashboard-text", "Pending", $baseurl, $show, $hoststatustypes, SERVICESTATE_PENDING, $service_state_totals[4], true, null, true);

    $service_icon_problem = build_service_state_text(
        "serviceproblem", "serviceproblem-text", "Problems", $baseurl, $show, 
        $hoststatustypes, SERVICESTATE_WARNING | SERVICESTATE_UNKNOWN | SERVICESTATE_CRITICAL, $service_total_problems
    );

    $service_icon_unhandled = build_service_state_text(
        "serviceunhandled", "serviceunhandled-text", "Unhandled", $baseurl, $show, 
        $hoststatustypes, SERVICESTATE_WARNING | SERVICESTATE_UNKNOWN | SERVICESTATE_CRITICAL, $service_unhandled_problems, true,
        ['serviceattr' => SERVICESTATUSATTR_NOTACKNOWLEDGED | SERVICESTATUSATTR_NOTINDOWNTIME], false // Set to true for the last element
    );

    
        // Building the output with generated state texts and icons
    $output .= '
    <div class="neptune-host-service-dashboard-container">
        <div class="neptune-host-service-dashboard-top-row">
            <div style="border-radius:8px 0 0 0" class="neptune-host-service-dashboard-tab" data-tab="host"> 
                <div class="neptune-tab-title">
                    Host
                </div>
                <div class="neptune-tab-info">
                    ' . $total_records . ' total
                </div>
            </div>
            <div class="neptune-host-service-dashboard-tab border-b neptune-tab-selectable primary-foreground" data-tab="service">
                <div class="neptune-tab-title">
                    Service
                </div>
                <div class="neptune-tab-info">
                ' . $service_total_records . ' total
                </div>
            </div>
            <div class="neptune-host-service-dashboard-top-row-space">
            </div>
        </div>
        <div class="neptune-host-service-dashboard-bottom-row">
            <div id="host-content" class="neptune-tab-content">
                <div class="neptune-graph-container" id="neptune-graph-container">
                    <div class="neptune-graph-header">
                        <div class="status-dot-array" style="padding-left:20px;font-size:16px;">
                            <span class="status-dot-text mono-text"><span style="margin-right:6px" class="status-dot dot-8 hostup"></span>Up</span>
                            <span class="status-dot-text mono-text"><span style="margin-right:6px" class="status-dot dot-8 hostdown"></span>Down</span>
                            <span class="status-dot-text mono-text"><span style="margin-right:6px" class="status-dot dot-8 hostunreachable-dashboard"></span>Unreachable</span>
                            <span class="status-dot-text mono-text"><span style="margin-right:6px" class="status-dot dot-8 hostunhandled"></span>Unhandled</span>
                            <span class="status-dot-text mono-text"><span style="margin-right:6px" class="status-dot dot-8 hostproblem"></span>Problems</span>
                            <span class="status-dot-text mono-text"><span style="margin-right:6px" class="status-dot dot-8 hostpending-dashboard"></span>Pending</span>
                        </div>
                         <div class="neptune-graph-header-info">
                                <div class="neptune-graph-header-tabs">
                                    <div class="neptune-scale-tab neptune-scale-tab-active" data-scale="linear">Linear</div>
                                    <div class="neptune-scale-tab" data-scale="logarithmic">Logarithmic</div>
                                </div>
                        </div>
                    </div>
                    <div class="neptune-graph-body" id="neptune-graph-body"></div>
                </div>
                <div class="neptune-status-numerics">
                    ' . $host_icon_up . '
                    ' . $host_icon_down . '
                    ' . $host_icon_unreachable . '
                    ' . $host_icon_unhandled . '  
                    ' . $host_icon_problem . '
                    ' . $host_icon_pending . ' 
                </div>
            </div> 
            <div id="service-content" class="neptune-tab-content" style="display: none;">
            <div class="neptune-graph-container" id="neptune-service-graph-container">
            <div class="neptune-graph-header">
                <div class="status-dot-array" style="padding-left:20px;font-size:16px;">
                    <span class="status-dot-text mono-text"><span style="margin-right:6px" class="status-dot dot-8 hostup"></span>OK</span>
                    <span class="status-dot-text mono-text"><span style="margin-right:6px" class="status-dot dot-8 servicewarning-dashboard"></span>Warning</span>
                    <span class="status-dot-text mono-text"><span style="margin-right:6px" class="status-dot dot-8 servicecritical-dashboard"></span>Critical</span>
                    <span class="status-dot-text mono-text"><span style="margin-right:6px" class="status-dot dot-8 serviceunknown-dashboard"></span>Unknown</span>
                    <span class="status-dot-text mono-text"><span style="margin-right:6px" class="status-dot dot-8 serviceunhandled"></span>Unhandled</span>
                    <span class="status-dot-text mono-text"><span style="margin-right:6px" class="status-dot dot-8 serviceproblem"></span>Problem</span>
                    <span class="status-dot-text mono-text"><span style="margin-right:6px" class="status-dot dot-8 servicepending-dashboard"></span>Pending</span>
                </div>
                <div class="neptune-graph-header-info">
                        <div class="neptune-graph-header-tabs">
                            <div class="neptune-scale-tab neptune-scale-tab-active" data-scale="linear">Linear</div>
                            <div class="neptune-scale-tab" data-scale="logarithmic">Logarithmic</div>
                    </div>
            </div>
        </div>
        <div class="neptune-graph-body" id="neptune-service-graph-body"></div>
            </div>
                <div class="neptune-status-numerics-service">
                    ' . $service_icon_ok . '
                    ' . $service_icon_warning . '
                    ' . $service_icon_critical . '
                    ' . $service_icon_unknown . '
                    ' . $service_icon_unhandled . '  
                    ' . $service_icon_problem . '
                    ' . $service_icon_pending . '
                </div>
            </div>
        </div>
    </div>';

$javascript = '
    <script>
    $(document).ready(function() {

        var lastSelectedScale = {
            host: "linear",
            service: "linear"
        };
    
        // Updates UI based on the selected tab and applies the last selected scale
        function switchTab(tabName) {
            // Toggle active class for tabs
            $(".neptune-host-service-dashboard-tab")
                .addClass("border-b primary-foreground neptune-tab-selectable")
                .removeClass("active")
                .filter("[data-tab=\'" + tabName + "\']")
                .removeClass("border-b primary-foreground neptune-tab-selectable")
                .addClass("active");
    
            // Hide all tab contents and show the selected one
            $(".neptune-tab-content").hide();
            $("#" + tabName + "-content").show();
    
            // Set the active scale tab based on the last selection
            setActiveScaleTab(lastSelectedScale[tabName]);
    
            // Update the chart scale and reflow
            updateAndReflowChart(tabName);
        }
    
        // Sets the active class for the selected scale tab
        function setActiveScaleTab(scale) {
            $(\'.neptune-scale-tab\')
                .removeClass(\'neptune-scale-tab-active\')
                .filter(\'[data-scale="\' + scale + \'"]\')
                .addClass(\'neptune-scale-tab-active\');
        }
    
        // Updates chart scale and reflows chart for smooth resizing
        function updateAndReflowChart(tabName) {
            var chart = tabName === \'host\' ? window.hostChart : window.serviceChart;
            var scale = lastSelectedScale[tabName];
            var minVal = scale === \'logarithmic\' ? 1 : 0;
    
            if (chart) {
                chart.update({ yAxis: { type: scale, softMin: minVal } });
                setTimeout(function() { chart.reflow(); }, 0);
            }
        }
    
        // Event handler for tab switches
        $(".neptune-host-service-dashboard-tab").click(function() {
            var tabName = $(this).data("tab");
            switchTab(tabName);
        });
    
        // Event handler for scale tab changes
        $(\'.neptune-scale-tab\').on(\'click\', function() {
            var selectedScale = $(this).data(\'scale\');
            var tabName = $(\'#host-content\').is(\':visible\') ? \'host\' : \'service\';
    
            setActiveScaleTab(selectedScale);
            
            lastSelectedScale[tabName] = selectedScale; // Remember the selected scale
            updateAndReflowChart(tabName); // Update and reflow the chart
        });
    
        // Initialize the host tab on page load
        switchTab(\'host\');
    });
    </script>
    ';    

$host_status_summary_chart = '
<script>
$(document).ready(function() {
        Highcharts.setOptions({
            chart: {
                style: {
                    fontFamily: \'Geist\',
                }
            }
        });

        window.hostChart = Highcharts.chart(\'neptune-graph-body\', {
            chart: {
                type: \'column\',
                backgroundColor: \'var(--card)\',
                plotBackgroundColor: \'var(--card)\'
            },
            title: {
                text: \'\',
                style: {
                    "font-family": "\'Geist\'",
                    "font-size": 20,
                }
            },
            xAxis: {
                categories: [\'Up\', \'Down\', \'Unreachable\', \'Unhandled\', \'Problem\', \'Pending\'],
                labels: {
                    style: {
                        color: \'var(--muted-foreground)\',
                        fontWeight:500
                    }
                },
                lineColor: \'var(--border)\',
            },
            yAxis: {
                type: \'linear\', 
                softMin: 1, // Setting a soft minimum to avoid aggressive scaling at the lower end
                minTickInterval: 1,
                title: {
                    text: \'\',
                    style: {
                        color: \'var(--muted-foreground)\', // Title text color
                        fontSize: \'14px\', // Font size
                        fontFamily: \'Geist, sans-serif\',
                        fontWeight: 500,
                    },
                },
                labels: {
                    style: {
                        color: \'var(--muted-foreground)\',
                        fontWeight:300
                    }
                },
                gridLineColor: \'#1A2533\',
                gridLineDashStyle:\'dot\',
            },
            plotOptions: {
                column: {
                    colorByPoint: true,
                    borderWidth: 0,
                    borderRadius: 5,
                    cursor: "pointer", // Make the bars act as links
                    point: {
                        events: {
                            click: function () {
                                var urls = [
                                    "' . $baseurl . '&show=hosts&hoststatustypes=2&servicestatustypes=0",
                                    "' . $baseurl . '&show=hosts&hoststatustypes=4&servicestatustypes=0",
                                    "' . $baseurl . '&show=hosts&hoststatustypes=8&servicestatustypes=0",
                                    "' . $baseurl . '&show=hosts&servicestatustypes=0&hoststatustypes=12&hostattr=10",
                                    "' . $baseurl . '&show=hosts&hoststatustypes=12&servicestatustypes=0",
                                    "' . $baseurl . '&show=hosts&hoststatustypes=1&servicestatustypes=0"
                                ];
                                window.location.href = urls[this.index];
                            }
                        }
                    }
                },
                lineColor: \'var(--muted)\',
            },
            colors: [
                \'var(--md-ok)\', // Up 
                \'var(--md-critical)\', // Down 
                \'var(--md-unreachable)\', // Unreachable 
                \'var(--md-unhandled)\',    // Unhandled 
                \'var(--md-problem)\', // Problem 
                \'var(--md-pending)\',    // Pending 
            ],
            series: [{
                name: \'Hosts\',
                data: [' . $host_state_totals[0] . ', ' . $host_state_totals[1] . ', ' . $host_state_totals[2] . ', ' . $host_unhandled_problems . ', ' . $host_total_problems . ', ' . $host_state_totals[3] . '] ,
               showInLegend: false,
            }],
            exporting: {
                enabled: false
            },
             credits: {
                enabled: false
            },
            tooltip: {
                backgroundColor: \'var(--primary-foreground)\', // Change the background color of the tooltip
                borderColor: \'var(--border)\',
                borderWidth:1,
                style: {
                    color: \'var(--foreground)\', // Change the text color inside the tooltip
                    fontSize: \'14px\',
                },
            }
        });
    });
    </script>
    ';

    $service_status_summary_chart = '
<script>
$(document).ready(function() {
        Highcharts.setOptions({
            chart: {
                style: {
                    fontFamily: \'Geist\',
                }
            }
        });

        window.serviceChart = Highcharts.chart(\'neptune-service-graph-body\', {
            chart: {
                type: \'column\',
                backgroundColor: \'var(--card)\',
                plotBackgroundColor: \'var(--card)\'
            },
            title: {
                text: \'\',
                style: {
                    "font-family": "\'Geist\'",
                    "font-size": 20,
                }
            },
            xAxis: {
                categories: [\'OK\', \'Warning\', \'Critical\',  \'Unknown\', \'Unhandled\', \'Problems\', \'Pending\'],
                labels: {
                    style: {
                        color: \'var(--muted-foreground)\',
                        fontWeight:500
                    }
                },
                lineColor: \'var(--border)\',
            },
            yAxis: {
                type: \'linear\', 
                softMin: 1,
                minTickInterval: 1,
                title: {
                    text: \'\',
                    style: {
                        color: \'var(--muted-foreground)\', // Title text color
                        fontSize: \'14px\', // Font size
                        fontFamily: \'Geist, sans-serif\',
                        fontWeight: 500,
                    },
                },
                labels: {
                    style: {
                        color: \'var(--muted-foreground)\',
                        fontFamily: \'Geist, sans-serif\',
                        fontWeight: 300,
                    },
                },
                gridLineColor: \'#1A2533\',
                gridLineDashStyle:\'dot\',
            },
            plotOptions: {
                column: {
                    colorByPoint: true,
                    borderWidth: 0,
                    borderRadius: 5,
                    cursor: "pointer", // Make the bars act as links
                    point: {
                        events: {
                            click: function () {
                                var urls = [
                                    "' . $baseurl . '&show=services&hoststatustypes=0&servicestatustypes=2",
                                    "' . $baseurl . '&show=services&hoststatustypes=0&servicestatustypes=4",
                                    "' . $baseurl . '&show=services&hoststatustypes=0&servicestatustypes=16",
                                    "' . $baseurl . '&show=services&hoststatustypes=0&servicestatustypes=8",
                                    "' . $baseurl . '&show=services&hoststatustypes=0&servicestatustypes=28&serviceattr=10",
                                    "' . $baseurl . '&show=services&hoststatustypes=0&servicestatustypes=28",
                                    "' . $baseurl . '&show=services&hoststatustypes=0&servicestatustypes=1"
                                ];
                                window.location.href = urls[this.index];
                            }
                        }
                    }
                },
                lineColor: \'var(--border)\',

            },
            colors: [
                \'var(--md-ok)\', // Ok 
                \'var(--md-warning)\', // Warning
                \'var(--md-critical)\', // Critical
                \'var(--md-unknown)\', // Unknown 
                \'var(--md-unhandled)\', // Unhandled 
                \'var(--md-problem)\', // Problems 
                \'var(--md-pending)\' // Pending 
            ],
            series: [{
                name: \'Services\',
                data: [' . $service_state_totals[0] . ', ' . $service_state_totals[1] . ', ' . $service_state_totals[2] . ', ' . $service_state_totals[3] . ', '  . $service_unhandled_problems . ',' . $service_total_problems . ', ' . $service_state_totals[4] . '],
                showInLegend: false,
            }],
            exporting: {
                enabled: false
            },
             credits: {
                enabled: false
            },
            tooltip: {
                backgroundColor: \'var(--primary-foreground)\', // Change the background color of the tooltip
                borderColor: \'var(--border)\',
                borderWidth:1,
                style: {
                    color: \'var(--foreground)\', // Change the text color inside the tooltip
                    fontSize: \'14px\',
                },
            }
        });
    });
    </script>
    ';

    $output .= $javascript . $host_status_summary_chart . $service_status_summary_chart;

    return $output;
}


/**
 * Get the service status summary HTML
 *
 * @param   array   $args   Arguments
 * @return  string          HTML output
 */
function xicore_ajax_get_service_status_summary_html($args = null)
{
    $output = '';
    $host = grab_array_var($args, "host", "");
    $hostgroup = grab_array_var($args, "hostgroup", "");
    $servicegroup = grab_array_var($args, "servicegroup", "");
    $hoststatustypes = grab_array_var($args, "hoststatustypes", HOSTSTATE_ANY);

    if ($hostgroup == "all")
        $hostgroup = "";
    if ($servicegroup == "all")
        $servicegroup = "";
    if ($host == "all")
        $host = "";

    // Limit hosts by hostgroup or host
    $host_ids = array();
    if (!empty($hostgroup)) {
        $host_ids = get_hostgroup_member_ids($hostgroup);
    } else if (!empty($host)) {
        $host_ids[] = get_host_id($host);
    }

    // Limit service by servicegroup
    $service_ids = array();
    if (!empty($servicegroup)) {
        $service_ids = get_servicegroup_member_ids($servicegroup);
    }

    // PREP TO GET TOTAL RECORD COUNTS FROM BACKEND...

    $backendargs = array();
    $backendargs["cmd"] = "getservicestatus";
    $backendargs["limitrecords"] = false;
    $backendargs["totals"] = 1;
    $backendargs["combinedhost"] = true;

    // Host ID limiters
    if (!empty($host_ids)) {
        $backendargs["host_id"] = "in:" . implode(',', $host_ids);
    }

    // Service ID limiters
    if (!empty($service_ids)) {
        $backendargs["service_id"] = "in:" . implode(',', $service_ids);
    }

    // Get total services
    $xml = get_xml_service_status($backendargs);
    $total_records = 0;
    if ($xml) {
        $total_records = intval($xml->recordcount);
    }

    // Get state totals (ok/pending checked later)
    $state_totals = array();
    for ($x = 1; $x <= 3; $x++) {
        $backendargs["current_state"] = $x;
        $xml = get_xml_service_status($backendargs);
        $state_totals[$x] = 0;
        if ($xml) {
            $state_totals[$x] = intval($xml->recordcount);
        }
    }

    // Get ok (non-pending)
    $backendargs["current_state"] = 0;
    $backendargs["has_been_checked"] = 1;
    $xml = get_xml_service_status($backendargs);
    $state_totals[0] = 0;
    if ($xml) {
        $state_totals[0] = intval($xml->recordcount);
    }

    // Get pending
    $backendargs["current_state"] = 0;
    $backendargs["has_been_checked"] = 0;
    $xml = get_xml_service_status($backendargs);
    $state_totals[4] = 0;
    if ($xml) {
        $state_totals[4] = intval($xml->recordcount);
    }

    // Total problems
    $total_problems = $state_totals[1] + $state_totals[2] + $state_totals[3];

    // Unhandled problems
    $backendargs["current_state"] = "in:1,2,3";
    unset($backendargs["has_been_checked"]);
    $backendargs["problem_acknowledged"] = 0;
    $backendargs["scheduled_downtime_depth"] = 0;
    $xml = get_xml_service_status($backendargs);
    $unhandled_problems = 0;
    if ($xml) {
        $unhandled_problems = intval($xml->recordcount);
    }

    $output .= '<div class="infotable_title">' . _('Service Status Summary') . '</div>';

    $show = "services";

    // URLs
    $baseurl = get_base_url() . "includes/components/xicore/status.php?";
    if (!empty($hostgroup)) {
        $baseurl .= "&hostgroup=" . urlencode($hostgroup);
    }
    if (!empty($servicegroup)) {
        $baseurl .= "&servicegroup=" . urlencode($servicegroup);
    }
    if (!empty($host)) {
        $baseurl .= "&host=" . urlencode($host);
    }
    $state_text = array();

    $state_text[0] = "<div class='serviceok";
    if ($state_totals[0] > 0) {
        $state_text[0] .= " haveserviceok";
    }
    $state_text[0] .= "'>";
    $state_text[0] .= "<a class='hc-text' href='" . $baseurl . "&show=" . $show . "&hoststatustypes=" . $hoststatustypes . "&servicestatustypes=" . SERVICESTATE_OK . "'>" . $state_totals[0] . "</a>";
    $state_text[0] .= "</div>";

    $state_text[1] = "<div class='servicewarning";
    if ($state_totals[1] > 0) {
        $state_text[1] .= " haveservicewarning";
    }
    $state_text[1] .= "'>";
    $state_text[1] .= "<a class='hc-text' href='" . $baseurl . "&show=" . $show . "&hoststatustypes=" . $hoststatustypes . "&servicestatustypes=" . SERVICESTATE_WARNING . "'>" . $state_totals[1] . "</a>";
    $state_text[1] .= "</div>";

    $state_text[3] = "<div class='serviceunknown";
    if ($state_totals[3] > 0) {
        $state_text[3] .= " haveserviceunknown";
    }
    $state_text[3] .= "'>";
    $state_text[3] .= "<a class='hc-text' href='" . $baseurl . "&show=" . $show . "&hoststatustypes=" . $hoststatustypes . "&servicestatustypes=" . SERVICESTATE_UNKNOWN . "'>" . $state_totals[3] . "</a>";
    $state_text[3] .= "</div>";

    $state_text[2] = "<div class='servicecritical";
    if ($state_totals[2] > 0) {
        $state_text[2] .= " haveservicecritical";
    }
    $state_text[2] .= "'>";
    $state_text[2] .= "<a class='hc-text' href='" . $baseurl . "&show=" . $show . "&hoststatustypes=" . $hoststatustypes . "&servicestatustypes=" . SERVICESTATE_CRITICAL . "'>" . $state_totals[2] . "</a>";
    $state_text[2] .= "</div>";

    $state_text[4] = "<div class='servicepending";
    if ($state_totals[4] > 0) {
        $state_text[4] .= " haveservicepending";
    }
    $state_text[4] .= "'>";
    $state_text[4] .= "<a class='hc-text' href='" . $baseurl . "&show=" . $show . "&hoststatustypes=" . $hoststatustypes . "&servicestatustypes=" . SERVICESTATE_PENDING . "'>" . $state_totals[4] . "</a>";
    $state_text[4] .= "</div>";

    $unhandled_problems_text = "<div class='unhandledserviceproblems";
    if ($unhandled_problems > 0) {
        $unhandled_problems_text .= " haveunhandledserviceproblems";
    }
    $unhandled_problems_text .= "'>";
    $unhandled_problems_text .= "<a class='hc-text' href='" . $baseurl . "&show=" . $show . "&hoststatustypes=" . $hoststatustypes . "&servicestatustypes=" . (SERVICESTATE_WARNING | SERVICESTATE_UNKNOWN | SERVICESTATE_CRITICAL) . "&serviceattr=" . (SERVICESTATUSATTR_NOTACKNOWLEDGED | SERVICESTATUSATTR_NOTINDOWNTIME) . "'>" . $unhandled_problems . "</a>";
    $unhandled_problems_text .= "</div>";

    $total_problems_text = "<div class='serviceproblems";
    if ($total_problems > 0) {
        $total_problems_text .= " haveserviceproblems";
    }
    $total_problems_text .= "'>";
    $total_problems_text .= "<a class='hc-text' href='" . $baseurl . "&show=" . $show . "&hoststatustypes=" . $hoststatustypes . "&servicestatustypes=" . (SERVICESTATE_WARNING | SERVICESTATE_UNKNOWN | SERVICESTATE_CRITICAL) . "'>" . $total_problems . "</a>";
    $total_problems_text .= "</div>";

    $total_records_text = "<div class='allservices";
    if ($total_records > 0) {
        $total_records_text .= " haveallservices";
    }
    $total_records_text .= "'>";
    $total_records_text .= "<a class='hc-text' href='" . $baseurl . "&show=" . $show . "&hoststatustypes=" . $hoststatustypes . "'>" . $total_records . "</a>";
    $total_records_text .= "</div>";

    if (is_neptune()) {
        $host_icon_ok = '<span class="status-dot hostup dot-10" title=' . _('Ok') . '></span>';
        $host_icon_critical = '<span class="status-dot hostdown dot-10" title=' . _('Critical') . '></span>';
        $host_icon_unknown = '<span class="status-dot hostunknown dot-10" title=' . _('Unknown') . '></span>';
        $host_icon_warning = '<span class="status-dot hostwarning dot-10" title=' . _('Warning') . '></span>';
        $host_icon_pending = '<span class="status-dot hostpending dot-10" title=' . _('Pending') . '></span>';
        $host_icon_unhandled = '<span class="status-dot hostunknown dot-10" title=' . _('Unhandled') . '></span>';
        $host_icon_problems = '<span class="status-dot hostwarning dot-10" title=' . _('Problems') . '></span>';
        $host_icon_all = '<span class="status-dot hostpending dot-10" title=' . _('All') . '></span>';
        
        $output .= '
        <table class="table infotable-neptune table-condensed">
        <tbody>';
        $output .= '
        <tr><td>' . $host_icon_ok . _("Ok") . $state_text[0] . '</td><td>' . $host_icon_pending . _("Pending") . $state_text[4] . '</td></tr>';
        $output .= '
        <tr><td>' . $host_icon_warning . _("Warning") . $state_text[1] . '</td><td>' . $host_icon_problems . _("Problems") . $total_problems_text . '</td></tr>';
        $output .= '
        <tr><td>' . $host_icon_unknown . _("Unknown") . $state_text[3] . '</td><td>' . $host_icon_unhandled . _("Unhandled Problems") . $unhandled_problems_text . '</td></tr>';
        $output .= '
        <tr><td>' . $host_icon_critical . _("Critical") . $state_text[2] . '</td><td>' . $host_icon_all . _("All") . $total_records_text . '</td></tr>';
        $output .= '
        </tbody>
        </table>';
    }
    else {
        $output .= '
        <table class="infotable table table-condensed table-striped table-bordered">
        <thead>
        <tr><th>' . _("Ok") . '</th><th>' . _("Warning") . '</th><th>' . _("Unknown") . '</th><th>' . _("Critical") . '</th><th>' ._("Pending") . '</th></tr>
        </thead>
        ';

        $output .= '
        <tbody>
        <tr><td>' . $state_text[0] . '</td><td>' . $state_text[1] . '</td><td>' . $state_text[3] . '</td><td>' . $state_text[2] . '</td><td>' . $state_text[4] . '</td></tr>
        </tbody>
        ';

        $output .= '
        <thead>
        <tr><th colspan="2">' . _('Unhandled') . '</th><th colspan="2">' . _('Problems') . '</th><th>' . _('All') . '</th></tr>
        </thead>
        ';

        $output .= '
        <tbody>
        <tr><td colspan="2">' . $unhandled_problems_text . '</td><td colspan="2">' . $total_problems_text . '</td><td>' . $total_records_text . '</td></tr>
        </tbody>
        ';

        $output .= '</table>';
        }
        $output .= '<div class="ajax_date">' . _('Last Updated') . ': ' . get_datetime_string(time()) . '</div>';

    return $output;
}


/**
 * Get hostgroup status overview HTML
 *
 * @param   array   $args   Arguments
 * @return  string          HTML output
 */
function xicore_ajax_get_hostgroup_status_overview_html($args = null)
{
    global $cfg;
    $hostgroup = grab_array_var($args, "hostgroup");
    $hostgroup_alias = grab_array_var($args, "hostgroup_alias");
    $style = grab_array_var($args, "style");
    $output = '';
    $icons = '';

    $xistatus_url = get_base_url() . "includes/components/xicore/status.php";
    $icons .= "<div class='statusdetaillink'><a href='" . $xistatus_url . "?show=services&hostgroup=" . urlencode($hostgroup) . "'><i class='material-symbols-outlined md-18 md-400 md-button md-action tt-bind' title='" . _("View Hostgroup Service Details") . "'>description</i></a></div>";

    if ($args['mode'] == DASHLET_MODE_INBOARD) {
        $extinfo_url = get_base_url() . "includes/components/nagioscore/ui/extinfo.php";
        $icons .= "<div class='statusdetaillink'><a href='" . $extinfo_url . "?type=5&hostgroup=" . urlencode($hostgroup) . "'><i class='material-symbols-outlined md-18 md-400 md-button md-action tt-bind' title='" . _("Hostgroup Commands") . "'>terminal</i></a></div>";
    } else {
        $icons .= 
        '<div class="statusdetaillink group-dt-popup" data-name="' . encode_form_val($hostgroup) . '">
            <a><i class="material-symbols-outlined md-18 md-400 md-button md-action tt-bind" title="' . _("View Hostgroup Commands") . '">terminal</i></a>
        </div>';
    }

    if (!empty($cfg['reverse_hostgroup_alias']) && $cfg['reverse_hostgroup_alias'] == 1) {
        $output .= '<div class="infotable_title"><div class="infotable_title_text">' . $hostgroup . ' (' . $hostgroup_alias . ')</div><div class="infotable_title_icons">' . $icons . '</div></div>';
    } else {
        $output .= '<div class="infotable_title"><div class="infotable_title_text">' . $hostgroup_alias . ' (' . $hostgroup . ')</div><div class="infotable_title_icons">' . $icons . '</div></div>';
    }

    if (!is_neptune()) {
        $output .= "<table class='statustable hostgroup table table-condensed dashlettable-in table-striped table-bordered " . $style . "table'>\n";
    } else {
        $output .= "<table class='statustable hostgroup table table-condensed dashlettable-in " . $style . "table'>\n";
    }
    $output .= "<thead>\n";
    $output .= "<tr><th>" . _('Host') . "</th><th>" . _('Status') . "</th><th>" . _('Services') . "</th></tr>\n";
    $output .= "</thead>\n";

    // Limit hosts by hostgroup or host
    $host_ids = array();
    if (!empty($hostgroup)) {
        $host_ids = get_hostgroup_member_ids($hostgroup);
    } else if (!empty($host)) {
        $host_ids[] = get_host_id($host);
    }

    // GET HOST STATUS

    $backendargs = array();
    $backendargs["cmd"] = "gethoststatus";
    $backendargs["orderby"] = "host_name:a";

    // Host ID limiters
    if (!empty($host_ids)) {
        $backendargs["host_id"] = "in:" . implode(',', $host_ids);
    }
    $xml = get_xml_host_status($backendargs);

    $current_host = 0;
    if ($xml) {
        $last_host = "";

        foreach ($xml->hoststatus as $x) {

            $this_host = strval($x->name);
            $address = strval($x->address);
            $host_name = $this_host;

            if ($this_host != $last_host) {

                $current_host++;

                // Finish the row for the previous host
                if ($current_host > 1) {

                    // GET SERVICE STATUS

                    $backendargs = array();
                    $backendargs["cmd"] = "getservicestatus";
                    $backendargs["orderby"] = "host_name:a";
                    $backendargs["host_name"] = $last_host;
                    $xmls = get_xml_service_status($backendargs);

                    // Initialize service state counts
                    $services_ok = 0;
                    $services_warning = 0;
                    $services_unknown = 0;
                    $services_critical = 0;

                    // Get service state counts
                    $current_service = 0;
                    if ($xmls) {
                        foreach ($xmls->servicestatus as $xs) {
                            $current_service++;
                            $service_current_state = intval($xs->current_state);
                            switch ($service_current_state) {
                                case 0:
                                    $services_ok++;
                                    break;
                                case 1:
                                    $services_warning++;
                                    break;
                                case 2:
                                    $services_critical++;
                                    break;
                                case 3:
                                    $services_unknown++;
                                    break;
                                default:
                                    break;
                            }
                        }
                    }

                    $base_url = get_base_url() . "includes/components/xicore/status.php?&show=services&host=" . urlencode($last_host) . "&servicestatustypes=";

                    $services_cell = "";

                    if (!is_neptune()) {
                        if ($services_ok > 0)
                            $services_cell .= "<div class='serviceok'><a href='" . $base_url . SERVICESTATE_OK . "'>" . $services_ok . " " . _("Ok") . "</a></div>";
                        if ($services_warning > 0)
                            $services_cell .= "<div class='servicewarning'><a href='" . $base_url . SERVICESTATE_WARNING . "'>" . $services_warning . " " . _("Warning") . "</a></div>";
                        if ($services_unknown > 0)
                            $services_cell .= "<div class='serviceunknown'><a href='" . $base_url . SERVICESTATE_UNKNOWN . "'>" . $services_unknown . " " . _("Unknown") . "</a></div>";
                        if ($services_critical > 0)
                            $services_cell .= "<div class='servicecritical'><a href='" . $base_url . SERVICESTATE_CRITICAL . "'>" . $services_critical . " " . _("Critical") . "</a></div>";
                     } else {
                        if ($services_ok > 0)
                            $services_cell .= "<div><span class='status-dot dot-10 serviceok'></span><a class='hc-text' href='" . $base_url . SERVICESTATE_OK . "'>" . $services_ok . " " . _("Ok") . "</a></div>";
                        if ($services_warning > 0)
                            $services_cell .= "<div><span class='status-dot dot-10 servicewarning'></span><a class='hc-text' href='" . $base_url . SERVICESTATE_WARNING . "'>" . $services_warning . " " . _("Warning") . "</a></div>";
                        if ($services_unknown > 0)
                            $services_cell .= "<div><span class='status-dot dot-10 serviceunknown'></span><a class='hc-text' href='" . $base_url . SERVICESTATE_UNKNOWN . "'>" . $services_unknown . " " . _("Unknown") . "</a></div>";
                        if ($services_critical > 0)
                            $services_cell .= "<div><span class='status-dot dot-10 servicecritical'></span><a class='hc-text' href='" . $base_url . SERVICESTATE_CRITICAL . "'>" . $services_critical . " " . _("Critical") . "</a></div>";
                    }

                    if ($current_service == 0) {
                        $services_cell .= _("No services found");
                    }

                    $output .= "<td>" . $services_cell . "</td>";
                    $output .= "</tr>\n";
                }

                $last_host = $this_host;

                // Start a new host row......

                if (($current_host % 2) == 0) {
                    $rowclass = "even";
                } else {
                    $rowclass = "odd";
                }

                // Host status 
                $host_current_state = intval($x->current_state);
                switch ($host_current_state) {
                    case 0:
                        $status_string = _("Up");
                        $host_status_class = "hostup";
                        $host_row_class = "hostup";
                        break;
                    case 1:
                        $status_string = _("Down");
                        $host_status_class = "hostdown";
                        $host_row_class = "hostdown";
                        break;
                    case 2:
                        $status_string = _("Unreachable");
                        $host_status_class = "hostunreachable";
                        $host_row_class = "hostunreachable";
                        break;
                    default:
                        $status_string = "";
                        $host_status_class = "";
                        $host_row_class = "";
                        break;
                }

                $host_name_cell = "";
                $host_icons = "";
                
                // Downtime
                if (intval($x->scheduled_downtime_depth) > 0) {
                    $host_icons .= get_host_status_note_image("downtime.png", _("This host is in scheduled downtime"));
                }
                $host_icons .= get_object_icon_html($x->icon_image, $x->icon_image_alt);

                $host_name_cell .= "<div><a class='hc-text' href='" . get_host_status_detail_link($host_name) . "' title='" . $address . "'>";
                $host_name_cell .= "<div class='hostname'>" . $host_name . "</div>";
                $host_name_cell .= "<div class='hosticons'>";
                $host_name_cell .= $host_icons;

                // Service details link
                $url = get_base_url() . "includes/components/xicore/status.php?show=services&host=".urlencode($host_name);
                $alttext = _("View service status details for this host");

                $host_name_cell .= "<a class='hc-text' href='" . $url . "'><i class='material-symbols-outlined md-button md-middle md-18 md-400 tt-bind' title='" . $alttext . "'>description</i></a>";
                $host_name_cell .= "</div>";
                $host_name_cell .= "</a></div>";

                if (is_neptune()) { $host_row_class = ''; }
                $output .= "<tr class='" . $rowclass . " " . $host_row_class . "'>";
                $output .= "<td style='min-width:150px'>" . $host_name_cell . "</td>";
                if (is_neptune()){
                    $output .= '<td class="text-foreground"> <span class="status-dot ' . $host_status_class . ' dot-10"></span>' . $status_string . '</td>';
                } else {
                    $output .= "<td class='" . $host_status_class . "'>" . $status_string . "</td>";
                }
            }

        }

        // Finish the last host row
        if ($current_host > 0) {

            // GET SERVICE STATUS

            $backendargs = array();
            $backendargs["cmd"] = "getservicestatus";
            $backendargs["orderby"] = "host_name:a";
            $backendargs["host_name"] = $last_host;
            $xmls = get_xml_service_status($backendargs);

            // Initialize service state counts
            $services_ok = 0;
            $services_warning = 0;
            $services_unknown = 0;
            $services_critical = 0;

            // Get service state counts
            $current_service = 0;
            if ($xmls) {
                foreach ($xmls->servicestatus as $xs) {
                    $current_service++;
                    $service_current_state = intval($xs->current_state);
                    switch ($service_current_state) {
                        case 0:
                            $services_ok++;
                            break;
                        case 1:
                            $services_warning++;
                            break;
                        case 2:
                            $services_critical++;
                            break;
                        case 3:
                            $services_unknown++;
                            break;
                        default:
                            break;
                    }
                }
            }

            $base_url = get_base_url() . "includes/components/xicore/status.php?&show=services&host=" . urlencode($last_host) . "&servicestatustypes=";

            $services_cell = "";
            if(is_neptune()){
                if ($services_ok > 0)
                $services_cell .= "<div><span class='status-dot dot-10 serviceok'></span><a class='hc-text' href='" . $base_url . SERVICESTATE_OK . "'>" . $services_ok . " " . _("Ok") . "</a></div>";
            if ($services_warning > 0)
                $services_cell .= "<div><span class='status-dot dot-10 servicewarning'></span><a class='hc-text' href='" . $base_url . SERVICESTATE_WARNING . "'>" . $services_warning . " " . _("Warning") . "</a></div>";
            if ($services_unknown > 0)
                $services_cell .= "<div><span class='status-dot dot-10 serviceunknown'></span><a class='hc-text' href='" . $base_url . SERVICESTATE_UNKNOWN . "'>" . $services_unknown . " " . _("Unknown") . "</a></div>";
            if ($services_critical > 0)
                $services_cell .= "<div><span class='status-dot dot-10 servicecritical'></span><a class='hc-text' href='" . $base_url . SERVICESTATE_CRITICAL . "'>" . $services_critical . " " . _("Critical") . "</a></div>"; 
            } else {
            if ($services_ok > 0)
                $services_cell .= "<div class='serviceok'><a class='hc-text' href='" . $base_url . SERVICESTATE_OK . "'>" . $services_ok . " " . _("Ok") . "</a></div>";
            if ($services_warning > 0)
                $services_cell .= "<div class='servicewarning'><a class='hc-text' href='" . $base_url . SERVICESTATE_WARNING . "'>" . $services_warning . " " . _("Warning") . "</a></div>";
            if ($services_unknown > 0)
                $services_cell .= "<div class='serviceunknown'><a class='hc-text' href='" . $base_url . SERVICESTATE_UNKNOWN . "'>" . $services_unknown . " " . _("Unknown") . "</a></div>";
            if ($services_critical > 0)
                $services_cell .= "<div class='servicecritical'><a class='hc-text' href='" . $base_url . SERVICESTATE_CRITICAL . "'>" . $services_critical . " " . _("Critical") . "</a></div>";
            }

            if ($current_service == 0)
                $services_cell .= "No services found";

            $output .= "<td>" . $services_cell . "</td>";
            $output .= "</tr>\n";
        }

    }

    // No services/hosts found
    if ($current_host == 0) {
        $output .= "<tr><td colspan='3'>" . _('No status information found') . ".</td></tr>";
    }

    $output .= "</table>";
    $output .= '<div class="ajax_date">' . _('Last Updated') . ': ' . get_datetime_string(time()) . '</div>';

    return $output;
}


/**
 * Get HTML for hostgroup status grid layout
 *
 * @param   array   $args   Arguments
 * @return  string          HTML output
 */
function xicore_ajax_get_hostgroup_status_grid_html($args = null)
{
    global $cfg;

    $hostgroup = grab_array_var($args, "hostgroup");
    $hostgroup_alias = grab_array_var($args, "hostgroup_alias");
    $style = grab_array_var($args, "style");

    $output = '';
    $icons = "";

    $xistatus_url = get_base_url() . "includes/components/xicore/status.php";
    $icons .= "<div class='statusdetaillink'><a class='hc-text' href='" . $xistatus_url . "?show=services&hostgroup=" . urlencode($hostgroup) . "'><i class='material-symbols-outlined md-18 md-400 md-button md-action tt-bind' title='" . _("View Hostgroup Service Details") . "'>description</i></a></div>";

    if ($args['mode'] == DASHLET_MODE_INBOARD) {
        $extinfo_url = get_base_url() . "includes/components/nagioscore/ui/extinfo.php";
        $icons .= "<div class='statusdetaillink'><a class='hc-text'  href='" . $extinfo_url . "?type=5&hostgroup=" . urlencode($hostgroup) . "'><i class='material-symbols-outlined md-18 md-400 md-button md-action tt-bind' title='" . _("Hostgroup Commands") . "'>terminal</i></a></div>";
    } else {
        $icons .= 
        '<div class="statusdetaillink group-dt-popup" data-name="' . encode_form_val($hostgroup) . '">
            <a><i class="material-symbols-outlined md-18 md-400 md-button md-action tt-bind" title="' . _("View Hostgroup Commands") . '">terminal</i></a>
        </div>';
    }

    if (!empty($cfg['reverse_hostgroup_alias']) && $cfg['reverse_hostgroup_alias'] == 1) {
        $output .= '<div class="infotable_title"><div class="infotable_title_text">' . $hostgroup . ' (' . $hostgroup_alias . ')</div><div class="infotable_title_icons">' . $icons . '</div></div>';
    } else {
        $output .= '<div class="infotable_title"><div class="infotable_title_text">' . $hostgroup_alias . ' (' . $hostgroup . ')</div><div class="infotable_title_icons">' . $icons . '</div></div>';
    }

    $output .= "<table class='statustable hostgroup table table-condensed table-striped table-bordered dashlettable-in " . $style . "table'>\n";
    $output .= "<thead>\n";
    $output .= "<tr><th>" . _("Host") . "</th><th>" . _("Status") . "</th><th>" . _("Services") . "</th></tr>\n";
    $output .= "</thead>\n";

    // Limit hosts by hostgroup or host
    $host_ids = array();
    if (!empty($hostgroup)) {
        $host_ids = get_hostgroup_member_ids($hostgroup);
    } else if (!empty($host)) {
        $host_ids[] = get_host_id($host);
    }

    // GET HOST STATUS

    $backendargs = array();
    $backendargs["cmd"] = "gethoststatus";
    $backendargs["orderby"] = "host_name:a";

    // Host ID limiters
    if (!empty($host_ids)) {
        $backendargs["host_id"] = "in:" . implode(',', $host_ids);
    }
    $xml = get_xml_host_status($backendargs);

    $current_host = 0;
    if ($xml) {

        $last_host = "";
        $services_cell = "";

        foreach ($xml->hoststatus as $x) {

            $this_host = strval($x->name);
            $host_name = $this_host;
            $address = strval($x->address);

            if ($this_host != $last_host) {

                $current_host++;

                // Finish the row for the previous host
                if ($current_host > 1) {
                    $output .= "<td>" . $services_cell . "</td>";
                    $output .= "</tr>\n";
                }

                // GET SERVICE STATUS

                $backendargs = array();
                $backendargs["cmd"] = "getservicestatus";
                $backendargs["orderby"] = "host_name:a,service_description:a";
                $backendargs["host_name"] = $this_host;
                $xmls = get_xml_service_status($backendargs);

                // Initialize service state info
                $services_cell = "";

                // Get service state info
                $current_service = 0;
                if ($xmls) {
                    foreach ($xmls->servicestatus as $xs) {
                        $current_service++;
                        $service_current_state = intval($xs->current_state);
                        switch ($service_current_state) {
                            case 0:
                                $status_class = "serviceok";
                                break;
                            case 1:
                                $status_class = "servicewarning";
                                break;
                            case 2:
                                $status_class = "servicecritical";
                                break;
                            case 3:
                                $status_class = "serviceunknown";
                                break;
                            default:
                                break;
                        }

                        $services_cell .= "<div class='inlinestatus " . $status_class . "'><a class='hc-text' href='" . get_service_status_detail_link($x->name, $xs->name) . "'>" . $xs->name . "</a></div>";
                    }
                }

                if ($current_service == 0) {
                    $services_cell = _("No services found");
                }

                $last_host = $this_host;

                if (($current_host % 2) == 0) {
                    $rowclass = "even";
                } else {
                    $rowclass = "odd";
                }

                // Host status 
                $host_current_state = intval($x->current_state);
                switch ($host_current_state) {
                    case 0:
                        $status_string = _("Up");
                        $host_status_class = "hostup";
                        $host_row_class = "hostup";
                        break;
                    case 1:
                        $status_string = _("Down");
                        $host_status_class = "hostdown";
                        $host_row_class = "hostdown";
                        break;
                    case 2:
                        $status_string = _("Unreachable");
                        $host_status_class = "hostunreachable";
                        $host_row_class = "hostunreachable";
                        break;
                    default:
                        $status_string = "";
                        $host_status_class = "";
                        $host_row_class = "";
                        break;
                }

                $host_name_cell = "";
                $host_icons = "";
                
                // Downtime
                if (intval($x->scheduled_downtime_depth) > 0) {
                    $host_icons .= get_host_status_note_image("downtime.png", _("This host is in scheduled downtime"));
                }
                
                $host_icons .= get_object_icon_html($x->icon_image, $x->icon_image_alt);
                                
                $host_name_cell .= "<div><a class='hc-text' href='" . get_host_status_detail_link($host_name) . "' title='" . $address . "'>";
                $host_name_cell .= "<div class='hostname'>" . $host_name . "</div>";
                $host_name_cell .= "<div class='hosticons'>";
                $host_name_cell .= $host_icons;

                // Service details link
                $url = get_base_url() . "includes/components/xicore/status.php?show=services&host=".urlencode($host_name);
                $alttext = _("View service status details for this host");
                if(is_neptune()){
                    $host_name_cell .= "<a href='" . $url . "'><i class='material-symbols-outlined md-button md-middle md-18 md-400 tt-bind' title='" . $alttext . "'>description</i></a>";
                } else {
                    $host_name_cell .= "<a href='" . $url . "'><i class='material-symbols-outlined md-button md-middle md-18 md-400 tt-bind' title='" . $alttext . "'>description</i></a>";
                }
                $host_name_cell .= "</div>";
                $host_name_cell .= "</a></div>";

                if (is_neptune()) { $host_row_class = ''; }
                $output .= "<tr class='" . $rowclass . " " . $host_row_class . "'>";
                $output .= "<td style='height:5.5vh; width:150px;'>" . $host_name_cell . "</td>";
                if (is_neptune()){
                    $output .= '<td class="text-foreground"> <span class="status-dot ' . $host_status_class . ' dot-10"></span>' . $status_string . '</td>';
                } else {
                    $output .= "<td class='" . $host_status_class . "'>" . $status_string . "</td>";
                }
            }

        }

        // Finish the last host row
        if ($current_host > 0) {
            $output .= "<td>" . $services_cell . "</td>";
            $output .= "</tr>\n";
        }

    }

    // No services/hosts found
    if ($current_host == 0) {
        $output .= "<tr><td colspan='3'>" . _("No status information found.") . "</td></tr>";
    }

    $output .= "</table>";
    $output .= '<div class="ajax_date">' . _('Last Updated') . ': ' . get_datetime_string(time()) . '</div>';

    return $output;
}


/**
 * Get service group status overview HTML
 *
 * @param   array   $args   Arguments
 * @return  string          HTML output
 */
function xicore_ajax_get_servicegroup_status_overview_html($args = null)
{
    global $cfg;

    $servicegroup = grab_array_var($args, "servicegroup", "all");
    $servicegroup_alias = grab_array_var($args, "servicegroup_alias");
    $style = grab_array_var($args, "style");

    $output = '';
    $icons = "";

    $xistatus_url = get_base_url() . "includes/components/xicore/status.php";
    $icons .= "<div class='statusdetaillink'><a href='" . $xistatus_url . "?show=services&servicegroup=" . urlencode($servicegroup) . "'><span class='material-symbols-outlined md-button md-middle md-18 md-400 md-action tt-bind' title='" . _('View Servicegroup Service Details') . "'>description</span></a></div>";

    if ($args['mode'] == DASHLET_MODE_INBOARD) {
        $extinfo_url = get_base_url() . "includes/components/nagioscore/ui/extinfo.php";
        $icons .= "<div class='statusdetaillink'><a href='" . $extinfo_url . "?type=8&servicegroup=" . urlencode($servicegroup) . "'><i class='material-symbols-outlined md-button md-middle md-18 md-400 md-action tt-bind' title='" . _("Servicegroup Commands") . "'>terminal</i></a></div>";
    } else {
        $icons .= 
        '<div class="statusdetaillink group-dt-popup" data-name="' . encode_form_val($servicegroup) . '">
            <a><i class="material-symbols-outlined md-18 md-400 md-button md-middle tt-bind" title="' . _("View Servicegroup Commands") . '">terminal</i></a>
        </div>';
    }

    if (!empty($cfg['reverse_servicegroup_alias']) && $cfg['reverse_servicegroup_alias'] == 1) {
        $output .= '<div class="infotable_title"><div class="infotable_title_text">' . $servicegroup . ' (' . $servicegroup_alias . ')</div><div class="infotable_title_icons">' . $icons . '</div></div>';
    } else {
        $output .= '<div class="infotable_title"><div class="infotable_title_text">' . $servicegroup_alias . ' (' . $servicegroup . ')</div><div class="infotable_title_icons">' . $icons . '</div></div>';
    }

    if (!is_neptune()) {
        $output .= "<table class='statustable servicegroup table table-condensed table-striped table-bordered dashlettable-in " . $style . "table'>\n";
    } else {
        $output .= "<table class='statustable servicegroup table table-condensed dashlettable-in " . $style . "table'>\n";
    }
    $output .= "<thead>\n";
    $output .= "<tr><th>" . _('Host') . "</th><th>" . _('Status') . "</th><th>" . _('Services') . "</th></tr>\n";
    $output .= "</thead>\n";

    // Limit service by servicegroup
    $service_ids = array();
    if (!empty($servicegroup)) {
        $service_ids = get_servicegroup_member_ids($servicegroup);
    }

    // GET STATUS

    $backendargs = array();
    $backendargs["cmd"] = "getservicestatus";
    $backendargs["combinedhost"] = true;
    $backendargs["orderby"] = "host_name:a";

    // Service ID limiters
    if (!empty($service_ids)) {
        $backendargs["service_id"] = "in:" . implode(',', $service_ids);
    }
    $xml = get_xml_service_status($backendargs);

    $current_host = 0;
    if ($xml) {

        $last_host = "";
        $services_ok = 0;
        $services_warning = 0;
        $services_critical = 0;
        $services_unknown = 0;

        foreach ($xml->servicestatus as $x) {

            $this_host = strval($x->host_name);
            $host_name = $this_host;
            $address = strval($x->host_address);

            if ($this_host != $last_host) {

                $current_host++;

                // Finish the last host row
                if ($current_host > 1) {

                    $base_url = get_base_url() . "includes/components/xicore/status.php?&show=services&host=" . urlencode($last_host) . "&servicegroup=" . urlencode($servicegroup) . "&servicestatustypes=";

                    $services_cell = "";
                    if (is_neptune()) {
                        if ($services_ok > 0)
                            $services_cell .= "<div><a href='" . $base_url . SERVICESTATE_OK . "'><span class='status-dot hostup dot-10'></span>" . $services_ok . " " . _("Ok") . "</a></div>";
                        if ($services_warning > 0)
                            $services_cell .= "<div><a href='" . $base_url . SERVICESTATE_WARNING . "'><span class='status-dot hostwarning dot-10'></span>" . $services_warning . " " . _("Warning") . "</a></div>";
                        if ($services_unknown > 0)
                            $services_cell .= "<div><a href='" . $base_url . SERVICESTATE_UNKNOWN . "'><span class='status-dot hostunknown dot-10'></span>" . $services_unknown . " " . _("Unknown") . "</a></div>";
                        if ($services_critical > 0)
                            $services_cell .= "<div><a href='" . $base_url . SERVICESTATE_CRITICAL . "'><span class='status-dot hostdown dot-10'></span>" . $services_critical . " " . _("Critical") . "</a></div>";
                        }
                    else {
                        if ($services_ok > 0)
                            $services_cell .= "<div class='serviceok'><a href='" . $base_url . SERVICESTATE_OK . "'>" . $services_ok . " " . _("Ok") . "</a></div>";
                        if ($services_warning > 0)
                            $services_cell .= "<div class='servicewarning'><a href='" . $base_url . SERVICESTATE_WARNING . "'>" . $services_warning . " " . _("Warning") . "</a></div>";
                        if ($services_unknown > 0)
                            $services_cell .= "<div class='serviceunknown'><a href='" . $base_url . SERVICESTATE_UNKNOWN . "'>" . $services_unknown . " " . _("Unknown") . "</a></div>";
                        if ($services_critical > 0)
                            $services_cell .= "<div class='servicecritical'><a href='" . $base_url . SERVICESTATE_CRITICAL . "'>" . $services_critical . " " . _("Critical") . "</a></div>";
                    }
                    $output .= "<td>" . $services_cell . "</td>";
                    $output .= "</tr>\n";
                }

                $last_host = $this_host;
                $services_ok = 0;
                $services_warning = 0;
                $services_critical = 0;
                $services_unknown = 0;

                if (($current_host % 2) == 0) {
                    $rowclass = "even";
                } else {
                    $rowclass = "odd";
                }

                // Host status 
                $host_current_state = intval($x->host_current_state);
                switch ($host_current_state) {
                    case 0:
                        $status_string = _("Up");
                        $host_status_class = "hostup";
                        $host_row_class = "hostup";
                        break;
                    case 1:
                        $status_string = _("Down");
                        $host_status_class = "hostdown";
                        $host_row_class = "hostdown";
                        break;
                    case 2:
                        $status_string = _("Unreachable");
                        $host_status_class = "hostunreachable";
                        $host_row_class = "hostunreachable";
                        break;
                    default:
                        $status_string = "";
                        $host_status_class = "";
                        $host_row_class = "";
                        break;
                }

                $host_name_cell = "";
                $host_icons = "";
                // Downtime
                if (intval($x->scheduled_downtime_depth) > 0) {
                    $host_icons .= get_host_status_note_image("downtime.png", _("This host is in scheduled downtime"));
                }
                $host_icons .= get_object_icon_html($x->host_icon_image, $x->host_icon_image_alt);

                $host_name_cell .= "<div><a href='" . get_host_status_detail_link($host_name) . "' title='" . $address . "'>";
                $host_name_cell .= "<div class='hostname'>" . $host_name . "</div>";
                $host_name_cell .= "<div class='hosticons'>";
                $host_name_cell .= $host_icons;

                // Service details link
                $url = get_base_url() . "includes/components/xicore/status.php?show=services&host=".urlencode($host_name);
                $alttext = _("View service status details for this host");
                $host_name_cell .= "<a href='" . $url . "'><i class='material-symbols-outlined md-button md-18 md-400 md-middle tt-bind' title='" . $alttext . "'>description</i></a>";
                $host_name_cell .= "</div>";
                $host_name_cell .= "</a></div>";

                if (is_neptune()) { $host_row_class = ''; }
                $output .= "<tr class='" . $rowclass . " " . $host_row_class . "'>";
                $output .= "<td>" . $host_name_cell . "</td>";
                if (is_neptune()){
                    $output .= '<td class="text-foreground"> <span class="status-dot ' . $host_status_class . ' dot-10"></span>' . $status_string . '</td>';
                } else {
                    $output .= "<td class='" . $host_status_class . "'>" . $status_string . "</td>";
                }
            }

            // Adjust service status totals for current host
            $service_current_state = intval($x->current_state);
            switch ($service_current_state) {
                case 0:
                    $services_ok++;
                    break;
                case 1:
                    $services_warning++;
                    break;
                case 2:
                    $services_critical++;
                    break;
                case 3:
                    $services_unknown++;
                    break;
                default:
                    break;
            }

        }

        // Finish the last host row
        if ($current_host > 0) {

            $base_url = get_base_url() . "includes/components/xicore/status.php?&show=services&host=" . urlencode($last_host) . "&servicegroup=" . urlencode($servicegroup) . "&servicestatustypes=";

            $services_cell = "";
            if (is_neptune()) {
                if ($services_ok > 0)
                    $services_cell .= "<div><a href='" . $base_url . SERVICESTATE_OK . "'><span class='status-dot hostup dot-10'></span>" . $services_ok . " " . _("Ok") . "</a></div>";
                if ($services_warning > 0)
                    $services_cell .= "<div><a href='" . $base_url . SERVICESTATE_WARNING . "'><span class='status-dot hostwarning dot-10'></span>" . $services_warning . " " . _("Warning") . "</a></div>";
                if ($services_unknown > 0)
                    $services_cell .= "<div><a href='" . $base_url . SERVICESTATE_UNKNOWN . "'><span class='status-dot hostunknown dot-10'></span>" . $services_unknown . " " . _("Unknown") . "</a></div>";
                if ($services_critical > 0)
                    $services_cell .= "<div><a href='" . $base_url . SERVICESTATE_CRITICAL . "'><span class='status-dot hostdown dot-10'></span>" . $services_critical . " " . _("Critical") . "</a></div>";
            }
            else {
                if ($services_ok > 0)
                    $services_cell .= "<div class='serviceok'><a href='" . $base_url . SERVICESTATE_OK . "'>" . $services_ok . " " . _("Ok") . "</a></div>";
                if ($services_warning > 0)
                    $services_cell .= "<div class='servicewarning'><a href='" . $base_url . SERVICESTATE_WARNING . "'>" . $services_warning . " " . _("Warning") . "</a></div>";
                if ($services_unknown > 0)
                    $services_cell .= "<div class='serviceunknown'><a href='" . $base_url . SERVICESTATE_UNKNOWN . "'>" . $services_unknown . " " . _("Unknown") . "</a></div>";
                if ($services_critical > 0)
                    $services_cell .= "<div class='servicecritical'><a href='" . $base_url . SERVICESTATE_CRITICAL . "'>" . $services_critical . " " . _("Critical") . "</a></div>";
            }

            $output .= "<td>" . $services_cell . "</td>";
            $output .= "</tr>\n";
        }

    }

    // No services/hosts found
    if ($current_host == 0) {
        $output .= "<tr><td colspan='3'>" . _("No status information found.") . "</td></tr>";
    }

    $output .= "</table>";
    $output .= '<div class="ajax_date">' . _('Last Updated') . ': ' . get_datetime_string(time()) . '</div>';

    return $output;
}


/**
 * Get service group status HTML grid layout
 *
 * @param   array   $args   Arguments
 * @return  string          HTML output
 */
function xicore_ajax_get_servicegroup_status_grid_html($args = null)
{
    global $cfg;

    $servicegroup = grab_array_var($args, "servicegroup");
    $servicegroup_alias = grab_array_var($args, "servicegroup_alias");
    $style = grab_array_var($args, "style");

    $output = '';
    $icons = '';

    $xistatus_url = get_base_url() . "includes/components/xicore/status.php";
    $icons .= "<div class='statusdetaillink'><a href='" . $xistatus_url . "?show=services&servicegroup=" . urlencode($servicegroup) . "'><span class='material-symbols-outlined md-button md-middle md-18 md-400 tt-bind' title='" . _('View Servicegroup Service Details') . "'>description</span></a></div>";

    if ($args['mode'] != DASHLET_MODE_INBOARD) {
        $extinfo_url = get_base_url() . "includes/components/nagioscore/ui/extinfo.php";
        $icons .= "<div class='statusdetaillink'><a href='" . $extinfo_url . "?type=8&servicegroup=" . urlencode($servicegroup) . "'><i class='material-symbols-outlined md-button md-middle md-18 md-400 tt-bind' title='" . _("Servicegroup Commands") . "'>terminal</i></a></div>";
    } else {
        $icons .= 
        '<div class="statusdetaillink group-dt-popup" data-name="' . encode_form_val($servicegroup) . '">
            <a><i class="material-symbols-outlined md-18 md-400 md-button md-middle tt-bind" title="' . _("View Servicegroup Commands") . '">terminal</i></a>
        </div>';
    }

    if (!empty($cfg['reverse_servicegroup_alias']) && $cfg['reverse_servicegroup_alias'] == 1) {
        $output .= '<div class="infotable_title"><div class="infotable_title_text">' . $servicegroup . ' (' . $servicegroup_alias . ')</div><div class="infotable_title_icons">' . $icons . '</div></div>';
    } else {
        $output .= '<div class="infotable_title"><div class="infotable_title_text">' . $servicegroup_alias . ' (' . $servicegroup . ')</div><div class="infotable_title_icons">' . $icons . '</div></div>';
    }

    $output .= "<table class='statustable servicegroup table table-condensed table-striped table-bordered dashlettable-in " . $style . "table'>\n";
    $output .= "<thead>\n";
    $output .= "<tr><th>" . _("Host") . "</th><th>" . _("Status") . "</th><th>" . _("Services") . "</th></tr>\n";
    $output .= "</thead>\n";

    // Limit service by servicegroup
    $service_ids = array();
    if (!empty($servicegroup)) {
        $service_ids = get_servicegroup_member_ids($servicegroup);
    }

    // GET STATUS

    $backendargs = array();
    $backendargs["cmd"] = "getservicestatus";
    $backendargs["combinedhost"] = true;
    $backendargs["orderby"] = "host_name:a,service_description:a";

    // Service ID limiters
    if (!empty($service_ids)) {
        $backendargs["service_id"] = "in:" . implode(',', $service_ids);
    }
    $xml = get_xml_service_status($backendargs);

    $current_host = 0;
    if ($xml) {

        $last_host = "";
        $services_cell = "";

        foreach ($xml->servicestatus as $x) {

            $this_host = strval($x->host_name);
            $host_name = $this_host;
            $address = strval($x->host_address);

            if ($this_host != $last_host) {

                $current_host++;

                // Finish the last host row
                if ($current_host > 1) {
                    $output .= "<td>" . $services_cell . "</td>";
                    $output .= "</tr>\n";
                }

                // Initialize service state info
                $services_cell = "";
                $last_host = $this_host;

                if (($current_host % 2) == 0) {
                    $rowclass = "even";
                } else {
                    $rowclass = "odd";
                }

                // Host status 
                $host_current_state = intval($x->host_current_state);
                switch ($host_current_state) {
                    case 0:
                        $status_string = _("Up");
                        $host_status_class = "hostup";
                        $host_row_class = "hostup";
                        break;
                    case 1:
                        $status_string = _("Down");
                        $host_status_class = "hostdown";
                        $host_row_class = "hostdown";
                        break;
                    case 2:
                        $status_string = _("Unreachable");
                        $host_status_class = "hostunreachable";
                        $host_row_class = "hostunreachable";
                        break;
                    default:
                        $status_string = "";
                        $host_status_class = "";
                        $host_row_class = "";
                        break;
                }

                $host_name_cell = "";
                $host_icons = "";
                // Downtime
                if (intval($x->scheduled_downtime_depth) > 0) {
                    $host_icons .= get_host_status_note_image("downtime.png", _("This host is in scheduled downtime"));
                }
                $host_icons .= get_object_icon_html($x->host_icon_image, $x->host_icon_image_alt);

                $host_name_cell .= "<div><a href='" . get_host_status_detail_link($host_name) . "' title='" . $address . "'>";
                $host_name_cell .= "<div class='hostname'>" . $host_name . "</div>";
                $host_name_cell .= "<div class='hosticons'>";
                $host_name_cell .= $host_icons;

                // Service details link
                $url = get_base_url() . "includes/components/xicore/status.php?show=services&host=".urlencode($host_name);
                $alttext = _("View service status details for this host");
                $host_name_cell .= "<a href='" . $url . "'><i class='material-symbols-outlined md-18 md-400 md-button md-middle tt-bind' title='" . $alttext . "'>description</i></a>";
                $host_name_cell .= "</div>";
                $host_name_cell .= "</a></div>";

                if (is_neptune()) { $host_row_class = ''; }
                $output .= "<tr class='" . $rowclass . " " . $host_row_class . "'>";
                $output .= "<td>" . $host_name_cell . "</td>";
                if (is_neptune()){
                    $output .= '<td class="text-foreground"> <span class="status-dot ' . $host_status_class . ' dot-10"></span>' . $status_string . '</td>';
                } else {
                    $output .= "<td class='" . $host_status_class . "'>" . $status_string . "</td>";
                }
            }

            // Get service state info
            $service_current_state = intval($x->current_state);
            switch ($service_current_state) {
                case 0:
                    $status_class = "serviceok";
                    break;
                case 1:
                    $status_class = "servicewarning";
                    break;
                case 2:
                    $status_class = "servicecritical";
                    break;
                case 3:
                    $status_class = "serviceunknown";
                    break;
                default:
                    break;
            }

            $services_cell .= "<div class='inlinestatus " . $status_class . "'><a href='" . get_service_status_detail_link($x->host_name, $x->name) . "'>" . $x->name . "</a></div>";
        }

        // Finish the last host row
        if ($current_host > 0) {
            $output .= "<td>" . $services_cell . "</td>";
            $output .= "</tr>\n";
        }

    }

    // No services/hosts found
    if ($current_host == 0) {
        $output .= "<tr><td colspan='3'>" . _("No status information found.") . "</td></tr>";
    }

    $output .= "</table>";
    $output .= '<div class="ajax_date">' . _('Last Updated') . ': ' . get_datetime_string(time()) . '</div>';

    return $output;
}


/**
 * Get host group status summary HTML
 *
 * @param   array   $args   Arguments
 * @return  string          HTML output
 */
function xicore_ajax_get_hostgroup_status_summary_html($args = null)
{
    global $cfg;
    $style = grab_array_var($args, "style");

    $tableclass = 'statustable hostgroup table table-condensed table-striped table-bordered table-auto-width "' . $style . '"table';
    $host_icon_up = $host_icon_down = $host_icon_unreachable = $host_icon_ok = $host_icon_warning = $host_icon_unreachable = $host_icon_critical = '';
    if (is_neptune()) {
        $tableclass = 'table dashlettable-in table-condensed';
        $host_icon_up = '<span class="status-dot hostup dot-10" title=' . _('Up') . '></span>';
        $host_icon_down = '<span class="status-dot hostdown dot-10" title=' . _('Down') . '></span>';
        $host_icon_unreachable = '<span class="status-dot hostunknown dot-10" title=' . _('Unreachable') . '></span>';
        $host_icon_ok = '<span class="status-dot hostup dot-10" title=' . _('Ok') . '></span>';
        $host_icon_critical = '<span class="status-dot hostdown dot-10" title=' . _('Critical') . '></span>';
        $host_icon_unknown = '<span class="status-dot hostunknown dot-10" title=' . _('Unknown') . '></span>';
        $host_icon_warning = '<span class="status-dot hostwarning dot-10" title=' . _('Warning') . '></span>';
    }

    $status_text = array();

    $ag = array("orderby" => "hostgroup_name:a");

    $dashlet_title = _("All");

    // If specific hostgroups were selected, only get those
    $hostgroup = grab_array_var($args, "hostgroup", "");
    if ($hostgroup) {
        $dashlet_title = encode_form_val($hostgroup);
        $hostgroups = explode(", ", $hostgroup);
        $hostgroup_ids_str = "";
        $y = 0;
        foreach ($hostgroups as $hg) {
            $hgid = get_hostgroup_id($hg);
            if ($y > 0)
                $hostgroup_ids_str .= ",";
            $hostgroup_ids_str .= $hgid;
            $y++;
        }
        $ag["hostgroup_id"] = "in:" . $hostgroup_ids_str;
    }

    $output = '<div class="infotable_title">' . _('Status Summary for ') . $dashlet_title . _(' Host Groups') . '</div>';
    $output .= "<table class= '" . $tableclass . "'>\n";
    $output .= "<thead>\n";
    $output .= "<tr><th>" . _("Host Group") . "</th><th>" . _("Hosts") . "</th><th>" . _("Services") . "</th></tr>\n";
    $output .= "</thead>\n";
    
    // Get hostgroups
    $xmlhg = get_xml_hostgroup_objects($ag);

    $xistatus_url = get_base_url() . "includes/components/xicore/status.php";

    // Loop over all hostgroups
    $current_hostgroup = 0;
    if ($xmlhg && intval($xmlhg->recordcount) > 0) {

        foreach ($xmlhg->hostgroup as $hg) {

            $current_hostgroup++;

            $hgname = strval($hg->hostgroup_name);
            $hgalias = strval($hg->alias);

            // Initialize the array for this hostgroup
            $status_text[$hgname] = array(
                "hostgroup_cell" => "",
                "host_cell" => "",
                "service_cell" => ""
            );

            $icons = "";
        
            $icons .= "<div class='statusdetaillink'><a href='" . $xistatus_url . "?show=services&hostgroup=" . urlencode($hgname) . "'><span class='material-symbols-outlined md-button md-middle md-18 md-400 tt-bind' title='" . _('View Hostgroup Service Details') . "'>description</span></a></div>";
          
            if ($args['mode'] == DASHLET_MODE_INBOARD) {
                $extinfo_url = get_base_url() . "includes/components/nagioscore/ui/extinfo.php";
                $icons .= "<div class='statusdetaillink'><a href='" . $extinfo_url . "?type=5&hostgroup=" . urlencode($hgname) . "'><span class='material-symbols-outlined md-button md-middle md-18 md-400 tt-bind' title='" . _('Hostgroup Commands') . "'>terminal</span></a></div>";
            } else {
                $icons .= 
                '<div class="statusdetaillink group-dt-popup" data-name="' . encode_form_val($hgname) . '">
                    <a><i class="material-symbols-outlined md-button md-middle md-18 md-400 tt-bind" title="' . _('View Hostgroup Commands') . '">terminal</i></a>
                </div>';
            }

            if (!empty($cfg['reverse_hostgroup_alias']) && $cfg['reverse_hostgroup_alias'] == 1) {
                $status_text[$hgname]["hostgroup_cell"] = "<div class='neptune-flex-center'><div class='hostgroup_name'>" . $hgname . " (" . $hgalias . ")</div><div class='hostgroup_icons'>" . $icons . "</div></div>";
            } else {
                $status_text[$hgname]["hostgroup_cell"] = "<div class='neptune-flex-center'><div class='hostgroup_name'>" . $hgalias . " (" . $hgname . ")</div><div class='hostgroup_icons'>" . $icons . "</div></div>";
            }

            // Get host status for this hostgroup
            $host_ids = get_hostgroup_member_ids($hgname);

            // GET HOST STATUS

            $backendargs = array();
            $backendargs["cmd"] = "gethoststatus";
            $backendargs["orderby"] = "host_name:a";
            $backendargs["brevity"] = 1;

            // Host ID limiters
            if (!empty($host_ids)) {
                $backendargs["host_id"] = "in:" . implode(',', $host_ids);
            }
            $xmlh = get_xml_host_status($backendargs);

            $total_up = 0;
            $total_down = 0;
            $total_unreachable = 0;

            $current_host = 0;
            if ($xmlh && intval($xmlh->recordcount) > 0) {
                foreach ($xmlh->hoststatus as $hs) {
                    $current_host++;
                    switch (intval($hs->current_state)) {
                        case 0:
                            $total_up++;
                            break;
                        case 1:
                            $total_down++;
                            break;
                        case 2:
                            $total_unreachable++;
                            break;
                        default:
                            break;
                    }
                }
            }

            $host_cell = "";

            if(is_neptune()){
                if ($total_up > 0) {
                    $host_cell .= "<div class='hc-text'>" . $host_icon_up . "<a href='" . $xistatus_url . "?show=hosts&hostgroup=" . urlencode($hgname) . "&hoststatustypes=" . HOSTSTATE_UP . "'>" . $total_up . " " . _("Up") . "</a></div>";
                }
                if ($total_down > 0) {
                    $host_cell .= "<div class='hc-text'>" . $host_icon_down . "<a href='" . $xistatus_url . "?show=hosts&hostgroup=" . urlencode($hgname) . "&hoststatustypes=" . HOSTSTATE_DOWN . "'>" . $total_down . " " . _("Down") . "</a></div>";
                }
                if ($total_unreachable > 0) {
                    $host_cell .= "<div class='hc-text'>" . $host_icon_unreachable . "<a href='" . $xistatus_url . "?show=hosts&hostgroup=" . urlencode($hgname) . "&hoststatustypes=" . HOSTSTATE_UNREACHABLE . "'>" . $total_unreachable . " " . _("Unreachable") . "</a></div>";
                }
            } else {
                if ($total_up > 0) {
                    $host_cell .= "<div class='hc-text hostup'>" . $host_icon_up . "<a href='" . $xistatus_url . "?show=hosts&hostgroup=" . urlencode($hgname) . "&hoststatustypes=" . HOSTSTATE_UP . "'>" . $total_up . " " . _("Up") . "</a></div>";
                }
                if ($total_down > 0) {
                    $host_cell .= "<div class='hc-text hostdown'>" . $host_icon_down . "<a href='" . $xistatus_url . "?show=hosts&hostgroup=" . urlencode($hgname) . "&hoststatustypes=" . HOSTSTATE_DOWN . "'>" . $total_down . " " . _("Down") . "</a></div>";
                }
                if ($total_unreachable > 0) {
                    $host_cell .= "<div class='hc-text hostunreachable'>" . $host_icon_unreachable . "<a href='" . $xistatus_url . "?show=hosts&hostgroup=" . urlencode($hgname) . "&hoststatustypes=" . HOSTSTATE_UNREACHABLE . "'>" . $total_unreachable . " " . _("Unreachable") . "</a></div>";
                }  
            }

            $status_text[$hgname]["host_cell"] = $host_cell;

            // GET SERVICE STATUS

            $backendargs = array();
            $backendargs["cmd"] = "getservicestatus";
            $backendargs["orderby"] = "host_name:a";
            $backendargs["brevity"] = 1;

            // Host ID limiters
            if (!empty($host_ids)) {
                $backendargs["host_id"] = "in:" . implode(',', $host_ids);
            }
            $xmls = get_xml_service_status($backendargs);

            $total_ok = 0;
            $total_warning = 0;
            $total_unknown = 0;
            $total_critical = 0;

            $current_service = 0;
            if ($xmls && intval($xmls->recordcount) > 0) {
                foreach ($xmls->servicestatus as $ss) {
                    $current_service++;
                    switch (intval($ss->current_state)) {
                        case 0:
                            $total_ok++;
                            break;
                        case 1:
                            $total_warning++;
                            break;
                        case 2:
                            $total_critical++;
                            break;
                        case 3:
                            $total_unknown++;
                            break;
                        default:
                            break;
                    }
                }
            }

            $service_cell = "";

            if(is_neptune()){
                if ($total_ok > 0) {
                    $service_cell .= "<div class='hc-text'>" . $host_icon_ok . "<a href='" . $xistatus_url . "?show=services&hostgroup=" . urlencode($hgname) . "&servicestatustypes=" . SERVICESTATE_OK . "'>" . $total_ok . " " . _("Ok") . "</a></div>";
                }
                if ($total_warning > 0) {
                    $service_cell .= "<div class='hc-text'>" . $host_icon_warning . "<a href='" . $xistatus_url . "?show=services&hostgroup=" . urlencode($hgname) . "&servicestatustypes=" . SERVICESTATE_WARNING . "'>" . $total_warning . " " . _("Warning") . "</a></div>";
                }
                if ($total_unknown > 0) {
                    $service_cell .= "<div class='hc-text'>" . $host_icon_unknown . "<a href='" . $xistatus_url . "?show=services&hostgroup=" . urlencode($hgname) . "&servicestatustypes=" . SERVICESTATE_UNKNOWN . "'>" . $total_unknown . " " . _("Unknown") . "</a></div>";
                }
                if ($total_critical > 0) {
                    $service_cell .= "<div class='hc-text'>" . $host_icon_critical . "<a href='" . $xistatus_url . "?show=services&hostgroup=" . urlencode($hgname) . "&servicestatustypes=" . SERVICESTATE_CRITICAL . "'>" . $total_critical . " " . _("Critical") . "</a></div>";
                }
            } else {
                if ($total_ok > 0) {
                    $service_cell .= "<div class='hc-text serviceok'>" . $host_icon_ok . "<a href='" . $xistatus_url . "?show=services&hostgroup=" . urlencode($hgname) . "&servicestatustypes=" . SERVICESTATE_OK . "'>" . $total_ok . " " . _("Ok") . "</a></div>";
                }
                if ($total_warning > 0) {
                    $service_cell .= "<div class='hc-text servicewarning'>" . $host_icon_warning . "<a href='" . $xistatus_url . "?show=services&hostgroup=" . urlencode($hgname) . "&servicestatustypes=" . SERVICESTATE_WARNING . "'>" . $total_warning . " " . _("Warning") . "</a></div>";
                }
                if ($total_unknown > 0) {
                    $service_cell .= "<div class='hc-text serviceunknown'>" . $host_icon_unknown . "<a href='" . $xistatus_url . "?show=services&hostgroup=" . urlencode($hgname) . "&servicestatustypes=" . SERVICESTATE_UNKNOWN . "'>" . $total_unknown . " " . _("Unknown") . "</a></div>";
                }
                if ($total_critical > 0) {
                    $service_cell .= "<div class='hc-text servicecritical'>" . $host_icon_critical . "<a href='" . $xistatus_url . "?show=services&hostgroup=" . urlencode($hgname) . "&servicestatustypes=" . SERVICESTATE_CRITICAL . "'>" . $total_critical . " " . _("Critical") . "</a></div>";
                }
            }

            $status_text[$hgname]["service_cell"] = $service_cell;
        }
    }

    // Output status data
    $x = 0;
    foreach ($status_text as $st) {
        $x++;
        if (($x % 2) == 0) {
            $rowclass = "even";
        } else {
            $rowclass = "odd";
        }
        $output .= "<tr class='" . $rowclass . "'><td>" . $st["hostgroup_cell"] . "</td><td>" . $st["host_cell"] . "</td><td>" . $st["service_cell"] . "</td></tr>";
    }

    // No hostgroups found
    if ($current_hostgroup == 0) {
        $output .= "<tr><td colspan='3'>" . _('No status information found') . ".</td></tr>";
    }

    $output .= "</table>";
    $output .= '<div class="ajax_date">' . _('Last Updated') . ': ' . get_datetime_string(time()) . '</div>';

    return $output;
}


/**
 * Get service group status summary HTML
 *
 * @param   array   $args   Arguments
 * @return  string          HTML output
 */
function xicore_ajax_get_servicegroup_status_summary_html($args = null)
{
    global $cfg;

    $style = grab_array_var($args, "style");

    $tableclass = 'statustable servicegroup table table-condensed table-striped table-bordered table-auto-width "' . $style . '"table';
    $icon_up = $icon_down = $icon_unreachable = $icon_ok = $icon_warning = $icon_unreachable = $icon_critical = '';
    if (is_neptune()) {
        $tableclass = 'table dashlettable-in table-condensed';
        $icon_up = '<span class="status-dot hostup dot-10" title=' . _('Up') . '></span>';
        $icon_down = '<span class="status-dot hostdown dot-10" title=' . _('Down') . '></span>';
        $icon_unreachable = '<span class="status-dot hostunknown dot-10" title=' . _('Unreachable') . '></span>';
        $icon_ok = '<span class="status-dot hostup dot-10" title=' . _('Ok') . '></span>';
        $icon_critical = '<span class="status-dot hostdown dot-10" title=' . _('Critical') . '></span>';
        $icon_unknown = '<span class="status-dot hostunknown dot-10" title=' . _('Unknown') . '></span>';
        $icon_warning = '<span class="status-dot hostwarning dot-10" title=' . _('Warning') . '></span>';
    }

    $status_text = array();

    $dashlet_title = _("All");

    $ag = array("orderby" => "servicegroup_name:a");

    // If specific hostgroups were selected, only get those
    $servicegroup = grab_array_var($args, "servicegroup", "");
    if ($servicegroup) {
        $dashlet_title = encode_form_val($servicegroup);
        $servicegroups = explode(", ", $servicegroup);
        $servicegroup_ids_str = "";
        $y = 0;
        foreach ($servicegroups as $sg) {
            $sgid = get_servicegroup_id($sg);
            if ($y > 0)
                $servicegroup_ids_str .= ",";
            $servicegroup_ids_str .= $sgid;
            $y++;
        }
        $ag["servicegroup_id"] = "in:" . $servicegroup_ids_str;
    }

    $output = '<div class="infotable_title">' . _('Status Summary for ') . $dashlet_title . _(' Service Groups') . '</div>';
    $output .= "<table class='" . $tableclass . "'>\n";
    $output .= "<thead>\n";
    $output .= "<tr><th>" . _("Service Group") . "</th><th>" . _("Hosts") . "</th><th>" . _("Services") . "</th></tr>\n";
    $output .= "</thead>\n";

    // Get all servicegroups
    $xmlsg = get_xml_servicegroup_objects($ag);

    $xistatus_url = get_base_url() . "includes/components/xicore/status.php";

    // Loop over all servicegroups
    $current_servicegroup = 0;
    if ($xmlsg && intval($xmlsg->recordcount) > 0) {

        foreach ($xmlsg->servicegroup as $sg) {

            $current_servicegroup++;

            $sgname = strval($sg->servicegroup_name);
            $sgalias = strval($sg->alias);

            // Initialize the array for this servicegroup
            $status_text[$sgname] = array(
                "servicegroup_cell" => "",
                "host_cell" => "",
                "service_cell" => ""
            );

            $icons = "";

            $icons .= "<div class='statusdetaillink'><a href='" . $xistatus_url . "?show=services&servicegroup=" . urlencode($sgname) . "'><span class='material-symbols-outlined md-button md-middle md-18 md-400 tt-bind' title='" . _('View Servicegroup Service Details') . "'>description</span></a></div>";

            if ($args['mode'] == DASHLET_MODE_INBOARD) {
                $extinfo_url = get_base_url() . "includes/components/nagioscore/ui/extinfo.php";
                $icons .= "<div class='statusdetaillink'><a href='" . $extinfo_url . "?type=8&servicegroup=" . urlencode($sgname) . "'><span class='material-symbols-outlined md-button md-middle md-18 md-400 tt-bind' title='" . _('Servicegroup Commands') . "'>terminal</span></a></div>";
            } else {
                $icons .= 
                '<div class="statusdetaillink group-dt-popup" data-name="' . encode_form_val($sgname) . '">
                    <a><span class="material-symbols-outlined md-button md-middle md-18 md-400 tt-bind" title="' . _('View Servicegroup Commands') . '">terminal</span></a>
                </div>';
            }

            if (!empty($cfg['reverse_servicegroup_alias']) && $cfg['reverse_servicegroup_alias'] == 1) {
                $status_text[$sgname]["servicegroup_cell"] = "<div class='neptune-flex-center'><div class='servicegroup_name'>" . $sgname . " (" . $sgalias . ")</div><div class='servicegroup_icons'>" . $icons . "</div></div>";
            } else {
                $status_text[$sgname]["servicegroup_cell"] = "<div class='neptune-flex-center'><div class='servicegroup_name'>" . $sgalias . " (" . $sgname . ")</div><div class='servicegroup_icons'>" . $icons . "</div></div>";
            }

            // Limit by servicegroup
            $host_ids = get_servicegroup_host_member_ids($sgname);

            // GET HOST STATUS

            $backendargs = array();
            $backendargs["cmd"] = "gethoststatus";
            $backendargs["orderby"] = "host_name:a";
            $backendargs["brevity"] = 1;

            // Host ID limiters
            if (!empty($host_ids)) {
                $backendargs["host_id"] = "in:" . implode(',', $host_ids);
            }
            $xmlh = get_xml_host_status($backendargs);

            $total_up = 0;
            $total_down = 0;
            $total_unreachable = 0;

            $current_host = 0;
            if ($xmlh && intval($xmlh->recordcount) > 0) {
                foreach ($xmlh->hoststatus as $hs) {
                    $current_host++;
                    switch (intval($hs->current_state)) {
                        case 0:
                            $total_up++;
                            break;
                        case 1:
                            $total_down++;
                            break;
                        case 2:
                            $total_unreachable++;
                            break;
                        default:
                            break;
                    }
                }
            }

            $host_cell = "";

            if (is_neptune()) {
                if ($total_up > 0) {
                    $host_cell .= "<div>" . $icon_up . "<a href='" . $xistatus_url . "?show=services&servicegroup=" . urlencode($sgname) . "&hoststatustypes=" . HOSTSTATE_UP . "'>" . $total_up . " " . _("Up") . "</a></div>";
                }
                if ($total_down > 0) {
                    $host_cell .= "<div>" . $icon_down . "<a href='" . $xistatus_url . "?show=services&servicegroup=" . urlencode($sgname) . "&hoststatustypes=" . HOSTSTATE_DOWN . "'>" . $total_down . " " . _("Down") . "</a></div>";
                }
                if ($total_unreachable > 0) {
                    $host_cell .= "<div>" . $icon_unreachable . "<a href='" . $xistatus_url . "?show=services&servicegroup=" . urlencode($sgname) . "&hoststatustypes=" . HOSTSTATE_UNREACHABLE . "'>" . $total_unreachable . " " . _("Unreachable") . "</a></div>";
                }
            } else {
                if ($total_up > 0) {
                    $host_cell .= "<div class='hostup'>" . $icon_up . "<a href='" . $xistatus_url . "?show=services&servicegroup=" . urlencode($sgname) . "&hoststatustypes=" . HOSTSTATE_UP . "'>" . $total_up . " " . _("Up") . "</a></div>";
                }
                if ($total_down > 0) {
                    $host_cell .= "<div class='hostdown'>" . $icon_down . "<a href='" . $xistatus_url . "?show=services&servicegroup=" . urlencode($sgname) . "&hoststatustypes=" . HOSTSTATE_DOWN . "'>" . $total_down . " " . _("Down") . "</a></div>";
                }
                if ($total_unreachable > 0) {
                    $host_cell .= "<div class='hostunreachable'>" . $icon_unreachable . "<a href='" . $xistatus_url . "?show=services&servicegroup=" . urlencode($sgname) . "&hoststatustypes=" . HOSTSTATE_UNREACHABLE . "'>" . $total_unreachable . " " . _("Unreachable") . "</a></div>";
                }
            }

            $status_text[$sgname]["host_cell"] = $host_cell;

            // Limit services by servicegroup
            $service_ids = get_servicegroup_member_ids($sgname);

            // GET SERVICE STATUS

            $backendargs = array();
            $backendargs["cmd"] = "getservicestatus";
            $backendargs["orderby"] = "host_name:a";
            $backendargs["brevity"] = 1;

            // Service ID limiters
            if (!empty($service_ids)) {
                $backendargs["service_id"] = "in:" . implode(',', $service_ids);
            }
            $xmls = get_xml_service_status($backendargs);

            $total_ok = 0;
            $total_warning = 0;
            $total_unknown = 0;
            $total_critical = 0;

            $current_service = 0;
            if ($xmls && intval($xmls->recordcount) > 0) {
                foreach ($xmls->servicestatus as $ss) {
                    $current_service++;
                    switch (intval($ss->current_state)) {
                        case 0:
                            $total_ok++;
                            break;
                        case 1:
                            $total_warning++;
                            break;
                        case 2:
                            $total_critical++;
                            break;
                        case 3:
                            $total_unknown++;
                            break;
                        default:
                            break;
                    }
                }
            }

            $service_cell = "";

            if (is_neptune()) {
                if ($total_ok > 0) {
                    $service_cell .= "<div>" . $icon_ok . "<a href='" . $xistatus_url . "?show=services&servicegroup=" . urlencode($sgname) . "&servicestatustypes=" . SERVICESTATE_OK . "'>" . $total_ok . " " . _("Ok") . "</a></div>";
                }
                if ($total_warning > 0) {
                    $service_cell .= "<div>" . $icon_warning . "<a href='" . $xistatus_url . "?show=services&servicegroup=" . urlencode($sgname) . "&servicestatustypes=" . SERVICESTATE_WARNING . "'>" . $total_warning . " " . _("Warning") . "</a></div>";
                }
                if ($total_unknown > 0) { 
                    $service_cell .= "<div>" . $icon_unknown . "<a href='" . $xistatus_url . "?show=services&servicegroup=" . urlencode($sgname) . "&servicestatustypes=" . SERVICESTATE_UNKNOWN . "'>" . $total_unknown . " " . _("Unknown") . "</a></div>";
                }
                if ($total_critical > 0) {
                    $service_cell .= "<div>" . $icon_critical . "<a href='" . $xistatus_url . "?show=services&servicegroup=" . urlencode($sgname) . "&servicestatustypes=" . SERVICESTATE_CRITICAL . "'>" . $total_critical . " " . _("Critical") . "</a></div>";
                }
            }
            else {
                if ($total_ok > 0) {
                    $service_cell .= "<div class='serviceok'>" . $icon_ok . "<a href='" . $xistatus_url . "?show=services&servicegroup=" . urlencode($sgname) . "&servicestatustypes=" . SERVICESTATE_OK . "'>" . $total_ok . " " . _("Ok") . "</a></div>";
                }
                if ($total_warning > 0) {
                    $service_cell .= "<div class='servicewarning'>" . $icon_warning . "<a href='" . $xistatus_url . "?show=services&servicegroup=" . urlencode($sgname) . "&servicestatustypes=" . SERVICESTATE_WARNING . "'>" . $total_warning . " " . _("Warning") . "</a></div>";
                }
                if ($total_unknown > 0) { 
                    $service_cell .= "<div class='serviceunknown'>" . $icon_unknown . "<a href='" . $xistatus_url . "?show=services&servicegroup=" . urlencode($sgname) . "&servicestatustypes=" . SERVICESTATE_UNKNOWN . "'>" . $total_unknown . " " . _("Unknown") . "</a></div>";
                }
                if ($total_critical > 0) {
                    $service_cell .= "<div class='servicecritical'>" . $icon_critical . "<a href='" . $xistatus_url . "?show=services&servicegroup=" . urlencode($sgname) . "&servicestatustypes=" . SERVICESTATE_CRITICAL . "'>" . $total_critical . " " . _("Critical") . "</a></div>";
                }
            }

            $status_text[$sgname]["service_cell"] = $service_cell;
        }
    }

    // Output status data
    $x = 0;
    foreach ($status_text as $st) {
        $x++;
        if (($x % 2) == 0) {
            $rowclass = "even";
        } else {
            $rowclass = "odd";
        }
        $output .= "<tr class='" . $rowclass . "'><td>" . $st["servicegroup_cell"] . "</td><td>" . $st["host_cell"] . "</td><td>" . $st["service_cell"] . "</td></tr>";
    }

    // No servicegroups found
    if ($current_servicegroup == 0) {
        $output .= "<tr><td colspan='3'>" . _("No status information found.") . "</td></tr>";
    }

    $output .= "</table>";
    $output .= '<div class="ajax_date">' . _('Last Updated') . ': ' . get_datetime_string(time()) . '</div>';

    return $output;
}   
