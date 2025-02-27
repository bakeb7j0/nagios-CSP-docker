
CREATE TABLE IF NOT EXISTS `tbl_mrtgfile` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `config_name` varchar(255) NOT NULL default '', /* unused, prevents errors in purgeRelations */
  `config_id` tinyint(3) unsigned NOT NULL default '0',
  `file_name` varchar(255) NOT NULL,
  `alias` varchar(255) NOT NULL default '',
  `host_name` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `hostgroup_name` tinyint unsigned NOT NULL default 0, /* unused, prevents errors in purgeRelations */
  `last_modified` timestamp NOT NULL DEFAULT '1970-02-02 01:01:01' ON UPDATE current_timestamp,
  `access_rights` varchar(8) default NULL,
  `active` boolean NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS `tbl_lnkMrtgfileToHost` (
  `idMaster` int(11) NOT NULL,
  `idSlave` int(11) NOT NULL,
  `exclude` boolean NOT NULL default 0,
  PRIMARY KEY  (`idMaster`,`idSlave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE tbl_domain ADD COLUMN mrtgconfig varchar(255) NOT NULL default '/etc/mrtg/conf.d/';