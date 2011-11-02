<?

/*
 * This file is part of the Arcabouco Framework.
 * (c) 2008 Patrick Negri <patrick@agencialobo.com.br>
 * (c) 2008 Paulo Lobo <plobo@agencialobo.com.br>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class deploy_module
{
	function index() {
	
		return true;
	}
	
	function update_settings()
	{
		return true;
	}
	
	function deploy_install()
	{
		
		return true;
	}
	
	function deploy_update_system_wide()
	{
		$params = web::params();
		if (core::get_environment() != 'development') return false;
		header("Content-type: text/html; charset=UTF-8");
		
		$server_data_file = core::base_directory() . '/config/wide_deploy.yml';

		if (!file_exists($server_data_file)) return false;
		
		$deploy_configuration = yaml::load($server_data_file);
		
		if (!isset($deploy_configuration['wide_deploy'])) return false;

		ini_set("max_execution_time",0); 
		ini_set('output_buffering', 0);

		function dummyErrorHandler ($errno, $errstr, $errfile, $errline) {
		}

		function eeFlush() {
			ob_start();
			ob_end_clean();
			flush();
			set_error_handler("dummyErrorHandler");
			ob_end_flush();
			restore_error_handler();
		}

		forceFlush();
		forceFlush();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<title>Executing Task: Arcabouco deployment...</title>
	<head>
	<style type="text/css" media="screen">
		pre
		{
			color:#555;
			font-size:11px;
		}
	</style>
	</head>
</head>
<body>
<script type="text/javascript">
	//<![CDATA[
function pageScroll() {
		window.scrollBy(0,1000); // horizontal and vertical scroll increments
		scrolldelay = setTimeout('pageScroll()',100); // scrolls every 100 milliseconds
}
pageScroll();
	//]]>
</script>
<center>
<div style="text-align:left;width:760px;margin-left:auto;margin-right:auto">
<div style="text-align:center;padding:20px">
<img src="/core/imgs/logo-arcabouco.jpg" alt="" />
</div>
<pre>
<?
echo "<h1>Performing System Wide Update (Todos os sites)...</h1>\r\n";

		function my_ssh_disconnect($reason, $message, $language) {
		  printf("Server disconnected with reason code [%d] and message: %s\n",
				 $reason, $message);
		}

		foreach ($deploy_configuration['wide_deploy'] as $deploy_info) {
			
			$name = $deploy_info["name"];
			$server = $deploy_info["host"];
			$user = $deploy_info["username"];
			$password = $deploy_info["password"];
			$directory = $deploy_info["directory"];
			
			echo "<h2 style=\"margin:0px;margin-top:10px;\">" . $name . "</h2>\r\n";
			
			$dirname = make_filename(strtolower($name));
			
			$site_key = core::base_directory() . "/.ssh/" . $dirname . "/key";
			if (!is_dir(dirname($site_key))) mkdir(dirname($site_key),777);
			
			if (!file_exists($site_key))
			{
				//echo "Generating SSH Key Pair...\r\n";
				forceFlush();
				mkdir(core::base_directory() . '/.ssh');
				exec("ssh-keygen.exe -t rsa -f " . $site_key . " -N ''");
				//echo "Done...\r\n";
				forceFlush();
				echo "Registering Key on Server...";
				forceFlush();
				//echo "conecting...\r\n";
				forceFlush();
				$connection = ssh2_connect($server, 22);
				//echo "handshaking...\r\n";
				forceFlush();
				ssh2_auth_password($connection, $user, $password);
				//echo "sending keys...\r\n";
				forceFlush();
				ssh2_exec($connection, 'rm ~/key.pub');
				ssh2_scp_send($connection, $site_key . '.pub', '~/key.pub', 0644);
				//echo "moving keys...\r\n";
				forceFlush();
				ssh2_exec($connection, 'if [ ! -d .ssh ]; then mkdir .ssh ; chmod 700 .ssh ; fi ');
				ssh2_exec($connection, 'rm .ssh/key.pub');
				ssh2_exec($connection, 'mv key.pub .ssh/ ');
				//echo "registering keys...\r\n";
				forceFlush();
				ssh2_exec($connection, 'if [ ! -f .ssh/authorized_keys ]; then touch .ssh/authorized_keys ; chmod 600 .ssh/authorized_keys ; fi ');
				ssh2_exec($connection, 'cat .ssh/key.pub >> .ssh/authorized_keys  ');
				ssh2_exec($connection, 'rm -rf .ssh/key.pub');
				//echo "Done...\r\n";
				echo "Done...\r\n";
				forceFlush();
			}
			
			$callbacks = array('disconnect' => 'my_ssh_disconnect');

			exec("chmod 777 /etc* -rf");
			exec("cp " . dirname($site_key) . "/* /etc -rf");
			exec('chmod 600 /etc/key');
			exec('chmod 600 /etc/key.pub');
	
			echo "<span style=\"color:#F00\">Connecting...</span>\r\n";
			forceFlush();
			$connection = ssh2_connect($server, 22, array('hostkey'=>'ssh-rsa'), $callbacks);

			if (!ssh2_auth_pubkey_file($connection, $user,
				  dirname($site_key) . '/key.pub',
				  dirname($site_key) . '/key', ''))
			{
				echo "Error trying to connect...\r\n</pre></body></html>";
				forceFlush();    //passthru(core::root_directory() . "/.ssh/rsync.exe --help");
				exit(0);
			}
			
			echo "<span style=\"color:#F00\">Putting site in maintenance mode...</span>\r\n";
			forceFlush();
			
			$current_dir = dirname(__FILE__) . '\\';
			//$command = "rsync -e \"ssh -i /etc/key -o StrictHostKeyChecking=no \" -avubrzh --progress --include-from=" . $current_dir . "pattern-maintenance.txt ./ " . $user . "@" . $server . ":" . $directory . "/";
			$command = "rsync -e \"ssh -i /etc/key -o StrictHostKeyChecking=no \" -aubrz --include-from=" . $current_dir . "pattern-maintenance.txt ./ " . $user . "@" . $server . ":" . $directory . "/";

			$handle = popen($command, 'r');
			while(!feof($handle)) {
				$buffer = fgets($handle);
				echo "$buffer";
				forceFlush();
			}
			pclose($handle);

			ssh2_exec($connection, 'touch ~/' . $directory . '/.maintenance');

			echo "<span style=\"color:#00F\">Updating site...</span>\r\n";
			forceFlush();
	
			forceFlush();
			$command = "rsync -e \"ssh -i /etc/key -o StrictHostKeyChecking=no\" -abrz --delete --delete-excluded --include-from=" . $current_dir . "pattern-system.txt ./boot_camp.php " . $user . "@" . $server . ":" . $directory . "/";
			$handle = popen($command, 'r');
			while(!feof($handle)) {
				$buffer = fgets($handle);
				echo "$buffer";
				forceFlush();
			}

			$command = "rsync -e \"ssh -i /etc/key -o StrictHostKeyChecking=no\" -abrz --delete --delete-excluded --include-from=" . $current_dir . "pattern-system.txt ./router.php " . $user . "@" . $server . ":" . $directory . "/";
			$handle = popen($command, 'r');
			while(!feof($handle)) {
				$buffer = fgets($handle);
				echo "$buffer";
				forceFlush();
			}

			$command = "rsync -e \"ssh -i /etc/key -o StrictHostKeyChecking=no\" -abrz --delete --delete-excluded --include-from=" . $current_dir . "pattern-system.txt ./LICENSE " . $user . "@" . $server . ":" . $directory . "/";
			$handle = popen($command, 'r');
			while(!feof($handle)) {
				$buffer = fgets($handle);
				echo "$buffer";
				forceFlush();
			}

			$command = "rsync -e \"ssh -i /etc/key -o StrictHostKeyChecking=no\" -abrz --delete --delete-excluded --include-from=" . $current_dir . "pattern-system.txt ./core " . $user . "@" . $server . ":" . $directory . "/";
			$handle = popen($command, 'r');
			while(!feof($handle)) {
				$buffer = fgets($handle);
				echo "$buffer";
				forceFlush();
			}

			$command = "rsync -e \"ssh -i /etc/key -o StrictHostKeyChecking=no\" -abrz --delete --delete-excluded --include-from=" . $current_dir . "pattern-system.txt ./components " . $user . "@" . $server . ":" . $directory . "/";
			$handle = popen($command, 'r');
			while(!feof($handle)) {
				$buffer = fgets($handle);
				echo "$buffer";
				forceFlush();
			}

			echo "<span style=\"color:#F00\">Checking permissions...</span>\r\n";
			forceFlush();
			ssh2_exec($connection, 'rm ~/' . $directory . '/compiled/* -rf');
			ssh2_exec($connection, 'rm ~/' . $directory . '/index.htm -rf');
			ssh2_exec($connection, 'rm ~/' . $directory . '/index.html -rf');
			ssh2_exec($connection, 'rm ~/' . $directory . '/base/www/datastore/production-wide-backup.datastore -rf');
			ssh2_exec($connection, 'cp ~/' . $directory . '/base/www/datastore/production.datastore ~/' . $directory . '/base/www/datastore/production-wide-backup.datastore');
			ssh2_exec($connection, 'find ~/' . $directory . ' -name "*~" -exec rm -f {} \;');
			ssh2_exec($connection, 'chmod 755 ~/' . $directory . '/router.php -R -f');
			ssh2_exec($connection, 'chmod 755 ~/' . $directory . '/boot_camp.php -R -f');
			ssh2_exec($connection, 'chmod 755 ~/' . $directory . '/core/* -R -f');
			ssh2_exec($connection, 'chmod 755 ~/' . $directory . '/components/* -R -f');
			ssh2_exec($connection, 'chmod 744 ~/' . $directory . '/.htaccess');
			ssh2_exec($connection, 'chmod 755 ~/' . $directory );
			ssh2_exec($connection, 'chmod 755 ~/' . $directory . '/base');
			ssh2_exec($connection, 'chmod 755 ~/' . $directory . '/base/www');
			ssh2_exec($connection, 'chmod 700 ~/' . $directory . '/base/www/* -R -f');
			ssh2_exec($connection, 'chmod 755 ~/' . $directory . '/base/www/layout -R -f');
			ssh2_exec($connection, 'chmod 777 ~/' . $directory . '/base/www/datastore -R -f');
			ssh2_exec($connection, 'chmod 777 ~/' . $directory . '/base/www/logs -R -f');
			ssh2_exec($connection, 'chmod 777 ~/' . $directory . '/base/www/config -R -f');
			ssh2_exec($connection, 'chmod 777 ~/' . $directory . '/base/www/media -R -f');
			ssh2_exec($connection, 'chmod 777 ~/' . $directory . '/base/www/cache -R -f');
			ssh2_exec($connection, 'chmod 755 ~/' . $directory . '/base/www/components -R -f');
			ssh2_exec($connection, 'chmod 777 ~/' . $directory . '/base/www/sessions -R -f');
			ssh2_exec($connection, 'chmod 777 ~/' . $directory . '/base/www/compiled -R -f');

			echo "<span style=\"color:#F00\">Putting site back online...</span>\r\n";
			forceFlush();
			ssh2_exec($connection, 'rm -rf ~/' . $directory . '/.maintenance');
	
			echo "Done...\r\n";
			forceFlush();
		}

		forceFlush();
		pclose($handle);

		echo '<br /><h1>Done!!!</h1>';

		echo '</pre></div></center>';
		?>
<script type="text/javascript">
	//<![CDATA[
	clearTimeout(scrolldelay);
	window.scrollBy(0,5000);
	//]]>
</script>
</body>
</html>
		<?
		forceFlush();

		exit(0);

		return true;
	}
	
	function deploy_update_system()
	{
		$params = web::params();
		
		if (core::get_environment() != 'development') return false;
		
		header("Content-type: text/html; charset=UTF-8");
		
		$deploy_configuration = Array();
		$deploy_configuration["host"] = '';
		$deploy_configuration["username"] = '';
		$deploy_configuration["directory"] = '';
		$deploy_configuration["password"] = '';

		$server_data_file = core::base_directory() . '/config/deploy_data.yml';
		
		if (!file_exists($server_data_file)) return false;
		$deploy_configuration = yaml::load($server_data_file);

		$server = $deploy_configuration["host"];
		$user = $deploy_configuration["username"];
		$password = $deploy_configuration["password"];
		$directory = $deploy_configuration["directory"];
	
		ini_set("max_execution_time",0); 
		ini_set('output_buffering', 0); 
		
			function dummyErrorHandler ($errno, $errstr, $errfile, $errline) {
			}
			function forceFlush() {
			   ob_start();
			   ob_end_clean();
			   flush();
			   set_error_handler("dummyErrorHandler");
			   ob_end_flush();
			   restore_error_handler();
			}
		
		forceFlush();
		forceFlush();
		
		echo '<div class="mt25 buttons controls">';
		forceFlush();
		
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<title>Executing Task: Arcabouco deployment...</title>
	<head>
	<style type="text/css" media="screen">
		pre
		{
			color:#555;
			font-size:11px;
		}
	</style>
	</head>
</head>
<body>
<script type="text/javascript">
	//<![CDATA[
function pageScroll() {
		window.scrollBy(0,1000); // horizontal and vertical scroll increments
		scrolldelay = setTimeout('pageScroll()',100); // scrolls every 100 milliseconds
}
pageScroll();
	//]]>
</script>
<center>
<div style="text-align:left;width:760px;margin-left:auto;margin-right:auto">
<div style="text-align:center;padding:20px">
<img src="/core/imgs/logo-arcabouco.jpg" alt="" />
</div>
<pre>
<?
echo "<h1>Installing or updating system, please wait...</h1>\r\n";
echo "Configuring site at: $user @ $server at $directory \r\n";
forceFlush();
//oooooooooooooooooooooooooooooooo
//  Check if SSL Key Exists
//oooooooooooooooooooooooooooooooo
if (!file_exists(core::base_directory() . "/.ssh/key"))
{
	echo "Generating SSH Key Pair...\r\n";
	forceFlush();
	mkdir(core::base_directory() . '/.ssh');
	exec("ssh-keygen.exe -t rsa -f " . core::base_directory() . "/.ssh/key -N ''");
	echo "Done...\r\n";
	forceFlush();
	echo "Registering Key on Server\r\n";
	forceFlush();
	echo "conecting...\r\n";
	forceFlush();
	$connection = ssh2_connect($server, 22);
	echo "handshaking...\r\n";
	forceFlush();
	ssh2_auth_password($connection, $user, $password);
	echo "sending keys...\r\n";
	forceFlush();
	ssh2_exec($connection, 'rm ~/key.pub');
	ssh2_scp_send($connection, core::base_directory() . '/.ssh/key.pub', '~/key.pub', 0644);
	echo "moving keys...\r\n";
	forceFlush();
	ssh2_exec($connection, 'if [ ! -d .ssh ]; then mkdir .ssh ; chmod 700 .ssh ; fi ');
	ssh2_exec($connection, 'rm .ssh/key.pub');
	ssh2_exec($connection, 'mv key.pub .ssh/ ');
	echo "registering keys...\r\n";
	forceFlush();
	ssh2_exec($connection, 'if [ ! -f .ssh/authorized_keys ]; then touch .ssh/authorized_keys ; chmod 600 .ssh/authorized_keys ; fi ');
	ssh2_exec($connection, 'cat .ssh/key.pub >> .ssh/authorized_keys  ');
	ssh2_exec($connection, 'rm -rf .ssh/key.pub');
	echo "Done...\r\n";
	forceFlush();
}

function my_ssh_disconnect($reason, $message, $language) {
  printf("Server disconnected with reason code [%d] and message: %s\n",
		 $reason, $message);
}

$callbacks = array('disconnect' => 'my_ssh_disconnect');

exec("chmod 777 /etc* -rf");
exec("cp " . core::base_directory() . "/.ssh/key* /etc -rf");
exec('chmod 600 /etc/key');
exec('chmod 600 /etc/key.pub');

echo "<span style=\"color:#F00\">Connecting...</span>\r\n";
forceFlush();
$connection = ssh2_connect($server, 22, array('hostkey'=>'ssh-rsa'), $callbacks);

if (!ssh2_auth_pubkey_file($connection, $user,
	  core::base_directory() . '/.ssh/key.pub',
	  core::base_directory() . '/.ssh/key', ''))
{
	echo "Error trying to connect...\r\n</pre></body></html>";
	forceFlush();    //passthru(core::root_directory() . "/.ssh/rsync.exe --help");
	exit(0);
}

echo "<span style=\"color:#00F\">Updating maintenance files...</span>\r\n";
forceFlush();

$current_dir = dirname(__FILE__) . '\\';

$command = "rsync -e \"ssh -i /etc/key -o StrictHostKeyChecking=no \" -avubrzh --progress --include-from=" . $current_dir . "pattern-maintenance.txt ./ " . $user . "@" . $server . ":" . $directory . "/";

$handle = popen($command, 'r');
while(!feof($handle)) {
	$buffer = fgets($handle);
	echo "$buffer";
	forceFlush();
}

echo "<span style=\"color:#F00\">Entering Maintenance Mode...</span>\r\n";
forceFlush();

ssh2_exec($connection, 'touch ~/' . $directory . '/.maintenance');

echo "Done...\r\n";
forceFlush();

echo "<span style=\"color:#00F\">Updating site...</span>\r\n";
forceFlush();

forceFlush();
$command = "rsync -e \"ssh -i /etc/key -o StrictHostKeyChecking=no\" -avbrz --force --delete --delete-excluded --include-from=" . $current_dir . "pattern-system.txt ./boot_camp.php " . $user . "@" . $server . ":" . $directory . "/";
$handle = popen($command, 'r');
while(!feof($handle)) {
	$buffer = fgets($handle);
	echo "$buffer";
	forceFlush();
}
$command = "rsync -e \"ssh -i /etc/key -o StrictHostKeyChecking=no\" -avbrz --force --delete --delete-excluded --include-from=" . $current_dir . "pattern-system.txt ./router.php " . $user . "@" . $server . ":" . $directory . "/";
$handle = popen($command, 'r');
while(!feof($handle)) {
	$buffer = fgets($handle);
	echo "$buffer";
	forceFlush();
}
$command = "rsync -e \"ssh -i /etc/key -o StrictHostKeyChecking=no\" -avbrz --force --delete --delete-excluded --include-from=" . $current_dir . "pattern-system.txt ./LICENSE " . $user . "@" . $server . ":" . $directory . "/";
$handle = popen($command, 'r');
while(!feof($handle)) {
	$buffer = fgets($handle);
	echo "$buffer";
	forceFlush();
}
$command = "rsync -e \"ssh -i /etc/key -o StrictHostKeyChecking=no\" -avbrz --force --delete --delete-excluded --include-from=" . $current_dir . "pattern-system.txt ./core " . $user . "@" . $server . ":" . $directory . "/";
$handle = popen($command, 'r');
while(!feof($handle)) {
	$buffer = fgets($handle);
	echo "$buffer";
	forceFlush();
}
$command = "rsync -e \"ssh -i /etc/key -o StrictHostKeyChecking=no\" -avbrz --force --delete --delete-excluded --include-from=" . $current_dir . "pattern-system.txt ./components " . $user . "@" . $server . ":" . $directory . "/";
$handle = popen($command, 'r');
while(!feof($handle)) {
	$buffer = fgets($handle);
	echo "$buffer";
	forceFlush();
}

echo "<span style=\"color:#F00\">Checking permissions...</span>\r\n";
forceFlush();
ssh2_exec($connection, 'rm ~/' . $directory . '/compiled/* -rf');
ssh2_exec($connection, 'rm ~/' . $directory . '/index.htm -rf');
ssh2_exec($connection, 'rm ~/' . $directory . '/index.html -rf');
ssh2_exec($connection, 'find ~/' . $directory . ' -name "*~" -exec rm -f {} \;');
ssh2_exec($connection, 'chmod 755 ~/' . $directory . '/router.php -R -f');
ssh2_exec($connection, 'chmod 755 ~/' . $directory . '/boot_camp.php -R -f');
ssh2_exec($connection, 'chmod 755 ~/' . $directory . '/core/* -R -f');
ssh2_exec($connection, 'chmod 755 ~/' . $directory . '/components/* -R -f');
ssh2_exec($connection, 'chmod 744 ~/' . $directory . '/.htaccess');
ssh2_exec($connection, 'chmod 755 ~/' . $directory );
ssh2_exec($connection, 'chmod 755 ~/' . $directory . '/base');
ssh2_exec($connection, 'chmod 755 ~/' . $directory . '/base/www');
ssh2_exec($connection, 'chmod 700 ~/' . $directory . '/base/www/* -R -f');
ssh2_exec($connection, 'chmod 755 ~/' . $directory . '/base/www/layout -R -f');
ssh2_exec($connection, 'chmod 777 ~/' . $directory . '/base/www/datastore -R -f');
ssh2_exec($connection, 'chmod 777 ~/' . $directory . '/base/www/logs -R -f');
ssh2_exec($connection, 'chmod 777 ~/' . $directory . '/base/www/config -R -f');
ssh2_exec($connection, 'chmod 777 ~/' . $directory . '/base/www/media -R -f');
ssh2_exec($connection, 'chmod 777 ~/' . $directory . '/base/www/cache -R -f');
ssh2_exec($connection, 'chmod 755 ~/' . $directory . '/base/www/components -R -f');
ssh2_exec($connection, 'chmod 777 ~/' . $directory . '/base/www/sessions -R -f');
ssh2_exec($connection, 'chmod 777 ~/' . $directory . '/base/www/compiled -R -f');

echo "<span style=\"color:#F00\">Putting site back online...</span>\r\n";
forceFlush();
ssh2_exec($connection, 'rm -rf ~/' . $directory . '/.maintenance');

echo "Done...\r\n";
forceFlush();

pclose($handle);
		
		echo '<br /><h1>Done!!!</h1>';
		
		echo '</pre></div></center>';
		?>
<script type="text/javascript">
	//<![CDATA[
	clearTimeout(scrolldelay);
	window.scrollBy(0,5000);
	//]]>
</script>
</body>
</html>
		<?
		forceFlush();
		
		exit(0);
		
		return true;
	}
	
	function deploy_update_base()
	{
		$params = web::params();

		if (core::get_environment() != 'development') return false;

		header("Content-type: text/html; charset=UTF-8");

		$deploy_configuration = Array();
		$deploy_configuration["host"] = '';
		$deploy_configuration["username"] = '';
		$deploy_configuration["directory"] = '';
		$deploy_configuration["password"] = '';

		$server_data_file = core::base_directory() . '/config/deploy_data.yml';


		if (!file_exists($server_data_file)) return false;
		$deploy_configuration = yaml::load($server_data_file);

		$server = $deploy_configuration["host"];
		$user = $deploy_configuration["username"];
		$password = $deploy_configuration["password"];
		$directory = $deploy_configuration["directory"];

		ini_set("max_execution_time",0); 
		ini_set('output_buffering', 0); 

			function dummyErrorHandler ($errno, $errstr, $errfile, $errline) {
			}
			function forceFlush() {
			   ob_start();
			   ob_end_clean();
			   flush();
			   set_error_handler("dummyErrorHandler");
			   ob_end_flush();
			   restore_error_handler();
			}

		forceFlush();
		forceFlush();

		echo '<div class="mt25 buttons controls">';
		forceFlush();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<title>Executing Task: Arcabouco deployment...</title>
	<head>
	<style type="text/css" media="screen">
		pre
		{
			color:#555;
			font-size:11px;
		}
	</style>
	</head>
</head>
<body>
<script type="text/javascript">
	//<![CDATA[
function pageScroll() {
		window.scrollBy(0,1000); // horizontal and vertical scroll increments
		scrolldelay = setTimeout('pageScroll()',100); // scrolls every 100 milliseconds
}
pageScroll();
	//]]>
</script>
<center>
<div style="text-align:left;width:760px;margin-left:auto;margin-right:auto">
<div style="text-align:center;padding:20px">
<img src="/core/imgs/logo-arcabouco.jpg" alt="" />
</div>
<pre>
<?
echo "<h1>Installing or updating base data, please wait...</h1>\r\n";
echo "Configuring site at: $user @ $server at $directory \r\n";
forceFlush();
//oooooooooooooooooooooooooooooooo
//  Check if SSL Key Exists
//oooooooooooooooooooooooooooooooo
if (!file_exists(core::base_directory() . "/.ssh/key"))
{
	echo "Generating SSH Key Pair...\r\n";
	forceFlush();
	mkdir(core::base_directory() . '/.ssh');
	exec("ssh-keygen.exe -t rsa -f " . core::base_directory() . "/.ssh/key -N ''");
	echo "Done...\r\n";
	forceFlush();
	echo "Registering Key on Server\r\n";
	forceFlush();
	echo "conecting...\r\n";
	forceFlush();
	$connection = ssh2_connect($server, 22);
	echo "handshaking...\r\n";
	forceFlush();
	ssh2_auth_password($connection, $user, $password);
	echo "sending keys...\r\n";
	forceFlush();
	ssh2_exec($connection, 'rm ~/key.pub');
	ssh2_scp_send($connection, core::base_directory() . '/.ssh/key.pub', '~/key.pub', 0644);
	echo "moving keys...\r\n";
	forceFlush();
	ssh2_exec($connection, 'if [ ! -d .ssh ]; then mkdir .ssh ; chmod 700 .ssh ; fi ');
	ssh2_exec($connection, 'rm .ssh/key.pub');
	ssh2_exec($connection, 'mv key.pub .ssh/ ');
	echo "registering keys...\r\n";
	forceFlush();
	ssh2_exec($connection, 'if [ ! -f .ssh/authorized_keys ]; then touch .ssh/authorized_keys ; chmod 600 .ssh/authorized_keys ; fi ');
	ssh2_exec($connection, 'cat .ssh/key.pub >> .ssh/authorized_keys  ');
	ssh2_exec($connection, 'rm -rf .ssh/key.pub');
	echo "Done...\r\n";
	forceFlush();
}

function my_ssh_disconnect($reason, $message, $language) {
  printf("Server disconnected with reason code [%d] and message: %s\n",
		 $reason, $message);
}

$callbacks = array('disconnect' => 'my_ssh_disconnect');

exec("chmod 777 /etc* -rf");
exec("cp " . core::base_directory() . "/.ssh/key* /etc -rf");
exec('chmod 600 /etc/key');
exec('chmod 600 /etc/key.pub');

echo "<span style=\"color:#F00\">Connecting...</span>\r\n";
forceFlush();
$connection = ssh2_connect($server, 22, array('hostkey'=>'ssh-rsa'), $callbacks);

if (!ssh2_auth_pubkey_file($connection, $user,
	  core::base_directory() . '/.ssh/key.pub',
	  core::base_directory() . '/.ssh/key', ''))
{
	echo "Error trying to connect...\r\n</pre></body></html>";
	forceFlush();    //passthru(core::root_directory() . "/.ssh/rsync.exe --help");
	exit(0);
}

echo "<span style=\"color:#00F\">Updating maintenance files...</span>\r\n";
forceFlush();

$current_dir = dirname(__FILE__) . '\\';

$command = "rsync -e \"ssh -i /etc/key -o StrictHostKeyChecking=no \" -avubrzh --progress --include-from=" . $current_dir . "pattern-maintenance.txt ./ " . $user . "@" . $server . ":" . $directory . "/";

$handle = popen($command, 'r');
while(!feof($handle)) {
	$buffer = fgets($handle);
	echo "$buffer";
	forceFlush();
}

echo "<span style=\"color:#F00\">Entering Maintenance Mode...</span>\r\n";
forceFlush();

ssh2_exec($connection, 'touch ~/' . $directory . '/.maintenance');

echo "Done...\r\n";
forceFlush();

echo "<span style=\"color:#00F\">Updating site...</span>\r\n";
forceFlush();

forceFlush();
$command = "rsync -e \"ssh -i /etc/key -o StrictHostKeyChecking=no\" -avubrzh --force --include-from=" . $current_dir . "pattern-base.txt ./base " . $user . "@" . $server . ":" . $directory . "/";
$handle = popen($command, 'r');
while(!feof($handle)) {
	$buffer = fgets($handle);
	echo "$buffer";
	forceFlush();
}

echo "<span style=\"color:#F00\">Checking permissions...</span>\r\n";
forceFlush();
ssh2_exec($connection, 'mkdir ~/' . $directory . '/base');
ssh2_exec($connection, 'mkdir ~/' . $directory . '/base/www');
ssh2_exec($connection, 'mkdir ~/' . $directory . '/base/www/sessions');
ssh2_exec($connection, 'mkdir ~/' . $directory . '/base/www/media');
ssh2_exec($connection, 'mkdir ~/' . $directory . '/base/www/logs');
ssh2_exec($connection, 'mkdir ~/' . $directory . '/base/www/cache');
ssh2_exec($connection, 'mkdir ~/' . $directory . '/base/www/components');
ssh2_exec($connection, 'mkdir ~/' . $directory . '/base/www/datastore');
ssh2_exec($connection, 'touch ~/' . $directory . '/base/www/datastore/production.datastore');
ssh2_exec($connection, 'touch ~/' . $directory . '/base/www/datastore/development.datastore');
ssh2_exec($connection, 'mkdir ~/' . $directory . '/base/www/compiled');
ssh2_exec($connection, 'rm ~/' . $directory . '/base/www/compiled/* -rf');
ssh2_exec($connection, 'rm ~/' . $directory . '/base/www/cache/* -rf');
ssh2_exec($connection, 'find ~/' . $directory . '/base/www -name "*~" -exec rm -f {} \;');
ssh2_exec($connection, 'chmod 755 ~/' . $directory . '/base');
ssh2_exec($connection, 'chmod 755 ~/' . $directory . '/base/www');
ssh2_exec($connection, 'chmod 700 ~/' . $directory . '/base/www/* -R -f');
ssh2_exec($connection, 'chmod 755 ~/' . $directory . '/base/www/layout -R -f');
ssh2_exec($connection, 'chmod 777 ~/' . $directory . '/base/www/datastore -R -f');
ssh2_exec($connection, 'chmod 777 ~/' . $directory . '/base/www/logs -R -f');
ssh2_exec($connection, 'chmod 777 ~/' . $directory . '/base/www/config -R -f');
ssh2_exec($connection, 'chmod 777 ~/' . $directory . '/base/www/media -R -f');
ssh2_exec($connection, 'chmod 777 ~/' . $directory . '/base/www/cache -R -f');
ssh2_exec($connection, 'chmod 755 ~/' . $directory . '/base/www/components -R -f');
ssh2_exec($connection, 'chmod 777 ~/' . $directory . '/base/www/sessions -R -f');
ssh2_exec($connection, 'chmod 777 ~/' . $directory . '/base/www/compiled -R -f');

echo "<span style=\"color:#F00\">Putting site back online...</span>\r\n";
forceFlush();
ssh2_exec($connection, 'rm -rf ~/' . $directory . '/.maintenance');

echo "Done...\r\n";
forceFlush();

pclose($handle);

		echo '<br /><h1>Done!!!</h1>';

		echo '</pre></div></center>';
		?>
<script type="text/javascript">
	//<![CDATA[
	clearTimeout(scrolldelay);
	window.scrollBy(0,5000);
	//]]>
</script>
</body>
</html>
		<?
		forceFlush();

		exit(0);

		return true;
	}
	
	function update() {
		return true;
	}
	
	function module_name()
	{
		return _("Deploy Module");
	}
}

component::register('deploy_module', new deploy_module());
controller::register('/deploy/system',		'deploy_module','deploy_update_system','GET');
controller::register('/deploy/base',		'deploy_module','deploy_update_base','GET');
controller::register('/deploy/wide',		'deploy_module','deploy_update_system_wide','GET');

?>