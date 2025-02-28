UPDATE nagios_objects set name1 = '' WHERE name1 IS NULL;
UPDATE nagios_objects set name2 = '' WHERE name2 IS NULL;

ALTER TABLE nagios_objects MODIFY `name1` varchar(1023) NOT NULL default '' COLLATE utf8_bin;
ALTER TABLE nagios_objects MODIFY `name2` varchar(1023) NOT NULL default '' COLLATE utf8_bin;

ALTER TABLE `nagios_servicestatus` MODIFY `check_command` varchar(2048) NOT NULL DEFAULT '';
ALTER TABLE `nagios_hoststatus` MODIFY `check_command` varchar(2048) NOT NULL DEFAULT '';

set @index_exists := (SELECT INDEX_NAME FROM `information_schema`.`STATISTICS` WHERE TABLE_NAME = 'nagios_contact_notificationcommands' AND INDEX_NAME = 'contact_id' LIMIT 1);
set @sqlstmt := if( @index_exists = '',
'select ''INFO: index did not exist''',
'ALTER TABLE `nagios_contact_notificationcommands` DROP INDEX `contact_id`');
prepare stmt from @sqlstmt;
execute stmt;

CREATE UNIQUE INDEX `contact_id` on nagios_contact_notificationcommands(`contact_id`,`notification_type`,`command_object_id`);

ALTER TABLE nagios_contactnotificationmethods MODIFY `command_args` varchar(2048) NOT NULL default '';
ALTER TABLE nagios_contact_notificationcommands MODIFY `command_args` varchar(2048) NOT NULL default '';
ALTER TABLE nagios_eventhandlers MODIFY `command_args` varchar(2048) NOT NULL default '';
ALTER TABLE nagios_externalcommands MODIFY `command_args` varchar(2048) NOT NULL default '';
ALTER TABLE nagios_hostchecks MODIFY `command_args` varchar(2048) NOT NULL default '';
ALTER TABLE nagios_hosts MODIFY `check_command_args` varchar(2048) NOT NULL default '';
ALTER TABLE nagios_hosts MODIFY `eventhandler_command_args` varchar(2048) NOT NULL default '';
ALTER TABLE nagios_servicechecks MODIFY `command_args` varchar(2048) NOT NULL default '';
ALTER TABLE nagios_services MODIFY `check_command_args` varchar(2048) NOT NULL default '';
ALTER TABLE nagios_services MODIFY `eventhandler_command_args` varchar(2048) NOT NULL default '';

ALTER TABLE nagios_contactgroups MODIFY `alias` varchar(2048) NOT NULL DEFAULT '';
ALTER TABLE nagios_contacts MODIFY `alias` varchar(2048) NOT NULL DEFAULT '';
ALTER TABLE nagios_hostgroups MODIFY `alias` varchar(2048) NOT NULL DEFAULT '';
ALTER TABLE nagios_hosts MODIFY `alias` varchar(2048) NOT NULL DEFAULT '';
ALTER TABLE nagios_servicegroups MODIFY `alias` varchar(2048) NOT NULL DEFAULT '';
ALTER TABLE nagios_timeperiods MODIFY `alias` varchar(2048) NOT NULL DEFAULT '';

ALTER TABLE nagios_acknowledgements MODIFY `author_name` varchar(1024) NOT NULL DEFAULT '';
ALTER TABLE nagios_commenthistory MODIFY `author_name` varchar(1024) NOT NULL DEFAULT '';
ALTER TABLE nagios_comments MODIFY `author_name` varchar(1024) NOT NULL DEFAULT '';
ALTER TABLE nagios_downtimehistory MODIFY `author_name` varchar(1024) NOT NULL DEFAULT '';
ALTER TABLE nagios_scheduleddowntime MODIFY `author_name` varchar(1024) NOT NULL DEFAULT '';

ALTER TABLE nagios_acknowledgements MODIFY `comment_data` varchar(4096) NOT NULL DEFAULT '';
ALTER TABLE nagios_commenthistory MODIFY `comment_data` varchar(4096) NOT NULL DEFAULT '';
ALTER TABLE nagios_comments MODIFY `comment_data` varchar(4096) NOT NULL DEFAULT '';
ALTER TABLE nagios_downtimehistory MODIFY `comment_data` varchar(4096) NOT NULL DEFAULT '';
ALTER TABLE nagios_scheduleddowntime MODIFY `comment_data` varchar(4096) NOT NULL DEFAULT '';

ALTER TABLE nagios_hosts MODIFY `display_name` varchar(2048) NOT NULL DEFAULT '';
ALTER TABLE nagios_services MODIFY `display_name` varchar(2048) NOT NULL DEFAULT '';

