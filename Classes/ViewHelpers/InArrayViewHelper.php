<?php

namespace Nng\Nnfesubmit\ViewHelpers;


class InArrayViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {


    /**
     * @param string $func
     * @param string $value
     */

   public function render( $arr = null, $value = null ) {

		if (!is_array($arr)) return false;
		return in_array($value, $arr);
		
    }
    
    
}
?>