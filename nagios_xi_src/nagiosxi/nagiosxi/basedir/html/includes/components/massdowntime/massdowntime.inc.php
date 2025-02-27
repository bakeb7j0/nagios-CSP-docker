<?php
//
// Mass Downtime Component
// Copyright (c) 2024-2024 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/../componenthelper.inc.php');

$massdowntime_component_name = "massdowntime";
massdowntime_component_init();

////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////

function massdowntime_component_init()
{
    global $massdowntime_component_name;
    $versionok = massdowntime_component_checkversion();
    $desc = _("This component allows administrators to schedule downtime for mass hosts and services.");

    if (!$versionok) {
        $desc = "<b>" . _("Error: This component requires Nagios XI 2024R1.2 or later.") . "</b>";
    }

    $args = array(
        COMPONENT_NAME => $massdowntime_component_name,
        COMPONENT_VERSION => '1.0.0',
        COMPONENT_DATE => '08/06/2024',
        COMPONENT_AUTHOR => "Nagios Enterprises, LLC",
        COMPONENT_DESCRIPTION => $desc,
        COMPONENT_TITLE => _("Mass Downtime"),
        COMPONENT_REQUIRES_VERSION => 60200
    );

    // Register this component with XI
    register_component($massdowntime_component_name, $args);

    // Register the addmenu function
    if ($versionok) {
        register_callback(CALLBACK_MENUS_INITIALIZED, 'massdowntime_component_addmenu');
    }
}

function massdowntime_component_checkversion()
{
    if (!function_exists('get_product_release'))
        return false;
    if (get_product_release() < 60200)
        return false;
    return true;
}

function massdowntime_component_addmenu($arg = null)
{
    if (is_readonly_user(0)) {
        return;
    }

    global $massdowntime_component_name;
    $urlbase = get_component_url_base($massdowntime_component_name);

    $mi = find_menu_item(MENU_HOME, "menu-home-acknowledgements", "id");
    if ($mi == null) {
        return;
    }

    $order = grab_array_var($mi, "order", "");
    if ($order == "") {
        return;
    }

    $neworder = $order + 0.1;

    add_menu_item(MENU_HOME, array(
        "type" => "link",
        "title" => _("Mass Downtime"),
        "id" => "menu-home-massdowntime",
        "order" => $neworder,
        "opts" => array(
            "href" => $urlbase . "/index.php"
        )
    ));
}