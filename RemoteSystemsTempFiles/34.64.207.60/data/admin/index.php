<?php 
class AppController {
    
    /**
     * App Type
     *
     * @var string
     */
    private $appType = '';
    
    /**
     * scriptUrl
     *
     * @var string
     */
    private $scriptUrl = '';
    
    public function __construct($appType = '', $scriptUrl = '') {
        $this -> appType = $appType;
        $this -> scriptUrl = $scriptUrl;
        if (empty($this -> appType)) {
            $this -> appType = 'web';
        }
        if (empty($this -> scriptUrl)) {
            $this -> scriptUrl = '/index';
        }
    }
    
    private function getEnvVar($key = '') {
        if (!empty($GLOBALS['_SERVER'][$key])) {
            return $GLOBALS['_SERVER'][$key];
        } else if (!empty($GLOBALS['HTTP_SERVER_VARS'][$key])) {
            return $GLOBALS['HTTP_SERVER_VARS'][$key];
        } else {
            return '';
        }
    }
    
    private $contentType = '';
    public function GetOpenUrlHeader($curl, $headerLine ) {
        $matches = Array();
        if (preg_match('#Content-Type: (.+)#', $headerLine, $matches)) {
            $this -> contentType = $matches[1];
        }
        return strlen($headerLine);
    }
    
    /**
     *
     * @return string
     */
    public function GetOpenUrl($url, $fields, $method = 'GET', $header = Array())
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_URL, $url);
        if (!empty($header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        if (empty($fields)) {
            $fields = Array();
        }
        switch($method) {
            case 'POST' :
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
                break;
            default :
                curl_setopt($ch, CURLOPT_POST, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
                break;
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, array($this , 'GetOpenUrlHeader'));
        $response = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($status_code == 200) {
            $matches = Array();
            if (preg_match('#^redirect:([^ ]+)$#', $response, $matches)) {
                header('Location: ' . $matches[1]);
            } else {
                if (empty($this -> contentType)) {
                    $this -> contentType = 'text/html';
                }
                header('Content-Type: '. $this -> contentType);
                echo $response;
            }
            exit();
        } else {
            return "error";
        }
    }
    
    
    public function parseApp() {
       if (!file_exists(HOME_DIR . '/index.html')) {
            echo "NOT READY";
            exit();
        }
        $contents = file_get_contents(HOME_DIR . '/index.html');
        $matches = Array();
        $fields = Array();
        if (preg_match('#styles\.([a-z0-9]+)\.css#', $contents, $matches)) {
            $fields['styles'] = $matches[1];
        }
        if (preg_match('#runtime\.([a-z0-9]+)\.js#', $contents, $matches)) {
            $fields['runtime'] = $matches[1];
        }
        if (preg_match('#"es2015\-polyfills\.([a-z0-9]+)\.js#', $contents, $matches)) {
            $fields['es2015Polyfills'] = $matches[1];
        }
        if (preg_match('#"polyfills\.([a-z0-9]+)\.js#', $contents, $matches)) {
            $fields['polyfills'] = $matches[1];
        }
        if (preg_match('#main\.([a-z0-9]+)\.js#', $contents, $matches)) {
            $fields['main'] = $matches[1];
        }
        $fields['userAgent'] = $this -> getEnvVar('HTTP_USER_AGENT');
        $fields['acceptLanguage'] = $this -> getEnvVar('HTTP_ACCEPT_LANGUAGE');
        $fields['remoteAddr'] = $_SERVER['REMOTE_ADDR'];
        $appType = $this -> appType;
        $scriptUrl = $this -> scriptUrl;
        if (empty($scriptUrl)) {
            $scriptUrl = 'index';
        }
        if (empty($appType)) {
            $appType = 'web';
        }
        if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
            $fields['referer'] = $_SERVER['HTTP_REFERER'];
        }
        $fields['scriptUrl'] = $scriptUrl;
        $fields['appType'] = $appType;
        if (isset($_GET['fix'])) {
            $fields['useFix'] = 'Y';
        } else {
            $fields['useFix'] = 'N';
        }
        $fields['DATA_GET'] = $_GET;
        $fields['DATA_POST'] = $_POST;
        
        return $this -> GetOpenUrl(API_URL . '/'.$appType.'/html/index.do', $fields, 'POST');
    }
}

$apiType = 'web';

switch ( $_SERVER ['HTTP_HOST']) {
    
    case 'admin.connerstone.com':
        define ( "HOME_DIR", dirname ( __FILE__ ) . '/src' );
        define ( "API_URL", 'https://api.connerstone.com' );
        $apiType = 'admin';
        
        break;
    default :
        echo $_SERVER ['HTTP_HOST'];
        exit();
}

$app = new AppController($apiType, $_SERVER['SCRIPT_URL']);
echo $app -> parseApp();


?>
