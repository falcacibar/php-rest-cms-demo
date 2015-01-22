<?php
$cfConfig = array(	
	'cf_path'	=> 'cf',
	'adodb_path'	=> 'adodb',
	'databases'		=> array(
/*
								array(
										'type'		=> 'mysql',
										'user' 		=> 'devel_chicashots',
										'password'	=> 'js83hs82h27y6',
										'host'		=> 'localhost',
										'db'		=> 'chicashots'

								)
*/								
	),						
 	'security'		=> array(
		'password_encript_function'	=> '',
		'users'						=> array(
												array('admin', 'admin')
										),
		'resource_groups'			=> array(
												'Admin' => array('AdminPanel')
									),
		'acl'						=> array( 
												'resources_groups' 	=> array(),
												'resources'			=> array(
																		'Admin'	=> array(
																						'admin' => array(0),
																						0	=> array()
																		)
												)
									) 
	) 
)
?>
