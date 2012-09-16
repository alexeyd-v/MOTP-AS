<?php
/**
 * Mobile OTP Authentication Server - MOTP-AS
 * 
 * This project strives to bring you the best implementation of the M-OTP
 * authentication system, integrated with Radius.
 *
 * PHP version 5
 *
 * @category Core
 * @package  Motp-as
 * @author   Jon Spriggs <jon@sprig.gs>
 * @license  http://www.gnu.org/licenses/agpl.html AGPLv3
 * @link     https://github.com/MOTP-AS/MOTP-AS GitHub Repo
 */
/**
 * This class handles all the code to ensure consistent processing of HTML and
 * CLI requests.
 *
 * @category IO
 * @package  Motp-as
 * @author   Jon Spriggs <jon@sprig.gs>
 * @license  http://www.gnu.org/licenses/agpl.html AGPLv3
 * @link     https://github.com/MOTP-AS/MOTP-AS GitHub Repo
 */
class IO_Handler
{
    /**
     * This value holds the content of the request data. It is held like this
     * to permit dependency injection.
     *
     * @var IO_Handler
     */
    protected static $objData = null;
    protected static $floatGenerationTime = null;
    protected $arrRequestUrl      = null;
    protected $requestUrlFull     = null;
    protected $requestUrlExParams = null;
    protected $strUsername        = null;
    protected $strPassword        = null;
    protected $strRequestMethod   = null;
    protected $hasIfModifiedSince = null;
    protected $hasIfNoneMatch     = null;
    protected $arrRqstParameters  = null;
    protected $strPathSite        = null;
    protected $strPathRouter      = null;
    protected $arrPathItems       = null;
    protected $strPathFormat      = null;
    protected $intPrefAcceptType  = 0;
    protected $strPrefAcceptType  = null;
    protected $arrAcceptTypes     = null;
    protected $intPrefAcceptLang  = 0;
    protected $strPrefAcceptLang  = null;
    protected $arrAcceptLangs     = null;
    protected $strBasePath        = null;
    protected $strUserAgent       = null;
    protected $arrSession         = null;

    /**
     * This function wrappers the processing of all IO requests, and returns
     * consistent data to the routing or CLI based scripts.
     *
     * @param IO_HandlerData $objInjectionData This is used to inject data for 
     * use in unit testing
     * 
     * @return IO_HandlerData
     */
    public static function request($objInjectionData = null)
    {
        if (self::$objData == null) {
            self::$objData = new IO_Handler();

            if (is_object($objInjectionData)) {
                if (!isset($objInjectionData->SERVER)) {
                    self::$objData->SERVER = $_SERVER;
                } else {
                    self::$objData->SERVER = $objInjectionData->SERVER;
                }
                
                if (!isset($objInjectionData->POST)) {
                    self::$objData->POST = $_POST;
                } else {
                    self::$objData->POST = $objInjectionData->POST;
                    self::$objData->REQUEST = $objInjectionData->POST;
                }
                
                if (!isset($objInjectionData->GET)) {
                    self::$objData->GET = $_GET;
                } else {
                    self::$objData->GET = $objInjectionData->GET;
                    self::$objData->REQUEST = $objInjectionData->GET;
                }

                if (!isset($objInjectionData->INPUT)) {
                    self::$objData->INPUT = file_get_contents('php://input');
                } else {
                    self::$objData->INPUT = $objInjectionData->INPUT;
                }
                
                if (!isset($objInjectionData->FILES)) {
                    self::$objData->FILES = $_FILES;
                } else {
                    self::$objData->FILES = $objInjectionData->FILES;
                }
                
                if (!isset($objInjectionData->SESSION)) {
                    if (isset($_COOKIE['PHPSESSID'])) {
                        self::$objData->SESSION = &$_SESSION;
                    }
                } else {
                    self::$objData->SESSION = $objInjectionData->SESSION;
                }
                
                if (!isset($objInjectionData->GET) && !isset($objInjectionData->POST)) {
                    self::$objData->REQUEST = $_REQUEST;
                }
                
                if (!isset($objInjectionData->GLOBALS)) {
                    if (isset($objInjectionData->POST)
                        || isset($objInjectionData->GET)
                        || isset($objInjectionData->FILES)
                        || isset($objInjectionData->REQUEST)
                        || isset($objInjectionData->SERVER)
                    ) {
                        self::$objData->GLOBALS = array(
                            '_POST' => &self::$objData->POST, 
                            '_GET' => &self::$objData->GET, 
                            '_COOKIE' => array(), 
                            '_FILES' => &self::$objData->FILES, 
                            '_ENV' => array(), 
                            '_REQUEST' => &self::$objData->REQUEST, 
                            '_SERVER' => &self::$objData->SERVER,
                        );
                        if (isset(self::$objData->SERVER['argv'])) {
                            self::$objData->GLOBALS['_SERVER']['argv'] = self::$objData->SERVER['argv'];
                        }
                        if (isset(self::$objData->SERVER['argc'])) {
                            self::$objData->GLOBALS['_SERVER']['argc'] = self::$objData->SERVER['argc'];
                        }
                    } else {
                        self::$objData->GLOBALS = $GLOBALS;
                    }
                } else {
                    self::$objData->GLOBALS = $objInjectionData->GLOBALS;
                }
            }

            return self::processRequest();
        } else {
            return self::$objData;
        }
    }
    
    /**
     * This function processes the values stored in the static $objData value
     * into usable data.
     * 
     * @return IO_HandlerData
     */
    protected static function processRequest()
    {
        // First, get the script name or URL, and any parameters received

        if ( ! isset(self::$objData->SERVER['REQUEST_METHOD'])) {
            if (preg_match('/\/(.*)$/', self::$objData->GLOBALS['argv'][0]) == 0) {
                $filename = trim(`pwd`) . '/' . self::$objData->GLOBALS['argv'][0];
            } else {
                $filename = self::$objData->GLOBALS['argv'][0];
            }
            $url = 'file://' . $filename;
            $args = self::$objData->GLOBALS['argv'];
            unset($args[0]);
            $data = array();
            foreach ($args as $key => $part) {
                if (preg_match('/^([^=]+)=(.*)$/', $part, $matches)) {
                    $data[$matches[1]] = $matches[2];
                } else {
                    $data[$part] = '';
                }
            }
            self::$objData->strRequestMethod = 'file';
        } else {
            $url = "http";
            if (isset(self::$objData->SERVER['HTTPS'])) {
                $url .= 's';
            }
            $url .= '://';

            // Let's check if they gave us HTTP credentials

            if (isset(self::$objData->SERVER['HTTP_AUTHORIZATION'])) {
                $arrAuthParams = explode(":", base64_decode(substr(self::$objData->SERVER['HTTP_AUTHORIZATION'], 6)));
                self::$objData->strUsername = $arrAuthParams[0];
                unset($arrAuthParams[0]);
                self::$objData->strPassword = implode('', $arrAuthParams);
            } elseif (isset(self::$objData->SERVER['PHP_AUTH_USER']) and isset(self::$objData->SERVER['PHP_AUTH_PW'])) {
                self::$objData->strUsername = self::$objData->SERVER['PHP_AUTH_USER'];
                self::$objData->strPassword = self::$objData->SERVER['PHP_AUTH_PW'];
            }

            if (self::$objData->strUsername != null) {
                $url .= self::$objData->strUsername;
                if (self::$objData->strPassword != null) {
                    $url .= ':' . self::$objData->strPassword;
                }
                $url .= '@';
            }
            
            $url .= self::$objData->SERVER['SERVER_NAME'];
            if ((isset(self::$objData->SERVER['HTTPS']) 
                && self::$objData->SERVER['SERVER_PORT'] != 443) 
                || ( ! isset(self::$objData->SERVER['HTTPS']) 
                && self::$objData->SERVER['SERVER_PORT'] != 80)
            ) {
                $url .= ':' . self::$objData->SERVER['SERVER_PORT'];
            }
            $url .= self::$objData->SERVER['REQUEST_URI'];
            
            switch(strtolower(self::$objData->SERVER['REQUEST_METHOD'])) {
            case 'head':
                // Typically a request to see if this has changed since the last 
                // time
                self::$objData->strRequestMethod = 'head';
                $data = self::$objData->REQUEST;
                break;
            case 'get':
                self::$objData->strRequestMethod = 'get';
                $data = self::$objData->GET;
                break;
            case 'post':
                self::$objData->strRequestMethod = 'post';
                $data = self::$objData->POST;
                if (isset(self::$objData->FILES) and is_array(self::$objData->FILES)) {
                    $data['_FILES'] = self::$objData->FILES;
                }
                break;
            case 'put':
                self::$objData->strRequestMethod = 'put';
                parse_str(self::$objData->INPUT, self::$objData->PUT);
                $data = self::$objData->PUT;
                break;
            case 'delete':
                self::$objData->strRequestMethod = 'delete';
                $data = self::$objData->REQUEST;
                break;
            }
        }

        // Next, parse the URL or script name we just received, and store it.

        self::$objData->arrRequestUrl = parse_url($url);
        self::$objData->requestUrlFull = $url;

        // Take off any parameters, if they've been kept

        if (strlen(trim(self::$objData->requestUrlFull)) > 0) {
            $match = preg_match('/^([^\?]+)/', self::$objData->requestUrlFull, $matches);
            self::$objData->requestUrlExParams = $matches[1];
        }
        
        // Store any of the parameters we aquired before. Add an 
        // "if-modified-since" parameter too.

        if (isset(self::$objData->SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            // Taken from http://www.justsoftwaresolutions.co.uk/webdesign ... 
            // /provide-last-modified-headers-and-handle-if-modified-since-in-php.html
            self::$objData->hasIfModifiedSince = preg_replace('/;.*$/', '', self::$objData->SERVER["HTTP_IF_MODIFIED_SINCE"]);
        }
        
        if (isset(self::$objData->SERVER['HTTP_IF_NONE_MATCH'])) {
            preg_match_all('/"([^"^,]+)/', self::$objData->SERVER["HTTP_IF_NONE_MATCH"], $hasIfNoneMatch);
            if (isset($hasIfNoneMatch[0])) {
                unset($hasIfNoneMatch[0]);
                foreach ($hasIfNoneMatch as $tempIfNoneMatch) {
                    if (is_array($tempIfNoneMatch)) {
                        foreach ($tempIfNoneMatch as $value) {
                            self::$objData->hasIfNoneMatch[] = $value;
                        }
                    }
                }
            }
        }
        
        // Make the list of accepted types into an array, and then step through 
        // it.
        if (isset(self::$objData->SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $arrAccept = explode(',', strtolower(str_replace(' ', '', self::$objData->SERVER['HTTP_ACCEPT_LANGUAGE'])));
            foreach ($arrAccept as $acceptItem) {
                $q = 1;
                if (strpos($acceptItem, ';q=')) {
                    list($acceptItem, $q) = explode(';q=', $acceptItem);
                }
                if ($q > 0) {
                    self::$objData->arrAcceptLangs[$acceptItem] = $q;
                    if ($q > self::$objData->intPrefAcceptLang) {
                        self::$objData->intPrefAcceptLang = $q;
                        self::$objData->strPrefAcceptLang = $acceptItem;
                    }
                }
            }
        }

        self::$objData->arrRqstParameters = $data;
        
        // Special case for browsers who can't cope with sending the full range
        // of HTTP actions.
        if (isset(self::$objData->arrRqstParameters['HTTPaction'])) {
            switch(strtolower(self::$objData->arrRqstParameters['HTTPaction'])) {
            case 'head':
                // Typically a request to see if this has changed since the 
                // last time
                self::$objData->strRequestMethod = 'head';
                unset(self::$objData->arrRqstParameters['HTTPaction']);
                break;
            case 'delete':
                self::$objData->strRequestMethod = 'delete';
                unset(self::$objData->arrRqstParameters['HTTPaction']);
                break;
            }
        }

        // Remove the trailing slash from the path, if there is one

        if (substr(self::$objData->arrRequestUrl['path'], -1) == '/') {
            self::$objData->arrRequestUrl['path'] = substr(self::$objData->arrRequestUrl['path'], 0, -1);
        }

        // If the path is just / then keep it, otherwise remove the leading 
        // slash from the path

        $match = preg_match('/\/(.*)/', self::$objData->arrRequestUrl['path'], $matches);
        if ($match > 0) {
            self::$objData->arrRequestUrl['path'] = $matches[1];
        }

        // We need to find where the start of the site is (for example, 
        // it may be http://webserver/myproject, or http://myproject)

        // Assume the start is at the end of http://servername/ and that the 
        // router path is everything from there out.

        self::$objData->strPathSite = '';
        self::$objData->strPathRouter = self::$objData->arrRequestUrl['path'];

        // Next make sure that we have a script name, and that this is not just 
        // a CLI script.

        if (isset(self::$objData->SERVER['REQUEST_METHOD']) && isset(self::$objData->SERVER['SCRIPT_NAME'])) {

            // Separate out the individual characters of the URL path we 
            // received and the script path

            $arrPathElements = str_split(self::$objData->arrRequestUrl['path']);
            $match = preg_match('/\/(.*)$/', self::$objData->SERVER['SCRIPT_NAME'], $matches);
            $arrScriptElements = str_split($matches[1]);

            // Then compare each character one-by-one until we reach the end of 
            // the URL or the script name and path names diverge

            $char = 0;
            while (isset($arrPathElements[$char]) 
                && isset($arrScriptElements[$char]) 
                && $arrPathElements[$char] == $arrScriptElements[$char]
            ) {
                $char++;
            }

            // Use that information to build the pathSite (the base URL for the 
            // site) and the routed path (/my/action)

            self::$objData->strPathSite = substr(self::$objData->arrRequestUrl['path'], 0, $char);
            self::$objData->strPathRouter = substr(self::$objData->arrRequestUrl['path'], $char);
        }

        // To ensure the first character of the pathRouter isn't '/', check for 
        // it and trim it. I can't actually figure out why this went in here, 
        // but I don't seem to be able to test it!
        
        if (substr(self::$objData->strPathRouter, 0, 1) == '/') {
            self::$objData->strPathRouter = substr(self::$objData->strPathRouter, 1);
        }
        
        // And ensure the last character of the site path isn't '/', check for 
        // that and trim it.
        if (substr(self::$objData->strPathSite, -1) == '/') {
            self::$objData->strPathSite = substr(self::$objData->strPathSite, 0, -1);
        }

        // Get the routed path as it's slash-delimited values into an array

        self::$objData->arrPathItems = explode('/', self::$objData->strPathRouter);

        // Let's talk about the format to return data as, or rather, the 
        // preferred (Internet Media) accepted-type. This was inserted after 
        // reading this comment:
        // http://www.lornajane.net/posts/2012/building-a-restful-php-server-understanding-the-request#comment-3218

        self::$objData->strPathFormat = '';
        self::$objData->intPrefAcceptType = 0;
        self::$objData->strPrefAcceptType = 'text/html';
        self::$objData->arrAcceptTypes = array();
        $arrDenyTypes = array();

        // This is based on http://stackoverflow.com/questions/1049401/how-to-select-content-type-from-http-accept-header-in-php

        // Make the list of accepted types into an array, and then step through 
        // it.
        if (isset(self::$objData->SERVER['HTTP_ACCEPT'])) {
            $arrAccept = explode(',', strtolower(str_replace(' ', '', self::$objData->SERVER['HTTP_ACCEPT'])));
            foreach ($arrAccept as $acceptItem) {

                // All accepted Internet Media Types (or Mime Types, as they 
                // once we known) have a Q (Quality?) value. The default "Q" 
                // value is 1;
                $q = 1;

                // but the client may have sent another value
                if (strpos($acceptItem, ';q=')) {
                    // In which case, use it.
                    list($acceptItem, $q) = explode(';q=', $acceptItem);
                }

                // If the quality is 0, it's not accepted - in this case, so why 
                // bother logging it? Also, IE has a bad habit of saying it 
                // accepts everything. Ignore that case.

                if ($q > 0 && $acceptItem != '*/*') {
                    self::$objData->arrAcceptTypes[$acceptItem] = $q;
                    if ($q > self::$objData->intPrefAcceptType) {
                        self::$objData->intPrefAcceptType = $q;
                        self::$objData->strPrefAcceptType = $acceptItem;
                    }
                } else {
                    $arrDenyTypes[$acceptItem] = true;
                }
            }

            // If the last item contains a dot, for example file.json, then we 
            // can suspect the user is specifying the file format to prefer. So, 
            // let's look at the last chunk of the requested URL. Does it 
            // contain a dot in it?

            $arrLastUrlItem = explode('.', self::$objData->arrPathItems[count(self::$objData->arrPathItems)-1]);
            if (count($arrLastUrlItem) > 1) {

                // First we clear down the last path item, as we're going to be 
                // re-creating it without the format tag

                self::$objData->arrPathItems[count(self::$objData->arrPathItems)-1] = '';

                // Next we step through each part of that last chunk, looking 
                // for the bit after the last dot.

                foreach ($arrLastUrlItem as $key=>$urlItem) {

                    // If it's the last part, this is the format we'll be using, 
                    // otherwise rebuild that last item

                    if ($key + 1 == count($arrLastUrlItem)) {
                        self::$objData->strPathFormat = $urlItem;

                        // Remove the pathFormat from the pathRouter, and the 
                        // "."

                        self::$objData->strPathRouter = substr(self::$objData->strPathRouter, 0, - (1 + strlen(self::$objData->strPathFormat)));

                        // Now let's try and mark the format up as something we 
                        // can use as an accept type. Here are the common ones
                        // you're likely to see (from 
                        // http://en.wikipedia.org/wiki/Internet_media_type)

                        switch (strtolower(self::$objData->strPathFormat)) {

                        // Application types

                        case 'json':
                            self::$objData->setAcceptType(
                                'application/json',
                                $arrDenyTypes
                            );
                            break;
                        case 'atom':
                            self::$objData->setAcceptType(
                                'application/atom+xml',
                                $arrDenyTypes
                            );
                            break;
                        case 'pdf':
                            self::$objData->setAcceptType(
                                'application/pdf',
                                $arrDenyTypes
                            );
                            break;
                        case 'ps':
                            self::$objData->setAcceptType(
                                'application/postscript',
                                $arrDenyTypes
                            );
                            break;
                        case 'rss':
                            self::$objData->setAcceptType(
                                'application/rss+xml',
                                $arrDenyTypes
                            );
                            break;
                        case 'soap':
                            self::$objData->setAcceptType(
                                'application/soap+xml',
                                $arrDenyTypes
                            );
                            break;
                        case 'xhtml':
                            self::$objData->setAcceptType(
                                'application/xhtml+xml',
                                $arrDenyTypes
                            );
                            break;
                        case 'zip':
                            self::$objData->setAcceptType(
                                'application/zip',
                                $arrDenyTypes
                            );
                            break;
                        case 'gz':
                        case 'gzip':
                            self::$objData->setAcceptType(
                                'application/x-gzip',
                                $arrDenyTypes
                            );
                            break;

                        // Audio Types

                        case 'mp3':
                        case 'mpeg3':
                            self::$objData->setAcceptType(
                                'audio/mpeg',
                                $arrDenyTypes
                            );
                            break;
                        case 'm4a':
                            self::$objData->setAcceptType(
                                'audio/mp4',
                                $arrDenyTypes
                            );
                            break;
                        case 'ogg':
                            self::$objData->setAcceptType(
                                'audio/ogg',
                                $arrDenyTypes
                            );
                            break;

                        // Image types

                        case 'png':
                            self::$objData->setAcceptType(
                                'image/png',
                                $arrDenyTypes
                            );
                            break;
                        case 'jpg':
                        case 'jpeg':
                            self::$objData->setAcceptType(
                                'image/jpeg',
                                $arrDenyTypes
                            );
                            break;
                        case 'gif':
                            self::$objData->setAcceptType(
                                'image/gif',
                                $arrDenyTypes
                            );
                            break;
                        case 'svg':
                            self::$objData->setAcceptType(
                                'image/svg+xml',
                                $arrDenyTypes
                            );
                            break;

                        // Text types

                        case 'css':
                            self::$objData->setAcceptType(
                                'text/css',
                                $arrDenyTypes
                            );
                            break;
                        case 'htm':
                        case 'html':
                            self::$objData->setAcceptType(
                                'text/html',
                                $arrDenyTypes
                            );
                            break;
                        case 'csv':
                            self::$objData->setAcceptType(
                                'text/csv',
                                $arrDenyTypes
                            );
                            break;
                        case 'xml':
                            self::$objData->setAcceptType(
                                'text/xml',
                                $arrDenyTypes
                            );
                            break;
                        case 'txt':
                            self::$objData->setAcceptType(
                                'text/plain',
                                $arrDenyTypes
                            );
                            break;
                        case 'vcd':
                            self::$objData->setAcceptType(
                                'text/vcard',
                                $arrDenyTypes
                            );
                            break;

                        // Video types

                        case 'ogv':
                            self::$objData->setAcceptType(
                                'video/ogg',
                                $arrDenyTypes
                            );
                            break;
                        case 'avi':
                            self::$objData->setAcceptType(
                                'video/mpeg',
                                $arrDenyTypes
                            );
                            break;
                        case 'mp4':
                        case 'mpeg':
                            self::$objData->setAcceptType(
                                'video/mp4',
                                $arrDenyTypes
                            );
                            break;
                        case 'webm':
                            self::$objData->setAcceptType(
                                'video/webm',
                                $arrDenyTypes
                            );
                            break;
                        case 'wmv':
                            self::$objData->setAcceptType(
                                'video/x-ms-wmv',
                                $arrDenyTypes
                            );
                            break;

                        // Open/Libre/MS Office file formats

                        case 'doc':
                            self::$objData->setAcceptType(
                                'application/msword',
                                $arrDenyTypes
                            );
                            break;
                        case 'docx':
                            self::$objData->setAcceptType(
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                $arrDenyTypes
                            );
                            break;
                        case 'odt':
                            self::$objData->setAcceptType(
                                'application/vnd.oasis.opendocument.text',
                                $arrDenyTypes
                            );
                            break;
                        case 'xls':
                            self::$objData->setAcceptType(
                                'application/vnd.ms-excel',
                                $arrDenyTypes
                            );
                            break;
                        case 'xlsx':
                            self::$objData->setAcceptType(
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                $arrDenyTypes
                            );
                            break;
                        case 'ods':
                            self::$objData->setAcceptType(
                                'application/vnd.oasis.opendocument.spreadsheet',
                                $arrDenyTypes
                            );
                            break;
                        case 'ppt':
                            self::$objData->setAcceptType(
                                'application/vnd.ms-powerpoint',
                                $arrDenyTypes
                            );
                            break;
                        case 'pptx':
                            self::$objData->setAcceptType(
                                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                                $arrDenyTypes
                            );
                            break;
                        case 'odp':
                            self::$objData->setAcceptType(
                                'application/vnd.oasis.opendocument.presentation',
                                $arrDenyTypes
                            );
                            break;
                        case 'js':
                            self::$objData->setAcceptType(
                                'text/javascript',
                                $arrDenyTypes
                            );
                            break;

                        // Not one of the above types. Hopefully you won't see 
                        // this!!!

                        default:
                            self::$objData->setAcceptType(
                                'unknown/' . self::$objData->strPathFormat,
                                $arrDenyTypes
                            );
                        }
                    } else {
                        if (self::$objData->arrPathItems[count(self::$objData->arrPathItems)-1] != '') {
                            self::$objData->arrPathItems[count(self::$objData->arrPathItems)-1] .= '.';
                        }
                        self::$objData->arrPathItems[count(self::$objData->arrPathItems)-1] .= $urlItem;
                    }
                }
            }
        }

        // Next let's build the "basePath" - this is the URL which refers to 
        // base of the script and is used in the HTML to point back to
        // resources within this service.

        self::$objData->strBasePath = self::$objData->arrRequestUrl['scheme'] . "://";
        if (isset(self::$objData->arrRequestUrl['host'])) {
            self::$objData->strBasePath .= self::$objData->arrRequestUrl['host'];
        }
        if (isset(self::$objData->arrRequestUrl['port']) and self::$objData->arrRequestUrl['port'] != '') {
            self::$objData->strBasePath .= ':' . self::$objData->arrRequestUrl['port'];
        }
        if (isset(self::$objData->strPathSite) and self::$objData->strPathSite != '') {
            self::$objData->strBasePath .= '/' . self::$objData->strPathSite;
        }
        self::$objData->strBasePath .=  '/';

        // Let's get the user agent - it's just for a giggle in most cases, as 
        // it's not authorititive, but it might help if you're
        // getting site stats, or trying not to track people with cookies.

        if (isset(self::$objData->SERVER['HTTP_USER_AGENT'])) {
            // Remember, this isn't guaranteed to be accurate
            self::$objData->strUserAgent = self::$objData->SERVER['HTTP_USER_AGENT'];
        }
        
        return self::$objData;
    }
    
    /**
     * This function reads the $arrMediaTypes array above, and returns whether 
     * it's a valid site, rest (api) or media type.
     * 
     * It is used when making decisions about whether to return data to the user 
     * in that format.
     *
     * @param string $category  The type of request we believe this media type 
     * should work for
     * @param string $mediaType The media type (replaced, on null with the 
     * detected media type)
     * 
     * @return boolean The value from the table above.
     */
    public function hasMediaType($category = 'site', $mediaType = null)
    {
        if ($mediaType == null) {
            $mediaType = $this->strPrefAcceptType;
        }
        if (isset(IO_Handler_StaticData::$arrMediaTypes[$mediaType])) {
            switch ($category) {
            case 'media':
            case 'rest':
            case 'site':
                return IO_Handler_StaticData::$arrMediaTypes[$mediaType][$category];
                break;
            default:
                return false;
            }
        } else {
            return false;
        }
    }
    
    /**
     * This function updates the arrRequestData array with the MIME type to 
     * handle, based on the file extension. It returns the maximum MIME type
     * value.
     *
     * @param string $strAcceptType The MIME type
     * @param array  $arrDenyTypes  An array of mime types we're not interested
     * in.
     * 
     * @return integer
     */
    public function setAcceptType(
        $strAcceptType = '', 
        $arrDenyTypes = array()
    ) {
        if (! isset($arrDenyTypes[$strAcceptType])) {
            $this->arrAcceptTypes[$strAcceptType] = 2;
        }
        if (2 > $this->intPrefAcceptType) {
            $this->intPrefAcceptType = 2;
            $this->strPrefAcceptType = $strAcceptType;
        }
        return $this->intPrefAcceptType;
    }

    /**
     * Return the exploded Request URL array
     *
     * @return array 
     */
    public function get_arrRequestUrl()
    {
        return $this->arrRequestUrl;
    }
    
    /**
     * Return the full Request URL
     *
     * @return string
     */
    public function get_requestUrlFull()
    {
        return $this->requestUrlFull;
    }
    
    /**
     * Return the full Request URL excluding GET parameters
     *
     * @return string 
     */
    public function get_requestUrlExParams()
    {
        return $this->requestUrlExParams;
    }
    
    /**
     * Return the Username from the request
     *
     * @return string 
     */
    public function get_strUsername()
    {
        return $this->strUsername;
    }

    /**
     * Return the Password from the request
     *
     * @return string 
     */
    public function get_strPassword()
    {
        return $this->strPassword;
    }

    /**
     * Return the request method (PUT, GET, POST, DELETE, HEAD, etc) from the 
     * request
     *
     * @return string 
     */
    public function get_strRequestMethod()
    {
        return $this->strRequestMethod;
    }
    
    /**
     * If set, return the "Has-If-Modified-Since" value
     *
     * @return null|datetime 
     */
    public function get_hasIfModifiedSince()
    {
        return $this->hasIfModifiedSince;
    }
    
    /**
     * If set, return the "If-None-Match" value (for etags associated to a page)
     *
     * @return null|string 
     */
    public function get_hasIfNoneMatch()
    {
        return $this->hasIfNoneMatch;
    }
    
    /**
     * Return the array of all the parameters supplied by the request.
     *
     * @return array 
     */
    public function get_arrRqstParameters()
    {
        return $this->arrRqstParameters;
    }
    
    /**
     * Return the path of everything in the URL past the router
     *
     * @return string
     */
    public function get_strPathSite()
    {
        return $this->strPathSite;
    }
    
    /**
     * Return the path of everything up to the Router.
     *
     * @return string
     */
    public function get_strPathRouter()
    {
        return $this->strPathRouter;
    }
    
    /**
     * Return the array of "path items" - basically, everything after the router
     * comes into play.
     *
     * @return array 
     */
    public function get_arrPathItems()
    {
        return $this->arrPathItems;
    }
    
    /**
     * If we've forced the Internet Type by providing a file extension, return
     * that value.
     *
     * @return string 
     */
    public function get_strPathFormat()
    {
        return $this->strPathFormat;
    }
    
    /**
     * Return the preferred (highest valued) accepted Internet Type (Mime Type),
     * where, if supplied, your browser can force it's preferred Internet Type
     * by supplying a known file extension.
     *
     * @return string 
     */
    public function get_strPrefAcceptType()
    {
        return $this->strPrefAcceptType;
    }
    
    /**
     * Return the array of Internet Types (Mime Types) your browser will accept, 
     * or, where forced by supplying a known file extension, that value as the 
     * top response.
     *
     * @return array 
     */
    public function get_arrAcceptTypes()
    {
        return $this->arrAcceptTypes;
    }
    
    /**
     * Return the base path of the URL, up to the point where the router takes
     * over.
     *
     * @return string
     */
    public function get_strBasePath()
    {
        return $this->strBasePath;
    }
    
    /**
     * Return the user agent string
     *
     * @return string
     */
    public function get_strUserAgent()
    {
        return $this->strUserAgent;
    }
    
    /**
     * Return the content of the $_SESSION array.
     *
     * @return array 
     */
    public function get_arrSession()
    {
        return $this->arrSession;
    }
    
    /**
     * Return the highest rated, first listed accepted language
     *
     * @return string 
     */
    public function get_strPrefAcceptLang()
    {
        return $this->strPrefAcceptLang;
    }

    /**
     * Return the array of accepted languages
     *
     * @return array
     */
    public function get_arrAcceptLangs()
    {
        return $this->arrAcceptLangs;
    }
}

/**
 * This class is basically a glorified constant. It stores what type of data
 * should be returned from each of the MIME Types (now known as Media Types).
 *
 * @category IO
 * @package  Motp-as
 * @author   Jon Spriggs <jon@sprig.gs>
 * @license  http://www.gnu.org/licenses/agpl.html AGPLv3
 * @link     https://github.com/MOTP-AS/MOTP-AS GitHub Repo
 */
class IO_Handler_StaticData
{
    /**
     * This stores the various Media/MIME types for requests and responses that
     * may be seen. Each array entry stores another array of three values - 
     * media, rest and site. Media is a static file type such as images, 
     * documents, javascript scripts or CSS, rest is a programatic type of 
     * response (usually things like JSON, XML, or HTML), while site is types
     * of data that would be used in non-REST site rendering (HTML or Text).
     *
     * @var array 
     */
    public static $arrMediaTypes = array(
        'application/json' => array(
            'media' => false, 'rest' => true, 'site' => false
        ),
        'application/atom+xml' => array(
            'media' => false, 'rest' => true, 'site' => false
        ),
        'application/pdf' => array(
            'media' => true, 'rest' => false, 'site' => false
        ),
        'application/postscript' => array(
            'media' => true, 'rest' => false, 'site' => false
        ),
        'application/rss+xml' => array(
            'media' => false, 'rest' => true, 'site' => false
        ),
        'application/soap+xml' => array(
            'media' => false, 'rest' => false, 'site' => false
        ),
        'application/xhtml+xml' => array(
            'media' => false, 'rest' => true, 'site' => true
        ),
        'application/zip' => array(
            'media' => true, 'rest' => false, 'site' => false
        ),
        'application/x-gzip' => array(
            'media' => true, 'rest' => false, 'site' => false
        ),
        'audio/mpeg' => array(
            'media' => true, 'rest' => false, 'site' => false
        ),
        'audio/mp4' => array(
            'media' => true, 'rest' => false, 'site' => false
        ),
        'audio/ogg' => array(
            'media' => true, 'rest' => false, 'site' => false
        ),
        'image/png' => array(
            'media' => true, 'rest' => false, 'site' => false
        ),
        'image/jpeg' => array(
            'media' => true, 'rest' => false, 'site' => false
        ),
        'image/gif' => array(
            'media' => true, 'rest' => false, 'site' => false
        ),
        'image/svg+xml' => array(
            'media' => true, 'rest' => false, 'site' => false
        ),
        'text/css' => array(
            'media' => true, 'rest' => false, 'site' => true
        ),
        'text/html' => array(
            'media' => false, 'rest' => true, 'site' => true
        ),
        'text/csv' => array(
            'media' => false, 'rest' => true, 'site' => false
        ),
        'text/xml' => array(
            'media' => false, 'rest' => true, 'site' => false
        ),
        'text/plain' => array(
            'media' => false, 'rest' => true, 'site' => true
        ),
        'text/vcard' => array(
            'media' => false, 'rest' => true, 'site' => true
        ),
        'video/ogg' => array(
            'media' => true, 'rest' => false, 'site' => false
        ),
        'video/mpeg' => array(
            'media' => true, 'rest' => false, 'site' => false
        ),
        'video/mp4' => array(
            'media' => true, 'rest' => false, 'site' => false
        ),
        'video/webm' => array(
            'media' => true, 'rest' => false, 'site' => false
        ),
        'video/x-ms-wmv' => array(
            'media' => true, 'rest' => false, 'site' => false
        ),
        'application/msword' => array(
            'media' => true, 'rest' => false, 'site' => false
        ),
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => array(
            'media' => true, 'rest' => false, 'site' => false
        ),
        'application/vnd.oasis.opendocument.text' => array(
            'media' => true, 'rest' => false, 'site' => false
        ),
        'application/vnd.ms-excel' => array(
            'media' => true, 'rest' => false, 'site' => false
        ),
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => array(
            'media' => true, 'rest' => false, 'site' => false
        ),
        'application/vnd.oasis.opendocument.spreadsheet' => array(
            'media' => true, 'rest' => false, 'site' => false
        ),
        'application/vnd.ms-powerpoint' => array(
            'media' => true, 'rest' => false, 'site' => false
        ),
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => array(
            'media' => true, 'rest' => false, 'site' => false
        ),
        'application/vnd.oasis.opendocument.presentation' => array(
            'media' => true, 'rest' => false, 'site' => false
        ),
        'text/javascript' => array(
            'media' => true, 'rest' => false, 'site' => false
        )
    );

}