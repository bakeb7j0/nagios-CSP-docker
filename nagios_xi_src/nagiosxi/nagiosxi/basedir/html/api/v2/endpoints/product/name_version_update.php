<?php

namespace api\v2\product;
use api\v2\Base;

require_once(dirname(__FILE__) . '/../../../../includes/common.inc.php');

class name extends Base {
    public function authorized_for_get() {
        return true;
    }

    public function get() {
        $response = [
            'display_full_name' =>     get_product_name(),
            'display_short_name' =>    get_product_name(true),
            'overridden_full_name' =>  get_product_name(false, true),
            'overridden_short_name' => get_product_name(true, true)
        ];
        return $response;
    }
}

class version extends Base {
    public function authorized_for_get() {
        return true;
    }

    public function get() {
        $response = [
            'version' => get_product_version(),
            'release' => get_product_release(),
            'build'   => get_build_id()
        ];
        return $response;
    }
}

/**
 * Gets information and status regarding product updates, like the update URL and whether an update is available
 */
class update extends Base {
    public function authorized_for_get() {
        return true;
    }

    public function get() {
        $last_update_check_succeeded = get_option("last_update_check_succeeded");
        $update_available = get_option("update_available");
        $hide_updates = false;
        if (custom_branding()) {
            global $bcfg;
            if ($bcfg['hide_updates']) {
                $hide_updates = true;
            }
        }
        $response = [
            "update_url" => get_update_check_url(),
            "lastUpdateCheckSucceeded" => $last_update_check_succeeded,
            "updateAvailable" => $update_available,
            "hideUpdates" => $hide_updates,
        ];
        return $response;
    }
}