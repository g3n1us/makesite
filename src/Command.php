<?php

namespace G3n1us\Makesite;

class Command{

	private static $instance;

	private static $stdin;

	public function __construct(){
		if(static::$instance) throw new \Exception("One instance can exist at at time.");
		static::$instance = $this;
	}

	public function __destruct(){
		fclose(static::getStdin());
	}

	public static function __callStatic($name, $arguments){
		if(!static::$instance) new self;
		return call_user_func_array([static::$instance, "_$name"], $arguments);
	}

	public function __call($name, $arguments){
		if(!static::$instance) new self;
		return call_user_func_array([static::$instance, "_$name"], $arguments);
	}

	public function _run($argv){
		Install::check();
		array_shift($argv);
		$flags = [];
		if(count($argv) > 1) {
			$subdomain = array_pop($argv);
			$flags = $argv;
		}
		else{
			$subdomain = (count($argv) === 1) ? $argv[0] : false;
		}

		// See if asking for help...
		if(!!array_intersect($flags, ['-h', '--help'])) die(say($this->helptext, "WARNING"));

		if(!!array_intersect($flags, ['tld', 'TLD'])) die($this->setTld());

		if(!!array_intersect($flags, ['-d', '--delete'])) {
			$this->delete($subdomain);
			return;
		}


		if($subdomain == false) {

			$subdomain = $this->ask("Specify the subdomain. This will be prefixed to: " . $this->config('publicdns'));
		}

		if(empty($subdomain)) {
			$this->error('You must specify a subdomain!');
		}

		$this->save($subdomain);
	}


	public function _getStdin(){
		if(!static::$stdin){
			static::$stdin = fopen('php://stdin', 'r');
		}
		return static::$stdin;
	}

	public function _ask($question){
		say($question);
		echo "> ";
		$stdin = $this->getStdin();
		return clean(fgets($stdin));
	}

	public function _config($key = null, $value = null){
		$configfile = getenv('HOME')."/.config/makesite/config.json";
		$config = json_decode(file_get_contents($configfile), true);
		if($key && !empty($value)){
			$config[$key] = $value;
			file_put_contents($configfile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
			return $this->config($key) == $value;
		}
		if($key && array_key_exists($key, $config)){
			return $config[$key];
		}
		return $config;
	}


	public function _getTld(){
		say("One or more configuration steps need to be set.");
		say("The TLD is the main domain name that your sites will respond to. For example: if your TLD is google.com, all of your sites will be prefixed to this, such as mynewsite.google.com.");
		say("You should choose something that is not a real domain name, otherwise you won't be able to access the real version of that domain.");
		return $this->ask("What would you like your TLD to be?");
	}


	public function _setTld(){
		$tld = $this->getTld();
		$this->config('publicdns', $tld);
		exec("valet tld $tld");
	}


	public function _getRoot(){
		$root = $this->config('allsitesroot');
		if($root[0] === '~'){
			$root = ltrim($root, '~');

			$root = getenv('HOME') . $root;
		}
		return $root;
	}


	public function _getServerName($subdomain){
		return "$subdomain." . $this->config('publicdns');
	}


	public function _error($message = null){
		say($message . PHP_EOL, "FAILURE");
		die(say('exiting' . PHP_EOL, "FAILURE"));
	}


	public function _confirm($question = "Are you sure?"){
		$answer = $this->ask($question . " [y/n]");
		if(!in_array(strtolower($answer), ['y', 'yes'])){
			exit(say('exiting' . PHP_EOL));
		}
	}

	private function _save($subdomain){
		$root = $this->getRoot();
		$siteroot = "$root/$subdomain";
		if(is_dir($siteroot)){
			$this->error("The site already exists!");
		}
		$hostname = $this->getServerName($subdomain);

		$this->confirm("$hostname will be created. Continue?");
		@mkdir("$siteroot/public_html", 0755, true);
		file_put_contents("$siteroot/public_html/index.html", $this->tpl($this->startercontent, ['servername' => $subdomain]));
// 		$configroot = $this->config('configroot');
		$username = get_current_user();
// 		exec("sudo touch $configroot/$hostname.conf && sudo chown $username $configroot/$hostname.conf");
		$hostcontents = $this->tpl($this->apache_template, [
			'siteroot' => $siteroot,
			'servername' => $hostname,
		]);
		file_put_contents("$siteroot/site.conf", $hostcontents);
// 		file_put_contents("$configroot/$hostname.conf", $hostcontents);
// 		exec("ln -s $configroot/$hostname.conf $siteroot/site.conf");

		// sudo virtualmin create-domain --domain data-commander.dev.also-too.com --parent dev.also-too.com  --web --ssl --mysql --dir --letsencrypt

		exec("valet secure $subdomain");

		say("$hostname has been created.", "SUCCESS");

		exec("open https://$hostname");
	}


	private function _delete($subdomain){
		$hostname = $this->getServerName($subdomain);
		$this->confirm("$hostname will be permanently DELETED. Continue?");

		$root = $this->getRoot();
		$siteroot = "$root/$subdomain";
		if(!is_dir($siteroot)){
			$this->error("The site doesn't exist!");
		}
		$trash_path = getenv('HOME') . "/.Trash";
		if(is_dir("$trash_path/$subdomain")) rename($siteroot, "$trash_path/$subdomain-" . time());
		else rename($siteroot, "$trash_path/$subdomain");


// 		$configroot = $this->config('configroot');

/*
		if(file_exists("$trash_path/$hostname.conf")) rename("$configroot/$hostname.conf", "$trash_path/$hostname.conf-" . time());
		else rename("$configroot/$hostname.conf", "$trash_path/$hostname.conf");
*/

		exec("valet unsecure $subdomain");
		say("$hostname has been deleted.", "SUCCESS");
	}


	private function _tpl($tpl, $vars = []){
		$output = $tpl;
		foreach($vars as $key => $value){
			$output = str_replace("{{" . $key . "}}", $value, $output);
		}
		return $output;
	}


	private $helptext = "
Usage: makesite [sitename]

if not specified, interactive mode will ask for sitename. This should be the subdomain that will be prefixed to the base url.

";

	private $apache_template = '<VirtualHost *:80>

ServerName {{servername}}:80
DocumentRoot {{siteroot}}/public_html
ErrorLog /private/var/log/apache2/{{servername}}_error_log
CustomLog /private/var/log/apache2/{{servername}}_access_log combined
ServerAdmin webmaster@localhost
DirectoryIndex index.html index.php index.htm

<Directory "{{siteroot}}/public_html">
		Options -Indexes
		AllowOverride All
		allow from all
		Require all granted
</Directory>

</VirtualHost>

<VirtualHost *:443>

ServerName {{servername}}:443
DocumentRoot {{siteroot}}/public_html
ErrorLog /private/var/log/apache2/{{servername}}_error_log
CustomLog /private/var/log/apache2/{{servername}}_access_log combined
ServerAdmin webmaster@localhost
DirectoryIndex index.html index.php index.htm

<Directory "{{siteroot}}/public_html">
		Options -Indexes
		AllowOverride All
		allow from all
		Require all granted
</Directory>

SSLCertificateFile "/usr/local/etc/httpd/server.crt"
SSLCertificateKeyFile "/usr/local/etc/httpd/server.key"

</VirtualHost>
';

	private $startercontent = '<body style="background-color:black;color:white;font-family:monospace;text-align:center; margin:0;padding:0">
	<svg style="height: 100vh;width: 100vw;text-align: center;">
		<text style="fill: white;font-size: 50vh;width: 100%;" dominant-baseline="middle" y="25%">Hello</text>
		<text style="fill: white;font-size: 20vh;width: 100%;" dominant-baseline="middle" y="50%">{{servername}}</text>
	</svg>
</body>';


}
