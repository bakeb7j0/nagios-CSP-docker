<?php

namespace api\v2\product;
use api\v2\Base;

require_once(dirname(__FILE__) . '/../../../../includes/common.inc.php');

/**
 * API endpoint for information relating to the product license
 */
class license extends Base {
    /**
     * Auth function for get request method on api/v2/product/license
     * All necessary auth is done in check_authentication so this just returns true.
     */
    public function authorized_for_get() {
        return true;
    }

    /**
     * Getting current info on the status of the license
     */
    public function get() {
        return get_license_info_array();
    }
}