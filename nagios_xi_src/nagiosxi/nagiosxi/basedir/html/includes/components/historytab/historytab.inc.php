<?php
// History Tab COMPONENT
// Copyright (c) 2015-2024 Troy Lea aka Box293
// Copyright (c) 2024-present Nagios Enterprises, LLC. All rights reserved.

require_once(dirname(__FILE__).'/../componenthelper.inc.php');

/* respect the name */
$historytab_component_name = "historytab";

/* Run the initialization function */
historytab_component_init();

////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////

function historytab_component_init() {
    global $historytab_component_name;

    $versionOk = historytab_component_checkversion();

    $desc = "This component adds a 'History tab' to Host and Service Detail pages where historical comments, acknowledgements, downtime and external commands are displayed.";

    if (!$versionOk) {
        $desc .= '<br><b>Error: This component requires Nagios XI 2024R1.2 or later.</b>';
    }

    $args = array (
        COMPONENT_NAME        => $historytab_component_name,
        COMPONENT_VERSION     => '3.0.0',
        COMPONENT_AUTHOR      => 'Nagios Enterprises, LLC',
        COMPONENT_DESCRIPTION => 'Adds a tab to Host and Service Detail screens to show history for comments, acknowledgements, downtime and external commands.  ' . $desc,
        COMPONENT_TITLE       => _('History Tab'),
        COMPONENT_REQUIRES_VERSION => 60200,
    );

    register_component($historytab_component_name, $args);

    // Add a menu link
    if ($versionOk) {
        register_callback(CALLBACK_SERVICE_TABS_INIT, 'historytab_component_addtab');
        register_callback(CALLBACK_HOST_TABS_INIT, 'historytab_component_addtab');
    }
}

///////////////////////////////////////////////////////////////////////////////////////////
// MISC FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function historytab_component_checkversion() {
    if (!function_exists('get_product_release')) {
        return false;
    }

    /* Requires greater than 2024R1.2 */
    if (get_product_release() < 60200) {
        return false;
    }

    return true;
}

function historytab_component_addtab($cbtype, &$cbdata) {
    /* Grab the GET variables */
    $host = grab_array_var($cbdata, 'host');
    $service = grab_array_var($cbdata, 'service');

    /* Get the unique service ID */
    if ($service == "") {
        $object_id = get_host_id($host);
    } else {
        $object_id = get_service_id($host, $service);
    }

    $history_array = null;
    $pager_results = null;
    $search = '';
    $clear_args = '';

    $row_limit = 25;

    ob_start();
?>
    <script>
        var current_page = 1;

        var args = {};
        var report_url_args = {};
        var record_limit = 0;
        var max_records = 0;
        var max_pages = 1;
        var object_id = "<?= $object_id ?>";

        $(document).ready(function() {
            load_page('init');
        });
    </script>

    <script type='text/javascript' src='<?= get_base_url() ?>includes/js/reports.js?<?= get_build_id() ?>'></script>
    <script type='text/javascript' src='../historytab/historytab.inc.js?<?= get_build_id() ?>'></script>

    <?= get_nagios_session_protector() ?>

    <!-- Content goes here -->
    <div id="report" class="historytab_data" style="display: none"></div>
<?php
    $content = ob_get_clean();

    $newtab = array(
        'id'        => 'historytab',
        'title'     => _('History'),
        'content'   => $content,
        'icon'      => '<i class="material-symbols-outlined md-18 md-300 md-middle">history</i>',
    );

    /* Add new tab */
    $cbdata['tabs'][] = $newtab;
}

?>
