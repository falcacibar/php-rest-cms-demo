<?php
/**
 * Counter Framework Auxiliary and Utility functions
 *
 * @package CounterFramework
 * @subpackage Core
 * 
 */
 
 
 
$adodbMainFile = $cfConfig['adodb_path']."/adodb.inc.php";

try {
	require_once($adodbMainFile);
	
	class cfDBConnection extends ADOConnection {

	}	
}  

catch(Exception $e) {
	cfError::create(
		'I cannot find adodb main file in "'.$adodbMainFile.'".', 
		CTRFWK_ERROR_ENGINE
	);
} 

unset($adodbMainFile);
								
if (!defined('ADODB_ERROR_HANDLER_TYPE')) define('ADODB_ERROR_HANDLER_TYPE',E_USER_ERROR); 
if (!defined('ADODB_ERROR_HANDLER')) define('ADODB_ERROR_HANDLER','cf_ADODBErrorWrapper');

function cf_ADODBErrorWrapper($dbms, $fn, $errno, $errmsg, $p1, $p2, &$thisConnection)
{
	switch($fn) {
	case 'EXECUTE':
		$sql = $p1;
		$inputparams = $p2;

		$s = "$dbms error: [$errno: $errmsg] in $fn(\"$sql\")\n";
		break;
	case 'PCONNECT':
	case 'CONNECT':
		$host = $p1;
		$database = $p2;

		$s = "$dbms error: [$errno: $errmsg] in $fn($host, '****', '****', $database)\n";
		break;
	default:
		$s = "$dbms error: [$errno: $errmsg] in $fn($p1, $p2)\n";
		break;
	}
	
	if (defined('ADODB_ERROR_LOG_TYPE')) {
		$t = date('Y-m-d H:i:s');
		if (defined('ADODB_ERROR_LOG_DEST'))
			error_log("($t) $s", ADODB_ERROR_LOG_TYPE, ADODB_ERROR_LOG_DEST);
		else
			error_log("($t) $s", ADODB_ERROR_LOG_TYPE);
	}
	
	if(substr($fn, -7) == 'CONNECT') 
		cfError::create($s, cfErrorADOdbConn);
	else {		
		$thisConnection->application->errors->register(
														$s, 
														cfErrorADOdb
		);
	}
	return false;
}

$ADODB_FETCH_MODE	= 2;
$ADODB_COUNTRECS	= FALSE;
?>