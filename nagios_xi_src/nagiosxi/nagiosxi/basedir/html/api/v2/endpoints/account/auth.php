<?php

namespace api\v2\account;
use api\v2\Base;

require_once (dirname(__FILE__) . '/../../../../includes/common.inc.php');

/**
 * Gets authorization information and permissions for a specific user
 */
class auth extends Base {

    /**
     * Auth function for get request method on api/v2/auth 
     * All necessary auth is done in check_authentication so this just returns true.
     */
    public function authorized_for_get() {
        return true;
    }

    public function get() {
        $response = [
            "nsp" => get_nagios_session_protector_id(),
            "username" => $_SESSION["username"],
            "isAdmin" => is_admin(),
            "isAuthorizedForMonitoringSystem" => is_authorized_for_monitoring_system(),
            "canConfigureObjects" => is_authorized_to_configure_objects() && !is_readonly_user(),
            "canAccessCCM" => user_can_access_ccm(),
            "canAutoDeploy" => user_has_permission('autodeploy_access'),
        ];
        return $response;
    }
}