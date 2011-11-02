<?php

/*
 * This file is part of the Arcabouco Framework.
 * (c) 2008 Patrick Negri <patrick@agencialobo.com.br>
 * (c) 2008 Paulo Lobo <plobo@agencialobo.com.br>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class inflector
{
	protected static $singular_rules = array();
	protected static $plural_rules = array ();
	protected static $uncountable = array();
	protected static $irregular = array();
	
	protected static $default_language_rule = 'en';
	
	protected static $rules = Array();
	
	static function set_default_language($va)
	{
		self::$default_language_rule = $va;
	}
	
	static function load_rules($language_code, $singular, $plural, $uncountable, $irregular )
	{
		self::$rules[$language_code] = Array(
						'singular' => $singular,
						'plural' => $plural,
						'uncountable' => $uncountable,
						'irregular' => $irregular);
	}
	
	// ------ CLASS METHODS ------ //

	// ---- Public methods ---- //

	// {{{ pluralize()

	/**
	* Pluralizes English nouns.
	* 
	* @access public
	* @static
	* @param    string    $word    English noun to pluralize
	* @return string Plural noun
	*/
	
	static function pluralize_if($word,$count,$language='')
	{
		if (abs($count) < 2) return $word;
		return self::pluralize($word,$language);
	}
	
	static function pluralize($word,$language='')
	{
		$lowercased_word = strtolower($word);

		$lc = ($language=='')?(self::$default_language_rule):$language;

		foreach (self::$rules[$lc]['uncountable'] as $_uncountable){
			if(substr($lowercased_word,(-1*strlen($_uncountable))) == $_uncountable){
				return $word;
			}
		}

		foreach (self::$rules[$lc]['irregular'] as $_plural=> $_singular){
			if (preg_match('/(^'.$_plural.')$/i', $word, $arr)) {
				return preg_replace('/('.$_plural.')$/i', substr($arr[0],0,1).substr($_singular,1), $word);
			}
		}
		
		foreach (self::$rules[$lc]['singular'] as $singular => $plural) {
			if (preg_match($singular, $word)) {
				return preg_replace($singular, $plural, $word);
			}
		}
		return false;

	}

	// }}}
	// {{{ singularize()

	/**
	* Singularizes English nouns.
	* 
	* @access public
	* @static
	* @param    string    $word    English noun to singularize
	* @return string Singular noun.
	*/
	static function singularize($word,$language='')
	{
	
		$lc = ($language=='')?(self::$default_language_rule):$language;

		$lowercased_word = strtolower($word);
		foreach (self::$rules[$lc]['uncountable'] as $_uncountable){
			if(substr($lowercased_word,(-1*strlen($_uncountable))) == $_uncountable){
				return $word;
			}
		}

		foreach (self::$rules[$lc]['irregular'] as $_singular => $_plural){
			if (preg_match('/(^'.$_plural.')$/i', $word, $arr)) {
				return preg_replace('/('.$_plural.')$/i', substr($arr[0],0,1).substr($_singular,1), $word);
			}
		}

		foreach (self::$rules[$lc]['plural'] as $plural => $singular) {
			if (preg_match($plural, $word)) {
				return preg_replace($plural, $singular, $word);
			}
		}

		return $word;
	}

	// }}}
	// {{{ titleize()

	/**
	* Converts an underscored or CamelCase word into a English
	* sentence.
	* 
	* The titleize function converts text like "WelcomePage",
	* "welcome_page" or  "welcome page" to this "Welcome
	* Page".
	* If second parameter is set to 'first' it will only
	* capitalize the first character of the title.
	* 
	* @access public
	* @static
	* @param    string    $word    Word to format as tile
	* @param    string    $uppercase    If set to 'first' it will only uppercase the
	* first character. Otherwise it will uppercase all
	* the words in the title.
	* @return string Text formatted as title
	*/
	static function titleize($word, $uppercase = '')
	{
		$uppercase = $uppercase == 'first' ? 'ucfirst' : 'ucwords';
		return $uppercase(inflector::humanize(inflector::underscore($word)));
	}

	// }}}
	// {{{ camelize()

	/**
	* Returns given word as CamelCased
	* 
	* Converts a word like "send_email" to "SendEmail". It
	* will remove non alphanumeric character from the word, so
	* "who's online" will be converted to "WhoSOnline"
	* 
	* @access public
	* @static
	* @see variablize
	* @param    string    $word    Word to convert to camel case
	* @return string UpperCamelCasedWord
	*/
	static function camelize($word)
	{
		if(preg_match_all('/\/(.?)/',$word,$got)){
			foreach ($got[1] as $k=>$v){
				$got[1][$k] = '::'.strtoupper($v);
			}
			$word = str_replace($got[0],$got[1],$word);
		}
		return str_replace(' ','',ucwords(preg_replace('/[^A-Z^a-z^0-9^:]+/',' ',$word)));
	}

	// }}}
	// {{{ underscore()

	/**
	* Converts a word "into_it_s_underscored_version"
	* 
	* Convert any "CamelCased" or "ordinary Word" into an
	* "underscored_word".
	* 
	* This can be really useful for creating friendly URLs.
	* 
	* @access public
	* @static
	* @param    string    $word    Word to underscore
	* @return string Underscored word
	*/
	static function underscore($word)
	{
		return  strtolower(preg_replace('/[^A-Z^a-z^0-9^\/]+/','_',
		preg_replace('/([a-z\d])([A-Z])/','\1_\2',
		preg_replace('/([A-Z]+)([A-Z][a-z])/','\1_\2',
		preg_replace('/::/', '/',$word)))));
	}
	
	static function dasherize($word)
	{
		return  strtolower(preg_replace('/[^A-Z^a-z^0-9^\/]+/','-',
		preg_replace('/([a-z\d])([A-Z])/','\1_\2',
		preg_replace('/([A-Z]+)([A-Z][a-z])/','\1_\2',
		preg_replace('/::/', '/',$word)))));
	}	

	// }}}
	// {{{ humanize()

	/**
	* Returns a human-readable string from $word
	* 
	* Returns a human-readable string from $word, by replacing
	* underscores with a space, and by upper-casing the initial
	* character by default.
	* 
	* If you need to uppercase all the words you just have to
	* pass 'all' as a second parameter.
	* 
	* @access public
	* @static
	* @param    string    $word    String to "humanize"
	* @param    string    $uppercase    If set to 'all' it will uppercase all the words
	* instead of just the first one.
	* @return string Human-readable word
	*/
	static function humanize($word, $uppercase = '')
	{
		$uppercase = $uppercase == 'all' ? 'ucwords' : 'ucfirst';
		return $uppercase(str_replace('_',' ',preg_replace('/_id$/', '',$word)));
	}

	// }}}
	// {{{ variablize()

	/**
	* Same as camelize but first char is lowercased
	* 
	* Converts a word like "send_email" to "sendEmail". It
	* will remove non alphanumeric character from the word, so
	* "who's online" will be converted to "whoSOnline"
	* 
	* @access public
	* @static
	* @see camelize
	* @param    string    $word    Word to lowerCamelCase
	* @return string Returns a lowerCamelCasedWord
	*/
	static function variablize($word)
	{
		$word = inflector::camelize($word);
		return strtolower($word[0]).substr($word,1);
	}

	// }}}
	// {{{ tableize()

	/**
	* Converts a class name to its table name according to rails
	* naming conventions.
	* 
	* Converts "Person" to "people"
	* 
	* @access public
	* @static
	* @see classify
	* @param    string    $class_name    Class name for getting related table_name.
	* @return string plural_table_name
	*/
	static function tableize($class_name)
	{
		return inflector::pluralize(inflector::underscore($class_name));
	}

	// }}}
	// {{{ classify()

	/**
	* Converts a table name to its class name according to rails
	* naming conventions.
	* 
	* Converts "people" to "Person"
	* 
	* @access public
	* @static
	* @see tableize
	* @param    string    $table_name    Table name for getting related ClassName.
	* @return string SingularClassName
	*/
	static function classify($table_name)
	{
		return inflector::camelize(inflector::singularize($table_name));
	}

	// }}}
	// {{{ ordinalize()

	/**
	* Converts number to its ordinal English form.
	* 
	* This method converts 13 to 13th, 2 to 2nd ...
	* 
	* @access public
	* @static
	* @param    integer    $number    Number to get its ordinal value
	* @return string Ordinal representation of given string.
	*/
	static function ordinalize($number)
	{
		if (in_array(($number % 100),range(11,13))){
			return $number.'th';
		}else{
			switch (($number % 10)) {
				case 1:
				return $number.'st';
				break;
				case 2:
				return $number.'nd';
				break;
				case 3:
				return $number.'rd';
				default:
				return $number.'th';
				break;
			}
		}
	}

	// }}}


	static function demodulize($module_name)
	{
		$module_name = preg_replace('/^.*::/','',$module_name);
		return inflector::humanize(inflector::underscore($module_name));
	}

	static function modulize($module_description)
	{
		return inflector::camelize(inflector::singularize($module_description));
	}


	/**
	 * Transforms a string to its unaccented version. 
	 * This might be useful for generating "friendly" URLs
	 */
	static function unaccent($text)
	{
		return utf8_encode(strtr(utf8_decode($text),
								utf8_decode('ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ'),
								utf8_decode('AAAAAAACEEEEIIIIDNOOOOOOUUUUYTsaaaaaaaceeeeiiiienoooooouuuuyty')
								)
							);
	}


	//static function foreign_key($class_name, $separate_class_name_and_id_with_underscore = true)
	//{
	//	return inflector::underscore(inflector::demodulize($class_name)).($separate_class_name_and_id_with_underscore ? "_id" : "id");
	//}



}

inflector::load_rules('en',
array(
			'/(quiz)$/i' => '\1zes',
			'/^(ox)$/i' => '\1en',
			'/([m|l])ouse$/i' => '\1ice',
			'/(matr|vert|ind)ix|ex$/i' => '\1ices',
			'/(x|ch|ss|sh)$/i' => '\1es',
			'/([^aeiouy]|qu)ies$/i' => '\1y',
			'/([^aeiouy]|qu)y$/i' => '\1ies',
			'/(hive)$/i' => '\1s',
			'/(?:([^f])fe|([lr])f)$/i' => '\1\2ves',
			'/sis$/i' => 'ses',
			'/([ti])um$/i' => '\1a',
			'/(buffal|tomat)o$/i' => '\1oes',
			'/(bu)s$/i' => '\1ses',
			'/(alias|status)/i'=> '\1es',
			'/(octop|vir)us$/i'=> '\1i',
			'/(ax|test)is$/i'=> '\1es',
			'/s$/i'=> 's',
			'/$/'=> 's'), 
array (
			'/(quiz)zes$/i' => '\\1',
			'/(matr)ices$/i' => '\\1ix',
			'/(vert|ind)ices$/i' => '\\1ex',
			'/^(ox)en/i' => '\\1',
			'/(alias|status)es$/i' => '\\1',
			'/([octop|vir])i$/i' => '\\1us',
			'/(cris|ax|test)es$/i' => '\\1is',
			'/(shoe)s$/i' => '\\1',
			'/(o)es$/i' => '\\1',
			'/(bus)es$/i' => '\\1',
			'/([m|l])ice$/i' => '\\1ouse',
			'/(x|ch|ss|sh)es$/i' => '\\1',
			'/(m)ovies$/i' => '\\1ovie',
			'/(s)eries$/i' => '\\1eries',
			'/([^aeiouy]|qu)ies$/i' => '\\1y',
			'/([lr])ves$/i' => '\\1f',
			'/(tive)s$/i' => '\\1',
			'/(hive)s$/i' => '\\1',
			'/([^f])ves$/i' => '\\1fe',
			'/(^analy)ses$/i' => '\\1sis',
			'/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '\\1\\2sis',
			'/([ti])a$/i' => '\\1um',
			'/(n)ews$/i' => '\\1ews',
			'/s$/i' => '',
			),
array('equipment', 'information', 'rice', 'money', 'species', 'series', 'fish', 'sheep'), 
array(
			'person' => 'people',
			'man' => 'men',
			'child' => 'children',
			'sex' => 'sexes',
			'move' => 'moves') );

inflector::load_rules('pt_BR',array(
			'/(ás)$/i' => 'ases',
			'/(ês)$/i' => 'eses',
			'/(el)$/i' => 'éis',
			'/(ol)$/i' => 'óis',
			'/(al)$/i' => 'ais',
			'/(oi)$/i' => 'ois',
			'/(ul)$/i' => 'uis',
			'/(m)$/i' => 'ns',
			'/(ão)$/i' => 'ões',
			'/(ao)$/i' => 'oes',
			'/s$/i'=> 's',
			'/$/'=> 's'
			),array (
			'/ases$/i' => 'às',
			'/eses$/i' => 'ês',
			'/éis$/i' => 'el',
			'/óis$/i' => 'ol',
			'/ais$/i' => 'al',
			'/ois$/i' => 'oi',
			'/uis$/i' => 'ul',
			'/ns$/i' => 'm',
			'/ões$/i' => 'ão',
			'/oes$/i' => 'ao',
			'/s$/i'=> '',
			),array(),array(
			'lápis' => 'lápis',
			'pires' => 'pires',
			'mão' => 'mãos',
			'afegão' => 'afegãos',
			'pão' => 'pães',
			'capitão' => 'capitães',
			'cão' => 'cães',
			'alemão' => 'alemães',
			'biles' => 'biles'
			) );
			
inflector::set_default_language(core::$defaults['system_inflector_language']);

?>