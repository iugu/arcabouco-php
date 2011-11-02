<?

/*
 * This file is part of the Arcabouco Framework.
 * (c) 2008 Patrick Negri <patrick@agencialobo.com.br>
 * (c) 2008 Paulo Lobo <plobo@agencialobo.com.br>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 
define("ARCABOUCO_VERSION","2.0.0");

class core
{
	protected static $environment = 'production';
	protected static $stack;
	public static 	 $defaults;
	protected static $__benchmark;
	public static $__last_bench = 0;
	
	protected static $__content_section_caching = Array();
	
	protected static $layout = '';
	
	static function get_environment()
	{
		return self::$environment;
	}
	
	static function set_environment($name='production')
	{
		switch ( $name )
		{
			case 'prodution':
				self::$environment = 'production';
			break;
			case 'development':
				self::$environment = 'development';
			break;
			case 'test':
				self::$environment = 'test';
			break;
			default:
				self::$environment = 'production';
			break;
		}
	}
	
	static function has_cdn() {
		if ( (isset(core::$defaults['use_cdn'])) &&
			 (core::$defaults['use_cdn'] == true)
		   ) {
		   return true;
		}
		return false;
	}
	
	static function cdn_host() {
		return core::$defaults['cdn_host'];
	}
	
	static function system_timezone() {
		return core::$defaults['system_timezone'];
	}
	
	static function has_feature($feature='') {
		if ( (isset(core::$defaults['features'])) &&
			 (strpos(core::$defaults['features'],$feature) !== false)
		   ) {
		   return true;
		}
		return false;
	}
	
	static function detect_development_environment()
	{
		if (defined('SANDBOX')) { self::set_environment('test'); }
		else if ( (isset($_SERVER['SERVER_PORT'])?$_SERVER['SERVER_PORT']:80) != "80") self::set_environment('development');
		
	}
	
	static function throw_errors($errno, $errmsg, $filename, $linenum, $vars)
	{
		ob_start();
		debug_print_backtrace();
		self::$stack = ob_get_clean();
		if ($errno == E_WARNING) return true;
		return false;
	}
	
	static function ob_ignoreerrors($buffer, $mode)
	{
		$error = error_get_last();

		if ($error)
		{		
			header("Content-type: text/html; charset=UTF-8");
			$buffer = '<div id="mpadv_error"><div style="padding:20px;color:#999"><div style="font:bold 60px verdana;">Ops!</div><div style="font:bold 18px verdana;">Ocorreu um erro interno ao executar a aplicação...<br /><br />Tente novamente mais tarde.</div></div></div>';
		}
		return $buffer;
	}
	
	static function ob_checkerrors($buffer, $mode)
	{
		$error = error_get_last();
		
		if ($error)
		{		
			$errno = $error["type"];
			$errmsg = $error["message"];
			$filename = $error["file"];
			$linenum = $error["line"];

			header("Content-type: text/html; charset=UTF-8");
			$buffer = '<div id="mpadv_error" style="background:#F0F0F0;color:#000;border:2px solid #FF0000;float:left"><div style="padding:5px;font:bold 14px verdana;color:#FFF;background:#CC0000">Ops! Error in code</div><div style="padding:10px;font:12px verdana;line-height:16px;color:#000">Its not possible to continue because of:<br /><br /><strong>Error type:</strong><br />'. $errno . '<br /><br /><strong>Message:</strong><br /><ul style="list-style-type:square"><li>' . $errmsg . '</li></ul><strong>File and line where error occurs:</strong><br />' . $filename . ', line ' . $linenum . '<br /><br /><strong>Debug Backtrace:</strong><br /><pre>' . self::$stack . '</pre></div></div><div style="clear:both">&nbsp;</div>';
		}
		return $buffer;
	}
	
	static function benchmark_start()
	{
//		if (self::get_environment() == "development")
//		{
			$timeparts = explode(" ",microtime());
			return self::$__benchmark = $timeparts[1].substr($timeparts[0],1);
//		}
		return 0;
	}
	
	static function benchmark_end($starttime=NULL)
	{
//		if (self::get_environment() == "development")
//		{
			$timeparts = explode(" ",microtime());
			$endtime = $timeparts[1].substr($timeparts[0],1);
			if ($starttime == NULL) $starttime = self::$__benchmark;
			self::$__last_bench = $endtime-$starttime;
			return number_format(self::$__last_bench,6,".","") . ' cpu / ' . (int)(1.0/(self::$__last_bench)) . ' cps';
//		}
		return 0;
	}
	
	static function enable_advanced_error_handling()
	{
		global $global_debug;
		if ($global_debug==true) {
			error_reporting(E_ALL);
			ob_start('core::ob_checkerrors');
			set_error_handler('core::throw_errors');
		} else {
			error_reporting(E_ALL);
			ob_start('core::ob_ignoreerrors');
			set_error_handler('core::throw_errors');
		}
	}
	static function root_directory()
	{
		$directory = rtrim(str_replace("\\","/",dirname(__FILE__)),"/");
		return substr($directory,0,strrpos($directory,"/"));
	}
	static function base_directory() {
		global $base_directory;
		return $base_directory;
	}
	static function relative_path($target_directory)
	{
		return str_replace(core::root_directory(),'', str_replace("\\","/",$target_directory) );
	}
	static function relative_base_path($target_directory)
	{
		return str_replace(core::base_directory(),'', str_replace("\\","/",$target_directory) );
	}
	static function transform_directory($dir)
	{
		return str_replace("\\","/",$dir);
	}
	static function list_dir($directory,$recursive=false)
	{
		$list = Array();
		$scanlisting = scandir($directory);
		foreach($scanlisting as $key => $value)
		{
			if (is_dir("$directory/$value") == true && $value != '.' && $value != '..')
			{
				array_push($list,$directory . $value . "/");
				if ($recursive)
				{
					$list=array_merge($list,self::list_dir($directory . $value . "/",$recursive));
				}
			}
		}
		return $list;
	}
	static function list_files($directory,$recursive=false)
	{
		$list = Array();
		if (!is_dir($directory)) return $list;
		$scanlisting = scandir($directory);
		foreach($scanlisting as $key => $value)
		{
			if (is_file("$directory/$value") == true && $value != '.' && $value != '..')
			{
				array_push($list,$directory . $value);
			}
			if (is_dir("$directory/$value") == true && $value != '.' && $value != '..')
			{
				if ($recursive)
				{
					$list=array_merge($list,self::list_files($directory . $value . "/",$recursive));
				}
			}
		}
		return $list;
	}
	
	static function list_files_pattern($sDir, $sPattern, $nFlags = NULL)
	{
		$sDir = escapeshellcmd($sDir);
	
		// Get the list of all matching files currently in the
		// directory.
	
		$aFiles = glob("$sDir/$sPattern", $nFlags);
	
		// Then get a list of all directories in this directory, and
		// run ourselves on the resulting array.  This is the
		// recursion step, which will not execute if there are no
		// directories.
	
		foreach (glob("$sDir/*", GLOB_ONLYDIR) as $sSubDir)
		{
		$aSubFiles = core::list_files_pattern($sSubDir, $sPattern, $nFlags);
		$aFiles = array_merge($aFiles, $aSubFiles);
		}
	
		// The array we return contains the files we found, and the
		// files all of our children found.
	
		return $aFiles;
	}
	
	static function create_image($fImg,$foImg,$largura,$altura,$tipo=1,$cor = Array(255,255,255))
	{
		global $raiz;

		if (file_exists($foImg)) {
			return $foImg;
		}

		if (!file_exists($fImg) && (strpos($fImg,'http')===false))
		{
			return false;
		}

		if (!is_dir( dirname($foImg) ))
		{
			mkdir( dirname($foImg) , 0777, true);
		}

		$imagem = null;
		if (strpos($fImg,"gif") !== FALSE)
		{
			$imagem = imagecreatefromgif( $fImg );
		}
		else if (strpos($fImg,"png") !== FALSE)
		{
			$imagem = imagecreatefrompng( $fImg );
		}
		else
		{
			$imagem = imagecreatefromjpeg( $fImg );
			//create_image
		}
		$tamanho_imagem = getimagesize($fImg);

		$proporcoes = $tamanho_imagem[0]/$tamanho_imagem[1];

		$largura_total = $largura_destino = $largura;
		$altura_total = $altura_destino = $altura;

		if ($tipo == 0)
		{
			$tamanho = $largura;
			if ($tamanho_imagem[0] > $tamanho_imagem[1])
			{
				// Largura
				$largura_destino = $tamanho;
				$altura_destino = $tamanho / $proporcoes;
			}
			else
			{
				// Altura
				$altura_destino = $tamanho;
				$largura_destino = $tamanho * $proporcoes;
			}
		}

		if ( ($tipo == 2) || ($tipo == 3) )
		{
			$tamanho = $largura;
			if ($tamanho_imagem[0] > $tamanho_imagem[1])
			{
				// Largura
				$largura_destino = $tamanho;
				$altura_destino = $tamanho / $proporcoes;
			}

			if ($altura_destino < $altura)
			{
				// Largura
				$altura_destino = $tamanho;
				$largura_destino = $tamanho * $proporcoes;
			}
		}

		$imagem_nova = null;

		if ($tipo != 3)
		{
			$imagem_nova = imagecreatetruecolor($largura_destino,$altura_destino);
		}
		else
		{
			$imagem_nova = imagecreatetruecolor($largura,$altura);
		}

		$cor = imagecolorallocate($imagem_nova, $cor[0], $cor[1], $cor[2]);
		imagefill($imagem_nova, 0, 0, $cor);

		$largura_imagem = $tamanho_imagem[0];
		$altura_imagem = $tamanho_imagem[1];

		if ((($largura_imagem < $largura_destino) && ($altura_imagem < $altura_destino)))
		{
			$largura_destino = $largura_imagem;
			$altura_destino = $altura_imagem;
		}
		else
		{
			if ($largura_destino/$altura_destino > $proporcoes) {
				$largura_destino = $altura_destino*$proporcoes;
			}
			else
			{
				$altura_destino = $largura_destino/$proporcoes;
			}
		}

		$posicao_x = 0;
		$posicao_y = 0;

		if ($tipo == 1)
		{
			$posicao_x = ($largura_total/2)-($largura_destino/2);
			$posicao_y = ($altura_total/2)-($altura_destino/2);
		}

		if ($tipo == 3)
		{
			$posicao_x = ($largura/2)-($largura_destino/2);
			$posicao_y = ($altura/2)-($altura_destino/2);
		}

		imagecopyresampled( $imagem_nova, 
							$imagem,
							$posicao_x,
							$posicao_y,
							0,
							0,
							$largura_destino,
							$altura_destino,
							$tamanho_imagem[0],
							$tamanho_imagem[1] );

		if (strpos($foImg,"gif") !== FALSE)
		{
			imagegif( $imagem_nova, $foImg );
		}
		else if (strpos($foImg,"png") !== FALSE)
		{
			imagepng($imagem_nova, $foImg);
		}
		else
		{
			imagejpeg($imagem_nova, $foImg ,95);
		}

		imagedestroy($imagem);
		imagedestroy($imagem_nova);
		return $foImg;
	}
	
	static function welcome_page()
	{
		web::configure_type_and_charset();
		
		$file = 'welcome';
		
		$lang_prefix = '';
		if (web::get_browser_language() != 'en') $lang_prefix = '_' . web::get_browser_language();
		
		if (file_exists(core::root_directory() . '/core/html/' . $file . $lang_prefix . '.html'))
		{
			include(core::root_directory() . '/core/html/' . $file . $lang_prefix . '.html');
			return true;
		}
		if (file_exists(core::root_directory() . '/core/html/' . $file . '.html'))
		{
			include(core::root_directory() . '/core/html/' . $file . '.html');
			return true;
		}
	}
	
	static function invalid_resource()
	{
		echo '<div id="mpadv_error" style="background:#F0F0F0;color:#000;border:2px solid #FF0000;float:left"><div style="padding:5px;font:bold 1px verdana;color:#FFF;background:#CC0000">Ops! Not Found</div><div style="padding:10px;font:12px verdana;line-height:16px;color:#000">The resource you are trying to access doesnt exists</div></div><div style="clear:both">&nbsp;</div>';
	}
	
	static function match_html_elements($tag_type,$html,$start=0,$count=-1)
	{
		$elm = Array();
		$tag_start = "<" . $tag_type;
		$tag_end = "</" . $tag_type . ">";
		while ( $position=strpos($html,$tag_start,$start) )
		{
			$i = $position;
			$size = strlen($html);
			$nested_count=1;
			while ($i < $size)
			{
				if (substr($html,$i,strlen($tag_start)) == $tag_start)
				{
					$nested_count++;
				}
				if (substr($html,$i,strlen($tag_end)) == $tag_end)
				{
					$nested_count--;
					if ($nested_count == 1) break;
				}
				$i++;
			}
			array_push($elm, Array($position,$i+strlen($tag_end)));
			if ($count != -1)
			{
				if ($count >= count($elm)) return $elm;
			}
			$start = $i;
		}
		return $elm;
	}
	
	static function find_html_by_regex($expression,$html_data,$tag_start=1,$tag_name=2,$tag_end=-1)
	{
		$found_html = preg_match_all("/" . $expression . "/i",$html_data,$matched_elements,PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
		
		$elements = Array();
		
		
		
		foreach ($matched_elements as $element)
		{
			
			$tag = $element[$tag_name][0];
			$tag_start_position = $element[$tag_start][1];
			$tag_end_position = 0;
			if ($tag_end==-1)
			{
				$tag_end_position = $element[ count($element)-1 ][1]+1;
			}
			else
			{
				$tag_end_position = $element[ $tag_end ][1]+1;
			}
			$complete_tag = substr($html_data,$tag_start_position,$tag_end_position-$tag_start_position);
			
			$elements[] = Array(
				'tag' => $tag,
				'start' => $tag_start_position,
				'end'	=> $tag_end_position,
				'html'	=> $complete_tag
			);
		}
		
		return $elements;
	}
	
	static function clear_html($html)
	{
		$html = preg_replace("/\s\s/"," ",$html);
		$html = preg_replace("/ +>/",">",$html);
		$html = preg_replace("/ +\"/","\"",$html);
		$html = preg_replace("/=\"\s/","=\"",$html);
		return $html;
	}
	
	static function template_clear_html($html)
	{
		$html = preg_replace("/ +>/",">",$html);
		$html = preg_replace("/ +/"," ",$html);
		$html = preg_replace("/ +\"/"," \"",$html);
		$html = preg_replace("/=\"\s/","=\"",$html);
		return $html;
	}

	static function template_value_array_first($array)
	{
		return reset($array);
	}

	static function template_value_array_end($array)
	{
		return end($array);
	}
	
	static function template_value_array_pop($array)
	{
		array_pop($array);
		return $array;
	}
	
	static function template_value_array_slice($array,$offset,$length)
	{
		return array_slice( $array, $offset, $length );
	}
	
	static function template_value_array_pop_first($array)
	{
		array_shift($array);
		return $array;
	}

	static function template_value_array_sort($array)
	{
		sort($array);
		return $array;
	}

	static function template_value_strip_newlines($value)
	{
		return str_replace("\n",'',str_replace("\r",'',$value));
	}

	static function template_value_newline_to_br($value)
	{
		return str_replace("\n",'<br />',str_replace("\r",'',$value));
	}

	static function template_value_size($value)
	{
		if (is_array($value)) return count($value);
		else if (is_string($value)) return mb_strlen($value,'utf-8');
	}

	static function template_format_date($value,$format)
	{
		if (strcmp($value,'now') == 0) return core::format_date(core::time(),$format,1);
		else return core::format_date($value,$format,1);
	}
	
	static function template_render_editable_area($search_variable,$docid,$cache_time=0) {
		global $breadcrumb;
		
		$co_name = 'earea_' . $search_variable . $docid;
		
		if ($cache_time > 0) {
			// @template area caching (LOL) - Unbeliveable
			$object = orm::get_cached_var($co_name);
			if ($object) return $object;
		}
		
		/*
		if (!isset(self::$__content_section_caching[$docid])) {
			$all_document_sections = orm::search('content_section','reference_id=' . $docid);
			if ($all_document_sections) {
				self::$__content_section_caching[$docid] = Array();
				foreach ($all_document_sections as $document_section) {
					self::$__content_section_caching[$docid][$document_section->name] = $document_section;
				}
			}
		}
		
		$section = null;
		if (isset(self::$__content_section_caching[$docid][$search_variable])) {
			$section = self::$__content_section_caching[$docid][$search_variable];
		}
		else {
			return 'Erro em Area Editável #50';
		}
		*/
		$section = orm::search_one('content_section','name="' . $search_variable . '" and reference_id=' . $docid);
		
		$content_text = '';
		if ($section) {
			
			$current_section_components = $section->content_section_component->find(ALL,Array('order'=>'position'));
			
			foreach ($current_section_components as $section_component) {
				$content_text .= $section_component->render($section->ROWID);
			}
		}
		
		if ((strlen($content_text) == 0) && (strpos($search_variable,'sra_') !== FALSE)) {
			$bcopy = content_management_system::current_breadcrumb();
			array_pop($bcopy);
			$found = false;
			foreach ($bcopy as $previous_item) {
				$item_url = ltrim(str_replace(web::document_url(),'',$previous_item['url']),'/');
				if ($item_url == '') $item_url = '/';
				$page = orm::search_one('content_page','url="' . $item_url . '" and published_at!=0 and published_at<=' . core::time());
				if ($page) {
					$section = orm::search_one('content_section','name="' . $search_variable . '" and reference_id=' . $page->ROWID);
					if ($section) {
						$current_section_components = $section->content_section_component->find(ALL,Array('order'=>'position'));
						foreach ($current_section_components as $section_component) {
							$content_text .= $section_component->render($section->ROWID);
						}
						if (strlen($content_text)) {
							$found = true;
							break;
						}
					}
				}
			}
			if ($found == false) {
				$page = orm::search_one('content_page','url="/" and published_at!=0 and published_at<=' . core::time());
				if ($page) {
					$section = orm::search_one('content_section','name="' . $search_variable . '" and reference_id=' . $page->ROWID);
					if ($section) {
						$current_section_components = $section->content_section_component->find(ALL,Array('order'=>'position'));
						foreach ($current_section_components as $section_component) {
							$content_text .= $section_component->render($section->ROWID);
						}
					}
				}
			}
		}
		
		if ($cache_time > 0) {
			orm::set_cached_var($co_name,$content_text,$cache_time);
		}
		
		return $content_text;
	}
	
	static function template_render_unique_editable_area($search_variable,$cache_time) {
		
		//$compiled_string .= '$section = orm::search_one(\'content_section\',\'name="' . $search_variable . '" and type=2 and reference_id=-1\');' . "\r\n";	
		$co_name = 'uearea_' . $search_variable;
		if ($cache_time > 0) {
			// @template area caching (LOL) - Unbeliveable
			$object = orm::get_cached_var($co_name);
			if ($object) return $object;
		}
		
		$section = orm::search_one('content_section','name="' . $search_variable . '" and type=2 and reference_id=-1');
		$content_text = '';
		if ($section) {
			$current_section_components = $section->content_section_component->find(ALL,Array('order'=>'position'));
			foreach ($current_section_components as $section_component) {
				$content_text .= $section_component->render($section->ROWID);
			}
		}
		if (strlen($content_text) == 0) {
			$content_text = '';
		}
		
		if ($cache_time > 0) {
			orm::set_cached_var($co_name,$content_text,$cache_time);
		}
		
		return $content_text;
	}

	static function template_get_variable($variable,$child_s)
	{
		$childs = Array();
		if ( ($found_dot = strpos($child_s,'.')) !== false ) {
			$childs = explode('.',$child_s);
		}
		else if (strlen($child_s) > 0) $childs = Array($child_s);

		if (isset($variable)) {
			if (count($childs) == 0) {
				if (is_object($variable)) return $variable;
				else if (is_array($variable)) return $variable;
				else return strval($variable);
			}
			else {
				if (is_object($variable)) {
					$next_index = $childs[0];
					array_shift($childs);
					return self::template_get_variable( $variable->$next_index, join( $childs,'.' ) );
				}
				else if (is_array($variable)) {
					$next_index = $childs[0];
					array_shift($childs);
					return self::template_get_variable( $variable[ $next_index ], join( $childs,'.' ) );
				}
			}
		}
		
	}

	static function template_create_thumb($image_source,$width=160,$height=120,$background_color='#FFFFFF',$prefix='thumb',$type=1)
	{
	
		if ($image_source == false) return '';
		if ($image_source == '') return '';

		$background_color = ltrim($background_color,'#');
		list($red_color,$green_color,$blue_color) = (strlen($background_color)==3)?str_split($background_color,1):str_split($background_color,2);
		$red_color = hexdec($red_color);
		$blue_color = hexdec($blue_color);
		$green_color = hexdec($green_color);

		//create_image($fImg,$foImg,$largura,$altura,$tipo=1,$cor = Array(255,255,255))
		//core::create_image

		//echo $image_source;

		$image_source = str_replace(web::document_url(),'',$image_source);

		$image_source_path = dirname($image_source);
		$image_source_path = core::base_directory() . $image_source_path;

		if (!is_dir($image_source_path)) {
			$image_source_path = get_media_diretory() . 'thumb_images/' . abs(crc32(basename($image_source)))%8192;
		}

		$image_destination_path = $image_source_path . '/tmp';
		
		$image_destination = '';
		$base_image = basename($image_source);
		
		$image_destination = $image_destination_path . '/' . $prefix . $width . 'x' . $height . 'x' . $type . '-' . substr($base_image,0,strpos($base_image,'.')) . '.jpg';
		
		if (strpos($image_source, "youtube.com") !== false) {
		
			$sha_l = sha1($image_source);
			$video_id = substr($sha_l,strlen($sha_l)-8);
			preg_match('#/vi/([\w\d-]+)/0.jpg#i', $image_source, $matches);
			if ($matches) {
				$video_id = $matches[1];
			}

			$image_destination = $image_destination_path . '/' . $prefix . $width . 'x' . $height . 'x' . $type . '-' . $video_id . '.jpg';
		}

		if (file_exists(core::base_directory() . $image_source)) {
			$image_source = core::base_directory() . $image_source;
		}

		core::create_image($image_source,$image_destination,$width,$height,$type,Array($red_color,$green_color,$blue_color));

		return web::document_url() . core::relative_path( $image_destination );
	}

	static function template_compile_value($attr,$return=false)
	{
		$values = str_getcsv( $attr, '|', "'" );

		$compiled_string = '';

		foreach ($values as $value)
		{
			$command = trim($value);
			$command_parameters = strpos($command,':');
			if ($command_parameters !== false) {
				$parameters_index = $command_parameters;
				$command_parameters = trim(substr($command,$command_parameters+1));
				$command = substr($command,0,$parameters_index);
			}
			switch ($command)
			{
				case 'encodeurl':
					$compiled_string = 'urlencode(' . $compiled_string . ')';
					break;
				case 'date':
					$compiled_string = 'core::template_format_date(' . $compiled_string . ',' . $command_parameters . ')';
					break;
				case 'number':
					$compiled_string = 'number_format(' . $compiled_string . (($command_parameters!='')?(',' . $command_parameters):'') . ')';
					break;
				case 'capitalize':
					$compiled_string = 'mb_ucwords(' . $compiled_string . ',\'utf-8\')';
					break;
				case 'capitalize_first':
					$compiled_string = 'mb_ucfirst(' . $compiled_string . ',\'utf-8\')';
					break;
				case 'uppercase':
					$compiled_string = 'mb_strtoupper(' . $compiled_string . ',\'utf-8\')';
					break;
				case 'lowercase':
					$compiled_string = 'mb_strtolower(' . $compiled_string . ',\'utf-8\')';
					break;
				case 'array':
					$compiled_string = 'array(' . $command_parameters . ')';
					break;
				case 'first':
					$compiled_string = 'core::template_value_array_first(' . $compiled_string . ')';
					break;
				case 'last':
					$compiled_string = 'core::template_value_array_end(' . $compiled_string . ')';
					break;
				case 'pop':
					$compiled_string = 'core::template_value_array_pop(' . $compiled_string . ')';
					break;
				case 'pop_first':
					$compiled_string = 'core::template_value_array_pop_first(' . $compiled_string . ')';
					break;
				case 'slice':
					$compiled_string = 'core::template_value_array_slice(' . $compiled_string . ',' . $command_parameters . ')';
					break;
				case 'join':
					$compiled_string = 'join(' . $compiled_string . ',\'' . trim($command_parameters,'\'') . '\')';
					break;
				case 'sort':
					$compiled_string = 'core::template_value_array_sort(' . $compiled_string . ')';
					break;
				case 'size':
					$compiled_string = 'core::template_value_size(' . $compiled_string . ')';
					break;
				case 'strip_html':
					$compiled_string = 'strip_tags(' . $compiled_string . ')';
					break;
				case 'strip_scripts':
					$compiled_string = 'strip_scripts(' . $compiled_string . ')';
					break;
				case 'strip_newlines':
					$compiled_string = 'core::template_value_strip_newlines(' . $compiled_string . ')';
					break;
				case 'newline_to_br':
					$compiled_string = 'core::template_value_newline_to_br(' . $compiled_string . ')';
					break;
				case 'replace':
					$compiled_string = 'str_replace('. $command_parameters . ',' . $compiled_string . ')';
					break;
				case 'replace_first':
					$compiled_string = 'str_replace('. $command_parameters . ',' . $compiled_string . ',1)';
					break;
				case 'remove':
					$compiled_string = 'str_replace('. $command_parameters . ',\'\',' . $compiled_string . ')';
					break;
				case 'remove_first':
					$compiled_string = 'str_replace('. $command_parameters . ',\'\',' . $compiled_string . ',1)';
					break;
				case 'truncate_text':
					$compiled_string = 'truncate_text(' . $compiled_string . ',' . $command_parameters . ')';
					break;
				case 'pluralize':
					$compiled_string = 'inflector::pluralize_if(' . $command_parameters. ',' . $compiled_string  . ')';
					break;
				case 'find_image':
					$compiled_string = 'find_content_image(' . $compiled_string  . ')';
					break;
				case 'create_thumb':
					$compiled_string = 'core::template_create_thumb(' . $compiled_string . ',' . $command_parameters . ')';
					break;
				case 'prepend':
					$value = trim($value);
					$append_string = '';
					if ( $value[0] == '@' ) {
						$variable_name = substr($value,1);
						$childs = '';
						if ( ($found_dot = strpos($variable_name,'.')) !== false ) {
							$childs = substr($variable_name,$found_dot+1);
							$variable_name = substr($variable_name,0,$found_dot);
						}
						$append_string = 'isset($' . $variable_name . ')?(core::template_get_variable($' . $variable_name . ',\'' . $childs . '\')):\'\'';
					}
					else {
						$append_string = '"' . addslashes(trim($command_parameters,'\'')) . '"';
					}
					if ($compiled_string != '') $compiled_string = $append_string . '.' . $compiled_string;
					else $compiled_string = $append_string;
					break;
				case 'append':
					$value = trim($value);
					$append_string = '';
					if ( $value[0] == '@' ) {
						$variable_name = substr($value,1);
						$childs = '';
						if ( ($found_dot = strpos($variable_name,'.')) !== false ) {
							$childs = substr($variable_name,$found_dot+1);
							$variable_name = substr($variable_name,0,$found_dot);
						}
						$append_string = 'isset($' . $variable_name . ')?(core::template_get_variable($' . $variable_name . ',\'' . $childs . '\')):\'\'';
					}
					else {
						$append_string = '"' . addslashes(trim($command_parameters,'\'')) . '"';
					}
					if ($compiled_string != '') $compiled_string = $compiled_string . '.' . $append_string;
					else $compiled_string = $append_string;
					break;
				/*
					minus
					plus
					times
					divided_by
				*/
				default:
					$value = trim($value);
					$append_string = '';
					if ( $value[0] == '@' ) {
						$variable_name = substr($value,1);
						$childs = '';
						if ( ($found_dot = strpos($variable_name,'.')) !== false ) {
							$childs = substr($variable_name,$found_dot+1);
							$variable_name = substr($variable_name,0,$found_dot);
						}
						$append_string = '(isset($' . $variable_name . ')?(core::template_get_variable($' . $variable_name . ',\'' . $childs . '\')):\'\')';
					}
					else {
						$append_string = '"' . addslashes($value) . '"';
					}
					if ($compiled_string != '') $compiled_string .= '.';
					$compiled_string .= $append_string;
			}
		}

		//$person = Array('name'=>'patrick ribeiro negri','age'=>27,'value'=>283.82);

		//echo $compiled_string;

		//echo '<br /><br />' . "\r\n";
		//echo eval('return stripslashes(' . $compiled_string . ');');
		if ($return) {
			$compiled_string = $compiled_string;
		} else {
			$compiled_string = '<?= ' . 'stripslashes(' . $compiled_string . ')' . ' ?>';
		}

		return str_replace('>','&ac_gt;',str_replace('<','&ac_lt;',$compiled_string));
	}

	static function template_attribute_extract($attribute,$string)
	{
		return preg_match_all("/" . $attribute . "=\"{(.+?)}\"/",$string,$matched,PREG_SET_ORDER)!=0?$matched[0][1]:'';
	}

	static function template_attribute_clean($attribute,$string)
	{
		return preg_replace("/" . $attribute . "=\"(.+?)\"/",'',$string);
	}

	static function template_compile_smart_tags(&$source,$search,$replacement,$wrap_inside=false,$allowed_tags=Array())
	{
		$offset = 0;
		$html_elements = core::find_html_by_regex('(<)([\w]+)[^>]*' . $search . '=[^>]+(.*?)(>)',$source);
		foreach ($html_elements as $html_element)
		{
			// TODO - ADD validation for tags
			$attr = self::template_attribute_extract($search,$html_element['html']);
			$compiled_attr = self::template_compile_value( $attr );

			if ($wrap_inside == false) {
				$compiled_html = self::template_attribute_clean($search,$html_element['html']);
				$compiled_html = self::template_attribute_clean($replacement,$compiled_html);
				$compiled_html = preg_replace('/(.*?)( \/>|>|\/>)/','${1} ' . $replacement . '="' . $compiled_attr . '"${2}',$compiled_html);
				$source = substr_replace( $source, $compiled_html,$html_element['start']-$offset,($html_element['end']-$html_element['start']) );
				$offset += ($html_element['end']-$html_element['start'])-strlen($compiled_html);
			}
			else {
				$compiled_html = self::template_attribute_clean($search,$html_element['html']);

				$element_information = core::match_html_elements($html_element['tag'],$source,$html_element['start']-$offset,1);

				$block_start = $html_element['end']-$offset;
				$block_end = $element_information[0][1] - strlen('</' . $html_element['tag'] . '>');
				$block_content = substr($source,$block_start,($block_end-$block_start));

				$source = substr($source,0,$html_element['start']-$offset) . $compiled_html . $compiled_attr . substr($source,$block_end);
				$offset += strlen($block_content);
				$offset -= strlen($compiled_attr);
				$offset += ($html_element['end']-$html_element['start'])-strlen($compiled_html);
			}
		}

	}

	static function template_compile_system_tags(&$source)
	{
		// WARNING - NOT OPTMIZED FUNCTION, WILL CRASH AND FUCK ENTIRE SYSTEMMMM!!!!
		// TODO, ACCEPTS COMPILATION OPTIONS
		do
		{
			$offset = 0;
			$html_elements = core::find_html_by_regex('(<)([\w]+)[^>]*display_if=[^>]+(.*?)(>)',$source);
			// create a partial find_html_by_regex
			
			foreach ($html_elements as $html_element)
			{
				$attr = self::template_attribute_extract('display_if',$html_element['html']);
				
				$append_string = self::template_compile_value($attr,true);
				$compiled_html = self::template_attribute_clean('display_if',$html_element['html']);
				
				$element_information = core::match_html_elements($html_element['tag'],$source,$html_element['start']-$offset,1);

				$block_start = $html_element['start']-$offset;
				$block_end = $element_information[0][1];
				$block_content = substr($source,$block_start,($block_end-$block_start));
				
				//echo $append_string;
				$compiled_block_content_start = '<? if ((' . $append_string . ')!=false) { ?>';
				$compiled_block_content_end = '<? } ?>';
				
				$block_start2 = $html_element['end']-$offset;
				$block_end2 = $element_information[0][1] - strlen('</' . $html_element['tag'] . '>');
				$block_content2 = substr($source,$block_start2,($block_end2-$block_start2));
				
				$old_length = strlen($source);
				$source = substr($source,0,$html_element['start']-$offset) . $compiled_block_content_start . $compiled_html . $block_content2 . '</' . $html_element['tag'] . '>' . $compiled_block_content_end . substr($source,$block_end);
	
				$offset += strlen($compiled_block_content_start)-strlen($block_content2);
				$offset -= strlen($html_element['html'])-strlen($compiled_html);
			}

			//echo $offset;
			break;
			

		} while (count($html_elements));

		do
		{
			$offset = 0;
			$html_elements = core::find_html_by_regex('(<)([\w]+)[^>]*walk_using=[^>]+(.*?)(>)',$source);
			// create a partial find_html_by_regex
			foreach ($html_elements as $html_element)
			{
				$attr = self::template_attribute_extract('walk_using',$html_element['html']);

				do
				{
					$unique_id_var = uniqid();
					if (isset($_GLOBALS[$unique_id_var])) $unique_id_var = false;
					else $_GLOBALS[$unique_id_var] = true;
				} while ($unique_id_var === false);

				$walk_variable_name = 'i_' . $unique_id_var;

				$values = str_getcsv( $attr, '|', "'" );
				if (count($values)) {
					$value = trim($values[0]);
					$append_string = '';
					if ( $value[0] == '@' ) {
						$variable_name = substr($value,1);
						$childs = '';
						if ( ($found_dot = strpos($variable_name,'.')) !== false ) {
							$childs = substr($variable_name,$found_dot+1);
							$variable_name = substr($variable_name,0,$found_dot);
						}
						//$append_string = '(isset($' . $variable_name . ')?(core::template_get_variable($' . $variable_name . ',\'' . $childs . '\')):null)';
					}

					$childs_f = Array();
					if ( ($found_dot = strpos($childs,'.')) !== false ) {
						$childs_f = explode('.',$childs);
					}
					else if (strlen($childs) > 0) $childs_f = Array(trim($childs));

					if (count($childs_f)) {
						$walk_variable_name = '$' . $childs_f[0];
					}
					else {
						$walk_variable_name = '$' . $variable_name;
					}
				}
				
				$append_string = self::template_compile_value($attr,true);

				$compiled_html = self::template_attribute_clean('walk_using',$html_element['html']);

				$element_information = core::match_html_elements($html_element['tag'],$source,$html_element['start']-$offset,1);

				$block_start = $html_element['end']-$offset;
				$block_end = $element_information[0][1] - strlen('</' . $html_element['tag'] . '>');
				$block_content = substr($source,$block_start,($block_end-$block_start));

				do
				{
					$unique_id = uniqid();
					if (isset($_GLOBALS[$unique_id])) $unique_id = false;
					else $_GLOBALS[$unique_id] = true;
				} while ($unique_id === false);

				$walk_variable_store = 'i_' . $unique_id;

				$compiled_block_content = '<? ' . "\r\n";

				$compiled_block_content .= '$' . $walk_variable_store . ' = ' . $append_string . ';' . "\r\n";
				
				$compiled_block_content .= 'if (!is_array($' . $walk_variable_store . ')) $' . $walk_variable_store . ' = Array();' . "\r\n";
				
				$compiled_block_content .= 'reset($' . $walk_variable_store . ');' . "\r\n";
				$compiled_block_content .= 'while (' . $walk_variable_name . ' = current($' . $walk_variable_store . ')) {' . "\r\n";
				$compiled_block_content .= '	$key = key($' . $walk_variable_store . ');' . "\r\n";
				$compiled_block_content .= '?>' . "\r\n" . $block_content . '<? ';
				$compiled_block_content .= '	next($' . $walk_variable_store . ');' . "\r\n";
				$compiled_block_content .= '}' . "\r\n";
				$compiled_block_content .= 'reset($' . $walk_variable_store . ');' . "\r\n";
				$compiled_block_content .= $walk_variable_name . ' = $' . $walk_variable_store . ';' . "\r\n";

				$compiled_block_content .= '?>' . "\r\n";

				$old_length = strlen($source);
				$source = substr($source,0,$html_element['start']-$offset) . $compiled_html . $compiled_block_content . substr($source,$block_end);

				$offset += strlen($compiled_block_content)-strlen($block_content);
				$offset -= strlen($html_element['html'])-strlen($compiled_html);

				//echo $offset;
				break;
			}
		} while (count($html_elements));
		
		
		
		//exit(0);
	}
	
	static function compile_template($template_name)
	{
		if (!file_exists(self::base_directory() . "/compiled/"))
		{
			mkdir( self::base_directory() . "/compiled/", 0777, true);
		}
		if (!file_exists($template_name)) return false;

		$compiled_template = self::base_directory() . "/compiled/" . basename($template_name);

		$template_time = filemtime($template_name);
		$compiled_time = 0;
		if (file_exists($compiled_template)) $compiled_time = filemtime($compiled_template);

		//if ($template_time == $compiled_time) return $compiled_template;

		$source = file_get_contents($template_name);

		// Prepare for compile
		$source = preg_replace_callback(
			'/"{(.+?)}"/is',
			create_function(
				'$matches',
				'return str_replace(\'<\',\'&ac_lt;\',str_replace(\'>\',\'&ac_gt;\',$matches[0]));'
			),
			$source
		);

		self::template_compile_smart_tags($source,'id_from','id');
		self::template_compile_smart_tags($source,'value_from','value');
		self::template_compile_smart_tags($source,'href_from','href');
		self::template_compile_smart_tags($source,'title_from','title');
		self::template_compile_smart_tags($source,'alt_from','alt');
		self::template_compile_smart_tags($source,'src_from','src');
		self::template_compile_smart_tags($source,'width_from','width');
		self::template_compile_smart_tags($source,'height_from','height');
		self::template_compile_smart_tags($source,'contents_from','',true);
		self::template_compile_system_tags($source);

		/*
		$offset = 0;
		$html_elements = core::find_html_by_regex('(<)([\w]+)[^>]* value_from=[^>]*(.*?)(>)',$source);
		foreach ($html_elements as $html_element)
		{
			// TODO - ADD validation for tags
			$attr = self::template_attribute_extract('value_from',$html_element['html']);
			$compiled_attr = self::template_compile_value( $attr );
			$compiled_html = self::template_attribute_clean('value_from',$html_element['html']);
			$compiled_html = self::template_attribute_clean('value',$compiled_html);

			$compiled_html = preg_replace('/(.*?)>/','${1} value="' . $compiled_attr . '">',$compiled_html);
			$source = substr_replace( $source, $compiled_html,$html_element['start']-$offset,($html_element['end']-$html_element['start']) );
			$offset += ($html_element['end']-$html_element['start'])-strlen($compiled_html);
		}
		*/
		

		$new_source = "";
		$offset = 0;
		preg_match_all("/(<!--) {(.+?)} (-->)/i",$source,$matched_elements,PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
		foreach ($matched_elements as $template_command) {
			//$start = $template_command[0][1];
			//print_r($template_command);
			//ooooooooooooooooooooooooooooooooooo
			$inicio = $template_command[1][1];
			$template_commands = trim($template_command[2][0],' ');
			$fim = $template_command[3][1] + strlen('-->');
			
			$compiled_commands = '';
			
			$parse_commands = explode('|',$template_commands);
			
			foreach ($parse_commands as $parsed_command) {
				list($command,$parameters) = explode(':',$parsed_command);
				$command = trim($command,' ');
				$parameters = trim($parameters,' ');
				
				$compiled_string = '';
				
				switch ($command)
				{
					case 'set_cache_expiration':
						$compiled_string = '$expiration_date = ' . $parameters . ';';
						break;
					case 'import_layout':
						$compiled_string = 'core::set_layout(\'' . $parameters . '\');';
						break;
					case 'editable_area':
						$parameters = explode(',',$parameters);
						$title = trim(isset($parameters[0])?$parameters[0]:'',' ');
						$variable = trim(isset($parameters[1])?$parameters[1]:'',' ');
						$width = trim(isset($parameters[2])?$parameters[2]:0,' ');
						$height = trim(isset($parameters[3])?$parameters[3]:0,' ');
						$cache_time = trim(isset($parameters[4])?$parameters[4]:0,' ');
						
						$search_variable = $variable;
						if ($search_variable == 'content_area') $search_variable = 'content';
						$compiled_string .= '$' . $variable . ' = core::template_render_editable_area("' . $search_variable . '",$content->ROWID,' . $cache_time . ');' . "\r\n";
						
						break;
					case 'unique_editable_area':
						$parameters = explode(',',$parameters);
						$title = trim(isset($parameters[0])?$parameters[0]:'',' ');
						$variable = trim(isset($parameters[1])?$parameters[1]:'',' ');
						$width = trim(isset($parameters[2])?$parameters[2]:0,' ');
						$height = trim(isset($parameters[3])?$parameters[3]:0,' ');
						$cache_time = trim(isset($parameters[4])?$parameters[4]:0,' ');

						$search_variable = $variable;
						if ($search_variable == 'content_area') $search_variable = 'content';
						
						$search_variable = $variable;
						if ($search_variable == 'content_area') $search_variable = 'content';
						$compiled_string .= '$' . $variable . ' = core::template_render_unique_editable_area("' . $search_variable . '",'. $cache_time . ');' . "\r\n";
						
						/*
						$compiled_string .= '$section = orm::search_one(\'content_section\',\'name="' . $search_variable . '" and type=2 and reference_id=-1\');' . "\r\n";

						$compiled_string .= '$' . $variable . ' = \'\';' . "\r\n";

						$compiled_string .= 'if ($section) {' . "\r\n";

						$compiled_string .= '$current_section_components = $section->content_section_component->find(ALL,Array(\'order\'=>\'position\'));' . "\r\n";
						$compiled_string .= 'foreach ($current_section_components as $section_component) {' . "\r\n";
						$compiled_string .=	'$' . $variable . ' .= $section_component->render($section->ROWID);' . "\r\n";
						$compiled_string .= '} }' . "\r\n";
						*/

						break;
				}
				
				$compiled_string = '<? ' . "\r\n" . $compiled_string . ' ?>';

				$compiled_commands .= str_replace('>','&ac_gt;',str_replace('<','&ac_lt;',$compiled_string));
			}
			
			$new_source .= substr($source,$offset,$inicio-$offset) . $compiled_commands;
			
			$offset = $fim;
		}
		$new_source .= substr($source,$offset);
		$source = $new_source;
		
		$new_source = "";
		$file_position = 0;
		$action_elements = core::find_html_by_regex('(<)([\w]+)[^>]* action_for=[^>]*(.+?).+?(>)',$source);
		foreach ($action_elements as $action_element) {
			$action_data = preg_match_all("/action_for=\"(.+?)\"/",$action_element['html'],$matched,PREG_SET_ORDER)!=0?$matched[0][1]:'';
			$action_element['html'] = preg_replace("/action_for=\"(.+?)\"/","",$action_element['html']);
			$action_element['html'] = core::clear_html($action_element['html']);
			if ($action_element['tag'] == 'form') {
				$element_information = core::match_html_elements($action_element['tag'],$source,$action_element['start'],1);
				$block_start = $action_element['end'];
				$block_end = $element_information[0][1] - strlen('</' . $action_element['tag'] . '>');

				$block_html = $action_element['html'];

				$action_form = explode(',', preg_replace('/\s+/','',trim($action_data,'%')));
				$action_name = $action_form[0] . '_' . $action_form[1];
				$action_form_prepare = '<? list($' . $action_name . '_action,$' . $action_name . '_method) = action_for(\'' . $action_form[1] . '\',\'' . $action_form[0] . '\'' . ((isset($action_form[2]))?(','.$action_form[2]):('')) . '); ?>';
				$block_html = $action_form_prepare . rtrim($block_html,'>');
				$block_html .= ' action="<?= $' . $action_name . '_action; ?>" method="<?= http_method($' . $action_name . '_method); ?>"><?= restful_form($' . $action_name . '_method) ?>';

				$new_source .= substr($source,$file_position,$action_element['start']-$file_position) . $block_html;
				$file_position = $action_element['end'];
			}
		}
		$new_source .= substr($source,$file_position);
		
		if (preg_match_all("/(<head>)(.*)(<\/head>)/ism",$new_source,$matches,PREG_OFFSET_CAPTURE | PREG_SET_ORDER))	
		{				
			//ooooooooooooooooooooooooooooooooooo
			//	Adicionar Meta Description
			//ooooooooooooooooooooooooooooooooooo
			$inicio = $matches[0][1][1] + strlen("<head>");
			$fim = $matches[0][3][1];
			$conteudo_head = $matches[0][2][0];
			//$conteudo_head = str_replace("\r","",$conteudo_head);
			//$conteudo_head = str_replace("\n","",$conteudo_head);		
			//$conteudo_head = str_replace("  ","",$conteudo_head);
		//	//	$conteudo_head = str_replace(">",">\r\n",$conteudo_head);
		//		$conteudo_head = str_replace("<","\r\n<",$conteudo_head);

			$conteudo_anteshead = "";
			$conteudo_fimhead = "";

			if (!strpos($conteudo_head,'before_header'))
			{
				$conteudo_anteshead .= "<? web::before_header() ?>";
			}

			if (!strpos($conteudo_head,'after_header'))
			{
				$conteudo_fimhead .= "<? web::after_header() ?>";
			}

			$new_source = substr($new_source,0,$inicio) . $conteudo_anteshead . $conteudo_head . $conteudo_fimhead . substr($new_source,$fim);

			//ooooooooooooooooooooooooooooooooooo
			//	Adicionar inclusoes necessarias
			//ooooooooooooooooooooooooooooooooooo

			//echo "<pre>";
			//print_r($matches);		
			//echo "</pre>";
		}

		if (preg_match_all("/<\/body>/ism",$new_source,$matches,PREG_OFFSET_CAPTURE | PREG_SET_ORDER))	
		{	
			$inicio = $matches[0][0][1];

			//oooooooooooooooooooooooooo
			// Inclusoes no Final
			$new_source = substr($new_source,0,$inicio) . "<? web::after_body() ?>\r\n" . substr($new_source,$inicio);
		}

		if (preg_match_all("/(<body.*?>)/ism",$new_source,$matches,PREG_OFFSET_CAPTURE | PREG_SET_ORDER))	
		{	
			$inicio = $matches[0][0][1];
			$string = $matches[0][0][0];

			//oooooooooooooooooooooooooo
			// Inclusoes no Final
			$new_source = substr($new_source,0,$inicio+strlen($string)) . "<? web::before_body() ?>\r\n" . substr($new_source,$inicio+strlen($string));
		}

		foreach ( component::all() as $component ) if (method_exists($component,'compile_template')) $new_source = $component->compile_template($new_source);
		
		$source = $new_source;

		//self::template_clear_html(

		$source = self::template_clear_html($source);
		$source = str_replace('&ac_lt;','<',$source);
		$source = str_replace('&ac_gt;','>',$source);

		file_put_contents($compiled_template,$source);
		$modified_time = filemtime($template_name);
		touch($compiled_template,$modified_time);
		
		/*
		echo '<pre>';
		echo htmlentities($source);
		echo '</pre>';
		exit(0);
		*/
		return $compiled_template;
	}
	
	/*
	static function template_var($object,$variable)
	{
		if ($variable == '#') return $object;
		if (is_object($object)) if (isset($object->$variable)) return $object->$variable;
		if (isset($object[$variable])) return $object[$variable];
		return '';
	}
	
	static function compile_template($template_name)
	{
		if (!file_exists(self::base_directory() . "/compiled/"))
		{
			mkdir( self::base_directory() . "/compiled/", 0777, true);
		}
		if (!file_exists($template_name)) return false;
		
		$compiled_template = self::base_directory() . "/compiled/" . basename($template_name);
		
		$template_time = filemtime($template_name);
		$compiled_time = 0;
		//if (file_exists($compiled_template)) $compiled_time = filemtime($compiled_template);
		
		if ($template_time == $compiled_time) return $compiled_template;

		$source = file_get_contents($template_name);

		$new_source = '';
		$last_index = 0;
		$dynamic_values = Array();
		if (preg_match_all("/value_from=\"%(.+?)%\"/ism",$source,$matches,PREG_OFFSET_CAPTURE | PREG_SET_ORDER))
		{
			foreach ($matches as $match)
			{
				$match_position = $match[0][1];
				$tag_start = strrpos( substr($source,0,$match_position),"<");
				$tag_name = '';
				$tag_end = $tag_start;
				
				$template_code_size = strlen($match[0][0]);
				
				$template_codes = explode("|",$match[1][0]);
				$template_code = $template_codes[0];

				for ($i=1;$i<count($template_codes);$i++)
				{
					if (trim($template_codes[$i]) == "uppercase")
					{
						$template_code = 'mb_strtoupper(' . $template_code . ',\'utf-8\')';
					}
					else if (trim($template_codes[$i]) == "lowercase")
					{
						$template_code = 'mb_strtolower(' . $template_code . ',\'utf-8\')';
					}
					else if (trim($template_codes[$i]) == "ucfirst")
					{
						$template_code = 'ucfirst(' . $template_code . ')';
					}
					else if (trim($template_codes[$i]) == "key")
					{
						$template_code = 'key(' . $template_code . ')';
					}
					else if (strpos($template_codes[$i],'append') !== FALSE) 
					{
						$template_code = 'core::append(' . $template_code . ',\'' . substr($template_codes[$i], strpos($template_codes[$i],'append:')+strlen('append:') ) . '\')';
					}
					else if (strpos($template_codes[$i],'prepend') !== FALSE)
					{
						$template_code = 'core::prepend(' . $template_code . ',\'' . substr($template_codes[$i], strpos($template_codes[$i],'prepend:')+strlen('prepend:') ) . '\')';
					}
					else
					{
						$template_code = $template_codes[$i] . '(' . $template_code . ')';
					}
				}
				$template_code = '<?= ' . $template_code . ' ?>';

				if (preg_match('/<(.+?) /',substr($source,$tag_start),$ematches)) $tag_name = $ematches[1];
				
				$new_source .= substr($source,$last_index,$tag_start-$last_index);
				
				if (strtoupper($tag_name) == "INPUT") {
					
					$tag_end = strpos( $source,"/>", $match_position)+2;
					
					$source_block = substr($source,$tag_start,$tag_end-$tag_start);
					$source_block = substr($source_block,0,$match_position-$tag_start) . 'value="' . $template_code . '" ' . substr($source_block,($match_position-$tag_start)+$template_code_size);
					//$source_block_start = substr($source_block,0,strpos($source_block,">")+1);
					//$source_block_start = preg_replace("/ +/"," ",$source_block_start);
					//$source_block_start = preg_replace("/ >/",">",$source_block_start);
					//$source_block = $source_block_start . $template_code;
					
				}
				else if (strtoupper($tag_name) == "IMG") {

					$tag_end = strpos( $source,"/>", $match_position)+2;

					$source_block = substr($source,$tag_start,$tag_end-$tag_start);
					$source_block = substr($source_block,0,$match_position-$tag_start) . 'src="' . $template_code . '" ' . substr($source_block,($match_position-$tag_start)+$template_code_size);
					//$source_block_start = substr($source_block,0,strpos($source_block,">")+1);
					//$source_block_start = preg_replace("/ +/"," ",$source_block_start);
					//$source_block_start = preg_replace("/ >/",">",$source_block_start);
					//$source_block = $source_block_start . $template_code;

				}
				else if (strtoupper($tag_name) == "A") {

					$tag_end = strpos( $source,">", $match_position)+1;

					$source_block = substr($source,$tag_start,$tag_end-$tag_start);
					$source_block = substr($source_block,0,$match_position-$tag_start) . 'href="' . $template_code . '" ' . substr($source_block,($match_position-$tag_start)+$template_code_size);
					//$source_block_start = substr($source_block,0,strpos($source_block,">")+1);
					//$source_block_start = preg_replace("/ +/"," ",$source_block_start);
					//$source_block_start = preg_replace("/ >/",">",$source_block_start);
					//$source_block = $source_block_start . $template_code;

				}
				else if (strtoupper($tag_name) == "FORM") {

					$tag_end = strpos( $source,">", $match_position)+1;

					$source_block = substr($source,$tag_start,$tag_end-$tag_start);
					$source_block = substr($source_block,0,$match_position-$tag_start) . 'action="' . $template_code . '" ' . substr($source_block,($match_position-$tag_start)+$template_code_size);
					//$source_block_start = substr($source_block,0,strpos($source_block,">")+1);
					//$source_block_start = preg_replace("/ +/"," ",$source_block_start);
					//$source_block_start = preg_replace("/ >/",">",$source_block_start);
					//$source_block = $source_block_start . $template_code;

				}
				else {
				
					$elms = self::match_html_elements($tag_name,$source,$tag_start);
					if ($elms) $tag_end = $elms[0][1];
	
					$source_block = substr($source,$tag_start,$tag_end-$tag_start);
					$source_block = substr($source_block,0,$match_position-$tag_start) . substr($source_block,($match_position-$tag_start)+$template_code_size);
					$source_block_start = substr($source_block,0,strpos($source_block,">")+1);
					$source_block_start = preg_replace("/ +/"," ",$source_block_start);
					$source_block_start = preg_replace("/ >/",">",$source_block_start);
					$source_block_end =  '</' . $tag_name . '>';
					$source_block = $source_block_start . $template_code . $source_block_end;
	

				}
				
				$new_source .= $source_block;
				$last_index = $tag_end;
				
			}
		}
		$new_source .= substr($source,$last_index);
		$source = $new_source;
		
		//ooooooooooooooooooooooooooooo
		//	Compiling Action FOR
		$new_source = "";
		$file_position = 0;
		$action_elements = core::find_html_by_regex('(<)([\w]+)[^>]* action_for=[^>]*(.+?).+?(>)',$source);
		foreach ($action_elements as $action_element) {
			$action_data = preg_match_all("/action_for=\"(.+?)\"/",$action_element['html'],$matched,PREG_SET_ORDER)!=0?$matched[0][1]:'';
			$action_element['html'] = preg_replace("/action_for=\"(.+?)\"/","",$action_element['html']);
			$action_element['html'] = core::clear_html($action_element['html']);
			if ($action_element['tag'] == 'form') {
				$element_information = core::match_html_elements($action_element['tag'],$source,$action_element['start'],1);
				$block_start = $action_element['end'];
				$block_end = $element_information[0][1] - strlen('</' . $action_element['tag'] . '>');
				
				$block_html = $action_element['html'];
				
				$action_form = explode(',', preg_replace('/\s+/','',trim($action_data,'%')));
				$action_name = $action_form[0] . '_' . $action_form[1];
				$action_form_prepare = '<? list($' . $action_name . '_action,$' . $action_name . '_method) = action_for(\'' . $action_form[1] . '\',\'' . $action_form[0] . '\'' . ((isset($action_form[2]))?(','.$action_form[2]):('')) . '); ?>';
				$block_html = $action_form_prepare . rtrim($block_html,'>');
				$block_html .= ' action="<?= $' . $action_name . '_action; ?>" method="<?= http_method($' . $action_name . '_method); ?>"><?= restful_form($' . $action_name . '_method) ?>';

				$new_source .= substr($source,$file_position,$action_element['start']-$file_position) . $block_html;
				$file_position = $action_element['end'];
			}
		}
		$new_source .= substr($source,$file_position);
		$source = $new_source;
		
		$new_source = '';
		$last_index = 0;
		$dynamic_values = Array();
		if (preg_match_all("/repeat=\"%(.+?)%\"/ism",$source,$matches,PREG_OFFSET_CAPTURE | PREG_SET_ORDER))
		{
			foreach ($matches as $match)
			{
				$match_position = $match[0][1];
				$tag_start = strrpos( substr($source,0,$match_position),"<");
				$tag_name = '';
				$tag_end = $tag_start;

				$template_code_size = strlen($match[0][0]);

				if (preg_match('/<(.+?) /',substr($source,$tag_start),$ematches)) $tag_name = $ematches[1];

				$elms = self::match_html_elements($tag_name,$source,$tag_start);
				if ($elms) $tag_end = $elms[0][1];

				$new_source .= substr($source,$last_index,$tag_start-$last_index);

				$template_codes = explode("|",$match[1][0]);
				$template_code = $template_codes[0];

				for ($i=1;$i<count($template_codes);$i++)
				{
					if (trim($template_codes[$i]) == "uppercase")
					{
						$template_code = 'mb_strtoupper(' . $template_code . ',\'utf-8\')';
					}
					else if (trim($template_codes[$i]) == "lowercase")
					{
						$template_code = 'mb_strtolower(' . $template_code . ',\'utf-8\')';
					}
					else
					{
						$template_code = $template_codes[$i] . '(' . $template_code . ')';
					}
				}
				$template_code = trim($template_code);

				$source_block = substr($source,$tag_start,$tag_end-$tag_start);
				$source_block = substr($source_block,0,$match_position-$tag_start) . substr($source_block,($match_position-$tag_start)+$template_code_size);
				$source_block = preg_replace("/ +/"," ",$source_block);
				$source_block = preg_replace("/ >/",">",$source_block);
				$foreach_id =  $template_code . '_' . rand(0,100000);
				$source_block_start = '<? foreach ( $' . $template_code . ' as ' . '$' . $foreach_id . ') { ?>';
				$source_block_end = '<? } ?>';
				
				$source_block = preg_replace("/\\$" . $template_code . "\.([^ |,]+)/",'core::template_var( $' . $foreach_id . ',"' . "\\1" . '")',$source_block);

				$new_source .= $source_block_start . $source_block . $source_block_end;

				$last_index = $tag_end;
			}
		}
		$new_source .= substr($source,$last_index);
		$source = $new_source;
		
		$new_source = preg_replace("/\\$([^ |,(]+)\.([^ |,]+)/",'core::template_var( $' . "\\1" . ',"' . "\\2" . '")',$new_source);
		
		
		if (preg_match_all("/(<head>)(.*)(<\/head>)/ism",$new_source,$matches,PREG_OFFSET_CAPTURE | PREG_SET_ORDER))	
		{				
			//ooooooooooooooooooooooooooooooooooo
			//	Adicionar Meta Description
			//ooooooooooooooooooooooooooooooooooo
			$inicio = $matches[0][1][1] + strlen("<head>");
			$fim = $matches[0][3][1];
			$conteudo_head = $matches[0][2][0];
			$conteudo_head = str_replace("\r","",$conteudo_head);
			$conteudo_head = str_replace("\n","",$conteudo_head);		
			$conteudo_head = str_replace("  ","",$conteudo_head);
		//		$conteudo_head = str_replace(">",">\r\n",$conteudo_head);
		//		$conteudo_head = str_replace("<","\r\n<",$conteudo_head);
	
			$conteudo_anteshead = "";
			$conteudo_fimhead = "";
	
			if (!strpos($conteudo_head,'before_header'))
			{
				$conteudo_anteshead .= "<? web::before_header() ?>";
			}
	
			if (!strpos($conteudo_head,'after_header'))
			{
				$conteudo_fimhead .= "<? web::after_header() ?>";
			}
	
			$new_source = substr($new_source,0,$inicio) . "\r\n" . $conteudo_anteshead  . $conteudo_head . $conteudo_fimhead . substr($new_source,$fim);
	
			//ooooooooooooooooooooooooooooooooooo
			//	Adicionar inclusoes necessarias
			//ooooooooooooooooooooooooooooooooooo
	
			//echo "<pre>";
			//print_r($matches);		
			//echo "</pre>";
		}
		
		if (preg_match_all("/<\/body>/ism",$new_source,$matches,PREG_OFFSET_CAPTURE | PREG_SET_ORDER))	
		{	
			$inicio = $matches[0][0][1];
	
			//oooooooooooooooooooooooooo
			// Inclusoes no Final
			$new_source = substr($new_source,0,$inicio) . "<? web::after_body() ?>\r\n" . substr($new_source,$inicio);
		}
		
		if (preg_match_all("/(<body.*?>)/ism",$new_source,$matches,PREG_OFFSET_CAPTURE | PREG_SET_ORDER))	
		{	
			$inicio = $matches[0][0][1];
			$string = $matches[0][0][0];
			
			//oooooooooooooooooooooooooo
			// Inclusoes no Final
			$new_source = substr($new_source,0,$inicio+strlen($string)) . "<? web::before_body() ?>\r\n" . substr($new_source,$inicio+strlen($string));
		}
		
		foreach ( component::all() as $component ) if (method_exists($component,'compile_template')) $new_source = $component->compile_template($new_source);
		
		file_put_contents($compiled_template,$new_source);
		$modified_time = filemtime($template_name);
		touch($compiled_template,$modified_time);
		
		
		return $compiled_template;
	}
	*/
	
	static function get_title($title)
	{
		if (web::get_title() != '') 
		{
			return web::get_title();
		}

		return $title;
	}
	
	// GMT Functions
	
	//ooooooooooooooooooooooooooooooooo
	//	PTK: Dates must be sabed in GMT data format
	//		 So we can display correct times for entire world based on their location
	//ooooooooooooooooooooooooooooooooo
	static function date($day=0,$month=0,$year=0,$hour=0,$minute=0,$second=0)
	{
		if (($day != 0) && ($month != 0) && ($year != 0))
		{
			return date("U",mktime($hour,$minute,$second,$month,$day,$year));
		}
	
		if ($day == 0) $day = date("d");
		if ($month == 0) $month = date("m");
		if ($year == 0) $year = date("Y");
		
		return date("U",mktime($hour,$minute,$second,$month,$day,$year));
	}
	
	static function time($timevar=null)
	{
		if ($timevar == null)
		{
			$timevar = time();
		}
		return $timevar;
	}
	
	static function format_date($valor,$formato='%A, %d/%B/%Y %H:%M:%S',$retornar=0)
	{
		// Go back here and reformat
		if ( (isset($_SERVER['SERVER_SOFTWARE'])) && (stristr($_SERVER['SERVER_SOFTWARE'], 'WIN'))) {
			$formato = utf8_decode($formato);
		}
		$datetime = strftime( $formato , $valor + (core::$defaults["system_timezone"]*3600) );
		if ( (isset($_SERVER['SERVER_SOFTWARE'])) && (stristr($_SERVER['SERVER_SOFTWARE'], 'WIN')) ) {
			if ($retornar) return utf8_encode($datetime);
			echo utf8_encode($datetime);
			return;
		}

		if ($retornar) return $datetime;
		echo $datetime;
	}
	
	static function date_from_str($valor_str)
	{
		$date_and_time = explode(' ',$valor_str);
		$date = '';
		$time = '';
		$date = $date_and_time[0];
		$time_parts = Array(0,0,0);
		if (count($date_and_time) > 1)
		{
			$time = $date_and_time[1];
			$time_parts = explode(':',$time);
		}
		$date_parts = explode('/',$date);
		
		return core::date($date_parts[0],$date_parts[1],$date_parts[2],isset($time_parts[0])?$time_parts[0]:$time_parts[0],isset($time_parts[1])?$time_parts[1]:0,isset($time_parts[2])?$time_parts[2]:0) - core::$defaults["system_timezone"]*3600 ;
	}
	
	static function configure_i18n($codepage)
	{
		putenv('LANG='.$codepage . '.UTF-8');
		$new_codepage = $codepage;
		if ($new_codepage == 'pt_BR') $new_codepage = 'ptb';
		setlocale(LC_ALL,$codepage.'.UTF-8',$new_codepage);
		if(function_exists('bindtextdomain')) {
			bindtextdomain("core", core::root_directory() . "/core/locale/");
			bind_textdomain_codeset("core", 'utf-8');
			textdomain("core");
		}
	}

	static function i18n_add_domain($domain,$domain_path)
	{
		bindtextdomain($domain, $domain_path);
	}

	static function i18n_get_text($text,$domain="core") {
		dgettext($domain, $text);
	}

	static function set_language_domain($appdomain)
	{
		textdomain($appdomain);
	}
	
	static function set_layout($layout_name)
	{
		self::$layout = $layout_name;
	}
	
	static function get_layout()
	{
		return self::$layout;
	}
	
	static function render_html($file,$variables=Array())
	{
		web::send_headers();
		foreach ($variables as $key=>$value) $$key=$value;
		
		$default_template = $file;
		$custom_template = core::base_directory() . '/layout/' . basename($file);

		$template = (file_exists($custom_template)) ? $custom_template : $default_template;

		$compiled_template = core::compile_template($template);
		
		if ($compiled_template) include($compiled_template);
	}
	
	static function append($a,$b)
	{
		return $a . $b;
	}
	
	static function prepend($a,$b)
	{
		return $b . $a;
	}
	
	static function application_name() {
		return self::$defaults['application_name'];
	}
	
	static function default_mailbox() {
		return isset(self::$defaults['default_mailbox'])?self::$defaults['default_mailbox']:'webmaster@agencialobo.com.br';
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

	static function global_clear_variable($variable)
	{
		global $GLOBALS;
		unset($GLOBALS[$variable]);
	}

}

//o-------------------------------o
//		Helper Functions
//o-------------------------------o
function i18n_get_text($text,$domain="core") {
	core::i18n_get_text($text,$domain);
}

function i18n_text($text,$domain="core") {
	core::i18n_get_text($text,$domain);
}

//o-------------------------------o
//		Detect if is Development
//o-------------------------------o
core::detect_development_environment();
core::$defaults = yaml::load(core::base_directory() . '/config/config.yml');


?>
