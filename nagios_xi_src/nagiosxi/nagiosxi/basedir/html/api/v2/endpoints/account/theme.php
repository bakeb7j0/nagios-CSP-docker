<?php

namespace api\v2\account;
use api\v2\Base;

require_once(dirname(__FILE__) . '/../../../../includes/common.inc.php');

/**
 * Getting and setting the user theme
 */
class theme extends Base {
    /**
     * Auth function for get request method on api/v2/account/theme
     * All necessary auth is done in check_authentication so this just returns true.
     */
    public function authorized_for_get() {
        return true;
    }

    public function get() {
        $response = [
            "theme" => get_theme(),
        ];
        return $response;
    }

    /**
     * Auth function for post request method on api/v2/account/theme
     * All necessary auth is done in check_authentication so this just returns true.
     */
    public function authorized_for_post() {
        return true;
    }

    public function post() {
        $theme = grab_request_var("theme", get_theme());
        set_user_meta(0, "theme", $theme);
        $response = [
            "theme" => $theme,
        ];
        return $response;
    }
}