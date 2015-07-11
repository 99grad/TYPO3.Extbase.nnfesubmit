<?php

namespace Nng\Nnfesubmit\Mapper;


class ZvmcalendarMapper extends \Nng\Nnfesubmit\Mapper\AbstractMapper {

	
	function map ( $data, $settings ) { 
		
		$data = $this->mapToTCA( $data, $settings );
		$data['from_date'] = $this->strtotime( $data['from_date'] );
		$data['to_date'] = $this->strtotime( $data['to_date'] );
		
		$media = $settings['media'];
		
		foreach ($media as $k => $path) {
			if ($data[$k]) {
				$unique_filename = $this->basicFileFunctions->getUniqueName(basename($data[$k]), $path);
				\TYPO3\CMS\Core\Utility\GeneralUtility::upload_copy_move($data[$k], $unique_filename);
				if (file_exists($unique_filename)) {
					//$this->anyHelper->addFlashMessage ( 'Media kopiert', 'Die Datei '.$data[$k].' wurde erfolgreich verschoben.', 'OK');
					unlink($data[$k]);
					$data[$k] = basename($unique_filename);
				} else {
					$this->anyHelper->addFlashMessage ( 'Datei nicht kopiert', 'Die Datei '.$data[$k].' konnte nicht kopiert werden.', 'WARNING');
					unset($data[$k]);
				}
			}
		}
				
		return $data;
	}


	function insertViewVariables ( &$view, $setting ) {
		
		$data = array('categories' => array('2'=>'Für Kinder', '3'=>'Für Erwachsene'));
		
		if (get_class($view) == 'TYPO3\CMS\Fluid\View\TemplateView') {	
			$view->assignMultiple($data);
		} else {
			$tmp = array_merge($view);
			\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule( $tmp, $data );
			$view = $tmp;
		}
	}


	function validate ( $data, $settings ) {
		$errors = array();
		
		$f = trim($data['from_date']);
		$t = trim($data['to_date']);
		$dFrom = explode('.', $f);
		$dTo = explode('.', $t);
		
		if ($f && !checkdate($dFrom[1], $dFrom[0], $dFrom[2])) $errors['from_date'] = 1;
		if ($t && !checkdate($dTo[1], $dTo[0], $dTo[2])) $errors['to_date'] = 1;
		if ($t && $f && strtotime($t) < strtotime($f)) {
			 $errors['from_date'] = 1;  
			 $errors['to_date'] = 1;
		}
		return $errors;
	}


}


?>