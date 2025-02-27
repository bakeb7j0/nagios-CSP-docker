<?php
//
// Copyright (c) 2008-2025 Nagios Enterprises, LLC. All rights reserved.
//

if (!isset($configwizards)) {
    $configwizards = array();
}

// Include all dashlets - only if we're in the UI
if (!defined("BACKEND") && !defined("SUBSYSTEM")) {
    $path = dirname(__FILE__) . "/configwizards/";
    $subdirs = scandir($path);

    foreach ($subdirs as $wizdirname) {
        if ($wizdirname == "." || $wizdirname == "..") {
            continue;
        }

        $directory = $path . $wizdirname;

        if (is_dir($directory)) {
            $configfile = $directory . "/$wizdirname.inc.php";

            if (file_exists($configfile)) {
                include_once($configfile);
            }
        }
    }
}

// Alphabetically sort configwizards by display title.

$wizardnames = array();

foreach ($configwizards as $wizard) {
    $wizardnames[] = $wizard['display_title'];
}

array_multisort($wizardnames, SORT_ASC, $configwizards);
