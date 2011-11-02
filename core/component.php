<?

/*
 * This file is part of the Arcabouco Framework.
 * (c) 2008 Patrick Negri <patrick@agencialobo.com.br>
 * (c) 2008 Paulo Lobo <plobo@agencialobo.com.br>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class component
{
	protected static $avaiable=Array();
	protected static $component_permissions=Array();
	protected static $component_default_permissions=Array();
	
	protected static $modules_with_permission=Array();
	
	static function get_list()
	{
		$core_components = core::list_dir( core::root_directory() . "/components/",false );
		$base_components = core::list_dir( core::base_directory() . "/components/",false );
		
		return array_merge($core_components,$base_components);
	}
	
	static function is_filter($var)
	{
		return (strpos($var,"_filter") != FALSE);
	}
	
	static function isnt_filter($var)
	{
		return !(strpos($var,"_filter") != FALSE);
	}
	
	static function load_filters()
	{
		$list = array_filter( self::get_list(), "component::is_filter" );
		foreach ($list as $item)
		{
			if (file_exists($item . "/config.php"))
			{
				include_once($item . "config.php");
			}
		}
		controller::prepare_routing_paths();
	}
	
	static function load_components()
	{
		$list = array_filter( self::get_list(), "component::isnt_filter" );
		foreach ($list as $item)
		{
			if (file_exists($item . "/config.php"))
			{
				include_once($item . "config.php");
			}
		}
		controller::prepare_routing_paths();
	}
	
	static function register($name,$instance)
	{
		if (!array_key_exists($name,self::$avaiable)) self::$avaiable[$name] = $instance;
		self::initialize_permissions($name);
		return $instance;
	}
	
	static function find($name)
	{
		if (array_key_exists($name,self::$avaiable)) return self::$avaiable[$name];
		return null;
	}
	
	static function all()
	{
		return self::$avaiable;
	}
	
	static function initialize_permissions($name)
	{
		if (!isset(self::$component_permissions[$name])) self::$component_permissions[$name] = Array();
		if (!isset(self::$component_default_permissions[$name])) self::$component_default_permissions[$name] = 'public';
	}
	
	static function register_permission_rule($name,$rule)
	{
		self::initialize_permissions($name);
		array_push( self::$component_permissions[$name], $rule );
		self::$component_permissions[$name] = array_unique(self::$component_permissions[$name]);
	}
	
	static function get_permission_rules($name)
	{
		self::initialize_permissions($name);
		return self::$component_permissions[$name];
	}
	
	static function set_default_permission($name,$value)	// private or public
	{
		self::initialize_permissions($name);
		self::$component_default_permissions[$name] = $value;
	}
	
	static function is_public($name)
	{
		self::initialize_permissions($name);
		return self::$component_default_permissions[$name] == 'public';
	}
	
	static function have_permissions($module_name,$rule)
	{
		
		$have = true;
		
		$all_modules = null;
		$add_modules = false;
		
		if (!count(self::$modules_with_permission)) {
			$all_modules = component::all();
			$add_modules = true;
		}
		else {
			$all_modules = self::$modules_with_permission;
		}

		foreach ( $all_modules as $component ) {
			if (method_exists($component,'have_permissions_router')) {
				if ($add_modules) {
					self::$modules_with_permission[] = $component;
				}
				if ($component->have_permissions_router($module_name,$rule) == false) {
					$have = false;
				}
			}
		}
		
		
		
		return $have;
	}
	
	static function redirect_if_not_logged()
	{
		$have = true;

		foreach ( component::all() as $component ) {
			if (method_exists($component,'have_logged_router')) {
				if ($component->have_logged_router() == false) {
					$have = false;
				}
			}
		}

		return $have;
	}

}

?>