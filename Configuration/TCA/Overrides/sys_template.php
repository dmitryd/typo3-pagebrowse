<?php
if (!defined ('TYPO3_MODE')) {
die ('Access denied.');
}
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('pagebrowse', 'static/page_browser/', 'Page browser');
