#!/bin/bash -e
#
# Sets up the xi-sys.cfg file on full install
#

xivar() {
    ./xivar "$1" "$2"
    eval "$1"=\"\$2\"
}

# Add a newline at end of file just in case there isn't one (thanks Git!)
printf "\n" >> xi-sys.cfg

# XI version
xivar xiver $(sed -n '/full/ s/.*=\(.*\)/\L\1/p' ./nagiosxi/basedir/var/xiversion)

# OS-related variables have a detailed long variable, and a more useful short
# one: distro/dist, version/ver, architecture/arch. If in doubt, use the short
. ./get-os-info
xivar distro  "$distro"
xivar version "$version"
xivar ver     "${version%%.*}" # short major version, e.g. "6" instead of "6.2"
xivar architecture "$architecture"

# Set dist variable like before (el5/el6 on both CentOS & Red Hat)
case "$distro" in
    CentOS | RedHatEnterpriseServer | OracleServer | CloudLinux )
        xivar dist "el$ver"
        ;;
    Fedora )
        xivar dist "fedora$ver"
        ;;
    Debian )
        xivar dist "debian$ver"
        ;;
    "SUSE LINUX" )
        xivar dist "suse$ver"
        ;;
    *)
        xivar dist $(echo "$distro$ver" | tr A-Z a-z)
esac

xivar arch "$architecture"

case "$dist" in
    el8 | el9 )
        xivar ntpd chronyd
        if [ "$arch" = "x86_64" ]; then
            xivar php_extension_dir /usr/lib64/php/modules
        else
            xivar php_extension_dir /usr/lib/php/modules
        fi
        ;;
    ubuntu20 | ubuntu22 | ubuntu24 | debian8 | debian9 | debian10 | debian11 | debian12 )
            xivar apacheuser www-data
            xivar apachegroup www-data
            xivar httpdconf /etc/apache2/apache2.conf
            xivar httpdconfdir /etc/apache2/conf-enabled
            xivar httpdroot /var/www/html
            xivar phpini /etc/php5/apache2/php.ini
            xivar phpconfd /etc/php5/apache2/conf.d
            xivar phpconfdcli /etc/php5/cli/conf.d
            xivar mibsdir /usr/share/mibs
            xivar httpd apache2
            xivar ntpd ntp
            xivar crond cron
            xivar mysqld mysql
        if [ "$dist" == "ubuntu20" ]; then
            xivar mibsdir /usr/share/snmp/mibs
            xivar phpini /etc/php/7.4/apache2/php.ini
            xivar phpconfd /etc/php/7.4/apache2/conf.d
            xivar phpconfdcli /etc/php/7.4/cli/conf.d
        elif [ "$dist" == "ubuntu22" ]; then
            xivar mibsdir /usr/share/snmp/mibs
            xivar phpini /etc/php/8.1/apache2/php.ini
            xivar phpconfd /etc/php/8.1/apache2/conf.d
            xivar phpconfdcli /etc/php/8.1/cli/conf.d
        elif [ "$dist" == "ubuntu24" ]; then
            xivar ntpd ntpsec
            xivar mibsdir /usr/share/snmp/mibs
            xivar phpini /etc/php/8.3/apache2/php.ini
            xivar phpconfd /etc/php/8.3/apache2/conf.d
            xivar phpconfdcli /etc/php/8.3/cli/conf.d
        elif [ "$dist" = "debian11" ]; then
            xivar mibsdir /usr/share/snmp/mibs
            xivar phpini /etc/php/7.4/apache2/php.ini
            xivar phpconfd /etc/php/7.4/apache2/conf.d
            xivar phpconfdcli /etc/php/7.4/cli/conf.d
            xivar mysqld mariadb
        elif [ "$dist" == "debian12" ]; then
            xivar ntpd ntpsec
            xivar mibsdir /usr/share/snmp/mibs
            xivar phpini /etc/php/8.2/apache2/php.ini
            xivar phpconfd /etc/php/8.2/apache2/conf.d
            xivar phpconfdcli /etc/php/8.2/cli/conf.d
            xivar mysqld mariadb
        fi
        ;;
    *)
        :
esac

# load xi config if present
if [ -f /usr/local/nagiosxi/html/config.inc.php ]; then
    /usr/bin/php nagiosxi/basedir/scripts/import_xiconfig.php | sed -e 's/=/ /;s/'\''/\\'\''/g' | xargs -I % sh -c './xivar %' 
fi

# try and detect an appropriate amount of cores for make -j
procs=2

# most linux and osx
if which getconf &>/dev/null && getconf _NPROCESSORS_ONLN &>/dev/null; then
    procs=$(getconf _NPROCESSORS_ONLN)
else
    # anything with a procfs
    if [ -f /proc/cpuinfo ]; then
        procs=$(cat /proc/cpuinfo | grep processor | wc -l)
        if [ "$procs" == "0" ]; then
            procs=2
        fi
    fi
fi

xivar make_j_flag $procs