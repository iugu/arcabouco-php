<?

/*
 * This file is part of the Arcabouco Framework.
 * (c) 2008 Patrick Negri <patrick@agencialobo.com.br>
 * (c) 2008 Paulo Lobo <plobo@agencialobo.com.br>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 

function cmp_strlen($a, $b)
{
	return strlen($a[0]) > strlen($b[0]);
}

function strlen_sort($a,$subkey) {
	foreach($a as $k=>$v) {
		$b[$k] = Array(strtolower($v[$subkey]),$k);
	}
	usort($b,'cmp_strlen');
	foreach($b as $key=>$val) {
		$c[] = $a[$val[1]];

	}
	return $c;
}

function sksort(&$array, $subkey="id", $sort_ascending=false) {
	if (!count($array)) return;
	if (count($array)) $temp_array[key($array)] = array_shift($array);

	foreach($array as $key => $val){
		$offset = 0;
		$found = false;
		foreach($temp_array as $tmp_key => $tmp_val)
		{
			if(!$found and strtolower($val[$subkey]) > strtolower($tmp_val[$subkey]))
			{
				$temp_array = array_merge(    (array)array_slice($temp_array,0,$offset),
											array($key => $val),
											array_slice($temp_array,$offset)
										  );
				$found = true;
			}
			$offset++;
		}
		if(!$found) $temp_array = array_merge($temp_array, array($key => $val));
	}

	if ($sort_ascending) $array = array_reverse($temp_array);

	else $array = $temp_array;
}

if (!function_exists('url_for')) {
	function url_for($action,$object,$orm=null) {
		$prms = controller::get_parameters($action,$object);
		$pattern = $prms['url'];
		
		if (($orm != null) && (count($prms['parameters']))) {
			$i = 0;
			foreach ($prms['parameters'] as $parameter) {
				$parameter_value = '';
				if (orm::is_orm($orm)) {
					if ($parameter == 'id') {
						$parameter_value = $orm->to_param();
					}
					else {
						$parameter_value = $orm->$parameter;
					}
				}
				else if (is_array($orm)) {
					if (is_string($orm[$i])) {
						$parameter_value = $orm[$i];
						break;
					}
				}
				else if (is_string($orm))
				{
					$parameter_value = $orm;
				}
				$pattern = preg_replace('/\(.+?\)/',$parameter_value,$pattern,1);
				$i++;
			}
		}
		
		
		$destination = '/' . $pattern;
		$destination = web::document_url() . preg_replace('/\/+/','/',$destination);
		return $destination;
	}
}

if (!function_exists('method_for')) {
	function method_for($action,$object) {
		$prms = controller::get_parameters($action,$object);
		return strtolower($prms['method']);
	}
}

if (!function_exists('action_for')) {
	function action_for($action,$object,$orm=null) {
		$prms = controller::get_parameters($action,$object);
		
		$pattern = $prms['url'];

		if ($orm != null) {
			foreach ($prms['parameters'] as $parameter) {
				$parameter_value = '';
				if ($parameter == 'id') $parameter_value = $orm->to_param();
				else {
					$parameter_value = $orm->$parameter;
				}
				$pattern = preg_replace('/\(.+?\)/',$parameter_value,$pattern,1);
			}
		}
		
		$destination = '/' . $pattern;
		$destination = web::document_url() . preg_replace('/\/+/','/',$destination);
		return Array($destination,strtolower($prms['method']));
	}
}

if (!function_exists('http_method')) {
	function http_method($method) {
		return strtolower($method)=='get'?'get':'post';
	}
}

if (!function_exists('restful_form')) {
	function restful_form($var) {
		return '<input type="hidden" name="_method" value="' . $var . '" />' . "\r\n";
	}
}

if (!function_exists('input_error_for')) {
	function input_error_for($field_name) {
		$input_errors = web::get_input_errors();
		if (isset($input_errors[$field_name]) && ($input_errors[$field_name] !== false)) return 'field-error';
		return '';
	}
}

if (!function_exists('form_field')) {
	function form_field(&$a,$b,$options=Array()) {
		
		$field_id = inflector::underscore(inflector::unaccent($a)) . '_' . $b;
		$field_name = inflector::underscore(inflector::unaccent($a)) . '[' . $b . ']';
		$field_value = '';
		if (isset($a->$b)) if ($a->$b != '') $field_value = $a->$b;
		
		$field_html = $a->html_for($b,$options);

		if (count($a->errors_for($b)))
		{
			$field_html = '<div class="field-error input-container">' . $field_html . '</div>';
		}
		else
		{
			$field_html = '<div class="input-container">' . $field_html . '</div>';
		}
		
		return $field_html;
	}
}

if (!function_exists('text_field')) {
	function text_field($a,$b,$options=Array()) {

		$field_id = inflector::underscore(inflector::unaccent($a)) . '_' . $b;
		$field_name = inflector::underscore(inflector::unaccent($a)) . '[' . $b . ']';
		$field_value = '';
		if (isset($a->$b)) if ($a->$b != '') $field_value = $a->$b;

		$add_class = '';
		
		if (isset($options['class'])) $add_class .= $options['class'];
		
		$add_class .= isset($options['size'])?$options['size']:' large';

		$field_html = "<input class=\"$size\" id=\"$field_id\" name=\"$field_name\" size=\"30\" type=\"text\" value=\"$field_value\" />";

		if (count($a->errors_for($b)))
		{
			$field_html = '<div class="field_error">' . $field_html . '</div>';
		}

		return $field_html;
	}
}

if (!function_exists('password_field')) {
	function password_field($a,$b,$options=Array()) {

		$field_id = inflector::underscore(inflector::unaccent($a)) . '_' . $b;
		$field_name = inflector::underscore(inflector::unaccent($a)) . '[' . $b . ']';
		$field_value = '';
		if (isset($a->$b)) if ($a->$b != '') $field_value = $a->$b;
		
		$size = isset($options['size'])?$options['size']:30;

		$field_html = "<input id=\"$field_id\" name=\"$field_name\" size=\"$size\" type=\"password\" value=\"$field_value\" />";

		if (count($a->errors_for($b)))
		{
			$field_html = '<div class="field_error">' . $field_html . '</div>';
		}

		return $field_html;
	}
}

if (!function_exists('build_menu_for')) {
	function build_menu_for($children)
	{
		$menu_html = '';
		$depth = -1;
		$flag = false;
		

		$requested = web::document_requested();
		if ($requested != '/') $requested = ltrim($requested,'/');

		$selected_url = '';
		foreach ($children as $child)
		{
			$target_url = $child->url;
			if (strpos($target_url,'http://') === false) $target_url = web::document_url() . '/' . ltrim($target_url,'/');

			if (strstr($requested,$child->url) !== false) {
				if (strlen($selected_url) < strlen($child->url)) {
					$selected_url = $child->url;
				}
			}
		}

		foreach ($children as $child)
		{
	
			$selected = '';
			$a_class = '';

			$target_url = $child->url;

			if (strpos($target_url,'http://') === false) $target_url = web::document_url() . '/' . ltrim($target_url,'/');

			if (strstr($requested,$child->url) !== false) {
				$selected = 'selected';
			}

			if (strcmp($child->url,$selected_url) == 0) {
				$a_class = ' class="active"';
			}
	
			while ($child->depth+1 > $depth) {
				if ($depth == -1) {
					$menu_html .= "<ul class=\"menu\">\n" . "<li class=\"" . $selected . "\">";
				} else {
					$menu_html .= "<ul>\n" . "<li class=\"" . $selected . "\">";
				}
				$flag = false;
				$depth++;
			}
			while ($child->depth+1 < $depth) {
				$menu_html .= "</li>\n" . "</ul>\n";
				$depth--;
			}
			if ($flag) {
				$menu_html .= "</li>\n" . "<li class=\"" . $selected . "\">";
				$flag = false;
			}
	
			$menu_html .= '<a href="' . $target_url . '"' . $a_class . '><span>' . $child->name;
			if ($depth == 0) $menu_html .= ' &raquo;';
			$menu_html .= '</span></a>';
			$flag = true;
		}
	
		while ($depth-- > -1) {
			$menu_html .= "</li>\n" . "</ul>\n";
		}
		return $menu_html;
	}
}

if (!function_exists('build_menu_for_2')) {
	function build_menu_for_2($children)
	{
		$menu_html = '';
		$depth = 0;
		$flag = false;
		
		$requested = web::document_requested();
		if ($requested != '/') $requested = ltrim($requested,'/');
		
		$selected_url = '';
		foreach ($children as $child)
		{
			$target_url = $child['url'];
			if (strpos($target_url,'http://') === false) $target_url = web::document_url() . '/' . ltrim($target_url,'/');
			
			if (strpos($requested,$child['url']) === 0) {
				if (strlen($selected_url) < strlen($child['url'])) {
					$selected_url = $child['url'];
				}
			}
		}
		
		$total = count($children);
		$i=0;
		foreach ($children as $child)
		{

			$selected = '';
			$a_class = '';
			$others = '';

			$target_url = $child['url'];

			if (strpos($target_url,'http://') === false) $target_url = web::document_url() . '/' . ltrim($target_url,'/');

			if (strstr($requested,$child['url']) !== false) {
				$selected = 'selected';
			}
			
			if (strcmp($child['url'],$selected_url) == 0) {
				$a_class = ' class="active"';
			}
			
			if ($i==0) {
				if ($others != '') $others .= ' ';
				$others .= 'first';
			}
			
			if ($i==($total)-1) {
				if ($others != '') $others .= ' ';
				$others .= 'last';
			}
			
			$others = ' ' . $others;

			while ($child['depth']+1 > $depth) {
				if ($depth == -1) {
					$menu_html .= "<ul class=\"menu\">\n" . "<li class=\"" . $selected . "\">";
				} else {
					$menu_html .= "<ul>\n" . "<li class=\"" . $selected . ' ' . $others . "\">";
				}
				$flag = false;
				$depth++;
			}
			while ($child['depth']+1 < $depth) {
				$menu_html .= "</li>\n" . "</ul>\n";
				$depth--;
			}
			if ($flag) {
				$menu_html .= "</li>\n" . "<li class=\"" . $selected . $others . "\">";
				$flag = false;
			}

			$menu_html .= '<a href="' . $target_url . '"' . $a_class . '><span>' . $child['name'];
			//if ($depth == 0) $menu_html .= ' &raquo;';
			$menu_html .= '</span></a>';
			$flag = true;
			$i++;
		}

		while ($depth-- > 0) {
			$menu_html .= "</li>\n" . "</ul>\n";
		}
		return $menu_html;
	}
}

if (!function_exists('call'))
{

	class call_object {
		function __get($m) {
			return false;
		}
		
		function __call($m, $a) {
			return false;
		}
	}

	function call($object) {
	
		$module = component::find($object);
		if ($module)
		{
			return $module;
		}
		else
		{
			return new call_object();
		}
	}
}

if (!function_exists('redirect_to')) {
	function redirect_to($url) {
		header("HTTP/1.1 301 Moved Permanently");
		web::redirect_to( $url );
	}
}

if (!function_exists('error_messages_for')) {
	function error_messages_for($object,$default_message='') {
		$all_errors = $object->get_errors();
		$error_count = count($all_errors);
		if ($error_count == 0) return '';
		$error_msg = $default_message!=''?$error_count . ' ' . inflector::pluralize_if( _("error"),$error_count) . ' ' . $default_message:$error_count . ' ' . inflector::pluralize_if( _("error"),$error_count) . ' ' . sprintf(_("prohibited this %s from being saved"), _(strtolower($object)) );
		$there_msg = _("There were problems with the following fields:");
		$li_msgs = '';
		foreach ($all_errors as $error) {
			$li_msgs .= "<li>" . mb_strtoupper($error['msg'][0],'utf-8') . substr($error['msg'],1) . "</li>\r\n";
		}
		$error_msg =<<<ENDOFHTML
			<h3>$error_msg</h3>
			<p>$there_msg</p>
			<ul>
				$li_msgs
			</ul>
ENDOFHTML;
		return $error_msg;
	}
}

if (!function_exists('render_html')) {
	function render_html($file,$vars=Array(),$format='normal') {
	
		if (strpos($file,'.js')!==FALSE) {
			web::configure_type_and_charset('Content-type: text/javascript; charset=UTF-8');
		}
		
		ob_start();
		if (file_exists($file))
		{
			core::render_html($file,$vars);
			
		}
		else if (file_exists( core::base_directory() . '/layout/' . basename($file) )) {
			core::render_html(core::base_directory() . '/layout/' . basename($file),$vars);
		}
		else
		{
			$trace  = debug_backtrace();
			$directory = core::transform_directory(dirname($trace[0]['file'])) . '/';
			$component_name = substr($file,0,strpos($file,'.'));
			
			// Last TRY
			$cmp_obj = call($component_name);
			$path = dirname(__FILE__);

			if (method_exists($cmp_obj,'path')) $path = rtrim($cmp_obj->path(),'/');
			
			if (file_exists($path . '/' . $file))
			{
				core::render_html($path . '/' . $file,$vars);
			}
			else if (file_exists($directory . $file))
			{
				core::render_html($directory . $file, $vars);
			}
		}
		$trace  = debug_backtrace();
		$directory = core::transform_directory(dirname($trace[0]['file'])) . '/';
		$contents = ob_get_clean();
		
		if ($format=='js') {
			$patterns = array (
					"/\r/",
					"/\'/",
					"/\"/"
			);
			$replace = array (
					"\\\r",
					"\\\'",
					"\\\""
			);
			$contents = preg_replace($patterns, $replace, $contents);

		}
		else
		{
		
			$component_name = substr($file,0,strpos($file,'.'));
			if (core::get_layout() == '') {
			
				$layout_filename = $directory . $component_name . '.layout.php';
				
				$final_layout = '';
				
				if (file_exists($layout_filename)) $final_layout = $layout_filename;
				else if (file_exists(core::base_directory() . '/layout/' . basename($layout_filename))) {
					$final_layout = core::base_directory() . '/layout/' . basename($layout_filename);
				}
				else {
					$default_layout = call('application_visuals')->get_layout();
					if ($default_layout !== false) $final_layout = $default_layout;
				}
				
				if ($final_layout != '') {
					ob_start();
					core::render_html($final_layout,array_merge(Array('content'=>$contents),$vars));
					$contents = ob_get_clean();
				}
			}
		}
		echo $contents;
	}
}

if (!function_exists('render_part')) {
	function render_part($file,$vars=Array(),$format='normal',$return=0) {
	
		if ($return == 0) {
			if (strpos($file,'.js')!==FALSE) {
				web::configure_type_and_charset('Content-type: text/javascript; charset=UTF-8');
			}
		}

		ob_start();
		if (file_exists($file))
		{
			core::render_html($file,$vars);
		}
		else
		{
			$trace  = debug_backtrace();
			$directory = core::transform_directory(dirname($trace[0]['file'])) . '/';
			$component_name = substr($file,0,strpos($file,'.'));

			// Last TRY
			$cmp_obj = call($component_name);
			$path = dirname(__FILE__);
			if (method_exists($cmp_obj,'path')) $path = rtrim($cmp_obj->path(),'/');

			if (file_exists($path . '/' . $file))
			{
				core::render_html($path . '/' . $file,$vars);
			}
			else if (file_exists($directory . $file))
			{
				core::render_html($directory . $file, $vars);
			}
		}
		$trace  = debug_backtrace();
		$directory = core::transform_directory(dirname($trace[0]['file'])) . '/';
		$contents = ob_get_clean();
		if ($format=='js') {
			$patterns = array (
					"/\r/",
					"/\'/",
					"/\"/"
			);
			$replace = array (
					"\\\r",
					"\\\'",
					"\\\""
			);
			$contents = preg_replace($patterns, $replace, $contents);

		}
		
		if ($return == 0) echo $contents;
		else return $contents;
	}
}

if (!function_exists('set_layout')) {
	function set_layout($file,$vars=Array()) {
		if (file_exists($file))
		{
			core::set_layout($file);
		}
		else
		{
			$trace  = debug_backtrace();
			$directory = core::transform_directory(dirname($trace[0]['file'])) . '/';
			if (file_exists($directory . $file))
			{
				core::set_layout($directory . $file);
			}
		}
	}
}

if (!function_exists('set_title')) {
	function set_title($title) {
		web::set_title($title);
	}
}

if (!function_exists('strip_scripts')) {
	function strip_scripts($string) {
		//do
		//$string = preg_replace("/<script[^>]*>.*</script[^>]*>/", "", $string);
		//while (eregi_replace("<script[^>]*>.*</script[^>]*>", "", $string)==1);
		$string = preg_replace("/<script[^>]*>.+?<\/script[^>]*>/", "", $string);
		do
		$string = eregi_replace("<script[^>]*>", "", $string);
		while (eregi_replace("<script[^>]*>", "", $string)==1);
		do
		$string = eregi_replace("<.* on.*=.*>", "", $string);
		while (eregi_replace("<.* on.*=.*>", "", $string)==1);
		do
		$string = eregi_replace("<.*=.*javascript:.*>", "", $string);
		while (eregi_replace("<.*=.*javascript:.*>", "", $string)==1);
		do
		$string = eregi_replace("<.*type=.*text/x-scriptlet.*>", "", $string);
		while (eregi_replace("<.*type=.*text/x-scriptlet.*>", "", $string)==1);
		do
		$string = eregi_replace("<embed.*AllowScriptAccess=.*always.*>", "", $string);
		while (eregi_replace("<embed.*AllowScriptAccess=.*always.*>", "", $string)==1);
		return $string;
	}
}

if (!function_exists('find_content_image')) {

	function find_content_image( $conteudo_texto ) {
		preg_match_all( "/(<img.*?>)/", $conteudo_texto, $matches,PREG_SET_ORDER);
		
		foreach ($matches as $match)
		{
			$imagem = $match[1];

			$src = 0;		
			if (preg_match("/src=\"(.*?)\"/i", $match[1], $submatches))
			{
				$src = $submatches[1];
			}

			$src = urldecode($src);

			if ($src)
			{
				return $src;
				break;
			}
		}
		return false;
	}
}

if (!function_exists('get_media_directory')) {
	function get_media_diretory( $rowid=-1, $prefix='', $sufix='' )
	{
		if ($rowid != -1) {
			return core::transform_directory(core::base_directory() . '/media/' . $prefix . ($rowid%8192) . '/' . $rowid . '/' . $sufix);
		}
		return core::transform_directory(core::base_directory() . '/media/' . $prefix . $sufix);
	}
}

if (!function_exists('byte_format')) {
	function byte_format($bytes) 
	{
		if ($bytes < 1024) return $bytes . 'b';
		$size = $bytes / 1024;
		if($size < 1024)
			{
			$size = number_format($size, 0);
			$size .= 'K';
			} 
		else 
			{
			if($size / 1024 < 1024) 
				{
				$size = number_format($size / 1024, 0);
				$size .= 'M';
				} 
			else if ($size / 1024 / 1024 < 1024)  
				{
				$size = number_format($size / 1024 / 1024, 0);
				$size .= 'G';
				} 
			}
		return $size;
	}
}

if(!function_exists('truncate_text'))
{
	function truncate_text($string, $limit, $break=" ", $pad="...") 
	{ 
		// return with no change if string is shorter than $limit  
		if(strlen($string) <= $limit) return $string;
		$limit -= strlen($pad);
		$string = substr($string, 0, $limit); 
		if(false !== ($breakpoint = strrpos($string, $break)))
		{
			$string = substr($string, 0, $breakpoint);
		}
		return $string . $pad;
	}
}

if(!function_exists('make_url'))
{
	function make_url($title) {
		$title = mb_strtolower($title, "utf-8");
		//$titulo_url = strtr($titulo_url, "çáàäâãéèëêíìîïóòôõöúùûü", "caaaaaeeeeiiiiooooouuuu");
		$title = preg_replace('~[^\\pL0-9_]+~u', '-', $title); // substitutes anything but letters, numbers and '_' with separator 
		$search = explode(",","ç,æ,œ,á,é,í,ó,ú,à,è,ì,ò,ù,ä,ë,ï,ö,ü,ÿ,â,ê,î,ô,û,å,e,i,ø,u,ã,õ,',\",?,!,.,=,:,#,\",%,$,@,*,(,),¨,&,´,^,~,ª,º");
		$replace = explode(",","c,ae,oe,a,e,i,o,u,a,e,i,o,u,a,e,i,o,u,y,a,e,i,o,u,a,e,i,o,u,a,o, , , , , , , , , , , , , , , , , ,,,,,");
		$title = str_replace($search, $replace, $title);
		$title =  preg_replace('/\s\s+/', ' ', $title);
		$title = str_replace(" ","-",$title);
		$title = str_replace(",","",$title);
		$title = str_replace("--","-",$title);
		$title = trim($title,'-');
		return $title;
	}
}

if(!function_exists('make_filename'))
{
	function make_filename($title) {
		$title = mb_strtolower($title, "utf-8");
		$title = preg_replace('~[^\\pL0-9_.]+~u', '-', $title); // substitutes anything but letters, numbers and '_' with separator 
		$search = explode(",","ç,æ,œ,á,é,í,ó,ú,à,è,ì,ò,ù,ä,ë,ï,ö,ü,ÿ,â,ê,î,ô,û,å,e,i,ø,u,ã,õ,',\",?,!,=,:,#,\",%,$,@,*,(,),¨,&,´,^,~,ª,º");
		$replace = explode(",","c,ae,oe,a,e,i,o,u,a,e,i,o,u,a,e,i,o,u,y,a,e,i,o,u,a,e,i,o,u,a,o");
		$title = str_replace($search, $replace, $title);
		$title =  preg_replace('/\s\s+/', ' ', $title);
		$title = str_replace(" ","-",$title);
		$title = str_replace(",","",$title);
		$title = str_replace("--","-",$title);
		$title = trim($title,'-');
		return $title;
	}
}

if(!function_exists('sec2hms'))
{
	function sec2hms ($sec, $padHours = false) 
	{
		// holds formatted string
		$hms = "";
	
		// there are 3600 seconds in an hour, so if we
		// divide total seconds by 3600 and throw away
		// the remainder, we've got the number of hours
		$hours = intval(intval($sec) / 3600); 
	
		// add to $hms, with a leading 0 if asked for
		$hms .= ($padHours) 
			  ? str_pad($hours, 2, "0", STR_PAD_LEFT). ':'
			  : $hours. ':';
	
		// dividing the total seconds by 60 will give us
		// the number of minutes, but we're interested in 
		// minutes past the hour: to get that, we need to 
		// divide by 60 again and keep the remainder
		$minutes = intval(($sec / 60) % 60); 
	
		// then add to $hms (with a leading 0 if needed)
		$hms .= str_pad($minutes, 2, "0", STR_PAD_LEFT). ':';
	
		// seconds are simple - just divide the total
		// seconds by 60 and keep the remainder
		$seconds = intval($sec % 60); 
	
		// add to $hms, again with a leading 0 if needed
		$hms .= str_pad($seconds, 2, "0", STR_PAD_LEFT);
	
		// done!
		return $hms;
	}
}

if (!function_exists('depth_decode')) {
	// TODO, default property and depth is wrong
	function depth_decode($collection)
	{
		$trees = array();
		$l = 0;

		if (count($collection) > 0) {
				// Node Stack. Used to help building the hierarchy
				$stack = array();

				foreach ($collection as $node) {
						$item = $node;
						$item['children'] = array();

						// Number of stack items
						$l = count($stack);

						// Check if we're dealing with different levels
						while($l > 0 && $stack[$l - 1]['property']['depth'] >= $item['property']['depth']) {
								array_pop($stack);
								$l--;
						}

						// Stack is empty (we are inspecting the root)
						if ($l == 0) {
								// Assigning the root node
								$i = count($trees);
								$trees[$i] = $item;
								$stack[] = & $trees[$i];
						} else {
								// Add node to parent
								$i = count($stack[$l - 1]['children']);
								$stack[$l - 1]['children'][$i] = $item;
								$stack[] = & $stack[$l - 1]['children'][$i];
						}
				}
		}

		return $trees;
	}
}

if (!function_exists('prepare_menu_for_decode')) {
	function prepare_menu_for_decode(&$objects)
	{
		$array = Array();
		foreach ($objects as $object) {
			$array[] = Array(
				'url'=>$object->url,
				'name'=>$object->name,
				'property'=>Array('depth'=>$object->depth)
			);
		}
		return depth_decode($array);
	}
}

if (!function_exists('depth_encode')) {
	function depth_encode(&$array,$objects,$current_depth=0)
	{
		if (!isset($objects['children'])) return;
		foreach ($objects['children'] as $object) {
	
			if ($object['children'] && (count($object['children']))) {
				$array[] = Array(
					'name'=>$object['name'],
					'url'=>$object['url'],
					'depth'=>$current_depth
				);
				depth_encode($array,$object,$current_depth+1);
			} else {
				$array[] = Array(
					'name'=>$object['name'],
					'url'=>$object['url'],
					'depth'=>$current_depth
				);
			}
		}
		
		if (count($array) == 1) {
			if (isset($array[0][0]['name'])) {
				$array = $array[0];
			}
		}
		
		return;
	}
}

// obsolete version helpers

if (!function_exists('bimboo1_TopMenu')) {
	function bimboo1_TopMenu() {
		return build_menu_for_2( bimboo_getTopMenu(Array(Array('name'=>'Principal','url'=>'/','depth'=>0))) );
	}
}

if (!function_exists('bimboo1_Breadcrumbs')) {
	function bimboo1_Breadcrumbs($page_title,$separator = '/') {
		$breadcrumb = content_management_system::current_breadcrumb();
		$bcopy = $breadcrumb;
		array_pop($bcopy);
		$links_html = '';
		foreach ($bcopy as $link) {
			$links_html .= '<a href="' . $link['url'] . '"><span>' . $link['name'] . '</span></a> ' . $separator . ' ';
		}
		$links_html .= '<span>' . $page_title . '</span>';
		return $links_html;
	}
}

?>