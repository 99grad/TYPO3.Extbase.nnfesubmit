<?php

$ajax = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('request');
$ajax['vendor'] = 'Nng';
$ajax['extensionName'] = 'Nnfesubmit';
         

$TSFE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController', $TYPO3_CONF_VARS, 0, 0);
tslib_eidtools::connectDB();
tslib_eidtools::initLanguage();

 
// Get FE User Information
$TSFE->initFEuser();

// Important: no Cache for Ajax stuff
$TSFE->set_no_cache();

// TCA laden für extensions
$TSFE->includeTCA();
\TYPO3\CMS\Core\Utility\GeneralUtility::loadTCA('nnfesubmit');


$TSFE->checkAlternativeIdMethods();

$TSFE->determineId();
//$TSFE->id = 2060;

$TSFE->initTemplate();
$TSFE->getConfigArray();
\TYPO3\CMS\Core\Core\Bootstrap::getInstance();

$TSFE->cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer');
$TSFE->settingLanguage();
$TSFE->settingLocale();

if (!$TSFE->baseUrl) {
	$baseUrl = $GLOBALS['TSFE']->config['config']['baseURL'];
	$TSFE->baseUrl = $baseUrl ? $baseUrl : $_SERVER['HTTP_HOST'];
}

$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\TYPO3\CMS\Extbase\Object\ObjectManager');
 
/**
 * Initialize Extbase bootstap
 */
$bootstrapConf['extensionName'] = 'Nnfesubmit';
$bootstrapConf['pluginName'] = 'Pi1';


$bootstrap = new TYPO3\CMS\Extbase\Core\Bootstrap();
$bootstrap->initialize($bootstrapConf);
 
$bootstrap->cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer');

/**
 * Build the request
 */
$request = $objectManager->get('\TYPO3\CMS\Extbase\Mvc\Request');
 
$request->setControllerVendorName('Nng');
$request->setcontrollerExtensionName('Nnfesubmit');
$request->setPluginName('Pi1');
$request->setControllerName('Eid');
$request->setControllerActionName('processRequest');
$request->setArguments( array_merge($_POST, $_GET));
 
$response = $objectManager->create('\TYPO3\CMS\Extbase\Mvc\ResponseInterface');
 
$dispatcher = $objectManager->get('\TYPO3\CMS\Extbase\Mvc\Dispatcher');
 
$dispatcher->dispatch($request, $response);

/**
*
*/
/*

$generator = $objectManager->create('TYPO3\CMS\Frontend\Page\PageGenerator');
$generator->pagegenInit();
$generator->getIncFiles();

$generator->renderContent();
*/

$content = $response->getContent();
echo $content;

?>