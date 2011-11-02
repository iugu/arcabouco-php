<?

/*
 * This file is part of the Arcabouco Framework.
 * (c) 2008 Patrick Negri <patrick@agencialobo.com.br>
 * (c) 2008 Paulo Lobo <plobo@agencialobo.com.br>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class database_adapter_mysql
{
	protected static $__open_handle = NULL;
	static $__field_types = Array('TEXT','BIGINT','REAL','blob');
	static $__map = Array(
		Array('text',		'TEXT',			true),
		Array('integer',	'BIGINT',		false),
		Array('real',		'REAL',			false),
		Array('blob',		'BLOB',			false)
	);
	
	function database_adapter_mysql( $resource_name, $resource_ptr, $resource_info = Array() ) {
		if (self::$__open_handle == NULL)
		{
			self::$__open_handle = mysql_connect($resource_info['host'],$resource_info['username'],$resource_info['password']);
			$db_selected = mysql_select_db($resource_info['database'], self::$__open_handle);
		}
		$this->__resource_name = $resource_name; $this->__object = $resource_ptr;
	}
	
	function field_to_column($type) {
		foreach(self::$__map as $typeinfo) if ($type == $typeinfo[0]) return $typeinfo[1];
	}
	
	function column_to_field($type) {
		$type = preg_replace('/\(.+?\)/','',$type);
		foreach(self::$__map as $typeinfo) {
			if (strtolower(trim($type,'"')) == strtolower(trim($typeinfo[1],'"'))) return $typeinfo[0];
		}
	}
	
	function column_value($type,$value) {
		foreach(self::$__map as $typeinfo) {
			if ($type == $typeinfo[0])
			{ 
				if ($typeinfo[2]) return '"' . mysql_real_escape_string($value) . '"';
			}
			else
			{
				if ($value == '') $value=0;
			}
			return $value;
		}
		return $value;
	}
	
	function format_name($name) {
		return $name;
	}
	
	function table_exists($table_name) {
		return "SHOW TABLES LIKE '" . $table_name . "'";
	}
	
	function drop_table($table_name) {
		return "DROP TABLE $table_name";
	}
	
	function create_table($table_name,$fields) {
		$fields = array_filter( array_map( array($this,'field_to_column'), $fields ), create_function('&$x','return in_array($x, database_adapter_mysql::$__field_types);') );
		$fields_string = array_reduce( array_map( Array('orm','spacefy'), array_keys($fields), array_values($fields) ), Array('orm','virgulate') );
		return 'CREATE TABLE ' . $table_name . ' (ROWID BIGINT AUTO_INCREMENT, PRIMARY KEY(ROWID)' . (($fields_string!='')?', '.$fields_string:'') . ') ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin';
	}
	
	function columns($table_name) {
		return "SHOW COLUMNS FROM " . $table_name;
	}
	
	function columns_after_query($results) {
		$fields = Array();
		foreach( $results as $table_field ) {
			if ($table_field['Field'] == 'ROWID') continue;
			$fields[$table_field['Field']]=$table_field['Type'];
		}
		return array_map( array($this,'column_to_field'), $fields );
	}
	
	function insert($table_name,$fields) {
		return 'INSERT INTO ' . $table_name . ' ( ' . array_reduce( array_keys($fields), Array('orm','virgulate') ) . ' ) values (' . array_reduce( array_values($fields), Array('orm','virgulate') ) . ')';
	}
	
	function update($table_name,$fields,$condition) {
		return 'UPDATE ' . $table_name . ' set ' . array_reduce( array_map( Array('orm','setify'), array_keys($fields), array_values($fields) ), Array('orm','virgulate')) . ' where ' . $condition;
	}
	
	function delete($table_name,$condition) {
		if (!is_array($condition)) {
			return 'DELETE FROM ' . $table_name . ' where (' . $condition . ')';
		} else {
			return 'DELETE FROM ' . $table_name . ' where ( ' . array_reduce( array_map( Array('orm','setify'), array_keys($condition), array_values($condition) ), Array('orm','andify')) . ' )';
		}
	}
	
	function query_builder( $table_name, $options = Array() ) {	
		return trim(ereg_replace(' +',' ', "SELECT " . (isset($options["distinct"])?$options["distinct"]:'') . ' ' . 
				(isset($options["select"])?$options["select"]: $table_name .'.*,' . $table_name .'.ROWID as ROWID') 
				. ' FROM ' . (isset($options["from"])?$options["from"]:$table_name) 
				. ' ' . (isset($options["joins"])?$options["joins"]:'') 
				. ' WHERE (' . (isset($options["conditions"])?($options["conditions"]==''?'1':$options["conditions"]):'1') 
				. ') ' . (isset($options["group"])?' GROUP BY ' . $options["group"]:'') 
				. ' ' . (isset($options["having"])?' HAVING ' . $options["having"]:'') 
				. ' ORDER BY ' . (isset($options["order"])?$options["order"]:$table_name . '.ROWID') 
				. ' ' . (isset($options["limit"])?'LIMIT ' . $options["limit"]:'') 
				. ' ' . (isset($options["offset"])?'OFFSET ' . $options["offset"]:'') ));
	}
	
	function execute($query) {
		$result = mysql_query( $query, self::$__open_handle  );
		if (!$result) {
			echo '<pre>' . "\r\n";
			echo 'Invalid query: ' . mysql_error() . "\r\n" . $query;
			echo '</pre>' . "\r\n";
		}
		return $result;
	}
	
	function execute_and_get_one($query) {
		$results = mysql_query( $query, self::$__open_handle  );
		return mysql_fetch_array($results,MYSQL_ASSOC);
	}
	
	function execute_and_get_all($query) {
		$results = mysql_query( $query, self::$__open_handle  );
		$return = Array();
		if (mysql_num_rows($results) > 0) {
			while ($row = mysql_fetch_assoc($results)) {
				$return[] = $row;
			}
		}
		return $return;
	}
	
	function last_row_inserted()
	{
		return mysql_insert_id( self::$__open_handle );
	}
	
	function get_one($results) {
		return mysql_fetch_array($results,MYSQL_ASSOC);
	}
	
	function get_all($results) {
		$return = Array();
		if (mysql_num_rows($results) > 0) {
			while ($row = mysql_fetch_assoc($results)) {
				$return[] = $row;
			}
		}
		return $return;
		
	}
	
	function create_index($table_name,$index_name,$fields) {
		//return 'CREATE INDEX "' . $index_name . '" ON "' . $table_name . '" (' . array_reduce( $fields, Array('orm','virgulate_and_comma') ) . ')';
	}
	
	function drop_index($index_name) {
		//return 'DROP INDEX "' . $index_name . '"';
	}
	
	function indexes($table_name) {
		//return 'SELECT name FROM sqlite_master WHERE type="index" AND name="' . $table_name . '"';
		return '';
	}
	
	function change_structure($table_name,$old_structure,$new_structure) {
		$queries = Array();

		// Create a TRANSACTION
		$queries[] = "START TRANSACTION";
		
		// Primeiro Passo, ver quais campos devem sair
		$fields_out = array_diff( $old_structure, $new_structure );
		
		// Segundo Passo, ver quais campos devem entrar
		$fields_in = array_diff( $new_structure, $old_structure );
		
		foreach ($fields_out as $field=>$type) {
			$queries[] = 'ALTER TABLE ' . $table_name . ' DROP COLUMN ' . $field;
		}
		
		foreach ($fields_in as $field=>$type) {
			$queries[] = 'ALTER TABLE ' . $table_name . ' ADD COLUMN ' . $field . ' ' . $this->field_to_column($type);
		}
		
		$queries[] = "COMMIT";
		
		foreach ($queries as $query) {
			mysql_query( $query, self::$__open_handle  );
		}
	}
}

?>