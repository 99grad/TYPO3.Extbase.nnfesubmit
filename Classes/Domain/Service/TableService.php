<?php

namespace Nng\Nnfesubmit\Domain\Service;


class TableService implements \TYPO3\CMS\Core\SingletonInterface{
		

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 * @inject
	 */
	protected $objectManager;
	
	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
	 * @inject
	 */
	protected $persistenceManager;
		
	/**
	* __construct
	*
	* @return void
	*/
	public function __construct(){}
	
	
	/**
	* action getEntry
	* Prüft, ob ein Eintrag in beliebiger Datenbank-Tabelle exisitert und gibt Datensatz zurück
	*
	* @return void
	*/
 
	public function getEntry ( $settings, $uid ) {

		/*	
		$obj = $this->objectManager->get($repository);
		$data = $obj->findByUid($uid);
		*/
		
		$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow( '*', $settings['tablename'], 'uid='.intval($uid).' AND deleted=0' );
		if (!$row) return array();
		
		if ($settings['mm_tables']) {
			foreach ($settings['mm_tables'] as $field=>$table) {
				$row[$field] = array();
				if ($mm_rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows( '*', $table, 'uid_local='.intval($uid), null )) {
					foreach ($mm_rows as $mm_row) {
						$row[$field][] = $mm_row['uid_foreign'];
					}
				}
			}
		}
		
		/*
		if ($settings['media']) {
			foreach ($settings['media'] as $field => $path) {
				if ($row[$field]) $row[$field] = $path.$row[$field];
			}
		}
		*/
		
		return $row;
	}
		
}
	