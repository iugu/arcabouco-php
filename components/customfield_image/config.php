<?

class image extends custom_field
{
	var $default_path = 'media/tmp';
	var $default_image = '/components/customfield_image/images/noimage.gif';
	var $generate_prefix = false;
	var $storage_filename = '';
	var $original_filename = '';
	var $filesize = 0;
	var $max_file_size = 2147483648;
	var $allowed_extensions = Array('jpg','png','gif');
	var $append_original_filename = true;
	var $width = 256;
	var $height = 256;
	var $temporary_filename = '';

	protected $__internal_errors_on_set = false;

	function set_default_path($tmp_path) {
		$this->default_path = $tmp_path;
	}

	function get_default_path() {
		return $this->default_path;
	}

	function set_default_image($tmp_image) {
		$this->default_image = $tmp_image;
	}

	function set_maximum_size($value) { $this->max_file_size = $value; }
	function get_maximum_size() { return $this->max_file_size; }

	function set_accepted_extensions($value) { $this->allowed_extensions = explode(',',str_replace(' ','',$value)); }
	function get_accepted_extensions() { return $this->allowed_extensions; }

	function file() {
		$this->storage_filename = $this->default_value();
	}

	function field_type() { return "text"; }

	function default_value()
	{
		return "";
	}

	function set_size($width,$height) {
		$this->width = $width;
		$this->height = $height;
	}

	function get_size() {
		return Array('width'=>$this->width,'height'=>$this->height);
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
	
	function get_image_size() {
		if ($this->storage_filename == '') return false;

		$path_and_file = rtrim(core::base_directory(),'/') . $this->storage_filename;
		if (!file_exists($path_and_file)) return false;

		list($original_width,$original_height) = getimagesize($path_and_file);
		return Array($original_width,$original_height);
	}
	
	function get_image($width=0,$height=0,$type=3,$color=Array(255,255,255)) {
		if ($this->storage_filename == '') return false;
		
		$path_and_file = rtrim(core::base_directory(),'/') . $this->storage_filename;
		$path_and_file = str_replace('//','/',$path_and_file);
		if (!file_exists($path_and_file)) return false;
		
		if ($width==0 || $height==0) return web::document_url() . core::relative_base_path($path_and_file);
		
		list($original_width,$original_height) = getimagesize($path_and_file);
		
		if (($width != $original_width) || ($height != $original_height)) {
			$image_thumb_filename = dirname($path_and_file) . "/tmp/${width}x${height}x${type}-" . basename($path_and_file);
			core::create_image($path_and_file, $image_thumb_filename,$width,$height,$type,$color);
			$path_and_file = $image_thumb_filename;
		}
		
		return web::document_url() . core::relative_base_path($path_and_file);
	}
	
	function cancel_set()
	{
		if (($this->storage_filename != '') && ($this->temporary_filename != '')) {
			unlink( core::base_directory() . $this->storage_filename);
			if ($this->temporary_filename != '') {
				$this->storage_filename = $this->temporary_filename;
				$this->temporary_filename = '';
			}
		}
	}
	
	function complete_set() {
		if ($this->temporary_filename != '') {
			unlink( core::base_directory() . $this->temporary_filename );
				if (strpos(core::base_directory() . $this->temporary_filename,'media/tmp') !== false) {
					$other_files = core::list_files(dirname(core::base_directory() . $this->temporary_filename) . '/',true);
					foreach ($other_files as $other_file) {
						if (strpos($other_file,basename($this->temporary_filename)) !== false) {
							unlink($other_file);
						}
					}
				}
			}
	}

	function set($tmp_value) {
		if (is_array($tmp_value)) {
			if (isset($tmp_value['uploaded_filename'])) {
				$this->temporary_filename = $tmp_value['uploaded_filename'];
				
				// Have been uploaded by Flash
				$filename = basename($tmp_value['original_filename']);
				$fileextension = '';

				if ($this->storage_filename != '') {
					if (file_exists(core::base_directory() . $this->storage_filename)) {
						unlink(core::base_directory() . $this->storage_filename);
						$other_files = core::list_files(dirname(core::base_directory() . $this->storage_filename) . '/tmp/');
						foreach ($other_files as $other_file) {
							if (strpos($other_file,basename($this->storage_filename)) !== false) {
								unlink($other_file);
							}
						}
					}
				}

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
						if ($new_filename != '') $new_filename .= '_';
						$new_filename .= $filename;
					}
					$new_filename .= $fileextension;
					$path_and_file = core::base_directory() . '/' . $this->default_path . '/' . $new_filename;

					if (file_exists($path_and_file)) {
						$this->enable_prefix_generation();
					}
				} while (file_exists($path_and_file));

				if (!is_dir(dirname($path_and_file))) mkdir( dirname($path_and_file), 0777, true);

				copy( core::base_directory() . $tmp_value['uploaded_filename'], $path_and_file );

				$this->storage_filename = core::relative_base_path($path_and_file);
				$this->original_filename = $tmp_value['original_filename'];
				$this->filesize = filesize($path_and_file);
			}
		}
		else {
			if (strpos($tmp_value,'ABDATA#') !== false) {
				$field_values = unserialize( substr($tmp_value,strpos($tmp_value,'ABDATA#')+7) );
				$this->storage_filename = $field_values['storage_filename'];
				$this->original_filename = $field_values['original_filename'];
				$this->filesize = $field_values['filesize'];

			}
			else {
				if ($tmp_value != '') {
					if ($this->storage_filename != '') {
						if (file_exists(core::base_directory() . $this->storage_filename)) {
							unlink(core::base_directory() . $this->storage_filename);
						}
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
	
	function on_delete()
	{
		if ($this->storage_filename != '') {
			if (file_exists(core::base_directory() . $this->storage_filename)) {
				unlink(core::base_directory() . $this->storage_filename);
			}
		}
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

		$field_token = $field_name . '_' . md5($field_name . '_' . uniqid(rand()));
		
		$has_rowid = '';
		if ($this->get_parent() && isset($this->get_parent()->ROWID)) $has_rowid = $this->get_parent()->ROWID;

		$field_id = inflector::underscore(inflector::unaccent($object)) . '_' . $field_name . '_' . $has_rowid . uniqid();
		$field_name = inflector::underscore(inflector::unaccent($object)) . '[' . $field_name . ']';

		$size = isset($options['size'])?$options['size']:'large';

		$field_value = $this->get();

			$box_width = $this->width;
			$box_height = $this->height + 32;
			$block_width = $this->width;
			$block_height = $this->height;
			$color = Array(255,255,255);

			$image_file = web::document_url() . $this->default_image;

			if ($this->storage_filename != '') {
				$image_file = web::document_url() . $this->storage_filename;
				
				$width = $block_width;
				$height = $block_height;
				$image_file = $this->storage_filename;
				$path_and_file = rtrim(core::base_directory(),'/') . $image_file;
				
				$image_thumb_filename = '';

				if (file_exists($path_and_file)) {
					$image_thumb_filename = dirname($path_and_file) . "/tmp/${width}x${height}x3-" . basename($path_and_file);
					core::create_image($path_and_file, $image_thumb_filename,$width,$height,1,$color);
				}
				
				$image_file = web::document_url() . core::relative_base_path( $image_thumb_filename );
			}
			
			
		?>
			<div style="width:<?= $box_width ?>px;height:<?= $box_height ?>px;margin-bottom:20px" class="customfield_image">
				<div style="width:<?= $block_width ?>px;height:<?= $block_height ?>px;" class="display" id="imgbox_<?= $field_id ?>"><img src="<?= $image_file ?>" width="<?= $block_width ?>" height="<?= $block_height ?>" alt="" /></div>
				<div class="w80 mla mra"><a href="#" id="editor_button_<?= $field_id ?>" class="button w80 tac"><span class="enable_icon"><img src="/components/customfield_image/images/edit.gif" width="16" height="16" alt="" />Editar</span></a></div>
			</div>
			<script type="text/javascript">
				//<![CDATA[
				function callback_return_<?= $field_id ?>(file)
				{
					var imgbox = $('imgbox_<?= $field_id ?>');
					
					imgbox.set('load', {method: 'get','data': {
							'width':<?= $block_width ?>,
							'height':<?= $block_height ?>,
							'file':file
						}
					});
					
					imgbox.load('<?= url_for('uploaded_image_thumb','customfield_image_handler') ?>');
					
					hide_lightbox();
				}

				window.addEvent('domready',function() {
					$('editor_button_<?= $field_id ?>').addEvent('click', function(e) {
						e.preventDefault();
						create_lightbox_html('<div id="customfield_image_editor"></div>',wz().width-60,wz().height-60);
						show_lightbox();
						var flashvars = {
							'imageURL': '',
							'docid': '<?= $field_name ?>',
							'target_url' : '<?= url_for('upload_image','customfield_image_handler',web::get_current_token()) ?>',
							'callback_function' : 'callback_return_<?= $field_id ?>',
							'callback_cancel'   : 'hide_lightbox',
							'current_page_image_directory' : ''
						};
						swfobject.embedSWF("/components/customfield_image/image-editor.swf", "customfield_image_editor", wz().width-60, wz().height-60, "10.0.0", "/components/01swfobject/expressInstall.swf",flashvars);
					});
				});
				//]]>
			</script>
		<?

		return '';
	}

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

class customfield_image_handler
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

		$style_file = $this->find_file('customfield_image.css');
		
$html = <<<ENDOFHTML
	<link rel="stylesheet" href="${style_file}" type="text/css" />

ENDOFHTML;

		return $html;
	}
	
	function uploaded_image_thumb($params) {
		if (!isset($params['file'])) return false;
		if (!isset($params['width'])) return false;
		if (!isset($params['height'])) return false;
		
		$width = $params['width'];
		$height = $params['height'];
		$image_file = $params['file'];
		$color = Array(255,255,255);
		
		//$image_file = '/media/tmp/4bb4b5d78d97215be30dfe3dd6c2bb1f70b8f2011acb0.jpg';
		
		$path_and_file = rtrim(core::base_directory(),'/') . $image_file;
		
		if (file_exists($path_and_file)) {
			$image_thumb_filename = dirname($path_and_file) . "/tmp/${width}x${height}x3-" . basename($path_and_file);
			core::create_image($path_and_file, $image_thumb_filename,$width,$height,1,$color);
			
			echo '<img src="' . core::relative_base_path($image_thumb_filename) . '" width="' . $width .'" height="' . $height . '" alt="" />';
		}

		return true;
	}

	function upload_image($params) {
		$POST_MAX_SIZE = ini_get('post_max_size');
		$unit = strtoupper(substr($POST_MAX_SIZE, -1));	

		$multiplier = ($unit == 'M' ? 1048576 : ($unit == 'K' ? 1024 : ($unit == 'G' ? 1073741824 : 1)));

		$length = 0;

		if (isset($_SERVER['CONTENT_LENGTH'])) $length = (int)$_SERVER['CONTENT_LENGTH'];

		if ($length > $multiplier*(int)$POST_MAX_SIZE && (int)$POST_MAX_SIZE) {
			header("HTTP/1.1 500 Internal Server Error");
			echo "POST exceeded maximum allowed size.";
			exit(0);
		}

		$media_directory = '/media/tmp/';

		$save_path = core::base_directory() . $media_directory;

		if (!is_dir($save_path)) mkdir( $save_path, 0777, true);

		$upload_name = "Filedata";
		$max_file_size_in_bytes = 2147483647;				// 2GB in bytes
		$extension_whitelist = array("jpg","png","jpeg","gif");	// Allowed file extensions
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

		if (!isset($_FILES[$upload_name])) {
			echo "No upload found in \$_FILES for " . $upload_name;
			return true;
		} else if (isset($_FILES[$upload_name]["error"]) && $_FILES[$upload_name]["error"] != 0) {
			echo $uploadErrors[$_FILES[$upload_name]["error"]];
			return true;
		} else if (!isset($_FILES[$upload_name]["tmp_name"]) || !@is_uploaded_file($_FILES[$upload_name]["tmp_name"])) {
			echo "Upload failed is_uploaded_file test.";
			return true;
		} else if (!isset($_FILES[$upload_name]['name'])) {
			echo "File has no name.";
			return true;
		}

		$file_size = @filesize($_FILES[$upload_name]["tmp_name"]);

		if (!$file_size || $file_size > $max_file_size_in_bytes) {
			echo "File exceeds the maximum allowed size";
			return true;
		}

		$file_name = strtolower(make_filename( basename($_FILES[$upload_name]['name']) ));
		$original_filename = strtolower(make_filename( basename($_FILES[$upload_name]['name']) ));

		$path_info = pathinfo($_FILES[$upload_name]['name']);
		$file_extension = $path_info["extension"];
		$is_valid_extension = false;
		foreach ($extension_whitelist as $extension) {
			if (strtolower($file_extension) == strtolower($extension)) {
				$is_valid_extension = true;
				break;
			}
		}
		if (!$is_valid_extension) {
			echo "Invalid file extension";
			return true;
		}

		$filename = str_replace(" ","-",$file_name);

		do
			{
			$new_filename = '';
			$new_filename .= uniqid() . md5(mt_rand());

			if (strrpos($filename,'.') !== false) {
				$fileextension = substr($filename,strrpos($filename,'.'));
				$filename = substr($filename,0,strrpos($filename,'.'));
			}
			$new_filename .= $fileextension;
			$path_and_file = $save_path . $new_filename;
		} while (file_exists($path_and_file));

		$file_name = $new_filename;

		if (!@move_uploaded_file($_FILES[$upload_name]["tmp_name"], strtolower($save_path.$file_name) )) {
			echo "File could not be saved: ". $save_path.$file_name;
			return true;
		}

		$token = web::begin_token_storage();
		$token_vars = web::session_get_variable($token);

		parse_str($params['docid'] . '[original_filename]=' . urlencode($original_filename) . '&' . $params['docid'] . '[uploaded_filename]=' . urlencode(core::relative_base_path($save_path.$file_name)),$fields);

		$token_vars['fields'] = array_merge_recursive($fields,array_diff_assoc($token_vars['fields'],$fields));

		web::session_set_variable($token,$token_vars);

		echo core::relative_base_path($save_path.$file_name);

		return true;
	}


}

component::register('customfield_image_handler', new customfield_image_handler());
controller::register('/admin/customfield_image/(.+?)/upload_data',		'customfield_image_handler',	'upload_image','POST',Array('token'));
controller::register('/admin/customfield_image/get_thumb',		'customfield_image_handler',	'uploaded_image_thumb','GET');

?>