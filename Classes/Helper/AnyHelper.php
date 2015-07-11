<?php

namespace Nng\Nnfesubmit\Helper;


class AnyHelper {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 * @inject
	 */
	protected $objectManager;


	/**
	 * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 * @inject
	 */
	protected $cObj;
	
	
	/* 
	 *	Old-School piBase-Object erzeugen um alte Plugins zu initialisieren
	 *
	 */
	 
	function piBaseObj () {
		$piObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\TYPO3\CMS\Frontend\Plugin\AbstractPlugin');
		$piObj->cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer');
		return $piObj;
		/*
		$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\TYPO3\CMS\Extbase\Object\ObjectManager');
		$configurationManager = $objectManager->get('Tx_Extbase_Configuration_ConfigurationManager');
		$piObj = $objectManager->create('\\TYPO3\\CMS\\Frontend\\Plugin\\AbstractPlugin');
		$piObj->cObj = $configurationManager->getContentObject();
		*/
		return $piObj;
	}
	
	
	function setPageTitle ( $titleStr ) {
		$GLOBALS['TSFE']->page['title'] = $titleStr;
		$GLOBALS['TSFE']->indexedDocTitle = $titleStr;
	}
		
	/* --------------------------------------------------------------- 
		Schlüssel erzeugen zur Validierung einer Abfrage
	*/
	

	function createKeyForUid ( $uid, $type = '' ) {
		$extConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['nnfesubmit']);
		if ($extConfig['encodingKey'] == '99grad') die('<h1>nnfesubmit</h1><p>Bitte aendere die "Salting Key" in der Extension-Konfiguration auf etwas anderes als "99grad" (im Extension-Manager auf die Extension klicken)</p>');
		return substr(strrev(md5($type.$uid.$extConfig['encodingKey'])), 0, 8);
	}
	
	function validateKeyForUid ( $uid, $key, $type = '' ) {	
		return self::createKeyForUid( $uid, $type ) == $key;
	}
	
	
	/* --------------------------------------------------------------- */

	
	function trimExplode ( $del, $str ) {
		if (!trim($str)) return array();
		$str = explode($del, $str);
		foreach ($str as $k=>$v) $str[$k] = trim($v);
		return $str;
	}
	
	function trimExplodeArray ( $arr ) {
		if (!$arr) return array();
		if (!is_array($arr)) $arr = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $arr);
		$final = array();
		foreach ($arr as $n) {
			if (trim($n)) $final[] = $n;
		}
		return $final;
	}
		
	/* ==================================================================================================
		Versand der Mail über SWIFT-Mailer
		
		$params	.fromEmail		=>	Absender E-Mail
				.fromName		=>	Absender Name
				.toEmail		=>	Array mit Empfängern der E-Mail (auch kommasep. Liste möglich)
				.subject		=>	Betreff der E-Mail
				.attachments	=>	Array mit vollständigem Pfad der Anhänge
				.inlineImages	=>	Array mit Pfad der Bilder, die als Inline-Image gesendet werden sollen
				.html			=>	HTML-Part der Mail
				.plaintext		=>	Plain-Text Mail
				
		http://docs.typo3.org/TYPO3/CoreApiReference/ApiOverview/Mail/Index.html
		http://swiftmailer.org/docs/messages.html
	*/


	function send_email ( $params, $conf ) {

		$mail = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('t3lib_mail_message'); // TYPO3\\CMS\\Core\\Mail\\MailMessage
		
		$mail->setFrom(array( $params['fromEmail'] => $params['fromName'] ));
		
		$recipients = $this->trimExplodeArray($params['toEmail']);
		$mail->setTo($recipients);
		
		$mail->setSubject($params['subject']);
		
		$attachments = $this->trimExplodeArray($params['attachments']);
		foreach ($attachments as $path) {
			$attachment = \Swift_Attachment::fromPath($path);
			$mail->attach($attachment);
		}
		
		$inlineImages = $this->trimExplodeArray($params['inlineImages']);
		$html = $params['html'];
		$plaintext = $params['plaintext'] ? $params['plaintext'] : $this->html2plaintext($params['html']);
		
		
		foreach ($inlineImages as $img) {
			$cid = $mail->embed(\Swift_Image::fromPath($img));
			$html = str_replace( array(
					'http://'.$params['domain'].'/'.$img,
					'https://'.$params['domain'].'/'.$img,
					\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL').$img,
					$img
				), $cid, $html);
		}

		if ($html) {
			$mail->setBody($html, 'text/html');
			$mail->addPart($plaintext, 'text/plain');	
		} else {
			$mail->setBody($plaintext, 'text/plain');
		}
		
		$mail->setReturnPath( $params['returnPath_email'] );
		
		$sent = $mail->send();
		if (!$sent) {
			$fp = fopen('mail_error.log', 'a');
			$to = join(',', $recipients);
			fputs($fp, date('d.m.Y H:i:s')." {$to}\n\n\n");
			fclose($fp);
			
			$helpMail = $this->params['errorMail_email'];
			$mail->setReturnPath( $helpMail );
			$mail->setTo( $helpMail );
			$mail->setSubject('Mailversand: FEHLER!');
			$mail->send();
		}
	}
	
	
	/* --------------------------------------------------------------- */

	
	public function generateFallbackPathsForPartials ( $tmplPath, $num = 4 ) {
		$paths = array();			
		$tmplPathParts = explode('/', $tmplPath);
		for ($i = 1; $i < $num; $i++) $paths[] = join('/', array_slice($tmplPathParts,0,-$i)).'/Partials/';
		$paths[] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('nnfesubmit').'Resources/Private/Partials/';
		return $paths;
	}
	
	function renderTemplate ( $path, $vars, $flattenVars = false, $pathPartials = null, $doubleRender = false ) {
		
		if (!$path || !file_exists($path)) return '';
		
		$view = $this->objectManager->get('TYPO3\\CMS\\Fluid\\View\\StandaloneView');
		$view->setTemplatePathAndFilename($path);
		
		$view->setPartialRootPaths( $pathPartials ? array($pathPartials) : $this->generateFallbackPathsForPartials($path) );
		
		if ($flattenVars) {
			if ($vars) $view->assignMultiple($vars);
		} else {
			$view->assign('data', $vars);
		}
		
		$html = $view->render();
		
		if ($doubleRender) {
			$view->setTemplateSource($html);
			$html = $view->render();
		}
		
		return $html;
	}
	
	
	function renderTemplateSource ( $template, $vars, $pathPartials = null ) {
		
		if (!$template) return '';
		
		if (strpos($template, '{namespace') === false) {
			$template = '{namespace VH=NNGrad\Nnfesubmit\ViewHelpers}'.$template;
		}
		
		$view = $this->objectManager->get('\TYPO3\CMS\Fluid\View\StandaloneView');		
		$view->setTemplateSource($template);
		$view->setPartialRootPaths( $pathPartials ? array($pathPartials) : $this->generateFallbackPathsForPartials($path) );
		$view->assignMultiple( $vars );
		$html = $view->render();
		
		return $html;
	}
	
	
	public function renderTypoScript ( $type, $setup ) {
		return $this->cObj->cObjGetSingle( $type, $setup );
	}

	/* --------------------------------------------------------------- 
		NOTICE, ERROR, WARNING, OK
		$this->anyHelper->addFlashMessage('so,so', 'ja ja');

	*/
	
	function addFlashMessage ( $title = '', $text = '', $type = 'OK') {
		
		$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\TYPO3\CMS\Extbase\Object\ObjectManager');
		$controllerContext = $objectManager->create('\TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext');
		$controllerContext->getFlashMessageQueue()->enqueue(
			$objectManager->get( '\TYPO3\CMS\Core\Messaging\FlashMessage', $text, $title, constant('\TYPO3\CMS\Core\Messaging\FlashMessage::'.$type), true )
		);
	}
	
	function renderFlashMessages () {
		
		$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\TYPO3\CMS\Extbase\Object\ObjectManager');
		$controllerContext = $objectManager->create('\TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext');
		if (count($controllerContext->getFlashMessageQueue()->getAllMessages())) {
			return $this->renderTemplate('typo3conf/ext/nnfesubmit/Resources/Private/Templates/FlashMessages.html', array() );
		}
		return '';
	}
		
	/* --------------------------------------------------------------- */
	
	function cleanTexteditorInput ( $html ) {
		if (is_array($html)) return $html;
		$html = strip_tags( $html, '<p><b><span><a>');
		$html = preg_replace('/<p(.*?)>(.*?)<\/p>/', '<p>$2</p>', $html);
		$html = str_replace('&nbsp;', ' ', $html);
		$html = str_replace('> <', '><', $html);
		$html = preg_replace('/(\s+)/', ' ', $html);
		$html = str_replace(array('<p></p>', '</p><br /><p>', '</p><br><p>'), '<br />', $html);
		$html = preg_replace('/<span(.*?)>(.*?)<\/span>/', '$2', $html);
		//$html = preg_replace('/<span(.*?)style="font-weight:(.*?)bold(.*?)">(.*?)<\/span>/', '<b>$4</b>', $html);
		return $html;
	}
	
	function getSuffix ( $file ) {
		if (!$file) return false;
		return strtolower(pathinfo($file, PATHINFO_EXTENSION));
	}
	
	function cloneArray( $arr ) {
		$ret = array();
		foreach ($arr as $k=>$v) $ret[$k] = $v;
		return $ret;
	}
	
	function cleanIntList ( $str='', $returnArray = null ) {
		$is_arr = is_array($str);
		if (trim($str) == '') return (($returnArray == null && !$is_arr) || $returnArr === false) ? '' : array();
		if ($is_arr) $str = join(',', $str);
		$str = $GLOBALS['TYPO3_DB']->cleanIntList( $str );
		if (($returnArray == null && !$is_arr) || $returnArr === false) return $str;
		return explode(',', $str);
	}
			
	function get_obj_by_attribute ( &$data, $key, $val = false, $retArr = false ) {
		$ref = array();
		foreach ($data as $k=>$v) {
			if ($val === false) {
				if ($retArr === true) {
					if (!is_array($ref[$v[$key]])) $ref[$v[$key]] = array();
					$ref[$v[$key]][] = &$data[$k];
				} else {
					$ref[$v[$key]] = &$data[$k];
				}
			} else {
				$ref[$v[$key]] = $val === true ? $v : $v[$val];
			}
		}
		return $ref;
	}
	
	function html2plaintext ( $html ) {
		return strip_tags($html);
	}
	
	// --------------------------------------------------------------------------------------------------------------------
	// Weiterleitung über http-Header location:...
	
	static function httpRedirect ( $pid = null, $vars = array() ) {
		if (!$pid && $_GET['id']) $pid = $_GET['id'];
		if (!$pid) $pid = $GLOBALS['TSFE']->id;	
		$pi = self::piBaseObj();
		$link = $pi->pi_getPageLink($pid, '', $vars); 
		$link = \TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl($link);
		header('Location: '.$link); 
		exit(); 
	}


	static function redirect ( $action = null, $controller = null, $extension = null, $vars = array() ) {
		$gp = $_GET;
		$arr = array(
			'tx_nnfesubmit_nnfesubmit' => array(
				'action' => $action,
				'controller' => $controller ? $controller : 'Main'
		));
		\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($gp, $arr);
		\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($gp, array('tx_nnfesubmit_nnfesubmit'=>$vars));
		unset($gp['id']);
		self::httpRedirect( null, $gp );
	}


	
}

?>