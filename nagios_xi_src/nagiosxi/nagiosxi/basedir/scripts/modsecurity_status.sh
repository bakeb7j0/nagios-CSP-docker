#!/bin/bash -e

BASEDIR=$(dirname $(readlink -f $0))

# Import Nagios XI and xi-sys.cfg config vars
. $BASEDIR/../etc/xi-sys.cfg

file='/etc/httpd/conf.d/mod_security.conf'
if [ "$distro" == "Debian" ] || [ "$distro" == "Ubuntu" ]; then
    file='/etc/modsecurity/modsecurity.conf'
fi

if [ ! -f $file ]; then
    echo "No ModSecurity Configuration file"
    exit 1
fi

current=$(grep '^\s*SecRuleEngine' $file | grep -c "On" || true)
if [ "$current" != "0" ]; then
    echo "ModSecurity Enabled"
    exit 0
else
    echo "ModSecurity Disabled"
    exit 1
fi