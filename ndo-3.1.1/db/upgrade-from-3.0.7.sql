ALTER TABLE nagios_downtimehistory MODIFY duration int(11) NOT NULL;

ALTER TABLE nagios_contacts ADD COLUMN `retain_status_information` smallint(6) NOT NULL default '0';
ALTER TABLE nagios_contacts ADD COLUMN `retain_nonstatus_information` smallint(6) NOT NULL default '0';

ALTER TABLE nagios_hostgroups ADD COLUMN `notes` varchar(255) NOT NULL default '';
ALTER TABLE nagios_hostgroups ADD COLUMN `notes_url` varchar(255) NOT NULL default '';
ALTER TABLE nagios_hostgroups ADD COLUMN `action_url` varchar(255) NOT NULL default '';

ALTER TABLE nagios_servicegroups ADD COLUMN `notes` varchar(255) NOT NULL default '';
ALTER TABLE nagios_servicegroups ADD COLUMN `notes_url` varchar(255) NOT NULL default '';
ALTER TABLE nagios_servicegroups ADD COLUMN `action_url` varchar(255) NOT NULL default '';

TRUNCATE TABLE nagios_hosts;
ALTER TABLE nagios_hosts ADD COLUMN `should_be_drawn` smallint(6) NOT NULL default '0';
ALTER TABLE nagios_hosts MODIFY `instance_id` smallint(6) NOT NULL default '1';
ALTER TABLE nagios_hosts DROP KEY `instance_id`;
ALTER TABLE nagios_hosts DROP KEY `host_object_id`;
ALTER TABLE nagios_hosts ADD UNIQUE KEY `host_object_id` (`host_object_id`);

TRUNCATE TABLE nagios_services;
ALTER TABLE nagios_services ADD COLUMN `parallelize_check` smallint(6) NOT NULL default '1';
ALTER TABLE nagios_services MODIFY `instance_id` smallint(6) NOT NULL default '1';
ALTER TABLE nagios_services DROP KEY `instance_id`;
ALTER TABLE nagios_services DROP KEY `service_object_id`;
ALTER TABLE nagios_services ADD UNIQUE KEY `service_object_id` (`service_object_id`);

CREATE TABLE IF NOT EXISTS `nagios_timeperiod_exceptions` (
  `timeperiod_exception_id` int(11) NOT NULL auto_increment,
  `instance_id` smallint(6) NOT NULL default '0',
  `timeperiod_id` int(11) NOT NULL default '0',
  `exception_type` smallint(6) NOT NULL default '0',
  `syear` smallint(6) NOT NULL default '0',
  `smon` smallint(6) NOT NULL default '0',
  `smday` smallint(6) NOT NULL default '0',
  `swday` smallint(6) NOT NULL default '0',
  `swday_offset` smallint(6) NOT NULL default '0',
  `eyear` smallint(6) NOT NULL default '0',
  `emon` smallint(6) NOT NULL default '0',
  `emday` smallint(6) NOT NULL default '0',
  `ewday` smallint(6) NOT NULL default '0',
  `ewday_offset` smallint(6) NOT NULL default '0',
  `skip_interval` smallint(6) NOT NULL default '0',
  PRIMARY KEY (`timeperiod_exception_id`),
  KEY `timeperiod_id` (`timeperiod_id`)
) ENGINE=MyISAM COMMENT='Timeperiod Exceptions';

CREATE TABLE IF NOT EXISTS `nagios_timeperiod_exception_timeranges` (
  `nagios_timeperiod_exception_timerange_id` int(11) NOT NULL auto_increment,
  `instance_id` smallint(6) NOT NULL default '0',
  `timeperiod_exception_id` int(11) NOT NULL default '0',
  `start_sec` int(11) NOT NULL default '0',
  `end_sec` int(11) NOT NULL default '0',
  PRIMARY KEY  (`nagios_timeperiod_exception_timerange_id`),
  KEY `timeperiod_exception_id` (`timeperiod_exception_id`)
) ENGINE=MyISAM  COMMENT='Timeperiod Exception Timeranges';

CREATE TABLE IF NOT EXISTS `nagios_timeperiod_exclusions` (
  `parent_id` int(11) NOT NULL,
  `child_id` int(11) NOT NULL,
  KEY `parent_id` (`parent_id`)
) ENGINE=MyISAM COMMENT='Timeperiod Exclusions';

DROP TABLE IF EXISTS `nagios_instances`;

CREATE TABLE IF NOT EXISTS `nagios_regions` (
  `region_id` smallint(6) NOT NULL auto_increment,
  `region_name` varchar(64) NOT NULL default '',
  `region_description` varchar(128) NOT NULL default '',
  PRIMARY KEY (`region_id`)
) ENGINE=MyISAM COMMENT='Not implemented yet';

INSERT INTO nagios_regions (`region_name`, `region_description`) 
  SELECT * FROM (SELECT 'default', 'Unassigned Hosts and Services') AS needs_a_name
  WHERE NOT EXISTS ( SELECT region_name FROM nagios_regions );

CREATE TABLE IF NOT EXISTS `nagios_instances` (
  `instance_id` smallint(6) NOT NULL auto_increment,
  `region_id` smallint(6) NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `heartbeat` datetime NOT NULL,
  `instance_name` varchar(64) NOT NULL default '',
  `instance_description` varchar(128) NOT NULL default '',
  PRIMARY KEY  (`instance_id`),
  UNIQUE KEY (`uuid`)
) ENGINE=MyISAM  COMMENT='Location names of various Nagios installations';

INSERT INTO nagios_instances (`region_id`, `uuid`, `heartbeat`, `instance_name`, `instance_description`)
  SELECT * FROM ( SELECT ( SELECT region_id FROM nagios_regions WHERE `region_name` = 'default' )
    , '0', FROM_UNIXTIME(0), 'unassigned', 'new hosts and services go here until claimed by an instance (not implemented yet)' ) as needs_a_name
  WHERE NOT EXISTS ( SELECT `region_id` FROM nagios_instances );

CREATE TABLE IF NOT EXISTS `nagios_rebalance_affected_instances` (
  `timestamp` datetime NOT NULL default '1970-01-01 00:00:01',
  `instance_id` smallint(6) NOT NULL default '1',
  KEY `instance_id` (`instance_id`)
) ENGINE=MyISAM COMMENT='Not Implemented Yet';
