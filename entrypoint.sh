#!/bin/bash

PATH="$PATH:/usr/local/nagios/bin:/usr/local/nagios/libexec"

echo "Starting required services for Nagios XI..."

# Start MySQL (if it's not running externally)
if [ -x "$(command -v mysqld_safe)" ]; then
    echo "Starting MariaDB/MySQL..."
    mysqld_safe --datadir=/var/lib/mysql &
    sleep 5
fi

# Start PostgreSQL (if required)
if [ -x "$(command -v postgres)" ]; then
    echo "Starting PostgreSQL..."
    su - postgres -c "/usr/bin/postgres -D /var/lib/pgsql/data" &
    sleep 5
fi

# Start Nagios Core
echo "Starting Nagios..."
/usr/local/nagios/bin/nagios -d /usr/local/nagios/etc/nagios.cfg

# Start Performance Data Processor (PNP4Nagios)
echo "Starting NPCD..."
/usr/local/nagios/bin/npcd -f /usr/local/nagios/etc/pnp/npcd.cfg -d

# Start NSCA (for passive checks)
echo "Starting NSCA..."
/usr/local/nagios/bin/nsca -c /usr/local/nagios/etc/nsca.cfg --daemon

# Start SNMP Trap Daemon (if needed)
if [ -x "$(command -v snmptrapd)" ]; then
    echo "Starting SNMP Trap Daemon..."
    snmptrapd -Lsd &
fi

# Start rrdcached (if required)
if [ -x "$(command -v rrdcached)" ]; then
    echo "Starting rrdcached..."
    rrdcached -s nagios -m 0660 -l unix:/var/run/rrdcached.sock -w 1800 -z 1800 -f 3600 &
fi

# Start cron (Nagios XI relies on scheduled jobs)
echo "Starting Cron daemon..."
cron &

# Start Apache (Web UI)
echo "Starting Apache..."
apachectl -D FOREGROUND
