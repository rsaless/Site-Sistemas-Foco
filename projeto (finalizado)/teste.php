<?php
##################################################################################################
##################################################################################################
## Description: PHP Download Controller                                                         ##
## Author:  	vanita5 <mail@vanita5.de>                                                       ##
## Date:    	Nov 2014                                                                        ##
## File:    	download.php                                                                    ##
## Version: 	1.0                                                                             ##
##                                                                                              ##
##                                                                                              ##
## =USAGE=                                                                                      ##
##                                                                                              ##
## HTML only:                                                                                   ##
##                                                                                              ##
##      <form method="POST" action="download.php">                                              ##
##          <input type="hidden" id="file" value="path/to/file.jpg">                            ##
##          <input type="submit" value="Download">                                              ##
##      </form>                                                                                 ##
##                                                                                              ##
##                                                                                              ##
## JavaScript:                                                                                  ##
##                                                                                              ##
##      document.location.href = 'download.php?file=' + path;                                   ##
##                                                                                              ##
##                                                                                              ##
## Supports GET and POST parameter 'file'.                                                      ##
##                                                                                              ##
## Make sure to configure the DOWNLOAD_DIR constant                                             ##
## as well as the allowed filetypes. Less filetypes increase security.                          ##
## (Although the download directory validation should be good enough).                          ##
## The download directory should be a relative path and must not contain '..'                   ##
##                                                                                              ##
## Never use '..' in a file path parameter. This is detected as                                 ##
## a manipulated parameter and the download will not occur. Make sure                           ##
## to place this file in a context, where it can access the intended download                   ##
## directory via a relative path. Absolute paths may work, however, you propably                ##
## don't want your users to see it.                                                             ##
##                                                                                              ##
## The HTTP methods can be enabled/disabled.                                                    ##
## You can leave both of them enabled until you really need to limit parameter passing.         ##
##                                                                                              ##
##                                                                                              ##
##################################################################################################
##################################################################################################
//Directory where this script is allowed to download from
define('DOWNLOAD_DIR', 'download/'); //trailing '/' required!
//Define allowed HTTP methods
define('ALLOW_POST', true);
define('ALLOW_GET', true);
//Allowed filetypes (file endings) this script is allowed to download
$allowedFiletypes = array('.gif', '.png', '.jpg', '.jpeg',
                          '.pdf', '.rar', '.zip', '.doc',
                          '.xsl', '.xlsx', '.ppt', '.pptx');
####################################################################
# Functions
####################################################################
/**
 * Read GET or POST parameter from the super globals.
 * Checks if parameter/field is set and has the option to
 * return a default value if the parameter is not set.
 * If you don't set a default value and a parameter is not set,
 * this will return the default value (null)
 *
 * @param string $field
 * @param string $default
 * @return string|null param value or default
 */
function readParam($field, $default = null) {
    //Variable auf default Wert setzen
    $var = $default;
    //Überprüfen, ob das Feld als POST oder GET Parameter existiert
    //und gesetzt ist.
    if (ALLOW_POST && isset($_POST[$field]) && $_POST[$field] != '') {
        $var = $_POST[$field];
    } else if (ALLOW_GET && isset($_GET[$field]) && $_GET[$field] != '') {
        $var = $_GET[$field];
    }
    return $var;
}
/**
 * Validate if the file exists and if
 * there is a permission (download dir) to download this file
 *
 * You should ALWAYS call this method if you don't want
 * somebody to download files not intended to be for the public.
 *
 * @param string $file GET parameter
 * @param array $allowedFiletypes (defined in the head of this file)
 * @return bool true if validation was successfull
 */
function validate($file, $allowedFiletypes) {
    //check if file exists
    if (!isset($file)
        || empty($file)
        || !file_exists($file)) {
        return false;
    }
    //check allowed filetypes
    $fileAllowed = false;
    foreach ($allowedFiletypes as $filetype) {
        if (strpos($file, $filetype) === (strlen($file) - strlen($filetype))) {
            $fileAllowed = true; //ends with $filetype
        }
    }
    if (!$fileAllowed) return false;
    //check download directory
    if (substr($file, 0, strlen(DOWNLOAD_DIR)) !== DOWNLOAD_DIR  //check download directory
        //Why do I check for '..' in the whole string and not just after the DOWNLOAD_DIR?
        //I first did. However, '/..', './../' and 'foo/bar/../../../' are all examples for manipulations that
        //would also work to access the parent dir.
        //So now we just check if the string contains '..' and then assume that it has been manipulated.
        //This means, you have to make sure not to use '..' internally.
        || strpos($file, '..') !== false) {
        return false;
    }
    return true;
}
/**
 * Download function.
 * Sets the HTTP header and supplies the given file
 * as a download to the browser.
 *
 * @param string $file path to file
 */
function download($file) {
    //Parse information
    $pathinfo = pathinfo($file);
    $extension = strtolower($pathinfo['extension']);
    $mimetype = null;
    //Get mimetype for extension
    //This list can be extended as you need it.
    //A good start to find mimetypes is the apache mime.types list
    // http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types
    switch ($extension) {
        case 'avi':     $mimetype = "video/x-msvideo"; break;
        case 'doc':     $mimetype = "application/msword"; break;
        case 'exe':     $mimetype = "application/octet-stream"; break;
        case 'flac':    $mimetype = "audio/flac"; break;
        case 'gif':     $mimetype = "image/gif"; break;
        case 'jpeg':
        case 'jpg':     $mimetype = "image/jpg"; break;
        case 'json':    $mimetype = "application/json"; break;
        case 'mp3':     $mimetype = "audio/mpeg"; break;
        case 'mp4':     $mimetype = "application/mp4"; break;
        case 'ogg':     $mimetype = "audio/ogg"; break;
        case 'pdf':     $mimetype = "application/pdf"; break;
        case 'png':     $mimetype = "image/png"; break;
        case 'ppt':     $mimetype = "application/vnd.ms-powerpoint"; break;
        case 'rtf':     $mimetype = "application/rtf"; break;
        case 'sql':     $mimetype = "application/sql"; break;
        case 'xls':     $mimetype = "application/vnd.ms-excel"; break;
        case 'xlsx':    $mimetype = "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"; break;
        case 'xml':     $mimetype = "application/xml"; break;
        case 'zip':     $mimetype = "application/zip"; break;
        default:        $mimetype = "application/force-download";
    }
    // Required for some browsers like Safari and IE
    if (ini_get('zlib.output_compression')) {
        ini_set('zlib.output_compression', 'Off');
    }
    //Set header
    header('Pragma: public');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Cache-Control: private', false); // required for some browsers
    header('Content-Type: '.$mimetype);
    header('Content-Disposition: attachment; filename="'.basename($file).'";');
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: '.filesize($file));
    ob_clean();
    flush();
    readfile($file);
}
####################################################################
$file = readParam('file');
if (!validate($file, $allowedFiletypes)) {
    die('Download failed.');
}
download($file);
?>