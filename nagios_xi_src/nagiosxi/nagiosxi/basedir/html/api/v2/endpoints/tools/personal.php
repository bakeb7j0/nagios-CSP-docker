<?php

namespace api\v2\tools;
use api\v2\Base;
use Exception;

require_once(dirname(__FILE__) . '/../../../../includes/common.inc.php');

class personal extends Base {

    /**
     * Auth function for get request method on api/v2/tools/personal 
     * All necessary auth is done in check_authentication so this just returns true.
     */
    public function authorized_for_get() {
        return true;
    }

    /**
     * Retrieves a tool url by ID or all tools if no ID is specified.
     * @return array The tool url or an error message if not found.
     */
    public function get() {
        $id = grab_request_var('id', '');
        if ($id == '') {
            $userid = $_SESSION['user_id'];
            $response = [];
            $mytools = get_mytools($userid);
            $x = 0;
            foreach ($mytools as $key => $value) {
                $response[$x]["name"] = $value["name"];
                $response[$x]["order"] = 101 + $x;
                $response[$x]["href"] ="mytools.php?go=1&id=" . $key;
                $response[$x]["id"] = $key;
                $response[$x]["url"] = $value["url"];
                $x++;
            }
        } else {
            $response = get_mytool_url(0, $id);
            if ($response == "") {
                throw new Exception(_("Invalid tool ID"), 404);
            }
        }
        return $response;
    }

    /**
     * Auth function for post request method on api/v2/tools/personal 
     */
    public function authorized_for_post() {
        return is_admin() || get_user_meta(0, "readonly_user", 1) != 1;
    }

    /**
     * Adds a new tool with provided name and url.
     * @return array Confirmation message of tool creation.
     */
    public function post() {
        check_nagios_session_protector();

        $url = grab_request_var('url', '');
        $name = grab_request_var('name', '');

        if (in_demo_mode() == true) {
            throw new Exception(_("Changes are disabled while in demo mode"), 403);
        }
        if (have_value($url) == false) {
            throw new Exception(_("Invalid tool URL"), 404);
        }
        if (have_value($name) == false) {
            throw new Exception(_("No tool name specified"), 404);
        }

        update_mytool(0, 0, $name, $url);

        return ["message" => _("Successfully added tool.")];
    }

    /**
     * Auth function for put request method on api/v2/tools/personal 
     */
    public function authorized_for_put() {
        return is_admin() || get_user_meta(0, "readonly_user", 1) != 1;
    }

    /**
     * Updates an existing tool with new name and/or url.
     * @return array Success message.
     */
    public function put() {
        check_nagios_session_protector();

        $id = grab_request_var('id', '');
        $url = grab_request_var('url', '');
        $name = grab_request_var('name', '');

        if (in_demo_mode() == true) {
            throw new Exception(_("Changes are disabled while in demo mode"), 403);
        }
        if (have_value($url) == false) {
            throw new Exception(_("Invalid tool URL"), 404);
        }
        if (have_value($name) == false) {
            throw new Exception(_("No tool name specified"), 404);
        }

        update_mytool(0, $id, $name, $url);

        return ["message" => "success"];
    }

    /**
     * Auth function for delete request method on api/v2/tools/personal 
     */
    public function authorized_for_delete() {
        return is_admin() || get_user_meta(0, "readonly_user", 1) != 1;
    }

    /**
     * Deletes a tool by its ID.
     * @return array Confirmation or error message based on the operation result.
     */
    public function delete() {
        check_nagios_session_protector();

        $id = grab_request_var('id', '');

        // Check for errors
        if (in_demo_mode() == true) {
            throw new Exception(_("Changes are disabled while in demo mode"), 403);
        }
        if ($id == '') {
            throw new Exception(_("Invalid tool"), 404);
        }

        if (!delete_mytool(0, $id)) {
            throw new Exception(_("Invalid tool ID"), 404);
        }

        return ["message" => _("Successfully deleted tool.")];
    }
}