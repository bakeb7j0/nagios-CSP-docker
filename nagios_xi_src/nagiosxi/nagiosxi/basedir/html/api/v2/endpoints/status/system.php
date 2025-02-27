<?php

namespace api\v2\status;
use api\v2\Base;

require_once(dirname(__FILE__) . '/../../../../includes/common.inc.php');

/**
 * System status information. You can see equivalent and information in Admin -> System Information -> System Status
 */
class system extends Base {
    public function authorized_for_get() {
        return true;
    }

    public function get() {
        $response = get_xml_sysstat_data();
        $response = json_decode(json_encode($response));

        // get_xml_sysstat_data() can return multiple 'daemons' entries under unknown circumstances. Limit this to the most recent one.
        if (property_exists($response, 'daemons')) {
            if (is_array($response->daemons)) {
                $response->daemons = $response->daemons[count($response->daemons) - 1];
            }
        }

        // Kind of a hack - "output" seems to include sensitive information sometimes, 
        // so let's not display that to everyone. -swolf 2024-02-16
        // Let's also display the pre-translated text just in case the frontend wants it.

        if (property_exists($response, 'daemons') && property_exists($response->daemons, 'daemon')) {
            for ($i = 0; $i < count($response->daemons->daemon); $i += 1) {
                $daemon =& $response->daemons->daemon[$i]; // I gotta unset(), forgive me
                if (property_exists($daemon, 'output')) {
                    unset($daemon->output);
                }

                switch ($daemon->{'@attributes'}->id) {
                    case "nagioscore": {
                        $daemon->display_name = _("Monitoring engine");
                    } break;
                    case "pnp": {
                        $daemon->display_name = _("Performance grapher");
                    } break;
                    default: {
                        $daemon->display_name = _("Unknown Daemon");
                    } break;
                }
                switch ($daemon->status) {
                    case SUBSYS_COMPONENT_STATUS_OK: {
                        $daemon->display_status = _("OK");
                        $daemon->display_running = _("Running");
                    } break;
                    case SUBSYS_COMPONENT_STATUS_ERROR: {
                        $daemon->display_status = _("Critical");
                        $daemon->display_running = _("Not running");
                    } break;
                    case SUBSYS_COMPONENT_STATUS_UNKNOWN: {
                        $daemon->display_status = _("Warning");
                        $daemon->display_running = _("Not running");
                    } break;
                }
            }
        }
        return $response;
    }
}