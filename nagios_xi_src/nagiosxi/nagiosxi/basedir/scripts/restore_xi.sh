#!/bin/bash
#
# Restores a Full Backup of Nagios XI
# Copyright (c) 2008-2020 Nagios Enterprises, LLC. All rights reserved.
#
RED='\033[0;31m'

# MySQL root password
themysqlpass=$(grep "mysqlpass=" /usr/local/nagiosxi/var/xi-sys.cfg | sed -e "s/mysqlpass=//;s/'//g")

# Uncomment and set to this server's MySQL root password (if it is different from the one in var/xi-sys.cfg).
#themysqlpass=""

# Tests mysql connection by opening a connection and selecting the DB we want to use
test_mysql_connection() {
    local db_host="$1"
    local db_port="$2"
    local db_username="$3"
    local db_password="$4"

    db_error=$(mysql -h "${db_host}" --port="${db_port}" -u "${db_username}" --password="${db_password}" -e "show databases;" 2>&1)

    return $?
}

# Test connecting to mysql to make sure we can connect, before continuing
verify_mysql_password () {
    local dbserver=$1
    local dbport=$2
    local testpassword=$3

    x=1

    while [ $x -le 5 ]; do
        test_mysql_connection "${dbserver}" "${dbport}" "root" "${testpassword}"

        if [ $? == 1 ]; then
            echo "ERROR: Could not connect to ${dbserver}:${dbport} with root password supplied."
            read -s -r -p "Please enter the MySQL root password: " testpassword
            echo ""
        else
            mysqlpass="$testpassword"
            break
        fi

        if [ $x -eq 5 ]; then
            echo "ERROR: Aborting restore: Could not connect to MySQL."
            echo "${db_error}"
            exit 1
        fi

        x=$((x+1))
    done
}

###############################################################################################
# Handle CHARACTER SET and COLLATE differences, when unsupported values are specified in the
# nagiosxi CREATE DATABASE statement, in the mysqldump backup file.
#
# ISSUE: Some MySql dumps specify the CHARACTER SET and/or COLLATE for the nagiosxi
#        Create Database statement.  This can cause the restore process to fail, during
#        restore on a new XI server when the Database version's collations are not compatible.
#        e.g., when the CHARACTER SET and/or COLLATE values are unsupported/unknown.
#
# EXAMPLE:
#        Restoring a dump from Ubuntu 22 to Debian 10 fails, because Ubuntu 22's mysqldump sets
#        the COLLATE for the nagiosxi database to "utf8mb4_0900_ai_ci", which is not supported
#        by the MySql/MariaDB version available on Debian 10.
#
# @param dbserver           # Ip address/hostname of database server
# @param dbport             # Database Server listening port
# @param sqlpass            # MySql root password
# @param mysql_dump_file    # SQL dump file to process
# @param debug  true        # Default: false
#
fix_collation_issues() {
    local dbserver=$1
    local dbport=$2
    local sqlpass=$3
    local mysql_dump_file=$4
    local debug=$5

    # Find the CREATE DATABASE command in the db backup file and grab the CHARACTER SET and COLLATE values (if set).
    # CREATE DATABASE /*!32312 IF NOT EXISTS*/ `nagiosxi`
    # /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
    create_db_stmt=$(grep -i "CREATE DATABASE" "${mysql_dump_file}")
    database=$(sed -E 's/CREATE DATABASE .*`(.*)`.*/\1/' <<< "${create_db_stmt}")
    backup_db_collate=$(grep -iPao "COLLATE .*? " <<< "${create_db_stmt}" | cut -d' ' -f 2)
    backup_db_character_set=$(grep -iPao "DEFAULT CHARACTER SET .*? " <<< "${create_db_stmt}" | cut -d' ' -f 4)

    if [[ "$debug" == true ]]; then
        echo "database [$database]"
        echo "backup_db_collate [$backup_db_collate]"
        echo "backup_db_character_set [$backup_db_character_set]"
    fi

    # If Collation is not specified in the backup, nothing to do.
    # NOTE: Character Set probably not an issue, since character set is the first part of the name of the collation.
    if [[ -z "${backup_db_collate}" ]]; then
        return 0
    fi

    # Credentials for MySql - prevents warning about password on the command line.
    # For use with --defaults-extra-file or ---defaults-file
    config='[client]\nhost='"${dbserver}"'\nport='"${dbport}"'\nuser=root\npassword='"${sqlpass}"

    # Does this DB support the specified COLLATION from the mysqldump?
    check_collate=$(mysql --defaults-extra-file=<(echo -e $config) -BNe 'USE '"${database}"'; show collation where `Collation` = "'"${backup_db_collate}"'";' | cut -f 1)
    check_character_set=$(mysql --defaults-extra-file=<(echo -e $config) -BNe 'USE '"${database}"'; show character set where `Charset` = "'"${backup_db_character_set}"'";' | cut -f 1)

    # If Collation is the same, nothing to do.
    # NOTE: Character Set probably not an issue, since character set is the first part of the name of the collation.
    if [[ "${backup_db_collate}" == "${check_collate}" ]]; then
        return 0
    fi

    if [[ "$debug" == true ]]; then
        # Get the default COLLATE and CHARACTER SET for this DB server.
        this_db_collate=$(mysql --defaults-extra-file=<(echo -e $config) -BNe 'USE '"${database}"'; SELECT @@COLLATION_CONNECTION;')
        this_db_character_set=$(mysql --defaults-extra-file=<(echo -e $config) -BNe 'USE '"${database}"'; SELECT @@CHARACTER_SET_CLIENT;')

        echo "check_collate [$check_collate]"
        echo "this_db_collate [$this_db_collate]"
        echo "check_character_set [$check_character_set]"
        echo "this_db_character_set [$this_db_character_set]"
    fi

    # If changes are necessary, create a backup of the original db backup file.
    if [[ -n "${backup_db_collate}" && -z "${check_collate}" || (-n "${backup_db_character_set}" && -z "${check_character_set}") ]]; then

        # Handle the condition where backup file COLLATION is specified and not supported.
        if [ -n "${backup_db_collate}" ] && [[ -z "${check_collate}" ]]; then
            if [[ "$debug" == true ]]; then
                echo "UNSUPPORTED COLLATION: Searching for a collation in the [$this_db_character_set] character set."
            fi

            # Try to find an appropriate collation, for the character set.
            # TODO: What to do if can't match?  Error out here?  Check at the begining of the script so they don't get partway through?
            new_db_character_set="${backup_db_character_set}"
            new_db_collate=$(mysql --defaults-extra-file=<(echo -e $config) -BNe 'USE '"${database}"'; show collation where `Charset` = "'"${this_db_character_set}"'" and `Default` = "'"Yes"'";' | cut -f 1)

            if [[ "$debug" == true ]]; then
                echo "new_db_character_set [$new_db_character_set]"
                echo "new_db_collate [$new_db_collate]"
            fi

            # If the character_set from the backup file does not match, try again (utf8 ==> utf8mb4)
            # Make sure it is not utf8mb3 (deprecated).
            if [[ -n "${new_db_collate}" ]] || [[ -n "${new_db_character_set}" ]]; then
                new_db_character_set=$(mysql --defaults-extra-file=<(echo -e $config) -BNe 'USE '"${database}"'; show character set where `Charset` like "'"${backup_db_character_set}%"'" and `Charset` not like "%mb3";' | cut -f 1)
                new_db_collate=$(mysql --defaults-extra-file=<(echo -e $config) -BNe 'USE '"${database}"'; SHOW COLLATION WHERE `Default` = "Yes" and `Charset` like "'"${backup_db_character_set}%"'" and `Charset` not like "%mb3";' | cut -f 1)

                if [[ "$debug" == true ]]; then
                    echo "ISSUES with character set and collate"
                    echo "new_db_character_set [$new_db_character_set]"
                    echo "new_db_collate [$new_db_collate]"
                fi
            fi

            # If we still do not have a match, quit.
            if [[ -n "${new_db_collate}" ]]; then
                sed -i "s/COLLATE ${backup_db_collate} /COLLATE ${new_db_collate} /" "${mysql_dump_file}"
            else
                echo -e "${RED}ERROR: No compatible Database Collation for Character Set [$backup_db_character_set]!${NC}"
                exit 1;
            fi

            # Change the CHARACTER SET is specified and not supported.
            if [ -n "${backup_db_character_set}" ] && [[ -z "${check_character_set}" ]]; then
                sed -i "s/DEFAULT CHARACTER SET ${backup_db_character_set} /DEFAULT CHARACTER SET ${new_db_character_set} /" "${mysql_dump_file}"
            fi
        fi
    fi
}   #fix_collation_issues()

###############################################################################################

echoerror() { echo "$@" 1>&2; }

###############################################################################################
# Syncs up the MySql user passwords from /usr/local/nagiosxi/html/config.inc.php to 
# etc/xi-sys.cfg, /usr/local/nagvis/etc/nagvis.ini.php, /usr/local/nagios/etc/ndo.cfg,
# and resets the MySql password using the root MySql password from etc/xi-sys.cfg.
#
# @param user               # The database user for accessing this database
# @param dbserver           # Ip address/hostname of database server
# @param dbport             # Database Server listening port
# @param backupPWD          # The password for the user from the db backup file
# @param currentPWD         # The password for the user from the local database
# @param debug  true        # Default: false
#
get_or_set_correct_password() {
    user="$1"
    dbserver="$2"
    dbport="$3"
    backupPWD="$4"
    currentPWD="$5"
    debug=$6

    password="$currentPWD"

    if [[ "$debug" == true ]]; then
        echoerror "Syncronizing database passwords for MySQL user $user..."
    fi

    # I'm assuming the user and port and other configs from the backup database are correct
    if [ "$backupPWD" != "$currentPWD" ]; then
        if [[ "$debug" == true ]]; then
            echoerror "Database passwords for MySQL user $user unsyncronized..."
        fi

        if ! test_mysql_connection "$dbserver" "$dbport" "$user" "${currentPWD}"; then
            if test_mysql_connection "$dbserver" "$dbport" "$user" "${backupPWD}"; then
                if [[ "$debug" == true ]]; then
                    echoerror "Backup password correct"
                fi

                password="$backupPWD"
            else
                if [[ "$debug" == true ]]; then
                    echoerror "Backup and current passwords both incorrect. Resetting MySQL password to current password..."
                fi

                ip=$(mysql -h $dbserver --port="$dbport" -u root -p$mysqlpass -sse "SELECT host FROM mysql.user WHERE user='$user'")

                if [ -z "$ip" ]; then
                    ip=$(ip addr | grep global | grep -m 1 'inet' | awk '/inet[^6]/{print substr($2,0)}' | sed 's|/.*||')
                fi

                if [ -z "$ip" ]; then
                    ip=$(ip addr | grep global | grep -m 1 'inet' | awk '/inet6/{print substr($2,0)}' | sed 's|/.*||')
                fi

                # Credentials for MySql - prevents warning about password on the command line.
                # For use with --defaults-extra-file or ---defaults-file
                config='[client]\nhost='"${dbserver}"'\nport='"${dbport}"'\nuser=root\npassword='"${mysqlpass}"

                mysqlversion=$(mysql --defaults-extra-file=<(echo -e $config) -BNe "select @@version;" | cut -d '-' -f 1)
                res=$?

                if [ $res != 0 ]; then
                    echo "Error determining MySql version.  Exiting..."
                    exit 1
                fi

                if [[ $(echo "$mysqlversion 5.7" | tr " " "\n" | sort --version-sort | head -n 1) = 5.7 ]]; then 
                    mysql --defaults-extra-file=<(echo -e $config) -e "ALTER USER '$user'@'$ip' IDENTIFIED BY '${password}';"
                else
                    mysql --defaults-extra-file=<(echo -e $config) -e "SET PASSWORD FOR '$user'@'$ip' = PASSWORD('${password}');"
                fi
            fi
        else
            if [[ "$debug" == true ]]; then
                echoerror "Current password correct"
            fi
        fi
    fi

    echo -n "$password"
}

##############################
# Main program
##############################

##############################
# VERIFY BACKUP FILE EXISTS
##############################
# Make sure we have the backup file
if [ $# != 1 ]; then
    echo "Usage: $0 <backupfile>"
    echo "This script restores your XI system using a previously made Nagios XI backup file."
    exit 1
fi
backupfile=$1

BASEDIR=$(dirname $(readlink -f $0))
SPECIAL_BACKUP=0

# Import Nagios XI and xi-sys.cfg config vars
. $BASEDIR/../var/xi-sys.cfg
eval $(php $BASEDIR/import_xiconfig.php)

# Must be root
me=`whoami`
if [ $me != "root" ]; then
    echo "You must be root to run this script."
    exit 1
fi

rootdir=/store/backups/nagiosxi

##############################
# MAKE SURE BACKUP FILE EXIST
##############################
if [ ! -f $backupfile ]; then
    echo "Unable to find backup file $backupfile!"
    exit 1
fi

# Get the subdir here because we need it for the following checks
subdir=$(tar tf "$backupfile" |head -1 |cut -f 1 -d /)

# Look inside the (nested) tarball to see what architecture the nagios
# executable is
if [ $backupfile == "/store/backups/nagiosxi-demo.tar.gz" ]; then
    backuparch="x86_64"
else
    backuparch=$(eval $(echo $(tar -xzOf $backupfile $subdir/nagiosxi.tar.gz | tar -xzOf - usr/local/nagiosxi/var/xi-sys.cfg |cat|grep ^arch\=));echo $arch)
fi

arch=$(uname -m)
case $arch in
    i*86 )   arch="i686" ;;
    x86_64 ) arch="x86_64" ;;
    * )      echo "Error detecting architecture."; exit 1
esac

if [ "$arch" != "$backuparch" ]; then
    echo "WARNING: you are trying to restore a $backuparch backup on a $arch system"
    echo "         Compiled plugins and other binaries will NOT be restored."
    echo
    read -r -p "Are you sure you want to continue? [y/N] " ok

    case "$ok" in
        Y | y ) : ;;
        * )     exit 1
    esac
fi

backupdist=$(eval $(echo $(tar -xzOf $backupfile $subdir/nagiosxi.tar.gz | tar -xzOf - usr/local/nagiosxi/var/xi-sys.cfg |cat|grep ^dist\=));echo $dist)

if [ "$dist" != "$backupdist" ]; then
    SPECIAL_BACKUP=1

    echo "WARNING: you are trying to restore a $backupdist backup on a $dist system"
    echo "         Compiled plugins and other binaries as well as httpd configurations"
    echo "         will NOT be restored."
    echo ""
    echo "         You will need to re-download the Nagios XI tarball, and re-install"
    echo "         the subcomponents for this system. More info here:"
    echo "         https://assets.nagios.com/downloads/nagiosxi/docs/Backing-Up-And-Restoring-Nagios-XI.pdf"
    echo ""
    read -r -p "Are you sure you want to continue? [y/N] " ok

    case "$ok" in
        Y | y ) : ;;
        * )     exit 1
    esac
fi

# Get the backupmysqlpass here because some directory change stuff happens
backupmysqlpass="$(eval $(echo $(tar -xzOf $backupfile $subdir/nagiosxi.tar.gz | tar -xzOf - usr/local/nagiosxi/var/xi-sys.cfg |cat|grep ^mysqlpass\=));echo $mysqlpass)"

##############################
# MAKE TEMP RESTORE DIRECTORY
##############################
#ts=`echo $backupfile | cut -d . -f 1`
ts=`date +%s`
echo "TS=$ts"
mydir=${rootdir}/${ts}-restore
mkdir -p $mydir
if [ ! -d $mydir ]; then
    echo "Unable to create restore directory $mydir!"
    exit 1
fi

##############################
# UNZIP BACKUP
##############################
echo "Extracting backup to $mydir..."
tar xps -f "$backupfile" -C "$mydir"

# Change to subdirectory
cd "$mydir/$subdir"

# Make sure we have some directories here...
backupdir=`pwd`
echo "In $backupdir..."
if [ ! -f nagiosxi.tar.gz ]; then
    echo "Unable to find files to restore in $backupdir"
    exit 1
fi

echo "Backup files look okay.  Preparing to restore..."

##############################
# SHUTDOWN SERVICES
##############################
echo "Shutting down services..."
$BASEDIR/manage_services.sh stop nagios
$BASEDIR/manage_services.sh stop npcd

##############################
# RESTORE DIRS
##############################
rootdir=/
echo "Restoring directories to ${rootdir}..."

# Nagios Core
echo "Restoring Nagios Core..."
if [ "$arch" == "$backuparch" ] && [ $SPECIAL_BACKUP -eq 0 ]; then
    rm -rf /usr/local/nagios
    cd $rootdir && tar xzf $backupdir/nagios.tar.gz 
else
    rm -rf /usr/local/nagios/etc /usr/local/nagios/share /usr/local/nagios/var
    cd $rootdir && tar --exclude="usr/local/nagios/bin" --exclude="usr/local/nagios/sbin" --exclude="usr/local/nagios/libexec" -xzf $backupdir/nagios.tar.gz
    cd $rootdir && tar --wildcards 'usr/local/nagios/libexec/*' -xzf $backupdir/nagios.tar.gz
fi

# Restore ramdisk if it exists
if [ -f "$backupdir/ramdisk.nagios" ]; then
    echo "Updating ramdisk configuration..."
    cp  $backupdir/ramdisk.nagios /etc/sysconfig/nagios
fi

# Nagios XI
echo "Restoring Nagios XI..."

# I've decided I always want to keep this information
mv $BASEDIR/../var/xi-sys.cfg /tmp/xi-sys.cfg

if [ "$arch" == "$backuparch" ] && [ $SPECIAL_BACKUP -eq 0 ]; then
    rm -rf /usr/local/nagiosxi
    cd $rootdir && tar xzfps $backupdir/nagiosxi.tar.gz 
else
    mv $BASEDIR/../var/certs /tmp/certs
    mv $BASEDIR/../var/keys /tmp/keys

    rm -rf /usr/local/nagiosxi
    cd $rootdir && tar xzfps $backupdir/nagiosxi.tar.gz 

    # Check for certs
    mkdir -p $BASEDIR/../var/certs
    cp -r /tmp/certs $BASEDIR/../var/

    rm -rf /tmp/certs

    # Check for keys
    mkdir -p $BASEDIR/../var/keys
    if [ -f $BASEDIR/../var/keys/xi.key ]; then
        rm -f /tmp/keys/xi.key
    fi
    cp -r /tmp/keys $BASEDIR/../var/

    rm -rf /tmp/keys
fi

# Part of always keeping the xi-sys.cfg file
cp -r /tmp/xi-sys.cfg $BASEDIR/../var/xi-sys.cfg
cp -r /tmp/xi-sys.cfg $BASEDIR/../etc/xi-sys.cfg
chown root:$nagiosgroup $BASEDIR/../etc/xi-sys.cfg
chmod 550 $BASEDIR/../etc/xi-sys.cfg
rm -f /tmp/xi-sys.cfg

# NagiosQL
if [ -d "/var/www/html/nagiosql" ]; then

    echo "Restoring NagiosQL..."
    rm -rf /var/www/html/nagiosql
    cd $rootdir && tar xzfps $backupdir/nagiosql.tar.gz

    # NagiosQL etc
    echo "Restoring NagiosQL backups..."
    rm -rf /etc/nagiosql
    cd $rootdir && tar xzfps $backupdir/nagiosql-etc.tar.gz 
fi

# NRDP
echo "Restoring NRDP backups..."
rm -rf /usr/local/nrdp
cd $rootdir && tar xzfps $backupdir/nrdp.tar.gz

# MRTG
if [ -f $backupdir/mrtg.tar.gz ]; then
    echo "Restoring MRTG..."
    rm -rf /var/lib/mrtg
    cd $rootdir && tar xzfps $backupdir/mrtg.tar.gz 
    cp -rp $backupdir/conf.d /etc/mrtg/
    cp -p $backupdir/mrtg.cfg /etc/mrtg/
    chown $apacheuser:$nagiosgroup /etc/mrtg/conf.d /etc/mrtg/mrtg.cfg
fi
cd $backupdir

# TODO - Fix MIBS across platforms
# SNMP configs and MIBS
if [ -f $backupdir/etc-snmp.tar.gz ]; then
    echo "Restoring SNMP configuration files..."
    cd $rootdir && tar xzfps $backupdir/etc-snmp.tar.gz
fi

if [ -f $backupdir/usr-share-snmp.tar.gz ]; then
    echo "Restoring SNMP MIBs..."
    cd $rootdir && tar xzfps $backupdir/usr-share-snmp.tar.gz
fi

# Nagvis 
if [ -f $backupdir/nagvis.tar.gz ]; then 
    echo "Restoring Nagvis backups..." 
    rm -rf /usr/local/nagvis 
    cd $rootdir && tar xzfps $backupdir/nagvis.tar.gz 
    chown -R $apacheuser:$nagiosgroup /usr/local/nagvis 
fi 

# nagios user home
if [ -f $backupdir/home-nagios.tar.gz ]; then
    echo "Restoring nagios home dir..."
    cd $rootdir && tar xzfps $backupdir/home-nagios.tar.gz
fi

# RE-IMPORT ALL XI CFG VARS
# The $mysqlpass variable will be changed to the Donor password, after this command
# Since this is NOT the password for the Recipient's password, $mysqlpass will need to be reset, below.
. $BASEDIR/../var/xi-sys.cfg
php $BASEDIR/import_xiconfig.php > $BASEDIR/config.dat
. $BASEDIR/config.dat
rm -rf $BASEDIR/config.dat

###################################################################################################################################################################################
# We need to find the true root password. If the database is offloaded, we want to one from the backup tarball xi-sys.cfg, if it isn't, we want the one in the current xi-sys.cfg #
###################################################################################################################################################################################

# Making the assumption that the ndoutils dbport and server is the same as the others
if [[ "$cfg__db_info__ndoutils__dbserver" == *":"* ]]; then
    dbport=`echo "$cfg__db_info__ndoutils__dbserver" | cut -f2 -d":"`
    dbserver=`echo "$cfg__db_info__ndoutils__dbserver" | cut -f1 -d":"`
else
    dbport='3306'
    dbserver="$cfg__db_info__ndoutils__dbserver"
fi

echo "Syncronizing MySQL root password..."
if [ "$backupmysqlpass" != "$mysqlpass" ]; then
    echo "MySQL root passwords unsyncronized..."
    if ! test_mysql_connection "$dbserver" "$dbport" "root" "${mysqlpass}"; then
        if test_mysql_connection "$dbserver" "$dbport" "root" "${backupmysqlpass}"; then
            echo "Backup MySQL root password worked"
            mysqlpass="$backupmysqlpass"
        else
            verify_mysql_password "$dbserver" "$dbport" "${backupmysqlpass}"
        fi
    else
        echo "Current MySQL root password worked"
    fi
fi

# Make sure it's correct
sed -i "s/$(grep '^mysqlpass=' "$BASEDIR/../etc/xi-sys.cfg")/mysqlpass=${mysqlpass}/" $BASEDIR/../etc/xi-sys.cfg
sed -i "s/$(grep '^mysqlpass=' "$BASEDIR/../var/xi-sys.cfg")/mysqlpass=${mysqlpass}/" $BASEDIR/../var/xi-sys.cfg
sed -i "s/$(grep '^PASSWORD=' "/root/scripts/automysqlbackup")/PASSWORD=${mysqlpass}/" /root/scripts/automysqlbackup

##############################
# RESTORE DATABASES
##############################
echo "Restoring MySQL databases..."

########################
#    NDOUTILS TABLE    #
########################
echo "Restoring ndoutils"

if [[ "$cfg__db_info__ndoutils__dbserver" == *":"* ]]; then
    ndoutils_dbport=`echo "$cfg__db_info__ndoutils__dbserver" | cut -f2 -d":"`
    ndoutils_dbserver=`echo "$cfg__db_info__ndoutils__dbserver" | cut -f1 -d":"`
else
    ndoutils_dbport='3306'
    ndoutils_dbserver="$cfg__db_info__ndoutils__dbserver"
fi

# Sync passwords here
backup_ndoutilspass="$cfg__db_info__ndoutils__pwd"
ndoutilspass="$(get_or_set_correct_password "$cfg__db_info__ndoutils__user" "$ndoutils_dbserver" "$ndoutils_dbport" "$backup_ndoutilspass" "$ndoutilspass" true)"

# Make sure everything is correct
sed -i "s/$(grep '^ndoutilspass=' "$BASEDIR/../etc/xi-sys.cfg")/ndoutilspass=${ndoutilspass}/" $BASEDIR/../etc/xi-sys.cfg
sed -i "s/$(grep '^ndoutilspass=' "$BASEDIR/../var/xi-sys.cfg")/ndoutilspass=${ndoutilspass}/" $BASEDIR/../var/xi-sys.cfg
sed -i "/\"ndoutils\" => array/,/),/s/\"pwd\" => ['\"]$backup_ndoutilspass['\"]/\"pwd\" => '$ndoutilspass'/" "$BASEDIR/../html/config.inc.php"
sed -i "s/$(grep '^db_pass=' '/usr/local/nagios/etc/ndo.cfg')/db_pass=$ndoutilspass/" "/usr/local/nagios/etc/ndo.cfg"

if [ -f "/usr/local/nagvis/etc/nagvis.ini.php" ]; then
    sed -i "s/$(grep '^dbpass=' '/usr/local/nagvis/etc/nagvis.ini.php')/dbpass=\"$ndoutilspass\"/" /usr/local/nagvis/etc/nagvis.ini.php
fi

echo "BEFORE verify_mysql_password"
# Test mysql and see if we can connect before continuing
verify_mysql_password "$ndoutils_dbserver" "$ndoutils_dbport" "$mysqlpass"

# $backupdir/mysql/nagios.sql"
nagios_sql_backup="$backupdir/mysql/nagios.sql"

echo "BEFORE fix_collation_issues"
# Make sure the character set and collation are supported.
# dbserver address, dbport, sql root password, mysql dump/backup file, debug flag
fix_collation_issues "${ndoutils_dbserver}" "${ndoutils_dbport}" "${mysqlpass}" "${nagios_sql_backup}" true

# Credentials for MySql - prevents warning about password on the command line.
# For use with --defaults-extra-file or ---defaults-file
config='[client]\nhost='"${ndoutils_dbserver}"'\nport='"${ndoutils_dbport}"'\nuser=root\npassword='"${mysqlpass}"
mysql --defaults-extra-file=<(echo -e $config) < "$nagios_sql_backup"
res=$?

if [ $res != 0 ]; then
    echo "Error restoring MySQL database 'nagios'"
    exit 1
fi

########################
#    NAGIOSQL TABLE    #
########################
echo "Restoring nagiosql"

if [[ "$cfg__db_info__nagiosql__dbserver" == *":"* ]]; then
    nagiosql_dbport=`echo "$cfg__db_info__nagiosql__dbserver" | cut -f2 -d":"`
    nagiosql_dbserver=`echo "$cfg__db_info__nagiosql__dbserver" | cut -f1 -d":"`
else
    nagiosql_dbport='3306'
    nagiosql_dbserver="$cfg__db_info__nagiosql__dbserver"
fi

# Sync passwords here
backup_nagiosqlpass="$cfg__db_info__nagiosql__pwd"
nagiosqlpass="$(get_or_set_correct_password "$cfg__db_info__nagiosql__user" "$nagiosql_dbserver" "$nagiosql_dbport" "$backup_nagiosqlpass" "$nagiosqlpass" true)"

# Make sure everything is correct
sed -i "s/$(grep '^nagiosqlpass=' "$BASEDIR/../etc/xi-sys.cfg")/nagiosqlpass=${nagiosqlpass}/" $BASEDIR/../etc/xi-sys.cfg
sed -i "s/$(grep '^nagiosqlpass=' "$BASEDIR/../var/xi-sys.cfg")/nagiosqlpass=${nagiosqlpass}/" $BASEDIR/../var/xi-sys.cfg
sed -i "/\"nagiosql\" => array/,/),/s/\"pwd\" => ['\"]$backup_nagiosqlpass['\"]/\"pwd\" => '$nagiosqlpass'/" "$BASEDIR/../html/config.inc.php"

# Test mysql again and see if we can connect before continuing
verify_mysql_password "$nagiosql_dbserver" "$nagiosql_dbport" "$mysqlpass"

# $backupdir/mysql/nagiosql.sql"
nagiosql_sql_backup="$backupdir/mysql/nagiosql.sql"

# Make sure the character set and collation are supported.
# dbserver address, dbport, sql root password, mysql dump/backup file, debug flag
fix_collation_issues "${nagiosql_dbserver}" "${nagiosql_dbport}" "${mysqlpass}" "${nagiosql_sql_backup}" true

# Credentials for MySql - prevents warning about password on the command line.
# For use with --defaults-extra-file or ---defaults-file
config='[client]\nhost='"${nagiosql_dbserver}"'\nport='"${nagiosql_dbport}"'\nuser=root\npassword='"${mysqlpass}"
mysql --defaults-extra-file=<(echo -e $config) < "$nagiosql_sql_backup"
res=$?

if [ $res != 0 ]; then
    echo "Error restoring MySQL database 'nagiosql'"
    exit 1
fi

########################
#    NAGIOSXI TABLE    #
########################
echo "Restoring Nagios XI MySQL database..."

if [[ "$cfg__db_info__nagiosxi__dbserver" == *":"* ]]; then
    nagiosxi_dbport=`echo "$cfg__db_info__nagiosxi__dbserver" | cut -f2 -d":"`
    nagiosxi_dbserver=`echo "$cfg__db_info__nagiosxi__dbserver" | cut -f1 -d":"`
else
    nagiosxi_dbport='3306'
    if [ "x$cfg__db_info__nagiosxi__dbserver" == "x" ]; then
        nagiosxi_dbserver="localhost"
    else
        nagiosxi_dbserver="$cfg__db_info__nagiosxi__dbserver"
    fi
fi

# Nagiosxi user
backup_nagiosxipass="$cfg__db_info__nagiosxi__pwd"
nagiosxipass="$(get_or_set_correct_password "$cfg__db_info__nagiosxi__user" "$nagiosxi_dbserver" "$nagiosxi_dbport" "$backup_nagiosxipass" "$nagiosxipass" true)"

# Make sure everything is correct
sed -i "s/$(grep '^nagiosxipass=' "$BASEDIR/../etc/xi-sys.cfg")/nagiosxipass=${nagiosxipass}/" $BASEDIR/../etc/xi-sys.cfg
sed -i "s/$(grep '^nagiosxipass=' "$BASEDIR/../var/xi-sys.cfg")/nagiosxipass=${nagiosxipass}/" $BASEDIR/../var/xi-sys.cfg
sed -i "/\"nagiosxi\" => array/,/),/s/\"pwd\" => ['\"]$backup_nagiosxipass['\"]/\"pwd\" => '$nagiosxipass'/" "$BASEDIR/../html/config.inc.php"

# dbmaint user
backup_dbmaintpass="$cfg__db_info__nagiosxi__dbmaint_pwd"
dbmaintpass="$(get_or_set_correct_password "$cfg__db_info__nagiosxi__dbmaint_user" "$nagiosxi_dbserver" "$nagiosxi_dbport" "$backup_dbmaintpass" "$dbmaintpass" true)"

# Make sure everything is correct
sed -i "s/$(grep '^dbmaintpass=' "$BASEDIR/../etc/xi-sys.cfg")/dbmaintpass=${dbmaintpass}/" $BASEDIR/../etc/xi-sys.cfg
sed -i "s/$(grep '^dbmaintpass=' "$BASEDIR/../var/xi-sys.cfg")/dbmaintpass=${dbmaintpass}/" $BASEDIR/../var/xi-sys.cfg
sed -i "/\"nagiosxi\" => array/,/),/s/\"dbmaint_pwd\" => ['\"]$backup_dbmaintpass['\"]/\"dbmaint_pwd\" => '$dbmaintpass'/" "$BASEDIR/../html/config.inc.php"

# Test mysql again and see if we can connect before continuing
verify_mysql_password "$nagiosxi_dbserver" "$nagiosxi_dbport" "$mysqlpass"

nagiosxi_sql_backup="$backupdir/mysql/nagiosxi.sql"

# Make sure the character set and collation are supported.
# dbserver address, dbport, sql root password, mysql dump/backup file, debug flag
fix_collation_issues "${nagiosxi_dbserver}" "${nagiosxi_dbport}" "${mysqlpass}" "${nagiosxi_sql_backup}" true

# Credentials for MySql - prevents warning about password on the command line.
# For use with --defaults-extra-file or ---defaults-file
config='[client]\nhost='"${nagiosxi_dbserver}"'\nport='"${nagiosxi_dbport}"'\nuser=root\npassword='"${mysqlpass}"
mysql --defaults-extra-file=<(echo -e $config) < "$nagiosxi_sql_backup"
res=$?

if [ $res != 0 ]; then
    echo "Error restoring MySQL database 'nagiosxi' !"
    exit 1
fi

echo "Restarting database servers..."
$BASEDIR/manage_services.sh restart mysqld

##############################
# RESTORE CRONJOB ENTRIES
##############################
echo "Restoring Apache cronjobs..."
if [[ "$distro" == "Ubuntu" ]] || [[ "$distro" == "Debian" ]]; then
    cp $backupdir/cron/apache /var/spool/cron/crontabs/$apacheuser
else
    cp $backupdir/cron/apache /var/spool/cron/apache
fi

##############################
# RESTORE SUDOERS
##############################
# Not necessary

##############################
# RESTORE LOGROTATE
##############################
echo "Restoring logrotate config files..."
cp -rp $backupdir/logrotate/nagiosxi /etc/logrotate.d

##############################
# RESTORE APACHE CONFIG FILES
##############################
if [ $SPECIAL_BACKUP -eq 0 ]; then
    echo "Restoring Apache config files..."
    cp -rp $backupdir/httpd/nagios.conf $httpdconfdir
    cp -rp $backupdir/httpd/nagiosxi.conf $httpdconfdir
    cp -rp $backupdir/httpd/nagvis.conf $httpdconfdir
    cp -rp $backupdir/httpd/nrdp.conf $httpdconfdir
    if [ -d "/etc/apache2/sites-available" ]; then
        cp -rp $backupdir/httpd/default-ssl.conf /etc/apache2/sites-available
    else
        cp -rp $backupdir/httpd/ssl.conf $httpdconfdir
    fi
else
    echo "Skipping Apache config files restoration"
fi

##############################
# RESTART SERVICES
##############################
$BASEDIR/manage_services.sh restart httpd
$BASEDIR/manage_services.sh start npcd
$BASEDIR/manage_services.sh start nagios

# TODO: Is this appropriate to add to manage_services.sh?
phpfpmservice=$(systemctl --type=service | grep "php.*fpm" | sed -e 's/[ \t].*//')

# If there is a php-fpm service, restart it.
if [ -n "${phpfpmservice}" ]; then
    echo "Restarting ${phpfpmservice}"
    output=$(systemctl restart "${phpfpmservice}")

    if [ -n "${output}" ]; then
        echo "${output}"
    fi
fi

##############################
# DELETE TEMP RESTORE DIRECTORY
##############################
rm -rf "${mydir}"

echo " "
echo "==============="
echo "RESTORE COMPLETE"
echo "==============="

exit 0
