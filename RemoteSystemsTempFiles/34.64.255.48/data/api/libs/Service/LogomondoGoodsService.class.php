<?php

/**
 * Project:     Kbmall & Maholn Project
 * File:        libs/Service/LogomondoGoodsService.class.php
 *
 * @link http://hanbiz.kr/
 * @author Kim Jong-gab <outmind0@naver.com>
 * @version 1.0
 * @since 1.0
 * @copyright 2001-2017 Hanbiz, Inc.
 * @package Kbmall
 */
namespace Service;

use Vo\AddressVo;
use Vo\CodeVo;
use Vo\GoodsInfoVo;
use Vo\LogomondoGoodsMallDataVo;
use Vo\LogomondoOptionDataVo;
use Vo\LogomondoOptionSizeVo;
use Vo\OptionTreeVo;
use Vo\RequestVo;
use Vo\LoginInfoVo;
use Vo\GoodsReviewVo;
use Vo\GoodsInfoDeliveryDetailVo;
use Vo\DeliveryDomesticVo;
use Vo\DeliveryVisitVo;
use Vo\DeliveryOverseasVo;
use Vo\ReturnExchangeVo;
use Vo\GoodsCartVo;

/**
 * �긽�뭹 �꽌鍮꾩뒪
 */
class LogomondoGoodsService extends GoodsService
{

    
    /**
     * 湲곕낯 二쇱냼 
     *
     * @var AddressVo
     */
    private $loadedBaseAddress = null;
    
    /**
     * �긽�뭹 �젙蹂� �뙆�떛�븯湲�
     *
     * @param GoodsInfoVo $vo
     * @param RequestVo $request
     * @param GoodsInfoVo $oldView
     * @return GoodsInfoVo
     */
    public function GetBranchInfoAddress($method = 'N', $addressKey = '', RequestVo $requestVo, $scmNo = 0, $branchId = '')
    {
        switch ($method) {
            case 'N':
                $address = $requestVo->GetFill(new AddressVo(), $addressKey);
                if (!empty($address) && !empty($address ->zonecode)) {
                    return $address;
                }
                if (empty($this -> loadedBaseAddress)) {
                    $this -> loadedBaseAddress = $this->GetServicePolicy()->GetBaseInfoView('ko')->address;
                }
                return $this -> loadedBaseAddress;
            case 'Y':
                if (! empty($scmNo)) {
                    try {
                        $scmInfo = $this->GetServiceScm()->GetScmInfoView($scmNo);
                        if (!empty($scmInfo->scmAddress) && !empty($scmInfo->scmAddress ->zonecode)) {
                            return $scmInfo->scmAddress;
                        }
                    } catch (\Exception $ex) {}
                }
                return $this -> GetBranchInfoAddress('N', $addressKey, $requestVo , $scmNo , $branchId);
            case 'B':
                $oldAddress = $requestVo->GetFill(new AddressVo(), $addressKey);
                if(!empty($branchId)) {
                    $branchAddress =  $this->GetServiceCommon()->GetBranchInfoView($branchId)->branchAddress;
                    if (!empty($branchAddress) && !empty($branchAddress ->zonecode)) {
                        if ($branchAddress -> zonecode != $oldAddress -> zonecode) {
                            return $branchAddress;
                        }
                    }
                }
                if (empty($oldAddress -> zonecode)) {
                    return $this -> GetBranchInfoAddress('Y', $addressKey, $requestVo , $scmNo , $branchId);
                }
                return $oldAddress;
        }
    }

    /**
     * �긽�뭹 �젙蹂� �뙆�떛�븯湲�
     *
     * @param GoodsInfoVo $vo
     * @param RequestVo $request
     * @param GoodsInfoVo $oldView
     * @return GoodsInfoVo
     */
    public function GetGoodsInfoParse(GoodsInfoVo $vo, RequestVo $request, GoodsInfoVo $oldView = null, $isScmCreate = false)
    {
        $vo = parent::GetGoodsInfoPreParse($vo, $request, $oldView);
        $options = Array();
        $mallOptions = Array();
        $mallGoodsAttr = Array();
        if ($request->hasKey('goodsMallData')) {
            $goodsMallData = new LogomondoGoodsMallDataVo();
            $goodsMallDataRequest = $request->GetRequestVo('goodsMallData');
            $goodsMallDataRequest->GetFill($goodsMallData);
            $goodsMallData->sendAddress = $this->GetBranchInfoAddress($goodsMallData->sendAddressSame, 'sendAddress', $goodsMallDataRequest, $vo->sellerMemNo, $goodsMallData->sendBranch);
            
            if(! empty($goodsMallData->visitAddress)) {
                foreach($goodsMallData->visitAddress as $key => $value) {
                    if(substr($value->value, 0, 1) != "Y") {
                        $goodsMallData->visitAddress[$key]->address = $this->GetBranchInfoAddress(substr($goodsMallData->visitAddress[$key]->value, 0, 1), 'visitAddress', $goodsMallDataRequest, $vo->sellerMemNo, $goodsMallData->visitAddress[$key]->branch_id);
                    }
                }
            }
//             $goodsMallData->visitAddress = $this->GetBranchInfoAddress(substr($goodsMallData->visitAddress->value, 0, 1), 'visitAddress', $goodsMallDataRequest, $vo->sellerMemNo, $goodsMallData->visitAddress->branch_id);
            
            $goodsMallData->returnAddress = $this->GetBranchInfoAddress($goodsMallData->returnAddressSame, 'returnAddress', $goodsMallDataRequest, $vo->sellerMemNo, $goodsMallData->returnBranch);
            if ($goodsMallDataRequest->hasKey('goodsStyle')) {
                $goodsMallData->goodsStyle = $goodsMallDataRequest->GetItemArray('goodsStyle');
            }
            if ($goodsMallDataRequest->hasKey('goodsLock')) {
                $goodsMallData->goodsLock = $goodsMallDataRequest->GetItemArray('goodsLock');
            }
            if ($goodsMallDataRequest->hasKey('options')) {
                $mallOptions = $goodsMallDataRequest->GetItemArray('options', new OptionTreeVo());
            }
            $goodsMallData->options = Array();
            $goodsMallData->optionsExt = Array();
            $goodsMallData->optionsText = Array();
            $vo->goodsMallData = $goodsMallData;
            if ($goodsMallData->freeSizeRingYn == 'Y') {
                $mallGoodsAttr[] = "GT_FSRING";
            }
            if ($goodsMallData->readyMadeYn == 'Y') {
                $mallGoodsAttr[] = "GM_Y";
            } else {
                $mallGoodsAttr[] = "GM_N";
            }
            if ($goodsMallData->makerSelf == 'Y') {
                $mallGoodsAttr[] = "GT_SELFMADE";
            }
            if ($goodsMallData->certificationYn == 'Y') {
                $mallGoodsAttr[] = "GT_HASCERT";
                if (! empty($goodsMallData->certificationType)) {
                    if(is_array($goodsMallData->certificationType) == 1) {
                        foreach ($goodsMallData->certificationType as $attr) {
                            $mallGoodsAttr[] = "GSC_" . $attr;
                        }
                    } else {
                        $mallGoodsAttr[] = "GSC_" . $goodsMallData->certificationType;
                    }
                }
            }
            if ($goodsMallData->guaranteeYn == 'Y') {
                $mallGoodsAttr[] = "GT_HASGUAR";
                if (! empty($goodsMallData->guaranteeTerm)) {
                    $mallGoodsAttr[] = "GUART_" . $goodsMallData->guaranteeTerm;
                }
            }
            foreach ($goodsMallData->deliveryMethod as $deliveryMethod) {
                $mallGoodsAttr[] = "DELIV_" . $deliveryMethod;
            }

            switch ($goodsMallData->goodsType) {
                case "G":
                    $mallGoodsAttr[] = "JT_GEMSTONE";
                    break;
                case "R":
                    $mallGoodsAttr[] = "JT_RING";
                    break;
                case "E":
                    $mallGoodsAttr[] = "JT_EARRING";
                    break;
                case "N":
                    $mallGoodsAttr[] = "JT_NECKLACE";
                    break;
                case "B":
                    $mallGoodsAttr[] = "JT_BRACELET";
                    break;
            }
            switch ($goodsMallData->goodsType) {
                case "R":
                case "E":
                case "N":
                case "B":
                    break;
                default:
                    $mallGoodsAttr[] = "JT_OTHERS";
                    break;
            }
            if (! empty($goodsMallData->goodsGender)) {
                foreach ($goodsMallData->goodsGender as $goodsGender) {
                    switch ($goodsGender) {
                        case "F":
                            $mallGoodsAttr[] = "GG_F";
                            break;
                        case "M":
                            $mallGoodsAttr[] = "GG_M";
                            break;
                    }
                }
                if (in_array("F", $goodsMallData->goodsGender) && in_array("M", $goodsMallData->goodsGender)) {
                    $mallGoodsAttr[] = "GG_U";
                }
            }
            if ($goodsMallData->goodsType != 'G') {
                foreach ($goodsMallData->goodsStyle as $goodsStyle) {
                    $mallGoodsAttr[] = "GT_" . $goodsStyle;
                }
                foreach ($goodsMallData->subMatter as $subMatter) {
                    $mallGoodsAttr[] = "GT_" . $subMatter;
                }
                foreach ($goodsMallData->settingStyle as $settingStyle) {
                    $mallGoodsAttr[] = "GT_" . $settingStyle;
                }
                switch ($goodsMallData->goodsType) {
                    case "G":
                    case "R":
                        break;
                    case "E":
                    case "N":
                    case "B":
                        foreach ($goodsMallData->goodsLock as $goodsLock) {
                            $mallGoodsAttr[] = "GT" . $goodsMallData->goodsType . "_" . $goodsLock;
                        }
                        break;
                }

                foreach ($goodsMallData->metalColor as $metalColor) {
                    $mallGoodsAttr[] = 'MC_' . $metalColor; // [] = 만 하면 초기화가 아니라 배열이 더해지는건가?
                    // 1 += 1;
                    // [0][0] MC_GOLD [0][1] MC_PT
                }
                foreach ($goodsMallData->metalType as $metalType) {
                    $mallGoodsAttr[] = 'MT_' . $metalType; 
                }
                foreach ($goodsMallData->metalPurity as $metalPurity) {
                    $mallGoodsAttr[] = 'TEST_' . $metalPurity;
                }
                foreach ($goodsMallData->stoneColor as $stoneColor) {
                    $mallGoodsAttr[] = 'SC_' . $stoneColor;
                }
                foreach ($goodsMallData->stoneType as $stoneType) {
                    $mallGoodsAttr[] = 'ST_' . $stoneType;
                }
                foreach ($goodsMallData->stoneShape as $stoneShape) {
                    $mallGoodsAttr[] = 'SS_' . $stoneShape;
                }
                if ($goodsMallData->useModel3dYn == 'Y') {
                    $mallGoodsAttr[] = "GT_MODEL3D";
                }
                if (! empty($goodsMallData->metalPlating)) {
                    $mallGoodsAttr[] = "PLAT_" . $goodsMallData->metalPlating;
                }
            } else {
                if (! empty($goodsMallData->stoneColorOne)) {
                    $mallGoodsAttr[] = 'SC_' . $goodsMallData->stoneColorOne;
                }
                if (! empty($goodsMallData->stoneTypeOne)) {
                    $mallGoodsAttr[] = 'ST_' . $goodsMallData->stoneTypeOne;
                }
                if (! empty($goodsMallData->stoneShapeOne)) {
                    $mallGoodsAttr[] = 'SS_' . $goodsMallData->stoneShapeOne;
                }
                if (! empty($goodsMallData->stoneSizeOne)) {
                    $mallGoodsAttr[] = 'SS_' . $goodsMallData->stoneSizeOne;
                }
            }
        }
        // 여기서 받아서 넣어줘야한다.
        if ($request->hasKey('optionData')) {
            $optionData = new LogomondoOptionDataVo();
            $optionDataRequest = $request->GetRequestVo('optionData');
            $optionDataRequest->GetFill($optionData);
           
            
            
            if ($optionDataRequest->hasKey('sizeInfo')) {// 이 코드에서 sizeinfo value에 나온 14k를 MT_부분에 저장
                $sizeInfoRequest = $optionDataRequest->GetRequestVo('sizeInfo');
                $sizeInfo = new LogomondoOptionSizeVo();
                $sizeInfoRequest->GetFill($sizeInfo);
                if ($sizeInfoRequest->hasKey("sizeInfo")) { // sizeinfo 안의 sizeinfo (sizeinfo[0].value 이런식으로 가져와야함)
                    $sizeInfo->sizeInfo = $sizeInfoRequest->GetItemArray("sizeInfo", new CodeVo());
//                     echo "console.log(\"{$sizeInfo}\");";
//                     echo "console.log(\"야호\");";
//                     $mallGoodsAttr[] = 'MT_' . $sizeInfo->sizeInfo[0]->value;
                    for ($i = 0; $i < count($sizeInfo->sizeInfo); $i++) {
                        $mallGoodsAttr[] = 'MT_' . $sizeInfo->sizeInfo[$i]->value;
                    }
//                     // 효민
//                     if($sizeInfo->sizeInfo[0]){
// //                         $mallGoodsAttr[] = 'MT_' . $sizeInfo->sizeInfo[0][0].value;
//                         echo "console.log(\"{$mallGoodsAttr}\");";
//                         echo "console.log(\"{$sizeInfo}\");";
//                      }
                }
                if ($sizeInfoRequest->hasKey("sizeOptions")) { // sizeinfo 안의 sizeoptions
                    $sizeInfo->sizeOptions = $sizeInfoRequest->GetItemArray("sizeOptions", new CodeVo());
                }
                $optionData->sizeInfo = $sizeInfo; // sizeinfo1 (sizeinfo1이라 칭하겠음, 안에 있는 것은 sizeinfo2.. sizeinfo1을 2로 바꿔준건가.
            }
            
            
            
            if ($optionDataRequest->hasKey('metal1Name')) {
                $optionData->metal1Name = $this->GetOptionTitleVo("metal1Name", $optionDataRequest);
            }
            if ($optionDataRequest->hasKey('metal2Name')) {
                $optionData->metal2Name = $this->GetOptionTitleVo("metal2Name", $optionDataRequest);
            }
            if ($optionDataRequest->hasKey('metal3Name')) {
                $optionData->metal3Name = $this->GetOptionTitleVo("metal3Name", $optionDataRequest);
            }
            if ($optionDataRequest->hasKey('stone1Name')) {
                $optionData->stone1Name = $this->GetOptionTitleVo("stone1Name", $optionDataRequest);
            }
            if ($optionDataRequest->hasKey('stone2Name')) {
                $optionData->stone2Name = $this->GetOptionTitleVo("stone2Name", $optionDataRequest);
            }
            if ($optionDataRequest->hasKey('stone3Name')) {
                $optionData->stone3Name = $this->GetOptionTitleVo("stone3Name", $optionDataRequest);
            }
            if ($optionDataRequest->hasKey('metalMake1')) {
                $optionData->metalMake1 = $optionDataRequest->GetItemArray('metalMake1', new CodeVo());
                // 효민
                $mallGoodsAttr[] = 'MT_' . substr($optionData->metalMake1[0]->value,0,6);
            }
            if ($optionDataRequest->hasKey('metalMake2')) {
                $optionData->metalMake2 = $optionDataRequest->GetItemArray('metalMake2', new CodeVo());
            }
            if ($optionDataRequest->hasKey('metalMake1')) {
                $optionData->metalMake3 = $optionDataRequest->GetItemArray('metalMake3', new CodeVo());
            }
            if ($optionDataRequest->hasKey('stoneMake1')) {
                $optionData->stoneMake1 = $optionDataRequest->GetItemArray('stoneMake1', new CodeVo());
            }
            if ($optionDataRequest->hasKey('stoneMake2')) {
                $optionData->stoneMake2 = $optionDataRequest->GetItemArray('stoneMake2', new CodeVo());
            }
            if ($optionDataRequest->hasKey('stoneMake1')) {
                $optionData->stoneMake3 = $optionDataRequest->GetItemArray('stoneMake3', new CodeVo());
            }
            if ($optionDataRequest->hasKey('options')) {
                $optionData->options = $optionDataRequest->GetItemArray('options', new OptionTreeVo());
                $options = array_merge($options, $optionData->options);
            }
            if ($optionDataRequest->hasKey('optionsExt')) {
                $optionData->optionsExt = $optionDataRequest->GetItemArray('optionsExt', new OptionTreeVo());
            }
            if ($optionDataRequest->hasKey('optionsText')) {
                $optionData->optionsText = $optionDataRequest->GetItemArray('optionsText', new OptionTreeVo());
            }

            $optionData->carvingDataList = $optionDataRequest->GetItemArray("carvingDataList", new CodeVo());
            if ($optionData->metal1 != "Y") {
                $optionData->metalColor1 = Array();
                $optionData->metalMake1 = Array();
            }
            if ($optionData->metal2 != "Y") {
                $optionData->metalColor2 = Array();
                $optionData->metalMake2 = Array();
            } else if ($optionData->metalColor2Same == 'Y') {
                $optionData->metalColor2 = $optionData->metalColor1;
                $optionData->metalMake2 = $optionData->metalMake1;
            }
            if ($optionData->metal3 != "Y") {
                $optionData->metalColor3 = Array();
                $optionData->metalMake3 = Array();
            } else if ($optionData->metalColor3Same == 'Y') {
                $optionData->metalColor3 = $optionData->metalColor1;
                $optionData->metalMake3 = $optionData->metalMake1;
            }
            if ($optionData->stone1 != "Y") {
                $optionData->stoneColor1 = Array();
                $optionData->stoneMake1 = Array();
            }
            if ($optionData->stone2 != "Y") {
                $optionData->stoneColor2 = Array();
                $optionData->stoneMake2 = Array();
            } else if ($optionData->stoneColor2Same == 'Y') {
                $optionData->stoneColor2 = $optionData->stoneColor1;
                $optionData->stoneMake2 = $optionData->stoneMake1;
            }
            if ($optionData->stone3 != "Y") {
                $optionData->stoneColor3 = Array();
                $optionData->stoneMake3 = Array();
            } else if ($optionData->stoneColor3Same == 'Y') {
                $optionData->stoneColor3 = $optionData->stoneColor1;
                $optionData->stoneMake3 = $optionData->stoneMake1; 
            }
            $vo->optionData = $optionData; // vo가 상품 전체고, 거기서 optionData = 위에서 만든 optionData.
            if ($vo->optionDisplayFl == 'M') {
               $vo -> options = $optionData->options;
            }
            if (! empty($optionData->stoneShape) && ! empty($goodsMallData)) {
                $goodsMallData->stoneShape = $optionData->stoneShape;
            }
            if ($optionData->carvingYn == 'Y' && ! empty($optionData->carvingDataList)) {
                $mallGoodsAttr[] = "GT_INCARV";
            }
            if ($optionData->speedUpMake == 'Y') { //빠른 주문 제작인듯..
                $mallGoodsAttr[] = "GT_SPEEDUP";
            }
            foreach ($optionData->stoneShape as $stoneShape) {
                $mallGoodsAttr[] = 'SS_' . $stoneShape;
            }
        }
        
        if($request->hasKey('deliveryDetail')) {
            $deliveryDetail = new GoodsInfoDeliveryDetailVo();
            $deliveryDetailRequest = $request->GetRequestVo('deliveryDetail');
            $deliveryDetailRequest->GetFill($deliveryDetail);
            
            if($deliveryDetailRequest->hasKey('domestic')) {
                $deliveryDetail->domestic = $deliveryDetailRequest->GetFill(new DeliveryDomesticVo(), 'domestic');
            }
            if($deliveryDetailRequest->hasKey('visit')) {
                $deliveryDetail->visit = $deliveryDetailRequest->GetFill(new DeliveryVisitVo(), 'visit');
            }
            if($deliveryDetailRequest->hasKey('overseas')) {
                $overseas = new DeliveryOverseasVo();
                $overseasRequest = $deliveryDetailRequest->GetRequestVo('overseas');
                $overseasRequest->GetFill($overseas);
                
                if($overseasRequest->hasKey('deliveryOptionList')) {
                    $overseas->deliveryOptionList = $overseasRequest->GetItemArray('deliveryOptionList', new CodeVo());                    
                }
                
                $deliveryDetail->overseas = $overseas;
            }
            if($deliveryDetailRequest->hasKey('domesticReturnExchange')) {
                $deliveryDetail->domesticReturnExchange = $deliveryDetailRequest->GetFill(new ReturnExchangeVo(), 'domesticReturnExchange');
            }
            if($deliveryDetailRequest->hasKey('overseasReturnExchange')) {
                $deliveryDetail->overseasReturnExchange = $deliveryDetailRequest->GetFill(new ReturnExchangeVo(), 'overseasReturnExchange');
            }
            
            $vo->deliveryDetail = $deliveryDetail;
        }
        
        $vo->options = array_merge($options, $mallOptions);
        if (count($vo->options) > 0) {
            $vo->optionFl = "Y";
        }
        if (!empty($vo -> goodsState)) {
            $mallGoodsAttr[] = 'GST_' . $vo -> goodsState;
        }
        $vo -> goodsMallSearchOption = Array();
        foreach ($mallGoodsAttr as $goodsAttr) {
            $goodsAttr = strtoupper($goodsAttr);
            if (! empty($goodsAttr) && ! in_array($goodsAttr, $vo->goodsMallSearchOption)) {
                $vo -> goodsMallSearchOption[] = $goodsAttr; // goodsAttr을 여기서 생성해서 넣어줌.
            }
        }
        
        if (DEBUG_MODE) {
            // $vo -> shortDescription = substr(implode(" ", $vo->goodsAttr), 0, 200);
            // $vo -> shortDescriptionLocale = null;
        }
        $vo = parent::GetGoodsInfoAfterParse($vo, $request, $oldView, $isScmCreate);
        return $vo; //마지막에 상품 전체 정보 업데이트한걸 리턴.
    }
    
    /**
     * �긽�뭹 �젙蹂� 媛��졇�삤湲�
     *
     * @param GoodsInfoVo $goodsInfo
     * @param LoginInfoVo $loginInfoVo
     * @param boolean $isCopy
     * @param string $displayType
     * @return GoodsInfoVo
     */
    public function GetCategoryGoodsList(GoodsInfoVo $goodsInfo, LoginInfoVo $loginInfoVo = null, $displayType = '', $scmNo = 0)
    {
        if (!empty($goodsInfo -> sellerMemNo)) {
            $scmNo = $goodsInfo -> sellerMemNo;
        }
        return parent::GetCategoryGoodsList($goodsInfo, $loginInfoVo, $displayType, $scmNo);
    }
    
    /**
     * �긽�뭹 由щ럭 �뙆�떛�븯湲�
     *
     * @param GoodsReviewVo $vo
     * @param RequestVo $request
     * @param GoodsReviewVo $oldView
     * @return GoodsReviewVo
     */
    public function GetGoodsReviewParse(GoodsReviewVo $vo = null, RequestVo $request = null, GoodsReviewVo $oldView = null, LoginInfoVo $loginInfo = null)
    {
        if ($vo != null) {
            if ($request->hasKey('attachImage')) {
                $vo->attachImage = $this->GetUploadFiles($request->attachImage, ! empty($oldView) ? $oldView->attachImage : '', 'review');
            }
            if (! empty($vo->title) && ! empty($vo->contents)) {
                $vo->isReview = 'Y';
                if (! empty($vo->orderOption)) {
                    $vo->isOrdered = 'Y';
                } else {
                    $vo->isOrdered = 'N';
                }
            } else {
                $vo->isReview = 'N';
                $vo->isOrdered = 'N';
            }
            if(! empty($vo->replyContents) && (! empty($vo->replyMemNo)) && (! empty($vo->replyScmNo))) {
                $vo->isReply = 'Y';
                $vo->replyContents = $this->GetSafeHtmlContents($vo->replyContetns, true);
            } else {
                $vo->isReply = 'N';
            }
            $vo->contents = $this->GetSafeHtmlContents($vo->contents, true);
        } else if ($oldView != null) {
            $oldView->attachImage = $this->GetUploadFiles('', $oldView->attachImage, 'review');
        }
        if (! empty($loginInfo)) {
            $this->GetMyArticle($loginInfo, $vo);
        } else {
            $this->GetMyArticle($this->loginInfo, $vo);
        }
        return $vo;
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
        switch ($controller->controllerType) {
            case 'front':
                $displayType = $controller->isMobile ? 'app' : 'web';
                switch ($cmd) {
                    case 'reviewReply.json':
                        return $this->GetGoodsReviewUpdate($controller->loginInfoVo, $uid, $request);
                }
                break;
        }
        
        return parent::GetServiceParse($controller, $cmd, $request, $subItemKey, $extraKey);
    }
}

?>