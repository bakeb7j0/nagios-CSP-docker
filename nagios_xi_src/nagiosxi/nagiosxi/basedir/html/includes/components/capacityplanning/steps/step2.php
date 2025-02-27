    <div class="hide" id="bad-custom-value-msg"><?= _("The custom value could not be parsed as a number.") ?> </div>
    <div class="hide" id="bad-timeframe-lookahead-msg"><?= _("The alerting time is greater than the lookahead period.") ?> </div>
    <script src="../includes/components/capacityplanning/includes/capacityreport.js.php"></script>
    <script src="../includes/configwizards/capacity-planning/capacity-planning-page-2.inc.js"></script>
    <link rel="stylesheet" href="../includes/configwizards/capacity-planning/capacity-planning.inc.css">
    <h5 class="ul"><?= _('Advanced Configuration') ?></h5>

    <div id="advanced-wrapper">
        <div id="custom-host-grid">
            <div class="flex-center">
                <div class="input-group flex-v-align">
                    <input type="checkbox" id="enable_custom_host" name="enable_custom_host" style="margin-left: 7px; margin-right:5px" <?= is_checked($enable_custom_host) ?>/>
                    <label for="enable_custom_host" class="select-cf-option"><?= _('Put all new services on specific host') ?>:</label>
                </div>
            </div>
            <div class="">
                <div class="flex-v-align">
                    <input type="text" class="textfield form-control" id="custom_host_name" size="28" name="custom_host_name" style="margin-left: 24px" placeholder="<?= _("Host Name") ?>" value="<?= $custom_host_name ?>" <?= (is_checked($enable_custom_host) ? '': 'disabled') ?>>
                </div>
            </div>
        </div>
    </div>

    <!-- table header -->
    <div class="grid">
        <h6><?= _('Host Name') ?></h6>
        <h6><?= _('Service Description') ?></h6>
        <h6><?= _('Perfdata Name') ?></h6>
        <div class="flex-center">
            <i class="fa fa-chevron-down" id="toggle-all-chevron"></i>
        </div>
    </div>

<?php
    // $checkargs is indexed to match $serviceargs.
    foreach ($serviceargs as $index => $service) {
        $click_me = '';

        if (array_key_exists('need_custom', $service) && $service['need_custom'] === "1") {
            $click_me = 'click-me';
        }
?>    
    <div class="grid <?= $click_me ?>" data-service-id="<?= $index ?>">
        <p><?= $service['host'] ?></p>
        <p><?= $service['service'] ?></p>
        <p><?= $service['perfdata'] ?></p>
        <div class="flex-center">
            <i class="fa fa-chevron-down config-chevron" id="" data-service-id="<?= $index ?>"></i>
        </div>
    </div>
    <div class="metrics-grid" data-service-id="<?= $index ?>">
        <div class="metrics-grid-table">
            <div class="flex-center grid-width-6">
            </div>
            <div class="flex-center grid-width-3">
                <div class="input-group double-input">
                    <label class="input-group-addon tt-bind input-grouped" title="<?= _('The original warning and critical values for performance data on this check') ?>">
                        <?= _('Performance Data Thresholds') ?>
                    </label>
                    <label class="input-group-addon input-grouped">
                        <img src="<?= theme_image('warning_small.png') ?>" class="tt-bind" title="<?= _('Warning Threshold') ?>">
                    </label>
                    <input type="text" disabled class="textfield form-control input-grouped" value="<?= $service['warn'] ?>">
                    <label class="input-group-addon input-grouped">
                        <img src="<?= theme_image('critical_small.png') ?>" class="tt-bind" title="<?= _('Critical Threshold') ?>">
                    </label>
                    <input type="text" disabled class="textfield form-control input-grouped" value="<?= $service['crit'] ?>">
                </div>
            </div>
            <div class="flex-center grid-width-3">
                <div class="input-group double-input">
                    <label class="input-group-addon tt-bind input-grouped smaller" title="<?= _('The warning threshold for the capacity planning check') ?>">
                        <?= sprintf(_('Alert %s within'), '<img src="'.theme_image('warning_small.png').'" class="tt-bind input-grouped">') ?>
                    </label>
                    <input type="text" size="2" class="textfield form-control input-grouped" name="checkargs[<?= $index ?>][timeframe_warning]" value="<?= $checkargs[$index]['timeframe_warning'] ?>">
                    <label class="input-group-addon input-grouped">
                        <?= _('days of exceeding') ?>
                    </label>

                    <select name="checkargs[<?= $index ?>][threshold_warning]" class="form-control input-grouped">
<?php
    if ($service['crit'] !== _('N/A')) {
?>
                        <option value="critical" <?=  is_selected($checkargs[$index]['threshold_warning'], 'critical') ?>><?= _('CRITICAL Threshold') ?></option>
<?php
    }

    if ($service['warn'] !== _('N/A')) {
?>
                        <option value="warning"  <?=  is_selected($checkargs[$index]['threshold_warning'], 'warning') ?>><?= _('WARNING Threshold') ?></option>
<?php
    }
?>
                        <option value="custom" <?= is_selected($checkargs[$index]['threshold_warning'], 'custom') ?>><?= _('Custom Value') ?></option>
                    </select>
                    <label class="input-group-addon input-grouped">
                        <?= _('as') ?>
                    </label>
                    <select name="checkargs[<?= $index ?>][minmax_warning]" class="form-control input-grouped">
                        <option value="max" <?=  is_selected($checkargs[$index]['minmax_warning'], 'max') ?>><?= _('Maximum') ?></option>
                        <option value="min" <?=  is_selected($checkargs[$index]['minmax_warning'], 'min') ?>><?= _('Minimum') ?></option>
                    </select>
                </div>
            </div>
            <div class="flex-center grid-width-3">
                <div class="input-group double-input">
                    <label class="input-group-addon tt-bind input-grouped" title="">
                        <?= _("Lookahead") ?>
                    </label>
                    <input name="checkargs[<?= $index ?>][lookahead]" type="text" class="textfield form-control input-grouped" value="<?= $checkargs[$index]['lookahead'] ?>" size="4">
                    <label class="input-group-addon input-grouped">
                        <?= _('weeks using') ?>
                    </label>
                    <select name="checkargs[<?= $index ?>][method]" class="form-control input-grouped">
                        <option value="Holt-Winters"  <?=  is_selected($checkargs[$index]['method'], 'Holt-Winters') ?>><?= _("Holt-Winters") ?></option>
                        <option value="Linear Fit"    <?=  is_selected($checkargs[$index]['method'], 'Linear Fit') ?>><?= _("Linear Fit") ?></option>
                        <option value="Quadratic Fit" <?=  is_selected($checkargs[$index]['method'], 'Quadratic Fit') ?>><?= _("Quadratic Fit") ?></option>
                        <option value="Cubic Fit"     <?=  is_selected($checkargs[$index]['method'], 'Cubic Fit') ?>><?= _("Cubic Fit") ?></option>
                    </select>
                    <label class="input-group-addon tt-bind input-grouped" title="">
                        <?= _("algorithm") ?>
                    </label>
                </div>
            </div>
            <div class="flex-center grid-width-3">
                <div class="input-group double-input">
                    <label class="input-group-addon tt-bind input-grouped smaller" title="<?= _('The critical threshold for the capacity planning check') ?>">
                        <?= sprintf(_('Alert %s within'), '<img src="'.theme_image('critical_small.png').'" class="tt-bind input-grouped">') ?>
                    </label>
                    <input type="text" size="2" class="textfield form-control input-grouped" name="checkargs[<?= $index ?>][timeframe_critical]" value="<?= $checkargs[$index]['timeframe_critical'] ?>">
                    <label class="input-group-addon input-grouped">
                        <?= _('days of exceeding') ?>
                    </label>

                    <select name="checkargs[<?= $index ?>][threshold_critical]" class="form-control input-grouped">
<?php
        if ($service['crit'] !== _('N/A')) {
?>
                        <option value="critical" <?=  is_selected($checkargs[$index]['threshold_critical'], 'critical') ?>><?= _('CRITICAL Threshold') ?></option>
<?php
        }

        if ($service['warn'] !== _('N/A')) {
?>
                        <option value="warning"  <?=  is_selected($checkargs[$index]['threshold_critical'], 'warning') ?>><?= _('WARNING Threshold') ?></option>
<?php
        }
?>
                        <option value="custom"   <?=  is_selected($checkargs[$index]['threshold_critical'], 'custom') ?>><?= _('Custom Value') ?></option>
                    </select>
                    <label class="input-group-addon input-grouped">
                        <?= _('as') ?>
                    </label>
                    <select name="checkargs[<?= $index ?>][minmax_critical]" class="form-control input-grouped">
                        <option value="max" <?=  is_selected($checkargs[$index]['minmax_critical'], 'max') ?>><?= _('Maximum') ?></option>
                        <option value="min" <?=  is_selected($checkargs[$index]['minmax_critical'], 'min') ?>><?= _('Minimum') ?></option>
                    </select>
                </div>
            </div>
            <div class="flex-center grid-width-2">
                <div class="input-group double-input">
                    <label class="input-group-addon tt-bind input-grouped" title="">
                        <?= _("Custom Value") ?>
                    </label>
                    <input type="text" name="checkargs[<?= $index ?>][custom_value]" class="textfield form-control input-grouped" value="<?= $checkargs[$index]['custom_value'] ?>" size="4">
                </div>
            </div>
            <div class="flex-center grid-width-1">
                <button type="button" class="btn btn-sm btn-default render-graph" data-service-id="<?= $index ?>" data-host="<?= $service['host'] ?>" data-service-description="<?= $service['service'] ?>" data-perfdata-name="<?= $service['perfdata'] ?>" ><?= _("Render Graph") ?></button>
            </div>
            <div class="flex-center grid-width-3">
                <div>
                    <span class="validation-error-text"></span>
                </div>
            </div>
            <div class="flex-center grid-width-6">
                <div id="highcharts-target-<?= $index ?>">
                </div>
            </div>
        </div>
    </div>
<?php
    }
?>