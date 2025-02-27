<?php
//
// Mass Immediate Check Component
// Copyright (c) 2019-2020 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/../../common.inc.php');

// Initialization stuff
pre_init();
init_session();

// Grab GET or POST variables and do prereq/auth checks
grab_request_vars();
check_prereqs();
check_authentication(false);

$title = _("Mass Downtime");
do_page_start(array("page_title" => $title), true);

$selectstate = grab_request_var("selectstate", 0);
$hostgroup = grab_request_var("hostgroup", "");
$servicegroup = grab_request_var("servicegroup", "");
$host = grab_request_var("host", "");
$limit = grab_request_var("object-select", "");

// Get timezone datepicker format
$format = get_user_meta(0, 'date_format');
if (is_null($format)) {
    $format = get_option('default_date_format');
}
if (isset($_SESSION['date_format'])) {
    $format = $_SESSION['date_format'];
}

$js_date = 'mm/dd/yy';
if ($format == DF_ISO8601) {
    $js_date = 'yy-mm-dd';
} else if ($format == DF_US) {
    $js_date = 'mm/dd/yy';
} else if ($format == DF_EURO) {
    $js_date = 'dd/mm/yy';
}

?>

<script type='text/javascript' src='<?php echo get_base_url(); ?>includes/js/reports.js?<?php echo get_build_id(); ?>'></script>
<script type="text/javascript">
    $(document).ready(function() {
        $('#duration_container').hide();

        $('#selectAllButton:button').toggle(function() {
            $('table input:checkbox').attr('checked','checked');
            $(this).val('Unselect All');
        }, function() {
            $('table input:checkbox').removeAttr('checked');
            $(this).val('Select All');
        });

        $('#type').change(function() {
            if ($(this).val() == 'schedule_downtime') {
                $('.downtime_opts').show();
                $('#flexible').change();
            } else if ($(this).val() == 'remove_downtime') {
                $('.downtime_opts').hide();
            }
        });

        $('#flexible').change(function() {
            if (this.checked) {
                $('#duration_container').show();
            } else {
                $('#duration_container').hide();
            }
        });

        // change a parent checkbox
        $('.host.parent').change(function() {
            // grab the id and checked value
            const id = $(this).data('id');
            const checked = $(this).prop('checked');
            // toggle all the children with the same id
            $(`.host.child[data-id=${id}]`).prop('checked', checked || false);
        });

        $('.datetimepicker').datetimepicker({
            dateFormat: '<?php echo $js_date; ?>',
            timeFormat: 'HH:mm:ss',
            showHour: true,
            showMinute: true,
            showSecond: true
        });

        $('.btn-datetimepicker').click(function() {
            if (!$(this).hasClass('disabled')) {
                var id = $(this).data('picker');
                $('#' + id).datetimepicker('show');
            }
        });
    });
</script>

<form method="get">
    <div class="well report-options form-inline">
        <div class="reportexportlinks">
            <?php if(is_neptune()) {
                echo '<span id="perf-options-btn" title="'._('Options').'" class="btn btn-sm btn-default tt-bind icon-in-btn fr" data-placement="bottom"><i class="material-symbols-outlined md-middle md-fill md-400">settings</i></span>';
            } ?>
        </div>
        <div class="neptune-drawer-options">
            <div class="reportoptionpicker">
                <div class="input-group">
                    <label class="input-group-addon"><?php echo _("Limit To"); ?></label>
                    <?php if (is_neptune()) { echo neptune_report_option_select(true, true, true, $limit); } ?>
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
                    <select name="hostgroup" id="hostgroupList" style="width: 150px;" class="form-control">
                        <option value=""><?php echo _("Hostgroup"); ?>:</option>
                        <?php
                        $args = array('orderby' => 'hostgroup_name:a');
                        $oxml = get_xml_hostgroup_objects($args);
                        if ($oxml) {
                            foreach ($oxml->hostgroup as $hg) {
                                $name = strval($hg->hostgroup_name);
                                echo "<option value='" . $name . "' " . is_selected($hostgroup, $name) . ">$name</option>";
                            }
                        }
                        ?>
                    </select>
                    <select name="servicegroup" id="servicegroupList" style="width: 175px;" class="form-control">
                        <option value=""><?php echo _("Servicegroup"); ?>:</option>
                        <?php
                        $args = array('orderby' => 'servicegroup_name:a');
                        $oxml = get_xml_servicegroup_objects($args);
                        if ($oxml) {
                            foreach ($oxml->servicegroup as $sg) {
                                $name = strval($sg->servicegroup_name);
                                echo "<option value='" . $name . "' " . is_selected($servicegroup, $name) . ">$name</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="input-group flex">
                    <label class="input-group-addon"><?php echo _("Status"); ?></label>
                    <select name="selectstate" id="selectstate" style="width: 150px;" class="rounded-r form-control">
                        <?php
                        echo "<option value='0' " . is_selected($selectstate, 0) . ">"._("Show All")."</option>";
                        echo "<option value='1' " . is_selected($selectstate, 1) . ">"._("Show In Downtime")."</option>";
                        ?>
                    </select>
                    <input type="submit" style="margin-left: 10px;" id="runButton" class='btn btn-sm btn-primary' name='runButton' value="<?php echo _("Update"); ?>">
                </div>
            </div>
        </div>
    </div>
</form>
<?php

/////////////////////////////////
// Filtering by group/state
/////////////////////////////////

$submitted = grab_request_var('submitted', false);
$feedback = '';

// Display output from command submissions 
if ($submitted) {
    $hosts = grab_request_var("hosts", []);
    $services = grab_request_var("services", []);
    $feedback = '';
    $mode = grab_request_var('type', '');
    
    if ($mode == "schedule_downtime") {
        $feedback = massdowntime_schedule_downtimes($hosts, $services);
    } else if ($mode == "remove_downtime") {
        $feedback = massdowntime_remove_downtimes($hosts, $services);
    } else {
        return display_message(true, false, _('How tf did you specify an invalid mode?'));
    }
}

if (is_readonly_user(0)) {
    $html = _("You are not authorized to use this component.");
} else {
    $hosts = massdowntime_get_hosts($selectstate);
    $services = massdowntime_get_services($selectstate, $hosts);
    $html = massdowntime_build_html($hosts, $services, $feedback);
}

print $html;

function massdowntime_get_hosts($selectstate = 0) {
    $hostgroup = grab_request_var("hostgroup", "");
    $servicegroup = grab_request_var("servicegroup", "");
    $host = grab_request_var("host", "");

    $hosts = [];
    $host_ids = [];
    $backendargs = [];
    if ($selectstate == 1) {
        $backendargs["scheduled_downtime_depth"] = "gt:0";
    }

    if (!empty($host)) {
        $hosts[] = $host;
    }

    if (!empty($hostgroup)) {
        $host_ids = get_hostgroup_member_ids($hostgroup);
    }

    if (!empty($servicegroup)) {
        $host_ids = get_servicegroup_host_member_ids($servicegroup);
    }

    if (!empty($hosts)) {
        $backendargs['host_name'] = "in:".implode(',', $hosts);
    }

    if (!empty($host_ids)) {
        $backendargs['host_id'] = "in:".implode(',', $host_ids);
    }

    $xml = get_xml_host_status($backendargs);
    $hosts = [];
    if ($xml) {
        foreach ($xml->hoststatus as $xmlhost) {
            $problem = true;
            if (("$xmlhost->current_state" == 0 && "$xmlhost->has_been_checked" == 1) || "$xmlhost->scheduled_downtime_depth" > 0 || "$xmlhost->problem_acknowledged" > 0) {
                $problem = false;
            }
            $hosts["$xmlhost->name"] = array(
                'host_state' => "$xmlhost->current_state", 
                'host_name' => "$xmlhost->name",
                'plugin_output' => "$xmlhost->status_text",
                'last_check' => "$xmlhost->last_check",
                'host_id' => "$xmlhost->host_id",
                'problem' => $problem,
                'scheduled_downtime_depth' => "$xmlhost->scheduled_downtime_depth"
            );
        }
    }
    return $hosts;
}

function massdowntime_get_services($selectstate = 0, &$hosts = []) {
    $services = [];
    $backendargs = [];
    $host_ids = [];
    if ($selectstate == 1) {
        $backendargs["scheduled_downtime_depth"] = "gt:0";
    }

    if (!empty($host)) {
        $backendargs["host_name"] = $host;
    }

    if (!empty($servicegroup)) {
        $service_ids = get_servicegroup_member_ids($servicegroup);
    }

    if (!empty($service_ids)) {
        $backendargs['service_id'] = "in:".implode(',', $service_ids);
    }

    $xml = get_xml_service_status($backendargs);
    if ($xml) {
        foreach ($xml->servicestatus as $xmlservice) {
            $service = array(
                'host_name' => "$xmlservice->host_name",
                'service_description' => "$xmlservice->name",
                'current_state' => "$xmlservice->current_state",
                'plugin_output' => "$xmlservice->status_text",
                'last_check' => "$xmlservice->status_update_time",
                'service_id' => "$xmlservice->service_id",
                'scheduled_downtime_depth' => "$xmlservice->scheduled_downtime_depth"
            );
            $services["$xmlservice->host_name"][] = $service;

            if (empty($hosts) && !in_array("$xmlservice->host_id", $host_ids)) {
                $host_ids[] = "$xmlservice->host_id";
            }
        }
    }

    // Run it back if no hosts were found but services were
    if (!empty($host_ids)) {
        $backendargs = [];
        $backendargs['host_id'] = "in:".implode(',', $host_ids);
        $xml = get_xml_host_status($backendargs);
        if ($xml) {
            foreach ($xml->hoststatus as $xmlhost) {
                $problem = true;
                if (("$xmlhost->current_state" == 0 && "$xmlhost->has_been_checked" == 1) || "$xmlhost->scheduled_downtime_depth" > 0 || "$xmlhost->problem_acknowledged" > 0) {
                    $problem = false;
                }
                $hosts["$xmlhost->name"] = array(
                    'host_state' => "$xmlhost->current_state", 
                    'host_name' => "$xmlhost->name",
                    'plugin_output' => "$xmlhost->status_text",
                    'last_check' => "$xmlhost->last_check",
                    'host_id' => "$xmlhost->host_id",
                    'problem' => $problem,
                    'scheduled_downtime_depth' => "$xmlhost->scheduled_downtime_depth"
                );
            }
        }
    }
    return $services;
}

function massdowntime_build_html($hosts, $services, $feedback) {
    $flexible = grab_request_var("flexible", 0);

    $html = "
        <h1>" . _('Mass Downtime') . "</h1>
        {$feedback}
        <div class='text-medium'>" . _("Use this tool to schedule downtime on large groups of hosts/services.") . "</div>";

    $html .= "<form id='form_massdowntime' action='index.php' method='post'>";

    $html .= "
        <div class='well submit-commands-box'>
            <div class='input-group'>
                <label class='input-group-addon' for='type'>" . _('Command Type') . "</label>
                <input type='hidden' id='submitted' name='submitted' value='true' />

                <select name='type' id='type' class='form-control width-fit'>
                    <option value='schedule_downtime'>" . _("Schedule Downtime") . "</option>
                    <option value='remove_downtime'>" . _("Remove Downtime") . "</option>
                </select>
            </div>

            <div class='downtime_opts'>
                <div id='customdates' class='input-group'>
                    <label class='input-group-addon width-fit'>" . _('From') . "</label>
                    <input class='form-control datetimepicker' type='text' id='start_time_box' name='start_time' value='" . encode_form_val(get_datetime_string(time())) . "'>
                    <div id='start_time_btn' data-picker='start_time_box' class='input-group-btn btn-flex btn btn-sm btn-default btn-datetimepicker'>
                        <i class='material-symbols-outlined md-16 md-400 md-middle'>calendar_month</i>
                    </div>
                    <label class='input-group-addon width-fit'>" . _('to') . "</label>
                    <input class='form-control datetimepicker' type='text' id='end_time_box' name='end_time' value='" . encode_form_val(get_datetime_string(strtotime('now + 2 hours'))) . "'>
                    <div id='end_time_btn' data-picker='end_time_box' class='input-group-btn btn btn-flex btn-sm btn-default btn-datetimepicker'>
                        <i class='material-symbols-outlined md-16 md-400 md-middle'>calendar_month</i>
                    </div>
                </div>
            </div>
                
            <div class='downtime_opts'>
                <div class='input-group flex-grow-1'>
                    <label class='input-group-addon' for='comment'>" . _("Comment") . "</label>
                    <input type='text' class='form-control' id='comment' name='comment' value='" . _('In downtime via mass action.') . "'>
                </div>
            </div>
            
            <div class='downtime_opts'>
                <div class='checkbox align-items-center-flex'>
                    <input type='checkbox' class='checkbox m-0' name='flexible' id='flexible'" . is_checked($flexible, 1) . " value='1'>
                    <label for='flexible' class='text-medium ml-2'>
                        " . _('Flexible') . "
                    </label>
                </div>
            </div>

            <div id='duration_container' class='downtime_opts input-group align-items-center-flex gap-2'>
                <div class='input-group'>
                    <label class='input-group-addon' for='duration'>" . _("Duration") . "</label>
                    <input type='text' class='form-control' id='duration' name='duration' size='2' value='" . 120 . "'>
                </div>
                
                <span class='neptune-subtext'>" . _('min') . "</span>
            </div>

            <button type='submit' class='btn btn-sm btn-primary' id='submit'>" . _("Submit Commands") . "</button>
        </div>
        <div>                
            <input type='button' class='btn btn-sm btn-default fl' id='selectAllButton'  title='" . _('Select All Hosts and Services') . "' value='" . _("Select All") . "'>
            <div class='clear'></div>
        </div> 
        <div class='w-full mt-20'>
            <table class='table table-condensed table-striped table-bordered table-auto-width w-full' id='massdowntime_table'>
                <thead>
                    <tr class='center'>
                        <th>" . _("Host") . "</th>
                        <th>" . _("Service") . "</th>
                        <th>" . _("Last Check") . "</th>
                        <th>" . _("Downtime") . "</th>
                        <th>" . _("Status Information") . "</th>
                    </tr>
                </thead>
                <tbody>";

    $hostcount = 0;
    foreach ($hosts as $host) {
        $host_class = host_class($host['host_state']);

        $service_html = "";

        // Verify we have services
        if (isset($services[$host['host_name']])) {
            foreach ($services[$host['host_name']] as $service) {
                if (is_neptune()) {
                    $service_html .= "
                <tr>  
                    <td></td>
                    <td class=''>
                        <div class='checkbox'>
                                <label class='flex items-center'>
                                    <input class='mass-downtime-service-box host child host{$hostcount}' data-id='{$hostcount}' type='checkbox' name='services[]' value='{$service['host_name']},{$service['service_description']},{$service['service_id']}'>
                                    <div class='flex items-center'>
                                        <span class='status-dot dot-10 " . service_class($service['current_state']) . "'></span>
                                    </div>
                                    {$service['service_description']}
                            </label>
                        </div>
                    </td>
                    <td>
                        <div class='last_check'>{$service['last_check']}</div>
                    </td>
                    <td>
                        <div class='last_check'>{$service['scheduled_downtime_depth']}</div>
                    </td>
                    <td>
                        <div class='plugin_output scrollable-30 neptune-geist-mono subtext'>{$service['plugin_output']}</div>
                    </td>
                </tr>";
                } else {
                    $service_html .= "
                <tr>  
                    <td></td>
                    <td class='" . service_class($service['current_state']) . "'>
                        <div class='checkbox'>
                            <label>
                                <input class='host child host{$hostcount}' data-id='{$hostcount}' type='checkbox' name='services[]' value='{$service['host_name']},{$service['service_description']},{$service['service_id']}'>
                                {$service['service_description']}
                            </label>
                        </div>
                    </td>
                    <td>
                        <div class='last_check'>{$service['last_check']}</div>
                    </td>
                    <td>
                        <div class='last_check'>{$service['scheduled_downtime_depth']}</div>
                    </td>
                    <td>
                        <div class='plugin_output'>{$service['plugin_output']}</div>
                    </td>
                </tr>";
                }
            
            }
        }

        if ($host['problem'] || $host['scheduled_downtime_depth'] || !empty($service_html)) {
            if (is_neptune()) {
                $html .= "
                <tr>
                    <td>
                        <div class='checkbox'>
                            <label class='flex items-center'>
                                <div class='flex items-center'>
                                    <span class='status-dot dot-10 {$host_class}'></span> 
                                </div>
                                <input class='massdowntime-checkbox' type='checkbox' name='hosts[]' value='{$host['host_name']},{$host['host_id']}'>{$host['host_name']}
                            </label>
                        </div>
                    </td>
                    <td>";
            } else {
                $html .= "
                <tr>
                    <td class='{$host_class}'>
                        <div class='checkbox'>
                            <label>
                                <input type='checkbox' name='hosts[]' value='{$host['host_name']},{$host['host_id']}'>{$host['host_name']}
                            </label>
                        </div>
                    </td>
                    <td>";
            }

            if (!empty($service_html)) {
                $html .= "<div class='checkbox'>
                        <label>
                            <input class='host parent host{$hostcount}' data-id='{$hostcount}' id='selectAllService' type='checkbox' value='{$host['host_name']}'><a>Select all {$host['host_name']} services</a>
                        </label>
                    </div>";
            }

            $html .= "</td>
                <td>
                    {$host['last_check']}
                </td>
                <td>
                    {$host['scheduled_downtime_depth']}
                </td>
                <td>
                    <div class='neptune-geist-mono subtext'>
                        {$host['plugin_output']}
                    </div>
                </td>
            </tr>
            ";

            $html .= $service_html;

            $hostcount++;

        }
    }

    if ($hostcount == 0) {
        $html .= "<tr>
            <td colspan='4'>"._("No hosts or services were found in downtime. To view all hosts and services, change the filter in the report options.")."</td>
        </tr>";
    }

    $html .= "</tbody></table></div><div class='clear'></div></form>";
    return $html;
}

function massdowntime_schedule_downtimes($hosts, $services) {

    // Bail if missing required values
    if (count($hosts) == 0 && count($services) == 0) {
        return display_message(true, false, _('You must specify at least one service.'));
    }

    $comment = grab_request_var('comment', '');
    $start_time = grab_request_var('start_time', 0);
    $end_time = grab_request_var('end_time', 0);
    $flexible = grab_request_var('flexible', 0);
    $duration = grab_request_var('duration', 0);

    if ($comment == '') {
        return display_message(true, false, _('You must specify a comment.'));
    }

    // Check for errors
    if (!$start_time || !$end_time) {
        return display_message(true, false, _('You must specify a start time and an end time.'));
    }

    // Check to make sure start_time is not before end_time
    $start_time = nstrtotime($start_time);
    $end_time = nstrtotime($end_time);
    if ($start_time > $end_time) {
        return display_message(true, false, _('You must select a start time that is before the end time.'));
    }

    // Generate command arguments
    $args = array(
        "comment_data" => $comment,
        "comment_author" => get_user_attr(0, 'name'),
        "start_time" => $start_time,
        "end_time" => $end_time,
        "fixed" => 1,
        "trigger_id" => 0
    );

    // If flexible, add a duration value
    if ($flexible == 1) {
        $args['fixed'] = 0;
        $args['duration'] = $duration * 60;
    }

    $errors = 0;
    // Schedule each host and service in the list of hosts and services selected
    foreach ($hosts as $host) {
        $args['host_name'] = explode(',', $host)[0];
        $core_cmd = core_get_raw_command(NAGIOSCORE_CMD_SCHEDULE_HOST_DOWNTIME, $args);
        $x = core_submit_command($core_cmd, $output);
        if (!$x) {
            $errors++;
        }
    }
    foreach ($services as $service) {
        $host_service = explode(',', $service);
        $args['host_name'] = $host_service[0];
        $args['service_name'] = $host_service[1];
        $core_cmd = core_get_raw_command(NAGIOSCORE_CMD_SCHEDULE_SVC_DOWNTIME, $args);
        $x = core_submit_command($core_cmd, $output);
        if (!$x) {
            $errors++;
        }
    }

    $msg = '';
    if ($errors) {
        return display_message(true, false, _('One or more scheduled commands could not be sent to Nagios Core.'));
    }
    return display_message(false, true, _('Successfully added all downtime. It may take up to a minute to show up on the list.'));
}

function massdowntime_remove_downtimes($hosts, $services) {

    // Bail if missing required values
    if (count($hosts) == 0 && count($services) == 0) {
        return display_message(true, false, _('You must specify at least one service.'));
    }

    $count = 0;
    $object_ids = "";
    
    foreach ($hosts as $host) {
        $host_id = explode(',', $host)[1];
        if ($count > 0) {
            $object_ids .= ",";
        }
        $object_ids .= "$host_id";
        $count++;
    }

    foreach ($services as $service) {
        $service_id = explode(',', $service)[2];
        if ($count > 0) {
            $object_ids .= ",";
        }
        $object_ids .= "$service_id";
        $count++;
    }

    $backendargs = array("object_id" => "in:" . $object_ids);
    $downtimes = get_scheduled_downtime($backendargs);

    if (count($downtimes) == 0) {
        return display_message(true, false, _('There are no hosts or services in downtime.'));
    }

    $errors = 0;
    foreach ($downtimes as $downtime) {
        // Delete downtime based on type
        if ($downtime['downtime_type'] == 2) {
            $cmd = NAGIOSCORE_CMD_DEL_HOST_DOWNTIME;
        } else {
            $cmd = NAGIOSCORE_CMD_DEL_SVC_DOWNTIME;
        }

        $core_cmd = core_get_raw_command($cmd, array('downtime_id' => $downtime['internal_downtime_id']));
        $x = core_submit_command($core_cmd, $output);
        if (!$x) {
            $errors++;
        }
    }
    $msg = '';
    if ($errors) {
        return display_message(true, false, _('One or more downtime removal commands could not be sent to Nagios Core.'));
    }
    return display_message(false, true, _('Successfully removed all selected downtimes.'));
}

function host_class($code, $has_been_checked = 1) {
    if ($has_been_checked != 1)
        return '';
    switch ($code) {
        case 0:
            return "hostup";
        case 1:
            return 'hostdown';
        default:
            return 'hostunreachable';
    }
}

function service_class($code, $has_been_checked = 1) {
    if ($has_been_checked != 1)
        return '';
    switch ($code) {
        case 0:
            return "serviceok";
        case 1:
            return 'servicewarning';
        case 2:
            return 'servicecritical';
        default:
            return 'serviceunknown';
    }
}