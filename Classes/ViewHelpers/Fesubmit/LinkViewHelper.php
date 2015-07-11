<?php

namespace Nng\Nnfesubmit\ViewHelpers\Fesubmit;

class LinkViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	* @var \Nng\Nnfesubmit\Helper\AnyHelper
	* @inject
	*/
	protected $anyHelper;
	
	/**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     * @inject
     */
    protected $objectManager;
    
    /**
     * @var \Nng\Nnfesubmit\Controller\MainController
     * @inject
     */
    protected $nnfesubmitController;
    
    
 	/**
     * @param string $action
     * @param string $type
     * @param int $uid
     * @param int $pluginUid
     * @param int $returnUrl
     * @param mixed $addQueryPrefix     
     */

   public function render( $action = null, $type = null, $uid = null, $pluginUid = null, $returnUrl = null, $addQueryPrefix = null ) {

   		switch ($action) {
   		
   			case 'edit':
   				$params = $this->nnfesubmitController->getEditLinkParams( array(
   					'type' 		=> $type,
   					'uid'		=> $uid,
   					'pluginUid' => $pluginUid,
   					'returnUrl' => $returnUrl
   				));
   				break;
   				
   			case 'fedelete':
   				$params = $this->nnfesubmitController->getFeDeleteLinkParams( array(
   					'type' 		=> $type,
   					'uid'		=> $uid,
   					'pluginUid' => $pluginUid,
					'returnUrl' => $returnUrl  					
   				));
   				break;
   		}
   		return $params;
	}

}
?>


