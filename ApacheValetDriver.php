<?php

namespace Valet\Drivers;

class ApacheValetDriver extends BasicValetDriver
{

    /**
     * Determine if the driver serves the request.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return bool
     */
    public function serves($sitePath, $siteName, $uri)
    {
        if (file_exists("$sitePath/site.conf")) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the incoming request is for a static file.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return string|false
     */
    public function isStaticFile($sitePath, $siteName, $uri)
    {
        return parent::isStaticFile($this->getDocumentRoot($sitePath), $siteName, $uri);
    }


    private function getDocumentRoot($sitePath){
        $apache_config = file_get_contents("$sitePath/site.conf");
        $matched = preg_match("/DocumentRoot (.*?)$/m", $apache_config, $matches);
        if($matched){
            return trim($matches[1]);
        }
        return false;
    }

    private function getDirectoryIndexes($sitePath){
        $apache_config = file_get_contents("$sitePath/site.conf");
        $indexfiles_matched = preg_match("/DirectoryIndex (.*?)$/m", $apache_config, $matches2);
        if($indexfiles_matched){
            $indexes = explode(' ', $matches2[1]);
        }
        else $indexes = ['index.php', 'index.html'];
    }

    /**
     * Get the fully resolved path to the application's front controller.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return string
     */
    public function frontControllerPath($sitePath, $siteName, $uri)
    {
        return parent::frontControllerPath($this->getDocumentRoot($sitePath), $siteName, $uri);
    }

    private function trim_uri($uri){
        return '/' . trim($uri, '/');
    }

}

if(!function_exists('dmp')){
    function dmp($var){
        die(var_dump($var));
    }
}
