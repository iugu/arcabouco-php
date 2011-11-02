<?

/*
	$this->arquivo->set_default_path('midia/imagens');
	$this->arquivo->enable_prefix_generation();
	$this->arquivo->disable_append_filename();
*/
class file extends custom_field
{
	var $default_path = 'media/tmp';
	var $generate_prefix = false;
	var $storage_filename = '';
	var $original_filename = '';
	var $filesize = 0;
	var $max_file_size = 2147483648;
	var $allowed_extensions = Array();

	var $append_original_filename = true;
	
	protected $__internal_errors_on_set = false;

	function set_default_path($tmp_path) {
		$this->default_path = $tmp_path;
	}

	function set_maximum_size($value) { $this->max_file_size = $value; }
	function get_maximum_size() { return $this->max_file_size; }
	
	function set_accepted_extensions($value) { $this->allowed_extensions = explode(',',str_replace(' ','',$value)); }
	function get_accepted_extensions() { return $this->allowed_extensions; }

	function make_filename($title) {
		$title = mb_strtolower($title, "utf-8");
		$search = explode(",","ç,æ,œ,á,é,í,ó,ú,à,è,ì,ò,ù,ä,ë,ï,ö,ü,ÿ,â,ê,î,ô,û,å,e,i,ø,u,ã,õ,',\",?,!,=,:,#,\",%,$,@,*,(,),¨,&,´,^,~,º,/,ª,+");
		$replace = explode(",","c,ae,oe,a,e,i,o,u,a,e,i,o,u,a,e,i,o,u,y,a,e,i,o,u,a,e,i,o,u,a,o, , , , , , , , , , , , , , , ,,,,,,,");
		$title = str_replace($search, $replace, $title);
		$title =  preg_replace('/\s\s+/', ' ', $title);
		$title = str_replace(" ","-",$title);
		$title = str_replace(",","",$title);
		$title =  preg_replace('/--+/', '-', $title);
		$title =  preg_replace('/-\./', '.', $title);
		$title = trim($title,'-');
		return $title;
	}

	function file() {
		$this->storage_filename = $this->default_value();
	}

	function field_type() { return "text"; }

	function default_value()
	{
		return "";
	}

	function enable_prefix_generation() {
		$this->generate_prefix = true;
	}

	function disable_prefix_generation() {
		$this->generate_prefix = false;
	}

	function enable_append_filename() {
		$this->append_original_filename = true;
	}

	function disable_append_filename() {
		$this->append_original_filename = false;
	}

	function set($tmp_value) {
		if (is_array($tmp_value)) {
			if (isset($tmp_value['name'])) {
				$filename = basename($tmp_value['name']);
				$fileextension = '';
				

				do
					{
					$new_filename = '';
					if ($this->generate_prefix) {
						$new_filename .= uniqid() . md5(mt_rand());
					}

					if (strrpos($filename,'.') !== false) {
						$fileextension = substr($filename,strrpos($filename,'.'));
						$filename = substr($filename,0,strrpos($filename,'.'));
					}
					if ($this->append_original_filename) {
						//$new_filename .= $this->make_filename(basename($tmp_value['name']));
						$new_filename .= '_';
						$new_filename .= $filename;
					}
					$new_filename .= $fileextension;
					$path_and_file = core::root_directory() . '/' . $this->default_path . '/' . $new_filename;
				} while (file_exists($path_and_file));
				
				$last_error = false;
				
				for ($i=0;$i<3;$i++) {
					$return = web::save_received_file($tmp_value,$path_and_file,$this->allowed_extensions,$this->max_file_size);
					if (isset($return['msg']) && ($return['msg'] == 'Success')) {
						$field_values = Array();
						$field_values['storage_filename'] = core::relative_path($path_and_file);
						$field_values['original_filename'] = basename($tmp_value['name']);
						$field_values['filesize'] = @filesize($tmp_value["tmp_name"]);
						
						if ($this->storage_filename != '') {
							unlink($this->storage_filename);
							$this->storage_filename = '';
							$this->original_filename = '';
							$this->filesize = 0;
						}

						$this->storage_filename = $field_values['storage_filename'];
						$this->original_filename = $field_values['original_filename'];
						$this->filesize = $field_values['filesize'];
						$last_error = false;
						break;
					}
					else {
						$last_error = $return['error_msg'];
					}
				}
				if ($last_error) {
					$this->__internal_errors_on_set = $last_error;
				}
			}
		}
		else
		{
			if (strpos($tmp_value,'ABDATA#') !== false) {
				$field_values = unserialize( substr($tmp_value,strpos($tmp_value,'ABDATA#')+7) );
				$this->storage_filename = $field_values['storage_filename'];
				$this->original_filename = $field_values['original_filename'];
				$this->filesize = $field_values['filesize'];

			}
			else {
				if ($tmp_value != '') {
					if ($this->storage_filename != '') {
						unlink($this->storage_filename);
						$this->storage_filename = '';
						$this->original_filename = '';
						$this->filesize = 0;
					}
				}
				$this->storage_filename = $tmp_value;
			}
		}
	}

	function get() { 
		return $this->original_filename;
	}

	function get_data() {
		// Prepare data for save
		$field_values = Array();
		$field_values['storage_filename'] = $this->storage_filename;
		$field_values['original_filename'] = $this->original_filename;
		$field_values['filesize'] = $this->filesize;
		return 'ABDATA#' . serialize($field_values);
	}

	function html_for($field_name,&$object,$options) {
		
		$field_token = $field_name . '_' . md5($field_name . '_' . uniqid(rand(),true));
	
		$field_id = inflector::underscore(inflector::unaccent($object)) . '_' . $field_name;
		$field_name = inflector::underscore(inflector::unaccent($object)) . '[' . $field_name . ']';

		$size = isset($options['size'])?$options['size']:'large';

		$field_value = $this->get();
		
		if ($field_value != '') {
			echo '<span class="fileblock">' . $field_value . '</span>';
		}
		
		return "<input class=\"$size file_input\" id=\"$field_id\" name=\"$field_name\" size=\"30\" type=\"file\" value=\"$field_value\" />";
	}
	
	/*
	
	*/
	
	function validate($value)
	{
		if ($this->__internal_errors_on_set != false) {
			_("Exceeded maximum allowed file size");
			_("Invalid file extension");
			if ($this->__internal_errors_on_set == "Upload failed is_uploaded_file test.") return true;
			else if ($this->__internal_errors_on_set == "No upload found.") return true;
			return _($this->__internal_errors_on_set);
		}
		else {
			return true;
		}
	}
	
	function __toString() {
		return $this->get();
	}
}

class customfield_file_handler
{
	function path() {
		return core::transform_directory(dirname(__FILE__));
	}

	function find_file($file) {
		$document_url = web::document_url();
		$application_visuals_js = $file;
		$layout_directory = core::root_directory() . '/layout/';
		return core::relative_path( (file_exists($layout_directory . basename($file))) ? $layout_directory . basename($file) : $this->path() . '/' . $file);
	}

	function before_head()
	{
		$document_url = web::document_url();
		
		$style_file = $this->find_file('customfield_file.css');

$html = <<<ENDOFHTML
<link rel="stylesheet" href="${style_file}" type="text/css" />
ENDOFHTML;

		return $html;
	}
	
	
}

component::register('customfield_file_handler', new customfield_file_handler());

?>