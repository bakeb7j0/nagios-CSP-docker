FROM ubuntu:22.04

# Define build-time arguments
#ARG MYSQL_HOST
#ARG MYSQL_PORT
#ARG MYSQL_PASS
#ARG NDOUTILS_PASS

# Set runtime environment variables
#ENV MYSQL_HOST=${MYSQL_HOST}
#ENV MYSQL_PORT=${MYSQL_PORT}
#ENV MYSQL_PASS=${MYSQL_PASS}
#ENV NDOUTILS_PASS=${NDOUTILS_PASS}

ENV MYSQL_HOST=coolify0.pnearing.ca
ENV MYSQL_PORT=3306
ENV MYSQL_PASS=n0Nx09MiTSM5UWVAFgd2OvtyfQGRbh1yZVb43NABzclbqCGymyiQvV2fZ5gmegcI

ENV INTERACTIVE=False
ENV DEBIAN_FRONTEND=noninteractive

# Update and upgrade image:
RUN apt-get update && apt-get upgrade -y && \
    apt-get install -y lsb-release

# Ensure correct working directory for installation
WORKDIR /tmp/

# Copy Nagios XI source files
COPY nagios_xi_src/ ./

WORKDIR /tmp/nagiosxi

# Ensure fullinstall is executable
RUN chmod +x fullinstall

# Run fullinstall at build time
RUN ./init.sh
RUN ./xivar mysqlpass "${MYSQL_PASS}" &&\
    ./xivar nagiosxipass "$(tr -dc '[:alnum:]' < /dev/urandom | head -c 20)" &&\
    ./xivar dbmaintpass "$(tr -dc '[:alnum:]' < /dev/urandom | head -c 20)" &&\
    ./xivar nagiosqlpass "$(tr -dc '[:alnum:]' < /dev/urandom | head -c 20)" &&\
    ./xivar ndoutilspass "$(tr -dc '[:alnum:]' < /dev/urandom | head -c 20)"
#    ./xivar ndoutilspass ${NDOUTILS_PASS}

# RUN ./00-repos
# Nothing but lsb-release is installed and ./init.sh rerun, so we install lsb-release previously, and ran init.sh
RUN touch installed.repos

RUN ./01-prereqs
# Clean up downloaded packages
RUN apt-get clean
# Configure MySQL Client to use remote host by default (without storing password)
RUN mkdir -p /etc/mysql/conf.d && \
    echo "[client]\nhost=${MYSQL_HOST}\nport=${MYSQL_PORT}" > /etc/mysql/conf.d/custom.cnf

RUN ./02-usersgroups
# RUN ./03-dbservers
# We are preforming the basics of #03 here: why run the whole script if we don't have to.
#  Edit nagiosxi/automysqlbackup for MYSQL_HOST, MYSQL_PORT, MYSQL_PASS
RUN sed -i -e "s/PASSWORD=.*/PASSWORD=${MYSQL_PASS}/g" nagiosxi/automysqlbackup &&\
    sed -i 's/^DBHOST=localhost/DBHOST=${MYSQL_HOST}/' nagiosxi/automysqlbackup &&\
    sed -i 's/^DBPORT=localhost/DBHOST=${MYSQL_PORT}/' nagiosxi/automysqlbackup &&\
    touch installed.dbservers
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



# Expose necessary ports and volumes:
EXPOSE 80 5666
VOLUME /nagios

# Copy entrypoint script
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Use entrypoint script to keep the container running
ENTRYPOINT ["top", "-b"]
