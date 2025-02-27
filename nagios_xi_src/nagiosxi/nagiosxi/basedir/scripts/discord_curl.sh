#!/bin/bash

#
# Discord Notifications Wizard Plugin
# Copyright (c) 2023 Nagios Enterprises, LLC. All rights reserved.
#

# Inputs
# --------------------------------------------------

#
# Host command defn:
#	command: discord_curl.sh webhook-url "$NOTIFICATIONTYPE$" "$HOSTNAME$" "$HOSTADDRESS$" "$HOSTSTATE$" "$HOSTOUTPUT$" "$LONGDATETIME$"
# Service command defn:
#	command: discord_curl.sh webhook-url "$NOTIFICATIONTYPE$" "$HOSTNAME$" "$HOSTADDRESS$" "$SERVICESTATE$" "$SERVICEOUTPUT$" "$LONGDATETIME$" "$SERVICEDESC$"
#

# Output
# --------------------------------------------------

#
# Script executes the following:
#   curl -H "Content-Type: application/json" -d '{"username":"test","content":"hello"}' "https://discordapp.com/api/webhooks/1026960031360491651/SIPz-NzlX83q7IvkyArLqAwQr9DYbG-obrCCGPKi__q28oqwdWNYYMdYdKADiE75cCEu"
#

# Script
# --------------------------------------------------

# Discord Webhooks takes the decimal index instead of hex/etc
# Discord Colors:
blurple=5793266
green=5763719
yellow=16705372
fuchsia=15418782
red=15548997
white=16777215
black=0

webhook_url=$1 # webhook url consists of https://discordapp.com/api/webhooks/ id / token
notification_type=$2
hostname=$3
hostaddress=$4
state=$5
output=$6
datetime=$7
servicedesc=$8

case $state in # takes in $state$ and sets message color accordingly
    "CRITICAL")
    color=$red
    emoji=":scream:"
    return=2
    ;;
    "WARNING")
    color=$yellow
    emoji=":sweat:"
    return=1
    ;;
    "OK")
    color=$green
    emoji=":slight_smile:"
    return=0
    ;;
    "DOWN")
    color=$red
    emoji=":scream:"
    return=2
    ;;
    "UP")
    color=$green
    emoji=":slight_smile:"
    return=0
    ;;
    "UNCREACHABLE")
    color=$fuchsia
    emoji=":sweat:"
    return=1
    ;;
    *)
    color=$fuchsia
    state="UNKNOWN"
    emoji=":sweat:"
    return=3
    ;;
esac

nodename=$hostname;
discord_json="";
state="$emoji $state";

if [ "$notification_type" = "service" ]; then # if service notification
    nodename=$hostname" - "$servicedesc;
    discord_json=$(jq -n \
    --arg nodetype $notification_type \
    --arg username "Nagios XI" \
    --arg title "Service: $nodename" \
    --arg description "$emoji" \
    --arg color "$color" \
    --arg hostaddress "$hostaddress" \
    --arg state "$state" \
    --arg output "$output" \
    --arg datetime "$datetime" \
    --arg servicedesc "$servicedesc" \
    '{
        username: $username,
        embeds: [{
        title: $title,
        color: $color,
        fields: [
            {name: "Host Address:", value: $hostaddress, inline: true},
            {name: "Service State:", value: $state, inline: true},
            {name: "Service Details:", value: $output}
        ],
        footer: {text: $datetime}
        }]
    }')
else # is Host
    discord_json=$(jq -n \
    --arg nodetype $notification_type \
    --arg username "Nagios XI" \
    --arg title "Host: $nodename" \
    --arg color "$color" \
    --arg hostaddress "$hostaddress" \
    --arg state "$state" \
    --arg output "$output" \
    --arg datetime "$datetime" \
    '{
        username: $username,
        embeds: [{
        title: $title,
        color: $color,
        fields: [
            {name: "Host Address:", value: $hostaddress, inline: true},
            {name: "Host State:", value: $state, inline: true},
            {name: "Host Details:", value: $output}
        ],
        footer: {text: $datetime}
        }]
    }')
fi

#Send message to Discord
curl -g -X POST -H "Content-Type: application/json" -d "$discord_json" "$webhook_url"