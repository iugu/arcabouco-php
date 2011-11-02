<?

/*
 * This file is part of the Arcabouco Framework.
 * (c) 2008 Patrick Negri <patrick@agencialobo.com.br>
 * (c) 2008 Paulo Lobo <plobo@agencialobo.com.br>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class pngfix_module
{
	function before_head()
	{
		$document_url = web::document_url() . core::relative_path(dirname(__FILE__));
		
$html = <<<ENDOFHTML
	<!--[if lte IE 6]>
	<style type= "text/css"> img { behavior: url("${document_url}/pngfix.htc"); } </style>
	<![endif]-->

ENDOFHTML;
		
		return $html;
	}
	
	function module_name()
	{
		return _("PNGFix");
	}
}

component::set_default_permission('pngfix_module','public');

component::register('pngfix_module', new pngfix_module());

?>