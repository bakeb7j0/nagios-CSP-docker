<?php

namespace api\v2\product;
use api\v2\Base;

require_once(dirname(__FILE__) . '/../../../../includes/common.inc.php');

/**
 * Information related to the custom branding component
 */
class branding extends Base {
    /**
     * Auth function for get request method on api/v2/product/branding
     * All necessary auth is done in check_authentication so this just returns true.
     */
    public function authorized_for_get() {
        return true;
    }

    /**
     * Getting information related to custom branding component
     */
    public function get() {
        $response = ['custom_branding' => "false"];
        if (custom_branding()) {
            global $bcfg;
            /* Notable omission: license_file, which is a filesystem path */
            $response = [
                'product_name'            => $bcfg['product_name'],
                'product_name_short'      => $bcfg['product_name_short'],
                'major_version'           => $bcfg['major_version'],
                'minor_version'           => $bcfg['minor_version'],
                'copyright'               => $bcfg['copyright'],
                'about'                   => $bcfg['about'],
                'hide_about'              => $bcfg['hide_about'],
                'hide_legal'              => $bcfg['hide_legal'],
                'hide_credits'            => $bcfg['hide_credits'],
                'hide_trademarks'         => $bcfg['hide_trademarks'],
                'hide_footer_left'        => $bcfg['hide_footer_left'],
                'hide_core_verify_header' => $bcfg['hide_core_verify_header'],
                'contact_support'         => $bcfg['contact_support'],
                'contact_sales'           => $bcfg['contact_sales'],
                'contact_web'             => $bcfg['contact_web'],
                'sales_email'             => $bcfg['sales_email'],
            ];
        }
        return $response;
    }
}