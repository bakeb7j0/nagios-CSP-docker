<?php
//
// Graph Explorer
// Copyright (c) 2014-2019 Nagios Enterprises, LLC. All rights reserved.
//


/**
 * @param $args
 *
 * @return string
 */
function fetch_piegraph($args)
{
    global $cfg;
    $theme = get_theme();

    $graph = '';
    $green_value = ($theme == "colorblind" || $theme == "neptunecolorblind") ? '#56B4E9' : '#b2ff5f';
    $red_value = ($theme == "colorblind" || $theme == "neptunecolorblind") ? '#CC79A7' : '#FF795F';
    $orange_value = ($theme == "colorblind" || $theme == "neptunecolorblind") ? '#D55E00' : '#FFC45F';
    $yellow_value = ($theme == "colorblind" || $theme == "neptunecolorblind") ? '#F0E442' : '#FEFF5F';

    // The color logic can be done more elegantly, getting this in quick because we're at the precipice of shipping
    if ($theme === "neptune") {
        $green_value = '#00EE5E';
        $red_value = '#FF0001';
        $orange_value = '#F5A623';
        $yellow_value = '#ECFD20';
        $foreground = '#E1E7EF';
    } else if ($theme === "neptunelight") {
        $green_value = '#008844';
        $red_value = '#D33D44';
        $orange_value = '#E16B16';
        $yellow_value = '#FAD800';
        $foreground = '#1A1A1A';
    }

    if ($args['pieType'] == 'hosthealth') $colors = "['$green_value', '$red_value', '$orange_value']"; //green,red,orange
    elseif ($args['pieType'] == 'servicehealth') $colors = "['$green_value','$yellow_value','$red_value', '$orange_value']"; //green,yellow,red,orange
    else $colors = "['#4572A7', '#AA4643', '#89A54E', '#80699B', '#3D96AE', '#DB843D', '#92A8CD', '#A47D7C', '#B5CA92']"; //default

    $height = grab_array_var($args, 'height', 500);
    $args['title'] = encode_form_val($args['title']);
    $args['subtitle'] = encode_form_val($args['subtitle']);
    $args['container'] = encode_form_valq($args['container']);
    $filename = str_replace(" ", "_", strtolower($args['title']));

    // Special export settings for local exporting
    $exporting = "";
    if (get_option('highcharts_local_export_server', 1)) {
        $exporting_url = "";
        $ini = parse_ini_file($cfg['root_dir'] . '/var/xi-sys.cfg');
        if ($ini['distro'] == "el9" || $ini['distro'] == "ubuntu22" || get_option('reports_exporting', 1)) {
            $exporting_url = "url: '" . get_base_url() . "/includes/components/highcharts/exporting-server/index.php',";
        }
        $exporting = "exporting: {
            {$exporting_url}
            sourceHeight: $('#{$args['container']}').height(),
            sourceWidth: $('#{$args['container']}').height(),
            filename: '{$filename}',
            chartOptions: { chart: { spacing: [20, 20, 25, 20] } },
            buttons: {
                contextButton: {
                    menuItems: [
                        'viewFullscreen', 
                        'printChart',
                        'separator',
                        'downloadPNG',
                        'downloadJPEG',
                        'downloadPDF',
                        'downloadSVG',
                        'separator',
                        'downloadCSV',
                        'downloadXLS'
                    ]
                }
            }
         },";
    }

    $color = "#000000";
    if (is_dark_theme()) {
        $color = '#EEEEEE';
    }
    if (is_neptune()) {
        $color = $foreground;
    }

    $graph .= <<<GRAPH

    Highcharts.setOptions({
        colors: {$colors} 
    });
    
    var chart;
    chart = new Highcharts.Chart({
        {$exporting}
        chart: {
            renderTo: '{$args['container']}',
            plotBackgroundColor: null,
            plotBorderWidth: null,
            plotShadow: false,
            height: {$height},
            animation: false
        },
        credits: {
            enabled: false
        },
        title: {
            text: '{$args['title']}'
        },
        tooltip: {
            formatter: function() {
                return '<b>'+ this.point.name +'</b>: '+ this.y +' %';
            }
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: {
                    enabled: true,
                    color: '{$color}',
                    connectorColor: '{$color}',
                    formatter: function() {
                        switch(this.point.name)
                        {
                            case 'OK':
                            case 'UP':
                                var middle = '<a href="{$args['url']}2" target="_blank" title="View All By State">'+ this.point.name +'</a>';
                            break;
                            
                            case 'WARNING':
                            case 'DOWN':
                                var middle = '<a href="{$args['url']}4" target="_blank" title="View All By State">'+ this.point.name +'</a>';
                            break;
                            
                            case 'UNKNOWN':
                            case 'UNREACHABLE':
                                var middle = '<a href="{$args['url']}8" target="_blank" title="View All By State">'+ this.point.name +'</a>';
                            break; 
                            
                            case 'CRITICAL':
                                var middle = '<a href="{$args['url']}16" target="_blank" title="View All By State">'+ this.point.name +'</a>';
                            break; 
                            
                            default: 
                                var middle = this.point.name;
                            break;
                        }
                        var string = '<strong>'+middle+'</strong>: '+ this.y +' %';
                        return string;
                        //return '<b><a href="index.php">'+ this.point.name +'</a></b>: '+ this.y +' %';
                    }
                }
            }
        },
        series: [{
            type: 'pie',
            name: '{$args['subtitle']}',
            data: [
                {$args['datastring']}
            ],
            animation: false
        }]
    });
GRAPH;

    return $graph;
} //end fetch_pie()
