<?php

namespace Nng\Nnfesubmit\Validation;
   
class FiletypeIsImageValidator extends \TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator {

	protected function isValid($value, $field = null, $gpVars = null) {
		if (is_array($value)) $value = $value['name'];
		if (!$value) return;
		$allowed = array('jpg', 'jpeg', 'gif', 'png', 'pdf');
		$suffix = strtolower( array_pop(explode('.', $value)) );
		if (!in_array($suffix, $allowed)) $this->addError('Filetype not valid. Allowed are one of: '.join(',', $allowed), 991221559976);
	}
	
}

?>