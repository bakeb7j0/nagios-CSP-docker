<?php

namespace api\v2\status;
use api\v2\Base;

require_once(dirname(__FILE__) . '/../../../../includes/common.inc.php');

/**
 * API wrapper for get_xml_host_status, look into the function to learn more about the structure of the output
 */
class host extends Base {
    public function authorized_for_get() {
        return true;
    }

    public function get() {
        global $request;
        $response = get_xml_host_status($request);
        if(!$response) {
            $response = array( "recordcount" => 0 );
        }
        return $response;
    }
}

/**
 * API wrapper for get_xml_service_status, look into the function to learn more about the structure of the output
 */
class service extends Base {
    public function authorized_for_get() {
        return true;
    }

    public function get() {
        global $request;
        $response = get_xml_service_status($request);
        if(!$response) {
            $response = array( "recordcount" => 0 );
        }
        return $response;
    }
}