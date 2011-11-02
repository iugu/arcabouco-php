<?

/*
 * This file is part of the Arcabouco Framework.
 * (c) 2008 Patrick Negri <patrick@agencialobo.com.br>
 * (c) 2008 Paulo Lobo <plobo@agencialobo.com.br>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
  
//o-------------------------------o
//		Switch Advanced Error
//		Reporting
//o-------------------------------o
core::enable_advanced_error_handling();
require(core::root_directory() . "/core/message.php");
require(core::root_directory() . "/core/inflector.php");
require(core::root_directory() . "/core/orm.php");
require(core::root_directory() . "/core/component.php");
require(core::root_directory() . "/core/controller.php");
require(core::root_directory() . "/core/helpers.php");
require(core::root_directory() . "/core/markdown.php");

//o-------------------------------o
//		Load Filters
//o-------------------------------o
component::load_filters();

?>