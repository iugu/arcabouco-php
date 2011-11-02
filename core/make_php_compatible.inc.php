<?

/*
 * This file is part of the Arcabouco Framework.
 * (c) 2008 Patrick Negri <patrick@agencialobo.com.br>
 * (c) 2008 Paulo Lobo <plobo@agencialobo.com.br>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

//o-------------------------------------------o
//		OLD PHP FIXES < 5.0
//o-------------------------------------------o
if(!function_exists('scandir')) {
	function scandir($dir, $sortorder = 0) {
		if(is_dir($dir) && $dirlist = @opendir($dir)) {
			while(($file = readdir($dirlist)) !== false) {
				$files[] = $file;
			}
			closedir($dirlist);
			($sortorder == 0) ? asort($files) : rsort($files); // arsort was replaced with rsort
			return $files;
		} else return false;
	}
}
if (!function_exists('json_encode'))
{
  function json_encode($a=false)
  {
	if (is_null($a)) return 'null';
	if ($a === false) return 'false';
	if ($a === true) return 'true';
	if (is_scalar($a))
	{
	  if (is_float($a))
	  {
		// Always use "." for floats.
		return floatval(str_replace(",", ".", strval($a)));
	  }

	  if (is_string($a))
	  {
		static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
		return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
	  }
	  else
		return $a;
	}
	$isList = true;
	for ($i = 0, reset($a); $i < count($a); $i++, next($a))
	{
	  if (key($a) !== $i)
	  {
		$isList = false;
		break;
	  }
	}
	$result = array();
	if ($isList)
	{
	  foreach ($a as $v) $result[] = json_encode($v);
	  return '[' . join(',', $result) . ']';
	}
	else
	{
	  foreach ($a as $k => $v) $result[] = json_encode($k).':'.json_encode($v);
	  return '{' . join(',', $result) . '}';
	}
  }
}

if (!function_exists('xml_decode')) {
	function xml_decode($contents, $get_attributes=1, $priority = 'tag') {
		if(!$contents) return array();

		if(!function_exists('xml_parser_create')) {
			//print "'xml_parser_create()' function not found!";
			return array();
		}

		//Get the XML parser of PHP - PHP must have this module for the parser to work
		$parser = xml_parser_create('');
		xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8"); # http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
		xml_parse_into_struct($parser, trim($contents), $xml_values);
		xml_parser_free($parser);

		if(!$xml_values) return;//Hmm...

		//Initializations
		$xml_array = array();
		$parents = array();
		$opened_tags = array();
		$arr = array();

		$current = &$xml_array; //Refference

		//Go through the tags.
		$repeated_tag_index = array();//Multiple tags with same name will be turned into an array
		foreach($xml_values as $data) {
			unset($attributes,$value);//Remove existing values, or there will be trouble

			//This command will extract these variables into the foreach scope
			// tag(string), type(string), level(int), attributes(array).
			extract($data);//We could use the array by itself, but this cooler.

			$result = array();
			$attributes_data = array();

			if(isset($value)) {
				if($priority == 'tag') $result = $value;
				else $result['value'] = $value; //Put the value in a assoc array if we are in the 'Attribute' mode
			}

			//Set the attributes too.
			if(isset($attributes) and $get_attributes) {
				foreach($attributes as $attr => $val) {
					if($priority == 'tag') $attributes_data[$attr] = $val;
					else $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
				}
			}

			//See tag status and do the needed.
			if($type == "open") {//The starting of the tag '<tag>'
				$parent[$level-1] = &$current;
				if(!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag
					$current[$tag] = $result;
					if($attributes_data) $current[$tag. '_attr'] = $attributes_data;
					$repeated_tag_index[$tag.'_'.$level] = 1;

					$current = &$current[$tag];

				} else { //There was another element with the same tag name

					if(isset($current[$tag][0])) {//If there is a 0th element it is already an array
						$current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
						$repeated_tag_index[$tag.'_'.$level]++;
					} else {//This section will make the value an array if multiple tags with the same name appear together
						$current[$tag] = array($current[$tag],$result);//This will combine the existing item and the new item together to make an array
						$repeated_tag_index[$tag.'_'.$level] = 2;

						if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well
							$current[$tag]['0_attr'] = $current[$tag.'_attr'];
							unset($current[$tag.'_attr']);
						}

					}
					$last_item_index = $repeated_tag_index[$tag.'_'.$level]-1;
					$current = &$current[$tag][$last_item_index];
				}

			} elseif($type == "complete") { //Tags that ends in 1 line '<tag />'
				//See if the key is already taken.
				if(!isset($current[$tag])) { //New Key
					$current[$tag] = $result;
					$repeated_tag_index[$tag.'_'.$level] = 1;
					if($priority == 'tag' and $attributes_data) $current[$tag. '_attr'] = $attributes_data;

				} else { //If taken, put all things inside a list(array)
					if(isset($current[$tag][0]) and is_array($current[$tag])) {//If it is already an array...

						// ...push the new element into that array.
						$current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;

						if($priority == 'tag' and $get_attributes and $attributes_data) {
							$current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
						}
						$repeated_tag_index[$tag.'_'.$level]++;

					} else { //If it is not an array...
						$current[$tag] = array($current[$tag],$result); //...Make it an array using using the existing value and the new value
						$repeated_tag_index[$tag.'_'.$level] = 1;
						if($priority == 'tag' and $get_attributes) {
							if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well

								$current[$tag]['0_attr'] = $current[$tag.'_attr'];
								unset($current[$tag.'_attr']);
							}

							if($attributes_data) {
								$current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
							}
						}
						$repeated_tag_index[$tag.'_'.$level]++; //0 and 1 index is already taken
					}
				}

			} elseif($type == 'close') { //End of tag '</tag>'
				$current = &$parent[$level-1];
			}
		}

		return($xml_array);
	}
}

if (!function_exists('xml_encode')) {
	function xml_encode($object, $indent='') {
		while (list($key, $val) = each($v)) {
			if ($key == '__attr') continue;
			// Check for __attr
			if (is_object($val->__attr)) {
				while (list($key2, $val2) = each($val->__attr)) {
					$attr .= " $key2=\"$val2\"";
				}
			}
			else $attr = '';
			if (is_array($val) || is_object($val)) {
				print("$indent<$key$attr>\n");
				xmlencode($val, $indent.'  ');
				print("$indent</$key>\n");
			}
			else print("$indent<$key$attr>$val</$key>\n");
		}
	}
}

if (!function_exists('csv_encode')) {
	function csv_encode($array,$csv_terminated="\n",$csv_separator=";",$csv_enclosed='"',$csv_escaped="\"") {
		$out = '';
		foreach ($array as $lines) {
			$schema_insert = '';
			foreach ($lines as $line)
			{
			//for ($i=0;$i<count($line);$i++) {
				if ($schema_insert != '') $schema_insert .= $csv_separator;
				$value = '';
				if (isset($line)) $value = $line;
				$schema_insert .= $csv_enclosed . str_replace($csv_enclosed, $csv_escaped . $csv_enclosed, $value) . $csv_enclosed;
			}
			$schema_insert .= $csv_terminated;
			$out .= $schema_insert;
		}
		return $out;
	}
}

if (!function_exists('csv_decode')) {
	function csv_decode($input, $delimiter = ",", $enclosure = '"', $escape = "\\") {
		$maxMBs = 5 * 1024 * 1024;
		$fp = fopen("php://temp/maxmemory:$maxMBs", 'r+');
		fputs($fp, $input);
		rewind($fp);

		$data = Array();
		
		while ( ($sdt = fgetcsv($fp, 0, $delimiter, $enclosure)) !== FALSE) {
			$data[] = $sdt;
		}

		fclose($fp);
		return $data;
	}
}

if (!function_exists('str_getcsv')) {
	function str_getcsv($input, $delimiter = ",", $enclosure = '"', $escape = "\\") {
		$maxMBs = 5 * 1024 * 1024;
		$fp = fopen("php://temp/maxmemory:$maxMBs", 'r+');
		fputs($fp, $input);
		rewind($fp);

		$data = fgetcsv($fp, 0, $delimiter, $enclosure); //  $escape only got added in 5.3.0

		fclose($fp);
		return $data;
	}
}

if(!function_exists('mb_ucwords'))
{
	function mb_ucwords($str,$encoding="UTF-8")
	{
		  return mb_convert_case($str, MB_CASE_TITLE, $encoding);
	}
}

if (!function_exists('mb_ucfirst') && function_exists('mb_substr')) {
	function mb_ucfirst($string,$encoding="UTF-8") {
		$string = mb_strtoupper(mb_substr($string, 0, 1),$encoding) . mb_substr($string, 1);
		return $string;
	}
}

if (!function_exists('getmicrotime')) {
	function getmicrotime() 
	{
		list($usec, $sec) = explode(" ", microtime()); 
		return ((float)$usec + (float)$sec);
	}
}

?>