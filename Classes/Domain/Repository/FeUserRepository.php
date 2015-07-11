<?php
namespace Nng\Nnfesubmit\Domain\Repository;


class FeUserRepository extends \Nng\Nnfesubmit\Domain\Repository\AbstractRepository {

	/**
	 * @var \Nng\Nnfesubmit\Domain\Repository\FeGroupsRepository
	 * @inject
	 */
	protected $feGroupsRepository = NULL;
	
	
	// Ja, lästert nur. Aber bei großen Datenmengen ist das hier immer noch deutlich schneller als das normale Repository!
	
	public function findAllByUid ( $onlyUids = null ) {
			
		if ($cache = $this->cacheUtility->getRamCache( __METHOD__, $onlyUids )) return $cache;
		$onlyUidsArr = join(',', \TYPO3\CMS\Core\Database\DatabaseConnection::cleanIntArray($onlyUids));
		
		$groups_by_uid = $this->feGroupsRepository->findAllByUid();
		$users_by_uid = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			"*", 
			'fe_users', 
			'1=1 '	.$this->settingsUtility->getEnableFields( 'fe_users' ) 
					.($onlyUids ? ' AND uid IN('.$onlyUidsArr.')' : ''),
			'', 
			'', 
			'', 
			'uid'
		);

		foreach ($users_by_uid as $k=>$item) {
			$ref = &$users_by_uid[$k];
			if ($ref['usergroup'] && ($groups = explode(',', $ref['usergroup']))) {
				$tmp = array();
				foreach ($groups as $uid) {
					if ($groups_by_uid[$uid]) {
						$tmp[] = &$groups_by_uid[$uid];
					}
				}
				$ref['usergroup'] = $tmp;
			}
		}

		$this->cacheUtility->setRamCache( $data, __METHOD__, $onlyUids );

		return $users_by_uid;
	}
	
	
	public function findOneByUid ( $uid ) {
		$data = $this->findAllByUid( array($uid) );
		if (!$data) return array();
		return array_pop($data);
	}
	
}