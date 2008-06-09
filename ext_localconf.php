<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

t3lib_extMgm::addPItoST43($_EXTKEY,'pi1/class.tx_mmcleanfilelink_pi1.php','_pi1','',1);

$TYPO3_CONF_VARS['EXTCONF']['css_styled_content']['pi1_hooks']['render_uploads'] = 'EXT:mm_cleanfilelink/pi1/class.tx_mmcleanfilelink_pi1.php:tx_mmcleanfilelink_pi1';
?>