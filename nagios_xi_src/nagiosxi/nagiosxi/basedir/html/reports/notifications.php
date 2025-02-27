<?php
//
// Notifications Report
// Copyright (c) 2010-2020 Nagios Enterprises, LLC. All rights reserved.
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
    $mode = grab_request_var("mode", "");
    switch ($mode) {
        case "csv":
            get_notifications_csv();
            break;
        case "pdf":
            export_report('notifications', EXPORT_PDF, EXPORT_LANDSCAPE);
            break;
        case "submitpdf":
            submit_report('notifications', EXPORT_PDF, EXPORT_LANDSCAPE);
            break;
        case "jpg":
            export_report('notifications', EXPORT_JPG);
            break;
        case "getservices":
            $host = grab_request_var("host", "");
            $args = array('brevity' => 1, 'host_name' => $host, 'orderby' => 'service_description:a');
            $oxml = get_xml_service_objects($args);
            echo '<option value="">['._("All Services").']</option>';
            if ($oxml) {
                foreach ($oxml->service as $serviceobj) {
                    $name = strval($serviceobj->service_description);
                    echo "<option value='" . $name . "' " . is_selected($service, $name) . ">$name</option>\n";
                }
            }
            break;
        case "getreport":
            get_notifications_report();
            break;
        case "getpage":
            get_notifications_page();
            break;
        default:
            display_notifications();
            break;
    }
}


///////////////////////////////////////////////////////////////////
// BACKEND DATA FUNCTIONS
///////////////////////////////////////////////////////////////////


// Get notification data in XML format from the backend
function get_notifications_data($args)
{
    $xml = get_xml_notificationswithcontacts($args);
    return $xml;
}


///////////////////////////////////////////////////////////////////
// REPORT GENERATION FUCNTIONS
///////////////////////////////////////////////////////////////////


function display_notifications()
{
    global $request;

    // Makes sure user has appropriate license level
    licensed_feature_check();

    // Get values passed in GET/POST request
    $page = grab_request_var("page", 0);
    $reportperiod = grab_request_var("reportperiod", "last24hours");
    $startdate = grab_request_var("startdate", "");
    $enddate = grab_request_var("enddate", "");
    $search = grab_request_var("search", "");
    $host = grab_request_var("host", "");
    $service = grab_request_var("service", "");
    $hostgroup = grab_request_var("hostgroup", "");
    $servicegroup = grab_request_var("servicegroup", "");

    // Do not do any processing unless we have default report running enabled
    $disable_report_auto_run = get_option("disable_report_auto_run", 0);

    // Fix custom dates
    if ($reportperiod == "custom") {
        if ($enddate == "") {
            $enddate = date("Y-m-d H:i:s");
        }
        if ($startdate == "") {
            $startdate = date("Y-m-d H:i:s", strtotime("-1 day"));
            $enddate = date("Y-m-d H:i:s");
        }
    }

    $auto_start_date = get_datetime_string(strtotime('yesterday'), DT_SHORT_DATE);
    $auto_end_date = get_datetime_string(strtotime('today'), DT_SHORT_DATE);

    // Get timezone datepicker format
    if (isset($_SESSION['date_format']))
        $format = $_SESSION['date_format'];
    else {
        if (is_null($format = get_user_meta(0, 'date_format')))
            $format = get_option('default_date_format');
    }
    $f = get_date_formats();

    $js_date = 'mm/dd/yy';
    if ($format == DF_ISO8601) {
        $js_date = 'yy-mm-dd';
    } else if ($format == DF_US) {
        $js_date = 'mm/dd/yy';
    } else if ($format == DF_EURO) {
        $js_date = 'dd/mm/yy';
    }

    do_page_start(array("page_title" => _("Notifications")), true);
?>

<script type="text/javascript">
$(document).ready(function () {

    showhidedates();

    // If we should run it right away
    if (!<?php echo $disable_report_auto_run; ?>) {
        run_notifications_ajax();
    }

    $('.datetimepicker').datetimepicker({
        dateFormat: '<?php echo $js_date; ?>',
        timeFormat: 'HH:mm:ss',
        showHour: true,
        showMinute: true,
        showSecond: true
    });

    $('.btn-datetimepicker').click(function() {
        var id = $(this).data('picker');
        $('#' + id).datetimepicker('show');
    });

    $('#startdateBox').click(function () {
        $('#reportperiodDropdown').val('custom');
        if ($('#startdateBox').val() == '' && $('#enddateBox').val() == '') {
            $('#startdateBox').val('<?php echo $auto_start_date;?>');
            $('#enddateBox').val('<?php echo $auto_end_date;?>');
        }
    });

    $('#enddateBox').click(function () {
        $('#reportperiodDropdown').val('custom');
        if ($('#startdateBox').val() == '' && $('#enddateBox').val() == '') {
            $('#startdateBox').val('<?php echo $auto_start_date;?>');
            $('#enddateBox').val('<?php echo $auto_end_date;?>');
        }
    });

    if (!is_neptune()) {
        $('#hostList').searchable({maxMultiMatch: 9999});
        $('#serviceList').searchable({maxMultiMatch: 9999});
        $('#hostgroupList').searchable({maxMultiMatch: 9999});
        $('#servicegroupList').searchable({maxMultiMatch: 9999});
    }
    
    if ($('#serviceList').is(':visible')) {
        $('.serviceList-sbox').show();
    } else {
        $('.serviceList-sbox').hide();
    }

    $('#hostList').change(function () {
        $('#hostgroupList').val('');
        $('#servicegroupList').val('');

        if ($(this).val() != '') {
            update_service_list();
            $('#serviceList').show();
            $('.serviceList-sbox').show();
        } else {
            $('#serviceList').val('').hide();
            $('.serviceList-sbox').hide();
        }
    });

    $('#servicegroupList').change(function () {
        $('#hostList').val('');
        $('#hostgroupList').val('');
        $('#serviceList').val('').hide();
        $('.serviceList-sbox').hide();
    });

    $('#hostgroupList').change(function () {
        $('#servicegroupList').val('');
        $('#hostList').val('');
        $('#serviceList').val('').hide();
        $('.serviceList-sbox').hide();
    });

    $('#reportperiodDropdown').change(function () {
        showhidedates();
    });

    // Actually return the report
    $('#run').click(function() {
        run_notifications_ajax();
    });

    // Get the export button link and send user to it
    $('.btn-export').on('mousedown', function(e) {
        var type = $(this).data('type');
        var formvalues = $("form").serialize();
        formvalues += '&mode=getreport';
        var url = "<?php echo get_base_url(); ?>reports/notifications.php?" + formvalues + "&mode=" + type;

        if (type == "submitpdf") {
            $(this).children("i").replaceWith('<i class="fa fa-spin fa-pulse fa-spinner"></i>')
            var icon = $(this).children("i")
            fetch(url).then(submit_report(e, icon))
        } else {
            if (e.which == 2) {
                window.open(url);
            } else if (e.which == 1) {
                window.location = url;
            }
        }
    });

});

var report_sym = 0;
function run_notifications_ajax() {
    report_sym = 1;
    setTimeout('show_loading_report()', 500);

    var formvalues = $("form").serialize();
    formvalues += '&mode=getreport';
    var url = 'notifications.php?'+formvalues;

    current_page = 1;

    $.get(url, {}, function(data) {
        report_sym = 0;
        hide_throbber();
        $('#report').html(data);
        $('#report .tt-bind').tooltip();
    });
}
</script>

<script type='text/javascript' src='<?php echo get_base_url(); ?>includes/js/reports.js?<?php echo get_build_id(); ?>'></script>

<form method="get" id="report-options" data-type="notifications">
    <div class="well report-options form-inline">

        <div class="reportexportlinks">
            <?php echo get_add_myreport_html(_('Notifications'), $_SERVER['PHP_SELF'], array()); ?>
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                    <?php echo _('Download'); ?> <i class="material-symbols-outlined md-20 md-400 md-middle">arrow_drop_down</i>
                </button>
                 <ul class="dropdown-menu" role="menu" aria-labelledby="dLabel">
                    <li><a class="btn-export" data-type="csv" title="<?php echo _("Download as CSV"); ?>"><i class="material-symbols-outlined md-16 md-400 md-middle">description</i> <?php echo _("CSV"); ?></a></li>
                    <li><a class="btn-export" data-type="submitpdf" title="<?php echo _("Download as PDF"); ?>"><i class="material-symbols-outlined md-16 md-400 md-middle">picture_as_pdf</i> <?php echo _("PDF"); ?></a></li>
                </ul>
            </div>
        </div>

        <div class="reportsearchbox">
            <input type="text" size="15" name="search" id="searchBox" value="<?php echo encode_form_val($search); ?>" placeholder="<?php echo _("Search..."); ?>" class="textfield form-control">
        </div>

        <div class="neptune-drawer-options">

        <div class="reportoptionpicker">

            <div class="input-group">
                <label class="input-group-addon"><?php echo _("Period"); ?></label>
                <select id="reportperiodDropdown" name="reportperiod" class="form-control">
                    <?php
                    $tp = get_report_timeperiod_options();
                    foreach ($tp as $shortname => $longname) {
                        echo "<option value='" . $shortname . "' " . is_selected($shortname, $reportperiod) . ">" . $longname . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div id="customdates" class="cal">
                <div class="input-group" style="width: 450px;">
                    <label class="input-group-addon"><?php echo _('From') ?></label>
                    <input class="form-control datetimepicker" type="text" id='startdateBox' name="startdate" value="<?php echo encode_form_val(get_datetime_from_timestring($startdate)); ?>">
                    <div data-picker="startdateBox" class="input-group-btn btn btn-sm btn-default btn-datetimepicker">
                        <i class="material-symbols-outlined md-16 md-400 md-middle">calendar_month</i>
                    </div>
                    <label class="input-group-addon" style="border-left: 0; border-right: 0;"><?php echo _('to') ?></label>
                    <input class="form-control datetimepicker" type="text" id='enddateBox' name="enddate" value="<?php echo encode_form_val(get_datetime_from_timestring($enddate)); ?>">
                    <div data-picker="enddateBox" class="input-group-btn btn btn-sm btn-default btn-datetimepicker">
                        <i class="material-symbols-outlined md-16 md-400 md-middle">calendar_month</i>
                    </div>
                </div>
            </div>
        
            <div class="input-group">
                <label class="input-group-addon"><?php echo _("Limit To"); ?></label>
                <?php if (is_neptune()) { echo neptune_report_option_select(); } ?>
                <select name="host" id="hostList" style="width: 150px;" class="form-control">
                    <option value=""><?php echo _("Host"); ?>:</option>
                    <?php
                    $args = array('brevity' => 1, 'orderby' => 'host_name:a');
                    $oxml = get_xml_host_objects($args);
                    if ($oxml) {
                        foreach ($oxml->host as $hostobject) {
                            $name = strval($hostobject->host_name);
                            echo "<option value='" . $name . "' " . is_selected($host, $name) . ">$name</option>\n";
                        }
                    }
                    ?>
                </select>
                <select name="service" id="serviceList" style="width: 200px; <?php if (empty($service) && empty($host)) { echo 'display: none;'; } ?>" class="form-control">
                    <option value="">[<?php echo _("All Services"); ?>]</option>
                    <?php
                    $args = array('brevity' => 1, 'host_name' => $host, 'orderby' => 'service_description:a');
                    $oxml = get_xml_service_objects($args);
                    if ($oxml) {
                        foreach ($oxml->service as $serviceobj) {
                            $name = strval($serviceobj->service_description);
                            echo "<option value='" . $name . "' " . is_selected($service, $name) . ">$name</option>\n";
                        }
                    }
                    ?>
                </select>
                <select name="hostgroup" id="hostgroupList" style="width: 150px;" class="form-control">
                    <option value=""><?php echo _("Hostgroup"); ?>:</option>
                    <?php
                    $args = array('orderby' => 'hostgroup_name:a');
                    $oxml = get_xml_hostgroup_objects($args);
                    if ($oxml) {
                        foreach ($oxml->hostgroup as $hg) {
                            $name = strval($hg->hostgroup_name);
                            echo "<option value='" . $name . "' " . is_selected($hostgroup, $name) . ">$name</option>\n";
                        }
                    }
                    ?>
                </select>
                <select name="servicegroup" id="servicegroupList" style="width: 150px;" class="form-control">
                    <option value=""><?php echo _("Servicegroup"); ?>:</option>
                    <?php
                    $args = array('orderby' => 'servicegroup_name:a');
                    $oxml = get_xml_servicegroup_objects($args);
                    if ($oxml) {
                        foreach ($oxml->servicegroup as $sg) {
                            $name = strval($sg->servicegroup_name);
                            echo "<option value='" . $name . "' " . is_selected($servicegroup, $name) . ">$name</option>\n";
                        }
                    }
                    ?>
                </select>
            </div>

            <button type="button" id="run" class='btn btn-sm btn-primary' name='reporttimesubmitbutton'><?php echo _("Run"); ?></button>

        </div>

        </div>

        
    </div>
</form>

<div id="report"></div>

<?php
}


// Function for pagination, done via AJAX calls
function get_notifications_page()
{
    global $request;

    // Makes sure user has appropriate license level
    licensed_feature_check();

    // Get values passed in GET/POST request
    $page = grab_request_var("page", 0);
    $records = grab_request_var("records", 10);
    $reportperiod = grab_request_var("reportperiod", "last24hours");
    $startdate = grab_request_var("startdate", "");
    $enddate = grab_request_var("enddate", "");
    $search = grab_request_var("search", "");
    $host = grab_request_var("host", "");
    $service = grab_request_var("service", "");
    $hostgroup = grab_request_var("hostgroup", "");
    $servicegroup = grab_request_var("servicegroup", "");
    $export = grab_request_var("export", 0);

    // Fix custom dates
    if ($reportperiod == "custom") {
        if ($enddate == "") {
            $enddate = date("Y-m-d H:i:s");
        }
        if ($startdate == "") {
            $startdate = date("Y-m-d H:i:s", strtotime("-1 day"));
            $enddate = date("Y-m-d H:i:s");
        }
    }

    // Special "all" stuff
    if ($hostgroup == "all")
        $hostgroup = "";
    if ($servicegroup == "all")
        $servicegroup = "";
    if ($host == "all")
        $host = "";

    // Can do hostgroup OR servicegroup OR host
    if ($hostgroup != "") {
        $servicegroup = "";
        $host = "";
    } else if ($servicegroup != "") {
        $host = "";
    }

    // Limit hosts by hostgroup or host
    $host_ids = array();
    if ($hostgroup != "") {
        $host_ids = get_hostgroup_member_ids($hostgroup, true);
    }

    // Limit service by servicegroup
    $service_ids = array();
    if ($servicegroup != "") {
        $service_ids = get_servicegroup_member_ids($servicegroup);
    }

    $object_ids_str = "";
    $y = 0;

    foreach ($host_ids as $hid) {
        if ($y > 0)
            $object_ids_str .= ",";
        $object_ids_str .= $hid;
        $y++;
    }

    foreach ($service_ids as $sid) {
        if ($y > 0)
            $object_ids_str .= ",";
        $object_ids_str .= $sid;
        $y++;
    }

    // Determine start/end times based on period
    get_times_from_report_timeperiod($reportperiod, $starttime, $endtime, $startdate, $enddate);

    // TOTAL RECORD COUNT (FOR PAGING): if you wanted to get the total count of records in a given timeframe (instead of the records themselves), use this:
    $args = array(
        "starttime" => $starttime,
        "endtime" => $endtime,
        "totals" => 1
    );

    if ($object_ids_str != "") {
        $args["object_id"] = "in:" . $object_ids_str;
    } else {
        if ($host != "") {
            $args["host_name"] = $host;
        }
    }
    if ($service != "") {
        $args["service_description"] = $service;
    }

    $xml = get_notifications_data($args);
    $total_records = 0;
    if ($xml) {
        $total_records = intval($xml->recordcount);
    }

    // Determine paging information
    $args = array(
        "reportperiod" => $reportperiod,
        "startdate" => $startdate,
        "enddate" => $enddate,
        "starttime" => $starttime,
        "endtime" => $endtime,
        "search" => $search,
        "host" => $host,
        "service" => $service,
        "hostgroup" => $hostgroup,
        "servicegroup" => $servicegroup
    );

    $pager_results = get_table_pager_info("", $total_records, $page, $records, $args);
    $first_record = (($pager_results["current_page"] - 1) * $records);

    // SPECIFIC RECORDS (FOR PAGING): if you want to get specific records, use this type of format:
    $args = array(
        "starttime" => $starttime,
        "endtime" => $endtime,
        "records" => $records . ":" . $first_record
    );

    if ($search != '') {
        $args['search'] = $search;
    }

    if ($object_ids_str != "") {
        $args["object_id"] = "in:" . $object_ids_str;
    } else {
        if ($host != "") {
            $args["host_name"] = $host;
        }
    }
    
    if ($service != "") {
        $args["service_description"] = $service;
    }
    
    $xml = get_notifications_data($args);
    $state_width = (is_neptune()) ? '110px' : '80px';

    ?>

    <table class="table table-condensed table-hover table-bordered table-no-margin table-striped<?php if ($export) { echo ' table-export'; }?>">
            <thead>
            <tr>
                <th style="width: 150px;"><?php echo _("Date / Time"); ?></th>
                <th><?php echo _("Host"); ?></th>
                <th><?php echo _("Service"); ?></th>
                <th><?php echo _("Reason"); ?></th>
                <th style="width: 70px;"><?php echo _("Escalated"); ?></th>
                <th style="width: <?php echo $state_width; ?>;"><?php echo _("State"); ?></th>
                <th><?php echo _("Contact"); ?></th>
                <th><?php echo _("Dispatcher"); ?></th>
                <th><?php echo _("Information"); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php
            if ($xml) {
                if ($total_records == 0) {
                    echo "<tr><td colspan='10'>" . _("No matching results found.  Try expanding your search criteria") . ".</td></tr>\n";
                } else foreach ($xml->notification as $not) {

                    $trclass = "";
                    $tdclass = "";

                    $object_type = intval($not->objecttype_id);
                    $host_name = strval($not->host_name);
                    $display_host_name = $host_name;
                    $display_host_name .= get_host_alias($host_name);
                    $service_description = strval($not->service_description);
                    $display_service_description = $service_description;
                    $display_service_description .= get_service_alias($host_name, $service_description);
                    $output = strval($not->output);
                    $contact = strval($not->contact_name);
                    $command = strval($not->notification_command);
                    $reason = intval($not->notification_reason);
                    $escalated = intval($not->escalated);

                    $state = intval($not->state);

                    $reason_text = get_notification_reason_string($reason, $object_type, $state);

                    if ($escalated == 0)
                        $escalated_text = "No";
                    else
                        $escalated_text = "Yes";

                    if ($object_type == OBJECTTYPE_HOST) {
                        $state_text = host_state_to_string($state);
                        switch ($state) {
                            case 0:
                                $trclass = "hostrecovery";
                                $tdclass = "hostup";
                                $dotcolor = "hostup";
                                break;
                            case 1:
                                $trclass = "hostproblem";
                                $tdclass = "hostdown";
                                $dotcolor = "hostdown";
                                break;
                            case 2:
                                $trclass = "hostproblem";
                                $tdclass = "hostunreachable";
                                $dotcolor = "hostunknown";
                                break;
                            default:
                                break;
                        }
                    } else {
                        $state_text = service_state_to_string($state);
                        switch ($state) {
                            case 0:
                                $trclass = "servicerecovery";
                                $tdclass = "serviceok";
                                $dotcolor = "hostup";
                                break;
                            case 1:
                                $trclass = "serviceproblem";
                                $tdclass = "servicewarning";
                                $dotcolor = "hostwarning";
                                break;
                            case 2:
                                $trclass = "serviceproblem";
                                $tdclass = "servicecritical";
                                $dotcolor = "hostdown";
                                break;
                            case 3:
                                $trclass = "serviceproblem";
                                $tdclass = "serviceunknown";
                                $dotcolor = "hostunknown";
                                break;
                            default:
                                break;
                        }
                    }

                    $state_text = (is_neptune()) ? ucfirst(strtolower($state_text)) : $state_text;

                    switch ($command) {
                        case "xi_host_notification_handler":
                        case "xi_service_notification_handler":
                            $dispatcher = "Nagios XI";
                            break;
                        default:
                            $dispatcher = "Custom: $command";
                            break;
                    }

                    $status_bubble = (is_neptune()) ? '<span class="status-dot ' . $dotcolor . ' dot-10"></span>' : '';
                    $tdclass = (is_neptune()) ? 'nowrap' : $tdclass;
                    $info_class = (is_neptune()) ? "class='mono mono-text'" : "";

                    if ($export) {
                        $burl = get_external_url();
                    } else {
                        $burl = get_base_url();
                    }

                    $base_url = $burl . "includes/components/xicore/status.php";
                    $host_url = $base_url . "?show=hostdetail&host=" . urlencode($host_name);
                    $service_url = $base_url . "?show=servicedetail&host=" . urlencode($host_name) . "&service=" . urlencode($service_description);

                    echo "<tr class='" . $trclass . "'>";
                    echo "<td nowrap><span class='notificationtime'>" . $not->start_time . "</span></td>";

                    echo "<td><a href='" . $host_url . "'>" . $display_host_name . "</a></td>";

                    if ($display_service_description == "") {
                        echo "<td>-</td>";
                    } else {
                        echo "<td><a href='" . $service_url . "'>" . $display_service_description . "</a></td>";
                    }
                    echo "<td>" . $reason_text . "</td>";
                    echo "<td>" . $escalated_text . "</td>";
                    echo "<td class='" . $tdclass . "'>" . $status_bubble . $state_text . "</td>";
                    echo "<td>" . $contact . "</td>";
                    echo "<td>" . $dispatcher . "</td>";
                    echo "<td " . $info_class . "><div class='scrollable-30'>" . $output . "</div></td>";
                    echo "</tr>";
                }
            }
            ?>
            </tbody>
        </table>

    <?php
}


function get_notifications_report()
{
    global $request;

    // Makes sure user has appropriate license level
    licensed_feature_check();

    // Get values passed in GET/POST request
    $page = grab_request_var("page", 0);
    $records = grab_request_var("records", get_user_meta(0, 'report_defualt_recordlimit', 10));
    $reportperiod = grab_request_var("reportperiod", "last24hours");
    $startdate = grab_request_var("startdate", "");
    $enddate = grab_request_var("enddate", "");
    $search = grab_request_var("search", "");
    $host = grab_request_var("host", "");
    $service = grab_request_var("service", "");
    $hostgroup = grab_request_var("hostgroup", "");
    $servicegroup = grab_request_var("servicegroup", "");
    $export = grab_request_var("export", 0);

    // Fix custom dates
    if ($reportperiod == "custom") {
        if ($enddate == "") {
            $enddate = date("Y-m-d H:i:s");
        }
        if ($startdate == "") {
            $startdate = date("Y-m-d H:i:s", strtotime("-1 day"));
            $enddate = date("Y-m-d H:i:s");
        }
    }

    // Special "all" stuff
    if ($hostgroup == "all")
        $hostgroup = "";
    if ($servicegroup == "all")
        $servicegroup = "";
    if ($host == "all")
        $host = "";

    // Can do hostgroup OR servicegroup OR host
    if ($hostgroup != "") {
        $servicegroup = "";
        $host = "";
    } else if ($servicegroup != "") {
        $host = "";
    }

    // Limit hosts by hostgroup or host
    $host_ids = array();
    if ($hostgroup != "") {
        $host_ids = get_hostgroup_member_ids($hostgroup, true);
    }

    // Limit service by servicegroup
    $service_ids = array();
    if ($servicegroup != "") {
        $service_ids = get_servicegroup_member_ids($servicegroup);
    }

    $object_ids_str = "";
    $y = 0;

    foreach ($host_ids as $hid) {
        if ($y > 0)
            $object_ids_str .= ",";
        $object_ids_str .= $hid;
        $y++;
    }

    foreach ($service_ids as $sid) {
        if ($y > 0)
            $object_ids_str .= ",";
        $object_ids_str .= $sid;
        $y++;
    }

    // Determine start/end times based on period
    get_times_from_report_timeperiod($reportperiod, $starttime, $endtime, $startdate, $enddate);

    // TOTAL RECORD COUNT (FOR PAGING): if you wanted to get the total count of records in a given timeframe (instead of the records themselves), use this:
    $args = array(
        "starttime" => $starttime,
        "endtime" => $endtime,
        "totals" => 1
    );

    if ($search != '') {
        $args['search'] = $search;
    }

    if ($object_ids_str != "") {
        $args["object_id"] = "in:" . $object_ids_str;
    } else {
        if ($host != "") {
            $args["host_name"] = $host;
        }
    }
    if ($service != "") {
        $args["service_description"] = $service;
    }

    $xml = get_notifications_data($args);
    $total_records = 0;
    if ($xml) {
        $total_records = intval($xml->recordcount);
    }

    // Determine paging information
    $args = array(
        "reportperiod" => $reportperiod,
        "startdate" => $startdate,
        "enddate" => $enddate,
        "starttime" => $starttime,
        "endtime" => $endtime,
        "search" => $search,
        "host" => $host,
        "service" => $service,
        "hostgroup" => $hostgroup,
        "servicegroup" => $servicegroup
    );

    $pager_results = get_table_pager_info("", $total_records, $page, $records, $args);
    $first_record = (($pager_results["current_page"] - 1) * $records);
    $old_page_select = '';

    $title = _("Notifications");
    $sub_title = "";
    if ($service != "") {
        $title = _("Service Notifications");
        $sub_title = "
        <div class='servicestatusdetailheader'>
            <div class='serviceimage'>
                " . get_object_icon($host, $service, true) . "
            </div>
            <div class='servicetitle'>
                <div class='servicename'>
                    <a href='" . get_service_status_detail_link($host, $service) . "'>" . encode_form_val($service) . "</a>" . get_service_alias($host, $service) . "
                </div>
                <div class='hostname'>
                    <a href='" . get_host_status_detail_link($host) . "'>" . encode_form_val($host) . "</a>" . get_host_alias($host) . "
                </div>
            </div>
            <div class='clear'></div>
        </div>";

    } else if ($host != "") {
        $title = _("Host Notifications");
        $sub_title = "
            <div class='hoststatusdetailheader'>
                <div class='hostimage'>
                    " . get_object_icon($host, "", true) . "
                </div>
                <div class='hosttitle'>
                    <div class='hostname'>
                        <a href='" . get_host_status_detail_link($host) . "'>" . encode_form_val($host) . "</a>" . get_host_alias($host) . "
                    </div>
                </div>
                <div class='clear'></div>
            </div>";
    } else if ($hostgroup != "") {
        $title = _("Hostgroup Notifications");
        $sub_title = "
            <div class='hoststatusdetailheader'>
                <div class='hosttitle'>
                    <div class='hostname'>" . encode_form_val($hostgroup) . get_hostgroup_alias($hostgroup) . "</div>
                </div>
            </div>";

    } else if ($servicegroup != "") {
        $title = _("Servicegroup Notifications");
        $sub_title = "
            <div class='hoststatusdetailheader'>
                <div class='hosttitle'>
                    <div class='hostname'>" . encode_form_val($servicegroup) . get_servicegroup_alias($servicegroup) . "</div>
                </div>
            </div>";
    }
    $report_covers_from = "
                <div class='neptune-subtext mb-2'>" . _("Report covers from") . ":
                    <strong>" . get_datetime_string($starttime, DT_SHORT_DATE_TIME, DF_AUTO, "null") . "</strong> " . _("to") . "
                    <strong>" . get_datetime_string($endtime, DT_SHORT_DATE_TIME, DF_AUTO, "null") . "</strong>
                </div>";

    if ($export) {

        do_page_start(array("page_title" => $title), true);

        // Default logo stuff
        $logo = "nagiosxi-logo-small.png";
        $logo_alt = get_product_name();

        // Use custom logo if it exists
        $logosettings_raw = get_option("custom_logo_options");
        if ($logosettings_raw == "") {
            $logosettings = array();
        } else {
            $logosettings = unserialize($logosettings_raw);
        }

        $custom_logo_enabled = grab_array_var($logosettings, "enabled");
        if ($custom_logo_enabled == 1) {
            $logo = grab_array_var($logosettings, "logo", $logo);
            $logo_alt = grab_array_var($logosettings, "logo_alt", $logo_alt);
        }
?>
        <script type='text/javascript' src='<?= get_base_url() ?>includes/js/reports.js?<?= get_build_id() ?>'></script>

        <div style="padding-bottom: 10px;">
            <div style="float: left; margin-right: 30px;">
                <img src="<?= get_base_url() ?>images/<?= $logo ?>" border="0" alt="<?= $logo_alt ?>" title="<?= $logo_alt ?>">
            </div>
            <div style="float: left; height: 44px;">
                <h1 style="margin: 0; padding: 0 0 5px 0;"><?= $title ?></h1>
                <?= $report_covers_from ?>
            </div>
            <div style="clear:both;"></div>
        </div>
        <?= $sub_title ?>
<?php
    } else {
        ?>
        <h1 style="margin-bottom: 10px;"><?= $title ?></h1>
        <?= $sub_title ?>
        <?= $report_covers_from ?>
<?php
    }

    $clear_args = array(
        "reportperiod" => $reportperiod,
        "startdate" => $startdate,
        "enddate" => $enddate,
        "starttime" => $starttime,
        "endtime" => $endtime,
        "search" => $search,
        "host" => $host,
        "service" => $service,
        "hostgroup" => $hostgroup,
        "servicegroup" => $servicegroup
    );
?>
    <div class="recordcounttext">
        <?= table_record_count_text($pager_results, $search, true, $clear_args, '', true) ?>
    </div>
<?php
    if (!$export) {

        if (is_neptune()) {
            ob_start();
?>
        <span class="pager-select-page reports">
            <span>
                <?= _('Page') ?>
            </span>
            <span>
                <input type="text" class="tablepagertextfield condensed pagenum" style="width: 25px;" name="page" value="<?= $pager_results["current_page"] ?>">
                <span class="neptune-slash-small">/</span>  <span class="neptune-text-muted pagetotal pagination-total"><?= get_formatted_number($pager_results["total_pages"], 0) ?></span>
            </span>
        </span>
<?php
            $jump_to = ob_get_clean();
        } else {
            ob_start();
?>
            <?= _('Page') ?> <span class="pagenum"> 1 <?= _('of') ?> <?= $pager_results['total_pages'] ?></span>
<?php
            $jump_to = ob_get_clean();
            ob_start();
?>
        <input type="text" class="form-control condensed jump-to">
        <button class="btn btn-xs btn-default tt-bind jump btn-flex" title="<?= _('Jump to Page') ?>">
            <i class="material-symbols-outlined md-16 md-400 md-middle">expand_circle_right</i>
        </button>
<?php
            $old_page_select = ob_get_clean();
        }
?>
    <div class="ajax-pagination report-pagination neptune-ajax-pagination">
        <div class="pagination-ctrl">
            <button class="<?= (is_neptune()) ? 'reports-' : '' ?>btn btn-xs btn-default first-page btn-flex" title="<?= _('Last Page') ?>"><i class="material-symbols-outlined md-20 md-400 md-middle">keyboard_double_arrow_left</i></button>
            <button class="<?= (is_neptune()) ? 'reports-' : '' ?>btn btn-xs btn-default previous-page btn-flex" title="<?= _('Previous Page') ?>" disabled><i class="material-symbols-outlined md-20 md-400 md-middle">keyboard_arrow_left</i></button>
            <span class="page-count-margin"><?= $jump_to ?></span>
            <button class="<?= (is_neptune()) ? 'reports-' : '' ?>btn btn-xs btn-default next-page btn-flex" title="<?= _('Next Page') ?>"><i class="material-symbols-outlined md-20 md-400 md-middle">keyboard_arrow_right</i></button>
            <button class="<?= (is_neptune()) ? 'reports-' : '' ?>btn btn-xs btn-default last-page btn-flex" title="<?= _('Last Page') ?>"><i class="material-symbols-outlined md-20 md-400 md-middle">keyboard_double_arrow_right</i></button>
        </div>
        <select class="form-control <?= (!is_neptune()) ? 'condensed' : '' ?> num-records">
            <option value="5"<?= ($pager_results['records_per_page'] == 5) ? ' selected' : '' ?>>5 <?= _('Per Page') ?></option>
            <option value="10"<?= ($pager_results['records_per_page'] == 10) ? ' selected' : '' ?>>10 <?= _('Per Page') ?></option>
            <option value="25"<?= ($pager_results['records_per_page'] == 25) ? ' selected' : '' ?>>25 <?= _('Per Page') ?></option>
            <option value="50"<?= ($pager_results['records_per_page'] == 50) ? ' selected' : '' ?>>50 <?= _('Per Page') ?></option>
            <option value="100"<?= ($pager_results['records_per_page'] == 100) ? ' selected' : '' ?>>100 <?= _('Per Page') ?></option>
        </select>
        <?= $old_page_select ?>
    </div>
<?php
    }

    $url_args = array(
        "reportperiod" => $reportperiod,
        "startdate" => $startdate,
        "enddate" => $enddate,
        "starttime" => $starttime,
        "endtime" => $endtime,
        "search" => $search,
        "host" => $host,
        "service" => $service,
        "hostgroup" => $hostgroup,
        "servicegroup" => $servicegroup,
        "export" => intval($export)
    );
?>
    <script>
        var report_url = '<?= get_base_url() ?>reports/notifications.php';
        var report_url_args = <?= json_encode($url_args) ?>;
        var record_limit = <?= $pager_results['records_per_page'] ?>;
        var max_records = <?= $pager_results['total_records'] ?>;
        var max_pages = <?= $pager_results['total_pages'] ?>;

        $(document).ready(function() {
            load_page();
        });
    </script>

    <div class="reportentries">
        <div id="loadscreen" class="hide"></div>
        <div id="loadscreen-spinner" class="sk-spinner sk-spinner-center sk-spinner-rotating-plane hide"></div>
        <div class="report-data" style="min-height: 140px;"></div>
    </div>
<?php
    if (!$export) {
?>
    <div class="ajax-pagination report-pagination  neptune-ajax-pagination">
        <div class="pagination-ctrl">
            <button class="<?= (is_neptune()) ? 'reports-' : '' ?>btn btn-xs btn-default first-page btn-flex" title="<?= _('Last Page') ?>"><i class="material-symbols-outlined md-20 md-400 md-middle">keyboard_double_arrow_left</i></button>
            <button class="<?= (is_neptune()) ? 'reports-' : '' ?>btn btn-xs btn-default previous-page btn-flex" title="<?= _('Previous Page') ?>" disabled><i class="material-symbols-outlined md-20 md-400 md-middle">keyboard_arrow_left</i></button>
            <span class="page-count-margin"><?= $jump_to ?></span>
            <button class="<?= (is_neptune()) ? 'reports-' : '' ?>btn btn-xs btn-default next-page btn-flex" title="<?= _('Next Page') ?>"><i class="material-symbols-outlined md-20 md-400 md-middle">keyboard_arrow_right</i></button>
            <button class="<?= (is_neptune()) ? 'reports-' : '' ?>btn btn-xs btn-default last-page btn-flex" title="<?= _('Last Page') ?>"><i class="material-symbols-outlined md-20 md-400 md-middle">keyboard_double_arrow_right</i></button>
        </div>
        <select class="form-control <?= (!is_neptune()) ? 'condensed' : '' ?> num-records">
            <option value="5"<?= ($pager_results['records_per_page'] == 5) ? ' selected' : '' ?>>5 <?= _('Per Page') ?></option>
            <option value="10"<?= ($pager_results['records_per_page'] == 10) ? ' selected' : '' ?>>10 <?= _('Per Page') ?></option>
            <option value="25"<?= ($pager_results['records_per_page'] == 25) ? ' selected' : '' ?>>25 <?= _('Per Page') ?></option>
            <option value="50"<?= ($pager_results['records_per_page'] == 50) ? ' selected' : '' ?>>50 <?= _('Per Page') ?></option>
            <option value="100"<?= ($pager_results['records_per_page'] == 100) ? ' selected' : '' ?>>100 <?= _('Per Page') ?></option>
        </select>
        <?= $old_page_select ?>
    </div>
<?php
    }

    do_page_end(true);
}


// This function gets the XML records of notification data for multiple
// output formats (CSV, PDF, HTML)
function get_notifications_xml()
{
    global $request;

    // Makes sure user has appropriate license level
    licensed_feature_check();

    // Get values passed in GET/POST request
    $reportperiod = grab_request_var("reportperiod", "last24hours");
    $startdate = grab_request_var("startdate", "");
    $enddate = grab_request_var("enddate", "");
    $search = grab_request_var("search", "");
    $host = grab_request_var("host", "");
    $service = grab_request_var("service", "");
    $hostgroup = grab_request_var("hostgroup", "");
    $servicegroup = grab_request_var("servicegroup", "");

    // Fix custom dates
    if ($reportperiod == "custom") {
        if ($enddate == "") {
            $enddate = date("Y-m-d H:i:s");
        }
        if ($startdate == "") {
            $startdate = date("Y-m-d H:i:s", strtotime("-1 day"));
            $enddate = date("Y-m-d H:i:s");
        }
    }

    // Special "all" stuff
    if ($hostgroup == "all")
        $hostgroup = "";
    if ($servicegroup == "all")
        $servicegroup = "";
    if ($host == "all")
        $host = "";

    // Can do hostgroup OR servicegroup OR host
    if ($hostgroup != "") {
        $servicegroup = "";
        $host = "";
    } else if ($servicegroup != "") {
        $host = "";
    }

    // Limit by hostgroup/servicegroup
    $host_ids = array();
    if ($hostgroup != "") {
        $host_ids = get_hostgroup_member_ids($hostgroup, true);
    }

    $service_ids = array();
    if ($servicegroup != "") {
        $service_ids = get_servicegroup_member_ids($servicegroup);
    }

    $object_ids_str = "";
    $y = 0;
    foreach ($host_ids as $hid) {
        if ($y > 0)
            $object_ids_str .= ",";
        $object_ids_str .= $hid;
        $y++;
    }
    foreach ($service_ids as $sid) {
        if ($y > 0)
            $object_ids_str .= ",";
        $object_ids_str .= $sid;
        $y++;
    }

    // Determine start/end times based on period
    get_times_from_report_timeperiod($reportperiod, $starttime, $endtime, $startdate, $enddate);

    // SPECIFIC RECORDS (FOR PAGING): if you want to get specific records, use this type of format:
    $args = array(
        "starttime" => $starttime,
        "endtime" => $endtime,
    );

    // Searches host, service, contact, and output
    if ($search != '') {
        $args['search'] = $search;
    }

    if ($object_ids_str != "") {
        $args["object_id"] = "in:" . $object_ids_str;
    } else {
        if ($host != "")
            $args["host_name"] = $host;
    }
    if ($service != "") {
        $args["service_description"] = $service;
    }

    $xml = get_notifications_data($args);
    return $xml;
}

// This function generates a CSV file of notification data
function get_notifications_csv()
{
    $xml = get_notifications_xml();

    header("Content-type: application/octet-stream");
    header("Content-Disposition: attachment; filename=\"" . time() ."-notifications.csv\"");

    // Column definitions
    echo "time,host,service,reason,escalated,state,contact,dispatcher,command,information\n";

    if ($xml) {
        foreach ($xml->notification as $not) {

            // What type of log entry is this?  we change the image used for each line based on what type it is...
            $object_type = intval($not->objecttype_id);
            $host_name = strval($not->host_name);
            $service_description = strval($not->service_description);
            if ($object_type == OBJECTTYPE_HOST) {
                $state = host_state_to_string(intval($not->state));
            } else {
                $state = service_state_to_string(intval($not->state));
            }
            $output = strval($not->output);
            $contact = strval($not->contact_name);
            $command = strval($not->notification_command);

            $reason = intval($not->notification_reason);
            $reason_text = get_notification_reason_string($reason, $object_type, $not->state);

            $escalated = intval($not->escalated);

            switch ($command) {
                case "xi_host_notification_handler":
                case "xi_service_notification_handler":
                    $dispatcher = "Nagios XI";
                    break;
                default:
                    $dispatcher = "Custom";
                    break;
            }

            if ($service_description == "")
                $service_description = "-";

            echo $not->start_time . ",\"" . $host_name . "\",\"" . $service_description . "\",\"" . $reason_text . "\",\"" . $escalated . "\",\"" . $state . "\",\"" . $contact . "\"," . $dispatcher . "," . $command . ",\"" . str_replace('&apos;', "'", html_entity_decode($output)) . "\"\n";
        }
    }
}

///////////////////////////////////////////////////////////////////
// HELPER FUNCTIONS
///////////////////////////////////////////////////////////////////

// Return the corresponding image
function get_notification_type_info($objecttype, $state, $statetype, &$img, &$text)
{
    $img = "info.png";
    $text = "";

    if ($objecttype == OBJECTTYPE_HOST) {
        switch ($state) {
            case 0:
                $img = "recovery.png";
                break;
            case 1:
                $img = "critical.png";
                break;
            case 2:
                $img = "critical.png";
                break;
        }
    } else {
        switch ($state) {
            case 0:
                $img = "recovery.png";
                break;
            case 1:
                $img = "warning.png";
                break;
            case 2:
                $img = "critical.png";
                break;
            case 3:
                $img = "unknown.png";
                break;
        }
    }
}