<?php

namespace api\v2\status;
use api\v2\Base;

require_once(dirname(__FILE__) . '/../../../../includes/common.inc.php');

/**
 * API wrapper for get_program_status_xml_output, look into the function to learn more about the structure of the output
 */
class program extends Base {
    public function authorized_for_get() {
        return true;
    }

    public function get() {
        return get_program_status_xml_output(array(), true);
    }
}