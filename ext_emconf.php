<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "be_acl".
 *
 * Auto generated 06-08-2014 20:07
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Backend ACLs',
	'description' => 'Backend Access Control Lists',
	'category' => 'be',
	'version' => '1.8.0',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Sebastian Kurfuerst',
	'author_email' => 'sebastian@garbage-group.de',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
            'typo3' => '8.7.0-9.9.99',
            'beuser' => '8.7.0-9.9.99'
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
);
