<?php
    /**************************************************************************
     * This module is for step1 of a wizard and adds the option to copy or
     * update/edit an existing configuration.
     * 
     * REQUIREMENTS:
     * 1. Include wizards-bs5.js, for the required Javascript functions.
     * 2. The wizard must provide 
     * 
     * NOTES:
     * 1. Hidden if no existing configurations
     * 2. 
     **************************************************************************/
    if (isset($nodes)) {
?>
        <!--                                                      -->
        <!-- TODO: Better way to do this.                         -->
        <!-- Create a javascript list of existing configurations. -->
        <!--                                                      -->
        <script>
            var hostname = '<?= $hostname ?>';
            var wizardname = '<?= $wizard_name ?>';
            var ajaxUrl = base_url+'includes/configwizards/configwizards.ajax.php';
console.log("ajaxUrl ["+ajaxUrl+"]");
 
            var hostsList = (<?= json_encode($nodes) ?>);
<?php
            # Setup defaults
            $checked = ' checked';
            $unchecked = '';
            $newStatus = $unchecked;
            $copyStatus = $unchecked;
            $updateStatus = $unchecked;

            if (!isset($operation) || $operation == 'new') {
                # Default
                $newStatus = $checked;
                $operation = 'new';
            } else if ($operation == 'copy') {
                $copyStatus = $checked;
            } else if ($operation == 'update') {
                $updateStatus = $checked;
            } else {
                # Something went wrong, so use the default.
                $newStatus = $checked;
                $operation = 'new';
            }
?>
            // NOTE: '""' required for "empty" string response from JSON.parse().
            var hostData = JSON.parse('<?= (!empty($config)) ? json_encode($config) : '""' ?>');

        </script>

        <style>
            #loadFormLabel i.icon-small, #hostConfigLabel i.icon-small {
                left: 0;
            }
            .placeholder { color: grey; }
        </style>

        <!--                                                                                            -->
        <!-- Configurations have already been created, so offer to copy or edit, as well as create new. -->
        <!--                                                                                            -->
        <div class="row pb-3">
            <div class="col-sm-auto">
                <nav class="navbar navbar-dark bg-dark ps-3 pe-3 rounded">
                    <div class="btn-group wizard-btn-bar" role="group" aria-label="Coniguration Management">
                        <input name="operation" type="radio" class="btn-check" id="new" value="new" autocomplete="off" <?= $newStatus ?>>
                        <label class="btn btn-dark" for="new">New</label>

                        <input name="operation" type="radio" class="btn-check" id="copy" value="copy" autocomplete="off" <?= $copyStatus ?> <?= (empty($nodes) ? 'disabled' : '') ?>>
                        <label class="btn btn-dark" for="copy">Copy</label>

                        <input name="operation" type="radio" class="btn-check" id="update" value="update" autocomplete="off" <?= $updateStatus ?> <?= (empty($nodes) ? 'disabled' : '') ?>>
                        <label class="btn btn-dark" for="update">View/Edit</label>

                        <!--                                                -->
                        <!-- List of existing configurations (hosts/nodes). -->
                        <!--                                                -->
                        <div class="input-group ps-1">
                            <select id="hostConfig" class="form-select rounded-end" list="hostConfigOptions" style="min-width: 310px;" title="Choose a configuration" disabled>
                                <option value="" disabled selected hidden><?= (!empty($nodes)) ? 'Choose a Configuration' : 'Please create a New Configuration' ?></option>
<?php
        foreach ($nodes as $node) {
?>
                                <option value="<?= $node ?>"><?= $node ?></option>
<?php
        }
    ?>
                            </select>
                            <label id="hostConfigLabel" for="hostConfig" class="form-label ps-2 pt-1"><?= xi6_info_tooltip(_("Search for and select a configuration to view, change, or copy.")) ?></label>
                        </div>
                    </div>
                </nav>
            </div>
        </div>
<?php
    }
?>
        <!--                                                       -->
        <!-- Message displayed if a configuration has been loaded. -->
        <!--                                                       -->
        <div id="loadConfigAlert" class="alert align-items-center d-flex w-50 <?= ($operation == 'new') ? 'visually-hidden' : '' ?>" role="alert">
            <i class="fa fa-pencil fa-2x pe-3"></i>
            <div id="loadedConfigSuccess"></div>
        </div>
