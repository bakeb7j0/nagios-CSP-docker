<?php

function xi6_tooltip($text, $icon = 'info', $filled = false, $offset = true) {
    $noOffset = '';

    if ($icon == 'question') {
        $iconClause = $filled ? "icon-$icon-filled" : "icon-$icon";
    } else {
        $iconClause = $filled ? "icon-$icon-fill" : "icon-$icon";
    }

    if (!$offset) {
        $noOffset = "no_offset";
    }

    $tooltip = "<i data-bs-toggle=\"tooltip\" data-bs-html=\"true\" data-bs-placement=\"top\" title=\"$text\" class=\"icon $iconClause icon-small $noOffset\"></i>";

    return $tooltip;
}

function xi6_info_tooltip($text, $filled = false, $offset = true) {
    return xi6_tooltip($text, 'info', $filled, $offset);
}

function xi6_title_tooltip($text) {
    $tooltip = ' data-bs-toggle="tooltip" data-bs-placement="top" title="'.$text.'"';
    return $tooltip;
}

?>