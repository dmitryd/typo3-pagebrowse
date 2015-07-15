<?php

// Add pi1 plugin
$TCA['tt_content']['types']['list']['subtypes_excludelist']['pagebrowse_pi1'] = 'layout,select_key,pages';
$TCA['tt_content']['types']['list']['subtypes_addlist']['pagebrowse_pi1'] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
	array('LLL:EXT:pagebrowse/pi1/locallang.xml:tt_content.list_type_pi1','pagebrowse_pi1'), 'list_type', 'pagebrowse');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('pagebrowse_pi1', 'FILE:EXT:pagebrowse/pi1/flexform_ds.xml');
