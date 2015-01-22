<?php
require_once(dirname(__FILE__)."/res/config/application.config.php");
require_once($cfConfig['cf_path']."/class/Application.class.php");
require_once($cfConfig['cf_path']."/include/adodbWrapper.inc.php");
require_once($cfConfig['cf_path']."/class/StringFactory.class.php");

class application extends cfApplication {
	public $file = __FILE__;	
	
	public function JSONSerializer($var) {
		return json_encode($var);
	}
	
	public function JSONUnserializer() {
		return json_decode($var);
	}
	
	public function constructor() {
		cfUtil::export();
		$this->securityType 		= cfSecurityDefault;
		$this->security->policy		= (($this->requestAssert(1, 'admin') && !$this->requestAssert(2, 'login')) ? 'deny' : 'allow');

		$this->AdminDefault			= 'contents';
		$this->AdminLoginPlace		= 'login';

		$this->queryFactory			= new cfSQLFactory();
		$this->dbQueryManager();

		
		$this->registerPlace('Dummy', "/dummy.html/");		
		$this->registerPlace('ImageCatalog', "/^(girls|rooms|chicas|habitaciones)/image/(\d+)(/|.jpg)/");
		$this->registerPlace('ImageBanner', "/^(banners)/image/(\d+)(/|.jpg)/");
		
		$this->registerPlace('Admin', '/^admin/(login|menu|contents|girls|rooms|banners|logout)/?$/', cfAppendSlashRegexp, true);		
		$this->registerPlace('View', "/^(?!admin)/", cfAppendSlashRegexp, true);
		
		$this->registerRedirect("/^admin.*/", 'admin/login/');
	}                     
	
	public function View() {		
		if($this->requestAssert(2, 'chicas', 'habitaciones') )
			$currentCatalog	= (($this->requestAssert(2, 'chicas')) ? 2 : 4);
		
		require(dirname(__FILE__)."/res/templates/site.main.php");		
	}
	
	public function Dummy() {
		die("<html><head></head><body>This is a dummy page specially for upload files.</body></html>");
	}

	public function ImageBanner() {
		cfUtil::noCacheHeaders();			
		$image = $this->dbConnection->getOne($this->queryFactory->select('`ban_image`', 'banner', "WHERE ban_id = ?"), array($this->requestChunk(3)));
		
		if(strlen($image) < 1) { 
			$image	= file_get_contents(dirname(__FILE__)."/images/t.gif");
			$type	= 'image/gif';
		} else {
			$matches	= array();
			preg_match('/(JFIF|GIF8[79]|PNG)/', substr($image, 0, 127), $matches);
					
			$type		= strtolower($matches[1]);
			
			if($type == 'jfif')						$type = 'jpeg';
			elseif(substr($type, 0, 3) == 'gif')	$type == 'gif';
			elseif($type == 'png')					$type == 'png';
			
			$type = 'image/'.$type;
		}
		
		header("Content-Type: ".$type);
		header("Content-Length: ".strlen($image));
					
		echo $image;			
	}
	
	public function ImageCatalog() {		
		cfUtil::noCacheHeaders();			
		$image = $this->dbConnection->getOne($this->queryFactory->select('`pci_image`', 'page_catalog_images', "WHERE pci_id = ?"), array($this->requestChunk(3)));
		
		if(strlen($image) < 1) { 
			$image	= file_get_contents(dirname(__FILE__)."/images/t.gif");
			$type	= 'image/gif';
		} else {
			$matches	= array();
			preg_match('/(JFIF|GIF8[79]|PNG)/', substr($image, 0, 127), $matches);
					
			$type		= strtolower($matches[1]);
			
			if($type == 'jfif')						$type = 'jpeg';
			elseif(substr($type, 0, 3) == 'gif')	$type == 'gif';
			elseif($type == 'png')					$type == 'png';
			
			$type = 'image/'.$type;
		}
		
		header("Content-Type: ".$type);
		header("Content-Length: ".strlen($image));
					
		echo $image;		
	}
		
	public function Admin(){		
		$isLogin = ($this->requestChunk(2) == $this->AdminLoginPlace);		
		
		if($this->requestAssert(2, 'girls', 'rooms') )
			$currentCatalog	= (($this->requestAssert(2, 'girls')) ? 2 : 4);
		
		if($this->server->requestMethod == "POST") {
			$this->JSONRPCErrors();
			
			if($this->requestAssert(2, 'menu') && $this->action == 'list') {
				die($this->JSONRPCResponse(array(
											array('c' => 'Contenidos'					, 'l' => $this->basePath('/admin/contents/')),
											array('c' => 'Chicas'						, 'l' => $this->basePath('/admin/girls/')),
											array('c' => 'Habitaciones'					, 'l' => $this->basePath('/admin/rooms/')),
											array('c' => 'Publicidad'					, 'l' => $this->basePath('/admin/banners/')),
											array('c' => utf8_encode('Cerrar Sesi�n')	, 'l' => $this->basePath('/admin/logout/'))
				), null, null));
			} else { 			
				if($isLogin)
					return $this->AdminLogin(); 
				
				$this->JSONRPCErrors();
				
				$queryInputs	= array();
				$fields			= array();
				$selectApp		= '';
				$postFields		= '';
				$addOrEdit		= false;
				
				if($this->requestAssert(2, 'banners')) {					
					$table		= 'banner';
					$identity	= '`ban_id`';
					
					if($this->action == 'list')
					{
						$postFields	= 'ban_name as nombre, ban_id as id';
						$selectApp	= 'ORDER BY `ban_name` ASC';
					} else {
						if($this->action == 'edit') { 			
							cfUtil::phpFileUploadErrorFinder('banner', 'Error en carga de la imagen:', true);
							
							if($_FILES['banner']['tmp_name']) {								
								$this->dbConnection->UpdateBlob(
																	'banner', 'ban_image', 
																	(empty($_FILES['banner']['tmp_name']) ? null : file_get_contents($_FILES['banner']['tmp_name'])), 
																	'ban_id = '.$this->post->id
								);
							}
						}
												
						die($this->JSONRPCResponse(true, null, null));
					} 
					
										
				} elseif($this->requestAssert(2, 'contents')) {					
					$table		= 'page_contents';
					$identity	= '`pcn_id`';
					
					if($this->action == 'list')
					{
						$postFields	= '@{ContentsPostFields}';
						$selectApp	= 'INNER JOIN `page` ON `page`.`pag_id` = `page_contents`.`pag_id` ORDER BY `pcn_name` ASC';
					} 
					elseif($this->action == 'edit' || $this->action == 'delete') 
					{
						if($this->action == 'edit') {
							$fields			= array('pcn_content');
							$queryInputs	= array(utf8_decode(trim(stripslashes($this->post->contenido))));
						}
											
						array_push($queryInputs, $this->post->id);					
					}
				} elseif($this->requestAssert(2, 'girls', 'rooms') ) {					
					$table			= 'page_catalog';
					$identity		= '`pct_id`';
					
					if($this->action == 'list')
					{
						$postFields	= '@{CatalogEntryPostFields}';
						$selectApp	= 'WHERE `pag_id` = '.$currentCatalog.' ORDER BY `pct_name` ASC';

						$rs = $this->dbConnection->GetAll($this->queryFactory->select($postFields, $table, $selectApp));
						if($rs) {
							for($i=0;$i<count($rs);$i++) {
								$mrs = $this->dbConnection->GetAll($this->queryFactory->select('@{ImageFormFields}', 'page_catalog_images', 'WHERE pct_id = '.$rs[$i]['id']));
								
								if($mrs)																
									$rs[$i]['i'] = $mrs;
							}
						}
						
						die($this->JSONRPCResponse($rs, null, null));							
					} 
					elseif($this->action == 'add' || $this->action == 'edit' || $this->action == 'delete') 
					{
						if($addOrEdit = ($this->action == 'add' || $this->action == 'edit')) 
						{
							$this->post->nombre = utf8_encode(trim($this->post->nombre));
							if(empty($this->post->nombre))
								return $this->errors->register('El nombre no puede estar vacio.', cfError, true);
								
							$this->post->desclarga = utf8_encode(trim(stripslashes($this->post->contenido)));
								
							$fields			= array('pag_id', 'pct_name', 'pct_content', 'pct_enabled');
							$queryInputs	= array($currentCatalog, $this->post->nombre, $this->post->contenido, cfUtil::bool2int(isset($this->post->habilitado)));
						} else {
							if(isset($this->post->imgid)) {
								$this->dbConnection->Execute($this->queryFactory->simpleDelete('page_catalog_images', 'pci_id'), array($this->post->imgid));
								die($this->JSONRPCResponse(true, null, null));
							}
						}
						
						
											
						if($this->action != 'add')
							array_push($queryInputs, $this->post->id);					
					}
				}
			}				
			// die($this->JSONRPCResponse($_FILES, null, null));
			
			$this->AdminJSONRPCPost($table, $postFields, $selectApp, $fields, $identity, $queryInputs, ($this->requestAssert(2, 'girls', 'rooms') && $addOrEdit));
			
			if($this->requestAssert(2, 'girls', 'rooms') && $addOrEdit) {
				if($this->action == 'add')
					$this->post->id = $this->dbConnection->Insert_ID();
				
				foreach($_FILES as $name => $handler) {									
					cfUtil::phpFileUploadErrorFinder($name, 'Error en carga de la imagen:', true);
					 
					if($handler['tmp_name']) {
						$this->dbConnection->Execute($this->queryFactory->simpleInsert('page_catalog_images', array('pct_id', 'pci_description', 'pci_image', 'pci_enabled')), array($this->post->id, '', '', 1)); 						
						$this->dbConnection->UpdateBlob(
															'page_catalog_images', 'pci_image', 
															(empty($handler['tmp_name']) ? null : file_get_contents($handler['tmp_name'])), 
															'pci_id = '.$this->dbConnection->Insert_ID()
						);
					}
				}
				
				print($this->JSONRPCResponse(true, null, null));
			}
		} elseif($this->requestAssert(2, 'logout')) {
			$this->users->logout();
			$this->redirect("admin/login/");
		} elseif($this->requestAssert(2, 'menu') && $this->action == 'list') {
			 echo $this->JSONSerializer($this->getMenuStructure());
		} else require(dirname(__FILE__)."/res/templates/panel.base.php");
		
	}
	
	public function panelIncludeFile($dir, $module, $default) {		
		$fname = $dir.'/panel.'.$module.'.php';
		if(!file_exists($fname))	
			return $dir.'/panel.'.$default.'.php';
		else return $fname;		
	}
	
	public function AdminLogin() {
		if($this->action == 'auth') {
			$response = $this->users->auth($this->post->usuario, utf8_decode($this->post->clave));
			$error	  = (($response) ? null : 'Nombre de usuario o contrase�a incorrecta, intente nuevamente.');
			
			print($this->JSONRPCResponse(cfUtil::int2bool((int) $response), $error));
		} elseif($this->users->loggedIn) {
			$this->redirect("admin/".$this->AdminDefault); 
		} else {
			require(dirname(__FILE__)."/res/templates/admin.login.php");
		}		
	}

	public function AdminJSONRPCPost($table, $SFPostFields, $selectAppend, $fields, $identityField, $queryInputs, $noprint=false) {
		if($this->action == 'list') {
			print($this->JSONRPCResponse($this->dbConnection->GetAll($this->queryFactory->select($SFPostFields, $table, $selectAppend)), null, null));
		} else {		
			if($this->action == 'add') {
				$this->dbConnection->Execute($this->queryFactory->simpleInsert($table, $fields), $queryInputs);			
			} elseif($this->action == 'edit') {
				$this->dbConnection->Execute($this->queryFactory->simpleUpdate($table, $fields, $identityField), $queryInputs);								
			} elseif($this->action == 'delete') {							
				$this->dbConnection->Execute($this->queryFactory->simpleDelete($table, $identityField), $queryInputs);
			}
			
			if(!$noprint)
				print($this->JSONRPCResponse(true, null, null));
		}
	}
	
	public function getContentFrom($name, $content = 'MainContent') {
		return $this->dbConnection->GetOne($this->queryFactory->select('pcn_content', 'page_contents', 'INNER JOIN `page` ON `page`.`pag_id` = `page_contents`.`pag_id` WHERE `pag_name` = ?'), array($name));
	}
	
	public function unauthorized() {
		$this->redirect('admin/login');
	}
	
	public function forbidden() {
		$this->unauthorized();
	}
	
	public function index() {
	}
	
	final public function dbQueryManager() {
		$this->queryFactory->register('Enabled', '`%s_enabled` = 1');		

		$this->queryFactory->register('ImageFormFields', '
													`pci_id`				as `id`,
													`pci_description`		as `descripcion`,
													`pci_enabled`			as `habilitado`
									');
		
		
		$this->queryFactory->register('ContentsPostFields', '
													`pcn_id`				as `id`,
													`pcn_content`			as `contenido`,
													`pag_caption`			as `nombre`
									');

		$this->queryFactory->register('CatalogEntryPostFields', '
													`pct_id`				as `id`,													
													`pct_name`				as `nombre`,
													`pct_content`			as `contenido`,
													`pct_enabled`			as `habilitado`
									');

	}
}

$application = new application();
?>