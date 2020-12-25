<?php

namespace G3n1us\Makesite;

class Install{

	public function __construct(){

	}

	public static function check(){
		$HOME = getenv('HOME');
		say("Installing Valet...");
		$cli = @exec('valet install');	
		@exec('valet trust');	
	
		// look for config file
		if(!file_exists("$HOME/.config/makesite/config.json")){
			@mkdir("$HOME/.config/makesite");
			copy(dirname(__DIR__)."/config.json.example", "$HOME/.config/makesite/config.json");
		}

		if(!Command::config('publicdns')){
			Command::setTld();
		}

		$root = Command::getRoot();

		if(!is_dir($root)){
			@mkdir($root, 0755, true);
			exec("cd \"$root\" && valet park");
		}

		if(!file_exists("$HOME/.config/valet/Drivers/ApacheValetDriver.php")){
			copy(dirname(__DIR__)."/ApacheValetDriver.php", "$HOME/.config/valet/Drivers/ApacheValetDriver.php");
		}

	}

}
