<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}
t3lib_extMgm::addStaticFile($_EXTKEY,'static/page_browser/', 'Page browser');

// Add pi1 plugin
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY . '_pi1'] = 'layout,select_key,pages';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY . '_pi1'] = 'pi_flexform';
t3lib_extMgm::addPlugin(array('LLL:EXT:pagebrowse/pi1/locallang.xml:tt_content.list_type_pi1', $_EXTKEY . '_pi1'), 'list_type');
t3lib_extMgm::addPiFlexFormValue($_EXTKEY .'_pi1', 'FILE:EXT:pagebrowse/pi1/flexform_ds.xml');

?>
