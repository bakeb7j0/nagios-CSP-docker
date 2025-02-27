<?php
//
// XI Core Dashlet Functions
// Copyright (c) 2008-2018 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../componenthelper.inc.php');
include_once(dirname(__FILE__) . '/../../utils-dashlets.inc.php');


////////////////////////////////////////////////////////////////////////
// MISC DASHLETS
////////////////////////////////////////////////////////////////////////


/**
 * Available update dashlet HTML generation function
 *
 * @param 	string 	$mode
 * @param 	string 	$id
 * @param 	array  	$args
 * @return 	string
 */
function xicore_dashlet_available_updates($mode = DASHLET_MODE_PREVIEW, $id = "", $args = array())
{
    $output = "";

    switch ($mode) {
        case DASHLET_MODE_GETCONFIGHTML:
            $output = '';
            break;
        case DASHLET_MODE_OUTBOARD:
        case DASHLET_MODE_INBOARD:

            $id = "xi_available_updates_" . random_string(6);

            $output = '';

            $output .= '<div class="infotable_title">' . _('Available Updates') . '</div>';

            $output .= '
			<div class="xi_available_updates_dashlet" id="' . $id . '">
			<img src="' . theme_image("throbber.gif") . '"> Checking for updates...
			</div><!--xi_available_updates_dashlet-->

	<script type="text/javascript">
	$(document).ready(function(){

				get_' . $id . '_content();
					
				$("#' . $id . '").everyTime(' . get_dashlet_refresh_rate(24 * 60 * 60, "available_updates") . ', "timer-' . $id . '", function(i) {
					get_' . $id . '_content();
				});
				
				function get_' . $id . '_content(){
					$("#' . $id . '").each(function(){
						var optsarr = {
							"func": "get_available_updates_html",
							"args": ""
							}
						var opts=JSON.stringify(optsarr);
						get_ajax_data_innerHTML("getxicoreajax",opts,true,this);
						});
					}
		
	});
	</script>
			';

            break;

        case DASHLET_MODE_PREVIEW:
            $imgurl = get_component_url_base() . "xicore/images/dashlets/available_updates_preview.png";


			if(!is_neptune()) {
                $imgurl = get_component_url_base() . "xicore/images/dashlets/available_updates_preview.png";
			} else if (get_theme() == 'neptunelight') {
                $imgurl = get_component_url_base() . "xicore/images/dashlets/available_updates_neptune_light_preview.png";
            } else {
                $imgurl = get_component_url_base() . "xicore/images/dashlets/available_updates_neptune_preview.png";
            }

            $output = '
			<img src="' . $imgurl . '">
			';
            break;
    }

    return $output;
}
