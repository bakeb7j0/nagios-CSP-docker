#!/bin/bash

service nagios start
service ncpd start
service cron start
service nrpe start
service ncsa start
loginctl enable-linger nagios
echo "Starting Apache..."
apachectl -D FOREGROUND
