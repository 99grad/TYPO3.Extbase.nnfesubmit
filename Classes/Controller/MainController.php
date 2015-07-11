<?php

namespace Nng\Nnfesubmit\Controller;


class MainController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {


 	/**
     * @var \Nng\Nnfesubmit\Domain\Repository\EntryRepository
     * @inject
     */
    protected $entryRepository;
    
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     * @inject
     */
    protected $objectManager;
    
    /**
     * @var \Nng\Nnfesubmit\Helper\AnyHelper
     * @inject
     */
    protected $anyHelper;

    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder
     * @inject
     */
    protected $uriBuilder;

    
    /**
     * @var \Nng\Nnfesubmit\Domain\Service\TableService
     * @inject
     */
    protected $tableService;
    
    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     * @inject
     */
    protected $persistenceManager;
	
	/**
     * @var \TYPO3\CMS\Core\Utility\File\BasicFileUtility
     * @inject     
     */
    protected $basicFileFunctions;
    
    /**
     * @var \Nng\Nnfesubmit\Helper\FileHelper
     * @inject
     */
	protected $fileHelper;
	
	/**
	* @var \Nng\Nnfesubmit\Utilities\SettingsUtility
	* @inject
	*/
	protected $settingsUtility;
	
	/**
	* @var \Nng\Nnfesubmit\Domain\Repository\FeUserRepository
	* @inject
	*/
	protected $feUserRepository;
	
	
	
	
	/**
	* Initializes the current action
	*
	* @return void
	*/
	
	public function initializeAction() {
		
		$type = $this->settings['tablename'];		
		$gp = (array) $this->settings['default'];
		
		\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($gp, (array) $this->settings[$type]['default']);
		$gp = array_merge($gp, $this->request->getArguments());

		$this->_GP = $gp;
		$this->feUser = $GLOBALS['TSFE']->fe_user->user;
	}

	public function initializeObject() {
	
	}
	
	public function initializeView() {
	
		$this->view->assign( 'gp', $this->_GP );
		$this->view->assign( 'feUser', $this->feUser );

		$type = $this->settings['tablename'];		
		$settings = $this->settings[$type];
		$tmplPath = $settings['templatePath'];

		$this->view->assign('extSettings', $this->settings);
		$this->view->assign('settings', $settings);
		
		$this->view->setPartialRootPaths( $this->anyHelper->generateFallbackPathsForPartials($tmplPath) );		
		
		$this->includeJsCss( $settings );
		
		$paths = array();			
		$paths[] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('nnfesubmit').'Resources/Private/Partials/';

	}
	
	
	public function includeJsCss ( $settings ) {

		foreach ($settings['includeJS'] as $k=>$v) {
			$GLOBALS['TSFE']->additionalHeaderData['nnfesubmit'.md5($v)] = '<script type="text/javascript" src="'.$v.'"></script>';
		}
		foreach ($settings['includeCSS'] as $k=>$v) {
			$GLOBALS['TSFE']->additionalHeaderData['nnfesubmit'.md5($v)] = '<link rel="stylesheet" type="text/css" media="all" href="'.$v.'" />';
		}
	}
	
	
	public function GP_baseVars( $vars = null ) {
	
		$gpVars = $this->_GP;
		
		$baseVars = array(
			'pluginUid' => intval($gpVars['pluginUid']),
			'returnUrl'	=> $gpVars['returnUrl']
		);

		\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule( $baseVars, (array) $vars );

		return $baseVars;
	}
	
	/**
	 * action list
	 *
	 * @return void
	 */

	public function mainAction() {
		$entries = $this->entryRepository->findAll();
		$this->view->assign('entries', $entries);
	}



	/**
	 * action showForm
	 *
	 * @return void
	 */

	public function showFormAction() {
		
		$type = $this->settings['tablename'];		
		$settings = $this->settings[$type];
		$extName = $settings['extension'];
		$tmplPath = $settings['templatePath'];

		$this->view->setTemplatePathAndFilename($tmplPath.'Form.html');
		
		$gpVars = $this->_GP;
		$data = array();


		// Editieren eines Eintrages
		if ($gpVars['key'] && $gpVars['entry']) {
						
			if ($this->anyHelper->validateKeyForUid($gpVars['entry'], $gpVars['key'])) {
				if ($entry = $this->entryRepository->findByUid( $gpVars['entry'] )) {
					$data = json_decode($entry->getData(), true);
					$data = array_merge($data, array(
						'_key'		=> $gpVars['key'],
						'_entry' 	=> $gpVars['entry']
					));
				}
			}
			
			if ($this->anyHelper->validateKeyForUid($gpVars['entry'], $gpVars['adminKey'], 'admin')) {
				$data = array_merge($data, array('_adminKey' => $gpVars['adminKey']));
			}
			
		}
		
		$this->insertViewVariablesFromMapper( $extName );
		\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule( $gpVars, $data );
		$this->view->assign( 'gp', $gpVars );

		if ($gpVars['finalize']) {
			 //$this->redirect('finalize', 'Main', null, $gpVars);
			 $this->finalizeAction();
		}
	}
	
	
	/**
	 * action showForm
	 *
	 * @return void
	 */

	public function validateFormAction() {

		$gpVars = $this->_GP;
		$type = $this->settings['tablename'];		
		$settings = $this->settings[$type];
		$extName = $settings['extension'];
		$tmplPath = $settings['templatePath'];

		$uploads = array();
		
		// Validierung des Formulars
		// z.B. ... validation.personinfo.email = required,email

		$errorData = array();
		if ($ref = $settings['validation']) {
			foreach ($ref as $field=>$types) {
				$types = $this->anyHelper->trimExplode(',', $types);
				foreach ($types as $type) {
					
					$isMedia = $settings['media'][$field];
					$checkfield = $isMedia ? $field.'_upload' : $field;
					
					$fieldValue = $gpVars[$checkfield];
					$altFieldValue = $gpVars[$field]; 
					
					if ($isMedia && is_array($fieldValue) && isset($fieldValue['name'])) {
						$fieldValue = $fieldValue['name'];
					}
					
					if (!$fieldValue && $altFieldValue) $fieldValue = $altFieldValue;
					
					$validator = '\\Nng\\Nnfesubmit\\Validation\\'.ucfirst($type).'Validator';
					if (class_exists($validator)) {
						// Eigener Validator?
						$checker = $this->objectManager->create($validator);
						if ($errors = $checker->validate($fieldValue, $checkfield, $gpVars)->getErrors()) {
							$errorData[$field][$type] = 1;
							$errorData[$checkfield][$type] = 1;
						}
					} else {
						// Typo3 interner Validator
						$validator = '\\TYPO3\\CMS\\Extbase\\Validation\\Validator\\'.ucfirst($type).'Validator';
						$checker = $this->objectManager->create($validator);
						if ($errors = $checker->validate($fieldValue)->getErrors()) {
							$errorData[$field][$type] = 1;
							$errorData[$checkfield][$type] = 1;
						}
					}
					
				}
			}
		}
		
		// Spezieller Validator enthalten?
		$mapperName = '\\Nng\\Nnfesubmit\\Mapper\\'.\TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($extName).'Mapper';
		if (class_exists($mapperName)) {
			$mapper = $this->objectManager->create($mapperName);
			\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule( $errorData, $mapper->validate($gpVars, $settings));
		}

		// Dateien hochgeladen?
		if ($files = $_FILES['tx_nnfesubmit_nnfesubmit']) {

			foreach ($files['name'] as $k=>$file) {
				
				if ($files['size'][$k] > $this->settings['maxUploadFileSize']*1000) {
					$errorData[$k]['filesize'] = 1;
				} else if (!$this->fileHelper->isForbidden($file) && !$errorData[$k]) {
					$file = \TYPO3\CMS\Core\Utility\File\BasicFileUtility::cleanFileName(trim($file));
					$unique_filename = $this->basicFileFunctions->getUniqueName($file, 'uploads/tx_nnfesubmit/');
					if (\TYPO3\CMS\Core\Utility\GeneralUtility::upload_copy_move($files['tmp_name'][$k], $unique_filename)) {
						$kn = $k;
						if (substr($kn, -7) == '_upload') $kn = substr($kn,0,-7);
						$uploads[$kn] = basename($unique_filename);
					}
				} else if ($file) {
					$errorData[$k]['invalid_filetype'] = 1;
				}
			}
		}
		
		$gpVars = array_merge( $gpVars, $uploads);
		foreach ($gpVars as $k=>$v) {
			$gpVars[$k] = $this->anyHelper->cleanTexteditorInput($v);
		}
		
		$this->view->assign( 'gp', $gpVars );
		$this->view->assign( 'errors', $errorData);
		$this->insertViewVariablesFromMapper( $extName );
				
		$this->_GP = $gpVars;

		if ($errorData) {
			$this->view->setTemplatePathAndFilename($tmplPath.'Form.html');
		} else if ($settings['showConfirmationForm']) {
			$this->showConfirmationFormAction();
		} else {
			$this->finalizeAction();
		}
	}
	
	
	/**
	 * action showForm
	 *
	 * @return void
	 */

	public function showConfirmationFormAction() {
		
		$gpVars = $this->_GP;
		
		$type = $this->settings['tablename'];		
		$settings = $this->settings[$type];
		$extName = $settings['extension'];
		$tmplPath = $settings['templatePath'];
		
		$this->insertViewVariablesFromMapper( $extName );		
		$this->view->setTemplatePathAndFilename($tmplPath.'FormConfirm.html');
	}
	
	
	/**
	 * action showForm
	 *
	 * @return void
	 */

	public function finalizeAction() {
	
		$gpVars = $this->_GP;

		unset($gpVars['finalize']);
		$gpVars['_feUserUid'] = $GLOBALS['TSFE']->fe_user ? $GLOBALS['TSFE']->fe_user->user['uid'] : '';
		
		$this->view->assign('entries', $entries);
		
		$type = $this->settings['tablename'];		
		$settings = $this->settings[$type];
		$extName = $settings['extension'];
		$tmplPath = $settings['templatePath'];

		if (!($email = $settings['admin']['toEmail'])) $email = $this->settings['adminEmail'];
		$sendToAdmin = $email && $settings['admin']['enabled'] !== '0';
		
		$entry = false;
		if ($gpVars['_key'] && $gpVars['_entry']) {
			if ($entry = $this->entryRepository->findByUid( $gpVars['_entry'] )) {
			
				// Admin war am Werk (erkennbar an gültiger _adminKey) -> Änderungen direkt freischalten
				if (
					(!$sendToAdmin && $this->anyHelper->validateKeyForUid($gpVars['_entry'], $gpVars['_key'])) || 
					($sendToAdmin && $this->anyHelper->validateKeyForUid($gpVars['_entry'], $gpVars['_adminKey'], 'admin'))) {

					$entry->setData( json_encode($gpVars) );
					$this->persistenceManager->persistAll();
					$this->approveAction( $gpVars['_entry'] );
					return $this->anyHelper->renderFlashMessages();
				}
			}
		}
		
		// Ein neuer Eintrag...
		if (!$entry) {
			$entry = new \Nng\Nnfesubmit\Domain\Model\Entry();
		}

		$entry->setExt( $type );
		$entry->setData( json_encode($gpVars) );

		$this->entryRepository->add( $entry );
		$this->persistenceManager->persistAll();
		

		$srcUid 		= $entry->getSrcuid();
		$validationUid 	= $entry->getUid();
		$validationKey 	= $this->anyHelper->createKeyForUid( $validationUid );
		$adminKey 		= $this->anyHelper->createKeyForUid( $validationUid, 'admin' );
		
		$mapperVars 	= (array) $this->insertViewVariablesFromMapper( $extName );

		// E-Mail an Admin zur Freigabe senden?
		if ($sendToAdmin) {
		
			// Link zum Löschen des Eintrags
			$adminKey = $this->anyHelper->createKeyForUid( $validationUid, 'admin' );

			$data = array_merge_recursive( $gpVars, $mapperVars, array(
				'feUser'			=> $this->feUser,
				'validationKey'		=> $validationKey,
				'validationUid'		=> $validationUid,
				'baseUrl'			=> $GLOBALS['TSFE']->baseUrl,
				'pageUid'			=> $settings['editPid'] ? $settings['editPid'] : $GLOBALS['TSFE']->id,
				'settings'			=> $settings
			));
			if ($adminKey) $data['adminKey'] = $adminKey;
			
			$emailTemplate = $srcUid ? 'EmailAdminFeedit.html' : 'EmailAdmin.html';
			$html = $this->anyHelper->renderTemplate ( 
				$tmplPath.$emailTemplate, 
				$data, true, null, false
			);

die($html);

			$this->anyHelper->send_email(array_merge($settings['admin'], array(
				'html'		=> $html
			)));
			
			if ($settings['thanksPid']) {
				$this->anyHelper->httpRedirect( $settings['thanksPid'] );
			} else {
				$this->anyHelper->redirect('thanks', null, null, $this->GP_baseVars(array('type'=>$type)));
			}
			
		} else {
		
			// Kein Admin im Spiel, direkt freischalten
			$this->approveAction( $validationUid );
		}
				
	}
	
	/**
	 * action approveAction
	 *
	 * @return void
	 */
	 
	public function approveAction ( $uid ) {
	
		if (!($entry = $this->entryRepository->findByUid( $uid ))) return $this->anyHelper->addFlashMessage('Kein Eintrag gefunden', 'Wurde der Link evt. schon mal geklickt?', 'ERROR');
		
		$gpVars = $this->_GP;
		$type = $entry->getExt();
		$settings = $this->settings[$type];
		$extName = $settings['extension'];
		$tmplPath = $settings['templatePath'];
		
		$data = json_decode($entry->getData(), true);
		
		if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($extName)) return $this->anyHelper->addFlashMessage('Extension '.$extName.' nicht geladen', 'Der Datensatz konnte keiner Extension zugeordnet werden', 'ERROR');
		
		// Variablen mit eigenem Mapper mappen, falls vorhanden. Sonst Default-Mapper nehmen
		$mapperName = '\\Nng\Nnfesubmit\\Mapper\\'.\TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($extName).'Mapper';
		if (class_exists($mapperName)) {
			$mapper = $this->objectManager->create($mapperName);
			$obj = $mapper->map($data, $settings);
		} else {
			$mapper = $this->objectManager->create('\Nng\Nnfesubmit\Mapper\AbstractMapper');
			$obj = $mapper->map($data, $settings);
		}
		
		// War es ein bestehender Eintrag? Dann enthält $srcUid jetzt die ursprüngliche UID der fremden Tabelle
		$srcUid = $entry->getSrcuid();
		$insertData = array();
		
		//*
		// ... und ab in die Datenbank damit
		if ($obj) {
			if ($insertData = $mapper->write( $obj, $settings, $srcUid )) {
				$this->entryRepository->remove($entry);
				$mapper->map_mm( $obj, $settings );
				$this->persistenceManager->persistAll();
			} else {
				return $this->anyHelper->addFlashMessage('Fehler beim Schreiben', 'Der Datensatz konnte nicht in die Datenbank geschrieben werden.', 'ERROR');
			}
		}
		//*/
		
		// Seiten-Cache löschen für angegebene Seiten
		if ($pidList = $this->anyHelper->trimExplode(',', $settings['clearCachePid'])) {
			$TCE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\TYPO3\CMS\Core\DataHandling\DataHandler');
			$TCE->admin = 1;
		
			// Brutal, aber nicht anders lösbar
			$TCE->clear_cacheCmd('all');
			
			// Das ging leider nicht:
			foreach ($pidList as $pid) {
				$TCE->clear_cacheCmd($pid);
			}
		}

		$mapperVars = $this->insertViewVariablesFromMapper( $extName, $data );
		
		// Nachricht an Admin über erfolgreichen Eintrag?
		// E-Mail an Admin zur Freigabe senden
		
		$notificationType = $srcUid ? 'feedit' : 'upload';
		
		if ($settings['notification'] && $settings['notification'][$notificationType]['enabled'] !== '0') {
		
			// Daten des Ersteller (fe_user) laden, damit in der Mail der Name eingefügt werden kann
			$feUser = $this->feUserRepository->findOneByUid( $data['_feUserUid'] );
			
			// Link zum Löschen des Eintrags
			$adminKey 			= $this->anyHelper->createKeyForUid( $insertData['uid'], 'admin' );
			$deleteLinkParams 	= $this->getFeDeleteLinkParams(array_merge( $data, $insertData, array('type'=>$type, 'adminKey'=>$adminKey)));
			$editLinkParams 	= $this->getEditLinkParams(array_merge( $data, $insertData, array('type'=>$type, 'adminKey'=>$adminKey)));

			$data = array_merge_recursive( $data, $mapperVars, array(
				'feUser'			=> $feUser,
				'baseUrl'			=> $GLOBALS['TSFE']->baseUrl,
				'settings'			=> $settings,
				'deleteLinkParams'	=> $deleteLinkParams,
				'editLinkParams'	=> $editLinkParams
			));

			$html = $this->anyHelper->renderTemplate ( 
				$tmplPath.'EmailNotification'.ucfirst($notificationType).'.html', 
				$data, true, null, false
			);
						
			$this->anyHelper->send_email(array_merge($settings['notification'][$notificationType], array(
				'html'		=> $html
			)));
		}

		if ($srcUid) {
			$this->anyHelper->addFlashMessage('Eintrag geändert', 'Ihre Änderungen wurden gespeichert.');
		} else {
			$this->anyHelper->addFlashMessage('Eintrag übernommen', 'Die Daten wurden erfolgreich in die Datenbank eingetragen.');		
		}
		
		if ($settings['approvedPid']) {
			$this->anyHelper->httpRedirect( $settings['approvedPid'] );
		} else if ($settings['showApprovedPage'] !== '0') {
			// Weiterleitung auf "Beitrag freigeschaltet"-Seite
			$this->anyHelper->redirect('approved', null, null, $this->GP_baseVars(array('type'=>$type)));
		} else if ($gpVars['returnUrl']) {
			// Weiterleiten auf ursprüngliche Seite, in die Beitrag eingefügt wird oder Formular ausgefüllt wurde
			$this->anyHelper->httpRedirect( $gpVars['returnUrl'] );
		} else if ($data['returnUrl']) {
			$this->anyHelper->httpRedirect( $data['returnUrl'] );	
		} else {
			// Weiß nicht, wohin. Also hier bleiben.
			$this->anyHelper->httpRedirect();
		}
			
	}
	
	
	
	/**
	 * action thanksAction
	 *
	 * @return void
	 */
	 
	public function thanksAction () {
		$args = $this->request->getArguments();
		$type = $args['type'];
		$settings = $this->settings[$type];
		$extName = $settings['extension'];
		$tmplPath = $settings['templatePath'];
		
		$this->insertViewVariablesFromMapper( $extName );
		if (!($tmpl = $settings['thanksTemplate'])) $tmpl = 'Thanks.html'; 
		$this->view->setTemplatePathAndFilename($tmplPath.$tmpl);
	}
	
	
	/**
	 * action approvedAction
	 *
	 * @return void
	 */
	 
	public function approvedAction () {
		$args = $this->request->getArguments();
		$type = $args['type'];
		$settings = $this->settings[$type];
		$extName = $settings['extension'];
		$tmplPath = $settings['templatePath'];
		
		$this->anyHelper->addFlashMessage('Eintrag übernommen', 'Die Daten wurden erfolgreich in die Datenbank übernommen.');
		$this->insertViewVariablesFromMapper( $extName );
		
		if (!($tmpl = $settings['approvedTemplate'])) $tmpl = 'Approved.html'; 
		$this->view->setTemplatePathAndFilename($tmplPath.$tmpl);
	}
	
	
	/**
	 * action insertViewVariablesFromMapper
	 * Falls nötig, kann für jede Extension im Mapper eine Function "insertViewVariables" definiert werden um z.B. 
	 * ein Kategorien-Baum oder andere Variablen in die View zu injecten.
	 *
	 * @return void
	 */
	 
	public function insertViewVariablesFromMapper( $extName, $gp = null ) {
		if ($mapper = $this->createMapperObject($extName)) {
			$view = (array) $this->view;
			return $mapper->insertViewVariables($view, $this->settings[$extName], $this->settings, $gp ? $gp : $this->_GP);
		}
	}
	
	
	public function createMapperObject ( $extName ) {
		$mapperName = '\\Nng\\Nnfesubmit\\Mapper\\'.\TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($extName).'Mapper';
		if (class_exists($mapperName)) return $this->objectManager->create($mapperName);
		return false;
	}
	
	
	/**
	 * action removeAction
	 *
	 * @return void
	 */
	 
	public function removeAction ( $uid ) {
	
		if (!$entry = $this->entryRepository->findByUid( $uid )) return $this->anyHelper->addFlashMessage('Kein Eintrag gefunden', 'Wurde der Link evt. schon mal geklickt? Wurde Beitrag bereits in die Datenbank geschrieben, kann er jetzt nur noch über das Backend gelöscht werden.', 'ERROR');

		$type = $entry->getExt();
		$settings = $this->settings[$type];
		$extName = $settings['extension'];
		
		$data = json_decode($entry->getData(), true);

		if ($media = $settings['media']) {
			foreach ($media as $k=>$v) {
				if (is_file($data[$k])) @unlink($data[$k]);
			}
		}

		$this->entryRepository->remove($entry);
		$this->persistenceManager->persistAll();		
		$this->anyHelper->addFlashMessage('Eintrag gelöscht', 'Die Daten wurden erfolgreich aus der Datenbank gelöscht.');
		
		if ($url = $data['returnUrl']) {
			$this->anyHelper->httpRedirect($url);
		}
	}
	
	/**
	 * action editAction
	 *
	 * @return void
	 */
	 
	public function editAction ( $uid ) {
	
		if (!($entry = $this->entryRepository->findByUid( $uid ))) return $this->anyHelper->addFlashMessage('Kein Eintrag gefunden', 'Wurde die Daten bereits in die Datenbank geschrieben? Falls ja, kann der Beitrag nur noch über das Backend bearbeitet werden.', 'ERROR');
		$data = json_decode($entry->getData(), true);
				
		$this->anyHelper->httpRedirect( null, $this->GP_baseVars(array(
			'tx_nnfesubmit_nnfesubmit[key]' 		=> $this->anyHelper->createKeyForUid($uid),
			'tx_nnfesubmit_nnfesubmit[entry]' 		=> $uid,
			'tx_nnfesubmit_nnfesubmit[adminKey]' 	=> $_GET['adminKey'],
			'tx_nnfesubmit_nnfesubmit[pluginUid]' 	=> intval($data['pluginUid']),
			'tx_nnfesubmit_nnfesubmit[returnUrl]'	=> $data['returnUrl']			
		)));
		
	}
	
	/**
	 * action getFormInstance
	 * Gibt das vollständig als HTML gerenderte Formular zurück um es in andere Extension einzubinden.
	 * Dazu existiert ein spezieller ViewHelper. Beispiel befindet sich in Configuration/TypoScript/setup.txt
	 *
	 * @return void
	 */
	 
	public function getFormInstance ( $params ) {
	
		$gp = (array) $_POST['tx_nnfesubmit_nnfesubmit'];
		
		\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule( $gp, (array) $_GET['tx_nnfesubmit_nnfesubmit'] );

		if ($params['_GP']) {
			\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($params['_GP'], $gp);
			$gp = $params['_GP'];
		}

		$this->request = $this->objectManager->get('\TYPO3\CMS\Extbase\Mvc\Request');
		$this->request->setArguments( $gp );
		$this->initializeAction();
		
		$setup = $GLOBALS['TSFE']->tmpl->setup['lib.'];
		$params = $this->settingsUtility->add_ts_setup_dots( $params );
		\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($setup['tx_nnfesubmit_instance.'], $params);
		
		if ($params['settings.'] && $tablename = $params['settings.']['tablename']) {
			\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($setup['tx_nnfesubmit_instance.']['settings.'][$tablename.'.'], $params['settings.']);		
		}
		
		$action = $this->_GP['action'];
		if ($_GET['action']) $action = $_GET['action'];

		if ($action) \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule(
			$setup['tx_nnfesubmit_instance.'], array('switchableControllerActions.'=>array('Main.'=>array('1'=>$action)))
		);
		
		$html = $this->anyHelper->renderTypoScript( $setup['tx_nnfesubmit_instance'], $setup['tx_nnfesubmit_instance.'] );
		return $html;
	}
	
	/**
	 * action getEditLinkParams
	 * Link-Parameter zurückgeben, die zum Editieren eines bestehende Datensatzes einer fremden Tabelle notwendig sind
	 *
	 * @return array
	 */
	 
	public function getEditLinkParams ( $params ) {
		$uid = intval( $params['uid'] );
		$this->_GP = $params;
		return $this->GP_baseVars( array(
			'eID'		=> 'nnfesubmit', 
			'action'	=> 'feedit', 
			'type'		=> $params['type'],
			'adminKey'	=> $params['adminKey'],
			'uid'		=> $uid,
			'key'		=> $this->anyHelper->createKeyForUid( $uid )
		), $params);
	}
	
	

	
	/**
	 * action feeditAction
	 * Bearbeiten eines bestehenden Datensatzes aus fremder Tabelle starten. 
	 * Dazu Kopie in tx_nnfesubmit_domain_model_entry anlegen und zum Formular weiterleiten
	 *
	 * @return array
	 */
	public function feeditAction ( $params ) {

		$this->settings = $this->settingsUtility->getSettings();
		
		$uid = intval( $params['uid'] );
		$type = $params['type'];
		$settings = $this->settings[$type];
		$extName = $settings['extension'];
		
		// Prüfen, ob Datensatz in fremder Tabelle exisitert
		if (!($data = $this->tableService->getEntry($settings, $uid))) {
			return $this->anyHelper->addFlashMessage('Kein Eintrag gefunden', "In der Tabelle {$settings['tablename']} wurde kein Datensatz mit der uid={$uid} gefunden.", 'ERROR');
		}
		
		// Datensatz zum Bearbeiten anlegen
		if (!($entry = $this->entryRepository->getEntryForExt( $uid, $type ))) {
			$entry = $this->objectManager->get('\Nng\Nnfesubmit\Domain\Model\Entry');
			$this->entryRepository->add($entry);
			$this->persistenceManager->persistAll();
			
			//$unique_filename = $this->basicFileFunctions->getUniqueName($file, 'uploads/tx_nnfesubmit/');
			//if (\TYPO3\CMS\Core\Utility\GeneralUtility::upload_copy_move($files['tmp_name'][$k], $unique_filename)) {
		}

		// Media zurück in den Ordner uploads/tx_nnfesubmit kopieren
		$media = $settings['media'];
		foreach ($media as $k => $path) {
			if ($data[$k]) {
				$unique_filename = $this->basicFileFunctions->getUniqueName(trim(basename($data[$k])), 'uploads/tx_nnfesubmit/');
				\TYPO3\CMS\Core\Utility\GeneralUtility::upload_copy_move($path.$data[$k], $unique_filename);
				if (!file_exists($unique_filename)) {
					$this->anyHelper->addFlashMessage ( 'Datei nicht kopiert', 'Die Datei '.$data[$k].' konnte nicht kopiert werden.', 'WARNING');
				}
			}
		}

		//$entry->setFeUser( $GLOBALS['TSFE']->fe_user->user['uid'] );
		$entry->setCruserId( $GLOBALS['TSFE']->fe_user->user['uid'] );
		$entry->setSrcuid( $uid );
		$entry->setExt( $type );
		$entry->setData( json_encode($data) );

		$this->entryRepository->update($entry);
		$this->persistenceManager->persistAll();
		
		$entryUid = $entry->getUid();
		$newAdminKey = '';
		
		if ($params['adminKey'] && $this->anyHelper->validateKeyForUid($uid, $params['adminKey'], 'admin') ) {
			$newAdminKey = $this->anyHelper->createKeyForUid($entryUid, 'admin');
		}
		
		//http://adhok.99grad.de/index.php?id=17&id=17&nnf%5B193%5D%5Buid%5D=3&cHash=f14da214fc18a7f53b4da7342f3abe64&eID=nnfesubmit&action=feedit&type=nnfilearchive&uid=21&key=02bc7442
		
		$this->anyHelper->httpRedirect( $settings['editPid'], array(
			'nnf' => $params['nnf'],
			'tx_nnfesubmit_nnfesubmit[key]' 		=> $this->anyHelper->createKeyForUid($entryUid),
			'tx_nnfesubmit_nnfesubmit[adminKey]' 	=> $newAdminKey,
			'tx_nnfesubmit_nnfesubmit[entry]' 		=> $entryUid,
			'tx_nnfesubmit_nnfesubmit[pluginUid]' 	=> intval($params['pluginUid']),
			'tx_nnfesubmit_nnfesubmit[returnUrl]'	=> $params['returnUrl']
		));
		
//		\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($entry);
//		$this->editAction( $entry->getUid() );
		die();
		
	}
	
	
	
	/**
	 * action getFeDeleteLinkParams
	 * Link-Parameter zurückgeben, die zum Löschen eines bestehende Datensatzes einer fremden Tabelle notwendig sind
	 *
	 * @return array
	 */
	 
	public function getFeDeleteLinkParams ( $params ) {
		$uid = intval( $params['uid'] );
		$this->_GP = $params;
		return $this->GP_baseVars(array(
			'eID'		=> 'nnfesubmit', 
			'action'	=> 'fedelete',
			'type'		=> $params['type'],
			'adminKey'	=> $params['adminKey'],
			'uid'		=> $uid,
			'pluginUid' => intval($params['pluginUid']),
			'key'		=> $this->anyHelper->createKeyForUid( $uid )
		));
	}
	
	
	
	/**
	 * action feDeleteAction
	 * Löschen eines bestehenden Datensatzes aus fremder Tabelle. 
	 *
	 * @return array
	 */
	 
	public function feDeleteAction ( $params ) {
	
		$this->settings = $this->settingsUtility->getSettings();
		
		$uid = intval( $params['uid'] );
		$type = $params['type'];
		$settings = $this->settings[$type];
		$extName = $settings['extension'];
		$tmplPath = $settings['templatePath'];
		
		// Prüfen, ob Datensatz in fremder Tabelle exisitert
		if (!($data = $this->tableService->getEntry($settings, $uid))) {
			return $this->anyHelper->addFlashMessage('Kein Eintrag gefunden', "In der Tabelle {$settings['tablename']} wurde kein Datensatz mit der uid={$uid} gefunden.", 'ERROR');
		}

		$mapperVars = $this->insertViewVariablesFromMapper( $extName, $params );
		
		//*
		if ($mapper = $this->createMapperObject($extName)) {
			if (!$mapper->delete($data, $settings)) {
				return $this->anyHelper->addFlashMessage('Eintrag konnte nicht gelöscht werden', "Fehler beim Löschen des Eintrags mit der uid={$data['uid']} in Tabelle {$settings['tablename']}", 'ERROR');			
			}
		}
		//*/
		
		
		if ($settings['notification']['fedelete']['enabled'] !== '0') {
		
			$feUser = $this->feUserRepository->findOneByUid( $GLOBALS['TSFE']->fe_user->user['uid'] );
			
			$data = array_merge_recursive( $data, $mapperVars, array(
				'feUser'		=> $feUser,
				'baseUrl'		=> $GLOBALS['TSFE']->baseUrl,
				'settings'		=> $settings
			));

			$html = $this->anyHelper->renderTemplate ( 
				$tmplPath.'EmailNotificationFeDelete.html', 
				$data, true, null, false
			);

			$this->anyHelper->send_email(array_merge($settings['notification']['fedelete'], array(
				'html'		=> $html
			)));
		}
		
		$this->anyHelper->addFlashMessage ( 'Datensatz gelöscht', 'Der Datensatz wurde erfolgreich entfernt.', 'OK');
		
		if ($params['returnUrl']) {
			$this->anyHelper->httpRedirect( $params['returnUrl'] );
		}
		
		$this->anyHelper->httpRedirect( null, array(
			'nnf' => $params['nnf'],
			'tx_nnfesubmit_nnfesubmit[key]' 	=> $this->anyHelper->createKeyForUid($entryUid),
			'tx_nnfesubmit_nnfesubmit[entry]' 	=> $entryUid,
			'tx_nnfesubmit_nnfesubmit[pluginUid]' 	=> intval($params['pluginUid']),
			'tx_nnfesubmit_nnfesubmit[returnUrl]'	=> $params['returnUrl']			
		));
		
		/*
		$this->anyHelper->httpRedirect( null, array(
			'nnf' => $params['nnf']
		));
		*/
	}
	
	public function editEntryAction () {
	
	}
	
	
}
?>