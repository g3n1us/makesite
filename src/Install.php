<?php

namespace G3n1us\Makesite;

class Install{

	public function __construct(){

	}

	public static function check(){
		$cli = @exec('valet -V 2> /dev/null');
		if(empty($cli)){
			say('Installing Valet...');
			exec('composer global require laravel/valet');
		}

		// look for config file
		if(!file_exists(getenv('HOME')."/.config/makesite/config.json")){
			@mkdir(getenv('HOME')."/.config/makesite");
			copy(dirname(__DIR__)."/config.json.example", getenv('HOME')."/.config/makesite/config.json");
		}

		if(!Command::config('publicdns')){
			Command::setTld();
		}

		$root = Command::getRoot();

		if(!is_dir($root)){
			@mkdir($root, 0755, true);
			exec("cd \"$root\" && valet park");
		}

		if(!file_exists(getenv('HOME')."/.config/valet/Drivers/ApacheValetDriver.php")){
			copy(dirname(__DIR__)."/ApacheValetDriver.php", getenv('HOME')."/.config/valet/Drivers/ApacheValetDriver.php");
		}

	}

}
