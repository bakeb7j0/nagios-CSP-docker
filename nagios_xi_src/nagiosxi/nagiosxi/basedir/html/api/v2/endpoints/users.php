<?php
namespace api\v2;

require_once(dirname(__FILE__) . '/../../../includes/common.inc.php');
require_once(dirname(__FILE__) . '/../../../includes/utils-users.inc.php');

/**
 * Getting, setting, updating users on the XI system
 */
class users extends Base {
    /**
     * Needs to be an admin to use this endpoint
     */
    public function authorized_for_get() {
        return is_admin();
    }

    public function get() {
        $response = get_users();
        foreach ($response as $key => $user) {
            if (is_array($user) && isset($user['user_id'])) {
                $user_id = $user['user_id'];
                $phone = get_user_meta($user_id, "mobile_number");
                $auth_type = get_user_meta($user_id, "auth_type");
                $auth_level = get_user_meta($user_id, "userlevel");

                $response[$key]['mobile_number'] = $phone;
                $response[$key]['auth_type'] = $auth_type;
                $response[$key]['userlevel'] = $auth_level;
            }
        }
        return $response;
    }

    /**
     * Needs to be an admin to use this endpoint
     */
    public function authorized_for_post() {
        return is_admin();
    }

    public function post() {
        $username = grab_request_var('username', '');
        $password = grab_request_var('password', '');
        $email = grab_request_var('email', '');

        $errors = [];

        $response = add_user_account($username, $password, "name", $email, "1", true, true, true, $errors);

        if ($response) {
            $response = ['message' => 'User created successfully'];
        } else {
            $response = ['error' => $errors];
        }
        return $response;
    }

    /**
     * Needs to be an admin to use this endpoint
     */
    public function authorized_for_delete() {
        return is_admin();
    }

    public function delete() {
        $userid = grab_request_var('userid', null);
        if ($userid) {
            // Call the delete_user_id function with the extracted userid
            $response = delete_user_id($userid);
    
            if ($response) {
                $response = ['message' => 'User deleted successfully'];
            } else {
                $response = ['error' => 'Failed to delete user'];
            }
        } else {
            $response = ['error' => 'No user ID provided'];
        }
        return $response;
    }
}