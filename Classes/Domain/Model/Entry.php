<?php

namespace Nng\Nnfesubmit\Domain\Model;

class Entry extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * ext
	 *
	 * @var string
	 */
	protected $ext;

	/**
	 * srcuid
	 *
	 * @var int
	 */
	protected $srcuid;
	
	/**
	 * cruser_id
	 *
	 * @var int
	 */
	protected $cruser_id;
	
	/**
	 * data
	 *
	 * @var string
	 */
	protected $data;

	/**
	 * status
	 *
	 * @var integer
	 */
	protected $status;

	/**
	 * Returns the ext
	 *
	 * @return string $ext
	 */
	public function getExt() {
		return $this->ext;
	}

	/**
	 * Sets the ext
	 *
	 * @param string $ext
	 * @return void
	 */
	public function setExt($ext) {
		$this->ext = $ext;
	}

	/**
	 * Returns the data
	 *
	 * @return string $data
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * Sets the data
	 *
	 * @param string $data
	 * @return void
	 */
	public function setData($data) {
		$this->data = $data;
	}

	public function getSrcuid() {
		return $this->srcuid;
	}
	
	public function setSrcuid($data) {
		$this->srcuid = $data;
	}
	
	public function getCruserId() {
		return $this->cruser_id;
	}
	
	public function setCruserId($data) {
		$this->cruser_id = $data;
	}
	
	/**
	 * Returns the status
	 *
	 * @return integer $status
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * Sets the status
	 *
	 * @param integer $status
	 * @return void
	 */
	public function setStatus($status) {
		$this->status = $status;
	}

}
?>