<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'Nng.' . $_EXTKEY,
	'Nnfesubmit',
	array(
		'Main' => 'main,showForm,validateForm,showConfirmationForm,finalize,thanks,approved',
		
	),
	// non-cacheable actions
	array(
		'Main' => 'main,showForm,validateForm,showConfirmationForm,finalize,thanks,approved',
		
	)
);


// eID Dispatcher, z.B. zum direktend Drucken der Versandetiketten über Link aus E-Mail
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['nnfesubmit'] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('nnfesubmit').'Classes/Dispatcher/EidDispatcher.php';


?>