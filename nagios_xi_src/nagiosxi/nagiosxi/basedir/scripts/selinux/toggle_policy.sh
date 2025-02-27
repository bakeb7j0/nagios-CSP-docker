#!/bin/bash -e
###########################################################################################
# If you encounter problems after enabling selinux, you can test if selinux is the problem
# by using the command "setenforce 0" to temporarily disable selinux
# If the issue resolves when selinux is disabled, you can see the issue by using the
# command "ausearch -m AVC -ts recent"
# In order to fix this issue, it requires some degree of knowledge on the part of the user
# Sometimes, a file context is the issue. Sometimes, a boolean, maybe a port. 
# To generate a new policy, if one is needed, based on the errors you may encounter, 
# use "ausearch -m AVC -ts recent | audit2allow -m example > example.te" and modify as necessary. 
# It is not recommended to give anything the dac_override capability. Change file permissions
# instead. You can then use the steps taken in the script below to compile the policy 
# and install it.
###########################################################################################
basedir=$(readlink -f $(dirname $0))

usage() {
    echo ""
    echo "This is a script to add the Nagios XI SELinux policy"
    echo ""
    echo "  -i | --install    Install Nagios XI SELinux policy"
    echo "  -r | --remove     Remove Nagios XI SELinux policy"
    echo "  -h | --help       Print this message"
    echo ""
}

remove_policy() {
    # Remove nagiosxi policy if installed
    if semodule -l | grep -q "nagiosxi"; then
        echo "Removing Nagios XI SELinux policy"
        semodule -X 500 -r nagiosxi
        echo "Removed Nagios XI SELinux policy"
    fi
}

add_policy() {
    # Remove any previously existing policies
    remove_policy

    echo "Adding Nagios XI SELinux policy"
    if selinuxenabled; then
        # Compiling Policy
        checkmodule -M -m -o "$basedir/nagiosxi.mod" "$basedir/nagiosxi.te"
        semodule_package -o "$basedir/nagiosxi.pp" -m "$basedir/nagiosxi.mod" -f "$basedir/nagiosxi.fc"
        semodule -X 500 -i "$basedir/nagiosxi.pp"
        # Booleans
        semanage boolean --modify --on nagios_run_sudo
        semanage boolean --modify --on httpd_can_sendmail
        semanage boolean --modify --on httpd_run_stickshift
        semanage boolean --modify --on httpd_mod_auth_pam
        semanage boolean --modify --on httpd_setrlimit
        semanage boolean --modify --on httpd_can_network_connect
        semanage boolean --modify --on httpd_execmem
        semanage boolean --modify --on domain_can_mmap_files
        semanage boolean --modify --on authlogin_nsswitch_use_ldap
        # Wrap up by restoring context for important files
        restorecon -R -v /usr
        restorecon -R -v /etc
        restorecon -R -v /var/lib/mrtg
        restorecon -R -v /run/nagios.lock
        restorecon -R -v /home/nagios
        if [ -f /run/lock/mrtg ]; then
            restorecon -R -v /run/lock/mrtg
        fi
        # When a process runs as root and tries to access a file it does not have user/group ownership of, it will generate a dac_override AVC error. Instead of giving dac_override capability, we do this.
        chown root /var/spool/snmptt
        chown root /var/lib/net-snmp
        chmod g+w /var/lib/net-snmp
        chmod g+w /usr/local/nagios/var/nagios.log

        echo "Added Nagios XI SELinux policy"
    else
        echo "SELinux is set to \"disabled\" in /etc/selinux/config, you will need to enable SELinux and reboot the machine to enable the Nagios XI policy."
    fi
}

# Was selinux disabled during install?
if [ `command -v selinuxenabled` ]; then
    case "$1" in
        -i | --install )
            add_policy
            ;;
        -r | --remove )
            remove_policy
            ;;
        -h | --help | * )
            usage
            ;;
    esac
else
    echo "No SELinux on this machine"
    exit 0
fi
