FROM ubuntu:22.04

# Set non-interactive frontend
ENV DEBIAN_FRONTEND=noninteractive

# Install dependencies
RUN apt update && \
    apt install -y wget unzip apache2 php libapache2-mod-php php-mysql mysql-server expect && \
    apt clean

# Ensure MySQL system user exists and has correct permissions
RUN useradd -r -u 999 -g root -s /bin/false mysql || true

# Set up MySQL data directory with correct ownership
RUN mkdir -p /var/lib/mysql && chown -R mysql:mysql /var/lib/mysql

# Initialize MySQL (ensures a clean setup)
RUN mysqld --initialize-insecure --user=mysql --datadir=/var/lib/mysql & sleep 5

# Start MySQL in the background
RUN mysqld_safe --bind-address=127.0.0.1 & \
    sleep 5 && \
    while ! mysqladmin ping --silent; do sleep 1; done

# COPY EDITED Nagios XI
WORKDIR /tmp
COPY nagios_xi_src/* ./
RUN chmod 755 -R nagiosxi

# Install Nagios XI (while MySQL is running)
WORKDIR /tmp/nagiosxi
RUN ./fullinstall --non-interactive

# Expose required ports
EXPOSE 80 5667

# Copy entrypoint script
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Use entrypoint script to start MySQL and Apache when the container runs
ENTRYPOINT ["/entrypoint.sh"]



#FROM ubuntu:22.04
#LABEL authors="Peter Nearing"
#
#
#ENV NAGIOS_USER=nagios
#ENV NAGIOS_GROUP=nagios
#ENV NAGIOS_CMD_GROUP=nagioscmd
#ENV NAGIOS_HOME=/home/$NAGIOS_USER
#ENV APACHE_USER=www-data
#
#ENV DEBIAN_FRONTEND=noninteractive
#ENV PERL_MM_USE_DEFAULT=1
#RUN apt-get update
#RUN apt-get install apt-transport-https
#RUN apt-get upgrade -y
#
#
#RUN apt-get install -y mysql-server
#
#RUN systemctl start mysql
#
#WORKDIR /tmp/
#COPY blobs/xi-2024R1.3.3.tar.gz ./
#RUN tar -xvzf xi-2024R1.3.3.tar.gz
#WORKDIR /tmp/nagiosxi/
#RUN ./fullinstall --non-interactive
#
#




################
## 00-repos: Using ubuntu22, not changing repos.
#
#
################
## 01-prereqs:
#RUN apt-get install -y build-essential libgd3 libgd-dev python3-numpy python3-lxml python3-simplejson python3-winrm \
#    libmysqlclient-dev python3-pymssql python3-lxml python3-numpy python3-pip python3-rrdtool sshpass \
#    automake autoconf bc dnsutils dos2unix dstat libxml-parser-perl fping gcc libc6 graphviz apache2 apache2-utils \
#    iptables mcrypt locales libmcrypt-dev make automake mailutils snmp snmptt snmptrapd snmpd libsnmp-base libsnmp-dev \
#    snmp-mibs-downloader nmap ntp slapd libpq5 libpq-dev libldap2-dev ldap-utils openssl ssh libexpat1-dev libssl-dev \
#    curl libcurl4-openssl-dev perl libmail-imapclient-perl libdbd-mysql-perl libnet-dns-perl libnet-snmp-perl php \
#    php-pdo php-ssh2 php-sybase libapache2-mod-php php-gd php-mysql php-pear php-sqlite3 php-mbstring libssh2-1-dev \
#    php-pgsql php-intl php-dev php-snmp php-imap php-ldap php-curl libpq-dev rrdtool librrds-perl subversion sudo \
#    sysstat traceroute unzip wget xinetd zip tftp-hpa uuid-runtime rsync xfonts-75dpi xfonts-base postgresql gawk \
#    postgresql-contrib ansible dc git iputils-ping cron jq poppler-utils mysql-server
#
#RUN apt-get clean
#
## Generate locales for the Nagios XI:
#COPY install_scripts/01-generate_locales.sh ./
#RUN ./01-generate_locales.sh
#
## Instal cpan
#RUN LANG="C" cpan install CPAN
#
## Enable apache mods and default ssl site:
#RUN a2enmod cgi
#RUN a2enmod ssl
#RUN a2enmod rewrite
#RUN a2ensite default-ssl
#
######################
## 02-usersgroups:
#
## Create the groups:
#RUN ( egrep -i "^${NAGIOS_GROUP}"     /etc/group || groupadd $NAGIOS_GROUP    )
#RUN ( egrep -i "^${NAGIOS_CMD_GROUP}" /etc/group || groupadd $NAGIOS_CMD_GROUP)
#
## Create the user:
#RUN ( id -u $NAGIOS_USER || useradd --system -d $NAGIOS_HOME -g $NAGIOS_GROUP $NAGIOS_USER)
#
## Add users to groups:
#RUN usermod -a -G $NAGIOS_CMD_GROUP $NAGIOS_USER
#RUN usermod -a -G $NAGIOS_GROUP $APACHE_USER
#RUN usermod -a -G $NAGIOS_CMD_GROUP $APACHE_USER
#
#
#
#RUN systemctl disable postgresql.service
#
#RUN echo "# Nagios Services:" >> /etc/services
#RUN echo "nrpe    5666/tcp" >> /etc/services
#RUN echo "nsca    5667/tcp" >> /etc/services
#
#COPY configs/nagiosxi.sudoers /etc/sudoers.d/nagiosxi

#ENTRYPOINT ["top", "-b"]