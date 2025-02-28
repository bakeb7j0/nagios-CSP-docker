ALTER TABLE nagios_eventhandlers MODIFY `output` TEXT NOT NULL;
ALTER TABLE nagios_hostchecks MODIFY `output` TEXT NOT NULL;
ALTER TABLE nagios_hoststatus MODIFY `output` TEXT NOT NULL;
ALTER TABLE nagios_notifications MODIFY `output` TEXT NOT NULL;
ALTER TABLE nagios_servicechecks MODIFY `output` TEXT NOT NULL;
ALTER TABLE nagios_servicestatus MODIFY `output` TEXT NOT NULL;
ALTER TABLE nagios_statehistory MODIFY `output` TEXT NOT NULL;
ALTER TABLE nagios_systemcommands MODIFY `output` TEXT NOT NULL;

ALTER TABLE nagios_hosts MODIFY `alias` varchar(255) NOT NULL default '';
ALTER TABLE nagios_contacts MODIFY `alias` varchar(255) NOT NULL default '';
