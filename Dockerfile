FROM ubuntu:22.04
MAINTAINER "Peter Nearing"
# Define build-time arguments
ARG MYSQL_HOST
ARG MYSQL_PORT
ARG MYSQL_ROOT_PASS

# Set runtime environment variables
ENV MYSQL_HOST=${MYSQL_HOST}
ENV MYSQL_PORT=${MYSQL_PORT}
ENV MYSQL_ROOT_PASS=${MYSQL_ROOT_PASS}

ENV INTERACTIVE=False
ENV DEBIAN_FRONTEND=noninteractive
ENV GLOBAL_MYSQL_HOST=${MYSQL_HOST}
ENV DOCKER_INSTALLER=True

# Update and upgrade image:
RUN apt-get update && apt-get upgrade -y && \
    apt-get install -y lsb-release

# Ensure correct working directory for installation
WORKDIR /tmp/

# Copy Nagios XI source files
COPY nagios_xi_src/ ./

WORKDIR /tmp/nagiosxi

# Run install at build time
RUN ./init.sh
RUN ./xivar mysqlpass "${MYSQL_ROOT_PASS}" &&\
    ./xivar nagiosxipass "$(tr -dc '[:alnum:]' < /dev/urandom | head -c 20)" &&\
    ./xivar dbmaintpass "$(tr -dc '[:alnum:]' < /dev/urandom | head -c 20)" &&\
    ./xivar nagiosqlpass "$(tr -dc '[:alnum:]' < /dev/urandom | head -c 20)" &&\
    ./xivar ndoutilspass "$(tr -dc '[:alnum:]' < /dev/urandom | head -c 20)"

# RUN ./00-repos
# Nothing but lsb-release is installed and ./init.sh rerun, so we install lsb-release previously, and ran init.sh
RUN touch installed.repos

RUN ./01-prereqs &&\
    apt-get clean
# Clean up downloaded packages
# Configure MySQL Client to use remote host by default (without storing password)
RUN mkdir -p /etc/mysql/conf.d && \
    echo "[client]\nhost=${MYSQL_HOST}\nport=${MYSQL_PORT}" > /etc/mysql/conf.d/custom.cnf

RUN ./02-usersgroups
# RUN ./03-dbservers
# We are preforming the basics of #03 here: why run the whole script if we don't have to.
#  Edit nagiosxi/automysqlbackup for MYSQL_HOST, MYSQL_PORT, MYSQL_PASS
RUN sed -i -e "s/PASSWORD=.*/PASSWORD=${MYSQL_ROOT_PASS}/g" nagiosxi/automysqlbackup &&\
    sed -i 's/^DBHOST=localhost/DBHOST=${MYSQL_HOST}/' nagiosxi/automysqlbackup &&\
    sed -i 's/^DBPORT=localhost/DBHOST=${MYSQL_PORT}/' nagiosxi/automysqlbackup &&\
    touch installed.dbservers &&\
    touch installed.mysql
RUN ./04-services
RUN ./05-sudoers
RUN ./06-firewall
RUN ./07-selinux
RUN ./08-dbbackups
RUN ./09-sourceguardian
RUN ./10-phpini

RUN ./11-subcomponents &&\
    apt-get clean

RUN ./12-mrtg

RUN ./13-installxi

RUN ./14-cronjobs
#RUN ./15-chkconfigalldaemons
RUN touch installed.chkconfig

RUN ./16-importnagiosql
#RUN ./17-startdaemons
RUN ./18-webroot



# Expose necessary ports and volumes:
EXPOSE 80 5666
VOLUME /nagios

# Copy entrypoint script
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Use entrypoint script to keep the container running
ENTRYPOINT ["/entrypoint.sh"]
