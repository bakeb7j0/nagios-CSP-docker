#!/bin/bash -e

#################################################################################################
#
# Purpose:
#
# Syncs up the MySql user passwords from /usr/local/nagiosxi/html/config.inc.php to 
# etc/xi-sys.cfg, /usr/local/nagvis/etc/nagvis.ini.php, /usr/local/nagios/etc/ndo.cfg,
# and resets the MySql password using the root MySql password from etc/xi-sys.cfg.
#
# Requirements:
# - The current root MySql password must be set in etc/xi-sys.cfg, to change the other passwords.
#
# Author: Laura Gute
#
#################################################################################################

# A little polish, with colors and font weight.
BOLD=$(tput bold)
NORMAL=$(tput sgr0)
CYAN='\033[0;36m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

# config.inc.php
nagiosxipwd=''
ndoutilspwd=''
nagiosqlpwd=''
dbmaintpwd=''

# xi-sys.cfg
realrootpass=''
nagiosxipass=''
dbmaintpass=''
nagiosqlpass=''
ndoutilspass=''

debug=false

#
# Grab passwords from the $cfg['db_info'] array definition in config.inc.php.
#
# /usr/local/nagiosxi/html/config.inc.php
#
# // DB-specific connection information
# $cfg['db_info'] = array(
#     "nagiosxi" => array(
#         "dbmaint_user" => 'dbmaint_nagiosxi',
#         "dbmaint_pwd" => 'DBMAINTDEFAULTPASSWORD',
#         "user" => 'nagiosxi',
#         "pwd" => 'NAGIOSXIDEFAULTPASSWORD',
#     ),
#     "ndoutils" => array(
#         "user" => 'ndoutils',
#         "pwd" => 'NDOUTILSDEFAULTPASSWORD',
#     ),
#     "nagiosql" => array(
#         "user" => 'nagiosql',
#         "pwd" => 'NAGIOSQLDEFAULTPASSWORD',
#     ),
# );
#
get_config_inc_passwords() {
    nagiosxiArray=$(tr '\r\n' '\0' </usr/local/nagiosxi/html/config.inc.php | grep -iPao "$cfg\[.db_info.\].*?\);" | tr -d '\0' | tr -d '\n')

    # Parse the passwords.
    nagiosxipwd=$(sed -E 's/.*["'"'"']user["'"'"'][ \t]+=>[ \t]+["'"'"']nagiosxi["'"'"'],[ \t]+["'"'"']pwd["'"'"'] => ["'"'"'](.+?)["'"'"'].*/\1/' <<< "${nagiosxiArray}" | sed -e 's/["'"'"'],.*//')
    ndoutilspwd=$(sed -E 's/.*["'"'"']user["'"'"'][ \t]+=>[ \t]+["'"'"']ndoutils["'"'"'],[ \t]+["'"'"']pwd["'"'"'] => ["'"'"'](.+?)["'"'"'].*/\1/' <<< "${nagiosxiArray}" | sed -e 's/["'"'"'],.*//')
    nagiosqlpwd=$(sed -E 's/.*["'"'"']user["'"'"'][ \t]+=>[ \t]+["'"'"']nagiosql["'"'"'],[ \t]+["'"'"']pwd["'"'"'] => ["'"'"'](.+?)["'"'"'].*/\1/' <<< "${nagiosxiArray}" | sed -e 's/["'"'"'],.*//')
    dbmaintpwd=$(sed -E 's/.*["'"'"']dbmaint_pwd["'"'"'][ \t]+=>[ \t]+["'"'"'](.+?)["'"'"'],.*/\1/' <<< "${nagiosxiArray}" | sed -e 's/["'"'"'],.*//')

    if [[ "$debug" == true ]]; then
        echo -e "\nconfig.inc.php MySql user passwords"
        echo "==================================="
        echo "nagiosxipwd [${nagiosxipwd}]"
        echo "ndoutilspwd [${ndoutilspwd}]"
        echo "nagiosqlpwd [${nagiosqlpwd}]"
        echo "dbmaintpwd  [${dbmaintpwd}]"
    fi
}

#
# Get the MySql root password and other user passwords from the etc/xi-sys.cfg.
#
# NOTE: The root password from etc/xi-sys.cfg vs var/xi-sys.cfg should be the same.
#
# @param    rootpass
#
get_xi_sys_passwords() {
    local rootpass=$1;

    local etc_mysqlpass=$(grep "mysqlpass=" /usr/local/nagiosxi/etc/xi-sys.cfg | sed -e "s/mysqlpass=//;s/'//g")
    local var_mysqlpass=$(grep "mysqlpass=" /usr/local/nagiosxi/var/xi-sys.cfg | sed -e "s/mysqlpass=//;s/'//g")

    if [[ "$debug" == true ]]; then
        echo -e "\netc/xi-sys.cfg MySql user passwords"
        echo "==================================="
        echo -e "${RED}realrootpass   [${rootpass}]${NC}"
    fi

    # Set the global variables.
    nagiosxipass=$(grep "nagiosxipass=" /usr/local/nagiosxi/etc/xi-sys.cfg | sed -e "s/nagiosxipass=//;s/'//g")
    dbmaintpass=$(grep "dbmaintpass=" /usr/local/nagiosxi/etc/xi-sys.cfg | sed -e "s/dbmaintpass=//;s/'//g")
    nagiosqlpass=$(grep "nagiosqlpass=" /usr/local/nagiosxi/etc/xi-sys.cfg | sed -e "s/nagiosqlpass=//;s/'//g")
    ndoutilspass=$(grep "ndoutilspass=" /usr/local/nagiosxi/etc/xi-sys.cfg | sed -e "s/ndoutilspass=//;s/'//g")

    if [[ "$debug" == true ]]; then
        echo -e "${BLUE}etc mysqlpass [${etc_mysqlpass}]${NC}"
        echo -e "${BLUE}var mysqlpass [${var_mysqlpass}]${NC}"
        echo "nagiosxipass  [${nagiosxipass}]"
        echo "dbmaintpass   [${dbmaintpass}]"
        echo "nagiosqlpass  [${nagiosqlpass}]"
        echo "ndoutilspass  [${ndoutilspass}]"
    fi
}

#
# Get the ndo password(s) from nagvis.ini.php & ndo.cfg
#
get_ndo_passwords() {
    nagvisdbpass=$(grep 'dbpass=' /usr/local/nagvis/etc/nagvis.ini.php | sed -e 's/dbpass=//;s/["'"'"']//g')
    ndodb_pass=$(grep 'db_pass=' /usr/local/nagios/etc/ndo.cfg | sed -e 's/db_pass=//;s/"'"'"']//g')

    if [[ "$debug" == true ]]; then
        echo -e "\n nagvis.ini.php & ndo.cfg passwords"
        echo "==================================="
        echo "nagvisdbpass [${nagvisdbpass}]"
        echo "ndodb_pass   [${ndodb_pass}]"
    fi
}

#
# Use the MySql root password $mysqlpass, to sync the other user passwords to config.inc.php
#
# /usr/local/nagiosxi/html/config.inc.php
#
# // DB-specific connection information
# $cfg['db_info'] = array(
#     "nagiosxi" => array(
#         "dbmaint_user" => 'dbmaint_nagiosxi',
#         "dbmaint_pwd" => 'DBMAINTDEFAULTPASSWORD',
#         "user" => 'nagiosxi',
#         "pwd" => 'NAGIOSXIDEFAULTPASSWORD',
#     ),
#     "ndoutils" => array(
#         "user" => 'ndoutils',
#         "pwd" => 'NDOUTILSDEFAULTPASSWORD',
#     ),
#     "nagiosql" => array(
#         "user" => 'nagiosql',
#         "pwd" => 'NAGIOSQLDEFAULTPASSWORD',
#     ),
# );
#
#
# @param    rootpass
#
sync_using_config() {
    local dbserver=$1;
    local dbport=$2;
    local rootpass=$3;

    if [[ "$debug" == true ]]; then
        echo -e "${RED}rootpass     [${rootpass}]${NC}"
    fi

    # Credentials for MySql - prevents warning about password on the command line.
    # For use with --defaults-extra-file or ---defaults-file
    config='[client]\nhost='"${dbserver}"'\nport='"${dbport}"'\nuser=root\npassword='"${rootpass}"

    # Get the MySql version, preserving multi-dot formats.
echo "sync_using_config: version"
    mysqlversion=$(mysql --defaults-extra-file=<(echo -e $config) -BNe "select @@version;" | cut -d '-' -f 1)
    res=$?

    if [ $res != 0 ]; then
        echo "Error determining MySql version.  Exiting..."
        exit 1
    fi

    # Update the MySql passwords for the users with the config.inc.php pwd(s).
    # Slight overkill, just in case we need to check multi dot version strings.
    if [[ $(echo "$mysqlversion 5.7" | tr " " "\n" | sort --version-sort | head -n 1) = 5.7 ]]; then 
echo "sync_using_config: version >= 5.7"
        mysql --defaults-extra-file=<(echo -e $config) -e "ALTER USER 'nagiosxi'@'localhost' IDENTIFIED BY '${nagiosxipwd}';"
        mysql --defaults-extra-file=<(echo -e $config) -e "ALTER USER 'ndoutils'@'localhost' IDENTIFIED BY '${ndoutilspwd}';"
        mysql --defaults-extra-file=<(echo -e $config) -e "ALTER USER 'nagiosql'@'localhost' IDENTIFIED BY '${nagiosqlpwd}';"
        mysql --defaults-extra-file=<(echo -e $config) -e "ALTER USER 'dbmaint_nagiosxi'@'localhost' IDENTIFIED BY '${dbmaintpwd}';"
    else
echo "sync_using_config: version < 5.7"
        mysql --defaults-extra-file=<(echo -e $config) -e "SET PASSWORD FOR 'nagiosxi'@'localhost' = PASSWORD('${nagiosxipwd}');"
        mysql --defaults-extra-file=<(echo -e $config) -e "SET PASSWORD FOR 'ndoutils'@'localhost' = PASSWORD('${ndoutilspwd}');"
        mysql --defaults-extra-file=<(echo -e $config) -e "SET PASSWORD FOR 'nagiosql'@'localhost' = PASSWORD('${nagiosqlpwd}');"
        mysql --defaults-extra-file=<(echo -e $config) -e "SET PASSWORD FOR 'dbmaint_nagiosxi'@'localhost' = PASSWORD('${dbmaintpwd}');"
    fi

    # Update the MySql passwords for ndo in nagvis.inc.php & ndo.cfg with the pwd from config.inc.php.
    sed -i "s/$(grep 'dbpass=' '/usr/local/nagvis/etc/nagvis.ini.php')/dbpass=\"${ndoutilspwd}\"/" /usr/local/nagvis/etc/nagvis.ini.php
    sed -i "s/$(grep 'db_pass=' '/usr/local/nagios/etc/ndo.cfg')/db_pass=${ndoutilspwd}/" /usr/local/nagios/etc/ndo.cfg

    #
    # Update passwords in xi-sys.cfg
    #

    # Update the MySql passwords for the users in xi-sys.cfg with the config.inc.php pwd(s).
    sed -i "s/$(grep 'nagiosxipass=' '/usr/local/nagiosxi/etc/xi-sys.cfg')/nagiosxipass=${nagiosxipwd}/" /usr/local/nagiosxi/etc/xi-sys.cfg
    sed -i "s/$(grep 'dbmaintpass=' '/usr/local/nagiosxi/etc/xi-sys.cfg')/dbmaintpass=${dbmaintpwd}/" /usr/local/nagiosxi/etc/xi-sys.cfg
    sed -i "s/$(grep 'nagiosqlpass=' '/usr/local/nagiosxi/etc/xi-sys.cfg')/nagiosqlpass=${nagiosqlpwd}/" /usr/local/nagiosxi/etc/xi-sys.cfg
    sed -i "s/$(grep 'ndoutilspass=' '/usr/local/nagiosxi/etc/xi-sys.cfg')/ndoutilspass=${ndoutilspwd}/" /usr/local/nagiosxi/etc/xi-sys.cfg
    sed -i "s/$(grep 'mysqlpass=' '/usr/local/nagiosxi/etc/xi-sys.cfg')/mysqlpass=${rootpass}/" /usr/local/nagiosxi/etc/xi-sys.cfg

    sed -i "s/$(grep 'mysqlpass=' '/usr/local/nagiosxi/var/xi-sys.cfg')/mysqlpass=${rootpass}/" /usr/local/nagiosxi/var/xi-sys.cfg
}

###
# sync_mysql_passwords()
# 
# 1. Gather the mysql user passwords from config.inc.php
# 2. Gather the passwords from etc/xi-sys.cfg
# 3. Update the MySql XI user passwords using the values from config.inc.php
# 4. Update the ndo password in the nagvis.ini.php
# 5. Update the ndo password in the ndo.cfg
# 6. Restart the appropriate processes.
# 7. Repeat step 1 - so the user can see the result.
# 8. Repeat step 2 - so the user can see the result.
#
###

############################################################
# Main program                                             #
############################################################

# Get the MySql root password etc/xi-sys.cfg.
realrootpass=$(grep "mysqlpass=" /usr/local/nagiosxi/etc/xi-sys.cfg | sed -e "s/mysqlpass=//;s/'//g")

VALID_ARGS=$(getopt -o "d" --long "hostname:,dbport:,rootpwd:,debug" -- "$@")

# Seems to be redundant, since getopt handles invalid arguments.
if [[ ! $? ]]; then
    echo "VALID_ARGS ERROR!"
    exit 1;
fi

# Get the options
# NOTE: $realrootpass can be changed with --rootpwd
eval set -- "$VALID_ARGS"
while : 
do
    case "$1" in
        --hostname)     hostname=$2; shift 2 ;;
        --dbport)       dbport=$2; shift 2 ;;
        --rootpwd)      realrootpass=$2; shift 2 ;;
        -d | --debug)   debug=true; shift ;;
        --) break ;;
         *) echo "Internal error!" ; exit 1 ;;
    esac
done

if [[ -z $hostname ]]; then
    echo "ERROR: hostname is required!"
    exit 1
fi

if [[ -z $dbport ]]; then
    echo "ERROR: dbport is required!"
    exit 1
fi

if [[ -z $realrootpass ]]; then
    echo "ERROR: rootpwd is required!"
    exit 1
fi

if [[ "$debug" == true ]]; then
    echo "realrootpass [${realrootpass}]"
fi

get_config_inc_passwords
get_xi_sys_passwords "${realrootpass}"
get_ndo_passwords

sync_using_config "${hostname}" "${dbport}" "${realrootpass}"

if [[ "$debug" == true ]]; then
    echo -e "\n###################################"
    echo "Final Passwords"
    echo "###################################"
    get_config_inc_passwords
    get_xi_sys_passwords
    get_ndo_passwords
fi
