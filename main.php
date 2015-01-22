<?php
error_reporting(E_ALL);

ini_set('sesison.use_cookies', '0');
ini_set('sesison.auto_start', '0');

require_once(dirname(__FILE__).'/res/config/application.config.php');
require_once($cfConfig['cf_path'].'/class/Application.class.php');
require_once($cfConfig['cf_path'].'/include/adodbWrapper.inc.php');
require_once(dirname(__FILE__).'/res/include/imageaux.lib.php');


class application extends cfApplication {
	public $file					= __FILE__;		
	public $sessionAutostart		= false;
	
	const  mds_url					= 'http://cms.mds.devel.tryer.cl';
	const  mds_cms_image_url		= 'http://cms.mds.devel.tryer.cl/files/images/';
	
	public function JSONSerializer($var) {
		return json_encode($var);
	}
	
	public function JSONUnserializer() {
		return json_decode($var);
	}
	const mcdisco_re	= '/^contents/mcdisco(theque)?/';
	
	public function constructor() {
		@session_destroy();
		cfUtil::export();
		
		ini_set('default_charset', 'UTF-8');
		setlocale(LC_ALL, 'es_ES.UTF-8');
		
		$this->securityType 		= cfSecurityDefault;
		$this->security->policy		= 'allow';
		
		cfdb::driver($this->dbConn);
		
		$this->JSONRPCErrors();
		
		$this->registerPlace('contents_mcdisco_events_highlights'			, self::mcdisco_re.'events/highlights/', cfAppendSlashRedirectOnly);
		$this->registerPlace('contents_mcdisco_events_list'					, self::mcdisco_re.'events/list/', cfAppendSlashRedirectOnly);
		$this->registerPlace('contents_mcdisco_events_id'					, self::mcdisco_re.'events/\d+/', cfAppendSlashRedirectOnly);
		
		$this->registerPlace('contents_mcdisco_parties_highlights'			, self::mcdisco_re.'parties/highlights/', cfAppendSlashRedirectOnly);
		$this->registerPlace('contents_mcdisco_parties_list'				, self::mcdisco_re.'parties/list/', cfAppendSlashRedirectOnly);
		$this->registerPlace('contents_mcdisco_parties_id'					, self::mcdisco_re.'parties/\d+/', cfAppendSlashRedirectOnly);
		
		$this->registerPlace('contents_mcdisco_image_galleries_list'		, self::mcdisco_re.'image-galleries/list/', cfAppendSlashRedirectOnly);
		$this->registerPlace('contents_mcdisco_image_galleries_id'			, self::mcdisco_re.'image-galleries/\d+/', cfAppendSlashRedirectOnly);
		
		$this->registerPlace('contents_mcdisco_videos_list'					, self::mcdisco_re.'videos/list/', cfAppendSlashRedirectOnly);
		
		$this->registerPlace('contents_mcdisco_home_about'					, self::mcdisco_re.'home/about/', cfAppendSlashRedirectOnly);
		$this->registerPlace('contents_mcdisco_home_welcome'				, self::mcdisco_re.'home/welcome/', cfAppendSlashRedirectOnly);	
		$this->registerPlace('contents_mcdisco_home_highlight'				, self::mcdisco_re.'home/highlight/', cfAppendSlashRedirectOnly);
				
		$this->registerPlace('contents_mcdisco_location_info'				, self::mcdisco_re.'location/info/', cfAppendSlashRedirectOnly);
		$this->registerPlace('contents_mcdisco_guestlist_info'				, self::mcdisco_re.'guestlist/info/', cfAppendSlashRedirectOnly);
		//
		
		 
		$this->contents_mcdisco_base_query		= 
					cfdb::query()
							->select('events')
								->cms_events
									->id_event(null, 'id')
									->id_image("CONCAT('".self::mds_cms_image_url."', :field)", 'image')
									->name()
									->start_date('date', 'date')
									->start_date('time', 'time')
									->days('null', 'days')
									->description(null, 'text')
							->_
							->union()
								->select()
									->cms_events_weekly
										->id_event_weekly()
										->id_image("CONCAT('".self::mds_cms_image_url."', :field)", 'image')
										->name()
										->param("NULL", 'date')
										->start_time(null, 'time')
										->days()
										->description(null, 'text')
								->_
							->_
						;
					

//		$this->registerRedirect('/.*/', self::mds_url);
	}
	
	public function index() {
		echo '{"error":"404 No Encontrado"}';
	}

	public function response(&$in, $json_in=false) {
		if($jsonp = (isset($_GET['callback']) && preg_match('#[a-zA-Z_$][0-9a-zA-Z_$]*#xsm', $_GET['callback']))) {
			header('Content-Type: text/javascript');
			echo $_GET['callback'].'(';
		} else {
			header('Content-Type: application/json');
		}		
		
		echo ($json_in)	? $in : json_encode($in);
		unset($in);
		
		if($jsonp) echo ')';
	}
	
	private function processQueryForPaging(cfdbQuery &$query) {
		if($haveLimit = isset($_GET['limit']))
			$query->limit(($limit = (int) $_GET['limit']));
		
		if(isset($_GET['skip']))
			$query->skip($_GET['skip']);
		elseif($haveLimit && isset($_GET['page'])) {
			$page = (int) $_GET['page'];
			$query->skip(($limit * $page) - $limit);
		}
		
		return $query;
	}
	
	
	/**
	 * @return cfQuery
	 */
	public function contents_mcdisco_base_query() {
			return	cfdb::query()
							->select('events')
								->cms_events
									->id_event(null, 'id')
									->id_image("CONCAT('".self::mds_cms_image_url."', :field)", 'image')
									->name()
									->start_date('date', 'date')
									->start_date('time', 'time')
									->days('null', 'days')
									->description(null, 'text')
							->_
							->union()
								->select()
									->cms_events_weekly
										->id_event_weekly()
										->id_image("CONCAT('".self::mds_cms_image_url."', :field)", 'image')
										->name()
										->param("NULL", 'date')
										->start_time(null, 'time')
										->days()
										->description(null, 'text')
								->_
							->_
						;

	}
	
	public function contents_mcdisco_cms_events_highlights($type) {
		$q1 = cfdb::query()
				->select()
					->events							
				->_
				->from()
					->select(
							 $q2 = $this->contents_mcdisco_base_query()
									->where()
										->start_date(gte, "ADDTIME(DATE(now()), '11:00')", y)											
										->end_date(gte, "now()", y)
										->enabled(eq, 1, y)
										->highlight(eq, 1, y)
										->id_event_type(eq, $type)
									->_
									->union
										->where()
												->end_time(gte, "time(now())", y)
												->enabled(eq, 1, y)
												->highlight(eq, 1, y)
												->id_event_type(eq, $type)
										->_
									->_  
					)
				->_
				->order()
					->param('RAND()', ASC)
				->_;		
		
		if($type == 7)
			$q1->limit(1);
		
		$this->response($this->dbConn->GetRow($q1));
		
		unset($q1, $q2);
	}
		
	public function contents_mcdisco_cms_events_list($type) {		
		$this->response($this->dbConn->GetAll(
			$q = $this->processQueryForPaging($this->contents_mcdisco_base_query()
							->where()
								->start_date(gte, "ADDTIME(DATE(now()), '11:00')", y)											
								->end_date(gte, "now()", y)
								->enabled(eq, 1, y)
								->id_event_type(eq, $type)
							->_
							->union
								->where()
										->end_time(gte, "time(now())", y)
										->enabled(eq, 1, y)
										->id_event_type(eq, $type)
								->_
							->order
								->date(asc)
								->time(asc)
							->_  
		
		)));
		
		unset($q);
	}
	
	public function contents_mcdisco_cms_events_id($type) {
		$this->response($this->dbConn->GetRow(
			$q = $this->contents_mcdisco_base_query()							
							->where()
								->id_event(eq, (int) $this->requestChunk(4), y)
								->id_event_type(eq, $type)
							->_
							->union
								->where()
										->id_event_weekly(eq, (int) $this->requestChunk(4), y)
										->id_event_type(eq, $type)
								->_
							->_  
		
		));
		
		unset($q);
	}
	
	public function contents_mcdisco_events_id() {		
		return $this->contents_mcdisco_cms_events_id(7);	
	}	
	
	public function contents_mcdisco_events_list() {
		return $this->contents_mcdisco_cms_events_list(7); 
	}
	
	public function contents_mcdisco_events_highlights() {
		return $this->contents_mcdisco_cms_events_highlights(7);
	}

	public function contents_mcdisco_parties_id() {		
		return $this->contents_mcdisco_cms_events_id(1);	
	}	
	
	public function contents_mcdisco_parties_list() {
		return $this->contents_mcdisco_cms_events_list(1); 
	}
	
	public function contents_mcdisco_parties_highlights() {
		return $this->contents_mcdisco_cms_events_highlights(1);
	}
	
	
	const contents_mcdisco_image_galleries_place	= 38;
	const contents_mcdisco_image_galleries_base		= '/contents/mcdisco/image-galleries/';
	
	public function contents_mcdisco_image_galleries_list() {
		$q = $this->processQueryForPaging(cfdb::query()
			->select()
				->cms_image_galleries
					->id_image_gallery(null, 'id')
					->title(null, 'name')
					->id_image_gallery("CONCAT('".self::mds_cms_image_url."', cms_image_gallery_cover_image(:field),'?size=header&crop=true')", 'image')
//					->id_image_gallery("CONCAT('http://".$this->server->httpHost.self::contents_mcdisco_image_galleries_base."', :field,'/')", 'images')
			->_
			->innerJoin()
				->cms_image_gallery_places
					->id_image_gallery(eq)
				->cms_image_galleries
					->id_image_gallery()					
			->_
			->where()
				->id_place(eq, self::contents_mcdisco_image_galleries_place)
			->_
			->order()
				->last_update(desc)
			->_);
				
		if(isset($this->get->limit))	$q->limit((int) $this->get->limit);
		$this->response($this->dbConn->GetAll($q));
		
		unset($q);
	}
	
	public function contents_mcdisco_image_galleries_id() {
		$id		= (int) $this->requestChunk(4);

		$this->response(
			cfUtil::array_phagocyte_assoc(
						$this->dbConn->GetRow(
							$q1 = cfdb::query()
								->select()
									->cms_image_galleries
										->id_image_gallery(null, 'id')
										->title(null, 'name')
									->_
								->where()								
									->id_image_gallery(eq, $id)
						)
						, array('images' => $this->dbConn->GetCol(
								$q2 = $this->processQueryForPaging(cfdb::query()
									->select()
										->cms_image_gallery_files
											->id_image("CONCAT('".self::mds_cms_image_url."', :field)", 'image')
										->_
										->innerJoin()
											->cms_images
												->id_image(eq)
											->cms_image_gallery_files
												->id_image()
										->_
										->where()								
											->id_image_gallery(eq, $id)							
										->_
										->order()
											->highlight(desc)
										->_
					)))
			)
		);
		
		unset($q1, $q2);
	}
	
	public function contents_mcdisco_videos_list() {
		$count		= 1;
		$data		= array();
		$i			= 0;	 
		
		while($count) {
			$yt_info	= json_decode(file_get_contents('http://gdata.youtube.com/feeds/api/videos?alt=json&q=marina+club&orderby=published&format=5&author=casinomarinadelsol&max-results=50&start-index='.(($i===0) ? 1 : $i)), true);			
			
			$count 		= isset($yt_info['feed']['entry']);
			if($count) {
				foreach($yt_info['feed']['entry'] as &$feedEntry) {
					$entry		= array();			
					
					foreach($feedEntry['media$group']['media$content'] as &$media) {
						if($media['type'] === 'application/x-shockwave-flash') {
							$entry['url'] = $media['url'];
							break;
						}	
					}
					
					$entry['title']		= $feedEntry['title']['$t'];
					
					$secs				= (int) $feedEntry['media$group']['yt$duration']['seconds'];				
					$entry['duration']	= ((int) ($secs / 60)).':'.((int) ($secs % 60));					
		
					array_push($data, array() + $entry);
					unset($entry); 
				}
			}
			
			unset($yt_info); 
			
			$i += 50;
		}	
		
		$this->response($data);
	}

	public function single_document($id) {		
		$result = $this->dbConn->GetRow(
		 		$q2 = cfdb::query()
				->select()
					->cms_documents						
						->title()
						->document(null, 'text')
					->_
					->where()								
						->id_document(eq, $id)							
					->_
		
		);
		
		unset($q2);		
		return $result; 
	}
	
	public function contents_mcdisco_home_highlight() {
		return $this->contents_mcdisco_cms_events_highlights(7);
	}
	
	public function contents_mcdisco_home_about() {
		$content = $this->single_document(36);		
		$this->response(
			cfUtil::array_phagocyte_assoc(
						$content
						, array('image' => self::mds_cms_image_url . 1 )
			)
		); 
	}
	
	public function contents_mcdisco_home_welcome() {
		$content = $this->single_document(37);		
		$this->response(
			cfUtil::array_phagocyte_assoc(
						$content
						, array('image' => self::mds_cms_image_url . 1 )
			)
		);
		
	}
	
	public function contents_mcdisco_location_info() {
		$content = $this->single_document(35);
				
		$this->response(
			cfUtil::array_phagocyte_assoc(
						$content
						, array('form' => 'about:blank')
			)
		);
		
	}
	
	public function contents_mcdisco_guestlist_info() {
		$content = $this->single_document(38);
				
		$this->response(
			cfUtil::array_phagocyte_assoc(
						$content
						, array('form' => 'about:blank')
			)
		);
	}
}

$application = new application();
?>
