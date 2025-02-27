<?php
//
// Manage config section of Nagios XI admin panel
// Copyright (c) 2008-2020 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/../includes/common.inc.php');

// Initialization stuff
pre_init();
init_session();
grab_request_vars();

// Check prereqs and authentication
check_prereqs();
check_authentication();


// Only admins can access this page
if (is_admin() == false) {
    echo _("You are not authorized to access this feature. Contact your system administrator for more information, or to obtain access to this feature.");
    exit();
}


route_request();


function route_request()
{
    global $request;

    if (isset($request['update'])) {
        do_update_options();
    } else {
        show_options();
    }
}


/**
 * @param   bool    $error
 * @param   string  $msg
 */
function show_options($error = false, $msg = "")
{
    global $request, $cfg;

    $url = get_option('url');
    if (have_value($url) == false) {
        $url = get_base_url();
    }

    $reset = 0;
    if (array_key_exists('reset_frame', $_SESSION)) {
        $reset = $_SESSION['reset_frame'];
        unset($_SESSION['reset_frame']);
    }

    // Get options
    $url = grab_request_var("url", $url);
    $external_url = grab_request_var("external_url", get_option('external_url', ''));
    $language = grab_request_var("defaultLanguage", get_option('default_language'));
    $date_format = grab_request_var("defaultDateFormat", get_option('default_date_format'));
    $number_format = grab_request_var("defaultNumberFormat", intval(get_option('default_number_format')));
    $week_format = grab_request_var("defaultWeekFormat", intval(get_option('default_week_format')));
    $disable_renewal_reminder = grab_request_var('disable_renewal_reminder', get_option('disable_renewal_reminder', 0));

    // System setting for new XI theme
    $theme = grab_request_var("theme", get_option('theme', 'xi5'));
    $hc_theme = grab_request_var("hc_theme", get_option('default_highcharts_theme'));
    $highchart_scale = grab_request_var("highchart_scale", get_option('highchart_scale'));
    $highcharts_default_type = grab_request_var("highcharts_default_type", get_option('highcharts_default_type', 'line'));
    $perfdata_theme = grab_request_var("perfdata_theme", get_option('perfdata_theme'));
    $wc_enable = grab_request_var("wc_enable", get_option('wc_enable', 1));
    $wc_display = grab_request_var("wc_display", get_option('wc_display', 0));

    // Config Timezone
    $cfg_timezone = grab_request_var("timezone", get_option('timezone'));

    // Acknowledgement defaults
    $adefault_sticky_acknowledgment = grab_request_var('adefault_sticky_acknowledgment', get_option('adefault_sticky_acknowledgment', 1));
    $adefault_send_notification = grab_request_var('adefault_send_notification', get_option('adefault_send_notification', 1));
    $adefault_persistent_comment = grab_request_var('adefault_persistent_comment', get_option('adefault_persistent_comment'));

    // Sensitive Fields Autocomplete
    $sensitive_field_autocomplete = grab_request_var('sensitive_field_autocomplete', get_option('sensitive_field_autocomplete', 1));
    $reports_exporting = grab_request_var('reports_exporting', get_option('reports_exporting', 1));

    $ccm_manage_mrtg = grab_request_var('ccm_manage_mrtg', get_option('ccm_manage_mrtg', 0));

    // Cookie timeout
    $cookie_timeout_mins = grab_request_var('cookie_timeout_mins', get_option('cookie_timeout_mins', 30));
    $cookie_auto_refresh = grab_request_var('cookie_auto_refresh', get_option('cookie_auto_refresh', 1));

    // Frame options
    $frame_options_norestrict = grab_request_var('frame_options_norestrict', get_option('frame_options_norestrict', 0));
    $frame_options_allowed_hosts = grab_request_var('frame_options_allowed_hosts', get_option('frame_options_allowed_hosts', ''));
    $frame_src_norestrict = grab_request_var('frame_src_norestrict', get_option('frame_src_norestrict', 1));
    $frame_src_allowed_hosts = grab_request_var('frame_src_allowed_hosts', get_option('frame_src_allowed_hosts', ''));

    $curl_ssl_version = grab_request_var('curl_ssl_version', get_option('curl_ssl_version', 6));

    $curl_force_verifypeer = grab_request_var('curl_force_verifypeer', get_option('curl_force_verifypeer', 1));

    $verify_host_header = grab_request_var('verify_host_header', get_option('verify_host_header', 0));

    $hc_ignore_null = grab_request_var('hc_ignore_null', get_option('hc_ignore_null', 0));
    $hc_show_rrd_stats = grab_request_var('hc_show_rrd_stats', array());
    if (empty($hc_show_rrd_stats)) {
        $temp = get_option('hc_show_rrd_stats', array('avg', 'max', 'last'));
        if (is_array($temp)) {
            $hc_show_rrd_stats = $temp;
        } else {
            $hc_show_rrd_stats = unserialize($temp);
        }
    }

    // Passwords and Accounts
    $account_lockout = grab_request_var('account_lockout', get_option('account_lockout', 0));
    $account_login_attempts_before_lockout = grab_request_var('account_login_attempts_before_lockout', get_option('account_login_attempts_before_lockout', 3));
    $account_lockout_period = grab_request_var('account_lockout_period', get_option('account_lockout_period', 300));
    $pw_check_old_passwords = grab_request_var('pw_check_old_passwords', get_option('pw_check_old_passwords', 0));
    $pw_enforce_requirements = grab_request_var('pw_enforce_requirements', get_option('pw_enforce_requirements', 0));
    $pw = get_pw_requirements_array();

    $two_factor_auth = grab_request_var('two_factor_auth', get_option('two_factor_auth', 0));
    $two_factor_timeout = grab_request_var('two_factor_timeout', get_option('two_factor_timeout', 15));
    $two_factor_cookie = grab_request_var('two_factor_cookie', get_option('two_factor_cookie', 0));
    $two_factor_cookie_timeout = grab_request_var('two_factor_cookie_timeout', get_option('two_factor_cookie_timeout', 90));

    $secure_rr_url = grab_request_var('secure_rr_url', get_option('secure_rr_url', grab_array_var($cfg, 'secure_response_url', 0)));
    $insecure_login = grab_request_var('insecure_login', get_option('insecure_login', 0));
    $rr_valid_link_timeout = grab_request_var('rr_valid_link_timeout', get_option('rr_valid_link_timeout', 30));

    // SSH Terminal
    $ssh_terminal_disable = grab_request_var('ssh_terminal_disable', get_option('ssh_terminal_disable', 1));

    // ModSecurity
    $modsecurity_enabled = grab_request_var('modsecurity_enabled', get_option('modsecurity_enabled', 0));

    // User email template
    $default_email_subject = _("%product% Account Created");
    $default_email_body = _("An account has been created for you to access %product%. You can login using the following information:\n\nUsername: %username%\nPassword: %password%\nURL: %url%\n\n");
    $user_new_account_email_subject = grab_request_var('user_new_account_email_subject', get_option('user_new_account_email_subject', $default_email_subject));
    $user_new_account_email_body = grab_request_var('user_new_account_email_body', get_option('user_new_account_email_body', $default_email_body));

    // Fuse key
    $fusekey = get_option('fusekey');
    if (empty($fusekey)) {
        $fusekey = strtoupper(md5(uniqid()));
        set_option('fusekey', $fusekey);
    }

    if (substr($url, -1) != '/') { $url .= '/'; }
    if (!empty($external_url) && substr($external_url, -1) != '/') { $external_url .= '/'; }

    // If perfdata theme doesn't exist we should set it to 1
    if (get_option('perfdata_theme') == NULL) {
        $perfdata_theme = 1;
    }

    // Default to check for updates unless overridden
    $auc = get_option('auto_update_check');
    if ($auc == "") {
        $auc = 1;
    }
    $auto_update_check = grab_request_var("auto_update_check", $auc);
    if ($auto_update_check == "on") {
        $auto_update_check = 1;
    }

    // Allow html in certain areas?
    $allow_status_html = grab_request_var('allow_status_html', get_option('allow_status_html'));
    $allow_comment_html = grab_request_var('allow_comment_html', get_option('allow_comment_html'));

    // Get global variables
    $languages = get_languages();
    $number_formats = get_number_formats();
    $date_formats = get_date_formats();
    $week_formats = get_week_formats();

    // Start actual "System Settings" page output
    do_page_start(array("page_title" => _("System Settings")), true);

    if ($reset) {
        // Flash the next flash message
        flash_message(_('System settings updated.'));
    }
?>

    <script type="text/javascript">
    <?php if ($reset) { ?>
    window.parent.location.href="<?php echo get_base_url(); ?>admin/?xiwindow=globalconfig.php";
    <?php } ?>
    </script>

    <h1><?php echo _("System Settings"); ?></h1>
    <?php display_message($error, false, $msg); ?>

    <form id="manageOptionsForm" method="post" style="margin-top: 5px;">
        <input type="hidden" name="options" value="1">
        <?php echo get_nagios_session_protector(); ?>
        <input type="hidden" name="update" value="1">
        <input type="hidden" value="general" id="tab_hash" name="tab_hash">

        <script type="text/javascript">
        $(document).ready(function() {
            function grey_out_based_on_pw_requirements() {
                if ($('#pw_enforce_requirements').is(':checked')) {
                    $('#pw_max_age').prop('disabled', false);
                    $('#pw_min_length').prop('disabled', false);
                    $('#pw_enforce_complexity').prop('disabled', false);
                    $('h5.complexity, table.complexity').show();
                } else {
                    $('#pw_max_age').prop('disabled', true);
                    $('#pw_min_length').prop('disabled', true);
                    $('#pw_enforce_complexity').prop('disabled', true);
                    $('h5.complexity, table.complexity').hide();
                }
            }
            function grey_out_based_on_complexity_requirements() {
                if (!$('#pw_enforce_complexity').is(':checked') || $('#pw_enforce_complexity').is(':disabled')) {
                    $('#pw_complex_upper').prop('disabled', true);
                    $('#pw_complex_lower').prop('disabled', true);
                    $('#pw_complex_numeric').prop('disabled', true);
                    $('#pw_complex_special').prop('disabled', true);
                } else {
                    $('#pw_complex_upper').prop('disabled', false);
                    $('#pw_complex_lower').prop('disabled', false);
                    $('#pw_complex_numeric').prop('disabled', false);
                    $('#pw_complex_special').prop('disabled', false);
                }
            }

            $('#pw_enforce_requirements').click(function() {
                grey_out_based_on_pw_requirements();
                grey_out_based_on_complexity_requirements();
            });
            $('#pw_enforce_complexity').click(function() {
                grey_out_based_on_complexity_requirements();
            });

            grey_out_based_on_pw_requirements();
            grey_out_based_on_complexity_requirements();
        });
        </script>

        <div id="tabs" class="hide">

            <ul>
                <li><a href="#general"><i class="material-symbols-outlined md-18 md-400 md-middle md-pointer md-padding">settings</i> <?php echo _('General'); ?></a></li>
                <li><a href="#security"><i class="material-symbols-outlined md-18 md-400 md-middle md-pointer md-padding">verified_user</i> <?php echo _('Security'); ?></a></li>
                <li><a href="#passwords"><i class="material-symbols-outlined md-18 md-400 md-middle md-pointer md-padding">lock</i> <?php echo _('Passwords & Accounts'); ?></a></li>
                <li><a href="#display"><i class="material-symbols-outlined md-18 md-400 md-middle md-pointer md-padding">computer</i> <?php echo _('Theme & Display'); ?></a></li>
                <li><a href="#defaults"><i class="material-symbols-outlined md-18 md-400 md-middle md-pointer md-padding">person</i> <?php echo _('User Accounts'); ?></a></li>
                <li><a href="#integration"><i class="material-symbols-outlined md-18 md-400 md-middle md-pointer md-padding">key</i> <?php echo _('Integration'); ?></a></li>
                <li><a href="#backwards"><i class="material-symbols-outlined md-18 md-400 md-middle md-pointer md-padding">history</i> <?php echo _('Backward Compatibility'); ?></a></li>
            </ul>
            <div id="general" class="neptune-admin-config-table neptune-admin-config-table-215">
                <?php if (!is_neptune()) { ?>
                    <h5 class="ul"><?php echo _("General Program Settings"); ?></h5>
                <?php } else {
                    echo neptune_heading(_('General Program Settings'));
                } ?>
                <table class="table table-condensed table-no-border table-auto-width">
                    <tr>
                        <td class="vt">
                            <label for="urlBox"><?php echo _("Program URL"); ?>:</label>
                        </td>
                        <td>
                            <div>
                                <input type="text" size="45" name="url" id="urlBox" value="<?php echo encode_form_val($url); ?>" class="textfield form-control">
                                <?php if (!is_neptune()) { ?>
                                    <div class="subtext"><?php echo sprintf(_("The default URL used to access %s directly from your internal network"), get_product_name()); ?>.</div>
                                <?php } else {
                                    echo neptune_subtext(sprintf(_("The default URL used to access %s directly from your internal network"), get_product_name()));
                                } ?>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="vt">
                            <label for="externalurlBox"><?php echo _("External URL"); ?>:</label>
                        </td>
                        <td>
                            <input type="text" size="45" name="external_url" id="externalurlBox" value="<?php echo encode_form_val($external_url); ?>" class="textfield form-control">
                            <?php if (!is_neptune()) { ?>
                                <div class="subtext"><?php echo sprintf(_("The URL used to access %s from outside of your internal network (if different than the default above).  If defined, this URL will be referenced in email alerts and generated pdf reports to allow quick access to the interface"), get_product_name()); ?>.</div>
                            <?php } else {
                                echo neptune_subtext_max_width(sprintf(_("The URL used to access %s from outside of your internal network (if different than the default above).  If defined, this URL will be referenced in email alerts and generated pdf reports to allow quick access to the interface"), get_product_name()), 500);
                            } ?>
                        </td>
                    </tr>
                    <?php if (!is_v2_license()) { ?>
                    <tr>
                        <td></td>
                        <?php if (!is_neptune()) { ?>
                            <td class="checkbox">
                                <label class="checkbox">
                                    <input type="checkbox" class="checkbox" id="autoUpdateCheckBox" name="auto_update_check" <?php echo is_checked($auto_update_check, 1); ?>>
                                    <?php echo _("Automatically Check for Updates"); ?>
                                    <span>(<a href="<?php echo get_update_check_url(); ?>" target="_blank" rel="noreferrer"><?php echo _("Check Now"); ?></a>)</span>
                                </label>
                            </td>
                        <?php } else { ?>
                            <td>
                                <div class="centered-checkbox">
                                    <input type="checkbox" class="" id="autoUpdateCheckBox" name="auto_update_check" <?php echo is_checked($auto_update_check, 1); ?>>
                                    <label for="autoUpdateCheckBox"> <?php echo _("Automatically Check for Updates"); ?>
                                    <span>(<a href="<?php echo get_update_check_url(); ?>" target="_blank" rel="noreferrer"><?php echo _("Check Now"); ?></a>)</span>
                                    </label>
                                </div>
                            </td>
                        <?php } ?>
                    </tr>
                    <?php } ?>
                </table>

                <?php
                $current_timezone = get_current_timezone();
                if (!empty($cfg_timezone) && $cfg_timezone != $current_timezone) {
                    $current_timezone = $cfg_timezone;
                }
                $timezones = get_timezones();
                if (!is_neptune()) { ?>
                    <h5 class="ul"><?php echo _("Timezone Settings"); ?></h5>
                <?php } else {
                    echo neptune_heading(_('Timezone Settings'));
                } ?>
                <table class="table table-condensed table-no-border table-auto-width">
                    <tr>
                        <td><label><?php echo _("Timezone"); ?>:</label></td>
                        <td>
                            <select id="timezone" name="timezone" class="form-control">
                                <?php
                                $set = false;
                                foreach ($timezones as $name => $tz) {
                                    ?>
                                    <option value="<?php echo $tz; ?>"<?php if ($tz == $current_timezone) {
                                        echo "selected";
                                        $set = true;
                                    } ?>><?php echo $name; ?></option>
                                <?php
                                }

                                if (!$set) {
                                    ?>
                                    <option value="<?php echo $current_timezone; ?>"
                                            selected><?php echo $current_timezone; ?></option>
                                <?php
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php if (!is_neptune()) { ?>
                    <h5 class="ul"><?php echo _("Other Settings"); ?></h5>
                <?php } else {
                    echo neptune_heading(_('Other Settings'));
                } ?>
                <table class="table table-condensed table-no-border table-auto-width">
                    <tr>
                        <td>
                            <label><?php echo _("Acknowledgement Defaults"); ?>:</label>
                        </td>
                        <?php if (!is_neptune()) { ?>
                            <td class="checkbox">
                                <label>
                                    <input type="checkbox" name="adefault_sticky_acknowledgment" value="1" <?php echo is_checked($adefault_sticky_acknowledgment, 1); ?>> <?php echo _('Sticky Acknowledgement'); ?>
                                </label>
                                <label style="margin-left: 10px;">
                                    <input type="checkbox" name="adefault_send_notification" value="1" <?php echo is_checked($adefault_send_notification, 1); ?>> <?php echo _('Send Notification'); ?>
                                </label>
                                <label style="margin-left: 10px;">
                                    <input type="checkbox" name="adefault_persistent_comment" value="1" <?php echo is_checked($adefault_persistent_comment, 1); ?>> <?php echo _('Persistent Comment'); ?>
                                </label>
                            </td>
                        <?php } else { ?>
                            <td>
                                <?php
                                echo neptune_centered_checkbox(_('Sticky Acknowledgement'), "adefault_sticky_acknowledgment", "adefault_sticky_acknowledgment", $adefault_sticky_acknowledgment, 1);
                                echo neptune_centered_checkbox(_('Send Notification'), "adefault_send_notification", "adefault_send_notification", $adefault_send_notification, 1);
                                echo neptune_centered_checkbox(_('Persistent Comment'), "adefault_persistent_comment", "adefault_persistent_comment", $adefault_persistent_comment, 1);
                                ?>
                            </td>
                        <?php } ?>
                    </tr>
                    <tr>
                        <td>
                            <label><?php echo _("Report Exporting"); ?>:</label>
                        </td>
                        <?php if (!is_neptune()) { ?>
                            <td class="checkbox">
                                <label>
                                    <input type="checkbox" name="reports_exporting" id="reports_exporting"  value="1" <?php echo is_checked($reports_exporting, 1); ?>> <?php echo _('Local Exporting'); ?>
                                </label>
                            </td>
                        <?php } else { ?>
                            <td>
                                <?php
                                echo neptune_centered_checkbox(_('Local Exporting'), "reports_exporting", "reports_exporting", $reports_exporting, 1);
                                ?>
                            </td>
                        <?php } ?>
                    </tr>
                    <tr>
                        <td>
                            <label>
                                <?php echo _("Autocomplete Sensitive Fields"); ?>:
                            </label>
                        </td>
                        <?php if (!is_neptune()) { ?>
                            <td class="checkbox">
                                <label>
                                    <input type="checkbox" id="sensitive_field_autocomplete" name="sensitive_field_autocomplete" value="1" <?php echo is_checked($sensitive_field_autocomplete, 1); ?>>
                                    <?php echo _("When checked, autocompletion is enabled for sensitive fields"); ?>
                                </label>
                            </td>
                        <?php } else { ?>
                            <td>
                                <?php
                                echo neptune_centered_checkbox(_('When checked, autocompletion is enabled for sensitive fields'), "sensitive_field_autocomplete", "sensitive_field_autocomplete", $sensitive_field_autocomplete, 1);
                                ?>
                            </td>
                        <?php } ?>
                    </tr>
                    
                    <tr>
                        <td>
                            <label>
                                <?php echo _("Automatic MRTG Configuration Management"); ?>:
                            </label>
                        </td>
                        <?php if (!is_neptune()) { ?>
                            <td class="checkbox">
                                <label>
                                    <input type="checkbox" id="ccm_manage_mrtg" name="ccm_manage_mrtg" value="1" <?php echo is_checked($ccm_manage_mrtg, 1); ?>>
                                    <?php echo _("When checked, the Core Config Manager will delete MRTG configuration files that are no longer needed."); ?>
                                </label>
                            </td>
                        <?php } else { ?>
                            <td>
                                <?php
                                echo neptune_centered_checkbox(_('When checked, the Core Config Manager will delete MRTG configuration files that are no longer needed.'), "ccm_manage_mrtg", "ccm_manage_mrtg", $ccm_manage_mrtg, 1);
                                ?>
                            </td>
                        <?php } ?>
                    </tr>
                    <tr>
                        <td rowspan="2">
                            <label>
                                <?php echo _('Allow HTML Rendering'); ?>:
                            </label>
                        </td>
                        <?php if (!is_neptune()) { ?>
                            <td class="checkbox" style="padding-bottom: 0;">
                                <p>(<?php echo _('WARNING: The below options are considered insecure and could potentially lead to XSS vulnerabilities. These are turned off by default.'); ?>)</p>
                                <label class="checkbox">
                                    <input type="checkbox" id="allow_status_html" name="allow_status_html" <?php echo is_checked($allow_status_html); ?>>
                                    <?php echo _("Allow HTML tags in host/service statuses"); ?>
                                </label>
                            </td>
                        <tr>
                            <td class="checkbox" style="padding-top: 0;">
                                <label class="checkbox">
                                    <input type="checkbox" id="allow_comment_html" name="allow_comment_html" <?php echo is_checked($allow_comment_html); ?>>
                                    <?php echo _("Allow HTML tags in host/service comments"); ?>
                                </label>
                            </td>
                        </tr>
                        <?php } else { ?>
                            <td>
                                <div class="message">
                                    <ul class="alert warningMessage">
                                        <li>
                                            <i class="material-symbols-outlined md-24">warning</i>
                                            <?php echo _('WARNING: The below options are considered insecure and could potentially lead to XSS vulnerabilities. These are turned off by default.'); ?>
                                        </li>
                                    </ul>
                                <?php
                                echo neptune_centered_checkbox(_('Allow HTML tags in host/service statuses'), "allow_status_html", "allow_status_html", $allow_status_html);
                                echo neptune_centered_checkbox(_('Allow HTML tags in host/service comments'), "allow_comment_html", "allow_comment_html", $allow_comment_html);
                                ?>
                            </td>
                        <?php } ?>
                    </tr>

                </table>
            </div>

            <div id="security" class="neptune-admin-config-table neptune-admin-config-table-215">
                <?php if (!is_neptune()) { ?>
                    <h5 class="ul"><?php echo _("Session Cookie Settings"); ?></h5>
                <?php } else {
                    echo neptune_heading(_('Session Cookie Settings'));
                } ?>
                <table class="table table-condensed table-no-border table-auto-width">
                    <tr>
                        <td>
                            <label>
                                <?php echo _("Keep Alive"); ?>:
                            </label>
                        </td>
                        <?php if (!is_neptune()) { ?>
                            <td class="checkbox">
                                <label for="cookie_auto_refresh">
                                    <input type="checkbox" class="checkbox" name="cookie_auto_refresh" id="cookie_auto_refresh" value="1" <?php echo is_checked($cookie_auto_refresh, 1); ?>>
                                    <?php echo _("Keep session alive while interface is open, even if user is inactive."); ?>
                                </label>
                            </td>
                        <?php } else { ?>
                            <td>
                                <?php
                                echo neptune_centered_checkbox(_('Keep session alive while interface is open, even if user is inactive'), "cookie_auto_refresh", "cookie_auto_refresh", $cookie_auto_refresh, 1);
                                ?>
                            </td>
                        <?php } ?>
                    </tr>
                    <tr>
                        <td class="vt">
                            <label for="cookie_timeout_mins">
                                <?php echo _("Session Timeout"); ?>:
                            </label>
                        </td>
                        <td class="form-inline">
                            <div class="input-group">
                                <input type="text" name="cookie_timeout_mins" id="cookie_timeout_mins" value="<?php echo intval($cookie_timeout_mins); ?>" class="form-control" style="width: 50px;">
                                <div class="input-group-addon"><?php echo _("min"); ?></div>
                            </div>
                            <div class="subtext neptune-form-subtext neptune-form-spacer">
                                <?php echo _("Amount of time in minutes that a user remains logged in while inactive. Set timeout to 0 for unlimited. Some pages such as the operations center will not log users out for inactivity."); ?>
                            </div>
                        </td>
                    </tr>
                </table>
                <?php if (!is_neptune()) { ?>
                    <h5 class="ul"><?php echo _("Two Factor Authentication (Email)"); ?></h5>
                <?php } else {
                    echo neptune_heading(_('Two Factor Authentication (Email)'));
                } ?>
                <table class="table table-condensed table-no-border table-auto-width">
                    <tr>
                        <td>
                            <label>
                                <?php echo _("Enable Two Factor Auth"); ?>:
                            </label>
                        </td>
                        <?php if (!is_neptune()) { ?>
                            <td class="checkbox">
                                <label for="two_factor_auth">
                                    <input type="checkbox" name="two_factor_auth" id="two_factor_auth" value="1" <?php echo is_checked($two_factor_auth, 1); ?>>
                                    <?php echo _("Send an email to users after logging in to authenticate."); ?>
                                </label>
                            </td>
                        <?php } else { ?>
                            <td>
                                <?php
                                echo neptune_centered_checkbox(_('Send an email to users after logging in to authenticate'), "two_factor_auth", "two_factor_auth", $two_factor_auth, 1);
                                ?>
                            </td>
                        <?php } ?>
                    </tr>
                    <tr>
                        <td class="vt">
                            <label for="two_factor_timeout">
                                <?php echo _("Two Factor Token Timeout"); ?>:
                            </label>
                        </td>
                        <td class="form-inline">
                            <div class="input-group">
                                <input type="text" name="two_factor_timeout" id="two_factor_timeout" value="<?php echo intval($two_factor_timeout); ?>" class="form-control" style="width: 50px;">
                                <div class="input-group-addon"><?php echo _("min"); ?></div>
                            </div>
                            <div class="subtext neptune-form-subtext neptune-form-spacer">
                                <?php echo _("Amount of time in minutes that two factor auth tokens should expire."); ?>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label>
                                <?php echo _("Two Factor Cookie"); ?>:
                            </label>
                        </td>
                        <?php if (!is_neptune()) { ?>
                            <td class="checkbox">
                                <label for="two_factor_cookie">
                                    <input type="checkbox" class="checkbox" name="two_factor_cookie" id="two_factor_cookie" value="1" <?php echo is_checked($two_factor_cookie, 1); ?>>
                                    <?php echo _("Allow users to choose to remember their computer/browser with a cookie."); ?>
                                </label>
                            </td>
                        <?php } else { ?>
                            <td>
                                <?php
                                echo neptune_centered_checkbox(_('Allow users to choose to remember their computer/browser with a cookie'), "two_factor_cookie", "two_factor_cookie", $two_factor_cookie, 1);
                                ?>
                            </td>
                        <?php } ?>
                    </tr>
                    <tr>
                        <td class="vt">
                            <label for="two_factor_cookie_timeout">
                                <?php echo _("Two Factor Cookie Timeout"); ?>:
                            </label>
                        </td>
                        <td class="form-inline">
                            <div class="input-group">
                                <input type="text" name="two_factor_cookie_timeout" id="two_factor_cookie_timeout" value="<?php echo intval($two_factor_cookie_timeout); ?>" class="form-control" style="width: 50px;">
                                <div class="input-group-addon"><?php echo _("days"); ?></div>
                            </div>
                            <div class="subtext neptune-form-subtext neptune-form-spacer">
                                <?php echo _("Amount of time in days that two factor cookie should expire."); ?>
                            </div>
                        </td>
                    </tr>
                </table>
                <?php if (!is_neptune()) { ?>
                    <h5 class="ul"><?php echo _("Rapid Response URL Settings"); ?></h5>
                <?php } else {
                    echo neptune_heading(_('Rapid Response URL Settings'));
                } ?>
                <table class="table table-condensed table-no-border table-auto-width">
                    <tr>
                        <td>
                            <label>
                                <?php echo _("Secure Rapid Response"); ?>:
                            </label>
                        </td>
                        <?php if (!is_neptune()) { ?>
                            <td class="checkbox">
                                <label for="secure_rr_url">
                                    <input type="checkbox" name="secure_rr_url" id="secure_rr_url" value="1" <?php echo is_checked($secure_rr_url, 1); ?>>
                                    <?php echo _("Secure rapid response URL sent in notifications (users will not get automatically logged in)"); ?>
                                </label>
                            </td>
                        <?php } else { ?>
                        <td>
                            <?php
                            echo neptune_centered_checkbox(_('Secure rapid response URL sent in notifications (users will not get automatically logged in)'), "secure_rr_url", "secure_rr_url", $secure_rr_url, 1);
                            ?>
                        </td>
                        <?php } ?>
                    </tr>
                    <tr>
                        <td class="vt">
                            <label for="rr_valid_link_timeout">
                                <?php echo _("Rapid Response URL Timeout"); ?>:
                            </label>
                        </td>
                        <td class="form-inline">
                            <div class="input-group">
                                <input type="text" name="rr_valid_link_timeout" id="rr_valid_link_timeout" value="<?php echo intval($rr_valid_link_timeout); ?>" class="form-control" style="width: 50px;">
                                <div class="input-group-addon"><?php echo _("min"); ?></div>
                            </div>
                            <div class="subtext neptune-form-subtext neptune-form-spacer">
                                <?php echo _("Amount of time in minutes before rapid response URLs time out and cannot be used."); ?>
                            </div>
                        </td>
                    </tr>
                </table>
                <?php if (!is_neptune()) { ?>
                    <h5 class="ul"><?php echo _("Page Security Settings"); ?></h5>
                <?php } else {
                    echo neptune_heading(_('Page Security Settings'));
                } ?>
                <table class="table table-condensed table-no-border table-auto-width">
                    <tr>
                        <td class="vt" style="width: 180px;"><label><?php echo _('Frame Restrictions'); ?></label></td>
                        <td>
                            <p>
                                <?php echo sprintf(_('By default in %s 5.3.0+ all pages are not allowed to be used inside frames'), get_product_name()); ?> (<code>frame</code> <?php echo _('or'); ?> <code>iframe</code>) <?php echo _('except by pages that are loaded from the same hostname.'); ?> <?php echo _("This addition was made to protect against clickjacking and improves the overall security of the application. This restriction is done by setting the <code>X-Frame-Options</code> header to <code>SAMEORIGIN</code> and applying <code>Content-Security-Policy: frame-ancestors 'self'</code>."); ?> <?php echo _("You can add specific hosts which require a <code>?req_frame_access=&lt;host&gt;</code> GET or POST field set when placing a page into a frame not on the same origin and will keep the set frame name for the duration of the session. You can also disable all frame restrictions."); ?>
                            </p>
                            <div class="input-group" style="min-width: 400px; width: 50%; margin-bottom: 10px;">
                                <label class="input-group-addon tt-bind" title="<?php echo _('Accepts a comma separated list of hosts'); ?>"><?php echo _('Allowed Hosts'); ?> 
                                <?php if (!is_neptune()) { ?> <i class="material-symbols-outlined md-pending md-middle md-14 md-help">help</i>
                                <?php } else { ?> <i class="material-symbols-outlined md-pending md-middle md-18 md-help" style="padding: 0 0 2px 0">help</i>
                                <?php } ?>
                                </label>
                                <input type="text" value="<?php echo encode_form_val($frame_options_allowed_hosts); ?>" class="form-control" name="frame_options_allowed_hosts" placeholder="<?php echo _('Example'); ?>: hostname.local,secure.hostname.local">
                            </div>
                            <?php if (!is_neptune()) { ?>
                                <div class="checkbox" style="margin-bottom: 10px">
                                    <label>
                                        <input type="checkbox" value="1" name="frame_options_norestrict" <?php echo is_checked($frame_options_norestrict, 1); ?>>
                                        <b><?php echo _('Disable'); ?></b> - <?php echo _('Do not restrict pages from being opened in iframes from anywhere. (Will not apply <code>X-Frame-Options</code> or <code>Content-Security-Policy: frame-ancestors</code> headers)'); ?>
                                    </label>
                                </div>
                            <?php } else { ?>
                                <div>
                                    <?php
                                    echo neptune_centered_checkbox('<b>' . _('Disable') . '</b>' . ' - ' . _('Do not restrict pages from being opened in iframes from anywhere. (Will not apply <code>X-Frame-Options</code> or <code>Content-Security-Policy: frame-ancestors</code> headers)'), "frame_options_norestrict", "frame_options_norestrict", $frame_options_norestrict, 1);
                                    echo neptune_section_spacer();
                                    ?>
                                </div>
                            <?php } ?>
                            <p>
                                <?php echo sprintf(_('By default in %s 2024+ all outside pages are not allowed inside the product frames'), get_product_name()); ?> (<code>frame</code> <?php echo _('or'); ?> <code>iframe</code>) <?php echo _('except by pages that are added in tools and views.'); ?> <?php echo _("This restriction is done by applying <code>Content-Security-Policy: frame-ancestors 'self'</code>. You can add more allowed sources below. If there are any errors, ensure that the url is complete and correct."); ?>
                            </p>
                            <div class="input-group" style="min-width: 400px; width: 50%; margin-bottom: 10px;">
                                <label class="input-group-addon tt-bind" title="<?php echo _('Accepts a comma separated list of hosts'); ?>"><?php echo _('Allowed Hosts'); ?>
                                <?php if (!is_neptune()) { ?> <i class="material-symbols-outlined md-pending md-middle md-14 md-help">help</i>
                                <?php } else { ?> <i class="material-symbols-outlined md-pending md-middle md-18 md-help" style="padding: 0 0 2px 0">help</i>
                                <?php } ?>
                                </label>
                                <input type="text" value="<?php echo encode_form_val($frame_src_allowed_hosts); ?>" class="form-control" name="frame_src_allowed_hosts" placeholder="<?php echo _('Example'); ?>: hostname.local,secure.hostname.local">
                            </div>
                            <?php if (!is_neptune()) { ?>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" value="1" name="frame_src_norestrict" <?php echo is_checked($frame_src_norestrict, 1); ?>>
                                        <b><?php echo _('Disable'); ?></b> - <?php echo _('Do not restrict source of pages that are opened in iframes. (Will not apply <code>Content-Security-Policy: frame-src</code> headers)'); ?>
                                    </label>
                                </div>
                            <?php } else { ?>
                                <div>
                                    <?php
                                    echo neptune_centered_checkbox('<b>' . _('Disable') . '</b>' . ' - ' . _('Do not restrict source of pages that are opened in iframes. (Will not apply <code>Content-Security-Policy: frame-src</code> headers)'), "frame_src_norestrict", "frame_src_norestrict", $frame_src_norestrict, 1);
                                    ?>
                                </div>
                            <?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="vt"><label><?php echo _('PHP cURL SSL Version'); ?></label></td>
                        <td>
                            <select name="curl_ssl_version" class="form-control" style="width: 50%">
                                <option value="6" <?php echo is_selected($curl_ssl_version, 6); ?>>TLSv1.2</option>
                                <option value="5" <?php echo is_selected($curl_ssl_version, 5); ?>>TLSv1.1</option>
                                <option value="0" <?php echo is_selected($curl_ssl_version, 0); ?>>TLSv1.0 / PHP <?php echo _('Default'); ?></option>
                            </select>
                            <?php if (!is_neptune()) { ?>
                                <p style="margin: 5px 0 0 0;"><?php echo sprintf(_('The connection type for the internal cURL call in %s to use. We default this to TLSv1.2 in 5.3.0 but can be changed to older, still secure versions only.'), get_product_name()); ?></p>
                            <?php } else {
                                echo neptune_subtext(sprintf(_('The connection type for the internal cURL call in %s to use. We default this to TLSv1.2 in 5.3.0 but can be changed to older, still secure versions only.'), get_product_name()));
                            } ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label>
                                <?php echo _("Force cURL Peer Verification"); ?>
                            </label>
                        </td>
                        <?php if (!is_neptune()) { ?>
                            <td class="checkbox">
                                <label for="curl_force_verifypeer">
                                    <input type="checkbox" name="curl_force_verifypeer" id="curl_force_verifypeer" value="1" <?php echo is_checked($curl_force_verifypeer, 1); ?>>
                                    <p style="margin: 5px 0 0 0;"><?php echo _("As of Nagios XI 5.8.9, all internally-loaded URLs will verify peer certificates before processing data. Uncheck this box to allow Nagios XI to skip peer verification when loading Nagios XI, Core, or Log Server URLs."); ?></p>
                                </label>
                            </td>
                        <?php } else { ?>
                            <td>
                                <?php
                                echo neptune_centered_checkbox(_('As of Nagios XI 5.8.9, all internally-loaded URLs will verify peer certificates before processing data. Uncheck this box to allow Nagios XI to skip peer verification when loading Nagios XI, Core, or Log Server URLs'), "curl_force_verifypeer", "curl_force_verifypeer", $curl_force_verifypeer, 1);
                                ?>
                            </td>
                        <?php } ?>
                        <td>
                    </tr>
                    <tr>
                        <td>
                            <label>
                                <?php echo _("Verify Host Header"); ?>
                            </label>
                        </td>
                        <?php if (!is_neptune()) { ?>
                            <td class="checkbox">
                                <label for="verify_host_header">
                                    <input type="checkbox" name="verify_host_header" id="verify_host_header" value="1" <?php echo is_checked($verify_host_header, 1); ?>>
                                    <p style="margin: 5px 0 0 0;"><?php echo _("Select this option if you want to enforce Host header verification to prevent against Host header injection attacks. You will only be able to access XI from the External URL specified in System Settings or from the IP address of the XI machine."); ?></p>
                                </label>
                            </td>
                        <?php } else { ?>
                            <td>
                                <?php
                                echo neptune_centered_checkbox(_("Select this option if you want to enforce Host header verification to prevent against Host header injection attacks. You will only be able to access XI from the External URL specified in System Settings or from the IP address of the XI machine."), "verify_host_header", "verify_host_header", $verify_host_header, 1, false, "", 1);
                                ?>
                            </td>
                        <?php } ?>
                        <td>
                    </tr>
                </table>
                <?php if (!is_neptune()) { ?>
                    <h5 class="ul"><?php echo _("SSH Terminal"); ?></h5>
                <?php } else {
                    echo neptune_heading(_('SSH Terminal'));
                } ?>
                <table class="table table-condensed table-no-border table-auto-width">
                    <tr>
                        <td>
                            <label>
                                <?php echo _("SSH Terminal"); ?>:
                            </label>
                        </td>
                        <?php if (!is_neptune()) { ?>
                            <td class="checkbox">
                                <label for="ssh_terminal_disable">
                                    <input type="checkbox" name="ssh_terminal_disable" id="ssh_terminal_disable" value="1" <?php echo is_checked($ssh_terminal_disable, 1); ?>>
                                    <?php echo _("Disable the SSH Terminal web portal."); ?>
                                </label>
                            </td>
                        <?php } else { ?>
                            <td>
                                <?php
                                echo neptune_centered_checkbox(_('Disable the SSH Terminal web portal'), "ssh_terminal_disable", "ssh_terminal_disable", $ssh_terminal_disable, 1);
                                ?>
                            </td>
                        <?php } ?>
                    </tr>
                </table>
                <?php if (!is_neptune()) { ?>
                    <h5 class="ul"><?php echo _("ModSecurity"); ?></h5>
                <?php } else {
                    echo neptune_heading(_('ModSecurity'));
                } ?>
                <div class="alert alert-warning">
                    <i class="fa fa-exclamation l"></i> 
                    <b><?php echo _('Warning'); ?>:</b>
                    <?php echo _('ModSecurity Web Application Firewall is in Beta.'); ?>
                    <a href="https://answerhub.nagios.com/support/s/article/Apache-ModSecurity-in-Nagios-XI-2024-b7ebac03" rel="noreferrer nofollow" target="_blank" ><?php echo _("Documentation") ?></a>
                </div>
                <table class="table table-condensed table-no-border table-auto-width">
                    <tr>
                        <td>
                            <label <?php if(is_neptune()) { ?> style="margin-left:0.25rem" <?php } ?> >
                                <?php echo _("ModSecurity"); ?>:
                            </label>
                        </td>
                        <?php if (!is_neptune()) { ?>
                            <td class="checkbox">
                                <label for="modsecurity_enabled">
                                    <input type="checkbox" name="modsecurity_enabled" id="modsecurity_enabled" value="1" <?php echo is_checked($modsecurity_enabled, 1); ?>>
                                    <?php echo _("Enable ModSecurity Web Application Firewall"); ?>
                                </label>
                            </td>
                        <?php } else { ?>
                            <td>
                                <?php
                                echo neptune_centered_checkbox(_('Enable ModSecurity Web Application Firewall'), "modsecurity_enabled", "modsecurity_enabled", $modsecurity_enabled, 1);
                                ?>
                            </td>
                        <?php } ?>
                    </tr>
                </table>
            </div>

            <div id="passwords" class="neptune-admin-config-table neptune-admin-config-table-215">
                <?php if (!is_neptune()) { ?>
                    <h5 class="ul"><?php echo _("Account Locking"); ?></h5>
                <?php } else {
                    echo neptune_heading(_('Account Locking'));
                } ?>
                <table class="table table-condensed table-no-border table-auto-width">
                    <tr>
                        <td>
                            <label>
                                <?php echo _("Enable Account Lockout"); ?>:
                            </label>
                        </td>
                        <?php if (!is_neptune()) { ?>
                            <td class="checkbox">
                                <label>
                                    <input type="checkbox" class="checkbox" name="account_lockout" id="account_lockout" value="1" <?php echo is_checked($account_lockout, 1); ?>>
                                    <?php echo _("After unsuccessful login attempts, a user will be locked out of their account."); ?>
                                </label>
                            </td>
                        <?php } else { ?>
                            <td>
                                <?php
                                echo neptune_centered_checkbox(_('After unsuccessful login attempts, a user will be locked out of their account'), "account_lockout", "account_lockout", $account_lockout, 1);
                                ?>
                            </td>
                        <?php } ?>
                    </tr>
                    <tr>
                        <td class="vt">
                            <label for="account_login_attempts_before_lockout">
                                <?php echo _("Unsuccessful Login Attempts"); ?>:
                            </label>
                        </td>
                        <td>
                            <input type="text" name="account_login_attempts_before_lockout" id="account_login_attempts_before_lockout" value="<?php echo $account_login_attempts_before_lockout; ?>" class="form-control" style="width: 55px;">
                            <div class="subtext neptune-form-subtext neptune-form-spacer">
                                <?php echo _("Accounts will be locked after this many unsuccessful attempts to login."); ?>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="vt">
                            <label for="account_lockout_period">
                                <?php echo _("Lockout Period"); ?>:
                            </label>
                        </td>
                        <td>
                            <input type="text" name="account_lockout_period" id="account_lockout_period" value="<?php echo $account_lockout_period; ?>" class="form-control" style="width: 55px;">
                            <div class="subtext neptune-form-subtext neptune-form-spacer">
                                <?php echo _("Accounts will be unavailable for this period of time after a lockout (in seconds)."); ?><br />
                                <b><?php echo _("Enter 0 to require accounts to be unlocked by an Administrator."); ?><b>
                            </div>
                        </td>
                    </tr>
                </table>

                <?php if (!is_neptune()) { ?>
                    <h5 class="ul"><?php echo _("Local Password Requirements"); ?></h5>
                <?php } else {
                    echo neptune_heading(_('Local Password Requirements'));
                } ?>
                <table class="table table-condensed table-no-border table-auto-width">
                    <tr>
                        <td>
                            <label>
                                <?php echo _("Disallow Old Passwords"); ?>:
                            </label>
                        </td>
                        <?php if (!is_neptune()) { ?>
                            <td class="checkbox">
                                <label>
                                    <input type="checkbox" name="pw_check_old_passwords" id="pw_check_old_passwords" value="1" <?php echo is_checked($pw_check_old_passwords, 1); ?>>
                                    <?php echo _("Do not allow users to re-use old passwords when changing passwords."); ?>
                                </label>
                            </td>
                        <?php } else { ?>
                            <td>
                                <?php
                                echo neptune_centered_checkbox(_('Do not allow users to re-use old passwords when changing passwords'), "pw_check_old_passwords", "pw_check_old_passwords", $pw_check_old_passwords, 1);
                                ?>
                            </td>
                        <?php } ?>
                    </tr>
                    <tr>
                        <td>
                            <label>
                                <?php echo _("Enforce Requirements"); ?>:
                            </label>
                        </td>
                        <?php if (!is_neptune()) { ?>
                            <td class="checkbox">
                                <label>
                                    <input type="checkbox" name="pw_enforce_requirements" id="pw_enforce_requirements" value="1" <?php echo is_checked($pw_enforce_requirements, 1); ?>>
                                    <?php echo _("Enforce specified requirements."); ?>
                                </label>
                            </td>
                        <?php } else { ?>
                            <td>
                                <?php
                                echo neptune_centered_checkbox(_('Enforce specified requirements'), "pw_enforce_requirements", "pw_enforce_requirements", $pw_enforce_requirements, 1);
                                ?>
                            </td>
                        <?php } ?>
                    </tr>
                    <tr>
                        <td class="vt">
                            <label for="pw_max_age">
                                <?php echo _("Maximum Password Age"); ?>:
                            </label>
                        </td>
                        <td>
                            <input type="text" name="pw_max_age" id="pw_max_age" value="<?php echo $pw['max_age']; ?>" class="form-control" style="width: 55px;">
                            <div class="subtext neptune-form-subtext neptune-form-spacer">
                                <?php echo _("Passwords are required to be reset after they've been in use for these many days."); ?><br />
                                <b><?php echo _("Enter 0 for unlimited password age."); ?><b>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="vt">
                            <label for="pw_min_length">
                                <?php echo _("Minimum Password Length"); ?>:
                            </label>
                        </td>
                        <td>
                            <input type="text" name="pw_min_length" id="pw_min_length" value="<?php echo $pw['min_length']; ?>" class="form-control" style="width: 55px;">
                            <div class="subtext neptune-form-subtext neptune-form-spacer">
                                <?php echo _("Passwords are required to have at least this many characters."); ?>
                            </div>
                        </td>
                    </tr>
                </table>

                <h5 class="ul complexity"><?php echo _("Password Complexity"); ?></h5>
                <table class="table table-condensed table-no-border table-auto-width complexity">
                    <tr>
                        <td>
                            <label>
                                <?php echo _("Enforce Complexity Requirements"); ?>:
                            </label>
                        </td>
                        <?php if (!is_neptune()) { ?>
                            <td class="checkbox">
                                <label>
                                    <input type="checkbox" name="pw_enforce_complexity" id="pw_enforce_complexity" value="1" <?php echo is_checked($pw['enforce_complexity'], 1); ?>>
                                    <?php echo _("Enforce specified complexity requirements."); ?>
                                </label>
                            </td>
                        <?php } else { ?>
                            <td>
                                <?php
                                echo neptune_centered_checkbox(_('Enforce specified complexity requirements'), "pw_enforce_complexity", "pw_enforce_complexity", $pw['enforce_complexity'], 1);
                                ?>
                            </td>
                        <?php } ?>
                    </tr>
                    <tr>
                        <td class="vt">
                            <label for="pw_complex_upper">
                                <?php echo _("Minimum Uppercase Characters"); ?>:
                            </label>
                        </td>
                        <td>
                            <input type="text" name="pw_complex_upper" id="pw_complex_upper" value="<?php echo $pw['complex_upper']; ?>" class="form-control" style="width: 55px;">
                        </td>
                    </tr>
                    <tr>
                        <td class="vt">
                            <label for="pw_complex_lower">
                                <?php echo _("Minimum Lowercase Characters"); ?>:
                            </label>
                        </td>
                        <td>
                            <input type="text" name="pw_complex_lower" id="pw_complex_lower" value="<?php echo $pw['complex_lower']; ?>" class="form-control" style="width: 55px;">
                        </td>
                    </tr>
                    <tr>
                        <td class="vt">
                            <label for="pw_complex_numeric">
                                <?php echo _("Minimum Numeric Characters"); ?>:
                            </label>
                        </td>
                        <td>
                            <input type="text" name="pw_complex_numeric" id="pw_complex_numeric" value="<?php echo $pw['complex_numeric']; ?>" class="form-control" style="width: 55px;">
                        </td>
                    </tr>
                    <tr>
                        <td class="vt">
                            <label for="pw_complex_special">
                                <?php echo _("Minimum Special Characters"); ?>:
                            </label>
                        </td>
                        <td>
                            <input type="text" name="pw_complex_special" id="pw_complex_special" value="<?php echo $pw['complex_special']; ?>" class="form-control" style="width: 55px;">
                        </td>
                    </tr>
                </table>
            </div>

            <div id="display" class="neptune-admin-config-table neptune-admin-config-table-215">
                <?php if (!is_neptune()) { ?>
                    <h5 class="ul"><?php echo _("Theme Settings"); ?></h5>
                <?php } else {
                    echo neptune_heading(_('Theme Settings'));
                } ?>
                <table class="table table-condensed table-no-border table-auto-width">
                    <tr>
                        <td><label><?php echo _("User Interface Theme"); ?>:</label></td>
                        <td>
                            <select id="theme" name="theme" class="form-control">
                                <option value="xi5"<?php if ($theme == 'xi5') { echo " selected"; } ?>><?php echo _("Modern"); ?></option>
                                <option value="xi5dark"<?php if ($theme == 'xi5dark') { echo " selected"; } ?>><?php echo _("Modern Dark"); ?></option>
                                <option value="neptune"<?php if ($theme == 'neptune') { echo " selected"; } ?>><?php echo _("Neptune"); ?></option>
                                <option value="neptunelight"<?php if ($theme == 'neptunelight') { echo " selected"; } ?>><?php echo _("Neptune Light"); ?></option>
                                <option value="xi2014"<?php if ($theme == 'xi2014') { echo " selected"; } ?>><?php echo _("2014"); ?></option>
                                <option value="classic"<?php if ($theme == 'classic') { echo " selected"; } ?>><?php echo _("Classic"); ?></option>
                                <option value="colorblind"<?php if ($theme == 'colorblind') { echo " selected"; } ?>><?php echo _("Color Correction (Protanopia + Deuteranopia)"); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php if (!is_neptune()) { ?>
                    <h5 class="ul"><?php echo _("Display Settings (Highcharts)"); ?></h5>
                <?php } else {
                    echo neptune_heading(_('Display Settings (Highcharts)'));
                } ?>
                <table class="table table-condensed table-no-border table-auto-width">
                    <tr>
                        <td><label><?php echo _("Highcharts Color Theme"); ?>:</label></td>
                        <td>
                            <select id="hc_theme" name="hc_theme" class="form-control" style="max-width:200px;">
                                <option value="default"<?php if ($hc_theme == 'default') { echo " selected"; } ?>><?php echo _("Default (White)"); ?></option>
                                <option value="gray"<?php if ($hc_theme == 'gray') { echo " selected"; } ?>><?php echo _("Dark Gray"); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td></td>
                        <?php if (!is_neptune()) { ?>
                            <td class="checkbox">
                                <label class="checkbox">
                                    <input type="checkbox" class="checkbox" id="perfdataThemeCheckBox" name="perfdata_theme" value="1" <?php echo is_checked($perfdata_theme, 1); ?>>
                                    <?php echo _("Use Highcharts for Performance Graphs page and host/service detail pages (host/service popup graph always uses Highcharts)"); ?>
                                </label>
                            </td>
                        <?php } else { ?>
                            <td>
                                <div class="centered-checkbox">
                                    <input type="checkbox" class="" id="perfdataThemeCheckBox" name="perfdata_theme" value="1" <?php echo is_checked($perfdata_theme, 1); ?> <?php echo (false ? 'disabled' : ''); ?>> 
                                    <label for="perfdataThemeCheckBox" style="max-width: 500px"><?php echo _("Use Highcharts for Performance Graphs page and host/service detail pages (host/service popup graph always uses Highcharts)") ?> </label>
                                </div>
                            </td>
                        <?php } ?>
                    </tr>
                    <tr>
                        <td><label><?php echo _("Scale for Graphs"); ?>:</label></td>
                        <td>
                            <select id="highchart_scale" name="highchart_scale" class="form-control" style="max-width:200px;">
                                <option value="linear"<?php if ($highchart_scale == 'linear') { echo " selected"; } ?>><?php echo _("Linear"); ?></option>
                                <option value="logarithmic"<?php if ($highchart_scale == 'logarithmic') { echo " selected"; } ?>><?php echo _("Logarithmic"); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><label><?php echo _("Default Type for Graphs"); ?>:</label></td>
                        <td>
                            <select id="highcharts_default_type" name="highcharts_default_type" class="form-control" style="max-width:200px;">
                                <option value="stacked"<?php if ($highcharts_default_type == "stacked") { echo " selected"; } ?>><?php echo _("Area (Stacked)"); ?></option>
                                <option value="area"<?php if ($highcharts_default_type == "area") { echo " selected"; } ?>><?php echo _("Area"); ?></option>
                                <option value="line"<?php if ($highcharts_default_type == "line") { echo " selected"; } ?>><?php echo _("Line"); ?></option>
                                <option value="spline"<?php if ($highcharts_default_type == "spline") { echo " selected"; } ?>><?php echo _("Spline"); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php if (!is_neptune()) { ?>
                    <h5 class="ul"><?php echo _("Data Settings (Highcharts)"); ?></h5>
                <?php } else {
                    echo neptune_heading(_('Data Settings (Highcharts)'));
                } ?>
                <table class="table table-condensed table-no-border table-auto-width">
                    <tr>
                        <td><label><?php echo _("Show in Legend"); ?>:</label></td>
                        <td>
                            <?php if (!is_neptune()) { ?>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="hc_show_rrd_stats[]" <?php if (in_array('last', $hc_show_rrd_stats)) { echo 'checked'; } ?> value="last"> <?php echo _('Last value'); ?> (Last)
                                    </label>
                                </div>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="hc_show_rrd_stats[]" <?php if (in_array('avg', $hc_show_rrd_stats)) { echo 'checked'; } ?> value="avg"> <?php echo _('Average'); ?> (Avg)
                                    </label>
                                </div>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="hc_show_rrd_stats[]" <?php if (in_array('max', $hc_show_rrd_stats)) { echo 'checked'; } ?> value="max"> <?php echo _('Maximum'); ?> (Max)
                                    </label>
                                </div>
                            <?php } else { ?>
                                <?php
                                $hc_show_rrd_stats_last = in_array('last', $hc_show_rrd_stats) ? "last" : "";
                                $hc_show_rrd_stats_avg = in_array('avg', $hc_show_rrd_stats) ? "avg" : "";
                                $hc_show_rrd_stats_max = in_array('max', $hc_show_rrd_stats) ? "max" : "";
                                echo neptune_centered_checkbox(_('Last value') . ' (Last)', "hc_show_rrd_stats_last", "hc_show_rrd_stats[]", $hc_show_rrd_stats_last, "last");
                                echo neptune_centered_checkbox(_('Average') . ' (Avg)', "hc_show_rrd_stats_avg", "hc_show_rrd_stats[]", $hc_show_rrd_stats_avg, "avg");
                                echo neptune_centered_checkbox(_('Maximum') . ' (Max)', "hc_show_rrd_stats_max", "hc_show_rrd_stats[]", $hc_show_rrd_stats_max, "max");
                                ?>
                            <?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <td><label><?php echo _("Calculation"); ?>:</label></td>
                        <td>
                            <?php if (!is_neptune()) { ?>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="hc_ignore_null" <?php echo is_checked($hc_ignore_null, 1); ?> value="1"> <?php echo _('Ignore null values when calculating Avg/Max/Last values'); ?>
                                    </label>
                                </div>
                            <?php } else {
                                echo neptune_centered_checkbox(_('Ignore null values when calculating Avg/Max/Last values'), "hc_ignore_null", "hc_ignore_null", $hc_ignore_null, 1);
                            } ?>
                        </td>
                    </tr>
                </table>
                <?php if (!is_neptune()) { ?>
                    <h5 class="ul"><?php echo _("Warning/Critical Line Display Settings (Highcharts)"); ?></h5>
                <?php } else {
                    echo neptune_heading(_('Warning/Critical Line Display Settings (Highcharts)'));
                } ?>
                <table class="table table-condensed table-no-border table-auto-width">
                    <tr>
                        <td class="vt"><label><?php echo _("Display Graph Buttons"); ?>:</label></td>
                        <td>
                            <select id="wc_enable" name="wc_enable" class="form-control" style="max-width: 70px">
                                <option value="1"<?php if ($wc_enable == 1) { echo " selected"; } ?>><?php echo _("On"); ?></option>
                                <option value="0"<?php if ($wc_enable == 0) { echo " selected"; } ?>><?php echo _("Off"); ?></option>
                            </select>
                            <p class="subtext neptune-form-subtext neptune-form-spacer"><?php echo _("This will disable the warning and critical line buttons from being displayed in any graphs."); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <td class="vt"><label><?php echo _("Auto Display"); ?>:</label></td>
                        <td>
                            <select id="wc_display" name="wc_display" class="form-control" style="max-width: 70px">
                                <option value="0"<?php if ($wc_display == 0) { echo " selected"; } ?>><?php echo _("Off"); ?></option>
                                <option value="1"<?php if ($wc_display == 1) { echo " selected"; } ?>><?php echo _("On"); ?></option>
                            </select>
                            <p class="subtext neptune-form-subtext neptune-form-spacer"><?php echo _("This will display the warning and critical lines when an eligible graph is loaded and drawn."); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <div id="defaults" class="neptune-admin-config-table neptune-admin-config-table-215">
                <?php if (!is_neptune()) { ?>
                    <h5 class="ul"><?php echo _("General User Settings"); ?></h5>
                <?php } else {
                    echo neptune_heading(_('General User Settings'));
                } ?>
                <table class="table table-condensed table-no-border table-auto-width">
                    <tr>
                        <td>
                            <label><?php echo _("Disable Renewal Reminder"); ?>:</label>
                        </td>
                        <?php if (!is_neptune()) { ?>
                            <td class="checkbox">
                                <label for="disable_renewal_reminder">
                                    <input type="checkbox" class="checkbox" name="disable_renewal_reminder" id="disable_renewal_reminder" value="1" <?php echo is_checked($disable_renewal_reminder, 1); ?>>
                                    <?php echo _("Disable the maintenance renewal reminder popup messages for non-admin users."); ?>
                                </label>
                            </td>
                        <?php } else { ?>
                            <td>
                                <?php
                                echo neptune_centered_checkbox(_('Disable the maintenance renewal reminder popup messages for non-admin users'), "disable_renewal_reminder", "disable_renewal_reminder", $disable_renewal_reminder, 1);
                                ?>
                            </td>
                        <?php } ?>
                    </tr>
                </table>
                <?php if (!is_neptune()) { ?>
                    <h5 class="ul"><?php echo _("Default User Settings"); ?></h5>
                <?php } else {
                    echo neptune_heading(_('Default User Settings'));
                } ?>
                <table class="table table-condensed table-no-border table-auto-width">
                    <tr>
                        <td>
                            <label for="defaultLanguage"><?php echo _("Language"); ?>:</label>
                        </td>
                        <td>
                            <select name="defaultLanguage" class="languageList dropdown form-control">
                                <?php
                                foreach ($languages as $lang => $title) {
                                    echo '<option value="' . $lang . '" ' . is_selected($language, $lang) . '>' . get_language_nicename($title) . '</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="defaultDateFormat"><?php echo _("Date Format"); ?>:</label>
                        </td>
                        <td>
                            <select name="defaultDateFormat" class="dateformatList dropdown form-control">
                                <?php
                                foreach ($date_formats as $id => $txt) {
                                    echo '<option value="' . $id . '" ' . is_selected($id, $date_format) . '>' . $txt . '</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="defaultNumberFormat"><?php echo _("Number Format"); ?>:</label>
                        </td>
                        <td>
                            <select name="defaultNumberFormat" class="numberformatList dropdown form-control">
                                <?php
                                foreach ($number_formats as $id => $txt) {
                                    echo '<option value="' . $id . '" ' . is_selected($id, $number_format) . '>' . $txt . '</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="defaultWeekFormat"><?php echo _("Week Format"); ?>:</label>
                        </td>
                        <td>
                            <select name="defaultWeekFormat" class="weekFormatList dropdown form-control">
                                <?php
                                foreach ($week_formats as $id => $txt) {
                                    echo '<option value="' . $id . '" ' . is_selected($id, $week_format) . '>' . $txt . '</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php if (!is_neptune()) { ?>
                    <h5 class="ul"><?php echo _("New User Account Information Email"); ?></h5>
                    <p><?php echo _("Update the template for email that goes out when <em>Email User Account Information</em> is checked (default) when adding new users."); ?><br><?php echo _("Available macros for this template: %product%, %username%, %password%, %url%"); ?></p>
                <?php } else {
                    echo neptune_heading(_('New User Account Information Email'));
                    echo neptune_subtitle(_('Update the template for email that goes out when <em>Email User Account Information</em> is checked (default) when adding new users.'));
                    echo neptune_section_spacer();
                } ?>
                <table class="table table-condensed table-no-border">
                    <tr>
                        <td style="width: 80px;"><label><?php echo _('Subject'); ?>:</label></td>
                        <td><input type="text" class="form-control" name="user_new_account_email_subject" style="width: 300px;" value="<?php echo encode_form_val($user_new_account_email_subject); ?>"></td>
                    </tr>
                    <tr>
                        <td><label><?php echo _('Body Text'); ?>:</label></td>
                        <td>
                            <textarea name="user_new_account_email_body" style="width: 50%; min-width: 400px; height: 120px;" class="form-control"><?php echo encode_form_val($user_new_account_email_body); ?></textarea>
                        </td>
                    </tr>
                </table>
            </div>

            <div id="integration" class="neptune-admin-config-table">
                <?php if (!is_neptune()) { ?>
                    <h5 class="ul"><?php echo _("Nagios Fusion Integration"); ?></h5>
                <?php } else {
                    echo neptune_heading(_('Nagios Fusion Integration'));
                } ?>
                <p class="neptune-subtext"><?php echo sprintf(_('The fuse key below should be given to your Nagios Fusion 4 server only. The key allows a Fusion 4 instance to connect and integrate with this %s system.'), get_product_name()); ?></p>
                <?php if (is_neptune()) { echo neptune_section_spacer(); } ?>
                <table class="table table-condensed table-no-border table-auto-width">
                    <tr>
                        <td><label><?php echo _('Fuse Key'); ?>:</label></td>
                        <td><input type="text" name="fusekey" value="<?php echo encode_form_val($fusekey); ?>" class="form-control api-key-readonly" style="width: 300px;" readonly></td>
                    </tr>
                </table>
            </div>

            <div id="backwards" class="neptune-admin-config-table">
                <?php if (!is_neptune()) { ?>
                    <h5 class="ul"><?php echo _("Backend Login Settings"); ?></h5>
                <?php } else {
                    echo neptune_heading(_('Backend Login Settings'));
                } ?>
                <table class="table table-condensed table-no-border table-auto-width">
                    <tr>
                    <?php if (!is_neptune()) { ?>
                        <td>
                            <label style="white-space: nowrap">
                                <?php echo _("Allow Insecure Logins"); ?>:
                            </label>
                        </td>
                        <td class="checkbox">
                            <label style="line-height: 1.5em;" for="insecure_login">
                                <input type="checkbox" style="margin-top: 10px;" name="insecure_login" id="insecure_login" value="1" <?php echo is_checked($insecure_login, 1); ?>>
                                <?php echo _("Allow users to use a backend ticket (and username) in a URL to log into the interface (old way of backend ticket auth). Each user has their own ticket that they can set in their profile section, much like the API key.<br>This option is <b>insecure</b> because it passes the ticket (a hash) as your password without encrypting. To be more secure, you should upgrade to single-auth tokens over SSL if possible."); ?>
                            </label>
                        </td>
                    <?php } else { ?>
                        <?php
                        echo neptune_subtitle(_("Allow users to use a backend ticket (and username) in a URL to log into the interface (old way of backend ticket auth). Each user has their own ticket that they can set in their profile section, much like the API key. This option is <b>insecure</b> because it passes the ticket (a hash) as your password without encrypting. To be more secure, you should upgrade to single-auth tokens over SSL if possible."));
                        ?>
                    </tr>
                    <tr>
                        <td>
                            <label style="white-space: nowrap">
                                <?php echo _("Allow Insecure Logins"); ?>:
                            </label>
                        </td>
                        <td>
                            <?php
                            echo neptune_centered_checkbox('', "insecure_login", "insecure_login", $insecure_login, 1);
                            ?>
                        </td>
                    <?php } ?>
                    </tr>
                </table>
            </div>

        </div>
        
        <div id="formButtons">
            <input type="submit" class="submitbutton btn btn-sm btn-primary" name="updateButton" value="<?php echo _("Update Settings"); ?>" id="updateButton">
            <input type="submit" class="submitbutton btn btn-sm btn-default" name="cancelButton" value="<?php echo _("Cancel"); ?>" id="cancelButton">
        </div>

    </form>
    <?php
    do_page_end(true);
}


// Save the options we just set with the above form
function do_update_options()
{
    global $request;

    // User pressed the cancel button
    if (isset($request["cancelButton"])) {
        header("Location: main.php");
		return;
    }

    // Check session
    check_nagios_session_protector();

    $errmsg = array();
    $errors = 0;

    // Get values
    $auto_update_check = grab_request_var("auto_update_check", "");
    $auto_update_check = ((have_value($auto_update_check)) ? 1 : 0);
    $url = grab_request_var("url", "");
    $external_url = grab_request_var("external_url", "");
    $date_format = grab_request_var("defaultDateFormat", DF_ISO8601);
    $number_format = grab_request_var("defaultNumberFormat", NF_2);
    $week_format = grab_request_var("defaultWeekFormat", WF_US);
    $language = grab_request_var("defaultLanguage", "");
    $allow_status_html = grab_request_var('allow_status_html', false);
    $allow_comment_html = grab_request_var('allow_comment_html', false);
    $disable_renewal_reminder = intval(grab_request_var('disable_renewal_reminder', 0));
    $ccm_manage_mrtg = intval(grab_request_var('ccm_manage_mrtg', 0));

    // Theme settings for 2014
    $theme = grab_request_var("theme", "");
    $hc_theme = grab_request_var("hc_theme", "");
    $highchart_scale = grab_request_var("highchart_scale", "");
    $highcharts_default_type = grab_request_var("highcharts_default_type", "line");
    $perfdata_theme = grab_request_var("perfdata_theme", 0);
    $wc_enable = grab_request_var("wc_enable", 1);
    $wc_display = grab_request_var("wc_display", 0);

    $hc_ignore_null = grab_request_var('hc_ignore_null', 0);
    $hc_show_rrd_stats = grab_request_var('hc_show_rrd_stats', array());

    $frame_options_norestrict = grab_request_var('frame_options_norestrict', 0);
    $frame_options_allowed_hosts = grab_request_var('frame_options_allowed_hosts', '');
    $frame_src_norestrict = grab_request_var('frame_src_norestrict', 0);
    $frame_src_allowed_hosts = grab_request_var('frame_src_allowed_hosts', '');
    $curl_ssl_version = grab_request_var('curl_ssl_version', 0);
    $curl_force_verifypeer = grab_request_var('curl_force_verifypeer', 0);

    $verify_host_header = grab_request_var('verify_host_header', 0);

    $cookie_timeout_mins = grab_request_var('cookie_timeout_mins', 30);
    $cookie_auto_refresh = grab_request_var('cookie_auto_refresh', 0);

    $ssh_terminal_disable = grab_request_var('ssh_terminal_disable', 0);
    // If the SSH Terminal is disabled - shut off the service as well
    // Otherwise make sure the service is running
    if ($ssh_terminal_disable == 1) {
        // Stop the service
        submit_command(COMMAND_STOP_SHELLINABOX);
        submit_command(COMMAND_DISABLE_SHELLINABOX);
    } else {
        // Start the serivce
        submit_command(COMMAND_START_SHELLINABOX);
        submit_command(COMMAND_ENABLE_SHELLINABOX);
    }

    // ModSecurity
    $modsecurity_enabled = grab_request_var('modsecurity_enabled', 0);
    if ($modsecurity_enabled == 1) {
        submit_command(COMMAND_ENABLE_MOD_SECURITY);
    } else {
        submit_command(COMMAND_DISABLE_MOD_SECURITY);
    }

    // Get the timezone
    $new_timezone = grab_request_var("timezone", "");

    // Acknowledgement defaults
    $adefault_sticky_acknowledgment = grab_request_var('adefault_sticky_acknowledgment', 0);
    $adefault_send_notification = grab_request_var('adefault_send_notification', 0);
    $adefault_persistent_comment = grab_request_var('adefault_persistent_comment', 0);

    // Sensitive field autocomplete
    $sensitive_field_autocomplete = grab_request_var('sensitive_field_autocomplete', 0);
    $reports_exporting = grab_request_var('reports_exporting', 0);

    // Passwords and Accounts
    $account_lockout = intval(grab_request_var('account_lockout', 0));
    $account_login_attempts_before_lockout = intval(grab_request_var('account_login_attempts_before_lockout', 3));
    $account_lockout_period = intval(grab_request_var('account_lockout_period', 300));
    $pw_check_old_passwords = intval(grab_request_var('pw_check_old_passwords', 0));
    $pw_enforce_requirements = intval(grab_request_var('pw_enforce_requirements', 0));
    $pw_requirements = array(
        'max_age'               => intval(grab_request_var('pw_max_age', 90)),
        'min_length'            => intval(grab_request_var('pw_min_length', 8)),
        'enforce_complexity'    => intval(grab_request_var('pw_enforce_complexity', 0)),
        'complex_upper'         => intval(grab_request_var('pw_complex_upper', 2)),
        'complex_lower'         => intval(grab_request_var('pw_complex_lower', 2)),
        'complex_numeric'       => intval(grab_request_var('pw_complex_numeric', 2)),
        'complex_special'       => intval(grab_request_var('pw_complex_special', 2)),
    );

    // Two factor auth
    $two_factor_auth = grab_request_var('two_factor_auth', 0);
    $two_factor_timeout = grab_request_var('two_factor_timeout', 15);
    $two_factor_cookie = grab_request_var('two_factor_cookie', 0);
    $two_factor_cookie_timeout = grab_request_var('two_factor_cookie_timeout', 90);

    // Secure rapid response URL
    $secure_rr_url = grab_request_var('secure_rr_url', 0);
    $insecure_login = grab_request_var('insecure_login', 0);
    $rr_valid_link_timeout = grab_request_var('rr_valid_link_timeout', 30);

    // New user email template
    $user_new_account_email_subject = grab_request_var('user_new_account_email_subject', '');
    $user_new_account_email_body = grab_request_var('user_new_account_email_body', '');

    // Make sure we have requirements
    if (in_demo_mode() == true)
        $errmsg[$errors++] = _("Changes are disabled while in demo mode.");
    if (have_value($url) == false)
        $errmsg[$errors++] = _("URL is blank.");
    else if (!valid_url($url))
        $errmsg[$errors++] = _("Invalid URL.");
    if (have_value($language) == false)
        $errmsg[$errors++] = _("Default language not specified.");
    if (!empty($new_timezone) && !is_valid_timezone($new_timezone))
        $errmsg[$errors++] = _("Not a valid timezone.");

    // Handle errors
    if ($errors > 0) {
        flash_message($errmsg[0], FLASH_MSG_ERROR);
        show_options();
        exit();
    }

    if (substr($url, -1) != '/') { $url .= '/'; }
    if (substr($external_url, -1) != '/' && !empty($external_url)) { $external_url .= '/'; }

    // Update options
    set_option("url", $url);
    set_option("external_url", $external_url);
    set_option("default_language", $language);
    set_language($language);
    set_option("auto_update_check", $auto_update_check);
    set_option("default_date_format", $date_format);
    set_option("default_number_format", $number_format);
    set_option("default_week_format", $week_format);
    set_option('allow_status_html', $allow_status_html);
    set_option('allow_comment_html', $allow_comment_html);
    set_option('disable_renewal_reminder', $disable_renewal_reminder);
    set_option('ccm_manage_mrtg', $ccm_manage_mrtg);

    set_option('adefault_sticky_acknowledgment', $adefault_sticky_acknowledgment);
    set_option('adefault_send_notification', $adefault_send_notification);
    set_option('adefault_persistent_comment', $adefault_persistent_comment);

    set_option('sensitive_field_autocomplete', $sensitive_field_autocomplete);
    set_option('reports_exporting', $reports_exporting);
    set_option('account_lockout', $account_lockout);
    set_option('account_login_attempts_before_lockout', $account_login_attempts_before_lockout);
    set_option('account_lockout_period', $account_lockout_period);
    set_option('pw_check_old_passwords', $pw_check_old_passwords);
    set_option('pw_enforce_requirements', $pw_enforce_requirements);
    set_option('pw_requirements', serialize($pw_requirements));

    // Set options for security
    set_option('frame_options_norestrict', $frame_options_norestrict);
    set_option('frame_options_allowed_hosts', $frame_options_allowed_hosts);
    set_option('frame_src_norestrict', $frame_src_norestrict);
    set_option('frame_src_allowed_hosts', $frame_src_allowed_hosts);
    set_option('curl_ssl_version', $curl_ssl_version);
    set_option('curl_force_verifypeer', $curl_force_verifypeer);
    set_option("verify_host_header", $verify_host_header);

    set_option('cookie_timeout_mins', $cookie_timeout_mins);
    set_option('cookie_auto_refresh', $cookie_auto_refresh);

    set_option('two_factor_auth', $two_factor_auth);
    set_option('two_factor_timeout', $two_factor_timeout);
    set_option('two_factor_cookie', $two_factor_cookie);
    set_option('two_factor_cookie_timeout', $two_factor_cookie_timeout);

    set_option('secure_rr_url', $secure_rr_url);
    set_option('insecure_login', $insecure_login);
    set_option('rr_valid_link_timeout', $rr_valid_link_timeout);

    set_option('ssh_terminal_disable', $ssh_terminal_disable);

    set_option('modsecurity_enabled', $modsecurity_enabled);

    // Set theme options for 2014
    $old_theme = get_option('theme', '');
    set_option("theme", $theme);
    set_option("default_highcharts_theme", $hc_theme);
    set_option("highcharts_default_type", $highcharts_default_type);
    set_option("highchart_scale", $highchart_scale);
    set_option("perfdata_theme", $perfdata_theme);
    set_option("wc_enable", $wc_enable);
    set_option("wc_display", $wc_display);

    set_option("hc_ignore_null", $hc_ignore_null);
    set_option("hc_show_rrd_stats", serialize($hc_show_rrd_stats));

    // Set new user email template
    set_option("user_new_account_email_subject", $user_new_account_email_subject);
    set_option("user_new_account_email_body", $user_new_account_email_body);

    // Mark that system settings were configured
    set_option("system_settings_configured", 1);

    // Log it
    send_to_audit_log("User updated global program settings", AUDITLOGTYPE_CHANGE);

    // Set the timezone (so we can update it when page reloads)
    if (is_valid_timezone($new_timezone)) {
        set_option("timezone", $new_timezone);

        // Update the timezone if we need to!
        $current_timezone = get_current_timezone();
        if (!empty($new_timezone) && $current_timezone != $new_timezone) {
            submit_command(COMMAND_CHANGE_TIMEZONE, $new_timezone);
        }
    }

    flash_message(_('System settings updated.'));

    $user_theme = get_user_meta(0, "theme");
    if ($old_theme != $theme && $user_theme == '') {
        $_SESSION['reset_frame'] = 1;
        flash_message(_('System settings updated. (Screen may flash once while UI theme changes)'));
    }

    # Redirect to the page
    header('Location: globalconfig.php');
}
