
# Add the column 'check_options' to nagios_servicestatus if it doesn't already exist
SET @exist := (SELECT COUNT(*) FROM `information_schema`.`columns` WHERE table_name = 'nagios_servicestatus' AND column_name = 'check_options');
SET @sqlstmt := if( @exist <= 0,
'ALTER TABLE `nagios_servicestatus` ADD `check_options` smallint(6) NOT NULL default ''0'' AFTER `check_type`',
'select ''INFO: Column already exists.''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

# Add the column 'check_options' to nagios_hoststatus if it doesn't already exist
SET @exist := (SELECT COUNT(*) FROM `information_schema`.`columns` WHERE table_name = 'nagios_hoststatus' AND column_name = 'check_options');
SET @sqlstmt := if (@exist <= 0, 
'ALTER TABLE `nagios_hoststatus` ADD `check_options` smallint(6) NOT NULL default ''0'' AFTER `check_type`',
'SELECT ''INFO: Column already exists.''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;