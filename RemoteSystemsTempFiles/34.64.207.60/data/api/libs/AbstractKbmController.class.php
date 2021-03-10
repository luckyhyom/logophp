<?php

/**
 * Project:     Kbmall & Maholn Project
 * File:        libs/AbstractKbmController.class.php
 *
 * @link http://hanbiz.kr/
 * @author Kim Jong-gab <outmind0@naver.com>
 * @version 1.0
 * @since 1.0
 * @copyright 2001-2017 Hanbiz, Inc.
 * @package Kbmall
 */
use Service\GarbageCollectionService;
use Vo\ExcelVo;
use Vo\PdfVo;
use Vo\RequestVo;
use Vo\LoginInfoVo;
use Service\AbstractService;

/**
 * KBM 컨트롤러 관리
 */
abstract class AbstractKbmController extends AbstractController
{

    /**
     * 모바일 접근 여부
     *
     * @var boolean
     */
    public $isMobile = false;

    /**
     * 관리자 접근 여부
     *
     * @var boolean
     */
    public $isAdmin = false;

    /**
     * 컨트롤러 행태
     * admin : 관리자 컨트롤러
     * front : 프론트 컨트롤러
     * html : 컨트롤러
     * erp : ERP 컨트롤러
     *
     * @var string
     */
    public $controllerType = 'front';

    /**
     * 생성자
     *
     * @param string $mallSiteId
     * @param boolean $isMobile
     * @param boolean $isAdmin
     * @param string $controllerType
     */
    public function __construct($mallSiteId = '', $isMobile = false, $isAdmin = false, $controllerType = '')
    {
        parent::__construct($mallSiteId);
        $this->controllerType = $controllerType;
        $this->isMobile = $isMobile;
        $this->isAdmin = $isAdmin;
        if (empty($this->mallId)) {
            exit();
        } else {
            // $runGc = rand(0, GC_DIVISOR) <= GC_PROBABILITY;
            $runGc = false;
            if ($runGc) {
                $gcService = new GarbageCollectionService($this->mallId);
                $gcService->GetRun('access');
            }
        }
    }

    /**
     * Excel 파일 다운로드
     *
     * @param string $downloadName
     * @param ExcelVo $params
     * @return stdClass
     */
    public function GetExcelDown($downloadName, ExcelVo $params)
    {
        if (! empty($this->loginInfoVo)) {
            $params->locale = $this->loginInfoVo->memLocale;
        }
        return parent::GetExcelDown($downloadName, $params);
    }

    /**
     * Pdf 파일 다운로드
     *
     * @param string $downloadName
     * @param PdfVo $params
     * @return stdClass
     */
    public function GetPdfDown($downloadName, PdfVo $params)
    {
        $policyService = $this->GetServicePolicy();
        if (! empty($this->loginInfoVo)) {
            $params->locale = $this->loginInfoVo->memLocale;
        } else {
            $params->locale = 'ko';
        }
        $params->addedParam = Array(
            'baseInfo' => $policyService->GetBaseInfoView($params->locale)
        );
        return parent::GetPdfDown($downloadName, $params);
    }

    /**
     * 토큰 서비스 가져오기
     *
     * @return \Service\TokenService
     */
    protected function GetServiceToken()
    {
        if (defined('TOKEN_SERVICE')) {
            return $this->GetService(TOKEN_SERVICE, $this->loginInfoVo);
        } else {
            return $this->GetService("TokenService", $this->loginInfoVo);
        }
    }

    /**
     * 공통 서비스 가져오기
     *
     * @return \Service\CommonService
     */
    public function GetServiceCommon()
    {
        if (defined('COMMON_SERVICE')) {
            return $this->GetService(COMMON_SERVICE, $this->loginInfoVo);
        } else {
            return $this->GetService("CommonService", $this->loginInfoVo);
        }
    }
    
    /**
     * 쇼핑몰 기타 서비스 가져오기
     *
     * @return \Service\MallService
     */
    public function GetServiceMall()
    {
        if (defined('MALL_SERVICE')) {
            return $this->GetService(MALL_SERVICE, $this->loginInfoVo);
        } else {
            return $this->GetService("MallService", $this->loginInfoVo);
        }
    }
    
    

    /**
     * 상품 서비스 가져오기
     *
     * @return \Service\GoodsService
     */
    public function GetServiceGoods()
    {
        if (defined('GOODS_SERVICE')) {
            return $this->GetService(GOODS_SERVICE, $this->loginInfoVo);
        } else {
            return $this->GetService("GoodsService", $this->loginInfoVo);
        }
    }

    /**
     * 주문 서비스 가죠오기
     *
     * @return \Service\OrderService
     */
    protected function GetServiceOrder()
    {
        if (defined('ORDER_SERVICE')) {
            return $this->GetService(ORDER_SERVICE, $this->loginInfoVo);
        } else {
            return $this->GetService("OrderService", $this->loginInfoVo);
        }
    }

    /**
     * 회원 서비스 가져오기
     *
     * @return \Service\MemberService
     */
    protected function GetServiceMember()
    {
        if (defined('MEMBER_SERVICE')) {
            return $this->GetService(MEMBER_SERVICE, $this->loginInfoVo);
        } else {
            return $this->GetService("MemberService", $this->loginInfoVo);
        }
    }

    /**
     * 공급업체 관리 서비스 가져오기
     *
     * @return \Service\ScmService
     */
    protected function GetServiceScm()
    {
        if (defined('SCM_SERVICE')) {
            return $this->GetService(SCM_SERVICE, $this->loginInfoVo);
        } else {
            return $this->GetService("ScmService", $this->loginInfoVo);
        }
    }

    /**
     * 정책정보 서비스 가져오기
     *
     * @return \Service\PolicyService
     */
    public function GetServicePolicy()
    {
        if (defined('POLICY_SERVICE')) {
            return $this->GetService(POLICY_SERVICE, $this->loginInfoVo);
        } else {
            return $this->GetService("PolicyService", $this->loginInfoVo);
        }
    }

    /**
     * 게시판 서비스 가져오기
     *
     * @param string $boardId
     * @return \Service\BoardService
     */
    protected function GetServiceBoard($boardId = '')
    {
        if (defined('BOARD_SERVICE')) {
            return $this->GetService(BOARD_SERVICE, $boardId, $this->loginInfoVo);
        } else {
            return $this->GetService("BoardService", $boardId, $this->loginInfoVo);
        }
    }

    /**
     * 통계정보 스비스 가져오기
     *
     * @return \Service\StatisticsService
     */
    protected function GetServiceStatistics()
    {
        if (defined('STATISTICS_SERVICE')) {
            return $this->GetService(STATISTICS_SERVICE, $this->loginInfoVo);
        } else {
            return $this->GetService("StatisticsService", $this->loginInfoVo);
        }
    }

    /**
     * 메일 서비스 가져오기
     *
     * @return \Service\MailService
     */
    protected function GetServiceMail()
    {
        if (defined('MAIL_SERVICE')) {
            return $this->GetService(MAIL_SERVICE, $this->loginInfoVo);
        } else {
            return $this->GetService("MailService", $this->loginInfoVo);
        }
    }

    /**
     * 문자서비스 가져오기
     *
     * @return \Service\SmsService
     */
    protected function GetServiceSms()
    {
        if (defined('SMS_SERVICE')) {
            return $this->GetService(SMS_SERVICE, $this->loginInfoVo);
        } else {
            return $this->GetService("SmsService", $this->loginInfoVo);
        }
    }

    /**
     * 알림서비스 가져오기
     *
     * @return \Service\NoticeService
     */
    protected function GetServiceNotice()
    {
        if (defined('NOTICE_SERVICE')) {
            return $this->GetService(NOTICE_SERVICE, $this->loginInfoVo);
        } else {
            return $this->GetService("NoticeService", $this->loginInfoVo);
        }
    }

    /**
     * 카카오 서비스 가져오기
     *
     * @return \Service\KakaoService
     */
    protected function GetServiceKakao()
    {
        if (defined('KAKAO_SERVICE')) {
            return $this->GetService(KAKAO_SERVICE, $this->loginInfoVo);
        } else {
            return $this->GetService("KakaoService", $this->loginInfoVo);
        }
    }

    /**
     * 푸쉬알림 서비스 가져오기
     *
     * @return \Service\PushNoticeService
     */
    protected function GetServicePushNotice()
    {
        if (defined('PUSH_NOTICE_SERVICE')) {
            return $this->GetService(PUSH_NOTICE_SERVICE, $this->loginInfoVo);
        } else {
            return $this->GetService("PushNoticeService", $this->loginInfoVo);
        }
    }
    
    /**
     * 가입하지않은 샵 서비스 가져오기
     * 
     * @return \Service\NonMemShopService
     */
    protected function GetServiceNonMemShop()
    {
        if (defined('NON_MEM_SHOP_SERVICE')) {
            return $this->GetService(NON_MEM_SHOP_SERVICE, $this->loginInfoVo);
        } else {
            return $this->GetService("NonMemShopService", $this->loginInfoVo);
        }
    }
    
    /**
     * 가입하지않은 샵 리뷰 서비스 가져오기 
     * 
     * @return \Service\NonMemShopReviewService
     */
    protected function GetServiceNonMemShopReview()
    {
        if (defined('NON_MEM_SHOP_REVIEW_SERVICE')) {
            return $this->GetService(NON_MEM_SHOP_REVIEW_SERVICE, $this->loginInfoVo);
        } else {
            return $this->GetService("NonMemShopReviewService", $this->loginInfoVo);
        }
    }

    /**
     * 접근자 정보
     *
     * @var \Vo\LoginInfoVo
     */
    public $loginInfoVo = null;

    /**
     * 토큰 확인
     *
     * @param RequestVo $request
     * @param string $actionType
     * @param boolean $isAdmin
     * @throws Exception
     */
    public function CheckToken(RequestVo $request, $actionType = '', $isAdmin = false)
    {
        if (! $request->HasToken()) {
            if ($isAdmin) {
                switch ($actionType) {
                    case 'common':
                    case 'token':
                    case 'token.json':
                        break;
                    default:
                        throw new Exception('', KbmException::DATA_ERROR_INVALID_LOGIN);
                }
            } else {
                switch ($actionType) {
                    case 'erpapi':
                        if (DEV_MODE && DEV_ERP_MODE) {
                            $tokenService = $this->GetServiceToken();
                            $this->loginInfoVo = $tokenService->GetTokenErpApi($request->GetToken());
                            if (empty($this->loginInfoVo)) {
                                throw new Exception('', KbmException::DATA_ERROR_TOKENINVALID);
                            }
                        } else {
                            throw new Exception('', KbmException::DATA_ERROR_INVALID_LOGIN);
                        }
                        break;
                    case 'mallservice' :
                    case 'common':
                    case 'order':
                    case 'token':
                    case 'token.json':
                        break;
                    default:
                        throw new Exception('', KbmException::DATA_ERROR_INVALID_LOGIN);
                }
            }
        } else {
            $tokenService = $this->GetServiceToken();
            switch ($actionType) {
                case 'erpapi':
                    $this->loginInfoVo = $tokenService->GetTokenErpApi($request->GetToken());
                    if (empty($this->loginInfoVo)) {
                        throw new Exception('', KbmException::DATA_ERROR_TOKENINVALID);
                    }
                    break;
                default:
                    $oldToken = $request->GetToken();
                    $this->loginInfoVo = $tokenService->GetTokenRefresh($oldToken, $isAdmin, $this->isMobile);
                    if (empty($this->loginInfoVo)) {
                        switch ($actionType) {
                            case 'common':
                                $request->SetToken("");
                                break;
                            case 'token':
                            case 'token.json':
                                $tokenService->GetTokenRefresh($request->GetToken(), $isAdmin, $this->isMobile, true);
                                break;
                            default:
                                throw new Exception('', KbmException::DATA_ERROR_TOKENINVALID);
                        }
                    }
                    if (!empty($this->loginInfoVo) && $this->loginInfoVo->token != $oldToken) {
                        $request->SetToken($this->loginInfoVo->token);
                    }
                    break;
            }
            if ($isAdmin) {
                switch ($actionType) {
                    case 'common':
                    case 'token':
                    case 'token.json':
                        break;
                    default:
                        if (empty($this->loginInfoVo->memNo)) {
                            throw new Exception('', KbmException::DATA_ERROR_INVALID_LOGIN);
                        }
                        switch ($this->loginInfoVo->memLevel) {
                            case 'A':
                            case 'S':
                            case 'K':
                            case 'P' :
                                break;
                            default:
                                throw new Exception('', KbmException::DATA_ERROR_INVALID_LOGIN);
                        }
                        break;
                }
            }
            if ($request->isSecure()) {
                $data = $this->cryptoJsAesDecrypt($request->ct, $request->iv, $request->s, $this->loginInfoVo->passphrase);
                if (empty($data)) {
                    throw new Exception('', KbmException::DATA_ERROR_TOKENINVALID);
                }
                $request->SetData($data);
            }
        }
    }

    /**
     * 토큰 서비스 실행
     *
     * @param string $cmd
     * @param RequestVo $request
     * @param boolean $isAdmin
     * @return \Vo\LoginInfoVo|\Vo\MessageVo
     */
    public function GetTokenService($cmd = '', RequestVo $request, $isAdmin = false)
    {
        $tokenService = $this->GetServiceToken();
        switch ($cmd) {
            case 'gcmid.json':
                return $tokenService->SetGcmId($request->GetToken(), $request->gcmId, $request->usePush == 'N' ? false : true, $request);
            case 'myinfo.json':
                return $tokenService->GetToken($request->GetToken());
            case 'refresh.json':
                return $tokenService->GetTokenRefresh($request->GetToken(), $isAdmin, $this->isMobile, true);
            case 'login.json':
                return $tokenService->GetLogin($request->GetToken(), $request, $isAdmin, $this->isMobile);
            case 'logout.json':
                return $tokenService->GetLogout($request->GetToken());
            case 'login_secure.json':
                return $tokenService->GetLoginSecure($request->GetToken(), $request, $isAdmin, $this->isMobile);
            case 'authcode.json':
                $authData = $tokenService->GetAuthToken($request);
                if (! empty($authData->code)) {
                    $authData->code = $this->cryptoJsAesEncrypt($authData->code, $this->loginInfoVo->passphrase);
                }
                return $authData;
                break;
            case '':
                return $tokenService->GetTokenRefresh($request->GetToken(), $isAdmin, $this->isMobile);
        }
    }

    /**
     * 현재 접근자의 Group 번호 가져오기
     *
     * @return string
     */
    public function GetGroupSno()
    {
        if (! empty($this->loginInfoVo)) {
            return $this->loginInfoVo->groupSno;
        }
        return '';
    }

    /**
     * 공유된 로그인 정보 가져오기
     *
     * @param RequestVo $request
     * @return LoginInfoVo|NULL
     */
    public function GetSharedLoginVo(RequestVo $request, AbstractService $service = null)
    {
        return null;
    }

    /**
     * 공통 서비스 실행 하기
     *
     * @param string $cmd
     * @param RequestVo $request
     * @param string $subItemKey
     * @throws Exception
     * @return mixed
     */
    protected function GetCommonService($cmd = '', RequestVo $request, $subItemKey = '', $extraKey = '')
    {
        return $this->GetServiceCommon()->GetServiceParse($this, $cmd, $request, $subItemKey, $extraKey);
    }
    
    /**
     * 기타 몰 서비스 가져오기
     *
     * @param string $cmd
     * @param RequestVo $request
     * @param string $subItemKey
     * @return \Vo\PagingVo
     */
    protected function GetMallService($servicePart = '', RequestVo $request = null, $cmd = '', $subItemKey = '', $extraKey = '')
    {
        return $this->GetServiceMall()->GetServiceParse($this, $servicePart, $request, $cmd, $subItemKey, $extraKey);
    }
    
    protected function GetNonMemShopService($servicePart = '', RequestVo $request = null, $cmd = '', $subItemKey = '', $extraKey = '')
    {
        return $this->GetServiceNonMemShop()->GetServiceParse($this, $servicePart, $request, $cmd, $subItemKey, $extraKey);
    }
    
    protected function GetNonMemShopReviewService($servicePart = '', RequestVo $request = null, $cmd = '', $subItemKey = '', $extraKey = '')
    {
        return $this->GetServiceNonMemShopReview()->GetServiceParse($this, $servicePart, $request, $cmd, $subItemKey, $extraKey);
    }
    
}

?>