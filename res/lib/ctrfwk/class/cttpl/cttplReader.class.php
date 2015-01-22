<?php
/**
 * Counter Templating Engine Reader Classes
 *
 * @package CounterTemplating
 * @subpackage Engine
 * 
 */

define('cttplFileEncodeNone', 	0);
define('cttplFileEncodeWindows',	1);
define('cttplFileEncodeUnix',	2);
define('cttplFileEncodeMac',		3);

class cttplReader {
	public $name		= '';
	
	public $source;
	public $cttpl;
	
	public $buff		= '';
	public $buffMaxLen	= 0;
	
	public $line		= 1;
	public $lineEnd		= '';
	public $fileEncode	= cttplFileEncodeNone;

	public function getSourceName() {
		return 'unknown source';	
	}
	
	final public function buffGetRest($len=false) {
		if($len === false)
			$len = &$this->buffMaxLen;
			
		if(!$len || $len >= $this->buffLen()) {
			$rest		= $this->buff;
			$this->buff	= '';
			return $rest;		
		} else {
			if( $this->buffLen() > $len) {
				$rest		= substr($this->buff, 0, $len);
				$this->buff	= substr($this->buff, (0 - ($this->buffLen() - $len)));
				return $rest;				
			}
		}
	}
	
	final public function buffFlush($len=false) {
		if($len === false)
			$len = &$this->buffMaxLen;
			
		if(!$len || $len >= $this->buffLen()) {
			print($this->buff);
			$this->buff = '';	
		} else {
			$buffLen = $this->buffLen();		 
			if( $buffLen > $this->buffMaxLen) {
				print(substr($this->buff, 0, ($buffLen - $this->buffMaxLen)));
				$this->buff = substr($this->buff, (0 - $this->buffMaxLen));
			}
		}
	}
	
	final public function buffMatch($word){
		if($this->buffLen() >= strlen($word)) 
			return (substr($this->buff, (0 - strlen($word))) == $word);
		else
			return false;
	}
	
	public function getc() {		
	}
	
	final public function buffLen() {
		return strlen($this->buff);
	}
	
	final public function __destruct() {
			$this->end();
	}
	public function end() {}
}

class cttplReaderFile extends cttplReader {
	final public function __construct($file, $cttpl, $startLine = 1) {
		$this->cttpl	= $cttpl;
		$this->source	= realpath($file);
		$this->file		= &$this->source;
		$this->fp		= null;		
		
		if(file_exists($this->file) && is_readable($this->file)) 
		{
			/* open file pointer */
			$this->fp		= fopen($this->file, 'r');
			
			if($this->fp) {
				/* lock file */
				flock($this->fp, LOCK_SH);
			}				
			
		} else return $this->cttpl->errorHandler(
					/* EMSG */ 'Cannot read the template file "'.$file.'".',
								cttplErrorEngine
		);						
			
	}
	
	final public function getSourceName() {
		return 'template file: '.$this->file;		
	}
	
	final public function rewind() {
		if(is_resource($this->fp))
			seek($this->fp, 0);
	}
	
	final public function end() {
		if(is_resource($this->fp)) {
			flock($this->fp, LOCK_UN);
			fclose($this->fp);
		}		
	}
	
	final public function getc() {
		if(feof($this->fp))
			return false;
		else {			
			$c = fgetc($this->fp);
			
			if($c !== false) {
				$this->buff .= $c;
				
				if($this->fileEncode == cttplFileEncodeNone){
					if($this->lineEnd == "\r") {
						if($c == "\n") {
							$this->fileEncode	= cttplFileEncodeWindows;
							$this->lineEnd		= "\r\n";	
						} else
							$this->fileEncode	= cttplFileEncodeMac;											
	
						$this->line++;
					} elseif($c == "\r") 
						$this->lineEnd		= "\r";	
					  elseif($c == "\n") {
						$this->fileEncode	= cttplFileEncodeUnix;
						$this->lineEnd		= "\n";
						$this->line++;
					  }
									  
				} else {
					if($this->buffMatch($this->lineEnd)) {
						$this->line++;
					}
				}
			}			
			return $c;
		} 
	}
}

class cttplReaderString extends cttplReader {
	final public function __construct($string, $cttpl, $startLine = 1, $fromFile=false) {
		$this->cttpl	= $cttpl;
		$this->source	= $string;
		$this->string	= &$this->source;
		$this->fromFile	= $fromFile;
					
		$this->rewind();
	}
	
	final public function getSourceName() {
		return		(($this->fromFile)
								? 'template file: '.$this->fromFile
								: 'template evaluated string: "'.str_replace('"', "\"", $this->string).'"'
						);
		
	}
	
	final public function rewind() {; $this->pointer = 0; }	
	final public function getc() {
		if($this->pointer >= strlen($this->string))
			return false;
		else {			
			$c = substr($this->string, $this->pointer++, 1);

			$this->buff .= $c;
			
			if($this->fileEncode == cttplFileEncodeNone){
				if($this->lineEnd == "\r") {
					if($c == "\n") {
						$this->fileEncode	= cttplFileEncodeWindows;
						$this->lineEnd		= "\r\n";	
					} else
						$this->fileEncode	= cttplFileEncodeMac;											

					$this->line++;
				} elseif($c == "\r") 
					$this->lineEnd		= "\r";	
				  elseif($c == "\n") {
					$this->fileEncode	= cttplFileEncodeUnix;
					$this->lineEnd		= "\n";
					++$this->line;	
				}				
			} else {
				if($this->buffMatch($this->lineEnd)) {
					++$this->line;
				}
			}
			
			return $c;
		} 
	}	
}
?>
