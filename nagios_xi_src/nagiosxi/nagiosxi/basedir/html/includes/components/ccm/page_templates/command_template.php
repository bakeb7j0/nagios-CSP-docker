<?php
//
//  Nagios Core Config Manager
//  Copyright (c) 2010-2019 Nagios Enterprises, LLC
//
//  File: command_settings.php
//  Desc: Creates the HTML for the Command Management page's tab. Used in the form class
//        to output the area where everything is defined.
//

if(empty($FIELDS['command_name'])) {
    $FIELDS['command_name'] = '';
}
if(empty($FIELDS['command_line'])) {
    $FIELDS['command_line'] = '';
}
?>
    
<?php if(is_neptune()) { ?>

    <div id="tab1">
        <div class="neptune-command-management">
            <div class="neptune-command-column-example neptune-spacer">
                <div class="neptune-command-input-row">
                    <label for="tfName" class="neptune-row-label"><?php echo _("Command Name"); ?> <span class="req" title="<?php echo _('Required'); ?>">*</span></label>
                    <input type="text" class="required form-control neptune-command-name-input"  value="<?php val(encode_form_val($FIELDS['command_name'])); ?>" id="tfName" name="tfName">
                </div>
                <div class="neptune-command-example-row">
                    <div class="neptune-row-label"></div>
                    <div class="subtext"><?php echo _("Example"); ?>: check_example</div>
                </div>
            </div>
            
            <div class="neptune-command-column-example neptune-spacer">
                <div class="neptune-command-input-row">
                    <label for="tfCommand" class="neptune-row-label"><?php echo _("Command Line"); ?> <span class="req" title="<?php echo _('Required'); ?>">*</span></label>
                    <input type="text" class="required form-control neptune-command-line-input" value="<?php val(encode_form_val($FIELDS['command_line'])); ?>" id="tfCommand" name="tfCommand">
                </div>
                <div class="neptune-command-example-row">
                    <div class="neptune-row-label"></div>
                    <div class="subtext"><?php echo _("Example"); ?>: $USER1$/check_example -H $HOSTADDRESS$ -P $ARG1$ $ARG2$</div>
                </div>
            </div>

            <div class="ccm-flex-neptune neptune-spacer">
                <label for="selCommandType" class="neptune-row-label"><?php echo _("Command Type"); ?>:</label>
                <div>
                    <select id="selCommandType" class="form-control" name="selCommandType">
                        <option <?php ccm_is_selected('command_type', 1); ?> value="1"><?php echo _("check command"); ?></option>
                        <option <?php ccm_is_selected('command_type', 2); ?> value="2"><?php echo _("misc command"); ?></option>
                        <option <?php ccm_is_selected('command_type', 0); ?> value="0"><?php echo _("unclassified"); ?></option>   
                    </select>
                </div>
            </div>
            <?php
            // Check if the active button should be checked
            $active_checked = '';
            if ((isset($FIELDS['active']) && $FIELDS['active'] == '1') || !isset($FIELDS['active'])) {
                $active_checked = 'checked="checked"';
            }
            ?>
            <div class="ccm-row spacer command-checkbox-row">
                <div class="checkbox">
                    <input name="chbActive" type="checkbox" class="neptune-ccm-checkbox" id="chbActive" value="1" <?php echo $active_checked; ?>> 
                    <label class="neptune-checkbox-label" for="chbActive"> <?php echo _("Active"); ?> 
                        <span class="material-symbols-outlined tooltip-info ccm-neptune-info" title="<?php echo _("Only active objects will be written to the config files and appear in Nagios. Inactive objects will only be shown in the CCM."); ?>">info</span> 
                    </label>
                </div>
            </div>
            <div class="ccm-flex-neptune neptune-spacer neptune-command-input-row">
                <label for="selPlugins" class="neptune-row-label neptune-command-row-label"><?php echo _("Available Plugins "); ?><span class='material-symbols-outlined tooltip-info ccm-neptune-info' title="<?php echo _("Selecting a plugin will also show documenation for that plugin."); ?>">info</span></label>
                <select name="selPlugins" class="form-control" id="selPlugins" style="margin-right: 3px;" onchange="get_plugin_help('<?php echo $_SESSION['token']; ?>')">
                    <option value="null">&nbsp;</option>
                    <?php
                    $plugins = scandir($cfg['component_info']['nagioscore']['plugin_dir']); 
                    foreach ($plugins as $p) {
                        if ($p =='.' || $p=='..' || strpos($p, 'check_') === false) { continue; } 
                        echo '<option value="'.encode_form_val($p).'">'.encode_form_val($p).'</option>'; 
                    }
                    ?>   
                </select>
            </div>
            <div id="pluginhelp"></div>
            <div class="clear"></div>
        </div>
    </div>
    <div id='documentation' class='overlay'></div>

<?php } else { ?>
    <div id="tab1">
        <div class="ccm-row">
            <label for="tfName"><?php echo _("Command Name"); ?> <span class="req" title="<?php echo _('Required'); ?>">*</span></label>
            <input type="text" class="required form-control"  value="<?php val(encode_form_val($FIELDS['command_name'])); ?>" id="tfName" name="tfName" style="width: 300px;">
            <div class="subtext"><?php echo _("Example"); ?>: check_example</div>
        </div>
        <div class="ccm-row">
            <label for="tfCommand"><?php echo _("Command Line"); ?> <span class="req" title="<?php echo _('Required'); ?>">*</span></label>
            <input type="text" class="required form-control" value="<?php val(encode_form_val($FIELDS['command_line'])); ?>" id="tfCommand" name="tfCommand" style="width: 750px;">
            <div class="subtext"><?php echo _("Example"); ?>: $USER1$/check_example -H $HOSTADDRESS$ -P $ARG1$ $ARG2$</div>
        </div>
        <div class="ccm-row spacer">
            <label for="selCommandType"><?php echo _("Command Type"); ?>:</label>
            <div>
                <select id="selCommandType" class="form-control" name="selCommandType">
                   <option <?php ccm_is_selected('command_type', 1); ?> value="1"><?php echo _("check command"); ?></option>
                   <option <?php ccm_is_selected('command_type', 2); ?> value="2"><?php echo _("misc command"); ?></option>
                   <option <?php ccm_is_selected('command_type', 0); ?> value="0"><?php echo _("unclassified"); ?></option>   
                </select>
            </div>
        </div>
        <?php
        // Check if the active button should be checked
        $active_checked = '';
        if ((isset($FIELDS['active']) && $FIELDS['active'] == '1') || !isset($FIELDS['active'])) {
            $active_checked = 'checked="checked"';
        }
        ?>
        <div class="ccm-row spacer">
            <div class="checkbox">
                <label>
                    <input name="chbActive" type="checkbox" class="checkbox" id="chbActive" value="1" <?php echo $active_checked; ?>> <?php echo _("Active"); ?>
                    <i class="fa fa-info-circle tooltip-info" title="<?php echo _("Only active objects will be written to the config files and appear in Nagios. Inactive objects will only be shown in the CCM."); ?>"></i>
                </label>
            </div>
        </div>
        <div class="ccm-row">
            <label for="selPlugins"><?php echo _("Available Plugins"); ?></label>
            <select name="selPlugins" class="form-control" id="selPlugins" style="margin-right: 3px;" onchange="get_plugin_help('<?php echo $_SESSION['token']; ?>')">
                <option value="null">&nbsp;</option>
                <?php
                $plugins = scandir($cfg['component_info']['nagioscore']['plugin_dir']); 
                foreach ($plugins as $p) {
                    if ($p =='.' || $p=='..' || strpos($p, 'check_') === false) { continue; } 
                    echo '<option value="'.encode_form_val($p).'">'.encode_form_val($p).'</option>'; 
                }
                ?>   
            </select>
            <i class="fa fa-info-circle tooltip-info" title="<?php echo _("Selecting a plugin will also show documenation for that plugin."); ?>"></i>
            <div id="pluginhelp"></div>
        </div>
        <div class="clear"></div>
    </div>
    <div id='documentation' class='overlay'></div>
<?php } ?>