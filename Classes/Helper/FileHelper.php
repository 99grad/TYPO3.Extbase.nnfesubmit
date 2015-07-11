<?php

namespace Nng\Nnfesubmit\Helper;


class FileHelper implements \TYPO3\CMS\Core\SingletonInterface {
	

	static $TYPES = array(
		'image'		=> array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'tif', 'tiff'),
		'document'	=> array('doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'ai', 'indd', 'txt'),
		'pdf'		=> array('pdf'),
		'forbidden'	=> array('php', 'php4', 'php5', 'pl', 'cgi')
	);
	
	
	static function exists ( $filename = null ) {
		return file_exists($filename);
	}
	
	static function isForbidden ( $filename = null ) {
		if (!$filename) return false;
		return in_array(self::suffix($filename), self::$TYPES['forbidden'] );
	}
	
	static function suffix ( $filename = null ) {
		if (!$filename) return false;
		return strtolower(array_pop(explode('.',$filename)));
	}
	
	
	static function filetype ( $filename = null ) {
		if (!$filename) return false;
		$suffix = self::suffix($filename);
		foreach (self::$TYPES as $k=>$arr) {
			if (in_array($suffix, $arr)) return $k; 
		}
		return 'other';
	}
	
	
	static function sendToBrowser ( $path, $download = false, $filename = null ) {

		$isFile = realpath( $path ) !== '';
		if ($filename) $suffix = pathinfo($filename, PATHINFO_EXTENSION);
		if (!$suffix && $isFile) $suffix = pathinfo($path, PATHINFO_EXTENSION);
		if (!$suffix) $suffix = 'txt';
		if (!$isFile && !$filename) $filename = 'download';		
		if (!$filename) $filename = $isFile ? basename($path) : 'download.'.$suffix;

		ob_end_clean();
		if ($download) header('Content-disposition: attachment; filename='.$filename);
		header('Content-type: application/'.$suffix);
		
		if ($isFile) {
			//header('Content-Length: ' . filesize($path));
			readfile( $path );
		} else {
			//header('Content-Length: ' . strlen($path));
			echo $path;
		}
		die();
	}

	
}

?>