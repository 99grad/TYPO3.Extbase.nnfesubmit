<?php

namespace Nng\Nnfesubmit\Utilities;

class SettingsUtility extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {


	/**
	 * @var \TYPO3\CMS\Extbase\Service\TypoScriptService
	 * @inject
	 */
	protected $typoscriptService;
	
	
	protected $configurationManager;
	protected $request;
	
	protected $cObj;
	protected $settings;
	protected $mergedSettings;
	
	protected $configuration;
	
	
	public function __construct () {
	
		$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\TYPO3\CMS\Extbase\Object\ObjectManager');
		$this->configurationManager = $objectManager->get('\TYPO3\CMS\Extbase\Configuration\ConfigurationManager');
		$this->request = $objectManager->get('\TYPO3\CMS\Extbase\Mvc\Request');
		
		$this->cObj = $this->configurationManager->getContentObject();
		$this->configuration = $this->configurationManager->getConfiguration( \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK );
		$this->settings = $this->configuration['settings'];
		
		$this->mergedSettings = $this->merge_settings_with_flexform();
		
	}

	
	public function getConfiguration () {
		return $this->configuration;
	}
	
	public function getMergedSettings () {
		return $this->mergedSettings;
	}
	
	public function getSettings () {
		$setup = $this->remove_ts_setup_dots($this->getTsSetup());
		return $setup['settings'];
	}
	
	/**
	* Bestimmte, wiederkehrende Voreinstellungen für die Views holen, z.B. Dropdown mit Länderliste etc.
	*
	*/

	
	public function getDefaultSettingsForView () {
		$settings = self::getTsSetup( false );
		return $settings;
	}
	
	
	
	/**
	* Merge flexform values with settings - only if flexform-value is not empty
	*
	*/
	private function merge_settings_with_flexform () {
		if (!$this->settings) return array();
		$tmp = array_merge_recursive( $this->settings );
		if ($this->settings['flexform']) {
			foreach ($this->settings['flexform'] as $k=>$v) {
				if (trim($v) != '') $tmp[$k] = $v;
			}
		}
		return $tmp;
	}
	
	
	
    /**
	*	Get TypoScript Setup for plugin (with "name."-Syntax) as array
	*
	*/
	public static function getTsSetup ( $pageUid = false, $plugin = 'tx_nnfesubmit' ) {
		
		$cacheID = '__tsSetupCache_'.$pageUid.'_'.$plugin;
		
		if (TYPO3_MODE == 'FE') {
			if (!$plugin) return $GLOBALS['TSFE']->tmpl->setup['plugin.'];
			return $GLOBALS['TSFE']->tmpl->setup['plugin.']["{$plugin}."];
		}
		
		if ($GLOBALS[$cacheID]) return $GLOBALS[$cacheID];

		if (!$pageUid) $pageUid = (int) $GLOBALS['_REQUEST']['popViewId'];
		if (!$pageUid) $pageUid = (int) preg_replace( '/(.*)(id=)([0-9]*)(.*)/i', '\\3', $GLOBALS['_REQUEST']['returnUrl'] );
		if (!$pageUid) $pageUid = (int) preg_replace( '/(.*)(id=)([0-9]*)(.*)/i', '\\3', $GLOBALS['_POST']['returnUrl'] );
		if (!$pageUid) $pageUid = (int) preg_replace( '/(.*)(id=)([0-9]*)(.*)/i', '\\3', $GLOBALS['_GET']['returnUrl'] );
		if (!$pageUid) $pageUid = (int) $GLOBALS['TSFE']->id;
		if (!$pageUid) $pageUid = (int) $_GET['id'];

		$sysPageObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\TYPO3\CMS\Frontend\Page\PageRepository');
		$rootLine = $sysPageObj->getRootLine($pageUid);
		$TSObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\TYPO3\CMS\Core\TypoScript\ExtendedTemplateService');
		$TSObj->tt_track = 0;
		$TSObj->init();
		$TSObj->runThroughTemplates($rootLine);
		$TSObj->generateConfig();

		$GLOBALS[$cacheID] = !$plugin ? $TSObj->setup['plugin.'] : $TSObj->setup['plugin.']["{$plugin}."];
		
		if (!$plugin) return $TSObj->setup['plugin.'];
		return $TSObj->setup['plugin.']["{$plugin}."];
		
	}
	
	
	/**
	*	Aller TCA Felder holen für bestimmte Tabelle
	*
	*/
	public static function getTCAColumns ( $table = 'tx_nnfesubmit_domain_model_entry' ) {
		$cols = $GLOBALS['TCA'][$table]['columns'];
		foreach ($cols as $k=>$v) {
			$cols[\TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToLowerCamelCase($k)] = $v;
		}
		return $cols;
	}
	
	/**
	*	Label eines bestimmten TCA Feldes holen
	*
	*/
	public static function getTCALabel ( $column = '', $table = 'tx_nnfesubmit_domain_model_entry' ) {
		$tca = self::getTCAColumns( $table );
		$label = $tca[$column]['label'];
		if ($LL = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($label)) return $LL;
		return $label;
	}
	
	
	public function getEnableFields ( $table ) {
		return $GLOBALS['TSFE']->sys_page->enableFields( $table, $GLOBALS['TSFE']->showHiddenRecords);
	}
	
	
	public function getExtConf ( $param = null, $ext = 'nnfesubmit' ) {
		$extConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$ext]);
		return $param ? $extConfig[$param] : $extConfig;
	}
	
	/* --------------------------------------------------------------- 
	
		Wandelt die "."-Arrays eines TypoScripts um, damit z.B.
		per JSON oder Fluid darauf zugegriffen werden kann.
		
		array(
			'demo' 	=> 'oups',
			'demo.'	=> array(
				'test' 	=> '1',
				'was'	=> '2'
			)
		)
		
		wird zu:
		
		array(
			'demo' => array(
				'__' 	=> 'oups'
				'test' 	=> '1',
				'was'	=> '2'
			)
		)
		
	*/
	
	function remove_ts_setup_dots ($ts) {
		return $this->typoscriptService->convertTypoScriptArrayToPlainArray($ts);
		
		foreach ($ts as $key=>$v) {
			if (substr($key,-1) == '.' && is_array($v)) {
				$v = self::remove_ts_setup_dots($v);
				$r = $ts[substr($key,0,-1)];
				$ts[substr($key,0,-1)] = $v;
				if ($r) $ts[substr($key,0,-1)]['_typoScriptNodeValue'] = $r;
				unset($ts[$key]);
			}
		}
		return $ts;
	}
	
	function add_ts_setup_dots ( $arr ) {
		return $this->typoscriptService->convertPlainArrayToTypoScriptArray( $arr );
	}
	
	function parse_flexform ( $xml, $sheet='sDEF', $lang='lDEF' ) {
		if (!$xml) return array();
		$arr = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($xml);
		$flat = array();
		foreach ($arr['data'][$sheet][$lang] as $k => $v) {
			$flat[$k] = $v['vDEF'];
		}
		return $flat;
	}
	
	
}

?>