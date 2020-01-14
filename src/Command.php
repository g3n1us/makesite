<?php

namespace G3n1us\Makesite;

class Command{


	private static $stdin;

	public function __construct(){

	}

	public static function run($argv){
		Install::check();

		if(count($argv) > 2) self::error('Too many arguments!');

		$subdomain = (count($argv) === 2) ? $argv[1] : false;

		// See if asking for help...
		if(in_array($subdomain, ['-h', '--help'])) die(say(self::$helptext, "WARNING"));

		if(in_array($subdomain, ['tld', 'TLD'])) die(self::setTld());




		if($subdomain == false) {

			$subdomain = self::ask("Specify the subdomain. This will be prefixed to: " . self::config('publicdns'));
		}

		if(empty($subdomain)) {
			self::error('You must specify a subdomain!');
		}

		self::save($subdomain);
	}

	public static function getStdin(){
		if(!self::$stdin){
			self::$stdin = fopen('php://stdin', 'r');
		}
		return self::$stdin;
	}

	public static function ask($question){
		say($question);
		echo "> ";
		$stdin = self::getStdin();
		return clean(fgets($stdin));
	}

	public static function config($key = null, $value = null){
		$configfile = getenv('HOME')."/.config/makesite/config.json";
		$config = json_decode(file_get_contents($configfile), true);
		if($key && !empty($value)){
			$config[$key] = $value;
			file_put_contents($configfile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
			return self::config($key) == $value;
		}
		if($key && array_key_exists($key, $config)){
			return $config[$key];
		}
		return $config;
	}


	public static function getTld(){
		return self::ask("What would you like your TLD to be?");
	}


	public static function setTld(){
		$tld = self::getTld();
		self::config('publicdns', $tld);
		exec("valet tld $tld");
	}


	public static function getRoot(){
		$root = self::config('allsitesroot');
		if($root[0] === '~'){
			$root = ltrim($root, '~');

			$root = getenv('HOME') . $root;
		}
		return $root;
	}


	public static function getServerName($subdomain){
		return "$subdomain." . self::config('publicdns');
	}


	public static function error($message = null){
		say($message . PHP_EOL, "FAILURE");
		die(say('exiting' . PHP_EOL, "FAILURE"));
	}


	private static function save($subdomain){
		$root = self::getRoot();
		$siteroot = "$root/$subdomain";
		if(is_dir($siteroot)){
			self::error("The site already exists!");
		}
		@mkdir("$siteroot/public_html", 0755, true);
		file_put_contents("$siteroot/public_html/index.html", self::tpl(self::$startercontent, ['servername' => $subdomain]));
		$configroot = self::config('configroot');
		$username = get_current_user();
		$hostname = self::getServerName($subdomain);
		exec("sudo touch $configroot/$hostname.conf && sudo chown $username $configroot/$hostname.conf");
		$hostcontents = self::tpl(self::$apache_template, [
			'siteroot' => $siteroot,
			'servername' => $hostname,
		]);
		file_put_contents("$configroot/$hostname.conf", $hostcontents);
		exec("ln -s $configroot/$hostname.conf $siteroot/site.conf");

		exec("open http://$hostname");
	}


	private static function tpl($tpl, $vars = []){
		$output = $tpl;
		foreach($vars as $key => $value){
			$output = str_replace("{{" . $key . "}}", $value, $output);
		}
		return $output;
	}


	private static $helptext = "
Usage: makesite [sitename]

if not specified, interactive mode will ask for sitename. This should be the subdomain that will be prefixed to the base url.

";

	private static $apache_template = '<VirtualHost *:80>

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

	private static $startercontent = '<body style="background-color:black;color:white;font-family:monospace;text-align:center; margin:0;padding:0">
	<svg style="height: 100vh;width: 100vw;text-align: center;">
		<text style="fill: white;font-size: 50vh;width: 100%;" dominant-baseline="middle" y="25%">Hello</text>
		<text style="fill: white;font-size: 20vh;width: 100%;" dominant-baseline="middle" y="50%">{{servername}}</text>
	</svg>
</body>';


}
