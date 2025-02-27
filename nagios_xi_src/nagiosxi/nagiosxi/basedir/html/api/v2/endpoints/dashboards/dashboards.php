<?php

namespace api\v2;
use api\v2\Base;
use Exception;

require_once(dirname(__FILE__) . '/../../../../includes/common.inc.php');

/**
 * Manages dashboards including retrieval, creation, deletion, and updates.
 */
class dashboards extends Base {

    /**
     * Auth function for get request method on api/v2/account/dashboards 
     * All necessary auth is done in check_authentication so this just returns true.
     */
    public function authorized_for_get() {
        return true;
    }

    /**
     * Retrieves a dashboard by ID, or all dashboards if no ID is specified.
     * @return array The dashboard data or an error message if not found.
     */
    public function get() {
        $id = grab_request_var('id', '');
        if($id == '') {
            $dashboards = get_dashboards();
            $response = [];
            foreach ($dashboards as $key => $value) {
                $response[$key]["id"] = $value["id"];
                $response[$key]["title"] = $value["title"];
                $response[$key]["opts"] = $value["opts"];
            }
        } else {
            $response = get_dashboard_by_id($_SESSION['user_id'], $id);
            if(is_null($response)) {
                throw new Exception(_("No dashboard found"), 404);
            }
        }
        return $response;
    }

    /**
     * Auth function for post request method on api/v2/account/dashboards 
     * All necessary auth is done in check_authentication so this just returns true.
     */
    public function authorized_for_post() {
        return true;
    }

    /**
     * Adds a new dashboard with provided parameters.
     * @return array Confirmation message of dashboard creation.
     */
    public function post() {
        check_nagios_session_protector();

        $title = grab_request_var("title", "");
        if (empty($title)) {
            throw new Exception(_("No title specified!"), 500);
        }

        $opts = [];

        $background = encode_form_val(grab_request_var("background", ""));
        $opts["background"] = $background;

        $transparent = grab_request_var("transparent", 0);
        $opts["transparent"] = $transparent;

        add_dashboard(0, $title, $opts);

        return ["message" => _("Successfully added dashboard")];
    }

    /**
     * Auth function for delete request method on api/v2/account/dashboards 
     * All necessary auth is done in check_authentication so this just returns true.
     */
    public function authorized_for_delete() {
        return true;
    }

    /**
     * Deletes a specified dashboard by its ID.
     * @return array Confirmation or error message based on the operation result.
     */
    public function delete() {
        check_nagios_session_protector();

        $id = grab_request_var("id", -1);
        if(!delete_dashboard_id(0, $id)) {
            throw new Exception(_("Could not delete dashboard."), 500);
        }

        // Add a default dashboard if that was the last one
        $dashboards = get_dashboards(0);
        if (count($dashboards) == 0) {
            $opts = array();
            add_dashboard(0, "Default Dashboard", $opts);
        }
        return ["message" => _("Successfully deleted dashboard")];
    }

    /**
     * Auth function for put request method on api/v2/account/dashboards 
     * All necessary auth is done in check_authentication so this just returns true.
     */
    public function authorized_for_put() {
        return true;
    }

    /**
     * Updates an existing dashboard with new settings.
     * @return array Success message.
     */
    public function put() {
        check_nagios_session_protector();

        $id = grab_request_var("id", 0);
        $title = grab_request_var("title", 0);
        $background = grab_request_var("background", 0);
        $transparent = grab_request_var("transparent", 0);

        $opts = array();
        $opts["background"] = $background;
        $opts["transparent"] = $transparent;

        update_dashboard(0, $id, $title, $opts);
        return ["message" => "success"];
    }
}