<?php
/* $Id: ext_localconf.php 8178 2008-02-05 10:15:30Z dmitry $ */

if (!defined ('TYPO3_MODE')) die('Access denied.');

t3lib_extMgm::addPItoST43($_EXTKEY, 'pi1/class.tx_pagebrowse_pi1.php', '_pi1', 'list_type', 1);

?>