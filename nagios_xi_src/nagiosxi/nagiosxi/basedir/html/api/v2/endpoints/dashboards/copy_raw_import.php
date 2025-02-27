<?php

namespace api\v2\dashboards;
use api\v2\Base;
use Exception;

require_once(dirname(__FILE__) . '/../../../../includes/common.inc.php');

/**
 * Handles cloning of dashboards.
 */
class copy extends Base {

    /**
     * Auth function for post request method on api/v2/account/dashboards/copy
     * All necessary auth is done in check_authentication so this just returns true.
     */
    public function authorized_for_post() {
        return true;
    }

    /**
     * Clones a dashboard based on the provided ID.
     * @return array Result of the cloning process.
     */
    public function post() {
        $id = grab_request_var("id", -1);
        $title = grab_request_var("title", "("._("Cloned").")");

        if(clone_dashboard_id(0, $id, $title)) {
            return ["message" => _("Dashboard successfully cloned.")];
        };
        throw new Exception(_("Could not clone dashboard"), 500);
    }
}

/**
 * Provides functionalities to export raw dashboard data.
 */
class raw extends Base {
    /**
     * Auth function for get request method on api/v2/account/dashboards/raw
     * All necessary auth is done in check_authentication so this just returns true.
     */
    public function authorized_for_get() {
        return true;
    }

    /**
     * Retrieves raw data of a specific dashboard by ID.
     * @return array serialized dashboard data.
     */

    public function get() {
        $dashboardid = grab_request_var("id", 0);
        $userid = $_SESSION['user_id'];

        $rawdashboard = get_dashboard_by_id($userid, $dashboardid);

        if($rawdashboard == null) {
            return ["message" => _("Could not locate dashboard with specified id")];
        }

        // Before we export the dashboard, we need to remove any Nagios Session Protector (NSP) values from each dashlet.
        // These will be re-added on import, we just don't want them to be easily viewable. -AC
        $dashlets_no_nsp = array();
        foreach($rawdashboard['dashlets'] as $dashlet) {
            if (array_key_exists('nsp', $dashlet['args'])){
                $dashlet['args']['nsp'] = '';
                array_push($dashlets_no_nsp, $dashlet);
            }
            else {
                array_push($dashlets_no_nsp, $dashlet);
            }
        }
        $rawdashboard['dashlets'] = $dashlets_no_nsp;

        // Yes, we have to double json_encode it - DA
        return ['dashboard' => json_encode(serialize($rawdashboard))];
    }
}

/**
 * Manages importing of dashboards.
 */
class import extends Base {
    /**
     * Auth function for post request method on api/v2/account/dashboards/import
     * All necessary auth is done in check_authentication so this just returns true.
     */
    public function authorized_for_post() {
        return true;
    }

    /**
     * Imports a dashboard configuration from provided data.
     */
    public function post() {
        $import = grab_request_var("import");
        if(importDashboard($import)) {
            return ['message' => _("Successfully imported dashboard")];
        } else {
            return ['message' => _("Invalid dashboard file!")];
        }
    }
}