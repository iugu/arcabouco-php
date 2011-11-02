<?

/*
 * This file is part of the Arcabouco Framework.
 * (c) 2008 Patrick Negri <patrick@agencialobo.com.br>
 * (c) 2008 Paulo Lobo <plobo@agencialobo.com.br>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 
class email extends custom_field
{
	var $value = '';
	
	function field_type() { return "text"; }
	function default_value()
	{
		return "";
	}
	static function validate($value)
	{
		if ($value == '') return true;
		if(eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", $value)) {
			return true;
		}
		else
		{
			return _("is not a valid address");
		}
	}
	
	function set($value) {
		$this->value = $value;
	}

	function get() {
		return $this->value;
	}

	function get_data() {
		return $this->value;
	}

	function __toString() {
		return $this->value;
	}
}
 
 ?>