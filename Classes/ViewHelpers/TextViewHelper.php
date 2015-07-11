<?php

namespace Nng\Nnfesubmit\ViewHelpers;


class TextViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {


    /**
     * @param string $func
     * @param string $value
     */

   public function render( $func = null, $value = null ) {
    
		if ($value == null) $value = $this->renderChildren();
		
		if ($func) {
			$func = explode(',', $func);
			foreach ($func as $f) {
				
				switch (trim($f)) {
				
					case 'price':
						setlocale(LC_MONETARY, 'de_DE');
						$value = '€ '.money_format('%!=#8.2i', $value);
						break;
						
					case 'minus1':
						$value = (int) $value;
						$value--;
						
					case 'weight':
						$value = floor($value*1000)/1000;
						$oldLocale = setlocale(LC_NUMERIC, "0");
						setlocale(LC_NUMERIC, 'de_DE');
						$locale = localeconv();
						$digits = $value == floor($value) ? 0 : $locale['frac_digits'];
						$value = number_format($value, $digits, $locale['decimal_point'], $locale['thousands_sep']);
						setlocale(LC_NUMERIC, $oldLocale);
						break;
					
					case 'stripTags':
						$value = strip_tags($value);
						break;
					
					case 'stripNbsp':
						$value = str_replace('&nbsp;', ' ', $value);
						break;
						
					case 'nl2br':
						$value = nl2br($value);
						$value = str_replace( array("\n", "\r"), '', $value);
						break;
					
					case 'utf8_decode':
						$value = utf8_decode($value);
						break;
						
					case 'urlencode':
						$value = urlencode($value);
						break;
						
					case 'htmlspecialchars':
						$value = htmlspecialchars($value);
						break;
						
					case 'stripTagsForTable':
						$value = strip_tags( $value, '<br><table><td><tr><b><strong>');
						break;
						
					case 'extractLinks':
						preg_match_all('~<a(.*?)href="([^"]+)"(.*?)>~', $value, $matches);
						return join(',', $matches[2]);
						break;
						
					case 'basename':
						if (!$value) return '';
						$value = basename($value);
						break;
						
					default: 
						break;
						
				}
			}
		}
		
		return $value;
		
    }
    
    
}
?>