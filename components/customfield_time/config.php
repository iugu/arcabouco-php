<?

 
class timeselect extends custom_field
{
	var $value = '';

	function field_type() { return "integer"; }
	function default_value()
	{
		return "";
	}
	function html_for($field_name,&$object,$options) {
		$field_value = '';
		if (isset($object->$field_name)) if ($object->$field_name != '') $field_value = $object->$field_name->get();

		$field_id = inflector::underscore(inflector::unaccent($object)) . '_' . $field_name;
		$field_name = inflector::underscore(inflector::unaccent($object)) . '[' . $field_name . ']';

		if ($field_value == 0) {
			$field_value = 0;
		}

		$size = isset($options['size'])?$options['size']:'large';
		
		$hour = floor($field_value/60);
		$minute = floor($field_value%60);
		$hour = str_pad($hour, 2, "0", STR_PAD_LEFT);
		$minute = str_pad($minute, 2, "0", STR_PAD_LEFT);

		$html_for = <<<ENDOFHTML
		<div class="cbf pr">
			<div class="fl" style="width:25px">
				<input type="text" name="${field_name}[hour]" value="${hour}" style="width:20px;text-align:center" />
			</div>
			<div class="fl tac w10">
				:
			</div>
			<div class="fl" style="width:25px">
				<input type="text" name="${field_name}[minute]" value="${minute}" style="width:20px;text-align:center" />
			</div>
		</div>
ENDOFHTML;

		return $html_for;
	}

	function set($value) {
		if (is_array($value)) {
			$this->value = ($value['hour']*60)+$value['minute'];
		} else {
			$this->value = $value;
		}
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