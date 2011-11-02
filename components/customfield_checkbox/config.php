<?

/*
 * This file is part of the Arcabouco Framework.
 * (c) 2008 Patrick Negri <patrick@agencialobo.com.br>
 * (c) 2008 Paulo Lobo <plobo@agencialobo.com.br>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 
class checkbox extends custom_field
{
	var $value = '';
	
	var $invert = false;
	
	function field_type() { return "integer"; }
	function default_value()
	{
		return "";
	}
	
	function set($value) {
		if (is_array($value)) {
			$this->value = $value[count($value)-1];
		} else {
			$this->value = $value;
		}
	}
	
	function invert($flag) {
		if ($flag) $this->invert = true;
		else $this->invert = false;
	}

	function html_for($field_name,&$object,$options) {
		$field_value = '';
		$checked_value = 1;
		
		if (isset($object->$field_name)) if ($object->$field_name != '') {
			$field_value = $object->$field_name->get();
			if ($object->$field_name->invert) $checked_value = 0;
		}
		
		$unchecked_value = 0;
		if ($checked_value == 0) $unchecked_value = 1;
		
		$has_rowid = '';
		if ($this->get_parent() && isset($this->get_parent()->ROWID)) $has_rowid = '_' . $this->get_parent()->ROWID;

		$field_id = inflector::underscore(inflector::unaccent($object)) . '_' . $field_name . $has_rowid;
		$field_name = inflector::underscore(inflector::unaccent($object)) . '[' . $field_name . ']';

		$add_class = '';
		if (isset($options['class'])) $add_class .= $options['class'];
		
		$checked_var = '';
		
		if (intval($field_value) == $checked_value) {
			$checked_var = ' checked="checked"';
		}
		
$field_html = <<<ENDOFHTML
<input type="hidden" name="${field_name}[]" id="${field_id}_field" value="${unchecked_value}" class="${add_class}" />
<input type="checkbox" name="${field_name}[]" id="${field_id}" value="${checked_value}" style="border:none" class="${add_class}" ${checked_var} />
ENDOFHTML;

		return $field_html;
	}

	function get() {
		return $this->value;
	}

	function get_data() {
		return $this->value;
	}

	function __toString() {
		return strval($this->value);
	}
}
 
 ?>