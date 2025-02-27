<?php

namespace api\v2\status;
use api\v2\Base;

require_once(dirname(__FILE__) . '/../../../../includes/common.inc.php');

/**
 * Just gets IP status useful for the LoginDialog component
 */
class ip extends Base {
    public function authorized_for_get() {
        return true;
    }

    public function get() {
        $server_ip_addr = $_SERVER['SERVER_ADDR'];
        // Get host/ip address from the internal url
        $host_or_ip = get_internal_url();
        preg_match('/:\/\/(.*)\//U', $host_or_ip, $clean);
        $host_or_ip = $clean[1];

        // Get IP from hostname if possible
        $could_not_resolve = false;
        if (!filter_var($host_or_ip, FILTER_VALIDATE_IP)) {
            $ip = gethostbyname($host_or_ip);
            if ($ip == $host_or_ip) {
                $could_not_resolve = true;
            } else {
                $host_or_ip = $ip;
            }
        }
        $mismatch_found = ($host_or_ip != $server_ip_addr);
        $response = array(
            "could_not_resolve" => $could_not_resolve,
            "mismatch_found" => $mismatch_found,
        );
        return $response;
    }
}