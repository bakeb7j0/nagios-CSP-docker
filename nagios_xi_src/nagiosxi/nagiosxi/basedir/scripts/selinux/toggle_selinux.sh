#!/bin/bash -e

BASEDIR=$(dirname $(readlink -f $0))

usage () {
    echo ""
    echo "Use this script to toggle SELinux settings. This does not add/remove the Nagios XI policy"
    echo ""
        echo " -e | --enforcing             Change SELinux config to enforcing"
        echo "                              Enforce SELinux policy on reboot"
        echo " -p | --permissive            Change SELinux config to permissive"
        echo "                              Allow but log SELinux violations on reboot"
        echo " -d | --disabled              Change SELinux config to disabled."
        echo "                              SELinux will not load any policies on reboot"
        echo " -s | --set <0|1>             Temporarily switch enforcement."
        echo "                              Equivalent to setenforce 0 or setenforce 1."
        echo "                              0 meaning permissive. 1 meaning enforcing"
    echo ""
}

if ! [ `command -v selinuxenabled` ]; then
    echo "No SELinux on this machine"
    exit 0
fi

toggle=""
while [ -n "$1" ]; do
    case "$1" in
        -h | --help)
            usage
            exit 0
            ;;
        -e | --enforcing)
            toggle=enforcing
            ;;
        -p | --permissive)
            toggle=permissive
            ;;
        -d | --disabled)
            toggle=disabled
            ;;
        -s | --set)
            toggle=set
            enforce=$2
            ;;
    esac
    shift
done

if [ -z $toggle ]; then
    usage
    exit 1
fi

if [ "$toggle" == "set" ]; then
    if [ "$enforce" != "0" ] && [ "$enforce" != "1" ]; then
        echo ""
        echo "Invalid value passed into setenforce"
        echo ""
        usage
        exit 1
    fi
    if selinuxenabled; then
        setenforce $enforce
        exit 0
    else
        echo "SELinux is disabled, you will have to use $0 --enforcing and reboot to use the set parameter"
        exit 1
    fi
fi

if ! selinuxenabled && [ "$toggle" = "enforcing" ]; then
    echo "You cannot switch directly from disabled to enforcing in order to prevent errors with relabeling files!"
    echo "You will have to first set to permissive with $0 --permissive, then reboot, then set to Enforcing with $0 --enforcing"
    exit 1
fi

if ! selinuxenabled && [ "$toggle" = "permissive" ]; then
    echo "You will have to reboot to set SELinux to $toggle"
fi

file="/etc/selinux/config"
sed -i "s/$(grep '^SELINUX=' $file)/SELINUX=$toggle/" $file
