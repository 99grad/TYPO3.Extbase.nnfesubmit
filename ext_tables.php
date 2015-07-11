<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
	$_EXTKEY,
	'Nnfesubmit',
	'Frontend Submission'
);

$pluginSignature = str_replace('_','',$_EXTKEY) . '_nnfesubmit';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature, 'FILE:EXT:' . $_EXTKEY . '/Configuration/FlexForms/flexform_nnfesubmit.xml');

if (TYPO3_MODE == 'BE')	{
	$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['\Nng\Nnfesubmit\Wizicons\AddContentElementWizicon'] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'Classes/Wizicons/AddContentElementWizicon.php';
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript', 'Frontend Submission');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_nnfesubmit_domain_model_entry', 'EXT:nnfesubmit/Resources/Private/Language/locallang_csh_tx_nnfesubmit_domain_model_entry.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_nnfesubmit_domain_model_entry');
$TCA['tx_nnfesubmit_domain_model_entry'] = array(
	'ctrl' => array(
		'title'	=> 'LLL:EXT:nnfesubmit/Resources/Private/Language/locallang_db.xlf:tx_nnfesubmit_domain_model_entry',
		'label' => 'ext',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'dividers2tabs' => TRUE,

		'versioningWS' => 2,
		'versioning_followPages' => TRUE,
		'origUid' => 't3_origuid',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l10n_parent',
		'transOrigDiffSourceField' => 'l10n_diffsource',
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
		),
		'searchFields' => 'ext,data,status,',
		'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Configuration/TCA/Entry.php',
		'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/tx_nnfesubmit_domain_model_entry.gif'
	),
);

?>