<?php
// Require common utilities and functions required to run Nagios XI
require_once(dirname(__FILE__) . '/../includes/common.inc.php');
  
// Initialization stuff
pre_init();
init_session(); // Connect to databases, handle cookies/session, set page headers
  
// Grab GET or POST variables and check pre-reqs
grab_request_vars();
check_prereqs();
check_authentication();

route_to_neptune();

// Start the page
do_page_start(array("page_title" => _('Enterprise')), true);

$base_url = get_base_url();

$enterprise_cards = array(
    array (
        "title" => _('Audit Log'),
        "body" => _("Track your changes for policy compliance."),
        "icon" => 'shield',
        "link" => $base_url . "admin/?xiwindow=auditlog.php",
        "target" => "_top"
    ),
    array (
        "title" => _('BPI'),
        "body" => _("One-click synchronization with Business Process Intelligence groups."),
        "icon" => 'work',
        "link" => $base_url . "?xiwindow=" . $base_url . "/includes/components/nagiosbpi/index.php",
        "target" => "_top"
    ),
    array (
        "title" => _('Bulk Modification Tool'),
        "body" => _("Modify attributes for numerous hosts and services."),
        "icon" => 'widgets',
        "link" => $base_url . "includes/components/bulkmodifications/index.php",
        "target" => "maincontentframe"
    ),
    array (
        "title" => _('Bulk Renaming Tool'),
        "body" => _("Allows bulk updates of host and service names, keeping all past status and performance data."),
        "icon" => 'drive_file_rename_outline',
        "link" => $base_url . "includes/components/rename/rename.php",
        "target" => "maincontentframe"
    ),
    array (
        "title" => _('Capacity Planning'),
        "body" => _("Visualize and alert performance prediction for upgrade planning."),
        "icon" => 'show_chart',
        "link" => $base_url . "reports/?xiwindow=" . $base_url . "includes/components/capacityplanning/capacityplanning.php",
        "target" => "_top"
    ),
    array (
        "title" => _('Deadpool Settings'),
        "body" => _("Automates cleanup, improves monitoring, and reduces false alarms."),
        "icon" => 'monitor_heart',
        "link" => $base_url . "admin/?xiwindow=deadpool.php",
        "target" => "_top"
    ),
    array (
        "title" => _('Notification Management'),
        "body" => _("Save and deploy notification settings for selected users or contact groups."),
        "icon" => 'notifications_active',
        "link" => $base_url . "admin/?xiwindow=" . $base_url . "includes/components/deploynotification/deploynotification.php",
        "target" => "_top"
    ),
    array (
        "title" => _('Scheduled Pages'),
        "body" => _("Automate PDF email delivery of hosts, services, groups or XI pages with the Schedule Pages feature."),
        "icon" => 'schedule',
        "link" => "https://answerhub.nagios.com/support/s/article/Scheduling-Reports-in-Nagios-XI-2024-5cd954e4",
        "is_external" => true,
        "target" => "_blank"
    ),
    array (
        "title" => _('Scheduled Reports'),
        "body" => _("Access all scheduled reports, sorted by user."),
        "icon" => 'flag',
        "link" => $base_url . "reports/?xiwindow=" . $base_url . "includes/components/scheduledreporting/manage.php",
        "target" => "_top"
    ),
    array (
        "title" => _('Scheduled Reports History'),
        "body" => _("View all scheduled reports history, filtered by user."),
        "icon" => 'history',
        "link" => $base_url . "reports/?xiwindow=" . $base_url . "includes/components/scheduledreporting/history.php",
        "target" => "_top"
    ),
    array (
        "title" => _('SLA Report'),
        "body" => _("Simplifies network performance tracking and compliance through scheduled reports."),
        "icon" => 'handshake',
        "link" => $base_url . "reports/?xiwindow=sla.php",
        "target" => "_top"
    ),
    array (
        "title" => _('SNMP Trap Interface'),
        "body" => _("Configure and view SNMP traps within XI."),
        "icon" => 'email',
        "link" => $base_url . "admin/?xiwindow=" . $base_url . "includes/components/nxti/index.php",
        "target" => "_top"
    ),
);
?>
    <h1><?php echo _('Welcome To Enterprise!'); ?></h1>
    <p class="neptune-subtext"><?php echo _('Explore our comprehensive enterprise features with a single click.'); ?></p>
    
        <div class="row enterprise-page-row">
            <div class="col-xs-12 col-sm-6 col-lg-3">
                <div class="enterprise-card">
                    <a href="<?php echo $base_url ?>admin/?xiwindow=auditlog.php" target="_top" class="enterprise-anchor">
                        <div class="enterprise-text-container">
                            <h5 class="enterprise-title"><?php echo _('Audit Log'); ?></h5>
                            <p class="enterprise-body"><?php echo _("Track your changes for policy compliance.")?></p>
                        </div>
                        <div class="enterprise-icon-container">
                            <i class="fa fa-shield fa-2x" aria-hidden="true"></i>
                        </div>
                    </a>
                </div>
            </div>
            <div class="col-xs-12 col-sm-6 col-lg-3">
                <div class="enterprise-card">
                    <a href="<?php echo $base_url ?>?xiwindow=<?php echo $base_url ?>/includes/components/nagiosbpi/index.php" target="_top" class="enterprise-anchor">
                        <div class="enterprise-text-container">
                            <h5 class="enterprise-title"><?php echo _('BPI'); ?></h5>
                            <p class="enterprise-body"><?php echo _("One-click synchronization with Business Process Intelligence groups.")?></p>
                        </div>
                        <div class="enterprise-icon-container">
                            <i class="fa fa-briefcase fa-2x" aria-hidden="true"></i>
                        </div>
                    </a>
                </div>
            </div>
            <div class="col-xs-12 col-sm-6 col-lg-3">
                <div class="enterprise-card">
                    <a  href="<?php echo $base_url ?>includes/components/bulkmodifications/index.php" class="enterprise-anchor">
                        <div class="enterprise-text-container">
                            <h5 class="enterprise-title"><?php echo _('Bulk Modification Tool'); ?></h5>
                            <p class="enterprise-body"><?php echo _("Modify attributes for numerous hosts and services.")?></p>
                        </div>
                        <div class="enterprise-icon-container">
                            <i class="fa fa-th-large fa-2x" aria-hidden="true"></i>
                        </div>
                    </a>
                </div>
            </div>
            <div class="col-xs-12 col-sm-6 col-lg-3">
                <div class="enterprise-card">
                    <a  href="<?php echo $base_url ?>includes/components/rename/rename.php" class="enterprise-anchor">
                        <div class="enterprise-text-container">
                            <h5 class="enterprise-title"><?php echo _('Bulk Renaming Tool'); ?></h5>
                            <p class="enterprise-body"><?php echo _("Allows bulk updates of host and service names, keeping all past status and performance data.")?></p>
                        </div>
                        <div class="enterprise-icon-container">
                            <i class="fa fa-tags fa-2x" aria-hidden="true"></i>
                        </div>
                    </a>
                </div>
            </div>
            <div class="col-xs-12 col-sm-6 col-lg-3">
                <div class="enterprise-card">
                    <a href="<?php echo $base_url ?>reports/?xiwindow=<?php echo $base_url ?>includes/components/capacityplanning/capacityplanning.php" target="_top" class="enterprise-anchor">
                        <div class="enterprise-text-container">
                            <h5 class="enterprise-title"><?php echo _('Capacity Planning'); ?></h5>
                            <p class="enterprise-body"><?php echo _("Visualize and alert performance prediction for upgrade planning.")?></p>
                        </div>
                        <div class="enterprise-icon-container">
                            <i class="fa fa-area-chart fa-2x" aria-hidden="true"></i>
                        </div>
                    </a>
                </div>
            </div>
            <div class="col-xs-12 col-sm-6 col-lg-3">
                <div class="enterprise-card">
                    <a href="<?php echo $base_url ?>admin/?xiwindow=deadpool.php" target="_top" class="enterprise-anchor">
                        <div class="enterprise-text-container">
                            <h5 class="enterprise-title"><?php echo _('Deadpool Settings'); ?></h5>
                            <p class="enterprise-body"><?php echo _("Automates cleanup, improves monitoring, and reduces false alarms.")?></p>
                        </div>
                        <div class="enterprise-icon-container">
                            <i class="fa fa-heartbeat fa-2x" aria-hidden="true"></i>
                        </div>
                    </a>
                </div>
            </div>
            <div class="col-xs-12 col-sm-6 col-lg-3">
                <div class="enterprise-card">
                    <a href="<?php echo $base_url ?>admin/?xiwindow=<?php echo $base_url ?>includes/components/deploynotification/deploynotification.php" target="_top" class="enterprise-anchor">
                        <div class="enterprise-text-container">
                            <h5 class="enterprise-title"><?php echo _('Notification Management'); ?></h5>
                            <p class="enterprise-body"><?php echo _("Save and deploy notification settings for selected users or contact groups.")?></p>
                        </div>
                        <div class="enterprise-icon-container">
                            <i class="fa fa-bell fa-2x" aria-hidden="true"></i>
                        </div>
                    </a>
                </div>
            </div>
            <div class="col-xs-12 col-sm-6 col-lg-3">
                <div class="enterprise-card">
                    <a href="https://answerhub.nagios.com/support/s/article/Scheduling-Reports-in-Nagios-XI-2024-5cd954e4" target="_blank" rel="nofollow" class="enterprise-anchor">
                        <div class="enterprise-text-container">
                            <h5 class="enterprise-title"><?php echo _('Scheduled Pages'); ?> <i class="fa fa-external-link enterprise-sub-icon" aria-hidden="true"></i></h5>
                            <p class="enterprise-body"><?php echo _("Automate PDF email delivery of hosts, services, groups or XI pages with the Schedule Pages feature.")?>
                        </div>
                        <div class="enterprise-icon-container">
                            <i class="fa fa-clock-o fa-2x" aria-hidden="true"></i>
                        </div>
                    </a>
                </div>
            </div>
            <div class="col-xs-12 col-sm-6 col-lg-3">
                <div class="enterprise-card">
                    <a href="<?php echo $base_url ?>reports/?xiwindow=<?php echo $base_url ?>includes/components/scheduledreporting/manage.php" target="_top" class="enterprise-anchor">
                        <div class="enterprise-text-container">
                            <h5 class="enterprise-title"><?php echo _('Scheduled Reports'); ?></h5>
                            <p class="enterprise-body"><?php echo _("Access all scheduled reports, sorted by user.")?></p>
                        </div>
                        <div class="enterprise-icon-container">
                            <i class="fa fa-flag fa-2x" aria-hidden="true"></i>
                        </div>
                    </a>
                </div>
            </div>
            <div class="col-xs-12 col-sm-6 col-lg-3">
                <div class="enterprise-card">
                    <a href="<?php echo $base_url ?>reports/?xiwindow=<?php echo $base_url ?>includes/components/scheduledreporting/history.php" target="_top" class="enterprise-anchor">
                        <div class="enterprise-text-container">
                            <h5 class="enterprise-title"><?php echo _('Scheduled Reports History'); ?></h5>
                            <p class="enterprise-body"><?php echo _("View all scheduled reports history, filtered by user.")?></p>
                        </div>
                        <div class="enterprise-icon-container">
                            <i class="fa fa-history fa-2x" aria-hidden="true"></i>
                        </div>
                    </a>
                </div>
            </div>
            <div class="col-xs-12 col-sm-6 col-lg-3">
                <div class="enterprise-card">
                    <a href="<?php echo $base_url ?>reports/?xiwindow=sla.php" target="_top" class="enterprise-anchor">
                        <div class="enterprise-text-container">
                            <h5 class="enterprise-title"><?php echo _('SLA Report'); ?></h5>
                            <p class="enterprise-body"><?php echo _("Simplifies network performance tracking and compliance through scheduled reports.")?></p>
                        </div>
                        <div class="enterprise-icon-container">
                            <i class="fa fa-handshake-o fa-2x" aria-hidden="true"></i>
                        </div>
                    </a>
                </div>
            </div>
            <div class="col-xs-12 col-sm-6 col-lg-3">
                <div class="enterprise-card">
                    <a href="<?php echo $base_url ?>admin/?xiwindow=<?php echo $base_url ?>includes/components/nxti/index.php" target="_top" class="enterprise-anchor">
                        <div class="enterprise-text-container">
                            <h5 class="enterprise-title"><?php echo _('SNMP Trap Interface'); ?></h5>
                            <p class="enterprise-body"><?php echo _("Configure and view SNMP traps within XI.")?></p>
                        </div>
                        <div class="enterprise-icon-container">
                            <i class="fa fa-envelope fa-2x" aria-hidden="true"></i>
                        </div>
                    </a>
                </div>
            </div>
            <?php $xisys = $cfg['root_dir'] . '/var/xi-sys.cfg';
                    $ini = parse_ini_file($xisys);
                    if ($ini['dist'] != "el9") { ?>
            <div class="col-xs-12 col-sm-6 col-lg-3">
                <div class="enterprise-card">
                    <a href="<?php echo $base_url ?>admin/?xiwindow=<?php echo $base_url ?>admin/sshterm.php" target="_top" class="enterprise-anchor">
                        <div class="enterprise-text-container">
                            <h5 class="enterprise-title"><?php echo _('_SSH Terminal'); ?></h5>
                            <p class="enterprise-body"><?php echo _("Allows you to connect to your XI system using SSH.")?></p>
                        </div>
                        <div class="enterprise-icon-container">
                            <i class="fa fa-terminal fa-2x" aria-hidden="true"></i>
                        </div>
                    </a>
                </div>
            </div>
            <?php } ?>
        </div>
        
        <!--If Enterprise is active, show applicable resources. Otherwise show marketing info-->
        <?php if (enterprise_features_enabled() == true) { ?>
            <div class="enterprise-info-column">
                <h5 class="ul"><?php echo _('Learn More'); ?></h5>
                <ul>
                    <li>
                        <a href="https://www.youtube.com/watch?v=oZjuq1KF2mA&list=PLN-ryIrpC_mDs6LFnWgkSqtsS_g_h_afG" target="_blank"><strong><?php echo _("Enterprise Features"); ?></strong></a>
                        <br><?php echo _("Watch a playlist of our Enterprise Features and more."); ?>
                    </li>
                    <li>
                        <a href="https://www.nagios.com/products/nagios-xi/edition-comparison/" target="_blank"><strong><?php echo _("Standard vs Enterprise"); ?></strong></a>
                        <br><?php echo _("Learn the difference between Standard and Enterprise Edition."); ?>
                    </li>
                    <li>
                        <a href="https://www.nagios.com/contact/" target="_blank"><strong><?php echo _("Contact Us"); ?></strong></a>
                        <br><?php echo _("Got more specific Enterprise questions? Ask Us!"); ?>
                    </li>
            </div>
        <?php } else { ?>
            <div class="enterprisefeaturenotice maincontent">
                This feature is part of the Enterprise Edition of Nagios XI. Your trial of this feature has expired. Some functionality may be limited or disabled. 
                <a href="/nagiosxi/admin/?xiwindow=license.php" target="_top">Enter your enterprise key</a> 
                or 
                <a href="https://go.nagios.com/xi-enterprise-upgrade" target="_blank">learn more</a> 
                about upgrading to Enterprise Edition.
            </div>
            <div class="enterprise-info-column">
                <h5 class="ul"><?php echo _('What is Enterprise?'); ?></h5>
                <ul>
                    <li>
                        <a href="https://www.nagios.com/products/nagios-xi/edition-comparison/" target="_blank"><strong><?php echo _("Standard vs Enterprise"); ?></strong></a>
                        <br><?php echo _("Learn the difference between Standard and Enterprise Edition."); ?>
                    </li>
                    <li>
                        <a href="https://www.nagios.com/case-studies/" target="_blank"><strong><?php echo _("Testimonials"); ?></strong></a>
                        <br><?php echo _("Checkout our case-studies and the impact we've made."); ?>
                    </li>
                    <li>
                        <a href="https://www.nagios.com/contact/" target="_blank"><strong><?php echo _("Contact Us"); ?></strong></a>
                        <br><?php echo _("Got more specific Enterprise questions? Contact Us!"); ?>
                    </li>
                </ul>
            </div>
        <?php } ?>

<!-- End the page -->
<?php do_page_end(true); ?>