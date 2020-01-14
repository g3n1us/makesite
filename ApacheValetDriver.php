<?php
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
/*
        dmp($a);

        $apache_config = file_get_contents("$sitePath/site.conf");
        $matched = preg_match("/DocumentRoot (.*?)$/m", $apache_config, $matches);
        if($matched){
            $frontPath = rtrim($matches[1]);
            $matches = [];

            if (preg_match('/^\/(.*?)\.php/', $uri, $matches)) {
                $filename = $matches[0];
                dmp($sitePath.$filename);
                if (file_exists($sitePath.$filename) && ! is_dir($sitePath.$filename)) {
                    $_SERVER['SCRIPT_FILENAME'] = $sitePath.$filename;
                    $_SERVER['SCRIPT_NAME'] = $filename;
                    return $sitePath.$filename;
                }
            }

// return '/srv/ehris.seanbethel.com/public_html/embed/dashboard.php';
dmp($frontPath.$uri);

            $indexfiles_matched = preg_match("/DirectoryIndex (.*?)$/m", $apache_config, $matches2);
            if($indexfiles_matched){
                $indexes = explode(' ', $matches2[1]);
            }
            else $indexes = ['index.php', 'index.html'];

            foreach($indexes as $index){
                if(file_exists("$frontPath/$index")){
                    return "$frontPath/$index";
                }
            }
            return false;
        }
        return $sitePath.'/public/index.php';
*/
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
