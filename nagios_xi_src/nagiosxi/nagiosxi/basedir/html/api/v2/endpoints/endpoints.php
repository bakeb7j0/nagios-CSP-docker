<?php

// Add your new endpoints here!
require_once(dirname(__FILE__) . '/account/auth.php');
require_once(dirname(__FILE__) . '/account/language.php');
require_once(dirname(__FILE__) . '/account/session.php');
require_once(dirname(__FILE__) . '/account/theme.php');
require_once(dirname(__FILE__) . '/tools/personal.php');
require_once(dirname(__FILE__) . '/tools/common.php');
require_once(dirname(__FILE__) . '/app/bootstrap.php');
require_once(dirname(__FILE__) . '/app/menu.php');
require_once(dirname(__FILE__) . '/dashboards/dashboards.php');
require_once(dirname(__FILE__) . '/dashboards/copy_raw_import.php');
require_once(dirname(__FILE__) . '/product/branding.php');
require_once(dirname(__FILE__) . '/product/license.php');
require_once(dirname(__FILE__) . '/product/name_version_update.php');
require_once(dirname(__FILE__) . '/status/host_service.php');
require_once(dirname(__FILE__) . '/status/ip.php');
require_once(dirname(__FILE__) . '/status/program.php');
require_once(dirname(__FILE__) . '/status/system.php');
require_once(dirname(__FILE__) . '/users.php');