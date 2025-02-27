#!/usr/bin/env bash

### Slack Notifications Wizard Plugin
# inspired by https://exchange.nagios.org/directory/Plugins/Notifications/Notification-for-Slack by obaarnes
# Copyright (c) 2023 Nagios Enterprises, LLC. All rights reserved.

# Inputs
# --------------------------------------------------

#
# Host command defn:
#	command: slack_curl.sh webhook-url "$NOTIFICATIONTYPE$" "$HOSTNAME$" "$HOSTADDRESS$" "$HOSTSTATE$" "$HOSTOUTPUT$" "$LONGDATETIME$"
# Service command defn:
#	command: slack_curl.sh webhook-url "$NOTIFICATIONTYPE$" "$HOSTNAME$" "$HOSTADDRESS$" "$SERVICESTATE$" "$SERVICEOUTPUT$" "$LONGDATETIME$" "$SERVICEDESC$"
#

# Output
# --------------------------------------------------

#
# Script executes the following:
#   curl -H "Content-Type: application/json" -d '{"username":"test","content":"hello"}' "https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX"
#

# Script
# --------------------------------------------------

# Slack Colors
aubergine=4A154B
horchata=F4EDE4
black=1D1C1D
white=FFFFFF
slack_blue=36C5F0
slack_green=2EB67D
slack_yellow=ECB22E
slack_red=E01E5A


webhook_url=$1 # webhook url consists of https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX
notification_type=$2
hostname=$3
hostaddress=$4
state=$5
output=$6
datetime=$7
servicedesc=$8

case $state in # takes in host state "UP" or "DOWN" and set message color accordingly

"DOWN")
    color=$slack_red
    desc=" :scream:"
    ;;
"UP")
    color=$slack_green
    desc=" :smile:"
    ;;
"CRITICAL")
    color=$slack_red
    desc=" :scream:"
    return=2
    ;;
"WARNING")
    color=$slack_yellow
    desc=" :sweat:"
    return=1
    ;;
"OK")
    color=$slack_green
    desc=" :smile:"
    return=0
    ;;
"UNREACHABLE")
    color=$slack_yellow
    desc=" :sweat:"
    return=1
    ;;
*)
    color=$aubergine
    desc=" UNKNOWN"
    return=3
    ;;
esac

echo "color: $color" > /tmp/slack.log
echo "desc: $desc" >> /tmp/slack.log
echo "notification_type: $notification_type" >> /tmp/slack.log
echo "hostname: $hostname" >> /tmp/slack.log
echo "hostaddress: $hostaddress" >> /tmp/slack.log
echo "state: $state" >> /tmp/slack.log
echo "output: $output" >> /tmp/slack.log
echo "datetime: $datetime" >> /tmp/slack.log
echo "servicedesc: $servicedesc" >> /tmp/slack.log


# -- old method, keeping in case we can't use jq
# slack_message=$(printf '{
#     "attachments": [
#         {
#             "color": "%s",
#             "title": "Host \"%s\" notification",
#             "text": "Host:        %s\nIP:             %s\nState:        %s%s"
#         },
#         {
#             "color": "%s",
#             "title": "Details:",
#             "text": "\n%s",
#             "footer": "Nagios notification: %s"
#         }
#     ]
# }' "$color" "$hostname" "$hostname" "$hostaddress" "$state" "$desc" "$color" "$output" "$datetime")

if [ "$notification_type" = "service" ]; then # if service notification
    hostname="$hostname - $servicedesc";
    slack_message=$(jq -n \
    --arg color "$color" \
    --arg hostname "$hostname" \
    --arg hostaddress "$hostaddress" \
    --arg state "$state" \
    --arg desc "$desc" \
    --arg output "$output" \
    --arg datetime "$datetime" \
    --arg servicedesc "$servicedesc" \
    '{
        attachments: [
            {
                color: $color,
                title: ("Service notification"),
                text: ( "Service:     \($hostname)\n" +
                        "IP:              \($hostaddress)\n" +
                        "State:         \($state)")
            },
            {
                color: $color,
                title: "Details:",
                text: "\($output)",
                footer: ("Nagios notification: \($datetime)")
            }
        ]
    }')
else # if host notification

    slack_message=$(jq -n \
    --arg color "$color" \
    --arg hostname "$hostname" \
    --arg hostaddress "$hostaddress" \
    --arg state "$state" \
    --arg desc "$desc" \
    --arg output "$output" \
    --arg datetime "$datetime" \
    '{
        attachments: [
            {
                color: $color,
                title: ("Host notification"),
                text: ("Host:         \($hostname)\n" +
                       "IP:              \($hostaddress)\n" +
                       "State:         \($state)")
            },
            {
                color: $color,
                title: "Details:",
                text: "\n\($output)",
                footer: ("Nagios notification: \($datetime)")
            }
        ]
    }')
fi

echo $(curl -4 -X POST --data-urlencode "payload=$slack_message" "$webhook_url")
