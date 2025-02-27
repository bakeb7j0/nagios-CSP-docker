<?php

$hs = count($FIELDS['pre_hosts_AB']);

?>

    <div id="tab1">
        <div class="ccm-row ccm-flex-neptune">
            <label for="tfName" class="neptune-row-label"><?= _("File Name"); ?> <span class="req" title="<?= _('Required'); ?>">*</span></label>
            <input type="text" class="required form-control max-width-500" value="<?php val(encode_form_val(grab_array_var($FIELDS, 'file_name'))); ?>" id="tfName" name="tfName">
        </div>
        <div class="ccm-row ccm-flex-neptune">
            <label for="tfAlias" class="neptune-row-label"><?= _("Alias"); ?></label>
            <input type="text" class="form-control max-width-500" value="<?php val(encode_form_val(grab_array_var($FIELDS, 'alias'))); ?>" id="tfAlias" name="tfAlias">
        </div>
        
        <div class="ccm-row">
            <button type="button" class="btn btn-sm btn-info btn-hostBox" onclick="overlay('hostBox')"><?php echo _("Manage Linked Host"); ?> <span class="badge"><?php echo $hs; ?></span></button>
        </div>
    </div>