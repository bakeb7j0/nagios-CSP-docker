#!/bin/bash

. /usr/local/nagiosxi/var/xi-sys.cfg
eval $(/usr/bin/php /usr/local/nagiosxi/scripts/import_xiconfig.php)

echo "MySQL root password:                  $mysqlpass"
echo "MySQL nagiosxi password:              $cfg__db_info__nagiosxi__pwd"
echo "MySQL dbmaint_nagiosxi password:      $cfg__db_info__nagiosxi__dbmaint_pwd"
echo "MySQL ndoutils password:              $cfg__db_info__ndoutils__pwd"
echo "MySQL nagiosql password:              $cfg__db_info__nagiosql__pwd"