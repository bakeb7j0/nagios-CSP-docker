<?php

include_once(dirname(__FILE__) . '/../dashlethelper.inc.php');

// Initialization stuff
pre_init();
init_session(true);

grab_request_vars();
check_prereqs();
check_authentication(false);

sansrisingports_route_request();

function sansrisingports_route_request() {
    
    // check if we're only interested in the highcharts graph before we do anything else
    $height = intval(grab_request_var('height', 300));
    $width = intval(grab_request_var('width', 400));
    $id = grab_request_var('id');

    echo sansrisingports_get_hc($id, $height, $width);    
}

// simply return the highcharts data necessary to create the graph
function sansrisingports_get_hc($id, $height, $width) {

    $date = date("Y-m-d");
    $url = "https://isc.sans.edu/portascii.html?start={$date}&end={$date}";

    $port_data_array = array();
    
    // get the data
    $data = file_get_contents($url);

    // break the data into an array
    $data_lines = explode("\n", $data);
    $port_count = 0;
    foreach ($data_lines as $line) {

        // skip lines that start with a comment
        if (strpos($line, '#') === 0)
            continue;

        // skip empty lines
        if (trim($line) === "")
            continue;

        // see if we are at the header row
        if  (
            strpos($line, 'port') !== false &&
            strpos($line, 'records') !== false &&
            strpos($line, 'targets') !== false &&
            strpos($line, 'sources') !== false) {
            continue;
        }

        // replace all whitespace with a single whitespace character
        $line = preg_replace('/\s/', ' ', $line);

        // break line into an array of its own!
        $line_array = explode(' ', $line);

        $port_data_array[] = array(
            'port' =>       $line_array[0],
            'records' =>    $line_array[1],
            'targets' =>    $line_array[2],
            'sources' =>    $line_array[3],
            'tcpratio' =>   $line_array[4],
            );

        $port_count++;
        if ($port_count >= 10)
                break;
    }

    $categories = '';
    $records = '';
    foreach ($port_data_array as $port) {
        $categories .= "'" . $port['port'] . "',";
        $records .= $port['records'] . ',';
    }

    // trim trailing comma
    $categories = substr($categories, 0, -1);
    $records = substr($records, 0, -1);
    
    
    // LOCAL EXPORTING lOGIC
    $exporting_url = get_base_url()."/includes/components/highcharts/exporting-server/index.php";
    $export_buttons = overwrite_hc_exporting_buttons();

    $title = 'SANS Internet Storm Center Top 10 Rising Ports for ' . $date;
    $filename = strtolower(str_replace(' ', '-', $title));
    $text_color = '#999';
    $font_title = "16px Lucida Grande, Lucida Sans Unicode, Verdana, Arial, Helvetica, sans-serif";
    $font_subtitle = "12px Lucida Grande, Lucida Sans Unicode, Verdana, Arial, Helvetica, sans-serif";
    $line_color = "grey";
    if (is_neptune()) {
        $text_color = 'var(--foreground)';
        $font_title = "14px Lucida Grande, Lucida Sans Unicode, Verdana, Arial, Helvetica, sans-serif";
        $font_subtitle = "10px Lucida Grande, Lucida Sans Unicode, Verdana, Arial, Helvetica, sans-serif";
        $line_color = 'var(--foreground)';
    }
    
    $container = $id;
    $output = "
        <script type='text/javascript'>

        Highcharts.setOptions({
            colors: ['#058DC7', '#50B432', '#ED561B', '#DDDF00', '#24CBE5', '#64E572', '#FF9655', '#FFF263', '#6AF9C4'] 
        });

        $(function () {
            $('#".$container."').highcharts({
                chart: {
                    type: 'bar',
                    height: " . $height . ",
                    width: " . $width . ",
                    backgroundColor: 'transparent'
                },
                exporting: {
                    url: '" . $exporting_url . "',
                    filename: '" . $filename . "',
                    buttons: {" . $export_buttons . "}
                },
                title: {
                    text: '" . $title . "',
                    style: {
                        color: '" . $text_color . "',
                        font: '" . $font_title . "'
                    }
                },
                subtitle: {
                    text: 'Source: <a href=" . $url . ">Internet Storm Center</a>',
                    style: {
                        color: '" . $text_color . "',
                        font: '" . $font_subtitle . "'
                    }
                },
                xAxis: {
                    categories: [" . $categories . "],
                    title: {
                        text: 'Ports',
                        style: {
                            color: '" . $line_color . "'
                        }
                    },
                    labels: {
                        style: {
                            color: '" . $line_color . "'
                        }
                    }
                },
                yAxis: {
                    min: 0,
                    title: {
                        text: 'Records',
                        align: 'high',
                        style: {
                            color: '" . $line_color . "'
                        }
                    },
                    labels: {
                        overflow: 'justify'
                    },
                    gridLineColor: '" . $line_color . "',
                    labels: {
                        style: {
                            color: '" . $line_color . "'
                        }
                    }
                },
                plotOptions: {
                    bar: {
                        dataLabels: {
                            enabled: true,
                        }
                    }
                },
                legend: {
                    enabled: false
                },
                credits: {
                    enabled: false
                },
                series: [{
                    name: 'Records',
                    data: [" . $records . "]
                }]
            });
        });
        </script>
    ";

    return $output;
}