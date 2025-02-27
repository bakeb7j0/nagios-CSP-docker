<?php

namespace api\v2\app;
use api\v2\Base;

require_once(dirname(__FILE__) . '/../../../../includes/common.inc.php');

/**
 * Gets values that were used and ton in core.js and are used in functions that were ported from core.js to new codebase
 */
class bootstrap extends Base {
    /**
     * Auth function for get request method on api/v2/app/bootstrap
     * All necessary auth is done in check_authentication so this just returns true.
     */
    public function authorized_for_get() {
        return true;
    }

    public function get() {
        $response = [
            "baseUrl" => get_base_url(),
            "backendUrl" => urlencode(get_backend_url(false)),
            "ajaxProxyUrl" => get_ajax_proxy_url(),
        ];
        return $response;
    }
}