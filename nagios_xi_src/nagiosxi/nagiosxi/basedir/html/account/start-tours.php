<?php 
//
// Nagios XI API Documentation
// Copyright (c) 2023 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/../includes/common.inc.php');

// Initialization stuff
pre_init();
init_session();

// Grab GET or POST variables and check pre-reqs
grab_request_vars();
check_prereqs();
check_authentication();

route_request();

function route_request()
{
    $page = grab_request_var("page", "");

    switch ($page) {
        default:
            show_tours();
            break;
    }
}

function show_tours() {
    do_page_start(array("page_title" => _('Start Tours')), true);
    ?>

    <div class="">
        <h1><?php echo _("Reset tours"); ?></h1>
        <h5 class="neptune-form-spacer"><?php echo _("Page Tours"); ?></h5>
        <div class="neptune-subheading-break neptune-form-spacer"></div>
        <div class="btn-row">
            <button class="btn btn-primary" id="home-tour"><?php echo _("Introduction Tour"); ?></button>
    <?php if (is_authorized_to_configure_objects() && !is_readonly_user()) { ?>
            <button class="btn btn-primary" id="wizard-website-tour"><?php echo _("Website Wizard Tour"); ?></button>
        </div>
        <div class="neptune-section-spacer"></div>
        <h5 class="neptune-form-spacer mt-20"><?php echo _("Wizard Tours"); ?></h5>
        <div class="neptune-subheading-break neptune-form-spacer"></div>
        <button class="btn btn-primary" id="wizard-tour"><?php echo _("Wizards Selection Tour"); ?></button>
    <?php } else { ?>
        </div>
    <?php } ?>
        <script src="../includes/js/api-help.js"></script>
        <script>
            function tourTarget(tourName, step, windowTarget, target, callback = null) {
                ajaxSetStep(tourName, 0, result => {
                    if (result === "success") {
                        if (callback !== null) {
                            callback();
                        } else {
                            if (windowTarget !== null) {
                                windowTarget.location = target;
                            }
                        }
                    } else {
                        alert("<?php echo _("Error setting tour step"); ?>");
                    }
                });
            }


            $('#home-tour').click(function() {
                tourTarget("home", 0, top, window.location.origin + "/nagiosxi");
            });
            
            $('#wizard-tour').click(function() {
                tourTarget("wizard-landing", 0, null, null, () => {
                    tourTarget("wizard-landing", 0, top, window.location.origin + "/nagiosxi/config/?xiwindow=monitoringwizard.php");
                });
            });

            $('#wizard-website-tour').click(function() {
                tourTarget("wizard-website", 0, window, window.location.origin + "/nagiosxi/config/monitoringwizard.php?update=1&nextstep=2&nsp=<?= get_nagios_session_protector_id() ?>&wizard=website");
            });

        </script>
    </div>
    <?php
    do_page_end(true);
}