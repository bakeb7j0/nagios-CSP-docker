<?php
//
// Default "Home Dashboard" on user login.
// Copyright (c) 2008-2020 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/common.inc.php');
include_once(dirname(__FILE__) . '/components/xicore/ajaxhelpers-status.inc.php');

// Initialization stuff
pre_init();
init_session();

// Grab GET or POST variables and check prereqs/auth
grab_request_vars();
check_prereqs();
check_authentication(false);

do_page();

function do_page() {
    $page_title = "Nagios XI";
    do_page_start(array("page_title" => $page_title), true);

    // Decide what to show for trial chip
    $trial_days_remaining = get_trial_days_left();
    $trial_background_class = "ok-background";
    $trial_text_class = "white-text";

    if ($trial_days_remaining == 0) {
        $trial_background_class = "critical-background";
    } else if ( $trial_days_remaining < 7) {
        $trial_background_class = "warning-background";
        $trial_text_class = "dark-text";
    }

?>

    <script>
        // Enable paid support card popovers
        $(function () {
            $('[data-toggle="popover"]').popover()
        })
    </script>

<?php if (is_neptune()): ?>

    <h1 class="home-title"><?php echo _('Home Dashboard'); ?> <a href="/nagiosxi/includes/components/homepagemod/useropts.php" class="tt-bind" data-placement="right" style="font-size: 16px;" title="<?php echo _('Change my default home page'); ?>"><span class="material-symbols-outlined neptune-icon-md-btn">settings</span></a></h1>
    <!-- Overview and Help Section -->
    <div style='margin-bottom:-12px;' class="container-fluid no-padding">
        <div class="row splash-page-row mb">
                <div style="width:100%">
                    <div style="width:100%;">
                        <?php echo xicore_ajax_get_host_and_service_status_summary_dashboard_html(); ?>
                    </div>
                </div>
                </div>
         </div>
        <!-- End Overview and Help Section -->
        <!-- Trial Resources Section -->
        <?php if (is_trial_license()) { ?>
        <div class="mt-4 mb-4" id="trial-title-row">
            <h3 id="trial-title"><?php echo _("Trial Resources"); ?></h3>
            <span id="days-remaining" class="<?php echo $trial_background_class . ' ' . $trial_text_class; ?>"><b><?php echo $trial_days_remaining; ?></b> Days Left in Trial</span>
        </div>
        <div class="row splash-page-row">
            <div class="col-sm-3 small-card-container">
                <div class="splash-card-small">
                    <a href="https://www.nagios.com/products/nagios-xi/#pricing" target="_blank" rel="noreferrer" class="small-card-anchor">
                        <span class="material-icons md-400 md-pointer" aria-hidden="true">attach_money</span>
                        <p><?php echo _("View Pricing"); ?><span class="material-icons md-18 support-external-link" aria-hidden="true">open_in_new</span></p>
                    </a>
                </div>
                <div class="splash-card-small">
                    <a href="https://www.nagios.com/products/nagios-xi/edition-comparison/" target="_blank" rel="noreferrer" class="small-card-anchor">
                        <span class="material-icons md-400 md-pointer" aria-hidden="true">format_list_bulleted</span>
                        <p><?php echo _("Compare Editions"); ?><span class="material-icons md-18 support-external-link" aria-hidden="true">open_in_new</span></p>
                    </a>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="splash-card">
                    <a href="https://www.nagios.com/events/webinars/" target="_blank" rel="noreferrer" class="splash-card-anchor">
                        <div class="splash-card-text-container">
                            <h5 class="splash-card-title"><?php echo _("Attend a Webinar"); ?><span class="material-icons md-18 support-external-link" aria-hidden="true">open_in_new</span></h5>
                            <p class="splash-card-body"><?php echo _("See the benefits of Nagios XI in real time, led by professional techinicians."); ?></p>
                        </div>
                        <div class="splash-card-icon-container">
                            <span class="material-icons md-400 md-pointer" aria-hidden="true">video_camera_back</span>
                        </div>
                    </a>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="splash-card">
                    <a href="https://www.nagios.com/services/quickstart/nagios-xi/" target="_blank" rel="noreferrer"  class="splash-card-anchor">
                        <div class="splash-card-text-container">
                            <h5 class="splash-card-title"><?php echo _("Request a Quickstart"); ?><span class="material-icons md-18 support-external-link" aria-hidden="true">open_in_new</span></h5>
                            <p class="splash-card-body"><?php echo _("Let a Nagios professional answer your questions and help customize your Nagios XI deployment."); ?></p>
                        </div>
                        <div class="splash-card-icon-container">
                            <span class="material-icons md-400 md-pointer" aria-hidden="true">rocket</span>
                        </div>
                    </a>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="splash-card">
                    <a href="https://www.nagios.com/request-demo/" target="_blank" rel="noreferrer"  class="splash-card-anchor">
                        <div class="splash-card-text-container">
                            <h5 class="splash-card-title"><?php echo _("Request a Demo"); ?><span class="material-icons md-18 support-external-link" aria-hidden="true">open_in_new</span></h5>
                            <p class="splash-card-body"><?php echo _("See what Nagios XI is capable of in a highly configured environment."); ?></p>
                        </div>
                        <div class="splash-card-icon-container">
                            <span class="material-icons md-400 md-pointer" aria-hidden="true">computer</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
        <?php } ?>
        <!-- End Trial Resources Section -->

        <!-- Getting Started Section -->
        <h3 class="splash-page-title"><?php echo _("Getting Started"); ?></h3>
        <span class="splash-page-title-subtext"><?php echo _("First steps any user can perform. Click a card to get started."); ?></span>
        <div class="row splash-page-row">
            <?php if (is_admin() || is_authorized_to_configure_objects()) { ?>
                <div class="col-sm-3">
                    <div class="splash-card">
                        <a href="<?php echo get_base_url() . "config/monitoringwizard.php"; ?>" class="splash-card-anchor">
                            <div class="splash-card-text-container">
                                <h5 class="splash-card-title"><?php echo _("Run a Wizard"); ?></h5>
                                <p class="splash-card-body"><?php echo _("Start monitoring quickly with easy-to-use Configuration Wizards."); ?></p>
                            </div>
                            <div class="splash-card-icon-container">
                            <span class="material-icons md-400 md-pointer" aria-hidden="true">auto_fix_high</span>
                            </div>
                        </a>
                    </div>
                </div>
            <?php } ?>
            <div class="col-sm-3">
                <div class="splash-card">
                    <a href="<?php echo get_base_url() . "account/"; ?>" target="_top" class="splash-card-anchor">
                        <div class="splash-card-text-container">
                            <h5 class="splash-card-title"><?php echo _("Account Settings"); ?></h5>
                            <p class="splash-card-body"><?php echo _("Change your password and other general settings."); ?></p>
                        </div>
                        <div class="splash-card-icon-container">
                            <span class="material-icons md-400 md-pointer" aria-hidden="true">settings</span>
                        </div>
                    </a>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="splash-card">
                    <a href="<?php echo get_base_url() . "account?xiwindow=notifyprefs.php"; ?>" target="_top" class="splash-card-anchor">
                        <div class="splash-card-text-container">
                            <h5 class="splash-card-title"><?php echo _("Notifications"); ?></h5>
                            <p class="splash-card-body"><?php echo _("Change how and when you receive alert notifications."); ?></p>
                        </div>
                        <div class="splash-card-icon-container">
                            <span class="material-icons md-400 md-pointer" aria-hidden="true">notifications</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
        <!-- End Getting Started Section -->

        <!-- Administrative Tasks Section -->
        <?php if (is_admin()) { ?>
            <h3 class="splash-page-title"><?php echo _("Administrative Tasks"); ?></h3>
            <span class="splash-page-title-subtext"><?php echo _("Some first steps an administrator may take. Click a card to get started."); ?></span>
            <div class="row splash-page-row">
                <div class="col-sm-3">
                    <div class="splash-card">
                        <a href="<?php echo get_base_url() . "admin?xiwindow=users.php"; ?>" target="_top" class="splash-card-anchor">
                            <div class="splash-card-text-container">
                                <h5 class="splash-card-title"><?php echo _("Add a User"); ?></h5>
                                <p class="splash-card-body"><?php echo _("Setup new users with access to Nagios XI."); ?></p>
                            </div>
                            <div class="splash-card-icon-container">
                                <span class="material-icons md-400 md-pointer" aria-hidden="true">person_add_alt_1</span>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="splash-card">
                        <a href="<?php echo get_base_url() . "admin?xiwindow=mailsettings.php"; ?>" target="_top" class="splash-card-anchor">
                            <div class="splash-card-text-container">
                                <h5 class="splash-card-title"><?php echo _("Mail Settings"); ?></h5>
                                <p class="splash-card-body"><?php echo _("Configure email settings for your system."); ?></p>
                            </div>
                            <div class="splash-card-icon-container">
                                <span class="material-icons md-400 md-pointer" aria-hidden="true">mail</span>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="splash-card">
                        <a href="<?php echo get_base_url().'includes/components/ccm/xi-index.php'; ?>" target="_top" class="splash-card-anchor">
                            <div class="splash-card-text-container">
                                <h5 class="splash-card-title"><?php echo _("Explore the CCM"); ?></h5>
                                <p class="splash-card-body"><?php echo _("Fine-tune hosts & services in the Core Config Manager."); ?></p>
                            </div>
                            <div class="splash-card-icon-container">
                                <span class="material-icons md-400 md-pointer" aria-hidden="true">settings_suggest</span>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="splash-card">
                        <a href="<?php echo get_base_url().'?xiwindow=includes/components/autodiscovery/'; ?>" target="_top" class="splash-card-anchor">
                            <div class="splash-card-text-container">
                                <h5 class="splash-card-title"><?php echo _("Run Autodiscovery"); ?></h5>
                                <p class="splash-card-body"><?php echo _("Automatically discover hosts and services on your network that can be monitored."); ?></p>
                            </div>
                            <div class="splash-card-icon-container">
                                <span class="material-icons md-400 md-pointer" aria-hidden="true">device_hub</span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        <?php } ?>
        <!-- End Administrative Tasks Section -->

        <!-- Popular Wizards Section -->
        <?php if (is_authorized_to_configure_objects() && !is_readonly_user()) { ?>
        <h3 class="splash-page-title"><?php echo _("Popular Wizards"); ?></h3>
        <span class="splash-page-title-subtext"><?php echo _("Click a card to begin using one of our most popular "); ?><a href="<?php echo get_base_url() . "config/monitoringwizard.php"; ?>"><?php echo _("Configuration Wizards"); ?></a>.</span>
        <div class="row splash-page-row">
            <div class="col-sm-2">
                <div class="material-card wizard-card">
                    <a href="<?php echo get_base_url(); ?>/config/monitoringwizard.php?update=1&nextstep=2&nsp=<?php echo get_nagios_session_protector_id();?>&wizard=s3" class="wizard-card-anchor">
                        <img src="<?php echo wizard_logo("s3.png"); ?>"/>
                        <span><?php echo _("Amazon S3"); ?></span>
                    </a>
                </div>
            </div>
            <div class="col-sm-2">
                <div class="material-card wizard-card">
                    <a href="<?php echo get_base_url(); ?>/config/monitoringwizard.php?update=1&nextstep=2&nsp=<?php echo get_nagios_session_protector_id();?>&wizard=mysqlserver" class="wizard-card-anchor">
                        <img src="<?php echo wizard_logo("mysql.png"); ?>"/>
                        <span><?php echo _("MySQL Server"); ?></span>
                    </a>
                </div>
            </div>
            <div class="col-sm-2">
                <div class="material-card wizard-card">
                    <a href="<?php echo get_base_url(); ?>/config/monitoringwizard.php?update=1&nextstep=2&nsp=<?php echo get_nagios_session_protector_id();?>&wizard=google-cloud" class="wizard-card-anchor">
                        <img src="<?php echo wizard_logo("google-cloud.png"); ?>"/>
                        <span><?php echo _("Google Cloud"); ?></span>
                    </a>
                </div>
            </div>
            <div class="col-sm-2">
                <div class="material-card wizard-card">
                    <a href="<?php echo get_base_url(); ?>/config/monitoringwizard.php?update=1&nextstep=2&nsp=<?php echo get_nagios_session_protector_id();?>&wizard=ncpa" class="wizard-card-anchor">
                        <img src="<?php echo wizard_logo("ncpa.png"); ?>"/>
                        <span><?php echo _("NCPA"); ?></span>
                    </a>
                </div>
            </div>
            <div class="col-sm-2">
                <div class="material-card wizard-card">
                    <a href="<?php echo get_base_url(); ?>/config/monitoringwizard.php?update=1&nextstep=2&nsp=<?php echo get_nagios_session_protector_id();?>&wizard=docker" class="wizard-card-anchor">
                        <img src="<?php echo wizard_logo("docker.png"); ?>"/>
                        <span><?php echo _("Docker"); ?></span>
                    </a>
                </div>
            </div>
            <div class="col-sm-2">
                <div class="material-card wizard-card">
                    <a href="<?php echo get_base_url(); ?>/config/monitoringwizard.php?update=1&nextstep=2&nsp=<?php echo get_nagios_session_protector_id();?>&wizard=snmpwalk" class="wizard-card-anchor">
                        <img src="<?php echo wizard_logo("snmp.png"); ?>"/>
                        <span><?php echo _("SNMP Walk"); ?></span>
                    </a>
                </div>
            </div>
        </div>
        </div>
        <?php } ?>

<?php else: ?>
    <h1 class="home-title home-title-alignment"><?php echo _('Home Dashboard'); ?> <a href="/nagiosxi/includes/components/homepagemod/useropts.php" class="tt-bind" data-placement="right" style="font-size: 16px;" title="<?php echo _('Change my default home page'); ?>"><a href="/nagiosxi/includes/components/homepagemod/useropts.php" class="tt-bind" data-placement="right" style="font-size: 16px;" title="<?php echo _('Change my default home page'); ?>"><span class="material-symbols-outlined">settings</span></a></a></h1>
    <!-- Overview and Help Section -->
    <div class="container-fluid no-padding">
        <div class="row splash-page-row">
            <div class="col-sm-4">
                <div class="material-card splash-dashlet-card">
                    <?php display_dashlet("xicore_host_status_summary", "", null, DASHLET_MODE_OUTBOARD); ?>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="material-card splash-dashlet-card">
                    <?php display_dashlet("xicore_service_status_summary", "", null, DASHLET_MODE_OUTBOARD); ?>
                </div>
            </div>
            <div class="col-sm-4 splash-page-support-section">
                <h5 class="splash-card-title no-margin-bottom"><?php echo _("Need Help?"); ?></h5>
                <span class="splash-page-title-subtext"><?php echo _("Our knowledgeable techs and community are here to help."); ?></span>
                <div class="divider"></div>
                <div id="support-cards-container">
                    <div class="material-card support-card no-margin-left">
                        <a href="https://support.nagios.com/forum/viewforum.php?f=16" target="_blank" class="small-card-anchor">
                            <span class="material-icons md-400 md-pointer" aria-hidden="true">people</span>
                            <p><?php echo _("Support Forum"); ?><span class="material-icons md-18 support-external-link" aria-hidden="true">open_in_new</span></p>
                        </a>
                    </div>
                    <div class="material-card support-card no-margin-right">
                        <a href="<?php echo get_base_url(); ?>/help" target="_top" class="small-card-anchor">
                            <span class="material-icons md-400 md-pointer" aria-hidden="true">help</span>
                            <p><?php echo _("Help Resources"); ?><span class="material-icons md-18 support-external-link" aria-hidden="true">open_in_new</span></p>
                            
                        </a>
                    </div>
                    <div class="material-card support-card no-margin-left" data-toggle="popover" data-trigger="hover" data-placement="bottom" title="Paid resource" data-content="<?php echo _("Opening a ticket on the Nagios AnswerHub requires an active support entitlement package."); ?>">
                        <span class="paid-chip info-background" style="display: flex;"><span class="material-icons" aria-hidden="true" style="font-size: 18px;">attach_money</span></span>
                        <a href="https://answerhub.nagios.com/support/login" target="_blank" class="small-card-anchor">
                            <span class="material-icons md-400 md-pointer" aria-hidden="true">question_answer</span>
                            <p><?php echo _("Nagios AnswerHub"); ?><span class="material-icons md-18 support-external-link" aria-hidden="true">open_in_new</span></p>
                        </a>
                    </div>
                    <div class="material-card support-card no-margin-right" data-toggle="popover" data-trigger="hover" data-placement="bottom" title="Paid resource" data-content="<?php echo _("Requires an additional phone support plan."); ?>">
                        <span class="paid-chip info-background" style="display: flex;"><span class="material-icons" aria-hidden="true" style="font-size: 18px;">attach_money</span></span>
                        <a href="tel:+1-651-204-9102" class="small-card-anchor">
                            <span class="material-icons md-400 md-pointer" aria-hidden="true">phone</span>
                            <p style="padding: 1px;"><?php echo _("Phone Support"); ?></p>
                            <p id="contact-support-number"><?php echo _("+1 651-204-9102"); ?></p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Overview and Help Section -->

        <!-- Trial Resources Section -->
        <?php if (is_trial_license()) { ?>
        <div id="trial-title-row">
            <h3 id="trial-title"><?php echo _("Trial Resources"); ?></h3>
            <span id="days-remaining" class="<?php echo $trial_background_class . ' ' . $trial_text_class; ?>"><b><?php echo $trial_days_remaining; ?></b> Days Left in Trial</span>
        </div>
        <span class="splash-page-title-subtext"><?php echo _("Evaluating Nagios XI? Use the resources below for more information."); ?></span>
        <div class="row splash-page-row">
            <div class="col-sm-3 small-card-container">
                <div class="splash-card-small">
                    <a href="https://www.nagios.com/products/nagios-xi/#pricing" target="_blank" rel="noreferrer" class="small-card-anchor">
                        <span class="material-icons md-400 md-pointer" aria-hidden="true">attach_money</span>
                        <p><?php echo _("View Pricing"); ?><span class="material-icons md-18 support-external-link" aria-hidden="true">open_in_new</span></p>
                    </a>
                </div>
                <div class="splash-card-small">
                    <a href="https://www.nagios.com/products/nagios-xi/edition-comparison/" target="_blank" rel="noreferrer" class="small-card-anchor">
                        <span class="material-icons md-400 md-pointer" aria-hidden="true">format_list_bulleted</span>
                        <p><?php echo _("Compare Editions"); ?><span class="material-icons md-18 support-external-link" aria-hidden="true">open_in_new</span></p>
                    </a>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="splash-card">
                    <a href="https://www.nagios.com/events/webinars/" target="_blank" rel="noreferrer" class="splash-card-anchor">
                        <div class="splash-card-text-container">
                            <h5 class="splash-card-title"><?php echo _("Attend a Webinar"); ?><span class="material-icons md-18 support-external-link" aria-hidden="true">open_in_new</span></h5>
                            <p class="splash-card-body"><?php echo _("See the benefits of Nagios XI in real time, led by professional techinicians."); ?></p>
                        </div>
                        <div class="splash-card-icon-container">
                            <span class="material-icons md-400 md-pointer" aria-hidden="true">video_camera_back</span>
                        </div>
                    </a>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="splash-card">
                    <a href="https://www.nagios.com/services/quickstart/nagios-xi/" target="_blank" rel="noreferrer"  class="splash-card-anchor">
                        <div class="splash-card-text-container">
                            <h5 class="splash-card-title"><?php echo _("Request a Quickstart"); ?><span class="material-icons md-18 support-external-link" aria-hidden="true">open_in_new</span></h5>
                            <p class="splash-card-body"><?php echo _("Let a Nagios professional answer your questions and help customize your Nagios XI deployment."); ?></p>
                        </div>
                        <div class="splash-card-icon-container">
                            <span class="material-icons md-400 md-pointer" aria-hidden="true">rocket</span>
                        </div>
                    </a>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="splash-card">
                    <a href="https://www.nagios.com/request-demo/" target="_blank" rel="noreferrer"  class="splash-card-anchor">
                        <div class="splash-card-text-container">
                            <h5 class="splash-card-title"><?php echo _("Request a Demo"); ?><span class="material-icons md-18 support-external-link" aria-hidden="true">open_in_new</span></h5>
                            <p class="splash-card-body"><?php echo _("See what Nagios XI is capable of in a highly configured environment."); ?></p>
                        </div>
                        <div class="splash-card-icon-container">
                            <span class="material-icons md-400 md-pointer" aria-hidden="true">computer</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
        <?php } ?>
        <!-- End Trial Resources Section -->
        

        <!-- Getting Started Section -->
        <h3 class="splash-page-title"><?php echo _("Getting Started"); ?></h3>
        <span class="splash-page-title-subtext"><?php echo _("First steps any user can perform. Click a card to get started."); ?></span>
        <div class="row splash-page-row">
            <?php if (is_admin() || is_authorized_to_configure_objects()) { ?>
                <div class="col-sm-3">
                    <div class="splash-card">
                        <a href="<?php echo get_base_url() . "config/monitoringwizard.php"; ?>" class="splash-card-anchor">
                            <div class="splash-card-text-container">
                                <h5 class="splash-card-title"><?php echo _("Run a Wizard"); ?></h5>
                                <p class="splash-card-body"><?php echo _("Start monitoring quickly with easy-to-use Configuration Wizards."); ?></p>
                            </div>
                            <div class="splash-card-icon-container">
                            <span class="material-icons md-400 md-pointer" aria-hidden="true">auto_fix_high</span>
                            </div>
                        </a>
                    </div>
                </div>
            <?php } ?>
            <div class="col-sm-3">
                <div class="splash-card">
                    <a href="<?php echo get_base_url() . "account/"; ?>" target="_top" class="splash-card-anchor">
                        <div class="splash-card-text-container">
                            <h5 class="splash-card-title"><?php echo _("Account Settings"); ?></h5>
                            <p class="splash-card-body"><?php echo _("Change your password and other general settings."); ?></p>
                        </div>
                        <div class="splash-card-icon-container">
                            <span class="material-icons md-400 md-pointer" aria-hidden="true">settings</span>
                        </div>
                    </a>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="splash-card">
                    <a href="<?php echo get_base_url() . "account?xiwindow=notifyprefs.php"; ?>" target="_top" class="splash-card-anchor">
                        <div class="splash-card-text-container">
                            <h5 class="splash-card-title"><?php echo _("Notifications"); ?></h5>
                            <p class="splash-card-body"><?php echo _("Change how and when you receive alert notifications."); ?></p>
                        </div>
                        <div class="splash-card-icon-container">
                            <span class="material-icons md-400 md-pointer" aria-hidden="true">notifications</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
        <!-- End Getting Started Section -->

        <!-- Administrative Tasks Section -->
        <?php if (is_admin()) { ?>
            <h3 class="splash-page-title"><?php echo _("Administrative Tasks"); ?></h3>
            <span class="splash-page-title-subtext"><?php echo _("Some first steps an administrator may take. Click a card to get started."); ?></span>
            <div class="row splash-page-row">
                <div class="col-sm-3">
                    <div class="splash-card">
                        <a href="<?php echo get_base_url() . "admin?xiwindow=users.php"; ?>" target="_top" class="splash-card-anchor">
                            <div class="splash-card-text-container">
                                <h5 class="splash-card-title"><?php echo _("Add a User"); ?></h5>
                                <p class="splash-card-body"><?php echo _("Setup new users with access to Nagios XI."); ?></p>
                            </div>
                            <div class="splash-card-icon-container">
                                <span class="material-icons md-400 md-pointer" aria-hidden="true">person_add_alt_1</span>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="splash-card">
                        <a href="<?php echo get_base_url() . "admin?xiwindow=mailsettings.php"; ?>" target="_top" class="splash-card-anchor">
                            <div class="splash-card-text-container">
                                <h5 class="splash-card-title"><?php echo _("Mail Settings"); ?></h5>
                                <p class="splash-card-body"><?php echo _("Configure email settings for your system."); ?></p>
                            </div>
                            <div class="splash-card-icon-container">
                                <span class="material-icons md-400 md-pointer" aria-hidden="true">mail</span>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="splash-card">
                        <a href="<?php echo get_base_url().'includes/components/ccm/xi-index.php'; ?>" target="_top" class="splash-card-anchor">
                            <div class="splash-card-text-container">
                                <h5 class="splash-card-title"><?php echo _("Explore the CCM"); ?></h5>
                                <p class="splash-card-body"><?php echo _("Fine-tune hosts & services in the Core Config Manager."); ?></p>
                            </div>
                            <div class="splash-card-icon-container">
                                <span class="material-icons md-400 md-pointer" aria-hidden="true">settings</span>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="splash-card">
                        <a href="<?php echo get_base_url().'?xiwindow=includes/components/autodiscovery/'; ?>" target="_top" class="splash-card-anchor">
                            <div class="splash-card-text-container">
                                <h5 class="splash-card-title"><?php echo _("Run Autodiscovery"); ?></h5>
                                <p class="splash-card-body"><?php echo _("Automatically discover hosts and services on your network that can be monitored."); ?></p>
                            </div>
                            <div class="splash-card-icon-container">
                                <span class="material-icons md-400 md-pointer" aria-hidden="true">device_hub</span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        <?php } ?>
        <!-- End Administrative Tasks Section -->

        <!-- Popular Wizards Section -->
        <h3 class="splash-page-title"><?php echo _("Popular Wizards"); ?></h3>
        <span class="splash-page-title-subtext"><?php echo _("Click a card to begin using one of our most popular "); ?><a href="<?php echo get_base_url() . "config/monitoringwizard.php"; ?>"><?php echo _("Configuration Wizards"); ?></a>.</span>
        <div class="row splash-page-row">
            <div class="col-sm-2">
                <div class="material-card wizard-card">
                    <a href="<?php echo get_base_url(); ?>/config/monitoringwizard.php?update=1&nextstep=2&nsp=<?php echo get_nagios_session_protector_id();?>&wizard=s3" class="wizard-card-anchor">
                        <img src="<?php echo wizard_logo("s3.png"); ?>"/>
                        <span><?php echo _("Amazon S3"); ?></span>
                    </a>
                </div>
            </div>
            <div class="col-sm-2">
                <div class="material-card wizard-card">
                    <a href="<?php echo get_base_url(); ?>/config/monitoringwizard.php?update=1&nextstep=2&nsp=<?php echo get_nagios_session_protector_id();?>&wizard=mysqlserver" class="wizard-card-anchor">
                        <img src="<?php echo wizard_logo("mysql.png"); ?>"/>
                        <span><?php echo _("MySQL Server"); ?></span>
                    </a>
                </div>
            </div>
            <div class="col-sm-2">
                <div class="material-card wizard-card">
                    <a href="<?php echo get_base_url(); ?>/config/monitoringwizard.php?update=1&nextstep=2&nsp=<?php echo get_nagios_session_protector_id();?>&wizard=google-cloud" class="wizard-card-anchor">
                        <img src="<?php echo wizard_logo("google-cloud.png"); ?>"/>
                        <span><?php echo _("Google Cloud"); ?></span>
                    </a>
                </div>
            </div>
            <div class="col-sm-2">
                <div class="material-card wizard-card">
                    <a href="<?php echo get_base_url(); ?>/config/monitoringwizard.php?update=1&nextstep=2&nsp=<?php echo get_nagios_session_protector_id();?>&wizard=ncpa" class="wizard-card-anchor">
                        <img src="<?php echo wizard_logo("ncpa.png"); ?>"/>
                        <span><?php echo _("NCPA"); ?></span>
                    </a>
                </div>
            </div>
            <div class="col-sm-2">
                <div class="material-card wizard-card">
                    <a href="<?php echo get_base_url(); ?>/config/monitoringwizard.php?update=1&nextstep=2&nsp=<?php echo get_nagios_session_protector_id();?>&wizard=docker" class="wizard-card-anchor">
                        <img src="<?php echo wizard_logo("docker.png"); ?>"/>
                        <span><?php echo _("Docker"); ?></span>
                    </a>
                </div>
            </div>
            <div class="col-sm-2">
                <div class="material-card wizard-card">
                    <a href="<?php echo get_base_url(); ?>/config/monitoringwizard.php?update=1&nextstep=2&nsp=<?php echo get_nagios_session_protector_id();?>&wizard=snmpwalk" class="wizard-card-anchor">
                        <img src="<?php echo wizard_logo("snmp.png"); ?>"/>
                        <span><?php echo _("SNMP Walk"); ?></span>
                    </a>
                </div>
            </div>
        </div>
        <!-- End Popular Wizards Section -->
<?php endif; ?>

<?php
    do_page_end(true);
}