<?php

namespace Nng\Nnfesubmit\Controller;

class EidController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {


    /**
     * @var \Nng\Nnfesubmit\Helper\AnyHelper
     * @inject
     */
    protected $anyHelper;


 	/**
     * @var \Nng\Nnfesubmit\Controller\MainController
     * @inject
     */
    protected $mainController;



	public function initializeAction() {
		$this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\TYPO3\CMS\Extbase\Object\ObjectManager');
		$this->anyHelper = $this->objectManager->create('\Nng\Nnfesubmit\Helper\AnyHelper');
		$this->mainController = $this->objectManager->create('\Nng\Nnfesubmit\Controller\MainController');
	}
	
     /* 
     *	Wir von EidDispatcher.php aufgerufen, wenn in URL &eID=nnfesubmit übergeben wurde
     *
     *	Controller der aufgerufenen Aktionen können eine Flash-Message generieren, z.B. per
     *	$this->anyHelper->addFlashMessage('Text', 'Title', 'OK');
     *
     *	Gibt es keine Nachrichten, wird auf Startseite weitergeleitet
     *
     */
	
	function processRequestAction () {
	
		$_GP = $this->request->getArguments();
		
		$action = $_GP['action'];
		$uid = (int) $_GP['uid'];
		$key = $_GP['key'];
		
		// Validierung der Aktion
		if ($action) {
			if (!$this->anyHelper->validateKeyForUid($uid, $key)) {
				die("Validierung fehlgeschlagen.");
			}
		}
	
		if ($action == 'approve') {
		
			// Klick auf "Bestätigen" in Admin-Email 
			$this->mainController->approveAction( $uid );
		} else if ($action == 'remove') {
		
			// Klick auf "Löschen" in E-Mail
			$this->mainController->removeAction( $uid );		
		} else if ($action == 'edit') {
		
			// Klick auf "Bearbeiten" aus der E-Mail
			return $this->mainController->editAction( $uid );
		} else if ($action == 'feedit') {

			// Klick auf "Bearbeiten" eines bestehenden Datensatzes im Frontend
			$message = $this->mainController->feeditAction( $_GP );
		} else if ($action == 'fedelete') {

			// Klick auf "Löschen" eines bestehenden Datensatzes im Frontend
			$message = $this->mainController->feDeleteAction( $_GP );
		}

		if ($message = $this->anyHelper->renderFlashMessages()) return $message;
		//$this->anyHelper->httpRedirect( 0 );
		
	}


}


?>