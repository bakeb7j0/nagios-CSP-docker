#!/bin/bash -e

BASEDIR=$(dirname $(readlink -f $0))

# Import Nagios XI and xi-sys.cfg config vars
. $BASEDIR/../etc/xi-sys.cfg

usage () {
    echo ""
    echo "Use this scrip to toggle mod_security."
    echo ""
        echo " -d | --disable         Disable mod security"
        echo " -e | --enable          Enable mod security"
    echo ""
}

toggle=enable
while [ -n "$1" ]; do
    case "$1" in
        -h | --help)
            usage
            exit 0
            ;;
        -d | --disable)
            toggle=disable
            ;;
        -e | --enable)
            toggle=enable
            ;;
    esac
    shift
done

file='/etc/httpd/conf.d/mod_security.conf'
if [ "$distro" == "Debian" ] || [ "$distro" == "Ubuntu" ]; then
    file='/etc/modsecurity/modsecurity.conf'
fi

current=$(grep '^\s*SecRuleEngine' $file | grep -c "Off" || true)

if [ "$toggle" == "enable" ]; then
    sed -i "s/$(grep '^\s*SecRuleEngine' $file)/SecRuleEngine On/" $file
elif [ "$toggle" == "disable" ]; then
    sed -i "s/$(grep '^\s*SecRuleEngine' $file)/SecRuleEngine Off/" $file
fi

if [ "$current" != "$( [ "$toggle" == "enable" ]; echo $? )" ]; then
    systemctl restart $httpd
fi
