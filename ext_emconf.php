<?php

########################################################################
# Extension Manager/Repository config file for ext "pagebrowse".
#
# Auto generated 09-11-2010 17:52
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Universal page browser',
	'description' => 'Provides page browsing services for extensions',
	'category' => 'fe',
	'author' => 'Dmitry Dulepov',
	'author_email' => 'dmitry@typo3.org',
	'shy' => '',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '1.3.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '4.0.0-4.5.99',
			'php' => '5.2.0-10.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:13:{s:9:"ChangeLog";s:4:"a2c9";s:12:"ext_icon.gif";s:4:"c538";s:17:"ext_localconf.php";s:4:"821e";s:14:"ext_tables.php";s:4:"cccc";s:14:"doc/manual.sxw";s:4:"e646";s:31:"pi1/class.tx_pagebrowse_pi1.php";s:4:"2e53";s:19:"pi1/flexform_ds.xml";s:4:"b1b9";s:17:"pi1/locallang.xml";s:4:"e8ee";s:14:"res/styles.css";s:4:"34c0";s:18:"res/styles_min.css";s:4:"e907";s:17:"res/template.html";s:4:"d59b";s:33:"static/page_browser/constants.txt";s:4:"156c";s:29:"static/page_browser/setup.txt";s:4:"6aa6";}',
	'suggests' => array(
	),
);

?>