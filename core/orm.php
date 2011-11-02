<?php

/*
 * This file is part of the Arcabouco Framework.
 * (c) 2008 Patrick Negri <patrick@agencialobo.com.br>
 * (c) 2008 Paulo Lobo <plobo@agencialobo.com.br>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

define("ALL", 'ALL');
define("ONE", 'ONE');
define("RANDOM", 'RANDOM');

define("HAVE_NONE", 0);
define("HAVE_ONE", 1);
define("HAVE_MANY", 2);

/*
	Direction of Relationship
*/
define("ZERO_ZERO", 0);
define("ZERO_ONE", 1);
define("ZERO_MANY", 2);
define("ONE_ZERO", 3);
define("ONE_ONE", 4);
define("ONE_MANY", 5);
define("MANY_ZERO", 6);
define("MANY_ONE", 7);
define("MANY_MANY", 8);

/**
 * ORM File
 * @todo Urgent
 *     - optimize / rewrite code
 *     - document code
 *
*/

/**
* Custom Field Object for Object Relational Mapping
*
* @package Custom Field
* 
*/
class custom_field
{
	protected $__parent = null;
	
	function set_parent(&$pObject) { $this->__parent = $pObject; }
	function get_parent() { return $this->__parent; }
	
	/**
	 * field_type()
	 *
	 * This function is a dummy function responsible to return how the data is spected
	 * to be saved when sent to database adapter:
	 * {@source}
	 *
	*/
	function field_type()
	{
		return "text";
	}
	
	function cancel_set() {
		
		return true;
	}
	
	function complete_set() {
	
		return true;
	}

	/**
	 * default_value()
	 *
	 * This function is a dummy function responsible to return default data values:
	 * {@source}
	 *
	*/
	function default_value()
	{
		return '';
	}
	
	function get_data()
	{
		return '';
	}
}

class textarea extends custom_field
{
	var $value = '';
	
	function field_type() { return "text"; }
	function default_value()
	{
		return "";
	}
	function html_for($field_name,&$object,$options) {
		$field_value = '';
		if (isset($object->$field_name)) if ($object->$field_name != '') $field_value = $object->$field_name->get();

		$field_id = inflector::underscore(inflector::unaccent($object)) . '_' . $field_name;
		$field_name = inflector::underscore(inflector::unaccent($object)) . '[' . $field_name . ']';
		
		$add_class = '';
		if (isset($options['class'])) $add_class .= $options['class'];
		
		$add_class .= isset($options['size'])?' ' . $options['size']:' large';

		return "<textarea id=\"$field_id\" class=\"$add_class\" name=\"$field_name\" cols=\"30\" rows=\"4\">$field_value</textarea>";
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

class password extends custom_field
{
	var $value = '';
	function field_type() { return "text"; }
	
	function default_value()
	{
		return "";
	}
	
	function html_for($field_name,&$object,$options) {
		$field_value = '';
		if (isset($object->$field_name)) if ($object->$field_name != '') $field_value = $object->$field_name;

		$field_id = inflector::underscore(inflector::unaccent($object)) . '_' . $field_name;
		$field_name = inflector::underscore(inflector::unaccent($object)) . '[' . $field_name . ']';
		
		$size = isset($options['size'])?$options['size']:'large';

		return "<input id=\"$field_id\" class=\"$size\" autocomplete=\"off\" name=\"$field_name\" size=\"30\" type=\"password\" value=\"$field_value\" />";
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

class password_confirmation extends custom_field
{
	var $value = '';
	function field_type() { return "virtual"; }
	function default_value()
	{
		return "";
	}
	function html_for($field_name,&$object,$options) {
		$field_value = '';
		if (isset($object->$field_name)) if ($object->$field_name != '') $field_value = $object->$field_name;

		$field_id = inflector::underscore(inflector::unaccent($object)) . '_' . $field_name;
		$field_name = inflector::underscore(inflector::unaccent($object)) . '[' . $field_name . ']';
		
		$size = isset($options['size'])?$options['size']:'large';

		return "<input id=\"$field_id\" class=\"$size\" name=\"$field_name\" size=\"30\" type=\"password\" value=\"$field_value\" />";
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

/**
 * Object Relational Mapping
 *
 * @package orm
 * 
*/
class orm
{
	public static $__database_configuration = NULL;
	public static 	 $__relationship = Array();
	public static 	 $__additional_fields = Array();
	public static $__existent_tables = Array();
	protected static $__table_fields = Array();
	protected $__table_name = '';
	protected $__adapter = NULL;
	protected $__errors = NULL;
	protected $__rules = NULL;
	protected $__conf = Array();
	
	static $__map = Array('text','integer','real','blob','calculated','virtual');

	//static function is_orm($var) { if (is_object($var)) { if (class_exists($var)) { $parents = class_parents($var);  return array_key_exists('orm',$parents); } } if (is_array($var)) { return false; } $parents = false; if (class_exists($var)) { $parents = class_parents($var); } if ( (is_string($var)) && ($parents == FALSE) ) { return false; } if ( (!is_object($var)) && ($parents === FALSE) ) { return false; } return array_key_exists('orm',$parents); }
	
	static function is_orm($var) {
	
		if (is_object($var)) 
		{
			if (class_exists($var)) {
				$parents = class_parents($var);
				return array_key_exists('orm',$parents);
			}
		}

		if (is_array($var))
		{
			return false;
		}
		
		if (is_object($var)) $class_name = get_class($var);
		else $class_name = $var;

		$parents = false;
		if (class_exists($class_name)) {
			$parents = class_parents($class_name);
		}

		if ( (is_string($var)) && ($parents == FALSE) ) { 
			return false;
		}

		if ( (!is_object($var)) && ($parents === FALSE) ) { 
			return false;
		}

		return array_key_exists('orm',$parents);
	}
	
	static function is_customfield($var) { $parents = class_parents($var); return array_key_exists('custom_field',$parents); }
	
	static function virgulate($x,$y)
	{
		if ($x != '') $x.=',';
		return $x . $y;
	}
	
	static function virgulate_and_comma($x,$y)
	{
		if ($x) $x.=',';
		return $x . '"'.$y.'"';
	}
	
	static function virgulate_and_single_comma($x,$y)
	{
		if ($x) $x.=',';
		return $x . "'".$y."'";
	}
	
	static function spacefy($x,$y)
	{
		return $x . ' ' . $y;
	}
	
	static function spacefy_and_comma($x,$y)
	{
		return '"'.$x.'"' . ' ' . '"'.$y.'"';
	}
	
	static function spacefy_and_single_comma($x,$y)
	{
		return "'".$x."'" . ' ' . "'".$y."'";
	}
	
	static function setify($x,$y)
	{
		return $x . "=" . $y;
	}
	
	static function setify_and_comma($x,$y)
	{
		return '"' . $x. '"' . '=' . '"' . $y . '"';
	}
	
	static function setify_and_comma_values($x,$y)
	{
		return $x . '=' . '"' . $y . '"';
	}
	
	static function setify_and_single_comma($x,$y)
	{
		return "'" . $x. "'" . '=' . "'" . $y . "'";
	}	
	
	static function andify($x,$y)
	{
		if ($x) $x.=' and ';
		return $x . $y;
	}
	
	static function generate_relationship()
	{
		orm::$__relationship = Array();
		$orm_classes = array_filter( get_declared_classes(), "orm::is_orm" );
		foreach ($orm_classes as $ar_class)
		{
			$class_vars = get_class_vars($ar_class);			
			foreach ($class_vars as $field_name=>$value)
			{
				if (strpos($field_name,'__') !== FALSE) continue;
				if ( ($value != "text") && ($value != "integer") && ($value != "real") && ($value != "blob") && ($value != "calculated") && ($value != "virtual" ) )
				{
					$object_name = trim($value,"*");
					if (!isset(orm::$__relationship[$object_name])) orm::$__relationship[$object_name] = Array();
					if (strpos($value,"*") !== FALSE) orm::$__relationship[$object_name][$ar_class] = HAVE_MANY;
					else orm::$__relationship[$object_name][$ar_class] = HAVE_ONE;
				}
			}
		}
	}
	
	static function gather_relationship($arA,$arB)
	{
		if (!isset(orm::$__relationship[$arA])) return HAVE_NONE;
		if (!isset(orm::$__relationship[$arA][$arB])) return HAVE_NONE;
		return orm::$__relationship[$arA][$arB];
	}
	
	static function calculate_relationship($arA,$arB)
	{
		$relationA = self::gather_relationship($arA,$arB);
		$relationB = self::gather_relationship($arB,$arA);
		if 		 ( ($relationA == HAVE_NONE) && ($relationB == HAVE_NONE) ) return ZERO_ZERO;
		else if  ( ($relationA == HAVE_ONE) && ($relationB == HAVE_NONE) ) return ZERO_ONE;
		else if  ( ($relationA == HAVE_MANY) && ($relationB == HAVE_NONE) ) return ZERO_MANY;
		else if  ( ($relationA == HAVE_NONE) && ($relationB == HAVE_ONE) ) return ONE_ZERO;
		else if  ( ($relationA == HAVE_ONE) && ($relationB == HAVE_ONE) ) return ONE_ONE;
		else if  ( ($relationA == HAVE_MANY) && ($relationB == HAVE_ONE) ) return ONE_MANY;
		else if  ( ($relationA == HAVE_NONE) && ($relationB == HAVE_MANY) ) return MANY_ZERO;
		else if  ( ($relationA == HAVE_ONE) && ($relationB == HAVE_MANY) ) return MANY_ONE;
		else if  ( ($relationA == HAVE_MANY) && ($relationB == HAVE_MANY) ) return MANY_MANY;
		return ZERO_ZERO;
	}
	
	static function have_records($results)
	{
		if (is_object($results)) return !$results->is_new();
		if (is_array($results)) return count($results) > 0;
		return false;
	}
	
	function html_for($field_name,$options=Array()) {
		$fields = get_class_vars( $this->__table_name );
		$field_type = 'text';
		if (isset($fields[$field_name])) $field_type = $fields[$field_name];
		$field_type = trim($field_type,"*");
		
		$size = isset($options['size'])?$options['size']:'large';

		$add_class = '';
		if (isset($options['class'])) $add_class .= $options['class'];
		
		if ($add_class != '') $add_class .= ' ';
		$add_class .= $size;

		if (class_exists($field_type))
		{
			if (self::is_orm($field_type))
			{
	
			}
			else if (self::is_customfield($field_type))
			{

				if (method_exists($field_type,'html_for'))
				{
					/*
						echo '<pre>';
						print_r($this->$field_name);
						echo '</pre>';
					*/
					
					//echo $this->__conf["fields"][$field_name];
					if (isset($this->$field_name) && (is_object($this->__conf["fields"][$field_name])) ) {
						return $this->$field_name->html_for($field_name,$this,$options);
					}
					else {
						$object = new $field_type;
						return $object->html_for($field_name,$this,$options);
					}
				}
				else
				{
					$field_value = '';
					if (isset($this->$field_name)) if ($this->$field_name != '') $field_value = $this->$field_name;
					$field_id = inflector::underscore(inflector::unaccent($this)) . '_' . $field_name;
					$field_name = inflector::underscore(inflector::unaccent($this)) . '[' . $field_name . ']';
					

					
					return "<input class=\"$add_class\"  id=\"$field_id\" name=\"$field_name\" size=\"30\" type=\"text\" value=\"$field_value\" />";
				}
			}
		}
		else
		{
			$field_value = '';
			if (isset($this->$field_name)) if ($this->$field_name != '') $field_value = $this->$field_name;
			
			$field_id = inflector::underscore(inflector::unaccent($this)) . '_' . $field_name;
			$field_name = inflector::underscore(inflector::unaccent($this)) . '[' . $field_name . ']';

			if ($field_type == 'integer') {
				return "<input class=\"$add_class\" id=\"$field_id\" name=\"$field_name\" size=\"15\" type=\"text\" value=\"$field_value\" />";
			}
			else if ($field_type == 'real') {
				return "<input class=\"$add_class\" id=\"$field_id\" name=\"$field_name\" size=\"15\" type=\"text\" value=\"$field_value\" />";
			}
			else
			{
				return "<input class=\"$add_class\" id=\"$field_id\" name=\"$field_name\" size=\"30\" type=\"text\" value=\"$field_value\" />";
			}
		}
		/*
		foreach ($fields as $field_name=>$field_type) {
				if (strpos($field_name,'__') !== FALSE) continue;
				if ( ($field_type != "text") && ($field_type != "integer") && ($field_type != "real") && ($field_type != "blob") && ($field_type != "calculated") && ($field_type != "virtual" ) ) {
					// Check for custom fields
					$field_type = trim($field_type,"*");
					if (!class_exists($field_type)) continue;
					if (self::is_orm($field_type)) continue;
					if (self::is_customfield($field_type)) {
						$this->__conf["fields"][$field_name] = new $field_type();
						$fields[$field_name] = $this->__conf["fields"][$field_name]->field_type();
					}
				}
			}
		*/
	}
	
	function build_relationship()
	{
		$fields = get_class_vars( $this->__table_name );
		$ar_relates = Array();
		if (isset(orm::$__relationship[$this->__table_name])) $ar_relates = orm::$__relationship[$this->__table_name];

		foreach ($ar_relates as $relation_name=>$relation_type)
		{
			// If have many or have at least one record, then auto create a callback field
			// Uses default name - Later we need to create something to allow custom field names based
			// on these lookups
			if ( ($relation_type == HAVE_MANY) || ($relation_type == HAVE_ONE) )
			{
				$name = $relation_name;
				if (!isset($fields[$name])) $fields[$name] = $relation_name;
			}
		}
		
		if (is_array($fields))
		{
			foreach ($fields as $field_name=>$field_type)
			{
				if (strpos($field_name,'__') !== FALSE) continue;
				if ( ($field_type != "text") && ($field_type != "integer") && ($field_type != "real") && ($field_type != "blob") && ($field_type != "calculated") && ($field_type != "virtual" ) )
				{
					$field_type = trim($field_type,"*");
					
					if (!class_exists($field_type)) continue;
					if (!orm::is_orm($field_type)) continue;
					$relationship = orm::calculate_relationship( $this->__table_name, $field_type );
					
					//echo 'Relation ' . $this->__table_name . ' x ' . $field_type . ' : ' . orm::relation_to_text($relationship) . "\r\n";
					
					if ($relationship == ONE_ZERO)
					{
						if (!isset($this->{$field_type . "_ROWID"})) $this->{$field_type . "_ROWID"} = "integer";
					}
					else if ( ($relationship == MANY_ONE) ||
							  ($relationship == ONE_MANY) ||
							  ($relationship == MANY_MANY) ||
							  ($relationship == MANY_ZERO) ||
							  ($relationship == ZERO_MANY) ||
							  ($relationship == ONE_ONE) )
					{
						$relation_a = $this->__table_name;
						$relation_b = $field_type;
						$relations_names = Array($relation_a,$relation_b);
						sort($relations_names);
						$relation_name = $relations_names[0] . "_and_" . $relations_names[1] . "_relations";
						
						if (!$this->cached_table_exists($relation_name))
						{
							$this->execute_query( $this->__adapter->create_table($relation_name,Array(
										$relations_names[0] . "_ROWID" => "integer",
										$relations_names[1] . "_ROWID" => "integer" 
									) ) );
							 self::$__existent_tables[$relation_name] = 1;
						}
					}
					$this->__conf["fields"][$field_name] = $field_type;
					
					unset($this->{$field_name});
				}
			}
		}
	}
	
	function execute_query($query)
	{
		core::benchmark_start();
		$result = $this->__adapter->execute($query);
		log::output_in_development("database.log",$query . " : in " . core::benchmark_end());
		return $result;
	}
	
	function execute_and_get_one($query)
	{
		core::benchmark_start();
		$result = $this->__adapter->execute_and_get_one($query);
		log::output_in_development("database.log",$query . " : in " . core::benchmark_end());
		return $result;
	}
	
	function execute_and_get_all($query)
	{
		core::benchmark_start();
		$result = $this->__adapter->execute_and_get_all($query);
		log::output_in_development("database.log",$query . " : in " . core::benchmark_end());
		return $result;
	}
	
	function get_all($result)
	{
		return $this->__adapter->get_all($result);
	}
	
	function get_one($result)
	{
		return $this->__adapter->get_one($result);
	}
	
	function cached_table_exists($table_name)
	{
		if (isset(self::$__existent_tables[$table_name])) return self::$__existent_tables[$table_name];

		$row = $this->__adapter->get_one( $this->execute_query( $this->__adapter->table_exists($table_name) ) );

		if (is_array($row)) {
			if ( in_array($table_name,$row) ) {
				self::$__existent_tables[$table_name] = 1;
				return 1;
			}
			else {
				return 0;
			}
		}
		
		return 0;
	}
	
	static function execute_query_static($query) {
		if (self::$__database_configuration == NULL) self::$__database_configuration = yaml::load(core::base_directory() . '/config/datastore.yml');
		$adapted_object = 'database_adapter_' . self::$__database_configuration[core::get_environment()]['adapter'];
		require_once( core::root_directory() . '/core/database_adapters/' . self::$__database_configuration[core::get_environment()]['adapter'] . ".php");
		$__adapter = new $adapted_object('', '', self::$__database_configuration[core::get_environment()]);
		core::benchmark_start();
		$result = $__adapter->execute($query);
		log::output_in_development("database.log",'static: ' . $query . " : in " . core::benchmark_end());
		return $result;
	}
	
	function orm( $values = Array() ) {
		$this->__rules = Array();
		$this->__conf["obj"] = Array();
		$this->__conf["fields"] = Array();

		$this->__table_name = get_class($this);

		if (self::$__database_configuration == NULL) self::$__database_configuration = yaml::load(core::base_directory() . '/config/datastore.yml');
		$adapted_object = 'database_adapter_' . self::$__database_configuration[core::get_environment()]['adapter'];
		require_once( core::root_directory() . '/core/database_adapters/' . self::$__database_configuration[core::get_environment()]['adapter'] . ".php");
		$this->__adapter = new $adapted_object($this->__table_name, $this, self::$__database_configuration[core::get_environment()]);

		self::build_relationship();

		if (!$this->cached_table_exists($this->__table_name)) {
			$resource_name = get_class($this);
			$fields = get_object_vars($this);

			foreach ($fields as $field_name=>$field_type) {
				if (strpos($field_name,'__') !== FALSE) continue;
				if ( ($field_type != "text") && ($field_type != "integer") && ($field_type != "real") && ($field_type != "blob") && ($field_type != "calculated")  ) {
					// Check for custom fields
					$field_type = trim($field_type,"*");
					if (!class_exists($field_type)) continue;
					if (self::is_orm($field_type)) continue;
					if (self::is_customfield($field_type)) {
						$this->__conf["fields"][$field_name] = new $field_type();
						$fields[$field_name] = $this->__conf["fields"][$field_name]->field_type();
						$this->__conf["fields"][$field_name]->set_parent($this);
						$this->$field_name = &$this->__conf["fields"][$field_name];
					}
				}
			}

			$this->execute_query( $this->__adapter->create_table($this->__table_name,$fields) );
			self::$__existent_tables[$this->__table_name] = 1;

		}
		else {
			// Check for Migrations => MIGRATE TABLE
			// echo "TABLE EXISTS";
			$table_columns = $this->get_columns($this->__table_name);
			$table_object_fields = Array();

			$fields = get_object_vars($this);
			foreach ($fields as $field_name=>$field_type) {
				if (strpos($field_name,'__') !== FALSE) continue;
				if ( ($field_type != "text") && ($field_type != "integer") && ($field_type != "real") && ($field_type != "blob") && ($field_type != "calculated")  ) {
					// Check for custom fields
					$field_type = trim($field_type,"*");
					if (!class_exists($field_type)) continue;
					if (self::is_orm($field_type)) continue;
					if (self::is_customfield($field_type)) {
						$this->__conf["fields"][$field_name] = new $field_type();
						
						
						//$custom_field = new $field_type();
						//$table_object_fields[$field_name] = $custom_field->field_type();
						if ($this->__conf["fields"][$field_name]->field_type() != 'virtual') {
							$table_object_fields[$field_name] = $this->__conf["fields"][$field_name]->field_type();
						}
						
						$this->__conf["fields"][$field_name]->set_parent($this);
						$this->$field_name = &$this->__conf["fields"][$field_name];
					}
				}
				else if (($field_type != 'calculated') && ($field_type != 'virtual'))
				{
					$table_object_fields[$field_name] = $field_type;
				}
			}
			
			if (count($table_columns) != count($table_object_fields)) {
				$this->__adapter->change_structure($this->__table_name,$table_columns,$table_object_fields);
			}
			//unset(self::$__existent_tables[$this->__table_name]);
		}

		$this->__errors = new message();

		self::configure_default_values();

		if (!$this->before_set_values()) return false;

		if (count($values) > 0) {
			foreach ($values as $field_name=>$field_value) {
				if (isset($this->$field_name)) {
					if (isset($this->__conf["fields"][$field_name])) {
						$this->$field_name->set($field_value);
					}
					else {
						$this->$field_name = $field_value;
					}
				}
			}
			//$this->save();
		}

		return $this;
	}

	
	function validates_format_of($field,$format,$message="")
	{
		if ($message == "") $message = _("is missing or invalid");
		
		$fields = Array();
		if (is_array($field)) $fields=$field;
		else $fields = Array($field);
		unset($field);
		
		$return_status = true;
		$field_value = $this->$field;
		foreach ($fields as $field)
		{
			if (preg_match("/" . $format . "/i",$field_value)) {
				$return_status = false;
			} else
			{
				self::add_error($field,$message );
			}
		}
	}

	function validates_presence_of($field,$message="")
	{
		$fields = Array();
		if (is_array($field)) $fields=$field;
		else $fields = Array($field);
		unset($field);
		if ($message == "") $message = _("cannot be blank");
		
		$return_status = true;
		foreach ($fields as $field) {
			if ($this->$field == "") {
				self::add_error($field,$message );
				$return_status = false;
			}
		}
		
		return $return_status;
	}

	function validates_uniqueness_of($field,$message="") {
		if ($message == "") $message = _("has already been taken");
		
		$fields = Array();
		if (is_array($field)) $fields=$field;
		else $fields = Array($field);
		unset($field);
		$return_status = true;
		
		foreach ($fields as $field) {
			$value = $this->$field;
			$class_name = get_class($this);
			$new_obj = new $class_name;
	
			//TODO
			if (count($new_obj->find( $this->__adapter->format_name($field) . '=' . $this->__adapter->format_name($value) . ' and ' . $this->__adapter->format_name('ROWID') . '!="' . $this->ROWID . '"')))
			{
				self::add_error($field,$message );
			}
			else
			{
				$return_status = false;
			}
		}
		
		return $return_status;
	}

	function validates_textsize_bigger_than($field,$value=0,$message="") {
		if ($message == "") $message = _("is too short");
		
		$fields = Array();
		if (is_array($field)) $fields=$field;
		else $fields = Array($field);
		unset($field);
		$return_status = true;
		
		foreach ($fields as $field) {
			if (strlen($this->$field) >= $value) {
				$return_status = false;
			} else
			{
				self::add_error($field, sprintf($message,$value) );
			}
		}
		
		return $return_status;
	}

	function validates_textsize_lower_than($field,$value=0,$message="") {
		if ($message == "") $message = _("is too long");
		
		$fields = Array();
		if (is_array($field)) $fields=$field;
		else $fields = Array($field);
		unset($field);
		$return_status = true;
		
		foreach ($fields as $field) {
			if (strlen($this->$field) <= $value) {
				$return_status = false;
			} else
			{
				self::add_error($field, sprintf($message,$value) );
			}
		}
		
		return $return_status;
	}

	function validates_textsize_of($field,$value=0,$message="") {
		if ($message == "") $message = _("must have %d character(s)");
		
		$fields = Array();
		if (is_array($field)) $fields=$field;
		else $fields = Array($field);
		unset($field);
		$return_status = true;
		
		foreach ($fields as $field) {
			if (strlen($this->$field) == $value) {
				$return_status = false;
			} else
			{
				self::add_error($field, sprintf($message,$value) );
			}
		}
		
		return $return_status;
	}

	function validates_acceptance_of($field,$message="",$field_name="")
	{
		if ($message == "") $message = _("must be accepted");
		
		$fields = Array();
		if (is_array($field)) $fields=$field;
		else $fields = Array($field);
		unset($field);
		$return_status = true;
		
		foreach ($fields as $field) {
			if (strlen($this->$field) == 1) {
				$return_status = false;
			} else
			{
				if ($field_name != '') $field = $field_name;
				self::add_error($field, $message );
			}
		}
		
		return $return_status;
	}

	function validates_exclusion_of($field,$format,$message="")
	{
		if ($message == "") $message = _("cannot be used");
		
		$fields = Array();
		if (is_array($field)) $fields=$field;
		else $fields = Array($field);
		unset($field);
		$return_status = true;
		
		foreach ($fields as $field) {
			$field_value = $this->$field;
			if (preg_match("/" . $format . "/i",$field_value)) {
				self::add_error($field,$message );
			} else
			{
				$return_status = false;
			}
		}
		
		return $return_status;
	}

	function validates_inclusion_of($field,$format,$message="")
	{
		if ($message == "") $message = _("cannot be value");
		
		$fields = Array();
		if (is_array($field)) $fields=$field;
		else $fields = Array($field);
		unset($field);
		$return_status = true;
		
		foreach ($fields as $field) {
			$field_value = $this->$field;
			if (!preg_match("/" . $format . "/i",$field_value)) {
				self::add_error($field,$message );
			} else
			{
				$return_status = false;
			}
		}
		
		return $return_status;
	}

	function validates_numericality_of($field,$only_integer=false,$message="") {
		if ($message == "") $message = _("is invalid");
		$function = "is_numeric";
		
		$fields = Array();
		if (is_array($field)) $fields=$field;
		else $fields = Array($field);
		unset($field);
		$return_status = true;
		
		foreach ($fields as $field) {
			if ($only_integer) $function = "is_int";
			if ($function($this->$field)) {
				$return_status = false;
			} else
			{
				self::add_error($field,$message );
			}
		}
		
		return $return_status;
	}

	function validates_confirmation_of($field,$confirmation_field,$message="")
	{
		if ($message == "") $message = _("doesn't match confirmation");
		
		$fields = Array();
		if (is_array($field)) $fields=$field;
		else $fields = Array($field);
		unset($field);
		$return_status = true;

		foreach ($fields as $field) {
			if (strcmp($this->$field,$this->$confirmation_field)==0) {
				$return_status = false;
			} else
			{
				self::add_error($field,$message );
			}
		}
		
		return $return_status;
	}

	function validate()
	{
		$fields = get_object_vars($this);

		foreach ($fields as $field_name=>$field_value)
		{
			if (strpos($field_name,'__') !== FALSE) continue;
			//oooooooooooooooooooooooooooooooooo
			//	Field values are threated here
			//oooooooooooooooooooooooooooooooooo
			if (isset($this->__conf["fields"][$field_name]))
			{
				if (orm::is_customfield( $this->__conf["fields"][$field_name] ))
				{
					if (method_exists($this->__conf["fields"][$field_name],'validate')) {
						$validation = $this->__conf["fields"][$field_name]->validate($field_value);
						if ($validation !== true)
						{
							$this->add_error($field_name,$validation);
						}
					}
				}
			}
		}
	}
	
	function cancel_transaction()
	{
		$fields = get_object_vars($this);
		foreach ($fields as $field_name=>$field_value)
		{
			if (strpos($field_name,'__') !== FALSE) continue;
			if (isset($this->__conf["fields"][$field_name]))
			{
				if (orm::is_customfield( $this->__conf["fields"][$field_name] )) if (method_exists($this->__conf["fields"][$field_name],'cancel_set')) $this->__conf["fields"][$field_name]->cancel_set();
			}
		}
	}
	
	function complete_transaction()
	{
		$fields = get_object_vars($this);
		foreach ($fields as $field_name=>$field_value)
		{
			if (strpos($field_name,'__') !== FALSE) continue;
			if (isset($this->__conf["fields"][$field_name]))
			{
				if (orm::is_customfield( $this->__conf["fields"][$field_name] )) if (method_exists($this->__conf["fields"][$field_name],'complete_set')) $this->__conf["fields"][$field_name]->complete_set();
			}
		}
	}
	
	function custom_field()
	{
		$fields = get_object_vars($this);

		foreach ($fields as $field_name=>$field_value)
		{
			if (strpos($field_name,'__') !== FALSE) continue;
			//oooooooooooooooooooooooooooooooooo
			//	Field values are threated here
			//oooooooooooooooooooooooooooooooooo
			if (isset($this->__conf["fields"][$field_name]))
			{
				if (orm::is_customfield( $this->__conf["fields"][$field_name] ))
				{
					if (method_exists($this->__conf["fields"][$field_name],'validate')) {
						$validation = $this->__conf["fields"][$field_name]->validate($field_value);
						if ($validation !== true)
						{
							$this->add_error($field_name,$validation);
						}
					}
				}
			}
		}
	}
	
	function errors()
	{
		return $this->__errors->all_messages();
	}
	
	function get_errors()
	{
		$errors = Array();
		
		if ($this->errors()) foreach ($this->errors() as $error_catg=>$error_type) foreach ($error_type as $error_name=>$error_num) array_push($errors,Array("msg"=>_(strtolower(inflector::titleize($error_catg))) . ' ' . $error_num ));
		return $errors;
	}

	function errors_for($to="default")
	{
		return $this->__errors->messages_for($to);
	}

	function add_error($field,$message)
	{
		return $this->__errors->add_message($message,$field);
	}

	function clear_errors()
	{
		return $this->__errors->clear();
	}
	
	function configure_default_values()
	{
		$fields = get_object_vars($this);
		foreach ($fields as $field_name=>$field_type)
		{
			if (strpos($field_name,'__') !== FALSE) continue;
			if ($field_type == "text") $this->$field_name = '';
			else if ($field_type == "integer") $this->$field_name = '';
			else if ($field_type == "real") $this->$field_name = '';
			else if ($field_type == "blob") $this->$field_name = '';
			else if ($field_type == "virtual") $this->$field_name = '';
			else if ($field_type == "calculated") $this->$field_name = '';
			else
			{
				if (is_object($field_type)) continue;
				$field_type = trim($field_type,"*");
				if (!class_exists($field_type)) continue;
				if (orm::is_orm($field_type)) continue;
			}
			if ($field_name == "created_at") $this->$field_name = core::time();
			if ($field_name == "updated_at") $this->$field_name = core::time();
		}
	}
	
	function update_values($values)
	{
		foreach ($values as $key=>$value) if (isset($this->$key)) $this->$key = $value;
	}
	
	function update_fields($values)
	{
		foreach ($values as $key=>$value) if (isset($this->$key)) $this->$key = $value;
	}
	
	function update_attributes($values)
	{
		foreach ($values as $key=>$value) {
			if (isset($this->$key)) {
				if (is_object($this->$key)) {
					if (self::is_customfield($this->$key)) {
						$this->$key->set($value);
					}
				} else {
					$this->$key = $value;
				}
			}
		}
	}
	
	function __get($m)
	{
		if (isset($this->__conf["fields"][$m]))
		{
			$relation_name = $this->__conf["fields"][$m];
			if (array_key_exists($m,$this->__conf["obj"]))
			{
				if (isset($this->ROWID))
				{
					$this->__conf["obj"][$m]->reset_rules();
					$this->__conf["obj"][$m]->add_rule( $this->__table_name . "_ROWID=".$this->ROWID);
				}
				return $this->__conf["obj"][$m];
			}

			$this->__conf["obj"][$m] = new $relation_name();
			$object = &$this->__conf["obj"][$m];

			if (isset($this->ROWID))
			{
				$object->reset_rules();
				$object->add_rule( $this->__table_name . "_ROWID=".$this->ROWID);
				$object->find(ONE);
			}

			return $object;
		}
		return false;
	}
	
	function add_rule($rule)
	{
		array_push($this->__rules,$rule);
		return true;
	}
	
	function reset_rules()
	{
		unset($this->__rules);
		$this->__rules = Array();
	}
	
	function set_rules($arr)
	{
		unset($this->__rules);
		$this->__rules=$arr;
	}
	
	function copy_rules()
	{
		return $this->__rules;
	}
	
	function dump_rules()
	{
		print_r($this->__rules);
	}
	
	function count($field_name="ROWID",$options=Array())
	{
		$options = $this->query_builder('ALL',$options);
		if ($field_name == 'ONE') $field_name = 'ROWID';
		if ($field_name == 'ALL') $field_name = 'ROWID';
		if ($field_name == 'RANDOM') $field_name = 'ROWID';
		$options["select"] = 'COUNT(*) as total ';
		
		$sql = $this->__adapter->query_builder($this->__table_name, $options);
		$result = $this->execute_query( $sql );
		if (!$result) return 0;

		

		if ($array = $this->__adapter->get_one($result) ) {
			return isset($array['total'])?$array['total']:0;
		}
	}
	
	function min($id="*",$options=Array())
	{
	
	}
	
	function max($id="*",$options=Array())
	{
	
	}
	
	function sum($id="*",$options=Array())
	{
	
	}
	
	function average($id="*",$options=Array())
	{
	
	}
	
	function find_by_sql($sql)
	{
	
	}
	
	function create()
	{
		if (isset($this->ROWID)) unset($this->ROWID);
		if (isset($this->created_at)) $this->created_at = core::time();
		if (isset($this->updated_at)) $this->updated_at = core::time();
		$go_and_save = false;
		foreach ($this->__rules as $rule)
		{
			if (strpos($rule,'=')!==FALSE) {
				list($rule_name,$rule_value) = explode('=',$rule);
				if (isset($this->$rule_name))
				{
					$this->$rule_name = $rule_value;
					$go_and_save = true;
				}
			}
		}
		if ($go_and_save) $this->save();
		return true;
	}
	
	function get_columns($table_name)
	{
		if (isset(self::$__table_fields[$table_name])) return self::$__table_fields[$table_name];
		$results = $this->execute_and_get_all( $this->__adapter->columns($table_name) );
		//$results = $this->__adapter->get_all($result);
		if (method_exists($this->__adapter,'columns_after_query')) $results = $this->__adapter->columns_after_query($results);
		if (count($results)) {
			unset($results['ROWID']);
			self::$__table_fields[$table_name] = $results;
		}
		return $results;
	}
	
	function get_fields()
	{
		$fields = get_class_vars(get_class($this));
		$record_fields = array_intersect_key($fields,array_flip(array_filter( array_keys($fields), create_function('&$x','return strpos($x,\'__\') === FALSE;') ) ));
		$record_fields = array_intersect_key($record_fields,array_filter( $record_fields, create_function('&$x','return (($x == "text") || ($x == "integer") || ($x == "real") || ($x == "blob") );')) );
		return $record_fields;
	}
	
	function remove_invalid_fields($fields)
	{
		return array_intersect_key($fields,$this->get_columns($this->__table_name));
	}
	
	function save()
	{
		if (!$this->before_validation()) {
			$this->cancel_transaction();
			return false;
		}
		if (!isset($this->ROWID))
		{
			if (!$this->before_validation_on_create()) {
				$this->cancel_transaction();
				return false;
			}
		}
		else
		{
			if (!$this->before_validation_on_update()){
				$this->cancel_transaction();
				return false;
			}
		}
		$this->clear_errors();
		$this->validate();
		if (count($this->errors()) != 0) {
			$this->cancel_transaction();
			return false;
		}
		if (!$this->after_validation()) {
			$this->cancel_transaction();
			return false;
		}
		if (!isset($this->ROWID)) {
			if (!$this->after_validation_on_create()) {
				$this->cancel_transaction();
				return false;
			}
		}
		else {
			if (!$this->after_validation_on_update()) {
				$this->cancel_transaction();
				return false;
			}
		}
		if (isset($this->updated_at)) $this->updated_at = core::time();
		if (!$this->before_save()) {
			$this->cancel_transaction();
			return false;
		}
		if (!isset($this->ROWID)) {
			if (!$this->before_create()) {
				$this->cancel_transaction();
				return false;
			}
		}
		else {
			if (!$this->before_update()) {
				$this->cancel_transaction();
				return false;
			}
		}
		
		foreach ($this->__rules as $rule)
		{
			$exf = explode("=",$rule);
			if (isset($this->$exf[0])) {
				$this->$exf[0] = $exf[1];
			}
		}

		foreach ($this->__conf["obj"] as $object_name=>$object) {
			$object_with_rules = $this->__get($object_name);
			if (!isset($object->ROWID))
			{
				//echo $object_name;
				//print_r($object);
				//$object_with_rules->save();
			}
		}
		
		$original_fields = get_object_vars($this);
		
		$table_fields = $this->get_columns($this->__table_name);
		
		$fields = Array();
		
		
		foreach ($original_fields as $field_name=>$field_type) {
			if (strpos($field_name,'__') !== FALSE) continue;
			if (self::is_customfield($field_type)) {
				if (method_exists($field_type,'get_data')) {
					$fields[$field_name] = $field_type->get_data();
				}
			}
			else
			{
				$fields[$field_name] = $field_type;
			}
		}
		
		$record_fields = array_intersect_key($fields,array_flip(array_filter( array_keys($fields), create_function('$x','if (orm::is_customfield($x)) return true; return strpos($x,\'__\') === FALSE;') ) ));
		$record_fields = array_intersect_key($record_fields,$table_fields);
		
//		print_r($record_fields);
//		print_r($table_fields);
		
		foreach($record_fields as $key=>$value)
		{
			//column_value
			$record_fields[$key] = $this->__adapter->column_value($table_fields[$key],$value);
		}
		
		if (isset($this->ROWID))
		{
			if ($this->ROWID == '') unset($this->ROWID);
			else $record_fields["ROWID"] = $this->ROWID;
		}
		
		
		$created_now = false;
		if (!isset($this->ROWID)) {
			// CREATE ACTION
			$created_now = true;
			$this->execute_query( $this->__adapter->insert($this->__table_name, $record_fields) );
			$this->ROWID = $this->__adapter->last_row_inserted();
		}
		else {
			// UPDATE ACTION
			$this->execute_query( $this->__adapter->update($this->__table_name, $record_fields, $this->__adapter->format_name($this->__table_name) . '.' . $this->__adapter->format_name('ROWID') . '="' . $this->ROWID . '"' ) );
		}
		
		$record_fields["ROWID"] = $this->ROWID;
		
		foreach ($this->__conf["obj"] as $object_name=>$object)
		{
			$object_with_rules = $this->__get($object_name);
			$find_rules = $object_with_rules->gather_rules();
			foreach ($find_rules as $relates)
			{
				//print_r($relates);
				if ($relates[0] == $this->__table_name)
				{
					$this->$relates[2] = $object_with_rules->ROWID;
					$record_fields[$relates[2]]=$object_with_rules->ROWID;
					$update_query = $this->__adapter->update($this->__table_name, $record_fields, $this->__adapter->format_name($this->__table_name) . '.' . $this->__adapter->format_name('ROWID') . '="' . $this->ROWID . '"' );
					$this->execute_query( $update_query );
				}
				else if (strpos($relates[0],"_and_") !== FALSE)
				{
					$record_fieldsR = Array();
					$exf = explode("=",$relates[1]);
					$record_fieldsR[$exf[0]] = $exf[1];
					$record_fieldsR[$object_with_rules->__table_name . "_ROWID"] = $object_with_rules->ROWID;
					$this->execute_query( $this->__adapter->delete($relates[0], $record_fieldsR) );
					$this->execute_query( $this->__adapter->insert($relates[0], $record_fieldsR) );
				}
			}
		}
		
		if ($created_now)
		{
			if (!$this->after_create()) {
				$this->cancel_transaction();
				return false;
			}
		}
		else
		{
			if (!$this->after_update()) {
				$this->cancel_transaction();
				return false;
			}
		}
		if (!$this->after_save()) {
			$this->cancel_transaction();
			return false;
		}
		
		$this->complete_transaction();

		return true;
	}
	
	function gather_rules()
	{
		$find_rules = Array();
		$relation_a = get_class($this);
		if (isset($this->__rules))
		{
			foreach ($this->__rules as $rule)
			{
				if (strpos($rule,"=") !== FALSE)
				{
					$rule_expanded = explode("=",$rule);
					if (isset($this->$rule_expanded[0]))
					{
						array_push($find_rules, Array($relation_a, $rule) );
					}
					else
					{
						$relation_b = str_replace("_ROWID","",$rule_expanded[0]);
						$relationship = orm::calculate_relationship($relation_a,$relation_b);
						if ( ($relationship == ZERO_ONE) )
						{
							$modified_rule = substr($rule,strpos($rule,"_ROWID")+1);
							array_push($find_rules, Array($relation_b, $modified_rule, $relation_a . "_ROWID" ) );
						}
						else if ( ($relationship == ONE_ZERO) )
						{
							array_push($find_rules, Array($relation_a, $rule , $relation_b . "_ROWID") );
						}						
						else if ( ($relationship == MANY_ONE) ||
								  ($relationship == ONE_MANY) ||
								  ($relationship == MANY_MANY) ||
								  ($relationship == MANY_ZERO) ||
								  ($relationship == ZERO_MANY) ||
								  ($relationship == ONE_ONE) )
						{
							$relations_names = Array($relation_a,$relation_b);
							sort($relations_names);
							$relation_all = $relations_names[0] . "_and_" . $relations_names[1] . "_relations";
							array_push($find_rules, Array($relation_all, $rule) );
						}
					}
				}
				else
				{
					array_push($find_rules, Array($relation_a, $rule) );
				}
			}
		}
		return $find_rules;
	}
	
	function get_related_tables()
	{
		$relation_a = get_class($this);
		$find_rules = $this->gather_rules(Array());
		
		$tables = Array();
		if (isset($find_rules))
		{
			foreach($find_rules as $rule)
			{
				if ($rule[0] != $this->__table_name)
				{
					$tables[] = $rule[0];
				}
			}
		}
		return $tables;
	}
	
	function query_builder($id,$options=Array())
	{
		$relation_a = get_class($this);
		$find_rules = $this->gather_rules(Array());

		$table_fields = $this->get_columns($this->__table_name);

		if (!isset($options["conditions"])) $options["conditions"] = '';

		if ($id == 'ONE') {
			// Call with LIMIT = 1
			$options['limit'] = 1;
			$options['offset'] = 0;
		}
		else if ($id == 'ALL') {
			$id = '';
		}
		else if (!is_numeric($id) ) {
			if ($options["conditions"] != '') $options["conditions"] .= " AND ";
			$options["conditions"] .= $id;
		} else {
			// Its a CONDITION
			if ($options["conditions"] != '') $options["conditions"] .= " AND ";
			$options["conditions"] .= $this->__adapter->format_name($this->__table_name) . '.' . $this->__adapter->format_name('ROWID') . '="' . $id . '"';
		}

		if (isset($find_rules))
		{
			foreach($find_rules as $rule)
			{
				if ($rule[0] != $this->__table_name)
				{
					// Join Tables
					$exf = explode("=",$rule[1]);
					//$options["select" = query_select
					$options["from"] = $rule[0];
					$options["joins"] = 'LEFT JOIN ' . $this->__adapter->format_name($this->__table_name) .' on (' . $this->__adapter->format_name($this->__table_name) . '.' . $this->__adapter->format_name('ROWID') . ' = ' . $this->__adapter->format_name($rule[0]) . '.' . $this->__adapter->format_name($this->__table_name . '_ROWID')  . ')';
					if ($options["conditions"] != '') $options["conditions"] .= " AND ";
					$options["conditions"] .= $this->__adapter->format_name($rule[0]) . '.' .  $this->__adapter->format_name($exf[0]) . '="' . $exf[1] . '"';
					//$base_select = " from " . $rule[0] . " as B left join " . $this->__table_name . " as A on ( A.ROWID = B." . inflector::singularize($this->__table_name) . "_ROWID and B." . $rule[1] . " ) ";
				}
				else
				{
					if ($options["conditions"] != '') $options["conditions"] .= " AND ";
					$options["conditions"] .= $rule[1];
				}
			}
		}
		return $options;
	}
	
	static function create_and_find($object_type,$id,$options=Array()) {
		$new_object = new $object_type;
		$new_object->find($id,$options);
		return $new_object;
	}
	
	static function search_one($object_type,$id,$options=Array())
	{
		$object = orm::create_and_find($object_type,$id,array_merge(Array("limit"=>1),$options));
		if (!$object->is_new()) return $object;
		return null;
	}
	
	static function search($object_type,$id,$options=Array())
	{
		$new_object = new $object_type;
		$return = $new_object->find($id,$options);
		return $return;
	}
	
	static function search_but_count($object_type,$id,$options=Array())
	{
		$new_object = new $object_type;
		return $new_object->count($id,$options);
	}
	
	static function paginate($object_type,$id,$options)
	{
		$count = orm::search_but_count($object_type,ALL,$options);
		$current_page = isset($options['current_page'])?$options['current_page']:1;
		$results_per_page = isset($options['per_page'])?$options['per_page']:11;
		$href = isset($options['href'])?$options['href']:1;

		$resultados = orm::search($object_type,ALL, array_merge(Array( 
						'offset'=>($current_page-1)*$results_per_page,
						'limit'=>$results_per_page
					), $options )
				);

		$pages = web::paginate($current_page, $count, $href, $results_per_page);

		return Array($resultados,$pages,$count);
	}
	
	static function find_all($object_type,$id,$options=Array()) {
		$new_object = new $object_type;
		$return = $new_object->find($id,$options);
		return $return;
	}
	
	/*
	function find($id,$options=Array())
	{
		$options = $this->query_builder($id=='RANDOM'?'ALL':$id,$options);

		if ((!is_numeric($id)) && ($id != 'ONE') && (strpos($id,'=')===FALSE) )
		{
			$total = $this->count($id=='RANDOM'?'ROWID':$id,$options);
			if ($total != 0)
			{
				if ($id == 'RANDOM')
				{
					$options['offset'] = rand()%$total;
					$options['limit'] = 1;
				}
			}
		}

		$result = $this->execute_and_get_all( $this->__adapter->query_builder($this->__table_name, $options) );
		$results = Array();
		if (!$result) return $results;

		$class_name = get_class($this);

		$executed_time = core::benchmark_start();

		$blank_object = new $class_name;
		$blank_object->set_rules($this->copy_rules());
		$blank_object_serialized = serialize($blank_object);

		foreach ($result as $array)
		{
			$new_obj = unserialize($blank_object_serialized);

			foreach ($array as $key=>$value)
			{
				$newkey = str_replace($this->__table_name . ".","",$key);
				if (($key == "ROWID") && ($value == "")) continue;

				if (orm::is_customfield($this->$newkey)) {
					$this->$newkey->set( stripslashes( $value ) );
				}
				else {
					$this->$newkey = stripslashes( $value );
				}

				if (orm::is_customfield($new_obj->$newkey)) {
					$new_obj->$newkey->set( stripslashes( $value ) );
				}
				else {
					$new_obj->$newkey = stripslashes( $value );
				}
			}

			if (!$new_obj->after_find()) continue;
			array_push( $results, $new_obj );
		}

		if (count($results)==1)
		{
			if ($results[0]->ROWID == '') return Array();
		}

		if (!count($results)) unset($this->ROWID);
		return $results;
	}*/
	
	function find($id,$options=Array())
	{
		$options = $this->query_builder($id=='RANDOM'?'ALL':$id,$options);

		if ($id == 'RANDOM') {
			if ((!is_numeric($id)) && ($id != 'ONE') && (strpos($id,'=')===FALSE) )
			{
				$total = $this->count($id=='RANDOM'?'ROWID':$id,$options);
				if ($total != 0)
				{
					if ($id == 'RANDOM')
					{
						$options['offset'] = rand()%$total;
						$options['limit'] = 1;
					}
				}
			}
		}

		$result = $this->execute_and_get_all( $this->__adapter->query_builder($this->__table_name, $options) );
		$results = Array();
		if (!count($result)) return $results;

		$class_name = get_class($this);

		$executed_time = core::benchmark_start();

		$blank_object = new $class_name;
		$blank_object->set_rules($this->copy_rules());

		$blank_object_serialized = serialize($blank_object);
		$blank_object = unserialize($blank_object_serialized);

		$fields = get_class_vars( $blank_object );
		$reset_fields = Array();

		if (is_array($fields))
		{
			foreach ($fields as $field_name=>$field_type)
			{
				if (strpos($field_name,'__') !== FALSE) continue;
				if ( ($field_type != "text") && ($field_type != "integer") && ($field_type != "real") && ($field_type != "blob") && ($field_type != "calculated") && ($field_type != "virtual" ) )
				{
					$field_type = trim($field_type,"*");
					if (!class_exists($field_type)) continue;
					if (!orm::is_orm($field_type)) continue;
					$reset_fields[] = $field_name;
				}
			}
		}

		foreach ($result as $array)
		{
			$new_obj = unserialize($blank_object_serialized);

			// Otimizacao do unserialize, removendo objetos
			foreach ($reset_fields as $field_name) unset($new_obj->$field_name);

			foreach ($array as $key=>$value)
			{
				$newkey = str_replace($this->__table_name . ".","",$key);
				if (($key == "ROWID") && ($value == "")) continue;

				if (orm::is_customfield($this->$newkey)) {
					$this->$newkey->set( stripslashes( $value ) );
				}
				else {
					$this->$newkey = stripslashes( $value );
				}

				if (orm::is_customfield($new_obj->$newkey)) {
					$new_obj->$newkey->set( stripslashes( $value ) );
				}
				else {
					$new_obj->$newkey = stripslashes( $value );
				}
			}

			if (!$new_obj->after_find()) continue;
			array_push( $results, $new_obj );
		}

		if (count($results)==1)
		{
			if ($results[0]->ROWID == '') return Array();
		}

		if (!$this->after_find()) return false;

		if (!count($results)) unset($this->ROWID);
		return $results;
	}
	
	function push( $item )
	{
		$docid = 0;
		if (is_object($item))
		{
			foreach ($this->__rules as $rule)
			{
				if (strpos($rule,'=')!==FALSE) {
					list($rule_name,$rule_value) = explode('=',$rule);
					if (isset($item->$rule_name))
					{
						$item->$rule_name = $rule_value;
					}
				}
			}
			$item->save();
			if (!isset($item->ROWID)) return false;
			$docid = $item->ROWID;
		}
		
		if (is_numeric($item))
		{
			$docid = $item;
		}
		$old_rules = $this->copy_rules();
		$this->reset_rules();
		$this->find( $docid,
			Array(
				"limit"=>"1"
			));
		$this->copy_rules( $old_rules );
		return $this;
	}
	
	function all()
	{
		return $this->find(ALL);
	}
	
	function __call($m, $a)
	{
	
	}
	
	function create_index($table_name,$index_name,$fields)
	{
		$this->execute_query( $this->__adapter->create_index($table_name,$index_name,$fields) );
		return true;
	}

	function drop_index($index_name) {
		$this->execute_query( $this->__adapter->drop_index($index_name) );
		return true;
	}

	function indexes($table_name) {
		$this->get_all( $this->execute_query( $this->__adapter->indexes($table_name) ) );
		return true;
	}
	
	function delete($records='')
	{
		if ($records == '')
		{
			if (!$this->before_destroy()) return false;
			$this->execute_query( $this->__adapter->delete($this->__table_name, $this->__adapter->format_name($this->__table_name) . '.' . $this->__adapter->format_name('ROWID') . '=' . $this->ROWID) );
			$related_tables = $this->get_related_tables();
			foreach ($related_tables as $table_name)
			{
				$this->execute_query( $this->__adapter->delete($table_name, $this->__adapter->format_name($table_name) . '.' . $this->__adapter->format_name($this->__table_name . '_ROWID') . '=' . $this->ROWID) );
			}
			return true;
		}
		else if (is_array($records))
		{
			foreach ($records as $record)
			{
				$this->execute_query( $this->__adapter->delete($this->__table_name, $this->__adapter->format_name($this->__table_name) . '.' . $this->__adapter->format_name('ROWID') . '=' . $record) );
			}
			return true;
		}
		else if (is_numeric($records))
		{
			if (!$this->before_destroy()) return false;
			$this->execute_query( $this->__adapter->delete($this->__table_name, $this->__adapter->format_name($this->__table_name) . '.' . $this->__adapter->format_name('ROWID') . '=' . $records) );
			return true;
		}
		else
		{
			if (!$this->before_destroy()) return false;
			$this->execute_query( $this->__adapter->delete($this->__table_name,$records) );
			return true;
		}
		return false;
	}

	function __toString()
	{
		return inflector::humanize(get_class($this));
	}
	
	function is_new()
	{
		return !isset($this->ROWID);
	}
	
	function to_param()
	{
		return isset($this->ROWID)?$this->ROWID:0;
	}
	
	function format_name($name)
	{
		return $this->__adapter->format_name($name);
	}
	
	function column_value($field_name,$field_value)
	{
		return $this->__adapter->column_value($field_name,$field_value);
	}
	
	static function relation_to_text($mode) {
		if ($mode == 0) return 'ZERO TO ZERO';
		else if ($mode == 1) return 'ZERO TO ONE';
		else if ($mode == 2) return 'ZERO TO MANY';
		else if ($mode == 3) return 'ONE TO ZERO';
		else if ($mode == 4) return 'ONE TO ONE';
		else if ($mode == 5) return 'ONE TO MANY';
		else if ($mode == 6) return 'MANY TO ZERO';
		else if ($mode == 7) return 'MANY TO ONE';
		else if ($mode == 8) return 'MANY TO MANY';
	}

	function before_set_values() {return true;}
	function before_validation() {return true;}
	function before_validation_on_create() {return true;}
	function before_validation_on_update() {return true;}
	function after_validation() {return true;}
	function after_validation_on_create() {return true;}
	function after_validation_on_update() {return true;}
	function before_save() {return true;}
	function before_create() {return true;}
	function after_create() {return true;}
	function before_update() {return true;}
	function after_update() {return true;}
	function after_save() {return true;}
	function before_destroy() {return true;}
	function after_destroy() {return true;}
	function after_find() { return true; }
}

?>