#!/bin/bash

BASEDIR=$(dirname $(readlink -f $0))

. $BASEDIR/../../../../var/xi-sys.cfg

if [ "$distro" == "Ubuntu" ] || [ "$distro" == "Debian" ]; then
	pecl install ssh2
else
	yum install php-pecl-ssh2 -y
fi

$BASEDIR/../../../../scripts/manage_services.sh restart httpd
chown $nagiosuser.$nagiosgroup /store/backups/nagiosxi
exit 0
