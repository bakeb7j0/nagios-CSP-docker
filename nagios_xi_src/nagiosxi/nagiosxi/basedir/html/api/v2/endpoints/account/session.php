<?php

namespace api\v2\account;
use api\v2\Base;

require_once(dirname(__FILE__) . '/../../../../includes/common.inc.php');

/**
 * Used to get user sessions and invalidate them
 */
class session extends Base {
    /**
     * Auth function for get request method on api/v2/account/session
     * All necessary auth is done in check_authentication so this just returns true.
     */
    public function authorized_for_get() {
        return true;
    }

    public function get() {
        $response = user_get_own_sessions();

        if ($response === false) {
            $response = ['error' => _('Failed to retrieve sessions.')];
        }
        return $response;
    }

    /**
     * Auth function for delete request method on api/v2/account/session
     * All necessary auth is done in check_authentication so this just returns true.
     */
    public function authorized_for_delete() {
        return true;
    }

    public function delete() {
        $session_id = intval(grab_request_var('session_id', 0));
        $user_id = $_SESSION['user_id'];

        if (empty($session_id)) {
            $response = ['error' => _('Not a valid user session.')];
            return $response;
        }

        $deleted_id = user_logout_session($user_id, $session_id);

        if (!$deleted_id) {
            $response = ['error' => _('Failed to remove session.')];
            return $response;
        }

        $response = ['success' => true, 'id' => $deleted_id];
        return $response;
    }
}