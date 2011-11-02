<?

/*
 * This file is part of the Arcabouco Framework.
 * (c) 2008 Patrick Negri <patrick@agencialobo.com.br>
 * (c) 2008 Paulo Lobo <plobo@agencialobo.com.br>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$timeparts = explode(" ",microtime());
$build_time = $timeparts[1].substr($timeparts[0],1);

$build_page_cache = false;
$expiration_date = 0;

$root_directory = rtrim(str_replace("\\","/",dirname(__FILE__)),"/");

if (file_exists($root_directory . '/.maintenance')) {
	header("Location: maintenance/index.html");
	exit(0);
}

$host = isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:'';
$subdomain = substr($host, 0, strrpos($host, '.'));
$subdomain = substr($subdomain, 0, strrpos($subdomain, '.'));
$subdomain = substr($subdomain, 0, strrpos($subdomain, '.'));

$base_exists = false;

if (strlen($subdomain) > 0) {
	if (is_dir($root_directory . '/base/' . $subdomain . '/')) {
		$base_directory = $root_directory . '/base/' . $subdomain;
		$base_exists = true;
	}
}

$fatal_error = false;
if (!$base_exists) {
	if (is_dir($root_directory . '/base/config')) {
		$base_directory = $root_directory . '/base';
	}
	else if (is_dir($root_directory . '/base/www/config')) {
		$base_directory = $root_directory . '/base/www';
	}
	else {
		$fatal_error = true;
	}
}
if (!is_dir($base_directory . '/config')) $fatal_error = true;
if (!is_dir($base_directory . '/cache')) $fatal_error = true;
if (!is_dir($base_directory . '/layout')) $fatal_error = true;

if ($fatal_error) {
	echo 'Bimboo system error, check deployment';
	exit(0);
}

$_GLOBALS['request'] = preg_replace('/[ <>\'\"\r\n\t\(\)]/', '', (isset($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI']:''));
$_GLOBALS['query_string'] = preg_replace('/[ <>\'\"\r\n\t\(\)]/', '', (isset($_SERVER['QUERY_STRING'])?$_SERVER['QUERY_STRING']:''));
if (strpos($_GLOBALS['request'],"?")) $_GLOBALS['request'] = substr($_GLOBALS['request'],0,strpos($_GLOBALS['request'],"?"));
if (strlen($_GLOBALS['request'])>1) $_GLOBALS['request'] = rtrim($_GLOBALS['request'],"/");

//o-------------------------------o
//		Configure time as GMT
//o-------------------------------o
date_default_timezone_set('GMT');
$pedido_original = trim($_GLOBALS['request'],'/');

$query_cache_text = '';

if (isset($_GET['p'])) {
	$query_cache_text .= 'p=' . $_GET['p'] . ';';
}

//$_GLOBALS['query_string']


if ($pedido_original == '') $pedido_original = 'index';

/*
$sha_ped = sha1($pedido_original);
$cache_base = substr($sha_ped,strlen($sha_ped)-4) . '-' . basename($pedido_original);

$cache_uniq_id = sha1($query_cache_text);
if ($subdomain != '') $subdomain .= '-';

$cache_base_id = $base_directory . '/cache/' . $subdomain . $cache_base;
$cache_id = $cache_base_id . '-' . $cache_uniq_id;
*/

// Novo Calculo de ID do Cache (Tentando previnir o efeito de muitos arquivos no mesmo diretório)
$cache_string = $pedido_original;
$cache_hashing = hash('adler32',$cache_string);
$cache_directory = '';
$calc = strlen($cache_hashing)-1;
$cache_directory = (hexdec( substr($cache_hashing,0,2) )%2048) . '/' . (hexdec( substr($cache_hashing,$calc-2) )%2048) . '/' . (hexdec( substr($cache_hashing,$calc-4,2) )%2048) . '/';
$cache_id = $base_directory . '/cache/' . $cache_directory . basename($pedido_original) . '-' . hash('adler32',$subdomain.$pedido_original.$query_cache_text);

if (!is_dir( dirname($cache_id) ))
{
	mkdir( dirname($cache_id) , 0777, true);
}

$fplock = fopen($cache_id . '.lock', "w");

while (flock($fplock, LOCK_EX) == false) {
	usleep(50000);
}

// Verificar
// Chamar no CallBack de SAVE (DYNAMIC CONTENT)

$cache_type = '.html';

$phpver = phpversion();
$useragent = (isset($_SERVER["HTTP_USER_AGENT"]) ) ? $_SERVER["HTTP_USER_AGENT"] : '';
$accept_encoding = (isset($_SERVER["HTTP_ACCEPT_ENCODING"]) ) ? $_SERVER["HTTP_ACCEPT_ENCODING"] : '';

// wait for a filelock

if ( strstr( $accept_encoding , 'gzip') )
{
	$cache_type = '.html.gz';
}
if (file_exists($cache_id . $cache_type))
{
	$template_time = filemtime($cache_id . $cache_type);

	if ( (file_exists($base_directory . '/cache/' . $cache_directory . basename($pedido_original) . '.prototyping')) || (time() < $template_time))
	{
		flock($fplock, LOCK_UN); // libera o lock
		fclose($fplock);
		if (file_exists($cache_id . '.lock')) unlink($cache_id . '.lock');

		$fp = @fopen($cache_id . $cache_type, 'r');
		$contents = fread($fp, filesize($cache_id . $cache_type));
		fclose($fp);

		if ($cache_type == '.html.gz') {
			header('Content-Encoding: gzip');
		}
		header("Content-type: text/html; charset=UTF-8");
		$size = strlen($contents);
		echo substr($contents,0,$size-12) . substr($contents,$size-8);

		//log::output_in_development("requests.log",web::document_requested() . " [cached] : in " . core::benchmark_end($cache_time));
		if($fp = @fopen( $base_directory . "/logs/" . "requests.log" , 'a+'))
		{
			$timeparts = explode(" ",microtime());
			$endtime = $timeparts[1].substr($timeparts[0],1);
			fwrite($fp, $pedido_original . " [cached] : in " . number_format($endtime-$build_time,6,".","") . ' cpu / ' . (int)(1.0/($endtime-$build_time)) . ' cps' . "\r\n" );
			fclose($fp);
		}

		exit(0);
	}
	
}

$_method = strtolower( preg_replace('/[ <>\'\"\r\n\t\(\)]/', '', isset($_POST["_method"])?$_POST["_method"]:(isset($_GET["_method"])?$_GET["_method"]:'' )) );
$_GLOBALS['request_method'] = ($_method!='')?$_method:(strtolower(preg_replace('/[ <>\'\"\r\n\t\(\)]/', '', (isset($_SERVER['REQUEST_METHOD'])?$_SERVER['REQUEST_METHOD']:''))));

//o-------------------------------o
//		Libraries
//o-------------------------------o
require_once($root_directory . "/core/log.php");
require_once($root_directory . "/core/web.php");
require_once($root_directory . "/core/make_php_compatible.inc.php");
require_once($root_directory . "/core/yaml.php");
require_once($root_directory . "/core/core.php");
require_once($root_directory . "/boot_camp.php");

//orm::execute_query_static('BEGIN');

/*
$start = microtime(1);
$endtime = microtime(1);
echo number_format($endtime-$build_time,6,".","") . ' cpu / ' . (int)(1.0/($endtime-$build_time)) . ' cps' . "\r\n";
if (!class_exists('core')) {
	echo 'entered here';
	require_once($root_directory . "/core/log.php");
	require_once($root_directory . "/core/web.php");
	require_once($root_directory . "/core/make_php_compatible.inc.php");
	require_once($root_directory . "/core/yaml.php");
	require_once($root_directory . "/core/core.php");
	require_once($root_directory . "/boot_camp.php");
}
$endtime = microtime(1);
echo number_format($endtime-$start,6,".","") . ' cpu / ' . (int)(1.0/($endtime-$start)) . ' cps' . "\r\n";

echo class_exists('bibibi');
class bibibi
{
	var $b = 'teste';
}
echo class_exists('bibibi');

exit(0);
*/

log::output_in_development("requests.log",'loaded library (' . web::document_requested() . ')' . " : in " . core::benchmark_end($build_time));

core::configure_i18n( web::get_browser_language() );

if (web::document_requested() != '/')
{
	$not_found = true;

	$file_extension = strtolower(substr(strrchr(web::document_requested(),"."),1));

	$extension_whitelist = array("jpg","png","jpeg","gif","ico","doc","docx","xls","xlsx","js","css");	// Allowed file extensions
	$is_valid_extension = false;
	foreach ($extension_whitelist as $extension) {
		if (strtolower($file_extension) == strtolower($extension)) {
			$is_valid_extension = true;
			break;
		}
	}
	if ($is_valid_extension)
	{
		if (file_exists(core::base_directory() . web::document_requested()))
		{
			$ctype = "";

			switch( $file_extension ) {
			case "pdf": $ctype="application/pdf"; break;
			case "exe": $ctype="application/octet-stream"; break;
			case "zip": $ctype="application/zip"; break;
			case "doc": $ctype="application/msword"; break;
			case "xls": $ctype="application/vnd.ms-excel"; break;
			case "ppt": $ctype="application/vnd.ms-powerpoint"; break;
			case "gif": $ctype="image/gif"; break;
			case "png": $ctype="image/png"; break;
			case "jpeg":
			case "jpg": $ctype="image/jpg"; break;
			case "mp3": $ctype="audio/mpeg"; break;
			case "wav": $ctype="audio/x-wav"; break;
			case "mpeg":
			case "mpg":
			case "mpe": $ctype="video/mpeg"; break;
			case "mov": $ctype="video/quicktime"; break;
			case "avi": $ctype="video/x-msvideo"; break;
			case "swf": $ctype="application/x-shockwave-flash"; break;
			case "xml": $ctype="text/xml; charset=UTF-8"; break;
			case "css": $ctype="text/css; charset=UTF-8"; break;
			case "js": $ctype="text/javascript; charset=UTF-8"; break;
			case "html": $ctype="text/html; charset=UTF-8"; break;
			case "php":
			case "txt": return false;
			default: $ctype="application/force-download";
			}

			web::configure_expiration(60*60*24);

			$mod = isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])?$_SERVER['HTTP_IF_MODIFIED_SINCE']:0;
			$if_modified_since = preg_replace('/;.*$/', '', $mod);
			$mtime = filemtime(core::base_directory() . web::document_requested());
			$gmdate_mod = gmdate('D, d M Y H:i:s', $mtime) . ' GMT';

			if ($if_modified_since == $gmdate_mod) {
				flock($fplock, LOCK_UN); // libera o lock
				fclose($fplock);
				if (file_exists($cache_id . '.lock')) unlink($cache_id . '.lock');
				web::not_modified();
				exit(0);
			}

			web::last_modified($gmdate_mod);
			web::configure_type_and_charset("Content-Type: $ctype");
			web::send_headers();

			log::output_in_development("requests.log",web::document_requested() . " : in " . core::benchmark_end($build_time));

			flock($fplock, LOCK_UN); // libera o lock
			fclose($fplock);
			if (file_exists($cache_id . '.lock')) unlink($cache_id . '.lock');
			
			$fh = fopen(core::base_directory() . web::document_requested(),"rb");
			if ($fh !== FALSE) {
				while( (!feof($fh)) && (connection_status()==0) ){
					print(fread($fh, 1024*8));
					flush();
				}
				fclose($fh);
			}
			
			exit(0);
		}
		else
		{
			flock($fplock, LOCK_UN); // libera o lock
			fclose($fplock);
			if (file_exists($cache_id . '.lock')) unlink($cache_id . '.lock');
			header("HTTP/1.0 404 Not Found");
			header("Status: 404 Not Found");
			$_SERVER['REDIRECT_STATUS'] = 404;
			exit(0);
		}
	}

	$not_found=false;
}

//o-------------------------------o
//		Start Sessions
//		Persistent Browser Data
//o-------------------------------o
web::enable_sessions();

//o-------------------------------o
//		If in development
//		Do log stuff
//o-------------------------------o
if (core::get_environment() == "development")
{
	// Log Rules?
}

//o-------------------------------o
//	  Call PreProcess Filters
//o-------------------------------o
foreach (component::all() as $component) method_exists($component,"preprocess")?$component->preprocess():0;

//o-------------------------------o
//		Enable Output Compression
//o-------------------------------o
web::enable_gzip();

// Pre Proccesses Passed
// 2300 per second
// log::output_in_development("requests.log",'preprocess' . " : in " . core::benchmark_end($build_time));

//o-------------------------------o
//		Load Addons
//o-------------------------------o
component::load_components();

//o-------------------------------o
//		Generate Relationships
//o-------------------------------o
orm::generate_relationship();

log::output_in_development("requests.log",'loaded core (' . web::document_requested() . ')' . " : in " . core::benchmark_end($build_time));

$proccess_time = core::benchmark_start();

//o-------------------------------o
//		Route request to adapters
//o-------------------------------o

ob_start();
$routed = controller::dispatch_request();
$content = ob_get_clean();
echo $content;

log::output_in_development("requests.log",'dispatched (' . web::document_requested() . ')' . " : in " . core::benchmark_end($build_time));

//echo $base_directory;
//echo 'ok';
//exit(0);

if (!$routed)
{
	// If not handled by adapters
	web::not_found();
	
	if (file_exists(core::base_directory() . "/layout/404.html"))
	{
		$build_page_cache = true;
		$expiration_date = 60;
		$site_title = core::application_name();
		$site_url = web::document_url();
		ob_start();
		call('bimboo')->disable();
		call('swfobject')->disable();
		call('mt_miftree')->disable();
		call('textarea_resizer')->disable();
		core::render_html(core::base_directory() . "/layout/404.html", get_defined_vars() );
		$not_found = true;
		$content = ob_get_clean();
		if (core::get_layout() != '')
		{
			ob_start();
			core::render_html( core::get_layout(), get_defined_vars() );
			$content = ob_get_clean();
			echo $content;
		}
		else
		{
			web::send_headers();
			echo $content;
		}
	}
	else core::invalid_resource();
}

//o-------------------------------o
//	  Call PostProcess Filters
//o-------------------------------o
foreach (component::all() as $component) method_exists($component,"postprocess")?$component->postprocess(get_defined_vars()):0;

//orm::execute_query_static('END');

//o-------------------------------o
//		If in development
//		Do log stuff
//o-------------------------------o
if (core::get_environment() == "development")
{
	// Log Rules?
	log::output_in_development("requests.log",web::document_requested() . " : in " . core::benchmark_end($build_time));
}

if (core::get_environment() != "test")
{
	if (web::redirected_to()) {
		web::redirect(web::redirected_to());
	}
}

if (isset($_SESSION['sem_cache']) && ($_SESSION['sem_cache'])) {
	$build_page_cache = false;
}

if ($expiration_date == 0) {
	$build_page_cache = false;
}

if($fp = @fopen( $base_directory . "/logs/" . "requests.log" , 'a+'))
{
	$timeparts = explode(" ",microtime());
	$endtime = $timeparts[1].substr($timeparts[0],1);
	fwrite($fp, 'loaded (' . $pedido_original . " : in " . number_format($endtime-$build_time,6,".","") . ' cpu / ' . (int)(1.0/($endtime-$build_time)) . ' cps' . "\r\n" );
	fclose($fp);
}

// override defaults
if (core::get_environment() == "development")
{
	$build_page_cache = false;
}

if (!web::redirected_to()) {
	if ($build_page_cache) {
		$output_h = "\x1f\x8b\x08\x00\x00\x00\x00\x00";
		$output_s = gzcompress($content, 6);
	
		$output_c = pack('V', crc32($content));
		$output_c .= pack('V', strlen($content));	
	
		$contents_z = $output_h . $output_s . $output_c;
	
		$nome_diretorio = dirname( $cache_id . ".gz" );
		if (!is_dir($nome_diretorio))
		{
		mkdir( $nome_diretorio, 0777, true);
		}
	
		if($fp = @fopen( $cache_id . ".html.gz" , 'w')) {
		  fwrite($fp, $contents_z);
		  fclose($fp);	  
		}
	
		if($fp = @fopen( $cache_id  . ".html" , 'w')) {
		  fwrite($fp, $content);
		  fclose($fp);	  
		}
	
		touch($cache_id . ".html", time()+$expiration_date);
		touch($cache_id . ".html.gz", time()+$expiration_date);
	}
}

flock($fplock, LOCK_UN); // libera o lock
fclose($fplock);
unlink($cache_id . '.lock');

?>