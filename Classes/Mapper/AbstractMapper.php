<?php

namespace Nng\Nnfesubmit\Mapper;

class AbstractMapper {


    /**
     * @var \Nng\Nnfesubmit\Helper\AnyHelper
     * @inject
     */
    protected $anyHelper;

 	/**
     * @var \TYPO3\CMS\Core\Utility\File\BasicFileUtility
     * @inject     
     */
    protected $basicFileFunctions;
    
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     * @inject     
     */
    protected $objectManager;
    
    	
	
	
	function map ( $data, $settings ) {
		return $this->mapToTCA( $data, $settings );
	}
	
	
	/*
	 *	Mapping der Daten NACH dem Schreiben des Eintrages, 
	 *	z.B. um Rücksicht auf spezielle mm-Tabellen zu nehmen
	 *	$data enthält jetzt auch eine uid für den Datensatz
	 *
	 */
	 
	function map_mm ( $data, $settings ) {
		if (!$data['uid'] || !$settings['mm_tables']) return;
		foreach ($settings['mm_tables'] as $field=>$mm_table) {
			if ($uids = $data[$field]) {
				if (!is_array($uids)) $uids = array($uids);
				foreach ($uids as $uid) {
					$obj = array('uid_local' => (int) $data['uid'], 'uid_foreign' => (int) $uid);
					$GLOBALS['TYPO3_DB']->exec_INSERTquery($mm_table, $obj);
				}
			}
		}
		
	}

	
	function write ( &$data, $settings, $srcuid = null ) {
		$tablename = $settings['tablename'];
		if (!$data || !$tablename) return false;
		$obj = array();
		foreach ($data as $k=>$v) {
			if (is_array($v)) {
				if (in_array($k, array_keys($settings['mm_tables']))) {
					$obj[$k] = count($v);
				} else {
					$obj[$k] = $GLOBALS['TYPO3_DB']->quoteStr(join(',', $v), $tablename);
				}
			} else {
				$obj[$k] = $GLOBALS['TYPO3_DB']->quoteStr($v, $tablename);
			}
		}
		
		if ($srcuid) {
			if (!($result = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($tablename, 'uid='.intval($srcuid), $obj))) return array();
			$data['uid'] = $srcuid;
		} else {
			if (!($result = $GLOBALS['TYPO3_DB']->exec_INSERTquery($tablename, $obj))) return array();
			$data['uid'] = $GLOBALS['TYPO3_DB']->sql_insert_id();
		}
		return $data;
	}
	
	
	function delete( &$data, $settings ) {
		$tablename = $settings['tablename'];
		if (!$data['uid'] || !$tablename) return false;
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery($tablename, 'uid='.intval($data['uid']), array('deleted'=>1));
		return true;
	}
	
	
	function insertViewVariables ( $view, $setting ) {
		
	}
	
	function getTCA ( $tablename ) {
		$GLOBALS['TSFE']->includeTCA(); 
		\TYPO3\CMS\Core\Utility\GeneralUtility::loadTCA($tablename);
		$columns = array_keys($GLOBALS['TCA'][$tablename]['columns']);
		return $columns;
	}


	function mapToTCA ( $data, $settings ) {
		$tablename = $settings['tablename'];
		$columns = array_merge($this->getTCA( $tablename ), array('pid', 'uid', 'cruser_id'));
		
		$obj = array();
		foreach ($columns as $column) {
			if (trim($settings['insert'][$column])) $obj[$column] = $settings['insert'][$column];
			if (isset($data[$column])) $obj[$column] = $data[$column];
		}

		foreach ($settings['mapping'] as $k=>$v) {
			$obj[$k] = $data[$v];
		}
		
		
		$obj = array_merge( array(
			'tstamp' => mktime(),
			'crdate' => mktime()		
		), $obj);
		
		return $obj;
	}
	
	
	function strtotime ( $str, $format = 'd.m.Y') {
		return strtotime($str);
	}
	
}


?>