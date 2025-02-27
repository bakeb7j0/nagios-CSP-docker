#!/bin/bash -e

#
# Converts Nagios Core contact and template mail commands to Nagios XI mail commands
# Copyright (c) 2024 Nagios Enterprises, LLC. All rights reserved.
#

BASEDIR=$(dirname $(readlink -f $0))

# Import Nagios XI and xi-sys.cfg config vars
. $BASEDIR/../var/xi-sys.cfg
eval $(php $BASEDIR/import_xiconfig.php)


####################
# Add the Nagios XI mail commands to the database if they don't exist
####################

notify_commands=$(php -r 'require_once("/usr/local/nagiosxi/html/includes/common.inc.php"); 
							check_prereqs();  
							echo nagiosql_get_command_id("notify-host-by-email"); 
							echo "\n"; 
							echo nagiosql_get_command_id("notify-service-by-email"); 
							echo "\n"; 
							echo nagiosql_get_command_id("notify-host-by-email-xi"); 
							echo "\n"; 
							echo nagiosql_get_command_id("notify-service-by-email-xi");');
IFS=$'\n' notify_commands=($notify_commands)

notify_host_by_email=${notify_commands[0]}
notify_service_by_email=${notify_commands[1]}
notify_host_by_email_xi=${notify_commands[2]}
notify_service_by_email_xi=${notify_commands[3]}

if ([ -z "$notify_host_by_email_xi" ] || [ "$notify_host_by_email_xi" -eq 0 ]) || ([ -z "$notify_service_by_email_xi" ] || [ "$notify_service_by_email_xi" -eq 0 ]); then
	add_command() {
		local command=$1
		local command_line=$2

		if [ ! -f /usr/local/nagios/etc/import/ximail.cfg ]; then
			echo "Creating ximail.cfg in /usr/local/nagios/etc/objects."
			mkdir -p /usr/local/nagios/etc/import
			echo "" >> /usr/local/nagios/etc/import/ximail.cfg
		fi

		# if not file ximail.cfg in /usr/local/nagios/etc/import, create and populate it
		if ! grep -q "$command" "/usr/local/nagios/etc/import/ximail.cfg" ; then
			echo "Creating ximail.cfg in /usr/local/nagios/etc/import."
			echo "" >> /usr/local/nagios/etc/import/ximail.cfg
			echo "define command {" >> /usr/local/nagios/etc/import/ximail.cfg
			echo "	command_name	$command" >> /usr/local/nagios/etc/import/ximail.cfg
			echo "	command_line	$command_line" >> /usr/local/nagios/etc/import/ximail.cfg
			echo "}" >> /usr/local/nagios/etc/import/ximail.cfg
		fi
	}

	add_command "notify-host-by-email-xi" '/usr/local/nagiosxi/scripts/contact_notification_handler.php --contactemail="$CONTACTEMAIL$" --subject="** $NOTIFICATIONTYPE$ Host Alert: $HOSTNAME$ is $HOSTSTATE$ **" --message="***** Nagios Monitor XI Alert *****\n\nNotification Type: $NOTIFICATIONTYPE$\nHost: $HOSTNAME$\nState: $HOSTSTATE$\nAddress: $HOSTADDRESS$\nInfo: $HOSTOUTPUT$\n\nDate/Time: $LONGDATETIME$\n"'
	
	notify_host_by_email_xi=$(mysql -h "$cfg__db_info__nagiosql__dbserver" -u "$cfg__db_info__nagiosql__user" --password="$cfg__db_info__nagiosql__pwd" --database="$cfg__db_info__nagiosql__db" --execute="
		SELECT id FROM tbl_command WHERE command_name='notify-host-by-email-xi';" | tail -n +2 | tr -d '[:space:]')

	add_command "notify-service-by-email-xi" '/usr/local/nagiosxi/scripts/contact_notification_handler.php --contactemail="$CONTACTEMAIL$" --subject="** $NOTIFICATIONTYPE$ Service Alert: $HOSTALIAS$/$SERVICEDESC$ is $SERVICESTATE$ **" --message="***** Nagios Monitor XI Alert *****\n\nNotification Type: $NOTIFICATIONTYPE$\n\nService: $SERVICEDESC$\nHost: $HOSTALIAS$\nAddress: $HOSTADDRESS$\nState: $SERVICESTATE$\n\nDate/Time: $LONGDATETIME$\n\nAdditional Info:\n\n$SERVICEOUTPUT$"'

	notify_service_by_email_xi=$(mysql -h "$cfg__db_info__nagiosql__dbserver" -u "$cfg__db_info__nagiosql__user" --password="$cfg__db_info__nagiosql__pwd" --database="$cfg__db_info__nagiosql__db" --execute="
		SELECT id FROM tbl_command WHERE command_name='notify-service-by-email-xi';" | tail -n +2 | tr -d '[:space:]')

	(
		cd $proddir/scripts
		./reconfigure_nagios.sh
	)
fi


####################
# RETRIES: 5
# Check if the Nagios Core mail commands are in the database
####################

notify_host_by_email=$(mysql -h "$cfg__db_info__nagiosql__dbserver" -u "$cfg__db_info__nagiosql__user" --password="$cfg__db_info__nagiosql__pwd" --database="$cfg__db_info__nagiosql__db" --execute="
	SELECT id FROM tbl_command WHERE command_name='notify-host-by-email';" | tail -n +2 | tr -d '[:space:]')
notify_service_by_email=$(mysql -h "$cfg__db_info__nagiosql__dbserver" -u "$cfg__db_info__nagiosql__user" --password="$cfg__db_info__nagiosql__pwd" --database="$cfg__db_info__nagiosql__db" --execute="
	SELECT id FROM tbl_command WHERE command_name='notify-service-by-email';" | tail -n +2 | tr -d '[:space:]')
notify_host_by_email_xi=$(mysql -h "$cfg__db_info__nagiosql__dbserver" -u "$cfg__db_info__nagiosql__user" --password="$cfg__db_info__nagiosql__pwd" --database="$cfg__db_info__nagiosql__db" --execute="
	SELECT id FROM tbl_command WHERE command_name='notify-host-by-email-xi';" | tail -n +2 | tr -d '[:space:]')
notify_service_by_email_xi=$(mysql -h "$cfg__db_info__nagiosql__dbserver" -u "$cfg__db_info__nagiosql__user" --password="$cfg__db_info__nagiosql__pwd" --database="$cfg__db_info__nagiosql__db" --execute="
	SELECT id FROM tbl_command WHERE command_name='notify-service-by-email-xi';" | tail -n +2 | tr -d '[:space:]')

RETRIES=5
# while the commands are not in the database, retry 5 times
while ([ -z "$notify_host_by_email" ] || [ "$notify_host_by_email" -eq 0 ]) || ([ -z "$notify_service_by_email" ] || [ "$notify_service_by_email" -eq 0 ]) || ([ -z "$notify_host_by_email_xi" ] || [ "$notify_host_by_email_xi" -eq 0 ]) || ([ -z "$notify_service_by_email_xi" ] || [ "$notify_service_by_email_xi" -eq 0 ]); do
	if [ $RETRIES -eq 0 ]; then
		echo "Nagios XI mail commands failed to be added to the database. If you would still like to update your contacts, please run the convert_core_contacts.sh script manually or update using the Nagios XI page Admin->System Config->Email Settings."
		exit 1
	fi

	echo "The Nagios Core mail commands are not in the database. Retrying in 5 seconds..."
	sleep 5

	notify_host_by_email=$(mysql -h "$cfg__db_info__nagiosql__dbserver" -u "$cfg__db_info__nagiosql__user" --password="$cfg__db_info__nagiosql__pwd" --database="$cfg__db_info__nagiosql__db" --execute="
		SELECT id FROM tbl_command WHERE command_name='notify-host-by-email';" | tail -n +2 | tr -d '[:space:]')
	notify_service_by_email=$(mysql -h "$cfg__db_info__nagiosql__dbserver" -u "$cfg__db_info__nagiosql__user" --password="$cfg__db_info__nagiosql__pwd" --database="$cfg__db_info__nagiosql__db" --execute="
		SELECT id FROM tbl_command WHERE command_name='notify-service-by-email';" | tail -n +2 | tr -d '[:space:]')
	notify_host_by_email_xi=$(mysql -h "$cfg__db_info__nagiosql__dbserver" -u "$cfg__db_info__nagiosql__user" --password="$cfg__db_info__nagiosql__pwd" --database="$cfg__db_info__nagiosql__db" --execute="
		SELECT id FROM tbl_command WHERE command_name='notify-host-by-email-xi';" | tail -n +2 | tr -d '[:space:]')
	notify_service_by_email_xi=$(mysql -h "$cfg__db_info__nagiosql__dbserver" -u "$cfg__db_info__nagiosql__user" --password="$cfg__db_info__nagiosql__pwd" --database="$cfg__db_info__nagiosql__db" --execute="
		SELECT id FROM tbl_command WHERE command_name='notify-service-by-email-xi';" | tail -n +2 | tr -d '[:space:]')

	RETRIES=$((RETRIES-1))
done


####################
# Count the number of contacts and templates using the Nagios Core mail commands
####################

count_from_database() {
	local table=$1
	local cmd_id=$2

	mysql -h "$cfg__db_info__nagiosql__dbserver" -u "$cfg__db_info__nagiosql__user" --password="$cfg__db_info__nagiosql__pwd" --database="$cfg__db_info__nagiosql__db" --execute="
		SELECT COUNT(*) FROM $table WHERE idSlave='$cmd_id';" | tail -n +2 | tr -d '[:space:]'
}

notify_host_count_contact=$(	count_from_database "tbl_lnkContactToCommandHost" 				$notify_host_by_email)
notify_service_count_contact=$(	count_from_database "tbl_lnkContactToCommandService" 			$notify_service_by_email)
notify_host_count_template=$(	count_from_database "tbl_lnkContacttemplateToCommandHost" 		$notify_host_by_email)
notify_service_count_template=$(count_from_database "tbl_lnkContacttemplateToCommandService" 	$notify_service_by_email)

if [ "$notify_host_count_contact" -eq 0 ] && [ "$notify_service_count_contact" -eq 0 ] && [ "$notify_host_count_template" -eq 0 ] && [ "$notify_service_count_template" -eq 0 ]; then
	exit 0
fi


####################
# Update the database if the counts are greater than 0 and the user wants to update
####################

update_database() {
	local table=$1
	local old_cmd_id=$2
	local new_cmd_id=$3

	mysql -h "$cfg__db_info__nagiosql__dbserver" -u "$cfg__db_info__nagiosql__user" --password="$cfg__db_info__nagiosql__pwd" --database="$cfg__db_info__nagiosql__db" --force --execute="
		UPDATE $table SET idSlave='$new_cmd_id' WHERE idSlave='$old_cmd_id';"
}


# check with the user if they want to update the database
echo ""
echo "You have $notify_host_count_contact host and $notify_service_count_contact service commands in your contacts that are using the Nagios Core mail commands."
echo "You have $notify_host_count_template host and $notify_service_count_template service commands in your contact templates that are using the Nagios Core mail commands."
echo ""
echo "Would you like to update the database to use Nagios XI mail commands for these contacts instead? ([y]/n)"

read -r update_db

if [ -z "$update_db" ]; then
	echo "Empty input received. Updating the database."
	update_db="y"
fi

if [ "$update_db" != "y" ]; then
	echo "Exiting without updating the database. You can still update Nagios Core mail contacts to use your Nagios XI mail commands in the Admin->System Config->Email Settings page or running the update-core-contacts script located with this upgrade script."
	exit 0
fi

update_database "tbl_lnkContactToCommandHost" 				$notify_host_by_email 		$notify_host_by_email_xi
update_database "tbl_lnkContactToCommandService" 			$notify_service_by_email 	$notify_service_by_email_xi

update_database "tbl_lnkContacttemplateToCommandHost" 		$notify_host_by_email 		$notify_host_by_email_xi
update_database "tbl_lnkContacttemplateToCommandService" 	$notify_service_by_email 	$notify_service_by_email_xi


####################
# Reconfigure Nagios
####################

(
	cd "$proddir/scripts"
	./reconfigure_nagios.sh
)
