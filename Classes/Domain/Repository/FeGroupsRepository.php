<?php
namespace Nng\Nnfesubmit\Domain\Repository;


class FeGroupsRepository extends \Nng\Nnfesubmit\Domain\Repository\AbstractRepository {


	public function findAllByUid () {

		if ($cache = $this->cacheUtility->getRamCache( __METHOD__ )) return $cache;

		$data = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			"*", 
			'fe_groups', 
			'1=1 '.$this->settingsUtility->getEnableFields( 'fe_groups' ),
			'', 
			'', 
			'', 
			'uid'
		);

		$this->cacheUtility->setRamCache( $data, __METHOD__ );

		return $data;
	}
	
}