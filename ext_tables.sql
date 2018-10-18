#
# Table structure for table 'tx_beacl_acl'
# Feature: #85160 - Auto create management DB fields from TCA ctrl
#
CREATE TABLE tx_beacl_acl (
	type int(11) unsigned DEFAULT '0' NOT NULL,
	object_id int(11) unsigned DEFAULT '0' NOT NULL,
	permissions int(11) unsigned DEFAULT '0' NOT NULL,
	recursive tinyint(1) unsigned DEFAULT '0' NOT NULL,
	UNIQUE KEY uniqueacls (pid,type,object_id,recursive)
);
