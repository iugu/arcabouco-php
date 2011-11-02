<?

/*
 * This file is part of the Arcabouco Framework.
 * (c) 2008 Patrick Negri <patrick@agencialobo.com.br>
 * (c) 2008 Paulo Lobo <plobo@agencialobo.com.br>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class web
{
	public static $send_compressed = true;
	public static $request = '';
	public static $request_method = '';
	public static $redirect_to = '';
	public static $type_and_charset = 'Content-type: text/html; charset=UTF-8';
	public static $title = '';
	public static $tokens = Array();

	static function reset()
	{
		self::$requested = '';
		self::$request_method = '';
		self::$redirect_to = '';
	}

	static function configure_type_and_charset($default="Content-type: text/html; charset=UTF-8")
	{
		if (isset($default)) self::$type_and_charset = $default;
	}
	
	static function get_type_and_charset()
	{
		return self::$type_and_charset;
	}
	
	static function random_string()
	{
		$chars = "abcdefghijkmnopqrstuvwxyz023456789";
		srand((double)microtime()*1000000);
		$i = 0;
		$pass = '' ;
	
		while ($i <= 32) {
			$num = rand() % 33;
			$tmp = substr($chars, $num, 1);
			$pass = $pass . $tmp;
			$i++;
		}
		return $pass;
	}
	
	static function send_headers()
	{
		if (error_get_last()) exit(0);
		if (headers_sent()) return;
		header(self::get_type_and_charset());
	}
	
	static function not_modified()
	{
		header("HTTP/1.0 304 Not Modified");
	}
	
	static function not_found()
	{
		header("HTTP/1.0 404 Not Found");
	}
	
	static function last_modified($gmt_time)
	{
		header("Last-Modified: $gmt_time");
	}
	
	static function document_referer()
	{
		global $_SERVER;
		if (isset($_SERVER['HTTP_REFERER'])) return $_SERVER['HTTP_REFERER'];
		return '';
	}

	static function check_gzip_before_send($buffer,$mode)
	{
		if (error_get_last()) return;
		if (headers_sent()) return;
		if (!self::$send_compressed) return $buffer;
		return @ob_gzhandler($buffer,$mode);
	}

	static function disable_compression()
	{
		self::$send_compressed = false;
	}

	static function enable_compression()
	{
		self::$send_compressed = true;
	}

	static function enable_gzip()
	{
		self::$send_compressed = true;
		$phpver = phpversion();
		$useragent = (isset($_SERVER["HTTP_USER_AGENT"]) ) ? $_SERVER["HTTP_USER_AGENT"] : '';
		if ( $phpver >= '4.0.4pl1' && ( strstr($useragent,'compatible') || strstr($useragent,'Gecko') ) )
		{
			if ( extension_loaded('zlib') )
			{
				ob_start('web::check_gzip_before_send');
			}
		}
		else if ( $phpver > '4.0' )
		{
			if ( strstr( isset($HTTP_SERVER_VARS['HTTP_ACCEPT_ENCODING'])?$HTTP_SERVER_VARS['HTTP_ACCEPT_ENCODING']:'' , 'gzip') )
			{
				if ( extension_loaded('zlib') )
				{
					$do_gzip_compress = TRUE;
					ob_start();
					ob_implicit_flush(0);
					header('Content-Encoding: gzip');
				}
			}
		}
	}

	static function enable_sessions()
	{
		session_save_path(core::base_directory() . '/sessions');
		session_cache_limiter('none');
		$cache_limiter = session_cache_limiter();

		/* Define o limite de tempo do cache em 30 minutos */
		session_cache_expire(60);
		$cache_expire = session_cache_expire();

		ini_set('session.cookie_lifetime',  0);	// Sessions never expire - Enable our manual control
		ini_set("session.gc_maxlifetime", 3600); 

		if (isset($_POST["PHPSESSID"])) {
			session_id($_POST["PHPSESSID"]);
		}
		if (isset($_GET["PHPSESSID"])) {
			session_id($_GET["PHPSESSID"]);
		}
		@session_start();
		header("Cache-control: private");
	}
	
	static function configure_expiration($default=3600)
	{
		header('Expires: '.gmdate('D, d M Y H:i:s', time()+$default).'GMT');
	}
	
	static function document_root()
	{
		global $root_directory;
		//$directory = realpath(isset($_SERVER['DOCUMENT_ROOT'])?$_SERVER['DOCUMENT_ROOT']:'');
		$directory = realpath($root_directory);
		$directory = rtrim(str_replace("\\","/",$directory),"/");
		return $directory;
	}
	
	static function document_relative()
	{
		return str_replace(core::root_directory(),'',self::document_root());
	}
	
	static function document_url()
	{
		$host = isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:'';
		return "http://" . $host . (self::document_relative()!=''?'/'.self::document_relative():'');
	}
	
	static function configure_request($request='',$method='')
	{
		global $_GLOBALS;
		if ($request == "")
		{
			$_GLOBALS['request'] = preg_replace('/[ <>\'\"\r\n\t\(\)]/', '', (isset($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI']:''));
			if (strpos($_GLOBALS['request'],"?")) $_GLOBALS['request'] = substr($_GLOBALS['request'],0,strpos($_GLOBALS['request'],"?"));
			if (strlen($_GLOBALS['request'])>5) $_GLOBALS['request'] = rtrim($_GLOBALS['request'],"/");
		}
		else
		{
			$_GLOBALS['request'] = $request;
		}

		if ($method == "")
		{
			$_method = strtolower( preg_replace('/[ <>\'\"\r\n\t\(\)]/', '', isset($_POST["_method"])?$_POST["_method"]:(isset($_GET["_method"])?$_GET["_method"]:'' )) );
			$_GLOBALS['request_method'] = ($_method!='')?$_method:(strtolower(preg_replace('/[ <>\'\"\r\n\t\(\)]/', '', (isset($_SERVER['REQUEST_METHOD'])?$_SERVER['REQUEST_METHOD']:''))));
		}
		else
		{
			$_GLOBALS['request_method'] = strtolower($method);
		}
	}

	static function document_requested()
	{
		global $_GLOBALS;
		return $_GLOBALS['request'];
	}

	static function method_requested()
	{
		global $_GLOBALS;
		return $_GLOBALS['request_method'];
	}
	
	static function format_requested()
	{
		$http_accept = isset($_SERVER['HTTP_ACCEPT'])?$_SERVER['HTTP_ACCEPT']:'';
		$javascript_position = strpos($http_accept,'text/javascript')!==FALSE?strpos($http_accept,'text/javascript'):9999999999999;
		$json_position = strpos($http_accept,'json')!==FALSE?strpos($http_accept,'json'):9999999999999;
		$html_position = strpos($http_accept,'text/html')!==FALSE?strpos($http_accept,'text/html'):9999999999999;
		$xml_position = strpos($http_accept,'text/xml')!==FALSE?strpos($http_accept,'text/xml'):9999999999999;
		
		if (($json_position < $html_position) && ($json_position < $xml_position) && ($json_position < $javascript_position)) return 'json';
		if (($javascript_position < $html_position) && ($javascript_position < $xml_position)) return 'js';
		if (($html_position < $javascript_position) && ($html_position != 9999999999999)) return 'html';
		if (($xml_position < $javascript_position) && ($xml_position < $html_position)) return 'xml';
		return 'html';
	}
	
	static function make_url($title)
	{
		$url = mb_strtolower($title, "utf-8");
		$search = explode(",","ç,æ,œ,á,é,í,ó,ú,à,è,ì,ò,ù,ä,ë,ï,ö,ü,ÿ,â,ê,î,ô,û,å,e,i,ø,u,ã,õ,?,#,=,$,/,!");
		$replace = explode(",","c,ae,oe,a,e,i,o,u,a,e,i,o,u,a,e,i,o,u,y,a,e,i,o,u,a,e,i,o,u,a,o,, , , , ,");
		$url = str_replace($search, $replace, $url);
		$url = str_replace(" ","-",$url);
		$url = preg_replace("/-+/","-",$url);
		return trim($url);
	}
	
	static function redirect($new_location)
	{
		header("Location: $new_location");
	}
	
	static function redirected_to()
	{
		return (self::$redirect_to!='')?self::$redirect_to:NULL;
	}
	
	static function redirect_to($new_location)
	{
		self::$redirect_to = $new_location;
	}
	
	static function params()
	{	
		$fields = Array();

		foreach ($_FILES as $key=>$value) {

			if (!isset($fields)) $fields = Array();
			if (!isset($fields[$key])) $fields[$key] = Array();
			if (!is_array($fields[$key])) $fields[$key] = Array();
			
			if (is_array($value['name'])) {
				foreach ($value['name'] as $j_key=>$j_value)
				{
					if (!isset($fields[$key][$j_key])) $fields[$key][$j_key] = Array();
					if (!is_array($fields[$key][$j_key])) $fields[$key][$j_key] = Array();
	
					$fields[$key][$j_key]['name'] = $j_value;
					$fields[$key][$j_key]['type'] = $value['type'][$j_key];
					$fields[$key][$j_key]['tmp_name'] = $value['tmp_name'][$j_key];
					$fields[$key][$j_key]['error'] = $value['error'][$j_key];
					$fields[$key][$j_key]['size'] = $value['size'][$j_key];
				}
			}
			else {
					$fields[$key]['name'] = $value['name'];
					$fields[$key]['type'] = $value['type'];
					$fields[$key]['tmp_name'] = $value['tmp_name'];
					$fields[$key]['error'] = $value['error'];
					$fields[$key]['size'] = $value['size'];
			}
		}
		
		if (strtolower(self::method_requested()) == "get") $fields = array_merge_recursive($_GET,$fields);
		else if (strtolower(self::method_requested()) == "post")  $fields = array_merge_recursive($_POST,$fields); 
		else if (strtolower(self::method_requested()) == "delete")  $fields = array_merge_recursive($_POST,$fields); 
		else if (strtolower(self::method_requested()) == "put") $fields = array_merge_recursive($_POST,$fields); 
		
		if (isset($fields['token'])) {
			web::configure_token_space($fields['token']);
		}
		
		$token_space = web::session_get_variable( web::get_current_token() );
		if (!isset($token_space['fields'])) $token_space['fields'] = Array();
		
		$fields = array_merge_recursive( $token_space['fields'], $fields );
		
		return $fields;
	}
	
	static function add_param($name,$value)
	{
		if (self::method_requested() == "get")
		{
			$_GET[$name] = $value;
		}
		else
		{
			$_POST[$name] = $value;
		}
	}
	
	static function get_param($name)
	{
		$params = self::params();
		return isset($params[$name])?$params[$name]:'';
	}
	
	static function remove_param($name)
	{
		if (self::method_requested() == "get")
		{
			unset($_GET[$name]);
		}
		else
		{
			unset($_POST[$name]);
		}
	}
	
	static function clear_params()
	{
		if (self::method_requested() == "get")
		{
			if (isset($_GET)) unset($_GET);
			$_GET = Array();
		}
		else
		{
			if (isset($_POST)) unset($_POST);
			$_POST = Array();
		}
	}
	
	static function set_message($msg)
	{
		global $_SESSION;
		$_SESSION['flash'] = $msg;
	}
	
	static function new_message()
	{
		global $_SESSION;
		return isset($_SESSION['flash']);
	}
	
	static function get_message()
	{
		global $_SESSION;
		$msg = $_SESSION['flash'];
		unset($_SESSION['flash']);
		return $msg;
	}
	
	static function set_title($title)
	{
		self::$title = $title;
	}
	
	static function get_title()
	{
		if (self::$title == '') {
			self::$title = core::application_name();
		}
		return self::$title;
	}
	
	static function before_header()
	{
		foreach ( component::all() as $component ) if (method_exists($component,'before_head')) echo $component->before_head();
	}
	
	static function after_header()
	{
		foreach ( component::all() as $component ) if (method_exists($component,'after_head')) echo $component->after_head();
	}
	
	static function after_body()
	{
		foreach ( component::all() as $component ) if (method_exists($component,'after_body')) echo $component->after_body();
	}
	
	static function before_body()
	{
		foreach ( component::all() as $component ) if (method_exists($component,'before_body')) {
			echo $component->before_body();
		}
	}
	
	static function session_set_variable($variable,$value)
	{
		global $_SESSION;
		$_SESSION[$variable] = $value;
	}
	
	static function session_get_variable($variable)
	{
		global $_SESSION;
		if (isset($_SESSION[$variable])) return $_SESSION[$variable];
		return false;
	}
	
	static function session_clear_variable($variable)
	{
		global $_SESSION;
		unset($_SESSION[$variable]);
	}
	
	static function cookie_set_variable($name,$value,$expire=0) {
		if (headers_sent()) return;
		setcookie($name,$value,$expire,'/');
	}
	
	static function cookie_get_variable($name) {
		if (isset($_COOKIE[$name])) return $_COOKIE[$name];
		return false;
	}
	
	static function user_networkid() {
		return isset($_SERVER['REMOTE_ADDR'])?ip2long($_SERVER['REMOTE_ADDR']):0;
	}
	
	static function global_set_variable($variable,$value)
	{
		global $GLOBALS;
		$GLOBALS[$variable] = $value;
	}
	
	static function global_get_variable($variable)
	{
		global $GLOBALS;
		if (isset($GLOBALS[$variable])) return $GLOBALS[$variable];
		return false;
	}
	
	static function clear_input_errors() {
		web::global_set_variable('input_errors',Array());
	}
	
	static function set_input_error($field_name,$message='') {
		$input_errors = web::global_get_variable('input_errors');
		if (!is_array($input_errors)) $input_errors = Array();
		if (!isset($input_errors[$field_name]) || !is_array($input_errors[$field_name])) $input_errors[$field_name] = Array();
		$input_errors[$field_name][] = $message;
		web::global_set_variable('input_errors',$input_errors);
	}
	
	static function clear_input_error($field_name) {
		$input_errors = web::global_get_variable('input_errors');
		if (!is_array($input_errors)) $input_errors = Array();
		$input_errors[$field_name] = false;
		web::global_set_variable('input_errors',$input_errors);
	}
	
	static function get_input_errors() {
		$input_errors = web::global_get_variable('input_errors');
		if (!is_array($input_errors)) $input_errors = Array();
		return $input_errors;
	}
	
	static function has_input_errors() {
		$input_errors = web::global_get_variable('input_errors');
		if (!is_array($input_errors)) $input_errors = Array();
		return count($input_errors)>0;
	}

	static function parse_browser_language($http_accept, $deflang = "en") {
	   if(isset($http_accept) && strlen($http_accept) > 1)  {
		  # Split possible languages into array
		  $x = explode(",",$http_accept);
		  foreach ($x as $val) {
			 #check for q-value and create associative array. No q-value means 1 by rule
			 if(preg_match("/(.*);q=([0-1]{0,1}\.\d{0,4})/i",$val,$matches))
				$lang[$matches[1]] = (float)$matches[2];
			 else
				$lang[$val] = 1.0;
		  }
	
		  #return default language (highest q-value)
		  $qval = 0.0;
		  foreach ($lang as $key => $value) {
			 if ($value > $qval) {
				$qval = (float)$value;
				$deflang = $key;
			 }
		  }
	   }
	   
	   if (strlen($deflang) > 2) {
	   	$deflang = str_replace('-','_',$deflang);
	   	$deflang = strtolower(substr($deflang,0,3)) . strtoupper(substr($deflang,3));
	   }
	   
	   return $deflang;
	}
	
	static function get_browser_language()
	{
		if (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"]))
		{
			return web::parse_browser_language($_SERVER["HTTP_ACCEPT_LANGUAGE"]);
		}
		else
		{
			return web::parse_browser_language(NULL);
		}
	}
	
	static function get_received_file_details($field_name)
	{
		$params = web::params();

		if (!isset($_FILES[$field_name])) return false;
		if (!isset($_FILES[$field_name]['name'])) return false;
		if (!isset($_FILES[$field_name]['tmp_name'])) return false;

		if (is_array($_FILES[$field_name]['name']))
		{

			$files = Array();
			for ($i=0;$i<count($_FILES[$field_name]['name']);$i++) {
				if (!isset($_FILES[$field_name]['name'][$i])) continue;
				if (!isset($_FILES[$field_name]['tmp_name'][$i])) continue;
				$files[] = Array(
					'name'=>$_FILES[$field_name]['name'][$i],
					'errors'=>$_FILES[$field_name]['error'][$i],
					'tmp_name'=>$_FILES[$field_name]['tmp_name'][$i]
				);

			}
			return $files;
		}
		else
		{

			return Array(
				'name'=>$_FILES[$field_name]['name'],
				'errors'=>$_FILES[$field_name]['error'],
				'tmp_name'=>$_FILES[$field_name]['tmp_name'],
			);
		}
	}
	
	static function save_received_file($field,$save_path,$extension_whitelist=array(),$max_file_size_in_bytes=2147483647)
	{
		$params = web::params();

		$return = Array();

		$destination_directory = dirname($save_path);
		if (!is_dir($destination_directory)) mkdir( $destination_directory, 0777, true);

		$upload_name = $field['name'];
		$valid_chars_regex = '.A-Z0-9_ !@#$%^&()+={}\[\]\',~`-';				// Characters allowed in the file name (in a Regular Expression format)		

		$MAX_FILENAME_LENGTH = 260;
		$file_name = "";
		$file_extension = "";
		$uploadErrors = array(
			0=>"There is no error, the file uploaded with success",
			1=>"The uploaded file exceeds the upload_max_filesize directive in php.ini",
			2=>"The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form",
			3=>"The uploaded file was only partially uploaded",
			4=>"No file was uploaded",
			6=>"Missing a temporary folder"
		);

		$POST_MAX_SIZE = ini_get('post_max_size');
		$unit = strtoupper(substr($POST_MAX_SIZE, -1));	

		$multiplier = ($unit == 'M' ? 1048576 : ($unit == 'K' ? 1024 : ($unit == 'G' ? 1073741824 : 1)));

		$length = 0;
		if (isset($_SERVER['CONTENT_LENGTH'])) $length = (int)$_SERVER['CONTENT_LENGTH'];

		$return = Array();

		if ($length > $multiplier*(int)$POST_MAX_SIZE && (int)$POST_MAX_SIZE) {
			$return['error_msg'] = 'Exceeded maximum allowed post size.';
			return $return;
		}

		if (!isset($field) || ($field['tmp_name'] == '')) {
			$return['error_msg'] = "No upload found.";
			return $return;
		} else if (isset($field["errors"]) && $field["errors"] != 0) {
			$return['error_msg'] = $uploadErrors[$field["errors"]];
			return $return;
		} else if (!isset($field["tmp_name"]) /* || !@is_uploaded_file($field["tmp_name"]) */ ) {
			$return['error_msg'] = 'Upload failed is_uploaded_file test.';
			return $return;
		} else if (!isset($field['name'])) {
			$return['error_msg'] = 'File has no name';
			return $return;
		}
		
		$file_size = @filesize($field["tmp_name"]);
		
		if (!$file_size || $file_size > $max_file_size_in_bytes) {
			$return['error_msg'] = 'Exceeded maximum allowed file size';
			return $return;
		}

		//$file_name = content::make_filename( basename($_FILES[$upload_name]['name']) );

		/*
		if (file_exists($save_path)) {
			$return['error_msg'] = 'File allready exists';
			return $return;
		}
		*/

		$path_info = pathinfo($field['name']);
		$file_extension = $path_info["extension"];
		$is_valid_extension = false;
		foreach ($extension_whitelist as $extension) {
			if (strtolower($file_extension) == strtolower($extension)) {
				$is_valid_extension = true;
				break;
			}
		}
		if ((!$is_valid_extension) && (count($extension_whitelist) > 0))  {
			$return['error_msg'] = 'Invalid file extension';
			return $return;
		}

		$file_name = str_replace(" ","-",$file_name);

		if (strpos( $field['tmp_name'], get_media_diretory() . 'tmp/' ) !== false) {
			if (!copy($field['tmp_name'],$save_path)) {
				$return['error_msg'] = "File could not be saved: ". $save_path;
				return $return;
			}
			unlink($field['tmp_name']);
		}
		else if (!@move_uploaded_file($field["tmp_name"], $save_path )) {
			$return['error_msg'] = "File could not be saved: ". $save_path;
			return $return;
		}

		$return['name'] = basename($save_path);
		$return['original_name'] = basename($file_name);
		$return['msg'] = 'Success';

		return $return;

	}
	
	static function begin_token_storage($form_name)
	{
		if (web::get_param('token') != '') return web::get_param('token');
		$form_token = md5($form_name . '_' . uniqid(rand(),true));
		web::session_set_variable($form_token, Array('fields'=>Array()) );
		web::configure_token_space($form_token);
		return $form_token;
	}
	
	static function store_token_on_form($token)
	{
		echo '<input type="hidden" name="token" value="' . $token . '" />';
	}
	
	static function configure_token_space($form_token) {
		array_push( self::$tokens, $form_token );
	}
	
	static function get_current_token() {
		if (count(self::$tokens) == 0) return Array();
		return self::$tokens[count(self::$tokens)-1];
	}
	
	static function end_token_storage() {
		array_pop( self::$tokens );
	}
	
	static function clear_token_storage($token)
	{
		web::session_clear_variable($token);
		web::session_set_variable($token, Array('fields'=>Array()) );
	}
	
	static function document_domain()
	{
		$hostname = isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:'';
		preg_match('@^(?:http://)?([^/:]+)@i', $hostname, $matches);
		$host = $matches;
		preg_match('/[^.]+\.[^.]+$/', $host, $matches);
		if (count($matches)) {
			$host = $matches[0];
		}
		return $host;
	}
	
	static function paginate($current_page,$count,$href,$results_per_page=11,$space='-')
	{
		$total_pages = ceil($count/$results_per_page);
		$page_walk = $current_page-1;
		$display_pages = 10;

		// Satisfy a ODD number
		if (!($display_pages&1)) $display_pages++;
		
		$pages = Array();
		
		if ($count == 0) {
			$page_number = 1;
			array_push($pages, Array('page_number'=>$page_number,'text'=>$page_number,'selected'=>true,'href'=>$href . $page_number) );
			return $pages;
		}
		
		// Calculate Total and Half Steps
		$total_steps = $display_pages;
		$half_steps = floor($total_steps/2);
		$page_walk_minus_half = $page_walk-$half_steps;
		$page_walk_plus_half = $page_walk+$half_steps;
		$free_slots_left = $half_steps-(($page_walk_minus_half<0)?abs($page_walk_minus_half):0);
		$free_slots_right = $half_steps-(($page_walk_plus_half>$total_pages)?$page_walk_plus_half%$total_pages:0);
		$total_slots_left = $free_slots_left+$half_steps-$free_slots_right;
		$total_slots_right = $free_slots_right+$half_steps-$free_slots_left;
		
		$page_walk -= $total_slots_left;
		if ($page_walk < 0) $page_walk = 0;
		
		
		
		$steps = 0;
		while ( ($steps < $display_pages) && ($page_walk < $total_pages) ) {
			$page_number = $page_walk;
			if ( ($steps == 0) && ($page_number != 0) ) $page_number = 0;
			if ( ($steps == $display_pages-1) && ($page_number != ($total_pages-1)) ) $page_number = $total_pages-1;
			$selected = $page_walk == ($current_page-1);
			if ( ($steps == 1) && ($page_number != 1) ) $page_number = 'space';
			if ( ($steps == $display_pages-2) && ($page_number < ($total_pages-2)) ) $page_number = 'space';
			if ( is_numeric($page_number) ) $page_number = $page_number+1;
			
			if ($selected) array_push($pages, Array('page_number'=>$page_number,'text'=>$page_number,'selected'=>true,'href'=>$href . $page_number) );
			else if ($page_number === 'space') array_push($pages, Array('page_number'=>false,'text'=>$space,'selected'=>false,'href'=>'') );
			else array_push($pages, Array('page_number'=>$page_number,'text'=>$page_number,'selected'=>false,'href'=>$href . $page_number) );
			
			$page_walk++;
			$steps++;
		}
		return $pages;
	}
}



?>