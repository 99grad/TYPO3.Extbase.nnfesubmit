<?php

namespace Nng\Nnfesubmit\ViewHelpers\Fesubmit;

class FormViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

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
     * @param string $func
     * @param string $value
     * @param string $params
     */

   public function render( $params = null ) {
   		if (!$params) $params = array();
    	return $this->nnfesubmitController->getFormInstance( $params );
	}

}
?>


