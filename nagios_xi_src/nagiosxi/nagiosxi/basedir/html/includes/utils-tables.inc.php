<?php
//
// Copyright (c) 2008-2020 Nagios Enterprises, LLC. All rights reserved.
//


////////////////////////////////////////////////////////////////////////
// OLD TABLE FUNCTIONS
////////////////////////////////////////////////////////////////////////


/**
 * @param        $sb
 * @param        $so
 * @param        $keyname
 * @param        $title
 * @param null   $extra_args
 * @param string $th_extra
 * @param string $url
 *
 * @return string
 */
function sorted_table_header($sb, $so, $keyname, $title, $extra_args = null, $th_extra = "", $url = "")
{

    if ($url == "")
        $theurl = get_current_page();
    else
        $theurl = $url;

    $classes = "sort-header";
    $args = "sortby=" . urlencode($keyname);
    if ($sb == $keyname) {
        if ($so == "asc") {
            $classes .= " headerSortDown";
            $newsortorder = "desc";
        } else if ($so == "desc") {
            $classes .= " headerSortUp";
            $newsortorder = "asc";
        }
        $args .= "&amp;sortorder=" . $newsortorder;
    } else
        $args .= "&amp;sortorder=asc";

    if (have_value($extra_args)) {
        foreach ($extra_args as $k => $v) {
            $args .= "&amp;" . urlencode($k) . "=" . urlencode($v);
        }
    }
    //$title="TEST";
    //$theurl="URL";
    //$output="<th class='".$classes."' ".$th_extra."><a href='".$theurl."?".$args."'>".htmlentities($title)."</a></th>";

    //fix to make entire table header clickable -MG
    $output = "<th class='" . $classes . "' " . $th_extra . ">
				<a href='" . $theurl . "?" . $args . "'>
                    <div class='th_link'>" . $title . "</div>
				</a></th>";

    return $output;
}


/**
 * @param string $url
 * @param int    $total_records
 * @param int    $current_page
 * @param int    $records_per_page
 * @param null   $args
 *
 * @return array
 */
function get_table_pager_info($url = "", $total_records = 0, $current_page = 1, $records_per_page = 10, $args = null)
{

    // clean vars
    $total_records = intval($total_records);
    $current_page = intval($current_page);
    $records_per_page = intval($records_per_page);

    // construct the base url
    $theurl = htmlentities($url);
    $theurl .= "?records=" . $records_per_page . "";
    if ($args != null) {
        foreach ($args as $k => $v) {
            $theurl .= "&amp;" . urlencode($k) . "=" . urlencode($v);
        }
    }

    // special value
    if ($records_per_page == -1)
        $records_per_page = $total_records;

    // adjust records per page if necessary
    if ($records_per_page < 1)
        $records_per_page = 1;

    // calculate total pages
    $total_pages = ceil($total_records / $records_per_page);

    // adjust current page if necessary
    if ($current_page <= 0)
        $current_page = 1;
    if ($current_page > $total_pages)
        $current_page = $total_pages;

    // calculate next/previous page numbers
    $previous_page = $current_page - 1;
    if ($previous_page < 1)
        $previous_page = 1;
    $next_page = $current_page + 1;
    if ($next_page > $total_pages)
        $next_page = $total_pages;

    // calculate first and last records
    $first_record = (($current_page - 1) * $records_per_page) + 1;
    $last_record = ($current_page * $records_per_page);
    if ($first_record < 0)
        $first_record = 0;
    if ($last_record > $total_records)
        $last_record = $total_records;

    // construct urls
    $first_page_url = $theurl . "&amp;page=1";
    $last_page_url = $theurl . "&amp;page=" . $total_pages;
    $next_page_url = $theurl . "&amp;page=" . $next_page;
    $prev_page_url = $theurl . "&amp;page=" . $previous_page;

    // return array
    $result = array(
        "records_per_page" => $records_per_page,
        "current_page" => $current_page,
        "total_pages" => $total_pages,
        "next_page" => $next_page,
        "previous_page" => $previous_page,
        "first_page_url" => $first_page_url,
        "last_page_url" => $last_page_url,
        "next_page_url" => $next_page_url,
        "previous_page_url" => $prev_page_url,
        "total_records" => $total_records,
        "first_record" => $first_record,
        "last_record" => $last_record
    );

    return $result;
}


/**
 * @param        $pager_info
 * @param        $query
 * @param bool   $show_clear
 * @param null   $clear_url_args
 * @param string $url
 *
 * @return string
 */
function table_record_count_text($pager_info, $query, $show_clear = false, $clear_url_args = null, $url = "", $ajax = false)
{
    global $cfg;

    if ($url == "")
        $theurl = get_current_page();
    else
        $theurl = $url;

    $txt = "";
    $txt .= _("Showing") . " <span class='showing-records'>" . get_formatted_number($pager_info["first_record"]) . "-" . get_formatted_number($pager_info["last_record"]) . "</span> " . _("of") . " " . get_formatted_number($pager_info["total_records"]);

    // were there more records that the max we allow?  if so, show a "+" sign
    if ($pager_info["total_records"] == $cfg['default_result_records']) {
        $txt .= "+";
    }

    if (have_value($query)) {
        $txt .= " " . _("total matches for") . " '<b>" . htmlentities($query, ENT_COMPAT, 'UTF-8') . "</b>'";
    } else {
        $txt .= " " . _("total records");
    }

    if (have_value($query)) {
        if ($ajax) {
            $txt .= "<i class='material-symbols-outlined tt-bind clear-search md-button md-400 md-20 md-critical md-middle' data-placement='right' alt='" . _('Clear search criteria') . "' title='" . _('Clear search criteria') . "'>close</i>";
        } else if ($show_clear == true) {
            $txt .= " <a href='" . $theurl . "?";
            foreach ($clear_url_args as $var => $val)
                $txt .= "&amp;" . urlencode($var) . "=" . urlencode($val);
            $txt .= "'>";
            $txt .= "<i class='material-symbols-outlined tt-bind clear-search md-button md-400 md-20 md-critical md-middle' data-placement='right' alt='" . _('Clear search criteria') . "' title='" . _('Clear search criteria') . "'>close</i>";
            $txt .= "</a>";
        }
    }

    return $txt;
}


/**
 * @param      $pager_results
 * @param null $record_options
 * @param null $extra_options
 */
function table_record_pager($pager_results, $record_options = null, $extra_options = null)
{
    echo get_table_record_pager($pager_results, $record_options, $extra_options);
}


/**
 * @param      $pager_results
 * @param null $record_options
 * @param null $extra_options
 *
 * @return string
 */
function get_table_record_pager($pager_results, $record_options = null, $extra_options = null)
{
    $output = '<div class="xi-pager ajax-pagination">';
    if (is_neptune()){

        if (is_null($record_options)) {
            $record_options = array("5", "10", "25", "50", "100", "200");
        }

        if ($pager_results['total_pages'] > 1) {
            $disabled = '';
            if ($pager_results['current_page'] <= 1) { $disabled = 'disabled'; }
            $output .= '<span style="margin-right:-20px; gap: 10px;" class="flex justify-start items-center"><a '.$disabled.' href="'.$pager_results['first_page_url'].'" class="btn-pager first-page tt-bind" title="'._('First Page').'">
                            <i class="material-symbols-outlined md-20 md-400 md-middle">keyboard_double_arrow_left</i>
                        </a>';


            $output .= '<a '.$disabled.' href="'.$pager_results['previous_page_url'].'" class="btn-pager previous-page tt-bind" title="'._('Previous Page').'">
                            <i class="material-symbols-outlined md-20 md-400 md-middle">keyboard_arrow_left</i>
                        </a></span>';
        }

        $output .= '
        <span class="pager-select-page">
            <span>
                '._('Page').' 
            </span>

            <span>
            <input 
                type="text" 
                class="tablepagertextfield condensed" 
                style="width: 25px;" 
                name="page" 
                value="'.$pager_results["current_page"].'"> 
            <span class="neptune-slash-small">/</span>  <span class="neptune-text-muted pagination-total">'.get_formatted_number($pager_results["total_pages"], 0).'</span>
            </span>

        </span>';
    
    $output .= '
        <span class="pager-select-numpages">
            <select class="tablepagerselect condensed" name="records">';
        foreach ($record_options as $opt) {
            $output .= "<option " . is_selected($pager_results["records_per_page"], $opt) . " value='" . $opt . "'>" . $opt . " " . _("Per Page") . " </option>";
        }
        $output .= '</select>';
        $output .= '<button type="submit" class="tablepagersubmitbutton btn btn-default btn-page-go" name="pagemove">'._('Go').'</button></span>';

        // If we need to print out any extra options
        if ($extra_options) {
            foreach ($extra_options as $var => $val) {
                $output .= '<input type="hidden" name="'.encode_form_val($var).'" value="'.encode_form_val($val).'">';
            }
        }

        if ($pager_results['total_pages'] > 1) {
            $disabled = '';
            if ($pager_results['current_page'] >= $pager_results['total_pages']){ $disabled = 'disabled'; }
            $output .= '<span style="gap: 10px;" class="flex justify-start items-center">';
            $output .= "<a ".$disabled." href='" . $pager_results["next_page_url"] . "' class='btn-pager next-page tt-bind' title='" . _('Next Page') . "'><i class='material-symbols-outlined md-20 md-400 md-middle'>keyboard_arrow_right</i></a>";
            $output .= "<a ".$disabled." href='" . $pager_results["last_page_url"] . "' class='btn-pager last-page tt-bind' title='" . _('Last Page') . "'><i class='material-symbols-outlined md-20 md-400 md-middle'>keyboard_double_arrow_right</i></a>";
            $output .= '</span>';            
        }

        $output .= '</div>';
        return $output;
    } 

    // <!-- anything that isn't neptune theme  

    if (is_null($record_options)) {
        $record_options = array("5", "10", "25", "50", "100", "200");
    }

    if ($pager_results['total_pages'] > 1) {
        $output .= '<a href="'.$pager_results['first_page_url'].'" class="btn btn-xs btn-default btn-pager first-page width-fit btn-flex" title="'._('First Page').'">
                        <i class="material-symbols-outlined md-20 md-400 md-middle">keyboard_double_arrow_left</i>
                    </a>';

        $output .= '<a href="'.$pager_results['previous_page_url'].'" class="btn btn-xs btn-default btn-pager previous-page width-fit btn-flex" title="'._('Previous Page').'">
                        <i class="material-symbols-outlined md-20 md-400 md-middle">keyboard_arrow_left</i>
                    </a>';
    }

    $output .= '<span class="pager-select-page">'._('Page').' <input type="text" class="tablepagertextfield form-control condensed" style="width: 40px;" name="page" value="'.$pager_results["current_page"].'"> of '.get_formatted_number($pager_results["total_pages"], 0).'</span>';

    $output .= '<span class="pager-select-numpages"><select class="tablepagerselect form-control condensed" name="records">';
    foreach ($record_options as $opt) {
        $output .= "<option " . is_selected($pager_results["records_per_page"], $opt) . " value='" . $opt . "'>" . $opt . " " . _("Per Page") . " </option>";
    }
    $output .= '</select>';
    $output .= '<button type="submit" class="tablepagersubmitbutton btn btn-xs btn-default btn-page-go" name="pagemove">'._('Go').'</button></span>';

    // If we need to print out any extra options
    if ($extra_options) {
        foreach ($extra_options as $var => $val) {
            $output .= '<input type="hidden" name="'.htmlentities($var, ENT_COMPAT, 'UTF-8').'" value="'.htmlentities($val, ENT_COMPAT, 'UTF-8').'">';
        }
    }

    if ($pager_results['total_pages'] > 1) {
    $output .= "<a href='" . $pager_results["next_page_url"] . "' class='btn btn-xs btn-default btn-pager next-page width-fit btn-flex' title='"._('Next Page')."'><i class='material-symbols-outlined md-20 md-400 md-middle'>keyboard_arrow_right</i></a>";
    $output .= "<a href='" . $pager_results["last_page_url"] . "' class='btn btn-xs btn-default btn-pager last-page width-fit btn-flex' title='"._('Last Page')."'><i class='material-symbols-outlined md-20 md-400 md-middle'>keyboard_double_arrow_right</i></a>";
    }

    $output .= '</div>';
    return $output;
}


////////////////////////////////////////////////////////////////////////
// NEW TABLE FUNCTIONS
////////////////////////////////////////////////////////////////////////


/**
 * @param string $url
 * @param null   $pager_info
 *
 * @return array
 */
function get_record_pager_url_info($url = "", $pager_info = null)
{

    // construct the base url
    $theurl = $url;
    $theurl .= "?records=" . $pager_info["records_per_page"] . "";
    foreach ($args as $k => $v) {
        $theurl .= "&amp;" . urlencode($k) . "=" . urlencode($v);
    }

    // construct urls
    $first_page_url = $theurl . "&amp;page=1";
    $last_page_url = $theurl . "&amp;page=" . $pager_info["total_pages"];
    $next_page_url = $theurl . "&amp;page=" . $pager_info["next_page"];
    $prev_page_url = $theurl . "&amp;page=" . $pager_info["previous_page"];

    // return array
    $result = array(
        "base_url" => $theurl,
        "first_page_url" => $first_page_url,
        "last_page_url" => $last_page_url,
        "next_page_url" => $next_page_url,
        "previous_page_url" => $prev_page_url,
    );

    return $result;
}


/**
 * @param int  $total_records
 * @param int  $current_page
 * @param int  $records_per_page
 * @param null $args
 * @return array
 */
function get_record_pager_info($total_records = 0, $current_page = 1, $records_per_page = 10, $args = null)
{

    // clean vars
    $total_records = intval($total_records);
    $current_page = intval($current_page);
    $records_per_page = intval($records_per_page);

    // adjust records per page if necessary
    if ($records_per_page < 1)
        $records_per_page = 1;

    // calculate total pages
    $total_pages = ceil($total_records / $records_per_page);

    // adjust current page if necessary
    if ($current_page <= 0)
        $current_page = 1;
    if ($current_page > $total_pages)
        $current_page = $total_pages;

    // calculate next/previous page numbers
    $previous_page = $current_page - 1;
    if ($previous_page < 1)
        $previous_page = 1;
    $next_page = $current_page + 1;
    if ($next_page > $total_pages)
        $next_page = $total_pages;

    // calculate first and last records
    $first_record = (($current_page - 1) * $records_per_page) + 1;
    $last_record = ($current_page * $records_per_page);
    if ($first_record < 0)
        $first_record = 0;
    if ($last_record > $total_records)
        $last_record = $total_records;

    // return array
    $result = array(
        "records_per_page" => $records_per_page,
        "current_page" => $current_page,
        "total_pages" => $total_pages,
        "next_page" => $next_page,
        "previous_page" => $previous_page,
        "total_records" => $total_records,
        "first_record" => $first_record,
        "last_record" => $last_record
    );

    return $result;
}


/**
 * @param      $pager_info
 * @param      $query
 * @param bool $show_clear
 * @param null $clear_url_args
 * @return string
 */
function get_record_count_text($pager_info, $query, $show_clear = false, $clear_url_args = null)
{

    $txt = "";
    $txt .= _("Showing") . " " . get_formatted_number($pager_info["first_record"]) . "-" . get_formatted_number($pager_info["last_record"]) . " " . _("of") . " " . get_formatted_number($pager_info["total_records"]);

    if (have_value($query)) {
        $txt .= " " . _("total matches for") . " '<b>" . htmlentities($query, ENT_COMPAT, 'UTF-8') . "</b>'";
    } else {
        $txt .= " " . _("total records");
    }

    if ($show_clear == true && have_value($query)) {
        $txt .= " <a href='" . get_current_page() . "?";
        foreach ($clear_url_args as $var => $val)
            $txt .= "&amp;" . urlencode($var) . "=" . urlencode($val);
        $txt .= "'>";
        $txt .= "<img src='" . theme_image("b_clearsearch.png") . "' border='0' alt='" . _("Clear Search Criteria") . "' title='" . _("Clear Search Criteria") . "'>";
        $txt .= "</a>";
    }

    return $txt;
}


/**
 * @param      $pager_results
 * @param null $record_options
 */
function display_record_pager($pager_results, $record_options = null)
{

    if (is_null($record_options))
        $record_options = array("5", "10", "25", "50");

    echo "<a href='" . $pager_results["first_page_url"] . "'><img src='" . theme_image("b_first.png") . "' border='0' title='" . _("First Page") . "'></a>&nbsp;&nbsp;";
    echo "<a href='" . $pager_results["previous_page_url"] . "'><img src='" . theme_image("b_prev.png") . "' border='0'  title='" . _("Previous Page") . "'></a>&nbsp;&nbsp;";

    echo _("Page") . " <input type='text' class='tablepagertextfield' name='page' size='3' value='" . $pager_results["current_page"] . "'>";
    echo " / " . get_formatted_number($pager_results["total_pages"], 0) . "&nbsp;&nbsp;";
    ?>

    <select class="pagerselect" name="records">
        <?php
        foreach ($record_options as $opt)
            echo "<option " . is_selected($pager_results["records_per_page"], $opt) . ">" . $opt . "</option>\n";
        ?>
    </select>

    <input type='submit' class='pagersubmitbutton' name='pagemove' value='Go'>
    <?php
    echo "<a href='" . $pager_results["next_page_url"] . "'><img src='" . theme_image("b_next.png") . "' border='0' title='" . _("Next Page") . "'></a>&nbsp;&nbsp;";
    echo "<a href='" . $pager_results["last_page_url"] . "'><img src='" . theme_image("b_last.png") . "' border='0' title='" . _("Last Page") . "'></a>&nbsp;&nbsp;";
}