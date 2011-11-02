<?

 
class datetimeselect extends custom_field
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
			$field_value = core::time();
		}

		$size = isset($options['size'])?$options['size']:'large';

		$day = core::format_date($field_value,'%d',1);
		$month = core::format_date($field_value,'%m',1);
		$year = core::format_date($field_value,'%Y',1);
		$hour = core::format_date($field_value,'%H',1);
		$minute = core::format_date($field_value,'%M',1);

		for ($i=1;$i<13;$i++) {
			$current_month = 'month_' . $i . '_selected';
			$$current_month = ($month==$i)?'selected="selected"':'';
		}

		$html_for = <<<ENDOFHTML
		<div class="cbf pr">
			<div class="fl" style="width:30px">
				<input type="text" name="${field_name}[day]" value="${day}" style="width:20px;text-align:center" />
			</div>
			<div class="fl w60">
				<select name="${field_name}[month]" style="width:55px;">
					<option value="1"${month_1_selected}>Jan</option>
					<option value="2"${month_2_selected}>Fev</option>
					<option value="3"${month_3_selected}>Mar</option>
					<option value="4"${month_4_selected}>Abr</option>
					<option value="5"${month_5_selected}>Mai</option>
					<option value="6"${month_6_selected}>Jun</option>
					<option value="7"${month_7_selected}>Jul</option>
					<option value="8"${month_8_selected}>Ago</option>
					<option value="9"${month_9_selected}>Set</option>
					<option value="10"${month_10_selected}>Out</option>
					<option value="11"${month_11_selected}>Nov</option>
					<option value="12"${month_12_selected}>Dez</option>
				</select>
			</div>
			<div class="fl w10">
				,
			</div>
			<div class="fl" style="width:45px">
				<input type="text" name="${field_name}[year]" value="${year}" style="width:40px;text-align:center" />
			</div>
			<div class="fl tac" style="width:20px;font-size:11px;padding-top:0px">
				@
			</div>
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
			if (strlen($value['year']) == 2) {
				$value['year'] = '20' . $value['year'];
			}
			$value['day'] = intval($value['day']);
			if ($value['day'] == 0) $value['day'] = 1;
			if ($value['day'] > 31) $value['day'] = 31;
			$this->value = core::date(intval($value['day']),intval($value['month']),intval($value['year']),intval($value['hour']),intval($value['minute']),0) - core::$defaults["system_timezone"]*3600;
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