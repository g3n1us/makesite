<?php




function colorize($text, $status = 'NOTE') {
 $out = "";
 switch($status) {
  case "SUCCESS":
   $out = "[32m"; //Green
   break;
  case "FAILURE":
   $out = "[31m"; //Red
   break;
  case "WARNING":
   $out = "[33m"; //Yellow
   break;
  case "NOTE":
   $out = "[36m"; //Blue
   break;
  default:
   throw new Exception("Invalid status: " . $status);
 }
 return chr(27) . "$out" . "$text" . chr(27) . "[0m" . PHP_EOL;
}

function say($text, $status = 'NOTE'){
	echo(colorize($text, $status));
}

function clean($input){
	return trim(preg_replace('/[^a-z0-9\-\.]/', '', strtolower(trim($input))), '-');
}

