#!/bin/bash

service nagios start
service ncpd start
service ntpsec start
service cron start

echo "Starting Apache..."
apachectl -D FOREGROUND
