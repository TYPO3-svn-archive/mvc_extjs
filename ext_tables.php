<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['concatenateHandler'] = "EXT:mvc_extjs/Classes/PageRenderer/Service.php:&tx_MvcExtjs_PageRenderer_Service->doConcatenate";

?>