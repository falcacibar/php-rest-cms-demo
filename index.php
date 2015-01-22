<?php
/**
*  Counter Framework Bootstrap
*
**/

error_reporting(E_ALL);

define('cfDirectoryName', 'ctrfwk');

define('DS', DIRECTORY_SEPARATOR);
define('PS', PATH_SEPARATOR);

define('cfLocalPath',dirname(__FILE__).DS.'res');
define('cfTemp',cfLocalPath.'/tmp');

$pathsToSearch = array_merge(
			array(cfLocalPath.DS.'lib')
			, explode(PS, get_include_path())
);

foreach($pathsToSearch as $path) {
	if(	file_exists(($cd = $path.DS.cfDirectoryName))
		&& is_dir($cd) 	
	) {
		define('cfLibPath', $cd);
		break;
	}
}

if(!defined('cfLibPath')) {
	trigger_error(
		sprintf(
			 'Cannot find Counter Framework directory "%s" in paths "%s"' 
			,cfDirectoryName
			,join('", "', $pathsToSearch)
		),
		E_USER_ERROR
	);
}

unset($cfDirectory, $pathsToSearch, $path, $cd);
include(dirname(__FILE__).DS."main.php");
?>
