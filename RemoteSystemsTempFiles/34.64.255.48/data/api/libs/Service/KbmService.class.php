<?php

/**
 * Project:     Kbmall & Maholn Project
 * File:        libs/Service/KbmService.class.php
 *
 * @link http://hanbiz.kr/
 * @author Kim Jong-gab <outmind0@naver.com>
 * @version 1.0
 * @since 1.0
 * @copyright 2001-2017 Hanbiz, Inc.
 * @package Kbmall
 */
namespace Service;

use Vo\KbmTokenVo;
use Vo\LoginInfoVo;
use Vo\RequestVo;
use KbmException;

/**
 * KBM 서비스
 */
class KbmService extends AbstractService
{

    /**
     * 토큰 정보
     *
     * @var KbmTokenVo
     */
    private $apiToken = null;

    /**
     * 토큰 캐시 키
     *
     * @var string
     */
    const API_KEY = 'KBM_API_TOKEN';

    /**
     * 토큰 가져오기
     *
     * @return KbmTokenVo
     */
    public function GetKbmTokenVo()
    {
        if (is_null($this->apiToken)) {
            $this->apiToken = $this->GetCacheFile(self::API_KEY);
            if (empty($this->apiToken)) {
                $this->apiToken = new KbmTokenVo();
            }
        }
        return $this->apiToken;
    }

    /**
     * KBM 서비스 정보 가져오기
     *
     * @param string $url
     * @param string[] $fields
     * @param string $method
     * @param boolean $isSecure
     * @return mixed
     */
    public function GetKbmJson($url, $fields, $method = 'GET', $isSecure = false)
    {
        if (!$isSecure || $this->CheckApiLogin()) {
            $apiHeader = null;
            if (!empty($this->loginInfo) && !empty($this->loginInfo->memNo) && $this->loginInfo->memLevel == 'K') {
                $apiToken = $this->GetKbmTokenVo();
                if (!empty($apiToken->token) && $apiToken->loginToken == $this->loginInfo->token) {
                    $apiHeader = array(
                        'Authorization: Bearer ' . $apiToken->token,
                    );
                }
            }
            $result = $this->GetOpenJson(SERVICE_API_URL . 'manager/' . $url . '.json', $fields, $method, $apiHeader);
            if (!empty($result) && $result->result && isset($result->item)) {
                return $result->item;
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * 토큰 가져오기
     *
     * @param LoginInfoVo $loginInfo
     * @param string $passwd
     * @return boolean
     */
    public function GetToken(LoginInfoVo $loginInfo, $passwd = '')
    {
        if (!empty($loginInfo->token) && !empty($loginInfo->memNo)) {
            $result = $this->GetKbmJson('token', array(
                'mallId' => $this->mallId,
                'userNo' => $loginInfo->memNo,
                'userNm' => $loginInfo->memNm,
                'secret' => $passwd,
            ), 'POST');
            if (!empty($result->token)) {
                $this->apiToken = null;
                $apiToken = new KbmTokenVo();
                $apiToken->token = $result->token;
                $apiToken->loginToken = $loginInfo->token;
                $this->SetCacheFile(self::API_KEY, $apiToken, 60 * 60 * 24);
                return true;
            }
        }
        return false;
    }

    /**
     * 나의 정보 보기
     *
     * @return NULL
     */
    public function GetMyInfoView()
    {
        return null;
    }

    /**
     * 나의 정보 업데이트 하기
     *
     * @param RequestVo $request
     * @return NULL
     */
    public function GetMyInfoUpdate(RequestVo $request)
    {
        return null;
    }

    /**
     * API 로그인 체크
     *
     * @return boolean
     */
    public function CheckApiLogin()
    {
        if (!empty($this->loginInfo) && !empty($this->loginInfo->memNo) && $this->loginInfo->memLevel == 'K') {
            $apiToken = $this->GetKbmTokenVo();
            if (!empty($apiToken->token) && $apiToken->loginToken == $this->loginInfo->token) {
                return true;
            }
        }
        $this->GetException(KbmException::DATA_ERROR_AUTH);
    }

    /**
     * 요청 Request 를 Array 로 변환해서 가져오기
     *
     * @param RequestVo $request
     * @return mixed[]
     */
    public function GetRequest2Array(RequestVo $request)
    {
        $data = $request->GetData();
        if (is_object($data)) {
            $arrData = array();
            foreach ($data as $key => $value) {
                $arrData[$key] = $value;
            }
            return $arrData;
        } else {
            return $data;
        }
    }

    /**
     * 서비스 사용자 생성하기
     *
     * @param RequestVo $request
     * @return mixed
     */
    public function GetServiceUserCreate(RequestVo $request)
    {
        return $this->GetKbmJson('user', $this->GetRequest2Array($request), 'POST', true);
    }

    /**
     * 서비스 사용자 페이지 가져오기
     *
     * @param RequestVo $request
     * @return \Vo\PagingVo
     */
    public function GetServiceUserPaging(RequestVo $request)
    {
        return $this->GetKbmJson('user', $this->GetRequest2Array($request), 'GET', true);
    }

    /**
     * 서비스 사용자 정보 보기
     *
     * @param integer $uid
     * @return mixed[]
     */
    public function GetServiceUserView($uid = 0)
    {
        return $this->GetKbmJson('user/' . $uid, null, 'GET', true);
    }

    /**
     * 서비스 사용자 정보 업데이트
     *
     * @param integer $uid
     * @param RequestVo $request
     * @return mixed|NULL
     */
    public function GetServiceUserUpdate($uid = 0, RequestVo $request)
    {
        return $this->GetKbmJson('user/' . $uid, $this->GetRequest2Array($request), 'POST', true);
    }

    /**
     * 서비스 사용자 정보 삭제
     *
     * @param integer $uid
     * @return mixed|NULL
     */
    public function GetServiceUserDelete($uid = 0)
    {
        return $this->GetKbmJson('user/' . $uid, null, 'DELETE', true);
    }

    /**
     * 주문 정보 페이징 가져오기
     *
     * @param RequestVo $request
     * @return \Vo\PagingVo
     */
    public function GetOrderInfoPaging(RequestVo $request)
    {
        return $this->GetKbmJson('order', $this->GetRequest2Array($request), 'GET', true);
    }

    /**
     * 주문 정보 보기 가져오기
     *
     * @param integer $uid
     * @return mixed
     */
    public function GetOrderInfoView($uid = 0)
    {
        return $this->GetKbmJson('order/' . $uid, null, 'GET', true);
    }

    /**
     * 주문 정보 업데이트 하기
     *
     * @param integer $uid
     * @param RequestVo $request
     * @return mixed
     */
    public function GetOrderInfoUpdate($uid = 0, RequestVo $request)
    {
        return $this->GetKbmJson('order/' . $uid, $this->GetRequest2Array($request), 'POST', true);
    }

    /**
     * 주문 정보 삭제하기
     *
     * @param integer $uid
     * @return mixed|NULL
     */
    public function GetOrderInfoDelete($uid = 0)
    {
        return $this->GetKbmJson('order/' . $uid, null, 'DELETE', true);
    }

    /**
     * SMS 전송 문자 목록 가져오기
     *
     * @param RequestVo $request
     * @return mixed
     */
    public function GetKbTranPaging(RequestVo $request)
    {
        return $this->GetKbmJson('sms', $this->GetRequest2Array($request), 'GET', true);
    }

    /**
     * SMS 전송 보기
     *
     * @param integer $uid
     * @return mixed
     */
    public function GetKbTranView($uid = 0)
    {
        return $this->GetKbmJson('sms/' . $uid, null, 'GET', true);
    }

    /**
     * 카카오 전송 목록 가져오기
     *
     * @param RequestVo $request
     * @return mixed
     */
    public function GetKakaoTranPaging(RequestVo $request)
    {
        return $this->GetKbmJson('kakao', $this->GetRequest2Array($request), 'GET', true);
    }
    
    /**
     * 카카오 전송 보기
     *
     * @param integer $uid
     * @return mixed
     */
    public function GetKakaoTranView($uid = 0)
    {
        return $this->GetKbmJson('kakao/' . $uid, null, 'GET', true);
    }
    
    /**
     * 카카오 전송 목록 가져오기
     *
     * @param RequestVo $request
     * @return mixed
     */
    public function GetKakaoTplPaging(RequestVo $request)
    {
        return $this->GetKbmJson('kakao_tpl', $this->GetRequest2Array($request), 'GET', true);
    }
    
    /**
     * 카카오 전송 보기
     *
     * @param integer $uid
     * @return mixed
     */
    public function GetKakaoTplView($uid = 0)
    {
        return $this->GetKbmJson('kakao_tpl/' . $uid, null, 'GET', true);
    }

    /**
     * 카카오 전송 보기
     *
     * @param integer $uid
     * @return mixed
     */
    public function GetKakaoTplComment(RequestVo $request)
    {
        $result = $this->GetKbmJson('kakao_tpl_comment', $this->GetRequest2Array($request), 'POST', true);
        if (empty($result)) {
            $this->GetException(KbmException::DATA_ERROR_AUTH);
        } else {
            return $result;
        }
    }
    
    /**
     * MMS 전송 페이징 가져오기
     *
     * @param RequestVo $request
     * @return \Vo\PagingVo
     */
    public function GetKbMmsTranPaging(RequestVo $request)
    {
        return $this->GetKbmJson('mms', $this->GetRequest2Array($request), 'GET', true);
    }

    /**
     * MMS 전송 내용 가져오기
     *
     * @param integer $uid
     * @param RequestVo $request
     * @return mixed
     */
    public function GetKbMmsTranView($uid = 0, RequestVo $request)
    {
        return $this->GetKbmJson('mms/' . $uid, null, 'GET', true);
    }

    /**
     * Mail 전송 페이징 가져오기
     *
     * @param RequestVo $request
     * @return \Vo\PagingVo
     */
    public function GetKbMailTranPaging(RequestVo $request)
    {
        return $this->GetKbmJson('mail', $this->GetRequest2Array($request), 'GET', true);
    }

    /**
     * 메일 전송 내용 보기
     *
     * @param integer $uid
     * @param RequestVo $request
     * @return mixed
     */
    public function GetKbMailTranView($uid = 0, RequestVo $request)
    {
        return $this->GetKbmJson('mail/' . $uid, null, 'GET', true);
    }

    /**
     * SMS 전송 번호 등록하기
     *
     * @param RequestVo $request
     * @return mixed
     */
    public function GetServiceSmsNoCreate(RequestVo $request)
    {
        return $this->GetKbmJson('sendno', $this->GetRequest2Array($request), 'POST', true);
    }

    /**
     * SMS 전송 번호 등록하기
     *
     * @param RequestVo $request
     * @return \Vo\PagingVo
     */
    public function GetServiceSmsNoPaging(RequestVo $request)
    {
        return $this->GetKbmJson('sendno', $this->GetRequest2Array($request), 'GET', true);
    }

    /**
     * SMS 전송 번호 상세 정보 보기
     *
     * @param string $uid
     * @return mixed
     */
    public function GetServiceSmsNoView($uid = '')
    {
        return $this->GetKbmJson('sendno/' . $uid, null, 'GET', true);
    }

    /**
     * SMS 전송 번호 정보 업데이트 하기
     *
     * @param string $uid
     * @param RequestVo $request
     * @return mixed
     */
    public function GetServiceSmsNoUpdate($uid = '', RequestVo $request)
    {
        return $this->GetKbmJson('sendno/' . $uid, $this->GetRequest2Array($request), 'POST', true);
    }

    /**
     * SMS 전송 번호 삭제하기
     *
     * @param string $uid
     * @return mixed
     */
    public function GetServiceSmsNoDelete($uid = '')
    {
        return $this->GetKbmJson('sendno/' . $uid, null, 'DELETE', true);
    }
    
    /**
     * 서비스 파싱
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
        switch($controller -> controllerType) {
            case 'admin' :
                switch ($cmd) {
                    case 'myinfo.json':
                        switch ($request->GetMethod()) {
                            case 'GET':
                                return $this->GetMyInfoView();
                            case 'POST':
                                return $this->GetMyInfoUpdate($request);
                        }
                        break;
                    case 'member.json':
                        switch ($request->GetMethod()) {
                            case 'GET':
                                return $this->GetServiceUserPaging($request);
                            case 'POST':
                                return $this->GetServiceUserCreate($request);
                        }
                        break;
                    case 'member':
                        $uid = $controller->GetJsonKey($subItemKey);
                        switch ($request->GetMethod()) {
                            case 'GET':
                                return $this->GetServiceUserView($uid);
                            case 'POST':
                                return $this->GetServiceUserUpdate($uid, $request);
                            case 'DELETE':
                                return $this->GetServiceUserDelete($uid);
                        }
                        break;
                    case 'order.json':
                        return $this->GetOrderInfoPaging($request);
                    case 'order':
                        return $this->GetOrderInfoView($controller->GetJsonKey($subItemKey));
                    case 'sendno.json':
                        switch ($request->GetMethod()) {
                            case 'GET':
                                return $this->GetServiceSmsNoPaging($request);
                            case 'POST':
                                return $this->GetServiceSmsNoCreate($request);
                        }
                    case 'sendno':
                        $uid = $controller->GetJsonKey($subItemKey);
                        switch ($request->GetMethod()) {
                            case 'GET':
                                return $this->GetServiceSmsNoView($uid);
                            case 'POST':
                                return $this->GetServiceSmsNoUpdate($uid, $request);
                            case 'DELETE':
                                return $this->GetServiceSmsNoDelete($uid);
                        }
                        break;
                    case 'sms.json':
                        return $this->GetKbTranPaging($request);
                    case 'sms':
                        $uid = $controller->GetJsonKey($subItemKey);
                        return $this->GetKbTranView($uid);
                    case 'kakao.json':
                        return $this->GetKakaoTranPaging($request);
                    case 'kakao':
                        $uid = $controller->GetJsonKey($subItemKey);
                        return $this->GetKakaoTranView($uid);
                    case 'kakao_tpl.json':
                        return $this->GetKakaoTplPaging($request);
                    case 'kakao_tpl':
                        $uid = $controller->GetJsonKey($subItemKey);
                        return $this->GetKakaoTplView($uid);
                    case 'kakao_tpl_comment.json':
                        return $this->GetKakaoTplComment($request);
                    case 'mms.json':
                        return $this->GetKbMmsTranPaging($request);
                    case 'mms':
                        $uid = $controller->GetJsonKey($subItemKey);
                        return $this->GetKbMmsTranView($uid);
                    case 'mail.json':
                        return $this->GetKbMailTranPaging($request);
                    case 'mail':
                        $uid = $controller->GetJsonKey($subItemKey);
                        return $this->GetKbMailTranView($uid);
                }
                break;
            case 'front' :
                break;
        }
        $this->GetException(\KbmException::DATA_ERROR_UNKNOWN);
    }
    
}
