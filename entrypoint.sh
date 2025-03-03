#!/bin/bash
for svc in cron postfix nagios ncpd nrpe ncsa; do
  echo "Enabling service: $svc"
  systemctl enable "$svc"
done

for svc in cron postfix nagios ncpd nrpe ncsa; do
  echo "Staring service: $svc"
  systemctl start "$svc"
done

loginctl enable-linger nagios
echo "Starting Apache..."
apachectl -D FOREGROUND
