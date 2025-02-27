<?php
//
// XI Core Ajax Helper Functions
// Copyright (c) 2008-2019 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../componenthelper.inc.php');


////////////////////////////////////////////////////////////////////////
// SYSSTAT AJAX FUNCTIONS
////////////////////////////////////////////////////////////////////////


/**
 * Generates bar status HTML output
 *
 * @param   int     $rawval         Initial value
 * @param   string  $label          Label to display
 * @param   string  $displayval     Value to display on the bar
 * @param   int     $mult           Multiple values
 * @param   int     $maxval         Maximum value
 * @param   int     $level2         Yellow color threshold
 * @param   int     $level3         Red color threshold
 * @return  string                  Bar chart HTML output
 */
function xicore_ajax_get_stat_bar_html($rawval, $label, $displayval, $mult = 20, $maxval = 200, $level2 = 10, $level3 = 50)
{
    $theme = get_theme();
    $okay_color = COMMONCOLOR_GREEN;
    $unknown_color = COMMONCOLOR_ORANGE;
    $critical_color = COMMONCOLOR_RED;
    if ($theme == "colorblind") {
        $okay_color = COLORBLIND_OKAY;
        $unknown_color = COLORBLIND_UNKNOWN;
        $critical_color = COLORBLIND_CRITICAL;
    }
    else if (is_neptune()) {
        $okay_color = NEPTUNE_GREEN;
        $unknown_color = NEPTUNE_ORANGE;
        $critical_color = NEPTUNE_RED;
    }
    
    $val = (floatval($rawval) * $mult);
    if ($val > $maxval) {
        $val = $maxval;
    }

    if ($val <= 1) {
        $val = 1;
    } else if ($val <= 0) {
        $val = 0;
    }

    if ($val > $level3) {        
        $spanval = "<div class='statbar-neptune' style='height: 10px; width: " . $val . "px; background-color:  " . $critical_color . ";'>&nbsp;</div>";
    } else if ($val > $level2) {        
        $spanval = "<div class='statbar-neptune' style='height: 10px; width: " . $val . "px; background-color:  " . $unknown_color . ";'>&nbsp;</div>";
    } else {        
        $spanval = "<div class='statbar-neptune' style='height: 10px; width: " . $val . "px; background-color:  " . $okay_color . ";'>&nbsp;</div>";
    }

    $barclass = "";
    $output = '<tr><td><span class="sysstat_stat_subtitle">' . $label . '</span></td><td class="text-right">' . $displayval . '</td><td><span class="statbar' . $barclass . '">' . $spanval . '</span></td></tr>';

    return $output;
}


/**
 * Get server status HTML
 *
 * @param   array   $args   Arguments
 * @return  string          HTML output
 */
function xicore_ajax_get_server_stats_html($args = null)
{
    if (!is_admin()) {
        return _("You are not authorized to access this feature.  Contact your Nagios XI administrator for more information, or to obtain access to this feature.");
    }

    // Get sysstat data
    $xml = get_xml_sysstat_data();
    
    $output = '';
    $output .= '<div class="infotable_title">' . _('Server Statistics') . '</div>';
    if ($xml == null) {
        $output .= "No data";
    } else {
        $tableclass = 'infotable table table-condensed table-striped table-bordered';
        if (is_neptune()) {
            $tableclass = 'table dashlettable-in stattable-neptune table-condensed';
        }
        $output .= '
        <table class="' . $tableclass . '">
        <thead>
        <tr><th><div style="width: 75px;">' . _('Metric') . '</div></th><th><div style="width: 60px;">' . _('Value') . '</div></th><th><div style="width: 105px;"></div></th></tr>
        </thead>
        <tbody>
        ';

        // Added to account for multiple processors -SW
        $getprocessorcount = "cat /proc/cpuinfo | grep processor | wc -l";
        $processor_count = exec($getprocessorcount);
        $output .= '<tr><td colspan="3"><span class="sysstat_stat_title">' . _('Load') . '</span></td></tr>';

        // Load 1, 5, 15
        $output .= xicore_ajax_get_stat_bar_html($xml->load->load1, "1-min", $xml->load->load1, 10, 100, 25 * $processor_count, 75 * $processor_count);
        $output .= xicore_ajax_get_stat_bar_html($xml->load->load5, "5-min", $xml->load->load5, 10, 100, 25 * $processor_count, 75 * $processor_count);
        $output .= xicore_ajax_get_stat_bar_html($xml->load->load15, "15-min", $xml->load->load15, 10, 100, 25 * $processor_count, 75 * $processor_count);

        $output .= '<tr><td colspan="3"><span class="sysstat_stat_title">' . _('CPU Stats') . '</span></td></tr>';
        $output .= xicore_ajax_get_stat_bar_html($xml->iostat->user, "User", $xml->iostat->user . "%", 1, 100, 75, 95);
        $output .= xicore_ajax_get_stat_bar_html($xml->iostat->nice, "Nice", $xml->iostat->nice . "%", 1, 100, 75, 95);
        $output .= xicore_ajax_get_stat_bar_html($xml->iostat->system, "System", $xml->iostat->system . "%", 1, 100, 75, 95);
        $output .= xicore_ajax_get_stat_bar_html($xml->iostat->iowait, "I/O Wait", $xml->iostat->iowait . "%", 1, 100, 5, 15);
        $output .= xicore_ajax_get_stat_bar_html($xml->iostat->steal, "Steal", $xml->iostat->steal . "%", 1, 100, 5, 15);
        $output .= xicore_ajax_get_stat_bar_html($xml->iostat->idle, "Idle", $xml->iostat->idle . "%", 1, 100, 100, 100);

        $output .= '<tr><td colspan="3"><span class="sysstat_stat_title">' . _('Memory') . '</span></td></tr>';
        $total = intval($xml->memory->total);
        $output .= '<tr><td><span class="sysstat_stat_subtitle">' . _('Total') . '</div></td><td class="text-right">' . get_formatted_number($xml->memory->total, 0) . ' MB</td><td></td></tr>';
        $output .= xicore_ajax_get_stat_bar_html($xml->memory->used, "Used", get_formatted_number($xml->memory->used, 0) . " MB", (1 / $total) * 100, 100, 98, 99);
        $output .= xicore_ajax_get_stat_bar_html($xml->memory->free, "Free", get_formatted_number($xml->memory->free, 0) . " MB", (1 / $total) * 100, 100, 101, 101);
        $output .= xicore_ajax_get_stat_bar_html($xml->memory->shared, "Shared", get_formatted_number($xml->memory->shared, 0) . " MB", (1 / $total) * 100, 100, 101, 101);
        $output .= xicore_ajax_get_stat_bar_html($xml->memory->buffers, "Buffers", get_formatted_number($xml->memory->buffers, 0) . " MB", (1 / $total) * 100, 100, 101, 101);
        $output .= xicore_ajax_get_stat_bar_html($xml->memory->cached, "Cached", get_formatted_number($xml->memory->cached, 0) . " MB", (1 / $total) * 100, 100, 101, 101);

        $total = intval($xml->swap->total);
        if ($total > 0) { // changed to remove if no swap and remove possibility of division by zero - SW
            $output .= '<tr><td colspan="3"><span class="sysstat_stat_title">Swap</span></td></tr>';
            $output .= '<tr><td><span class="sysstat_stat_subtitle">Total</td></td><td class="text-right">' . get_formatted_number($xml->swap->total, 0) . ' MB</td><td></td></tr>';
            $output .= xicore_ajax_get_stat_bar_html($xml->swap->used, "Used", get_formatted_number($xml->swap->used, 0) . " MB", (1 / $total) * 100, 100, 50, 80);
            $output .= xicore_ajax_get_stat_bar_html($xml->swap->free, "Free", get_formatted_number($xml->swap->free, 0) . " MB", (1 / $total) * 100, 100, 100, 100);
        }

        $output .= '
        </tbody>
        </table>';

    }

    $output .= '
    <div class="ajax_date">' . _('Last Updated') . ': ' . get_datetime_string(time()) . '</div>
    ';

    return $output;
}


/**
 * Get component states HTML for AJAX calls
 *
 * @param   array   $args   Arguments
 * @return  string          HTML output
 */
function xicore_ajax_get_component_states_html($args = null)
{
    if (!is_admin()) {
        return _("You are not authorized to access this feature. Contact your system administrator for more information, or to obtain access to this feature.");
    }

    // Get sysstat data
    $xml = get_xml_sysstat_data();
    
    $output = '<div class="infotable_title">' . _('System Component Status') . '</div>';
    
    if ($xml == null) {
        $output .= _("No data");
    } else {
        $tableclass = 'infotable table table-condensed table-striped table-bordered';
        if (is_neptune()) {
            $tableclass = 'table dashlettable-in table-condensed';
        }
        $output .= '
        <table class="' . $tableclass . '">
        <thead>
        <tr><th>' . _('Component') . '</th><th>' . _('Status') . '</th><th>' . _('Action') . '</th></tr>
        </thead>
        <tbody>
        ';

        $components = array(
            "nagioscore",
            "npcd",
            "dbmaint",
            "cmdsubsys",
            "eventman",
            "feedproc",
            "reportengine",
            "cleaner",
            "nom",
            "sysstat",
        );
        foreach ($components as $c) {
            $output .= xicore_ajax_get_component_state_html($c, $xml);
        }
        $output .= '
        </tbody>
        </table>';
    }

    $output .= '<div class="ajax_date">' . _('Last Updated') . ': ' . get_datetime_string(time()) . '</div>';

    return $output;
}


/**
 * Get the component state HTML
 *
 * @param   string  $c      Component name
 * @param   object  $xml    XML object
 * @return  string          HTML output
 */
function xicore_ajax_get_component_state_html($c, $xml)
{
    global $cfg;

    if (!is_admin()) {
        return _("You are not authorized to access this feature. Contact your Nagios XI administrator for more information, or to obtain access to this feature.");
    }

    if ($xml == null) {
        return _("No data");
    }

    $actionimg = "cog.png";
    $actionimgtitle = _("Actions");
    $img = ("status-dot hostunknown dot-10");
    $imgtitle = _("Unknown State");
    $status = SUBSYS_COMPONENT_STATUS_UNKNOWN;
    $description = "";
    $menu = "";

    switch ($c) {

        case "nagioscore":
            $title = _("Monitoring Engine");
            foreach ($xml->daemons->daemon as $d) {
                if ($d["id"] == "nagioscore") {
                    $imgtitle = strval($d->output);
                    $status = intval($d->status);
                }
            }
            break;

        case "npcd":
            $title = _("Performance Grapher");
            foreach ($xml->daemons->daemon as $d) {
                if ($d["id"] == "pnp") {
                    $imgtitle = strval($d->output);
                    $status = intval($d->status);
                }
            }
            break;

        case "dbmaint":
            $title = _("Database Maintenance");
            $x = $xml->dbmaint;
            $lastupdate = intval($x->last_check);
            $diff = time() - $lastupdate;
            if ($diff <= 3600)
                $status = SUBSYS_COMPONENT_STATUS_OK;
            else
                $status = SUBSYS_COMPONENT_STATUS_ERROR;
            $ustr = get_duration_string($diff);
            $imgtitle = "Last Run " . $ustr . " Ago";
            if ($lastupdate == 0) {
                $status = SUBSYS_COMPONENT_STATUS_UNKNOWN;
                $imgtitle = "Not Run Yet";
            }
            break;

        case "cmdsubsys":
            $title = _("Command Subsystem");
            $x = $xml->cmdsubsys;
            $lastupdate = intval($x->last_check);
            $diff = time() - $lastupdate;
            if ($diff <= 120)
                $status = SUBSYS_COMPONENT_STATUS_OK;
            else
                $status = SUBSYS_COMPONENT_STATUS_ERROR;
            $ustr = get_duration_string($diff);
            $imgtitle = "Last Run " . $ustr . " Ago";
            break;

        case "eventman":
            $title = _("Event Manager");
            $x = $xml->eventman;
            $lastupdate = intval($x->last_check);
            $diff = time() - $lastupdate;
            if ($diff <= 120)
                $status = SUBSYS_COMPONENT_STATUS_OK;
            else
                $status = SUBSYS_COMPONENT_STATUS_ERROR;
            $ustr = get_duration_string($diff);
            $imgtitle = "Last Run " . $ustr . " Ago";
            break;

        case "feedproc":
            $title = _("Feed Processor");
            $x = $xml->feedprocessor;
            $lastupdate = intval($x->last_check);
            $diff = time() - $lastupdate;
            if ($diff <= 120)
                $status = SUBSYS_COMPONENT_STATUS_OK;
            else
                $status = SUBSYS_COMPONENT_STATUS_ERROR;
            $ustr = get_duration_string($diff);
            $imgtitle = "" . _('Last Run') . " " . $ustr . " " . _('Ago') . "";
            break;

        case "reportengine":
            $title = _("Report Engine");
            $x = $xml->reportengine;
            $lastupdate = intval($x->last_check);
            $diff = time() - $lastupdate;
            if ($diff <= 120)
                $status = SUBSYS_COMPONENT_STATUS_OK;
            else
                $status = SUBSYS_COMPONENT_STATUS_ERROR;
            $ustr = get_duration_string($diff);
            $imgtitle = "" . _('Last Run') . " " . $ustr . " " . _('Ago') . "";
            break;

        case "nom":
            $title = _("Nonstop Operations Manager");
            $x = $xml->nom;
            $lastupdate = intval($x->last_check);
            $diff = time() - $lastupdate;
            if ($diff <= 3600)
                $status = SUBSYS_COMPONENT_STATUS_OK;
            else
                $status = SUBSYS_COMPONENT_STATUS_ERROR;
            $ustr = get_duration_string($diff);
            $imgtitle = "" . _('Last Run') . " " . $ustr . " " . _('Ago') . "";
            if ($lastupdate == 0) {
                $status = SUBSYS_COMPONENT_STATUS_UNKNOWN;
                $imgtitle = _("Not Run Yet");
            }
            break;

        case "cleaner":
            $title = _("Cleaner");
            $x = $xml->cleaner;
            $lastupdate = intval($x->last_check);
            $diff = time() - $lastupdate;
            if ($diff <= 3600)
                $status = SUBSYS_COMPONENT_STATUS_OK;
            else
                $status = SUBSYS_COMPONENT_STATUS_ERROR;
            $ustr = get_duration_string($diff);
            $imgtitle = "" . _('Last Run') . " " . $ustr . " " . _('Ago') . "";
            if ($lastupdate == 0) {
                $status = SUBSYS_COMPONENT_STATUS_UNKNOWN;
                $imgtitle = _("Not Run Yet");
            }
            break;

        case "sysstat":
            $title = _("System Statistics");
            $x = $xml->sysstat;
            $lastupdate = intval($x->last_check);
            $diff = time() - $lastupdate;
            if ($diff <= 120)
                $status = SUBSYS_COMPONENT_STATUS_OK;
            else
                $status = SUBSYS_COMPONENT_STATUS_ERROR;
            $ustr = get_duration_string($diff);
            $imgtitle = "" . _('Last Updated') . " " . $ustr . " " . _('Ago') . "";
            break;

        default:
            break;

    }

    if ($xml == null) {
        $img = ("status-dot hostunknown dot-10");
    } else {
        switch ($status) {
            case SUBSYS_COMPONENT_STATUS_OK:
                $img = ("status-dot hostup dot-10");
                break;
            case SUBSYS_COMPONENT_STATUS_ERROR:
                $img = ("status-dot hostdown dot-10");
                break;
            case SUBSYS_COMPONENT_STATUS_UNKNOWN:
                $img = ("status-dot hostunknown dot-10");
                break;
            default:
                break;
        }
    }

    switch ($c) {
        case "nagioscore":
            $title = _("Monitoring Engine");
            $action_div = "<span class='centered_flex'>";
            foreach ($xml->daemons->daemon as $d) {
                if ($d["id"] == "nagioscore") {

                    $status = intval($d->status);

                    if ($status != SUBSYS_COMPONENT_STATUS_OK)
                        $action_div .= '<span class="material-symbols-outlined md-400 md-18 md-action md-button md-middle" title=' . _('Start') . ' onClick="submit_command(' . COMMAND_NAGIOSCORE_START . ')">play_arrow</span>';
                    if ($status != SUBSYS_COMPONENT_STATUS_ERROR)
                        $action_div .= '<span class="material-symbols-outlined md-400 md-18 md-action md-button md-middle" title=' . _('Restart') . ' onClick="submit_command(' . COMMAND_NAGIOSCORE_RESTART . ')">replay</span>';
                    if ($status != SUBSYS_COMPONENT_STATUS_ERROR)
                        $action_div .= '<span class="material-symbols-outlined md-400 md-18 md-action md-button md-middle" title=' . _('Stop') . ' onClick="submit_command(' . COMMAND_NAGIOSCORE_STOP . ')">stop_circle</span>';
                }
            }
            $action_div .= "</span>";
            break;
        case "npcd":
            $title = _("Performance Grapher");
            $action_div = "<span class='centered_flex'>";
            foreach ($xml->daemons->daemon as $d) {
                if ($d["id"] == "pnp") {

                    $status = intval($d->status);

                    if ($status != SUBSYS_COMPONENT_STATUS_OK)
                        $action_div .= '<span class="material-symbols-outlined md-400 md-18 md-action md-button md-middle" title=' . _('Start') . ' onClick="submit_command(' . COMMAND_NPCD_START . ')">play_arrow</span>';
                    if ($status != SUBSYS_COMPONENT_STATUS_ERROR)
                        $action_div .= '<span class="material-symbols-outlined md-400 md-18 md-action md-button md-middle" title=' . _('Restart') . ' onClick="submit_command(' . COMMAND_NPCD_RESTART . ')">replay</span>';
                    if ($status != SUBSYS_COMPONENT_STATUS_ERROR)
                        $action_div .= '<span class="material-symbols-outlined md-400 md-18 md-action md-button md-middle" title=' . _('Stop') . ' onClick="submit_command(' . COMMAND_NPCD_STOP . ')">stop_circle</span>';
                }
            }
            $action_div .= "</span>";
            break;
        default:
            $action_div = '';
            break;
    }

    $output = '
    <tr>
    <td>
    <div class="sysstat_componentstate_title">' . $title . '</div>
    <div class="sysstat_componentstate_description">' . $description . '</div>
    </td>
    <!--td><div class="sysstate_componentstate_image text-center"><span class="' . $img . '" title="' . $imgtitle . '"></span></div></td-->
    <td><div class="sysstate_componentstate_image text-center"><span class="' . $img . '" title="' . $imgtitle . '"></span></div></td>
    <td>' . $action_div . '</td>
    </tr>
    ';

    return $output;
}
