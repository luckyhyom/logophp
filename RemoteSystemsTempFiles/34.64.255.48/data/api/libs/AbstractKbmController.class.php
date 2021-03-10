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
 * KBM �뚢뫂�뱜嚥▲끇�쑎 �꽴占썹뵳占�
 */
abstract class AbstractKbmController extends AbstractController
{

    /**
     * 筌뤴뫀而�占쎌뵬 占쎌젔域뱄옙 占쎈연�겫占�
     *
     * @var boolean
     */
    public $isMobile = false;

    /**
     * �꽴占썹뵳�딆쁽 占쎌젔域뱄옙 占쎈연�겫占�
     *
     * @var boolean
     */
    public $isAdmin = false;

    /**
     * �뚢뫂�뱜嚥▲끇�쑎 占쎈뻬占쎄묶
     * admin : �꽴占썹뵳�딆쁽 �뚢뫂�뱜嚥▲끇�쑎
     * front : 占쎈늄嚥≪쥚�뱜 �뚢뫂�뱜嚥▲끇�쑎
     * html : �뚢뫂�뱜嚥▲끇�쑎
     * erp : ERP �뚢뫂�뱜嚥▲끇�쑎
     *
     * @var string
     */
    public $controllerType = 'front';

    /**
     * 占쎄문占쎄쉐占쎌쁽
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
     * Excel 占쎈솁占쎌뵬 占쎈뼄占쎌뒲嚥≪뮆諭�
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
     * Pdf 占쎈솁占쎌뵬 占쎈뼄占쎌뒲嚥≪뮆諭�
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
     * 占쎈꽅占쎄쿃 占쎄퐣�뜮袁⑸뮞 揶쏉옙占쎌죬占쎌궎疫뀐옙
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
     * �⑤벏�꽰 占쎄퐣�뜮袁⑸뮞 揶쏉옙占쎌죬占쎌궎疫뀐옙
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
     * 占쎈닪占쎈릅筌륅옙 疫꿸퀬占� 占쎄퐣�뜮袁⑸뮞 揶쏉옙占쎌죬占쎌궎疫뀐옙
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
     * 占쎄맒占쎈�� 占쎄퐣�뜮袁⑸뮞 揶쏉옙占쎌죬占쎌궎疫뀐옙
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
     * 雅뚯눖揆 占쎄퐣�뜮袁⑸뮞 揶쏉옙雅뚯쥙�궎疫뀐옙
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
     * 占쎌돳占쎌뜚 占쎄퐣�뜮袁⑸뮞 揶쏉옙占쎌죬占쎌궎疫뀐옙
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
     * �⑤벀�닋占쎈씜筌ｏ옙 �꽴占썹뵳占� 占쎄퐣�뜮袁⑸뮞 揶쏉옙占쎌죬占쎌궎疫뀐옙
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
     * 占쎌젟筌��굞�젟癰귨옙 占쎄퐣�뜮袁⑸뮞 揶쏉옙占쎌죬占쎌궎疫뀐옙
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
     * 野껊슣�뻻占쎈솇 占쎄퐣�뜮袁⑸뮞 揶쏉옙占쎌죬占쎌궎疫뀐옙
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
     * 占쎈꽰�④쑴�젟癰귨옙 占쎈뮞�뜮袁⑸뮞 揶쏉옙占쎌죬占쎌궎疫뀐옙
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
     * 筌롫뗄�뵬 占쎄퐣�뜮袁⑸뮞 揶쏉옙占쎌죬占쎌궎疫뀐옙
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
     * �눧紐꾩쁽占쎄퐣�뜮袁⑸뮞 揶쏉옙占쎌죬占쎌궎疫뀐옙
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
     * 占쎈르�뵳�눘苑뚪뜮袁⑸뮞 揶쏉옙占쎌죬占쎌궎疫뀐옙
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
     * 테스트 코드
     *
     * @return \Service\NoticeServicetest
     */
    protected function GetServiceNoticetest()
    {
        if (defined('NOTICE_SERVICE')) {
            return $this->GetService(NOTICE_SERVICE, $this->loginInfoVo);
        } else {
            return $this->GetService("NoticeServicetest", $this->loginInfoVo);
        }
    }
    
    
    /**
     * 燁삳똻萸낉옙�궎 占쎄퐣�뜮袁⑸뮞 揶쏉옙占쎌죬占쎌궎疫뀐옙
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
     * 占쎈쳳占쎈룴占쎈르�뵳占� 占쎄퐣�뜮袁⑸뮞 揶쏉옙占쎌죬占쎌궎疫뀐옙
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
     * 揶쏉옙占쎌뿯占쎈릭筌욑옙占쎈륫占쏙옙 占쎄틣 占쎄퐣�뜮袁⑸뮞 揶쏉옙占쎌죬占쎌궎疫뀐옙
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
     * 揶쏉옙占쎌뿯占쎈릭筌욑옙占쎈륫占쏙옙 占쎄틣 �뵳�됰윮 占쎄퐣�뜮袁⑸뮞 揶쏉옙占쎌죬占쎌궎疫뀐옙 
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
     * 占쎌젔域뱀눘�쁽 占쎌젟癰귨옙
     *
     * @var \Vo\LoginInfoVo
     */
    public $loginInfoVo = null;

    /**
     * 占쎈꽅占쎄쿃 占쎌넇占쎌뵥
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
     * 占쎈꽅占쎄쿃 占쎄퐣�뜮袁⑸뮞 占쎈뼄占쎈뻬
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
     * 占쎌겱占쎌삺 占쎌젔域뱀눘�쁽占쎌벥 Group 甕곕뜇�깈 揶쏉옙占쎌죬占쎌궎疫뀐옙
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
     * �⑤벊��占쎈쭆 嚥≪뮄�젃占쎌뵥 占쎌젟癰귨옙 揶쏉옙占쎌죬占쎌궎疫뀐옙
     *
     * @param RequestVo $request
     * @return LoginInfoVo|NULL
     */
    public function GetSharedLoginVo(RequestVo $request, AbstractService $service = null)
    {
        return null;
    }

    /**
     * �⑤벏�꽰 占쎄퐣�뜮袁⑸뮞 占쎈뼄占쎈뻬 占쎈릭疫뀐옙
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
     * 疫꿸퀬占� 筌륅옙 占쎄퐣�뜮袁⑸뮞 揶쏉옙占쎌죬占쎌궎疫뀐옙
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