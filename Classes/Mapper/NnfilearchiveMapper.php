<?php

namespace Nng\Nnfesubmit\Mapper;


class NnfilearchiveMapper extends \Nng\Nnfesubmit\Mapper\AbstractMapper {

	
	/**
	 * @var \Nng\Nnfilearchive\Domain\Repository\CategoryRepository
	 * @inject
	 */
	protected $categoryRepository = NULL;
	
	
	function map ( $data, $settings ) { 
		
		$data = $this->mapToTCA( $data, $settings );		
		$media = $settings['media'];
		
		
		foreach ($media as $k => $path) {

			if ($data[$k]) {
				$basefile = basename($data[$k]);
				$unique_filename = $this->basicFileFunctions->getUniqueName(trim($basefile), $path);
				
				\TYPO3\CMS\Core\Utility\GeneralUtility::upload_copy_move('uploads/tx_nnfesubmit/'.$basefile, $unique_filename);
				
				if (file_exists($unique_filename)) {
					//$this->anyHelper->addFlashMessage ( 'Media kopiert', 'Die Datei '.$data[$k].' wurde erfolgreich verschoben.', 'OK');
					unlink('uploads/tx_nnfesubmit/'.$basefile);
					$data[$k] = basename($unique_filename);
				} else {
					$this->anyHelper->addFlashMessage ( 'Datei nicht kopiert', 'Die Datei '.$data[$k].' konnte nicht kopiert werden.', 'WARNING');
					unset($data[$k]);
				}
			}
			
		}
				
		return $data;
	}


	function insertViewVariables ( &$view, $settings, $extSettings, $gp ) {
		
		if ($plugInUid = intval($gp['pluginUid'])) {
			$this->categoryRepository->updateSettingsFromPlugIn( $plugInUid );
		}
		
		// Kategorien-Baum laden
		$categoryTree = $this->categoryRepository->getCategoryTreeBranchWithItems();

//		\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump(  $gp  );
		
		$data = array(
			'categoryTree' 	=> $categoryTree,
			'mapper'		=> $settings['mapper']
		);
		
		if (get_class($view) == 'TYPO3\CMS\Fluid\View\TemplateView') {	
			$view->assignMultiple($data);
			return $data;
		} else {
			$tmp = (array) array_merge($view);
			\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule( $tmp, $data );
			$view = $tmp;
			return $tmp;
		}
		
	}


	function validate ( $data, $settings ) {
		$errors = array();
		/*
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
		*/
		return $errors;
	}


}


?>