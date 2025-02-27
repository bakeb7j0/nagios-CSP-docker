<?php
//
// Config Wizard Helper Functions
// Copyright (c) 2008-2018 Nagios Enterprises, LLC and others.
//

if (!defined('SUBSYSTEM')) {
	require_once(dirname(__FILE__) . '/../common.inc.php');
}

/**
 * Builds a hidden overlay div for selecting hosts or services. 
 * Show the overlay with show_overlay() as can be seen in the eventhandler notify wizards.
 *
 * @param string $type        nagios object type (host, service, command, etc)
 * @param string $nodelist    json encoded list of hosts or services (use get_xml_host_objects() or get_xml_service_objects())
 * @return string returns populated overlay html to select lists for the $type object
 */
function construct_overlay($type, $nodelist) {
    global $unique;

    if($type == 'host') {
        $display_display_name = grab_request_var("display_host_display_name", get_user_meta(0, "display_host_display_name"));
    } else if($type == 'service') {
        $display_display_name = grab_request_var("display_service_display_name", get_user_meta(0, "display_service_display_name"));
    }

    $Title = ucfirst($type);
    $Titles = ucfirst($type).'s';
    
    $html = "<div class='overlay' id='{$type}Box'>

    <div class='overlay-title'>
        <h2>{$Titles}</h2>
        <div class='overlay-close ccm-tt-bind' data-placement='left' title='"._('Close')."' onclick='killOverlay(\"{$type}Box\")'><i class='fa fa-times'></i></div>
        <div class='clear'></div>
    </div>

    <div class='left'>
        <div class='listDiv'>
            <div class='filter'>
                <span class='clear-filter ccm-tt-bind' title='"._('Clear')."'><i class='fa fa-times fa-14'></i></span>
                <input type='text' id='filter{$Titles}' class='form-control fc-fl' style='border-bottom: 0;' placeholder='"._('Filter')."...'>
            </div>
            <select name='sel{$Titles}[]' class='form-control fc-m lists' multiple='multiple' id='sel{$Titles}' ondblclick='transferMembers(\"sel{$Titles}\", \"tbl{$Titles}\", \"{$type}s\")'>
                <!-- option value is tbl ID -->
    ";

    $nodelist = json_decode(json_encode($nodelist), true);
    if (key_exists("recordcount", $nodelist) && $nodelist["recordcount"] > 1) {
        $nodelist = $nodelist[$type];
    } else {
        $nodelist = array($nodelist[$type]);
    }
    $unique++;

    foreach($nodelist as $key => $node){

        if($type == 'host'){
            $id = $node['@attributes']['id'];
            $host_name = $node['host_name'];
            if($display_display_name) {
                $display_name = $node['display_name'];
            } else {
                $display_name = $node['alias'];
            }

            $disabled = '';
            if(!$node['is_active']){
                $disabled = 'disabled';
            }

            $node = json_encode($node);
            $html .= "<option id='".$unique++."' name='{$host_name}' value='{$node}' title='{$host_name}' ".$disabled.">{$display_name}</option>";
        } else if($type == 'service'){
            $id = $node['@attributes']['id'];
            $host_name = $node['host_name'];
            if($display_display_name) {
                $display_name = $node['display_name'];
            } else {
                $display_name = $node['service_description'];
            }

            $disabled = '';
            if(!$node['is_active']){
                $disabled = 'disabled';
            }

            $node = json_encode($node);
            $html .= "<option id='".$unique++."' name='{$host_name} - {$display_name}' value='{$node}' title='{$host_name}' ".$disabled.">{$host_name} - {$display_name}</option>";
        }

    }
    $html .= "  </select>
                <div class='overlay-left-bottom'>
                    <button type='button' class='btn btn-sm btn-primary fl' onclick=\"transferMembers('sel{$Titles}', 'tbl{$Titles}', '{$type}s')\">"._("Add Selected")." <i class='fa fa-chevron-right'></i></button>
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
                            <a class='fr' title='Remove All' href='javascript:void(0)' onclick=\"removeAll('tbl{$Titles}')\">"._("Remove All")."</a>
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

    </div> 

    <script type=\"text/javascript\" src=\"../includes/components/ccm/javascript/form_js.js?".get_build_id()."\"></script>

    <!-- end {$type}box --> ";

    return $html;

}