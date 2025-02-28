################
NDO 3 Change Log
################

3.1.1 - 2024-06-13
------------------
FIXES
- Updated header files to work with Nagios Core 4.5.3

3.1.0 - 2023-06-06
------------------
FEATURES
- Added missing tables for timeperiods
- Added several missing columns for contacts, hostgroups, servicegroups, hosts, and services
- Added comment_history_data, downtime_history_data to ndo.cfg to allow more granular control over which data gets exported.

FIXES
- Additional fix for TPS#15549 (listed below)
- Fixed incorrect contact_id being written to nagios_contact_addresses, nagios_contact_notificationcommands
- Fixed incorrect handling of downtimes over 9.1 hours in length

3.0.7 - 2021-07-08
------------------
FIXES
- Added option "log_failed_queries" to ndo.cfg. Set this to 0 to disable failed query logging
- Fixed issue where nagios_objects.name2 would occasionally be set to NULL
- Fixed issue where leftover comments and other objects would cause hosts and services to continue showing in the database after deletion
[TPS#15549]
- Widened all text columns significantly

3.0.6 - 2021-02-25
------------------
FIXES
- Increased performance for queries involving comment history and downtimes on large/long-running systems.
- Fixed error when adding downtimes which expire after 2038.

3.0.5 - 2020-12-28
------------------
FIXES
- Drastically reduced startup time for some systems
- Fixed occasional long shutdown times in Nagios Core
- Fixed segmentation faults related to severed MySQL connections
- Fixed issue with service display_name being set to the service description

3.0.4 - 2020-10-02
------------------
FIXES
- Fixed issue with downtime brokering on startup
- Fixed logging of failed queries for WRITE_HOSTS/WRITE_SERVICES/WRITE_CONTACTS
- Fixed blank host/service status rows that may get added during a hard restart

3.0.3 - 2020-08-25
------------------
FIXES
- Fixed issue with version comparison in database upgrade script
- Fixed issue with failed timed_event brokering on startup
- Fixed issue with erroneous logging of notification brokering failures
- Fixed improper handling of callback registration when some event types were disabled

3.0.2 - 2020-07-14
------------------
FIXES
- Fixed host/service/contact tables being truncated on restarts (long-standing PENDING states in Nagios XI host/service status)
- Fixed issue with writing contacts to object tables during startup when duplicate objects exist in the nagios configuration.
- Fixed issues around NDO trying to broker its own error logs when MySQL was disconnected or disabled.
- Fixed issues with NEB callback registration priority for Mod Gearman compatibility
- Fixed issue where changing capitalization of an existing host/service would partially fail.
- Improved MySQL reconnection logic to increase chances of successful reconnection and reduce performance impact in Nagios Core.
- Made previously compile-time debugging configuration available in ndo.cfg.
- Added more information to the logs when handling errors during startup.
- Added removal of inactive objects from the host/service/contact status tables instead of truncating them completely.

3.0.1 - 2020-06-11
------------------
FIXES
- Fixed failure on startup due to oversized subqueries in ndo_write_contact_objects, 
	  ndo_write_services_objects, and ndo_write_hosts_objects
- Fixed errors when re-running the upgrade script for 2.1.3->3.0.x
- Fixed "name1 is null" error messages during startup due to missing timeperiods.

3.0.0 - 2020-06-02
------------------
Initial Rewrite.
