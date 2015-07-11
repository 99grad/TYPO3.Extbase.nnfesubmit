<?php
namespace Nng\Nnfesubmit\Domain\Repository;


class AbstractRepository extends \TYPO3\CMS\Extbase\Persistence\Repository {

	/**
	 * @var \Nng\Nnfesubmit\Helper\AnyHelper
	 * @inject
	 */
	protected $anyHelper;
	
	/**
	* @var \Nng\Nnfesubmit\Utilities\SettingsUtility
	* @inject
	*/
	protected $settingsUtility;

	/**
	* @var \Nng\Nnfesubmit\Utilities\CacheUtility
	* @inject
	*/
	protected $cacheUtility;


}