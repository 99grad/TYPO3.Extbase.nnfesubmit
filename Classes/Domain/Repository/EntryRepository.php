<?php

namespace Nng\Nnfesubmit\Domain\Repository;

class EntryRepository extends \TYPO3\CMS\Extbase\Persistence\Repository {

	public function getEntryForExt ( $srcuid, $ext ) {
		$query = $this->createQuery();
        $query->matching(
        	$query->logicalAnd(
        		$query->equals('srcuid', $srcuid),
        		$query->equals('ext', $ext)
        	)
        );
		return $query->execute()->getFirst();
	}
	
}

?>