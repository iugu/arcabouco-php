<?

class log
{
	static function output($filename,$message)
	{
		global $base_directory;
		if($fp = @fopen( $base_directory . "/logs/" . $filename , 'a+'))
		{
			fwrite($fp, "$message\r\n" );
			fclose($fp);
		}
	}

	static function output_in_development($filename,$message)
	{
		if (core::get_environment() == "development")
		{
			self::output($filename,$message);
		}
	}
}

?>
