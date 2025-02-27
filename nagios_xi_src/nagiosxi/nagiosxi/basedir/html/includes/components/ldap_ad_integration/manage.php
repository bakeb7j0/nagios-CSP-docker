<?php
//
// LDAP / Active Directory Integration
// Copyright (c) 2015-2024 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__).'/../../common.inc.php');
require_once(dirname(__FILE__).'/../componenthelper.inc.php');

require_once(dirname(__FILE__).'/adLDAP/src/adLDAP.php');
include_once(dirname(__FILE__).'/ldap_ad_integration.inc.php');

// Initialization stuff
pre_init();
init_session();

// Grab GET or POST variables, check prereqs and authentication
grab_request_vars();
check_prereqs();
check_authentication();

// Only admins can access this page
if (is_admin() == false) {
    echo _("You do not have access to this section.");
    exit();
}

route_request();

function route_request()
{
    $cmd = grab_request_var("cmd", "");

    switch ($cmd)
    {
        case 'deleteserver':
            process_delete_server();
            break;

        case 'editserver':
            process_edit_server();
            break;

        case 'addserver':
            process_add_server();
            break;

        default:
            display_page();
            break;
    }
}

/*
// Save settings
$server = array(
    "enabled" => $enabled,
    "conn_method" => $conn_method,
    "ad_account_suffix" => $account_suffix,
    "ad_domain_controllers" => $domain_controllers,
    "base_dn" => $base_dn,
    "security_level" => $security_level,
    "ldap_port" => $ldap_port,
    "ldap_host" => $ldap_host
);

set_option("ldap_ad_integration_component_servers", serialize($settings));
*/

// Display the default page
function display_page($posted=false, $error='', $msg='')
{
    $servers = auth_server_list();
    $component_url = get_component_url_base("ldap_ad_integration");

    // Get the settings that someone put in and tried to save...
    $enabled = grab_request_var("enabled", "");
    if (!empty($enabled) && $posted) { $enabled = 1; }
    else if (empty($enabled) && !$posted) { $enabled = 1; }

    $conn_method = grab_request_var("conn_method", "ad");
    $base_dn = grab_request_var("base_dn", "");
    $security_level = grab_request_var("security_level", "none");

    // AD Only
    $account_suffix = grab_request_var("account_suffix", "");
    $domain_controllers = grab_request_var("domain_controllers", "");

    // LDAP Only
    $ldap_host = grab_request_var("ldap_host", "");
    $ldap_port = grab_request_var("ldap_port", "389");

    do_page_start(array("page_title" => _("LDAP / Active Directory Integration Configuration")), true);

    echo neptune_page_title(_("LDAP / Active Directory Integration Configuration"));
?>
    <div class="hide">
<?php
    echo neptune_centered_checkbox(_('Enable LDAP / Active Directory Authentication'), 'enabled', 'enabled', $enabled, 1);
?>
    </div>

    <div>
        <div style="width: 48%; min-width: 750px; float: left;">
<?php
    if (!is_neptune()) {
?>    
            <h5 class="ul">LDAP/AD Authentication Servers</h5>
            <p>
                <?= _("Authentication servers can be used to authenticate users over on login.") ?><br>
                <?= _("Once a server has been added you can ") ?> <a href="<?= $component_url ?>/index.php"><?= _("import users") ?></a>
            </p>
<?php
    } else {
        echo neptune_heading(_("LDAP/AD Authentication Servers"));
        echo neptune_subtext(_("Authentication servers can be used to authenticate users over on login.").'<br>'.
                             _("Once a server has been added you can ").' <a href="'.$component_url.'/index.php">'._("import users").'</a>.');
    }
?>
            <p>
                <button id="add-auth-server" type="button" class="btn btn-sm btn-primary"><?= _("Add Authentication Server") ?></button>
            </p>

            <table class="table table-condensed table-striped table-bordered" style="width: 94%;">
                <thead>
                    <tr>
                        <th style="width: 20px; text-align: center;"></th>
                        <th><?= _("Server(s)") ?></th>
                        <th><?= _("Type") ?></th>
                        <th><?= _("Encryption") ?></th>
                        <th style="width: 120px;"><?= _("Associated Users") ?></th>
                        <th style="text-align: center; width: 60px;"><?= _("Actions") ?></th>
                    </tr>
                </thead>
                <tbody id="ldap_ad_servers">
<?php
                    if (empty($servers)) {
?>
                    <tr>
                        <td colspan="9"><?= _("There are currently no LDAP or AD servers to authenticate against.") ?></td>
                    </tr>
<?php
                    } else {

                        foreach ($servers as $server) {
?>
                        <tr>
                            <td style="text-align: center;">
                                <i class="material-symbols-outlined md-20 md-400 md-middle
                                            <?= ($server['enabled'] ? 'md-ok' : 'md-critical') ?>"
                                     title="<?= ($server['enabled'] ? _("Enabled") : _("Not enabled")) ?>"
                                     ><?= ($server['enabled'] ? 'check_circle' : 'error') ?></i>
                            </td>
                            <td>
                                <?= encode_form_val(($server['conn_method'] == "ldap" ? $server['ldap_host']
                                                                                      : $server['ad_domain_controllers'])) ?>
                                
                            </td>
                            <td><?= ldap_ad_display_type($server['conn_method']) ?></td>
                            <td>
<?php
                        if ($server['security_level'] == 'tls') {
                            echo 'STARTTLS';
                        } else if ($server['security_level'] == 'ssl') {
                            echo 'SSL/TLS';
                        } else {
                            echo _('None');
                        }
?>
                            </td>
                            <td><?= ldap_ad_get_associations($server['id']) ?></td>
                            <td class="neptune-td-nowrap actionCell">
                                <a class="edit tt-bind" data-id="<?= $server['id'] ?>" title="<?= _("Edit server") ?>"><i class="material-symbols-outlined md-button md-action md-400 md-20 md-middle">edit_square</i></a>
                                <a class="tt-bind" href="manage.php?cmd=deleteserver&server_id=<?= $server['id'] ?>" title="<?= _("Remove server") ?>"><i class="material-symbols-outlined md-button md-action md-400 md-20 md-middle">delete</i></a>
                            </td>
                        </tr>
                        <?php
                        }
                    }
                    ?>
                </tbody>
            </table>

            <div <?= (!$posted ? 'class="hide"' : '')?> id="manage-servers">
                <form action="manage.php" method="post"> 
<?php
    echo neptune_heading(_("Authentication Server Settings"));

    if (!empty($error)) {
?>
                <div class="message" style="width: 450px;">
                    <ul class="errorMessage">
                        <li><?= $error ?></li>
                    </ul>
                </div>
<?php
    }

    if (!is_neptune()) {
?>
                <table style="margin-bottom: 20px;">
                    <tr>
                        <td></td>
                        <td>
                            <label style="margin-bottom: 10px;">
                                <input type="checkbox" name="enabled" value="1" <?= ($enabled ? "checked" : '') ?>>
                                <?= _("Enable this authentication server") ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top" style="padding-top: 4px;">
                            <label for="conn_method"><?= _("Connection Method") ?>:</label>
                        </td>
                        <td>
                            <select name="conn_method" id="conn_method" class="form-control">
                                <option value="ad" <?= is_selected($conn_method, 'ad') ?>><?= _("Active Directory") ?></option>
                                <option value="ldap" <?= is_selected($conn_method, 'ldap') ?>><?= _("LDAP") ?></option>
                            </select>
                            <div style="margin-bottom: 10px;"><?= _('Use either LDAP or Active Directory settings to connect') ?>.</div>
                        </td>
                    </tr>
                    <tr class="option ad-options ldap-options">
                        <td valign="top" style="padding-top: 4px;">
                            <label for="base_dn"><?= _('Base DN') ?>:</label>
                        </td>
                        <td valign="top">
                            <input type="text" size="40" name="base_dn" id="base_dn" value="<?= encode_form_val($base_dn) ?>" placeholder="dc=nagios,dc=com" class="textfield form-control">
                            <div style="margin-bottom: 10px;"><?= _('The LDAP-format starting object (distinguished name) that your users are defined below, such as') ?> <strong>DC=nagios,DC=com</strong>.</div>
                        </td>
                    </tr>
                    <tr class="option ad-options <?= ($conn_method == "ldap" ? 'hide' : '') ?>">
                        <td valign="top" style="padding-top: 4px;">
                            <label for="account_suffix"><?= _('Account Suffix') ?>:</label>
                        </td>
                        <td>
                            <input type="text" size="25" name="account_suffix" id="account_suffix" value="<?= encode_form_val($account_suffix) ?>" placeholder="@nagios.com" class="textfield form-control">
                            <div style="margin-bottom: 10px;"><?= _('The part of the full user identification after the username, such as') ?> <strong>@nagios.com</strong>.</div>
                        </td>
                    </tr>
                    <tr class="option ad-options <?= ($conn_method == "ldap" ? 'hide' : '') ?>">
                        <td valign="top" style="padding-top: 4px;">
                            <label for="domain_controllers"><?= _('Domain Controllers') ?>:</label>
                        </td>
                        <td valign="top">
                            <input type="text" size="80" name="domain_controllers" id="domain_controllers" value="<?= encode_form_val($domain_controllers) ?>" placeholder="dc1.nagios.com,dc2.nagios.com" class="textfield form-control">
                            <div style="margin-bottom: 10px;"><?= _('A comma-separated list of domain controllers on your network.') ?></div>
                        </td>
                    </tr>
                    <tr class="option ldap-options <?= ($conn_method == "ad" ? 'hide' : '') ?>">
                        <td valign="top" style="padding-top: 4px;">
                            <label for="ldap_host"><?= _("LDAP Host") ?>:</label>
                        </td>
                        <td>
                            <input type="text" name="ldap_host" id="ldap_host" value="<?= encode_form_val($ldap_host) ?>" placeholder="ldap.nagios.com" class="textfield form-control" style="width: 300px;">
                            <div style="margin-bottom: 10px;"><?= _('The IP address or hostname of your LDAP server.') ?></div>
                        </td>
                    </tr>
                    <tr class="option ldap-options <?= ($conn_method == "ad" ? 'hide' : '') ?>">
                        <td valign="top" style="padding-top: 4px;">
                            <label for="ldap_port"><?= _("LDAP Port") ?>:</label>
                        </td>
                        <td>
                            <input type="text" name="ldap_port" id="ldap_port" value="<?= encode_form_val($ldap_port) ?>" class="textfield form-control" style="width: 60px;">
                            <div style="margin-bottom: 10px;"><?= _('The port your LDAP server is running on. (Default is 389)') ?></div>
                        </td>
                    </tr>
                    <tr class="option ad-options ldap-options">
                        <td valign="top" style="padding-top: 4px;">
                            <label for="security_level"><?= _('Security') ?>:</label>
                        </td>
                        <td valign="top">
                            <select name="security_level" id="security_level" class="form-control">
                                <option value="none" <?= ldap_ad_has_security($security_level, "none") ?>><?= _('None') ?></option>
                                <option value="ssl" <?= ldap_ad_has_security($security_level, "ssl") ?>>SSL/TLS</option>
                                <option value="tls" <?= ldap_ad_has_security($security_level, "tls") ?>>STARTTLS</option>
                            </select>
                            <div><?= _("The type of security (if any) to use for the connection to the server(s). The STARTTLS option may use a plain text connection if the server does not upgrade the connection to TLS.") ?></div>
                        </td>
                    </tr>
                </table>
<?php
    } else {

        echo neptune_centered_checkbox(_('Enable this authentication server'), 'enabled', 'enabled', $enabled, 1);
        echo neptune_section_spacer();

        $conn_method_options = array(
                'ad'    => _("Active Directory"),
                'ldap'  => _("LDAP"),
            );

        echo neptune_select(_("Connection Method"), 'conn_method', 'conn_method', $conn_method_options, $conn_method, _('Use either LDAP or Active Directory settings to connect'));

        $description = _('The LDAP-format starting object (distinguished name) that your users are defined below, such as').' <strong>DC=nagios,DC=com</strong>.';

        echo neptune_text(_('Base DN'), 'base_dn', 'base_dn', encode_form_val($base_dn), $description, '', 'form-item-required', ' placeholder="dc=nagios,dc=com"', 'option ad-options ldap-options');

        $description = _('The part of the full user identification after the username, such as').' <strong>@nagios.com</strong>.';

        echo neptune_text(_('Account Suffix'), 'account_suffix', 'account_suffix', encode_form_val($account_suffix), $description, '', 'form-item-required', ' placeholder="@nagios.com"', 'option ad-options '.($conn_method == "ldap" ? 'hide' : ''));

        $description = _('A comma-separated list of domain controllers on your network.');

        echo neptune_text(_('Domain Controllers'), 'domain_controllers', 'domain_controllers', encode_form_val($domain_controllers), $description, '', 'form-item-required', ' placeholder="dc1.nagios.com,dc2.nagios.com"', 'option ad-options '.($conn_method == "ldap" ? 'hide' : ''));

        $description = _('The IP address or hostname of your LDAP server.');

        echo neptune_text(_('LDAP Host'), 'ldap_host', 'ldap_host', encode_form_val($ldap_host), $description, '', 'form-item-required', ' placeholder="ldap.nagios.com"', 'option ldap-options '.($conn_method == "ad" ? 'hide' : ''));

        $description = _('The port your LDAP server is running on. (Default is 389)');

        echo neptune_text(_('LDAP Port'), 'ldap_port', 'ldap_port', encode_form_val($ldap_port), $description, '', 'form-item-required', '', 'option ldap-options '.($conn_method == "ad" ? 'hide' : ''));

        $security_level_options = array(
                'none'    => _('None'),
                'ssl'  => 'SSL/TSL',
                'tls'  => 'STARTTLS',
            );

        # TODO: fix this when the code is merged with additional data in array.
        #echo neptune_select(_("Connection Method"), 'conn_method', 'conn_method', $conn_method_options, $conn_method, _('Use either LDAP or Active Directory settings to connect'));
?>
                    <div class="neptune-form-element option ad-options ldap-options">
                        <label class="neptune-form-label-spacer"><?= _('Security') ?></label>
                        <div class="neptune-form-element-wrapper"><!--
                         --><select id="security_level" name="security_level" class="form-control dropdown">
                                <option value="none" <?= ldap_ad_has_security($security_level, "none") ?>><?= _('None') ?></option>
                                <option value="ssl" <?= ldap_ad_has_security($security_level, "ssl") ?>>SSL/TLS</option>
                                <option value="tls" <?= ldap_ad_has_security($security_level, "tls") ?>>STARTTLS</option>
                            </select><!--
                     --></div>
                        <div class="subtext neptune-form-subtext neptune-form-spacer"><?= _("The type of security (if any) to use for the connection to the server(s). The STARTTLS option may use a plain text connection if the server does not upgrade the connection to TLS.") ?></div>
                    </div>
<?php
    }
?>
                <p class="btn-row">
                    <input type="hidden" id="server_id" name="server_id" value="">
                    <input type="hidden" id="cmd" name="cmd" value="addserver">
                    <button type="submit" class="btn btn-sm btn-primary"><?= _("Save Server") ?></button> 
                    <button type="button" class="btn btn-sm btn-default" id="cancel-add"><?= _("Cancel") ?></button>
                </p>
                </form>

            </div>
        </div>
        <div style="width: 50%; min-width: 600px; float: left;">
<?php

    if (!is_neptune()) {
?>    
            <h5 class="ul"><?= _("Certificate Authority Management") ?></h5>
            <p><?= _("For connecting over SSL/TLS, or STARTTLS using self-signed certificates you will need to add the certificate(s) of the domain controller(s) to the local certificate authority so they are trusted. If any certificate was signed by a host other than itself, that certificate authority/host certificate needs to be added.") ?></p>
<?php
    } else {
        echo neptune_heading(_("Certificate Authority Management"));
        echo neptune_subtext(_("For connecting over SSL/TLS, or STARTTLS using self-signed certificates you will need to add the certificate(s) of the domain controller(s) to the local certificate authority so they are trusted. If any certificate was signed by a host other than itself, that certificate authority/host certificate needs to be added."));
    }
?>
            <div style="margin-bottom: 10px;">
                <button type="button" class="btn btn-sm btn-primary" id="add-ca-cert"><?= _("Add Certificate") ?></button>
            </div>
            <table class="table table-condensed table-striped table-bordered">
                <thead>
                    <tr>
                        <th><?= _("Hostname") ?></th>
                        <th><?= _("Issuer (CA)") ?></th>
                        <th><?= _("Expires On") ?></th>
                        <th style="text-align: center; width: 60px;"><?= _("Actions") ?></th>
                    </tr>
                </thead>
                <tbody id="cert-list">
                </tbody>
            </table>
        </div>
        <div style="clear: both;"></div>
    </div>

    <a href="#" id="show-advanced-options" class="btn-icon-and-text text-medium"><?= _("Advanced") ?> <i id="advanced-options-chevron" class="material-symbols-outlined">chevron_right</i></a>
    <div class="hide advanced-options">

        <table class="table table-condensed table-no-border table-auto-width">
            <tbody>
                <tr>
                    <td>
                        <label class="toggle_switch">
                            <input type="checkbox" id="ad-ldap-debugging" class="enable_toggle_slider" <?= is_checked(get_option('ad_ldap_debugging', 'off')) ?>>
                            <span class="toggle_slider"></span>
                        </label>
                    </td>
                    <td>
                        <label>
                            <?= _("Enable setup and authentication debugging") ?>
                        </label>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <script type="text/javascript">
    $(document).ready(function() {

        // Load a list of certificates
        load_certificates();

        var spacer = '';
<?php
    if (!is_neptune()) {
?>
        spacer = 'padding-top: 10px;'
<?php
    }
?>
        // Popup window to add a certificate
        $("#add-ca-cert").click(function() {
            var content = 
                "<div>" +
                "    <h2><?= encode_form_valq(_('Add Certificate to Certificate Authority')) ?></h2>" +
                "    <p class='text-small'>" +
                "        <?= encode_form_valq(_('To add a certificate to the certificate authority, copy and paste the actual certificate between, and including, the begin/end certificate sections.')) ?>" +
                "    </p>" +
                "    <div class='neptune-form-element '>" +
                "       <label for='cahost' class='neptune-form-label-spacer'><?= encode_form_valq(_('Hostname')) ?></label>" +
                "       <div class='neptune-form-element-wrapper align-items-center-flex'>" +
                "           <input name='host' class='form-control' id='cahost' type='text' style='width: 200px;' readonly>" +
                "           <div id='caload' class='sk-spinner sk-spinner-fading-circle neptune-spinner' style='font-size: 20px; margin: 0px 7.5px; display: none;'> " +
                "               <div class='sk-circle1 sk-circle'></div> " +
                "               <div class='sk-circle2 sk-circle'></div> " +
                "               <div class='sk-circle3 sk-circle'></div> " +
                "               <div class='sk-circle4 sk-circle'></div> " +
                "               <div class='sk-circle5 sk-circle'></div> " +
                "               <div class='sk-circle6 sk-circle'></div> " +
                "               <div class='sk-circle7 sk-circle'></div> " +
                "               <div class='sk-circle8 sk-circle'></div> " +
                "               <div class='sk-circle9 sk-circle'></div> " +
                "               <div class='sk-circle10 sk-circle'></div> " +
                "               <div class='sk-circle11 sk-circle'></div> " +
                "               <div class='sk-circle12 sk-circle'></div> " +
                "           </div>" +
                "           <span id='caerror' style='display: none; margin-left: 6px; color: red;'></span>" +
                "       </div>" +
                "       <div class='subtext neptune-form-subtext neptune-form-spacer'></div>" +
                "    </div>" +
                "    <div class='neptune-form-element' style='"+spacer+"'>" +
                "        <label for='cert' class='neptune-form-label-spacer form-item-required'><?= encode_form_valq(_('Certificate')) ?></label>" +
                "        <div class='neptune-form-element-wrapper'>" +
                "            <textarea id='cert' spellcheck='false' name='cert' style='width: 446px; height: 200px; font-family: monospace; font-size: 10px;' class='form-control'></textarea>" +
                "        </div>" +
                "    </div>" +
                "    <div class='subtext neptune-form-subtext neptune-form-spacer' style='"+spacer+"'>" +
                "        <button id='add-ca-cert-popup' class='btn btn-sm btn-primary' disabled><?= encode_form_valq(_('Add Certificate')) ?></button>" +
                "    </div>" +
                "</div>";
<?php
    if (!is_neptune()) {
?>
            $("#child_popup_container").height(392);
            $("#child_popup_container").width(470);
            $("#child_popup_layer").height(412);
            $("#child_popup_layer").width(490);
<?php
    } else {
?>
            $("#child_popup_container").height(460);
            $("#child_popup_container").width(480);
            $("#child_popup_layer").height(480);
            $("#child_popup_layer").width(500);
<?php
    }
?>
            $("#child_popup_layer").css("position", "fixed");
            center_child_popup();
            set_child_popup_content(content); 
            display_child_popup(300);
        });

        // Popup the window to load in a certificate
        $("#child_popup_container").on("paste", "#cert", function(e) {
            $("#caerror").hide();
            $("#caload").show();
            var elem = $(this);
            setTimeout(function() {
                var cert = elem.val();
                $.post("<?= $component_url ?>/ajax.php", { cmd: "getcertinfo", cert: cert }, function(data) {
                    $("#caload").hide();
                    if (data.error) {
                        $("#caerror").html('<img src="<?= theme_image("error_small.png") ?>"> '+data.message).show();
                    } else {
                        $("#cahost").val(data.certinfo.CN);
                        $("#add-ca-cert-popup").attr("disabled", false);
                    }
                }, "json");
            }, 200);
        });

        // Actually add the certificate when clicking the button
        $("#child_popup_container").on("click", "#add-ca-cert-popup", function(e) {
            $("#caerror").hide();
            $("#add-ca-cert-popup").attr("disabled", true);
            $("#cert").attr("disabled", true);
            var cert = $("#cert").val();
            $.post("<?= $component_url ?>/ajax.php", { cmd: "addcert", cert: cert }, function(data) {
                if (data.error) {
                    $("#caerror").html('<img src="<?= theme_image("error_small.png") ?>"> '+data.message).show();
                    $("#add-ca-cert-popup").attr("disabled", false);
                    $("#cert").attr("disabled", false);
                } else {
                    close_child_popup();
                    load_certificates();
                }
            }, "json");
        });

        // Remove a certificate from the list/on the filesystem
        $("#cert-list").on("click", ".remove", function(e) {
            var cert_id = $(this).data("certid");
            $.post("<?= $component_url ?>/ajax.php", { cmd: "delcert", cert_id: cert_id }, function(data) {
                load_certificates();
            }, "json");
        });

        // Change the options for an LDAP server vs Active Directory
        $("#conn_method").click(function() {
            var type = $(this).val();
            $('.option').hide();
            if (type == "ad") {
                // Show AD options
                $(".ad-options").show();
            } else {
                // Show the LDAP options
                $(".ldap-options").show();
            }
        });

        $("#add-auth-server").click(function() {
            clear_server_form();
            $('#cmd').val('addserver');
            $("#manage-servers").show();
        });

        $("#cancel-add").click(function() {
            $("#manage-servers").hide();
            $(".message").remove();
        });

        $(".edit").click(function() {
            var server_id = $(this).data('id');

            // Get server information
            $.post('<?= $component_url ?>/ajax.php', { cmd: 'getserver', server_id: server_id }, function(server) {
                if (server != '') {

                    clear_server_form();

                    // Set all server information into correct spots
                    if (server.enabled == 1) {
                        $('input[name="enabled"]').attr('checked', true);
                    } else {
                        $('input[name="enabled"]').attr('checked', false);
                    }
                    $('#conn_method').val(server.conn_method).trigger('click');
                    $('#security_level').val(server.security_level);
                    $('#base_dn').val(server.base_dn);
                    if (server.conn_method == "ldap") {
                        $('#ldap_host').val(server.ldap_host);
                        $('#ldap_port').val(server.ldap_port);
                    } else {
                        $('#account_suffix').val(server.ad_account_suffix);
                        $('#domain_controllers').val(server.ad_domain_controllers);
                    }

                    $('#cmd').val('editserver');
                    $('#server_id').val(server_id);
                    $("#manage-servers").show();
                }
            }, 'json');
            
        });

        $('#show-advanced-options').click(function () {

            var advancedOptionsChevron = document.getElementById('advanced-options-chevron');

            if (advancedOptionsChevron.textContent === 'chevron_right') {
                advancedOptionsChevron.textContent = 'expand_more';
            } else {
                advancedOptionsChevron.textContent = 'chevron_right';
            }

            $('.advanced-options').toggle();

            // Scroll first invalid element into view.
            // TODO: Something is snapping the page back to the top, after this scrolls.
            var advancedOption = $('.advanced-options');

            if (advancedOption != null) {
                $(advancedOption)[0].scrollIntoView({behavior: "smooth", block: "center", inline: "nearest"});
            }
        });

        $('#ad-ldap-debugging').change(function () {
            $.post("<?= $component_url ?>/ajax.php", { cmd: "setdebugging", value: this.checked }, function(data) {
                if ( data.status == 'error') {
                    flash_message(data.error, error, true);
                } else {
                    if (data.new_value == 'off') {
                        flash_message("<?= _('AD/LDAP debugging has been disabled.') ?>", 'success', true);
                    } else {
                        flash_message("<?= _('AD/LDAP debugging has been enabled.') ?>", 'success', true);
                    }
                }
            }, "json");
        });

    });

    function clear_server_form() {
        $('input[name="enabled"]').attr('checked', true);
        $('#conn_method').val('ad').trigger('click');
        $('#security_level').val('none');
        $('#base_dn').val('');
        $('#ldap_host').val('');
        $('#ldap_port').val('');
        $('#account_suffix').val('');
        $('#domain_controllers').val('');
        $('#server_id').val('');
    }

    // Loads a list of certificates from on the system
    function load_certificates() {
        var html = "";
        $.post("<?= $component_url ?>/ajax.php", { cmd: "getcerts" }, function(data) {
            if (data.length > 0) {
                $.each(data, function(k, v) {
                    var time = '';
                    if (v.valid_to == -1) {
                        time = "<?= _('Unknown') ?>";
                    } else {
                        var to = new Date(v.valid_to*1000);
                        time = to.toString();
                    }
                    html += '<tr><td><span title="ID: '+v.id+'">'+v.host+'</span></td><td>'+v.issuer+'</td><td>'+time+'</td><td style="text-align: center;"><a class="remove tt-bind" title="<?= encode_form_valq(_("Remove certificate")) ?>" data-certid="'+v.id+'"><i class="material-symbols-outlined md-button md-action md-400 md-20 md-middle">delete</i></a></td></tr>';
                });
            } else {
                html += '<tr><td colspan="9"><?= encode_form_valq(_("You have not added any CA certificates through the web interface")) ?></td></tr>';
            }
            $("#cert-list").html(html);
        }, "json");
    }
    </script>

    <?php
    do_page_end(true);
}

// Process the form for adding a new server...
function process_add_server()
{
    // Verify we have all the required info (based on LDAP or AD)
    $error = "";
    $enabled = grab_request_var("enabled", "");
    if (!empty($enabled)) { $enabled = 1; }

    $conn_method = grab_request_var("conn_method", "");
    $base_dn = grab_request_var("base_dn", "");
    $security_level = grab_request_var("security_level", "none");

    // AD Only
    $account_suffix = grab_request_var("account_suffix", "");
    $domain_controllers = grab_request_var("domain_controllers", "");

    // LDAP Only
    $ldap_host = grab_request_var("ldap_host", "");
    $ldap_port = grab_request_var("ldap_port", "");

    if ($conn_method == "ad") {

        // Verify AD sections required
        if (empty($account_suffix) || empty($base_dn) || empty($domain_controllers) || empty($security_level)) {
            $error = _("You must fill out all the Active Directory server information.");
        }

    } else if ($conn_method == "ldap") {

        // Verify LDAP sections required
        if (empty($ldap_host) || empty($ldap_port) || empty($base_dn) || empty($security_level)) {
            $error = _("You must fill out all the LDAP server information.");
        }

    } else {
        // Not a recognized connection method...
        $error = _("Unknown connection method specified.");
    }

    // Quit if error exists
    if (!empty($error)) {
        display_page(true, $error);
        exit;
    }

    $server = array("id" => uniqid(),
                    "enabled" => $enabled,
                    "conn_method" => $conn_method,
                    "ad_account_suffix" => $account_suffix,
                    "ad_domain_controllers" => $domain_controllers,
                    "base_dn" => $base_dn,
                    "security_level" => $security_level,
                    "ldap_port" => $ldap_port,
                    "ldap_host" => $ldap_host);

    // Add server
    auth_server_add($server);

    // Success... display normal page
    display_page();
}

function process_edit_server()
{
    $server_id = grab_request_var('server_id', '');

    // Verify we have all the required info (based on LDAP or AD)
    $error = "";
    $enabled = grab_request_var("enabled", "");
    if (!empty($enabled)) { $enabled = 1; }

    $conn_method = grab_request_var("conn_method", "");
    $base_dn = grab_request_var("base_dn", "");
    $security_level = grab_request_var("security_level", "none");

    // AD Only
    $account_suffix = grab_request_var("account_suffix", "");
    $domain_controllers = grab_request_var("domain_controllers", "");

    // LDAP Only
    $ldap_host = grab_request_var("ldap_host", "");
    $ldap_port = grab_request_var("ldap_port", "");

    if ($conn_method == "ad") {

        // Verify AD sections required
        if (empty($account_suffix) || empty($base_dn) || empty($domain_controllers) || empty($security_level)) {
            $error = _("You must fill out all the Active Directory server information.");
        }

    } else if ($conn_method == "ldap") {

        // Verify LDAP sections required
        if (empty($ldap_host) || empty($ldap_port) || empty($base_dn) || empty($security_level)) {
            $error = _("You must fill out all the LDAP server information.");
        }

    } else {
        // Not a recognized connection method...
        $error = _("Unknown connection method specified.");
    }

    // Quit if error exists
    if (!empty($error)) {
        display_page(true, $error);
        exit;
    }

    // Update the server
    $server_new = array("enabled" => $enabled,
                        "conn_method" => $conn_method,
                        "ad_account_suffix" => $account_suffix,
                        "ad_domain_controllers" => $domain_controllers,
                        "base_dn" => $base_dn,
                        "security_level" => $security_level,
                        "ldap_port" => $ldap_port,
                        "ldap_host" => $ldap_host);

    // Auth server update
    auth_server_update($server_id, $server_new);

    // Success... display normal page
    display_page();
}

function process_delete_server()
{
    $server_id = grab_request_var('server_id', '');
    if (empty($server_id)) {
        $error = _("Must select a current server to remove.");
    }

    // Quit if error exists
    if (!empty($error)) {
        display_page(true, $error);
        exit;
    }

    // Remove auth server
    auth_server_remove($server_id);

    // Success... display normal page
    display_page();
}