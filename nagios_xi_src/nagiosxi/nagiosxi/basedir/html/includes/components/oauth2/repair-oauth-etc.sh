#!/bin/bash

OA2_DIR="/usr/local/nagiosxi/etc/components/oauth2"

# make folder
# mkdir -p "$OA2_DIR/providers"

# folder permissions
CFG_FILE=$(cat /usr/local/nagiosxi/var/xi-sys.cfg)
apacheuser=$(echo "$CFG_FILE" | grep -oP "apacheuser='\K[^']*")
nagiosgroup=$(echo "$CFG_FILE" | grep -oP "nagiosgroup='\K[^']*")
chown -R $apacheuser:$nagiosgroup $OA2_DIR