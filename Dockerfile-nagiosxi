 FROM ubuntu:22.04

# Define build-time arguments
ARG MYSQL_HOST
ARG MYSQL_PORT
ARG MYSQL_PASS
# Set runtime environment variables
ENV DEBIAN_FRONTEND=noninteractive
ENV MYSQL_HOST=${MYSQL_HOST}
ENV MYSQL_PORT=${MYSQL_PORT}
ENV MYSQL_PASS=${MYSQL_PASS}

# Update and install necessary packages
RUN apt-get update && apt-get upgrade -y && \
    apt-get install -y mysql-client && \
    apt-get clean

# Configure MySQL Client to use remote host by default (without storing password)
RUN mkdir -p /etc/mysql/conf.d && \
    echo "[client]\nhost=${MYSQL_HOST}\nport=${MYSQL_PORT}" > /etc/mysql/conf.d/custom.cnf

# Ensure correct working directory for installation
WORKDIR /tmp/

# Copy Nagios XI source files
COPY nagios_xi_src/ ./

# Fix ndo subcomponent:
# Set db_host, db_port to database host and port:
RUN sed -i 's/^db_host=.*/db_host=${MYSQL_HOST}/' nagiosxi/subcomponents/ndo/mods/cfg/ndo.cfg &&\
    sed -i 's/^db_port=.*/db_port=${MYSQL_PORT}/' nagiosxi/subcomponents/ndo/mods/cfg/ndo.cfg


WORKDIR /tmp/nagiosxi

# Ensure fullinstall is executable
RUN chmod +x fullinstall

# Run fullinstall at build time
#RUN ./fullinstall -p ${MYSQL_PASS} -n
RUN ./init.sh
RUN ./xivar mysqlpass "${MYSQL_PASS}" &&\
    ./xivar nagiosxipass "$(tr -dc '[:alnum:]' < /dev/urandom | head -c 20)" &&\
    ./xivar dbmaintpass "$(tr -dc '[:alnum:]' < /dev/urandom | head -c 20)" &&\
    ./xivar nagiosqlpass "$(tr -dc '[:alnum:]' < /dev/urandom | head -c 20)" &&\
    ./xivar ndoutilspass "$(tr -dc '[:alnum:]' < /dev/urandom | head -c 20)"

RUN ./00-repos
RUN ./01-prereqs
RUN ./02-usersgroups
RUN ./03-dbservers
RUN ./04-services
RUN ./05-sudoers
RUN ./06-firewall
RUN ./07-selinux
RUN ./08-dbbackups
RUN ./09-sourceguardian
RUN ./10-phpini
RUN ./11-subcomponents
RUN ./12-mrtg
RUN ./13-installxi
RUN ./14-cronjobs
RUN ./15-chkconfigalldaemons
RUN ./16-importnagiosql
#RUN ./17-startdaemons
RUN ./18-webroot
# Expose necessary ports


EXPOSE 80 5666

# Copy entrypoint script
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Use entrypoint script to keep the container running
ENTRYPOINT ["top", "-b"]
