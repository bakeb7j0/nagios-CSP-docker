<?php

namespace api\v2\app;
use api\v2\Base;

require_once(dirname(__FILE__) . '/../../../../includes/common.inc.php');

/**
 * Gets the entire menu structure parsed into an easier to digest array
 */
class menu extends Base {
    /**
     * Auth function for get request method on api/v2/app/menu
     * All necessary auth is done in check_authentication so this just returns true.
     */
    public function authorized_for_get() {
        return true;
    }

    /**
     * API wrapper for get_menu_all
     */
    public function get() {
        return get_menu_all();
    }
}