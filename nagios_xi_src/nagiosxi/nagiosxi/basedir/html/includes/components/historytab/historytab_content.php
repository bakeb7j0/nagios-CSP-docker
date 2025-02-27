<?php
// History Tab COMPONENT
// Copyright (c) 2015-2024 Troy Lea aka Box293
// Copyright (c) 2024-present Nagios Enterprises, LLC. All rights reserved.

require_once(dirname(__FILE__).'/../../common.inc.php');

pre_init();
init_session();
grab_request_vars();
check_prereqs();
check_authentication(false);

const ACKNOWLEDGEMENT = 'ACKNOWLEDGEMENT';
const COMMENT = 'COMMENT';
const COMMENT_HISTORY = 'COMMENT_HISTORY';
const SCHEDULED_DOWNTIME = 'SCHEDULED_DOWNTIME';
const DOWNTIME_HISTORY = 'DOWNTIME_HISTORY';
const EXTERNALCOMMAND = 'EXTERNALCOMMAND';

/**
 * Get history data
 */
function setup_history_sql($object_id, $row_limit, &$sql_statements) {
    # Global array of DB tables
    global $db_tables;

    $escaped_object_id = escape_sql_param($object_id, DB_NDOUTILS, true);

    #
    # NOTE: Seems like it would make more sense to pull by date than a fixed #,
    #       which varies for the queries with current & historical records.
    #   - A search/filter may also be useful.
    #   - Specifying the # (or better duration) of records to pull.
    #

    # Get Acknowledgements 
    $sql_statements['acknowledgements'] =
        "SELECT entry_time, ".
        "       state,".
        "       author_name,".
        "       comment_data,".
        "       '".ACKNOWLEDGEMENT."' AS type ".
        "FROM {$db_tables[DB_NDOUTILS]['acknowledgements']} ".
        "WHERE object_id = $escaped_object_id ".
        "ORDER BY entry_time DESC ".
        "LIMIT 0, $row_limit";

    # Get Comments and Historical Comments
    $sql_statements['comments'] =
        "SELECT NC.entry_time,".
        "       NC.author_name,".
        "       NC.comment_data,".
        "       '".COMMENT."' AS type ".
        "FROM {$db_tables[DB_NDOUTILS]['comments']} NC ".
        "LEFT JOIN {$db_tables[DB_NDOUTILS]['commenthistory']} NCH".
        "    ON  NC.entry_time = NCH.entry_time".
        "    AND NC.author_name = NCH.author_name".
        "    AND NC.comment_data = NCH.comment_data".
        "    AND NC.object_id = NCH.object_id ".
        "    AND NCH.object_id = $escaped_object_id ".
        "GROUP BY entry_time, author_name ".
        "UNION ".
        "SELECT entry_time,".
        "       author_name,".
        "       comment_data,".
        "       '".COMMENT_HISTORY."' AS type ".
        "FROM {$db_tables[DB_NDOUTILS]['commenthistory']} NCH ".
        "WHERE object_id = $escaped_object_id".
        "  AND NOT EXISTS".
        "        (SELECT comment_id".
        "         FROM {$db_tables[DB_NDOUTILS]['comments']} NC".
        "         WHERE NC.entry_time = NCH.entry_time".
        "           AND NC.author_name = NCH.author_name".
        "           AND NC.comment_data = NCH.comment_data".
        "           AND NC.object_id = NCH.object_id".
        "           AND NC.object_id = $escaped_object_id) ".
        "GROUP BY entry_time, author_name ".
        "ORDER BY entry_time DESC ".
        "LIMIT 0, ".($row_limit * 2).";";

    # Get Scheduled Downtime Comments and Historical Scheduled Downtime Comments
    $sql_statements['scheduleddowntimecomments'] =
        "SELECT entry_time,".
        "       comment_data,".
        "       scheduled_start_time,".
        "       scheduled_end_time,".
        "       author_name,".
        "       '".SCHEDULED_DOWNTIME."' AS type ".
        "FROM {$db_tables[DB_NDOUTILS]['scheduleddowntime']} ".
        "WHERE object_id = $escaped_object_id ".
        "GROUP BY entry_time, author_name ".
        "UNION ".
        "SELECT entry_time,".
        "       comment_data,".
        "       scheduled_start_time,".
        "       scheduled_end_time,".
        "       author_name,".
        "       '".DOWNTIME_HISTORY."' AS type ".
        "FROM {$db_tables[DB_NDOUTILS]['downtimehistory']} NDH ".
        "WHERE object_id = $escaped_object_id".
        "  AND NOT EXISTS".
        "        (SELECT downtimehistory_id".
        "         FROM {$db_tables[DB_NDOUTILS]['scheduleddowntime']} NSD".
        "         WHERE NSD.entry_time = NDH.entry_time".
        "           AND NSD.author_name = NDH.author_name".
        "           AND NSD.scheduled_start_time = NDH.scheduled_start_time".
        "           AND NSD.scheduled_end_time = NDH.scheduled_end_time".
        "           AND NSD.object_id = NDH.object_id".
        "           AND NSD.object_id = $escaped_object_id) ".
        "GROUP BY entry_time, author_name ".
        "ORDER BY entry_time DESC LIMIT 0, ".($row_limit * 2).";";

    # Get External Commands
    $sql_statements['externalcommands'] =
        "SELECT entry_time,".
        "       command_type,".
        "       command_name,".
        "       command_args,".
        "       '".EXTERNALCOMMAND."' AS type ".
        "FROM {$db_tables[DB_NDOUTILS]['externalcommands']} ".
        "WHERE command_name IN".
        "      ('DISABLE_HOST_EVENT_HANDLER',".
        "       'DISABLE_SVC_EVENT_HANDLER',".
        "       'ENABLE_HOST_EVENT_HANDLER',".
        "       'ENABLE_SVC_EVENT_HANDLER',".
        "       'DISABLE_HOST_FLAP_DETECTION',".
        "       'DISABLE_SVC_FLAP_DETECTION',".
        "       'ENABLE_HOST_FLAP_DETECTION',".
        "       'ENABLE_SVC_FLAP_DETECTION',".
        "       'DISABLE_HOST_CHECK',".
        "       'DISABLE_SVC_CHECK',".
        "       'ENABLE_HOST_CHECK',".
        "       'ENABLE_SVC_CHECK',".
        "       'DISABLE_HOST_NOTIFICATIONS',".
        "       'DISABLE_SVC_NOTIFICATIONS',".
        "       'ENABLE_HOST_NOTIFICATIONS',".
        "       'ENABLE_SVC_NOTIFICATIONS',".
        "       'DISABLE_PASSIVE_HOST_CHECKS',".
        "       'DISABLE_PASSIVE_SVC_CHECKS',".
        "       'ENABLE_PASSIVE_HOST_CHECKS',".
        "       'ENABLE_PASSIVE_SVC_CHECKS',".
        "       'START_OBSESSING_OVER_HOST',".
        "       'START_OBSESSING_OVER_SVC',".
        "       'STOP_OBSESSING_OVER_HOST',".
        "       'STOP_OBSESSING_OVER_SVC') ".
        "ORDER BY entry_time DESC ".
        "LIMIT 0, $row_limit";

    # Get Object
    $sql_statements['currentobject'] =
        "SELECT * FROM {$db_tables[DB_NDOUTILS]['objects']} ".
        "WHERE object_id = $escaped_object_id ".
        "  AND is_active = 1";
}

/*
 * Get the history data and setup the data array.
 */
function get_history_data($sql_statements, &$history_array) {
    // Get the data from the database
    $acknowledgements = exec_sql_query(DB_NDOUTILS, $sql_statements['acknowledgements'], false);
    $comments = exec_sql_query(DB_NDOUTILS, $sql_statements['comments'], false);
    $scheduleddowntimecomments = exec_sql_query(DB_NDOUTILS, $sql_statements['scheduleddowntimecomments'], false);
    $externalcommands = exec_sql_query(DB_NDOUTILS, $sql_statements['externalcommands'], false);
    $currentobject = exec_sql_query(DB_NDOUTILS, $sql_statements['currentobject'], false);

    $currentobject_name1 = '';
    $currentobject_name2 = '';

    foreach ($currentobject as $currentobject_row) {
        $currentobject_name1 = $currentobject_row['name1'];
        $currentobject_name2 = $currentobject_row['name2'];
    }

    # Convert the ADORecordSets to a simple PHP array $history_array.
    foreach ($acknowledgements as $acknowledgements_row) {
        $history_array[] = array(
                'entry_time'    => $acknowledgements_row['entry_time'],
                'comment_data'  => $acknowledgements_row['comment_data'],
                'state'         => $acknowledgements_row['state'],
                'author_name'   => $acknowledgements_row['author_name'],
                'type'          => $acknowledgements_row['type'],
                'type_desc'     => _('Acknowledgement'),
            );
    }

    foreach($comments as $comment_row) {
        $history_array[] = array(
                'entry_time'    => $comment_row['entry_time'],
                'comment_data'  => $comment_row['comment_data'],
                'author_name'   => $comment_row['author_name'],
                'type'          => $comment_row['type'],
                'type_desc'     => ($comment_row['type'] == COMMENT) ? _('Comment') : _('Comment History'),
            );
    }

    foreach ($scheduleddowntimecomments as $downtimecomments) {
        $history_array[] = array(
                'entry_time'    => $downtimecomments['entry_time'],
                'comment_data'  => "'{$downtimecomments['comment_data']}' "._('is the comment for the downtime scheduled from')." {$downtimecomments['scheduled_start_time']} "._('to')." {$downtimecomments['scheduled_end_time']}",
                'author_name'   => $downtimecomments['author_name'],
                'type'          => $downtimecomments['type'],
                'type_desc'     => ($downtimecomments['type'] == SCHEDULED_DOWNTIME) ? _('Scheduled Downtime') : _('Downtime History'),
            );
    }

    foreach ($externalcommands as $externalcommands_row) {
        $ec_test = 1;

        # Determine if this command is for this host
        $externalcommand_host_explode = explode(';',  $externalcommands_row['command_args']);

        if ($currentobject_name1 == $externalcommand_host_explode[0]) {
            if ($currentobject_name2 == '') {
                if (!isset($externalcommand_host_explode[1])) {
                    $ec_test = 2;
                }
            } else if (isset($externalcommand_host_explode[1])) {
                if ($currentobject_name2 == $externalcommand_host_explode[1]) {
                    $ec_test = 2;
                }
            }
        }

        if ($ec_test == 2) {
            switch ($externalcommands_row['command_name']) {
                case 'DISABLE_HOST_EVENT_HANDLER':
                    $externalcommand_comment_data = _('Host event handler disabled');
                    break;

                case 'DISABLE_SVC_EVENT_HANDLER':
                    $externalcommand_comment_data = _('Service event handler disabled');
                    break;

                case 'ENABLE_HOST_EVENT_HANDLER':
                    $externalcommand_comment_data = _('Host event handler enabled');
                    break;

                case 'ENABLE_SVC_EVENT_HANDLER':
                    $externalcommand_comment_data = _('Service event handler enabled');
                    break;

                case 'DISABLE_HOST_FLAP_DETECTION':
                    $externalcommand_comment_data = _('Host flap detection disabled');
                    break;

                case 'DISABLE_SVC_FLAP_DETECTION':
                    $externalcommand_comment_data = _('Service flap detection disabled');
                    break;

                case 'ENABLE_HOST_FLAP_DETECTION':
                    $externalcommand_comment_data = _('Host flap detection enabled');
                    break;

                case 'ENABLE_SVC_FLAP_DETECTION':
                    $externalcommand_comment_data = _('Service flap detection enabled');
                    break;

                case 'DISABLE_HOST_CHECK':
                    $externalcommand_comment_data = _('Active host checks disabled');
                    break;

                case 'DISABLE_SVC_CHECK':
                    $externalcommand_comment_data = _('Active service checks disabled');
                    break;

                case 'ENABLE_HOST_CHECK':
                    $externalcommand_comment_data = _('Active host checks enabled');
                    break;

                case 'ENABLE_SVC_CHECK':
                    $externalcommand_comment_data = _('Active service checks enabled');
                    break;

                case 'DISABLE_HOST_NOTIFICATIONS':
                    $externalcommand_comment_data = _('Host notifications disabled');
                    break;

                case 'DISABLE_SVC_NOTIFICATIONS':
                    $externalcommand_comment_data = _('Service notifications disabled');
                    break;

                case 'ENABLE_HOST_NOTIFICATIONS':
                    $externalcommand_comment_data = _('Host notifications enabled');
                    break;

                case 'ENABLE_SVC_NOTIFICATIONS':
                    $externalcommand_comment_data = _('Service notifications enabled');
                    break;

                case 'DISABLE_PASSIVE_HOST_CHECKS':
                    $externalcommand_comment_data = _('Passive host checks disabled');
                    break;

                case 'DISABLE_PASSIVE_SVC_CHECKS':
                    $externalcommand_comment_data = _('Passive service checks disabled');
                    break;

                case 'ENABLE_PASSIVE_HOST_CHECKS':
                    $externalcommand_comment_data = _('Passive host checks enabled');
                    break;

                case 'ENABLE_PASSIVE_SVC_CHECKS':
                    $externalcommand_comment_data = _('Passive service checks enabled');
                    break;

                case 'START_OBSESSING_OVER_HOST':
                    $externalcommand_comment_data = _('Host obsession enabled');
                    break;

                case 'START_OBSESSING_OVER_SVC':
                    $externalcommand_comment_data = _('Service obsession enabled');
                    break;

                case 'STOP_OBSESSING_OVER_HOST':
                    $externalcommand_comment_data = _('Host obsession disabled');
                    break;

                case 'STOP_OBSESSING_OVER_SVC':
                    $externalcommand_comment_data = _('Service obsession disabled');
                    break;
            }

            $history_array[] = array(
                    'entry_time'    => $externalcommands_row['entry_time'],
                    'comment_data'  => $externalcommand_comment_data,
                    'author_name'   => _('Authorized User'),
                    'type'          => $externalcommands_row['type'],
                    'type_desc'     => _('External Command'),
                );
        }
    }
}

function init_history_data($object_id, $row_limit, &$history_array) {
    # Initializing, so make sure everything is clean.
    $history_array = [];
    $sql_statements = [];

    # TODO: pass along search value?
    setup_history_sql($object_id, $row_limit, $sql_statements);

    # The $history_array is passed by reference.
    get_history_data($sql_statements, $history_array);

    # Sort the History array by the 'entry_time' field
    usort($history_array, 'historytab_cmp');
}

/*
 * Get the data and generate the HTML.
 */
function get_history_page($history_array, $args) {

    $page = $args['page'];
    $search = grab_array_var($args, 'search', '');
    $records = $args['records'];

    $total_records = count($history_array);

    $args = [ "totals" => 1 ];

    $pager_results = get_table_pager_info("", $total_records, $page, $records, $args);
    $first_record = (($pager_results["current_page"] - 1) * $records);

    // SPECIFIC RECORDS (FOR PAGING): if you want to get specific records, use this type of format:
    $args = array(
        "records" => $records . ":" . $first_record
    );

    if ($search != '') {
        $args['search'] = $search;
    }

    $clear_args = array(
        "search" => $search,
    );

    $url_args = array(
        "search" => $search,
    );
?>
    <script type='text/javascript'>
        // TODO: report_url_args used?
        report_url_args = <?= json_encode($url_args) ?>;
        record_limit = <?= $pager_results['records_per_page'] ?>;
        max_records = <?= $pager_results['total_records'] ?>;
        max_pages = <?= $pager_results['total_pages'] ?>;

        function pagerUpdate() {
            if (current_page < max_pages) {
                $('.next-page').attr('disabled', false);
                $('.last-page').attr('disabled', false);
            } else {
                $('.next-page').attr('disabled', true);
                $('.last-page').attr('disabled', true);
            }

            if (current_page > 1) {
                $('.previous-page').attr('disabled', false);
                $('.first-page').attr('disabled', false);
            } else if (current_page <= 1) {
                $('.previous-page').attr('disabled', true);
                $('.first-page').attr('disabled', true);
            }

            // Set the page number before we actually load the page so it shows up
            // in case someone clicks the button a bunch of times
            if (max_pages > 0) {
                $('.pagenum').html(current_page + ' of ' + max_pages);
                $('.pagenum').val(current_page);
                $('.pagetotal').html(max_pages);
            } else {
                $('.pagenum').html('0 of 0');
                $('.pagenum').val(0);
                $('.pagetotal').html(0);
            }

            var lastrecord = current_page * record_limit;
            var firstrecord = (lastrecord) - record_limit + 1;

            if (lastrecord > max_records) {
                lastrecord = max_records;
            }

            if (lastrecord > 0) {
                $('.showing-records').html(firstrecord + '-' + lastrecord);
            } else {
                $('.showing-records').html('0-0');
            }
        }

        $(document).ready(function() {
            /* Overrides the function from js/reports.js */
            $('#report').on('change', '.history-num-records', function() {
                record_limit = $(this).val();
                $('.history-num-records').val(record_limit);

                // Update record limit variables
                max_pages = Math.ceil(max_records/record_limit);

                if (current_page > max_pages) {
                    current_page = max_pages;
                }

                // Set the user's new default report record limit
                args.records = record_limit;

                load_page();
            });
        });

        pagerUpdate();
    </script>

    <div class="recordcounttext">
        <?= table_record_count_text($pager_results, $search, true, $clear_args, '', true) ?>
    </div>
<?php
    $records = $pager_results['records_per_page'];
    $jump_to = '';
    $old_page_select = '';

    if (is_neptune()) {
        ob_start();
?>
    <span class="pager-select-page reports">
        <span>
            <?= _('Page') ?>
        </span>
        <span>
            <input type="text" class="tablepagertextfield condensed pagenum" style="width: 25px;" name="page" value="<?= $pager_results["current_page"] ?>">
            <span class="neptune-slash-small">/</span>
            <span class="neptune-text-muted pagetotal pagination-total"><?= get_formatted_number($pager_results["total_pages"], 0) ?></span>
        </span>
    </span>
<?php
        $jump_to = ob_get_clean();
    } else {
        ob_start();
?>
        <?= _('Page') ?> <span class="pagenum"> 1 <?= _('of') ?> <?= $pager_results['total_pages'] ?></span>
<?php
        $jump_to = ob_get_clean();
        ob_start();
?>
    <input type="text" class="form-control condensed jump-to">
    <button class="btn btn-xs btn-default tt-bind jump btn-flex" title="<?= _('Jump to Page') ?>">
        <i class="material-symbols-outlined md-16 md-400 md-middle">expand_circle_right</i>
    </button>
<?php
        $old_page_select = ob_get_clean();
    }
?>
    <div class="ajax-pagination report-pagination neptune-ajax-pagination">
        <div class="pagination-ctrl">
            <button class="<?= (is_neptune()) ? 'reports-' : '' ?>btn btn-xs btn-default first-page btn-flex" title="<?= _('Last Page') ?>"><i class="material-symbols-outlined md-20 md-400 md-middle">keyboard_double_arrow_left</i></button>
            <button class="<?= (is_neptune()) ? 'reports-' : '' ?>btn btn-xs btn-default previous-page btn-flex" title="<?= _('Previous Page') ?>" disabled><i class="material-symbols-outlined md-20 md-400 md-middle">keyboard_arrow_left</i></button>
            <span class="page-count-margin"><?= $jump_to ?></span>
            <button class="<?= (is_neptune()) ? 'reports-' : '' ?>btn btn-xs btn-default next-page btn-flex" title="<?= _('Next Page') ?>"><i class="material-symbols-outlined md-20 md-400 md-middle">keyboard_arrow_right</i></button>
            <button class="<?= (is_neptune()) ? 'reports-' : '' ?>btn btn-xs btn-default last-page btn-flex" title="<?= _('Last Page') ?>"><i class="material-symbols-outlined md-20 md-400 md-middle">keyboard_double_arrow_right</i></button>
        </div>
        <select class="form-control <?= (!is_neptune()) ? 'condensed' : '' ?> history-num-records">
            <option value="5"<?= ($pager_results['records_per_page'] == 5) ? ' selected' : '' ?>>5 <?= _('Per Page') ?></option>
            <option value="10"<?= ($pager_results['records_per_page'] == 10) ? ' selected' : '' ?>>10 <?= _('Per Page') ?></option>
            <option value="25"<?= ($pager_results['records_per_page'] == 25) ? ' selected' : '' ?>>25 <?= _('Per Page') ?></option>
            <option value="50"<?= ($pager_results['records_per_page'] == 50) ? ' selected' : '' ?>>50 <?= _('Per Page') ?></option>
            <option value="100"<?= ($pager_results['records_per_page'] == 100) ? ' selected' : '' ?>>100 <?= _('Per Page') ?></option>
        </select>
        <?= $old_page_select ?>
    </div>
<?php
    if (empty($history_array)) {
?>
        <?= _('No history found') ?>
<?php
    } else {
        #
        # Build the table
        #
?>
    <table class="table table-condensed table-hover table-bordered table-no-margin table-striped">
        <thead>
            <tr>
                <th><?= _("Date / Time") ?></th>
                <th><?= _("Type") ?></th>
                <th><?= _("Data") ?></th>
                <th><?= _("Author") ?></th>
                <th><?= _("State") ?></th>
            </tr>
        </thead>
        <tbody>
<?php
        # Keep track of the records that will be "printed".
        $line_num = 0;
        $start_count = ($page - 1) * $records;
        $record_max = $start_count + $records;

        foreach ($history_array as $line => $value) {
            $line_num++;

            # Skipping...
            if ($line_num <= $start_count) {
                continue;
            }

            # Checking to make sure we are not displaying more than $records
            if ($line_num > $record_max) {
                break;
            }

            switch (grab_array_var($history_array[$line], 'state', -1)) {
                case 0:
                    $trclass = 'servicerecovery';
                    $tdclass = 'serviceok';
                    $dotcolor = 'hostup';
                    $status_text = _('OK');
                    break;

                case 1:
                    $trclass = "serviceproblem";
                    $tdclass = "servicewarning";
                    $dotcolor = "hostwarning";
                    $status_text = _('WARNING');
                    break;

                case 2:
                    $trclass = "serviceproblem";
                    $tdclass = "servicecritical";
                    $dotcolor = "hostdown";
                    $status_text = _('CRITICAL');
                    break;

                case 3:
                    $trclass = "serviceproblem";
                    $tdclass = "serviceunknown";
                    $dotcolor = "hostunknown";
                    $status_text = _('UNKNOWN');
                    break;

                default:
                    $trclass = '';
                    $tdclass = '';
                    $dotcolor = '';
                    $status_text = '';
                    break;
            }

            $status_text = (is_neptune()) ? ucfirst(strtolower($status_text)) : $status_text;

            $status_bubble = (is_neptune()) ? '<span class="status-dot ' . $dotcolor . ' dot-10"></span>' : '';
            $tdclass = (is_neptune()) ? 'nowrap' : $tdclass;
            $info_class = (is_neptune()) ? "mono mono-text" : "";
?>
            <tr class='<?= $trclass ?>'>
                <td><?= $history_array[$line]['entry_time'] ?></td>
                <td><?= $history_array[$line]['type_desc'] ?></td>
                <td class='<?= $info_class ?>'><?= $history_array[$line]['comment_data'] ?></td>
                <td><?= $history_array[$line]['author_name'] ?></td>
<?php
            switch ($history_array[$line]['type']) {
                case ACKNOWLEDGEMENT:
?>
                <td class='<?= $tdclass ?>'><?= $status_bubble ?><?= $status_text ?></td>
<?php
                    break;

                case COMMENT:
                case COMMENT_HISTORY:
                case EXTERNALCOMMAND:
                case SCHEDULED_DOWNTIME:
                case DOWNTIME_HISTORY:
                default:
?>
                <td><?= _('N/A') ?></td>
<?php
                    break;
            }
        }
?>
            </tr>
        </tbody>
    </table>

    <div class="ajax-pagination report-pagination neptune-ajax-pagination">
        <div class="pagination-ctrl">
            <button class="<?= (is_neptune()) ? 'reports-' : '' ?>btn btn-xs btn-default first-page btn-flex" title="<?= _('Last Page') ?>"><i class="material-symbols-outlined md-20 md-400 md-middle">keyboard_double_arrow_left</i></button>
            <button class="<?= (is_neptune()) ? 'reports-' : '' ?>btn btn-xs btn-default previous-page btn-flex" title="<?= _('Previous Page') ?>" disabled><i class="material-symbols-outlined md-20 md-400 md-middle">keyboard_arrow_left</i></button>
            <span class="page-count-margin"><?= $jump_to ?></span>
            <button class="<?= (is_neptune()) ? 'reports-' : '' ?>btn btn-xs btn-default next-page btn-flex" title="<?= _('Next Page') ?>"><i class="material-symbols-outlined md-20 md-400 md-middle">keyboard_arrow_right</i></button>
            <button class="<?= (is_neptune()) ? 'reports-' : '' ?>btn btn-xs btn-default last-page btn-flex" title="<?= _('Last Page') ?>"><i class="material-symbols-outlined md-20 md-400 md-middle">keyboard_double_arrow_right</i></button>
        </div>
        <select class="form-control <?= (!is_neptune()) ? 'condensed' : '' ?> history-num-records">
            <option value="5"<?= ($pager_results['records_per_page'] == 5) ? ' selected' : '' ?>>5 <?= _('Per Page') ?></option>
            <option value="10"<?= ($pager_results['records_per_page'] == 10) ? ' selected' : '' ?>>10 <?= _('Per Page') ?></option>
            <option value="25"<?= ($pager_results['records_per_page'] == 25) ? ' selected' : '' ?>>25 <?= _('Per Page') ?></option>
            <option value="50"<?= ($pager_results['records_per_page'] == 50) ? ' selected' : '' ?>>50 <?= _('Per Page') ?></option>
            <option value="100"<?= ($pager_results['records_per_page'] == 100) ? ' selected' : '' ?>>100 <?= _('Per Page') ?></option>
        </select>
        <?= $old_page_select ?>
    </div>
<?php
    }
}

/*
 * This is for sorting the history_array
 * comparison function
 * $a, $b = Oldest to Newest
 * $b, $a = Newest to Oldest
 */
function historytab_cmp ($b, $a) {
    return strcmp($a['entry_time'], $b['entry_time']);
}


/*
 * Main section
 */
$object_id = grab_request_var('object_id', false);
$args = grab_request_var('args', false);

$records =  grab_array_var($args, 'records', 0);

if ($records == 0) {
    $args['records'] = grab_request_var("records", get_user_meta(0, 'report_default_recordlimit', 10));
}

$mode = $args['mode'];
$page = $args['page'];

# TODO: Allow the user to pick adjust max rows to get from each query.
$row_limit = 10;

# Paging - moving to next page or changing the # of rows displayed on a page.
if ($mode == 'getpage') {
    // TODO: Handle Search.
    $search = '';
    $clear_args = '';

    # TODO: Would it be worth it to put the data in the session?
    init_history_data($object_id, $row_limit, $history_array, $args['page']);
    get_history_page($history_array, $args);

# Load initial page.
} else {
    $history_array = null;
    $pager_results = null;
    $search = '';
    $clear_args = '';

    init_history_data($object_id, $row_limit, $history_array);
    get_history_page($history_array, $args);
}

?>