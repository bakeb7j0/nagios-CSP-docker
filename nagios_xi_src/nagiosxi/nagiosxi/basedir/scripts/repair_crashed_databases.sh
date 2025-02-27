#!/bin/bash
#
# Repair MySQL Databases
# Copyright (c) 2024 Nagios Enterprises, LLC. All rights reserved.
#

# This script is a set of steps in the Nagios Support "playbook", that have been compiled and proven
# by Nagios Support, to fix most of the "fatal" database corruption issues encountered by Nagios XI users.
#
# NOTE: 90%+ of database corruption issues are caused by running out of disk space.
#
# TODO:
#   - Check and handle return codes
#   - Output steps
#   - Send output to a log file
#
# Usage:
# repair_corrupt_mysql.sh

BASEDIR=$(dirname $(readlink -f $0))

# Import Nagios XI and xi-sys.cfg config vars
#
# NOTE: To run this on a dev box, add the real mysqlpass to the end of nagiosxi/basedir/var/xi-sys.cfg.
. $BASEDIR/../var/xi-sys.cfg
#echo "mysqlpass [${mysqlpass}]"    # Uncomment to verify the root password

# Setup the credentials for MySql - prevents warning about password on the command line.
# For use with --defaults-extra-file or ---defaults-file
config='[client]\nuser=root\npassword='"${mysqlpass}"

# Check if running this script is warranted - Sprint 2
check_mysql () {
    # Get the location of the MySQL/MariaDB data.
    datadir=$(mysql -u root --password=${mysqlpass} -e 'SELECT @@datadir;')

    #Filesystem      Size  Used Avail Use% Mounted on
    #/dev/nvme0n1p3   36G   16G   20G  45% /
    diskfree=$(df -h /var/lib/mysql)

    crashCheck=$(mysql --defaults-extra-file=<(echo -e $config) nagios -BNe "show table status where comment like '%crash%';" nagiosql mysql -uroot -p"$MYSQLROOTPASS" -e "show table status where comment like '%crash%';" nagiosxi mysql -uroot -p"$MYSQLROOTPASS" -e "show table status where comment like '%crash%';" nagios)
    mysql --defaults-extra-file=<(echo -e $config) nagios -BNe 'truncate nagios_hoststatus; truncate nagios_hosts; truncate nagios_services; truncate nagios_servicestatus; truncate nagios_servicechecks;'
}

playbook() {
    # Setup the credentials for MySql - prevents warning about password on the command line.
    # For use with --defaults-extra-file or ---defaults-file
    config='[client]\nuser=root\npassword='"${mysqlpass}"

    #
    # Support "playbook" for serious DB Corruption issues.
    #
    # START PLAYBOOK
    #

    # NPCD
    echo "Stopping npcd"
    output=$(systemctl stop npcd)

    if [ -n "${output}" ]; then
        echo "${output}"
    fi

    # NAGIOS
    echo "Stopping nagios"
    output=$(systemctl stop nagios)

    if [ -n "${output}" ]; then
        echo "${output}"
    fi

    # CRON
    cronservice=$(systemctl list-unit-files | grep "^cron" | awk '{print $1}')

    if [ -n "${cronservice}" ]; then
        echo "Stopping ${cronservice}"
        output=$(systemctl stop "${cronservice}")

        if [ -n "${output}" ]; then
            echo "${output}"
        fi
    else
        echo "ERROR: Restarting Stopping Cron. Service not found!"
    fi

    # These tables repopulate when nagios starts.
    echo "Truncating tables"
    mysql --defaults-extra-file=<(echo -e $config) nagios -BNe 'truncate nagios_hoststatus; truncate nagios_hosts; truncate nagios_services; truncate nagios_servicestatus; truncate nagios_servicechecks;'
    mysql --defaults-extra-file=<(echo -e $config) nagiosxi -BNe 'truncate table xi_events; truncate table xi_meta; truncate table xi_eventqueue;'

    # --force
    # --repair
    # --use-frm: use the data dictionary to repair MyISAM tables.
    #
    # NOTE: This does nothing to repair the InnoDB tables.
    #
    # TODO: Does this need to handle offloaded DBs?
    echo "Attempting to repair MyISAM tables"
    mysqlcheck --defaults-extra-file=<(echo -e $config) --force --repair --all-databases --use-frm

    # Adding OPTIMIZE TABLES to shrink InnoDB tables.
    echo "Attempting to repair InnoDB tables"
    mysqlcheck --defaults-extra-file=<(echo -e $config) --force --optimize --databases nagios
    mysqlcheck --defaults-extra-file=<(echo -e $config) --force --optimize --databases nagiosql
    mysqlcheck --defaults-extra-file=<(echo -e $config) --force --optimize --databases nagiosxi

    # MYSQL/MARIADB
    # This may help with corrupted InnoDB tables.
    sqlservice=$(systemctl --type=service | grep -E "(mysql|mariadb)" | awk '{print $1}')

    if [ -n "${sqlservice}" ]; then
        echo "Restarting ${sqlservice}"
        output=$(systemctl restart "${sqlservice}")

        if [ -n "${output}" ]; then
            echo "${output}"
        fi
    else
        echo "ERROR: Restarting Database Service. Service not found!"
    fi

    echo "Removing lock & temporary files"
    rm -f /usr/local/nagios/var/rw/nagios.cmd
    rm -f /usr/local/nagios/var/nagios.lock
    rm -f /var/run/nagios.lock
    rm -f /var/lib/mrtg/mrtg_l
    rm -f /usr/local/nagiosxi/var/*.lock
    rm -f /usr/local/nagiosxi/tmp/*.lock

    echo "Clearing the Message Queues"
    for i in `ipcs -q | grep nagios | awk '{print $2}'`; do ipcrm -q $i; done

    # PYTHON 
    echo "Killing python processes"
    pkill python

    # APACHE processes
    apacheuser=$(ps -e -o uname | grep -E "(apache|www-data)" | head -1)

    if [ -n "${apacheuser}" ]; then
        echo "Killing the ${apacheuser} user's processes"
        output=$(pkill -9 -u "${apacheuser}")

        if [ -n "${output}" ]; then
            echo "${output}"
        fi
    fi

    # APACHE
    apacheservice=$(systemctl --type=service | grep -E "(http|apache2)" | awk '{print $1}')

    if [ -n "${apacheservice}" ]; then
        echo "Restarting ${apacheservice}"
        output=$(systemctl restart "${apacheservice}")

        if [ -n "${output}" ]; then
            echo "${output}"
        fi
    else
        echo "ERROR: Restarting Apache Service. Service not found!"
    fi

    # PHPFPM - RHEL, only
    phpfpmservice=$(systemctl --type=service | grep "php.*fpm" | awk '{print $1}')

    if [ -n "${phpfpmservice}" ]; then
        echo "Restarting ${phpfpmservice}"
        output=$(systemctl restart "${phpfpmservice}")

        if [ -n "${output}" ]; then
            echo "${output}"
        fi
    fi

    # NPCD
    echo "Starting npcd"
    output=$(systemctl start npcd)

    if [ -n "${output}" ]; then
        echo "${output}"
    fi

    # CRON
    if [ -n "${cronservice}" ]; then
        echo "Starting ${cronservice}"
        output=$(systemctl start "${cronservice}")

        if [ -n "${output}" ]; then
            echo "${output}"
        fi
    else
        echo "ERROR: Restarting Stopping Cron. Service not found!"
    fi

    # NAGIOS
    echo "Starting nagios"
    output=$(systemctl start nagios)

    if [ -n "${output}" ]; then
        echo "${output}"
    fi

    #
    # END PLAYBOOK
    #
}

verify_mysql() {
    # Check status of MySQL and verify
    ret=$($BASEDIR/manage_services.sh status mysqld)
    mysqlstatus=$?

    if [ ! $mysqlstatus -eq 0 ]; then
        rm -f /var/lib/mysql/mysql.sock
    fi

    echo " "
    echo "==============="
    echo "REPAIR COMPLETE"
    echo "==============="

    exit $exit_code
}

############################################################
# Display Help                                             #
############################################################

help() {
    echo
    echo "This script attempts to repair corrupted tables in the Nagios XI MySQL databases."
    echo
    echo "Usage:"
    echo "    repair_corrupt_mysql.sh"
    echo "         [-h|--help]"
    echo
    echo "-h|--help         Print this Help."
    echo
}

############################################################
# Process the input options. Add options as needed.        #
############################################################

# Break up the args list, to make it more readable.
argsList=$(printf '%s' \
    'help')

VALID_ARGS=$(getopt -o h --long "${argsList}" -- "$@")

# Seems to be redundant, since getopt handles invalid arguments.
if [[ ! $? ]]; then
    echo "VALID_ARGS ERROR!"
    exit 1;
fi

# Get the options
eval set -- "$VALID_ARGS"
while : 
do
    case "$1" in
        -h | --help)        help; exit ;;
        --) break ;;
    esac
done

############################################################
# Attempt to repair the XI databases.                      #
############################################################

#check_mysql
playbook
verify_mysql
