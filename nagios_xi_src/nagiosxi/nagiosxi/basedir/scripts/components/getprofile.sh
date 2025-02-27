#!/bin/bash 
#
# Main function processing down at the bottom of the script 
# Search for int main 

if [ $UID != 0 ] ; then
        printf "\n\n\n You must run the script as root\n\n\n\a"
        exit 1
fi

SCRVER="1701119802" 
DEBUG="nottrue"
PROFBASE=""
BASEDIR="/tmp"
NAGPROD="0"

if  [ `echo $0 | grep "getprofile.sh\|XI_DiagnosticProfile.sh" ` ] ; then 
	NAGPROD="XI" 
	BASEDIR="/usr/local/nagiosxi/var/components/profile"
	folder=$1
	funcstuff=$2 

elif  [ `echo $0 | grep "nnaprofile.sh\|NNA_DiagnosticProfile.sh"  ` ]  ; then 
	NAGPROD="NNA" 
	BASEDIR="/usr/local/nagiosna"
	funcstuff=$1 

elif  [ `echo $0 | grep "fuprofile.sh\|NFU_DiagnosticProfile.sh"  ` ]  ; then 
	NAGPROD="NFU" 
	BASEDIR="/usr/local/nagiosfusion"
	funcstuff=$1 

elif  [ `echo $0 | grep "brokenprofile.sh\|BRK_DiagnosticProfile.sh"  ` ]  ; then 
	NAGPROD="BRK" 
	BASEDIR="/tmp"
	funcstuff=$1 

elif  [ `echo $0 | grep "profile.sh\|LS_DiagnosticProfile.sh"  ` ]  ; then 
	NAGPROD="LS" 
	BASEDIR="/usr/local/nagioslogserver/tmp"
	funcstuff=$1 
	if [ "$DEBUG" = "true" ]; then 
	echo "$0 - $NAGPROD - $BASEDIR - $funcstuff"  
	fi 
else
	printf "\n\a\n=============\nFilename is incorrect - Make sure this script is named correctly for the product.\nPlease contact support if you don't know what this means\n =============\n\n"  
	exit 1  
fi  

##############################
process_args () {
# Process the arugments passed on the command line  
##############################

funcstuff="`echo $funcstuff |  sed -e 's/[^[:alnum:]|-]//g'  `"

if [ -z $funcstuff ]; then
	funcstuff="ALL"         
fi

if  [  $NAGPROD = "XI" ] ; then

	if [ "$DEBUG" = "true" ]; then 
		echo Process Args - $NAGPROD == XI
	fi	

	# Clean the folder name
#	folder=$(echo "$folder" | sed -e 's/[^[:alnum:]|-]//g')
	folder=`echo "$folder" | sed -e 's/[^[:alnum:]|-]//g'` 

	if [ "$folder" == "" ]; then
    		echo "You must enter a folder name/id to generate a profile."
		echo "Example: ./getprofile.sh <id> [function]"
		exit 1
	fi

	if [ "$DEBUG" = "true" ]; then 
		printf "\nfolder == $folder \n"
	fi 

elif [ $NAGPROD = "LS" ] ; then
	if [ "$DEBUG" = "true" ]; then 
		echo LS - $NAGPROD == LS
	fi
	echo 
elif [ $NAGPROD = "NNA" ] ; then
	if [ "$DEBUG" = "true" ]; then 
	echo - NNA - $NAGPROD == NNA
fi 
	echo 

elif [ $NAGPROD = "NFU" ] ; then
	echo  
elif [ $NAGPROD = "BRK" ] ; then
	echo 	
fi
 
} # End of process_args

 
##############################
get_os_and_version () {
# Get OS & version
##############################

if [ "$DEBUG" = "true" ]; then 
	echo DEBUGGGGING !!!  
printf "\nGathering OS and Version information @ `/bin/date` \n\n"   
fi

if which lsb_release &>/dev/null; then
    distro=`lsb_release -si`
    version=`lsb_release -sr`
elif [ -r /etc/redhat-release ]; then

    if rpm -q centos-release; then
        distro=CentOS
    elif rpm -q centos-stream-release; then
        distro=CentOS
    elif rpm -q sl-release; then
        distro=Scientific
    elif [ -r /etc/oracle-release ]; then
        distro=OracleServer
    elif rpm -q cloudlinux-release; then
        distro=CloudLinux
    elif rpm -q fedora-release; then
        distro=Fedora
    elif rpm -q redhat-release || rpm -q redhat-release-server; then
        distro=RedHatEnterpriseServer
    fi >/dev/null

    version=`sed 's/.*release \([0-9.]\+\).*/\1/' /etc/redhat-release`
else
    # Release is not RedHat or CentOS, let's start by checking for SuSE
    # or we can just make the last-ditch effort to find out the OS by sourcing os-release if it exists
    if [ -r /etc/os-release ]; then
        source /etc/os-release
        if [ -n "$NAME" ]; then
            distro=$NAME
            version=$VERSION_ID
        fi
    fi
fi

ver="${version%%.*}"



} ;  ### End of get_os_and_version


##############################
setup_dir_structure () {
##############################

if [ "$DEBUG" = "true" ]; then 
	printf "\n Directory Setup  $NAGPROD   \n" 
fi
if [ "$NAGPROD" = "XI" ] ; then
	if [ -d "$BASEDIR" ] ; then 
		PROFBASE="$BASEDIR/$folder/"
	elif  [ -d `echo "$BASEDIR" | rev | cut -f2- -d"/" | rev` ] ; then
        	mkdir "$BASEDIR"
		PROFBASE="$BASEDIR/$folder/"
	else
		PROFBASE="/tmp/$folder/"
		chown "$NAGUSR":"$NAGGRP" "$BASEDIR"
		umask 007  
	fi 
#		chown "$NAGUSR":$NAGGRP "$BASEDIR"
#		chmod -R 700 "$BASEDIR"
	if [ -d $PROFBASE ] ; then	
		rm -rf "$PROFBASE"
	fi
	mkdir "$PROFBASE"
	chown -R "$NAGUSR" "$BASEDIR"
	chmod 770 "$BASEDIR"

elif [ $NAGPROD = "LS" ] ; then
	PROFDIR="system-profile"
	BASEDIR="/usr/local/nagioslogserver/tmp/"
	if [ -d "$BASEDIR" ];  then
		PROFBASE="$BASEDIR/$PROFDIR"
		umask 007  
	else
		PROFBASE="/tmp/$PROFDIR"
		umask 007  
	fi
#	echo dirstr - "$NAGPROD" - "$PROFBASE" - "$BASEDIR"  

elif [ "$NAGPROD" = "NNA" ] ; then
	TMSTMP="`date +%s`"
	if [ -d $BASEDIR ] ; then
                PROFBASE="$BASEDIR/tmp/$TMSTMP"
        else
                PROFBASE="/tmp/$TMSTMP/"
                umask 077
        fi
	if [ -d "$PROFBASE" ] ; then 
	        rm -rf "$PROFBASE"
	fi 
        mkdir -p "$PROFBASE"
	chown "$NAGUSR":"$NAGGRP" "$BASEDIR"
        chmod 700 "$BASEDIR"
	
elif [ $NAGPROD = "BRK" ] ; then
	TMSTMP="`date +%s`"
	if [ -d "$BASEDIR" ] ; then
                PROFBASE="$BASEDIR/$TMSTMP"
                umask 077
        fi
	if [ -d "$PROFBASE" ] ; then 
	        rm -rf "$PROFBASE"
	fi 
        mkdir -p "$PROFBASE"
	chown "$NAGUSR":"$NAGGRP" "$PROFBASE"
elif [ $NAGPROD = "NFU" ] ; then
	PROFDIR="tmp/support_profile"		
	PROFBASE="$BASEDIR/$PROFDIR"

#	echo dirstr - $NAGPROD == NFU  $PROFDIR $PROFBASE 
	if [ -d "$PROFBASE" ] ; then
		rm -fr "$PROFBASE" || exit 2  
       		mkdir -p "$PROFBASE" || exit 1  
		chown "$NAGUSR":"$NAGGRP" "$PROFBASE" 
	elif [ -d "$BASEDIR" ] ; then
       		mkdir -p "$PROFBASE" || exit 2  
		chown "$NAGUSR":"$NAGGRP" "$PROFBASE"	
else
        print "\n Problem creating the support profile directory \n"
        exit 2
fi

fi

if [ "$DEBUG" = "true" ]; then 
	printf "\nSetting up directory structure @ `/bin/date` \n"   
fi 

# Create the folder setup
NAGLOGBASE="$PROFBASE/nagios-logs"
mkdir -p "$NAGLOGBASE"
chown "$NAGUSR":"$NAGGRP" "$NAGLOGBASE"
LOGBASE="$PROFBASE/logs"
mkdir -p "$LOGBASE"
chown "$NAGUSR":"$NAGGRP" "$LOGBASE"
VERBASE="$PROFBASE/versions"
mkdir -p "$VERBASE"
chown "$NAGUSR":"$NAGGRP" "$VERBASE"
DBBASE="$PROFBASE/DB"
mkdir -p "$DBBASE"
chown "$NAGUSR":"$NAGGRP" "$DBBASE"
APACHEPHPBASE="$PROFBASE/Apache-PHP"
mkdir -p "$APACHEPHPBASE"
chown "$HTTPUSER":"$NAGGRP" "$APACHEPHPBASE"

FSBASE="$PROFBASE/Filesystem"
mkdir -p "$FSBASE"
chown "$NAGUSR":"$NAGGRP" "$DBBASE"

} ### End of setup_dir_structure 


##############################
gather_system_info () {
##############################

if [ "$DEBUG" = "true" ]; then 
	printf "\nGathering System information @ `/bin/date` \n\n"   | tee -a "$PROFBASE/getprofile.out"  
fi 

printf "\n Script Version $SCRVER \n" >> "$PROFBASE/script_version.txt"  

if [ "$DEBUG" = "true" ]; then 
	echo "Cron log..."
fi 
if [ -f /var/log/cron ]; then
    tail -n 5000 /var/log/cron >  "$LOGBASE/cron.log"
fi

if [ -f /usr/bin/sar ]; then

	if [ "$DEBUG" = "true" ]; then 
	echo "Creating sar log..."
	fi 
	sar 1 5 > "$PROFBASE/sar.txt"
fi

if [ -f /usr/sbin/sestatus ]; then
	if [ "$DEBUG" = "true" ]; then 
	echo "Checking SEstatus."
	fi
	/usr/sbin/sestatus >> "$PROFBASE/SE-FIPS.txt"
fi

if [ -f /usr/bin/fips-mode-setup ]; then

	if [ "$DEBUG" = "true" ]; then 
	echo "Checking fips-info"
	fi
	/usr/bin/fips-mode-setup --check >>  "$PROFBASE/SE-FIPS.txt"
fi

if [ -f /usr/bin/openssl ]; then
	printf "\n ======== Version of openssl installed ======== \n" > "$PROFBASE/openssl-info.txt"  
	openssl version -a >> "$PROFBASE/openssl-info.txt"  
	printf "\n ======== openssl ciphers ======== \n" >> "$PROFBASE/openssl-info.txt"  
	openssl ciphers -v  >> "$PROFBASE/openssl-info.txt"  
	printf "\n ======== ssl.h ======== \n" >> "$PROFBASE/openssl-info.txt"  
	find /usr/include -name ssl.h -ls >> "$PROFBASE/openssl-info.txt"  
	find /usr/local -name ssl.h -ls >> "$PROFBASE/openssl-info.txt"  
fi
	if [ "$DEBUG" = "true" ]; then 
	echo "Creating socket.txt..."
	fi 
if [ -f /usr/bin/netstat ] ; then
        /usr/bin/netstat -an >> $PROFBASE/socket.txt
elif [ -f /bin/netstat ] ; then
        /bin/netstat >> $PROFBASE/socket.txt
elif [ -f /usr/bin/ss ] ; then
        /usr/bin/ss -sn > $PROFBASE/socket.txt
elif [ -f /usr/sbin/ss ] ; then
        /usr/sbin/ss -sn > $PROFBASE/socket.txt
elif [ -f /bin/ss ] ; then
        /bin/ss -sn >> $PROFBASE/socket.txt
fi

if [ "$DEBUG" = "true" ]; then 
	echo "Creating systemlog.txt..."
fi 

if [ -f /var/log/messages ]; then
 tail -n 500000 /var/log/messages >  "$LOGBASE/messages.txt"
elif [ -f /var/log/syslog ]; then
 tail -n 500000 /var/log/syslog >> "$LOGBASE/messages.txt"
fi

if [ "$DEBUG" = "true" ]; then 
	echo "Creating apacheerrors.txt..."
fi 

if [ -d /var/log/httpd ]; then
    for a in ` ls /var/log/httpd | grep -v gz`  
        do
            /usr/bin/tail -n10000 /var/log/httpd/$a > "$APACHEPHPBASE/$a.txt"
        done

elif [ -d /var/log/apache2 ]; then
    for a in ` ls /var/log/apache2 | grep -v gz `
        do
            /usr/bin/tail -n10000 /var/log/apache2/$a > "$APACHEPHPBASE/$a.txt"
        done
fi

if [ -d /var/log/php-fpm ]; then
    for a in ` ls /var/log/php-fpm | grep -v gz`  
        do
            /usr/bin/tail -n10000 /var/log/php-fpm/$a > "$APACHEPHPBASE/$a.php-fpm.txt"
        done
fi 

 
if [ "$DEBUG" = "true" ]; then 
	echo "Getting Release Information..."
fi 
/bin/cat /etc/*release* > "$VERBASE/OS-Release.txt"



if [ "$DEBUG" = "true" ]; then 
	echo "Getting Network Information..."
fi
ip addr > "$PROFBASE/ip_addr.txt"

if [ "$DEBUG" = "true" ]; then 
	echo "Getting CPU info..."
fi 
/bin/cat /proc/cpuinfo > "$PROFBASE/cpuinfo.txt"

if [ "$DEBUG" = "true" ]; then 
	echo "Getting memory info..."
fi
free -m > "$PROFBASE/meminfo.txt"

if [ "$DEBUG" = "true" ]; then 
	echo "IPCS data..." > "$PROFBASE/ipcs.txt"
fi
ipcs >> "$PROFBASE/ipcs.txt"

if [ "$DEBUG" = "true" ]; then 
	echo "Creating memorybyprocess.txt..."
fi 
ps aux --sort -rss > "$PROFBASE/memorybyprocess.txt"

if [ "$DEBUG" = "true" ]; then 
	echo "Creating filesystem.txt..."
fi 

df -h > "$FSBASE/filesystem.txt"
echo "" >> "$FSBASE/filesystem.txt"
df -i >> "$FSBASE/filesystem.txt"

if [ "$DEBUG" = "true" ]; then 
	echo "Gathering PS information - psaef.txt and ps-aexl.txt."
fi 
ps -aef > "$PROFBASE/psaef.txt"
ps -aexl > "$PROFBASE/ps-aexl.txt"

if [ "$DEBUG" = "true" ]; then 
	echo "Creating top log..."
fi 
top -b -n 1 > "$PROFBASE/top.txt"

if [ "$DEBUG" = "true" ]; then 
	echo "ulimit.."
fi 
ulimit -a  > "$PROFBASE/ulimit.txt"

if [ "$DEBUG" = "true" ]; then 
	echo "Getting Firewall information..."
fi 

if which iptables >/dev/null 2>&1; then
    echo "iptables -S" > "$PROFBASE/iptables.txt"
    echo "-----------" >> "$PROFBASE/iptables.txt"
    iptables -S >> "$PROFBASE/iptables.txt" 2>&1
fi

if which firewall-cmd >/dev/null 2>&1; then
    echo "firewall-cmd --list-all-zones" > "$PROFBASE/firewalld.txt"
    echo "-----------" >> "$PROFBASE/firewalld.txt"
    firewall-cmd --list-all-zones >> "$PROFBASE/firewalld.txt" 2>&1
fi

if which ufw >/dev/null 2>&1; then
    echo "ufw status" > "$PROFBASE/ufw.txt"
    echo "-----------" >> "$PROFBASE/ufw.txt"
    ufw status >> "$PROFBASE/ufw.txt" 2>&1
fi

if [ "$DEBUG" = "true" ]; then 
echo "Getting maillog..."
fi 

if [ -f /var/log/maillog ]; then
    tail -n 1000 /var/log/maillog > "$LOGBASE/maillog"
elif [ -f /var/log/mail.log ]; then
    tail -n 1000 /var/log/mail.log > "$LOGBASE/maillog"
fi

if [ "$DEBUG" = "true" ]; then 
echo "Gathering backup perms..."
fi 
 
find -L /store/ -ls > "$FSBASE/store_file_perms.txt"
find -L /etc/ -ls > "$FSBASE/etc_file_perms.txt"

ps -axef | grep "boks\|falcon-sensor\|CrowdStrike\|mdatp\|McAfee\|sophos-spl\|Symantec\|rapid7\|BESClient\|taegis-agent\|bitdefender-security-tools\|cybereason-sensor\|fireeye\|ds_client\|Tanium\|redcloak\|nessus" | grep -v grep > $PROFBASE/security_agents_installed.txt

if [ "$DEBUG" = "true" ]; then 
	echo "Testing for connectivity to api.nagios.com"
fi 

printf "\n======================= default `date` =======================\n" > "$PROFBASE/api-connectivity.txt"  2>&1 
curl -s  -m 10 -i https://www.nagios.com/checkforupdates/'?product=nagiosxi&version=5.11.3&build=1675277543&profcheck='`date +%s` | head -n 8 | head -n 10  >>  "$PROFBASE/api-connectivity.txt" 2>&1 

printf "\n======================= --noproxy flag `date` =======================\n" >>  "$PROFBASE/api-connectivity.txt" 2>&1 
curl -s -m 10 -i https://www.nagios.com/checkforupdates/'?product=nagiosxi&version=5.11.3&build=1675277543&profcheck='`date +%s` --noproxy api.nagios.com   | head -n 8 >> "$PROFBASE/api-connectivity.txt" 2>&1 
printf "\n======================= `date` =======================\n" >>  $PROFBASE/api-connectivity.txt 2>&1  

printf "\n======================= systemctl -a =======================\n" > -a $PROFBASE/systemctl.txt 2>&1 
systemctl -a >>  $PROFBASE/systemctl.txt 2>&1 


} #### End gather_system_info  


##############################
gather_NCPA_info () {
##############################

if [ "$DEBUG" = "true" ]; then 
	printf "\n Gather NCPA info \n" 
fi 

if [ -d /usr/local/ncpa ]; then
	mkdir $PROFBASE/NCPA 
if [ "$DEBUG" = "true" ]; then 
	printf "\n Gathering NCPA info \n" 
fi 
	find /usr/local/ncpa -ls > $PROFBASE/NCPA/NCPA_file_perms.txt  
        if [ -f /usr/sbin/ss ] ; then
               /usr/sbin/ss -ln  |grep 5693 >  $PROFBASE/NCPA/Port5693_LISTEN.txt 
        elif [ -f /bin/ss ] ; then
               /bin/ss -ln  |grep 5693 >  $PROFBASE/NCPA/Port5693_LISTEN.txt 
        fi

	cp /usr/local/ncpa/etc/ncpa.cfg $PROFBASE/NCPA/ 

	for LF in `find /usr/local/ncpa/var/log -type f ` 	
	do 
		cp $LF $PROFBASE/NCPA/
	done 
fi
 
} #### End gather_NCPA_info  

##############################
gather_package_info () {
##############################

if [ "$DEBUG" = "true" ]; then 
	printf "\nGathering package information @ `/bin/date` \n\n"   | tee -a $PROFBASE/getprofile.out
fi 
if [ `command -v yum` ] && [ ! -f /var/run/yum.pid ]; then
    yum -C list installed >  "$VERBASE/yum_installed.txt" & 
    sleep 5  
# Don't fear the repos ( you're welcome Craig ) 
    yum -C history >  "$PROFBASE/yum_history.txt" & 
    yum repolist > "$PROFBASE/yum_repolist.txt" & 
    yum repoinfo > "$PROFBASE/yum_repoinfo.txt" & 

elif [ `command -v apt` ]; then
   apt list --installed >  "$VERBASE/apt_installed.txt"
   if [ -f /etc/apt/sources.list ]; then 
   	cp /etc/apt/sources.list $PROFBASE/ 
   fi

elif [ `command -v rpm` ]; then
   rpm -qa >  "$VERBASE/rpm_installed.txt"

fi

}  ### End of gather_diskuse_info 

##############################
gather_diskuse_info () {
##############################


if [ "$DEBUG" = "true" ]; then 
	printf "\nGathering disk usage information @ `/bin/date` \n\n"   | tee -a $PROFBASE/getprofile.out
fi 
/bin/mount -l  > $FSBASE/mountpoints.txt 

printf "============ du -sh /var/*  ============\n" >>   $FSBASE/du.txt
du -sh /var/* >>   $FSBASE/du.txt

printf "============ du -sh /tmp ============\n" >>   $FSBASE/du.txt
du -sh /tmp >>   $FSBASE/du.txt

printf "============ df -h ============\n" >>   $FSBASE/du.txt
df -h  >>   $FSBASE/du.txt
printf "============ df -i ============\n" >>   $FSBASE/du.txt
df -i  >>   $FSBASE/du.txt

if  [  $NAGPROD = "XI" ] ; then

#echo gather_diskuse_info - $NAGPROD == XI

	printf "============ du -sh /usr/local/nagios ============\n" >>   $FSBASE/du.txt  
	du -sh /usr/local/nagios  >>   $FSBASE/du.txt  

	printf "============ du -sh /usr/local/nagiosxi ============\n" >>   $FSBASE/du.txt  
	du -sh /usr/local/nagiosxi >>   $FSBASE/du.txt  

elif [ $NAGPROD = "LS" ] ; then

	printf "============ du -sh /usr/local/nagioslogserver ============\n" >>   $FSBASE/du.txt
	du -sh /usr/local/nagioslogserver  >>   $FSBASE/du.txt

	printf "============ du -sh /var/log/elasticsearch ============\n" >>   $FSBASE/du.txt
	du -sh /var/log >>   $FSBASE/du.txt

	printf "============ du -sh /var/log/logstash ============\n" >>   $FSBASE/du.txt
	du -sh /var/log/logstash  >>   $FSBASE/du.txt

elif [ $NAGPROD = "NNA" ] ; then
#	echo NNA - $NAGPROD == NNA
	printf "============ du -sh /usr/local/nagios ============\n" >>   $FSBASE/du.txt  
	du -sh /usr/local/nagiosna >> $FSBASE/du.txt  

elif [ $NAGPROD = "NFU" ] ; then
	echo NFU 
	printf "============ du -sh /usr/local/nagiosfusion ============\n" >>   $FSBASE/du.txt
	du -sh /usr/local/nagiosfusion >>   $FSBASE/du.txt
fi


}  ### End of gather_diskuse_info 

##############################
gather_third_party ()  {
##############################

if [ "$DEBUG" = "true" ]; then 
	printf "\nGathering 3rd party information @ `/bin/date` \n\n"   | tee -a $PROFBASE/getprofile.out 
fi 

PHPVERSION=`php -r "print_r(phpversion());"`
if [ "$DEBUG" = "true" ]; then 
	echo "Gathering PHP info "
fi 
#echo "Fetching PHP info ..."

if [ "$PHPVERSION" != "5.4.16" ]; then
    php -r "print_r(openssl_get_cert_locations());" > "$APACHEPHPBASE/php-cert-locations.txt"
fi

php -r 'phpinfo();' > "$APACHEPHPBASE/php-info.txt" 

if [ "$DEBUG" = "true" ]; then 
	echo "Gathering Cert info .. "
fi 

if [ -d /etc/openldap ]; then
    find  -L /etc/openldap -ls > "$PROFBAES/openldapcerts.txt"
elif [ -d /etc/ldap/ ]; then
    find -L /etc/ldap -ls  > "$PROFBAES/openldapcerts.txt"
fi

if [ -d /etc/pki ]; then
    find -L  /etc/pki -ls  > "$PROFBASE/etc-pki.txt"
fi

find -L /etc -name php.ini -exec cp {} "$APACHEPHPBASE/" \; 
find -L /usr/local/etc -name php.ini -exec cp {} "$APACHEPHPBASE/usr_local_etc_php.ini" \; 

if [ "$DEBUG" = "true" ]; then 
	printf "\nPython links information @ `/bin/date` \n\n"   | tee -a $PROFBASE/getprofile.out 
fi 
find -L /usr/bin/ -name "python*" -ls > "$PROFBASE/python_links.txt" 
printf "\n\n=============================\n\n"  >> "$PROFBASE/python_links.txt" 
ls -la /usr/bin/python* >> "$PROFBASE/python_links.txt"
 

if [ "$DEBUG" = "true" ]; then 
	printf "\nPerl module information @ `/bin/date` \n\n"   | tee -a $PROFBASE/getprofile.out 
fi 
#if [ -f /usr/bin/instmodsh ]; then
# printf "l\n q\n\n\q\n" | /usr/bin/instmodsh     > "$PROFBASE/perlmod.log"
#fi


 
if  [  $NAGPROD = "XI" ] ; then

	if [ "$DEBUG" = "true" ]; then 
	echo "Getting NagVis version..."
	fi 

	grep -i const_version /usr/local/nagvis/share/server/core/defines/global.php > "$VERBASE/nagvis.txt"
	find -L /usr/local/nagvis -ls >  "$FSBASE/nagvis_file_perms.txt"

	if [ "$DEBUG" = "true" ]; then 
	echo "Getting WKTMLTOPDF version..."
	fi 

	/bin/chromium-browser --version > "$VERBASE/chromium.txt"

	if [ -f /var/log/snmptt/snmptt.log ] && `grep -q snmptt /etc/passwd` ; then
		/usr/bin/su -s /bin/bash -g snmptt snmptt -c "/usr/bin/tail -n1000 /var/log/snmptt/snmptt.log" > "$LOGBASE/snmptt.txt"
	fi
	if [ -f /var/log/snmptt/snmpttsystem.log ] && `grep -q snmptt /etc/passwd` ; then
		/usr/bin/su -s /bin/bash -g snmptt snmptt -c "/usr/bin/tail -n1000 /var/log/snmptt/snmpttsystem.log" > "$LOGBASE/snmpttsystem.txt"
	fi
	if [ -f /var/log/snmpttunknown.log ] && `grep -q snmptt /etc/passwd` ; then
		/usr/bin/su -s /bin/bash -g snmptt snmptt -c  "/usr/bin/tail -n1000 /var/log/snmpttunknown.log" > "$LOGBASE/snmpttunknown.log.txt"
	fi

	find -L /var/spool/snmptt/ -ls  > "$LOGBASE/snmptt_var_spool.txt" 
fi 


if [ "$DEBUG" = "true" ]; then 
	echo "Gathering Apache info "
fi

for apconf in `find -L /etc -name "httpd.conf" ; find /usr/local -name "httpd.conf"` 
do 
	cp $apconf  $APACHEPHPBASE/`echo $apconf |  sed s/"\/"/"_"/g | cut -f2- -d"_"` 
done

if [ -d /etc/httpd/conf.d/ ]  ; then 
        /usr/bin/su -s /bin/bash -g "$NAGGRP" "$HTTPUSER" -c  tar -czf $APACHEPHPBASE/httpd_conf.d.tgz /etc/httpd/conf.d/ 2> /dev/null 
fi 

} ### End of gather_third_party   

####################
Take5 () {
####################

STARTTIME="`date +%s`" 
OUTPATH="/tmp"
I=0

echo > $OUTPATH/Systats-$STARTTIME 
printf "\n===========================\nGathering info @`date`\n===========================\n"  | tee -a $OUTPATH/Systats-$STARTTIME  
printf "\n-----------------\nInterface Information\n-----------------\n"  | tee -a $OUTPATH/Systats-$STARTTIME 
if [ -f /usr/sbin/ip ] ; then 
        /usr/sbin/ip address | tee -a $OUTPATH/Systats-$STARTTIME 
elif [ -f sbin/ip ] ; then 
        /sbin/ip address | tee -a $OUTPATH/Systats-$STARTTIME 
elif [ -f /sbin/ifconfig ] ; then  
        /sbin/ifconfig -a | tee -a $OUTPATH/Systats-$STARTTIME 
fi 

printf "\n-----------------\nRoute Information\n-----------------\n"  | tee -a $OUTPATH/Systats-$STARTTIME 
if [ -f /usr/sbin/ip ] ; then 
        /usr/sbin/ip route | tee -a $OUTPATH/Systats-$STARTTIME 
elif [ -f sbin/ip ] ; then 
        /sbin/ip route | tee -a $OUTPATH/Systats-$STARTTIME 
elif [ -f /usr/bin/netstat ] ; then  
        /usr/bin/netstat -nr | tee -a $OUTPATH/Systats-$STARTTIME 
fi 

while [ $I -le 60 ]  
do 
        ((I=I+1))
        echo $I 

        printf "\n----------------- Top Summary -----------------\n"  | tee -a $OUTPATH/Systats-$STARTTIME 
        top -b -n 1 | tee -a $OUTPATH/Systats-$STARTTIME


        if [ -f /usr/sbin/ss ] ; then 
                printf "\n----------------- Socket Summary -----------------\n"  | tee -a $OUTPATH/Systats-$STARTTIME 
                /usr/sbin/ss -sn  | tee -a $OUTPATH/Systats-$STARTTIME 
        elif [ -f /bin/ss ] ; then 
                printf "\n----------------- Socket Summary -----------------\n"  | tee -a $OUTPATH/Systats-$STARTTIME 
                /bin/ss -sn  | tee -a $OUTPATH/Systats-$STARTTIME 
        fi

        if [ -f /usr/bin/netstat ] ; then 
                printf "\n----------------- Socket Listing -----------------\n"  | tee -a $OUTPATH/Systats-$STARTTIME 
                /usr/bin/netstat -na |  tee -a $OUTPATH/Systats-$STARTTIME 

        elif [ -f /usr/sbin/ss ] ; then 
                printf "\n----------------- Socket Listing -----------------\n"  | tee -a $OUTPATH/Systats-$STARTTIME 
                /usr/bin/ss -an  | tee -a $OUTPATH/Systats-$STARTTIME 
        elif [ -f /bin/ss ] ; then 
                printf "\n----------------- Socket Listing -----------------\n"  | tee -a $OUTPATH/Systats-$STARTTIME 
                /bin/ss -an  | tee -a $OUTPATH/Systats-$STARTTIME 
        fi

        if [ -f /usr/bin/sar ] ; then 
                printf "\n----------------- sar output -----------------\n"  | tee -a $OUTPATH/Systats-$STARTTIME 
                sar 1 5 | tee -a  $OUTPATH/Systats-$STARTTIME
        elif [ -f /usr/bin/iostat  ] ; then 
                printf "\n----------------- iostat output -----------------\n"  | tee -a $OUTPATH/Systats-$STARTTIME 
                /usr/bin/iostat 5 
        else
                printf "\n----------------- sleeping 5 -----------------\n"  | tee -a $OUTPATH/Systats-$STARTTIME 
                sleep 5 
        fi
done 

printf "\n===========================\nInformtion collection complete @ `date`\n===========================\n\n"  | tee -a $OUTPATH/Systats-$STARTTIME  
#printf "\ngzipping $OUTPATH/Systats-$STARTTIME\n "  | tee -a $OUTPATH/Systats-$STARTTIME  

gzip $OUTPATH/Systats-$STARTTIME 

printf "===========================\nThank You for your patience!\n===========================\n\n\nPlease attach the following file to your Nagios support ticket: $OUTPATH/Systats-$STARTTIME.gz\n\n\n"  | tee -a $OUTPATH/Systats-$STARTTIME  
exit 0 

} ### End of Take5 

##############################
install_it ()  {
##############################
#### !!!!! XI and LS ONLY !!!!!! 
##############################
if  [  $NAGPROD = "XI" ] ; then


	PROF_FILE=`find -L /usr/local/nagiosxi -name getprofile.sh -print `

if [ "$DEBUG" = "true" ]; then 
	echo $PROF_FILE
fi 
	DATESTR=`date +%m-%d-%y`

	if [ -f "$PROF_FILE" -a "$0" ]; then
		cp "$PROF_FILE" "$PROF_FILE".bkp.$DATESTR
		echo "$PROF_FILE" "$PROF_FILE".bkp.$DATESTR
		cp $0 "$PROF_FILE"
		chown root:nagios "$PROF_FILE"
		chmod 750 "$PROF_FILE"
	fi

elif [ $NAGPROD = "LS" ] ; then
	PROF_FILE="/usr/local/nagioslogserver/scripts/profile.sh" 

	if [ -f "$PROF_FILE" -a "$0"  ]; then
		DATESTR=`date +%m-%d-%y`
		cp "$PROF_FILE" "$PROF_FILE".bkp.$DATESTR
	if [ "$DEBUG" = "true" ]; then 
		printf "\n $PROF_FILE backed up to $PROF_FILE.bkp.$DATESTR  \n" 
	fi 
		cp $0 "$PROF_FILE"
		chown root:nagios "$PROF_FILE"
		chmod 750 "$PROF_FILE"
	fi 

elif [ $NAGPROD = "NNA" ] ; then
	echo 
elif [ $NAGPROD = "NFU" ] ; then
	echo NFU 
fi

} ## End install_it


##############################
zip_it_up ()  {
##############################

if  [  $NAGPROD = "XI" ] ; then

if [ "$DEBUG" = "true" ]; then 
	echo "Zipping directory..."
	printf "\nZipping up profile.zip @ `/bin/date` \n\n"   | tee -a $PROFBASE/getprofile.out 
fi 
    ts=$(date +%s)
    if [ "$DEBUG" = "true" ]; then 
    echo "BASEDIR/tmp $BASEDIR/tmp - PROFBASE $PROFBASE"
    fi 
    cd $PROFBASE/..

    mv $PROFBASE "profile-$ts"

    if [ -f profile.zip ] ; then
	    rm profile.zip 
    fi
# Added for compatibility for the ln below - should be removed with the ln statement below  
    if [ -f ../profile.zip ] ; then
            rm ../profile.zip 
    fi

    if [ "$DEBUG" = "true" ]; then 
   	 zip -r profile.zip "profile-$ts" 
    else 
   	 zip -qr profile.zip "profile-$ts" 
    fi 

# Added the link for backwards compatibility - if the script works as expected this should be removed after 6 months and the cd statement above adjusted to "cd ../../" 
    ln profile.zip ../profile.zip
    rm -rf "profile-$ts"
    printf "\n============================================\nPlease attach the file `pwd`/profile.zip to your Nagios support ticket:\n============================================\n"  

echo "Backup and Zip complete!"

elif [ $NAGPROD = "LS" ] ; then

## temporarily change to that directory, zip, then leave
(
    ts=$(date +%s)
    OUTPUTDIR="/tmp"
#    OUTPUTFILE="system-profile-diag.${ts}"
    OUTPUTFILE="system-profile"
    cd $PROFBASE
    cd ..
    tar -czf $OUTPUTDIR/"${OUTPUTFILE}.tar.gz" $PROFDIR 2> /dev/null  
    rm -fr "$PROFBASE"
    printf "\n============================================\nPlease attach the file $OUTPUTDIR/${OUTPUTFILE}.tar.gz to your Nagios support ticket:\n============================================\n"
)

echo "Backup and Zip complete!"

elif [ $NAGPROD = "NNA" ] ; then
#	echo zip_it_up - $NAGPROD == NNA
	cd $BASEDIR/tmp 

    	if [ -f profile.zip ] ; then
	    rm profile.zip 
	fi

	mv $TMSTMP "profile-$TMSTMP"  
	if [ "$DEBUG" = "true" ]; then
		zip -r profile.zip "profile-$TMSTMP"
	else 
		zip -qq -r profile.zip "profile-$TMSTMP"
	fi
	if [ $? -eq 0 ] ; then 
		rm -fr "profile-$TMSTMP"
	fi
	printf "\n============================================\nPlease attach the file  `pwd`/profile.zip to your Nagios support ticket:\n============================================\n"  


elif [ $NAGPROD = "NFU" ] ; then
#	echo zip_it_up - $NAGPROD == NFU
	if [ "$DEBUG" = "true" ]; then 
	echo "Zipping directory..."
	printf "\nZipping up profile.zip @ `/bin/date` \n\n"   | tee -a $PROFBASE/getprofile.out
	fi
	ts=$(date +%s)
	echo "profbase $PROFBASE"
	cd $PROFBASE
	cd ..
	mv "$PROFBASE" "profile-$ts"
        if [ -f profile.zip ] ; then
            rm profile.zip
        fi
	if [ "$DEBUG" = "true" ]; then 
		zip -r profile.zip "profile-$ts"
	else
		zip -qq -r profile.zip "profile-$ts"
	fi
	# Added the link for backwards compatibility - if the script works as expected this should be removed after 6 months and the cd statement above adjusted to "cd ../../"
#        if [ -f ../profile.zip -o -L ../profile.zip ] ; then
#            rm profile.zip
#        fi
#	ln profile.zip ../profile.zip
	rm -rf "profile-$ts"
	printf "\n============================================\nPlease attach the file `pwd`/profile.zip to your Nagios support ticket:\n============================================\n"
echo "Backup and Zip complete!"

elif [ $NAGPROD = "BRK" ] ; then
	if [ "$DEBUG" = "true" ]; then 
		echo "taring up the directory..."
		printf "\ntaring up brkprofile.tgz @ `/bin/date` \n\n"   | tee -a $PROFBASE/getprofile.out
	fi
	ts=$(date +%s)
	echo "profbase $PROFBASE"
	cd $PROFBASE
	cd ..
        if [ -f brkprofile.tgz ] ; then
            rm brkprofile.tgz 
        fi

	mv "$PROFBASE" "profile-$ts"
	if [ "$DEBUG" = "true" ]; then 
		tar -czvf brkprofile.tgz "profile-$ts"
	else
		tar -czvf brkprofile.tgz "profile-$ts"
	fi
	rm -rf "profile-$ts"
	printf "\n============================================\nPlease attach the file `pwd`/brkprofile.tgz to your Nagios support ticket:\n============================================\n"
echo "Backup and Zip complete!"


fi 

} ; #### End zip_it_ip 


###############################
# Primarily XI specific stuff 
#
##############################
generate_profile_dot_html () {
# This function creates the a modified version 
# of the php script that produces the profile.html file 
##############################

printf "\nPD9waHAKLy8KLy8gQ29weXJpZ2h0IChjKSAyMDA4LTIwMjIgTmFnaW9zIEVudGVycHJpc2VzLCBM
TEMuIEFsbCByaWdodHMgcmVzZXJ2ZWQuCi8vCgpyZXF1aXJlX29uY2UoJy91c3IvbG9jYWwvbmFn
aW9zeGkvaHRtbC9pbmNsdWRlcy9jb21wb25lbnRzL2NvbXBvbmVudGhlbHBlci5pbmMucGhwJyk7
CnJlcXVpcmVfb25jZSgnL3Vzci9sb2NhbC9uYWdpb3N4aS9odG1sL2luY2x1ZGVzL2NvbmZpZ3dp
emFyZHMuaW5jLnBocCcpOwoKLy8gSW5pdGlhbGl6YXRpb24gc3R1ZmYKLy8gR3JhYiBHRVQgb3Ig
UE9TVCB2YXJpYWJsZXMgYW5kIGNoZWNrIHByZS1yZXFzCi8vZ3JhYl9yZXF1ZXN0X3ZhcnMoKTsK
Y2hlY2tfcHJlcmVxcygpOwoKLy8gdmlldyB0aGUgcHJvZmlsZQplY2hvIGJ1aWxkX3Byb2ZpbGVf
b3V0cHV0KCk7CgovKioKICogQHJldHVybiBzdHJpbmcKICovCmZ1bmN0aW9uIGJ1aWxkX3Byb2Zp
bGVfb3V0cHV0KCkKewogICAgJGNvbnRlbnQgPSAiPGg0Pk5hZ2lvcyBYSSAtIFN5c3RlbSBJbmZv
PC9oND4iOwoKICAgIC8vIFN5c3RlbQogICAgJGNvbnRlbnQgLj0gc2hvd19zeXN0ZW1fc2V0dGlu
Z3MoKTsKCiAgICAkY29udGVudCAuPSBzaG93X2FwYWNoZV9zZXR0aW5ncygpOwoKICAgIC8vIFRp
bWUgaW5mbwogICAgJGNvbnRlbnQgLj0gc2hvd190aW1lX3NldHRpbmdzKCk7CgogICAgLy8gWEkg
U3BlY2lmaWMgRGF0YQogICAgJGNvbnRlbnQgLj0gc2hvd194aV9pbmZvKCk7CgogICAgLy8gU3Vi
c3lzdGVtIGNhbGxzCiAgICAkY29udGVudCAuPSBydW5fc3Vic3lzdGVtX3Rlc3RzKCk7CgogICAg
Ly8gTmV0d29yawogICAgJGNvbnRlbnQgLj0gc2hvd19uZXR3b3JrX3NldHRpbmdzKCk7CgogICAg
Ly8gU2hvdyB3aXphcmQgYW5kIGNvbXBvbmVudCB2ZXJzaW9ucwogICAgJGNvbnRlbnQgLj0gc2hv
d19jb21wb25lbnRfaW5mbygpOwogICAgJGNvbnRlbnQgLj0gc2hvd193aXphcmRfaW5mbygpOwog
ICAgJGNvbnRlbnQgLj0gc2hvd19kYXNobGV0X2luZm8oKTsKCiAgICByZXR1cm4gbmwyYnIoJGNv
bnRlbnQpOwp9CgpmdW5jdGlvbiBzaG93X2NvbXBvbmVudF9pbmZvKCkKewogICAgZ2xvYmFsICRj
b21wb25lbnRzOwoKICAgICRodG1sID0gJzxoNT5OYWdpb3MgWEkgQ29tcG9uZW50czwvaDU+JzsK
ICAgICRodG1sIC49ICc8dGFibGU+JzsKCiAgICBmb3JlYWNoICgkY29tcG9uZW50cyBhcyAkY29t
cCkgewogICAgICAgICRodG1sIC49ICc8dHI+JzsKICAgICAgICAkaHRtbCAuPSAnPHRkPicgLiAk
Y29tcFsnYXJncyddWyduYW1lJ10gLiAnPC90ZD4nOwogICAgICAgICRodG1sIC49ICc8dGQ+JyAu
IEAkY29tcFsnYXJncyddWyd2ZXJzaW9uJ10gLiAnPC90ZD4nOwogICAgICAgICRodG1sIC49ICc8
L3RyPic7CiAgICB9CgogICAgJGh0bWwgLj0gJzwvdGFibGU+JzsKICAgIHJldHVybiAkaHRtbDsK
fQoKZnVuY3Rpb24gc2hvd193aXphcmRfaW5mbygpCnsKICAgIGdsb2JhbCAkY29uZmlnd2l6YXJk
czsKCiAgICAkaHRtbCA9ICc8aDU+TmFnaW9zIFhJIENvbmZpZyBXaXphcmRzPC9oNT4nOwogICAg
JGh0bWwgLj0gJzx0YWJsZT4nOwoKICAgIGZvcmVhY2ggKCRjb25maWd3aXphcmRzIGFzICRjZncp
IHsKICAgICAgICAkaHRtbCAuPSAnPHRyPic7CiAgICAgICAgJGh0bWwgLj0gJzx0ZD4nIC4gJGNm
d1snbmFtZSddIC4gJzwvdGQ+JzsKICAgICAgICAkaHRtbCAuPSAnPHRkPicgLiBAJGNmd1sndmVy
c2lvbiddIC4gJzwvdGQ+JzsKICAgICAgICAkaHRtbCAuPSAnPC90cj4nOwogICAgfQoKICAgICRo
dG1sIC49ICc8L3RhYmxlPic7CiAgICByZXR1cm4gJGh0bWw7Cn0KCmZ1bmN0aW9uIHNob3dfZGFz
aGxldF9pbmZvKCkKewogICAgZ2xvYmFsICRkYXNobGV0czsKCiAgICAkaHRtbCA9ICc8aDU+TmFn
aW9zIFhJIERhc2hsZXRzPC9oNT4nOwogICAgJGh0bWwgLj0gJzx0YWJsZT4nOwoKICAgIGZvcmVh
Y2ggKCRkYXNobGV0cyBhcyAkZGFzaCkgewogICAgICAgICRodG1sIC49ICc8dHI+JzsKICAgICAg
ICAkaHRtbCAuPSAnPHRkPicgLiAkZGFzaFsnbmFtZSddIC4gJzwvdGQ+JzsKICAgICAgICAkaHRt
bCAuPSAnPHRkPicgLiBAJGRhc2hbJ3ZlcnNpb24nXSAuICc8L3RkPic7CiAgICAgICAgJGh0bWwg
Lj0gJzwvdHI+JzsKICAgIH0KCiAgICAkaHRtbCAuPSAnPC90YWJsZT4nOwogICAgcmV0dXJuICRo
dG1sOwogICAgCn0KCmZ1bmN0aW9uIHNob3dfbmV0d29ya19zZXR0aW5ncygpCnsKICAgICRuZXR3
b3JrID0gIjxoNT5OZXR3b3JrIFNldHRpbmdzPC9oNT4iOwogICAgJG5ldHdvcmsgLj0gIjxwcmU+
IiAuIHNoZWxsX2V4ZWMoJ2lwIGFkZHInKSAuICI8L3ByZT4iIC4gIlxuIjsKICAgICRuZXR3b3Jr
IC49ICI8cHJlPiIgLiBzaGVsbF9leGVjKCdpcCByb3V0ZScpIC4gIjwvcHJlPiIgLiAiXG4iOwog
ICAgCiAgICByZXR1cm4gJG5ldHdvcms7Cn0KCi8qKgogKiBAcmV0dXJuIHN0cmluZwogKi8KZnVu
Y3Rpb24gc2hvd19zeXN0ZW1fc2V0dGluZ3MoKQp7CgogICAgJHByb2ZpbGUgPSBwaHBfdW5hbWUo
J24nKTsKICAgICRwcm9maWxlIC49ICcgJyAuIHBocF91bmFtZSgncicpOwogICAgJHByb2ZpbGUg
Lj0gJyAnIC4gcGhwX3VuYW1lKCdtJyk7CiAgICBAZXhlYygnd2hpY2ggZ25vbWUtc2Vzc2lvbiAy
PiYxJywgJG91dHB1dCwgJGdub21lKTsKCiAgICAkY29udGVudCA9ICI8aDU+U3lzdGVtPC9oNT4i
OwogICAgJGNvbnRlbnQgLj0gIk5hZ2lvcyBYSSB2ZXJzaW9uOiAiIC4gZ2V0X3Byb2R1Y3RfdmVy
c2lvbigpIC4gIlxuIjsKICAgICRjb250ZW50IC49ICJSZWxlYXNlIGluZm86ICRwcm9maWxlXG4i
OwogICAgLy9kZXRlY3QgZGlzdHJvIGFuZCB2ZXJzaW9uCiAgICAkZmlsZSA9IEBmaWxlX2dldF9j
b250ZW50cygnL2V0Yy9yZWRoYXQtcmVsZWFzZScpOwogICAgaWYgKCEkZmlsZSkKICAgICAgICAk
ZmlsZSA9IEBmaWxlX2dldF9jb250ZW50cygnL2V0Yy9mZWRvcmEtcmVsZWFzZScpOwogICAgaWYg
KCEkZmlsZSkKICAgICAgICAkZmlsZSA9IEBmaWxlX2dldF9jb250ZW50cygnL2V0Yy9sc2ItcmVs
ZWFzZScpOwoKICAgICRjb250ZW50IC49ICRmaWxlOwogICAgJGNvbnRlbnQgLj0gKCRnbm9tZSA+
IDApID8gIkdub21lIGlzIG5vdCBpbnN0YWxsZWRcbiIgOiAiIEdub21lIEluc3RhbGxlZFxuIjsK
CiAgICBpZiAoY2hlY2tfZm9yX3Byb3h5KCkpICRjb250ZW50IC49ICJQcm94eSBhcHBlYXJzIHRv
IGJlIGluIHVzZVxuIjsKCiAgICByZXR1cm4gJGNvbnRlbnQ7Cgp9CgovKioKICogQHJldHVybiBz
dHJpbmcKICovCmZ1bmN0aW9uIHNob3dfYXBhY2hlX3NldHRpbmdzKCkKewogICAgJGNvbnRlbnQg
PSAiPGg1PkFwYWNoZSBJbmZvcm1hdGlvbjwvaDU+IjsKICAgICRjb250ZW50IC49ICJQSFAgVmVy
c2lvbjogIiAuIFBIUF9WRVJTSU9OIC4gIlxuIjsKICAgIHJldHVybiAkY29udGVudDsKfQoKLyoq
CiAqIEByZXR1cm4gc3RyaW5nCiAqLwpmdW5jdGlvbiBzaG93X3RpbWVfc2V0dGluZ3MoKQp7Cgog
ICAgJHBocF90eiA9IChpbmlfZ2V0KCdkYXRlLnRpbWV6b25lJykgPT0gJycpID8gJ05vdCBzZXQn
IDogaW5pX2dldCgnZGF0ZS50aW1lem9uZScpOwogICAgJGNvbnRlbnQgPSAiPGg1PkRhdGUvVGlt
ZTwvaDU+IjsKICAgICRjb250ZW50IC49ICJQSFAgVGltZXpvbmU6ICRwaHBfdHogXG4iOwogICAg
JGNvbnRlbnQgLj0gIlBIUCBUaW1lOiAiIC4gZGF0ZSgncicpIC4gIlxuIjsKICAgICRjb250ZW50
IC49ICJTeXN0ZW0gVGltZTogIiAuIGV4ZWMoJy9iaW4vZGF0ZSAtUicpIC4gIlxuIjsKICAgIHJl
dHVybiAkY29udGVudDsKfQoKLyoqCiAqIEByZXR1cm4gc3RyaW5nCiAqLwpmdW5jdGlvbiBzaG93
X3hpX2luZm8oKQp7CiAgICBnbG9iYWwgJGNmZzsKICAgIGdsb2JhbCAkZGJfdGFibGVzOwogICAg
JHN0YXRkYXRhID0gJyc7CgogICAgLy8gSG9zdCBhbmQgc2VydmljZSBjb3VudAogICAgJGhvc3Rj
b3VudCA9IGdldF9hY3RpdmVfaG9zdF9saWNlbnNlX2NvdW50KCk7CiAgICAkc2VydmljZWNvdW50
ID0gZ2V0X2FjdGl2ZV9zZXJ2aWNlX2xpY2Vuc2VfY291bnQoKTsKICAgCiAgICAvLyBMYXN0IDYg
b2YgTGljZW5zZQogICAgJGxpY2Vuc2VfZW5kc193aXRoID0gc3Vic3RyKHRyaW0oZ2V0X2xpY2Vu
c2Vfa2V5KCkpLCAtNik7CgogICAgLy9hZGQgdG8gc3RhdGRhdGEgc3RyaW5nCiAgICAkc3RhdGRh
dGEgLj0gIlRvdGFsIEhvc3RzOiAkaG9zdGNvdW50IFxuIjsKICAgICRzdGF0ZGF0YSAuPSAiVG90
YWwgU2VydmljZXM6ICRzZXJ2aWNlY291bnQgXG5cbiI7CgogICAgLy9jb250ZW50IG91dHB1dAog
ICAgJGNvbnRlbnQgPSAiPGg1Pk5hZ2lvcyBYSSBEYXRhPC9oNT4iOwogICAgJGNvbnRlbnQgLj0g
IkxpY2Vuc2UgZW5kcyBpbjogIiAuICRsaWNlbnNlX2VuZHNfd2l0aCAuICJcbiI7CiAgICBpZiAo
aXNfdHJpYWxfbGljZW5zZSgpKQogICAgICAgICRjb250ZW50IC49ICJEYXlzIGxlZnQgaW4gVHJp
YWw6ICIuIGdldF90cmlhbF9kYXlzX2xlZnQoKSAuICJcbiI7CgogICAgLy8gR2V0IFVVSUQKICAg
ICR1dWlkID0gJyc7CiAgICAkeGlfdXVpZF9maWxlID0gJGNmZ1sncm9vdF9kaXInXSAuICcvdmFy
L3hpLXV1aWQnOwogICAgaWYgKGZpbGVfZXhpc3RzKCR4aV91dWlkX2ZpbGUpKSB7CiAgICAgICAg
JHV1aWQgPSB0cmltKGZpbGVfZ2V0X2NvbnRlbnRzKCR4aV91dWlkX2ZpbGUpKTsKICAgIH0KICAg
ICRjb250ZW50IC49ICI8ZGl2PlVVSUQ6ICIgLiAkdXVpZCAuICI8L2Rpdj4iOwoKICAgIC8vIEdl
dCBpbnN0YWxsYXRpb24gbWV0aG9kCiAgICAkaW5zdGFsbF90eXBlID0gIm1hbnVhbC91bmtub3du
IjsKICAgICRpbnN0YWxsX3R5cGVfZmlsZSA9ICRjZmdbJ3Jvb3RfZGlyJ10gLiAnL3Zhci94aS1p
dHlwZSc7CiAgICBpZiAoZmlsZV9leGlzdHMoJGluc3RhbGxfdHlwZV9maWxlKSkgewogICAgICAg
ICRpbnN0YWxsX3R5cGUgPSB0cmltKGZpbGVfZ2V0X2NvbnRlbnRzKCRpbnN0YWxsX3R5cGVfZmls
ZSkpOwogICAgfQogICAgJGNvbnRlbnQgLj0gIjxkaXY+SW5zdGFsbCBUeXBlOiAiIC4gJGluc3Rh
bGxfdHlwZSAuICI8L2Rpdj4iOwoKICAgICRjb250ZW50IC49ICI8YnI+IjsKICAgICRjb250ZW50
IC49ICRzdGF0ZGF0YTsKCiAgICAvLyBVUkwgcmVmZXJlbmNlIGNhbGxzCiAgICAkYmFzZV91cmwg
PSBnZXRfb3B0aW9uKCJ1cmwiKTsKICAgICRleHRlcm5hbF91cmwgPSBnZXRfb3B0aW9uKCJ1cmwi
KTsKICAgICRjb250ZW50IC49ICJQcm9ncmFtICBVUkw6ICIgLiAkYmFzZV91cmwgLiAiXG4iOwog
ICAgJGNvbnRlbnQgLj0gIkV4dGVybmFsIFVSTDogIiAuICRleHRlcm5hbF91cmwgLiAiXG4iOwog
ICAgcmV0dXJuICRjb250ZW50Owp9CgovKioKICogQHJldHVybiBib29sCiAqLwpmdW5jdGlvbiBj
aGVja19mb3JfcHJveHkoKQp7CgogICAgJHByb3h5ID0gZmFsc2U7CgogICAgJGYgPSBAZm9wZW4o
Jy9ldGMvd2dldHJjJywgJ3InKTsKICAgIGlmICgkZikgewogICAgICAgIHdoaWxlICghZmVvZigk
ZikpIHsKICAgICAgICAgICAgJGxpbmUgPSBmZ2V0cygkZik7CiAgICAgICAgICAgIGlmICghJGxp
bmUgfHwgJGxpbmVbMF0gPT0gJyMnKSBjb250aW51ZTsKICAgICAgICAgICAgaWYgKHN0cnBvcygk
bGluZSwgJ3VzZV9wcm94eSA9IG9uJykgIT09IEZBTFNFKSB7CiAgICAgICAgICAgICAgICAkcHJv
eHkgPSB0cnVlOwogICAgICAgICAgICAgICAgYnJlYWs7CiAgICAgICAgICAgIH0KICAgICAgICB9
CiAgICB9CgogICAgJHByb3h5X2VudiA9IGV4ZWMoJy9iaW4vZWNobyAkaHR0cF9wcm94eScpOwog
ICAgaWYgKHN0cmxlbigkcHJveHlfZW52ID4gMCkpICRwcm94eSA9IHRydWU7CiAgICByZXR1cm4g
JHByb3h5OwoKfQoKLyoqCiAqIEByZXR1cm4gc3RyaW5nCiAqLwpmdW5jdGlvbiBydW5fc3Vic3lz
dGVtX3Rlc3RzKCkKewogICAgZ2xvYmFsICRjZmc7CgogICAgLy9sb2NhbGhvc3QgcGluZyByZXNv
bHZlCiAgICAkY29udGVudCA9ICI8aDU+UGluZyBUZXN0IGxvY2FsaG9zdDwvaDU+IjsKICAgICRw
aW5nID0gJy9iaW4vcGluZyAtYyAzIGxvY2FsaG9zdCAyPiYxJzsKICAgICRjb250ZW50IC49ICJS
dW5uaW5nOiA8cHJlPiRwaW5nIDwvcHJlPiI7CiAgICAkaGFuZGxlID0gcG9wZW4oJHBpbmcsICdy
Jyk7CiAgICB3aGlsZSAoKCRidWYgPSBmZ2V0cygkaGFuZGxlLCA0MDk2KSkgIT0gZmFsc2UpCiAg
ICAgICAgJGNvbnRlbnQgLj0gJGJ1ZjsKCiAgICBwY2xvc2UoJGhhbmRsZSk7CgogICAgLy9nZXQg
c3lzdGVtIGluZm8KICAgICRodHRwcyA9IGdyYWJfYXJyYXlfdmFyKCRjZmcsICJ1c2VfaHR0cHMi
LCBmYWxzZSk7CiAgICAkdXJsID0gKCRodHRwcyA9PSB0cnVlKSA/ICJodHRwcyIgOiAiaHR0cCI7
CiAgICAvL2NoZWNrIGZvciBwb3J0ICMKICAgICRwb3J0ID0gZ3JhYl9hcnJheV92YXIoJGNmZywg
J3BvcnRfbnVtYmVyJywgZmFsc2UpOwogICAgJHBvcnQgPSAoJHBvcnQpID8gJzonIC4gJHBvcnQg
OiAnJzsKCiAgICAvL0NDTSByZXNvbHZlCiAgICAkY29udGVudCAuPSAiPGg1PlRlc3Qgd2dldCBU
byBsb2NhbGhvc3Q8L2g1PiI7CiAgICAkdXJsIC49ICI6Ly9sb2NhbGhvc3QiIC4gJHBvcnQgLiBn
ZXRfY29tcG9uZW50X3VybF9iYXNlKCJjY20iLCBmYWxzZSkgLiAiLyI7CiAgICAkY29udGVudCAu
PSAiV0dFVCBGcm9tIFVSTDogJHVybCBcbiI7CiAgICAkY29udGVudCAuPSAiUnVubmluZzogPHBy
ZT4vdXNyL2Jpbi93Z2V0ICR1cmwgPC9wcmU+IjsKCiAgICAkaGFuZGxlID0gcG9wZW4oIi91c3Iv
YmluL3dnZXQgIiAuICR1cmwgLiAnIC1PICcgLiBnZXRfdG1wX2RpcigpIC4gJy9jY21faW5kZXgu
dG1wIDI+JjEnLCAncicpOwogICAgd2hpbGUgKCgkYnVmID0gZmdldHMoJGhhbmRsZSwgMjA5Nikp
ICE9IGZhbHNlKQogICAgICAgICRjb250ZW50IC49IGh0bWxlbnRpdGllcygkYnVmKTsKCiAgICBw
Y2xvc2UoJGhhbmRsZSk7CiAgICB1bmxpbmsoZ2V0X3RtcF9kaXIoKSAuICcvY2NtX2luZGV4LnRt
cCcpOwogICAgcmV0dXJuICRjb250ZW50Owp9\n" | base64 -d > profgen.php

chmod 700 profgen.php
php profgen.php >  $PROFBASE/profile.html 2>/dev/null
rm profgen.php

}

##############################
gather_nagios_core_info () {
##############################

if [ "$DEBUG" = "true" ]; then 
printf "\nGathering Nagios Core information @ `/bin/date` \n\n"   | tee -a "$PROFBASE/getprofile.out"
fi 

if [ "$DEBUG" = "true" ]; then 
	echo "First 54 lines of status.dat"
fi 
/usr/bin/su -s /bin/bash -g $NAGGRP $NAGUSR -c "head -n 54 /usr/local/nagios/var/status.dat" > "$PROFBASE/status.dat-54.txt" 

if [ "$DEBUG" = "true" ]; then 
	echo "Creating nagios.txt..."
fi 
#nagios_log_file=$(cat /usr/local/nagios/etc/nagios.cfg | sed -n -e 's/^log_file=//p')
nagios_log_file=`/usr/bin/strings /usr/local/nagios/etc/nagios.cfg | sed -n -e 's/^log_file=//p' `
/usr/bin/su -s /bin/bash -g $NAGGRP $NAGUSR -c "/usr/bin/tail -n10000 $nagios_log_file" &> "$NAGLOGBASE/nagios.txt"

if [ "$DEBUG" = "true" ]; then 
echo "Creating perfdata.txt..."
fi 

perfdata_log_file="`/usr/bin/strings /usr/local/nagios/etc/pnp/process_perfdata.cfg | sed -n -e 's/^LOG_FILE = //p'`" 
/usr/bin/su -s /bin/bash -g $NAGGRP $NAGUSR -c "/usr/bin/tail -n 1000 $perfdata_log_file" &> "$NAGLOGBASE/perfdata.txt"

if [ "$DEBUG" = "true" ]; then 
echo "Creating npcd.txt..."
fi 
npcd_log_file="`/usr/bin/strings /usr/local/nagios/etc/pnp/npcd.cfg | sed -n -e 's/^log_file = //p'`" 
/usr/bin/su -s /bin/bash -g $NAGGRP $NAGUSR -c "/usr/bin/tail -n1000 $npcd_log_file" &> "$NAGLOGBASE/npcd.txt"

if [ "$DEBUG" = "true" ]; then 
echo "Copying objects.cache..."
fi 
objects_cache_file="`/usr/bin/strings /usr/local/nagios/etc/nagios.cfg | sed -n -e 's/^object_cache_file=//p' | tr -d '\r'`"
/usr/bin/su -s /bin/bash -g $NAGGRP $NAGUSR -c "cp $objects_cache_file $PROFBASE/"

spool_perfdata_location="`/usr/bin/strings /usr/local/nagios/etc/pnp/npcd.cfg | sed -n -e 's/^perfdata_spool_dir = //p'`" 
echo "Total files in $spool_perfdata_location" > "$PROFBASE/file_counts.txt"
ls -al "$spool_perfdata_location" | wc -l >> "$PROFBASE/file_counts.txt"
echo "" >> "$PROFBASE/file_counts.txt"


spool_xidpe_location="`/usr/bin/strings /usr/local/nagios/etc/commands.cfg | sed -n -e 's/\$TIMET\$.perfdata.host//p' | sed -n -e 's/\s*command_line\s*\/bin\/mv\s//p' | sed -n -e 's/.*\s//p'`"
echo "Total files in $spool_xidpe_location" >> "$PROFBASE/file_counts.txt"
ls -al "$spool_xidpe_location" | wc -l >> "$PROFBASE/file_counts.txt"
echo "" >> "$PROFBASE/file_counts.txt"

SPOOL_CHK_RES_LOC="`/usr/bin/strings /usr/local/nagios/etc/nagios.cfg | grep check_result_path  | cut -f2 -d"=" `"
echo "Total files in $SPOOL_CHK_RES_LOC " >> "$PROFBASE/file_counts.txt"
ls -al "$SPOOL_CHK_RES_LOC" | wc -l >> "$PROFBASE/file_counts.txt"
echo "" >> "$PROFBASE/file_counts.txt"


if [ "$DEBUG" = "true" ]; then 
echo "Getting Nagios Core version..."
fi 
/usr/local/nagios/bin/nagios --version > "$VERBASE/nagios.txt"

if [ "$DEBUG" = "true" ]; then 
echo "Getting NPCD version..."
fi
/usr/local/nagios/bin/npcd --version > "$VERBASE/npcd.txt"

if [ "$DEBUG" = "true" ]; then 
echo "Getting NRPE version..."
fi 
/usr/local/nagios/bin/nrpe --version > "$VERBASE/nrpe.txt"

if [ "$DEBUG" = "true" ]; then 
echo "Getting NSCA version..."
fi 
/usr/local/nagios/bin/nsca --version > "$VERBASE/nsca.txt"

if [ "$DEBUG" = "true" ]; then 
echo "Verifying ndo.so version ..."
fi 
/usr/bin/su -s /bin/bash -g $NAGGRP $NAGUSR -c "/usr/bin/strings /usr/local/nagios/bin/ndo.so | grep Copyright" > "$VERBASE/ndo.so.txt"

if [ "$DEBUG" = "true" ]; then 
echo "Getting Nagios-Plugins version..."
fi 
su -s /bin/bash nagios -c "/usr/local/nagios/libexec/check_ping --version" > "$VERBASE/nagios-plugins.txt"

find -L /usr/local/nagios -ls > "$FSBASE/nagios_core_file_perms.txt"

find -L /var/lib/mrtg/ -ls > "$FSBASE/var_lib_mrtg_perms.txt"
printf "\n\n\n" >> "$FSBASE/var_lib_mrtg_perms.txt"
ls -la /var/lib | grep "\." >> "$FSBASE/var_lib_mrtg_perms.txt"

if [ "$DEBUG" = "true" ]; then 
	echo "Getting nagios user status ..."
fi 
passwd -S nagios > "$PROFBASE/nagios-user-status.txt"
printf "\n ===================== \n" >> "$PROFBASE/nagios-user-status.txt"

chage -l nagios >> "$PROFBASE/nagios-user-status.txt"

} ### End gather_nagios_core_info 



##############################
gather_nagiosXI_info () {
##############################

if [ "$DEBUG" = "true" ]; then 
	printf "\nGathering Nagios XI information @ `/bin/date` \n\n"   | tee -a $PROFBASE/getprofile.out
fi 
if [ "$DEBUG" = "true" ]; then 
	echo "Please wait......."
fi 
echo "$distro" > "$PROFBASE/hostinfo.txt"
echo "$version" >> "$PROFBASE/hostinfo.txt"

if [ "$DEBUG" = "true" ]; then 
	echo "XI version information"
fi 
/usr/bin/su -s /bin/bash -g "$NAGGRP" "$NAGUSR" -c "/bin/cat /usr/local/nagiosxi/var/xiversion" > "$VERBASE/XIversion.txt"

if [ "$DEBUG" = "true" ]; then 
	echo "Getting UID/GIDs for nagios/apache/www-data user status ..."
fi 
printf "\n ============= UIDs ============= \n" > "$PROFBASE/GID-UID.txt"
grep "nagios\|apache\|www-data" /etc/passwd >> "$PROFBASE/GID-UID.txt"
printf "\n ============= GIDs ============= \n" >> "$PROFBASE/GID-UID.txt"
grep "nagios\|apache\|www-data" /etc/group >> "$PROFBASE/GID-UID.txt"
grep -i NAGIOS /etc/sudoers > $PROFBASE/sudoers.txt  


if [ `grep apache /etc/passwd` ]; then
        crontab -l -u apache >  $PROFBASE/crontab_apache.txt
elif [ `grep www-data /etc/passwd`   ]; then
        crontab -l -u www-data >  $PROFBASE/crontab_www-data.txt
fi

#
#
############## var logs in nagiosxi  
XIVARLOGS="cmdsubsys.log event_handler.log eventman.log perfdataproc.log sysstat.log feedproc.log cleaner.log dbmaint.log chromium_report.log nom.log recurringdowntime.log scheduledreporting.log"
LINECOUNT="4000"
###############
#
#
if [ "$DEBUG" = "true" ]; then 
echo "Creating system information..."
fi 

for logf in $XIVARLOGS
do

if [ -f /usr/local/nagiosxi/var/$logf ]; then

	if [ "$DEBUG" = "true" ]; then 
	    printf "\n Gathering $logf\n"
	fi
	/usr/bin/su -s /bin/bash -g $NAGGRP $NAGUSR -c "tail -n $LINECOUNT /usr/local/nagiosxi/var/$logf" > "$NAGLOGBASE/$logf"
fi

done
logf=""
#
#
############## Component Logs 
COMPLOGS="auditlog.log capacityplanning.log scheduledbackups.log"
LINECOUNT="4000"
###############
#

for logf in $COMPLOGS
do

if [ -f /usr/local/nagiosxi/var/components/$logf ]; then
	if [ "$DEBUG" = "true" ]; then 
	    printf "\n Gathering Component Log $logf\n"
	fi 
   /usr/bin/su -s /bin/bash -g "$NAGGRP" "$HTTPUSER" -c "tail -n $LINECOUNT /usr/local/nagiosxi/var/components/$logf" > "$NAGLOGBASE/$logf"
fi

done
logf=""

#
#
#
if [ "$DEBUG" = "true" ]; then 
	echo "Gathering upgrade.log ..."
fi
 
if [ -f /usr/local/nagiosxi/tmp/upgrade.log   ]; then
/usr/bin/su -s /bin/bash -g "$NAGGRP" "$NAGUSR" -c "/usr/bin/tail -n 50000 /usr/local/nagiosxi/tmp/upgrade.log"  > "$NAGLOGBASE/upgrade.log"
fi

if [ "$DEBUG" = "true" ]; then 
	echo "Copy of config.inc.php..."
fi 
cp /usr/local/nagiosxi/html/config.inc.php "$PROFBASE/config.inc.php"
sed -i '/pwd/d' "$PROFBASE/config.inc.php"
sed -i '/password/d' "$PROFBASE/config.inc.php"

FILE="`ls /usr/local/nagiosxi/nom/checkpoints/nagioscore/ | sort -n -t _ -k 2 | grep .gz | tail -1`"
/usr/bin/su -s /bin/bash -g "$NAGGRP" "$NAGUSR" -c "cp /usr/local/nagiosxi/nom/checkpoints/nagioscore/$FILE $PROFBASE/"

mkdir "$PROFBASE/snapshots"
chown "$NAGUSR":"$NAGGRP"  "$PROFBASE/snapshots"

for I in `find -L /usr/local/nagiosxi/nom/checkpoints/nagioscore/ -name "*.gz" -o -name "*.diff" | tail -6`
do
        /usr/bin/su -s /bin/bash -g "$NAGGRP" "$NAGUSR" -c "cp $I $PROFBASE/snapshots"
done

if [ "$DEBUG" = "true" ]; then 
	echo "Counting Performance Data Files..."
	echo "Counting MRTG Files..."
fi 
echo "Total files in /etc/mrtg/conf.d/" >> "$PROFBASE/file_counts.txt"
ls -al /etc/mrtg/conf.d/ | wc -l >> "$PROFBASE/file_counts.txt"
echo "" >> "$PROFBASE/file_counts.txt"

if [ "$DEBUG" = "true" ]; then 
	echo "Copying MRTG Configs..."
fi 

tar -pczf "$PROFBASE/mrtg.tar.gz" /etc/mrtg/ 2> /dev/null   

echo "Total files in /var/lib/mrtg/" >> "$PROFBASE/file_counts.txt"
ls -al /var/lib/mrtg/ | wc -l >> "$PROFBASE/file_counts.txt"
echo "" >> "$PROFBASE/file_counts.txt"

if [ "$DEBUG" = "true" ]; then 
	echo "Getting BPI configs..."
fi 
mkdir -p "$PROFBASE/bpi/"
/usr/bin/su -s /bin/bash -g "$NAGGRP" "$HTTPUSER" -c "cp /usr/local/nagiosxi/etc/components/bpi.conf* $PROFBASE/bpi/" 2>/dev/null

if [ "$DEBUG" = "true" ]; then 
	echo "Getting phpmailer.log..."
	# got the message ;-) 
fi 
if [ -f /usr/local/nagiosxi/tmp/phpmailer.log ]; then
    /usr/bin/su -s /bin/bash -g "$NAGGRP" "$NAGUSR" -c "tail -n 1500 /usr/local/nagiosxi/tmp/phpmailer.log > $PROFBASE/phpmailer.log"
fi

if [ "$DEBUG" = "true" ]; then 
	echo "Getting Crontab information..."
fi 
/bin/cat /etc/cron.d/nagiosxi >  "$PROFBASE/etc-crontab-file.txt"

if [ "$DEBUG" = "true" ]; then 
	echo "Getting nom data..."
fi 
error_txt="`ls -t /usr/local/nagiosxi/nom/checkpoints/nagioscore/errors/*.txt 2>/dev/null | head -n 1`"
error_tar_gz="`ls -t /usr/local/nagiosxi/nom/checkpoints/nagioscore/errors/*.tar.gz 2>/dev/null | head -n 1`"
sql_gz="`ls -t /usr/local/nagiosxi/nom/checkpoints/nagiosxi/*.sql.gz 2>/dev/null | head -n 1 `"

mkdir -p "$PROFBASE/nom/"
mkdir -p "$PROFBASE/nom/checkpoints/nagioscore/"
mkdir -p "$PROFBASE/nom/checkpoints/nagiosxi/"
mkdir -p "$PROFBASE/nom/checkpoints/nagioscore/errors/"
chown -R "$NAGUSR":"$NAGGRP" $PROFBASE/nom/

if [ ! -z "$error_txt" ]; then
        /usr/bin/su -s /bin/bash -g "$NAGGRP" "$NAGUSR" -c "cp  /usr/local/nagiosxi/nom/checkpoints/nagioscore/errors/*.txt  $PROFBASE/nom/checkpoints/nagioscore/errors/"
fi

if [ -f "$error_tar_gz"  ]; then
        /usr/bin/su -s /bin/bash -g "$NAGGRP" "$NAGUSR" -c "cp $error_tar_gz $PROFBASE/nom/checkpoints/nagioscore/errors/"
fi


if [ -f "$sql_gz" ]; then
        /usr/bin/su -s /bin/bash -g "$NAGGRP" "$NAGUSR" -c "cp $sql_gz $PROFBASE/nom/checkpoints/nagiosxi/"
fi


if [ -f "/usr/local/nagiosxi/tmp/profile-$folder.html" ]; then
        /usr/bin/su -s /bin/bash -g "$NAGGRP" "$NAGUSR" -c "mv -f /usr/local/nagiosxi/tmp/profile-$folder.html $PROFBASE/profile.html"
fi

######### get file perms for the nagiosxi direcotry 
find -L /usr/local/nagiosxi -ls >   $FSBASE/nagiosXI_file_perms.txt

if [ -d /var/nagiosramdisk ] ; then 
        find /var/nagiosramdisk -ls > $FSBASE/var_nagiosramdisk.txt  
fi 


} ### gather_nagiosXI_info 

##############################
gather_NFU_info () {
##############################

find "$BASEDIR" -ls > $PROFBASE/Filesystem/Fusion_file_perms.txt

for I in ` find /usr/local/nagiosfusion/var -type f `   
do 
        /usr/bin/su -s /bin/bash -g "$NAGGRP" "$NAGUSR" -c "tail -n 20000 $I >  $PROFBASE/` echo $I | sed -e 's/[^[:alnum:]|-]//g' |  rev | cut -f1 -d"/" | rev `"  
done

mysql -h 127.0.0.1 -uroot -pfusion -e 'SELECT NOW(); SELECT @@GLOBAL.time_zone, @@SESSION.time_zone;' > $PROFBASE/DB/db_timezone.txt 

echo "select * from servers;" | mysql -uroot -pfusion fusion  > $PROFBASE/DB/db_servers_table.txt  
echo "select * from users ;" | mysql -uroot -pfusion fusion > $PROFBASE/DB/db_users_table.txt
echo "select * from options ;" | mysql -uroot -pfusion fusion > $PROFBASE/DB/db_options_table.txt

if [ -f $PROFBASE/DB/db_options_table.txt ] ; then 
        /usr/bin/strings $PROFBASE/DB/db_options_table.txt |grep license_key  >  $PROFBASE/license_key.txt 
fi 


} ### End gather_NFU_info  

##############################
gather_db_info () {
##############################

if [ "$DEBUG" = "true" ]; then 
printf "\n Gathering DB information @ `/bin/date` \n\n"   | tee -a $PROFBASE/getprofile.out

echo "Creating mysqllog.txt..."
fi 

db_host=$(
    php -r '
        define("CFG_ONLY", 1);
        require_once($argv[1]);
        print(@$cfg["db_info"]["ndoutils"]["dbserver"] . "\n");
    ' \
        '/usr/local/nagiosxi/html/config.inc.php' 2>/dev/null |
    tail -1
)

if [ "$DEBUG" = "true" ]; then
        echo "MYSQROOTPASS ==  $MYSQLROOTPASS"
fi

echo "The database host is $db_host" > "$DBBASE/database_host.txt"
if [ "$db_host" == "localhost" ]; then

    if [ -f /var/log/mysqld.log ]; then
        /usr/bin/su -s /bin/bash -g mysql mysql -c "/usr/bin/tail -n 5000 /var/log/mysqld.log" > "$DBBASE/database_log.txt"
    elif [ -f /var/log/mariadb/mariadb.log ] ; then
        /usr/bin/su -s /bin/bash -g mysql mysql -c  "/usr/bin/tail -n 5000 /var/log/mariadb/mariadb.log" >> "$DBBASE/database_log.txt"
    elif [ -f /var/log/mysql/mysql.log ]; then
        /usr/bin/su -s /bin/bash -g mysql mysql -c "/usr/bin/tail -n 5000 /var/log/mysql/mysql.log" >> "$DBBASE/database_log.txt"
    elif [ -f /var/log/mysql/mysqld.log ]; then
        /usr/bin/su -s /bin/bash -g mysql mysql -c "/usr/bin/tail -n 5000 /var/log/mysql/mysqld.log" >> "$DBBASE/database_log.txt"
    fi
#
# Get max connetions  
#
    if [ -f /usr/bin/mysql -o -f /usr/local/bin/mysql ]; then
        printf "\n======================\nmysql max connections\n======================\n" >>  "$DBBASE/db_max_con.txt"
        mysql -uroot -p"$MYSQLROOTPASS" -e "show global status like '%used_connections%'; show variables like 'max_connections';" >>  "$DBBASE/db_max_con.txt" 2>/dev/null
        mysql -uroot -p"$MYSQLROOTPASS" -e "show global status like 'max_used_connections';" >>  "$DBBASE/db_vars.txt" 2>/dev/null
        mysql -uroot -p"$MYSQLROOTPASS" -N -e "show variables like 'max_allowed_packet';" >>  "$DBBASE/db_vars.txt" 2>/dev/null
        mysql -uroot -p"$MYSQLROOTPASS" -N -e "show variables like 'wait_timeout';" >>  "$DBBASE/db_vars.txt" 2>/dev/null
        mysql -uroot -p"$MYSQLROOTPASS" -N -e "show variables like 'interactive_timeout';" >>  "$DBBASE/db_vars.txt" 2> /dev/null
        mysql -uroot -p"$MYSQLROOTPASS" -N -e "show variables like 'open_files_limit';" >>  "$DBBASE/db_vars.txt" 2> /dev/null
    fi

#
# Get mysql table size and variables  
#
    if [ -f /usr/bin/mysql -o -f /usr/local/bin/mysql ]; then
        printf "\n======================\nmysql table size\n======================\n" >>  "$DBBASE/mysql_db_table_size.txt"
        mysql -uroot -p"$MYSQLROOTPASS" --table <<< "select * from (select table_name, round(((data_length + index_length) / 1024 / 1024), 2) as sz from information_schema.tables where table_schema like 'nagios%') as x order by x.sz;" >>  "$DBBASE/mysql_db_table_size.txt" 2>/dev/null
    fi

   if [ -f /usr/bin/mysql -o -f /usr/local/bin/mysql ]; then
        printf "\n" >>  "$DBBASE/mysql_db_table_crash.txt"
        mysql -uroot -p"$MYSQLROOTPASS" -e "show table status where comment like '%crash%';" nagiosql >> "$DBBASE/mysql_db_table_crash.txt" 2>/dev/null
        mysql -uroot -p"$MYSQLROOTPASS" -e "show table status where comment like '%crash%';" nagiosxi >> "$DBBASE/mysql_db_table_crash.txt" 2>/dev/null
        mysql -uroot -p"$MYSQLROOTPASS" -e "show table status where comment like '%crash%';" nagios >> "$DBBASE/mysql_db_table_crash.txt" 2>/dev/null
        printf "\n======================\nStrict Trans Tables \n======================\n" >>  "$DBBASE/mysql_strict_trans_table.txt"
        printf "SELECT @@SQL_MODE, @@GLOBAL.SQL_MODE\G;\n" | mysql -uroot -p"$MYSQLROOTPASS" nagiosql >>  "$DBBASE/mysql_strict_trans_table.txt" 2>/dev/null
	mysql -V >>  "$DBBASE/mysql_version.txt" 2>/dev/null
   fi

    # Check if we are running with postgresql
    $(grep -q pgsql /usr/local/nagiosxi/html/config.inc.php)

    if [ $? -eq 0 ]; then

if [ "$DEBUG" = "true" ]; then 
        echo "Getting xi_users..."
fi 
        echo 'select * from xi_users;' | psql nagiosxi nagiosxi > "$PROFBASE/xi_users.txt"

if [ "$DEBUG" = "true" ]; then 
        echo "Getting xi_usermeta..."
fi 
        echo 'select * from xi_usermeta;' | psql nagiosxi nagiosxi > "$PROFBASE/xi_usermeta.txt"

if [ "$DEBUG" = "true" ]; then 
        echo "Getting xi_options(mail)..."
fi 
        echo 'select * from xi_options;' | psql nagiosxi nagiosxi | grep mail > "$PROFBASE/xi_options_mail.txt"

if [ "$DEBUG" = "true" ]; then 
        echo "Getting xi_otions(smtp)..."
fi 
        echo 'select * from xi_options;' | psql nagiosxi nagiosxi | grep smtp > "$PROFBASE/xi_options_smtp.txt"
        
        psql nagiosxi nagiosxi <<< "select relname as table, pg_size_pretty(pg_total_relation_size(relid)) as size, pg_size_pretty(pg_total_relation_size(relid) - pg_relation_size(relid)) as externalsize from pg_catalog.pg_statio_user_tables order by pg_total_relation_size(relid) desc;"  >>  "$DBBASE/postgres_db_table_size.txt"

    else

if [ "$DEBUG" = "true" ]; then 
        echo "Getting xi_users... "
fi 
        echo 'select * from xi_users;' | mysql -uroot -p"$MYSQLROOTPASS" nagiosxi -t > "$PROFBASE/xi_users.txt" 2>/dev/null

if [ "$DEBUG" = "true" ]; then 
        echo "Getting xi_usermeta..."
fi
        echo 'select * from xi_usermeta;' | mysql -uroot -p"$MYSQLROOTPASS" nagiosxi -t > "$PROFBASE/xi_usermeta.txt" 2>/dev/null

if [ "$DEBUG" = "true" ]; then 
        echo "Getting xi_options(mail)..."
fi 
        echo 'select * from xi_options;' | mysql -t -uroot -p"$MYSQLROOTPASS" nagiosxi 2>/dev/null | grep mail > "$PROFBASE/xi_options_mail.txt"

if [ "$DEBUG" = "true" ]; then 

        echo "Getting additional xi_options information "
fi 
#
# TG would like to keep the previous calls seperate  
#
echo 'select * from xi_options;' | mysql -s -uroot -p"$MYSQLROOTPASS" nagiosxi 2>&1 | grep -v "default_notification_messages\|pw_\|proxy_auth\|license_key\|scheduled_\|smtp_password\|user_new_account_email\|fusekey\|mail_inbound\|deploy_token\|inbound_nrdp_tokens\|nsca_password" 2>/dev/null > "$PROFBASE/xi_options.txt"

if [ "$DEBUG" = "true" ]; then 
        echo "Getting xi_otions(smtp)..."
fi 
        echo 'select * from xi_options;' | mysql -t -uroot -p"$MYSQLROOTPASS" nagiosxi 2>/dev/null | grep smtp > "$PROFBASE/xi_options_smtp.txt"



    fi

    if which mysqladmin >/dev/null 2>&1; then
        errlog=$(mysqladmin -uroot -p"$MYSQLROOTPASS" variables  2> /dev/null | grep log_error)
        if [ $? -eq 0 ] && [ -f "$errlog" ]; then
            /usr/bin/tail -n500 "$errlog" > "$DBBASE/database_errors.txt"
        fi
    fi

    # Do manual check also, just in case we didn't get a log
    if [ -f /var/log/mysql.err ]; then
        /usr/bin/tail -n 1000 /var/log/mysql.err > "$DBBASE/database_errors.txt"
    elif [ -f /var/log/mysql/error.log ]; then
        /usr/bin/tail -n 1000 /var/log/mysql/error.log > "$DBBASE/database_errors.txt"
    elif [ -f /var/log/mariadb/error.log ]; then
        /usr/bin/tail -n 1000 /var/log/mariadb/error.log > "$DBBASE/database_errors.txt"
    fi

find  -L /var/lib/mysql -ls  > "$DBBASE/mysql_var_lib_files.txt"

fi ### End of if [ "$db_host" == "localhost" ]; then

} ### End gather_db_info 

###############################
# Primarily LogServer specific stuff 
# NLS - Log Server
##############################

####################
is_command() {
####################
    which "$1" >/dev/null 2>&1
} # End of is_command


####################
underline() {
####################
    echo "--------------------------"
    echo ""
}


####################
gather_ls_info () {
####################

CURLTIMEOUT="30"

if [ "$DEBUG" = "true" ]; then 
echo "Gathering license info  "
fi 

curl -m $CURLTIMEOUT -s http://localhost:9200/nagioslogserver/cf_option/license_key >> "$PROFBASE/license.raw" 2>&1
/bin/cat "$PROFBASE/license.raw"  | grep _source | rev | cut -f2 -d\" | rev > "$PROFBASE/license.txt"

curl -m $CURLTIMEOUT -s 'http://localhost:9200/nagioslogserver/cf_option/LDAP_certificates' >> "$PROFBASE/LDAP_certifcates.txt" 2>&1

echo "Current date" > "$PROFBASE/timestamp.txt"
underline >> "$PROFBASE/timestamp.txt"
date >> "$PROFBASE/timestamp.txt"


echo "Version" > "$PROFBASE/lsversion.txt"
underline >> "$PROFBASE/lsversion.txt"
/bin/cat /var/www/html/nagioslogserver/lsversion >> "$PROFBASE/lsversion.txt" 2>&1


echo "curl -m $CURLTIMEOUT -s 'localhost:9200/_cat/master?v'" > "$PROFBASE/masters.txt"
underline >> "$PROFBASE/masters.txt"
curl -m $CURLTIMEOUT -s "localhost:9200/_cat/master?v" >> "$PROFBASE/masters.txt" 2>&1


echo "curl -m $CURLTIMEOUT -s 'localhost:9200/_cat/nodes?v'" > "$PROFBASE/nodes.txt"
underline >> "$PROFBASE/nodes.txt"
curl -m $CURLTIMEOUT -s "localhost:9200/_cat/nodes?v" >> "$PROFBASE/nodes.txt" 2>&1


echo "curl -m $CURLTIMEOUT -s 'localhost:9200/_cluster/health/*?level=shards&pretty'" > "$PROFBASE/shard-health.txt"
underline >> "$PROFBASE/shard-health.txt"
curl -m $CURLTIMEOUT -s "localhost:9200/_cluster/health/*?level=shards&pretty" >> "$PROFBASE/shard-health.txt" 2>&1


echo "curl -m $CURLTIMEOUT -s 'localhost:9200/_cat/shards?pretty'" > "$PROFBASE/shard-status.txt"
underline >> "$PROFBASE/shard-status.txt"
curl -m $CURLTIMEOUT -s "localhost:9200/_cat/shards?pretty" >> "$PROFBASE/shard-status.txt" 2>&1


echo "curl -m $CURLTIMEOUT -s 'localhost:9200/_cat/indices?pretty'" > "$PROFBASE/indices-status.txt"
underline >> "$PROFBASE/indices-status.txt"
curl -m $CURLTIMEOUT -s "localhost:9200/_cat/indices?pretty" >> "$PROFBASE/indices-status.txt" 2>&1


echo "curl -m $CURLTIMEOUT -s 'localhost:9200/_nodes/jvm?pretty'" > "$PROFBASE/jvm-status.txt"
underline >> "$PROFBASE/jvm-status.txt"
curl -m $CURLTIMEOUT -s "localhost:9200/_nodes/jvm?pretty" >> "$PROFBASE/jvm-status.txt" 2>&1

echo "curl -m $CURLTIMEOUT -s 'localhost:9200/_cluster/state?pretty'" > "$PROFBASE/cluster.txt"
underline >> "$PROFBASE/cluster.txt"
curl -m $CURLTIMEOUT -s "localhost:9200/_cluster/state?pretty" >> "$PROFBASE/cluster.txt" 2>&1


echo "curl -m $CURLTIMEOUT -s 'localhost:9200/_cat/pending_tasks?v'" > "$PROFBASE/pending-tasks.txt"
underline >> "$PROFBASE/pending-tasks.txt"
curl -m $CURLTIMEOUT -s "localhost:9200/_cat/pending_tasks?v" >> "$PROFBASE/pending-tasks.txt" 2>&1


echo "curl -m $CURLTIMEOUT -s 'localhost:9200/_cat/recovery?v'" > "$PROFBASE/recovery.txt"
underline >> "$PROFBASE/recovery.txt"
curl -m $CURLTIMEOUT -s "localhost:9200/_cat/recovery?v" >> "$PROFBASE/recovery.txt" 2>&1

echo "curl -m $CURLTIMEOUT -XGET 'http://localhost:9200/nagioslogserver/cf_option/disable_reverse_dns?pretty'" >  "$PROFBASE/disable_reverse_dns.txt"
underline >> "$PROFBASE/disable_reverse_dns.txt"
curl -m $CURLTIMEOUT -XGET 'http://localhost:9200/nagioslogserver/cf_option/disable_reverse_dns?pretty' -s >> "$PROFBASE/disable_reverse_dns.txt"

echo "curl -m $CURLTIMEOUT -s 'localhost:9200/_cat/plugins?v'" > "$PROFBASE/plugins.txt"
underline >> "$PROFBASE/plugins.txt"
curl -m $CURLTIMEOUT -s "localhost:9200/_cat/plugins?v" >> "$PROFBASE/plugins.txt" 2>&1

echo "curl -m $CURLTIMEOUT -s -XGET 'http://localhost:9200/_nodes/_all/?human&pretty'" > "$PROFBASE/nodes-all.txt"
underline >> "$PROFBASE/nodes-all.txt"
curl -m $CURLTIMEOUT -s -XGET 'http://localhost:9200/_nodes/_all/?human&pretty' >> "$PROFBASE/nodes-all.txt" 2>&1

echo "/usr/local/nagioslogserver/logstash/bin/plugin list" > "$PROFBASE/logstash-plugins.txt"
underline >> "$PROFBASE/logstash-plugins.txt"
/usr/local/nagioslogserver/logstash/bin/plugin list >> "$PROFBASE/logstash-plugins.txt" 2>&1

echo "curl -m $CURLTIMEOUT -s 'http://localhost:9200/nagioslogserver/commands/_search?size=999&q=type%3Asystem&sort=command%3Aasc&pretty'" > "$PROFBASE/cmd_subsys_schedule.txt"
underline >> "$PROFBASE/plugins.txt"
curl -m $CURLTIMEOUT -s 'http://localhost:9200/nagioslogserver/commands/_search?size=999&q=type%3Asystem&sort=command%3Aasc&pretty' >> "$PROFBASE/cmd_subsys_schedule.txt" 2>&1

echo "free -m" > "$PROFBASE/memory.txt"
underline >> "$PROFBASE/memory.txt"
free -m >> "$PROFBASE/memory.txt" 2>&1

# Copy entire Logstash conf.d dir
if [ -d /usr/local/nagioslogserver/logstash/etc/conf.d ]; then
    cp -r /usr/local/nagioslogserver/logstash/etc/conf.d "$PROFBASE/logstash-confd"
fi


if [ "$DEBUG" = "true" ]; then 
	echo "Gathering nagioslogserver perms..."
fi 
 
find -L /usr/local/nagioslogserver/ -ls > "$FSBASE/logserver_file_perms.txt"
find -L  /var/www/html/ -ls > "$FSBASE/webroot_file_perms.txt"

#LSLGDIR=" /var/log/elasticsearch /var/log/logstash /usr/local/nagioslogserver/var/" 

if [ "$DEBUG" = "true" ]; then 
	echo "Gathering LogServer logs..."
fi 
#cp -r /var/log/elasticsearch "$PROFBASE/elasticsearchlog"
#cp -r /var/log/logstash "$PROFBASE/logstashlogs"
#cp -r /usr/local/nagioslogserver/var/*.log  "$NAGLOGBASE/"

# Number of lines to pull from ELK log files
LSLFCOUNT="500000" 

if [ ! -d $PROFBASE/elasticsearchlog ] ; then
        mkdir $PROFBASE/elasticsearchlog
fi
if [ ! -d $PROFBASE/logstash ] ; then
        mkdir $PROFBASE/logstash
fi

if [ ! -d $PROFBASE/nagioslogserver ] ; then
        mkdir $PROFBASE/nagioslogserver
fi
 chown -R "$NAGUSR":"$NAGGRP" "$PROFBASE" 
############################
for I in ` find /var/log/elasticsearch -type f -name "*.log" -print  `
do
        /usr/bin/su -s /bin/bash -g "$NAGGRP" "$NAGUSR" -c "tail -n $LSLFCOUNT  $I >   $PROFBASE/elasticsearchlog/`echo $I | rev | cut -f1 -d"/" | rev `"
done

for I in ` find /var/log/elasticsearch -type f -name "*.gz" -print  `
do
        /usr/bin/su -s /bin/bash -g "$NAGGRP" "$NAGUSR" -c "/usr/bin/zcat $I | tail -n $LSLFCOUNT >  $PROFBASE/elasticsearchlog/`echo $I | rev | cut -f1 -d"/" | rev |sed s/'.gz'//g `"
done

############################
for I in ` find /var/log/logstash -type f -name "*.log" -print  `
do
        /usr/bin/su -s /bin/bash -g "$NAGGRP" "$NAGUSR" -c "tail -n $LSLFCOUNT  $I >   $PROFBASE/logstash/`echo $I | rev | cut -f1 -d"/" | rev `"
done

for I in ` find /var/log/logstash -type f -name "*.gz" -print  `
do
        /usr/bin/su -s /bin/bash -g "$NAGGRP" "$NAGUSR" -c "/usr/bin/zcat $I | tail -n $LSLFCOUNT >  $PROFBASE/logstash/`echo $I | rev | cut -f1 -d"/" | rev |sed s/'.gz'//g `"
done

############################
for I in ` find /usr/local/nagioslogserver/var -type f -name "*.log" -print  `
do
        /usr/bin/su -s /bin/bash -g "$NAGGRP" "$NAGUSR" -c "tail -n $LSLFCOUNT  $I >   $PROFBASE/nagioslogserver/`echo $I | rev | cut -f1 -d"/" | rev `"
done

for I in ` find /usr/local/nagioslogserver/var -type f -name "*.gz" -print  `
do
        /usr/bin/su -s /bin/bash -g "$NAGGRP" "$NAGUSR" -c "/usr/bin/zcat $I | tail -n $LSLFCOUNT >  $PROFBASE/nagioslogserver/`echo $I | rev | cut -f1 -d"/" | rev |sed s/'.gz'//g ` "
done


if [ "$DEBUG" = "true" ]; then 
	echo "Running curator.sh show indicies..."
fi
 
/usr/local/nagioslogserver/scripts/curator.sh show indices --all-indices > "$PROFBASE/curator_test.txt"

echo "curl -m $CURLTIMEOUT -XGET 'http://localhost:9200/nagioslogserver/cf_option/maintenance_settings?pretty'" >  "$PROFBASE/maintenance_settings.txt"
underline >> "$PROFBASE/maintenance_settings.txt"
curl -m $CURLTIMEOUT -XGET 'http://localhost:9200/nagioslogserver/cf_option/maintenance_settings?pretty' -s >> "$PROFBASE/maintenance_settings.txt"

if is_command "pip"; then
        env pip list > "$VERBASE/pip_list.txt"
fi


#LSFILES=" /usr/local/nagioslogserver/elasticsearch/config/elasticsearch.yml /usr/local/nagioslogserver/elasticsearch/config/logging.yml /usr/local/nagioslogserver/var/node_uuid /etc/init.d/logstash /etc/machine_id /etc/init.d/logstash /etc/init.d/elasticsearch /etc/sysconfig/elasticsearch /etc/sysconfig/logstash "

# Updated LSFILES per Craig 
LSFILES="/usr/local/nagioslogserver/elasticsearch/config/elasticsearch.yml /usr/local/nagioslogserver/elasticsearch/config/logging.yml /usr/local/nagioslogserver/var/node_uuid /etc/init.d/logstash /etc/machine-id /etc/init.d/logstash /etc/init.d/elasticsearch /etc/sysconfig/elasticsearch /etc/sysconfig/logstash /etc/default/elasticsearch /etc/default/logstash"

for I in $LSFILES
do
	if [ -f "$I" ] ; then 
	        /usr/bin/su -s /bin/bash -g "$NAGGRP" "$NAGUSR" -c "cp $I $PROFBASE/`echo $I | sed s/'\/'/'_'/g |sed s/'_'/''/1`"
	else 
		
		if [ "$DEBUG" = "true" ]; then 
		printf "\n $I not found \n" 
		fi 
	fi  
done

} # End gather_ls_info  

####################
find_zero_byte_files () {
####################

if [ "$DEBUG" = "true" ]; then 
	echo "Gathering list of 0 byte state files"
fi 
# Credit to CD for the function 

rm -f $PROFBASE/zerobytefiles.txt
touch zerobytefiles.txt

#ESDIR=`grep -oP 'APP_DIR="\K[^"]+' /etc/sysconfig/elasticsearch`
#ESHOME=`grep -oP 'ES_HOME="\\$APP_DIR\K[^"]+' /etc/sysconfig/elasticsearch`
#ESDATA=`grep -oP 'DATA_DIR="\\$ES_HOME\K[^"]+' /etc/sysconfig/elasticsearch`

if [ -f /etc/sysconfig/elasticsearch ]; then
    ESDIR=`grep -oPs 'APP_DIR="\K[^"]+' /etc/sysconfig/elasticsearch`
    ESHOME=`grep -oPs 'ES_HOME="\\$APP_DIR\K[^"]+' /etc/sysconfig/elasticsearch`
    ESDATA=`grep -oPs 'DATA_DIR="\\$ES_HOME\K[^"]+' /etc/sysconfig/elasticsearch`
elif [ -f /etc/default/elasticsearch ] ; then
    ESDIR=`grep -oPs 'APP_DIR="\K[^"]+' /etc/default/elasticsearch`
    ESHOME=`grep -oPs 'ES_HOME="\\$APP_DIR\K[^"]+' /etc/default/elasticsearch`
    ESDATA=`grep -oPs 'DATA_DIR="\\$ES_HOME\K[^"]+' /etc/default/elasticsearch`
else
        printf "\n Looks like NLS is not installed !!!\n"
fi

ES_DATA="$ESDIR$ESHOME$ESDATA"

ZEROBYTEFILES=($(find $ES_DATA -type f -size 0 -name "*state*" ))

printf "${#ZEROBYTEFILES[@]} files under $ES_DATA are 0 bytes:\n" >> $PROFBASE/zerobytefiles.txt

for ZEROBYTEFILE in ${ZEROBYTEFILES[@]}; do
    printf "\n\t$ZEROBYTEFILE\n" >> $PROFBASE/zerobytefiles.txt
done
printf "\n\n" >> $PROFBASE/zerobytefiles.txt

REPOS=($(curl -m $CURLTIMEOUT -X GET "localhost:9200/_snapshot/?pretty" -s | grep -oP '"location" : "\K[^"]+'))

for REPO in ${REPOS[@]}; do
    ZEROBYTEREPOFILES=($(find $REPO -type f -size 0))
    printf "\n${#ZEROBYTEREPOFILES[@]} files under $REPO are 0 bytes:\n" >> $PROFBASE/zerobytefiles.txt

#    for ZEROBYTEREPOFILE in ${ZEROBYTEFILES[@]}; do
for ZEROBYTEREPOFILE in ${ZEROBYTEREPOFILES[@]}; do
        printf "\n\t$ZEROBYTEREPOFILE" >> $PROFBASE/zerobytefiles.txt
    done
done


} # End of find_zero_byte_files



####################
gather_nna_info () {
####################

NNALINECOUNT="500000"

find -L /usr/local/nagiosna -ls >   $FSBASE/nagiosna_file_perms.txt

#cat /usr/local/nagiosna/var/nna-itype > $PROFBASE/Installation_type.txt
/bin/cat /var/www/html/nagiosna/naversion > $PROFBASE/versions/NNA_version.txt 

for I in ` find /usr/local/nagiosna/var/ -type f -name "backend.log*" -print  `

do
        /usr/bin/su -s /bin/bash -g "$NAGGRP" "$NAGUSR" -c "tail -n $NNALINECOUNT  $I" >   "$PROFBASE/logs/`echo $I | rev | cut -f1 -d"/" | rev `"
done

echo "select * from nagiosna_Sources;" | mysql -t nagiosna -t >> $PROFBASE/DB/nagiosna_Sources.txt
echo 'select * from nagiosna_SourceGroups;' | mysql -unagiosna -pnagiosna nagiosna -t>> $PROFBASE/DB/nagiosna_SourceGroups.txt
echo 'select * from nagiosna_SourcesViewsLinker;' | mysql -unagiosna -pnagiosna nagiosna -t >> $PROFBASE/DB/nagiosna_SourcesViewsLinker.txt
echo 'select * from nagiosna_Views;' | mysql -unagiosna -pnagiosna nagiosna -t >> $PROFBASE/DB/nagiosna_SourcesViewsLinker.txt 
echo 'select * from nagiosna_Checks;' | mysql -unagiosna -pnagiosna nagiosna -t >> $PROFBASE/DB/nagiosna_Checks.txt
echo 'select * from nagiosna_SGLinker;' | mysql -unagiosna -pnagiosna nagiosna -t >> $PROFBASE/DB/nagiosna_SGLinker.txt
echo 'select * from nagiosna_cf_options;' | mysql -unagiosna -pnagiosna nagiosna -t >> $PROFBASE/DB/nagiosna_cf_options.txt

/bin/cat $PROFBASE/DB/nagiosna_cf_options.txt | grep license | cut -f7 -d"|" > $PROFBASE/license.txt 

if [ -f /var/log/mysqld.log ]; then
	/usr/bin/su -s /bin/bash -g mysql mysql -c "/usr/bin/tail -n $NNALINECOUNT /var/log/mysqld.log" > "$DBBASE/database_log.txt"
elif [ -f /var/log/mariadb/mariadb.log ]; then
	/usr/bin/su -s /bin/bash -g mysql mysql -c "/usr/bin/tail -n $NNALINECOUNT /var/log/mariadb/mariadb.log" >> "$DBBASE/database_log.txt"
elif [ -f /var/log/mysql/mysql.log ]; then
        "/usr/bin/tail -n $NNALINECOUNT /var/log/mysql/mysql.log" >> "$DBBASE/database_log.txt"
fi


chage -l nna >> $PROFBASE/nna_user.txt 
grep nna /etc/group >> $PROFBASE/nna_group.txt 


} # End of gather_nna_info

gather_brk_info () {
	find /tmp -ls  > $FSBASE/tmp-directory.txt  
	find /var -ls  > $FSBASE/var-directory.txt  

} # End of gather_brk_info 

####################
get_XI_dbpass () {
####################
if [ -f  /usr/local/nagiosxi/etc/xi-sys.cfg ] ; then
	MYSQLROOTPASS="`strings /usr/local/nagiosxi/etc/xi-sys.cfg | grep mysqlpass= | cut -f2 -d\' |sed s/'\`'//g | sed s/'\*'//g | sed s/'\;'//g | sed s/'\!'//g | cut -f1 -d'$'  `"	
else
      MYSQLROOTPASS="nagiosxi"
fi

} # get_XI_dbpass 

####################
set_user_and_group () {
####################
case "$distro" in 
	CentOS|Oracle|RedHat)  
	HTTPUSER="apache" 
	HTTPGROUP="apache"	
	;;

	Debian|Ubuntu) 
	HTTPUSER="www-data" 
	HTTPGROUP="www-data" 
	;;

	*)
	HTTPUSER="apache"
	HTTPGROUP="apache"
		;;
esac
if  [ "$NAGPROD" = "XI" -o "$NAGPROD" = "LS" -o "$NAGPROD" = "NFU" ] ; then
	NAGUSR="nagios" 
	NAGGRP="nagios" 
elif [ "$NAGPROD" = "NNA" ] ; then
	NAGUSR="nna"
	NAGGRP="nnacmd" 
else 
	printf "\n No OS Match \n"  
	NAGUSR="nagios"
	NAGGRP="nagios" 
fi 

} # setuserandgroup

#############################################
# int main 
#############################################

get_os_and_version
set_user_and_group 
process_args
setup_dir_structure  

if  [  "$NAGPROD" = "XI" ] ; then

	if [ -z $funcstuff ]; then
	#       printf "\n No funcstuff \n" 
       	 funcstuff="ALL"         
	fi

	
	case "$funcstuff" in
        ALL|all)
		get_XI_dbpass 
		gather_nagios_core_info
		printf "Percent Complete: 10\n" 
                gather_package_info 
		printf "Percent Complete: 20\n" 
                gather_system_info
		printf "Percent Complete: 30\n" 
                gather_db_info
		printf "Percent Complete: 40\n" 
                gather_third_party
		printf "Percent Complete: 50\n" 
                gather_diskuse_info
		printf "Percent Complete: 60\n" 
                generate_profile_dot_html
		printf "Percent Complete: 70\n" 
                gather_nagiosXI_info
		printf "Percent Complete: 80\n" 
		gather_NCPA_info
		printf "Percent Complete: 90\n" 
                zip_it_up
		printf "Percent Complete: 100\n" 
        ;;
        CORE|core)
                gather_nagios_core_info
                zip_it_up
        ;;
        SYSTEM|system)
                gather_system_info
                zip_it_up 
        ;;
        XI|xi)
                gather_nagiosXI_info
                zip_it_up
        ;;
        DB|db) 
                gather_db_info
                zip_it_up
        ;;
        3RD|3rd) 
                gather_third_party
                zip_it_up       
        ;;
        profile-dot-html|PROFILE-DOT-HTML) 
                generate_profile_dot_html
                zip_it_up
        ;;
        take5|TAKE5) 
                Take5
        ;;
        updategp|UPDATEGP) 
                install_it
        ;;
        *)
               printf "\n\n\n\n\n###########################################\n\nYou must enter a folder name/id to generate a profile.\n\n./getprofile.sh <id> [function]\n\n\n\n\n"
                exit 1
        ;;
	esac

elif [ "$NAGPROD" = "LS" ] ; then

	if [ -z $funcstuff ]; then
	#       printf "\n No funcstuff \n" 
	        funcstuff="ALL"         
	fi

	case "$funcstuff" in
        ALL|all)
		printf "Percent Complete: 12\n" 
                gather_package_info 
		printf "Percent Complete: 24\n" 
                gather_ls_info  
		printf "Percent Complete: 36\n" 
                gather_third_party
		printf "Percent Complete: 48\n" 
                gather_system_info
		printf "Percent Complete: 60\n" 
                gather_diskuse_info
		printf "Percent Complete: 72\n" 
                find_zero_byte_files
		printf "Percent Complete: 84\n" 
		gather_NCPA_info
		printf "Percent Complete: 96\n" 
                zip_it_up
		printf "Percent Complete: 100\n" 
        ;;
        SYSTEM|system)
                gather_system_info
                zip_it_up 
        ;;
        3RD|3rd) 
                gather_third_party
                zip_it_up       
        ;;
        install|INSTALL) 
               	install_it 
        ;;
        take5|TAKE5) 
                Take5
        ;;
        *)
               printf "\n\n\n\n\n###########################################\n\n./$0 [function]\n###########################################\n\n\n\n\n"
                exit 1
        ;;
	esac

elif [ "$NAGPROD" = "NNA" ] ; then
	gather_package_info 
	printf "Percent Complete: 14\n" 
	gather_system_info
	printf "Percent Complete: 28\n" 
	gather_third_party
	printf "Percent Complete: 42\n" 
	gather_diskuse_info
	printf "Percent Complete: 56\n" 
	gather_nna_info
	printf "Percent Complete: 70\n" 
	gather_NCPA_info
	printf "Percent Complete: 94\n" 
	zip_it_up
	printf "Percent Complete: 100\n" 

elif [ "$NAGPROD" = "NFU" ] ; then
	gather_package_info
	gather_third_party
	gather_system_info
	gather_diskuse_info
	gather_NCPA_info
	gather_NFU_info
	zip_it_up
elif [ "$NAGPROD" = "BRK" ] ; then
	DEBUG="true"
	printf "\n\n\n\n\n###########################################\nNOTE: You will see errors as the script runs you do not need to report them.\nJust attach the output file identified when the script completes\n###########################################\n\n\n\n\n\a"
	sleep 7 
	gather_package_info
	sleep 5 
	gather_third_party
	gather_system_info
	gather_diskuse_info
	gather_NCPA_info
	gather_nagios_core_info
	gather_db_info
	gather_nagiosXI_info
#	gather_ls_info
	gather_brk_info
	zip_it_up
fi

#echo folder $folder funcstuff $funcstuff
