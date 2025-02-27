<?php

namespace api\v2\account;
use api\v2\Base;

require_once(dirname(__FILE__) . '/../../../../includes/common.inc.php');

/**
 * Gets the language
 */
class language extends Base {
    /**
     * Auth function for get request method on api/v2/account/language
     * All necessary auth is done in check_authentication so this just returns true.
     */
    public function authorized_for_get() {
        return true;
    }

    public function get() {
        $user_id = $_SESSION["user_id"];
        $language = get_user_meta($user_id, "language", "");

        if($language == "") {
            if (!empty($_SESSION['language'])) {
                $language = $_SESSION["language"];
            } else {
                // Try user-specific and global default language from DB
                $udblang = get_user_meta(0, "default_language");
                if (!empty($udlang)) {
                    $language = $udblang;
                } else {
                    $dblang = get_option("default_language");
                    $language = $dblang;
                }
            }
        }

        return [
            "language" => $language,
        ];
    }
}