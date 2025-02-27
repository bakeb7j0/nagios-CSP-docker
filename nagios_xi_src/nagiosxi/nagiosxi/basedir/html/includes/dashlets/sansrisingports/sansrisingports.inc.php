<?php
//
// Copyright (c) 2008-2023 Nagios Enterprises, LLC.  All rights reserved.
//  

include_once(dirname(__FILE__) . '/../dashlethelper.inc.php');

sansrisingports_dashlet_init();

// initialize and register dashlet with xi
function sansrisingports_dashlet_init() {

    // respect the name!
    $name = "sansrisingports";

    $args = array(

        // need a name
        DASHLET_NAME =>             $name,

        // informative information
        DASHLET_VERSION =>          "4.0.1",
        DASHLET_DATE =>             "05-28-2024",
        DASHLET_AUTHOR =>           "Nagios Enterprises, LLC",
        DASHLET_DESCRIPTION =>      _("A graph of the top 10 rising ports from the SAN Internet Storm Center. Useful for spotting trends related to virus and worm outbreaks."),
        DASHLET_COPYRIGHT =>        _("Dashlet Copyright &copy; 2016 Nagios Enterprises. Data Copyright &copy; SANS Internet Storm Center."),
        DASHLET_LICENSE =>          _("Creative Commons Attribution-Noncommercial 3.0 United States License."),
        DASHLET_HOMEPAGE =>         _("https://www.nagios.com"),

        // the good stuff - only one output method is used.  order of preference is 1) function, 2) url
        DASHLET_FUNCTION =>         "sansrisingports_dashlet_func",
        //DASHLET_URL =>            get_dashlet_url_base($name)."/$name.php",

        DASHLET_TITLE =>            "SANS Internet Storm Center Top 10 Rising Ports",

        DASHLET_OUTBOARD_CLASS =>   "sansrisingports_outboardclass",
        DASHLET_INBOARD_CLASS =>    "sansrisingports_inboardclass",
        DASHLET_PREVIEW_CLASS =>    "sansrisingports_previewclass",

        DASHLET_WIDTH =>            "300px",
        DASHLET_HEIGHT =>           "212px",
        DASHLET_OPACITY =>          "0.8",
        DASHLET_BACKGROUND =>       "",
    );

    register_dashlet($name, $args);
}

// the function for printing the dashlet container and jquery ajax to pull the hc data
function sansrisingports_dashlet_func($mode = DASHLET_MODE_PREVIEW, $id = "", $args = null) {
    $output = "";
    $imgbase = get_dashlet_url_base("sansrisingports") . "/images/";

    switch ($mode) {
        case DASHLET_MODE_GETCONFIGHTML:
            break;
        case DASHLET_MODE_OUTBOARD:
        case DASHLET_MODE_INBOARD:

            $div_id = uniqid();
            $dashlet_url = get_dashlet_url_base("sansrisingports") . '/sansrisingports_ajax.php?id=' . $div_id;

            $output .= <<<OUTPUT
                <div id="{$div_id}"></div>
                <script>
                $(function () {

                    get_{$div_id}_content();

                    $("#{$div_id}").closest(".ui-resizable").on("resizestop", function(e, ui) {
                        get_{$div_id}_content();
                    });
                });

                function get_{$div_id}_content(height, width) {

                    if (height == undefined) { 
                        var height = $("#{$div_id}").parent().height(); 
                    }

                    if (width == undefined) { 
                        var width = $("#{$div_id}").parent().width(); 
                    }

                    height = height - 17;
                    if (height < 100) height = 300;

                    $("#{$div_id}").load("{$dashlet_url}" + "&height=" + height + "&width=" + width);

                    // Stop clicking in graph from moving dashlet
                    $("#{$div_id}").closest(".ui-draggable").draggable({ cancel: "#{$div_id}" });
                }
                </script>
OUTPUT;
            break;

        case DASHLET_MODE_PREVIEW:
            
            if(!is_neptune()) {
                $output = "<p><img src='" . $imgbase . "preview.png'></p>";
            } else if (get_theme() == 'neptunelight') {
                $output = "<img src='" . $imgbase . "sansrising_neptune_light_preview.png'>";
            } else {
                $output = "<img src='" . $imgbase . "neptune_preview.png'>";
            }
            
            break;
    }

    return $output;
}