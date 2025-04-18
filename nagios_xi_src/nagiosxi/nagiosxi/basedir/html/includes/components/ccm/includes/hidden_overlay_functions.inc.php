<?php
//
//  Nagios Core Config Manager
//  Copyright (c) 2010-2019 Nagios Enterprises, LLC
//
//  File: hidden_overlay_function.inc.php
//  Desc: Creates the HTML for all of the hidden overlays.
//


/**
 * Builds a hidden overlay div and populates values based on parameters given 
 *
 * @param   string  $type           Nagios object type (host, service, command, etc)
 * @param   string  $optionValue    The DB fieldname for that objects name (host_name, service_description, template_name)
 * @param   bool    $BA             Boolean switch, are there two-way relationships possible for this object (host->hostgroup, hostgroup->host) 
 * @param   bool    $tplOpts        Boolean switch for showing template options 
 * @param   string  $fieldArray     Optional specification for which select list to use
 * @return  string                  Returns populated html select lists for the $type object
 */
function build_hidden_overlay($type, $optionValue, $BA=false, $tplOpts=false, $fieldArray='', $exactType='')
{
    global $FIELDS;
    global $unique;
    $ccm_restricted = false;
    $curr_page = ccm_grab_request_var('type');

    // Check for permissions
    if (get_user_meta(0, 'ccm_access') == 2 && !is_authorized_for_all_objects() && !is_admin()) {
        $ccm_restricted = true;
        $ccm_user_access = array();
        $ccm_user_access['host'] = ccm_get_user_object_ids('host');
        $ccm_user_access['service'] = ccm_get_user_object_ids('service');
    }

    $full_title = _('Manage') . ' ' . ccm_get_full_title($type, true);

    $Title = ucfirst($type); 
    $Titles = ucfirst($type).'s'; 
    if ($fieldArray == '') {
        $fieldArray = 'sel'.$Title.'Opts'; 
    }

    $html = "<!-- ------------------------------------ {$Titles} ($type) --------------------- -->

    <div class='overlay' id='{$type}Box'>

    <div class='overlay-title'>
        <h2>{$full_title}</h2>
        <div class='overlay-close ccm-tt-bind' data-placement='left' title='"._('Close')."' onclick='killOverlay(\"{$type}Box\")'><i class='material-symbols-outlined md-400 material-icon-va'>close</i></div>
        <div class='clear'></div>
    </div>

    <div class='left'>
        <div class='listDiv'>
            <div class='filter'>
                <span class='clear-filter ccm-tt-bind' title='"._('Clear')."'><i class='material-symbols-outlined md-400 material-icon-va'>close</i></span>
                <input type='text' id='filter{$Titles}' class='form-control fc-fl form-control-bottom-open' style='border-bottom: 0;' placeholder='"._('Filter')."...'>
            </div>
            <select name='sel{$Titles}[]' class='form-control fc-m lists form-control-top-open' multiple='multiple' id='sel{$Titles}' ondblclick='transferMembers(\"sel{$Titles}\", \"tbl{$Titles}\", \"{$type}s\")'>
                <!-- option value is tbl ID -->
    ";
    
    if(is_neptune()) {
        $html = "<!-- ------------------------------------ {$Titles} ($type) --------------------- -->

        <div class='overlay' id='{$type}Box'>
    
        <div class='overlay-title'>
            <h2>{$full_title}</h2>
            <div class='overlay-close ccm-tt-bind' data-placement='left' title='"._('Close')."' onclick='killOverlay(\"{$type}Box\")'><span class='material-symbols-outlined md-400 ccm-close-icon'>close</span></div>
            <div class='clear'></div>
        </div>
    
        <div class='left'>
            <div class='listDiv'>
                <div class='filter'>
                    <span class='clear-filter ccm-tt-bind' title='"._('Clear')."'><i class='material-symbols-outlined md-400 material-icon-va'>close</i></span>
                    <input type='text' id='filter{$Titles}' class='form-control fc-fl form-control-bottom-open' style='border-bottom: 0;' placeholder='"._('Filter')."...'>
                </div>
                <select name='sel{$Titles}[]' class='form-control fc-m lists form-control-top-open overlay-select' multiple='multiple' id='sel{$Titles}' ondblclick='transferMembers(\"sel{$Titles}\", \"tbl{$Titles}\", \"{$type}s\")'>
                    <!-- option value is tbl ID -->
        ";
    }

    // Special case for hostService array
    if ($type == 'hostservice') {
        foreach ($FIELDS['selHostServiceOpts'] as $key => $opt) {
            $disabled = '';
            $selected = false;

            if (in_array($key, $FIELDS['pre_hostservices_AB'])) {
                $selected = true;
            }

            $text = $opt['name'];

            // Remove text if user does not have access
            if ($ccm_restricted) {
                list($host_id, $service_id) = explode('::0::', $key);
                if (!in_array($host_id, $ccm_user_access['host']) && !in_array($service_id, $ccm_user_access['service'])) {
                    if ($text == '*') {
                        $opt['active'] = 0;
                    }
                    else if (!$selected) {
                        continue;
                    }
                    else {
                        $text = _("Unknown");
                    }
                }
            }

            $html .= "<option ";
            if (grab_array_var($opt, 'active', 1) == 0) {
                $disabled = " disabled='disabled' class='disabled'";
            }

            // Set hostservice as selected (moves it to the other side)
            if ($selected) {
                $html .= "selected='selected' ";
            }

            if (in_array($key, $FIELDS['pre_hostservices_BA'])) {
                $disabled .= "disabled='disabled' class='hiddenDependency' ";
            }

            $html .= " id='".$unique++."' title='".$text."' value='".$key."'".$disabled.">".encode_form_val($text)."</option>";
        }
    } else if ($type == 'parent') {
        foreach ($FIELDS['selParentOpts'] as $key => $opt) {
            $selected = false;

            // The pre_hosts_BA are child elements of a parent host
            $pre_array = isset($FIELDS['pre_'.$type.'s_AB']) ? $FIELDS['pre_'.$type.'s_AB'] : $FIELDS['pre_'.$type.'s'] ;
            $child = '';

            if (in_array($opt['id'], $pre_array)) {
                $selected = true;
            }

            $text = $opt[$optionValue];

            // Remove text if user does not have access
            if ($ccm_restricted) {
                if (!in_array($opt['id'], $ccm_user_access['host'])) {
                    if (!$selected) {
                        continue;
                    }
                    $text = _("Unknown");
                }
            }

            $html .= '<option ';

            // Set as a selected parent host (moves it to the other side)
            if ($selected) {
                $html .= "selected='selected' orderid='".array_search($opt['id'], $pre_array)."' ";
            }

            if (in_array($opt['id'], $FIELDS['pre_hosts_BA'])) {
                $html .= 'disabled="disabled" class="child" ';
                $child = ' ['._('Child').']';
            } else if ($opt['active'] == 0) {
                $html .= ' disabled="disabled" class="disabled"';
            }

            $html .= ' id="'.$unique++.'" title="'.$text.'" value="'.$opt['id'].'">'.encode_form_val($text.$child).'</option>';
        }
    }
    // If there are two-way database relationships for this object
    else if ($BA == true) {
        foreach ($FIELDS[$fieldArray] as $opt) {
            $selected = false;

            // Set objects as selected (moves it to the other side)
            if (in_array($opt['id'], $FIELDS['pre_'.$type.'s_AB'])) {
                $selected = true;
            }

            $text = $opt[$optionValue];

            // Add alias for contacts only
            if ($type == 'contact') {
                if (@isset($opt['alias'])) {
                    $text .= ' (' . $opt['alias'] . ')';
                }
            }

            // Remove text if user does not have access
            if (($type == 'host' || $type == 'service') && $ccm_restricted) {
                if (!in_array($opt['id'], $ccm_user_access[$type])) {
                    if ($text == '*') {
                        $opt['active'] = 0;
                    }
                    else if (!$selected) {
                        continue;
                    }
                    else {
                        $text = _("Unknown");
                    }
                }
            }
            else if (($type == 'hostgroup' || $type == 'contactgroup' || $type == 'contact') && $ccm_restricted) {
                if ($text == '*') {
                    $opt['active'] = 0;
                }
            }

            // Remove * option for contacts on service escalations
            if ($curr_page == 'serviceescalation' && $type == 'contact' && $text == '*') {
                continue;
            }

            $html .= '<option ';
            if ($selected) {
                $html .= "selected='selected' ";
            }
            
            if (in_array($opt['id'], $FIELDS['pre_'.$type.'s_BA'])) {
                $html .= "disabled='disabled' class='hiddenDependency picker-option' title='"._('Object has a relationship established elsewhere')."' ";
            } else if (grab_array_var($opt,'active',1) == 0) {
                // If the object is not active we should turn it to disabled
                $html .= "disabled='disabled' class='disabled picker-option' ";
            }

            if ($type == 'host' || $type == 'hostgroup') {
                if (in_array($opt['id'], $FIELDS['pre_'.$type.'s_AB_exc'])) { $html .= 'data-exclude="1" '; }
            }

            $html .= " id='".$unique++."' title='$text' value='".$opt['id']."'>".encode_form_val($text).'</option>';
        }
    // Only one-way DB relationships (i.e. service dependency)
    } else {
        $pre_array = isset($FIELDS['pre_'.$type.'s_AB']) ? $FIELDS['pre_'.$type.'s_AB'] : $FIELDS['pre_'.$type.'s'] ;
        /* uniq_services and html_elements are same-indexed */
        $uniq_services = array();
        $html_elements = array();
        $current_index = 0;

        foreach ($FIELDS[$fieldArray] as $opt) {
            $selected = false;

            // If it needs to be a unique service let's only display a service name once
            if ($exactType == "serviceescalation" || $exactType == "servicedependency") {
                $current_index = count($uniq_services);
                if (!in_array($opt[$optionValue], $uniq_services)) {
                    $uniq_services[$current_index] = $opt[$optionValue];
                }
                else if (in_array($opt['id'], $pre_array)) {
                    // This service is the one that's actually selected,
                    // so we really ought to overwrite the other one.
                    $current_index = array_search($opt[$optionValue], $uniq_services, true);
                }
                else {
                    continue;
                }
            } else {
                $current_index++;
            }

            $text = $opt[$optionValue];

            // Remove * option for contacts on service escalations
            if ($curr_page == 'serviceescalation' && $type == 'contactgroup' && $text == '*') {
                continue;
            }

            if (in_array($opt['id'], $pre_array)) {
                $selected = true;
            }

            // Display hostnames just for the service/host dependencies (and don't display in serviceescalation form)
            if ($type == "service" && $exactType != "serviceescalation" && $exactType != "servicedependency") {
                if ($text != "*") {
                    $text = $opt['host_name'] . " - " . $text;
                }
            }

            // Remove text if user does not have access
            if (($type == 'host' || $type == 'service' || $type == 'servicedependency' || $type == 'hostdependency') && $ccm_restricted) {
                $usetype = $type;
                if ($type == 'servicedependency') {
                    $usetype = 'service';
                } else if ($type == 'hostdependency') {
                    $usetype = 'host';
                }
                if (!in_array($opt['id'], $ccm_user_access[$usetype])) {
                    if ($text == '*') {
                        $opt['active'] = 0;
                    }
                    else if (!$selected) {
                        continue;
                    }
                    else {
                        $text = _("Unknown");
                    }
                }
            }

            if (($curr_page == "serviceescalation" || $curr_page == "hostescalation") && $ccm_restricted){
                if ($text == '*') {
                    $opt['active'] = 0;
                }
            }

            $html_elements[$current_index] = '<option ';
            $disabled = "";
            
            if (grab_array_var($opt,'active',1) == 0) {
                $disabled = " disabled='disabled' class='disabled'";
            }
            
            if ($selected) {
                $html_elements[$current_index] .= "selected='selected' orderid='".array_search($opt['id'], $pre_array)."'";
            }

            if ($type == 'host' || $type == 'hostgroup' || $type == 'service' || $type == 'servicedependency') {
                if (in_array($opt['id'], $FIELDS['pre_'.$type.'s_AB_exc'])) {
                    $html_elements[$current_index] .= 'data-exclude="1" ';
                }
            }

            $html_elements[$current_index] .= " id='".$unique++."' title='".encode_form_val($text)."' value='".encode_form_val($opt['id'])."'".$disabled.">".encode_form_val($text).'</option>';
        }

        $html .= implode('', $html_elements);
    }
    $html .= "</select>
            <div class='overlay-left-bottom'>
                <button type='button' class='btn btn-sm btn-primary ccm-overlay-add icon-in-btn' onclick='transferMembers(\"sel{$Titles}\", \"tbl{$Titles}\", \"{$type}s\")'>"._("Add Selected")." <i class='material-symbols-outlined md-20 md-400'>chevron_right</i></button>";

    if(is_neptune()) {
        $html .= " 
                    <div class='ccm-label ccm-overlay-info' style='margin-right: 20px;'>
                        <div class='ccm-overlay-legend-line'><span class='material-symbols-outlined ccm-overlay-icon-override'>group</span> <span class='ccm-tt-bind qtt' title='"._('An example of a relationship that can only be linked in one direction is a child host with a host defined as the parent cannot be set as a child from the host it is already a child of.')."'>"._("Relationship defined elsewhere")."</span></div>
                        <div class='ccm-overlay-legend-line'><span class='material-symbols-outlined ccm-overlay-icon-override'>error</span> "._('Inactive object')."</div>
                    </div>

                    <div class='clear'></div>
                </div>

                <div class='closeOverlay'>
                    <button type='button' class='btn btn-sm btn-default' onclick='killOverlay(\"{$type}Box\")'>"._("Close")."</button>
                </div>

            </div>
        </div>
        <!-- end leftBox -->

        <div class='right'>
            <div class='right-container'>
                <table class='table table-no-margin table-small-border-bottom table-x-condensed table-assigned-header'>
                    <thead>
                        <tr>
                            <th colspan='2' class='form-control-bottom-open table-assigned-header'>
                                <span class='thMember'>"._("Assigned")."</span>
                                <a class='fr' title='"._("Remove All")."' href='javascript:void(0)' onclick=\"removeAll('tbl{$Titles}')\">"._("Remove All")."</a>
                                <div class='clear'></div>
                            </th>
                        </tr>
                    </thead>
                </table>
                <div class='assigned-container form-control-top-open lists'>
                    <table class='table table-x-condensed table-hover table-assigned' id='tbl{$Titles}'>
                        <tbody>
                            <!-- insert selected items here -->
                        </tbody>
                    </table>
                </div>";
                if ($tplOpts) { // Template options

                    $radType = $type.'s';

                    // Deal with inconsistent DB naming convention in NagiosQL. Make sure we have the correct form field name
                    $radType = (isset($FIELDS['contact_groups_tploptions']) && $type=='contactgroup') ? 'contact_groups' : $radType;
                    $radType = (isset($FIELDS['host_name_tploptions']) && $type=='host') ? 'host_name' : $radType;
                    $radType = (isset($FIELDS['hostgroup_name_tploptions']) && $type=='hostgroup') ? 'hostgroup_name' : $radType;
                    $radType = ($type=='hostcommand') ? 'host_notification_commands': $radType;
                    $radType = ($type=='servicecommand') ? 'service_notification_commands': $radType;
                    $v = $radType.'_tploptions';

            $html .= "<div class='neptune-overlay-right-buttons'>
                    <div class='overlay-radio' style='line-height: 30px;'>
                        <div class='btn-group ccm-btn-group' data-toggle='buttons'>
                            <label class='btn btn-xs btn-default ".(grab_array_var($FIELDS, $v, 0) == 0 ? 'active' : '' )."'>
                                <input type='radio' name='rad{$Title}' id='rad{$Title}0' value='0' ".@check($radType.'_tploptions', '0', true).">+
                            </label>
                            <label class='btn btn-xs btn-default ".(grab_array_var($FIELDS, $v, 0) == 1 ? 'active' : '' )."'>
                                <input type='radio' name='rad{$Title}' id='rad{$Title}1' value='1' ". @check($radType.'_tploptions', '1', true). ">"._('Null')."
                            </label>
                            <label class='btn btn-xs btn-default ".(grab_array_var($FIELDS, $v, 0) == 2  ? 'active' : '' )."'>
                                <input type='radio' name='rad{$Title}' id='rad{$Title}2' value='2' ". @check($radType.'_tploptions', '2', true). ">"._('Standard')."
                            </label>
                        </div>
                    </div>
                    <div class='' style='line-height: 30px;'>
                        <label style='margin-left: 10px;' class='ccm-tt-bind' title='{$Title} "._('inheritance options')."'>
                            <span class='material-symbols-outlined ccm-neptune-info'>info</span>
                        </label>
                    </div>
                </div>";
            }
            $html .= "
            </div>
        </div>

        <!-- $type radio buttons -->  

        </div> <!-- end {$type}box --> ";  
    } else {
        if ($tplOpts) { // Template options

            $radType = $type.'s';

            // Deal with inconsistent DB naming convention in NagiosQL. Make sure we have the correct form field name
            $radType = (isset($FIELDS['contact_groups_tploptions']) && $type=='contactgroup') ? 'contact_groups' : $radType;
            $radType = (isset($FIELDS['host_name_tploptions']) && $type=='host') ? 'host_name' : $radType;
            $radType = (isset($FIELDS['hostgroup_name_tploptions']) && $type=='hostgroup') ? 'hostgroup_name' : $radType;
            $radType = ($type=='hostcommand') ? 'host_notification_commands': $radType;
            $radType = ($type=='servicecommand') ? 'service_notification_commands': $radType;
            $v = $radType.'_tploptions';

        $html .= "<div class='fr' style='line-height: 30px;'>
                        <div class='btn-group' data-toggle='buttons'>
                            <label class='btn btn-xs btn-default ".(grab_array_var($FIELDS, $v, 0) == 0 ? 'active' : '' )."'>
                                <input type='radio' name='rad{$Title}' id='rad{$Title}0' value='0' ".@check($radType.'_tploptions', '0', true).">+
                            </label>
                            <label class='btn btn-xs btn-default ".(grab_array_var($FIELDS, $v, 0) == 1 ? 'active' : '' )."'>
                                <input type='radio' name='rad{$Title}' id='rad{$Title}1' value='1' ". @check($radType.'_tploptions', '1', true). ">"._('Null')."
                            </label>
                            <label class='btn btn-xs btn-default ".(grab_array_var($FIELDS, $v, 0) == 2  ? 'active' : '' )."'>
                                <input type='radio' name='rad{$Title}' id='rad{$Title}2' value='2' ". @check($radType.'_tploptions', '2', true). ">"._('Standard')."
                            </label>
                        </div>
                    </div>
                    <div class='fr' style='line-height: 30px;'>
                        <label style='margin-right: 10px;' class='ccm-tt-bind' title='{$Title} "._('inheritance options')."'>
                            <i class='material-symbols-outlined md-400 md-18 material-icon-va tooltip-info'>info</i>
                        </label>
                    </div>";
        }

                $html .= "<div class='fr ccm-label' style='margin-right: 20px;'>
                        <div><i class='material-symbols-outlined md-18 material-icon-va'>link</i> <span class='ccm-tt-bind qtt' title='"._('An example of a relationship that can only be linked in one direction is a child host with a host defined as the parent cannot be set as a child from the host it is already a child of.')."'>"._("Relationship defined elsewhere")."</span></div>
                        <div><i class='material-symbols-outlined md-18 material-icon-va'>error</i> "._('Inactive object')."</div>
                    </div>
                    <div class='clear'></div>
                </div>
                <div class='closeOverlay'>
                    <button type='button' class='btn btn-sm btn-default' onclick='killOverlay(\"{$type}Box\")'>"._("Close")."</button>
                </div>
            </div>
        </div>
        <!-- end leftBox -->

        <div class='right'>
            <div class='right-container'>
                <table class='table table-no-margin table-small-border-bottom table-x-condensed'>
                    <thead>
                        <tr>
                            <th colspan='2'>
                                <span class='thMember'>"._("Assigned")."</span>
                                <a class='fr' title='"._("Remove All")."' href='javascript:void(0)' onclick=\"removeAll('tbl{$Titles}')\">"._("Remove All")."</a>
                                <div class='clear'></div>
                            </th>
                        </tr>
                    </thead>
                </table>
                <div class='assigned-container'>
                    <table class='table table-x-condensed table-hover table-assigned' id='tbl{$Titles}'>
                        <tbody>
                            <!-- insert selected items here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- $type radio buttons -->  

        </div> <!-- end {$type}box --> ";  
    }
    return $html;
}


/**
 * Creates html overlay for the command-line test output
 *
 * @return string Returns the overlay's HTML
 */
function build_command_output_box()
{  
    $html = "
    <!-- ------------------------------------ Test Check Commands --------------------- -->
    <div class='overlay' id='commandOutputBox'>
        <div class='overlay-title'>
            <h2>"._('Run Check Command')."</h2>
            <div class='overlay-close ccm-tt-bind' data-placement='bottom' title='"._('Close')."' onclick='killOverlay(\"commandOutputBox\")'><i class='material-symbols-outlined md-400 material-icon-va'>close</i></div>
            <div class='clear'></div>
        </div>
        <div id='command_input'>
            <div class='input-group' style='width: 280px;'>
                <label class='input-group-addon'>"._("Host Address")."</label>
                <input type='text' id='check_address' class='form-control text'>
            </div>
        </div>
        <button type='button' class='btn btn-sm btn-primary icon-in-btn' id='run_command'><i class='material-symbols-outlined md-400 material-icon-va'>play_arrow</i> " . _("Run Check Command") . "</button>
        <div id='command_output' style='display: none; text-align: center; overflow: hidden'>
        </div>
        <div>
            <button type='button' class='btn btn-sm btn-default' id='overlay-close-btn' onclick=\"killOverlay('commandOutputBox')\">"._("Close")."</button>
        </div>
    </div>"; 
    
    return $html; 
}


/**
 * Creates html overlay for the free-variable definition form 
 *
 * @return string Returns html for the custom variables overlay
 */ 
function build_variable_box()
{
    $neptune_close_class = is_neptune() ? ' md-400 ccm-close-icon ' : '';
    $neptune_table_class = is_neptune() ? ' table-condensed table-ccm table-stripe ' : '';
    $width_wide = is_neptune() ? '43%' : '45%';
    $width_narrow = is_neptune() ? '14%' : '10%';
    $insert_wrapper_class = is_neptune() ? ' neptune-insert-variable ' : '';
    $modern_insert_class = is_neptune() ? '' : 'icon-in-btn';
    $html = "
    <!-- ------------------------------------ Custom Variables --------------------- -->
    <div class='overlay' id='variableBox' style='max-width: 900px;'>

        <div class='overlay-title'>
            <h2>"._('Manage Custom Variables')."</h2>
            <div class='overlay-close ccm-tt-bind' data-placement='left' title='"._('Close')."' onclick='killOverlay(\"variableBox\")'><span class='material-symbols-outlined $neptune_close_class'>close</span></div>
            <div class='clear'></div>
        </div>
        <div style='height: calc(100% - 40px); overflow-y: auto;'>

            <table class='table table-hover table-assigned table-bordered $neptune_table_class' id='tblVariables'>
                <thead>
                    <tr>
                        <th style='width: $width_wide;'>"._("Name")."</th>
                        <th style='width: $width_wide;'>"._("Value")."</th>
                        <th style='width: $width_narrow;'>"._("Actions")."</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- insert selected items here -->
                </tbody>
            </table>

            <div class='well form-horizontal $insert_wrapper_class' style='margin: 20px 0 10px 0;'>
                <input type='text' class='form-control' name='txtVariablename' id='txtVariablename' style='width:225px' placeholder='"._('Name')."'>
                <input type='text' class='form-control' name='txtVariablevalue' id='txtVariablevalue' style='width:225px' placeholder='"._('Value')."'>
                <button style='vertical-align: top;' type='button' class='btn btn-sm btn-primary $modern_insert_class' onclick='insertDefinition(false, false)'>"._("Insert")." <span class='material-symbols-outlined material-symbols-bold'>chevron_right</span></button>
            </div>

            <div class='closeOverlay'>
                <button type='button' class='btn btn-sm btn-default' onclick=\"killOverlay('variableBox')\">"._("Close")."</button>
            </div>
        </div>
    </div>
    ";
    return $html;
}