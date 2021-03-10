<?php

/**
 * Project:	KBMALL 1.0 Project
 * File:	libs/Service/AbstractService.class.php
 *
 * @link http://www.hanbiz.kr/
 * @author Kim Jong-gab <outmind0@naver.com>
 * @version 1.0
 * @since 1.0 
 * @copyright 2001-2017 Hanbiz, Inc.
 * @package kbmall
 */
namespace Service;

use Vo\AbstractMyArticleAnswerVo;
use Vo\AbstractMyArticleVo;
use Vo\AccessLogVo;
use Vo\AddressVo;
use Vo\DisplayMainItemVo;
use Vo\DownloadFormVo;
use Vo\ExternalVideoVo;
use Vo\FileLogVo;
use Vo\FileVo;
use Vo\LocaleTextVo;
use Vo\LoginInfoVo;
use Vo\MessageVo;
use Vo\PagingVo;
use Vo\RefGoodsImageVo;
use Vo\RequestVo;
use AbstractDatabase;
use DatabaseHelper;
use Exception;
use KbmException;
use stdClass;
use Vo\BaseCurrencyUnitVo;

/**
 * �꽌鍮꾩뒪 Abstract
 */
abstract class AbstractService
{

    /**
     * 紐곗븘�씠�뵒
     *
     * @var string
     */
    public $mallId = '';

    /**
     * �궗�씠�듃 �븘�씠�뵒
     *
     * @var string
     */
    public $siteId = '';

    /**
     * 濡쒓렇�씤 �젙蹂�
     *
     * @var LoginInfoVo
     */
    public $loginInfo = null;

    /**
     * �깮�꽦�옄
     *
     * @param string $mallSiteId
     * @param LoginInfoVo $loginInfo
     * @throws Exception
     */
    public function __construct($mallSiteId = '', LoginInfoVo $loginInfo = null)
    {
        if (empty($mallSiteId) && ! empty(self::$loadedMallId)) {
            $mallSiteId = self::$loadedMallId;
        }
        if (empty($loginInfo) && ! empty(self::$loadedLoginInfo)) {
            $loginInfo = self::$loadedLoginInfo;
        }
        list ($this->mallId, $this->siteId) = explode("@", $mallSiteId . '@');
        $this->loginInfo = $loginInfo;
        if (empty($this->mallId)) {
            throw new Exception(get_class($this) . ' unValid MAll ID', KbmException::DATA_ERROR_UNKNOWN);
        } else {
            if (empty(self::$loadedMallId)) {
                self::$loadedMallId = $this->mallId . '@' . $this->siteId;
            }
            if (empty(self::$loadedLoginInfo)) {
                self::$loadedLoginInfo = $this->loginInfo;
            }
        }
    }

    /**
     * �쇅遺� �옄猷� 媛��졇�삤湲�
     *
     * @param string $url
     * @param mixed[] $fields
     * @param string $method
     * @param string[] $header
     * @return stdClass
     */
    public function GetOpenJson($url, $fields = Array(), $method = 'GET', $header = Array(), $passphrase = '')
    {
        list ($statusCode, $response) = $this->GetOpenJsonRaw($url, $fields, $method, $header, $passphrase);
        if ($statusCode == 200) {
            return json_decode($response);
        } else {
            return null;
        }
    }

    /**
     * �쇅遺� �옄猷� 媛��졇�삤湲�
     *
     * @param string $url
     * @param mixed[] $fields
     * @param string $method
     * @param string[] $header
     * @return [stdClass]
     */
    public function GetOpenJsonRaw($url, $fields = Array(), $method = 'GET', $header = Array(), $passphrase = '')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        $realHeader = Array();
        if (! empty($header)) {
            foreach ($header as $key => $value) {
                if (strpos($value, ":") > 0) {
                    $realHeader[] = $value;
                } else {
                    $realHeader[] = $key . ': ' . $value;
                }
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $realHeader);
        }
        if (empty($fields)) {
            $fields = Array();
        }
        switch ($method) {
            case 'POST':
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
                break;
            case 'CRYPT':
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->cryptoJsAesEncrypt($fields, $passphrase)));
                break;
            case 'JSON':
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
                break;
            case 'PUT':
            case 'DELETE':
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
                break;
            default:
                if (! empty($fields)) {
                    $url .= '?' . http_build_query($fields);
                }
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, false);
                break;
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $apiLogPath = $this->CreateLogFile('/api/' . date('ym') . '/api_' . date('Ymd') . '.txt');
        $debugLine = Array();
        $debugLine[] = 'URL : ' . $url;
        switch ($method) {
            case 'POST':
                $debugLine[] = 'DATA : ' . print_r($fields, true);
                break;
            case 'JSON':
                $debugLine[] = 'DATA : ' . json_encode($fields);
                break;
            case 'CRYPT':
                $debugLine[] = 'DATA - CRYPT : ' . json_encode($fields);
                break;
        }
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $debugLine[] = 'HEADER : ' . implode("\n", $realHeader);
        $debugLine[] = 'METHOD : ' . $method;
        $debugLine[] = 'HTTPCODE : ' . $statusCode;
        $debugLine[] = 'RESPONSE : ' . $response;
        error_log("===============\n" . implode("\n", $debugLine) . "\n\n", 3, $apiLogPath);
        curl_close($ch);
        if (0 === mb_strpos($response, "\x1f" . "\x8b" . "\x08")) {
            $response = $this->GetGzResponseDecode($response);
        }
        return [
            $statusCode,
            $response
        ];
    }

    public function GetGzResponseDecode($data)
    {
        return gzinflate(substr($data, 10, - 8));
    }

    /**
     * 愿�由ъ옄 �뿬遺� �솗�씤
     *
     * @param LoginInfoVo $vo
     * @param string $checkType
     * @return boolean
     */
    public function IsUserAdmin(LoginInfoVo $vo = null, $checkType = 'A')
    {
        if (! empty($vo) && ! empty($vo->memNo)) {
            switch ($checkType) {
                case 'A':
                    return ($vo->memLevel == 'S' || $vo->memLevel == 'K' || $vo->memLevel == 'A' || $vo->memLevel == 'E');
                case 'S':
                    return ($vo->memLevel == 'S');
                case 'P':
                    return ($vo->memLevel == 'P');
            }
        }
        return false;
    }

    /**
     * 愿�由ъ옄 怨듦툒�뾽泥� 踰덊샇 媛��졇�삤湲�
     *
     * @return integer
     */
    public function GetUserAdminScmNo()
    {
        $vo = $this->getLoginInfo();
        if (! empty($vo)) {
            if ($this->IsUserAdmin($vo, 'P')) {
                return $vo->scmNo;
            }
        }
        return 0;
    }

    /**
     * �젒洹� �븘�씠�뵾 二쇱냼 �옄�졇�삤湲�
     *
     * @return string
     */
    public function GetUserIp()
    {
        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * 臾몄옄�뿉 BR �깭洹� �꽔湲�
     *
     * @param string $str
     * @return string
     */
    public function nl2br($str = '')
    {
        return nl2br($str);
    }

    /**
     * �삁�쇅 媛��졇�삤湲�
     *
     * @param string $code
     * @param string $msg
     * @throws Exception
     */
    public function GetException($code = 0, $msg = 'error_unknown')
    {
        switch ($msg) {
            case 'error_unknown':
                switch ($code) {
                    case KbmException::DATA_ERROR_NOTFOUND:
                    case KbmException::DATA_ERROR_VIEW:
                        $msg = '�빐�떦 �옄猷뚭� 諛쒓껄�릺吏� �븡�븯�뒿�땲�떎.';
                        break;
                    case KbmException::DATA_ERROR_CREATE:
                        $msg = '�옄猷� �깮�꽦以� �삤瑜섍� 諛쒖깮�븯���뒿�땲�떎.';
                        break;
                    case KbmException::DATA_ERROR_DELETE:
                        $msg = '�옄猷� �궘�젣以� �삤瑜섍� 諛쒖깮�븯���뒿�땲�떎.';
                        break;
                    case KbmException::DATA_ERROR_UPDATE:
                        $msg = '�옄猷� �뾽�뜲�씠�듃以� �삤瑜섍� 諛쒖깮�븯���뒿�땲�떎.';
                        break;
                    case KbmException::DATA_ERROR_AUTH:
                        $msg = '�빐�떦 �옄猷뚯뿉 ���븳 �젒洹� 沅뚰븳�씠 �뾾�뒿�땲�떎.';
                        break;
                    default:
                        $msg = '�븣�닔 �뾾�뒗 �삤瑜섍� 諛쒖깮�븯���뒿�땲�떎.';
                        break;
                }
                break;
        }
        $msgValue = $msg;
        throw new Exception($msgValue, $code);
    }

    /**
     * 罹먯떆 �궎 �뤃�뱶 紐� 媛��졇�삤湲�
     *
     * @param string $cachesFile
     * @param integer $uid
     * @return string
     */
    public function GetCacheDirName($cachesFile = '', $uid = 0)
    {
        return $cachesFile . '_' . $uid;
    }

    /**
     * �꽕�젙�맂 罹먯돩 �궘�젣
     *
     * @param string $cachesFile
     */
    public function UnSetCacheFile($cachesFile = '', $isGlobal = false)
    {
        $cacheService = CacheService::GetCacheService();
        if (strpos($cachesFile, '*') !== false) {
            $keys = $cacheService->keys($cachesFile, $isGlobal);
            foreach ($keys as $key) {
                $cacheService->delete($key, $isGlobal);
            }
        } else {
            $cacheService->delete($cachesFile, $isGlobal);
        }
    }

    /**
     * �꽕�젙�맂 罹먯돩 TTL �꽕�젙
     *
     * @param string $cachesFile
     */
    public function SetCacheFileTtl($cachesFile = '', $ttl = 0, $isGlobal = false)
    {
        $cacheService = CacheService::GetCacheService();
        if (strpos($cachesFile, '*') !== false) {
            $keys = $cacheService->keys($cachesFile, $isGlobal);
            foreach ($keys as $key) {
                $cacheService->expire($key, $ttl, $isGlobal);
            }
        } else {
            $cacheService->expire($cachesFile, $ttl, $isGlobal);
        }
    }

    /**
     * ���옣�맂 罹먯떆 �뙆�씪�쓽 TTL 媛��졇�삤湲�
     *
     * @param string $cachesFile
     * @param boolean $isGlobal
     * @return number[]|number
     */
    public function GetCacheFileTtl($cachesFile = '', $isGlobal = false)
    {
        $cacheService = CacheService::GetCacheService();
        if (strpos($cachesFile, '*') !== false) {
            $keys = $cacheService->keys($cachesFile, $isGlobal);
            $ttlList = Array();
            foreach ($keys as $key) {
                $ttlList[$key] = $cacheService->ttl($key, $isGlobal);
            }
            return $ttlList;
        } else {
            return $cacheService->ttl($cachesFile, $isGlobal);
        }
    }

    /**
     * 罹먯돩 �솚寃� ���옣
     *
     * @param string $cachesFile
     * @param string $contents
     * @param boolean $isStyle
     */
    public function SetCacheConf($cachesFile = '', $contents = '', $isStyle = true)
    {
        if ($isStyle) {
            file_put_contents(FILE_DIR . '/' . $cachesFile, $contents);
        }
    }

    /**
     * 罹먯쐞 �뙆�씪 ���옣
     *
     * @param string $cachesFile
     * @param stdClass|object $vo
     * @param integer $ttl
     */
    public function SetCacheFile($cachesFile = '', $vo = '', $ttl = 6000, $isGlobal = false)
    {
        $cacheService = CacheService::GetCacheService();
        $cacheService->setObject($cachesFile, $vo, $ttl, $isGlobal);
    }

    /**
     * 罹먯돩 �뙆�씪 媛��졇 �삤湲�
     *
     * @param string $cachesFile
     * @param boolean $useDevCheck
     * @return mixed
     */
    public function GetCacheFile($cachesFile = '', $useDevCheck = false, $isGlobal = false)
    {
        if ($useDevCheck && DEV_MODE) {
            return null;
        } else {
            $cacheService = CacheService::GetCacheService();
            return $cacheService->getObject($cachesFile, $isGlobal);
        }
    }

    /**
     * �꽌鍮꾩뒪 罹먯돩 �궎 媛��졇�삤湲�
     *
     * @param string $mainCode
     * @param string $subCode
     * @param string $sufixCode
     * @return string
     */
    public function GetServiceCacheKey($mainCode = '', $subCode = '', $sufixCode = '', $addSiteKey = false)
    {
        $cacheKeyList = Array();
        switch ($mainCode) {
            case '*':
                $cacheKeyList[] = $mainCode;
                break;
            case '':
            default:
                if (empty($mainCode)) {
                    $cacheKeyList[] = 'none';
                } else {
                    $cacheKeyList[] = $mainCode;
                }
                switch ($subCode) {
                    case '*':
                        $cacheKeyList[] = $subCode;
                        break;
                    case '':
                    default:
                        if (empty($subCode)) {
                            $cacheKeyList[] = 'none';
                        } else {
                            $cacheKeyList[] = $subCode;
                        }
                        switch ($sufixCode) {
                            case '*':
                                $cacheKeyList[] = $sufixCode;
                                break;
                            case '':
                            default:
                                if (empty($sufixCode)) {
                                    $cacheKeyList[] = 'none';
                                } else {
                                    $cacheKeyList[] = $sufixCode;
                                }
                        }
                }
                break;
        }
        if (! empty($this->siteId) && $addSiteKey) {
            $cacheKeyList[] = $this->siteId;
        }
        return $this->mallId . '_' . implode('_', $cacheKeyList);
    }

    /**
     * �뜲�씠�� 諛곗뿴�뿉�꽌 �꽌鍮꾩뒪 罹먯돩 �궎 媛��졇�삤湲�
     *
     * @param string $mainCode
     * @param array $data
     * @return string
     */
    public function GetServiceCacheKeyArray($mainCode = '', $data = Array())
    {
        $query = Array();
        foreach ($data as $key => $val) {
            $query[] = $key . '=' . json_encode($val);
        }
        return $this->mallId . '_' . $mainCode . '_' . md5(implode(',', $query));
    }

    /**
     * 濡쒓렇 �뙆�씪 留뚮뱾怨� �뙆�씪寃쎈줈 媛��졇�삤湲�
     *
     * @param string $fileName
     * @param boolean $append
     * @return string
     */
    public function CreateLogFile($fileName = '', $append = true)
    {
        $filePath = LOG_DIR . $fileName;
        if (! $append) {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        } else {
            if (file_exists($filePath) && filesize($filePath) > 1024 * 1024 * 5) {
                $fileSeqn = 0;
                while (true) {
                    $fileSeqn ++;
                    $fileRenamePath = LOG_DIR . $fileName . '.' . $fileSeqn;
                    if (! file_exists($fileRenamePath)) {
                        @rename($filePath, $fileRenamePath);
                        if (file_exists($filePath)) {
                            @unlink($filePath);
                        }
                        break;
                    }
                }
                clearstatcache(true);
            }
        }
        if (! file_exists($filePath)) {
            $dirPath = dirname($filePath);
            if (! file_exists($dirPath)) {
                mkdir($dirPath, 0777, true);
            }
            touch($filePath);
            chmod($filePath, 0777);
        }
        return $filePath;
    }

    /**
     * �뵒踰꾧렇 濡쒓렇 �궓湲곌린
     *
     * @param array $debugLine
     */
    public function DebugLog($debugLine = Array(), $logFileName = 'api_error_log')
    {
        if (! empty($debugLine)) {
            $apiLogPath = $this->CreateLogFile('/api/' . date('ym') . '/' . $logFileName . '_' . date('Ymd') . '.txt');
            $debugTxt = Array();
            $debugTxt[] = 'Date : ' . $this->getDateNow();
            foreach ($debugLine as $key => $val) {
                if (is_object($val)) {
                    $debugTxt[] = "'" . $key . "' : " . json_encode($val);
                } else {
                    $debugTxt[] = "'" . $key . "' : " . $val;
                }
            }
            error_log("===============\n" . implode("\n", $debugTxt) . "\n\n", 3, $apiLogPath);
        }
    }

    /**
     * �삤釉뚯젥�듃 �뵒踰꾧렇 濡쒓렇 �궓湲곌린
     *
     * @param mixed $vo
     */
    public function DebugLogObject($vo)
    {
        if (! empty($vo)) {
            $debugLine = Array();
            $debugLine['Date'] = $this->getDateNow();
            $debugLine['Name'] = get_class($vo);
            $debugLine['Value'] = json_encode($vo);
            $this->DebugLog($debugLine, 'debuglog');
        }
    }

    /**
     * �젒洹� 濡쒓렇 �꽕�젙
     *
     * @param string $accessKey
     * @param array $logs
     */
    public function setAccessLog($accessKey = '', array $logs = Array())
    {
        if (count($logs) > 0) {
            $logVo = new AccessLogVo();
            $logVo->accessIp = $_SERVER['REMOTE_ADDR'];
            $logVo->accessKey = strtolower($accessKey);
            $logVo->mallId = $this->mallId;
            if (! empty($this->loginInfo)) {
                $logVo->accessName = $this->loginInfo->memNm;
                $logVo->accessNo = $this->loginInfo->memNo;
            } else {
                $logVo->accessName = 'anonymous';
                $logVo->accessNo = 0;
            }
            $logVo->items = $logs;
            $accessDao = $this->GetDao('AccessLogDao');
            // $accessDao = new AccessLogDao();
            $accessDao->SetCreate($logVo);
        }
    }
    
    /**
     * �젒洹� 濡쒓렇 �궎 蹂�寃� 
     *
     * @param string $accessKey
     * @param string $oldAccessKey
     */
    public function changeAccessLogId($accessKey = '', $oldAccessKey = '')
    {
        if (!empty($accessKey)  && !empty($oldAccessKey)) {
            $logVo = new AccessLogVo();
            $logVo->mallId = $this->mallId;
            $logVo ->accessKey = $oldAccessKey;
            $logVo ->accessToContents = $accessKey;
            $accessDao = $this->GetDao('AccessLogDao');
            $accessDao->SetChangeKey($logVo);
        }
    }
    

    /**
     * �젒洹� 濡쒓렇 媛��졇�삤湲�
     *
     * @param array|object $oldVo
     * @param array|object $newVo
     * @param string $parentKey
     * @return AccessLogVo[]
     */
    protected function getAccessLog($oldVo = Array(), $newVo = Array(), $parentKey = '')
    {
        $log = Array();
        $voIsObject = true;
        if (is_array($newVo)) {
            $voIsObject = false;
        }
        foreach ($newVo as $key => $val) {
            $oldValue = null;
            if (! empty($oldVo)) {
                if (is_object($oldVo)) {
                    if (isset($oldVo->$key)) {
                        $oldValue = $oldVo->$key;
                    } else {
                        $oldValue = '';
                    }
                } else if (is_array($oldVo) && isset($oldVo[$key])) {
                    $oldValue = $oldVo[$key];
                } else {
                    $oldValue = '';
                }
            }
            $textOldValue = '';
            $textVal = $val;
            if (is_object($oldValue) || is_array($oldValue)) {
                $textOldValue = md5(json_encode($oldValue));
            } else {
                $textOldValue = md5($oldValue);
            }
            if (is_object($val) || is_array($val)) {
                $textVal = md5(json_encode($val));
            } else {
                $textVal = md5($val);
            }
            if ($textOldValue != $textVal) {
                if ($key == 'memPw') {
                    $oldValue = '**********';
                    $val = '**********';
                }
                if (is_object($val) || is_array($val)) {
                    $log = array_merge($log, $this->getAccessLog($oldValue, $val, $parentKey . ($voIsObject ? $key : '*') . '.'));
                } else if ($key != 'mallId' && $key != 'regDate' && $key != 'modDate') {
                    $logVo = new AccessLogVo();
                    if (is_object($val) || is_array($val)) {
                        $val = json_encode($val);
                    }
                    if (is_object($oldValue) || is_array($oldValue)) {
                        $oldValue = json_encode($oldValue);
                    }
                    if (is_string($val) && strlen($val) > 150 || (is_string($oldValue) && strlen($oldValue) > 150)) {
                        $perc = 0;
                        $sim = similar_text($oldValue, $val, $perc);
                        $logVo->accessFromContents = "text";
                        $logVo->accessToContents = "similarity: " . $sim . " (" . number_format($perc, 2) . "%)";
                    } else {
                        $logVo->accessFromContents = $oldValue;
                        $logVo->accessToContents = $val;
                    }
                    $logVo->accessTitle = $parentKey . ($voIsObject ? $key : '*');
                    $log[] = $logVo;
                }
            }
        }
        return $log;
    }

    /**
     * �뙆�씪 �궡�슜 媛��졇�삤湲�
     *
     * @param string $confName
     * @param string $locale
     * @param string $fileType
     * @return string
     */
    function GetFillContents($confName = '', $locale = 'ko', $fileType = 'html')
    {
        $fileName = '';
        $fileNameBasic = $confName;
        if (! empty($confName)) {
            switch ($locale) {
                case 'jp':
                case 'cn':
                case 'en':
                    $fileName = $confName . '_' . $locale;
                    break;
                default:
                    $fileName = $confName . '_ko';
                    break;
            }
        } else {
            switch ($locale) {
                case 'jp':
                case 'cn':
                case 'en':
                    $fileName = $locale;
                    break;
                default:
                    $fileName = 'ko';
                    break;
            }
        }
        $filePath = CONF_DIR . '/i18n/' . $fileName . '.' . $fileType;
        $filePathBasic = ! empty($fileNameBasic) ? CONF_DIR . '/i18n/' . $fileNameBasic . '.' . $fileType : '';
        if (! empty($filePath) && file_exists($filePath)) {
            return trim(implode('', file($filePath)));
        } else if (! empty($filePathBasic) && file_exists($filePathBasic)) {
            return trim(implode('', file($filePathBasic)));
        } else {
            return '';
        }
    }

    /**
     * �듅�젙 Json �뙆�씪濡� vo 梨꾩슦湲�
     *
     * @param object|stdClass $vo
     * @param string $confName
     * @param string $locale
     * @return string
     */
    function GetFillContentsJson($vo = null, $confName = '', $locale = 'ko')
    {
        if (! empty($vo) && is_object($vo)) {
            $contents = $this->GetFillContents($confName, $locale, 'json');
            if (empty($contents)) {
                $contents = $this->GetFillContents(strtolower($this->mallId) . '_' . $confName, $locale, 'json');
            }
            if (! empty($contents)) {
                $jsonData = json_decode($contents);
                if (! empty($jsonData)) {
                    foreach ($jsonData as $key => $value) {
                        if (empty($vo->$key)) {
                            $vo->$key = $value;
                        }
                    }
                }
            }
        }
        return $vo;
    }

    /**
     * �듅�젙 Json �뙆�씪�뿉�꽌 �듅�젙 媛� 媛��졇�삤湲�
     *
     * @param string $keyName
     * @param string $confName
     * @param string $locale
     * @return mixed
     */
    function GetFillContentsJsonText($keyName = '', $confName = '', $locale = 'ko')
    {
        if (! empty($keyName)) {
            $contents = $this->GetFillContents($confName, $locale, 'json');
            if (! empty($contents)) {
                $jsonData = json_decode($contents);
                if (! empty($jsonData) && isset($jsonData->$keyName) && ! empty($jsonData->$keyName)) {
                    return $jsonData->$keyName;
                }
            }
            $contents = $this->GetFillContents(strtolower($this->mallId) . '_' . $confName, $locale, 'json');
            if (! empty($contents)) {
                $jsonData = json_decode($contents);
                if (! empty($jsonData) && isset($jsonData->$keyName) && ! empty($jsonData->$keyName)) {
                    return $jsonData->$keyName;
                }
            }
        }
        return '';
    }

    /**
     * 寃��깋�슜 VO 媛��졇�삤湲�
     *
     * @param array|object $data
     * @param string|\Vo\SearchVo $clazzName
     * @return \Vo\SearchVo
     */
    public function GetFillSearchVo($data = null, $clazzName = 'SearchVo')
    {
        return $this->GetFill($data, $clazzName);
    }

    /**
     * 寃��깋�슜 VO 媛��졇�삤湲�
     *
     * @param array|object $data
     * @param string|\Vo\SearchVo $clazzName
     * @return \Vo\SearchVo
     */
    public function GetSearchVo($data = null, $clazzName = 'SearchVo', $numberSo = Array())
    {
        $searchVo = $this->GetFillSearchVo($data, $clazzName);
        if (! empty($searchVo->orderBy)) {
            if (is_array($searchVo->orderBy)) {
                $searchVo->orderBy = $searchVo->orderBy[0];
            }
        } else {
            $searchVo->orderBy = '';
        }
        if (isset($searchVo->query) && ! empty($searchVo->query)) {
            $searchVo->query = trim($searchVo->query);
            if (! empty($searchVo->query)) {
                if (! empty($numberSo) && ! empty($searchVo->so) && in_array($searchVo->so, $numberSo)) {
                    $numberStart = 0;
                    $numberEnd = 0;
                    $searchVo->query = preg_replace('#[^0-9\.\-~]#', '', $searchVo->query);
                    if (strpos($searchVo->query, '~') !== false) {
                        list ($numberStart, $numberEnd) = explode('~', $searchVo->query . '~0');
                        $numberStart = floatval(trim($numberStart));
                        $numberEnd = floatval(trim($numberEnd));
                    } else {
                        $numberEnd = $numberStart = floatval(trim($searchVo->query));
                    }
                    if ($numberStart > $numberEnd && ! empty($numberEnd)) {
                        $numberTmp = $numberStart;
                        $numberStart = $numberEnd;
                        $numberEnd = $numberTmp;
                    }
                    $searchVo->numberType = $searchVo->so;
                    $searchVo->numberStart = $numberStart;
                    $searchVo->numberEnd = $numberEnd;
                    $searchVo->so = '';
                    $searchVo->query = '';
                } else {
                    $searchVo->query = preg_replace('#[ ]{2,10}#', ' ', $searchVo->query);
                    $searchVo->query = str_replace(Array(
                        '_',
                        '%',
                        ' '
                    ), Array(
                        '\_',
                        '\%',
                        '%'
                    ), $searchVo->query);
                }
            }
        }
        if (isset($searchVo->queryList) && ! empty($searchVo->queryList)) {
            $queryList = Array();
            foreach ($searchVo->queryList as $query) {
                if (! empty($query)) {
                    $query = preg_replace('#[ ]{2,10}#', ' ', $query);
                    $query = str_replace(Array(
                        '%',
                        ' '
                    ), Array(
                        '\%',
                        '%'
                    ), $query);
                    if (! empty($query)) {
                        $queryList[] = $query;
                    }
                }
            }
            if (! empty($queryList)) {
                $queryAndList = Array();
                $queryOrList = Array();
                foreach ($queryList as $query) {
                    $subQueryList = Array();
                    $subQuery = explode("|", $query);
                    foreach ($subQuery as $orQuery) {
                        $orQuery = trim($orQuery);
                        if (! empty($orQuery)) {
                            if (! in_array($orQuery, $subQueryList)) {
                                $subQueryList[] = $orQuery;
                            }
                            if (! in_array($orQuery, $queryOrList)) {
                                $queryOrList[] = $orQuery;
                            }
                        }
                    }
                    if (! empty($subQueryList)) {
                        $queryAndList[] = $subQueryList;
                    }
                }
                if (! empty($queryAndList)) {
                    $searchVo->queryAndList = $queryAndList;
                    $searchVo->queryList = $queryOrList;
                } else {
                    $searchVo->queryAndList = Array();
                    $searchVo->queryList = Array();
                }
            } else {
                $searchVo->queryList = Array();
                $searchVo->queryAndList = Array();
            }
        } else {
            $searchVo->queryAndList = Array();
        }

        if (isset($searchVo->dateRange) && ! empty($searchVo->dateRange)) {
            list ($searchVo->dateStart, $searchVo->dateEnd) = explode('~', $searchVo->dateRange . '~');
        }
        if (isset($searchVo->dateStart) && ! empty($searchVo->dateStart)) {
            $searchVo->dateStart = date('Y-m-d H:i:s', strtotime($searchVo->dateStart));
        }
        if (isset($searchVo->dateEnd) && ! empty($searchVo->dateEnd)) {
            $searchVo->dateEnd = date('Y-m-d H:i:s', strtotime($searchVo->dateEnd));
        }
        return $searchVo;
    }

    /**
     * �뙆�씪 �떎�슫濡쒕뱶 �뤌 VO 媛��졇�삤湲�
     *
     * @param RequestVo $request
     * @param string $selectedItems
     * @param boolean $isPdf
     * @return DownloadFormVo
     */
    public function GetDownloadFormVo(RequestVo $request, $selectedItems = 'memNos', $isPdf = false)
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(360);
        $downloadFormVo = new DownloadFormVo();
        $downloadFormVo->mallId = $this->mallId;
        $downloadForm = $request->GetRequestVo('downloadForm');
        $downloadFormVo->downloadReason = $downloadForm->downloadReason;
        if (empty($downloadFormVo->downloadReason)) {
            $downloadFormVo->downloadReason = 'Unknown Reason';
        }
        $formSno = $downloadForm->formSno;
        if (empty($formSno)) {
            $downloadForm->formSno = '';
        }
        $searchRequest = null;
        switch ($downloadForm->whereFl) {
            case 'Q':
                $downloadFormVo->downloadMode = 'Q';
                $searchRequest = $request->GetRequestVo('formSearch');
                $downloadFormVo->downloadKeyword = $searchRequest->query;
                break;
            case 'S':
                $downloadFormVo->downloadMode = 'S';
                $searchRequest = new RequestVo(new stdClass(), false);
                $searchRequest->$selectedItems = $request->GetItemArray('selectedItems');
                break;
            case 'A':
            default:
                $downloadFormVo->downloadMode = 'A';
                $searchRequest = new RequestVo(new stdClass(), false);
                $downloadFormVo->downloadKeyword = $searchRequest->query;
                break;
        }
        $searchRequest->mallId = $this->mallId;
        $searchRequest->offset = 0;
        $searchRequest->limit = intval($downloadForm->pageNum);
        if ($searchRequest->limit > 20000) {
            $searchRequest->limit = 20000;
        }
        if ($searchRequest->limit < 3) {
            $searchRequest->limit = 3;
        }
        $downloadFormVo->formSno = $downloadForm->formSno;
        $maskingArea = '';
        if (! $isPdf) {
            if (! empty($downloadFormVo->formSno)) {
                $policy = $this->GetServicePolicy();
                $excelFormVo = $policy->GetExcelFormView($downloadFormVo->formSno);
                $downloadFormVo->fieldVoList = Array();
                $downloadFormVo->byitemDownload = $excelFormVo->byitemDownload;
                $downloadFormVo->fieldList = $excelFormVo->excelForm;
                if (! empty($excelFormVo->excelShortDt)) {
                    $downloadFormVo->fieldShortDt = $excelFormVo->excelShortDt;
                }
                $downloadFormVo->sheetTitle = $excelFormVo->title;

                $downloadFormVo->location = $excelFormVo->location;
                switch ($excelFormVo->location) {
                    case 'order_01': // 寃곗젣�떎�뙣/�떆�룄由ъ뒪�듃 CODE_EMENU_O_01
                        $downloadFormVo->sheetName = 'CODE_EMENU_O_01';
                        $maskingArea = 'order';
                        break;
                    case 'order_02': // 寃곗젣�셿猷뚮━�뒪�듃 CODE_EMENU_O_02
                        $downloadFormVo->sheetName = 'CODE_EMENU_O_02';
                        $maskingArea = 'order';
                        break;
                    case 'order_03': // 怨좉컼 援먰솚�떊泥� 愿�由� CODE_EMENU_O_03
                        $downloadFormVo->sheetName = 'CODE_EMENU_O_03';
                        $maskingArea = 'order';
                        break;
                    case 'order_04': // 怨좉컼 諛섑뭹�떊泥� 愿�由� CODE_EMENU_O_04
                        $downloadFormVo->sheetName = 'CODE_EMENU_O_04';
                        $maskingArea = 'order';
                        break;
                    case 'order_05': // 怨좉컼 �솚遺덉떊泥� 愿�由� CODE_EMENU_O_05
                        $downloadFormVo->sheetName = 'CODE_EMENU_O_05';
                        $maskingArea = 'order';
                        break;
                    case 'order_06': // 援먰솚痍⑥냼由ъ뒪�듃 CODE_EMENU_O_06
                        $downloadFormVo->sheetName = 'CODE_EMENU_O_06';
                        $maskingArea = 'order';
                        break;
                    case 'order_07': // 援먰솚異붽�由ъ뒪�듃 CODE_EMENU_O_07
                        $downloadFormVo->sheetName = 'CODE_EMENU_O_07';
                        $maskingArea = 'order';
                        break;
                    case 'order_08': // 援щℓ�솗�젙由ъ뒪�듃 CODE_EMENU_O_08
                        $downloadFormVo->sheetName = 'CODE_EMENU_O_08';
                        $maskingArea = 'order';
                        break;
                    case 'order_09': // 諛섑뭹愿�由� CODE_EMENU_O_09
                        $downloadFormVo->sheetName = 'CODE_EMENU_O_09';
                        $maskingArea = 'order';
                        break;
                    case 'order_10': // 諛곗넚�셿猷뚮━�뒪�듃 CODE_EMENU_O_10
                        $downloadFormVo->sheetName = 'CODE_EMENU_O_10';
                        $maskingArea = 'order';
                        break;
                    case 'order_11': // 諛곗넚以묐━�뒪�듃 CODE_EMENU_O_11
                        $downloadFormVo->sheetName = 'CODE_EMENU_O_11';
                        $maskingArea = 'order';
                        break;
                    case 'order_12': // �긽�뭹以�鍮꾩쨷 由ъ뒪�듃 CODE_EMENU_O_12
                        $downloadFormVo->sheetName = 'CODE_EMENU_O_12';
                        $maskingArea = 'order';
                        break;
                    case 'order_13': // �엯湲덈�湲� 由ъ뒪�듃 CODE_EMENU_O_13
                        $downloadFormVo->sheetName = 'CODE_EMENU_O_13';
                        $maskingArea = 'order';
                        break;
                    case 'order_14': // 二쇰Ц�넻�빀由ъ뒪�듃 CODE_EMENU_O_14
                        $downloadFormVo->sheetName = 'CODE_EMENU_O_14';
                        $maskingArea = 'order';
                        break;
                    case 'order_15': // 痍⑥냼愿�由� CODE_EMENU_O_15
                        $downloadFormVo->sheetName = 'CODE_EMENU_O_15';
                        $maskingArea = 'order';
                        break;
                    case 'order_16': // �솚遺덇�由� CODE_EMENU_O_16
                        $downloadFormVo->sheetName = 'CODE_EMENU_O_16';
                        $maskingArea = 'order';
                        break;
                    case 'order_17': // 諛쒗뻾 �슂泥� 由ъ뒪�듃 CODE_EMENU_O_17
                        $downloadFormVo->sheetName = 'CODE_EMENU_O_17';
                        $maskingArea = 'order';
                        break;
                    case 'order_18': // 諛쒗뻾 �궡�뿭 由ъ뒪�듃 CODE_EMENU_O_18
                        $downloadFormVo->sheetName = 'CODE_EMENU_O_18';
                        $maskingArea = 'order';
                        break;
                    case 'member_01':
                        $downloadFormVo->sheetName = 'CODE_EMENU_M_01';
                        $maskingArea = 'member';
                        break;
                    case 'member_02':
                        $downloadFormVo->sheetName = 'CODE_EMENU_M_02';
                        $maskingArea = 'member';
                        break;
                    case 'member_03':
                        $downloadFormVo->sheetName = 'CODE_EMENU_M_03';
                        $maskingArea = 'member';
                        break;
                    case 'member_04':
                        $downloadFormVo->sheetName = 'CODE_EMENU_M_04';
                        $maskingArea = 'member';
                        break;
                    case 'member_05':
                        $downloadFormVo->sheetName = 'CODE_EMENU_M_05';
                        $maskingArea = 'member';
                        break;
                    case 'member_06':
                        $downloadFormVo->sheetName = 'CODE_EMENU_M_06';
                        $maskingArea = 'member';
                        break;
                    case 'member_07':
                        $downloadFormVo->sheetName = 'CODE_EMENU_M_07';
                        $maskingArea = 'member';
                        break;
                    case 'member_08':
                        $downloadFormVo->sheetName = 'CODE_EMENU_M_08';
                        $maskingArea = 'member';
                        break;
                    case 'board_01':
                        $downloadFormVo->sheetName = 'CODE_EMENU_B_01';
                        $maskingArea = 'others';
                        break;
                    case 'board_02':
                        $downloadFormVo->sheetName = 'CODE_EMENU_B_02';
                        $maskingArea = 'others';
                        break;
                    case 'board_03':
                        $downloadFormVo->sheetName = 'CODE_EMENU_B_03';
                        $maskingArea = 'others';
                        break;
                    case 'board_04':
                        $downloadFormVo->sheetName = 'CODE_EMENU_B_04';
                        $maskingArea = 'others';
                        break;
                    case 'board_05':
                        $downloadFormVo->sheetName = 'CODE_EMENU_B_05';
                        $maskingArea = 'others';
                        break;
                    case 'board_06':
                        $downloadFormVo->sheetName = 'CODE_EMENU_B_06';
                        $maskingArea = 'others';
                        break;
                    case 'scm_01':
                        $downloadFormVo->sheetName = 'CODE_EMENU_S_01';
                        $maskingArea = 'scm';
                        break;
                    case 'scm_02':
                        $downloadFormVo->sheetName = 'CODE_EMENU_S_02';
                        $maskingArea = 'scm';
                        break;
                    case 'scm_03':
                        $downloadFormVo->sheetName = 'CODE_EMENU_S_03';
                        $maskingArea = 'scm';
                        break;
                    case 'scm_04':
                        $downloadFormVo->sheetName = 'CODE_EMENU_S_04';
                        $maskingArea = 'scm';
                        break;
                    case 'scm_05':
                        $downloadFormVo->sheetName = 'CODE_EMENU_S_05';
                        $maskingArea = 'scm';
                        break;
                    case 'scm_06':
                        $downloadFormVo->sheetName = 'CODE_EMENU_S_06';
                        $maskingArea = 'scm';
                        break;
                    case 'scm_07':
                        $downloadFormVo->sheetName = 'CODE_EMENU_S_07';
                        $maskingArea = 'scm';
                        break;
                    case 'scm_08':
                        $downloadFormVo->sheetName = 'CODE_EMENU_S_08';
                        $maskingArea = 'scm';
                        break;
                    case 'goods_01':
                        $downloadFormVo->sheetName = 'CODE_EMENU_G_01';
                        $maskingArea = 'others';
                        break;
                    case 'goods_02':
                        $downloadFormVo->sheetName = 'CODE_EMENU_G_02';
                        $maskingArea = 'others';
                        break;
                    case 'goods_03':
                        $downloadFormVo->sheetName = 'CODE_EMENU_G_03';
                        $maskingArea = 'others';
                        break;
                    case 'goods_04':
                        $downloadFormVo->sheetName = 'CODE_EMENU_G_04';
                        $maskingArea = 'others';
                        break;
                    case 'goods_05':
                        $downloadFormVo->sheetName = 'CODE_EMENU_G_05';
                        $maskingArea = 'others';
                        break;
                    case 'goods_06':
                        $downloadFormVo->sheetName = 'CODE_EMENU_G_06';
                        $maskingArea = 'others';
                        break;
                    case 'goods_07':
                        $downloadFormVo->sheetName = 'CODE_EMENU_G_07';
                        $maskingArea = 'others';
                        break;
                    case 'goods_08':
                        $downloadFormVo->sheetName = 'CODE_EMENU_G_08';
                        $maskingArea = 'others';
                        break;
                    case 'policy_01':
                        $downloadFormVo->sheetName = 'CODE_EMENU_A_01';
                        $maskingArea = 'others';
                        break;
                    case 'policy_02':
                        $downloadFormVo->sheetName = 'CODE_EMENU_A_02';
                        $maskingArea = 'others';
                        break;
                    case 'policy_03':
                        $downloadFormVo->sheetName = 'CODE_EMENU_A_03';
                        $maskingArea = 'others';
                        break;
                    case 'policy_04':
                        $downloadFormVo->sheetName = 'CODE_EMENU_A_04';
                        $maskingArea = 'others';
                        break;
                    case 'policy_05':
                        $downloadFormVo->sheetName = 'CODE_EMENU_A_05';
                        $maskingArea = 'others';
                        break;
                    case 'policy_06':
                        $downloadFormVo->sheetName = 'CODE_EMENU_A_06';
                        $maskingArea = 'others';
                        break;
                    case 'promotion_01':
                        $downloadFormVo->sheetName = 'CODE_EMENU_P_01';
                        $maskingArea = 'others';
                        break;
                    case 'promotion_02':
                        $downloadFormVo->sheetName = 'CODE_EMENU_P_02';
                        $maskingArea = 'others';
                        break;
                    case 'promotion_03':
                        $downloadFormVo->sheetName = 'CODE_EMENU_P_03';
                        $maskingArea = 'others';
                        break;
                    case 'promotion_04':
                        $downloadFormVo->sheetName = 'CODE_EMENU_P_04';
                        $maskingArea = 'others';
                        break;
                    case 'promotion_05':
                        $downloadFormVo->sheetName = 'CODE_EMENU_P_05';
                        $maskingArea = 'others';
                        break;
                    case 'promotion_06':
                        $downloadFormVo->sheetName = 'CODE_EMENU_P_06';
                        $maskingArea = 'others';
                        break;
                    case 'other_01':
                        $downloadFormVo->sheetName = 'CODE_EMENU_OTH_01';
                        $maskingArea = 'others';
                        break;
                    case 'other_02':
                        $downloadFormVo->sheetName = 'CODE_EMENU_OTH_02';
                        $maskingArea = 'others';
                        break;
                    case 'other_03':
                        $downloadFormVo->sheetName = 'CODE_EMENU_OTH_03';
                        $maskingArea = 'others';
                        break;
                    case 'other_04':
                        $downloadFormVo->sheetName = 'CODE_EMENU_OTH_04';
                        $maskingArea = 'others';
                        break;
                    case 'other_05':
                        $downloadFormVo->sheetName = 'CODE_EMENU_OTH_05';
                        $maskingArea = 'others';
                        break;
                    case 'other_06':
                        $downloadFormVo->sheetName = 'CODE_EMENU_OTH_06';
                        $maskingArea = 'others';
                        break;
                    case 'other_07':
                        $downloadFormVo->sheetName = 'CODE_EMENU_OTH_07';
                        $maskingArea = 'others';
                        break;
                    case 'other_08':
                        $downloadFormVo->sheetName = 'CODE_EMENU_OTH_08';
                        $maskingArea = 'others';
                        break;
                    case 'other_09':
                        $downloadFormVo->sheetName = 'CODE_EMENU_OTH_09';
                        $maskingArea = 'others';
                        break;
                    default:
                        $downloadFormVo->sheetName = $excelFormVo->location;
                        $maskingArea = 'others';
                        break;
                }
            } else {
                $downloadFormVo->fieldVoList = Array();
                $downloadFormVo->byitemDownload = '';
                $downloadFormVo->fieldList = Array();
                $downloadFormVo->sheetTitle = 'unKnown Title';
                $downloadFormVo->sheetName = 'CODE_EMENU_O_01';
                $maskingArea = '';
            }
        } else {
            switch ($downloadFormVo->formSno) {
                case 'member_info':
                    $downloadFormVo->sheetName = 'Member Info';
                    $maskingArea = 'member';
                    break;
                case 'member_address':
                    $downloadFormVo->sheetName = 'Member Address';
                    $maskingArea = 'member';
                    break;
                case 'order_report':
                case 'order_scm_report':
                    $downloadFormVo->sheetName = 'Order Report';
                    $maskingArea = 'order';
                    break;
                case 'order_customer_report':
                case 'order_scm_customer_report':
                    $downloadFormVo->sheetName = 'Order Customer Report';
                    $maskingArea = 'order';
                    break;
                case 'order_receipt':
                case 'order_scm_receipt':
                    $downloadFormVo->sheetName = 'Order Receipt';
                    $maskingArea = 'order';
                    break;
                case 'order_particular':
                case 'order_scm_particular':
                    $downloadFormVo->sheetName = 'Order Particular';
                    $maskingArea = 'order';
                    break;
                case 'order_tax_invoice':
                case 'order_scm_tax_invoice':
                    $downloadFormVo->sheetName = 'Order Tax Invoice';
                    $maskingArea = 'order';
                    break;
                case 'goods_info':
                    $downloadFormVo->sheetName = 'Goods Information';
                    $maskingArea = '';
                    break;
                case 'category_info':
                    $downloadFormVo->sheetName = 'Category Information';
                    $maskingArea = '';
                    break;
                case 'category_info_simple':
                    $downloadFormVo->sheetName = 'Category Simple Information';
                    $maskingArea = '';
                    break;
                case 'brand_info':
                    $downloadFormVo->sheetName = 'Brand Information';
                    $maskingArea = '';
                    break;
                case 'brand_info_simple':
                    $downloadFormVo->sheetName = 'Brand Simple Information';
                    $maskingArea = '';
                    break;
            }
        }
        if (! empty($maskingArea)) {
            if (empty($this->loginInfo) || ! in_array($maskingArea, $this->loginInfo->unmaskingData)) {
                $downloadFormVo->downloadMasking = 'Y';
            } else {
                $downloadFormVo->downloadMasking = 'N';
            }
        } else {
            $downloadFormVo->downloadMasking = 'N';
        }
        $downloadFormVo->password = ($downloadForm->passwordFl == 'Y') ? $downloadForm->password : '';
        $downloadFormVo->searchRequest = $searchRequest;
        $downloadFormVo->downloadFileName = $downloadForm->downloadFileName;
        return $downloadFormVo;
    }

    /**
     * �븞�쟾�븳 Vo Class 媛��졇�삤湲�
     *
     * @param string $clazzName
     * @return mixed
     */
    public function GetSafeVoClass($clazzName = 'stdClass')
    {
        switch ($clazzName) {
            case 'stdClass':
                return new \stdClass();
            default:
                $safeClazzName = \PhpLoader::GetSafeClassName($clazzName, 'Vo');
                return new $safeClazzName();
        }
    }

    /**
     * �듅�젙 Class 濡� Request 梨꾩슦湲�
     *
     * @param array|object $data
     * @param string|object $clazzName
     * @return object
     */
    public function GetFill($data = null, $clazzName = 'stdClass')
    {
        $obj = (gettype($clazzName) == 'string') ? $this->GetSafeVoClass($clazzName, 'Vo') : $clazzName;
        if (! empty($data)) {
            $varNames = get_object_vars($obj);
            if (is_object($data)) {
                if ($data instanceof RequestVo) {
                    $obj = $data->GetFill($obj);
                } else {
                    foreach ($varNames as $key => $val) {
                        $value = $data->$key;
                        if (! is_null($value)) {
                            switch (gettype($val)) {
                                case "boolean":
                                    $obj->$key = ($value == '1' || $value == 'Y' || $value == 'yes' || $value == 'y' || $value == true) ? true : false;
                                    break;
                                case "integer":
                                case "double":
                                    $obj->$key = doubleval($value);
                                    break;
                                case "array":
                                    if (is_array($value)) {
                                        $obj->$key = $value;
                                    }
                                    break;
                                case "object":
                                    break;
                                case "NULL":
                                case "unknown type":
                                case "string":
                                default:
                                    $obj->$key = $value;
                                    break;
                            }
                        }
                    }
                }
            } else if (is_array($data)) {
                foreach ($varNames as $key => $val) {
                    if (isset($data[$key])) {
                        $value = $data[$key];
                        switch (gettype($val)) {
                            case "boolean":
                                $obj->$key = ($value == '1' || $value == 'Y' || $value == 'yes' || $value == 'y') ? true : false;
                                break;
                            case "integer":
                                $obj->$key = intval($value);
                                break;
                            case "double":
                                $obj->$key = doubleval($value);
                                break;
                            case "array":
                                if (is_array($value)) {
                                    $obj->$key = $value;
                                }
                                break;
                            case "object":
                                break;
                            case "NULL":
                            case "unknown type":
                            case "string":
                            default:
                                $obj->$key = $value;
                                break;
                        }
                    }
                }
            }
        }
        if (! empty($this->mallId) && isset($obj->mallId)) {
            $obj->mallId = $this->mallId;
        }
        return $obj;
    }

    /**
     * �뾽濡쒕뱶 �맂 �뙆�씪由ъ뒪�듃 媛��졇�삤湲�
     *
     * @param string $newUpload
     * @return string[]
     */
    public function GetUploadFilesList($newUpload = '')
    {
        if (! empty($newUpload)) {
            return explode('@!@', $newUpload);
        } else {
            return Array();
        }
    }

    /**
     * �뙆�씪�쓣 �듅�젙 �쐞移섏뿉 �뾽濡쒕뱶�븯怨� 洹� 寃곌낵瑜� string �쑝濡� 媛��졇�삤湲�
     *
     * @param string $newUpload
     * @param string $oldUpload
     * @param string $dirPath
     * @param string $uploadType
     * @return string
     */
    public function GetUploadFiles($newUpload = '', $oldUpload = '', $dirPath = '', $uploadType = '')
    {
        $uploadFiles = Array();
        if (! empty($newUpload)) {
            $newUploadList = explode('@!@', $newUpload);
            foreach ($newUploadList as $fileTxt) {
                $result = $this->GetUploadFile($fileTxt, '', $dirPath, $uploadType);
                if (! empty($result)) {
                    $uploadFiles[] = $result;
                }
            }
        }
        if (! empty($oldUpload)) {
            $oldUploadList = explode('@!@', $oldUpload);
            $newUploadList = Array();
            foreach ($uploadFiles as $fileTxt) {
                list (, , , $fileUrl) = explode('#', $fileTxt . '#####');
                $newUploadList[] = $fileUrl;
            }
            foreach ($oldUploadList as $fileTxt) {
                list (, , , $fileUrl) = explode('#', $fileTxt . '#####');
                if (! in_array($fileUrl, $newUploadList)) {
                    if (file_exists(FILE_DIR . '/' . $fileUrl)) {
                        unlink(FILE_DIR . '/' . $fileUrl);
                    }
                }
            }
        }
        return implode('@!@', $uploadFiles);
    }

    /**
     * �뾽濡쒕뱶 �맂 �뙆�씪�뱾 蹂듭젣 以�鍮�
     *
     * @param string $newUpload
     * @return string
     */
    public function GetUploadFilesCopy($newUpload = '')
    {
        $uploadFiles = Array();
        if (! empty($newUpload)) {
            $newUploadList = explode('@!@', $newUpload);
            foreach ($newUploadList as $fileTxt) {
                $result = $this->GetUploadFileCopy($fileTxt);
                if (! empty($result)) {
                    $uploadFiles[] = $result;
                }
            }
        }
        return implode('@!@', $uploadFiles);
    }

    /**
     * �뾽濡쒕뱶 �맂 �뙆�씪 蹂듭젣 以�鍮�
     *
     * @param string $newUpload
     * @return string
     */
    public function GetUploadFileCopy($newUpload = '')
    {
        if (! empty($newUpload)) {
            list ($fileName, $fileSize, $fileType, $fileUrl) = explode('#', $newUpload);
            if (file_exists(FILE_DIR . '/' . $fileUrl)) {
                $matches = Array();
                if (preg_match('#([a-zA-Z0-9_]+)(/.+)#', $fileUrl, $matches)) {
                    $fileUrl = $matches[1] . '/clone' . $matches[2];
                }
                $newUpload = $fileName . '#' . $fileSize . '#' . $fileType . '#' . $fileUrl;
            } else {
                $newUpload = '';
            }
        }
        return $newUpload;
    }

    /**
     * 濡쒖��씪蹂� �뾽濡쒕뱶 �뙆�씪 泥섎━
     *
     * @param LocaleTextVo $newVo
     * @param LocaleTextVo $oldVo
     * @param string $dirPath
     * @param string $uploadType
     * @return string|LocaleTextVo
     */
    public function GetLocaleUploadFile($newVo = null, $oldVo = null, $dirPath = 'base', $uploadType = '')
    {
        if (! empty($newVo) || ! empty($oldVo)) {
            if (empty($newVo)) {
                $newVo = new LocaleTextVo();
            }
            foreach ($newVo as $locale => $value) {
                $newContents = $value;
                $oldContents = ! empty($oldVo) && isset($oldVo->$locale) && ! empty($oldVo->$locale) ? $oldVo->$locale : '';
                if (! empty($newContents) || ! empty($oldContents)) {
                    $newContents = $this->GetUploadFile($newContents, $oldContents, $dirPath, $uploadType);
                    if (! empty($newVo)) {
                        $newVo->$locale = $newContents;
                    }
                }
            }
        }
        return $this->GetLocaleTextVo($newVo);
    }

    /**
     * 遺�媛��꽭 �젙蹂� 媛��졇�삤湲�
     *
     * @param number $price
     * @param number $percent
     * @return number[]
     */
    public function GetTaxPrice($price = 0, $percent = 0)
    {
        $sumPrice = max(0, $price);
        $supplyPirce = 0;
        $taxPrice = 0;
        $taxNoPrice = 0;
        if ($percent > 0) {
            $supplyPirce = round($sumPrice / (1 + $percent / 100));
            $taxPrice = $sumPrice - $supplyPirce;
            $taxNoPrice = 0;
        } else {
            $taxNoPrice = $sumPrice;
        }
        return Array(
            $sumPrice,
            $supplyPirce,
            $taxPrice,
            $taxNoPrice
        );
    }

    /**
     * �뾽濡쒕뱶 �뙆�씪 媛�鍮꾩� 而щ젆�듃
     *
     * @param string[] $contentsList
     * @param string $mode
     */
    public function GetUploadFileCheck($contentsList = Array(), $mode = '')
    {
        $tmpFile = FILE_DIR . '/' . MALL_ID . '/tmp_checked.log';
        if (! file_exists($tmpFile)) {
            touch($tmpFile);
        }
        switch ($mode) {
            case 'clear':
                if (file_exists($tmpFile)) {
                    unlink($tmpFile);
                    touch($tmpFile);
                    $logFileName = MALL_ID . '/tmp_checked.log';
                    error_log("===============\n" . md5($logFileName) . ' ' . $logFileName . "\n\n", 3, $tmpFile);
                }
                break;
            case 'check':
                $oldFileList = Array();
                $savedFileList = Array();
                $unusedFileList = Array();
                $usedFileList = Array();
                $missingFileList = Array();
                $fileContents = file($tmpFile);
                $matches = Array();
                foreach ($fileContents as $line) {
                    if (preg_match('#([a-zA-Z0-9]+) (.+)#', $line, $matches)) {
                        $key = $matches[1];
                        $fileName = $matches[2];
                        $oldFileList[$key] = $fileName;
                    }
                }
                $fileList = glob(FILE_DIR . '/' . MALL_ID . '/{,**/,**/**/,**/**/**,**/**/**/**,**/**/**/**/**}*.*', GLOB_BRACE);
                foreach ($fileList as $fileName) {
                    $filePath = realpath($fileName);
                    if (file_exists($filePath)) {
                        if (preg_match('#/data/(' . MALL_ID . '/.+)#', $filePath, $matches)) {
                            $savedFileName = $matches[1];
                            $savedFileKey = md5($savedFileName);
                            $savedFileList[$savedFileKey] = $savedFileName;
                            if (! isset($oldFileList[$savedFileKey])) {
                                $unusedFileList[$savedFileKey] = $savedFileName;
                                unlink($filePath);
                            } else {
                                $usedFileList[$savedFileKey] = $savedFileName;
                            }
                        }
                    }
                }
                foreach ($oldFileList as $key => $fileName) {
                    if (! isset($savedFileList[$key]) && strpos($fileName, 'common/') !== 0) {
                        $missingFileList[$key] = $fileName;
                    }
                }
                $debugLine = Array();
                if (! empty($missingFileList)) {
                    $debugLine[] = "====================";
                    $debugLine[] = "Missing File (" . count($missingFileList) . ')';
                    $debugLine[] = "--------------------";
                    foreach ($missingFileList as $key => $fileName) {
                        $debugLine[] = $key . ' ' . $fileName;
                    }
                }
                if (! empty($unusedFileList)) {
                    $debugLine[] = "====================";
                    $debugLine[] = "Unused File (" . count($unusedFileList) . ')';
                    ;
                    $debugLine[] = "--------------------";
                    foreach ($unusedFileList as $key => $fileName) {
                        $debugLine[] = $key . ' ' . $fileName;
                    }
                }
                if (! empty($usedFileList)) {
                    $debugLine[] = "====================";
                    $debugLine[] = "Used File (" . count($usedFileList) . ')';
                    ;
                    $debugLine[] = "--------------------";
                    foreach ($usedFileList as $key => $fileName) {
                        $debugLine[] = $key . ' ' . $fileName;
                    }
                }
                if (! empty($debugLine)) {
                    error_log("===============\n" . implode("\n", $debugLine) . "\n\n", 3, $tmpFile);
                }
                break;
            default:
                if (! empty($contentsList)) {
                    $attachList = Array();
                    foreach ($contentsList as $contents) {
                        if (! empty($contents) && strlen($contents) > 10) {
                            $matches = Array();
                            if (preg_match_all('#data/([a-z0-9][a-z0-9\-_/]+\.[a-z0-9]{2,4})#', $contents, $matches, PREG_SET_ORDER)) {
                                foreach ($matches as $fileList) {
                                    $fileName = $fileList[1];
                                    if (! empty($fileName)) {
                                        if ($fileName == 'maholn/goods/202002/202002265e55f537b0989.jpg') {
                                            echo $contents;
                                        }
                                        $fileKey = md5($fileName);
                                        $attachList[$fileKey] = $fileName;
                                    }
                                }
                            }
                            if (preg_match_all('#\#(' . MALL_ID . '/[a-z0-9][a-z0-9\-_/]+\.[a-z0-9]{2,4})#', $contents, $matches, PREG_SET_ORDER)) {
                                foreach ($matches as $fileList) {
                                    $fileName = $fileList[1];
                                    if (! empty($fileName)) {
                                        if ($fileName == 'maholn/goods/202002/202002265e55f537b0989.jpg') {
                                            echo $contents;
                                        }
                                        $fileKey = md5($fileName);
                                        $attachList[$fileKey] = $fileName;
                                    }
                                }
                            }
                        }
                    }
                    $debugLine = Array();
                    foreach ($attachList as $key => $fileName) {
                        $debugLine[] = $key . ' ' . $fileName;
                    }
                    if (! empty($debugLine)) {
                        error_log("===============\n" . implode("\n", $debugLine) . "\n\n", 3, $tmpFile);
                    }
                }
                break;
        }
    }

    /**
     * �뾽濡쒕뱶 �뙆�씪�쓣 �뵒�뒪�겕�뿉 ���옣�븯怨� 寃곌낵瑜� 臾몄옄濡� 媛��졇�삤湲�
     *
     * @param string $newUpload
     * @param string $oldUpload
     * @param string $dirPath
     * @param string $uploadType
     * @return string
     */
    public function GetUploadFile($newUpload = '', $oldUpload = '', $dirPath = 'base', $uploadType = '')
    {
        if (! empty($newUpload)) {
            list ($fileName, $fileSize, $fileType, $fileUrl, $fileSeqn) = explode('#', $newUpload . '#####');
            if (empty($fileUrl)) {
                $fileUrl = $fileName;
            }
            $fileSize = intval($fileSize);
            $matches = Array();
            $fileDataPathRoot = (preg_match('#secure/#', $dirPath)) ? FILE_DIR_SECURE : FILE_DIR;
            if (preg_match('#((https|http)://.+)#', $fileUrl, $matches)) {
                $tmpName = FILE_DIR . '/junk/' . uniqid("tmp");
                @copy($fileUrl, $tmpName);
                if (file_exists($tmpName)) {
                    $fileType = mime_content_type($tmpName);
                    switch ($fileType) {
                        case 'image/jpeg':
                        case 'image/jpg':
                        case 'image/gif':
                        case 'image/png':
                            $fileSize = filesize($tmpName);
                            $fileName = basename($fileUrl);
                            $fileUrl = base64_encode(file_get_contents($tmpName));
                            if (! empty($fileUrl)) {
                                $fileUrl = 'data:' . $fileType . ';base64,' . $fileUrl;
                            }
                            break;
                    }
                    unlink($tmpName);
                }
            }
            if (preg_match('#data:([^;]+);base64,(.+)#', $fileUrl, $matches)) {
                if (empty($dirPath)) {
                    $dirPath = 'baseInfo';
                }
                $pathInfo = pathinfo($fileName);
                $extension = 'tmp';
                switch ($pathInfo['extension']) {
                    case 'dll':
                    case '':
                    case 'sh':
                    case 'csh':
                    case 'exe':
                    case 'php':
                    case 'html':
                    case 'asp':
                    case 'java':
                    case 'jsp':
                        break;
                    default:
                        $extension = $pathInfo['extension'];
                        break;
                }
                $fileUrl = $this->mallId . '/' . $dirPath . '/' . date('Ym') . '/' . uniqid(date('Ymd')) . '.' . strtolower($extension);
                $dirName = dirname($fileDataPathRoot . '/' . $fileUrl);
                if (! file_exists($dirName)) {
                    mkdir($dirName, 0777, true);
                }
                file_put_contents($fileDataPathRoot . '/' . $fileUrl, base64_decode($matches[2]));
                $fileSize = filesize($fileDataPathRoot . '/' . $fileUrl);
                if (! empty($oldUpload)) {
                    list (, , , $oldFileUrl) = explode('#', $oldUpload);
                    if (file_exists($fileDataPathRoot . '/' . $oldFileUrl)) {
                        unlink($fileDataPathRoot . '/' . $oldFileUrl);
                    }
                }
            } else if (preg_match('#([a-zA-Z0-9_]+)/(clone|tmp)(/.+)#', $fileUrl, $matches) || (! empty($dirPath) && ! empty($fileUrl) && strpos($fileUrl, $this->mallId . '/' . $dirPath . '/') !== 0)) {
                $oldUrl = '';
                if (! empty($matches)) {
                    switch ($matches[2]) {
                        case 'clone':
                            $oldUrl = $matches[1] . $matches[3];
                            break;
                        case 'tmp':
                            $oldUrl = $matches[1] . '/tmp' . $matches[3];
                            break;
                        default:
                            $oldUrl = '';
                            break;
                    }
                } else if (strpos($fileUrl, $this->mallId . '/') !== 0 || strpos($fileUrl, $this->mallId . '/tmp/') === 0) {
                    $oldUrl = $fileUrl;
                } else {
                    $oldUrl = '';
                }
                if (! empty($oldUrl) && file_exists(FILE_DIR . '/' . $oldUrl)) {
                    if (empty($dirPath)) {
                        $dirPath = 'baseInfo';
                    }
                    $pathInfo = pathinfo($fileName);
                    $extension = 'tmp';
                    switch ($pathInfo['extension']) {
                        case 'dll':
                        case '':
                        case 'sh':
                        case 'csh':
                        case 'exe':
                        case 'php':
                        case 'html':
                        case 'asp':
                        case 'java':
                        case 'jsp':
                            break;
                        default:
                            $extension = $pathInfo['extension'];
                            break;
                    }
                    $fileUrl = $this->mallId . '/' . $dirPath . '/' . date('Ym') . '/' . uniqid(date('Ymd')) . '.' . strtolower($extension);
                    $dirName = dirname($fileDataPathRoot . '/' . $fileUrl);
                    if (! file_exists($dirName)) {
                        mkdir($dirName, 0777, true);
                    }
                    copy(FILE_DIR . '/' . $oldUrl, $fileDataPathRoot . '/' . $fileUrl);
                    $fileSize = filesize($fileDataPathRoot . '/' . $fileUrl);
                    if (! empty($oldUpload)) {
                        list (, , , $oldFileUrl) = explode('#', $oldUpload);
                        if (file_exists($fileDataPathRoot . '/' . $oldFileUrl)) {
                            unlink($fileDataPathRoot . '/' . $oldFileUrl);
                        }
                    }
                }
            } else {
                if (file_exists($fileDataPathRoot . '/' . $fileUrl)) {
                    $fileSize = filesize($fileDataPathRoot . '/' . $fileUrl);
                } else {
                    $fileSize = 0;
                }
            }
            if ($fileSize > 0) {
                if (preg_match('#secure/#', $dirPath) && ! preg_match('#^x-#', $fileType)) {
                    $fileType = 'x-' . $fileType;
                }
                $newUpload = $fileName . '#' . $fileSize . '#' . $fileType . '#' . $fileUrl . '#' . $fileSeqn;
            }
        }
        return $newUpload;
    }

    /**
     * �옄�떇 VO �쓽 媛� 媛��졇�삤湲�
     *
     * @param mixed $vo
     * @param string $path
     * @param string $default
     * @return mixed
     */
    public function GetChildValue($vo = null, $path = '', $default = '')
    {
        if (! empty($vo) && is_object($vo)) {
            $pathValue = explode('.', $path);
            while (count($pathValue) > 0) {
                $key = array_shift($pathValue);
                if (empty($vo) || ! is_object($vo) || ! isset($vo->$key)) {
                    return $default;
                } else {
                    $vo = $vo->$key;
                }
            }
            return $vo;
        }
        return $default;
    }

    /**
     * @var BaseCurrencyUnitVo[]
     */
    protected $policyPriceRule = Array();
    
    /**
     * 媛�寃� 媛��졇�삤湲� 
     *
     * @param integer $price
     * @param string $priceType goods | mileage | coupon | member 
     * @return integer
     */
    public function GetPriceByUnit($price, $priceType = 'goods', $locale = 'ko')
    {
        if (!empty($price)) {
            if (empty($locale)) {
                $locale = 'ko';
            }
            switch ($locale) {
                case 'ko' :
                case 'en' :
                case 'cn' :
                case 'ko' :
                    break;                    
                case 'kr' :
                default:
                    $locale = 'ko';
                    break;
            }
            if (!isset($this -> policyPriceRule[$locale])) {
                $this -> policyPriceRule[$locale] = $this -> GetServicePolicy() -> GetBaseCurrencyUnitView($locale);
            }
            $policyPriceRule = $this -> policyPriceRule[$locale];
            if (!empty($policyPriceRule)) {
                $unitRoundPrecisionVo = null;
                switch($priceType) {
                    case 'goods' :
                        $unitRoundPrecisionVo = $policyPriceRule ->goodsUnit;
                        break;
                    case 'mileage' :
                        $unitRoundPrecisionVo = $policyPriceRule ->mileageUnit;
                        break;
                    case 'coupon' :
                        $unitRoundPrecisionVo = $policyPriceRule ->couponUnit;
                        break;
                    case 'member' :
                        $unitRoundPrecisionVo = $policyPriceRule ->memberGroupUnit;
                        break;
                }
                if (!empty($unitRoundPrecisionVo)) {
                    $unitPrecision = intval($unitRoundPrecisionVo ->unitPrecision);
                    $unitRound = $unitRoundPrecisionVo ->unitRound;
                    $unitPrecisionDiv = 1;
                    switch ($unitPrecision) {
                        case 2 :
                            $unitPrecisionDiv = 0.01;
                            break;
                        case 1 :
                            $unitPrecisionDiv = 0.1;
                            break;
                        case 0 :
                            $unitPrecisionDiv = 1;
                            break;
                        case -1 :
                            $unitPrecisionDiv = 10;
                            break;
                        case -2 :
                            $unitPrecisionDiv = 100;
                            break;
                        case -3 :
                            $unitPrecisionDiv = 1000;
                            break;
                        case -4 :
                            $unitPrecisionDiv = 10000;
                            break;
                        case 0 :
                        default:
                            $unitPrecisionDiv = 1;
                            break;
                    }
                    if ($unitPrecisionDiv != 0 && $unitPrecisionDiv != 1) {
                        switch($unitRound) {
                            case 'floor' :
                                return floor($price / $unitPrecisionDiv) * $unitPrecisionDiv;
                            case 'round' :
                                return round($price / $unitPrecisionDiv) * $unitPrecisionDiv;
                            case 'ceil' :
                                return ceil($price / $unitPrecisionDiv) * $unitPrecisionDiv;
                        }
                    }
                }
            }
        }
        return $price;
    }
    
    /**
     * �쎒 �렪吏묎린�뿉�꽌 �벑濡앸맂 濡쒖��씪 蹂� 泥섎━
     *
     * @param LocaleTextVo $newVo
     * @param LocaleTextVo $oldVo
     * @param string $dirPath
     * @return LocaleTextVo
     */
    public function GetLocaleTextParse($newVo = null, $oldVo = null, $dirPath = 'base')
    {
        if (! empty($newVo) || ! empty($oldVo)) {
            if (empty($newVo)) {
                $newVo = new LocaleTextVo();
            }
            foreach ($newVo as $locale => $value) {
                $newContents = $value;
                $oldContents = ! empty($oldVo) && isset($oldVo->$locale) && ! empty($oldVo->$locale) ? $oldVo->$locale : '';
                if (! empty($newContents) || ! empty($oldContents)) {
                    $newContents = $this->GetEditorParse($newContents, $oldContents, $dirPath);
                    if (! empty($newVo)) {
                        $newVo->$locale = $newContents;
                    }
                }
            }
        }
        return $this->GetLocaleTextVo($newVo);
    }

    /**
     * 硫붿씤 �끂異� �긽�뭹 �젙由� 諛� ��泥� �씠誘몄� �뵒�뒪�겕 ���옣
     *
     * @param DisplayMainItemVo $newVo
     * @param DisplayMainItemVo $oldVo
     * @param string $dirPath
     * @return DisplayMainItemVo
     */
    public function GetDisplayMainItemParse($newVo = null, $oldVo = null, $dirPath = 'base')
    {
        $oldImageList = Array();
        $newImageList = Array();
        if (! empty($newVo) && ! empty($newVo->refGoodsImage)) {
            foreach ($newVo->refGoodsImage as $codeImage) {
                if (! empty($codeImage->goodsImage)) {
                    $newImageList[] = $codeImage->goodsImage . '#' . $codeImage->goodsCode;
                }
            }
        }
        if (! empty($oldVo) && ! empty($oldVo->refGoodsImage)) {
            foreach ($oldVo->refGoodsImage as $codeImage) {
                if (! empty($codeImage->goodsImage)) {
                    $oldImageList[] = $codeImage->goodsImage . '#' . $codeImage->goodsCode;
                }
            }
        }
        $newUploadFiles = $this->GetUploadFiles(implode('@!@', $newImageList), implode('@!@', $oldImageList), $dirPath);
        $refGoodsImage = Array();
        if (! empty($newUploadFiles)) {
            $newUploadFilesList = explode('@!@', $newUploadFiles);
            foreach ($newUploadFilesList as $fileInfo) {
                list ($fileName, $fileSize, $fileType, $fileUrl, $goodsCode) = explode('#', $fileInfo . '####');
                if (! empty($goodsCode) && ! empty($fileUrl)) {
                    $refGoodsImageVo = new RefGoodsImageVo();
                    $refGoodsImageVo->goodsCode = $goodsCode;
                    $refGoodsImageVo->goodsImage = $fileName . '#' . $fileSize . '#' . $fileType . '#' . $fileUrl;
                    $refGoodsImage[] = $refGoodsImageVo;
                }
            }
        }
        if (! empty($newVo)) {
            $newVo->refGoodsImage = $refGoodsImage;
        }
        return $newVo;
    }

    /**
     * �쎒 �렪吏묎린濡� �옉�꽦�맂 �궡�슜�쓽 �씠誘몄�瑜� �뵒�뒪�겕濡� ���옣
     *
     * @param string $newUpload
     * @param string $oldUpload
     * @param string $dirPath
     * @return string|mixed
     */
    public function GetEditorParse($newUpload = '', $oldUpload = '', $dirPath = 'base')
    {
        if (! empty($newUpload) && $newUpload != $oldUpload) {
            $matches = Array();
            preg_match_all('#data:([^;]+);base64,([^"]+)#', $newUpload, $matches, PREG_SET_ORDER);
            foreach ($matches as $item) {
                $fileName = '';
                switch ($item[1]) {
                    case 'image/png':
                        $fileName = $this->mallId . '/' . $dirPath . '/' . date('Ym') . '/' . uniqid(date('Ymd')) . '.png';
                        break;
                    case 'image/jpeg':
                    case 'image/jpg':
                        $fileName = $this->mallId . '/' . $dirPath . '/' . date('Ym') . '/' . uniqid(date('Ymd')) . '.jpg';
                        break;
                    case 'image/gif':
                        $fileName = $this->mallId . '/' . $dirPath . '/' . date('Ym') . '/' . uniqid(date('Ymd')) . '.gif';
                        break;
                }
                if (! empty($fileName)) {
                    $dirName = dirname(FILE_DIR . '/' . $fileName);
                    if (! file_exists($dirName)) {
                        mkdir($dirName, 0777, true);
                    }
                    file_put_contents(FILE_DIR . '/' . $fileName, base64_decode($item[2]));
                    $newUpload = str_replace($item[0], '/data/' . $fileName, $newUpload);
                }
            }
            preg_match_all('#(http|https)://([^/]+)/([a-zA-Z0-9_]+)/([a-zA-Z0-9\./]+)#', $newUpload, $matches, PREG_SET_ORDER);
            foreach ($matches as $item) {
                $remoteUrl = $item[0];
                $remoteDomain = $item[2];
                $siteKey = $item[3];
                switch ($remoteDomain) {
                    case 'img.kbmall.dev.hanbiz.kr':
                    case 'images.kbmall.mymac':
                    case 'image.kbmall.hanbiz.kr':
                        $newUrl = $siteKey . '/tmp/' . uniqid('r') . basename($item[4]);
                        $dirName = dirname(FILE_DIR . '/' . $newUrl);
                        if (! file_exists($dirName)) {
                            mkdir($dirName, 0777, true);
                        }
                        @copy($remoteUrl, FILE_DIR . '/' . $newUrl);
                        $newUpload = str_replace($remoteUrl, '/data/' . $newUrl, $newUpload);
                        break;
                }
            }
            preg_match_all('#/data/([a-zA-Z0-9_]+)/(clone|tmp)/([a-zA-Z0-9_\./]+)#', $newUpload, $matches, PREG_SET_ORDER);
            foreach ($matches as $item) {
                $fileName = '';
                $orgFileName = '';
                switch ($item[2]) {
                    case 'tmp':
                        $orgFileName = $item[1] . '/tmp/' . $item[3];
                        break;
                    case 'clone':
                        $orgFileName = $item[1] . '/' . $item[3];
                        break;
                    default:
                        $orgFileName = '';
                        break;
                }
                if (file_exists(FILE_DIR . '/' . $orgFileName)) {
                    $pathInfo = pathinfo($orgFileName);
                    if (isset($pathInfo['extension'])) {
                        switch ($pathInfo['extension']) {
                            case 'dll':
                            case '':
                            case 'exe':
                            case 'php':
                            case 'html':
                            case 'asp':
                            case 'java':
                            case 'jsp':
                                break;
                            default:
                                $extension = $pathInfo['extension'];
                                break;
                        }
                        $fileName = $this->mallId . '/' . $dirPath . '/' . date('Ym') . '/' . uniqid(date('Ymd')) . '.' . $extension;
                    } else {
                        $orgFileName = '';
                    }
                } else {
                    $orgFileName = '';
                }
                if (! empty($fileName) && ! empty($orgFileName)) {
                    $dirName = dirname(FILE_DIR . '/' . $fileName);
                    if (! file_exists($dirName)) {
                        mkdir($dirName, 0777, true);
                    }
                    copy(FILE_DIR . '/' . $orgFileName, FILE_DIR . '/' . $fileName);
                    $newUpload = str_replace($item[0], '/data/' . $fileName, $newUpload);
                }
            }
        }
        return trim($newUpload);
    }

    /**
     * �쎒�렪吏묎린濡� �옉�꽦�맂 �궡�슜以� 泥⑤��맂 �뙆�씪 紐⑸줉
     *
     * @param string $newUpload
     * @return string[]|array
     */
    public function GetEditorFileUploadList($newUpload = '')
    {
        if (! empty($newUpload)) {
            $matches = Array();
            $fileList = Array();
            preg_match_all('#src=(["\']|)/data/([^ \'\"]+)#', $newUpload, $matches, PREG_SET_ORDER);
            foreach ($matches as $item) {
                $fileServer = $item[2];
                $fileName = basename($fileServer);
                if (! empty($fileName)) {
                    $dirPath = FILE_DIR . '/' . $fileServer;
                    if (file_exists($dirPath)) {
                        $fileSize = filesize($dirPath);
                        $fileType = mime_content_type($dirPath);
                        $fileList[$fileServer] = $fileName . '#' . $fileSize . '#' . $fileType . '#' . $fileServer;
                    }
                }
            }
            return $fileList;
        } else {
            return Array();
        }
    }

    /**
     * 濡쒖��씪 �쎒�렪吏묎린 �뙆�씪 �뾽濡쒕뱶 濡쒓렇 湲곕줉
     *
     * @param LocaleTextVo $newVo
     * @param LocaleTextVo $oldVo
     * @param string $fileParent
     * @param string $fileKey
     * @param string $filePart
     * @return integer
     */
    public function SetLocaleTextFileLogUpdate($newVo = null, $oldVo = null, $fileParent = '', $fileKey = '', $filePart = '')
    {
        $uploadSize = 0;
        $localData = Array(
            'ko',
            'en',
            'jp',
            'cn'
        );
        foreach ($localData as $locale) {
            $newContents = ! empty($newVo) && isset($newVo->$locale) && ! empty($newVo->$locale) ? $newVo->$locale : '';
            $oldContents = ! empty($oldVo) && isset($oldVo->$locale) && ! empty($oldVo->$locale) ? $oldVo->$locale : '';
            if (! empty($newContents) || ! empty($oldContents)) {
                $uploadSize += $this->SetEditorFileLogUpdate($newContents, $oldContents, $fileParent, $fileKey, $filePart . '_' . $locale);
            }
        }
        return $uploadSize;
    }

    /**
     * �쎒 �렪吏묎린 泥⑤� �씠誘몄� 濡쒓렇 湲곕줉
     *
     * @param string $newUpload
     * @param string $oldUpload
     * @param string $fileParent
     * @param string $fileKey
     * @param string $filePart
     * @return integer
     */
    public function SetEditorFileLogUpdate($newUpload = '', $oldUpload = '', $fileParent = '', $fileKey = '', $filePart = '')
    {
        $newUploadList = $this->GetEditorFileUploadList($newUpload);
        $oldUploadList = $this->GetEditorFileUploadList($oldUpload);
        if (! empty($newUploadList) || ! empty($oldUploadList)) {
            return $this->SetFileLogUpdate(implode('@!@', $newUploadList), implode('@!@', $oldUploadList), $fileParent, $fileKey, $filePart);
        } else {
            return 0;
        }
    }

    /**
     *
     * @var \Vo\BaseConfigVo
     */
    private static $baseConfigVo = null;

    /**
     * 濡쒖��씪 LocaleTextVo 媛��졇�삤湲�
     *
     * @param RequestVo $request
     * @param string $name
     * @return \Vo\LocaleTextVo
     */
    public function GetLocaleTextVoRequest(RequestVo $request, $name = "", $useForce = false)
    {
        if ($request->hasKey($name)) {
            return $this->GetLocaleTextVo($request->GetFill(new LocaleTextVo(), $name), $useForce);
        } else {
            return null;
        }
    }

    /**
     * �궎�썙�뱶 媛��졇�삤湲�
     *
     * @param string $keyword
     * @return string
     */
    public function GetSearchKeywordUniq($keyword = '')
    {
        $keyword = trim($keyword);
        if (! empty($keyword)) {
            $goodsSearchWordList = explode(' ', trim(preg_replace('#[\r\n\t,\#]#', ' ', $keyword)));
            $goodsSearchWordUniqList = Array();
            foreach ($goodsSearchWordList as $keyword) {
                $keyword = trim($keyword);
                if (! empty($keyword) && strlen($keyword) >= 2 && ! in_array($keyword, $goodsSearchWordUniqList)) {
                    $allChecked = true;
                    foreach ($goodsSearchWordUniqList as $line) {
                        if (strpos($line, $keyword) !== false) {
                            $allChecked = false;
                            break;
                        }
                    }
                    if ($allChecked) {
                        $goodsSearchWordUniqList[] = $keyword;
                    }
                }
            }
            $goodsSearchWordUniqListNew = Array();
            foreach ($goodsSearchWordUniqList as $keyword) {
                $goodsSearchWordUniqListNew[] = '#' . $keyword;
            }
            $goodsSearchWord = implode(' ', $goodsSearchWordUniqListNew);
            if (mb_strlen($goodsSearchWord, 'utf-8') > 200) {
                $goodsSearchWord = mb_substr($goodsSearchWord, 0, 200, 'utf-8');
            }
            return $goodsSearchWord;
        } else {
            return '';
        }
    }

    /**
     * 濡쒖��씪 �궎�썙�뱶 媛��졇�삤湲�
     *
     * @param string $title
     * @param LocaleTextVo $vo
     * @return string
     */
    public function GetLocaleTextKeyword($title = '', $vo = null)
    {
        $keywordList = Array();
        if (! empty($title)) {
            $keywordList[] = $title;
        }
        if (! empty($vo) && is_object($vo)) {
            $cloneVo = $this->GetLocaleTextVo(clone $vo);
            if ($cloneVo != null) {
                if (! empty($cloneVo->ko) && in_array($cloneVo->ko, $keywordList)) {
                    $keywordList[] = $cloneVo->ko;
                }
                if (! empty($cloneVo->en) && in_array($cloneVo->en, $keywordList)) {
                    $keywordList[] = $cloneVo->en;
                }
                if (! empty($cloneVo->cn) && in_array($cloneVo->cn, $keywordList)) {
                    $keywordList[] = $cloneVo->cn;
                }
                if (! empty($cloneVo->jp) && in_array($cloneVo->jp, $keywordList)) {
                    $keywordList[] = $cloneVo->jp;
                }
            }
        }
        return implode(' ', $keywordList);
    }

    /**
     * 濡쒖��씪 LocaleTextVo 媛��졇�삤湲�
     *
     * @param LocaleTextVo $vo
     * @return \Vo\LocaleTextVo
     */
    public function GetLocaleTextVo($vo = null, $useForce = false)
    {
        if (empty(self::$baseConfigVo)) {
            self::$baseConfigVo = $this->GetServicePolicy()->GetBaseConfigView();
        }
        $baseConfigVo = self::$baseConfigVo;
        if (($baseConfigVo->useLocale == 'Y' || $useForce) && ! empty($vo)) {
            if (! ($vo instanceof LocaleTextVo)) {
                $newVo = new LocaleTextVo();
                if (isset($vo->ko)) {
                    $newVo->ko = $vo->ko;
                }
                if (isset($vo->en)) {
                    $newVo->en = $vo->en;
                }
                if (isset($vo->cn)) {
                    $newVo->cn = $vo->cn;
                }
                if (isset($vo->ko)) {
                    $newVo->jp = $vo->jp;
                }
                $vo = $newVo;
            }
            if (strtolower($vo->ko) == 'base locale text') {
                $vo->ko = '';
            }
            if (strtolower($vo->en) == 'base locale text') {
                $vo->en = '';
            }
            if (strtolower($vo->cn) == 'base locale text') {
                $vo->cn = '';
            }
            if (strtolower($vo->jp) == 'base locale text') {
                $vo->jp = '';
            }

            if ($baseConfigVo->localeKo != 'Y') {
                $vo->ko = '';
            }
            if ($baseConfigVo->localeEn != 'Y') {
                $vo->en = '';
            }
            if ($baseConfigVo->localeCn != 'Y') {
                $vo->cn = '';
            }
            if ($baseConfigVo->localeJp != 'Y') {
                $vo->jp = '';
            }

            if (empty($vo->ko) && empty($vo->en) && empty($vo->cn) && empty($vo->jp)) {
                return null;
            } else {
                return $vo;
            }
        } else {
            return null;
        }
    }

    /**
     * 濡쒖��씪 �쎒 �렪吏묎린 泥⑤� �씠誘몄� 濡쒖슦 �삎�깭 移섑솚
     *
     * @param LocaleTextVo $vo
     * @return LocaleTextVo
     */
    public function GetEditorParseRawLocale(LocaleTextVo $vo)
    {
        if (! empty($vo->ko)) {
            $vo->ko = $this->GetEditorParseRaw($vo->ko);
        }
        if (! empty($vo->en)) {
            $vo->en = $this->GetEditorParseRaw($vo->en);
        }
        if (! empty($vo->cn)) {
            $vo->cn = $this->GetEditorParseRaw($vo->cn);
        }
        if (! empty($vo->jp)) {
            $vo->jp = $this->GetEditorParseRaw($vo->jp);
        }
        return $vo;
    }

    /**
     * �쎒�렪吏묎린 泥⑤� �씠誘몄� 濡쒖슦 �쁽�깭濡� 媛��졇�삤湲�
     *
     * @param string $contents
     * @return string
     */
    public function GetEditorParseRaw($contents)
    {
        $rawContents = $contents;
        if (! empty($contents)) {
            $matches = Array();
            preg_match_all('#src=[\'"]/data/([^ \'"]+)#', $contents, $matches, PREG_SET_ORDER);
            foreach ($matches as $item) {
                $fileName = $item[1];
                if (! empty($fileName)) {
                    $filePath = FILE_DIR . '/' . $fileName;
                    if (file_exists($filePath)) {
                        $matches = Array();
                        if (preg_match('#([a-zA-Z0-9_]+)(/.+)#', $fileName, $matches)) {
                            $base64Img = '/data/' . $matches[1] . '/clone' . $matches[2];
                        } else {
                            $base64Img = '/data/' . $fileName;
                        }
                    } else {
                        $base64Img = 'data:image/png;base64,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';
                    }
                    $rawContents = str_replace('/data/' . $fileName, $base64Img, $rawContents);
                }
            }
        }
        return $rawContents;
    }

    /**
     * �쇅遺� 留곹겕 VO 媛��졇�삤湲�
     *
     * @param string $externalVideoUrl
     * @return ExternalVideoVo
     */
    public function GetExternalVideoVo($externalVideoUrl = '')
    {
        $videoType = '';
        $videoId = '';
        $matches = Array();
        if (preg_match('#^(http|https)://(.+)/(watch\?v=|)([a-zA-Z0-9_\-]+)$#', $externalVideoUrl, $matches)) {
            switch ($matches[2]) {
                case 'youtu.be':
                case 'www.youtube.com':
                    $videoType = 'youtube';
                    $videoId = $matches[4];
                    break;
                case 'vimeo.com':
                case 'www.vimeo.com':
                    $videoType = 'vimeo';
                    $videoId = $matches[4];
                    break;
            }
        }
        if (! empty($videoType) && ! empty($videoId)) {
            $externalVideoVo = new ExternalVideoVo();
            $externalVideoVo->videoId = $videoId;
            $externalVideoVo->videoType = $videoType;
            switch ($videoType) {
                case 'youtube':
                    $externalVideoVo->videoUrl = 'https://www.youtube.com/embed/' . $videoId;
                    $externalVideoVo->videoThumbUrl = 'thumbnail.jpg#100#image/jpg#youtube/' . $videoId . '.jpg';
                    $externalVideoVo->externalVideoUrl = 'https://youtu.be/' . $videoId;
                    break;
                case 'vimeo':
                    $externalVideoVo->videoUrl = 'https://player.vimeo.com/video/' . $videoId;
                    $externalVideoVo->videoThumbUrl = 'thumbnail.jpg#100#image/jpg#vimeo/' . $videoId . '.jpg';
                    $externalVideoVo->externalVideoUrl = 'https://vimeo.com/' . $videoId;
                    break;
            }
            return $externalVideoVo;
        } else {
            return null;
        }
    }

    /**
     * �뙆�씪 濡쒓렇 DAO 媛��졇�삤湲�
     *
     * @return \Dao\FileLogDao
     */
    public function GetFileLogDao()
    {
        return $this->GetDao('FileLogDao');
    }

    /**
     * �뙆�씪 �뾽濡쒕뱶 濡쒓렇 �뾽�뜲�씠�듃
     *
     * @param string $newUpload
     * @param string $oldUpload
     * @param string $fileParent
     * @param string $fileKey
     * @param string $filePart
     * @return integer
     */
    public function IsUploadImage($imgPath)
    {
        if (! empty($imgPath) && strpos($imgPath, '/assets') == - 1 && strpos($imgPath, '/') > - 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * �뙆�씪 �뾽濡쒕뱶 濡쒓렇 �뾽�뜲�씠�듃
     *
     * @param string $newUpload
     * @param string $oldUpload
     * @param string $fileParent
     * @param string $fileKey
     * @param string $filePart
     * @return integer
     */
    public function SetFileLogUpdate($newUpload = '', $oldUpload = '', $fileParent = '', $fileKey = '', $filePart = '')
    {
        $uploadFileSize = 0;
        $fileGroup = $fileParent . '-' . $fileKey . '-' . $filePart;
        if (! empty($fileGroup) && $newUpload != $oldUpload) {
            $fileLogDao = $this->GetFileLogDao();
            $uploadList = explode('@!@', $newUpload);
            $uploadFileList = [];
            foreach ($uploadList as $fileInfo) {
                if (! empty($fileInfo)) {
                    list ($fileName, $fileSize, $fileType, $fileUrl) = explode('#', $fileInfo . '####');
                    $fileSize = intval($fileSize);
                    if (! empty($fileSize)) {
                        $fileVo = new FileVo();
                        $fileVo->fileName = $fileName;
                        $fileVo->fileSize = $fileSize;
                        $fileVo->fileServer = $fileUrl;
                        $fileVo->fileType = $fileType;
                        $uploadFileList[$fileUrl] = $fileVo;
                        $uploadFileSize += $fileSize;
                    }
                }
            }
            $fileUploadVo = new FileLogVo();
            $fileUploadVo->mallId = $this->mallId;
            $fileUploadVo->fileGroup = $fileGroup;
            $fileUploadVo->fileStatus = '';
            $uploadList = $fileLogDao->GetList($fileUploadVo, 100, 0);
            $oldUploadList = [];
            foreach ($uploadList as $fileVo) {
                $fileUrl = $fileVo->fileServer;
                if (! empty($fileUrl)) {
                    $oldUploadList[$fileUrl] = $fileVo;
                }
            }
            $newUploadList = [];
            $deleteUploadList = [];
            $updateUploadList = [];
            foreach ($uploadFileList as $fileUrl => $fileVo) {
                if (! isset($oldUploadList[$fileUrl])) {
                    $newUploadList[] = $fileVo;
                } else if ($oldUploadList[$fileUrl]->fileStatus == 'N') {
                    $newUploadList[] = $fileVo;
                    $deleteUploadList[] = $fileVo;
                }
            }
            foreach ($oldUploadList as $fileUrl => $fileVo) {
                if (! isset($uploadFileList[$fileUrl]) && $fileVo->fileStatus == 'Y') {
                    $updateUploadList[] = $fileVo;
                }
            }
            if (! empty($deleteUploadList)) {
                $fileUploadVo->fileStatus = 'N';
                $fileUploadVo->children = $deleteUploadList;
                $fileLogDao->SetDelete($fileUploadVo);
            }
            if (! empty($newUploadList)) {
                $fileUploadVo->fileStatus = 'Y';
                $fileUploadVo->children = $newUploadList;
                $fileLogDao->SetCreate($fileUploadVo);
            }
            if (! empty($updateUploadList)) {
                $fileUploadVo->fileStatus = 'N';
                $fileUploadVo->children = $updateUploadList;
                $fileLogDao->SetUpdate($fileUploadVo);
            }
        }
        return $uploadFileSize;
    }

    /**
     * �떛湲��넠 DAO
     *
     * @var \Dao\AbstractDao[]
     */
    private $loadedDao = Array();

    /**
     * DAO 媛��졇�삤湲�
     *
     * @param string $daoName
     * @return \Dao\AbstractDao
     */
    public function GetDao($daoName = '')
    {
        if (! isset($this->loadedDao[$daoName])) {
            $daoClassName = \PhpLoader::GetSafeClassName($daoName, 'Dao');
            $this->loadedDao[$daoName] = new $daoClassName();
        }
        return $this->loadedDao[$daoName];
    }

    /**
     * 濡쒕뱶�맂 �꽌鍮꾩뒪
     *
     * @var AbstractService
     */
    private static $loadedService = Array();

    /**
     * 濡쒕뱶�맂 紐곗븘�씠�뵒
     *
     * @var string
     */
    private static $loadedMallId = '';

    /**
     * 濡쒕뱶�맂 濡쒓렇�씤 �젙蹂�
     *
     * @var LoginInfoVo
     */
    private static $loadedLoginInfo = null;

    /**
     * �꽌鍮꾩뒪 媛��졇�삤湲�
     *
     * @param string $serviceName
     * @param string $mallId
     * @return AbstractService
     */
    static public function GetService($serviceName = '', $mallId = '', $extraParam = null, $extraParam2 = null)
    {
        if (empty($mallId)) {
            $mallId = self::$loadedMallId;
        }
        $serviceCacheKey = $mallId . '_' . $serviceName;
        if (! isset(self::$loadedService[$serviceCacheKey])) {
            $safeClassName = \PhpLoader::GetSafeClassName($serviceName, 'Service');
            self::$loadedService[$serviceCacheKey] = new $safeClassName($mallId, $extraParam, $extraParam2);
        }
        return self::$loadedService[$serviceCacheKey];
    }

    /**
     * �젙梨� �꽌鍮꾩뒪 媛��졇�삤湲�
     *
     * @return PolicyService
     */
    public function GetServicePolicy()
    {
        if (defined('POLICY_SERVICE')) {
            return $this->GetService(POLICY_SERVICE, '', $this->loginInfo);
        } else {
            return $this->GetService("PolicyService", '', $this->loginInfo);
        }
    }

    /**
     * 硫붿씪 �꽌鍮꾩뒪 媛��졇�삤湲�
     *
     * @return MailService
     */
    public function GetServiceMail()
    {
        if (defined('MAIL_SERVICE')) {
            return $this->GetService(MAIL_SERVICE, '', $this->loginInfo);
        } else {
            return $this->GetService("MailService", '', $this->loginInfo);
        }
    }

    /**
     * SMS �꽌鍮꾩뒪 媛��졇�삤湲�
     *
     * @return \Service\SmsService
     */
    public function GetServiceSms()
    {
        if (defined('SMS_SERVICE')) {
            return $this->GetService(SMS_SERVICE, '', $this->loginInfo);
        } else {
            return $this->GetService("SmsService", '', $this->loginInfo);
        }
    }

    /**
     * �븣由� �꽌鍮꾩뒪 媛��졇�삤湲�
     *
     * @return \Service\NoticeService
     */
    public function GetServiceNotice()
    {
        if (defined('NOTICE_SERVICE')) {
            return $this->GetService(NOTICE_SERVICE, '', $this->loginInfo);
        } else {
            return $this->GetService("NoticeService", '', $this->loginInfo);
        }
    }

    
    /**
     * 테스트 
     *
     * @return \Service\NoticeService
     */
    public function GetServiceNoticetest()
    {
        
        //echo $this->loginInfo;
        
        if (defined('NOTICE_SERVICE')) {
            return $this->GetService(NOTICE_SERVICE, '', $this->loginInfo);
        } else {
            return $this->GetService("NoticeService", '', $this->loginInfo);
        }
    }
    
    
    /**
     * Kakao �꽌鍮꾩뒪 媛��졇�삤湲�
     *
     * @return \Service\KakaoService
     */
    public function GetServiceKakao()
    {
        if (defined('KAKAO_SERVICE')) {
            return $this->GetService(KAKAO_SERVICE, '', $this->loginInfo);
        } else {
            return $this->GetService("KakaoService", '', $this->loginInfo);
        }
    }

    /**
     * Kakao �꽌鍮꾩뒪 媛��졇�삤湲�
     *
     * @return \Service\MallService
     */
    public function GetServiceMall()
    {
        if (defined('MALL_SERVICE')) {
            return $this->GetService(MALL_SERVICE, '', $this->loginInfo);
        } else {
            return $this->GetService("MallService", '', $this->loginInfo);
        }
    }

    /**
     * 怨듦툒�뾽泥� �꽌鍮꾩뒪 媛��졇�삤湲�
     *
     * @return \Service\ScmService
     */
    public function GetServiceScm()
    {
        if (defined('SCM_SERVICE')) {
            return $this->GetService(SCM_SERVICE, '', $this->loginInfo);
        } else {
            return $this->GetService("ScmService", '', $this->loginInfo);
        }
    }

    /**
     * �뫖�떆 �븣由� �꽌鍮꾩뒪 媛��졇�삤湲�
     *
     * @return \Service\PushNoticeService
     */
    public function GetServicePushNotice()
    {
        if (defined('PUSH_NOTICE_SERVICE')) {
            return $this->GetService(PUSH_NOTICE_SERVICE, '', $this->loginInfo);
        } else {
            return $this->GetService("PushNoticeService", '', $this->loginInfo);
        }
    }

    /**
     * 怨듯넻 �꽌鍮꾩뒪 媛��졇�삤湲�
     *
     * @return \Service\CommonService
     */
    public function GetServiceCommon()
    {
        if (defined('COMMON_SERVICE')) {
            return $this->GetService(COMMON_SERVICE, '', $this->loginInfo);
        } else {
            return $this->GetService("CommonService", '', $this->loginInfo);
        }
    }

    /**
     * �넻怨� �젙蹂� �꽌鍮꾩뒪 媛��졇�삤湲�
     *
     * @return \Service\StatisticsService
     */
    public function GetServiceStatistics()
    {
        if (defined('STATISTICS_SERVICE')) {
            return $this->GetService(STATISTICS_SERVICE, '', $this->loginInfo);
        } else {
            return $this->GetService("StatisticsService", '', $this->loginInfo);
        }
    }

    /**
     * �넗�겙 �꽌鍮꾩뒪 媛��졇�삤湲�
     *
     * @return \Service\TokenService
     */
    public function GetServiceToken()
    {
        if (defined('TOKEN_SERVICE')) {
            return $this->GetService(TOKEN_SERVICE, '', $this->loginInfo);
        } else {
            return $this->GetService("TokenService", '', $this->loginInfo);
        }
    }

    /**
     * �궎�썙�뱶 �꽌鍮꾩뒪 媛��졇�삤湲�
     *
     * @return \Service\KeywordService
     */
    public function GetServiceKeyword()
    {
        if (defined('KEYWORD_SERVICE')) {
            return $this->GetService(KEYWORD_SERVICE, '', $this->loginInfo);
        } else {
            return $this->GetService("KeywordService", '', $this->loginInfo);
        }
    }

    /**
     * �쉶�썝 �꽌鍮꾩뒪 媛��졇�삤湲�
     *
     * @return \Service\MemberService
     */
    public function GetServiceMember()
    {
        if (defined('MEMBER_SERVICE')) {
            return $this->GetService(MEMBER_SERVICE, '', $this->loginInfo);
        } else {
            return $this->GetService("MemberService", '', $this->loginInfo);
        }
    }

    /**
     * �긽�뭹 �꽌鍮꾩뒪 媛��졇�삤湲�
     *
     * @return \Service\GoodsService
     */
    public function GetServiceGoods()
    {
        if (defined('GOODS_SERVICE')) {
            return $this->GetService(GOODS_SERVICE, '', $this->loginInfo);
        } else {
            return $this->GetService("GoodsService", '', $this->loginInfo);
        }
    }

    /**
     * �긽�뭹 �꽌鍮꾩뒪 媛��졇�삤湲�
     *
     * @return \Service\BoardService
     */
    public function GetServiceBoard($boardId = '')
    {
        if (defined('BOARD_SERVICE')) {
            return $this->GetService(BOARD_SERVICE, '', $boardId, $this->loginInfo);
        } else {
            return $this->GetService("BoardService", '', $boardId, $this->loginInfo);
        }
    }

    /**
     * 二쇰Ц �꽌鍮꾩뒪 媛��졇�삤湲�
     *
     * @return \Service\OrderService
     */
    public function GetServiceOrder()
    {
        if (defined('ORDER_SERVICE')) {
            return $this->GetService(ORDER_SERVICE, '', $this->loginInfo);
        } else {
            return $this->GetService("OrderService", '', $this->loginInfo);
        }
    }

    /**
     * 諛곗넚議고쉶 �꽌鍮꾩뒪 媛��졇�삤湲�
     *
     * @return \Service\DeliveryService
     */
    public function GetServiceDelivery()
    {
        if (defined('DELIVERY_SERVICE')) {
            return $this->GetService(DELIVERY_SERVICE, '', $this->loginInfo);
        } else {
            return $this->GetService("DeliveryService", '', $this->loginInfo);
        }
    }

    /**
     * KBM 愿�由� �꽌鍮꾩뒪 媛��졇�삤湲�
     *
     * @return \Service\KbmService
     */
    public function GetServiceKbm()
    {
        if (defined('KBM_SERVICE')) {
            return $this->GetService(KBM_SERVICE, '', $this->loginInfo);
        } else {
            return $this->GetService("KbmService", '', $this->loginInfo);
        }
    }

    /**
     * �겢�옒�뒪 �뙆�씪 留뚮뱾湲�
     *
     * @param string $tableName
     * @param string $className
     * @return \Vo\TableSchemaVo
     */
    public function CreateClassFile($tableName = '', $className = '', $showType = '')
    {
        $helper = new DatabaseHelper('KBMALL 1.0', 'http://www.hanbiz.kr/', 'Kim Jong-gab', 'outmind0@naver.com', 'kbmall 1.0');
        switch ($showType) {
            case 'schema':
                return $helper->getT(AbstractDatabase::GetDataBase(), $tableName, $className);
            case '':
            default:
                return $helper->CreateClassFile(AbstractDatabase::GetDataBase(), $tableName, $className);
        }
    }

    /**
     * Excel Form 媛��졇�삤湲�
     *
     * @param integer $formSno
     * @return string[]
     */
    public function GetExcelForm($formSno = 0)
    {
        $policyService = $this->GetServicePolicy();
        $result = $policyService->GetExcelFormView($formSno);
        if (! empty($result) && ! empty($result->fieldValue)) {
            return explode(',', $result->fieldValue);
        } else {
            return Array();
        }
    }

    /**
     * 怨좎쑀 踰덊샇 媛��졇�삤湲�
     *
     * @param string $prefix
     * @param integer $length
     * @return string
     */
    public function GetUniqId($prefix = '', $length = 10)
    {
        $uniqNo = '';
        while ($length > strlen($uniqNo)) {
            $uniqNo .= str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
        }
        return $prefix . substr($uniqNo, 0, $length);
    }

    /**
     * �럹�씠吏� �굹�쓽 �븘�씠�뀥 �뿬遺� 泥댄겕
     *
     * @param LoginInfoVo $loginInfoVo
     * @param PagingVo $pagingVo
     * @param boolean $isAdmin
     * @return \Vo\PagingVo
     */
    public function SetMyArticlesPaging(LoginInfoVo $loginInfoVo = null, PagingVo $pagingVo = null, $isAdmin = false)
    {
        $pagingVo->items = $this->SetMyArticles($loginInfoVo, $pagingVo->items, $isAdmin);
        return $pagingVo;
    }

    /**
     * 由ъ뒪�겕 �굹�쓽 �븘�씠�뀥 �뿬遺� 泥댄겕
     *
     * @param LoginInfoVo $loginInfoVo
     * @param AbstractMyArticleVo[] $voList
     * @param boolean $isAdmin
     * @return AbstractMyArticleVo[]
     */
    public function SetMyArticles(LoginInfoVo $loginInfoVo = null, $voList = Array(), $isAdmin = false)
    {
        foreach ($voList as $vo) {
            $this->SetMyArticle($loginInfoVo, $vo, $isAdmin);
        }
        return $voList;
    }

    /**
     * �떒�씪 �븘�씠�뀥 �굹�쓽 �븘�씠�뀥 �뿬遺� 泥댄겕
     *
     * @param LoginInfoVo $loginInfoVo
     * @param AbstractMyArticleVo $vo
     * @param boolean $isAdmin
     * @return AbstractMyArticleVo
     */
    public function SetMyArticle(LoginInfoVo $loginInfoVo = null, AbstractMyArticleVo $vo = null, $isAdmin = false)
    {
        if ($isAdmin || $this->IsUserAdmin($loginInfoVo, 'A')) {
            $vo->isMyArticle = true;
        } else if (! empty($loginInfoVo)) {
            if (! empty($loginInfoVo->memNo) && $vo->memNo == $loginInfoVo->memNo) {
                $vo->isMyArticle = true;
            } else if (! empty($loginInfoVo->scmNo)) {
                if ($vo->scmNo == $loginInfoVo->scmNo || $vo->sellerMemNo == $loginInfoVo->scmNo) {
                    $vo->isMyArticle = true;
                } else if (in_array($loginInfoVo->scmNo, $vo->scmNos)) {
                    $vo->isMyArticle = true;
                } else {
                    $vo->isMyArticle = false;
                }
            } else {
                $vo->isMyArticle = false;
            }
        } else {
            $vo->isMyArticle = false;
        }
        if (empty($this->memberAccessVo)) {
            $this->memberAccessVo = $this->GetServicePolicy()->GetMemberAccessView();
        }
        switch ($vo->memType) {
            case 'A':
                $vo->displayNm = $this->GetDisplayName($vo->memNm, $vo->nickNm, $vo->memId, $this->memberAccessVo->adminDsp, $this->memberAccessVo->adminLimitDsp, $this->memberAccessVo->adminDspFix);
                break;
            case 'M':
            default:
                if (! $vo->isMyArticle) {
                    $vo->displayNm = $this->GetDisplayName($vo->memNm, $vo->nickNm, $vo->memId, $this->memberAccessVo->memberDsp, $this->memberAccessVo->memberLimitDsp, '');
                } else {
                    $vo->displayNm = $this->GetDisplayName($vo->memNm, $vo->nickNm, $vo->memId, $this->memberAccessVo->memberDsp, '0', '');
                }
                break;
        }
        if ($vo instanceof AbstractMyArticleAnswerVo && ! empty($vo->answerMemNo)) {
            switch ($vo->answerMemType) {
                case 'A':
                    $vo->answerDisplayNm = $this->GetDisplayName($vo->answerMemNm, $vo->answerNickNm, $vo->answerMemId, $this->memberAccessVo->adminDsp, $this->memberAccessVo->adminLimitDsp, $this->memberAccessVo->adminDspFix);
                    break;
                case 'M':
                default:
                    $vo->answerDisplayNm = $this->GetDisplayName($vo->answerMemNm, $vo->answerNickNm, $vo->answerMemId, $this->memberAccessVo->memberDsp, $this->memberAccessVo->memberLimitDsp, '');
                    break;
            }
        }
        if (! empty($vo->memNo)) {
            $vo->memProfileImg = 'profile.png#1000#image/png#' . $this->mallId . '/memprofile/' . ($vo->memNo % 256) . '/' . $vo->memNo . '.png';
        } else {
            $vo->memProfileImg = '';
        }
        return $vo;
    }

    /**
     * �몴�떆 �씠由� 媛��졇�삤湲�
     *
     * @param string $name
     * @param string $nick
     * @param string $id
     * @param string $maskType
     * @param string $maskLimit
     * @param string $fixName
     * @return string
     */
    public function GetDisplayName($name = '', $nick = '', $id = '', $maskType = 'R', $maskLimit = '0', $fixName = '')
    {
        $displayName = '';
        $maxLength = 5;
        switch ($maskType) {
            case 'R':
                $displayName = $name;
                $maxLength = 3;
                break;
            case 'N':
                $displayName = $nick;
                $maxLength = 5;
                break;
            case 'I':
                $displayName = $id;
                $maxLength = 7;
                break;
            case 'F':
                $displayName = $fixName;
                $maxLength = 10;
                break;
        }
        if (empty($displayName)) {
            $displayName = $name;
        }
        if (empty($displayName)) {
            $displayName = $nick;
        }
        if (empty($displayName)) {
            $displayName = $id;
        }
        if (empty($displayName)) {
            $displayName = 'anonymous';
        }
        $maxLength = max($maxLength, mb_strlen($displayName, 'utf-8'));
        switch ($maskLimit) {
            case '1':
                return $this->getMbStrPad(mb_substr($displayName, 0, 1, 'utf-8'), $maxLength, '*', STR_PAD_RIGHT);
            case '2':
                return $this->getMbStrPad(mb_substr($displayName, 0, 2, 'utf-8'), $maxLength, '*', STR_PAD_RIGHT);
            case '3':
                return $this->getMbStrPad(mb_substr($displayName, 0, 3, 'utf-8'), $maxLength, '*', STR_PAD_RIGHT);
            case '4':
                return $this->getMbStrPad(mb_substr($displayName, 0, 4, 'utf-8'), $maxLength, '*', STR_PAD_RIGHT);
            case '0':
            default:
                return $displayName;
                break;
        }
    }

    public function getMbStrPad($input, $padLength, $padString = ' ', $padType = STR_PAD_RIGHT)
    {
        $diff = strlen($input) - mb_strlen($input);

        return str_pad($input, $padLength + $diff, $padString, $padType);
    }

    /**
     *
     * @var \Vo\MemberAccessVo
     */
    private $memberAccessVo;

    /**
     * �떒�씪 �븘�씠�뀥 �굹�쓽 �븘�씠�뀥 �뿬遺� 泥댄겕
     *
     * @param LoginInfoVo $loginInfoVo
     * @param AbstractMyArticleVo $vo
     * @param boolean $isAdmin
     * @return AbstractMyArticleVo
     */
    public function GetMyArticle(LoginInfoVo $loginInfoVo = null, AbstractMyArticleVo $vo = null)
    {
        if (! empty($loginInfoVo) && ! empty($vo)) {
            list ($vo->memType, $vo->memNo, $vo->memId, $vo->memNm, $vo->nickNm) = $this->GetMyArticleInfo($loginInfoVo, $vo->memNo);
        }
        if ($vo instanceof AbstractMyArticleAnswerVo) {
            if (! empty($vo->answerContents)) {
                if ($this->isDateEmpty($vo->answerDate)) {
                    $vo->answerDate = $this->getDateNow();
                }
                list ($vo->answerMemType, $vo->answerMemNo, $vo->answerMemId, $vo->answerMemNm, $vo->answerNickNm) = $this->GetMyArticleInfo($loginInfoVo, $vo->answerMemNo);
                $vo->isAnswered = 'Y';
            } else {
                $vo->answerMemType = '';
                $vo->answerMemNo = 0;
                $vo->answerMemId = '';
                $vo->answerMemNm = '';
                $vo->answerNickNm = '';
                $vo->isAnswered = 'N';
            }
        }
        return $vo;
    }

    /**
     * �븘�씠�뀥 �옉�꽦�옄 �젙蹂� 媛��졇�삤湲�
     *
     * @param LoginInfoVo $loginInfoVo
     * @param number $memNo
     */
    public function GetMyArticleInfo(LoginInfoVo $loginInfoVo = null, $memNo = 0, $memNm = '')
    {
        $memType = 'G';
        $memId = '';
        $nickNm = '';
        if (! empty($loginInfoVo) && ! empty($loginInfoVo->memNo)) {
            switch ($loginInfoVo->memLevel) {
                case 'A':
                case 'K':
                case 'S':
                case 'E':
                    if (! empty($memNo) && $memNo != $loginInfoVo->memNo) {
                        try {
                            $memInfo = $this->GetServiceMember()->GetMemberView($memNo,'', true);
                            $loginInfoVo = new LoginInfoVo();
                            $loginInfoVo->memLevel = 'M';
                            $loginInfoVo->memNo = $memInfo->memNo;
                            $loginInfoVo->memId = $memInfo->memId;
                            $loginInfoVo->memNm = $memInfo->memNm;
                            $loginInfoVo->nickNm = $memInfo->nickNm;
                            try {
                                $this->GetServicePolicy()->GetMemberAdminView($memNo);
                                $memType = 'A';
                            } catch (Exception $ex) {
                                $scmInfo = $this->GetServiceScm()->GetScmInfoMyView($loginInfoVo);
                                if (! empty($scmInfo) && ! empty($scmInfo->scmNo)) {
                                    $memType = 'P';
                                } else {
                                    $memType = 'M';
                                }
                            }
                        } catch (Exception $ex) {}
                    } else {
                        $memType = 'A';
                    }
                    break;
                case 'P':
                    $memType = 'P';
                    break;
                default:
                    $memType = 'M';
                    break;
            }

            $memNo = $loginInfoVo->memNo;
            $memId = $loginInfoVo->memId;
            $memNm = $loginInfoVo->memNm;
            $nickNm = $loginInfoVo->nickNm;
        } else {
            $memId = '';
        }
        if (empty($memNm)) {
            $memNm = 'guest';
        }
        if (empty($nickNm)) {
            $nickNm = $memNm;
        }
        return Array(
            $memType,
            $memNo,
            $memId,
            $memNm,
            $nickNm
        );
    }

    /**
     * 硫붿씤 �쟾�떆 �꽕�젙 愿��젴 �긽�뭹 �젙由� �빐�꽌 媛��졇�삤湲�
     *
     * @param DisplayMainItemVo $mainItemVo
     * @param string $groupSno
     * @param string $displayType
     * @return DisplayMainItemVo
     */
    public function GetDisplayMainRefGoods($mainItemVo, $groupSno = '', $displayType = '')
    {
        if (! empty($mainItemVo) && ! ($mainItemVo instanceof DisplayMainItemVo)) {
            if (! ($mainItemVo instanceof DisplayMainItemVo)) {
                $newMainVo = new DisplayMainItemVo();
                foreach ($mainItemVo as $key => $value) {
                    $newMainVo->$key = $value;
                }
                $mainItemVo = $newMainVo;
            }
        }
        if (! empty($mainItemVo) && ($mainItemVo instanceof DisplayMainItemVo) && isset($mainItemVo->useYn) && isset($mainItemVo->sortAutoFl)) {
            $mainItemVo->refGoodsListVo = [];
            if ($mainItemVo->useYn != 'N' && $mainItemVo->sortAutoFl == 'U' && isset($mainItemVo->refGoodsCd) && count($mainItemVo->refGoodsCd) > 0) {
                $goodsService = $this->GetServiceGoods();
                foreach ($mainItemVo->refGoodsCd as $goodsCode) {
                    $mainItemVo->refGoodsListVo[] = $goodsService->GetGoodsInfoSimpleView($goodsCode);
                }
                $refGoodsImgMap = Array();
                foreach ($mainItemVo->refGoodsImage as $refGoodsImage) {
                    $refGoodsImgMap[$refGoodsImage->goodsCode] = $refGoodsImage->goodsImage;
                }
                foreach ($mainItemVo->refGoodsListVo as $refGoods) {
                    $goodsCode = $refGoods->goodsCode;
                    if (isset($refGoodsImgMap[$goodsCode])) {
                        $refGoods->goodsImageMaster = $refGoodsImgMap[$goodsCode];
                    }
                }
                $goodsService->GetGoodsPriceScmCheckGroupCode($mainItemVo->refGoodsListVo, $displayType, $groupSno);
            }
        }
        return $mainItemVo;
    }

    /**
     * 硫붿꽭吏� VO 媛��졇�삤湲�
     *
     * @param string $msgCode
     * @param mixed $extraData
     * @return MessageVo
     */
    public function GetMessageVo($msgCode = '', $extraData = null)
    {
        $vo = new MessageVo();
        if (empty($msgCode) || $msgCode == 'success') {
            $vo->code = 'success';
        } else {
            $vo->code = $msgCode;
            $policyService = $this->GetServicePolicy();
            $vo->message = $policyService->GetBaseMessageValue($msgCode);
        }
        if (! empty($extraData)) {
            $vo->extraData = $extraData;
        }
        return $vo;
    }

    /**
     * 濡쒓렇�씤 �젙蹂� 媛��졇�삤湲�
     *
     * @return \Vo\LoginInfoVo
     */
    public function getLoginInfo()
    {
        if (empty($this->loginInfo)) {
            $this->loginInfo = self::$loadedLoginInfo;
            return $this->loginInfo;
        } else {
            return $this->loginInfo;
        }
    }

    /**
     * 濡쒓렇�씤 �뿬遺� �솗�씤
     *
     * @param LoginInfoVo $loginInfoVo
     * @return boolean
     */
    public function CheckLogin(LoginInfoVo $loginInfoVo = null, $isAdmin = false)
    {
        if ($isAdmin) {
            return true;
        }
        if (! empty($loginInfoVo)) {
            if ($loginInfoVo->memNo > 0) {
                return true;
            } else {
                $this->GetException(KbmException::DATA_ERROR_AUTH);
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 怨듦툒�뾽泥� 愿�由ъ옄 濡쒓렇�씤 �뿬遺�
     *
     * @param LoginInfoVo $loginInfoVo
     * @return boolean
     */
    public function IsScmAdmin()
    {
        if (empty($this->loginInfo) && ! empty(self::$loadedLoginInfo)) {
            $this->loginInfo = self::$loadedLoginInfo;
        }
        if (! empty($this->loginInfo) && ! empty($this->loginInfo->scmNo) && $this->loginInfo->tokenType == 'A') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * �쟾�솕踰덊샇�뿉�꽌 �듅�닔 臾몄옄 �젣嫄고썑 媛��졇�삤湲�
     *
     * @param string $telNo
     * @return string
     */
    public function getTelSafeType($telNo = '')
    {
        if (! empty($telNo)) {
            $telNo = preg_replace('#^\+82\-#', '', $telNo);
            $telNo = preg_replace('#[^0-9]#', '', $telNo);
        }
        return $telNo;
    }

    /**
     * �듅�젙 Unixtime �쓣 二쇱뼱吏� �궇�옄 �룷留룹쑝濡� 蹂��솚�빐�꽌 媛��졇�삤湲�
     *
     * @param integer $timeNow
     * @param string $dateFormat
     * @return string
     */
    public function getDateNow($timeNow = 0, $dateFormat = 'Y-m-d H:i:s')
    {
        if (empty($timeNow)) {
            return date($dateFormat);
        } else {
            if ($timeNow > 0) {
                return date($dateFormat, $timeNow);
            } else {
                return date($dateFormat, 0);
            }
        }
    }

    /**
     * �듅�젙 Unixtime �쓣 二쇱뼱吏� �궇�옄 �룷留룹쑝濡� 蹂��솚�빐�꽌 媛��졇�삤湲�
     *
     * @param integer $timeNow
     * @param string $dateFormat
     * @return string
     */
    public function isObjectEquals($value1, $value2)
    {
        $valueKey1 = '';
        $valueKey2 = '';
        if (is_array($value1)) {
            if (empty($value1)) {
                $valueKey1 = '';
            } else {
                $valueKey1 = json_encode($value1);
            }
        } else if (is_object($value1)) {
            $valueKey1 = json_encode($value1);
        } else {
            if (empty($value1) || $value1 === '0' || $value1 === 0 || $value1 === '0000-00-00 00:00:00' || $value1 === '0000-00-00') {
                $valueKey1 = '';
            } else {
                $valueKey1 = json_encode(trim($value1));
            }
        }
        if (is_array($value2)) {
            if (empty($value2)) {
                $valueKey2 = '';
            } else {
                $valueKey2 = json_encode($value2);
            }
        } else if (is_object($value2)) {
            $valueKey2 = json_encode($value2);
        } else {
            if (empty($value2) || $value2 === '0' || $value2 === 0 || $value2 === '0000-00-00 00:00:00' || $value2 === '0000-00-00') {
                $valueKey2 = '';
            } else {
                $valueKey2 = json_encode(trim($value2));
            }
        }
        if (md5(strtoupper($valueKey1)) == md5(strtoupper($valueKey2))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * �궇�옄 鍮꾩뼱 �엳�뒗吏� �뿬遺� �솗�씤
     *
     * @param string $inDate
     * @return boolean
     */
    public function isDateEmpty($inDate = '')
    {
        if (empty($inDate) || strpos($inDate, '0000') === 0 || strpos($inDate, 'NaN') > - 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 踰꾩쟾 �솗�씤
     *
     * @param mixed $classObj
     * @param array $items
     * @return string[]
     */
    public function GetVersionCheckVoList($classObj = null, $items = Array())
    {
        $newList = Array();
        if (! empty($items)) {
            foreach ($items as $item) {
                $newList[] = $this->GetVersionCheckVo(clone $classObj, $item);
            }
        }
        return $newList;
    }

    /**
     * 踰꾩쟾 �솗�씤
     *
     * @param mixed $classObj
     * @param mixed $item
     * @return mixed
     */
    public function GetVersionCheckVo($classObj = null, $item = null)
    {
        if ($item instanceof $classObj) {
            return $item;
        } else {
            if (! is_null($item)) {
                foreach ($item as $key => $value) {
                    if ($classObj instanceof \stdClass || isset($classObj->$key)) {
                        $classObj->$key = $value;
                    }
                }
                return $classObj;
            } else {
                return null;
            }
        }
    }

    /**
     * 踰꾩쟾 �솗�씤
     *
     * @param mixed $classObj
     * @param mixed $item
     * @return mixed
     */
    public function GetDeepClone($classObj = null)
    {
        if (! empty($classObj)) {
            if (is_array($classObj)) {
                $newData = Array();
                foreach ($classObj as $key => $data) {
                    $newData[$key] = $this->GetDeepClone($data);
                }
                return $newData;
            } else if (is_object($classObj)) {
                $newData = clone $classObj;
                foreach ($newData as $key => $data) {
                    if (is_array($data) || is_object($data)) {
                        $newData->$key = $this->GetDeepClone($data);
                    }
                }
                return $newData;
            } else {
                return $classObj;
            }
        }
        return $classObj;
    }

    /**
     * 踰꾩쟾 �솗�씤
     *
     * @param mixed $classObj
     * @param mixed $item
     * @return mixed
     */
    public function GetAuthCodeValue($authValue, $authType = 'email')
    {
        if (strpos($authValue, '#') > 0) {
            list ($authValue) = explode('#', $authValue);
            return $authValue;
        } else {
            return $authValue;
        }
    }

    /**
     * 踰꾩쟾 �솗�씤
     *
     * @param mixed $classObj
     * @param mixed $item
     * @return mixed
     */
    public function GetMaskingEncrypt($str, $secretKey = 'secret key', $secretIv = 'secret iv')
    {
        $key = hash('sha256', $secretKey);
        $iv = substr(hash('sha256', $secretIv), 0, 16);
        return $secretIv . '#' . str_replace("=", "", base64_encode(openssl_encrypt($str, "AES-256-CBC", $key, 0, $iv)));
    }

    public function GetMaskingDecrypt($str, $secretKey = 'secret key')
    {
        if (strpos($str, '#') > 0) {
            list ($secretIv, $str) = explode('#', $str);
            $key = hash('sha256', $secretKey);
            $iv = substr(hash('sha256', $secretIv), 0, 16);
            return openssl_decrypt(base64_decode($str), "AES-256-CBC", $key, 0, $iv);
        } else {
            return $str;
        }
    }

    /**
     * 踰꾩쟾 �솗�씤
     *
     * @param mixed $classObj
     * @param mixed $item
     * @return mixed
     */
    public function GetMaskingValue($maskValue = '', $maskType = 'email')
    {
        if (! empty($maskValue)) {
            switch ($maskType) {
                case 'email':
                    $matches = Array();
                    if (preg_match('#^([a-z0-9]{2})[^@]+@(.+)$#', $maskValue, $matches)) {
                        return $this->GetMaskingEncrypt($maskValue, $this->loginInfo->passphrase, $matches[1] . '******@' . $matches[2]);
                    } else {
                        return '';
                    }
                    break;
                case 'tel':
                    $matches = Array();
                    if (preg_match('#^((\+|)[0-9\-]+)\-([0-9]{2})[0-9]*\-([0-9]{2})[0-9]*$#', $maskValue, $matches)) {
                        return $this->GetMaskingEncrypt($maskValue, $this->loginInfo->passphrase, $matches[1] . '-' . $matches[3] . '**-' . $matches[4] . '**');
                    } else if (preg_match('#^(0[0-9]{1,2})([0-9]{2})[0-9]{1,2}([0-9]{2})[0-9]{2}$#', $maskValue, $matches)) {
                        return $this->GetMaskingEncrypt($maskValue, $this->loginInfo->passphrase, $matches[1] . '-' . $matches[2] . '**-' . $matches[3] . '**');
                    } else if (preg_match('#^([0-9]{4})([0-9]{2})[0-9]{2}$#', $maskValue, $matches)) {
                        return $this->GetMaskingEncrypt($maskValue, $this->loginInfo->passphrase, $matches[1] . '-' . $matches[2] . '**');
                    } else {
                        return $maskValue;
                    }
                    break;
                case 'address':
                    if ($maskValue instanceof AddressVo) {
                        if (preg_match('#^(.+(�쓭|硫�|�룞|湲�)) #', $maskValue->address, $matches)) {
                            $maskValue->address = $matches[1] . ' **';
                            $maskValue->addressSub = '';
                        } else {
                            $maskValue->addressSub = '';
                        }
                    }
                    return $maskValue;
                    break;
            }
        } else {
            return $maskValue;
        }
    }

    /**
     * 踰꾩쟾 �솗�씤
     *
     * @param mixed $classObj
     * @param mixed $item
     * @return mixed
     */
    public function GetUnMaskingValue($maskValue = '', $maskType = 'email')
    {
        if (! empty($maskValue)) {
            switch ($maskType) {
                case 'email':
                    $matches = Array();
                    if (preg_match('/^(.+)#.+$/', $maskValue, $matches)) {
                        return $matches[1];
                    } else {
                        return $maskValue;
                    }
                    break;
                case 'address':
                    if ($maskValue instanceof AddressVo) {
                        $matches = Array();
                        if (preg_match('/^(.+)#.+$/', $maskValue->addressSub, $matches)) {
                            $maskValue->addressSub = $matches[1];
                        }
                        return $maskValue;
                    } else {
                        $matches = Array();
                        if (preg_match('/^(.+)#.+$/', $maskValue, $matches)) {
                            return $matches[1];
                        } else {
                            return $maskValue;
                        }
                    }
                    break;
                case 'tel':
                    $matches = Array();
                    if (preg_match('/^(.+)#.+$/', $maskValue, $matches)) {
                        $maskValue = $matches[1];
                    }
                    if (preg_match('/^\+82\-(.+)$/', $maskValue, $matches)) {
                        $maskValue = $matches[1];
                    }
                    return $maskValue;
                    break;
            }
        } else {
            return $maskValue;
        }
    }

    /**
     * HTML 而⑦뀗痢� 媛��졇�삤湲�
     *
     * @param string $contents
     * @return string
     */
    public function GetSafeHtmlContents($contents = '', $isPlainText = true)
    {
        $contents = trim($contents);
        if (! empty($contents)) {
            $checkSafeHtml = true;
            if (! empty($this->loginInfo)) {
                switch ($this->loginInfo->tokenType) {
                    case 'A':
                        $checkSafeHtml = false;
                        break;
                    case 'M':
                    case 'W':
                    default:
                        $checkSafeHtml = true;
                        break;
                }
            }
            if ($checkSafeHtml) {
                if ($isPlainText) {
                    $contents = str_replace('<', '&lt;', $contents);
                    $contents = str_replace('>', '&gt;', $contents);
                } else {
                    $contents = preg_replace('#<(|/)(body|frame|link|object|iframe|frameset|script|style|meta)([^>]*)(|/)>#i', '&lt;$1$2$3$4&gt;', $contents);
                    $contents = preg_replace('# on([a-zA-Z]{3,10})#i', ' data-on$1', $contents);
                }
                return $contents;
            } else {
                return $contents;
            }
        } else {
            return $contents;
        }
    }

    /**
     * �궗�씠�듃 �솚寃� �젙蹂� 媛��졇�삤湲�
     *
     * @param \Vo\CodeVo[] $codeVoList
     * @param string $defValue
     * @return string
     */
    public function GetCodeValueBySite($codeVoList = Array(), $defValue = '')
    {
        if (! empty($codeVoList) && count($codeVoList) > 0) {
            foreach ($codeVoList as $vo) {
                if ($vo->value == $this->siteId) {
                    if (! empty($vo->text)) {
                        $defValue = $vo->text;
                    }
                    break;
                }
            }
        }
        return $defValue;
    }

    /**
     * json �뙆�씪紐낆뿉�꽌 �궎媛� 異붿텧
     *
     * @param string $subItemKey
     * @return string
     */
    public function GetJsonKey($subItemKey = '')
    {
        $matches = Array();
        if (preg_match('#^([a-z0-9A-Z_\-]+)(\.json|\.xml|\.do|\.html|)$#', $subItemKey, $matches)) {
            return $matches[1];
        } else {
            return '';
        }
    }

    /**
     * �븫�솕 �빐�젣�빐�꽌 媛��졇�삤湲�
     *
     * @param string $ctBase64
     * @param string $ivHex
     * @param string $s
     * @param string $passphrase
     * @return mixed
     */
    public function cryptoJsAesDecryptSimple($vo, $passphrase = '')
    {
        if (! empty($vo)) {
            if (isset($vo->ct) && isset($vo->iv) && isset($vo->s)) {
                return $this->cryptoJsAesDecrypt($vo->ct, $vo->iv, $vo->s);
            }
        }
        return $vo;
    }

    /**
     * �븫�솕 �빐�젣�빐�꽌 媛��졇�삤湲�
     *
     * @param string $ctBase64
     * @param string $ivHex
     * @param string $s
     * @param string $passphrase
     * @return mixed
     */
    public function cryptoJsAesDecrypt($ctBase64, $ivHex, $s, $passphrase = '')
    {
        try {
            $salt = hex2bin($s);
            $iv = hex2bin($ivHex);
        } catch (Exception $e) {
            return null;
        }
        $ct = base64_decode($ctBase64);
        if (empty($passphrase)) {
            $passphrase = MST_PASSWD_SECRET;
        }
        if (empty($passphrase)) {
            $passphrase = 'e6f8a3245effe2e8b2db8ae2e7006c17';
        }
        $concatedPassphrase = $passphrase . $salt;
        $md5Data = array();
        $md5Data[0] = md5($concatedPassphrase, true);
        $result = $md5Data[0];
        for ($i = 1; $i < 3; $i ++) {
            $md5Data[$i] = md5($md5Data[$i - 1] . $concatedPassphrase, true);
            $result .= $md5Data[$i];
        }
        $key = substr($result, 0, 32);
        return json_decode(openssl_decrypt($ct, 'aes-256-cbc', $key, true, $iv));
    }

    /**
     * �듅�젙 �뜲�씠��瑜� �븫�샇�솕 �븯湲�
     *
     * @param string $jsonData
     * @param string $passphrase
     * @return stdClass
     */
    public function cryptoJsAesEncrypt($jsonData, $passphrase = '')
    {
        if (empty($passphrase)) {
            $passphrase = MST_PASSWD_SECRET;
        }
        if (empty($passphrase)) {
            $passphrase = 'e6f8a3245effe2e8b2db8ae2e7006c17';
        }
        $value = json_encode($jsonData);
        $salt = openssl_random_pseudo_bytes(8);
        $salted = '';
        $dx = '';
        while (strlen($salted) < 48) {
            $dx = md5($dx . $passphrase . $salt, true);
            $salted .= $dx;
        }
        $key = substr($salted, 0, 32);
        $iv = substr($salted, 32, 16);
        $encrypted_data = openssl_encrypt($value, 'aes-256-cbc', $key, true, $iv);
        $data = new stdClass();
        $data->ct = base64_encode($encrypted_data);
        $data->iv = bin2hex($iv);
        $data->s = bin2hex($salt);
        return $data;
    }

    /**
     * �쟾�솕踰덊샇 �룷留� �솗�씤
     *
     * @param string $telNo
     * @return string
     */
    public function checkFillTelNo($telNo = '')
    {
        $telNo = trim($telNo);
        if (! empty($telNo)) {
            if (strpos($telNo, '+') === false || strpos($telNo, '-') === false) {
                $search = array(
                    '*',
                    'O',
                    '�뀋',
                    '怨�',
                    '�씪',
                    '�븯�굹',
                    'I',
                    '�씠',
                    '�몮',
                    '�궪',
                    '�궗',
                    '�꽬',
                    '�삤',
                    '�쑁',
                    '�뿬�꽢',
                    '移�',
                    '�뙏',
                    '援�',
                    '�븘�솄'
                );
                $replace = array(
                    '0',
                    '0',
                    '0',
                    '0',
                    '1',
                    '1',
                    '1',
                    '2',
                    '2',
                    '3',
                    '4',
                    '4',
                    '5',
                    '6',
                    '6',
                    '7',
                    '8',
                    '9',
                    '9'
                );
                $telNo = str_replace($search, $replace, $telNo);
                $telNo = preg_replace('#[^0-9]#', '', $telNo);
                $matches = Array();
                if (preg_match('#^10[0-9]{8}$#', $telNo)) {
                    $telNo = '820' . $telNo;
                } else if (preg_match('#^1[0-9]{7}$#', $telNo) || preg_match('#^0[0-9]{7,10}$#', $telNo)) {
                    $telNo = '82' . $telNo;
                }
                if (preg_match('#^(1)([0-9]{3})([0-9]{3})([0-9]{4})$#', $telNo, $matches)) {
                    $telNo = '+' . $matches[1] . '-' . $matches[2] . '-' . $matches[3] . '-' . $matches[4];
                } else if (preg_match('#^([0-9]{2})([0-9]{3})([0-9]{4})([0-9]{4})$#', $telNo, $matches)) {
                    $telNo = '+' . $matches[1] . '-' . $matches[2] . '-' . $matches[3] . '-' . $matches[4];
                } else if (preg_match('#^([0-9]{2})([0-9]{2,3})([0-9]{4})([0-9]{4})$#', $telNo, $matches)) {
                    $telNo = '+' . $matches[1] . '-' . $matches[2] . '-' . $matches[3] . '-' . $matches[4];
                } else if (preg_match('#^([0-9]{2})([0-9]{4})([0-9]{4})$#', $telNo, $matches)) {
                    $telNo = '+' . $matches[1] . '-' . $matches[2] . '-' . $matches[3];
                } else if (preg_match('#^([0-9]{2})([0-9]{2})([0-9]{3})([0-9]{4})$#', $telNo, $matches)) {
                    $telNo = '+' . $matches[1] . '-' . $matches[2] . '-' . $matches[3] . '-' . $matches[4];
                }
            }
        }
        return $telNo;
    }

    /**
     * 二쇱냼 �슦�렪踰덊샇 梨꾩슦湲�
     *
     * @param AddressVo $addressVo
     * @return \Vo\AddressVo
     */
    public function checkFillAddressVo(AddressVo $addressVo = null)
    {
        if (! empty($addressVo) && ! empty($addressVo->address) && strlen($addressVo->address) > 5 && empty($addressVo->zonecode)) {
            $address = trim($addressVo->address);
            if (! empty($address)) {
                $commonService = $this->GetServiceCommon();
                $addrVo = $commonService->GetZipcodeVoByKeyword($address, 'kr');
                if (! empty($addrVo)) {
                    $addressVo->nationCode = $addrVo->nationCode;
                    $addressVo->zonecode = $addrVo->zipNo;
                }
            }
        }
        if (! empty($addressVo) && empty($addressVo->nationCode)) {
            $addressVo->nationCode = 'kr';
        }
        return $addressVo;
    }

    /**
     * �꽌鍮꾩뒪 �뙆�떛
     *
     * @param \AbstractKbmController $controller
     * @param string $cmd
     * @param RequestVo $request
     * @param string $subItemKey
     * @param string $extraKey
     * @return mixed
     */
    public function GetServiceParse(\AbstractKbmController $controller, $cmd = '', RequestVo $request, $subItemKey = '', $extraKey = '')
    {
        return $this->GetException(\KbmException::DATA_ERROR_UNKNOWN);
    }
}

?>