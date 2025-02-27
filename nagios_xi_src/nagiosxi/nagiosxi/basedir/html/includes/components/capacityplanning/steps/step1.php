    <script src="../includes/configwizards/capacity-planning/capacity-planning-page-1.inc.js"></script>
    <!--link rel="stylesheet" href="../includes/configwizards/capacity-planning/capacity-planning.inc.css"-->

<?php
    #include_once __DIR__.'/../../../utils-xi6-wizards.inc.php';
?>
    <div class="container-fluid m-0 g-0">
        <!--                         -->
        <!-- The configuration form. -->
        <!--                         -->
        <div id="configForm">
            <h2><?= _('Select Data to Monitor') ?></h2>

            <div class="hide" id="default-service-selector"><option value=""><?= _("Service") ?>:</option></div>
            <div class="hide" id="default-data-selector"><option value=""><?= _("Perfdata Name") ?>:</option></div>
            <div class="hide" id="default-remove-row"><img class="remove-row" src="<?= theme_image('cross.png') ?>" title="Remove this row"></div>
            <div class="hide" id="no-threshold-message"><?= _("N/A") ?></div>
            <div class="hide" id="threshold-message"><?= _("Alert if the threshold (%d) is reached within %s days.") ?></div>
            <div class="hide" id="needs-custom-value"><?= _('This plugin does not report a warning/critical threshold. Set capacity planning thresholds on the next page') ?></div>
            <div class="hide" id="unselected-error"><?= _('Please select a host, service, and perfdata name') ?></div>

            <div id="default-row" class="hide">
                <div class="d-flex flex-row mb-1">
                    <div class="flex-column px-0 min-width-14em">
                        <div class="input-group flex-nowrap">
                            <input type="hidden" name="serviceargs[counter_here][host]" value="host_here" />
                            <input type="hidden" name="serviceargs[counter_here][service]" value="service_here" />
                            <input type="hidden" name="serviceargs[counter_here][perfdata]" value="perfdata_here" />
                            needs_custom_input_here
                            <label class="input-group-text rounded-start"><?= _('Name') ?></label>
                            <label class="input-group-text min-width-40em rounded-0">host_here - service_here - perfdata_here</label>
                        </div>
                    </div>
                    <div class="flex-column px-0">
                        <div class="input-group flex-nowrap">
                            <input type="hidden" name="serviceargs[counter_here][warn]" value="warn_here" />
                            <label class="input-group-text"><i <?= xi6_title_tooltip(_('Warning Threshold')) ?> class="material-symbols-outlined md-warning md-18 md-400 md-middle">warning</i></label>
                            <input type="text" class="textfield form-control" name="serviceargs[counter_here][warn-days]" value="warn_days_here" title="needs_custom_value_here" warn_disabled_here/>
                            <label class="input-group-text">days</label>
                        </div>
                    </div>
                    <div class="flex-column flex-nowrap ps-0 pe-2">
                        <div class="input-group">
                            <input type="hidden" name="serviceargs[counter_here][crit]" value="crit_here" />
                            <label class="input-group-text"><img src="<?= theme_image('critical_small.png') ?>"></label>
                            <input type="text" class="textfield form-control" name="serviceargs[counter_here][crit-days]" value="crit_days_here" title="needs_custom_value_here" crit_disabled_here/>
                            <label class="input-group-text">days</label>
                        </div>
                    </div>
                    <div class="flex-column pt-1">
                        <img class="remove-row" src="<?= theme_image('cross.png') ?>" title="Remove this row">
                    </div>
                </div>
            </div>
            <div class="hide" id="no-entries-message">
                <div class="col-sm-3 remove-on-addition">
                    <div class="input-group input-group-sm">
                        <label class="input-group-text"><?= _('No selected entries yet') ?></label>
                    </div>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-sm-6">
                    <label for="host-selector" class="form-label form-item-required"> Host</label>
                    <div class="input-group position-relative">
                        <select name="host-selector" id="host-selector" class="form-select monitor rounded" placeholder="<?= _("Select ") ?> " required>
                            <option value="" disabled selected><?= _("Choose a host...") ?></option>
<?php
    // get_xml_host_objects() returns a reversed list... can't figure out the SQL mapping so we'll iterate in reverse.
    $x = 0;

    for ($x = count($print_these_hosts->host) - 1; $x >= 0; $x--) {
        $host = $print_these_hosts->host[$x];
?>
                            <option value="<?= $host->host_name ?>"><?= $host->host_name ?></option>
<?php
    }
?>
                        </select>
                        <div class="invalid-feedback">
                            <?= _("Please select a Host") ?>
                        </div>
                        <i id="host-selector_Alert" class="visually-hidden position-absolute top-0 start-100 translate-middle icon icon-circle color-ok icon-size-status"></i>
                    </div>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-sm-6">
                    <label for="host-selector" class="form-label form-item-required"><?= _('Service') ?></label>
                    <div class="input-group position-relative">

                        <select name="service-selector" id="service-selector" class="form-select monitor rounded" required>
                            <option value=""><?= _("Choose a service...") ?></option>
                        </select>

                        <div class="invalid-feedback">
                            <?= _("Please select a Service") ?>
                        </div>
                        <i id="host-selector_Alert" class="visually-hidden position-absolute top-0 start-100 translate-middle icon icon-circle color-ok icon-size-status"></i>
                    </div>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-sm-6">
                    <label for="host-selector" class="form-label form-item-required"><?= _('Perfdata Name') ?></label>
                    <div class="input-group position-relative">

                        <select name="data-selector" id="data-selector" class="form-select monitor rounded" required>
                            <option value=""><?= _("Choose Perfdata...") ?></option>
                        </select>

                        <div class="invalid-feedback">
                            <?= _("Please choose a type of Performance Data to Monitor") ?>
                        </div>
                        <i id="host-selector_Alert" class="visually-hidden position-absolute top-0 start-100 translate-middle icon icon-circle color-ok icon-size-status"></i>
                    </div>
                </div>
            </div>

            <div class="row mt-3 mb-4">
                <div class="col-sm-6">
                    <div class="input-group position-relative">
                        <button type="button" id="add-to-list" class="btn btn-sm btn-primary"><?= _("Add to Configuration") ?></button>
                    </div>
                </div>
            </div>


            <div id="configDiv" class="">
                <h2><?= _('Configuration') ?></h2>

            <div id="bucket-container" class="col-sm border-block mb-2 overflow-scroll">

<?php
    foreach ($serviceargs as $index => $row) {
        $needs_custom = (empty($row['warn']) || $row['warn'] === _("N/A")) && (empty($row['crit']) || $row['crit'] === _("N/A"));

        $warn_disabled = false;

        if ($row['warn'] === _("N/A") || $row['warn'] === '') {
            $warn_disabled = true;
        }

        $crit_disabled = false;

        if ($row['crit'] === _("N/A") || $row['crit'] === '') {
            $crit_disabled = true;
        }
?>
                <div id="table_insert_here" class="overflow-scroll">
                    <div class="d-flex flex-row g-1">
                        <div class="flex-column flex-nowrap">
                            <div class="input-group input-group-sm flex-nowrap">
                                <input type="hidden" name="serviceargs[<?= $index ?>][host]" value="<?= $row['host'] ?>" />
                                <input type="hidden" name="serviceargs[<?= $index ?>][service]" value="<?= $row['service'] ?>" />
                                <input type="hidden" name="serviceargs[<?= $index ?>][perfdata]" value="<?= $row['perfdata'] ?>" />
                                <?= ($needs_custom ? '<input type="hidden" name="serviceargs['. $index . '][need_custom]" value="1" />' : '') ?>
                                <label class="input-group-text rounded-start"><?= _('Name') ?></label>
                                <label class="input-group-text min-width-40em rounded-0"><?= $row['host'] ?> - <?= $row['service'] ?> - <?= $row['perfdata'] ?></label>
                            </div>
                        </div>
                        <div class="flex-column flex-nowrap">
                            <div class="input-group input-group-sm">
                                <input type="hidden" name="serviceargs[<?= $index ?>][warn]" value="<?= $row['warn'] ?>" />
                                <label class="input-group-text"><i <?= xi6_title_tooltip(_('Warning Threshold')) ?> class="material-symbols-outlined md-warning md-18 md-400 md-middle">warning</i></label>
                                <input type="text" class="textfield form-control" name="serviceargs[<?= $index ?>][warn-days]" value="<?= ($warn_disabled ? '' : $row['warn-days']) ?>" title="<?= ($warn_disabled ? sprintf(_("Alert if the threshold (%s) is reached within ___ days."), $row['warn']) : '') ?>" <?= ($warn_disabled ? 'disabled' : '') ?>/>
                                <label class="input-group-text">days</label>
                            </div>
                        </div>
                        <div class="flex-column flex-nowrap">
                            <div class="input-group input-group-sm">
                                <input type="hidden" name="serviceargs[<?= $index ?>][crit]" value="<?= $row['crit'] ?>" />
                                <label class="input-group-text"><img src="<?= theme_image('critical_small.png') ?>"></label>
                                <input type="text" class="textfield form-control" name="serviceargs[<?= $index ?>][crit-days]" value="<?= ($warn_disabled ? '' : $row['crit-days']) ?>" title="<?= ($crit_disabled ? sprintf(_("Alert if the threshold (%s) is reached within ___ days."), $row['crit']) : '') ?>" <?= ($crit_disabled ? 'disabled' : '') ?>/>
                                <label class="input-group-text">days</label>
                            </div>
                        </div>
                        <div class="flex-column flex-nowrap pt-1">
                            <img class="remove-row" src="<?= theme_image('cross.png') ?>" title="<?= _('Remove this row') ?>">
                        </div>
                    </div>
                </div>
<?php
    }

    if (empty($serviceargs)) {
?>    
                <div id="table_insert_here">
                    <div class="col-3 remove-on-addition">
                        <div class="input-group input-group">
                            <label class="input-group-text"><?= _('No entries have been added to the configuration') ?></label>
                        </div>
                    </div>
                </div>
<?php
    }
?>
            </div> <!-- configDiv -->
        </div> <!-- config -->
    </div> <!-- container -->

    <script type="text/javascript" src="<?= get_base_url() ?>includes/js/wizards-bs5.js?<?= get_build_id(); ?>"></script>
