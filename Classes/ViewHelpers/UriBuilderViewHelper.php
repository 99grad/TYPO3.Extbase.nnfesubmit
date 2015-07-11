<?php

namespace Nng\Nnfesubmit\ViewHelpers;


class UriBuilderViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {


    /**
     * @param string $pageUid
     * @param string $additionalParams
     */

   public function render( $pageUid = null, $additionalParams = null ) {

		$uriBuilder = $this->controllerContext->getUriBuilder();
		
		if (!$pageUid) {
			$uri = 'index.php?';
		} else {
			$uri = $uriBuilder->reset()->setTargetPageUid($pageUid)->setAddQueryString(false)->build();
		}
		
		$uri_parts = parse_url($uri);
		
		parse_str($uri_parts['query'], $vars);
		unset($vars['cHash']);
		
		$query = array_merge( $vars, $additionalParams );
		
		return $uri.http_build_query($query, '', '&amp;');
		
    }
    
    
}
?>