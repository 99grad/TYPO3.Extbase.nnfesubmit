<?php

namespace Nng\Nnfesubmit\ViewHelpers;

 
class ContentElementViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {


	/**
	* @var \Nng\Nnfesubmit\Helper\AnyHelper
	* @inject
	*/
	protected $anyHelper;
	
    private $cObj;
    
    public function initializeObject () {
    	$this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\TYPO3\CMS\Extbase\Object\ObjectManager');
		$this->anyHelper = $this->objectManager->create('\Nng\Nnfesubmit\Helper\AnyHelper');
    }
    
    /**
     *
     * Render
     *
     * Renders a content element
     *
     * @param int $uid
     * @param array $data
     * return string
     */
     
    public function render($uid, $data = null) {
    
    	$this->cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tslib_cObj');
    	
        $conf = array(
            'tables' => 'tt_content',
            'source' => $uid,
            'dontCheckPid' => 1
        );
        $html = $this->cObj->RECORDS($conf);
        if ($data) {
        	$html = $this->anyHelper->renderTemplateSource($html, $data);
        }
        return $html ? $html : "Keine Content-Element mit uid {$uid} gefunden. Evtl. muss uid im View angepasst werden!";
    }

    
}

?>