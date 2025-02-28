set @index_exists := (SELECT COLUMN_KEY FROM `information_schema`.`COLUMNS` WHERE TABLE_NAME = 'nagios_logentries' AND COLUMN_NAME = 'logentry_data' LIMIT 1);

set @sqlstmt := if( @index_exists = '',
'select ''INFO: index did not exist''',
'ALTER TABLE `nagios_logentries` DROP INDEX `logentry_data`');
prepare stmt from @sqlstmt;
execute stmt;

ALTER TABLE nagios_logentries MODIFY `logentry_data` mediumtext NOT NULL;
CREATE INDEX `logentry_data` on nagios_logentries(`logentry_data`(255));


ALTER TABLE nagios_services MODIFY `check_command_args` varchar(1024) NOT NULL DEFAULT '';
ALTER TABLE nagios_hosts MODIFY `check_command_args` varchar(1024) NOT NULL DEFAULT '';