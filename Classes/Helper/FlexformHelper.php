<?php
namespace Nng\Nnfesubmit\Helper;

class FlexformHelper {
	
	/**
	* @var \Nng\Nnfesubmit\Utilities\SettingsUtility
	* @inject
	*/
	protected $settingsUtility;

	/**
    * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
    */
	protected $objectManager;
	
	
	function __construct () {
		$this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\TYPO3\CMS\Extbase\Object\ObjectManager');
		$this->settingsUtility = $this->objectManager->get("\Nng\Nnfesubmit\Utilities\SettingsUtility");
	}
	
	
	function insertTypesFromSetup ( $config, $a = null ) {
		
		$ts = $this->settingsUtility->getTsSetup();
		$ts = $ts['settings.'];

		if (!$ts) {
			$config['items'] = array( array('Kein TS gefunden - Template-Vorlagen können per plugin.tx_nnfesubmit.settings definiert werden', '') );
			return $config;
		}
		
		foreach ($ts as $k=>$v) {
			if (is_array($v) && $v['extension']) {
				$k = substr($k,0,-1);
				$config['items'] = array_merge( $config['items'], array( array($v['title'].' (EXT:'.$v['extension'].' · TS:'.$k.')', $k, '') ) );
			}
		}
		
		
		return $config;
	}
	
	
}

?>