<?php
/**
 * File: mailsettings.php
 * 
 * Mail settings for Nagios XI sent emails
 * 
 * FUNCTIONS:
 * do_update_settings() - updates the mail settings
 * show_settings()      - shows the mail settings page
 * 
 * 
 * Copyright (c) 2008-2023 Nagios Enterprises, LLC. All rights reserved.
 */

require_once(dirname(__FILE__) . '/../includes/common.inc.php');
require_once(dirname(__FILE__) . '/../includes/components/oauth2/createprovider.php');

// Initialization stuff
pre_init();
init_session();

// Grab GET or POST variables and check pre-reqs
grab_request_vars();
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
        do_update_settings();
    } elseif (isset($request['convertcorecontacts'])){
        convert_core_contacts();
    } else {
        show_settings();
    }
}

/*
* Generates and displays the mail settings page
*/
function show_settings()
{
    global $cfg;

    // Get defaults
    $mailmethod = get_option("mail_method", "");
    if ($mailmethod == "") {
        $mailmethod = "sendmail";
    }

    $fromaddress = get_option("mail_from_address", sprintf("%s <root@localhost>", get_product_name()));

    $smtphost           = get_option("smtp_host", "");
    $smtpport           = get_option("smtp_port", 0);
    $smtpusername       = get_option("smtp_username", "");
    $smtppassword       = get_option("smtp_password", "");
    $smtpsecurity       = get_option("smtp_security", "");
    $smtpoauthname      = get_option("smtp_oauth_name", "");
    $php_sendmail_log   = get_option("php_sendmail_log", 0);
    $php_sendmail_debug = get_option("php_sendmail_debug", 0);

    if ($smtpsecurity == "") {
        $smtpsecurity = "none";
    }

    $xisys = $cfg['root_dir'] . '/var/xi-sys.cfg';
    $ini = parse_ini_file($xisys);
    if ($ini['distro'] == "Debian") {
        $exta = _("To continue, configure with the following command as root (or using sudo) to configure the system").": <code>dpkg-reconfigure exim4-config</code>";
    } else if ($ini['distro'] == "Ubuntu") {
        $exta = _("To continue, configure with the following command as root (or using sudo) to configure the system").": <code>dpkg-reconfigure postfix</code>";
    }

    // Get variables submitted to us
    $mailmethod     = grab_request_var("mailmethod", $mailmethod);
    $fromaddress    = grab_request_var("fromaddress", $fromaddress);
    $smtphost       = grab_request_var("smtphost", $smtphost);
    $smtpport       = grab_request_var("smtpport", $smtpport);
    $smtpusername   = grab_request_var("smtpusername", $smtpusername);
    $smtppassword   = grab_request_var("smtppassword", $smtppassword);
    $smtpsecurity   = grab_request_var("smtpsecurity", $smtpsecurity);
    $smtpoauthname  = grab_request_var("smtpoauthname", $smtpoauthname);

    // Inbound settings
    $mail_inbound_process       = grab_request_var("mail_inbound_process",           get_option("mail_inbound_process", 0));
    $mail_inbound_replyto       = grab_request_var("mail_inbound_replyto",           get_option("mail_inbound_replyto", ""));
    $mail_inbound_process_time  = grab_request_var("mail_inbound_process_time",      get_option("mail_inbound_process_time", 2));
    $mail_inbound_type          = grab_request_var("mail_inbound_type",              get_option("mail_inbound_type", "imap"));
    $mail_inbound_host          = grab_request_var("mail_inbound_host",              get_option("mail_inbound_host", ""));
    $mail_inbound_port          = grab_request_var("mail_inbound_port",              get_option("mail_inbound_port", ""));
    $mail_inbound_user          = grab_request_var("mail_inbound_user",              get_option("mail_inbound_user", ""));
    $mail_inbound_pass          = grab_request_var("mail_inbound_pass", decrypt_data(get_option("mail_inbound_pass", "")));
    $mail_inbound_encryption    = grab_request_var("mail_inbound_encryption",        get_option("mail_inbound_encryption", "none"));
    $mail_inbound_validate      = grab_request_var("mail_inbound_validate",          get_option("mail_inbound_validate", 1));
    $mail_inbound_legacy        = grab_request_var("mail_inbound_legacy",            get_option("mail_inbound_legacy", 0));
    // OAuth inbound not yet finished, so hide it -- TODO: finish OAuth inbound and remove this
    $mail_inbound_legacy        = 1;

    do_page_start(array("page_title" => _('Email Settings')), true);
?>
    <!-- handling Send Method changing -->
    <script type="text/javascript">
        $(document).ready(function() {

            /**************************************************************************
            
                Show/Hide settings based on selections

                Sendmail
                SMTP Basic Auth
                SMTP OAuth
                    // With or without SMTP settings
                MS Graph OAuth

            **************************************************************************/

            $('input:radio[name=mailmethod]').change(function() {
                var method = this.value;
                show_hide_settings(method);
            });

            $('select[name=smtpoauthname]').change(function() {
                var method = $('input:radio[name=mailmethod]:checked').val();
                show_hide_settings(method);
            });

            function show_hide_settings(method) {
                $('.alert-sendmail').hide();

                // hide all settings
                $('.smtp-settings').hide();
                $('.smtpOAuth-settings').hide();
                $('.msGraphOA2-settings').hide();

                $('#phpmailerlogging td label input').prop('disabled', false);
                if('<?php echo $php_sendmail_log; ?>' == ""){
                    $('#phpmailerlogging td label input').prop('checked', false);
                } else {
                    $('#phpmailerlogging td label input').prop('checked', true);
                }

                $('#phpmailerdebugging td label input').prop('disabled', false);
                if('<?php echo $php_sendmail_debug; ?>' == ""){
                    $('#phpmailerdebugging td label input').prop('checked', false);
                } else {
                    $('#phpmailerdebuggingtd label input').prop('checked', true);
                }

                // if(typeof providers === 'undefined' || providers.length == 0){
                //     providers = {};
                // }

                // mailmethod radio selection handling
                switch(method){
                    case 'sendmail':
                        $('.alert-sendmail').show();
                        break;

                    case 'smtp':
                        $('.smtp-settings').show();
                        // show SMTP user and pass in case they were hidden from OAuth
                        $('input[name=smtpusername]').parent().parent().show();
                        $('input[name=smtppassword]').parent().parent().show();

                        // set values to database values
                        $('input[name=smtphost]').val('<?php echo $smtphost; ?>');
                        $('input[name=smtpport]').val('<?php echo $smtpport; ?>');
                        $('input[name=smtpusername]').val('<?php echo $smtpusername; ?>');
                        $('input[name=smtppassword]').val('<?php echo $smtppassword; ?>');
                        break;

                    case 'smtpOAuth':
                        clear_smtp();
                        $('.smtpOAuth-settings').show();

                        ////// WIP custom SMTP OAuth
                        // if(providers.length == 0){
                        //     break;
                        // }
                        // vars for code readability
                        // let oauthconfigname = $('select[name=smtpoauthname]').val();
                        // let providername = providers[oauthconfigname]['provider'];
                        // let accessTemplateName = providers[oauthconfigname]['accessTemplate'];
                        // let accessTemplate = accessTemplates[providername][accessTemplateName];

                        // if(!accessTemplate){
                        //     alert('<?php echo _('No access template found for ');?>'+accessTemplateName+'<?php echo _('. Please reconfigure OAuth.');?>');
                        //     return;
                        // }
                        
                        // // if not default template and SMTP OAuth, populate and show SMTP settings
                        // if(!accessTemplate.hasOwnProperty('.default') || accessTemplate['.default'] != 'true'){
                        //     $('input[name=smtphost]').val(accessTemplate['smtpHost']);
                        //     $('input[name=smtpport]').val(accessTemplate['smtpPort']);
                        //     let smtpsecurity = accessTemplate['smtpSecurity'];
                        //     if(smtpsecurity == 'SMTPS') smtpsecurity = 'ssl';
                        //     if(smtpsecurity == 'STARTTLS') smtpsecurity = 'tls';
                        //     $('input:radio[name=smtpsecurity][value="'+smtpsecurity+'"]').prop('checked', true);
                        //     // hide SMTP user and pass for OAuth
                        //     $('input[name=smtpusername]').parent().parent().hide();
                        //     $('input[name=smtppassword]').parent().parent().hide();
                        //     $('.smtp-settings').show();
                        // }
                        break;

                    case 'msGraphOA2':
                        clear_smtp();
                        $('.msGraphOA2-settings').show();
                        $('#phpmailerlogging td label input').prop('disabled', true);
                        $('#phpmailerlogging td label input').prop('checked', false);
                        break;

                    default:
                        break;  
                }
            }

            // initial load - set the correct settings
            $('input:radio[name=mailmethod][value="<?php echo $mailmethod; ?>"]').prop('checked', true).trigger('change');

            // initialize Azure OAuth2 credentials from /usr/local/nagiosxi/etc/components/oauth2/oauth2mailcredentials.json
            $.post('../includes/components/oauth2/oauth-ajaxhelper.php', {
                nsp:    '<?php echo get_nagios_session_protector_id(); ?>',
                cmd: 'get_azure_smtp_credentials'
            }, function(response){
                response = JSON.parse(response);
                if(response['status'] == 'success'){
                    // autofill the form with email settings
                    $('input[name=oauthclientid]').val(response['credentials']['clientId']);
                    $('input[name=oauthtenantid]').val(response['credentials']['tenantId']);
                    $('input[name=oauthclientsecret]').val(response['credentials']['clientSecret']);

                    // if MS Graph OAuth, test the credentials
                    if('<?php echo $mailmethod; ?>' == 'msGraphOA2' && 
                        $('input[name=oauthclientid]').val() && 
                        $('input[name=oauthtenantid]').val() && 
                        $('input[name=oauthclientsecret]').val()
                    ){
                        test_azure_client_credentials(alert = false, setmailmethod = false);
                    }
                } else {
                    // clear the form
                    $('input[name=oauthclientid]').val('');
                    $('input[name=oauthtenantid]').val('');
                    $('input[name=oauthclientsecret]').val('');
                }
            });

            // if php version below 7.4.0, replace #OAuthRadios html with message and disable update settings button
            <?php
            $phpVersionTooLow = (version_compare(PHP_VERSION, '7.4.0') < 0)? json_encode(true): json_encode(false); //Note: this is used later to disable the update settings button
            ?>
            if(<?php echo $phpVersionTooLow; ?>){
                <?php
                    $osfile = '/etc/os-release';
                    if(file_exists($osfile)){
                        $osfilecontents = file_get_contents($osfile);
                    } else {
                        $osfilecontents = '';
                    }
                    
                    // if Cent/RHEL 7/8, add link to upgrade instructions
                    $upgradePHPLink = '';
                    if (preg_match('/CentOS/', $osfilecontents) || preg_match('/Red Hat/', $osfilecontents)) {
                        if (preg_match('/VERSION_ID="7/', $osfilecontents)) {
                            $upgradePHPLink = '<a href="https://support.nagios.com/kb/article/nagios-xi-upgrading-to-php-7-860.html" target="_blank">'._("Nagios XI -- Upgrade CentOS 7 PHP version").'</a>';
                        } elseif (preg_match('/VERSION_ID="8/', $osfilecontents)) {
                            $upgradePHPLink = '<a href="https://answerhub.nagios.com/support/s/article/PHP-7-2-to-7-4-Upgrade-Instructions-for-CentOS-8-and-CentOS-Stream-8-b42c49aa" target="_blank">'._("Nagios XI -- Upgrade CentOS 8 PHP version").'</a>';
                        }
                    }
                    if($upgradePHPLink){
                        $upgradePHPLink = ' using the following link: ' . $upgradePHPLink;
                    }

                    // PHP version too low display message
                    $message = _('You are running PHP version ').'<strong>'.PHP_VERSION.'</strong>'._(', please upgrade PHP to 7.4+ to use OAuth2.0').$upgradePHPLink.'.';
                    echo "message = '<p class=\"alert alert-info\" style=\"margin: 10px 0;\">$message</p>';";
                ?>
                $('.smtpOAuth-settings').find('table').html(message);
                $('.msGraphOA2-settings').find('table').html(message);
            }
        }); // end document ready

        function clear_smtp(){
            $('input[name=smtphost]').val('');
            $('input[name=smtpport]').val('');
            $('input[name=smtpusername]').val('');
            $('input[name=smtppassword]').val('');
            $('input:radio[name=smtpsecurity][value="none"]').prop('checked', true);
        }
    </script>

    <style>
        table tr td:nth-child(1) { width: 150px; }
    </style>

    <h1><?php echo _('Email Settings'); ?></h1>

    <p class="neptune-subtext"><?php echo sprintf(_('Modify the settings used by your %s system for sending email alerts and informational messages.'), get_product_name()); ?></p>
    <?php if (is_neptune()) { echo neptune_section_spacer(); } ?>
    <p class="btn-row" style="margin: 10px 0;"><a href="testemail.php" class="btn btn-sm btn-info btn-icon-and-text"><i class="material-symbols-outlined md-400 md-middle md-20">send</i> <?php echo _("Send a Test Email"); ?></a></p>
    <script>
        $(document).ready(function() {
            // submit form when test email button is clicked
            $('a[href="testemail.php"]').on('click', function(event) {
                event.preventDefault();
                // Submit the form via AJAX
                $.ajax({
                    url: $('#manageMailSettingsForm').attr('action'),
                    type: 'POST',
                    data: $('#manageMailSettingsForm').serialize(), // Serialize the form data
                    success: function(response) {
                        window.location.href = 'testemail.php';
                    }
                });
            }); // end test email button click

            // enable/disable button based on form change
            const form = $('#manageMailSettingsForm');

            var disabledmailmethods = [];
            // if php version below 7.4.0, disable Update Settings button while on OAuth mail methods
            if(<?php echo $phpVersionTooLow; ?>){
                disabledmailmethods = ['smtpOAuth', 'msGraphOA2'];
            }
            // on form alteration, enable the update settings button if not a disabled mail method (OAuth when PHP < 7.4)
            form.find('input, select').on('change, input', function() {
                if(!disabledmailmethods.includes($('input:radio[name=mailmethod]:checked').val())){
                    $('#updateButton').prop('disabled', false);
                } else {
                    $('#updateButton').prop('disabled', true);
                }
            });

            form.on('reset', function() {
                $('#updateButton').prop('disabled', true);
            });

            $('#manageMailSettingsForm').submit(test_azure_client_credentials);
        }); // end document ready
    </script>


    <form id="manageMailSettingsForm" class="neptune-admin-config-table neptune-admin-config-table-150" method="post">
        <?php echo get_nagios_session_protector(); ?>
        <input type="hidden" name="update" value="1">
        <input type="hidden" value="outbound" id="tab_hash" name="tab_hash">

        <div id="tabs" class="hide">

            <ul>
                <li><div><a href="#outbound"><i class="material-symbols-outlined md-400 md-middle md-18">send</i> <?php echo _('Outbound'); ?></a></div></li>
                <li><a href="#inbound"><i class="material-symbols-outlined md-400 md-middle md-18">inbox</i> <?php echo _('Inbound'); ?></a></li>
            </ul>

            <div id="outbound">
                <?php
                    $notify_host_by_email = nagiosql_get_command_id("notify-host-by-email");
                    $notify_host_by_email_xi = nagiosql_get_command_id("notify-host-by-email-xi");
                    $notify_service_by_email = nagiosql_get_command_id("notify-service-by-email");
                    $notify_service_by_email_xi = nagiosql_get_command_id("notify-service-by-email-xi");

                    $notify_host_by_email_contact = exec_sql_query(DB_NAGIOSQL, "SELECT COUNT(*) FROM tbl_lnkContactToCommandHost WHERE idSlave='$notify_host_by_email';");
                    $notify_service_by_email_contact = exec_sql_query(DB_NAGIOSQL, "SELECT COUNT(*) FROM tbl_lnkContactToCommandService WHERE idSlave='$notify_service_by_email';");
                    $notify_host_by_email_template = exec_sql_query(DB_NAGIOSQL, "SELECT COUNT(*) FROM tbl_lnkContacttemplateToCommandHost WHERE idSlave='$notify_host_by_email';");
                    $notify_service_by_email_template = exec_sql_query(DB_NAGIOSQL, "SELECT COUNT(*) FROM tbl_lnkContacttemplateToCommandService WHERE idSlave='$notify_service_by_email';");

                    foreach(['notify_host_by_email_contact', 'notify_service_by_email_contact', 'notify_host_by_email_template', 'notify_service_by_email_template'] as $command){
                        $$command = intval(str_replace("COUNT(*)", "", $$command));
                    }

                    $total = $notify_host_by_email_contact + $notify_service_by_email_contact + $notify_host_by_email_template + $notify_service_by_email_template;
                    
                    # check if any contacts or their templates are using notify-host-by-email or notify-service-by-email and if so, allow them to convert to notify-host-by-email-xi or notify-service-by-email-xi
                   if   ($total &&
                        ((is_authorized_to_configure_objects() && !is_readonly_user()) || user_can_access_ccm())) {
                ?>
                <div class="alert alert-info" style="margin: 10px 0; display: flex; flex-direction: column; align-items: flex-start;">
                    <p><?php echo _('You have contacts or contact templates using Nagios Core email notifications. You can convert these to use your Nagios XI email configuration by clicking the button below.'); ?></p>
                    <table class="table table-condensed table-no-border table-auto-width" style="background-color: transparent !important; color:inherit; margin-bottom: 12px;">
                        <tr>
                            <th><?php echo _('Legacy Commands:'); ?></th>
                        </tr>
                        <tr>
                            <td style="padding-left: 12px;"><?php echo _('Host Notifications:'); ?></td>
                            <td><?php echo $notify_host_by_email_contact; ?> <?php echo _('contacts'); ?></td>
                            <td><?php echo $notify_host_by_email_template; ?> <?php echo _('contact templates'); ?></td>
                        </tr>
                        <tr>
                            <td style="padding-left: 12px; padding-bottom:12px;"><?php echo _('Service Notifications:'); ?></td>
                            <td style="padding-bottom: 12px;"><?php echo $notify_service_by_email_contact; ?> <?php echo _('contacts'); ?></td>
                            <td style="padding-right: 12px; padding-bottom:12px;"><?php echo $notify_service_by_email_template; ?> <?php echo _('contact templates'); ?></td>
                        </tr>
                    </table>
                    <button type="button" class="btn btn-sm btn-info" id="convertCoreContacts"><?php echo _('Convert Contacts'); ?></button>
                </div>
                <?php } ?>
                <p class="alert alert-info" style="margin: 10px 0;"><strong><?php echo _('Note'); ?>:</strong> <?php echo _('Mail messages may fail to be delivered if your XI server does not have a valid DNS name. For more information, read'); ?> <a href="https://assets.nagios.com/downloads/nagiosxi/docs/Understanding-Email-Sending-In-Nagios-XI.pdf" target="_blank"><?php echo _('Understanding Email Sending in Nagios XI') ?></a>.</p>

                <?php if (!is_neptune()) { ?>
                    <h5 class="ul"><?php echo _("Outbound Mail Settings"); ?></h5>
                <?php } else {
                    echo neptune_section_spacer();
                    echo neptune_heading(_('Outbound Mail Settings'));
                } ?>
                <table class="table table-condensed table-no-border table-auto-width">
                    <tr>
                        <td class="vt">
                            <label><?php echo _('Send From'); ?>:</label>
                        </td>
                        <td>
                            <!-- TODO split this into From Name: and From Address: -->
                            <input name="fromaddress" type="text" class="textfield form-control max-width-460" value="<?php echo encode_form_val($fromaddress); ?>" size="40">
                        </td>
                    </tr>
                    <tr>
                        <td class="vt">
                            <label><?php echo _('Send Method'); ?>:</label>
                        </td>
                        <td>
                            <div class="radio m-0">
                                <label>
                                    <input name="mailmethod" type="radio" value="sendmail" <?php echo is_checked($mailmethod, "sendmail"); ?>><?php echo _("Sendmail"); ?>
                                </label>
                            </div>
                            <div class="radio m-0">
                                <label>
                                    <input name="mailmethod" type="radio" value="smtp" <?php echo is_checked($mailmethod, "smtp"); ?>><?php echo _("SMTP with Basic Auth"); ?>
                                </label>
                            </div>
                            <div id="OAuthRadios">
                                <div class="radio m-0">
                                    <label>
                                        <input name="mailmethod" type="radio" value="smtpOAuth" <?php echo is_checked($mailmethod, "smtpOAuth"); ?>><?php echo _("Gmail with OAuth2"); ?>
                                    </label>
                                </div>
                                <div class="radio m-0">
                                    <label>
                                        <input name="mailmethod" type="radio" value="msGraphOA2" <?php echo is_checked($mailmethod, "msGraphOA2"); ?>><?php echo _("Microsoft with OAuth2"); ?>
                                    </label>
                                </div>
                            </div>
                            <?php if ($ini['distro'] == 'Debian' || $ini['distro'] == 'Ubuntu') { ?>
                            <div class="alert alert-sendmail alert-info" style="margin: 10px 0 0 0; <?php if ($mailmethod == 'smtp') { echo "display: none;"; } ?>">
                                <b><?php echo _('Note'); ?>:</b> <?php echo _('On some systems, sendmail may not be configured to send emails outside of localhost. We highly recommend using SMTP configuration.'); ?><br>
                            </div>
                            <?php } ?>
                        </td>
                    </tr>
                    <tr id='phpmailerlogging'>
                        <td>
                            <label><?php echo _('Logging'); ?>:</label>
                        </td>
                        <?php if (!is_neptune()) { ?>
                            <td class="checkbox">
                                <label>
                                    <input type="checkbox" class="checkbox" id="php_sendmail_log" name="php_sendmail_log" value="1" <?php echo is_checked($php_sendmail_log, 1); ?>><?php echo _('Enable logging of mail sent with the internal mail component (PHPMailer) <b>') . get_tmp_dir() . '/phpmailer.log</b>'; ?>
                                </label>
                            </td>
                        <?php } else { ?>
                            <td>
                                <?php
                                echo neptune_centered_checkbox(_('Enable logging of mail sent with the internal mail component (PHPMailer) <b>') . get_tmp_dir() . '/phpmailer.log</b>', "php_sendmail_log", "php_sendmail_log", $php_sendmail_log, 1);
                                ?>
                            </td>
                        <?php } ?>
                    </tr>
                    <tr id='phpmailerdebugging'>
                        <td>
                            <label><?php echo _('Debugging'); ?>:</label>
                        </td>
                        <?php if (!is_neptune()) { ?>
                            <td class="checkbox">
                                <label>
                                    <input type="checkbox" class="checkbox" id="php_sendmail_debug" name="php_sendmail_debug" value="1" <?php echo is_checked($php_sendmail_debug, 1); ?>><?php echo _('Enable debugging of mail sent with the internal mail component (PHPMailer) - output in PHP error log'); ?>
                                </label>
                            </td>
                        <?php } else { ?>
                            <td>
                                <?php
                                echo neptune_centered_checkbox(_('Enable debugging of mail sent with the internal mail component (PHPMailer) - output in PHP error log'), "php_sendmail_debug", "php_sendmail_debug", $php_sendmail_debug, 1);
                                ?>
                            </td>
                        <?php } ?>
                    </tr>
                </table>

                <div class="smtp-settings">
                    <?php if (!is_neptune()) { ?>
                        <h5 class="ul"><?php echo _("SMTP Settings"); ?></h5>
                    <?php } else {
                        echo neptune_heading(_('SMTP Settings'));
                    } ?>
                    <table class="table table-condensed table-no-border table-auto-width">
                        <tr>
                            <td>
                                <label><?php echo _('Host'); ?>:</label>
                            </td>
                            <?php if (!is_neptune()) { ?>
                                <td>
                                    <input name="smtphost" type="text" class="textfield form-control" value="<?php echo encode_form_val($smtphost); ?>" size="40">
                                    <i class="fa fa-question-circle pop" data-content="<?php echo _('You can set up a failover/backup mail server by using a semi-colon (;) to define multiple SMTP hosts.'); ?><br><br><?php echo _('Example'); ?>:<br>smtp@test.com;smtp2@test.com"></i>
                                </td>
                            <?php } else { ?>
                                <td>
                                    <div class="neptune-form-element">
                                        <div class="neptune-form-element-wrapper input-group" style="max-width: none">
                                            <input type="text" class="textfield form-control textfield-with-btn" id="smtphost" name="smtphost" value="<?= encode_form_val($smtphost); ?>" />
                                            <span class="input-group-btn">
                                                <button type="button" class="btn btn-sm btn-default tt-bind btn-help"  title="<?php echo _('You can set up a failover/backup mail server by using a semi-colon (;) to define multiple SMTP hosts.'); ?> <?php echo _('Example'); ?>:smtp@test.com;smtp2@test.com">
                                                    <i class="material-symbols-outlined md-help md-22">help</i>
                                                </button> 
                                            </span>
                                        </div>
                                    </div>
                                </td>
                            <?php } ?>
                        </tr>
                        <tr>
                            <td>
                                <label><?php echo _('Port'); ?>:</label>
                            </td>
                            <td>
                                <input name="smtpport" type="text" class="textfield form-control" value="<?php echo encode_form_val($smtpport); ?>" size="4">
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label><?php echo _('Username'); ?>:</label>
                            </td>
                            <td>
                                <input name="smtpusername" type="text" class="textfield form-control" value="<?php echo encode_form_val($smtpusername); ?>" size="20">
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label><?php echo _('Password'); ?>:</label>
                            </td>
                            <?php if (!is_neptune()) { ?>
                                <td>
                                    <input name="smtppassword" type="password" class="textfield form-control" value="<?php echo encode_form_val($smtppassword); ?>" size="20" <?php echo sensitive_field_autocomplete(); ?>>
                                    <button type="button" style="vertical-align: top;" class="btn btn-sm btn-default tt-bind btn-show-password" title="<?php echo _("Show password"); ?>"><i class="fa fa-eye"></i></button>
                                </td>
                            <?php } else { ?>
                                <td>
                                    <div class="neptune-form-element">
                                        <div class="neptune-form-element-wrapper input-group" style="max-width: none">
                                            <input type="password" class="form-control" id="smtppassword" name="smtppassword" value="<?php echo encode_form_val($smtppassword); ?>" <?php echo sensitive_field_autocomplete(); ?> />
                                            <span class="input-group-btn">
                                                <button type="button" class="btn btn-sm btn-default tt-bind btn-show-password" title="<?= _('Show') ?>">
                                                    <i class="material-symbols-outlined md-22 md-pointer">Visibility</i>
                                                </button>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                            <?php } ?>
                        </tr>
                        <tr>
                            <td class="vt">
                                <label><?php echo _('Security'); ?>:</label>
                            </td>
                            <td>
                                <div class="radio m-0">
                                    <label>
                                        <input name="smtpsecurity" type="radio" value="none" <?php echo is_checked($smtpsecurity, "none"); ?>><?php echo _("None"); ?>
                                    </label>
                                </div>
                                <div class="radio m-0">
                                    <label>
                                        <input name="smtpsecurity" type="radio" value="tls" <?php echo is_checked($smtpsecurity, "tls"); ?>>TLS
                                    </label>
                                </div>
                                <div class="radio m-0">
                                    <label>
                                        <input name="smtpsecurity" type="radio" value="ssl" <?php echo is_checked($smtpsecurity, "ssl"); ?>>SSL
                                    </label>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="smtpOAuth-settings">
                    <?php if (!is_neptune()) { ?>
                        <h5 class="ul"><?php echo _("Google Cloud OAuth2 Settings"); ?></h5>
                    <?php } else {
                        echo neptune_heading(_('Google Cloud OAuth2 Settings'));
                    } ?>

                    <!-- OLD VERSION for full OAuth customization -- DO NOT DELETE THE PHP SECTION THAT DEFINES $redirectUri/$googleredirectUri -->
                    <!-- <table class='table table-condensed table-no-border table-auto-width'>
                        <tr>
                            <td>
                                <label><?php echo _('OAuth2 Configuration'); ?>:</label>
                            </td>
                            <td>
                                <select id='smtpoauthname' name='smtpoauthname' class="form-control" style="width: 155px; vertical-align: middle;">
                                </select>
                                <button class="btn btn-sm btn-default" id="refreshprovidersbutton" title="refresh providers"><i class="fa fa-refresh"></i></button>
                                <button class="btn btn-sm btn-default" id="deleteproviderbutton" title="delete provider"><i class="fa fa-trash"></i></button>
                                <?php
                                    //set redirectUri to localhost if ip is private
                                    preg_match("/(?<=\/\/).+?(?=\/)/", get_component_url_base("oauth2"), $ip);
                                    if(preg_match("/[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}/",$ip[0],$ip)){ //ip is private, must use https or localhost
                                        if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') { //https, can use private ip (at least with Azure)
                                            $baseUri = get_component_url_base("oauth2");
                                            $googleBaseUri = preg_replace("/(?<=\/\/)[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}?(?=\/)/","localhost",get_component_url_base("oauth2"));
                                        } else { //http, must use localhost
                                            $baseUri = preg_replace("/(?<=\/\/)[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}?(?=\/)/","localhost",get_component_url_base("oauth2"));
                                        }
                                    } else { //ip is not private, can use domain name
                                        $baseUri = get_component_url_base("oauth2");
                                    }
                                    $googleBaseUri = isset($googleBaseUri) ? $googleBaseUri : $baseUri;
                                    $googleredirectUri = $googleBaseUri."/oauth-gmailsimplified-authcodeflow.php";
                                    $redirectUri = $baseUri."/oauth-authcodeflow.php";
                                ?>
                                <a type='button' class='btn btn-sm btn-primary' id='new-oa2' style="display: inline-block; width: 100px;" href="<?php echo $redirectUri; ?>" title="<?php echo _("You must enable HTTPS on your Nagios XI server or be logged in to your XI server via SSH to configure OAuth."); ?>" target="_blank"><?php echo _('New OAuth'); ?></a>
                                <a type='button' class='btn btn-sm btn-primary' id='edit-oa2' style="display: inline-block; width: 100px;" href="<?php echo $redirectUri; ?>?src=mail" title="<?php echo _("You must enable HTTPS on your Nagios XI server or be logged in to your XI server via SSH to configure OAuth."); ?>" target="_blank"><?php echo _('Edit OAuth'); ?></a>
                                <script>
                                    $('#new-oa2').click(function(event){
                                        <?php 
                                        if(!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
                                            if (!in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1', $_SERVER['SERVER_ADDR']))) {
                                                echo "event.preventDefault();";
                                                echo "if (!confirm('"._("You must enable HTTPS on your Nagios XI server or be logged in to your XI server via SSH to configure OAuth. If you are logged in to your XI Server via SSH you can ignore this message.")."'))".
                                                " { return false; } ".
                                                "else { window.open('".$redirectUri."', '_blank'); }";
                                            }
                                        }
                                        ?>
                                    });
                                    $('#edit-oa2').click(function(event){
                                        <?php 
                                        if(!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
                                            if (!in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1', $_SERVER['SERVER_ADDR']))) {
                                                echo "event.preventDefault();";
                                                echo "if (!confirm('"._("You must enable HTTPS on your Nagios XI server or be logged in to your XI server via SSH to configure OAuth. If you are logged in to your XI Server via SSH you can ignore this message.")."'))".
                                                " { return false; } ".
                                                "else { window.open('".$redirectUri."?src=mail', '_blank'); }";
                                            }
                                        }
                                        ?>
                                    });
                                </script>
                            </td>
                        </tr>
                        
                    </table> --><!-- Hidden for simplified GMail setup -->

                    <!-- TODO: add link to setup instructions (BB) -->
                    <!-- add message with link to instructions on setting up OAuth with Gmail -->
                    <p style="margin-bottom: 0px;" class="neptune-subtext">
                        <?php echo _("Please see the <a href='https://answerhub.nagios.com/support/s/article/Setting-up-Gmail-SMTP-with-OAuth-2-0-4ef4e977' target='_blank'>Nagios Knowledgebase</a> for instructions on how to configure Nagios XI to send mail using OAuth2.0 from a Google account."); ?>
                    </p>
                    <?php if (is_neptune()) { echo neptune_section_spacer(); }
                    // load GMail OAuth credentials or set clientId/clientSecret to empty string
                    $GMailOAuthCredentials = new stdClass();
                    $GMailOAuthCredentials->clientId = "";
                    $GMailOAuthCredentials->clientSecret = "";
                    if(file_exists("/usr/local/nagiosxi/etc/components/oauth2/providers/NagiosXIGMailDefault.json")){
                        $GMailOAuthCredentials = json_decode(decrypt_data(file_get_contents("/usr/local/nagiosxi/etc/components/oauth2/providers/NagiosXIGMailDefault.json")));
                    }         
                    ?>
                    <table class='table table-condensed table-no-border table-auto-width'>
                        <tr>
                            <td>
                                <label><?php echo _('Client ID'); ?>:</label>
                            </td>
                            <td>
                                <input name="gmailclientid" type="text" class="textfield form-control" value="<?php echo $GMailOAuthCredentials->clientId; ?>" size="40">
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label><?php echo _('Client Secret'); ?>:</label>
                            </td>
                            <?php if (!is_neptune()) { ?>
                                <td>
                                    <input name="gmailclientsecret" type="password" class="textfield form-control" value="<?php echo $GMailOAuthCredentials->clientSecret; ?>" size="40"><button type='button' style='margin-left: 3px; vertical-align:top;' class='btn btn-sm btn-default tt-bind btn-show-password' title='<?php echo _('Show/Hide password'); ?>'>
                                        <i class='fa fa-eye'></i></button>
                                </td>
                            <?php } else { ?>
                                <td>
                                    <div class="neptune-form-element">
                                        <div class="neptune-form-element-wrapper input-group" style="max-width: none">
                                            <input type="password" class="textfield form-control textfield-with-btn" id="gmailclientsecret" name="gmailclientsecret" value="<?php echo $GMailOAuthCredentials->clientSecret; ?>" />
                                            <span class="input-group-btn">
                                                <button type="button" class="btn btn-sm btn-default tt-bind btn-show-password" title="<?= _('Show'); ?>">
                                                    <i class="material-symbols-outlined md-22 md-pointer">Visibility</i>
                                                </button>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                            <?php } ?>
                        </tr>
                        <tr>
                            <td>
                                <label><?php echo _('Redirect URI'); ?>:</label>
                            </td>
                            <td>
                                <?php if (!is_neptune()) { ?>
                                    <input name="gmailredirecturi" type="text" class="textfield form-control" value="<?php echo $googleredirectUri; ?>" size="40" disabled>
                                    <button class="btn btn-sm btn-default" style='margin-left: -1px; vertical-align:top;' id="copyredirecturi" title="copy redirect uri"><i class="fa fa-copy"></i></button>
                                <?php } else { ?>
                                    <div class="neptune-form-element">
                                        <div class="neptune-form-element-wrapper input-group" style="max-width: none">
                                            <input type="text" class="textfield form-control textfield-with-btn" id="gmailredirecturi" name="gmailredirecturi" value="<?php echo $googleredirectUri; ?>" disabled />
                                            <span class="input-group-btn">
                                                <button type="button" class="btn btn-sm btn-default tt-bind btn-help" title="<?= _('Copy') ?>">
                                                    <i class="material-symbols-outlined md-22 md-pointer">content_copy</i>
                                                </button>
                                            </span>
                                        </div>
                                    </div>
                                <?php } ?>
                            </td>
                        </tr>
                        <tr>
                            <td>
                            </td>
                            <?php if (!is_neptune()) { ?>
                                <td>
                                    <button id='configure-gmailoa2' class='btn btn-sm btn-primary' style="display: inline-block; width: 200px;" title="<?php echo _("You must enable HTTPS on your Nagios XI server or be logged in to your XI server via SSH to configure OAuth."); ?>" target="_blank"><?php echo _('Continue to OAuth verification'); ?></button>
                                </td>
                            <?php } else { ?>
                                <td>
                                    <button id='configure-gmailoa2' class='btn btn-sm btn-primary' title="<?php echo _("You must enable HTTPS on your Nagios XI server or be logged in to your XI server via SSH to configure OAuth."); ?>" target="_blank"><?php echo _('Continue to OAuth verification'); ?></button>
                                </td>
                            <?php } ?>
                        </tr>
                        <tr>
                            <td>
                                <label><?php echo _('Refresh Token status:'); ?></label>
                            </td>
                            <td class="btn-row">
                                <span id="gmailhasrefreshtoken" class="material-symbols-outlined md-400 md-middle md-inherit md-default-cursor md-pending">help</span>
                                <button class="btn btn-sm btn-default" id="gmailcheckrefresh" title="check refresh token"><i class="material-symbols-outlined md-middle md-400 md-18 md-pointer">sync</i></button>
                            </td>
                        <tr>
                    </table>
                    <script>
                        //document ready
                        $(document).ready(function(){
                            //
                            $('#copyredirecturi').click(function(event){
                                event.preventDefault();
                                if (navigator.clipboard && navigator.clipboard.writeText) {
                                    return navigator.clipboard.writeText($(this).prev().val());
                                } else {
                                    $(this).prev().select();
                                    document.execCommand("copy");
                                }
                            });

                            //
                            $('#configure-gmailoa2').click(function(event){
                                event.preventDefault();
                                var clientId     = $('input[name=gmailclientid]').val();
                                var clientSecret = $('input[name=gmailclientsecret]').val();
                            <?php 
                                // remove http(s) from redirect url so we don't get caught by login.php
                                preg_match('/(https?):\/\/(.+)/', $googleredirectUri, $googleredirectGET);
                                // Not using HTTPS or FQDN, warn user and then redirect if they OK
                                if(!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
                                    if (!in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1', $_SERVER['SERVER_ADDR']))) {
                                        echo "if (!confirm('"._("You must enable HTTPS on your Nagios XI server or be logged in to your XI server via SSH to configure OAuth. If you are logged in to your XI Server via SSH you can ignore this message.")."'))".
                                        " { return false; } ".
                                        "else { 
                                            window.open('".$googleredirectUri."?clientId='+clientId+'&clientSecret='+clientSecret+'&https=".$googleredirectGET[1]."&redirectUri=".$googleredirectGET[2]."', '_blank'); 
                                        }";
                                    } else {
                                        echo "window.open('".$googleredirectUri."?clientId='+clientId+'&clientSecret='+clientSecret+'&https=".$googleredirectGET[1]."&redirectUri=".$googleredirectGET[2]."', '_blank');";
                                    }
                                } else {
                                    echo "window.open('".$googleredirectUri."?clientId='+clientId+'&clientSecret='+clientSecret+'&https=".$googleredirectGET[1]."&redirectUri=".$googleredirectGET[2]."', '_blank');";
                                }
                                ?>
                            });

                            // check refresh token status
                            $('#gmailcheckrefresh').click(function(event){
                                event.preventDefault();
                                gmail_check_refresh_token();
                            });
                        });

                        /**
                         * AJAX call to check if refresh token exists
                         * 
                         * sets the image to either a green checkmark or a red !
                         */
                        async function gmail_check_refresh_token(){
                            let googleIcon = $('#gmailhasrefreshtoken');
                            googleIcon.html('more_horiz').attr('title', '<?php echo _("checking..."); ?>');
                            googleIcon.removeClass('md-ok');
                            googleIcon.removeClass('md-critical');
                            googleIcon.addClass('md-pending');
                            $.ajax({
                                url: '../includes/components/oauth2/oauth-ajaxhelper.php',
                                type: 'POST',
                                data: {
                                    nsp: '<?php echo get_nagios_session_protector_id(); ?>',
                                    cmd: 'get_specific_provider',
                                    // cmd: 'get_providers',
                                    provider: 'NagiosXIGMailDefault'
                                },
                                success: function(data){
                                    data = JSON.parse(data);
                                    if(data['status'] == 'success'){
                                        if(data['provider']['refreshToken'] != ''){
                                            googleIcon.removeClass('md-pending');
                                            googleIcon.html('check_circle').addClass('md-ok').attr('title', '<?php echo _("Refresh token found"); ?>');

                                        } else {
                                            googleIcon.removeClass('md-pending');
                                            googleIcon.html('error').addClass('md-critical').attr('title', '<?php echo _("Refresh token not found"); ?>');

                                        }
                                    } else {
                                        googleIcon.removeClass('md-pending');
                                        googleIcon.html('error').addClass('md-critical').attr('title', data['message']);                                           
                                    }
                                }
                            });
                        }
                    </script>
                </div>

                <div class="msGraphOA2-settings">
                    <!-- TODO: add link to setup instructions (BB) -->
                    <?php if (!is_neptune()) { ?>
                        <h5 class="ul"><?php echo _("Azure OAuth2 Credentials"); ?></h5>
                    <?php } else {
                        echo neptune_heading(_('Azure OAuth2 Credentials'));
                    } ?>
                    <p style="margin-bottom: 0px;" class="neptune-subtext">
                        <?php echo _("Please see the <a href='https://answerhub.nagios.com/support/s/article/Setting-up-Microsoft-SMTP-with-OAuth-2-0-4d263f26' target='_blank'>Nagios Knowledgebase</a> for instructions on how to configure Nagios XI to send mail using OAuth2.0 from a Microsoft account."); ?>
                    </p>
                    <?php if (is_neptune()) { echo neptune_section_spacer(); } ?>
                    <table class='table table-condensed table-no-border table-auto-width'>
                        <tr>
                            <td>
                                <label><?php echo _('Client ID'); ?>:</label>
                            </td>
                            <td>
                                <input name="oauthclientid" type="text" class="textfield form-control" value="" size="40">
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label><?php echo _('Tenant ID'); ?>:</label>
                            </td>
                            <td>
                                <input name="oauthtenantid" type="text" class="textfield form-control" value="" size="40">
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label><?php echo _('Client Secret'); ?>:</label>
                            </td>
                            <?php if (!is_neptune()) { ?>
                                <td>
                                    <input name="oauthclientsecret" type="password" class="textfield form-control" value="" size="40"><button type='button' style='margin-left: 3px; vertical-align:top;' class='btn btn-sm btn-default tt-bind btn-show-password' title='<?php echo _('Show/Hide password'); ?>'>
                                        <i class='fa fa-eye'></i></button>
                                </td>
                            <?php } else { ?>
                                <td>
                                    <div class="neptune-form-element">
                                        <div class="neptune-form-element-wrapper input-group" style="max-width: none">
                                            <input type="password" class="textfield form-control textfield-with-btn" id="oauthclientsecret" name="oauthclientsecret" value="" />
                                            <span class="input-group-btn">
                                                <button type="button" class="btn btn-sm btn-default tt-bind btn-show-password" title="<?= _('Show'); ?>">
                                                    <i class="material-symbols-outlined md-22 md-pointer">Visibility</i>
                                                </button>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                            <?php } ?>
                        </tr>
                        <tr>
                            <td>
                                </td>
                            <td class="btn-row">
                                <?php if (!is_neptune()) { ?>
                                    <button type='button' class='btn btn-sm btn-default' id='testoa2credentials' style="display: inline-block; width: 140px;" title="<?php echo _("Test OAuth2 credentials."); ?>"><?php echo _('Test Credentials'); ?></button>
                                <?php } else { ?>
                                    <button type='button' class='btn btn-sm btn-default' id='testoa2credentials' title="<?php echo _("Test OAuth2 credentials."); ?>"><?php echo _('Test Credentials'); ?></button>
                                <?php } ?>
                                <span id="testoa2result" class="material-symbols-outlined md-400 md-middle md-inherit md-default-cursor md-pending">help</span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div id="inbound">
                <?php if (!is_neptune()) { ?>
                    <div class="checkbox" style="margin: 10px 0;">
                        <label style="margin-right: 4px;">
                            <input type="checkbox" name="mail_inbound_process" value="1" <?php echo is_checked($mail_inbound_process, 1); ?>>
                            <?php echo _('Enable incoming email processing'); ?>
                        </label>
                        <i class="material-symbols-outlined md-400 md-20 md-help tt-bind md-middle" title="<?php echo _('Processing incoming mail allows you to set up an email address that will be used as a reply-to address for alerts.') . ' ' . _('Recipients can respond to emails with commands to acknowledge, schedule downtime, and more.'); ?>">help</i>
                    </div>
                <?php } else { ?>
                    <div class="btn-row">
                        <?php echo neptune_centered_checkbox(_('Enable incoming email processing'), "mail_inbound_process", "mail_inbound_process", $mail_inbound_process, 1); ?>
                        <i class="material-symbols-outlined md-400 md-20 md-help md-middle tt-bind" style="align-self: center" title="<?php echo _('Processing incoming mail allows you to set up an email address that will be used as a reply-to address for alerts.'). ' ' . _('Recipients can respond to emails with commands to acknowledge, schedule downtime, and more.'); ?>">help</i>
                    </div>
                <?php } ?>

                <?php if (!is_neptune()) { ?>
                    <h5 class="ul"><?php echo _("Inbound Mail Settings"); ?></h5>
                <?php } else {
                    echo neptune_section_spacer();
                    echo neptune_heading(_('Inbound Mail Settings'));
                } ?>
                
                <p class="neptune-subtext">
                    <?php echo _('Enter the email address for the inbox below to be parsed for notification replies. Nagios XI will automatically add this as the reply-to address for notification emails it sends.'); ?><br><b><?php echo _('This email address/inbox should not be used by anyone except Nagios XI. Emails will be deleted after processing.'); ?></b> <a target="_blank" href="https://assets.nagios.com/downloads/nagiosxi/docs/Inbound-Email-Commands-for-Nagios-XI.pdf"><?php echo _('View all commands you can send to Nagios XI'); ?></a>.
                </p>
                <?php if (is_neptune()) { echo neptune_section_spacer() ; } ?>

                <table class="table table-condensed table-no-border table-auto-width">
                    <tr>
                        <td>
                            <label><?php echo _('Reply-to Address'); ?>:</label>
                        </td>
                        <td colspan="2">
                            <input name="mail_inbound_replyto" type="text" class="textfield form-control" value="<?php echo encode_form_val($mail_inbound_replyto); ?>" size="40" style="width: 460px">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label style="white-space: nowrap"><?php echo _('Check Emails Every'); ?>:</label>
                        </td>
                        <td style="width: 113px">
                            <div class="input-group" style="display: table; width: 113px;">
                                <input type="text" name="mail_inbound_process_time" class="form-control" value="<?php echo $mail_inbound_process_time; ?>">
                                <label class="input-group-addon"><?php echo _('minutes'); ?></label>
                            </div>
                        </td>
                        <td>
                            <i class="material-symbols-outlined md-400 md-middle md-20 md-help tt-bind" title="<?php echo _('The amount of time between connecting to the email server and parsing them. Lowest setting is 1.'); ?>">help</i>
                        </td>
                    </tr>
                </table>

                <?php if (!is_neptune()) { ?>
                    <h5 class="ul"><?php echo _("Inbox Connection Settings"); ?></h5>
                <?php } else {
                    echo neptune_heading(_('Inbox Connection Settings'));
                } ?>

                <table class="table table-condensed table-no-border table-auto-width">
                    <tr>
                        <td></td><td>
                            <button type="button" class="btn btn-sm btn-info" id="copy_basic_from_outbound" title="<?php echo _('Copy settings from outbound mail settings'); ?>"><?php echo _('Copy from Outbound'); ?></button>
                        </td>
                    </tr>
                    <tr>
                        <td class="vt">
                            <label><?php echo _('Connection'); ?>:</label>
                        </td>
                        <td>
                            <div class="radio m-0">
                                <label>
                                    <input name="mail_inbound_type" type="radio" value="imap" <?php echo is_checked($mail_inbound_type, "imap"); ?>><?php echo _("IMAP"); ?>
                                </label>
                            </div>
                            <div class="radio m-0">
                                <label>
                                    <input name="mail_inbound_type" type="radio" value="pop3" <?php echo is_checked($mail_inbound_type, "pop3"); ?>>POP3
                                </label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><?php echo _('Host'); ?>:</label>
                        </td>
                        <td>
                            <input name="mail_inbound_host" type="text" class="textfield form-control" value="<?php echo encode_form_val($mail_inbound_host); ?>" size="40">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><?php echo _('Port'); ?>:</label>
                        </td>
                        <td>
                            <input name="mail_inbound_port" type="text" class="textfield form-control" value="<?php echo encode_form_val($mail_inbound_port); ?>" placeholder="143" size="4">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><?php echo _('Username'); ?>:</label>
                        </td>
                        <td>
                            <input name="mail_inbound_user" type="text" class="textfield form-control" value="<?php echo encode_form_val($mail_inbound_user); ?>" size="20">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label><?php echo _('Password'); ?>:</label>
                        </td>
                        <?php if (!is_neptune()) { ?>
                            <td>
                                <input name="mail_inbound_pass" type="password" class="textfield form-control" value="<?php echo encode_form_val($mail_inbound_pass); ?>" size="20" <?php echo sensitive_field_autocomplete(); ?>>
                                <button type="button" style="vertical-align: top;" class="btn btn-sm btn-default tt-bind btn-show-password" title="<?php echo _("Show password"); ?>"><i class="fa fa-eye"></i></button>
                            </td>
                        <?php } else { ?>
                            <td>
                                <div class="neptune-form-element">
                                    <div class="neptune-form-element-wrapper input-group" style="max-width: none">
                                        <input type="password" class="textfield form-control textfield-with-btn" id="mail_inbound_pass" name="mail_inbound_pass" value="<?php echo encode_form_val($mail_inbound_pass); ?>" <?php echo sensitive_field_autocomplete(); ?>/>
                                        <span class="input-group-btn">
                                            <button type="button" class="btn btn-sm btn-default tt-bind btn-show-password" title="<?= _('Show'); ?>">
                                                <i class="material-symbols-outlined md-22 md-pointer">Visibility</i>
                                            </button>
                                        </span>
                                    </div>
                                </div>
                            </td>
                        <?php } ?>
                    </tr>
                    <tr>
                        <td>
                            <label><?php echo _('Encryption'); ?>:</label>
                        </td>
                        <td>
                            <div class="radio m-0">
                                <label>
                                    <input name="mail_inbound_encryption" type="radio" value="none" <?php echo is_checked($mail_inbound_encryption, "none"); ?>><?php echo _("None"); ?>
                                </label>
                            </div>
                            <div class="radio m-0">
                                <label>
                                    <input name="mail_inbound_encryption" type="radio" value="tls" <?php echo is_checked($mail_inbound_encryption, "tls"); ?>>TLS
                                </label>
                            </div>
                            <div class="radio m-0">
                                <label>
                                    <input name="mail_inbound_encryption" type="radio" value="ssl" <?php echo is_checked($mail_inbound_encryption, "ssl"); ?>>SSL
                                </label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label></label>
                        </td>
                        <?php if (!is_neptune()) { ?>
                            <td class="checkbox">
                                <label>
                                    <input type="checkbox" name="mail_inbound_validate" value="1" <?php echo is_checked($mail_inbound_validate, 1); ?>>
                                    <?php echo _('Validate SSL certificate of mail server host'); ?>
                                </label>
                            </td>
                        <?php } else { ?>
                            <td>
                                <?php
                                echo neptune_centered_checkbox(_('Validate SSL certificate of mail server host'), "mail_inbound_validate", "mail_inbound_validate", $mail_inbound_validate, 1);
                                ?>
                            </td>
                        <?php } ?>
                    </tr>
                    <tr>
                        <td></td>
                        <td>
                            <?php
                            // Check if we have settings saved
                            $can_test = true;
                            if (($mail_inbound_legacy && (empty($mail_inbound_host) || empty($mail_inbound_user) || empty($mail_inbound_pass)))){
                                $can_test = false;
                            }
                            ?>
                            <div class="flex">
                                <button type="submit" class="submitbutton btn btn-sm btn-info m-0" <?php if (!$can_test) { echo 'disabled'; } ?> name="testimapconn" id="testimapconn">
                                    <i class="material-symbols-outlined md-400 md-18 md-middle">power</i> <?php echo _('Test Connection'); ?>
                                </button>
                                
                            </div>
                            <?php if (!$can_test) { echo '<div class="neptune-form-subtext neptune-form-spacer" style="padding-top: 4px;">'._("Note: Must fill out and save all settings before testing.").'</div>'; } ?>
                        </td>
                    </tr>
                </table>
            </div>

        </div>

        <div id="formButtons">
            <button type="submit" class="submitbutton btn btn-sm btn-primary" name="updateButton" id="updateButton" disabled><?php echo _('Update Settings'); ?></button>
            <!-- <button type="submit" class="submitbutton btn btn-sm btn-default" name="cancelButton" id="cancelButton"><?php echo _('Cancel'); ?></button> --><!-- why was there a cancel button?? -->
        </div>

    </form>
    <script>
        $(document).ready(function(){
            $('#copy_basic_from_outbound').on('click', function(event){
                event.preventDefault();
                $('input[name=mail_inbound_host]').val($('input[name=smtphost]').val());
                $('input[name=mail_inbound_user]').val($('input[name=smtpusername]').val());
                $('input[name=mail_inbound_pass]').val($('input[name=smtppassword]').val()).trigger('change');
                $('input[name=mail_inbound_encryption][value=' + $('input[name=smtpsecurity]:checked').val() + ']').prop('checked', true);
            });
            
            if ('<?php echo json_encode(is_checked($mail_inbound_encryption, "none")); ?>' == 'checked') {
                $('input[name="mail_inbound_validate"]').prop('disabled', true).prop('checked', false);
            }

            $('#convertCoreContacts').click(function(event){
                event.preventDefault();
                $('#convertCoreContacts').prop('disabled', true);
                $('#convertCoreContacts').html('<i class="fa fa-spinner fa-spin"></i> <?php echo _('Converting...'); ?>');
                $.ajax({
                    url: 'mailsettings.php',
                    type: 'POST',
                    data: { convertcorecontacts: 1 },
                    success: function(data){
                        $('#convertCoreContacts').prop('disabled', false);
                        $('#convertCoreContacts').html('<?php echo _('Convert Core Contacts'); ?>');
                        
                        if (!data) {
                            show_flash_alert('<?php echo _('Core contacts and templates have been converted to use your Nagios XI mail settings.'); ?>', 'success');
                            $('#convertCoreContacts').parent().remove();
                        } else {
                            show_flash_alert('<?php echo _('There was an error converting core contacts: '); ?>' + data, 'error');
                        }
                    }
                });
            });


            $('#providersrefreshbutton').on('click', function(event){
                event.preventDefault();
                refresh_providers();
            });

            $('#deleteproviderbutton').on('click', function(event){
                event.preventDefault();
                delete_provider();
            });

            $('#testoa2credentials').on('click', function(event){
                event.preventDefault();
                test_azure_client_credentials();
            });

            $('#testimapconn').on('click', function(event){
                event.preventDefault();
                test_imap_connection();
            });

            $('#inbound').find('input, select').on('change, input', function(){
                if ($('#mail_inbound_host').val() == '' || $('#mail_inbound_user').val() == '' || $('#mail_inbound_pass').val() == ''){
                    $('#testimapconn').prop('disabled', true);
                } else {
                    $('#testimapconn').prop('disabled', false);
                }
            });
        });

        /**
         * populates OAuth credentials <select> with providers
         * 
         * @param {array} providersarray - array of providers
         */
        function populate_credentials(providersarray){
            if(!providersarray || Object.keys(providersarray).length == 0){
                return;
            }
            $('#smtpoauthname').empty();
            $.each(providersarray, function(i, provider){
                $('#smtpoauthname').append($('<option>', { value: provider['customName'], text: provider['customName']}));
            });
            if($('#smtpoauthname').find('option[value="<?php echo $smtpoauthname; ?>"]').length > 0){
                $('#smtpoauthname').val('<?php echo $smtpoauthname; ?>').trigger('change');
            }
        }

        /**
         * refreshes the OAuth credentials <select> with providers
         */
        async function refresh_providers(){
            $.ajax({
                url: '../includes/components/oauth2/oauth-ajaxhelper.php',
                type: 'post',
                data: { cmd: 'get_providers', 'nsp': '<?php echo get_nagios_session_protector_id(); ?>' },
                success: function(response) {
                    response = JSON.parse(response);
                    if(response){
                        populate_credentials(response);
                    } else {
                        alert(response.message);
                    }
                }
            });
        }

        /**
         * AJAX call to delete the selected provider credentials
         * 
         * @param {string} $('#smtpoauthname') - name of credentials to delete currently selected in <select>
         */
        async function delete_provider(){
            if(confirm(_("Are you sure you want to delete this provider?"))){
                $.ajax({ 
                    url: '../includes/components/oauth2/oauth-ajaxhelper.php',
                    type: 'post',
                    data: { cmd: 'delete_provider', credentialsname: $('#smtpoauthname').val(), 'nsp': '<?php echo get_nagios_session_protector_id(); ?>' }, success: function(response) {
                        response = JSON.parse(response);
                        if(response.status == 'success'){
                            refresh_providers();
                        } else {
                            alert(response.status);
                        }
                    }
                });
            }
            return;
        }

        /**
         * AJAX call to test client credentials against the selected provider for client credentials flow
         *     if credentials are good => save to /usr/local/nagiosxi/etc/components/oauth2/oauth2mailcredentials.json (mail credentials for client credentials flow)
         * 
         * @param {string} $('input:radio[name=mailmethod]:checked').val()  - mail method   => converts to provider (azure, google, etc) to create provider class
         * @param {string} $('input[name=oauthclientid]')                   - client id
         * @param {string} $('input[name=oauthtenantid]')                   - tenant id
         * @param {string} $('input[name=oauthclientsecret]')               - client secret
         * 
         */
        async function test_azure_client_credentials(alert = true, setmailmethod = true){
            if($('input[name=oauthclientid]').val() == '' || $('input[name=oauthtenantid]').val() == '' || $('input[name=oauthclientsecret]').val() == ''){
                show_flash_alert(_('Please fill out all fields'), 'error');
                return;
            }
            let mailmethod  = $('input:radio[name=mailmethod]:checked').val();
            let provider    = '';
            // if(mailmethod == 'msGraphOA2'){
                provider = 'azure';
            // } else if(mailmethod == 'smtpOA2'){
            //     provider = 'google';
            // }
            $('#testoa2result').find('img').attr('src', '<?php echo get_base_url(); ?>images/throbber.gif').attr('data-original-title', '<?php echo _("testing..."); ?>').width(16).height(16);
            let microsoftIcon = $('#testoa2result');
            microsoftIcon.html('more_horiz').attr('title', '<?php echo _("checking..."); ?>');
            microsoftIcon.removeClass('md-ok');
            microsoftIcon.removeClass('md-critical');
            microsoftIcon.addClass('md-pending');

            $.ajax({ 
                url:                '../includes/components/oauth2/oauth-ajaxhelper.php',
                type:               'post', 
                data: { 
                    cmd:            'test_azure_mail_credentials',
                    provider:       provider,
                    clientid:       $('input[name=oauthclientid]').val(),
                    tenantid:       $('input[name=oauthtenantid]').val(),
                    clientsecret:   $('input[name=oauthclientsecret]').val(),
                    setmailmethod:  setmailmethod,
                    nsp:            '<?php echo get_nagios_session_protector_id(); ?>' 
                }, success: function(response) {
                    response = JSON.parse(response);
                    if(response.status == 'success'){
                        microsoftIcon.removeClass('md-pending');
                        microsoftIcon.html('check_circle').addClass('md-ok').attr('title', '<?php echo _("Credentials validated and saved"); ?>');
                        if(alert){
                            show_flash_alert(_("Credentials validated and saved."), 'success');
                        }
                    } else {
                        microsoftIcon.removeClass('md-pending');
                        microsoftIcon.html('error').addClass('md-critical').attr('title', response.message);
                        show_flash_alert(response.message, 'error');
                    }
                }
            });
            return;
        }

        /**
         * AJAX call to test IMAP connection
         * 
         * @param {string} mail_inbound_host - IMAP host
         * @param {string} mail_inbound_port - IMAP port
         * @param {string} mail_inbound_user - IMAP username
         * @param {string} mail_inbound_pass - IMAP password
         * @param {string} mail_inbound_encryption - IMAP encryption
         * @param {string} mail_inbound_validate - IMAP validate SSL
         * 
         * @return {string} -- returns true/false and flashes alert on success or failure
         */
        async function test_imap_connection(){
            try{
                let mail_inbound_process    = $('input[name=mail_inbound_process]').is(':checked');
                let mail_inbound_replyto    = $('input[name=mail_inbound_replyto]').val();
                let mail_inbound_process_time = $('input[name=mail_inbound_process_time]').val();

                let mail_inbound_type       = $('input[name=mail_inbound_type]:checked').val();
                let mail_inbound_host       = $('input[name=mail_inbound_host]').val();
                let mail_inbound_port       = $('input[name=mail_inbound_port]').val();
                let mail_inbound_user       = $('input[name=mail_inbound_user]').val();
                let mail_inbound_pass       = $('input[name=mail_inbound_pass]').val();
                let mail_inbound_encryption = $('input[name=mail_inbound_encryption]:checked').val();
                let mail_inbound_validate   = $('input[name=mail_inbound_validate]').is(':checked');
                // let mail_inbound_legacy     = $('input[name=legacy_inbound]').is(':checked'); 
                let mail_inbound_legacy     = true; // always true for now, will be editable in future

                let verifyIMAPIcon = $('#testimapconn');
                verifyIMAPIcon.html('<i class="material-symbols-outlined md-400 md-18 md-middle">power</i> <?php echo _("Testing Connection"); ?>').prop('disabled', true);

                $.ajax({ 
                    url:                '../includes/components/oauth2/oauth-ajaxhelper.php',
                    type:               'post', 
                    data: { 
                        cmd:                'test_inbound_mail',
                        mail_inbound_legacy:        mail_inbound_legacy,
                        mail_inbound_process:       mail_inbound_process,
                        mail_inbound_replyto:       mail_inbound_replyto,
                        mail_inbound_process_time:  mail_inbound_process_time,

                        mail_inbound_type:          mail_inbound_type,
                        mail_inbound_host:          mail_inbound_host,
                        mail_inbound_port:          mail_inbound_port,
                        mail_inbound_user:          mail_inbound_user,
                        mail_inbound_pass:          mail_inbound_pass,
                        mail_inbound_encryption:    mail_inbound_encryption,
                        mail_inbound_validate:      mail_inbound_validate,
                        
                        nsp:                        '<?php echo get_nagios_session_protector_id(); ?>'
                    }, 
                    success: function(response) {
                        response = JSON.parse(response);
                        if(response.status == 'success'){
                            $(window).scrollTop(0);
                            verifyIMAPIcon.html('<i class="material-symbols-outlined md-400 md-18 md-middle">power</i> <?php echo _("Test Connection"); ?>').prop('disabled', false);
                            show_flash_alert(response.message, 'success');
                            return true;
                        } else {
                            $(window).scrollTop(0);
                            verifyIMAPIcon.html('<i class="material-symbols-outlined md-400 md-18 md-middle">power</i> <?php echo _("Test Connection"); ?>').prop('disabled', false);
                            show_flash_alert(response.message, 'error', 10000);
                            return false;
                        }
                    }
                });
            } catch(error){
                verifyImapIcon.html('<i class="material-symbols-outlined md-400 md-18 md-middle">power</i> <?php echo _("Test Connection"); ?>').prop('disabled', false);
                show_flash_alert(error, 'error');
                return false;
            }
        }
            
        /*
         *  Helper function to flash alerts
         *
         * @param {string} message - message to display
         * @param {string} type    - type of alert (success, error, info, warning)
         * 
         *   AJAX returns:
             * @return {string(HTML)} alert -- response from AJAX call
         * 
         * @return {bool} -- returns true/false and flashes alert on success or failure
         */
        async function show_flash_alert(message, type = '', timeout = 3000){
            try{
                let container = $('body');
                let alert = await $.post('../includes/components/oauth2/oauth-ajaxhelper.php', { 'cmd': 'flash_alert', 'message': message, 'type': type, 'nsp': '<?php echo get_nagios_session_protector_id(); ?>'}, function(response){}, "html");
                if(container && alert){
                    alert = $(alert).prependTo(container);
                    setTimeout(() => {alert.remove()}, timeout);
                }
                return true;
            } catch(error){
                return false;
            }
        }
    </script>


<?php
    do_page_end(true);
    exit();
} // end of show_settings()

/**
 * Updates settings then redirects to mailsettings.php
 */
function do_update_settings()
{
    global $request;

    // user pressed the cancel button
    if (isset($request["cancelButton"])) {
        header("Location: main.php");
        return;
    }

    // check session
    check_nagios_session_protector();

    $errmsg = array();
    $errors = 0;

    // Get outbound variables
    $mailmethod         = grab_request_var("mailmethod",    "sendmail");	
    $fromaddress        = grab_request_var("fromaddress",   "");	
    $smtphost           = grab_request_var("smtphost",      "");	
    $smtpport           = grab_request_var("smtpport",      "");	
    $smtpusername       = grab_request_var("smtpusername",  "");	
    $smtppassword       = grab_request_var("smtppassword",  "");	
    $smtpsecurity       = grab_request_var("smtpsecurity",  "");
    $php_sendmail_log   = grab_request_var("php_sendmail_log", "");	
    $php_sendmail_debug = grab_request_var("php_sendmail_debug", "");
    $smtpoauthname      = grab_request_var("smtpoauthname", "");

    // Get inbound variables	
    $mail_inbound_process       =               grab_request_var("mail_inbound_process",       0);	
    $mail_inbound_replyto       =               grab_request_var("mail_inbound_replyto",       "");	
    $mail_inbound_process_time  =        intval(grab_request_var("mail_inbound_process_time",  2));	
    $mail_inbound_type          =               grab_request_var("mail_inbound_type",          "imap");	
    $mail_inbound_host          =               grab_request_var("mail_inbound_host",          "");	
    $mail_inbound_port          =               grab_request_var("mail_inbound_port",          "");	
    $mail_inbound_user          =               grab_request_var("mail_inbound_user",          "");	
    $mail_inbound_pass          =  encrypt_data(grab_request_var("mail_inbound_pass",          ""));	
    $mail_inbound_encryption    =               grab_request_var("mail_inbound_encryption",    "none");	
    $mail_inbound_validate      =               grab_request_var("mail_inbound_validate",      0);	

    // make sure we have requirements
    if (in_demo_mode()) {
        $errmsg[$errors++] = _("Changes are disabled while in demo mode.");
    }
    if (!have_value($fromaddress)) {
        $errmsg[$errors++] = _("No from address specified.");
    }
    if ($mailmethod == "smtp") {
        if (!have_value($smtphost))
            $errmsg[$errors++] = _("No SMTP host specified.");
        if (!have_value($smtpport))
            $errmsg[$errors++] = _("No SMTP port specified.");
    }

    // Force inbound process time to at least 1 minute
    if ($mail_inbound_process_time < 1) {
        $mail_inbound_process_time = 1;
    }

    if (!in_demo_mode()) {
        // Outbound settings
        set_option("mail_method",           $mailmethod);
        set_option("mail_from_address",     $fromaddress);
        set_option("smtp_host",             $smtphost);
        set_option("smtp_port",             $smtpport);
        set_option("smtp_username",         $smtpusername);
        set_option("smtp_password",         $smtppassword);
        set_option("smtp_security",         $smtpsecurity);
        set_option("php_sendmail_log",      $php_sendmail_log);
        set_option("php_sendmail_debug",    $php_sendmail_debug);
        set_option("smtp_oauth_name",       $smtpoauthname);

        // Inbound settings
        set_option("mail_inbound_process",      $mail_inbound_process);
        set_option("mail_inbound_replyto",      $mail_inbound_replyto);
        set_option("mail_inbound_process_time", $mail_inbound_process_time);
        set_option("mail_inbound_type",         $mail_inbound_type);
        set_option("mail_inbound_host",         $mail_inbound_host);
        set_option("mail_inbound_port",         $mail_inbound_port);
        set_option("mail_inbound_user",         $mail_inbound_user);
        set_option("mail_inbound_pass",         $mail_inbound_pass);
        set_option("mail_inbound_encryption",   $mail_inbound_encryption);
        set_option("mail_inbound_validate",     $mail_inbound_validate);
    }

    // Make sure the phpmailer.log file has been created correctly.
    if ($php_sendmail_log) {
        $file = get_tmp_dir().'/phpmailer.log';

        // Create a 664 file (rw-rw-r--) apache/nagios
        $cmdline = ". ".get_root_dir()."/var/xi-sys.cfg && touch $file && chown \$apacheuser:\$nagiosgroup $file && chmod 0664 $file";
        $output = system($cmdline, $return_code);
    }

    if ($errors > 0) {
        $all_errors = implode(' ', $errmsg);
        flash_message(print_r($all_errors, true), FLASH_MSG_ERROR);
        
        show_settings();
    }

    // Mark that settings were updated
    set_option("mail_settings_configured", 1);

    send_to_audit_log("Updated global mail settings", AUDITLOGTYPE_CHANGE);

    flash_message(_("Mail settings updated.")); // this wasn't working properly
    header('Location: mailsettings.php');
}


function convert_core_contacts()
{
    $output = shell_exec('echo "y" | /usr/local/nagiosxi/scripts/convert_core_contacts.sh');
    $notify_host_by_email_xi = nagiosql_get_command_id("notify-host-by-email-xi");
    $notify_service_by_email_xi = nagiosql_get_command_id("notify-service-by-email-xi");
    reconfigure_nagioscore();

    # commands didn't get added through the script for one reason or another (probably reconfiguring breaking. Sometimes the timing doesn't work if Core takes too long), try again after reconfiguring
    if (!$notify_host_by_email_xi || !$notify_service_by_email_xi) {
        sleep(5); // wait for Core to reconfigure
        $output .= shell_exec('echo "y" | /usr/local/nagiosxi/scripts/convert_core_contacts.sh');
    }

    $notify_host_by_email_xi = nagiosql_get_command_id("notify-host-by-email-xi");
    $notify_service_by_email_xi = nagiosql_get_command_id("notify-service-by-email-xi");
   
    if (!$notify_host_by_email_xi || !$notify_service_by_email_xi) {
        $output .= "Could not add commands properly. You may need to run the following script manually: /usr/local/nagiosxi/scripts/convert_core_contacts.sh";
    }
}