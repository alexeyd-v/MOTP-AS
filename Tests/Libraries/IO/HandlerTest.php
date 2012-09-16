<?php

class IO_Handler_Test extends PHPUnit_Framework_TestCase
{
    protected $server = null;
    protected $file = null;

    public function setUp()
    {
        IO_Handler_Testable::reset();
        ob_start();
        $this->server = new stdClass();
        $this->server->SERVER = array(
            'REQUEST_METHOD' => 'GET',
            'HTTPS' => 1,
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => '1443',
            'REQUEST_URI' => '/service/talk/12/',
            'SCRIPT_NAME' => '/service/index.php',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'en/gb;q=1, en;q=0.9',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:12.0) Gecko/20100101 Firefox/12.0'
        );
        $this->server->GET = array();
        $this->server->POST = array();
        $this->server->REQUEST = array();
        $this->server->FILES = array();
        $this->server->INPUT = "";
        $this->server->GLOBALS = array(
            '_POST' => &$this->server->POST, 
            '_GET' => &$this->server->GET, 
            '_COOKIE' => array(), 
            '_FILES' => &$this->server->FILES, 
            '_ENV' => array(), 
            '_REQUEST' => &$this->server->REQUEST, 
            '_SERVER' => &$this->server->SERVER
        );

        $this->file = new stdClass();
        $this->file->SERVER = array(
            "PHP_SELF" => '',
            "SCRIPT_NAME" => '',
            "SCRIPT_FILENAME" => '',
            "PATH_TRANSLATED" => '',
            "DOCUMENT_ROOT" => '',
            "argv" => array(
                0 => __FILE__,
                1 => "param=1",
                2 => "dostuff"
            ),
            "argc" => 2
        );
        $this->file->GET = array();
        $this->file->POST = array();
        $this->file->REQUEST = array();
        $this->file->FILES = array();
        $this->file->INPUT = "";
        $this->file->GLOBALS = array(
            '_POST' => &$this->file->POST, 
            '_GET' => &$this->file->GET, 
            '_COOKIE' => array(), 
            '_FILES' => &$this->file->FILES, 
            '_ENV' => array(), 
            '_REQUEST' => &$this->file->REQUEST, 
            '_SERVER' => &$this->file->SERVER,
            "argv" => array(
                0 => __FILE__,
                1 => "param=1",
                2 => "dostuff"
            ),
            "argc" => 2
        );
    }
    
    public function tearDown()
    {
        header_remove();
    }
    public function parseServer()
    {
        IO_Handler_Testable::reset();
        $return = IO_Handler::request($this->server);
        return $return;
    }

    public function parseFile()
    {
        IO_Handler_Testable::reset();
        $return = IO_Handler::request($this->file);
        return $return;
    }

    public function testCreateObject()
    {
        $request = new IO_Handler();
        $this->assertTrue(is_object($request));
        $this->assertTrue(get_class($request) == 'IO_Handler');
    }
    
    public function testHasMediaType()
    {
        $request = new IO_Handler();
        $this->assertTrue($request->hasMediaType('site', 'text/html'));
        $this->assertTrue($request->hasMediaType('rest', 'text/html'));
        $this->assertFalse($request->hasMediaType('media', 'text/html'));
        $this->assertFalse($request->hasMediaType('dummy'));
        $this->assertFalse($request->hasMediaType('site', 'dummy/dummy'));
        $this->assertFalse($request->hasMediaType('dummy', 'text/html'));
    }

    public function testSimulatedServerConnection()
    {
        $request = $this->parseServer();
        $this->assertTrue($request->get_strRequestMethod() == 'get');
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12/');
        $this->assertTrue($request->get_requestUrlExParams() == 'https://localhost:1443/service/talk/12/');
        $this->assertTrue($request->get_strUsername() == null);
        $this->assertTrue($request->get_strPassword() == null);
        $this->assertTrue($request->get_strUserAgent() == 'Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:12.0) Gecko/20100101 Firefox/12.0');
        $this->assertTrue($request->get_strPrefAcceptType() == 'text/html');
        $arrPathItems = $request->get_arrPathItems();
        $this->assertTrue(count($arrPathItems) == 2);
        $this->assertTrue($arrPathItems[0] == 'talk');
        $this->assertTrue($arrPathItems[1] == '12');
        $arrParameters = $request->get_arrRqstParameters();
        $this->assertTrue(count($arrParameters) == 0);
        $this->assertTrue($request->get_strBasePath() == 'https://localhost:1443/service/');
        $this->assertTrue($request->hasMediaType());
        $this->assertTrue(is_array($request->get_arrRequestUrl()));
        $this->assertTrue($request->get_strPathSite() == 'service');
        $this->assertTrue($request->get_strPathRouter() == 'talk/12');
        $arrAcceptTypes = $request->get_arrAcceptTypes();
        $this->assertTrue(is_array($arrAcceptTypes));
        $this->assertTrue(count($arrAcceptTypes) == 3);
        $this->assertTrue($arrAcceptTypes['text/html'] == 1);
        $this->assertTrue($arrAcceptTypes['application/xhtml+xml'] == 1);
        $this->assertTrue($arrAcceptTypes['application/xml'] == "0.9");
    }

    public function testSimulatedServerWithNoSitePath()
    {
        $this->server->SERVER['REQUEST_URI'] = '/';
        $this->server->SERVER['SCRIPT_NAME'] = '/index.php';
        $request = $this->parseServer();
        $this->assertTrue($request->get_strPathSite() == '');
        $this->assertTrue($request->get_strPathRouter() == '');
    }

    public function testSimulatedHttpServerRequestOnNonStandardPort()
    {
        unset($this->server->SERVER['HTTPS']);
        $this->server->SERVER['SERVER_PORT'] = 8081;
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'http://localhost:8081/service/talk/12/');
    }

    public function testSimulatedServerConnectionUsingFormatExtensions()
    {
        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12.json';
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12.json');
        $this->assertTrue($request->get_strPrefAcceptType() == 'application/json');
        $this->assertTrue($request->get_strPathFormat() == 'json');
        $this->assertFalse($request->hasMediaType());
        $this->assertFalse($request->hasMediaType('site'));
        $this->assertTrue($request->hasMediaType('rest'));
        $this->assertFalse($request->hasMediaType('media'));
        
        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12.atom';
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12.atom');
        $this->assertTrue($request->get_strPrefAcceptType() == 'application/atom+xml');
        $this->assertFalse($request->hasMediaType());
        $this->assertFalse($request->hasMediaType('site'));
        $this->assertTrue($request->hasMediaType('rest'));
        $this->assertFalse($request->hasMediaType('media'));


        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12.pdf';
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12.pdf');
        $this->assertTrue($request->get_strPrefAcceptType() == 'application/pdf');
        $this->assertFalse($request->hasMediaType());
        $this->assertFalse($request->hasMediaType('site'));
        $this->assertFalse($request->hasMediaType('rest'));
        $this->assertTrue($request->hasMediaType('media'));


        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12.ps';
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12.ps');
        $this->assertTrue($request->get_strPrefAcceptType() == 'application/postscript');
        $this->assertFalse($request->hasMediaType());
        $this->assertFalse($request->hasMediaType('site'));
        $this->assertFalse($request->hasMediaType('rest'));
        $this->assertTrue($request->hasMediaType('media'));

        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12.rss';
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12.rss');
        $this->assertTrue($request->get_strPrefAcceptType() == 'application/rss+xml');
        $this->assertFalse($request->hasMediaType());
        $this->assertFalse($request->hasMediaType('site'));
        $this->assertTrue($request->hasMediaType('rest'));
        $this->assertFalse($request->hasMediaType('media'));

        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12.soap';
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12.soap');
        $this->assertTrue($request->get_strPrefAcceptType() == 'application/soap+xml');
        $this->assertFalse($request->hasMediaType());
        $this->assertFalse($request->hasMediaType('site'));
        $this->assertFalse($request->hasMediaType('rest'));
        $this->assertFalse($request->hasMediaType('media'));

        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12.xhtml';
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12.xhtml');
        $this->assertTrue($request->get_strPrefAcceptType() == 'application/xhtml+xml');
        $this->assertTrue($request->hasMediaType());
        $this->assertTrue($request->hasMediaType('site'));
        $this->assertTrue($request->hasMediaType('rest'));
        $this->assertFalse($request->hasMediaType('media'));

        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12.zip';
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12.zip');
        $this->assertTrue($request->get_strPrefAcceptType() == 'application/zip');
        $this->assertFalse($request->hasMediaType());
        $this->assertFalse($request->hasMediaType('site'));
        $this->assertFalse($request->hasMediaType('rest'));
        $this->assertTrue($request->hasMediaType('media'));

        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12.tar.gz';
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12.tar.gz');
        $this->assertTrue($request->get_strPrefAcceptType() == 'application/x-gzip');
        $this->assertFalse($request->hasMediaType());
        $this->assertFalse($request->hasMediaType('site'));
        $this->assertFalse($request->hasMediaType('rest'));
        $this->assertTrue($request->hasMediaType('media'));
        
        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12.mp3';
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12.mp3');
        $this->assertTrue($request->get_strPrefAcceptType() == 'audio/mpeg');
        $this->assertFalse($request->hasMediaType());
        $this->assertFalse($request->hasMediaType('site'));
        $this->assertFalse($request->hasMediaType('rest'));
        $this->assertTrue($request->hasMediaType('media'));

        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12.m4a';
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12.m4a');
        $this->assertTrue($request->get_strPrefAcceptType() == 'audio/mp4');
        $this->assertFalse($request->hasMediaType());
        $this->assertFalse($request->hasMediaType('site'));
        $this->assertFalse($request->hasMediaType('rest'));
        $this->assertTrue($request->hasMediaType('media'));
        
        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12.ogg';
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12.ogg');
        $this->assertTrue($request->get_strPrefAcceptType() == 'audio/ogg');
        $this->assertFalse($request->hasMediaType());
        $this->assertFalse($request->hasMediaType('site'));
        $this->assertFalse($request->hasMediaType('rest'));
        $this->assertTrue($request->hasMediaType('media'));
        
        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12.png';
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12.png');
        $this->assertTrue($request->get_strPrefAcceptType() == 'image/png');
        $this->assertFalse($request->hasMediaType());
        $this->assertFalse($request->hasMediaType('site'));
        $this->assertFalse($request->hasMediaType('rest'));
        $this->assertTrue($request->hasMediaType('media'));
        
        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12.jpg';
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12.jpg');
        $this->assertTrue($request->get_strPrefAcceptType() == 'image/jpeg');
        $this->assertFalse($request->hasMediaType());
        $this->assertFalse($request->hasMediaType('site'));
        $this->assertFalse($request->hasMediaType('rest'));
        $this->assertTrue($request->hasMediaType('media'));
        
        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12.gif';
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12.gif');
        $this->assertTrue($request->get_strPrefAcceptType() == 'image/gif');
        $this->assertFalse($request->hasMediaType());
        $this->assertFalse($request->hasMediaType('site'));
        $this->assertFalse($request->hasMediaType('rest'));
        $this->assertTrue($request->hasMediaType('media'));
        
        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12.svg';
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12.svg');
        $this->assertTrue($request->get_strPrefAcceptType() == 'image/svg+xml');
        $this->assertFalse($request->hasMediaType());
        $this->assertFalse($request->hasMediaType('site'));
        $this->assertFalse($request->hasMediaType('rest'));
        $this->assertTrue($request->hasMediaType('media'));
        
        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12.css';
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12.css');
        $this->assertTrue($request->get_strPrefAcceptType() == 'text/css');
        $this->assertTrue($request->hasMediaType());
        $this->assertTrue($request->hasMediaType('site'));
        $this->assertFalse($request->hasMediaType('rest'));
        $this->assertTrue($request->hasMediaType('media'));
        
        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12.html';
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12.html');
        $this->assertTrue($request->get_strPrefAcceptType() == 'text/html');
        $this->assertTrue($request->hasMediaType());
        $this->assertTrue($request->hasMediaType('site'));
        $this->assertTrue($request->hasMediaType('rest'));
        $this->assertFalse($request->hasMediaType('media'));
        
        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12.csv';
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12.csv');
        $this->assertTrue($request->get_strPrefAcceptType() == 'text/csv');
        $this->assertFalse($request->hasMediaType());
        $this->assertFalse($request->hasMediaType('site'));
        $this->assertTrue($request->hasMediaType('rest'));
        $this->assertFalse($request->hasMediaType('media'));

        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12.xml';
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12.xml');
        $this->assertTrue($request->get_strPrefAcceptType() == 'text/xml');
        $this->assertFalse($request->hasMediaType());
        $this->assertFalse($request->hasMediaType('site'));
        $this->assertTrue($request->hasMediaType('rest'));
        $this->assertFalse($request->hasMediaType('media'));

        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12.txt';
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12.txt');
        $this->assertTrue($request->get_strPrefAcceptType() == 'text/plain');
        $this->assertTrue($request->hasMediaType());
        $this->assertTrue($request->hasMediaType('site'));
        $this->assertTrue($request->hasMediaType('rest'));
        $this->assertFalse($request->hasMediaType('media'));

        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12.vcd';
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12.vcd');
        $this->assertTrue($request->get_strPrefAcceptType() == 'text/vcard');
        $this->assertTrue($request->hasMediaType());
        $this->assertTrue($request->hasMediaType('site'));
        $this->assertTrue($request->hasMediaType('rest'));
        $this->assertFalse($request->hasMediaType('media'));

        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12.ogv';
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12.ogv');
        $this->assertTrue($request->get_strPrefAcceptType() == 'video/ogg');
        $this->assertFalse($request->hasMediaType());
        $this->assertFalse($request->hasMediaType('site'));
        $this->assertFalse($request->hasMediaType('rest'));
        $this->assertTrue($request->hasMediaType('media'));

        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12.avi';
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12.avi');
        $this->assertTrue($request->get_strPrefAcceptType() == 'video/mpeg');
        $this->assertFalse($request->hasMediaType());
        $this->assertFalse($request->hasMediaType('site'));
        $this->assertFalse($request->hasMediaType('rest'));
        $this->assertTrue($request->hasMediaType('media'));

        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12.mp4';
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12.mp4');
        $this->assertTrue($request->get_strPrefAcceptType() == 'video/mp4');
        $this->assertFalse($request->hasMediaType());
        $this->assertFalse($request->hasMediaType('site'));
        $this->assertFalse($request->hasMediaType('rest'));
        $this->assertTrue($request->hasMediaType('media'));

        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12.webm';
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12.webm');
        $this->assertTrue($request->get_strPrefAcceptType() == 'video/webm');
        $this->assertFalse($request->hasMediaType());
        $this->assertFalse($request->hasMediaType('site'));
        $this->assertFalse($request->hasMediaType('rest'));
        $this->assertTrue($request->hasMediaType('media'));

        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12.wmv';
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12.wmv');
        $this->assertTrue($request->get_strPrefAcceptType() == 'video/x-ms-wmv');
        $this->assertFalse($request->hasMediaType());
        $this->assertFalse($request->hasMediaType('site'));
        $this->assertFalse($request->hasMediaType('rest'));
        $this->assertTrue($request->hasMediaType('media'));

        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12.doc';
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12.doc');
        $this->assertTrue($request->get_strPrefAcceptType() == 'application/msword');
        $this->assertFalse($request->hasMediaType());
        $this->assertFalse($request->hasMediaType('site'));
        $this->assertFalse($request->hasMediaType('rest'));
        $this->assertTrue($request->hasMediaType('media'));

        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12.docx';
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12.docx');
        $this->assertTrue($request->get_strPrefAcceptType() == 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $this->assertFalse($request->hasMediaType());
        $this->assertFalse($request->hasMediaType('site'));
        $this->assertFalse($request->hasMediaType('rest'));
        $this->assertTrue($request->hasMediaType('media'));
        
        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12.odt';
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12.odt');
        $this->assertTrue($request->get_strPrefAcceptType() == 'application/vnd.oasis.opendocument.text');
        $this->assertFalse($request->hasMediaType());
        $this->assertFalse($request->hasMediaType('site'));
        $this->assertFalse($request->hasMediaType('rest'));
        $this->assertTrue($request->hasMediaType('media'));

        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12.xls';
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12.xls');
        $this->assertTrue($request->get_strPrefAcceptType() == 'application/vnd.ms-excel');
        $this->assertFalse($request->hasMediaType());
        $this->assertFalse($request->hasMediaType('site'));
        $this->assertFalse($request->hasMediaType('rest'));
        $this->assertTrue($request->hasMediaType('media'));

        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12.xlsx';
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12.xlsx');
        $this->assertTrue($request->get_strPrefAcceptType() == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertFalse($request->hasMediaType());
        $this->assertFalse($request->hasMediaType('site'));
        $this->assertFalse($request->hasMediaType('rest'));
        $this->assertTrue($request->hasMediaType('media'));

        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12.ods';
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12.ods');
        $this->assertTrue($request->get_strPrefAcceptType() == 'application/vnd.oasis.opendocument.spreadsheet');
        $this->assertFalse($request->hasMediaType());
        $this->assertFalse($request->hasMediaType('site'));
        $this->assertFalse($request->hasMediaType('rest'));
        $this->assertTrue($request->hasMediaType('media'));

        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12.ppt';
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12.ppt');
        $this->assertTrue($request->get_strPrefAcceptType() == 'application/vnd.ms-powerpoint');
        $this->assertFalse($request->hasMediaType());
        $this->assertFalse($request->hasMediaType('site'));
        $this->assertFalse($request->hasMediaType('rest'));
        $this->assertTrue($request->hasMediaType('media'));

        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12.pptx';
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12.pptx');
        $this->assertTrue($request->get_strPrefAcceptType() == 'application/vnd.openxmlformats-officedocument.presentationml.presentation');
        $this->assertFalse($request->hasMediaType());
        $this->assertFalse($request->hasMediaType('site'));
        $this->assertFalse($request->hasMediaType('rest'));
        $this->assertTrue($request->hasMediaType('media'));

        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12.odp';
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12.odp');
        $this->assertTrue($request->get_strPrefAcceptType() == 'application/vnd.oasis.opendocument.presentation');
        $this->assertFalse($request->hasMediaType());
        $this->assertFalse($request->hasMediaType('site'));
        $this->assertFalse($request->hasMediaType('rest'));
        $this->assertTrue($request->hasMediaType('media'));

        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12.js';
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12.js');
        $this->assertTrue($request->get_strPrefAcceptType() == 'text/javascript');
        $this->assertFalse($request->hasMediaType());
        $this->assertFalse($request->hasMediaType('site'));
        $this->assertFalse($request->hasMediaType('rest'));
        $this->assertTrue($request->hasMediaType('media'));

        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12.random';
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12.random');
        $this->assertTrue($request->get_strPrefAcceptType() == 'unknown/random');
        $this->assertFalse($request->hasMediaType());
        $this->assertFalse($request->hasMediaType('site'));
        $this->assertFalse($request->hasMediaType('rest'));
        $this->assertFalse($request->hasMediaType('media'));
    }
    
    public function testSimulatedServerConnectionWithHttpAuthorization()
    {
        $this->server->SERVER['HTTP_AUTHORIZATION'] = 'basic:' . base64_encode('username:password');
        $request = $this->parseServer();
        $this->assertTrue($request->get_strRequestMethod() == 'get');
        $this->assertTrue($request->get_requestUrlFull() == 'https://username:password@localhost:1443/service/talk/12/');
        $this->assertTrue($request->get_requestUrlExParams() == 'https://username:password@localhost:1443/service/talk/12/');
        $this->assertTrue($request->get_strUsername() == 'username');
        $this->assertTrue($request->get_strPassword() == 'password');
    }

    public function testSimulatedServerConnectionWithPhpAuth()
    {
        $this->server->SERVER['PHP_AUTH_USER'] = 'username';
        $this->server->SERVER['PHP_AUTH_PW'] = 'password';
        $request = $this->parseServer();
        $this->assertTrue($request->get_strRequestMethod() == 'get');
        $this->assertTrue($request->get_requestUrlFull() == 'https://username:password@localhost:1443/service/talk/12/');
        $this->assertTrue($request->get_requestUrlExParams() == 'https://username:password@localhost:1443/service/talk/12/');
        $this->assertTrue($request->get_strUsername() == 'username');
        $this->assertTrue($request->get_strPassword() == 'password');
    }

    public function testSimulatedServerConnectionWithGetParameters()
    {
        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12/?dostuff&param=1';
        $this->server->GET = array(
            'dostuff' => '',
            'param' => '1'
        );
        $this->server->REQUEST = &$this->server->GET;
        $request = $this->parseServer();
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12/?dostuff&param=1');
        $this->assertTrue($request->get_requestUrlExParams() == 'https://localhost:1443/service/talk/12/');
        $arrParameters = $request->get_arrRqstParameters();
        $this->assertTrue(count($arrParameters) == 2);
        $this->assertTrue($arrParameters['dostuff'] == '');
        $this->assertTrue($arrParameters['param'] == '1');
    }

    public function testSimulatedServerConnectionWithHeadParameters()
    {
        $this->server->SERVER['HTTP_IF_MODIFIED_SINCE'] = gmdate('D, d M Y H:i:s \G\M\T', strtotime('2012-01-01')) . ';apparently sometimes some data appears here';
        $this->server->SERVER['HTTP_IF_NONE_MATCH'] = '"' . sha1('somecontent') . '", W/"' . sha1('someothercontent') . '"';
        $this->server->SERVER['REQUEST_METHOD'] = 'HEAD';
        $request = $this->parseServer();
        $this->assertTrue($request->get_strRequestMethod() == 'head');
        $this->assertTrue($request->get_hasIfModifiedSince() == 'Sun, 01 Jan 2012 00:00:00 GMT');
        $arrIfNoneMatch = $request->get_hasIfNoneMatch();
        $this->assertTrue($arrIfNoneMatch[0] == sha1('somecontent'));
        $this->assertTrue($arrIfNoneMatch[1] == sha1('someothercontent'));
    }

    public function testSimulatedServerConnectionWithPostParameters()
    {
        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12/';
        $this->server->SERVER['REQUEST_METHOD'] = 'POST';
        $this->server->POST = array(
            'dostuff' => '',
            'param' => '1'
        );
        $this->server->FILES = array(
            array(
                "file" => array(
                    "name" => "a_file",
                    "type" => "application/octet-stream",
                    "tmp_name" => "/tmp/phphwKqWs",
                    "error" => 0,
                    "size" => 2237
                )
            )
        );
        $this->server->REQUEST = &$this->server->POST;
        $request = $this->parseServer();
        $this->assertTrue($request->get_strRequestMethod() == 'post');
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12/');
        $arrParameters = $request->get_arrRqstParameters();
        $this->assertTrue(count($arrParameters) == 3);
        $this->assertTrue($arrParameters['dostuff'] == '');
        $this->assertTrue($arrParameters['param'] == '1');
        $this->assertTrue(is_array($arrParameters['_FILES']));
    }

    public function testSimulatedServerConnectionWithPutCall()
    {
        $this->server->SERVER['REQUEST_URI'] = '/service/talk/';
        $this->server->SERVER['REQUEST_METHOD'] = 'PUT';
        $this->server->INPUT = 'User=Person';
        $request = $this->parseServer();
        $this->assertTrue($request->get_strRequestMethod() == 'put');
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/');
        $arrRqstParameters = $request->get_arrRqstParameters();
        $this->assertTrue(is_array($arrRqstParameters));
        $this->assertTrue(count($arrRqstParameters) == 1);
        $this->assertTrue($arrRqstParameters['User'] == 'Person');
    }

    public function testSimulatedServerConnectionWithDeleteCall()
    {
        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12/';
        $this->server->SERVER['REQUEST_METHOD'] = 'DELETE';
        $request = $this->parseServer();
        $this->assertTrue($request->get_strRequestMethod() == 'delete');
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12/');
    }

    public function testSimulatedServerConnectionWithDeletePostValue()
    {
        $this->server->SERVER['REQUEST_METHOD'] = 'POST';
        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12/';
        $this->server->POST['HTTPaction'] = 'DELETE';
        $request = $this->parseServer();
        $this->assertTrue($request->get_strRequestMethod() == 'delete');
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12/');
    }
    

    public function testSimulatedServerConnectionWithHeadGetValue()
    {
        $this->server->SERVER['REQUEST_METHOD'] = 'GET';
        $this->server->SERVER['REQUEST_URI'] = '/service/talk/12/?HTTPaction=HEAD';
        $this->server->GET['HTTPaction'] = 'HEAD';
        $request = $this->parseServer();
        $this->assertTrue($request->get_strRequestMethod() == 'head');
        $this->assertTrue($request->get_requestUrlFull() == 'https://localhost:1443/service/talk/12/?HTTPaction=HEAD');
    }

    public function testSimulatedFileConnection()
    {
        $request = $this->parseFile();
        $this->assertTrue($request->get_strRequestMethod() == 'file');
        $this->assertTrue(strpos($request->get_requestUrlFull(), "/Tests/Libraries/IO/HandlerTest.php") > 0);
        $this->assertTrue(strpos($request->get_requestUrlExParams(), "/Tests/Libraries/IO/HandlerTest.php") > 0);
        $this->assertTrue($request->get_strUsername() == null);
        $this->assertTrue($request->get_strPassword() == null);
        $this->assertTrue($request->get_strUserAgent() == null);
        $this->assertTrue($request->get_strPrefAcceptType() == 'text/html');
        $arrPathItems = $request->get_arrPathItems();
        $this->assertTrue(count($arrPathItems) > 0);
        $this->assertTrue($arrPathItems[count($arrPathItems) - 1] == 'HandlerTest.php');
        $arrParameters = $request->get_arrRqstParameters();
        $this->assertTrue(count($arrParameters) == 2);
        $this->assertTrue($arrParameters['param'] == "1");
        $this->assertTrue($arrParameters['dostuff'] == '');
        $this->assertTrue($request->get_strBasePath() == 'file:///');
    }

    public function testSimulatedFileConnectionWithNoPath()
    {
        $this->file->GLOBALS['argv'][0] = 'bootstrap.php';
        $request = $this->parseFile();
        $this->assertTrue(strpos($request->get_requestUrlFull(), "/bootstrap.php") > 0);
    }
    
    /**
     * @expectedException LogicException
     */
    public function testGetGenerationTimeWithoutSettingItFirst()
    {
        @IO_Handler::getGenerationTime();
    }
    
    public function testSetGenerationTime()
    {
        IO_Handler::setGenerationTime(0);
        $this->assertTrue(IO_Handler::getGenerationTime(0) == 0);
        $this->assertTrue(IO_Handler::getGenerationTime(10) == 10);
    }
    
    public function testSetGenerationTimeRecalc()
    {
        IO_Handler::setGenerationTime();
        $this->assertTrue(IO_Handler::getGenerationTime() >= 0);
    }

    /**
     * @expectedException LogicException
     */
    public function testSetGenerationTimeTwice()
    {
        IO_Handler::setGenerationTime(0);
        @IO_Handler::setGenerationTime(0);
    }
    
    public function testRedirection()
    {
        $this->parseServer();
        IO_Handler::redirectTo('http://www.google.com');
    }
    
    public function testValidTranslation()
    {
        $this->parseServer();
        $this->assertTrue(IO_Handler::translate(array('en' => 'test'), 'en') == 'test');
        $this->assertTrue(IO_Handler::translate(array('en' => 'test')) == 'test');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testTranslationWithNoArray()
    {
        $this->parseServer();
        @IO_Handler::translate();
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testTranslationWithNoArrayValues()
    {
        $this->parseServer();
        @IO_Handler::translate(array());
    }
}

class IO_Handler_Testable extends IO_Handler
{
    public static function reset()
    {
        self::$objData = null;
        self::$floatGenerationTime = null;
    }
}