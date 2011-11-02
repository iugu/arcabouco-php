<?

/*
 * This file is part of the Arcabouco Framework.
 * (c) 2008 Patrick Negri <patrick@agencialobo.com.br>
 * (c) 2008 Paulo Lobo <plobo@agencialobo.com.br>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class controller
{
	public static $controllers = Array();
	public static $current_controller = '';

	static function register($pattern,$model,$controller,$method="GET",$variable_names = array())
	{
		array_push(self::$controllers, Array($pattern,$model,$controller,$method,$variable_names));
		
	}

	static function get_adapter($document_requested,$method_requested="GET")
	{
		//ksort(self::$controllers,SORT_NUMERIC);
		self::$controllers = strlen_sort(self::$controllers,0);
		self::$controllers = array_reverse(self::$controllers);
		foreach (self::$controllers as $object)
		{
			$rpat = "#^" . addslashes($object[0]) . "$#im";
			if (preg_match($rpat,$document_requested,$matches) && ($object[3]==strtoupper($method_requested)))
			{
				if (count($object[4]))
				{
					// Variable names from URI
					//web::params()
					for ($i=0;$i<count($object[4]);$i++)
					{
						web::add_param($object[4][$i],$matches[$i+1]);
					}
				}
				return $object;
			}
		}
		return null;
	}
	
	static function get_current_adapter()
	{
		return self::$current_controller;
	}
	
	static function prepare_routing_paths()
	{
		if (count(self::$controllers) == 0) return;
		self::$controllers = strlen_sort(self::$controllers,0);
		self::$controllers = array_reverse(self::$controllers);
	}

	static function dispatch_request()
	{
		if (error_get_last()) exit(0);
		$parameters = Array();

		if (count(self::$controllers) == 0) return false;
		
		$method = strtoupper(web::method_requested());
		$document_requested = web::document_requested();
		
		if ($method == '') $method = 'GET';

		foreach (self::$controllers as $object)
		{
			$rpat = "#^" . addslashes($object[0]) . "$#im";
			if (preg_match($rpat,$document_requested,$matches) && ($object[3]==$method))
			{

				if (count($object[4]))
				{
					// Variable names from URI
					//web::params()
					for ($i=0;$i<count($object[4]);$i++)
					{
						web::add_param($object[4][$i],$matches[$i+1]);
					}
				}
				
				$addon = component::find($object[1]);
				if (method_exists($addon,$object[2])) {
					self::$current_controller = $addon;
					$ret_obj = call_user_func_array( Array(&$addon,$object[2]), Array(web::params()) );
					if ($ret_obj !== false)
					{
						return $ret_obj;
					}
				}

				if (count($object[4]))
				{
					// Variable names from URI
					//web::params()
					for ($i=0;$i<count($object[4]);$i++)
					{
						web::remove_param($object[4][$i]);
					}
				}
			}
		}
		return false;
	}

	static function get_url($action,$module)
	{
		if (error_get_last()) exit(0);
		$parameters = Array();

		self::$controllers = strlen_sort(self::$controllers,0);
		self::$controllers = array_reverse(self::$controllers);
		foreach (self::$controllers as $object)
		{
			if (($action == $object[2]) && ($module == $object[1]))
			{
				return $object[0];
			}
		}
		return false;
	}

	static function get_parameters($action,$module)
	{
		if (error_get_last()) exit(0);
		$parameters = Array();

		self::$controllers = strlen_sort(self::$controllers,0);
		self::$controllers = array_reverse(self::$controllers);
		foreach (self::$controllers as $object)
		{
			if (($action == $object[2]) && ($module == $object[1]))
			{
				return Array('url'=>$object[0],'method'=>$object[3],'parameters'=>$object[4]);
			}
		}
		return false;
	}
}

?>