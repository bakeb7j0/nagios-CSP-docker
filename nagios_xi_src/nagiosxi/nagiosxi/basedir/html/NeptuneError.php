<?php
//
// Copyright (c) 2008-2020 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/includes/common.inc.php');


// Start session
init_session(false, false);

// Grab GET or POST variables and check pre-reqs
grab_request_vars();
check_prereqs(false);
check_authentication(true);

if(!is_neptune()) {
    header("Location: ".get_base_url());
}

do_page_start(["page_title" => _("Neptune Error")], true);

?>
<div class="flex flex-col gap-2">
    <h1 style="margin: 0px; padding: 0px"><?= _("The Neptune Theme encountered an error!") ?></h1>
    <p class="neptune-subtext"><?= _("An unexpected error occurred while loading the Neptune Theme.") ?></p>
    <p class="neptune-subtext"><?php printf(_("If the issue persists, please reach out to %s."), "<a rel=\"noreferrer\" target=\"_blank\" href=https://support.nagios.com/>"._("Customer Support")."</a>"); ?></p>
    <button class="btn btn-sm btn-primary" style="width:148px" id="NeptuneError"><?= _("Switch to Modern") ?></button>
</div>
<script>
    document.getElementById("NeptuneError").addEventListener("click", async function (event) {
        const params = new URLSearchParams({
            cmd: 'setusermeta',
            opts: JSON.stringify({
                keyname: 'theme',
                keyvalue: "xi5",
            }),
            nsp: "<?= get_nagios_session_protector_id() ?>",
        });
        fetch("<?= get_ajax_helper_url() ?>?"+params.toString()).then(function (response) {
            window.location.href = "<?= get_base_url() ?>";
        })
    })
</script>
<?php

do_page_end();