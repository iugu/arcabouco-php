<?

/*
 * This file is part of the Arcabouco Framework.
 * (c) 2008 Patrick Negri <patrick@agencialobo.com.br>
 * (c) 2008 Paulo Lobo <plobo@agencialobo.com.br>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class message
{
	var $name = "";
	var $subject = "";
	var $queue = Array();
	
	function set_name($name)
	{
		$this->name = $name;
		return $name;
	}
	
	function get_name()
	{
		return $this->name;
	}
	
	function set_subject($subject)
	{
		$this->subject = $subject;
		return $subject;
	}
	
	function get_subject()
	{
		return $this->subject;
	}
	
	function total_messages()
	{
		return count($this->queue);
	}
	
	function add_message($message,$to="default")
	{
		if (!isset($this->queue[$to])) $this->queue[$to] = Array();
		$this->queue[$to][] = $message;
		return true;
	}
	
	function messages_for($to="default")
	{
		if (!isset($this->queue[$to])) $this->queue[$to] = Array();
		if (count($this->queue[$to]) == 0) return NULL;
		return $this->queue[$to];
	}
	
	function all_messages()
	{
		if (count($this->queue) == 0) return NULL;
		return $this->queue;
	}
	
	function clear()
	{
		unset($this->queue);
		$this->queue = Array();
		return $this->queue;
	}
}

?>