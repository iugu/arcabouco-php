<?

/*
 * This file is part of the Arcabouco Framework.
 * (c) 2008 Patrick Negri <patrick@agencialobo.com.br>
 * (c) 2008 Paulo Lobo <plobo@agencialobo.com.br>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class database_adapter_sqlite2
{
	// Simple Query cacher for one session
	protected static $__query_cache = NULL;

	protected static $__open_handle = NULL;
	static $__field_types = Array('TEXT','INTEGER','REAL','blob');
	static $__map = Array(
		Array('text',		'TEXT',			true),
		Array('integer',	'INTEGER',		false),
		Array('real',		'REAL',			false),
		Array('blob',		'blob',			false)
	);
	
	function database_adapter_sqlite2( $resource_name, $resource_ptr, $resource_info = Array() ) {
		$database_file = core::base_directory() . "/" . $resource_info['database'];
		if (self::$__open_handle == NULL)
		{
			self::$__open_handle = sqlite_open($database_file,0666,$sqlite_error);
			sqlite_busy_timeout(self::$__open_handle, 60000);
			if (file_exists($database_file)) {
				$permission = substr(decoct( fileperms($database_file) ), 1);
			}
		}
		$this->__resource_name = $resource_name; $this->__object = $resource_ptr;
	}
	
	function format_name($name) {
		return '"' . $name . '"';
	}
	
	function field_to_column($type) {
		foreach(self::$__map as $typeinfo) if ($type == $typeinfo[0]) return $typeinfo[1];
	}
	
	function column_to_field($type) {
		foreach(self::$__map as $typeinfo) {
			if (trim($type,'"') == trim($typeinfo[1],'"')) return $typeinfo[0];
		}
	}
	
	function column_value($type,$value) {
		foreach(self::$__map as $typeinfo) {
			if ($type == $typeinfo[0])
			{ 
				if ($typeinfo[2]) return '\'' . sqlite_escape_string($value) . '\'';
			}
			else
			{
				if ($value == '') return 0;
			}
			return $value;
		}
		return $value;
	}
	
	function table_exists($table_name) {
		return "SELECT name FROM sqlite_master WHERE type='table' AND name='" . $table_name . "'";
	}
	
	function drop_table($table_name) {
		return "DROP TABLE $table_name";
	}
	
	function create_table($table_name,$fields) {
		$fields = array_filter( array_map( array($this,'field_to_column'), $fields ), create_function('&$x','return in_array($x, database_adapter_sqlite2::$__field_types);') );
		return 'CREATE TABLE ' . $table_name . ' ("ROWID" INTEGER NOT NULL PRIMARY KEY, ' . array_reduce( array_map( Array('orm','spacefy_and_comma'), array_keys($fields), array_values($fields) ), Array('orm','virgulate') ) . ')';
	}
	
	function columns($table_name) {
		return "PRAGMA table_info(" . $table_name . ")";
	}
	
	function columns_after_query($results) {
		$fields = Array();
		foreach( $results as $table_field ) $fields[$table_field['name']]=$table_field['type'];
		return array_map( array($this,'column_to_field'), $fields );
	}

	function insert($table_name,$fields) {

		return 'INSERT INTO ' . $table_name . ' ( ' . array_reduce( array_keys($fields), Array('orm','virgulate_and_single_comma') ) . ' ) values (' . array_reduce( array_values($fields), Array('orm','virgulate') ) . ')';
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
				(isset($options["select"])?$options["select"]:'"' . $table_name .'".*,"'. $table_name .'"."ROWID" as ROWID') 
				. ' FROM ' . (isset($options["from"])?$options["from"]:$table_name) 
				. ' ' . (isset($options["joins"])?$options["joins"]:'') 
				. ' WHERE (' . (isset($options["conditions"])?($options["conditions"]==''?'1':$options["conditions"]):'1') 
				. ') ' . (isset($options["group"])?' GROUP BY ' . $options["group"]:'') 
				. ' ' . (isset($options["having"])?' HAVING ' . $options["having"]:'') 
				. ' ORDER BY ' . (isset($options["order"])?$options["order"]:'"' . $table_name . '"."ROWID"') 
				. ' ' . (isset($options["limit"])?'LIMIT ' . $options["limit"]:'') 
				. ' ' . (isset($options["offset"])?'OFFSET ' . $options["offset"]:'') ));
	}
	
	function execute($query) {
		global $base_directory;
		$results = sqlite_query( self::$__open_handle, $query, SQLITE_ASSOC );
		$error_code = sqlite_last_error( self::$__open_handle );
		if ($error_code == 5) {
			$i = 0;
			for ($i=0;$i<5;$i++)
			{
				sleep(10);
				$results = sqlite_query( self::$__open_handle, $query, SQLITE_ASSOC );
				$error_code = sqlite_last_error( self::$__open_handle );
				if ($error_code == 0) {
					break;
				}
			}
		}
		if ($error_code != 0) {
			if($fp = @fopen( $base_directory . "/logs/" . "database_errors.log" , 'a+'))
			{
				fwrite($fp, core::format_date(core::time(),'%H:%M',1) . " - " . $error_code . ': ' . sqlite_error_string( $error_code ) . ": \r\n" . "\t\t" . $query . "\r\n" );
				fclose($fp);
			}
		}
		if ($error_code == 5) {
			// Interromper execução do Script, mecher nisto depois novamente
			// Estou interrompendo a execução pq o mané pode não achar a tabela e tentar
			// excluí-la ou mudar a estrutura
			echo '<div id="mpadv_error" style="background:#F0F0F0;color:#000;border:2px solid #FF0000;float:left"><div style="padding:5px;font:bold 14px verdana;color:#FFF;background:#CC0000">Ops! Erro crítico no Banco de Dados</div><div style="padding:10px;font:12px verdana;line-height:16px;color:#000">O banco de dados parece estar travado.<br />Contate o Suporte Técnico e mencione o código #10002.</div></div><div style="clear:both">&nbsp;</div>';
			exit(0);
		}
		if ($error_code == 8) {
			echo '<div id="mpadv_error" style="background:#F0F0F0;color:#000;border:2px solid #FF0000;float:left"><div style="padding:5px;font:bold 14px verdana;color:#FFF;background:#CC0000">Ops! Erro crítico no Banco de Dados</div><div style="padding:10px;font:12px verdana;line-height:16px;color:#000">O banco de dados parece estar em modo de leitura.<br />Contate o Suporte Técnico e mencione o código #10001.</div></div><div style="clear:both">&nbsp;</div>';
			exit(0);
		}
		if ($error_code == 17) {
			sqlite_close( self::$__open_handle );
			self::$__open_handle = NULL;
			if (self::$__open_handle == NULL)
			{
				self::$__open_handle = sqlite_open($database_file,0666,$sqlite_error);
				sqlite_busy_timeout(self::$__open_handle, 60000);
				if (file_exists($database_file)) {
					$permission = substr(decoct( fileperms($database_file) ), 1);
				}
			}
			$results = sqlite_query( self::$__open_handle, $query, SQLITE_ASSOC );
		}
		return $results;
	}
	
	function execute_and_get_one($query) {
		/*
		if (self::$__query_cache == NULL) {
			self::$__query_cache = Array();
		}

		if (strpos($query,'UPDATE')!==false) {
			self::$__query_cache = Array();
		}
		if (strpos($query,'CREATE')!==false) {
			self::$__query_cache = Array();
		}
		if (strpos($query,'INSERT')!==false) {
			self::$__query_cache = Array();
		}

		if (array_key_exists( $query, self::$__query_cache ))
		{
			return self::$__query_cache[$query];
		}
		else
		{
			$res = sqlite_query( self::$__open_handle, $query, SQLITE_ASSOC );
			$results = sqlite_fetch_array($res,SQLITE_ASSOC);;

			self::$__query_cache[$query] = $results;

			return $results;
		}
		*/
		$res = $this->execute($query);
		$results = sqlite_fetch_array($res,SQLITE_ASSOC);;
		return $results;
	}
	
	function execute_and_get_all($query) {
	/*
		if (self::$__query_cache == NULL) {
			self::$__query_cache = Array();
		}
		
		if (strpos($query,'UPDATE')!==false) {
			self::$__query_cache = Array();
		}
		if (strpos($query,'CREATE')!==false) {
			self::$__query_cache = Array();
		}
		if (strpos($query,'INSERT')!==false) {
			self::$__query_cache = Array();
		}
		
		if (array_key_exists( $query, self::$__query_cache ))
		{
			return self::$__query_cache[$query];
		}
		else
		{
			$res = sqlite_query( self::$__open_handle, $query, SQLITE_ASSOC );
			$results = sqlite_fetch_all($res,SQLITE_ASSOC);
			
			self::$__query_cache[$query] = $results;
		
			return $results;
		}
		*/
		$res = $this->execute($query);
		$results = sqlite_fetch_all($res,SQLITE_ASSOC);
		return $results;
	}
	
	function last_row_inserted()
	{
		return sqlite_last_insert_rowid( self::$__open_handle );
	}
	
	function get_one($results) {
		return sqlite_fetch_array($results,SQLITE_ASSOC);
	}
	
	function get_all($results) {
		return sqlite_fetch_all($results);
	}
	
	function create_index($table_name,$index_name,$fields) {
		return 'CREATE INDEX "' . $index_name . '" ON "' . $table_name . '" (' . array_reduce( $fields, Array('orm','virgulate_and_comma') ) . ')';
	}
	
	function drop_index($index_name) {
		return 'DROP INDEX "' . $index_name . '"';
	}
	
	function indexes($table_name) {
		return 'SELECT name FROM sqlite_master WHERE type="index" AND name="' . $table_name . '"';
	}
	
	function change_structure($table_name,$old_structure,$new_structure) {
		global $base_directory;
		//oooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooo
		//	Do whatever is necessary to translate from one structure to another
		//	This is the only function that can execute queries directly, etc
		//oooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooo
		
		$old_field_names = array_reduce( array_keys($old_structure), Array('orm','virgulate') );
		$new_field_names = array_reduce( array_keys($new_structure), Array('orm','virgulate') );
		$copy_fields = array_intersect_assoc($new_structure,$old_structure);
		$copy_field_names = array_reduce( array_keys($copy_fields), Array('orm','virgulate') );
		
		if($fp = @fopen( $base_directory . "/logs/" . "database_errors.log" , 'a+'))
		{
			fwrite($fp, core::format_date(core::time(),'%H:%M',1) . " - changed structure called for: " . $table_name . "\r\n" );
			ob_start();
			print_r($old_structure);
			print_r($new_structure);
			$structures = ob_get_clean();
			fwrite($fp,$structures);
			fclose($fp);
		}
		
		$queries = Array();
		
		// Create a TRANSACTION
		$queries[] = "BEGIN TRANSACTION";
		
		if ($this->get_one($this->execute( $this->table_exists('tmp_' . $table_name) ))) {
			// Drop temporary table if exists
			$queries[] = "DROP TABLE tmp_" . $table_name;
		}
		
		// Create temporary table and store data
		$queries[] = "CREATE TEMPORARY TABLE tmp_" . $table_name . '(' . array_reduce( array_map( Array('orm','spacefy_and_comma'), array_keys($copy_fields), array_values($copy_fields) ), Array('orm','virgulate') ) . ')';
		
		// Copy data from Table to Temporary Table
		$queries[] = "INSERT INTO tmp_" . $table_name . " (ROWID,$copy_field_names) SELECT ROWID,$copy_field_names  from " . $table_name; 
		
		// Drop current Table
		$queries[] = "DROP TABLE " . $table_name;
		
		// Create new Table
		$queries[] = $this->create_table($table_name,$new_structure);
		
		// Copy data from temporary table to new table
		$queries[] = "INSERT INTO " . $table_name . " (ROWID,$copy_field_names) SELECT ROWID,$copy_field_names from tmp_" . $table_name;
		
		// Drop temporary Table
		$queries[] = "DROP TABLE tmp_" . $table_name;
		
		// Commit TRANSACTION
		$queries[] = "COMMIT";
		
		foreach ($queries as $query) {
			sqlite_query(self::$__open_handle,$query);
			//echo $query . "\r\n";
		}
	}
}

?>