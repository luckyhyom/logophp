<?php

/**
 * Project:     Kbmall & Maholn Project
 * File:        libs/Service/GoodsService.class.php
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
use Vo\BrandVo;
use Vo\CategoryVo;
use Vo\CodeVo;
use Vo\DashBoardStatusDataVo;
use Vo\DashBoardStatusVo;
use Vo\DeliveryPackingPriceVo;
use Vo\DisplayMainItemVo;
use Vo\DisplayThemeVo;
use Vo\ExcelVo;
use Vo\ExternalVideoVo;
use Vo\FileLogSearchVo;
use Vo\FileLogVo;
use Vo\FileVo;
use Vo\GoodsCartDeliveryInfoVo;
use Vo\GoodsCartOptionItemVo;
use Vo\GoodsCartOptionVo;
use Vo\GoodsCartOrderInfoVo;
use Vo\GoodsCartPayInfoVo;
use Vo\GoodsCartPriceVo;
use Vo\GoodsCartReceiptInfoVo;
use Vo\GoodsCartSearchVo;
use Vo\GoodsCartVo;
use Vo\GoodsCategoryVo;
use Vo\GoodsDiscountInfoVo;
use Vo\GoodsDiscountPriceVo;
use Vo\GoodsFavContentsVo;
use Vo\GoodsFavMustInfoVo;
use Vo\GoodsFavOptionsVo;
use Vo\GoodsInfoAuctionBidderVo;
use Vo\GoodsInfoAuctionSimpleVo;
use Vo\GoodsInfoAuctionVo;
use Vo\GoodsInfoLogVo;
use Vo\GoodsInfoSimpleVo;
use Vo\GoodsInfoVo;
use Vo\GoodsQnaSearchVo;
use Vo\GoodsQnaVo;
use Vo\GoodsRecentSearchVo;
use Vo\GoodsReviewSearchVo;
use Vo\GoodsReviewVo;
use Vo\GoodsSearchVo;
use Vo\LocaleTextVo;
use Vo\LoginInfoVo;
use Vo\MemberCouponVo;
use Vo\MemberGroupVo;
use Vo\MemberVo;
use Vo\NaverShopCategoryVo;
use Vo\OptionTreeVo;
use Vo\PagingVo;
use Vo\PdfVo;
use Vo\PriceRangeVo;
use Vo\RefShopVo;
use Vo\RequestVo;
use Vo\SearchVo;
use Vo\SeoTagVo;
use Vo\TitleContentVo;
use Vo\TreeSearchVo;
use Vo\TreeVo;
use Exception;
use KbmException;
use stdClass;

/**
 * 상품 서비스
 */
class GoodsService extends AbstractService
{

    /**
     * 추가 상품 서비스 가져오기
     *
     * @return GoodsMallService
     */
    public function GetServiceGoodsMall()
    {
        if (defined('MALL_TYPE')) {
            switch (MALL_TYPE) {
                case 'hhosting':
                    return self::GetService('GoodsMallHhostingService', $this->mallId);
                    break;
            }
        }
        return null;
    }

    /**
     * 상품 추가 서비스 실행하기
     *
     * @param GoodsInfoVo $vo
     * @param string $synkType
     */
    public function GetGoodsMallSynk(GoodsInfoVo $vo, $synkType = '')
    {
        $goodsMallService = $this->GetServiceGoodsMall();
        if (! empty($goodsMallService)) {
            switch ($synkType) {
                case 'create':
                    return $goodsMallService->GetMergeCreate($vo);
                case 'delete':
                    return $goodsMallService->GetMergeDelete($vo);
                case 'update':
                    return $goodsMallService->GetMergeUpdate($vo);
                case 'view':
                    return $goodsMallService->GetMergeView($vo);
            }
        }
        return $vo;
    }

    /**
     * 상품 관리 DAO 가져오기
     *
     * @return \Dao\GoodsInfoDao
     */
    public function GetGoodsInfoDao()
    {
        return parent::GetDao('GoodsInfoDao');
    }

    /**
     * 상품 정보 VO 가져오기
     *
     * @param string $uid
     * @param RequestVo $request
     * @param mixed $vo
     * @return GoodsInfoVo
     */
    public function GetGoodsInfoVo($uid = '', RequestVo $request = null, $vo = null)
    {
        $vo = parent::GetFill($request, empty($vo) ? 'GoodsInfoVo' : $vo);
        $vo->goodsCode = $uid;
        return $vo;
    }

    /**
     * 상품 정보 캐쉬 삭제하고 관련 정보 로그 남기기
     *
     * @param GoodsInfoVo $oldVo
     * @param GoodsInfoVo $newVo
     */
    private function UnsetGoodsInfoCache(GoodsInfoVo $oldVo, GoodsInfoVo $newVo, $isScmCreate = false)
    {
        if ($oldVo !== $newVo) {
            $log = $this->getAccessLog($oldVo, $newVo, 'goodsInfo.');
            $this->setAccessLog('GoodsInfo.' . $oldVo->goodsCode, $log);
            $this->SetFileLogUpdate($newVo->goodsImageMaster, $oldVo->goodsImageMaster, 'goods', $newVo->goodsCode, 'goodsImageMaster');
            $this->SetFileLogUpdate($newVo->goodsImageAdded, $oldVo->goodsImageAdded, 'goods', $newVo->goodsCode, 'goodsImageAdded');
            $this->SetEditorFileLogUpdate($newVo->goodsDescription, $oldVo->goodsDescription, 'goods', $newVo->goodsCode, 'goodsDescription');
            $this->SetEditorFileLogUpdate($newVo->goodsDescriptionMobile, $oldVo->goodsDescriptionMobile, 'goods', $newVo->goodsCode, 'goodsDescriptionMobile');
            $this->SetEditorFileLogUpdate($newVo->detailInfoDelivery, $oldVo->detailInfoDelivery, 'goods', $newVo->goodsCode, 'detailInfoDelivery');
            $this->SetEditorFileLogUpdate($newVo->detailInfoAs, $oldVo->detailInfoAs, 'goods', $newVo->goodsCode, 'detailInfoAs');
            $this->SetEditorFileLogUpdate($newVo->detailInfoRefund, $oldVo->detailInfoRefund, 'goods', $newVo->goodsCode, 'detailInfoRefund');
            $this->SetEditorFileLogUpdate($newVo->detailInfoExchange, $oldVo->detailInfoExchange, 'goods', $newVo->goodsCode, 'detailInfoExchange');
            $this->SetLocaleTextFileLogUpdate($newVo->goodsDescriptionLocale, $this->GetChildValue($oldVo, 'goodsDescriptionLocale'), 'goods', $newVo->goodsCode, 'goodsDescriptionLocale');
            $this->SetLocaleTextFileLogUpdate($newVo->goodsDescriptionMobileLocale, $this->GetChildValue($oldVo, 'goodsDescriptionMobileLocale'), 'goods', $newVo->goodsCode, 'goodsDescriptionMobileLocale');
            $this->UnsetGoodsInfoOptionCache($newVo->goodsCode, 'options', $newVo->options, $oldVo->options);
            $this->UnsetGoodsInfoOptionCache($newVo->goodsCode, 'optionsExt', $newVo->optionsExt, $oldVo->optionsExt);
            $this->UnsetGoodsInfoOptionCache($newVo->goodsCode, 'optionsText', $newVo->optionsText, $oldVo->optionsText);
            $this->UnsetGoodsInfoOptionCache($newVo->goodsCode, 'optionsRef', $newVo->optionsRef, $oldVo->optionsRef);
            if (! $isScmCreate && ! empty($newVo) && ! empty($newVo->sellerMemNo)) {
                $scmGoodsConfigVo = $this->GetServicePolicy()->GetScmGoodsConfigView();
                switch ($scmGoodsConfigVo->adminApplyFl) {
                    case 'D':
                        $this->GetServiceScm()->GetGoodsInfoDeleteReal($newVo->goodsCode);
                        break;
                    case 'U':
                        $changeFields = new \stdClass();
                        foreach ($newVo as $key => $value) {
                            if (empty($oldVo) || ! isset($oldVo->$key) || $oldVo->$key != $value) {
                                $changeFields->$key = $value;
                            }
                        }
                        $updateRequest = new RequestVo($changeFields, true);
                        $this->GetServiceScm()->GetGoodsInfoUpdateReal($newVo->goodsCode, $updateRequest);
                        break;
                }
            }
        }
        $this->ReloadGoodsCategory($newVo);
        $sellerMemNoList = Array();
        if ($oldVo->sellerMemNo != $newVo->sellerMemNo) {
            if (! empty($oldVo->sellerMemNo)) {
                $sellerMemNoList[] = $oldVo->sellerMemNo;
            }
            if (! empty($newVo->sellerMemNo)) {
                $sellerMemNoList[] = $newVo->sellerMemNo;
            }
        } else if (! empty($newVo->sellerMemNo)) {
            $sellerMemNoList[] = $newVo->sellerMemNo;
        }
        if (! empty($sellerMemNoList)) {
            $scmService = $this->GetServiceScm();
            foreach ($sellerMemNoList as $sellerMemNo) {
                $scmService->UnsetCodeDataList($sellerMemNo);
            }
        }
        if (! empty($newVo)) {
            parent::UnSetCacheFile(parent::GetServiceCacheKey('scm-goodsInfo', $newVo->goodsCode, '*'));
            parent::UnSetCacheFile(parent::GetServiceCacheKey('goodsInfo', $newVo->goodsCode, '*'));
        } else {
            parent::UnSetCacheFile(parent::GetServiceCacheKey('scm-goodsInfo', '*'));
            parent::UnSetCacheFile(parent::GetServiceCacheKey('goodsInfo', '*'));
        }
    }

    /**
     * 상품 카테고리 정보 리로드 하기
     *
     * @param GoodsInfoVo $newVo
     */
    public function ReloadGoodsCategory(GoodsInfoVo $newVo)
    {
        $goodsCategoryList = $newVo->goodsCategory;
        $categoryList = Array();
        $goodsCode = $newVo->goodsCode;
        if (! empty($goodsCategoryList)) {
            $categoryTreeList = $this->GetCategoryTreeVoList();
            foreach ($goodsCategoryList as $cateId) {
                $cateVo = isset($categoryTreeList[$cateId]) ? $categoryTreeList[$cateId] : null;
                $isMaster = true;
                while (! empty($cateVo)) {
                    $id = $cateVo->id;
                    if (isset($categoryList['C' . $id])) {
                        break;
                    }
                    $gCateVo = new GoodsCategoryVo();
                    $gCateVo->mallId = $this->mallId;
                    $gCateVo->cateType = 'C';
                    if ($isMaster) {
                        $gCateVo->isMaster = 'Y';
                        $isMaster = false;
                    } else {
                        $gCateVo->isMaster = 'N';
                    }
                    $gCateVo->goodsCode = $goodsCode;
                    $gCateVo->goodsCategory = $id;
                    $categoryList['C' . $id] = $gCateVo;
                    $cateVo = $cateVo->parentTreeVo;
                }
            }
        }
        $brandCd = $newVo->brandCd;
        if (! empty($brandCd)) {
            $brandTreeList = $this->GetBrandTreeVoList();
            $cateVo = isset($brandTreeList[$brandCd]) ? $brandTreeList[$brandCd] : null;
            $isMaster = true;
            while (! empty($cateVo)) {
                $id = $cateVo->id;
                if (isset($categoryList['B' . $id])) {
                    break;
                }
                $gCateVo = new GoodsCategoryVo();
                $gCateVo->mallId = $this->mallId;
                $gCateVo->cateType = 'B';
                if ($isMaster) {
                    $gCateVo->isMaster = 'Y';
                    $isMaster = false;
                } else {
                    $gCateVo->isMaster = 'N';
                }
                $gCateVo->goodsCode = $goodsCode;
                $gCateVo->goodsCategory = $id;
                $categoryList['B' . $id] = $gCateVo;
                $cateVo = $cateVo->parentTreeVo;
            }
        }
        if (! empty($newVo->goodsSearchOption)) {
            foreach ($newVo->goodsSearchOption as $searchOpt) {
                switch ($searchOpt) {
                    case 'GOPT_RECOM':
                    case 'GOPT_NEW':
                    case 'GOPT_HOT':
                    case 'GOPT_SALE':
                    case 'GOPT_EVENT':
                    case 'GOPT_BEST':
                        $id = $searchOpt;
                        if (isset($categoryList['O' . $id])) {
                            break;
                        }
                        $gCateVo = new GoodsCategoryVo();
                        $gCateVo->mallId = $this->mallId;
                        $gCateVo->cateType = 'O';
                        $gCateVo->isMaster = 'Y';
                        $gCateVo->goodsCode = $goodsCode;
                        $gCateVo->goodsCategory = $id;
                        $categoryList['O' . $id] = $gCateVo;
                        break;
                }
            }
        }
        $goodsIconColorList = Array();
        if (! empty($newVo->goodsColor)) {
            $goodsIconColorList['H'] = $newVo->goodsColor;
        }
        if (! empty($newVo->goodsIconFix)) {
            $goodsIconColorList['F'] = $newVo->goodsIconFix;
        }
        if (! empty($newVo->goodsIconTime)) {
            $goodsIconColorList['T'] = $newVo->goodsIconTime;
        }
        if (! empty($goodsIconColorList)) {
            $colorIconTreeList = $this->GetColorIconTreeVoList();
            foreach ($goodsIconColorList as $iconType => $goodsIconColorSet) {
                foreach ($goodsIconColorSet as $cateId) {
                    $cateVo = isset($colorIconTreeList[$cateId]) ? $colorIconTreeList[$cateId] : null;
                    if (! empty($cateVo)) {
                        $id = $cateVo->id;
                        if (isset($categoryList['IC' . $id])) {
                            break;
                        }
                        $gCateVo = new GoodsCategoryVo();
                        $gCateVo->mallId = $this->mallId;
                        $gCateVo->cateType = $iconType;
                        $gCateVo->isMaster = 'N';
                        $gCateVo->goodsCode = $goodsCode;
                        $gCateVo->goodsCategory = $id;
                        $categoryList['IC' . $id] = $gCateVo;
                    }
                }
            }
        }
        $newVo->categoryVoList = [];
        foreach ($categoryList as $cateVo) {
            $newVo->categoryVoList[] = $cateVo;
        }
        $goodsInfoDao = $this->GetGoodsInfoDao();
        $oldCategoryList = $goodsInfoDao->GetGoodsCategoryList($newVo, 1000, 0);
        $oldCategoryMap = Array();
        foreach ($oldCategoryList as $item) {
            $cateId = $item->goodsCategory;
            $oldCategoryMap[$cateId] = $item;
        }
        $goodsInfoDao->SetCategoryDelete($newVo);
        foreach ($newVo->categoryVoList as $item) {
            $cateId = $item->goodsCategory;
            if (isset($oldCategoryMap[$cateId])) {
                $oldCate = $oldCategoryMap[$cateId];
                $item->goodsCategoryOrd = $oldCate->goodsCategoryOrd;
                $item->goodsCategoryFix = $oldCate->goodsCategoryFix;
            }
            if (empty($item->goodsCategoryOrd)) {
                $oldCate = $goodsInfoDao->GetCategoryMax($item);
                if (! empty($oldCate)) {
                    $item->goodsCategoryOrd = $oldCate->goodsCategoryOrd;
                    $item->goodsCategoryFix = $oldCate->goodsCategoryFix;
                }
            }
        }
        if (count($newVo->categoryVoList) > 0) {
            $goodsInfoDao->SetCategoryCreate($newVo);
        }
        $goodsInfoDao->SetSearchOptionDelete($newVo);
        $categoryList = Array();
        if (count($newVo->categoryVoList) > 0) {
            foreach ($newVo->categoryVoList as $categoryVo) {
                $cateId = $categoryVo->goodsCategory;
                $categoryList[$cateId] = $categoryVo;
            }
        }
        if (! empty($newVo->goodsAttr)) {
            foreach ($newVo->goodsAttr as $cateId) {
                if (! isset($categoryList[$cateId])) {
                    $vo = new GoodsCategoryVo();
                    $vo->goodsCategory = $cateId;
                    $categoryList[$cateId] = $vo;
                }
            }
        }
        $newVo->categoryVoList = Array();
        foreach ($categoryList as $categoryVo) {
            $newVo->categoryVoList[] = $categoryVo;
        }
        if (count($newVo->categoryVoList) > 0) {
            $goodsInfoDao->SetSearchOptionCreate($newVo);
        }
        $newVo->categoryVoList = Array();
    }

    /**
     * 상품 카테고리 정보 전체 리로드 하기
     */
    public function ReloadGoodsCategoryAll()
    {
        $vo = new GoodsSearchVo();
        $vo->mallId = $this->mallId;
        $result = $this->GetGoodsInfoDao()->GetListSimple($vo, 100000, 0);
        foreach ($result as $item) {
            $this->ReloadGoodsCategory($item);
        }
    }

    /**
     * 상품 코드로 관련 캐시 정보 리로드 하기
     *
     * @param string $goodsCode
     * @param string $optionPart
     * @param OptionTreeVo[] $voList
     * @param OptionTreeVo[] $oldVoList
     */
    public function UnsetGoodsInfoOptionCache($goodsCode = '', $optionPart = '', $voList = Array(), $oldVoList = Array())
    {
        $voMapList = Array();
        $oldVoMapList = Array();
        if (! empty($voList) && is_array($voList)) {
            foreach ($voList as $vo) {
                if (! empty($vo->id) && ! empty($vo->optionImage) && $this->IsUploadImage($vo->optionImage)) {
                    $voMapList[] = $vo->optionImage;
                }
            }
        }
        if (! empty($oldVoList) && is_array($oldVoList)) {
            foreach ($oldVoList as $vo) {
                if (! empty($vo->id) && ! empty($vo->optionImage) && $this->IsUploadImage($vo->optionImage)) {
                    $oldVoMapList[] = $vo->optionImage;
                }
            }
        }
        if (! empty($voMapList) || ! empty($oldVoMapList)) {
            $this->SetFileLogUpdate(implode('@!@', $voMapList), implode('@!@', $oldVoMapList), 'goods', $goodsCode, $optionPart);
        }
    }

    /**
     * 상품 검색 VO 가져오기
     *
     * @param RequestVo $request
     * @param LoginInfoVo $loginInfo
     * @param string $displayType
     * @return GoodsSearchVo
     */
    public function GetGoodsSearchVo(RequestVo $request = null, LoginInfoVo $loginInfo = null, $displayType = '')
    {
        $vo = new GoodsSearchVo();
        parent::GetSearchVo($request, $vo);
        $vo->displayType = $displayType;
        $vo->goodsPricesRange = [];
        if (! empty($vo->scmNos)) {
            $scmNos = Array();
            foreach ($vo->scmNos as $scmNo) {
                if (! empty($scmNo)) {
                    $scmNos[] = intval($scmNo);
                }
            }
            $vo->scmNos = $scmNos;
        }
        if (! empty($vo->query)) {
            if ($vo->so == 'COUPON') {
                $vo->couponNo = $vo->query;
                $vo->query = '';
            } else {
                $queryLikeList = explode(' ', str_replace('%', ' ', $vo->query));
                $vo->queryLikeList = Array();
                foreach ($queryLikeList as $query) {
                    $query = trim($query);
                    if (! empty($query) && ! in_array($query, $vo->queryLikeList) && count($vo->queryLikeList) < 5) {
                        $vo->queryLikeList[] = $query;
                    }
                }
            }
        }

        if (empty($loginInfo) && empty($vo->scmNo)) {
            $vo->scmNo = $this->GetUserAdminScmNo();
        }
        if (!empty($loginInfo)) {
            $vo->token = $loginInfo->token;
        }
        
        
        if (! empty($vo->queryAndList)) {
            foreach ($vo->queryAndList as $seqn => $queryList) {
                if (! empty($queryList)) {
                    switch ($seqn) {
                        case 0:
                            $vo->queryList0 = $queryList;
                            break;
                        case 1:
                            $vo->queryList1 = $queryList;
                            break;
                        case 2:
                            $vo->queryList2 = $queryList;
                            break;
                        case 3:
                            $vo->queryList3 = $queryList;
                            break;
                        case 4:
                            $vo->queryList4 = $queryList;
                            break;
                        case 5:
                            $vo->queryList5 = $queryList;
                            break;
                        case 6:
                            $vo->queryList6 = $queryList;
                            break;
                        case 7:
                            $vo->queryList7 = $queryList;
                            break;
                        case 8:
                            $vo->queryList8 = $queryList;
                            break;
                        case 9:
                            $vo->queryList9 = $queryList;
                            break;
                        case 10:
                            $vo->queryList10 = $queryList;
                            break;
                        case 11:
                            $vo->queryList11 = $queryList;
                            break;
                        case 12:
                            $vo->queryList12 = $queryList;
                            break;
                        case 13:
                            $vo->queryList13 = $queryList;
                            break;
                        case 14:
                            $vo->queryList14 = $queryList;
                            break;
                        case 15:
                            $vo->queryList15 = $queryList;
                            break;
                    }
                }
            }
        }

        if (! empty($vo->goodsPrices)) {
            foreach ($vo->goodsPrices as $priceRange) {
                list ($fromPrice, $toPrice) = explode(':', $priceRange . ':0');
                $fromPrice = doubleval($fromPrice);
                $toPrice = doubleval($toPrice);
                if (! empty($fromPrice) || ! empty($toPrice)) {
                    $priceRangeVo = new PriceRangeVo();
                    if ($fromPrice > $toPrice) {
                        $toPrice = 90000000;
                    }
                    $priceRangeVo->from = $fromPrice;
                    $priceRangeVo->to = $toPrice;
                    $vo->goodsPricesRange[] = $priceRangeVo;
                }
            }
        }
        return $vo;
    }

    /**
     * 상품 정보 목록으로 가져오기
     *
     * @param RequestVo $request
     * @param LoginInfoVo $loginInfoVo
     * @param string $displayType
     * @param string $isHidden
     * @return \Vo\PagingVo
     */
    public function GetGoodsInfoPaging(RequestVo $request, LoginInfoVo $loginInfoVo = null, $displayType = '', $isHidden = 'N')
    {
        $vo = $this->GetGoodsSearchVo($request, $loginInfoVo, $displayType);
        $vo->isHiddenFl = $isHidden;
        $result = $this->GetGoodsInfoDao()->GetPaging($vo, $request->GetPerPage(10), $request->GetOffset());
        if (count($result->items) > 0 && $request->GetOffset() == 0) {
            $this->GetGoodsInfoKeywordCheck($vo->query, $loginInfoVo, $vo);
        }
        if (! empty($displayType)) {
            $simpleList = Array();
            foreach ($result->items as $item) {
                $simpleList[] = $this->GetGoodsInfoAsSimpleView($item);
            }
            $this->GetGoodsPriceScmCheck($loginInfoVo, $simpleList, $displayType);
            $result->items = $simpleList;
        } else {
            $this->GetGoodsPriceScmCheck($loginInfoVo, $result->items);
        }
        return $result;
    }

    /**
     * 상품 정보 목록으로 가져오기
     *
     * @param RequestVo $request
     * @param LoginInfoVo $loginInfoVo
     * @param string $displayType
     * @param string $isHidden
     * @return GoodsInfoVo[]
     */
    public function GetGoodsInfoList(RequestVo $request, LoginInfoVo $loginInfoVo = null, $displayType = '', $isHidden = 'N')
    {
        $vo = $this->GetGoodsSearchVo($request, $loginInfoVo, $displayType);
        $vo->isHiddenFl = $isHidden;
        $result = $this->GetGoodsInfoDao()->GetList($vo, $request->GetPerPage(10), $request->GetOffset());
        if (count($result) > 0 && $request->GetOffset() == 0) {
            $this->GetGoodsInfoKeywordCheck($vo->query, $loginInfoVo, $vo);
        }
        if (! empty($displayType)) {
            $simpleList = Array();
            foreach ($result as $item) {
                $simpleList[] = $this->GetGoodsInfoAsSimpleView($item);
            }
            $this->GetGoodsPriceScmCheck($loginInfoVo, $simpleList, $displayType);
            return $simpleList;
        } else {
            $this->GetGoodsPriceScmCheck($loginInfoVo, $result);
        }
        return $result;
    }

    /**
     * 상품 정보 목록으로 가져오기
     *
     * @param RequestVo $request
     * @param LoginInfoVo $loginInfoVo
     * @param string $displayType
     * @param string $isHidden
     * @return TreeVo[]
     */
    public function GetGoodsInfoSearchOptionList(RequestVo $request, LoginInfoVo $loginInfoVo = null, $displayType = '', $isHidden = 'N')
    {
        $vo = $this->GetGoodsSearchVo($request, $loginInfoVo, $displayType);
        $vo->isHiddenFl = $isHidden;
        $result = $this->GetGoodsInfoDao()->GetSearchOptionList($vo, 1000, 0);
        foreach ($result as $item) {
            unset($item->mallId);
            unset($item->value);
            unset($item->valueLocale);
            unset($item->keyword);
            unset($item->ord);
            unset($item->parentId);
            unset($item->parentTreeVo);
            unset($item->refGoodsList);
            unset($item->stockCnt);
            unset($item->refVo);
        }
        return $result;
    }

    /**
     * 회원 그룹 정책 정보 가져오기
     *
     * @param string $groupSno
     * @return MemberGroupVo
     */
    public function GetMemberGroupPolicy($groupSno = '')
    {
        if (! empty($groupSno)) {
            $policyService = $this->GetServicePolicy();
            try {
                return $policyService->GetMemberGroupView($groupSno);
            } catch (Exception $ex) {}
        }
        return null;
    }

    /**
     * 접근 회원에 맞는 회원 가격정보 및 Scm 정보 가져오기
     *
     * @param LoginInfoVo $loginInfoVo
     * @param GoodsInfoVo[] $items
     * @param string $displayType
     * @return GoodsInfoVo[]
     */
    public function GetGoodsPriceScmCheck(LoginInfoVo $loginInfoVo = null, $items = Array(), $displayType = '')
    {
        if (! empty($items)) {
            $groupSno = (! empty($loginInfoVo)) ? $loginInfoVo->groupSno : '';
            $locale = (! empty($loginInfoVo)) ? $loginInfoVo->memLocale : 'ko';
            $this->GetGoodsPriceScmCheckGroupCode($items, $displayType, $groupSno, $locale);
        }
        return $items;
    }

    /**
     * 회원 그룹 코드로 상품 가격정보 및 Scm 정보 가져오기
     *
     * @param GoodsInfoVo[] $items
     * @param string $displayType
     * @param string $groupSno
     * @return GoodsInfoVo[]
     */
    public function GetGoodsPriceScmCheckGroupCode($items = Array(), $displayType = '', $groupSno = '', $locale = 'ko')
    {
        if (! empty($items)) {
            $this->GetGoodsPriceScmCheckGroupVo($items, $displayType, $this->GetMemberGroupPolicy($groupSno), $locale);
        }
        return $items;
    }

    /**
     * 배열로 상품 가격 정보 조정하기
     *
     * @param GoodsInfoVo[] $items
     * @param string $displayType
     * @param MemberGroupVo $groupVo
     * @return GoodsInfoVo[]
     */
    public function GetGoodsPriceScmCheckGroupVo($items = Array(), $displayType = '', MemberGroupVo $groupVo = null, $locale = 'ko')
    {
        if (! empty($items)) {
            foreach ($items as $item) {
                $this->GetGoodsPriceScmCheckOne($item, $displayType, $groupVo, $locale);
            }
        }
        return $items;
    }

    /**
     * 옵션 가격 조정하기
     *
     * @param OptionTreeVo[] $optionsTree
     * @param float $discountOptionRate
     * @return OptionTreeVo[]
     */
    public function GetGoodsPriceOption($optionsTree = Array(), $discountOptionRate = 0.0, MemberGroupVo $groupVo = null, $optionSalePrice = 0, $locale = 'ko')
    {
        if (! empty($optionsTree)) {
            foreach ($optionsTree as $option) {
                $option->optionScmPrice = $option->optionPrice;
                $optionPrice = $option->optionPrice;
                switch ($option->optionPriceFl) {
                    case 'Y': // 회원 그룹,
                        $option->optionPrice = $optionPrice - $optionPrice * $discountOptionRate / 100;
                        break;
                    case 'N': // 판매가 고정
                        break;
                    case 'G': // 상품 회원 그룹
                        if (! empty($groupVo) && ! empty($option->optionPriceGroup)) {
                            $optionPriceGroup = $option->optionPriceGroup;
                            $groupCode = $groupVo->groupCode;
                            if (isset($optionPriceGroup->$groupCode)) {
                                $option->optionPrice = $optionPrice + $optionPriceGroup->$groupCode;
                            } else if (isset($optionPriceGroup->groupOthers)) {
                                $option->optionPrice = $optionPrice + $optionPriceGroup->groupOthers;
                            }
                        }
                        break;
                }
                $option->optionDealPrice = $option->optionPrice;
                $option->optionSalePrice = $optionSalePrice + $option->optionDealPrice;
                if (! empty($option->optionChildren)) {
                    $this->GetGoodsPriceOption($option->optionChildren, $discountOptionRate, $groupVo, $option->optionSalePrice, $locale);
                }
                $optionPrice = $this->GetPriceByUnit($optionPrice, 'goods', $locale);
                $option->optionPrice = $this->GetPriceByUnit($option->optionPrice, 'goods', $locale);
                $option->optionDealPrice = $this->GetPriceByUnit($option->optionDealPrice, 'goods', $locale);

                if ($optionPrice > $option->optionPrice) {
                    $option->optionDealSavePrice = $optionPrice - $option->optionPrice;
                } else {
                    $option->optionDealSavePrice = 0;
                }
                if ($option->optionDealSavePrice > 0 && $optionPrice != 0) {
                    $option->optionDealSaveRate = round($option->optionDealSavePrice / $optionPrice * 100);
                } else {
                    $option->optionDealSaveRate = 0;
                }
            }
        }
        return $optionsTree;
    }

    /**
     * 상품 목록에서 특정 그룹에 맞는 가격으로 설정해서 가져오기
     *
     * @param GoodsInfoSimpleVo[] $item
     * @param string $displayType
     * @param MemberGroupVo $groupVo
     * @return GoodsInfoVo[]
     */
    public function GetGoodsPriceScmCheckOne($item, $displayType = '', MemberGroupVo $groupVo = null, $locale = 'ko')
    {
        $discountRate = 0;
        $discountOptionRate = 0;
        $goodsPrice = $item->goodsPrice;
        switch ($item->goodsPriceFl) {
            case 'N': // 판매가 고정
                break;
            case 'G': // 상품 회원 그룹
                if (! empty($groupVo) && ! empty($item->goodsPriceGroup)) {
                    $goodsPriceGroup = $item->goodsPriceGroup;
                    $groupCode = $groupVo->groupCode;
                    if (isset($goodsPriceGroup->$groupCode)) {
                        $item->goodsPrice = max(0, $goodsPrice + $goodsPriceGroup->$groupCode);
                    } else if (isset($goodsPriceGroup->groupOthers)) {
                        $item->goodsPrice = max(0, $goodsPrice + $goodsPriceGroup->groupOthers);
                    }
                }
                break;
            case 'Y':
            default:
                if (! empty($groupVo)) {
                    $item->goodsScmPrice = $goodsPrice;
                    switch ($groupVo->fixedOrderTypeDc) {
                        case 'G':
                            if ($groupVo->dcLine <= $goodsPrice) {
                                $discountRate += floatval($groupVo->dcPercent);
                                $discountOptionRate += floatval($groupVo->dcPercent);
                            }
                            break;
                        case 'O':
                            if ($groupVo->dcLine <= $goodsPrice) {
                                $discountOptionRate += floatval($groupVo->dcPercent);
                            }
                            break;
                    }
                    switch ($groupVo->fixedOrderTypeOverlapDc) {
                        case 'G':
                            if ($groupVo->overlapDcLine <= $goodsPrice) {
                                $discountRate += floatval($groupVo->overlapDcPercent);
                                $discountOptionRate += floatval($groupVo->overlapDcPercent);
                            }
                            break;
                        case 'O':
                            if ($groupVo->dcLine <= $goodsPrice) {
                                $discountOptionRate += floatval($groupVo->overlapDcPercent);
                            }
                            break;
                    }
                }
                break;
        }
        if (! empty($discountRate)) {
            $discountRate = max(min($discountRate, 100), 0);
            if (! empty($discountRate) && $discountRate > 0) {
                $item->goodsPrice = $goodsPrice - $goodsPrice * $discountRate / 100;
            }
        }
        if (! empty($discountOptionRate)) {
            $discountOptionRate = max(min($discountOptionRate, 100), 0);
            if (isset($item->optionsTree) && ! empty($item->optionsTree)) {
                $this->GetGoodsPriceOption($item->optionsTree, $discountOptionRate, $groupVo, $item->goodsPrice, $locale);
            }
            if (isset($item->optionsTextTree) && ! empty($item->optionsTextTree)) {
                $this->GetGoodsPriceOption($item->optionsTextTree, $discountOptionRate, $groupVo, 0, $locale);
            }
            if (isset($item->optionsExtTree) && ! empty($item->optionsExtTree)) {
                $this->GetGoodsPriceOption($item->optionsExtTree, $discountOptionRate, $groupVo, 0, $locale);
            }
            if (isset($item->optionsRefTree) && ! empty($item->optionsRefTree)) {
                $this->GetGoodsPriceOption($item->optionsRefTree, $discountOptionRate, $groupVo, 0, $locale);
            }
        } else {
            if (isset($item->optionsTree) && ! empty($item->optionsTree)) {
                $this->GetGoodsPriceOption($item->optionsTree, 0, $groupVo, $item->goodsPrice, $locale);
            }
            if (isset($item->optionsTextTree) && ! empty($item->optionsTextTree)) {
                $this->GetGoodsPriceOption($item->optionsTextTree, 0, $groupVo, 0, $locale);
            }
            if (isset($item->optionsExtTree) && ! empty($item->optionsExtTree)) {
                $this->GetGoodsPriceOption($item->optionsExtTree, 0, $groupVo, 0, $locale);
            }
            if (isset($item->optionsRefTree) && ! empty($item->optionsRefTree)) {
                $this->GetGoodsPriceOption($item->optionsRefTree, 0, $groupVo, 0, $locale);
            }
        }

        if (! empty($groupVo)) {
            switch ($groupVo->fixedOrderTypeMileage) {
                case 'O':
                case 'G':
                    $goodsPrice = $item->goodsPrice;
                    if ($groupVo->mileageLine <= $goodsPrice && $groupVo->mileagePercent > 0) {
                        switch ($item->mileageGoodsUnit) {
                            case 'W':
                                $item->mileageGoods = min($goodsPrice, max(0, $item->mileageGoods + $goodsPrice * $groupVo->mileagePercent / 100));
                                $item->mileageGoodsUnit = 'W';
                                break;
                            case 'P':
                            default:
                                $item->mileageGoods = min(100, max(0, $item->mileageGoods + $groupVo->mileagePercent));
                                $item->mileageGoodsUnit = 'P';
                                break;
                        }
                    }
                    break;
            }
        }
        if ($item->fixedPrice <= $item->goodsPrice) {
            $item->fixedPrice = 0;
        }
        $item->goodsPrice = $this->GetPriceByUnit($item->goodsPrice, 'goods', $locale);
        $item->goodsDealPrice = $item->goodsPrice;
        if ($item->fixedPrice > 0 && $item->fixedPrice > $item->goodsPrice) {
            $item->goodsDealSavePrice = $item->fixedPrice - $item->goodsPrice;
        } else {
            $item->goodsDealSavePrice = 0;
        }
        $item->goodsDealSavePrice = $this->GetPriceByUnit($item->goodsDealSavePrice, 'goods', $locale);

        if ($item->goodsDealSavePrice > 0 && $item->fixedPrice > 0) {
            $item->goodsDealSaveRate = round($item->goodsDealSavePrice / $item->fixedPrice * 100);
        } else {
            $item->goodsDealSaveRate = 0;
        }
        switch ($item->mileageGoodsUnit) {
            case 'W':
                $item->mileageGoods = $this->GetPriceByUnit($item->mileageGoods, 'mileage', $locale);
                $item->mileageGoodsPerOne = min($item->goodsDealPrice, max(0, $item->mileageGoods));
                break;
            case 'P':
            default:
                $item->mileageGoodsPerOne = min($item->goodsDealPrice, max(0, round($item->goodsDealPrice * $item->mileageGoods / 100)));
                break;
        }
        $item->mileageGoodsPerOne = $this->GetPriceByUnit($item->mileageGoodsPerOne, 'mileage', $locale);
        $item->goodsIcon = [];
        if (! empty($item->goodsIconFix)) {
            foreach ($item->goodsIconFix as $icon) {
                $item->goodsIcon[] = $icon;
            }
        }
        if (! empty($item->goodsIconTime)) {
            $nowTime = time();
            $goodsIconOpenDate = ($item->goodsIconOpenDateTime == 0 || $item->goodsIconOpenDateTime < $nowTime);
            $goodsIconCloseDate = ($item->goodsIconCloseDateTime == 0 || ($item->goodsIconCloseDateTime + 60 * 60 * 24) >= $nowTime);
            if ($goodsIconOpenDate && $goodsIconCloseDate) {
                foreach ($item->goodsIconTime as $icon) {
                    $item->goodsIcon[] = $icon;
                }
            }
        }
        $item->goodsHasStockCode = '';
        $item->goodsHasStock = true;
        if ($item->soldOutFl != 'Y') {
            if ($item->stockFl == 'Y') {
                if ($item->stockCnt <= 0) {
                    $item->goodsHasStock = false;
                    $item->goodsHasStockCode = 'MSG_GSTOCK_ERROR_OUTSOTCK';
                }
            }
        } else {
            $item->goodsHasStockCode = 'MSG_GSTOCK_ERROR_OUTSOTCK';
            $item->goodsHasStock = false;
        }
        if ($item->goodsHasStock) {
            if ($displayType == 'app') {
                if ($item->goodsSellMobileFl == 'N' || $item->goodsDisplayMobileFl == 'N') {
                    $item->goodsHasStock = false;
                    $item->goodsHasStockCode = 'MSG_GSTOCK_ERROR_PLATFORM';
                }
            }
            if ($displayType == '' || $displayType == 'web') {
                if ($item->goodsSellFl == 'N' || $item->goodsDisplayFl == 'N') {
                    $item->goodsHasStock = false;
                    $item->goodsHasStockCode = 'MSG_GSTOCK_ERROR_PLATFORM';
                }
            }
        }
        if ($item->goodsHasStock) {
            $nowTime = time();
            $salesOpenDateTime = $this->isDateEmpty($item->salesOpenDate) ? 0 : strtotime($item->salesOpenDate);
            $salesCloseDateTime = $this->isDateEmpty($item->salesCloseDate) ? 0 : (strtotime($item->salesCloseDate) + 60 * 60 * 24);
            $goodsOpenDateTime = $this->isDateEmpty($item->goodsOpenDt) ? 0 : strtotime($item->goodsOpenDt);
            $goodsCloseDateTime = $this->isDateEmpty($item->goodsCloseDt) ? 0 : (strtotime($item->goodsCloseDt) + 60 * 60 * 24);
            if (! (($salesOpenDateTime == 0 || $salesOpenDateTime < $nowTime) && ($salesCloseDateTime == 0 || $salesCloseDateTime >= $nowTime) && ($goodsOpenDateTime == 0 || $goodsOpenDateTime < $nowTime) && ($goodsCloseDateTime == 0 || $goodsCloseDateTime >= $nowTime))) {
                $item->goodsHasStockCode = 'MSG_GSTOCK_ERROR_OUTDATE';
                $item->goodsHasStock = false;
            }
        }
        if ($item->goodsHasStock) {
            switch ($item->goodsPermission) {
                case 'M':
                    if (empty($groupVo)) {
                        $item->goodsHasStock = false;
                        $item->goodsHasStockCode = 'MSG_GSTOCK_ERROR_MEMBER';
                    }
                    break;
                case 'U':
                    if (empty($groupVo) || (! empty($item->memberGroupNo) && ! in_array($groupVo->groupCode, $item->memberGroupNo))) {
                        $item->goodsHasStock = false;
                        $item->goodsHasStockCode = 'MSG_GSTOCK_ERROR_GROUP';
                    }
                    break;
                case 'Y':
                default:
                    break;
            }
        }
        if ($item->goodsHasStock) {
            switch ($item->goodsAccess) {
                case 'M':
                    if (empty($groupVo)) {
                        $item->goodsHasStock = false;
                        $item->goodsHasStockCode = 'MSG_GSTOCK_ERROR_MEMBER';
                    }
                    break;
                case 'U':
                    if (empty($groupVo) || (! empty($item->memberGroupNo) && ! in_array($groupVo->groupCode, $item->memberGroupNo))) {
                        $item->goodsHasStock = false;
                        $item->goodsHasStockCode = 'MSG_GSTOCK_ERROR_GROUP';
                    }
                    break;
                case 'Y':
                default:
                    break;
            }
        }
        if ($item->goodsHasStock && $item->onlyAdultFl == 'Y' && (empty($this->loginInfo) || $this->loginInfo->isAdult != 'Y')) {
            $item->goodsHasStock = false;
            $item->goodsHasStockCode = 'MSG_GSTOCK_ERROR_ADULT';
            if (! empty($item->onlyAdultDisplayFl)) {
                if (! in_array('D', $item->onlyAdultDisplayFl)) {
                    $item->goodsHasStock = false;
                }
                if (! in_array('I', $item->onlyAdultDisplayFl)) {
                    $item->goodsImageMaster = '';
                    $item->goodsImageAdded = '';
                    $item->goodsImageAddedList = [];
                }
            } else {
                $item->goodsImageMaster = '';
                $item->goodsImageAdded = '';
                $item->goodsImageAddedList = [];
            }
        }
        return $item;
    }

    /**
     * 자주 주문하는 상품 페이징 가져오기
     *
     * @param LoginInfoVo $loginInfoVo
     * @param RequestVo $request
     * @param string $displayType
     * @return \Vo\PagingVo
     */
    public function GetGoodsMyOftenPaging(LoginInfoVo $loginInfoVo, RequestVo $request, $displayType = '')
    {
        $vo = $this->GetGoodsSearchVo($request, $loginInfoVo, $displayType);
        if ($this->CheckLogin($loginInfoVo)) {
            $vo->memNo = $loginInfoVo->memNo;
        }
        if (! empty($vo->memNo)) {
            $result = $this->GetGoodsInfoDao()->GetPaging($vo, $request->GetPerPage(10), $request->GetOffset());
            $this->GetGoodsPriceScmCheck($loginInfoVo, $result->items, $displayType);
            return $result;
        }
        return new PagingVo();
    }

    /**
     * 자주 주문하는 상품 목록 가져오기
     *
     * @param LoginInfoVo $loginInfoVo
     * @param RequestVo $request
     * @param string $displayType
     * @return GoodsInfoVo[]
     */
    public function GetGoodsMyOftenList(LoginInfoVo $loginInfoVo, RequestVo $request, $displayType = '')
    {
        $vo = $this->GetGoodsSearchVo($request, $loginInfoVo, $displayType);
        if ($this->CheckLogin($loginInfoVo)) {
            $vo->memNo = $loginInfoVo->memNo;
        }
        if (! empty($vo->memNo)) {
            $result = $this->GetGoodsInfoDao()->GetList($vo, $request->GetPerPage(10), $request->GetOffset());
            $this->GetGoodsPriceScmCheck($loginInfoVo, $result, $displayType);
            return $result;
        }
        return Array();
    }

    /**
     * 상품 간략 정보 목록 가져오기
     *
     * @param string $orderBy
     * @param integer $limit
     * @param string $goodsCategory
     * @param string $brandCd
     * @param integer $scmNo
     * @param string $displayType
     * @param string $groupSno
     * @return GoodsInfoSimpleVo[]
     */
    public function GetGoodsInfoSimpleList($orderBy, $limit = 30, $goodsCategory = '', $brandCd = '', $scmNo = 0, $displayType = '', $groupSno = '', $goodsSearchOptions = Array())
    {
        $vo = $this->GetGoodsSearchVo(null, null, $displayType);
        $vo->orderBy = $orderBy;
        $vo->displayCategory = '';
        if (! empty($goodsCategory)) {
            $vo->displayCategory = $goodsCategory;
        } else if (! empty($brandCd)) {
            $vo->displayCategory = $brandCd;
        } else {
            if (! empty($scmNo)) {
                $vo->scmNo = intval($scmNo);
            }
            $vo->displayCategory = '';
        }
        $vo->scmNo = intval($scmNo);
        if (empty($limit) || $limit < 1) {
            $limit = 10;
        }
        if (! empty($goodsSearchOptions)) {
            $vo->goodsSearchOptions = $goodsSearchOptions;
        }
        if (! empty($vo->displayCategory)) {
            $result = $this->GetGoodsInfoDao()->GetCategoryList($vo, $limit, 0);
        } else {
            $result = $this->GetGoodsInfoDao()->GetList($vo, $limit, 0);
        }
        $simpleList = Array();
        foreach ($result as $item) {
            $simpleList[] = $this->GetGoodsInfoAsSimpleView($item);
        }
        $this->GetGoodsPriceScmCheckGroupCode($simpleList, $displayType, $groupSno);
        return $simpleList;
    }

    /**
     * 특정 카테고리 상품 페이징 가져오기
     *
     * @param string $category
     * @param RequestVo $request
     * @param LoginInfoVo $loginInfoVo
     * @return \Vo\PagingVo
     */
    public function GetGoodsInfoCategoryPaging($category = '', RequestVo $request, LoginInfoVo $loginInfoVo = null)
    {
        $vo = $this->GetGoodsSearchVo($request, $loginInfoVo, '');
        $vo->displayCategory = $category;
        if (! empty($vo->displayCategory)) {
            $result = $this->GetGoodsInfoDao()->GetCategoryPaging($vo, $request->GetPerPage(10), $request->GetOffset());
            if (count($result->items) > 0 && $request->GetOffset() == 0) {
                $this->GetGoodsInfoKeywordCheck($vo->query, $loginInfoVo);
            }
            return $result;
        } else {
            return new PagingVo();
        }
    }

    /**
     * 특정 카테고리 상품 목록 가져오기
     *
     * @param string $category
     * @param RequestVo $request
     * @param LoginInfoVo $loginInfoVo
     * @return GoodsInfoVo[]|array
     */
    public function GetGoodsInfoCategoryList($category = '', RequestVo $request, LoginInfoVo $loginInfoVo = null)
    {
        $vo = $this->GetGoodsSearchVo($request, $loginInfoVo, '');
        $vo->displayCategory = $category;
        if (! empty($vo->displayCategory)) {
            $result = $this->GetGoodsInfoDao()->GetCategoryList($vo, $request->GetPerPage(10), $request->GetOffset());
            if (count($result) > 0 && $request->GetOffset() == 0) {
                $this->GetGoodsInfoKeywordCheck($vo->query, $loginInfoVo);
            }
            return $result;
        } else {
            return Array();
        }
    }

    /**
     * 특정 카테고리 상품 업데이트
     *
     * @param string $category
     * @param RequestVo $request
     * @param LoginInfoVo $loginInfoVo
     * @return GoodsInfoVo
     */
    public function GetGoodsInfoCategoryUpdate($category = '', RequestVo $request, LoginInfoVo $loginInfoVo = null)
    {
        $goodsCodes = $request->GetItemArray('goodsCodes');
        $batchForm = $request->batchForm;
        $movePoint = 0;
        $moveFix = 'I';
        $batchType = Array();
        if (! empty($batchForm)) {
            if (isset($batchForm->batchType) && is_array($batchForm->batchType)) {
                $batchType = $batchForm->batchType;
            }
            if (isset($batchForm->movePoint)) {
                $movePoint = intval($batchForm->movePoint);
            }
            $moveDirection = 'M';
            if (isset($batchForm->moveDirection)) {
                $moveDirection = $batchForm->moveDirection;
            }
            switch ($moveDirection) {
                case 'M':
                    $movePoint *= - 1;
            }
            if (isset($batchForm->moveFix)) {
                $moveFix = $batchForm->moveFix;
            }
        }
        $oldListOrderMap = Array();
        $oldListCodeMap = Array();
        $vo = new GoodsInfoVo();
        $vo->mallId = $this->mallId;
        $vo->goodsCategoryMst = $category;
        if (in_array('RESET', $batchType)) {
            $queryVo = new GoodsCategoryVo();
            $queryVo->mallId = $this->mallId;
            $queryVo->goodsCategory = $category;
            $queryVo->goodsCategoryFix = $moveFix;
            $oldList = $this->GetGoodsInfoDao()->GetCategoryGoodsList($queryVo, 10000, 0);
            foreach ($oldList as $key => $item) {
                $goodsCode = $item->goodsCode;
                $oldListOrderMap[$key] = $goodsCode;
                $oldListCodeMap[$goodsCode] = $item;
            }
        } else {
            $queryVo = new GoodsCategoryVo();
            $queryVo->mallId = $this->mallId;
            $queryVo->goodsCategory = $category;
            $oldList = $this->GetGoodsInfoDao()->GetCategoryGoodsList($queryVo, 10000, 0);
            $maxOrder = 1;
            foreach ($oldList as $item) {
                $goodsCode = $item->goodsCode;
                $goodsCategoryOrd = $item->goodsCategoryOrd;
                $oldListCodeMap[$goodsCode] = $item;
                $oldListOrderMap[$goodsCategoryOrd] = $goodsCode;
                $maxOrder = max($maxOrder, $goodsCategoryOrd);
            }
            ksort($oldListOrderMap);
            $sortedGoodsCodes = Array();
            foreach ($oldListOrderMap as $goodsCode) {
                if (in_array($goodsCode, $goodsCodes)) {
                    $sortedGoodsCodes[] = $goodsCode;
                }
            }
            if ($movePoint > 0) {
                $goodsCodes = array_reverse($sortedGoodsCodes);
            } else {
                $goodsCodes = $sortedGoodsCodes;
            }
            foreach ($goodsCodes as $goodsCode) {
                if (isset($oldListCodeMap[$goodsCode])) {
                    $goodCategory = $oldListCodeMap[$goodsCode];
                    switch ($moveFix) {
                        case 'N':
                        case 'Y':
                            $goodCategory->goodsCategoryFix = $moveFix;
                            break;
                    }
                    if ($movePoint != 0) {
                        $startPoint = 0;
                        foreach ($oldListOrderMap as $key => $value) {
                            if ($value == $goodsCode) {
                                $startPoint = $key;
                                break;
                            }
                        }
                        if (! empty($startPoint)) {
                            $endPoint = max(1, min($maxOrder, $startPoint + $movePoint));
                            if ($startPoint != $endPoint) {
                                $newListOrderMap = Array();
                                if ($startPoint < $endPoint) {
                                    foreach ($oldListOrderMap as $key => $value) {
                                        if ($key > $startPoint && $key <= $endPoint) {
                                            $newListOrderMap[$key - 1] = $value;
                                        } else if ($key != $startPoint) {
                                            $newListOrderMap[$key] = $value;
                                        }
                                    }
                                    $newListOrderMap[$endPoint] = $goodsCode;
                                } else {
                                    foreach ($oldListOrderMap as $key => $value) {
                                        if ($key < $startPoint && $key >= $endPoint) {
                                            $newListOrderMap[$key + 1] = $value;
                                        } else if ($key != $startPoint) {
                                            $newListOrderMap[$key] = $value;
                                        }
                                    }
                                    $newListOrderMap[$endPoint] = $goodsCode;
                                }
                                if (! empty($newListOrderMap)) {
                                    ksort($newListOrderMap);
                                    $oldListOrderMap = $newListOrderMap;
                                }
                            }
                        }
                    }
                }
            }
        }
        $vo->categoryVoList = Array();
        $seqn = 1;
        $updatedGoodsCodes = Array();
        foreach ($oldListOrderMap as $goodsCode) {
            if (isset($oldListCodeMap[$goodsCode])) {
                $goodCategory = $oldListCodeMap[$goodsCode];
                if ($goodCategory->goodsCategoryOrd != $seqn) {
                    $updatedGoodsCodes[] = $goodCategory->goodsCode;
                }
                $goodCategory->goodsCategoryOrd = $seqn;
                $vo->categoryVoList[] = $goodCategory;
                $seqn ++;
            }
        }
        if (count($vo->categoryVoList) > 0 && count($updatedGoodsCodes)) {
            if ($this->GetGoodsInfoDao()->SetGoodsCategoryCreate($vo)) {
                return $updatedGoodsCodes;
            }
        }
        return Array();
    }

    /**
     * 특정 카테고리 상품 페이징 가져오기
     *
     * @param string $category
     * @param RequestVo $request
     * @param LoginInfoVo $loginInfoVo
     * @return \Vo\PagingVo
     */
    public function GetGoodsInfoSearchPaging(RequestVo $request, LoginInfoVo $loginInfoVo = null)
    {
        $vo = $this->GetGoodsSearchVo($request, $loginInfoVo, '');
        $vo->displayCategory = '';
        $vo->goodsCategory = '';
        $vo->brandCd = '';
        $result = $this->GetGoodsInfoDao()->GetSearchPaging($vo, $request->GetPerPage(10), $request->GetOffset());
        if (count($result->items) > 0 && $request->GetOffset() == 0) {
            $this->GetGoodsInfoKeywordCheck($vo->query, $loginInfoVo);
        }
        return $result;
    }

    /**
     * 특정 카테고리 상품 목록 가져오기
     *
     * @param string $category
     * @param RequestVo $request
     * @param LoginInfoVo $loginInfoVo
     * @return GoodsInfoVo[]|array
     */
    public function GetGoodsInfoSearchList(RequestVo $request, LoginInfoVo $loginInfoVo = null)
    {
        $vo = $this->GetGoodsSearchVo($request, $loginInfoVo, '');
        $vo->displayCategory = '';
        $vo->goodsCategory = '';
        $vo->brandCd = '';
        $result = $this->GetGoodsInfoDao()->GetSearchList($vo, $request->GetPerPage(10), $request->GetOffset());
        if (count($result) > 0 && $request->GetOffset() == 0) {
            $this->GetGoodsInfoKeywordCheck($vo->query, $loginInfoVo);
        }
        return $result;
    }

    /**
     * 특정 카테고리 상품 업데이트
     *
     * @param string $category
     * @param RequestVo $request
     * @param LoginInfoVo $loginInfoVo
     * @return GoodsInfoVo
     */
    public function GetGoodsInfoSearchUpdate(RequestVo $request, LoginInfoVo $loginInfoVo = null)
    {
        $goodsCodes = $request->GetItemArray('goodsCodes');
        $batchForm = $request->batchForm;
        $movePoint = 0;
        $moveFix = 'I';
        $batchType = Array();
        if (! empty($batchForm)) {
            if (isset($batchForm->batchType) && is_array($batchForm->batchType)) {
                $batchType = $batchForm->batchType;
            }
            if (isset($batchForm->movePoint)) {
                $movePoint = intval($batchForm->movePoint);
            }
            $moveDirection = 'M';
            if (isset($batchForm->moveDirection)) {
                $moveDirection = $batchForm->moveDirection;
            }
            switch ($moveDirection) {
                case 'M':
                    $movePoint *= - 1;
            }
            if (isset($batchForm->moveFix)) {
                $moveFix = $batchForm->moveFix;
            }
        }
        $oldListOrderMap = Array();
        $oldListCodeMap = Array();
        $vo = new GoodsInfoVo();
        $vo->mallId = $this->mallId;
        $vo->goodsCategoryMst = 'search';
        if (in_array('RESET', $batchType)) {
            $queryVo = new GoodsCategoryVo();
            $queryVo->mallId = $this->mallId;
            $queryVo->goodsCategory = 'search';
            $queryVo->goodsCategoryFix = $moveFix;
            $oldList = $this->GetGoodsInfoDao()->GetSearchGoodsList($queryVo, 10000, 0);
            foreach ($oldList as $key => $item) {
                $goodsCode = $item->goodsCode;
                $oldListOrderMap[$key] = $goodsCode;
                $oldListCodeMap[$goodsCode] = $item;
            }
        } else {
            $queryVo = new GoodsCategoryVo();
            $queryVo->mallId = $this->mallId;
            $oldList = $this->GetGoodsInfoDao()->GetSearchGoodsList($queryVo, 10000, 0);
            $vo = new GoodsInfoVo();
            $vo->mallId = $this->mallId;
            $oldListOrderMap = Array();
            $oldListCodeMap = Array();
            $maxOrder = 1;
            foreach ($oldList as $seqn => $item) {
                $goodsCode = $item->goodsCode;
                $item->goodsCategoryOrd = $seqn + 1;
                $goodsCategoryOrd = $item->goodsCategoryOrd;
                $oldListCodeMap[$goodsCode] = $item;
                $oldListOrderMap[$goodsCategoryOrd] = $goodsCode;
                $maxOrder = max($maxOrder, $goodsCategoryOrd);
            }
            ksort($oldListOrderMap);
            $sortedGoodsCodes = Array();
            foreach ($oldListOrderMap as $goodsCode) {
                if (in_array($goodsCode, $goodsCodes)) {
                    $sortedGoodsCodes[] = $goodsCode;
                }
            }
            if ($movePoint > 0) {
                $goodsCodes = array_reverse($sortedGoodsCodes);
            } else {
                $goodsCodes = $sortedGoodsCodes;
            }
            foreach ($goodsCodes as $goodsCode) {
                if (isset($oldListCodeMap[$goodsCode])) {
                    $goodCategory = $oldListCodeMap[$goodsCode];
                    switch ($moveFix) {
                        case 'N':
                        case 'Y':
                            $goodCategory->goodsCategoryFix = $moveFix;
                            break;
                    }
                    if ($movePoint != 0) {
                        $startPoint = 0;
                        foreach ($oldListOrderMap as $key => $value) {
                            if ($value == $goodsCode) {
                                $startPoint = $key;
                                break;
                            }
                        }
                        if (! empty($startPoint)) {
                            $endPoint = max(1, min($maxOrder, $startPoint + $movePoint));
                            if ($startPoint != $endPoint) {
                                $newListOrderMap = Array();
                                if ($startPoint < $endPoint) {
                                    foreach ($oldListOrderMap as $key => $value) {
                                        if ($key > $startPoint && $key <= $endPoint) {
                                            $newListOrderMap[$key - 1] = $value;
                                        } else if ($key != $startPoint) {
                                            $newListOrderMap[$key] = $value;
                                        }
                                    }
                                    $newListOrderMap[$endPoint] = $goodsCode;
                                } else {
                                    foreach ($oldListOrderMap as $key => $value) {
                                        if ($key < $startPoint && $key >= $endPoint) {
                                            $newListOrderMap[$key + 1] = $value;
                                        } else if ($key != $startPoint) {
                                            $newListOrderMap[$key] = $value;
                                        }
                                    }
                                    $newListOrderMap[$endPoint] = $goodsCode;
                                }
                                if (! empty($newListOrderMap)) {
                                    ksort($newListOrderMap);
                                    $oldListOrderMap = $newListOrderMap;
                                }
                            }
                        }
                    }
                }
            }
        }
        $vo->goodsCategoryMst = 'search';
        $vo->categoryVoList = Array();
        $seqn = 1;
        $updatedGoodsCodes = Array();
        foreach ($oldListOrderMap as $goodsCode) {
            if (isset($oldListCodeMap[$goodsCode])) {
                $goodCategory = $oldListCodeMap[$goodsCode];
                $goodCategory->cateType = 'S';
                if ($goodCategory->goodsCategoryOrd != $seqn) {
                    $updatedGoodsCodes[] = $goodCategory->goodsCode;
                }
                $goodCategory->goodsCategoryOrd = $seqn;
                $vo->categoryVoList[] = $goodCategory;
                $seqn ++;
            }
        }
        if (count($vo->categoryVoList) > 0 && count($updatedGoodsCodes)) {
            if ($this->GetGoodsInfoDao()->SetGoodsSearchCreate($vo)) {
                return $updatedGoodsCodes;
            }
        }
        return Array();
    }

    /**
     * 상품 검색 키워드 업데이트
     *
     * @param string $keyword
     * @param LoginInfoVo $loginInfoVo
     */
    public function GetGoodsInfoKeywordCheck($keyword = '', LoginInfoVo $loginInfoVo = null, GoodsSearchVo $searchVo = null)
    {
        if (! empty($keyword) && ! empty($loginInfoVo)) {
            $uidKey = $this->mallId . '_keyworduser_' . md5($loginInfoVo->sharedToken . '_' . $keyword);
            $result = parent::GetCacheFile($uidKey);
            if (empty($result)) {
                $keywordService = $this->GetServiceKeyword();
                $keywordService->GetKeywordCreate('goods', $keyword);
                parent::SetCacheFile($uidKey, $keyword);
            }
        }
        if (! empty($loginInfoVo) && ! empty($searchVo)) {
            $categoryList = Array();
            if (! empty($searchVo->goodsCategory)) {
                $categoryList[] = $searchVo->goodsCategory;
            }
            if (! empty($searchVo->goodsCategorys)) {
                foreach ($searchVo->goodsCategorys as $goodsCategory) {
                    if (! in_array($goodsCategory, $categoryList)) {
                        $categoryList[] = $goodsCategory;
                    }
                }
            }
            if (! empty($categoryList)) {
                foreach ($categoryList as $category) {
                    if (! empty($category) && strlen($category) > 10) {
                        $uidKey = $this->GetServiceCacheKey('_categoryuser_', $loginInfoVo->sharedToken, md5($category));
                        $result = parent::GetCacheFile($uidKey, true);
                        if (empty($result)) {
                            parent::SetCacheFile($uidKey, $category);
                            $this->GetServiceStatistics()->SetLogUpdate($loginInfoVo->memLocale, 'category', $category, 1, '', true);
                        }
                    }
                }
            }
            $brandList = Array();
            if (! empty($searchVo->brandCd)) {
                $brandList[] = $searchVo->brandCd;
            }
            if (! empty($searchVo->brandCds)) {
                foreach ($searchVo->brandCds as $brand) {
                    if (! in_array($brand, $brandList)) {
                        $brandList[] = $brand;
                    }
                }
            }
            if (! empty($brandList)) {
                foreach ($brandList as $brand) {
                    if (! empty($brand) && strlen($brand) > 10) {
                        $uidKey = $this->GetServiceCacheKey('_branduser_', $loginInfoVo->sharedToken, md5($brand));
                        $result = parent::GetCacheFile($uidKey, true);
                        if (empty($result)) {
                            parent::SetCacheFile($uidKey, $brand);
                            $this->GetServiceStatistics()->SetLogUpdate($loginInfoVo->memLocale, 'brand', $brand, 1, '', true);
                        }
                    }
                }
            }
        }
    }

    /**
     * 상품 정보 이벤트 목록 가져오기
     *
     * @param string $eventId
     * @param RequestVo $request
     * @param LoginInfoVo $loginInfoVo
     * @return GoodsInfoVo[]
     */
    public function GetGoodsInfoEventList($eventId = '', RequestVo $request, LoginInfoVo $loginInfoVo = null)
    {
        $vo = parent::GetSearchVo($request, 'GoodsSearchVo');
        return $this->GetGoodsInfoDao()->GetList($vo, $request->GetPerPage(10), $request->GetOffset());
    }

    /**
     * 특정 회원의 최근 열람 상품 검색 조건 가져오기
     *
     * @param LoginInfoVo $loginInfoVo
     * @param RequestVo $request
     * @return GoodsRecentSearchVo
     */
    public function GetGoodsInfoRecentSearchVo(LoginInfoVo $loginInfoVo = null, RequestVo $request)
    {
        $vo = new GoodsRecentSearchVo();
        parent::GetSearchVo($request, $vo);
        $vo->token = $loginInfoVo->sharedToken;
        $goodsTodayVo = $this->GetServicePolicy()->GetGoodsTodayView();
        $vo->todayCnt = $goodsTodayVo->todayCnt;
        $vo->todayHour = $goodsTodayVo->todayHour;
        return $vo;
    }

    /**
     * 특정 회원의 최근 열람 상품 정보 목록 가져오기
     *
     * @param LoginInfoVo $loginInfoVo
     * @param RequestVo $request
     * @param string $displayType
     * @return GoodsInfoVo[]
     */
    public function GetGoodsInfoRecentList(LoginInfoVo $loginInfoVo = null, RequestVo $request, $displayType = '')
    {
        $vo = $this->GetGoodsInfoRecentSearchVo($loginInfoVo, $request);
        $result = $this->GetGoodsInfoDao()->GetRecentList($vo, $request->GetPerPage(10), $request->GetOffset());
        $this->GetGoodsPriceScmCheck($loginInfoVo, $result, $displayType);
        return $result;
    }

    /**
     * 특정 회원의 최근 열람 상품 페이징 가져오기
     *
     * @param LoginInfoVo $loginInfoVo
     * @param RequestVo $request
     * @param string $displayType
     * @return \Vo\PagingVo
     */
    public function GetGoodsInfoRecentPaging(LoginInfoVo $loginInfoVo = null, RequestVo $request, $displayType = '')
    {
        $vo = $this->GetGoodsInfoRecentSearchVo($loginInfoVo, $request);
        $result = $this->GetGoodsInfoDao()->GetRecentPaging($vo, $request->GetPerPage(10), $request->GetOffset());
        $this->GetGoodsPriceScmCheck($loginInfoVo, $result->items, $displayType);
        return $result;
    }

    /**
     * 특정회원의 관심 상품 목록 가져오기
     *
     * @param LoginInfoVo $loginInfoVo
     * @param RequestVo $request
     * @param string $displayType
     * @return GoodsInfoVo[]
     */
    public function GetGoodsInfoWishList(LoginInfoVo $loginInfoVo = null, RequestVo $request, $displayType = '')
    {
        $vo = $this->GetGoodsSearchVo($request, $loginInfoVo, $displayType);
        if ($this->CheckLogin($loginInfoVo)) {
            $vo->token = $loginInfoVo->sharedToken;
        }
        $result = $this->GetGoodsInfoDao()->GetWishList($vo, $request->GetPerPage(10), $request->GetOffset());
        $this->GetGoodsPriceScmCheck($loginInfoVo, $result, $displayType);
        return $result;
    }

    /**
     * 특정 회원의 관심 상품 페이징 가져오기
     *
     * @param LoginInfoVo $loginInfoVo
     * @param RequestVo $request
     * @param string $displayType
     * @return \Vo\PagingVo
     */
    public function GetGoodsInfoWishPaging(LoginInfoVo $loginInfoVo = null, RequestVo $request, $displayType = '')
    {
        $vo = $this->GetGoodsSearchVo($request, $loginInfoVo, $displayType);
        if ($this->CheckLogin($loginInfoVo)) {
            $vo->token = $loginInfoVo->sharedToken;
        }
        $result = $this->GetGoodsInfoDao()->GetWishPaging($vo, $request->GetPerPage(10), $request->GetOffset());
        $this->GetGoodsPriceScmCheck($loginInfoVo, $result->items, $displayType);
        return $result;
    }

    /**
     * 특정회원의 관심 상품 제거
     *
     * @param LoginInfoVo $loginInfoVo
     * @param RequestVo $request
     * @return boolean
     */
    public function GetGoodsInfoWishDelete(LoginInfoVo $loginInfoVo = null, RequestVo $request)
    {
        $vo = new GoodsSearchVo();
        parent::GetSearchVo($request, $vo);
        $vo->token = $loginInfoVo->sharedToken;
        return $this->GetGoodsInfoDao()->SetWishDelete($vo);
    }

    /**
     * 상품 정보를 간략상품 정보로 변환해서 가져오기
     *
     * @param GoodsInfoVo $goodsInfo
     * @return GoodsInfoSimpleVo
     */
    public function GetGoodsInfoAsSimpleView(GoodsInfoVo $goodsInfo, $result = null)
    {
        if (empty($result)) {
            $result = new GoodsInfoSimpleVo();
        }
        $result->goodsCode = $goodsInfo->goodsCode;
        $result->mallId = $goodsInfo->mallId;
        $result->goodsCode = $goodsInfo->goodsCode;
        $result->goodsCategoryMst = $goodsInfo->goodsCategoryMst;
        $result->goodsCd = $goodsInfo->goodsCd;
        $result->goodsNm = $goodsInfo->goodsNm;
        $result->goodsNmLocale = $goodsInfo->goodsNmLocale;
        $result->brandCd = $goodsInfo->brandCd;
        $result->brandNm = $goodsInfo->brandNm;
        $result->makerNm = $goodsInfo->makerNm;
        $result->originNm = $goodsInfo->originNm;
        $result->goodsPrice = $goodsInfo->goodsPrice;
        $result->fixedPrice = $goodsInfo->fixedPrice;
        $result->goodsDealPrice = $goodsInfo->goodsDealPrice;
        $result->goodsDealSavePrice = $goodsInfo->goodsDealSavePrice;
        $result->goodsDealSaveRate = $goodsInfo->goodsDealSaveRate;
        $result->sellerMemNo = $goodsInfo->sellerMemNo;
        $result->goodsDisplayFl = $goodsInfo->goodsDisplayFl;
        $result->goodsDisplayMobileFl = $goodsInfo->goodsDisplayMobileFl;
        $result->goodsSellFl = $goodsInfo->goodsSellFl;
        $result->goodsSellMobileFl = $goodsInfo->goodsSellMobileFl;
        $result->goodsSellType = $goodsInfo->goodsSellType;
        $result->goodsImageMaster = $goodsInfo->goodsImageMaster;
        $result->goodsImageAddedList = $goodsInfo->goodsImageAddedList;
        $result->goodsDescription = $goodsInfo->shortDescription;

        // $result->goodsDescriptionLocale = $goodsInfo->shortDescriptionLocale;
        // $result->goodsDescriptionMobile = $goodsInfo->goodsDescriptionMobile;
        // $result->goodsDescriptionMobileLocale = $goodsInfo->goodsDescriptionMobileLocale;
        $result->shortDescription = $goodsInfo->shortDescription;
        $result->shortDescriptionLocale = $goodsInfo->shortDescriptionLocale;
        $result->soldOutFl = $goodsInfo->soldOutFl;
        $result->stockFl = $goodsInfo->stockFl;
        $result->stockCnt = $goodsInfo->stockCnt;
        $result->optionFl = $goodsInfo->optionFl;
        $result->goodsIcon = $goodsInfo->goodsIcon;
        $result->goodsIconOpenDate = $goodsInfo->goodsIconOpenDate;
        $result->goodsIconCloseDate = $goodsInfo->goodsIconCloseDate;
        $result->goodsIconOpenDateTime = $goodsInfo->goodsIconOpenDateTime;
        $result->goodsIconCloseDateTime = $goodsInfo->goodsIconCloseDateTime;
        $result->goodsPermission = $goodsInfo->goodsPermission;
        $result->onlyAdultFl = $goodsInfo->onlyAdultFl;
        $result->onlyAdultDisplayFl = $goodsInfo->onlyAdultDisplayFl;
        $result->goodsAccess = $goodsInfo->goodsAccess;
        $result->memberGroupNo = $goodsInfo->memberGroupNo;
        $result->goodsOpenDt = $goodsInfo->goodsOpenDt;
        $result->goodsCloseDt = $goodsInfo->goodsCloseDt;
        $result->salesOpenDate = $goodsInfo->salesOpenDate;
        $result->salesCloseDate = $goodsInfo->salesCloseDate;
        $result->goodsIconTime = $goodsInfo->goodsIconTime;
        $result->goodsIconFix = $goodsInfo->goodsIconFix;
        $result->reviewCnt = $goodsInfo->reviewCnt;
        $result->reviewPoint = $goodsInfo->reviewPoint;
        $result->mileageFl = $goodsInfo->mileageFl;
        $result->mileageGoods = $goodsInfo->mileageGoods;
        $result->mileageGoodsUnit = $goodsInfo->mileageGoodsUnit;
        $result->mileageGoodsPerOne = $goodsInfo->mileageGoodsPerOne;
        $result->goodsPriceString = $goodsInfo->goodsPriceString;
        $result->goodsPriceFl = $goodsInfo->goodsPriceFl;
        $result->goodsPriceGroup = $goodsInfo->goodsPriceGroup;
        $result->goodsPriceUnit = $goodsInfo->goodsPriceUnit;
        $result->deliveryNation = $goodsInfo->deliveryNation;
        $result->deliveryPackingRule = $goodsInfo->deliveryPackingRule;
        $result->deliveryPackingPrice = $goodsInfo->deliveryPackingPrice;
        $result->addedField01 = $goodsInfo->addedField01;
        $result->addedField02 = $goodsInfo->addedField02;
        $result->addedField03 = $goodsInfo->addedField03;
        $result->addedField04 = $goodsInfo->addedField04;
        $result->addedField05 = $goodsInfo->addedField05;
        $result->addedField06 = $goodsInfo->addedField06;
        $result->addedField07 = $goodsInfo->addedField07;
        $result->addedField08 = $goodsInfo->addedField08;
        $result->addedField09 = $goodsInfo->addedField09;
        $result->addedField10 = $goodsInfo->addedField10;
        $result->scmRegDate = $goodsInfo->scmRegDate;
        $result->scmModDate = $goodsInfo->scmModDate;
        $result->scmApplyCnt = $goodsInfo->scmApplyCnt;
        $result->scmUnapplyCnt = $goodsInfo->scmUnapplyCnt;
        $result->deliveryDetail = $goodsInfo->deliveryDetail;
        $result->regDate = $goodsInfo->regDate;
        $result->modDate = $goodsInfo->modDate;
        if (isset($goodsInfo->externalVideoFl) && isset($goodsInfo->externalVideoUrl) && $goodsInfo->externalVideoFl == 'Y' && ! empty($goodsInfo->externalVideoUrl)) {
            $result->externalVideoFl = 'Y';
        } else {
            $result->externalVideoFl = 'N';
        }
        return $result;
    }

    /**
     * 상품 간략 정보 가져오기
     *
     * @param string $uid
     * @param string $displayType
     * @param string $groupSno
     * @return GoodsInfoSimpleVo
     */
    public function GetGoodsInfoSimpleView($uid = '', $displayType = 'web', $groupSno = '')
    {
        $uidKey = parent::GetServiceCacheKey('goodsInfo', $uid, 'simple');
        $result = parent::GetCacheFile($uidKey, true);
        if (empty($result) || ! ($result instanceof GoodsInfoSimpleVo)) {
            try {
                $result = $this->GetGoodsInfoAsSimpleView($this->GetGoodsInfoView($uid));
                if (! empty($groupSno)) {
                    $this->GetGoodsPriceScmCheckGroupCode(Array(
                        $result
                    ), $displayType, $groupSno);
                } else {
                    $this->GetGoodsPriceScmCheck(null, Array(
                        $result
                    ), $displayType);
                }
            } catch (Exception $ex) {
                $result = new GoodsInfoSimpleVo();
                $result->goodsCode = $uid;
                $result->goodsNm = 'No name goods';
            }
            parent::SetCacheFile($uidKey, $result);
        }
        return $result;
    }

    /**
     * 상품 간략 정보 가져오기
     *
     * @param string $uid
     * @param string $displayType
     * @param string $groupSno
     * @return GoodsInfoSimpleVo
     */
    public function GetGoodsInfoDiscountView($uid = '', LoginInfoVo $loginInfo = null, $displayType = '')
    {
        try {
            $goodsInfo = $this->GetGoodsInfoView($uid, $loginInfo, false, $displayType);
            $vo = new GoodsDiscountInfoVo();
            $vo->disCountList = [];
            $vo->mallId = $this->mallId;
            $vo->goodsCode = $goodsInfo->goodsCode;
            if ($goodsInfo->fixedPrice > $goodsInfo->goodsPrice) {
                $vo->fixedPrice = $goodsInfo->fixedPrice;
            } else {
                $vo->fixedPrice = 0;
            }
            if ($goodsInfo->fixedPrice > $goodsInfo->goodsScmPrice) {
                $vo->goodsScmPrice = $goodsInfo->goodsScmPrice;
                $priceVo = new GoodsDiscountPriceVo();
                $priceVo->discountType = 'S';
                $priceVo->discountAmount = $goodsInfo->fixedPrice - $goodsInfo->goodsScmPrice;
                $vo->disCountList[] = $priceVo;
            } else {
                $vo->goodsScmPrice = 0;
            }
            if ($goodsInfo->goodsScmPrice > $goodsInfo->goodsPrice) {
                $vo->goodsPrice = $goodsInfo->goodsPrice;
                $priceVo = new GoodsDiscountPriceVo();
                $priceVo->discountType = 'M';
                $priceVo->discountAmount = $goodsInfo->goodsScmPrice - $goodsInfo->goodsPrice;
                $vo->disCountList[] = $priceVo;
            } else {
                $vo->goodsPrice = 0;
            }
            $vo->goodsDealPrice = $goodsInfo->goodsDealPrice;
            $vo->goodsDealSavePrice = $goodsInfo->goodsDealSavePrice;
            $vo->goodsDealSaveRate = $goodsInfo->goodsDealSaveRate;

            $couponInfo = $this->GetServiceMember()->GetCouponViewByGoods(! empty($loginInfo) ? $loginInfo->groupSno : '', $uid, $displayType);
            if (! empty($couponInfo)) {
                $vo->couponInfo = $couponInfo;
                $priceVo = new GoodsDiscountPriceVo();
                $priceVo->discountType = 'C';
                $priceVo->discountNm = $couponInfo->couponNm;
                $priceVo->discountNmLocale = $couponInfo->couponNmLocale;
                switch ($couponInfo->couponUsePeriodType) {
                    case 'Y':
                        $priceVo->discountUsePeriodStartDate = $couponInfo->couponUsePeriodStartDate;
                        $priceVo->discountUsePeriodEndDate = $couponInfo->couponUsePeriodEndDate;
                        break;
                    default:
                        $priceVo->discountUsePeriodStartDate = $this->getDateNow();
                        $priceVo->discountUsePeriodEndDate = $this->getDateNow(time() + $couponInfo->couponUsePeriodDay * 60 * 60 * 24);
                        if (! $this->isDateEmpty($couponInfo->couponUseDateLimit)) {
                            $discountUsePeriodEndDate = strtotime($priceVo->discountUsePeriodEndDate);
                            $couponUseDateLimit = strtotime($couponInfo->couponUseDateLimit);
                            if ($couponUseDateLimit < $discountUsePeriodEndDate) {
                                $priceVo->discountUsePeriodEndDate = $this->getDateNow($couponUseDateLimit);
                            }
                        }
                        break;
                }
                switch ($couponInfo->couponBenefitType) {
                    case 'P':
                        $priceVo->discountAmount = $goodsInfo->goodsPrice * ($couponInfo->couponBenefit / 100);
                        break;
                    default:
                        $priceVo->discountAmount = $couponInfo->couponBenefit;
                        break;
                }
                $vo->disCountList[] = $priceVo;
            }
            return $vo;
        } catch (\Exception $ex) {
            return null;
        }
    }

    /**
     * 로드된 공급업체 정보
     *
     * @var \Vo\ScmInfoVo[]
     */
    private $loadScmInfo = Array();

    /**
     * 특정 공급업체 번호로 공급업체 정보 가져오기
     *
     * @param integer $scmNo
     * @return \Vo\ScmInfoVo
     */
    public function GetScmInfo($scmNo = 0)
    {
        if (! empty($scmNo)) {
            if (! isset($this->loadScmInfo[$scmNo])) {
                $scmService = $this->GetServiceScm();
                $this->loadScmInfo[$scmNo] = $scmService->GetScmInfoSimpleView($scmNo);
            }
            if (isset($this->loadScmInfo[$scmNo])) {
                return $this->loadScmInfo[$scmNo];
            }
        }
        return null;
    }

    /**
     * Obj file로부터 옵션 정보 가져오기
     *
     * @param string $objFilePath
     * @return string
     */
    public function GetOptionsFromObjFile($objFilePath = '')
    {
        return "";
    }

    /**
     * Obj file로부터 옵션 정보 가져오기
     *
     * @param string $objFilePath
     * @return \stdClass
     */
    public function GetGoodsInfoOptionsFromObjFile($objFilePath = '')
    {
        $optionInfo = $this->GetOptionsFromObjFile($objFilePath);
        $vo = new \stdClass();
        $vo->metalList = Array();
        $vo->stoneList = Array();
        if (! empty($optionInfo)) {
            $optionInfoList = explode(",", $optionInfo);
            foreach ($optionInfoList as $key) {
                if (! empty($key)) {
                    if (preg_match("#^s.+#", $key)) {
                        $vo->stoneList[] = $key;
                    } else if (preg_match("#^m.+#", $key)) {
                        $vo->metalList[] = $key;
                    }
                }
            }
        }
        return $vo;
    }

    /**
     * 상품 정보 가져오기
     *
     * @param GoodsInfoVo $goodsInfo
     * @param LoginInfoVo $loginInfoVo
     * @param boolean $isCopy
     * @param string $displayType
     * @return GoodsInfoVo
     */
    public function GetCategoryGoodsList(GoodsInfoVo $goodsInfo, LoginInfoVo $loginInfoVo = null, $displayType = '', $scmNo = 0)
    {
        $goodsRelationVo = null;
        if (($goodsInfo->refGoodsFl == 'A' || $goodsInfo->refGoodsFl == '') && ! empty($goodsInfo->goodsCategoryMst)) {
            $categoryKey = parent::GetServiceCacheKey('refgoodsInfo', $goodsInfo->goodsCategoryMst, $scmNo);
            $categoryGoods = parent::GetCacheFile($categoryKey);
            $goodsRelationVo = $this->GetServicePolicy()->GetGoodsRelationView();
            $maxRefGoods = max($goodsRelationVo->lineCnt * $goodsRelationVo->rowCnt, $goodsRelationVo->mobileLineCnt * $goodsRelationVo->mobileRowCnt);
            $limitRefGoods = 0;
            switch ($displayType) {
                case 'app':
                    $limitRefGoods = $goodsRelationVo->mobileLineCnt * $goodsRelationVo->mobileRowCnt;
                    break;
                case 'web':
                default:
                    $limitRefGoods = $goodsRelationVo->lineCnt * $goodsRelationVo->rowCnt;
                    break;
            }
            if (empty($categoryGoods) && $maxRefGoods > 0) {
                $searchVo = new GoodsSearchVo();
                $searchVo->mallId = $this->mallId;
                $searchVo->goodsCategory = $goodsInfo->goodsCategoryMst;
                if (! empty($scmNo)) {
                    $searchVo->scmNo = $scmNo;
                }
                $searchVo->offset = 0;
                $searchVo->limit = $maxRefGoods * 3;
                $searchVo->isHiddenFl = 'N';
                switch ($displayType) {
                    case 'app':
                        if ($goodsRelationVo->mobileSoldOutFl != 'Y') {
                            $searchVo->soldOutFl = 'N';
                        }
                        $searchVo->goodsDisplayFl = 'Y';
                        break;
                    case 'web':
                    default:
                        if ($goodsRelationVo->soldOutFl != 'Y') {
                            $searchVo->soldOutFl = 'N';
                        }
                        $searchVo->goodsDisplayMobileFl = 'Y';
                        break;
                }
                $refGoodsRequest = new RequestVo($searchVo);
                $categoryGoods = $this->GetGoodsInfoList($refGoodsRequest, $loginInfoVo);
                parent::SetCacheFile($categoryKey, $categoryGoods);
            }
            if (! empty($categoryGoods) && count($categoryGoods) > 0 && $limitRefGoods > 0) {
                $refGoodsList = Array();
                shuffle($categoryGoods);
                foreach ($categoryGoods as $goodInfo) {
                    if ($goodInfo->goodsCode != $goodsInfo->goodsCode) {
                        if (count($refGoodsList) >= $limitRefGoods) {
                            break;
                        }
                        $refGoodsList[] = $goodInfo;
                    }
                }
                $goodsInfo->refGoodsList = $refGoodsList;
            }
        } else if (! empty($goodsInfo->refGoods)) {
            $goodsInfo->refGoodsList = Array();
            foreach ($goodsInfo->refGoods as $refGoodsCode) {
                $simpleInfo = $this->GetGoodsInfoSimpleView($refGoodsCode, $displayType, ! empty($loginInfoVo) ? $loginInfoVo->groupSno : '');
                if (! empty($simpleInfo) && ! empty($simpleInfo->goodsCode) && ! empty($simpleInfo->goodsPrice)) {
                    $goodsInfo->refGoodsList[] = $simpleInfo;
                }
            }
        }
        if (count($goodsInfo->refGoodsList) > 0) {
            if (empty($goodsRelationVo)) {
                $goodsRelationVo = $this->GetServicePolicy()->GetGoodsRelationView();
            }
            $soldOutDisplayFl = 'D';
            switch ($displayType) {
                case 'app':
                    $soldOutDisplayFl = $goodsRelationVo->mobileSoldOutDisplayFl;
                    break;
                case 'web':
                default:
                    $soldOutDisplayFl = $goodsRelationVo->soldOutDisplayFl;
                    break;
            }
            switch ($soldOutDisplayFl) {
                case "T":
                    $refGoodsList = Array();
                    foreach ($goodsInfo->refGoodsList as $goodInfo) {
                        if ($goodInfo->goodsHasStock) {
                            $refGoodsList[] = $goodInfo;
                        }
                    }
                    foreach ($goodsInfo->refGoodsList as $goodInfo) {
                        if (! in_array($goodInfo, $refGoodsList)) {
                            $refGoodsList[] = $goodInfo;
                        }
                    }
                    $goodsInfo->refGoodsList = $refGoodsList;
                    break;
                case "D":
                default:
                    break;
            }
        }
        return $goodsInfo;
    }

    /**
     * 상품 정보 가져오기
     *
     * @param string $uid
     * @param LoginInfoVo $loginInfoVo
     * @param boolean $isCopy
     * @param string $displayType
     * @return GoodsInfoVo
     */
    public function GetGoodsInfoView($uid = '', LoginInfoVo $loginInfoVo = null, $isCopy = false, $displayType = '')
    {
        $uidKey = parent::GetServiceCacheKey('goodsInfo', $uid);
        $result = parent::GetCacheFile($uidKey, true);
        
        if (empty($result) || ! ($result instanceof GoodsInfoVo)) {
            $vo = $this->GetGoodsInfoVo($uid);
            $result = $this->GetGoodsInfoDao()->GetView($vo);
            if (! empty($result)) {
                $this->GetGoodsInfoViewParse($result);
                parent::SetCacheFile($uidKey, $result);
            } else {
                $this->GetException(KbmException::DATA_ERROR_VIEW);
            }
        }
        if (! empty($result)) {
            $this->GetGoodsMallSynk($result, 'view');
        }
        if (! empty($result) && ! empty($loginInfoVo)) {
            if (! empty($displayType)) {
                $this->GetCategoryGoodsList($result, $loginInfoVo, $displayType);
                $oldViewKey = parent::GetServiceCacheKey('log-goods' . date('Ymd'), $loginInfoVo->sharedToken, $uid);
                $oldViewLog = parent::GetCacheFile($oldViewKey, true);
                if (empty($oldViewLog)) {
                    $oldViewLog = $this->AddGoodsInfoLog('V', $uid, 1);
                    parent::SetCacheFile($oldViewKey, $oldViewLog);
                    if (! empty($uid) && strlen($uid) > 10) {
                        $uidKey = $this->GetServiceCacheKey('_goodsviewuser_', $loginInfoVo->sharedToken, md5($uid));
                        $oldViewLog = parent::GetCacheFile($uidKey, true);
                        if (empty($oldViewLog)) {
                            parent::SetCacheFile($uidKey, $uid);
                            $this->GetServiceStatistics()->SetLogUpdate($loginInfoVo->memLocale, 'goodsview', $uid, 1, '', true);
                        }
                    }
                }
            }
            $policyService = $this->GetServicePolicy();
            $policyService->GetMemberMileageGiveGoods($result);
            $this->GetGoodsPriceScmCheck($loginInfoVo, [
                $result
            ], $displayType);
            if (! empty($loginInfoVo)) {
                $policyService->GetDeliveryConfigGoods($result, $loginInfoVo);
            }
        }
        if ($isCopy && ! empty($result)) {
            return $this->GetGoodsInfoClone($result);
        } else {
            return $result;
        }
    }

    /**
     * 최근 배지 카운터 가져오기
     *
     * @param string $badgeType
     * @param \stdClass $queryInfo
     * @return number
     */
    public function GetTabBadge($badgeType = '', $queryInfo = Array())
    {
        $badgeCnt = 0;
        switch ($badgeType) {
            case 'goods_list':
                $badgeCnt = $this->GetGoodsInfoDao()->GetTabBadge($queryInfo);
                break;
            case 'goodsqna_list':
                $badgeCnt = $this->GetGoodsQnaDao()->GetTabBadge($queryInfo);
                break;
            case 'goodsreview_list':
                $badgeCnt = $this->GetGoodsReviewDao()->GetTabBadge($queryInfo);
                break;
        }
        return $badgeCnt;
    }

    /**
     * 최근 상태 가져오기
     *
     * @param DashBoardStatusVo $vo
     * @param string $statusType
     * @return DashBoardStatusDataVo
     */
    public function GetDashBoardStatus(DashBoardStatusVo $vo, $statusType = 'qna')
    {
        switch ($statusType) {
            case 'status':
                $info = $this->GetGoodsInfoDao()->GetDashBoardStatus($vo);
                if (! empty($info)) {
                    $vo->goodsCnt = $info->goodsCnt;
                    $vo->goodsSaleCnt = $info->goodsSaleCnt;
                    $vo->goodsOutStockCnt = $info->goodsOutStockCnt;
                    $vo->goodsOutStock10Cnt = $info->goodsOutStock10Cnt;
                    $vo->goodsUnSaleCnt = $info->goodsUnSaleCnt;
                    $vo->goodsTotalCnt = $info->goodsTotalCnt;
                    $vo->goodsQnaCnt = $info->goodsQnaCnt;
                } else {
                    $vo->goodsCnt = 0;
                    $vo->goodsSaleCnt = 0;
                    $vo->goodsOutStockCnt = 0;
                    $vo->goodsOutStock10Cnt = 0;
                    $vo->goodsUnSaleCnt = 0;
                    $vo->goodsTotalCnt = 0;
                    $vo->goodsQnaCnt = 0;
                }
                break;
            case 'qna':
                return $this->GetGoodsQnaDao()->GetDashBoardStatus($vo);
            case 'review':
            default:
                return $this->GetGoodsReviewDao()->GetDashBoardStatus($vo);
        }
        return new DashBoardStatusDataVo();
    }

    /**
     * 상품 정보 파싱
     *
     * @param GoodsInfoVo $result
     * @return GoodsInfoVo
     */
    public function GetGoodsInfoViewParse(GoodsInfoVo $result)
    {
        $result->goodsScmPrice = $result->goodsPrice;
        if (! empty($result->goodsImageAdded)) {
            $result->goodsImageAddedList = explode('@!@', $result->goodsImageAdded);
        }
        if (empty($result->goodsImageAddedList)) {
            $result->goodsImageAddedList = [];
        }
        if (! empty($result->deliveryNation)) {
            $result->deliveryNationCode = $result->deliveryNation;
        }
        if (empty($result->deliveryNationCode)) {
            $result->deliveryNationCode = 'kr';
        }
        if ($result->optionDisplayImage == 'P') {
            if ($result->optionFl == 'Y') {
                if (! empty($result->options)) {
                    foreach ($result->options as $options) {
                        if (! empty($options->optionImage) && $this->IsUploadImage($options->optionImage)) {
                            $result->goodsImageAddedList[] = $options->optionImage;
                        }
                    }
                }
                if (! empty($result->optionsExt)) {
                    foreach ($result->optionsExt as $options) {
                        if (! empty($options->optionImage) && $this->IsUploadImage($options->optionImage)) {
                            $result->goodsImageAddedList[] = $options->optionImage;
                        }
                    }
                }
            }
            if ($result->optionTextFl == 'Y') {
                if (! empty($result->optionsText)) {
                    foreach ($result->optionsText as $options) {
                        if (! empty($options->optionImage) && $this->IsUploadImage($options->optionImage)) {
                            $result->goodsImageAddedList[] = $options->optionImage;
                        }
                    }
                }
            }
            if ($result->optionRefFl == 'Y') {
                if (! empty($result->optionsRef)) {
                    foreach ($result->optionsRef as $options) {
                        if (! empty($options->optionImage) && $this->IsUploadImage($options->optionImage)) {
                            $result->goodsImageAddedList[] = $options->optionImage;
                        }
                    }
                }
            }
        }
        if ($result->externalVideoFl == 'Y' && ! empty($result->externalVideoUrl)) {
            $videoType = '';
            $videoId = '';
            $matches = Array();
            if (preg_match('#^(http|https)://(.+)/(watch\?v=|)([a-zA-Z0-9_\-]+)$#', $result->externalVideoUrl, $matches)) {
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
                $result->externalVideoVo = new ExternalVideoVo();
                $result->externalVideoVo->videoId = $videoId;
                $result->externalVideoVo->videoType = $videoType;
                switch ($videoType) {
                    case 'youtube':
                        $result->externalVideoVo->videoUrl = 'https://www.youtube.com/embed/' . $videoId;
                        $result->externalVideoVo->videoThumbUrl = 'thumbnail.jpg#100#image/jpg#youtube/' . $videoId . '.jpg';
                        $result->externalVideoUrl = 'https://youtu.be/' . $videoId;
                        break;
                    case 'vimeo':
                        $result->externalVideoVo->videoUrl = 'https://player.vimeo.com/video/' . $videoId;
                        $result->externalVideoVo->videoThumbUrl = 'thumbnail.jpg#100#image/jpg#vimeo/' . $videoId . '.jpg';
                        $result->externalVideoUrl = 'https://vimeo.com/' . $videoId;
                        break;
                }
            } else {
                $result->externalVideoVo = null;
                $result->externalVideoUrl = '';
            }
        } else {
            $result->externalVideoVo = null;
        }
        if ($result->optionFl == 'Y') {
            $result->optionsTree = $this->GetGoodsInfoOptionTree($result->options, $result->optionDisplayFl, $result->stockFl, $result->stockCnt);
        }
        if (empty($result->optionsTree)) {
            $result->optionFl = 'N';
        }
        if ($result->optionsExtFl == 'Y') {
            $result->optionsExtTree = $this->GetGoodsInfoOptionTree($result->optionsExt, '', $result->stockFl, $result->stockCnt);
        }
        if (empty($result->optionsExtTree)) {
            $result->optionsExtFl = 'N';
        }
        if ($result->optionTextFl == 'Y') {
            $result->optionsTextTree = $this->GetGoodsInfoOptionTree($result->optionsText, '', $result->stockFl, $result->stockCnt);
        }
        if (empty($result->optionsTextTree)) {
            $result->optionTextFl = 'N';
        }
        if ($result->optionRefFl == 'Y') {
            $result->optionsRefTree = $this->GetGoodsInfoOptionTree($result->optionsRef, '', $result->stockFl, $result->stockCnt);
        }
        if (empty($result->optionsRefTree)) {
            $result->optionRefFl = 'N';
        }
        $result->goodsRefKeys = [];
        $goodsCategoryIdList = $this->GetGoodsInfoDao()->GetGoodsCategoryList($result, 1000, 0);
        foreach ($goodsCategoryIdList as $vo) {
            $result->goodsRefKeys[] = $vo->goodsCategory;
        }
        return $result;
    }

    /**
     * 상품 옵션 정보 파싱
     *
     * @param OptionTreeVo[] $options
     * @param string $optionDisplayFl
     * @param string $stockFl
     * @return OptionTreeVo[]
     */
    public function GetGoodsInfoOptionTree($options, $optionDisplayFl = 'S', $stockFl = 'N', $stockCnt = 0)
    {
        $optionsList = Array();
        if (is_array($options)) {
            foreach ($options as $vo) {
                if (is_object($vo)) {
                    $item = clone $vo;
                    $key = $item->id;
                    if ($item->optionSellFl == 'Y') {
                        if ($stockFl == 'N') {
                            $item->stockCnt = 999999;
                        } else {
                            // $item->stockCnt = $stockCnt;
                        }
                    } else if ($item->optionSellFl == 'G') {
                        if ($stockFl == 'N') {
                            $item->stockCnt = 999999;
                        } else {
                            $item->stockCnt = $stockCnt;
                        }
                    } else {
                        $item->stockCnt = 0;
                    }
                    $optionsList[$key] = $item;
                    $parentKey = $item->parentId;
                    if (! empty($parentKey) && isset($optionsList[$parentKey])) {
                        /**
                         *
                         * @var OptionTreeVo $parentOption
                         */
                        $parentOption = $optionsList[$parentKey];
                        if ($item->optionViewFl == 'Y') {
                            $parentOption->optionChildren[] = $item;
                        }
                        if ($parentOption->optionTitleFl != 'Y') {
                            $item->optionTitle = '';
                            $item->optionTitleLocale = null;
                        }
                        $parentOption->isRequiredChild = true;
                    }
                }
            }
        }
        $topList = Array();
        foreach ($optionsList as $key => $item) {
            if (empty($item->parentId)) {
                if (! $item->isRequiredChild || count($item->optionChildren) > 0) {
                    $topList[] = $item;
                }
            }
        }
        switch ($optionDisplayFl) {
            case 'C':
                if (count($topList) > 0 && count($topList[0]->optionChildren) > 0) {
                    $scmSubList = $topList;
                    if (count($scmSubList) > 1) {
                        $lastChildOptionChild = $this->GetGoodsInfoOptionTreeChild($scmSubList[0], Array(), 0);
                        for ($i = 1; $i < count($scmSubList); $i ++) {
                            $comSubEndChildList = $this->GetGoodsInfoOptionTreeChild($scmSubList[$i], Array(), 0);
                            if (! empty($comSubEndChildList)) {
                                foreach ($lastChildOptionChild as $lastChild) {
                                    $lastChild->isRequiredChild = true;
                                    $lastChild->optionChildren = $scmSubList[$i]->optionChildren;
                                }
                            }
                            $lastChildOptionChild = $comSubEndChildList;
                        }
                    }
                    $this->GetGoodsInfoOptionTreeChildClone($scmSubList[0], 'top', 0);
                    $this->GetGoodsInfoOptionTreeStockCnt($scmSubList[0]);
                    return $scmSubList[0]->optionChildren;
                }
                break;
            default:
                foreach ($topList as $item) {
                    $this->GetGoodsInfoOptionTreeStockCnt($item);
                }
                return $topList;
        }
    }

    /**
     * 상품 옵션정보의 재고 조정하고 재고 갯수 가져오기
     *
     * @param OptionTreeVo $vo
     * @return integer
     */
    public function GetGoodsInfoOptionTreeStockCnt($vo = null)
    {
        if (! empty($vo->optionChildren)) {
            $stockCnt = 0;
            foreach ($vo->optionChildren as $item) {
                if (! empty($item->optionChildren)) {
                    $stockCnt += $this->GetGoodsInfoOptionTreeStockCnt($item);
                } else {
                    $stockCnt += $item->stockCnt;
                }
            }
            $vo->stockCnt = $stockCnt;
        }
        return $vo->stockCnt;
    }

    /**
     * 상품 옵션을 복제해서 가져오기
     *
     * @param OptionTreeVo $vo
     * @param string $parentId
     */
    public function GetGoodsInfoOptionTreeChildClone(OptionTreeVo $vo, $parentId = '', $depth = 0)
    {
        if (! empty($vo->optionChildren)) {
            $optionChildren = Array();
            foreach ($vo->optionChildren as $item) {
                $cloneItem = clone $item;
                $cloneItem->id = 'CO' . md5($parentId . '_' . $item->id);
                $cloneItem->refId = explode('_', $parentId . '_' . $item->id);
                $optionChildren[] = $cloneItem;
                if (! empty($cloneItem->optionChildren)) {
                    if ($depth < 3) {
                        $this->GetGoodsInfoOptionTreeChildClone($cloneItem, $parentId . '_' . $item->id, $depth + 1);
                    } else {
                        $cloneItem->optionChildren = Array();
                    }
                }
            }
            $vo->optionChildren = $optionChildren;
        }
    }

    /**
     * 옵션의 최하위 옵션 가져오기
     *
     * @param OptionTreeVo $vo
     * @param array $childList
     * @return OptionTreeVo
     */
    public function GetGoodsInfoOptionTreeChild(OptionTreeVo $vo, $childList = Array(), $depth = 0)
    {
        if (! empty($vo->optionChildren)) {
            foreach ($vo->optionChildren as $item) {
                if (! empty($item->optionChildren)) {
                    if ($depth < 3) {
                        $childList = $this->GetGoodsInfoOptionTreeChild($item, $childList, $depth + 1);
                    } else {
                        $item->optionChildren = Array();
                    }
                } else {
                    $childList[] = $item;
                }
            }
        }
        return $childList;
    }

    /**
     * 상품정보 Request 가져오기
     *
     * @param RequestVo $request
     * @param boolean $isCreate
     * @return RequestVo
     */
    public function GetScmGoodsInfoRequest(RequestVo $request = null, $isCreate = false)
    {
        $scmGoodsConfigVo = $this->GetServicePolicy()->GetScmGoodsConfigView();
        $scmReqData = new \stdClass();
        $orgData = $request->GetData();
        if ($scmGoodsConfigVo->emptyApplyFl == 'N') {
            foreach ($orgData as $key => $value) {
                if (! empty($value)) {
                    $scmReqData->$key = $value;
                }
            }
        } else {
            foreach ($orgData as $key => $value) {
                $scmReqData->$key = $value;
            }
        }
        switch ($scmGoodsConfigVo->autoApplyFl) {
            case 'Y':
                break;
            case 'U':
                foreach ($scmGoodsConfigVo->autoUnApplyField as $key) {
                    switch ($key) {
                        case "goodsNm":
                            unset($scmReqData->goodsNm);
                            unset($scmReqData->goodsNmLocale);
                            break;
                        case "shortDescription":
                            unset($scmReqData->shortDescription);
                            unset($scmReqData->shortDescriptionLocale);
                            break;
                        case "eventDescription":
                            unset($scmReqData->eventDescription);
                            unset($scmReqData->eventDescriptionLocale);
                            break;
                        case "goodsDescription":
                            unset($scmReqData->goodsDescription);
                            unset($scmReqData->goodsDescriptionLocale);
                            break;
                        case "externalVideoFl":
                            unset($scmReqData->externalVideoFl);
                            unset($scmReqData->externalVideoUrl);
                            unset($scmReqData->externalVideoVo);
                            unset($scmReqData->externalVideoWidth);
                            unset($scmReqData->externalVideoHeight);
                            break;
                        case "detailInfoDelivery":
                            unset($scmReqData->detailInfoDeliveryFl);
                            unset($scmReqData->detailInfoDelivery);
                            break;
                        case "detailInfoExchange":
                            unset($scmReqData->detailInfoExchangeFl);
                            unset($scmReqData->detailInfoExchange);
                            break;
                        case "detailInfoAs":
                            unset($scmReqData->detailInfoAsFl);
                            unset($scmReqData->detailInfoAs);
                            break;
                        case "deliveryFl":
                            unset($scmReqData->deliveryFl);
                            unset($scmReqData->deliveryOption);
                            break;
                        case "seoTag":
                            unset($scmReqData->seoTagFl);
                            unset($scmReqData->seoTag);
                            break;
                        case "naverOptions":
                            unset($scmReqData->naverFl);
                            unset($scmReqData->naverOptions);
                            break;
                        case "facebookOptions":
                            unset($scmReqData->facebookFl);
                            unset($scmReqData->facebookOptions);
                            break;
                        case "optionFl":
                            unset($scmReqData->optionFl);
                            unset($scmReqData->options);
                            unset($scmReqData->optionsName);
                            break;
                        case "optionsExtFl":
                            unset($scmReqData->optionExtFl);
                            unset($scmReqData->optionsExt);
                            unset($scmReqData->optionsExtName);
                            break;
                        case "optionTextFl":
                            unset($scmReqData->optionsText);
                            unset($scmReqData->optionsTextName);
                            break;
                        case "optionRefFl":
                            unset($scmReqData->optionRefFl);
                            unset($scmReqData->optionsRef);
                            unset($scmReqData->optionsRefName);
                            break;
                        case "refGoodsFl":
                            unset($scmReqData->refGoodsFl);
                            unset($scmReqData->refGoods);
                            break;
                        default:
                            unset($scmReqData->$key);
                            break;
                    }
                }
                break;
            case 'N':
            default:
                if ($isCreate) {
                    $scmReqData->goodsDisplayFl = 'N';
                    $scmReqData->goodsDisplayMobileFl = 'N';
                    $scmReqData->goodsSellFl = 'N';
                    $scmReqData->goodsSellMobileFl = 'N';
                } else {
                    foreach ($scmReqData as $key => $val) {
                        if (! empty($val)) {
                            unset($scmReqData->$key);
                        }
                    }
                }
                break;
        }
        return new RequestVo($scmReqData, false);
    }

    /**
     * 상품정보 생성하기
     *
     * @param RequestVo $request
     * @param boolean $isScmCreate
     * @return GoodsInfoVo
     */
    public function GetGoodsInfoCreate(RequestVo $request = null, $isScmCreate = false, $uid = '')
    {
        if ($isScmCreate) {
            $scmRequest = $this->GetScmGoodsInfoRequest($request, true);
            if (empty($uid)) {
                $vo = $this->GetGoodsInfoVo($this->GetUniqId("G" . date("Ymd")), $scmRequest);
            } else {
                $vo = $this->GetGoodsInfoVo($uid, $scmRequest);
            }
            $this->GetGoodsInfoParse($vo, $scmRequest, null, true);
        } else {
            if (empty($uid)) {
                $vo = $this->GetGoodsInfoVo($this->GetUniqId("G" . date("Ymd")), $request);
            } else {
                $vo = $this->GetGoodsInfoVo($uid, $request);
            }
            $this->GetGoodsInfoParse($vo, $request, null);
        }
        if ($isScmCreate) {
            $vo->sellerMemNo = $this->loginInfo->scmNo;
        }
        if ($this->GetGoodsInfoDao()->SetCreate($vo)) {
            $this->GetGoodsMallSynk($vo, 'create');
            $oldView = new GoodsInfoVo();
            $oldView->mallId = $vo->mallId;
            $oldView->goodsCode = $vo->goodsCode;
            $this->UnsetGoodsInfoCache($oldView, $vo, $isScmCreate);
            $vo->regDate = $vo->modDate = $this->getDateNow();
            return $vo;
        } else {
            parent::GetException(KbmException::DATA_ERROR_CREATE);
        }
    }

    /**
     * 상품을 Excel 파일로부터 생성하기
     *
     * @param RequestVo $request
     * @return GoodsInfoVo|boolean
     */
    public function GetGoodsInfoCreateExcel(RequestVo $request = null)
    {
        try {
            $goodsCode = $request->goodsCode;
            if (! empty($goodsCode) && strpos($goodsCode, 'xxxxx') === false) {
                try {
                    $this->GetGoodsInfoView($goodsCode);
                } catch (Exception $ex) {
                    $goodsCode = '';
                }
            } else {
                $goodsCode = '';
            }
            if ($this->IsScmAdmin()) {
                $scmService = $this->GetServiceScm();

                if (! empty($goodsCode)) {
                    return $scmService->GetGoodsInfoUpdate($goodsCode, $request, $this->loginInfo);
                } else {
                    return $scmService->GetGoodsInfoCreate($request, $this->loginInfo);
                }
            } else {
                if (! empty($goodsCode)) {
                    return $this->GetGoodsInfoUpdate($goodsCode, $request);
                } else {
                    return $this->GetGoodsInfoCreate($request);
                }
            }
        } catch (Exception $ex) {
            return false;
        }
    }

    /**
     * 상품 정보 파싱하기
     *
     * @param RequestVo $request
     * @param string $key
     * @return OptionTreeVo[]
     */
    public function GetOptionTreeVoItemArray(RequestVo $request, $key = '')
    {
        return $request->GetItemArray($key, new OptionTreeVo());
    }

    /**
     * 상품 정보 파싱하기
     *
     * @param GoodsInfoVo $vo
     * @param RequestVo $request
     * @param GoodsInfoVo $oldView
     * @return GoodsInfoVo
     */
    public function GetOptionTreeVoItemArrayFill(RequestVo $request, $key = '', $optionType = 'OPTION')
    {
        $optionsTree = $this->GetOptionTreeVoItemArray($request, $key);
        switch ($optionType) {
            case 'ESS':
            case 'TEXT':
            case 'EXT':
            case 'REF':
                foreach ($optionsTree as $vo) {
                    switch ($vo->optionPriceFl) {
                        case 'G':
                            $goodsPriceGroupData = $vo->optionPriceGroup;
                            $goodsPriceGroup = new \stdClass();
                            if (! empty($goodsPriceGroupData)) {
                                foreach ($goodsPriceGroupData as $key => $value) {
                                    if (! empty($key)) {
                                        $goodsPriceGroup->$key = intval($value);
                                    }
                                }
                            }
                            $vo->optionPriceGroup = $goodsPriceGroup;
                            break;
                        case 'N':
                        case 'Y':
                            $vo->optionPriceGroup = null;
                            break;
                        default:
                            $vo->optionPriceFl = 'Y';
                            $vo->optionPriceGroup = null;
                            break;
                    }
                    switch ($optionType) {
                        case 'TEXT':
                            $vo->optionTextUse = 'Y';
                            $vo->optionTextRequiredFl = 'Y';
                            break;
                    }
                    switch ($vo->optionTextUse) {
                        case 'Y':
                            if (empty($vo->optionTextDuplCart)) {
                                $vo->optionTextDuplCart = 'N';
                            }
                            break;
                        case 'N':
                            $vo->optionTextRequiredFl = 'N';
                            $vo->optionTextDuplCart = 'N';
                            break;
                        default:
                            $vo->optionTextUse = 'N';
                            $vo->optionTextRequiredFl = 'N';
                            $vo->optionTextDuplCart = 'N';
                            break;
                    }

                    if ($vo->optionTextUse == 'Y') {
                        if ($vo->optionMaxlen < 0) {
                            $vo->optionMaxlen = 0;
                        }
                        if ($vo->optionMaxlen > 0 && $vo->optionMinlen > $vo->optionMaxlen) {
                            $vo->optionMinlen = 0;
                        }
                    } else {
                        $vo->optionTextPlaceholder = '';
                        $vo->optionMinlen = 0;
                        $vo->optionMaxlen = 0;
                    }

                    if (empty($vo->optionTextRequiredFl)) {
                        $vo->optionTextRequiredFl = 'N';
                    }
                }
                break;
        }
        return $optionsTree;
    }

    /**
     * 상품 정보 파싱하기
     *
     * @param GoodsInfoVo $vo
     * @param RequestVo $request
     * @param GoodsInfoVo $oldView
     * @return GoodsInfoVo
     */
    public function GetGoodsInfoPreParse(GoodsInfoVo $vo, RequestVo $request, GoodsInfoVo $oldView = null)
    {
        $vo->goodsAttr = Array();
        $vo->goodsMallSearchOption = Array();
        if ($request->hasKey('goodsCategory')) {
            $vo->goodsCategory = $request->GetItemArray('goodsCategory');
            $vo->goodsCategoryMst = ! empty($vo->goodsCategory) ? $vo->goodsCategory[0] : '';
        }
        if ($request->hasKey('shortDescriptionLocale')) {
            $vo->shortDescriptionLocale = $this->GetLocaleTextVoRequest($request, 'shortDescriptionLocale');
            if (empty($vo->shortDescription) && ! empty($vo->shortDescriptionLocale)) {
                foreach ($vo->shortDescriptionLocale as $shortDescription) {
                    if (! empty($shortDescription)) {
                        $vo->shortDescription = $shortDescription;
                        break;
                    }
                }
            }
        }
        if ($request->hasKey('commission')) {
            $vo->commission = floatval($request->commission);
        }
        if ($request->hasKey('taxPercent')) {
            $vo->taxPercent = floatval($request->taxPercent);
        }
        if ($request->hasKey('goodsNmLocale')) {
            $vo->goodsNmLocale = $this->GetLocaleTextVoRequest($request, 'goodsNmLocale');
        }
        if ($request->hasKey('naverOptions')) {
            $vo->naverOptions = $request->GetFill(new RefShopVo(), 'naverOptions');
        }

        if ($request->hasKey('hscodeLocale')) {
            $vo->hscodeLocale = $request->GetItemArray('hscodeLocale');
            if (empty($vo->hscode)) {
                foreach ($vo->hscodeLocale as $hsCode) {
                    if (! empty($hsCode)) {
                        $vo->hscode = $hsCode;
                        break;
                    }
                }
            }
        }
        if ($request->hasKey('seoTag')) {
            $vo->seoTag = $request->GetFill(new SeoTagVo(), 'seoTag');
        }
        if ($request->hasKey('goodsImageMaster')) {
            $vo->goodsImageMaster = $this->GetUploadFile($request->goodsImageMaster, ! empty($oldView) ? $oldView->goodsImageMaster : '', 'goods');
        }
        if ($request->hasKey('optionFile')) {
            $vo->optionFile = $this->GetUploadFile($request->optionFile, ! empty($oldView) ? $oldView->optionFile : '', 'goods');
        }
        if ($request->hasKey('goodsImageAdded')) {
            $vo->goodsImageAdded = $this->GetUploadFiles($request->goodsImageAdded, ! empty($oldView) ? $oldView->goodsImageAdded : '', 'goods');
        }
        if ($request->hasKey('eventDescriptionLocale')) {
            $vo->eventDescriptionLocale = $this->GetLocaleTextVoRequest($request, 'eventDescriptionLocale');
        }
        if ($request->hasKey('goodsDescription')) {
            $vo->goodsDescription = $this->GetEditorParse($request->goodsDescription, ! empty($oldView) ? $oldView->goodsDescription : '', 'goods');
        }
        if ($request->hasKey('goodsDescriptionLocale')) {
            $vo->goodsDescriptionLocale = $this->GetLocaleTextVoRequest($request, 'goodsDescriptionLocale');
            $vo->goodsDescriptionLocale = $this->GetLocaleTextParse($vo->goodsDescriptionLocale, $this->GetChildValue($oldView, 'goodsDescriptionLocale', null), 'goods');
        }
        if ($request->hasKey('goodsDescriptionMobile')) {
            $vo->goodsDescriptionMobile = $this->GetEditorParse($request->goodsDescriptionMobile, ! empty($oldView) ? $oldView->goodsDescriptionMobile : '', 'goods');
        }
        if ($request->hasKey('goodsDescriptionMobileLocale')) {
            $vo->goodsDescriptionMobileLocale = $this->GetLocaleTextVoRequest($request, 'goodsDescriptionMobileLocale');
            $vo->goodsDescriptionMobileLocale = $this->GetLocaleTextParse($vo->goodsDescriptionMobileLocale, $this->GetChildValue($oldView, 'goodsDescriptionMobileLocale', null), 'goods');
        }
        if ($request->hasKey('detailInfoDelivery')) {
            $vo->detailInfoDelivery = $this->GetEditorParse($request->detailInfoDelivery, ! empty($oldView) ? $oldView->detailInfoDelivery : '', 'goods');
        }
        if ($request->hasKey('detailInfoAs')) {
            $vo->detailInfoAs = $this->GetEditorParse($request->detailInfoAs, ! empty($oldView) ? $oldView->detailInfoAs : '', 'goods');
        }
        if ($request->hasKey('detailInfoRefund')) {
            $vo->detailInfoRefund = $this->GetEditorParse($request->detailInfoRefund, ! empty($oldView) ? $oldView->detailInfoRefund : '', 'goods');
        }
        if ($request->hasKey('detailInfoExchange')) {
            $vo->detailInfoExchange = $this->GetEditorParse($request->detailInfoExchange, ! empty($oldView) ? $oldView->detailInfoExchange : '', 'goods');
        }
        if ($request->hasKey('addInfo')) {
            $titleConents = new TitleContentVo();
            $titleConents->titleLocale = new LocaleTextVo();
            $titleConents->contentsLocale = new LocaleTextVo();
            $vo->addInfo = $request->GetItemArray('addInfo', $titleConents);
        }
        if ($request->hasKey('addMustInfo')) {
            $titleConents = new TitleContentVo();
            $titleConents->titleLocale = new LocaleTextVo();
            $titleConents->contentsLocale = new LocaleTextVo();
            $vo->addMustInfo = $request->GetItemArray('addMustInfo', $titleConents);
        }
        if ($request->hasKey('options')) {
            $vo->options = $this->GetOptionTreeVoItemArrayFill($request, 'options', 'ESS');
        }
        if ($request->hasKey('optionsExt')) {
            $vo->optionsExt = $this->GetOptionTreeVoItemArrayFill($request, 'optionsExt', 'EXT');
        }
        if ($request->hasKey('optionsText')) {
            $vo->optionsText = $this->GetOptionTreeVoItemArrayFill($request, 'optionsText', 'TEXT');
        }
        if ($request->hasKey('optionsRef')) {
            $vo->optionsRef = $this->GetOptionTreeVoItemArrayFill($request, 'optionsRef', 'REF');
        }
        if ($request->hasKey('goodsPriceGroup')) {
            $goodsPriceGroupData = $request->GetRequestVo("goodsPriceGroup")->GetData();
            $goodsPriceGroup = new \stdClass();
            foreach ($goodsPriceGroupData as $key => $value) {
                if (! empty($key)) {
                    $goodsPriceGroup->$key = intval($value);
                }
            }
            $vo->goodsPriceGroup = $goodsPriceGroup;
        }
        if ($request->hasKey('deliveryPackingPrice')) {
            $vo->deliveryPackingPrice = $request->GetFill(new DeliveryPackingPriceVo(), 'deliveryPackingPrice');
        }
        $vo->optionsName = $this->GetOptionTitleVo('optionsName', $request, $vo->optionsName);
        $vo->optionsExtName = $this->GetOptionTitleVo('optionsExtName', $request, $vo->optionsExtName);
        $vo->optionsTextName = $this->GetOptionTitleVo('optionsTextName', $request, $vo->optionsTextName);
        $vo->optionsRefName = $this->GetOptionTitleVo('optionsRefName', $request, $vo->optionsRefName);
        return $vo;
    }

    /**
     * 옵션 타이틀 VO 가져오기
     *
     * @param string $formName
     * @param RequestVo $request
     * @param CodeVo $codeVo
     * @return CodeVo
     */
    public function GetOptionTitleVo($formName = '', RequestVo $request, $codeVo = null)
    {
        if ($request->hasKey($formName)) {
            if (empty($codeVo) || ! ($codeVo instanceof CodeVo)) {
                $codeVo = new CodeVo();
            }
            $request->GetFill($codeVo, $formName);
            if (! $codeVo->isTranslated) {
                $codeVo->textLocale = null;
            } else {
                if (isset($codeVo->textLocale) && ! empty($codeVo->textLocale)) {
                    $textLocale = new LocaleTextVo();
                    $textLocale->ko = $codeVo->textLocale->ko;
                    $textLocale->en = $codeVo->textLocale->en;
                    $textLocale->cn = $codeVo->textLocale->cn;
                    $textLocale->jp = $codeVo->textLocale->jp;
                    $codeVo->textLocale = $textLocale;
                }
            }
        }
        return $codeVo;
    }

    /**
     * 상품 정보 파싱하기
     *
     * @param GoodsInfoVo $vo
     * @param RequestVo $request
     * @param GoodsInfoVo $oldView
     * @return GoodsInfoVo
     */
    public function GetGoodsInfoAfterParse(GoodsInfoVo $vo, RequestVo $request, GoodsInfoVo $oldView = null, $isScmCreate = false)
    {
        if (! empty($vo->options)) {
            $vo->options = $this->GetGoodsInfoOptionParse($vo->options);
        }
        if (! empty($vo->optionsExt)) {
            $vo->optionsExt = $this->GetGoodsInfoOptionParse($vo->optionsExt);
        }
        if (! empty($vo->optionsText)) {
            $vo->optionsText = $this->GetGoodsInfoOptionParse($vo->optionsText);
        }
        if (! empty($vo->optionsRef)) {
            $vo->optionsRef = $this->GetGoodsInfoOptionParse($vo->optionsRef);
        }
        if (empty($vo->externalVideoUrl)) {
            $vo->externalVideoFl = 'N';
        }
        if ($vo->useCommission == 'N' && ! empty($vo->sellerMemNo)) {
            try {
                $scmInfo = $this->GetServiceScm()->GetScmInfoView($vo->sellerMemNo);
                $vo->commission = $scmInfo->scmCommission;
            } catch (\Exception $ex) {
                $vo->useCommission = 'Y';
            }
        }
        if (empty($vo->goodsCategoryMst) && ! empty($vo->goodsCategory)) {
            $vo->goodsCategoryMst = $vo->goodsCategory[0];
        }

        $goodsSearchWord = $this->GetLocaleTextKeyword($vo->goodsNm, $vo->goodsNmLocale);
        $goodsSearchWord .= ' ' . $vo->goodsSearchWord;
        if (! empty($vo->goodsCategoryMst)) {
            $goodsSearchWord .= ' ' . $this->GetCategoryNameKeyword($vo->goodsCategoryMst);
        }
        $vo->goodsSearchWord = $this->GetSearchKeywordUniq($goodsSearchWord);
        if ($vo->externalVideoFl == 'Y' && ! empty($vo->externalVideoUrl)) {
            $vo->goodsMallSearchOption[] = "HASEXV";
        }
        if (! empty($vo->goodsState)) {
            $vo->goodsMallSearchOption[] = "GS_" . $vo->goodsState;
        }
        if (! empty($vo->goodsMallSearchOption)) {
            $goodsMallSearchOption = Array();
            foreach ($vo->goodsMallSearchOption as $searchOption) {
                if (! empty($searchOption) && ! in_array($searchOption, $goodsMallSearchOption)) {
                    $goodsMallSearchOption[] = $searchOption;
                }
            }
            $vo->goodsMallSearchOption = $goodsMallSearchOption;
        } else {
            $vo->goodsMallSearchOption = Array();
        }
        if (! empty($vo->goodsSearchOption)) {
            $goodsSearchOption = Array();
            foreach ($vo->goodsSearchOption as $searchOption) {
                if (! empty($searchOption) && ! in_array($searchOption, $goodsSearchOption)) {
                    $goodsSearchOption[] = $searchOption;
                }
            }
            $vo->goodsSearchOption = $goodsSearchOption;
        } else {
            $vo->goodsSearchOption = Array();
        }
        $vo->goodsAttr = Array();
        $goodsAttr = Array();
        foreach ($vo->goodsSearchOption as $searchOption) {
            if (! empty($searchOption) && ! in_array($searchOption, $goodsAttr)) {
                $goodsAttr[] = $searchOption;
            }
        }
        foreach ($vo->goodsMallSearchOption as $searchOption) {
            if (! empty($searchOption) && ! in_array($searchOption, $goodsAttr)) {
                $goodsAttr[] = $searchOption;
            }
        }
        $vo->goodsAttr = $goodsAttr;
        $this->GetGoodsInfoVersionCheck($vo);
        if ($isScmCreate) {
            $scmApplyCnt = 0;
            foreach ($vo as $key => $value) {
                switch ($key) {
                    case 'mallId':
                    case 'goodsCode':
                    case 'regDate':
                    case 'modDate':
                    case 'scmRegDate':
                    case 'scmModDate':
                    case 'scmUnapplyCnt':
                    case 'scmApplyCnt':
                    case 'goodsAttr':
                        break;
                    default:
                        if (! $this->isObjectEquals($value, isset($oldView->$key) ? $oldView->$key : null)) {
                            $scmApplyCnt ++;
                        }
                        break;
                }
            }
            $vo->scmApplyCnt = $scmApplyCnt;
        }
        return $vo;
    }

    /**
     * 상품 정보 파싱하기
     *
     * @param GoodsInfoVo $vo
     * @param RequestVo $request
     * @param GoodsInfoVo $oldView
     * @return GoodsInfoVo
     */
    public function GetGoodsInfoParse(GoodsInfoVo $vo, RequestVo $request, GoodsInfoVo $oldView = null, $isScmCreate = false)
    {
        $vo = $this->GetGoodsInfoPreParse($vo, $request, $oldView);
        $vo = $this->GetGoodsInfoAfterParse($vo, $request, $oldView, $isScmCreate);
        return $vo;
    }

    /**
     * 상품 옵션 정보 파싱하기
     *
     * @param OptionTreeVo[] $voList
     * @param OptionTreeVo[] $oldVoList
     * @return OptionTreeVo[]
     */
    public function GetGoodsInfoOptionParse($voList = Array(), $oldVoList = Array())
    {
        foreach ($voList as $vo) {
            $vo->mallId = $this->mallId;
            if (empty($vo->id)) {
                $vo->id = uniqid("OPT");
            }
            if (empty($vo->optionColor) || ! preg_match('/^#[0-9a-fA-F]{6}$/', $vo->optionColor)) {
                $vo->optionColor = '#000000';
            }
            if (empty($vo->fontColor) || ! preg_match('/^#[0-9a-fA-F]{6}$/', $vo->fontColor)) {
                $vo->fontColor = '#ffffff';
            }
            if (! empty($vo->optionImage) && $this->IsUploadImage($vo->optionImage)) {
                $vo->optionImage = $this->GetUploadFile($vo->optionImage, '', 'options');
            }
            if (empty($vo->optionTitleFl) || ! preg_match('/^Y|N$/', $vo->optionTitleFl)) {
                $vo->optionTitleFl = 'N';
            }
            if (empty($vo->optionViewFl) || ! preg_match('/^Y|N$/', $vo->optionViewFl)) {
                $vo->optionViewFl = 'Y';
            }
            if (empty($vo->optionSellFl) || ! preg_match('/^Y|N|G$/', $vo->optionSellFl)) {
                $vo->optionSellFl = 'Y';
            }
            if (empty($vo->optionRequiredFl) || ! preg_match('/^Y|N$/', $vo->optionRequiredFl)) {
                $vo->optionRequiredFl = 'N';
            }
            if ($vo->optionTitleFl == 'Y' && ! empty($vo->optionTitle) && is_object($vo->optionTitle)) {
                $optionTitle = new CodeVo();
                $optionTitle->value = $vo->optionTitle->value;
                $optionTitle->text = $vo->optionTitle->text;
                $optionTitle->isTranslated = $vo->optionTitle->isTranslated;
                if (! $optionTitle->isTranslated) {
                    $optionTitle->textLocale = null;
                } else {
                    $textLocale = new LocaleTextVo();
                    if (isset($vo->optionTitle->textLocale)) {
                        $textLocale->ko = $vo->optionTitle->textLocale->ko;
                        $textLocale->en = $vo->optionTitle->textLocale->ko;
                        $textLocale->cn = $vo->optionTitle->textLocale->cn;
                        $textLocale->jp = $vo->optionTitle->textLocale->jp;
                    }
                    $optionTitle->textLocale = $textLocale;
                }
                $vo->optionTitle = $optionTitle;
            } else {
                $vo->optionTitleFl = 'N';
                $vo->optionTitle = null;
            }
            $vo->optionCostPrice = floatval($vo->optionCostPrice);
            $vo->optionPrice = floatval($vo->optionPrice);
            $vo->stockCnt = intval($vo->stockCnt);
            $vo->optionCostPrice = floatval($vo->optionCostPrice);
            $vo->optionMaxlen = intval($vo->optionMaxlen);
        }
        return $voList;
    }

    /**
     * 상품 정보 업데이트 하기
     *
     * @param string $uid
     * @param RequestVo $request
     * @return GoodsInfoVo
     */
    public function GetGoodsInfoUpdate($uid = '', RequestVo $request = null, $isScmCreate = false)
    {
        if ($uid == 'preview') {
            $uid = 'TMP' . str_pad(rand(0, 1000), 5, '0', STR_PAD_LEFT);
            try {
                $oldView = $this->GetGoodsInfoView($uid);
            } catch (\Exception $ex) {
                $tmpRequestVo = new RequestVo($request->GetData(), false);
                $tmpGoodsName = $tmpRequestVo->goodsNm;
                $tmpRequestVo->goodsNm = $uid;
                $vo = $this->GetGoodsInfoCreate($tmpRequestVo, false, $uid);
                $uid = $vo->goodsCode;
                $request->goodsNm = $tmpGoodsName;
            }
        }
        $oldView = $this->GetGoodsInfoView($uid);
        if ($isScmCreate) {
            $scmRequest = $this->GetScmGoodsInfoRequest($request, false);
            $vo = $this->GetGoodsInfoVo($uid, $scmRequest, clone $oldView);
            if (isset($scmRequest->goodsPrice) && $vo->goodsPrice != $oldView->goodsPrice) {
                $vo->goodsPriceFl = 'Y';
            }
            if (isset($scmRequest->options) && json_encode($vo->options) != json_encode($oldView->options)) {
                foreach ($vo->options as $opt) {
                    $opt->optionPriceFl = 'N';
                }
            }
            if (isset($scmRequest->optionsExt) && json_encode($vo->optionsExt) != json_encode($oldView->optionsExt)) {
                foreach ($vo->optionsExt as $opt) {
                    $opt->optionPriceFl = 'N';
                }
            }
            if (isset($scmRequest->optionsText) && json_encode($vo->optionsText) != json_encode($oldView->optionsText)) {
                foreach ($vo->optionsText as $opt) {
                    $opt->optionPriceFl = 'N';
                }
            }
            if (isset($scmRequest->optionsRef) && json_encode($vo->optionsRef) != json_encode($oldView->optionsRef)) {
                foreach ($vo->optionsRef as $opt) {
                    $opt->optionPriceFl = 'N';
                }
            }
            $this->GetGoodsInfoParse($vo, $scmRequest, $oldView, true);
        } else {
            $cloneView = clone $oldView;
            if ($request->hasKey('salesOpenDate')) {
                $cloneView->salesOpenDate = '0000-00-00 00:00:00';
            }
            if ($request->hasKey('salesCloseDate')) {
                $cloneView->salesCloseDate = '0000-00-00 00:00:00';
            }
            if ($request->hasKey('goodsOpenDt')) {
                $cloneView->goodsOpenDt = '0000-00-00 00:00:00';
            }
            if ($request->hasKey('goodsCloseDt')) {
                $cloneView->goodsCloseDt = '0000-00-00 00:00:00';
            }
            $vo = $this->GetGoodsInfoVo($uid, $request, $cloneView);
            $this->GetGoodsInfoParse($vo, $request, $oldView);
        }
        
        if ($this->GetGoodsInfoDao()->SetUpdate($vo)) {
            $this->GetGoodsMallSynk($vo, 'update');
            $this->UnsetGoodsInfoCache($oldView, $vo, $isScmCreate);
            $vo->modDate = $this->getDateNow();
            return $vo;
        } else {
            parent::GetException(KbmException::DATA_ERROR_UPDATE);
        }
    }

    /**
     * 상품 정보 복제하기
     *
     * @param string $uid
     * @param RequestVo $request
     * @return GoodsInfoVo
     */
    public function GetGoodsInfoCopy($uid = '', RequestVo $request = null)
    {
        $vo = $this->GetGoodsInfoClone($this->GetGoodsInfoView($uid));
        $reqCopy = new RequestVo($vo);
        return $this->GetGoodsInfoCreate($reqCopy);
    }

    /**
     * 상품정보 PDF 파일 생성하기
     *
     * @param RequestVo $request
     * @return PdfVo
     */
    public function GetGoodsInfoListPdf(RequestVo $request = null)
    {
        $downloadFormVo = $this->GetDownloadFormVo($request, 'goodsCodes', true);
        $data = $this->GetGoodsInfoList($downloadFormVo->searchRequest);
        $pdfVo = new PdfVo($downloadFormVo, $data);
        $pdfVo->SetFontSize(10);
        if (! empty($downloadFormVo->password)) {
            $pdfVo->SetPassword($downloadFormVo->password);
        }
        return $pdfVo;
    }

    /**
     * 유효한 상품 목록 가져오기
     *
     * @param GoodsInfoVo $item
     * @param GoodsInfoVo $oldItem
     * @return GoodsInfoVo
     */
    public function GetGoodsInfoEquals($item, $oldItem)
    {
        foreach ($item as $key => $val) {
            if ($val == '"') {
                $item->$key = $oldItem->$key;
            } else if (! empty($val)) {
                if (is_object($val)) {
                    $item->$key = $this->GetGoodsInfoEquals($item->$key, $oldItem->$key);
                } else {
                    $oldItem->$key = $val;
                }
            }
        }
        return $item;
    }

    /**
     * 유효한 상품 목록 가져오기
     *
     * @param GoodsInfoVo[] $data
     * @return GoodsInfoVo[]
     */
    public function GetGoodsInfoListValid($data = Array())
    {
        $validData = Array();
        $goodsCodeMap = Array();
        $lastGoodsInfo = new GoodsInfoVo();
        foreach ($data as $item) {
            $goodsCode = strtolower($item->goodsCode);
            $goodsNm = strtolower($item->goodsNm);
            if ($goodsCode != 'goodscode' && $goodsNm != 'goodsnm') {
                $this->GetGoodsInfoEquals($item, $lastGoodsInfo);
            }
        }
        foreach ($data as $seqn => $item) {
            $goodsCode = strtolower($item->goodsCode);
            if ($goodsCode != 'goodscode' && ! empty($item->goodsNm)) {
                $goodsCategory = Array();
                if (! empty($item->goodsCategoryMst) && $item->goodsCategoryMst != '"') {
                    $goodsCategory[] = $item->goodsCategoryMst;
                }
                $item->goodsCategoryMst = '';
                if (is_array($item->goodsCategory)) {
                    foreach ($item->goodsCategory as $category) {
                        if (! empty($category) && $category != '"' && ! in_array($category, $goodsCategory)) {
                            $goodsCategory[] = $category;
                        }
                    }
                }
                $item->goodsCategory = $goodsCategory;
                $item->options = $this->GetGoodsInfoExcelOptionsParse($item->options, $item->optionsTree);
                $item->optionsText = $this->GetGoodsInfoExcelOptionsParse($item->optionsText, $item->optionsTextTree);
                $item->optionsExt = $this->GetGoodsInfoExcelOptionsParse($item->optionsExt, $item->optionsExtTree);
                $item->optionsRef = $this->GetGoodsInfoExcelOptionsParse($item->optionsRef, $item->optionsRefTree);
                $item->commission = intval($item->commission);
                $item->fixedPrice = intval($item->fixedPrice);
                $item->costPrice = intval($item->costPrice);
                $item->goodsPrice = intval($item->goodsPrice);
                $item->goodsNm = trim($item->goodsNm);
                $lastGoodsCode = md5(strtolower(preg_replace('#[ \(\)]#', '', $item->goodsNm)));
                if (! empty($lastGoodsCode)) {
                    if (! isset($goodsCodeMap[$lastGoodsCode])) {
                        $goodsCodeMap[$lastGoodsCode] = Array();
                    }
                    $goodsCodeMap[$lastGoodsCode][] = $item;
                }
            }
        }
        foreach ($goodsCodeMap as $goodsList) {
            $lastGoodsInfo = null;
            $options = Array();
            $optionsExt = Array();
            $optionsText = Array();
            $optionsRef = Array();
            $goodsImageAdded = Array();
            $goodsDescription = Array();
            $goodsCategory = Array();
            foreach ($goodsList as $seqn => $goodsInfo) {
                if ($seqn == 0) {
                    $validData[] = $goodsInfo;
                    $lastGoodsInfo = $goodsInfo;
                    if (! empty($goodsInfo->goodsCategoryMst)) {
                        $goodsCategory[] = $goodsInfo->goodsCategoryMst;
                    }
                    if (! empty($goodsInfo->goodsCategory) && is_array($goodsInfo->goodsCategory)) {
                        foreach ($goodsInfo->goodsCategory as $cateId) {
                            if (! empty($cateId) && $cateId != '"' && ! in_array($cateId, $goodsCategory)) {
                                $goodsCategory[] = $cateId;
                            }
                        }
                    }
                    if (is_array($goodsInfo->options)) {
                        $options = $goodsInfo->options;
                    }
                    if (is_array($goodsInfo->optionsExt)) {
                        $optionsExt = $goodsInfo->optionsExt;
                    }
                    if (is_array($goodsInfo->optionsText)) {
                        $optionsText = $goodsInfo->optionsText;
                    }
                    if (is_array($goodsInfo->optionsRef)) {
                        $optionsRef = $goodsInfo->optionsRef;
                    }
                    if (! empty($goodsInfo->goodsImageAdded)) {
                        $goodsImageAdded = explode("@!@", $goodsInfo->goodsImageAdded);
                    }
                    if (! empty($goodsInfo->goodsDescription)) {
                        $goodsDescription[md5($goodsInfo->goodsDescription)] = $goodsInfo->goodsDescription;
                    }
                } else if (! empty($lastGoodsInfo)) {
                    if (! empty($goodsInfo->goodsCategoryMst) && ! in_array($goodsInfo->goodsCategoryMst, $goodsCategory)) {
                        $goodsCategory[] = $goodsInfo->goodsCategoryMst;
                    }
                    if (! empty($goodsInfo->goodsCategory) && is_array($goodsInfo->goodsCategory)) {
                        foreach ($goodsInfo->goodsCategory as $cateId) {
                            if (! empty($cateId) && $cateId != '"' && ! in_array($cateId, $goodsCategory)) {
                                $goodsCategory[] = $cateId;
                            }
                        }
                    }
                    if (is_array($goodsInfo->options)) {
                        $options = array_merge($options, $goodsInfo->options);
                    }
                    if (is_array($goodsInfo->optionsExt)) {
                        $optionsExt = array_merge($optionsExt, $goodsInfo->optionsExt);
                    }
                    if (is_array($goodsInfo->optionsText)) {
                        $optionsText = array_merge($optionsText, $goodsInfo->optionsText);
                    }
                    if (is_array($goodsInfo->optionsRef)) {
                        $optionsRef = array_merge($optionsRef, $goodsInfo->optionsRef);
                    }
                    if (! empty($goodsInfo->goodsImageMaster) && $goodsInfo->goodsImageMaster != '"') {
                        if (empty($lastGoodsInfo->goodsImageMaster)) {
                            $lastGoodsInfo->goodsImageMaster = $goodsInfo->goodsImageMaster;
                        } else if (! in_array($goodsInfo->goodsImageMaster, $goodsImageAdded)) {
                            $goodsImageAdded[] = $goodsInfo->goodsImageMaster;
                        }
                    }
                    if (! empty($goodsInfo->goodsImageAdded)) {
                        foreach (explode("@!@", $goodsInfo->goodsImageAdded) as $imgInfo) {
                            if (! empty($imgInfo) && $imgInfo != '"' && ! in_array($imgInfo, $goodsImageAdded)) {
                                $goodsImageAdded[] = $imgInfo;
                            }
                        }
                        $options = array_merge($options, $goodsInfo->options);
                    }
                    if (! empty($goodsInfo->goodsDescription)) {
                        $goodsDescription[md5($goodsInfo->goodsDescription)] = $goodsInfo->goodsDescription;
                    }
                }
            }
            if (! empty($lastGoodsInfo)) {
                if (! empty($goodsCategory)) {
                    $lastGoodsInfo->goodsCategoryMst = $goodsCategory[0];
                    $lastGoodsInfo->goodsCategory = $goodsCategory;
                }
                if (! empty($goodsDescription)) {
                    $lastGoodsInfo->goodsDescription = implode("\n<br/>\n", $goodsDescription);
                }
                if (! empty($lastGoodsInfo->goodsDescription)) {
                    $lastGoodsInfo->goodsDescription = $this->GetEditorParse($lastGoodsInfo->goodsDescription, '', 'junk');
                }
                if (! empty($goodsImageAdded)) {
                    if (empty($lastGoodsInfo->goodsImageMaster) || $lastGoodsInfo->goodsImageMaster == '"') {
                        $lastGoodsInfo->goodsImageMaster = array_shift($goodsImageAdded);
                    }
                    $lastGoodsInfo->goodsImageAdded = implode('@!@', $goodsImageAdded);
                }
                if (! empty($lastGoodsInfo->goodsImageMaster)) {
                    $lastGoodsInfo->goodsImageMaster = $this->GetUploadFiles($lastGoodsInfo->goodsImageMaster, '', 'junk');
                }
                if (! empty($lastGoodsInfo->goodsImageAdded)) {
                    $lastGoodsInfo->goodsImageAdded = $this->GetUploadFiles($lastGoodsInfo->goodsImageAdded, '', 'junk');
                }
                $lastGoodsInfo->options = $this->GetGoodsInfoOptionTreeVoValid($options);
                $lastGoodsInfo->optionsExt = $this->GetGoodsInfoOptionTreeVoValid($optionsExt);
                $lastGoodsInfo->optionsText = $this->GetGoodsInfoOptionTreeVoValid($optionsText);
                $lastGoodsInfo->optionsRef = $this->GetGoodsInfoOptionTreeVoValid($optionsRef);
                if (count($lastGoodsInfo->options) > 0) {
                    $lastGoodsInfo->optionFl = 'Y';
                } else {
                    $lastGoodsInfo->optionFl = 'N';
                }
                if (count($lastGoodsInfo->optionsExt) > 0) {
                    $lastGoodsInfo->optionsExtFl = 'Y';
                } else {
                    $lastGoodsInfo->optionsExtFl = 'N';
                }
                if (count($lastGoodsInfo->optionsText) > 0) {
                    $lastGoodsInfo->optionTextFl = 'Y';
                } else {
                    $lastGoodsInfo->optionTextFl = 'N';
                }
                if (count($lastGoodsInfo->optionsRef) > 0) {
                    $lastGoodsInfo->optionRefFl = 'Y';
                } else {
                    $lastGoodsInfo->optionRefFl = 'N';
                }
                if ($lastGoodsInfo->optionFl == 'Y') {
                    $lastGoodsInfo->optionsTree = $this->GetGoodsInfoOptionTree($lastGoodsInfo->options, $lastGoodsInfo->optionDisplayFl, $lastGoodsInfo->stockFl, $lastGoodsInfo->stockCnt);
                } else {
                    $lastGoodsInfo->optionsTree = Array();
                }
                if (empty($lastGoodsInfo->optionsTree)) {
                    $lastGoodsInfo->optionFl = 'N';
                }
                if ($lastGoodsInfo->optionsExtFl == 'Y') {
                    $lastGoodsInfo->optionsExtTree = $this->GetGoodsInfoOptionTree($lastGoodsInfo->optionsExt, '', $lastGoodsInfo->stockFl, $lastGoodsInfo->stockCnt);
                } else {
                    $lastGoodsInfo->optionsExtTree = Array();
                }
                if (empty($lastGoodsInfo->optionsExtTree)) {
                    $lastGoodsInfo->optionsExtFl = 'N';
                }
                if ($lastGoodsInfo->optionTextFl == 'Y') {
                    $lastGoodsInfo->optionsTextTree = $this->GetGoodsInfoOptionTree($lastGoodsInfo->optionsText, '', $lastGoodsInfo->stockFl, $lastGoodsInfo->stockCnt);
                } else {
                    $lastGoodsInfo->optionsTextTree = Array();
                }
                if (empty($lastGoodsInfo->optionsTextTree)) {
                    $lastGoodsInfo->optionTextFl = 'N';
                }
                if ($lastGoodsInfo->optionRefFl == 'Y') {
                    $lastGoodsInfo->optionsRefTree = $this->GetGoodsInfoOptionTree($lastGoodsInfo->optionsRef, '', $lastGoodsInfo->stockFl, $lastGoodsInfo->stockCnt);
                } else {
                    $lastGoodsInfo->optionsRefTree = Array();
                }
                if (empty($lastGoodsInfo->optionsRefTree)) {
                    $lastGoodsInfo->optionRefFl = 'N';
                }
                $validData[] = $lastGoodsInfo;
            }
        }
        return $validData;
    }

    /**
     * 유효한 상품 목록 가져오기
     *
     * @param OptionTreeVo[] $data
     * @return OptionTreeVo[]
     */
    public function GetGoodsInfoOptionTreeVoValid($data = Array())
    {
        $options = Array();
        $optionsMap = Array();
        foreach ($data as $opt) {
            if (! isset($optionsMap[$opt->id]) && ! empty($opt->value) && $opt->value != '"') {
                $optionsMap[$opt->id] = $opt;
            }
        }
        foreach ($optionsMap as $opt) {
            $options[] = $opt;
        }
        return $options;
    }

    /**
     * 상품 옵션을 Excel 옵션으로 전환하여 가져오기
     *
     * @param OptionTreeVo[] $optionMap
     * @return OptionTreeVo[]
     */
    public function GetGoodsInfoExcelOptionsParseValid($optionMap = Array(), $parentId = '')
    {
        $options = Array();
        if (is_array($optionMap)) {
            foreach ($optionMap as $child) {
                if (isset($child->id) && isset($child->value) && ! empty($child->value) && ! empty($child->id)) {
                    $childVo = new OptionTreeVo();
                    $childVo->id = $child->id;
                    $childVo->parentId = $parentId;
                    $childVo->optionPrice = $child->optionPrice;
                    $childVo->value = $child->value;
                    $childVo->optionChildren = Array();
                    $options[] = $childVo;
                    if (isset($child->optionChildren) && is_array($child->optionChildren) && ! empty($child->optionChildren)) {
                        $optionChildren = $this->GetGoodsInfoExcelOptionsParseValid($child->optionChildren, $child->id);
                        if (! empty($optionChildren)) {
                            foreach ($optionChildren as $childOpt) {
                                $options[] = $childOpt;
                            }
                        }
                    }
                }
            }
        }
        return $options;
    }

    /**
     * 상품 옵션을 Excel 옵션으로 전환하여 가져오기
     *
     * @param OptionTreeVo[] $optionMap
     * @param OptionTreeVo[] $optionTree
     * @return OptionTreeVo[]
     */
    public function GetGoodsInfoExcelOptionsParse($optionMap = Array(), $optionTree = Array())
    {
        $options = Array();
        $optionDataList = ! empty($optionTree) ? $optionTree : $optionMap;
        if (! empty($optionDataList)) {
            switch (gettype($optionDataList)) {
                case 'string':
                    $optionsList = explode('/', $optionDataList);
                    foreach ($optionsList as $txt) {
                        if (! empty($txt)) {
                            list ($value, $optionPrice) = explode(':', $txt . ':');
                            $value = trim($value);
                            if (! empty($value)) {
                                $optionTreeVo = new OptionTreeVo();
                                $optionTreeVo->id = uniqid('O');
                                $optionTreeVo->value = trim($value);
                                $optionTreeVo->optionPrice = doubleval(trim($optionPrice));
                                $options[] = $optionTreeVo;
                            }
                        }
                    }
                    break;
                default:
                    if (is_array($optionDataList)) {
                        $options = $this->GetGoodsInfoExcelOptionsParseValid($optionDataList, '');
                    }
                    break;
            }
        }
        return $options;
    }

    /**
     * 상품 샘플 옵션 가져오기
     *
     * @return OptionTreeVo[]
     */
    public function GetGoodsInfoSampleOptions($parentId = '', $seqn = 0)
    {
        $options = Array();
        if (rand(0, 10) > 5 && $seqn <= 3) {
            for ($o = 0; $o < rand(2, 5); $o ++) {
                $optionsVo = new OptionTreeVo();
                $optionsVo->id = strtoupper(uniqid('O' . $o . '_'));
                $optionsVo->value = 'optionName ' . $seqn . '_' . $o;
                if (rand(0, 50) > 7) {
                    $optionsVo->optionPrice = rand(10, 50) * 100;
                } else {
                    $optionsVo->optionPrice = 0;
                }
                $optionsVo->parentId = $parentId;
                $options[] = $optionsVo;
                $childOptions = $this->GetGoodsInfoSampleOptions($optionsVo->id, $seqn + 1);
                if (count($childOptions) > 0) {
                    foreach ($childOptions as $child) {
                        $options[] = $child;
                    }
                }
            }
        }
        return $options;
    }

    /**
     * 자주쓰는 상품 필수정보 샘플 Excel 파일 만들기
     *
     * @param number $size
     * @return \Vo\GoodsFavMustInfoVo[]
     */
    public function GetGoodsFavMustInfoSampleList($size = 10)
    {
        $excelData = Array();
        $timeNow = time();
        for ($i = 0; $i < $size; $i ++) {
            $vo = new GoodsFavMustInfoVo();
            $vo->infoId = strtoupper(uniqid('C'));
            $vo->infoName = 'Some Info (' . rand(100, 999999) . ')';
            $vo->contents = Array();
            for ($j = 0; $j < rand(1, 4); $j ++) {
                $contentsVo = new TitleContentVo();
                $contentsVo->title = 'Some Title (' . rand(100, 999999) . ')';
                $contentsVo->contents = 'Some Contents (' . rand(100, 999999) . ')';
                $vo->contents[] = $contentsVo;
            }
            $vo->regDate = $this->getDateNow($timeNow - rand(5, 1000) * 60);
            $vo->modDate = $this->getDateNow($timeNow - rand(5, 1000) * 60);
            $excelData[] = $vo;
        }
        return $excelData;
    }

    /**
     * 자주쓰는 상품 컨텐츠정보 샘플 Excel 파일 만들기
     *
     * @param number $size
     * @return \Vo\GoodsFavOptionsVo[]
     */
    public function GetGoodsFavOptionsSampleList($size = 10)
    {
        $excelData = Array();
        $timeNow = time();
        for ($i = 0; $i < $size; $i ++) {
            $vo = new GoodsFavOptionsVo();
            $vo->optionsId = strtoupper(uniqid('O'));
            $vo->optionsName = 'Some Option (' . rand(100, 999999) . ')';
            switch (rand(0, 3)) {
                case 0:
                    $vo->optionsType = 'R';
                    break;
                case 1:
                    $vo->optionsType = 'E';
                    break;
                case 2:
                    $vo->optionsType = 'T';
                    break;
                case 3:
                    $vo->optionsType = 'T';
                    break;
            }
            $vo->contents = Array();
            for ($j = 0; $j < rand(1, 4); $j ++) {
                $optionsVo = new OptionTreeVo();
                $optionsVo->id = uniqid('O');
                $optionsVo->value = 'Option Name (' . rand(100, 999999) . ')';
                $optionsVo->optionPrice = rand(1, 50) * 1000;
                $optionsVo->optionScmPrice = rand(1, 50) * 1000;
                $optionsVo->optionCostPrice = rand(1, 50) * 1000;
                $optionsVo->stockCnt = rand(1, 50);
                $optionsVo->optionCode = uniqid('C');
                $vo->contents[] = $optionsVo;
            }
            $vo->regDate = $this->getDateNow($timeNow - rand(5, 1000) * 60);
            $vo->modDate = $this->getDateNow($timeNow - rand(5, 1000) * 60);
            $excelData[] = $vo;
        }
        return $excelData;
    }

    /**
     * 자주쓰는 상품 컨텐츠정보 샘플 Excel 파일 만들기
     *
     * @param number $size
     * @return \Vo\GoodsFavContentsVo[]
     */
    public function GetGoodsFavContentsSampleList($size = 10)
    {
        $excelData = Array();
        $timeNow = time();
        for ($i = 0; $i < $size; $i ++) {
            $vo = new GoodsFavContentsVo();
            $vo->contentsId = strtoupper(uniqid('C'));
            $vo->contentsName = 'Some Title (' . rand(100, 999999) . ')';
            $vo->contents = 'Some Contents (' . rand(100, 999999) . ')';
            $vo->contentsText = 'Some Contents Text(' . rand(100, 999999) . ')';
            $vo->regDate = $this->getDateNow($timeNow - rand(5, 1000) * 60);
            $vo->modDate = $this->getDateNow($timeNow - rand(5, 1000) * 60);
            $excelData[] = $vo;
        }
        return $excelData;
    }

    /**
     * 샘플 Excel 파일 만들기
     *
     * @param number $size
     * @return \Vo\GoodsInfoVo[]
     */
    public function GetGoodsInfoSampleImage($size = 10, $isAttach = false)
    {
        $imgList = Array();
        $imgMap = Array(
            '01.jpg',
            '01.jpeg',
            '02.jpg',
            '02.jpeg',
            '03.jpg',
            '03.jpeg',
            '04.jpg',
            '04.jpeg',
            '05.jpg',
            '05.jpeg',
            '06.jpg',
            '06.jpeg',
            '07.jpg',
            '07.jpeg',
            '08.jpg',
            '08.jpeg',
            '09.jpg',
            '09.jpeg',
            '10.jpg',
            '10.jpeg',
            '11.jpg',
            '11.jpeg',
            '12.jpg',
            '12.jpeg'
        );
        for ($i = 1; $i <= $size; $i ++) {
            if ($isAttach) {
                shuffle($imgMap);
                $fileName = $imgMap[0];
                $fileServer = 'common/sample/' . $fileName;
                $filePath = FILE_DIR . '/../common_img/sample/' . $fileName;
                if (file_exists($filePath)) {
                    $fileSize = filesize($filePath);
                } else {
                    $fileSize = 0;
                }
                $imgList[] = $fileName . '#' . $fileSize . '#image/jpg#' . $fileServer;
            } else {
                if (rand(0, 20) > 7) {
                    shuffle($imgMap);
                    $imgList[] = IMAGE_URL . 'common/sample/' . $imgMap[0];
                } else {
                    $imgList[] = 'someImage_' . rand(100, 999999) . '.jpg#' . rand(1000, 30000);
                }
            }
        }
        return implode('@!@', $imgList);
    }

    /**
     * 샘플 Excel 파일 만들기
     *
     * @param number $size
     * @return \Vo\GoodsInfoVo[]
     */
    public function GetGoodsInfoSampleContents($size = 10)
    {
        $imgList = Array();
        $imgMap = Array(
            '01.jpg',
            '01.jpeg',
            '02.jpg',
            '02.jpeg',
            '03.jpg',
            '03.jpeg',
            '04.jpg',
            '04.jpeg',
            '05.jpg',
            '05.jpeg',
            '06.jpg',
            '06.jpeg',
            '07.jpg',
            '07.jpeg',
            '08.jpg',
            '08.jpeg',
            '09.jpg',
            '09.jpeg',
            '10.jpg',
            '10.jpeg',
            '11.jpg',
            '11.jpeg',
            '12.jpg',
            '12.jpeg'
        );
        for ($i = 1; $i <= $size; $i ++) {
            if (rand(0, 20) > 7) {
                shuffle($imgMap);
                $imgList[] = IMAGE_URL . 'common/sample/' . $imgMap[0];
            } else {
                $imgList[] = 'someImage_' . rand(100, 999999) . '.jpg';
            }
        }
        $htmlContents = Array();
        foreach ($imgList as $img) {
            $htmlContents[] = 'Goods Description -' . rand(1000, 999999) . '<br><img src="' . $img . '" />';
        }
        return implode("\n<br/>\n", $htmlContents);
    }

    /**
     * 샘플 Excel 파일 만들기
     *
     * @param number $size
     * @return \Vo\GoodsInfoVo[]
     */
    public function GetGoodsInfoSampleList($size = 10)
    {
        $excelData = Array();
        $timeNow = time();
        $cateKeys = Array();
        $cateInfo = $this->GetCategoryTreeList();
        foreach ($cateInfo as $cate) {
            $cateKeys[$cate->id] = $cate->id;
        }
        $brandInfo = $this->GetBrandTreeList();
        $brandKeys = Array();
        foreach ($brandInfo as $cate) {
            $brandKeys[$cate->id] = $cate->id;
        }
        for ($i = 0; $i < $size; $i ++) {
            $vo = new GoodsInfoVo();
            $vo->goodsCode = $this->GetUniqId("G" . date("Ymd"));
            $vo->goodsCd = $this->GetUniqId("GD" . date("Y"));
            $vo->goodsNm = 'Goods Name ' . rand(1000, 9999);
            $vo->goodsImageMaster = $this->GetGoodsInfoSampleImage(1);
            $vo->goodsImageAdded = $this->GetGoodsInfoSampleImage(rand(2, 5));
            $vo->goodsCategoryMst = '';
            $vo->goodsCategory = Array();
            if (! empty($cateKeys)) {
                for ($j = 0; $j < rand(2, 5); $j ++) {
                    $goodsCategory = array_rand($cateKeys);
                    if (! in_array($goodsCategory, $vo->goodsCategory)) {
                        $vo->goodsCategory[] = $goodsCategory;
                    }
                }
            }
            if (count($vo->goodsCategory) > 0) {
                $vo->goodsCategoryMst = $vo->goodsCategory[0];
            }
            if (! empty($cateKeys)) {
                $vo->brandCd = array_rand($brandKeys);
            }
            $vo->goodsSearchWord = '';
            $randomKeyword = Array(
                '컴퓨터',
                '이달의추천',
                '패션잡화',
                '액세서리',
                '노트북',
                '핸드폰',
                '스마트폰'
            );
            for ($j = 0; $j < rand(1, 4); $j ++) {
                shuffle($randomKeyword);
                $vo->goodsSearchWord .= $randomKeyword[0] . ' ';
            }
            $goodsSearchWord = $this->GetLocaleTextKeyword($vo->goodsNm, $vo->goodsNmLocale);
            $goodsSearchWord .= ' ' . $vo->goodsSearchWord;
            if (! empty($vo->goodsCategoryMst)) {
                $goodsSearchWord .= ' ' . $this->GetCategoryNameKeyword($vo->goodsCategoryMst);
            }
            $vo->goodsSearchWord = $this->GetSearchKeywordUniq($goodsSearchWord);

            $vo->goodsPrice = rand(50, 500) * 1000;
            $vo->fixedPrice = max(0, $vo->goodsPrice + rand(10, 50) * 1000);
            $vo->costPrice = max(0, $vo->goodsPrice - rand(10, 50) * 1000);
            $vo->commission = rand(5, 10);
            $vo->makerNm = '제조사' . rand(10, 99);
            $vo->originNm = rand(0, 10) > 7 ? '중국' : '한국';
            $vo->stockFl = 'N';
            $vo->taxPercent = rand(0, 10) > 7 ? 0 : 10;
            $vo->stockCnt = rand(10, 70);
            $vo->goodsState = 'N';
            $vo->goodsDescription = $this->GetGoodsInfoSampleContents(rand(1, 3));
            if (rand(0, 10) > 5) {
                $vo->options = $this->GetGoodsInfoSampleOptions();
            }
            if (! empty($vo->options)) {
                $optionsName = new CodeVo();
                $optionsName->text = '필수옵션명' . rand(10, 99);
                $optionsName->isTranslated = true;
                $vo->optionsName = $optionsName;
                $vo->optionFl = 'Y';
            } else {
                $vo->optionFl = 'N';
            }
            if (rand(0, 10) > 7) {
                $vo->optionsExt = $this->GetGoodsInfoSampleOptions();
            }
            if (! empty($vo->optionsExt)) {
                $optionsName = new CodeVo();
                $optionsName->text = '선택옵션명' . rand(10, 99);
                $optionsName->isTranslated = true;
                $vo->optionsExtName = $optionsName;
                $vo->optionsExtFl = 'Y';
            } else {
                $vo->optionsExtFl = 'N';
            }
            if (rand(0, 10) > 7) {
                $vo->optionsText = $this->GetGoodsInfoSampleOptions();
            }
            if (! empty($vo->optionsText)) {
                $optionsName = new CodeVo();
                $optionsName->text = '텍스트옵션명' . rand(10, 99);
                $optionsName->isTranslated = true;
                $vo->optionsTextName = $optionsName;
                $vo->optionTextFl = 'Y';
            } else {
                $vo->optionTextFl = 'N';
            }
            if (rand(0, 10) > 7) {
                $vo->optionsRef = $this->GetGoodsInfoSampleOptions();
            }
            if (! empty($vo->optionsRef)) {
                $optionsName = new CodeVo();
                $optionsName->text = '추가상품옵션명' . rand(10, 99);
                $optionsName->isTranslated = true;
                $vo->optionsRefName = $optionsName;
                $vo->optionRefFl = 'Y';
            } else {
                $vo->optionRefFl = 'N';
            }
            if ($vo->optionFl == 'Y') {
                $vo->optionsTree = $this->GetGoodsInfoOptionTree($vo->options, $vo->optionDisplayFl, $vo->stockFl, $vo->stockCnt);
            }
            if (empty($vo->optionsTree)) {
                $vo->optionFl = 'N';
            }
            if ($vo->optionsExtFl == 'Y') {
                $vo->optionsExtTree = $this->GetGoodsInfoOptionTree($vo->optionsExt, '', $vo->stockFl, $vo->stockCnt);
            }
            if (empty($vo->optionsExtTree)) {
                $vo->optionsExtFl = 'N';
            }
            if ($vo->optionTextFl == 'Y') {
                $vo->optionsTextTree = $this->GetGoodsInfoOptionTree($vo->optionsText, '', $vo->stockFl, $vo->stockCnt);
            }
            if (empty($vo->optionsTextTree)) {
                $vo->optionTextFl = 'N';
            }
            if ($vo->optionRefFl == 'Y') {
                $vo->optionsRefTree = $this->GetGoodsInfoOptionTree($vo->optionsRef, '', $vo->stockFl, $vo->stockCnt);
            }
            if (empty($vo->optionsRefTree)) {
                $vo->optionRefFl = 'N';
            }
            $vo->regDate = $this->getDateNow($timeNow - rand(5, 1000) * 60);
            $vo->modDate = $this->getDateNow($timeNow - rand(5, 1000) * 60);

            $this->GetGoodsInfoVersionCheck($vo);
            $this->GetGoodsInfoViewParse($vo);
            $excelData[] = $vo;
        }
        return $excelData;
    }

    /**
     * 카테고리 샘플 Excel 파일 만들기
     *
     * @param number $size
     * @return \Vo\CategoryVo[]
     */
    public function GetCategorySampleList($size = 10)
    {
        $excelData = Array();
        $timeNow = time();
        for ($i = 0; $i < $size; $i ++) {
            $vo = new CategoryVo();
            $vo->categoryId = strtoupper(uniqid('C'));
            if (rand(0, 10) > 4) {
                $vo->parentId = strtoupper(uniqid('C'));
            } else {
                $vo->parentId = '';
            }
            $vo->categoryNm = 'Category Info (' . rand(100, 999999) . ')';
            $vo->regDate = $this->getDateNow($timeNow - rand(5, 1000) * 60);
            $vo->modDate = $this->getDateNow($timeNow - rand(5, 1000) * 60);
            $excelData[] = $vo;
        }
        return $excelData;
    }

    /**
     * 카테고리 샘플 Excel 파일 만들기
     *
     * @param number $size
     * @return \Vo\CategoryVo[]
     */
    public function GetBrandSampleList($size = 10)
    {
        $excelData = Array();
        $timeNow = time();
        for ($i = 0; $i < $size; $i ++) {
            $vo = new BrandVo();
            $vo->brandId = strtoupper(uniqid('B'));
            if (rand(0, 10) > 4) {
                $vo->parentId = strtoupper(uniqid('B'));
            } else {
                $vo->parentId = '';
            }
            $vo->brandNm = 'Brand Info (' . rand(100, 999999) . ')';
            $vo->regDate = $this->getDateNow($timeNow - rand(5, 1000) * 60);
            $vo->modDate = $this->getDateNow($timeNow - rand(5, 1000) * 60);
            $excelData[] = $vo;
        }
        return $excelData;
    }

    /**
     * 상품 Q&A 샘플 Excel 파일 만들기
     *
     * @param number $size
     * @return \Vo\GoodsQnaVo[]
     */
    public function GetGoodsQnaSampleList($size = 10)
    {
        $excelData = Array();
        $timeNow = time();
        for ($i = 0; $i < $size; $i ++) {
            $vo = new GoodsQnaVo();
            $vo->regDate = $this->getDateNow($timeNow - rand(5, 1000) * 60);
            $vo->modDate = $this->getDateNow($timeNow - rand(5, 1000) * 60);
            $excelData[] = $vo;
        }
        return $excelData;
    }

    /**
     * 상품 리뷰 샘플 Excel 파일 만들기
     *
     * @param number $size
     * @return \Vo\GoodsReviewVo[]
     */
    public function GetGoodsReviewSampleList($size = 10)
    {
        $excelData = Array();
        $timeNow = time();
        for ($i = 0; $i < $size; $i ++) {
            $vo = new GoodsReviewVo();
            $vo->regDate = $this->getDateNow($timeNow - rand(5, 1000) * 60);
            $vo->modDate = $this->getDateNow($timeNow - rand(5, 1000) * 60);
            $excelData[] = $vo;
        }
        return $excelData;
    }

    /**
     * 상품 샘플 엑셀 파일 생성하여 가져오기
     *
     * @param OptionTreeVo[] $optionsTree
     * @return ExcelVo
     */
    public function GetGoodsInfoListOptionMap($optionsTree = Array(), $parentId = '', $parentMap = Array())
    {
        if (! empty($optionsTree) && is_array($optionsTree)) {
            foreach ($optionsTree as $tree) {
                $vo = new OptionTreeVo();
                $vo->id = $tree->id;
                $vo->value = $tree->value;
                $vo->optionPrice = $tree->optionPrice;
                $vo->parentId = '';
                if (! empty($tree->optionChildren) && is_array($tree->optionChildren)) {
                    foreach ($tree->optionChildren as $tree1) {
                        $vo1 = new OptionTreeVo();
                        $vo1->id = $tree1->id;
                        $vo1->value = $tree1->value;
                        $vo1->optionPrice = $tree1->optionPrice;
                        $vo1->parentId = $tree->id;
                        if (! empty($tree1->optionChildren) && is_array($tree1->optionChildren)) {
                            foreach ($tree1->optionChildren as $tree2) {
                                $vo2 = new OptionTreeVo();
                                $vo2->id = $tree2->id;
                                $vo2->value = $tree2->value;
                                $vo2->optionPrice = $tree2->optionPrice;
                                $vo2->parentId = $tree1->id;
                                $parentMap[] = Array(
                                    $vo,
                                    $vo1,
                                    $vo2
                                );
                            }
                        } else {
                            $parentMap[] = Array(
                                $vo,
                                $vo1
                            );
                        }
                    }
                } else {
                    $parentMap[] = Array(
                        $vo
                    );
                }
            }
        }
        return $parentMap;
    }

    /**
     * 상품 샘플 엑셀 파일 생성하여 가져오기
     *
     * @param mixed $item
     * @return mixed
     */
    public function GetGoodsInfoListSampleExcelEqualEmpty($item = null)
    {
        if (! empty($item) && is_object($item)) {
            foreach ($item as $key => $val) {
                if (! empty($val) && is_object($val)) {
                    $item->$key = $this->GetOrderInfoListSampleExcelEqualEmpty($val);
                } else if ($val == '' || $val == 0) {
                    $item->$key = '"';
                }
            }
        }
        return $item;
    }

    /**
     * 상품 샘플 엑셀 파일 생성하여 가져오기
     *
     * @param RequestVo $request
     * @return ExcelVo
     */
    public function GetGoodsInfoListSampleExcel(RequestVo $request = null)
    {
        $excelSampleData = $this->GetGoodsInfoSampleList(20);
        foreach ($excelSampleData as $vo) {
            $vo->goodsCode = 'G' . date('Ymd') . 'xxxxxxxxxx';
        }
        $excelData = Array();
        foreach ($excelSampleData as $goodsInfo) {
            $optionCnt = 1;
            $optionsTree = $this->GetGoodsInfoListOptionMap($goodsInfo->optionsTree, '', Array());
            $optionsExtTree = $this->GetGoodsInfoListOptionMap($goodsInfo->optionsExtTree, '', Array());
            $optionsTextTree = $this->GetGoodsInfoListOptionMap($goodsInfo->optionsTextTree, '', Array());
            $optionsRefTree = $this->GetGoodsInfoListOptionMap($goodsInfo->optionsRefTree, '', Array());
            $optionCnt = max($optionCnt, count($optionsTree));
            $optionCnt = max($optionCnt, count($optionsExtTree));
            $optionCnt = max($optionCnt, count($optionsTextTree));
            $optionCnt = max($optionCnt, count($optionsRefTree));
            for ($i = 0; $i < $optionCnt; $i ++) {
                $optTree = isset($optionsTree[$i]) ? $optionsTree[$i] : Array();
                $optExtTree = isset($optionsExtTree[$i]) ? $optionsExtTree[$i] : Array();
                $optTextTree = isset($optionsTextTree[$i]) ? $optionsTextTree[$i] : Array();
                $optRefTree = isset($optionsRefTree[$i]) ? $optionsRefTree[$i] : Array();
                $tmpGoodsInfo = null;
                if ($i == 0) {
                    $tmpGoodsInfo = $goodsInfo;
                } else {
                    $tmpGoodsInfo = $this->GetGoodsInfoListSampleExcelEqualEmpty(new GoodsInfoVo());
                }
                $tmpGoodsInfo->options = $optTree;
                $tmpGoodsInfo->optionsExt = $optExtTree;
                $tmpGoodsInfo->optionsText = $optTextTree;
                $tmpGoodsInfo->optionsRef = $optRefTree;
                if ($goodsInfo->optionFl == 'Y') {
                    $tmpGoodsInfo->optionsTree = $this->GetGoodsInfoOptionTree($tmpGoodsInfo->options, $goodsInfo->optionDisplayFl, $goodsInfo->stockFl, $goodsInfo->stockCnt);
                }
                if ($goodsInfo->optionsExtFl == 'Y') {
                    $tmpGoodsInfo->optionsExtTree = $this->GetGoodsInfoOptionTree($tmpGoodsInfo->optionsExt, '', $goodsInfo->stockFl, $goodsInfo->stockCnt);
                }
                if ($goodsInfo->optionTextFl == 'Y') {
                    $tmpGoodsInfo->optionsTextTree = $this->GetGoodsInfoOptionTree($tmpGoodsInfo->optionsText, '', $goodsInfo->stockFl, $goodsInfo->stockCnt);
                }
                if ($goodsInfo->optionRefFl == 'Y') {
                    $tmpGoodsInfo->optionsRefTree = $this->GetGoodsInfoOptionTree($tmpGoodsInfo->optionsRef, '', $goodsInfo->stockFl, $goodsInfo->stockCnt);
                }
                $excelData[] = $tmpGoodsInfo;
            }
        }
        $downloadFormVo = $this->GetDownloadFormVo($request, 'goodsCodes');
        $downloadFormVo->downloadFileName = 'goods_upload_sample';
        $excelVo = new ExcelVo($downloadFormVo, $excelData, true);
        $excelVo->AddHeaderList($downloadFormVo->fieldList, true);
        return $excelVo;
    }

    /**
     * 카테고리 트리 정보
     *
     * @var TreeVo[]
     */
    private $categoryTreeVoList = null;

    /**
     * 상품정보에서 대표 카테고리 정보 아이디 목록가져오기
     *
     * @param GoodsInfoVo $vo
     * @return string[]
     */
    public function GetGoodsInfoCategoryData(GoodsInfoVo $vo)
    {
        $cateName1 = '';
        $cateName2 = '';
        $cateName3 = '';
        $cateName4 = '';
        if ($vo->naverFl == 'Y') {
            if (! empty($vo->naverOptions) && ! empty($vo->naverOptions->category)) {
                try {
                    $categoryInfo = $this->GetNaverShopCategoryView($vo->naverOptions->category);
                    if (! empty($categoryInfo) && ! empty($categoryInfo->cateName)) {
                        list ($cateName1, $cateName2, $cateName3, $cateName4) = explode('>', $categoryInfo->cateName . '>>>>');
                        $cateName1 = trim($cateName1);
                        $cateName2 = trim($cateName2);
                        $cateName3 = trim($cateName3);
                        $cateName4 = trim($cateName4);
                    }
                } catch (Exception $ex) {}
            }
        }
        if (empty($cateName1)) {
            $goodsCategoryMst = $vo->goodsCategoryMst;
            if (! empty($goodsCategoryMst)) {
                if (empty($this->categoryTreeVoList)) {
                    $this->categoryTreeVoList = $this->GetCategoryTreeVoList();
                }
                if (isset($this->categoryTreeVoList[$goodsCategoryMst])) {
                    $cateVo = $this->categoryTreeVoList[$goodsCategoryMst];
                    $cateNameData = Array();
                    $cateNameData[] = $cateVo->value;
                    while (! empty($cateVo->parentTreeVo)) {
                        $cateVo = $cateVo->parentTreeVo;
                        $cateNameData[] = $cateVo->value;
                    }
                    $cateNameData = array_reverse($cateNameData);
                    foreach ($cateNameData as $key => $cateName) {
                        switch ($key) {
                            case 0:
                                $cateName1 = $cateName;
                                break;
                            case 1:
                                $cateName2 = $cateName;
                                break;
                            case 2:
                                $cateName3 = $cateName;
                                break;
                            case 3:
                                $cateName4 = $cateName;
                                break;
                        }
                    }
                }
            }
        }
        $categoryList = Array();
        if (! empty($cateName1)) {
            $categoryList[] = $cateName1;
        }
        if (! empty($cateName2)) {
            $categoryList[] = $cateName2;
        }
        if (! empty($cateName3)) {
            $categoryList[] = $cateName3;
        }
        if (! empty($cateName4)) {
            $categoryList[] = $cateName4;
        }
        return $categoryList;
    }

    /**
     * 상품 목록 Excel 파일로 가져오기
     *
     * @param RequestVo $request
     * @param string $isHidden
     * @return ExcelVo
     */
    public function GetGoodsInfoListExcel(RequestVo $request = null, $isHidden = 'N')
    {
        $downloadFormVo = $this->GetDownloadFormVo($request, 'goodsCodes');
        $excelData = $this->GetGoodsInfoList($downloadFormVo->searchRequest, null, '', $isHidden);
        $excelVo = new ExcelVo($downloadFormVo, $excelData);
        $excelVo->AddHeaderList($downloadFormVo->fieldList, true);
        return $excelVo;
    }

    /**
     * 특정 상품 숨기기
     *
     * @param string $uid
     * @return boolean
     */
    public function GetGoodsInfoHidden($uid = '')
    {
        $this->GetGoodsInfoChange(Array(
            $uid
        ), 'hidden');
        return true;
    }

    /**
     * 특정상품 삭제 하기
     *
     * @param string $uid
     * @return GoodsInfoVo
     */
    public function GetGoodsInfoDelete($uid = '')
    {
        $vo = $this->GetGoodsInfoVo($uid);
        if ($this->GetGoodsInfoDao()->SetDelete($vo)) {
            $this->GetGoodsMallSynk($vo, 'delete');
            $oldVo = new GoodsInfoVo();
            $oldVo->goodsCode = $vo->goodsCode;
            $this->UnsetGoodsInfoCache($vo, new GoodsInfoVo());
            return $vo;
        } else {
            parent::GetException(KbmException::DATA_ERROR_DELETE);
        }
    }

    /**
     * 상품 관련 배치 작업
     *
     * @param RequestVo $request
     * @param array $goodsCodes
     * @param string $mode
     * @return boolean
     */
    public function GetGoodsInfoBatch(RequestVo $request, $goodsCodes = Array(), $mode = '')
    {
        if (! empty($goodsCodes)) {
            if ($this->IsScmAdmin()) {
                switch ($mode) {
                    case 'price':
                        return $this->GetGoodsInfoChangePrice($goodsCodes, $request->GetRequestVo('batchForm'));
                    case 'info':
                        return $this->GetGoodsInfoChangeOthers($goodsCodes, $request->GetRequestVo('batchForm'));
                    case 'mileage':
                        // return $this->GetGoodsInfoChangeMileage($goodsCodes, $request->GetRequestVo('batchForm'));
                        break;
                    case 'iconcolor':
                    case 'icon_color':
                        return $this->GetGoodsInfoChangeIconColor($goodsCodes, $request->GetRequestVo('batchForm'));
                    case 'stock':
                        return $this->GetGoodsInfoChangeStock($goodsCodes, $request->GetRequestVo('batchForm'));
                    case 'link':
                        return $this->GetGoodsInfoChangeLink($goodsCodes, $request->GetRequestVo('batchForm'));
                    case 'delivery':
                        return $this->GetGoodsInfoChangeDelivery($goodsCodes, $request->GetRequestVo('batchForm'));
                    case 'changeModDate':
                    case 'toSoldout':
                    case 'copy':
                    case 'delete':
                    case 'hidden':
                    case 'show':
                    case 'changeDisplay':
                    case 'changeMain':
                    case 'changeCategory':
                        return $this->GetGoodsInfoChange($goodsCodes, $mode, $request->applyData);
                }
                return false;
            } else {
                switch ($mode) {
                    case 'price':
                        return $this->GetGoodsInfoChangePrice($goodsCodes, $request->GetRequestVo('batchForm'));
                    case 'info':
                        return $this->GetGoodsInfoChangeOthers($goodsCodes, $request->GetRequestVo('batchForm'));
                    case 'mileage':
                        return $this->GetGoodsInfoChangeMileage($goodsCodes, $request->GetRequestVo('batchForm'));
                        break;
                    case 'iconcolor':
                    case 'icon_color':
                        return $this->GetGoodsInfoChangeIconColor($goodsCodes, $request->GetRequestVo('batchForm'));
                    case 'stock':
                        return $this->GetGoodsInfoChangeStock($goodsCodes, $request->GetRequestVo('batchForm'));
                    case 'link':
                        return $this->GetGoodsInfoChangeLink($goodsCodes, $request->GetRequestVo('batchForm'));
                    case 'delivery':
                        return $this->GetGoodsInfoChangeDelivery($goodsCodes, $request->GetRequestVo('batchForm'));
                    case 'changeModDate':
                    case 'toSoldout':
                    case 'copy':
                    case 'delete':
                    case 'hidden':
                    case 'show':
                    case 'changeDisplay':
                    case 'changeMain':
                    case 'changeCategory':
                        return $this->GetGoodsInfoChange($goodsCodes, $mode, $request->applyData);
                }
            }
        } else {
            return false;
        }
    }

    /**
     * 상품에 특정 메스드로 목록 추가/삭제 변경하기
     *
     * @param mixed[] $oldData
     * @param mixed[] $newData
     * @param string $method
     * @return mixed[]
     */
    private function GetGoodsInfoMethodData($oldData = Array(), $newData = Array(), $method = 'A')
    {
        if (! is_array($oldData)) {
            $oldData = Array();
        }
        if (! is_array($newData)) {
            $newData = Array();
        }
        if (! empty($newData)) {
            switch ($method) {
                case 'A':
                    foreach ($newData as $value) {
                        if (! empty($value) && ! in_array($value, $oldData)) {
                            $oldData[] = $value;
                        }
                    }
                    return $oldData;
                case 'M':
                    return $newData;
                case 'D':
                    $modData = Array();
                    foreach ($oldData as $value) {
                        if (! empty($value) && ! in_array($value, $newData)) {
                            $modData[] = $value;
                        }
                    }
                    return $modData;
                case '':
                default:
                    break;
            }
        }
        return $oldData;
    }

    /**
     * 상품의 아이콘 색상 변경하기
     *
     * @param array $uidList
     * @param RequestVo $iconInfo
     * @return boolean
     */
    public function GetGoodsInfoChangeIconColor($uidList = Array(), RequestVo $iconInfo)
    {
        $searchVo = new GoodsSearchVo();
        $searchVo->mallId = $this->mallId;
        $searchVo->goodsCodes = $uidList;
        if (count($searchVo->goodsCodes) > 0) {
            $goodsIconDateMethod = $iconInfo->goodsIconDateMethod;
            $goodsIconOpenDate = $iconInfo->goodsIconOpenDate;
            $goodsIconCloseDate = $iconInfo->goodsIconCloseDate;
            $goodsIconTimeMethod = $iconInfo->goodsIconTimeMethod;
            $goodsIconTime = $iconInfo->goodsIconTime;
            $goodsIconFixMethod = $iconInfo->goodsIconFixMethod;
            $goodsIconFix = $iconInfo->goodsIconFix;
            $goodsColorMethod = $iconInfo->goodsColorMethod;
            $goodsColor = $iconInfo->goodsColor;
            $goodsSearchOptionMethod = $iconInfo->goodsSearchOptionMethod;
            $goodsSearchOption = $iconInfo->goodsSearchOption;
            if ($this->IsScmAdmin()) {
                $scmService = $this->GetServiceScm();
                foreach ($uidList as $goodsCode) {
                    try {
                        $goodsInfo = $this->GetGoodsInfoView($goodsCode);
                        $reqGoodsInfo = new RequestVo(Array(
                            'goodsCode' => $goodsCode
                        ), false);
                        if ($goodsIconDateMethod == 'Y') {
                            if (! $this->isDateEmpty($goodsIconOpenDate)) {
                                $reqGoodsInfo->goodsIconOpenDate = $goodsIconOpenDate;
                            }
                            if (! $this->isDateEmpty($goodsIconCloseDate)) {
                                $reqGoodsInfo->goodsIconCloseDate = $goodsIconCloseDate;
                            }
                        }
                        $reqGoodsInfo->goodsIconTime = $this->GetGoodsInfoMethodData($goodsInfo->goodsIconTime, $goodsIconTime, $goodsIconTimeMethod);
                        $reqGoodsInfo->goodsIconFix = $this->GetGoodsInfoMethodData($goodsInfo->goodsIconFix, $goodsIconFix, $goodsIconFixMethod);
                        $reqGoodsInfo->goodsColor = $this->GetGoodsInfoMethodData($goodsInfo->goodsColor, $goodsColor, $goodsColorMethod);
                        $reqGoodsInfo->goodsSearchOption = $this->GetGoodsInfoMethodData($goodsInfo->goodsSearchOption, $goodsSearchOption, $goodsSearchOptionMethod);
                        $scmService->GetGoodsInfoUpdate($goodsCode, $reqGoodsInfo, $this->loginInfo);
                    } catch (Exception $ex) {}
                }
            } else {
                foreach ($uidList as $goodsCode) {
                    try {
                        $goodsInfo = $this->GetGoodsInfoView($goodsCode);
                        if ($goodsIconDateMethod == 'Y') {
                            if (! $this->isDateEmpty($goodsIconOpenDate)) {
                                $goodsInfo->goodsIconOpenDate = $goodsIconOpenDate;
                            }
                            if (! $this->isDateEmpty($goodsIconCloseDate)) {
                                $goodsInfo->goodsIconCloseDate = $goodsIconCloseDate;
                            }
                        }
                        $goodsInfo->goodsIconTime = $this->GetGoodsInfoMethodData($goodsInfo->goodsIconTime, $goodsIconTime, $goodsIconTimeMethod);
                        $goodsInfo->goodsIconFix = $this->GetGoodsInfoMethodData($goodsInfo->goodsIconFix, $goodsIconFix, $goodsIconFixMethod);
                        $goodsInfo->goodsColor = $this->GetGoodsInfoMethodData($goodsInfo->goodsColor, $goodsColor, $goodsColorMethod);
                        $goodsInfo->goodsSearchOption = $this->GetGoodsInfoMethodData($goodsInfo->goodsSearchOption, $goodsSearchOption, $goodsSearchOptionMethod);
                        $reqGoodsInfo = new RequestVo($goodsInfo);
                        $this->GetGoodsInfoUpdate($goodsCode, $reqGoodsInfo);
                    } catch (Exception $ex) {}
                }
            }
        }
        return true;
    }

    /**
     * 상품의 재고 수량 변경하기
     *
     * @param array $uidList
     * @param RequestVo $stockInfo
     * @return boolean
     */
    public function GetGoodsInfoChangeStock($uidList = Array(), RequestVo $stockInfo)
    {
        if (count($uidList) > 0) {
            $goodsDisplayFl = $stockInfo->goodsDisplayFl;
            $goodsDisplayMobileFl = $stockInfo->goodsDisplayMobileFl;
            $goodsSellFl = $stockInfo->goodsSellFl;
            $goodsSellMobileFl = $stockInfo->goodsSellMobileFl;
            $soldOutFl = $stockInfo->soldOutFl;
            $stockFl = $stockInfo->stockFl;
            $stockMethod = $stockInfo->stockMethod;
            $stockCnt = intval($stockInfo->stockCnt);
            $optionFl = $stockInfo->optionFl;
            $optionSellFl = $stockInfo->optionSellFl;
            $optionStockMethod = $stockInfo->optionStockMethod;
            $optionStockCnt = intval($stockInfo->optionStockCnt);
            if ($this->IsScmAdmin()) {
                $scmService = $this->GetServiceScm();
                foreach ($uidList as $goodsCode) {
                    try {
                        $reqGoodsInfo = new RequestVo(Array(
                            'goodsCode' => $goodsCode
                        ), false);
                        $goodsInfo = $this->GetGoodsInfoView($goodsCode);
                        if ($goodsDisplayFl == 'Y' || $goodsDisplayFl == 'N') {
                            $reqGoodsInfo->goodsDisplayFl = $goodsDisplayFl;
                        }
                        if ($goodsDisplayMobileFl == 'Y' || $goodsDisplayMobileFl == 'N') {
                            $reqGoodsInfo->goodsDisplayMobileFl = $goodsDisplayMobileFl;
                        }
                        if ($goodsSellFl == 'Y' || $goodsSellFl == 'N') {
                            $reqGoodsInfo->goodsSellFl = $goodsSellFl;
                        }
                        if ($goodsSellMobileFl == 'Y' || $goodsSellMobileFl == 'N') {
                            $reqGoodsInfo->goodsSellMobileFl = $goodsSellMobileFl;
                        }
                        if ($soldOutFl == 'Y' || $soldOutFl == 'N') {
                            $reqGoodsInfo->soldOutFl = $soldOutFl;
                        }
                        if ($stockFl == 'Y' || $stockFl == 'N') {
                            $reqGoodsInfo->stockFl = $stockFl;
                        }
                        if ($stockMethod != '') {
                            $reqGoodsInfo->stockCnt = intval($goodsInfo->stockCnt);
                            switch ($stockMethod) {
                                case 'A':
                                    $reqGoodsInfo->stockCnt += $stockCnt;
                                    break;
                                case 'M':
                                    $reqGoodsInfo->stockCnt = $stockCnt;
                                    break;
                                case 'D':
                                    $reqGoodsInfo->stockCnt -= $stockCnt;
                                    break;
                            }
                        }
                        if ($optionFl == 'Y' || $optionFl == 'N') {
                            if ($optionFl == 'Y' && count($goodsInfo->options) > 0) {
                                $reqGoodsInfo->optionFl = 'Y';
                            } else {
                                $reqGoodsInfo->optionFl = 'N';
                            }
                        }
                        if ($goodsInfo->optionFl == 'Y') {
                            if ($optionSellFl == 'Y' || $optionSellFl == 'N') {
                                foreach ($goodsInfo->options as $option) {
                                    $option->optionSellFl = $optionSellFl;
                                }
                            }
                            if ($optionStockMethod != '') {
                                foreach ($goodsInfo->options as $option) {
                                    $option->stockCnt = intval($option->stockCnt);
                                    switch ($optionStockMethod) {
                                        case 'A':
                                            $option->stockCnt += $optionStockCnt;
                                            break;
                                        case 'M':
                                            $option->stockCnt = $optionStockCnt;
                                            break;
                                        case 'D':
                                            $option->stockCnt -= $optionStockCnt;
                                            break;
                                    }
                                }
                                $reqGoodsInfo->options = $goodsInfo->options;
                            }
                        }
                        $scmService->GetGoodsInfoUpdate($goodsCode, $reqGoodsInfo, $this->loginInfo);
                    } catch (Exception $ex) {}
                }
            } else {
                foreach ($uidList as $goodsCode) {
                    try {
                        $goodsInfo = $this->GetGoodsInfoView($goodsCode);
                        if ($goodsDisplayFl == 'Y' || $goodsDisplayFl == 'N') {
                            $goodsInfo->goodsDisplayFl = $goodsDisplayFl;
                        }
                        if ($goodsDisplayMobileFl == 'Y' || $goodsDisplayMobileFl == 'N') {
                            $goodsInfo->goodsDisplayMobileFl = $goodsDisplayMobileFl;
                        }
                        if ($goodsSellFl == 'Y' || $goodsSellFl == 'N') {
                            $goodsInfo->goodsSellFl = $goodsSellFl;
                        }
                        if ($goodsSellMobileFl == 'Y' || $goodsSellMobileFl == 'N') {
                            $goodsInfo->goodsSellMobileFl = $goodsSellMobileFl;
                        }
                        if ($soldOutFl == 'Y' || $soldOutFl == 'N') {
                            $goodsInfo->soldOutFl = $soldOutFl;
                        }
                        if ($stockFl == 'Y' || $stockFl == 'N') {
                            $goodsInfo->stockFl = $stockFl;
                        }
                        if ($stockMethod != '') {
                            $goodsInfo->stockCnt = intval($goodsInfo->stockCnt);
                            switch ($stockMethod) {
                                case 'A':
                                    $goodsInfo->stockCnt += $stockCnt;
                                    break;
                                case 'M':
                                    $goodsInfo->stockCnt = $stockCnt;
                                    break;
                                case 'D':
                                    $goodsInfo->stockCnt -= $stockCnt;
                                    break;
                            }
                        }
                        if ($optionFl == 'Y' || $optionFl == 'N') {
                            if ($optionFl == 'Y' && count($goodsInfo->options) > 0) {
                                $goodsInfo->optionFl = 'Y';
                            } else {
                                $goodsInfo->optionFl = 'N';
                            }
                        }
                        if ($goodsInfo->optionFl == 'Y') {
                            if ($optionSellFl == 'Y' || $optionSellFl == 'N') {
                                foreach ($goodsInfo->options as $option) {
                                    $option->optionSellFl = $optionSellFl;
                                }
                            }
                            if ($optionStockMethod != '') {
                                foreach ($goodsInfo->options as $option) {
                                    $option->stockCnt = intval($option->stockCnt);
                                    switch ($optionStockMethod) {
                                        case 'A':
                                            $option->stockCnt += $optionStockCnt;
                                            break;
                                        case 'M':
                                            $option->stockCnt = $optionStockCnt;
                                            break;
                                        case 'D':
                                            $option->stockCnt -= $optionStockCnt;
                                            break;
                                    }
                                }
                            }
                        }
                        $reqGoodsInfo = new RequestVo($goodsInfo);
                        $this->GetGoodsInfoUpdate($goodsCode, $reqGoodsInfo);
                    } catch (Exception $ex) {}
                }
            }
        }
        return true;
    }

    /**
     * 상품 관련 카테고리, 브랜드 재설정하기
     *
     * @param array $uidList
     * @param RequestVo $linkInfo
     * @return boolean
     */
    public function GetGoodsInfoChangeLink($uidList = Array(), RequestVo $linkInfo)
    {
        if (count($uidList) > 0) {
            $goodsCategory = $linkInfo->goodsCategory;
            $categoryMethod = $linkInfo->categoryMethod;
            $brandCd = $linkInfo->brandCd;
            $brandMethod = $linkInfo->brandMethod;
            $searchWordMethod = $linkInfo->searchWordMethod;
            $goodsSearchWord = $linkInfo->goodsSearchWord;
            $goodsSearchWordList = Array();
            if (! empty($searchWordMethod) && ! empty($goodsSearchWord)) {
                foreach (explode("#", preg_replace('# #', '#', $goodsSearchWord)) as $txt) {
                    $txt = trim($txt);
                    if (! empty($txt)) {
                        $goodsSearchWordList[] = $txt;
                    }
                }
            }
            if ($this->IsScmAdmin()) {
                $scmService = $this->GetServiceScm();
                foreach ($uidList as $goodsCode) {
                    try {
                        $reqGoodsInfo = new RequestVo(Array(
                            'goodsCode' => $goodsCode
                        ), false);
                        $goodsInfo = $this->GetGoodsInfoView($goodsCode);
                        $reqGoodsInfo->goodsCategory = $this->GetGoodsInfoMethodData($goodsInfo->goodsCategory, $goodsCategory, $categoryMethod);
                        if (! empty($searchWordMethod) && ! empty($goodsSearchWordList)) {
                            $oldGoodsSearchWord = Array();
                            foreach (explode("#", preg_replace('# #', '#', $goodsInfo->goodsSearchWord)) as $txt) {
                                $txt = trim($txt);
                                if (! empty($txt)) {
                                    $oldGoodsSearchWord[] = $txt;
                                }
                            }
                            $goodsSearchWord = $this->GetGoodsInfoMethodData($oldGoodsSearchWord, $goodsSearchWordList, $searchWordMethod);
                            $newGoodsSearchWord = Array();
                            foreach ($goodsSearchWord as $txt) {
                                if (! empty($txt)) {
                                    $newGoodsSearchWord[] = "#" . $txt;
                                }
                            }
                            $reqGoodsInfo->goodsSearchWord = implode(" ", $newGoodsSearchWord);
                        }
                        if ($brandMethod == 'Y' && ! empty($brandCd)) {
                            $reqGoodsInfo->brandCd = $brandCd;
                        }
                        $scmService->GetGoodsInfoUpdate($goodsCode, $reqGoodsInfo, $this->loginInfo);
                    } catch (Exception $ex) {
                        print_r($ex);
                    }
                }
            } else {
                foreach ($uidList as $goodsCode) {
                    try {
                        $goodsInfo = $this->GetGoodsInfoView($goodsCode);
                        $goodsInfo->goodsCategory = $this->GetGoodsInfoMethodData($goodsInfo->goodsCategory, $goodsCategory, $categoryMethod);
                        if (! empty($searchWordMethod) && ! empty($goodsSearchWordList)) {
                            $oldGoodsSearchWord = Array();
                            foreach (explode("#", preg_replace('# #', '#', $goodsInfo->goodsSearchWord)) as $txt) {
                                $txt = trim($txt);
                                if (! empty($txt)) {
                                    $oldGoodsSearchWord[] = $txt;
                                }
                            }
                            $goodsSearchWord = $this->GetGoodsInfoMethodData($oldGoodsSearchWord, $goodsSearchWordList, $searchWordMethod);
                            $newGoodsSearchWord = Array();
                            foreach ($goodsSearchWord as $txt) {
                                if (! empty($txt)) {
                                    $newGoodsSearchWord[] = "#" . $txt;
                                }
                            }
                            $goodsInfo->goodsSearchWord = implode(" ", $newGoodsSearchWord);
                        }
                        if ($brandMethod == 'Y' && ! empty($brandCd)) {
                            $goodsInfo->brandCd = $brandCd;
                        }
                        $reqGoodsInfo = new RequestVo($goodsInfo, false);
                        $this->GetGoodsInfoUpdate($goodsCode, $reqGoodsInfo);
                    } catch (Exception $ex) {}
                }
            }
        }
        return true;
    }

    /**
     * 상품 배송 정보 변경하기
     *
     * @param string[] $uidList
     * @param RequestVo $linkInfo
     * @return boolean
     */
    public function GetGoodsInfoChangeDelivery($uidList = Array(), RequestVo $linkInfo)
    {
        if (count($uidList) > 0) {
            $deliveryFl = $linkInfo->deliveryFl;
            $deliveryOption = $linkInfo->deliveryOption;
            $deliveryPackingRule = $linkInfo->deliveryPackingRule;
            $deliveryPackingPrice = $linkInfo->deliveryPackingPrice;
            if ($this->IsScmAdmin()) {
                $scmService = $this->GetServiceScm();
                foreach ($uidList as $goodsCode) {
                    try {
                        $reqGoodsInfo = new RequestVo(Array(
                            'goodsCode' => $goodsCode
                        ), false);
                        if ($deliveryFl != '') {
                            $reqGoodsInfo->deliveryFl = $deliveryFl;
                        }
                        if ($deliveryOption != '') {
                            $reqGoodsInfo->deliveryOption = $deliveryOption;
                        }
                        if ($deliveryPackingRule != '') {
                            $reqGoodsInfo->deliveryPackingRule = $deliveryPackingRule;
                            if (! empty($deliveryPackingPrice)) {
                                $reqGoodsInfo->deliveryPackingPrice = $deliveryPackingPrice;
                            }
                        }
                        $scmService->GetGoodsInfoUpdate($goodsCode, $reqGoodsInfo, $this->loginInfo);
                        print_r($reqGoodsInfo->GetData());
                    } catch (Exception $ex) {}
                }
            } else {
                foreach ($uidList as $goodsCode) {
                    try {
                        $goodsInfo = $this->GetGoodsInfoView($goodsCode);
                        if ($deliveryFl != '') {
                            $goodsInfo->deliveryFl = $deliveryFl;
                        }
                        if ($deliveryOption != '') {
                            $goodsInfo->deliveryOption = $deliveryOption;
                        }
                        if ($deliveryPackingRule != '') {
                            $goodsInfo->deliveryPackingRule = $deliveryPackingRule;
                            if (! empty($deliveryPackingPrice)) {
                                $goodsInfo->deliveryPackingPrice = $deliveryPackingPrice;
                            }
                        }
                        $reqGoodsInfo = new RequestVo($goodsInfo, false);
                        $this->GetGoodsInfoUpdate($goodsCode, $reqGoodsInfo);
                    } catch (Exception $ex) {}
                }
            }
        }
        return true;
    }

    /**
     * 상품 지급 마일리지 변경하기
     *
     * @param array $uidList
     * @param RequestVo $mileageInfo
     * @return boolean
     */
    public function GetGoodsInfoChangeMileage($uidList = Array(), RequestVo $mileageInfo)
    {
        $searchVo = new GoodsSearchVo();
        $searchVo->mallId = $this->mallId;
        $searchVo->goodsCodes = $uidList;
        if (count($searchVo->goodsCodes) > 0) {
            $batchForm = new stdClass();
            $batchForm->mileageFl = $mileageInfo->mileageFl;
            $batchForm->mileageGoodsUnit = $mileageInfo->mileageGoodsUnit;
            $batchForm->mileageGoods = $mileageInfo->mileageGoods;
            $batchForm->mileageGroup = $mileageInfo->mileageGroup;
            $searchVo->batchForm = $batchForm;
            $this->GetGoodsInfoDao()->SetMileage($searchVo);
            foreach ($uidList as $goodsCode) {
                parent::UnSetCacheFile(parent::GetServiceCacheKey('goodsInfo', $goodsCode, '*'));
            }
        }
        return true;
    }

    /**
     * 상품 판매 가격 변경하기
     *
     * @param array $uidList
     * @param RequestVo $priceInfo
     * @return boolean
     */
    public function GetGoodsInfoChangePrice($uidList = Array(), RequestVo $priceInfo)
    {
        $searchVo = new GoodsSearchVo();
        $searchVo->mallId = $this->mallId;
        $searchVo->goodsCodes = $uidList;

        if (count($searchVo->goodsCodes) > 0) {
            $batchForm = new stdClass();
            $batchForm->batchType = $priceInfo->batchType;
            $batchForm->goodsPrice = doubleval($priceInfo->goodsPrice);
            $batchForm->costPrice = doubleval($priceInfo->costPrice);
            $batchForm->fixedPrice = doubleval($priceInfo->fixedPrice);
            $batchForm->fromPrice = $priceInfo->fromPrice;
            $batchForm->price = doubleval($priceInfo->price);
            $batchForm->priceUnit = $priceInfo->priceUnit;
            switch ($priceInfo->priceMethod) {
                case 'M':
                    $batchForm->price = $batchForm->price * - 1;
                    break;
            }
            $useCommission = $priceInfo->useCommission;
            $batchForm->commission = - 1;
            if ($useCommission != '') {
                $batchForm->useCommission = $useCommission;
                $batchForm->commission = floatval($priceInfo->commission);
            }
            $batchForm->toPrice = $priceInfo->toPrice;
            $searchVo->batchForm = $batchForm;
            if ($this->IsScmAdmin()) {
                if ($batchForm->batchType == 'R' || $batchForm->goodsPrice > 0 || $batchForm->fixedPrice || $batchForm->costPrice) {
                    $scmService = $this->GetServiceScm();
                    foreach ($searchVo->goodsCodes as $goodsCode) {
                        $goodsInfo = $this->GetGoodsInfoView($goodsCode);
                        $reqGoodsInfo = new RequestVo(Array(
                            'goodsCode' => $goodsCode
                        ), false);
                        switch ($batchForm->batchType) {
                            case 'R':
                                $endPrice = 0;
                                switch ($batchForm->fromPrice) {
                                    case 'F':
                                        $endPrice = $reqGoodsInfo->fixedPrice;
                                        break;
                                    case 'C':
                                        $endPrice = $goodsInfo->costPrice;
                                        break;
                                    case 'P':
                                    default:
                                        $endPrice = $goodsInfo->goodsPrice;
                                        break;
                                }
                                switch ($batchForm->priceUnit) {
                                    case 'P':
                                        $endPrice = max(0, $endPrice * (1 + $batchForm->price / 100));
                                        break;
                                    default:
                                        $endPrice = max(0, $endPrice + $batchForm->price);
                                        break;
                                }
                                if (! empty($endPrice) && $endPrice > 0) {
                                    switch ($batchForm->toPrice) {
                                        case 'F':
                                            $reqGoodsInfo->fixedPrice = $endPrice;
                                            break;
                                        case 'C':
                                            $reqGoodsInfo->costPrice = $endPrice;
                                            break;
                                        case 'P':
                                        default:
                                            $reqGoodsInfo->goodsPrice = $endPrice;
                                            break;
                                    }
                                }
                                break;
                            case 'F':
                                if (! empty($batchForm->goodsPrice) && $batchForm->goodsPrice > 0) {
                                    $reqGoodsInfo->goodsPrice = $batchForm->goodsPrice;
                                }
                                if (! empty($batchForm->costPrice) && $batchForm->costPrice > 0) {
                                    $reqGoodsInfo->costPrice = $batchForm->costPrice;
                                }
                                if (! empty($batchForm->fixedPrice) && $batchForm->fixedPrice > 0) {
                                    $reqGoodsInfo->fixedPrice = $batchForm->fixedPrice;
                                }
                                break;
                        }
                        $scmService->GetGoodsInfoUpdate($goodsCode, $reqGoodsInfo, $this->loginInfo);
                    }
                }
            } else {
                if ($batchForm->batchType == 'R' || $batchForm->goodsPrice > 0 || $batchForm->fixedPrice || $batchForm->costPrice) {
                    $this->GetGoodsInfoDao()->SetPrice($searchVo);
                }
                if ($batchForm->commission >= - 1 && ! empty($batchForm->useCommission)) {
                    $this->GetGoodsInfoDao()->SetCommission($searchVo);
                }
                foreach ($uidList as $goodsCode) {
                    parent::UnSetCacheFile(parent::GetServiceCacheKey('goodsInfo', $goodsCode, '*'));
                }
            }
        }
        return true;
    }

    /**
     * 상품 판매 가격 변경하기
     *
     * @param array $uidList
     * @param RequestVo $priceInfo
     * @return boolean
     */
    public function GetGoodsInfoChangeOthers($uidList = Array(), RequestVo $othersInfo)
    {
        $searchVo = new GoodsSearchVo();
        $searchVo->mallId = $this->mallId;
        $searchVo->goodsCodes = $uidList;
        $infoMethod = $othersInfo->infoMethod;
        if (count($searchVo->goodsCodes) > 0 && count($infoMethod) > 0) {
            $batchForm = new stdClass();
            $batchForm->sellerMemNo = 0;
            if (in_array("SELLER", $infoMethod)) {
                $batchForm->sellerMemNo = $othersInfo->sellerMemNo;
            }
            $batchForm->originNm = '';
            if (in_array("ORIGIN", $infoMethod)) {
                $batchForm->originNm = $othersInfo->originNm;
            }
            $batchForm->makerNm = '';
            if (in_array("MAKER", $infoMethod)) {
                $batchForm->makerNm = $othersInfo->makerNm;
            }
            if (! empty($batchForm->sellerMemNo) || ! empty($batchForm->originNm) || ! empty($batchForm->makerNm)) {
                if ($this->IsScmAdmin()) {
                    $scmService = $this->GetServiceScm();
                    $scmRequest = new RequestVo(Array(
                        'sellerMemNo' => ''
                    ), false);
                    if (! empty($batchForm->originNm)) {
                        $scmRequest->originNm = $batchForm->originNm;
                    }
                    if (! empty($batchForm->makerNm)) {
                        $scmRequest->makerNm = $batchForm->makerNm;
                    }
                    foreach ($searchVo->goodsCodes as $goodsCode) {
                        $scmService->GetGoodsInfoUpdate($goodsCode, $scmRequest, $this->loginInfo);
                    }
                } else {
                    $searchVo->batchForm = $batchForm;
                    $this->GetGoodsInfoDao()->SetInfoOthers($searchVo);
                    foreach ($uidList as $goodsCode) {
                        parent::UnSetCacheFile(parent::GetServiceCacheKey('goodsInfo', $goodsCode, '*'));
                    }
                }
            }
        }
        return true;
    }

    /**
     * 상품 정보 복제하기
     *
     * @param GoodsInfoVo $vo
     * @return GoodsInfoVo
     */
    public function GetGoodsInfoClone(GoodsInfoVo $vo)
    {
        /**
         *
         * @var GoodsInfoVo $cloneVo
         */
        $cloneVo = clone $vo;
        if (! empty($cloneVo->goodsImageMaster)) {
            $cloneVo->goodsImageMaster = $this->GetUploadFileCopy($cloneVo->goodsImageMaster);
        }
        if (! empty($cloneVo->goodsImageAdded)) {
            $cloneVo->goodsImageAdded = $this->GetUploadFilesCopy($cloneVo->goodsImageAdded);
        }
        if (! empty($cloneVo->goodsDescription)) {
            $cloneVo->goodsDescription = $this->GetEditorParseRaw($cloneVo->goodsDescription);
        }
        if (! empty($cloneVo->goodsDescriptionLocale)) {
            $cloneVo->goodsDescriptionLocale = $this->GetEditorParseRawLocale($cloneVo->goodsDescriptionLocale);
        }
        if (! empty($cloneVo->goodsDescriptionMobile)) {
            $cloneVo->goodsDescriptionMobile = $this->GetEditorParseRaw($cloneVo->goodsDescriptionMobile);
        }
        if (! empty($cloneVo->goodsDescriptionMobileLocale)) {
            $cloneVo->goodsDescriptionMobileLocale = $this->GetEditorParseRawLocale($cloneVo->goodsDescriptionMobileLocale);
        }
        if (! empty($cloneVo->options)) {
            $cloneVo->options = $this->GetOptionTreeVoListClone($cloneVo->options);
        }
        if (! empty($cloneVo->optionsExt)) {
            $cloneVo->optionsExt = $this->GetOptionTreeVoListClone($cloneVo->optionsExt);
        }
        if (! empty($cloneVo->optionsText)) {
            $cloneVo->optionsText = $this->GetOptionTreeVoListClone($cloneVo->optionsText);
        }
        if (! empty($cloneVo->optionsRef)) {
            $cloneVo->optionsRef = $this->GetOptionTreeVoListClone($cloneVo->optionsRef);
        }
        return $cloneVo;
    }

    /**
     * 경매 관리 DAO 가져오기
     *
     * @return \Dao\GoodsInfoAuctionDao
     */
    public function GetAuctionInfoDao()
    {
        return parent::GetDao('GoodsInfoAuctionDao');
    }

    /**
     * 경매 정보 캐쉬 삭제하고 관련 정보 로그 남기기
     *
     * @param GoodsInfoAuctionVo $oldVo
     * @param GoodsInfoAuctionVo $newVo
     */
    private function UnsetGoodsInfoAuctionCache(GoodsInfoAuctionVo $oldVo, GoodsInfoAuctionVo $newVo)
    {
        if ($oldVo !== $newVo) {
            $log = $this->getAccessLog($oldVo, $newVo, 'auctionInfo.');
            $this->setAccessLog('AuctionInfo.' . $oldVo->auctionCode, $log);
            $this->SetFileLogUpdate($newVo->auctionImageMaster, $oldVo->auctionImageMaster, 'auction', $newVo->auctionCode, 'auctionImageMaster');
            $this->SetFileLogUpdate($newVo->auctionImageAdded, $oldVo->auctionImageAdded, 'auction', $newVo->auctionCode, 'auctionImageAdded');
            $this->SetEditorFileLogUpdate($newVo->detailInfoAuctionExchange, $oldVo->detailInfoAuctionExchange, 'auction', $newVo->auctionCode, 'detailInfoAuctionExchange');
        }
        if (! empty($newVo)) {
            parent::UnSetCacheFile(parent::GetServiceCacheKey('auctionInfo', $newVo->auctionCode, '*'));
        } else {
            parent::UnSetCacheFile(parent::GetServiceCacheKey('auctionInfo', '*'));
        }
    }

    /**
     * 상품 정보를 간략상품 정보로 변환해서 가져오기
     *
     * @param GoodsInfoAuctionVo $goodsInfo
     * @return GoodsInfoAuctionSimpleVo
     */
    public function GetAuctionInfoAsSimpleView(GoodsInfoAuctionVo $goodsInfo)
    {
        $result = new GoodsInfoAuctionSimpleVo();
        $this->GetGoodsInfoAsSimpleView($goodsInfo, $result);
        $result->auctionCode = $goodsInfo->auctionCode;
        $result->auctionImageMaster = $goodsInfo->auctionImageMaster;
        $result->auctionImageAdded = $goodsInfo->auctionImageAdded;
        $result->auctionStartYmd = $goodsInfo->auctionStartYmd;
        $result->auctionEndYmd = $goodsInfo->auctionEndYmd;
        $result->startPrice = $goodsInfo->startPrice;
        $result->endPriceFl = $goodsInfo->endPriceFl;
        $result->endPrice = $goodsInfo->endPrice;
        $result->currPrice = $goodsInfo->currPrice;
        $result->unitPrice = $goodsInfo->unitPrice;
        $result->saleCnt = $goodsInfo->saleCnt;
        $result->bidderCnt = $goodsInfo->bidderCnt;
        $result->minSaleCnt = $goodsInfo->minSaleCnt;
        $result->maxSaleCnt = $goodsInfo->maxSaleCnt;
        $result->auctionStatus = $goodsInfo->auctionStatus;
        return $result;
    }

    /**
     * 경매 정보 목록으로 가져오기
     *
     * @param RequestVo $request
     * @param LoginInfoVo $loginInfoVo
     * @param string $displayType
     * @param string $isHidden
     * @return \Vo\PagingVo
     */
    public function GetAuctionInfoPaging(RequestVo $request, LoginInfoVo $loginInfoVo = null, $displayType = '', $isHidden = 'N')
    {
        $vo = $this->GetGoodsSearchVo($request, $loginInfoVo, $displayType);
        $vo->isHiddenFl = $isHidden;
        $result = $this->GetAuctionInfoDao()->GetPaging($vo, $request->GetPerPage(10), $request->GetOffset());
        if (! empty($displayType)) {
            $simpleList = Array();
            foreach ($result->items as $item) {
                $simpleList[] = $this->GetAuctionInfoAsSimpleView($item);
            }
            $this->GetGoodsPriceScmCheck($loginInfoVo, $simpleList, $displayType);
            $result->items = $simpleList;
        } else {
            $this->GetGoodsPriceScmCheck($loginInfoVo, $result->items);
        }
        return $result;
    }

    /**
     * 경매 정보 목록으로 가져오기
     *
     * @param RequestVo $request
     * @param LoginInfoVo $loginInfoVo
     * @param string $displayType
     * @param string $isHidden
     * @return GoodsInfoAuctionVo[]
     */
    public function GetAuctionInfoList(RequestVo $request, LoginInfoVo $loginInfoVo = null, $displayType = '')
    {
        $vo = $this->GetGoodsSearchVo($request, $loginInfoVo, $displayType);
        $result = $this->GetAuctionInfoDao()->GetList($vo, $request->GetPerPage(10), $request->GetOffset());
        if (! empty($displayType)) {
            $simpleList = Array();
            foreach ($result as $item) {
                $simpleList[] = $this->GetGoodsInfoAsSimpleView($item);
            }
            $this->GetGoodsPriceScmCheck($loginInfoVo, $simpleList, $displayType);
            return $simpleList;
        } else {
            $this->GetGoodsPriceScmCheck($loginInfoVo, $result);
        }
        return $result;
    }

    /**
     * 상품 정보 복제하기
     *
     * @param string $uid
     * @param RequestVo $request
     * @return GoodsInfoVo
     */
    public function GetAuctionInfoCopy($uid = '', RequestVo $request = null)
    {
        $vo = $this->GetAuctionInfoClone($this->GetAuctionInfoView($uid));
        $reqCopy = new RequestVo($vo);
        return $this->GetAuctionInfoCreate($reqCopy);
    }

    /**
     * 경매 정보 업데이트 하기
     *
     * @param string $uid
     * @param RequestVo $request
     * @return GoodsInfoAuctionVo
     */
    public function GetAuctionInfoUpdate($uid = '', RequestVo $request = null)
    {
        $oldView = $this->GetAuctionInfoView($uid);
        $cloneView = clone $oldView;
        $vo = $this->GetGoodsInfoAuctionVo($uid, $request, $cloneView);
        $this->GetGoodsInfoAuctionParse($vo, $request, $oldView);
        if ($this->GetAuctionInfoDao()->SetUpdate($vo)) {
            $this->UnsetGoodsInfoAuctionCache($oldView, $vo);
            $this->GetAuctionInfoUpdateStatus($uid);
            $vo->modDate = $this->getDateNow();
            return $vo;
        } else {
            parent::GetException(KbmException::DATA_ERROR_UPDATE);
        }
    }

    /**
     * 경매 정보 업데이트 하기
     *
     * @param string $uid
     * @param RequestVo $request
     * @return GoodsInfoAuctionVo
     */
    public function GetAuctionInfoUpdateStatus($uid = '')
    {
        $bidderVo = new GoodsInfoAuctionBidderVo();
        $bidderVo->mallId = $this->mallId;
        $bidderVo->auctionCode = $uid;
        $auctionInfo = $this->GetAuctionInfoView($uid);
        switch ($auctionInfo->auctionBidderStatus) {
            case 'Y':
                $bidList = $this->GetAuctionInfoDao()->GetBidderList($bidderVo, 1000, 0);
                $saleCnt = $auctionInfo->saleCnt;
                $bidMap = Array();
                $changeList = Array();
                foreach ($bidList as $bidderInfo) {
                    switch ($bidderInfo->soldFl) {
                        case 'Y':
                            if (empty($bidderInfo->bidedCnt)) {
                                $bidderInfo->bidedCnt = $bidderInfo->auctionCnt;
                            }
                            $saleCnt -= $bidderInfo->bidedCnt;
                            $auctionSumPrice = $bidderInfo->bidedCnt * $bidderInfo->auctionPrice;
                            if ($auctionSumPrice != $bidderInfo->auctionSumPrice) {
                                $bidderInfo->auctionSumPrice = $auctionSumPrice;
                                $changeList[] = $bidderInfo;
                            }
                            break;
                        default:
                            $bidKey = str_pad($bidderInfo->auctionPrice, 20, '0', STR_PAD_LEFT) . '_' . str_pad(5000000000 - strtotime($bidderInfo->regDate), 20, '0', STR_PAD_LEFT) . '_' . str_pad($bidderInfo->memNo, 10, '0', STR_PAD_LEFT);
                            $bidMap[$bidKey] = $bidderInfo;
                            break;
                    }
                }
                krsort($bidMap);
                foreach ($bidMap as $bidderInfo) {
                    $soldFl = 'N';
                    $bidedCnt = 0;
                    if ($saleCnt > 0) {
                        $soldFl = 'E';
                        $bidedCnt = max(0, min($saleCnt, $bidderInfo->auctionCnt));
                    }
                    if ($soldFl != $bidderInfo->soldFl || $bidedCnt != $bidderInfo->bidedCnt) {
                        $bidderInfo->bidedCnt = $bidedCnt;
                        $bidderInfo->soldFl = $soldFl;
                        $bidderInfo->auctionSumPrice = $bidderInfo->bidedCnt * $bidderInfo->auctionPrice;
                        $changeList[] = $bidderInfo;
                    }
                    if ($soldFl == 'Y' || $soldFl == 'E') {
                        $saleCnt -= $bidderInfo->bidedCnt;
                    }
                }
                foreach ($changeList as $bidderInfo) {
                    $this->GetAuctionInfoDao()->SetBidderUpdate($bidderInfo);
                }
                break;
        }
        $this->GetAuctionInfoDao()->SetUpdateStatus($bidderVo);
    }

    /**
     * 경매 정보 업데이트 하기
     *
     * @param string $uid
     * @param RequestVo $request
     * @return GoodsInfoAuctionVo
     */
    public function GetAuctionInfoBidderCreate($uid = '', RequestVo $request = null, LoginInfoVo $loginInfo = null, $isAdmin = false)
    {
        $oldView = $this->GetAuctionInfoView($uid);
        if (! empty($loginInfo)) {
            switch ($oldView->auctionBidderStatus) {
                case 'Y':
                    break;
                default:
                    parent::GetException(KbmException::DATA_ERROR_AUTH);
                    break;
            }
        }
        $vo = new GoodsInfoAuctionBidderVo();
        parent::GetFill($request, $vo);
        $vo->auctionCode = $uid;
        if (! empty($loginInfo)) {
            $vo->memNo = $loginInfo->memNo;
            if ($vo->auctionCnt > $oldView->maxSaleCnt || $vo->auctionCnt < $oldView->minSaleCnt) {
                parent::GetException(KbmException::DATA_ERROR_AUTH);
            }
            if ($vo->auctionPrice < $oldView->startPrice) {
                parent::GetException(KbmException::DATA_ERROR_AUTH);
            }
        } else {
            $vo->memNo = $request->memNo;
        }
        if (empty($vo->memNo)) {
            parent::GetException(KbmException::DATA_ERROR_AUTH);
        }
        $memInfo = $this->GetServiceMember()->GetMemberView($vo->memNo, '', true);
        $vo->memNm = $memInfo->memNm;
        $vo->nickNm = $memInfo->nickNm;
        $vo->memType = 'M';
        $vo->memId = $memInfo->memId;
        $vo->memIp = $this->GetUserIp();
        $vo->email = $memInfo->email;
        $vo->goodsCnt = $vo->bidedCnt;
        $vo->goodsNm = $oldView->goodsNm;
        $vo->goodsNmLocale = $oldView->goodsNmLocale;
        $vo->goodsPrice = $vo->auctionPrice;
        $vo->auctionSumPrice = $vo->goodsCnt * $vo->goodsPrice;
        $oldBidderInfo = $this->GetAuctionInfoDao()->GetBidderView($vo);
        if (! empty($oldBidderInfo)) {
            $vo->modDate = $this->getDateNow();
            $vo->regDate = $oldBidderInfo->regDate;
        } else {
            $vo->modDate = $vo->regDate = $this->getDateNow();
        }
        if ($this->GetAuctionInfoDao()->SetBidderCreate($vo)) {
            $this->GetAuctionInfoUpdateStatus($uid);
            $vo->regDate = $vo->modDate = $this->getDateNow();
            return $vo;
        } else {
            parent::GetException(KbmException::DATA_ERROR_CREATE);
        }
    }

    /**
     * 경매 정보 업데이트 하기
     *
     * @param string $uid
     * @param RequestVo $request
     * @return GoodsInfoAuctionVo
     */
    public function GetAuctionInfoBidderDelete($uid = '', $memNos = Array(), $isAdmin = false)
    {
        if (! empty($memNos) && is_array($memNos)) {
            $vo = new GoodsInfoAuctionBidderVo();
            $vo->mallId = $this->mallId;
            $vo->auctionCode = $uid;
            $changeCnt = 0;
            foreach ($memNos as $memNo) {
                $vo->memNo = $memNo;
                if ($this->GetAuctionInfoDao()->SetBidderDelete($vo)) {
                    $changeCnt ++;
                }
            }
            if ($changeCnt > 0) {
                $this->GetAuctionInfoUpdateStatus($uid);
            }
        }
        return true;
    }

    /**
     * 경매 정보 업데이트 하기
     *
     * @param string $uid
     * @param RequestVo $request
     * @return GoodsInfoAuctionVo
     */
    public function GetAuctionInfoBidderWin($uid = '', $memNos = Array(), $isAdmin = false)
    {
        if (! empty($memNos) && is_array($memNos)) {
            $vo = new GoodsInfoAuctionBidderVo();
            $vo->mallId = $this->mallId;
            $vo->auctionCode = $uid;
            $changeCnt = 0;
            foreach ($memNos as $memNo) {
                $vo->memNo = $memNo;
                $oldVo = $this->GetAuctionInfoDao()->GetBidderView($vo);
                if (! empty($oldVo) && $oldVo->soldFl != 'Y') {
                    $oldVo->soldFl = 'Y';
                    if ($this->GetAuctionInfoDao()->SetBidderUpdate($oldVo)) {
                        $changeCnt ++;
                    }
                }
            }
            if ($changeCnt > 0) {
                $this->GetAuctionInfoUpdateStatus($uid);
            }
        }
        return true;
    }

    /**
     * 경매 정보 업데이트 하기
     *
     * @param string $uid
     * @param RequestVo $request
     * @return GoodsInfoAuctionVo[]
     */
    public function GetAuctionInfoBidderList($uid = '', RequestVo $request = null, $isAdmin = false)
    {
        $vo = new GoodsInfoAuctionBidderVo();
        parent::GetSearchVo($request, $vo);
        $vo->auctionCode = $uid;
        $result = $this->GetAuctionInfoDao()->GetBidderList($vo, $request->GetPerPage(10), $request->GetOffset());
        if (! $isAdmin) {}
        return $result;
    }

    /**
     * 특정상품 삭제 하기
     *
     * @param string $uid
     * @return GoodsInfoVo
     */
    public function GetAuctionInfoDelete($uid = '')
    {
        $vo = $this->GetGoodsInfoAuctionVo($uid);
        if ($this->GetAuctionInfoDao()->SetDelete($vo)) {
            $oldVo = new GoodsInfoAuctionVo();
            $oldVo->auctionCode = $vo->auctionCode;
            $this->UnsetGoodsInfoAuctionCache($vo, $oldVo);
            return $vo;
        } else {
            parent::GetException(KbmException::DATA_ERROR_DELETE);
        }
    }

    /**
     * 상품정보 PDF 파일 생성하기
     *
     * @param RequestVo $request
     * @return PdfVo
     */
    public function GetAuctionInfoListPdf(RequestVo $request = null)
    {
        $downloadFormVo = $this->GetDownloadFormVo($request, 'goodsCodes', true);
        $data = $this->GetAuctionInfoList($downloadFormVo->searchRequest);
        $pdfVo = new PdfVo($downloadFormVo, $data);
        $pdfVo->SetFontSize(10);
        if (! empty($downloadFormVo->password)) {
            $pdfVo->SetPassword($downloadFormVo->password);
        }
        return $pdfVo;
    }

    /**
     * 경매 목록 Excel 파일로 가져오기
     *
     * @param RequestVo $request
     * @param string $isHidden
     * @return ExcelVo
     */
    public function GetAuctionInfoListExcel(RequestVo $request = null, $isHidden = 'N')
    {
        $downloadFormVo = $this->GetDownloadFormVo($request, 'auctionCodes');
        $excelData = $this->GetAuctionInfoList($downloadFormVo->searchRequest, null, '');
        $excelVo = new ExcelVo($downloadFormVo, $excelData);
        $excelVo->AddHeaderList($downloadFormVo->fieldList, true);
        return $excelVo;
    }

    /**
     * 상품 정보 파싱
     *
     * @param GoodsInfoAuctionVo $result
     * @return GoodsInfoAuctionVo
     */
    public function GetAuctionInfoViewParse(GoodsInfoAuctionVo $result)
    {
        $this->GetGoodsInfoViewParse($result);
        return $result;
    }

    /**
     * 상품 정보 복제하기
     *
     * @param GoodsInfoVo $vo
     * @return GoodsInfoVo
     */
    public function GetAuctionInfoClone(GoodsInfoAuctionVo $vo)
    {
        /**
         *
         * @var GoodsInfoAuctionVo $cloneVo
         */
        $cloneVo = clone $vo;
        if (! empty($cloneVo->auctionImageMaster)) {
            $cloneVo->auctionImageMaster = $this->GetUploadFileCopy($cloneVo->auctionImageMaster);
        }
        if (! empty($cloneVo->auctionImageAdded)) {
            $cloneVo->auctionImageAdded = $this->GetUploadFilesCopy($cloneVo->auctionImageAdded);
        }
        if (! empty($cloneVo->detailInfoAuctionExchange)) {
            $cloneVo->detailInfoAuctionExchange = $this->GetEditorParseRaw($cloneVo->detailInfoAuctionExchange);
        }
        return $cloneVo;
    }

    /**
     * 경매 정보 가져오기
     *
     * @param string $uid
     * @param LoginInfoVo $loginInfoVo
     * @param boolean $isCopy
     * @param string $displayType
     * @return GoodsInfoAuctionVo
     */
    public function GetAuctionInfoView($uid = '', LoginInfoVo $loginInfoVo = null, $isCopy = false, $displayType = '')
    {
        $uidKey = parent::GetServiceCacheKey('auctionInfo', $uid);
        $result = parent::GetCacheFile($uidKey, true);
        if (empty($result) || ! ($result instanceof GoodsInfoAuctionVo)) {
            $vo = $this->GetGoodsInfoAuctionVo($uid);
            $result = $this->GetAuctionInfoDao()->GetView($vo);
            if (! empty($result)) {
                $this->GetAuctionInfoViewParse($result);
                parent::SetCacheFile($uidKey, $result);
            } else {
                $this->GetException(KbmException::DATA_ERROR_VIEW);
            }
        }
        switch ($result->auctionStatus) {
            case 'Y':
                $auctionStartYmd = strtotime($result->auctionStartYmd);
                $auctionEndYmd = strtotime($result->auctionEndYmd);
                $auctionNowYmd = time();
                if ($auctionStartYmd < $auctionNowYmd && $auctionEndYmd > $auctionNowYmd) {
                    $result->auctionBidderStatus = 'Y';
                } else if ($auctionStartYmd > $auctionNowYmd) {
                    $result->auctionBidderStatus = 'N';
                } else {
                    $result->auctionBidderStatus = 'E';
                }
                break;
            default:
                $result->auctionBidderStatus = $result->auctionStatus;
                break;
        }
        if ($isCopy && ! empty($result)) {
            return $this->GetAuctionInfoClone($result);
        } else {
            return $result;
        }
    }

    /**
     * 경매 간략 정보 가져오기
     *
     * @param string $uid
     * @param string $displayType
     * @param string $groupSno
     * @return GoodsInfoSimpleVo
     */
    public function GetAuctionInfoSimpleView($uid = '', $displayType = 'web', $groupSno = '')
    {
        $uidKey = parent::GetServiceCacheKey('auctionInfo', $uid, 'simple');
        $result = parent::GetCacheFile($uidKey, true);
        if (empty($result) || ! ($result instanceof GoodsInfoAuctionSimpleVo)) {
            try {
                $result = $this->GetGoodsInfoAsSimpleView($this->GetAuctionInfoView($uid));
                if (! empty($groupSno)) {
                    $this->GetGoodsPriceScmCheckGroupCode(Array(
                        $result
                    ), $displayType, $groupSno);
                } else {
                    $this->GetGoodsPriceScmCheck(null, Array(
                        $result
                    ), $displayType);
                }
            } catch (Exception $ex) {
                $result = new GoodsInfoAuctionSimpleVo();
                $result->goodsCode = $uid;
                $result->goodsNm = 'No name goods';
            }
            parent::SetCacheFile($uidKey, $result);
        }
        return $result;
    }

    /**
     * 상품 카트 아이템 가져오기
     *
     * @param LoginInfoVo $loginInfoVo
     * @param RequestVo $request
     * @return GoodsCartPriceVo
     */
    public function GetAuctionCartPrice(LoginInfoVo $loginInfoVo, RequestVo $request)
    {
        $goodsCartPrice = new GoodsCartPriceVo();
        $goodsCartPrice->items = $this->GetGoodsCartPriceItemList($request, 'A');
        $checkedItems = Array();
        $addressVo = new AddressVo();
        if ($request->hasKey('address')) {
            $request->GetFill($addressVo, 'address');
            $loginInfoVo->address = $addressVo;
        } else if (! empty($loginInfoVo) && ! empty($loginInfoVo->address)) {
            $addressVo = clone $loginInfoVo->address;
        }
        $preDeliveryPay = true;
        if ($request->hasKey('deliveryType')) {
            switch ($request->deliveryType) {
                case 'N':
                    $preDeliveryPay = false;
                    break;
                case 'Y':
                default:
                    $preDeliveryPay = true;
                    break;
            }
        }
        foreach ($goodsCartPrice->items as $item) {
            $item = $this->GetGoodsCartVo($item, $loginInfoVo, $preDeliveryPay);
            if (! empty($item)) {
                $checkedItems[] = $item;
            }
        }
        $goodsCartPrice->items = $checkedItems;
        $goodsCartPrice->orderInfo = new GoodsCartOrderInfoVo();
        $goodsCartPrice->orderInfo->recvAddress = $addressVo;
        $selectedCurrency = $request->selectedCurrency;
        $payInfo = new GoodsCartPayInfoVo();
        if (! empty($selectedCurrency)) {
            $goodsCartPrice->selectedCurrency = $selectedCurrency;
        } else {
            $goodsCartPrice->selectedCurrency = MST_CURRENCY;
        }
        if ($request->hasKey('deliveryCoupon')) {
            $goodsCartPrice->deliveryCoupon = $request->GetFill(new MemberCouponVo(), 'deliveryCoupon');
        }
        if ($request->hasKey('orderCoupon')) {
            $goodsCartPrice->orderCoupon = $request->GetFill(new MemberCouponVo(), 'orderCoupon');
        }
        if ($request->hasKey('useMileage')) {
            $payInfo->useMileage = doubleval($request->useMileage);
        }
        $goodsCartPrice->payInfo = $payInfo;
        $orderService = $this->GetServiceOrder();
        $goodsCartPrice->loginInfoVo = $loginInfoVo;
        $goodsCartPrice->selectedPayType = $request->GetFill(new CodeVo(), 'selectedPayType');
        $policyService = $this->GetServicePolicy();
        $policyService->GetSettleSettlekindPgType($goodsCartPrice);
        $result = $orderService->GetOrderInfoOrderStatus($orderService->GetOrderInfoParse($goodsCartPrice), true, $preDeliveryPay, $request->checkStock == 'Y' ? true : false);
        return $result;
    }

    /**
     * 상품 정보 VO 가져오기
     *
     * @param string $uid
     * @param RequestVo $request
     * @param mixed $vo
     * @return GoodsInfoAuctionVo
     */
    public function GetGoodsInfoAuctionVo($uid = '', RequestVo $request = null, $vo = null)
    {
        $vo = parent::GetFill($request, empty($vo) ? 'GoodsInfoAuctionVo' : $vo);
        $vo->auctionCode = $uid;
        return $vo;
    }

    /**
     * 상품 정보 파싱하기
     *
     * @param GoodsInfoVo $vo
     * @param RequestVo $request
     * @param GoodsInfoVo $oldView
     * @return GoodsInfoVo
     */
    public function GetGoodsInfoAuctionParse(GoodsInfoAuctionVo $vo, RequestVo $request, GoodsInfoAuctionVo $oldView = null)
    {
        if ($request->hasKey('auctionImageMaster')) {
            $vo->auctionImageMaster = $this->GetUploadFile($request->auctionImageMaster, ! empty($oldView) ? $oldView->auctionImageMaster : '', 'auction');
        }
        if ($request->hasKey('auctionImageAdded')) {
            $vo->auctionImageAdded = $this->GetUploadFiles($request->auctionImageAdded, ! empty($oldView) ? $oldView->auctionImageAdded : '', 'auction');
        }
        if ($request->hasKey('detailInfoAuctionExchange')) {
            $vo->detailInfoAuctionExchange = $this->GetEditorParse($request->detailInfoAuctionExchange, ! empty($oldView) ? $oldView->detailInfoAuctionExchange : '', 'auction');
        }
        if (empty($vo->auctionStatus)) {
            $vo->auctionStatus = 'N';
        }
        if (empty($vo->detailInfoAuctionFl)) {
            $vo->detailInfoAuctionFl = 'N';
        }
        return $vo;
    }

    /**
     * 상품정보 생성하기
     *
     * @param RequestVo $request
     * @param boolean $isScmCreate
     * @return GoodsInfoAuctionVo
     */
    public function GetAuctionInfoCreate(RequestVo $request = null, $uid = '')
    {
        if (empty($uid)) {
            $vo = $this->GetGoodsInfoAuctionVo($this->GetUniqId("A" . date("Ymd")), $request);
        } else {
            $vo = $this->GetGoodsInfoAuctionVo($uid, $request);
        }

        $this->GetGoodsInfoAuctionParse($vo, $request, null);
        if ($this->GetAuctionInfoDao()->SetCreate($vo)) {
            $oldView = new GoodsInfoAuctionVo();
            $oldView->mallId = $vo->mallId;
            $oldView->auctionCode = $vo->auctionCode;
            $this->UnsetGoodsInfoAuctionCache($oldView, $vo);
            $this->GetAuctionInfoUpdateStatus($vo->auctionCode);
            $vo->regDate = $vo->modDate = $this->getDateNow();
            return $vo;
        } else {
            parent::GetException(KbmException::DATA_ERROR_CREATE);
        }
    }

    /**
     * 상품 옵션정보 복제하기
     *
     * @param OptionTreeVo[] $voList
     * @return OptionTreeVo[]
     */
    public function GetOptionTreeVoListClone($voList)
    {
        $optionsList = Array();
        foreach ($voList as $vo) {
            $cloneVo = clone $vo;
            if (! empty($cloneVo->optionImage) && $this->IsUploadImage($cloneVo->optionImage)) {
                $cloneVo->optionImage = $this->GetUploadFileCopy($cloneVo->optionImage);
            }
            $optionsList[] = $cloneVo;
        }
        return $optionsList;
    }

    /**
     * 상품 메인 전시 상품 추가/삭제/지정 하기
     *
     * @param DisplayMainItemVo $vo
     * @param array $goodsCodes
     * @param string $mode
     * @return DisplayMainItemVo
     */
    private function GetDisplayMainItemVo(DisplayMainItemVo $vo, $goodsCodes = Array(), $mode = 'K')
    {
        if (empty($vo) || ! ($vo instanceof DisplayMainItemVo)) {
            $vo = new DisplayMainItemVo();
        }
        switch ($mode) {
            case 'K':
                break;
            case 'A':
                if ($vo->sortAutoFl != 'U') {
                    $vo->sortAutoFl = 'U';
                }
                foreach ($goodsCodes as $goodsCode) {
                    if (! in_array($goodsCode, $vo->refGoodsCd)) {
                        $vo->refGoodsCd[] = $goodsCode;
                    }
                }
                break;
            case 'M':
                if ($vo->sortAutoFl != 'U') {
                    $vo->sortAutoFl = 'U';
                }
                $vo->refGoodsCd = [];
                foreach ($goodsCodes as $goodsCode) {
                    if (! in_array($goodsCode, $vo->refGoodsCd)) {
                        $vo->refGoodsCd[] = $goodsCode;
                    }
                }
                break;
            case 'D':
                $refGoodsCd = [];
                foreach ($vo->refGoodsCd as $goodsCode) {
                    if (! in_array($goodsCode, $goodsCodes)) {
                        $refGoodsCd = $goodsCode;
                    }
                }
                $vo->refGoodsCd = $refGoodsCd;
                break;
        }
        return $vo;
    }

    /**
     * 상품 정보 일괄 변경하기
     *
     * @param string[] $uidList
     * @param string $mode
     * @param stdClass $applyData
     * @return boolean
     */
    public function GetGoodsInfoChange($uidList = Array(), $mode = 'changeModDate', $applyData = null)
    {
        $searchVo = new GoodsSearchVo();
        $searchVo->mallId = $this->mallId;
        $searchVo->goodsCodes = $uidList;
        if (count($searchVo->goodsCodes) > 0) {
            switch ($mode) {
                case 'changeMain':
                    $policyService = $this->GetServicePolicy();
                    $displayMain = $policyService->GetDisplayMainView();
                    $displayMain->recomInfo = $this->GetDisplayMainItemVo($displayMain->recomInfo, $searchVo->goodsCodes, $applyData->recomInfo);
                    $displayMain->newInfo = $this->GetDisplayMainItemVo($displayMain->newInfo, $searchVo->goodsCodes, $applyData->newInfo);
                    $displayMain->hotInfo = $this->GetDisplayMainItemVo($displayMain->hotInfo, $searchVo->goodsCodes, $applyData->hotInfo);
                    $displayMain->saleInfo = $this->GetDisplayMainItemVo($displayMain->saleInfo, $searchVo->goodsCodes, $applyData->saleInfo);
                    $displayMain->eventInfo = $this->GetDisplayMainItemVo($displayMain->eventInfo, $searchVo->goodsCodes, $applyData->eventInfo);
                    $displayMain->bestInfo = $this->GetDisplayMainItemVo($displayMain->bestInfo, $searchVo->goodsCodes, $applyData->bestInfo);
                    $request = new RequestVo($displayMain);
                    $policyService->GetDisplayMainUpdate($request);
                    break;
                case 'changeDisplay':
                    $goodsDisplayFl = (isset($applyData->goodsDisplayFl)) ? $applyData->goodsDisplayFl : 'K';
                    $goodsDisplayMobileFl = (isset($applyData->goodsDisplayMobileFl)) ? $applyData->goodsDisplayMobileFl : 'K';
                    $goodsSellFl = (isset($applyData->goodsSellFl)) ? $applyData->goodsSellFl : 'K';
                    $goodsSellMobileFl = (isset($applyData->goodsSellMobileFl)) ? $applyData->goodsSellMobileFl : 'K';
                    if ($goodsDisplayFl == 'Y' || $goodsDisplayFl == 'N') {
                        $searchVo->goodsDisplayFl = $goodsDisplayFl;
                    } else {
                        $searchVo->goodsDisplayFl = '';
                    }
                    if ($goodsSellFl == 'Y' || $goodsSellFl == 'N') {
                        $searchVo->goodsSellFl = $goodsSellFl;
                    } else {
                        $searchVo->goodsSellFl = '';
                    }
                    if ($goodsDisplayMobileFl == 'Y' || $goodsDisplayMobileFl == 'N') {
                        $searchVo->goodsDisplayMobileFl = $goodsDisplayMobileFl;
                    } else {
                        $searchVo->goodsDisplayMobileFl = '';
                    }
                    if ($goodsSellMobileFl == 'Y' || $goodsSellMobileFl == 'N') {
                        $searchVo->goodsSellMobileFl = $goodsSellMobileFl;
                    } else {
                        $searchVo->goodsSellMobileFl = '';
                    }
                    if ($searchVo->goodsDisplayFl != '' || $searchVo->goodsSellFl != '' || $searchVo->goodsDisplayMobileFl != '' || $searchVo->goodsSellMobileFl != '') {
                        $this->GetGoodsInfoDao()->SetDisplayUpdate($searchVo);
                    }

                    break;
                case 'changeModDate':
                    $this->GetGoodsInfoDao()->SetModDateNow($searchVo);
                    break;
                case 'toSoldout':
                    $this->GetGoodsInfoDao()->SetSoldout($searchVo);
                    break;
                case 'changeCategory':
                    $goodsCategory = $applyData->goodsCategory;
                    $method = $applyData->method;
                    foreach ($searchVo->goodsCodes as $goodsCode) {
                        try {
                            $goodsInfoVo = $this->GetGoodsInfoView($goodsCode);
                            $goodsInfoVo->goodsCategory = $this->GetGoodsInfoMethodData($goodsInfoVo->goodsCategory, $goodsCategory, $method);
                            $reqGoodsInfo = new RequestVo($goodsInfoVo);
                            $this->GetGoodsInfoUpdate($goodsCode, $reqGoodsInfo);
                        } catch (Exception $ex) {}
                    }
                    break;
                case 'copy':
                    foreach ($searchVo->goodsCodes as $goodsCode) {
                        try {
                            $goodsInfoVo = $this->GetGoodsInfoView($goodsCode);
                            $reqGoodsInfo = new RequestVo($this->GetGoodsInfoClone($goodsInfoVo));
                            $this->GetGoodsInfoCreate($reqGoodsInfo);
                        } catch (Exception $ex) {}
                    }
                    break;
                case 'delete':
                    try {
                        foreach ($searchVo->goodsCodes as $goodsCode) {
                            $goodsInfoVo = $this->GetGoodsInfoView($goodsCode);
                            if (! empty($goodsInfoVo->goodsImageMaster)) {
                                $goodsInfoVo->goodsImageMaster = $this->GetUploadFile('', $goodsInfoVo->goodsImageMaster, 'goods/' . $goodsInfoVo->goodsCode);
                            }
                            if (! empty($goodsInfoVo->goodsImageAdded)) {
                                $goodsInfoVo->goodsImageAdded = $this->GetUploadFiles('', $goodsInfoVo->goodsImageAdded, 'goods/' . $goodsInfoVo->goodsCode);
                            }
                            $this->GetGoodsInfoDao()->SetDelete($goodsInfoVo);
                        }
                    } catch (Exception $ex) {}
                    break;
                case 'hidden':
                    $this->GetGoodsInfoDao()->SetHidden($searchVo);
                    break;
                case 'show':
                    $this->GetGoodsInfoDao()->SetShow($searchVo);
                    break;
            }
            foreach ($uidList as $goodsCode) {
                parent::UnSetCacheFile(parent::GetServiceCacheKey('goodsInfo', $goodsCode, '*'));
            }
        }
        return true;
    }

    /**
     * 상품 카테고리 DAO 가져오기
     *
     * @return \Dao\CategoryDao
     */
    public function GetCategoryDao()
    {
        return parent::GetDao('CategoryDao');
    }

    /**
     * 상품 카테고리 VO 가져오기
     *
     * @param string $uid
     * @param RequestVo $request
     * @param CategoryVo $vo
     * @return CategoryVo
     */
    public function GetCategoryVo($uid = '', RequestVo $request = null, $vo = null)
    {
        $vo = parent::GetFill($request, empty($vo) ? 'CategoryVo' : $vo);
        if (! empty($uid)) {
            $vo->categoryId = $uid;
        }
        return $vo;
    }

    /**
     * 상품 카테고리 정보 캐쉬 삭제하기
     *
     * @param CategoryVo $oldVo
     * @param CategoryVo $newVo
     */
    private function UnsetCategoryCache(CategoryVo $oldVo = null, CategoryVo $newVo = null)
    {
        if (! empty($oldVo) || ! empty($oldVo)) {
            if ($oldVo !== $newVo) {
                $log = $this->getAccessLog($oldVo, $newVo, 'category.');
                $this->setAccessLog('Category.' . $oldVo->categoryId, $log);
            }
            parent::UnSetCacheFile(parent::GetServiceCacheKey('category', 'tree', '*'));
            parent::UnSetCacheFile(parent::GetServiceCacheKey('category', $oldVo->categoryId, '*'));
            parent::UnSetCacheFile(parent::GetServiceCacheKey('siteconf', 'page', '*', 'categoryConfig'));
            if (! empty($newVo) && ! empty($newVo->checkId)) {
                parent::UnSetCacheFile(parent::GetServiceCacheKey('goodsInfo', '*'));
                parent::UnSetCacheFile(parent::GetServiceCacheKey('category', '*'));
                $this->changeAccessLogId('Category.' . $newVo->checkId, 'Category.' . $newVo->categoryId);
                $this->GetCategoryDao()->SetUpdateIdChange($newVo);
            }
        } else {
            parent::UnSetCacheFile(parent::GetServiceCacheKey('category', '*'));
            $this->ReloadGoodsCategoryAll();
            parent::UnSetCacheFile(parent::GetServiceCacheKey('category', 'tree', 'code'));
        }
    }

    /**
     * 상품 카테고리 정보 페이징 가져오기
     *
     * @param RequestVo $request
     * @return \Vo\PagingVo
     */
    public function GetCategoryPaging(RequestVo $request)
    {
        $vo = parent::GetSearchVo($request);
        return $this->GetCategoryDao()->GetPaging($vo, $request->GetPerPage(10), $request->GetOffset());
    }

    /**
     * 상품 카테고리 목록 가져오기
     *
     * @param RequestVo $request
     * @return CategoryVo[]
     */
    public function GetCategoryList(RequestVo $request)
    {
        $vo = parent::GetSearchVo($request);
        return $this->GetCategoryDao()->GetList($vo, $request->GetPerPage(10), $request->GetOffset());
    }

    /**
     * 카테고리 Excel 파일로 가져오기
     *
     * @param RequestVo $request
     * @return ExcelVo
     */
    public function GetCategoryListExcel(RequestVo $request = null)
    {
        $downloadFormVo = $this->GetDownloadFormVo($request, 'categoryIds');
        $excelData = $this->GetCategoryList($downloadFormVo->searchRequest);
        $excelVo = new ExcelVo($downloadFormVo, $excelData);
        $excelVo->AddHeaderList($downloadFormVo->fieldList, true);
        return $excelVo;
    }

    /**
     * 카테고리 PDF 파일 생성하기
     *
     * @param RequestVo $request
     * @return PdfVo
     */
    public function GetCategoryListPdf(RequestVo $request = null)
    {
        $downloadFormVo = $this->GetDownloadFormVo($request, 'categoryIds', true);
        $data = $this->GetCategoryList($downloadFormVo->searchRequest);
        $pdfVo = new PdfVo($downloadFormVo, $data);
        $pdfVo->SetFontSize(10);
        $pdfVo->SetPageDirection('L');
        if (! empty($downloadFormVo->password)) {
            $pdfVo->SetPassword($downloadFormVo->password);
        }
        return $pdfVo;
    }

    /**
     * 상품 카테고리 정보 보기
     *
     * @param string $uid
     * @param string $groupSno
     * @return CategoryVo
     */
    public function GetCategoryView($uid = '', $groupSno = '')
    {
        $uidKey = parent::GetServiceCacheKey('category', $uid);
        $result = parent::GetCacheFile($uidKey, true);
        if (empty($result)) {
            $vo = $this->GetCategoryVo($uid);
            $result = $this->GetCategoryDao()->GetView($vo);
            if (! empty($result)) {
                $childVo = new TreeVo();
                $childVo->mallId = $this->mallId;
                $childVo->parentId = $uid;
                $result->childCategory = $this->GetCategoryDao()->GetTreeList($childVo, 100, 0);
                parent::SetCacheFile($uidKey, $result);
            } else {
                $this->GetException(KbmException::DATA_ERROR_VIEW);
            }
        }
        if (! empty($result)) {
            $this->GetDisplayMainRefGoods($result->recomInfo, $groupSno);
            $this->GetDisplayMainRefGoods($result->newInfo, $groupSno);
            $this->GetDisplayMainRefGoods($result->hotInfo, $groupSno);
            $this->GetDisplayMainRefGoods($result->saleInfo, $groupSno);
            $this->GetDisplayMainRefGoods($result->eventInfo, $groupSno);
            $this->GetDisplayMainRefGoods($result->bestInfo, $groupSno);
            $result->bannerListVo = Array();
            if (! empty($result->bannerIds) && is_array($result->bannerIds)) {
                $policyService = $this->GetServicePolicy();
                foreach ($result->bannerIds as $bannerId) {
                    try {
                        $result->bannerListVo[] = $policyService->GetBannerView($bannerId);
                    } catch (Exception $ex) {}
                }
            }
        }
        return $result;
    }

    /**
     * 상품 카테고리 정보 생성하기
     *
     * @param RequestVo $request
     * @return CategoryVo
     */
    public function GetCategoryCreate(RequestVo $request = null)
    {
        $vo = $this->GetCategoryVo(uniqid("C"), $request);
        $this->GetCategoryParse($vo, $request, null);
        if (! empty($vo->checkId)) {
            $vo->categoryId = $vo->checkId;
            $vo->checkId = '';
        }
        if ($this->GetCategoryDao()->SetCreate($vo)) {
            $oldView = new CategoryVo();
            $oldView->mallId = $this->mallId;
            $oldView->categoryId = $vo->categoryId;
            $this->UnsetCategoryCache($oldView, $vo);
            return $vo;
        } else {
            parent::GetException(KbmException::DATA_ERROR_CREATE);
        }
    }

    /**
     * 상품 카테고리 정보 파싱
     *
     * @param CategoryVo $vo
     * @param RequestVo $request
     * @param CategoryVo $oldView
     * @return CategoryVo
     */
    public function GetCategoryParse(CategoryVo $vo, RequestVo $request, CategoryVo $oldView = null)
    {
        if ($request->hasKey('seoTag')) {
            $vo->seoTag = $this->GetFill($request->GetRequestVo('seoTag'), 'SeoTagVo');
        }
        if ($request->hasKey('categoryNmLocale')) {
            $vo->categoryNmLocale = $this->GetLocaleTextVoRequest($request, 'categoryNmLocale');
        }
        if ($request->hasKey('recomInfo')) {
            $vo->recomInfo = $request->GetFill(new DisplayMainItemVo(), 'recomInfo');
        }
        if ($request->hasKey('newInfo')) {
            $vo->newInfo = $request->GetFill(new DisplayMainItemVo(), 'newInfo');
        }
        if ($request->hasKey('hotInfo')) {
            $vo->hotInfo = $request->GetFill(new DisplayMainItemVo(), 'hotInfo');
        }
        if ($request->hasKey('saleInfo')) {
            $vo->saleInfo = $request->GetFill(new DisplayMainItemVo(), 'saleInfo');
        }
        if ($request->hasKey('eventInfo')) {
            $vo->eventInfo = $request->GetFill(new DisplayMainItemVo(), 'eventInfo');
        }
        if ($request->hasKey('bestInfo')) {
            $vo->bestInfo = $request->GetFill(new DisplayMainItemVo(), 'bestInfo');
        }
        if ($request->hasKey('bannerIds')) {
            $vo->bannerIds = $request->GetItemArray("bannerIds");
        }
        if ($request->hasKey('topDispImgPc')) {
            $vo->topDispImgPc = $this->GetUploadFile($request->topDispImgPc, '', 'category-pc');
        }
        if ($request->hasKey('topDispImgMobile')) {
            $vo->topDispImgMobile = $this->GetUploadFile($request->topDispImgMobile, '', 'category-mobile');
        }
        if ($request->hasKey("categoryId")) {
            $vo->checkId = $request->categoryId;
        }
        if (! empty($vo->checkId) && $vo->categoryId != $vo->checkId) {
            $this->GetCategoryIdCheck($vo->checkId, $vo->categoryId);
        } else {
            $vo->checkId = '';
        }
        return $vo;
    }

    /**
     * 상품 카테고리 정보 업데이트
     *
     * @param string $uid
     * @param RequestVo $request
     * @return CategoryVo
     */
    public function GetCategoryUpdate($uid = '', RequestVo $request = null)
    {
        $oldView = $this->GetCategoryView($uid);
        $vo = $this->GetCategoryVo($uid, $request, $this->GetDeepClone($oldView));
        $this->GetCategoryParse($vo, $request, $oldView);
        if ($this->GetCategoryDao()->SetUpdate($vo)) {
            $this->UnsetCategoryCache($oldView, $vo);
            return $vo;
        } else {
            parent::GetException(KbmException::DATA_ERROR_UPDATE);
        }
    }

    /**
     * 상품 카테고리 정보 삭제
     *
     * @param string $uid
     * @return CategoryVo
     */
    public function GetCategoryDelete($uid = '')
    {
        $vo = $this->GetCategoryVo($uid);
        if ($this->GetCategoryDao()->SetDelete($vo)) {
            $this->UnsetCategoryCache($vo, new CategoryVo());
            return $vo;
        } else {
            parent::GetException(KbmException::DATA_ERROR_DELETE);
        }
    }

    /**
     * 상품 카테고리트리 정보 가져오기
     *
     * @param RequestVo $request
     * @return TreeVo[]
     */
    public function GetCategoryTreeList(RequestVo $request = null)
    {
        $vo = new TreeSearchVo();
        parent::GetSearchVo($request, $vo);
        return $this->GetCategoryDao()->GetTreeList($vo, 2000, 0);
    }

    /**
     * 상품 카테고리트리 정보 목록가져오기
     *
     * @param string $categoryId
     * @return TreeVo[]
     */
    public function GetCategoryNameKeyword($categoryId = '')
    {
        $keywordList = Array();
        if (! empty($categoryId)) {
            $cateVoList = $this->GetCategoryTreeVoList();
            if (isset($cateVoList[$categoryId])) {
                $parentTree = $cateVoList[$categoryId];
                while (! empty($parentTree)) {
                    if (! empty($parentTree->keyword)) {
                        $keywordList[] = $parentTree->keyword;
                    }
                    $parentTree = $parentTree->parentTreeVo;
                }
            }
        }
        return implode(' ', $keywordList);
    }

    /**
     * 로그된 상품 카테고리 정보
     *
     * @var TreeVo[]
     */
    private $loadedCategoryList = null;

    /**
     * 상품 카테고리트리 정보 목록가져오기
     *
     * @param RequestVo $request
     * @return TreeVo[]
     */
    public function GetCategoryTreeVoList()
    {
        if (empty($this->loadedCategoryList)) {
            $cateVoList = $this->GetCategoryTreeList();
            $cateVos = Array();
            foreach ($cateVoList as $treeVo) {
                $id = $treeVo->id;
                $treeVo->keyword = $this->GetLocaleTextKeyword($treeVo->value, $treeVo->valueLocale);
                $cateVos[$id] = $treeVo;
            }
            foreach ($cateVos as $treeVo) {
                $parentId = $treeVo->parentId;
                if (! empty($parentId) && isset($cateVos[$parentId])) {
                    $treeVo->parentTreeVo = $cateVos[$parentId];
                }
            }
            $this->loadedCategoryList = $cateVos;
        }
        return $this->loadedCategoryList;
    }

    /**
     * 상품 색상 아이콘 트리 정보 가져오기
     *
     * @param RequestVo $request
     * @return CodeVo[]
     */
    public function GetColorIconTreeList(RequestVo $request = null)
    {
        $vo = new TreeSearchVo();
        parent::GetSearchVo($request, $vo);
        $result = $this->GetGoodsInfoDao()->GetColorIconTreeList($vo, 2000, 0);
        $oldIdList = Array();
        foreach ($result as $item) {
            $oldIdList[] = $item->id;
        }

        $policyService = $this->GetServicePolicy();
        $goodsIconColorView = $policyService->GetGoodsIconColorView();
        $goodsIconList = Array();
        if (! empty($goodsIconColorView->goodsIconTime)) {
            $goodsIconList['goodsIconTime'] = $goodsIconColorView->goodsIconTime;
        }
        if (! empty($goodsIconColorView->goodsIconFix)) {
            $goodsIconList['goodsIconFix'] = $goodsIconColorView->goodsIconFix;
        }
        if (! empty($goodsIconColorView->goodsColor)) {
            $goodsIconList['goodsColor'] = $goodsIconColorView->goodsColor;
        }
        $cateVos = Array();
        foreach ($goodsIconList as $iconType => $iconItems) {
            foreach ($iconItems as $iconItem) {
                if ($iconItem->optionViewFl == 'Y') {
                    $id = $iconItem->id;
                    if (in_array($id, $oldIdList)) {
                        $codeVo = new CodeVo();
                        $codeVo->value = $id;
                        $codeVo->text = $iconItem->value;
                        $codeVo->textLocale = $iconItem->valueLocale;
                        $codeVo->children = null;
                        $codeVo->options = $iconItem;
                        $codeVo->parent = $iconType;
                        $cateVos[] = $codeVo;
                    }
                }
            }
        }
        return $cateVos;
    }

    /**
     * 로드된 색상 아이콘 트리 정보
     *
     * @var TreeVo[]
     */
    private $loadedColorIconList = null;

    /**
     * 상품 색상 아이콘 트리 목록 가져오기
     *
     * @param RequestVo $request
     * @return TreeVo[]
     */
    public function GetColorIconTreeVoList()
    {
        if (empty($this->loadedColorIconList)) {
            $policyService = $this->GetServicePolicy();
            $goodsIconColorView = $policyService->GetGoodsIconColorView();
            $goodsIconList = Array();
            if (! empty($goodsIconColorView->goodsIconTime)) {
                $goodsIconList[] = $goodsIconColorView->goodsIconTime;
            }
            if (! empty($goodsIconColorView->goodsIconFix)) {
                $goodsIconList[] = $goodsIconColorView->goodsIconFix;
            }
            if (! empty($goodsIconColorView->goodsColor)) {
                $goodsIconList[] = $goodsIconColorView->goodsColor;
            }
            $cateVos = Array();
            foreach ($goodsIconList as $iconItems) {
                foreach ($iconItems as $iconItem) {
                    if ($iconItem->optionViewFl == 'Y') {
                        $id = $iconItem->id;
                        $codeVo = new TreeVo();
                        $codeVo->id = $id;
                        $codeVo->value = $iconItem->value;
                        $codeVo->valueLocale = $iconItem->valueLocale;
                        $cateVos[$id] = $codeVo;
                    }
                }
            }
            $this->loadedColorIconList = $cateVos;
        }
        return $this->loadedColorIconList;
    }

    /**
     * 상품 카테고리 정보 업데이트
     *
     * @param RequestVo $request
     * @return TreeVo
     */
    public function GetCategoryTreeUpdate(RequestVo $request = null)
    {
        $oldList = $this->GetCategoryTreeList();
        $oldVos = Array();
        foreach ($oldList as $vo) {
            $cateId = $vo->id;
            $oldVos[$cateId] = $vo;
        }
        $newList = $request->GetItemArray('category', new TreeVo());
        $newVos = Array();
        foreach ($newList as $ord => $vo) {
            $cateId = $vo->id;
            $cateName = $vo->value;
            $cateNameLocale = $this->GetLocaleTextVo($vo->valueLocale);
            if (! empty($cateId) && ! empty($cateName)) {
                $cateVo = new TreeVo();
                $cateVo->mallId = $this->mallId;
                $cateVo->id = $cateId;
                $cateVo->parentId = isset($vo->parentId) ? $vo->parentId : '';
                $cateVo->value = $cateName;
                $cateVo->valueLocale = $cateNameLocale;
                $cateVo->ord = $ord;
                $newVos[$cateId] = $cateVo;
            }
        }
        $categoryDao = $this->GetCategoryDao();
        foreach ($newVos as $key => $vo) {
            if (isset($oldVos[$key])) {
                $vo->valueLocale = $this->GetLocaleTextVo($vo->valueLocale);
                $categoryDao->SetTreeUpdate($vo);
            } else {
                $oldId = $vo->id;
                $newId = $this->GetUniqId("C" . date("Y"));
                $vo->id = $newId;
                foreach ($newVos as $cvo) {
                    if ($cvo->parentId == $oldId) {
                        $cvo->parentId = $newId;
                    }
                }
                $categoryDao->SetTreeCreate($vo);
            }
        }
        foreach ($oldVos as $key => $vo) {
            if (! isset($newVos[$key])) {
                $categoryDao->SetTreeDelete($vo);
            }
        }
        $this->UnsetCategoryCache();
        return true;
    }

    /**
     * 상품 브랜드 정보 DAO 가져오기
     *
     * @return \Dao\BrandDao
     */
    public function GetBrandDao()
    {
        return parent::GetDao('BrandDao');
    }

    /**
     * 상품 브랜드 VO 가져오기
     *
     * @param string $uid
     * @param RequestVo $request
     * @param BrandVo $vo
     * @return BrandVo
     */
    public function GetBrandVo($uid = '', RequestVo $request = null, $vo = null)
    {
        $vo = parent::GetFill($request, empty($vo) ? 'BrandVo' : $vo);
        if (! empty($uid)) {
            $vo->brandId = $uid;
        }
        return $vo;
    }

    /**
     * 상품 브랜드 정보 캐쉬 삭제하기
     *
     * @param BrandVo $oldVo
     * @param BrandVo $newVo
     */
    private function UnsetBrandCache(BrandVo $oldVo = null, BrandVo $newVo = null)
    {
        if (! empty($oldVo) || ! empty($newVo)) {
            if ($oldVo !== $newVo) {
                $log = $this->getAccessLog($oldVo, $newVo, 'brand.');
                $this->setAccessLog('Brand.' . $oldVo->brandId, $log);
            }
            parent::UnSetCacheFile(parent::GetServiceCacheKey('brand', 'tree', '*'));
            parent::UnSetCacheFile(parent::GetServiceCacheKey('brand', $oldVo->brandId, '*'));
            parent::UnSetCacheFile(parent::GetServiceCacheKey('siteconf', 'page', '*', 'brandConfig'));
            if (! empty($newVo) && ! empty($newVo->checkId)) {
                parent::UnSetCacheFile(parent::GetServiceCacheKey('goodsInfo', '*'));
                parent::UnSetCacheFile(parent::GetServiceCacheKey('brand', '*'));
                $this->changeAccessLogId('Brand.' . $newVo->checkId, 'Brand.' . $newVo->brandId);
                $this->GetBrandDao()->SetUpdateIdChange($newVo);
            }
        } else {
            parent::UnSetCacheFile(parent::GetServiceCacheKey('brand', '*'));
            $this->ReloadGoodsCategoryAll();
        }
    }

    /**
     * 상품 브랜드 페이징 가져오기
     *
     * @param RequestVo $request
     * @return \Vo\PagingVo
     */
    public function GetBrandPaging(RequestVo $request)
    {
        $vo = parent::GetSearchVo($request);
        return $this->GetBrandDao()->GetPaging($vo, $request->GetPerPage(10), $request->GetOffset());
    }

    /**
     * 상품 브랜드 목록 가져오기
     *
     * @param RequestVo $request
     * @return BrandVo[]
     */
    public function GetBrandList(RequestVo $request)
    {
        $vo = parent::GetSearchVo($request);
        return $this->GetBrandDao()->GetList($vo, $request->GetPerPage(10), $request->GetOffset());
    }

    /**
     * 브랜드 Excel 파일로 가져오기
     *
     * @param RequestVo $request
     * @return ExcelVo
     */
    public function GetBrandListExcel(RequestVo $request = null)
    {
        $downloadFormVo = $this->GetDownloadFormVo($request, 'brandIds');
        $excelData = $this->GetBrandList($downloadFormVo->searchRequest);
        $excelVo = new ExcelVo($downloadFormVo, $excelData);
        $excelVo->AddHeaderList($downloadFormVo->fieldList, true);
        return $excelVo;
    }

    /**
     * 카테고리 PDF 파일 생성하기
     *
     * @param RequestVo $request
     * @return PdfVo
     */
    public function GetBrandListPdf(RequestVo $request = null)
    {
        $downloadFormVo = $this->GetDownloadFormVo($request, 'categoryIds', true);
        $data = $this->GetBrandList($downloadFormVo->searchRequest);
        $pdfVo = new PdfVo($downloadFormVo, $data);
        $pdfVo->SetFontSize(10);
        $pdfVo->SetPageDirection('L');
        if (! empty($downloadFormVo->password)) {
            $pdfVo->SetPassword($downloadFormVo->password);
        }
        return $pdfVo;
    }

    /**
     * 상품 브랜드 정보 가져오기
     *
     * @param string $uid
     * @return BrandVo
     */
    public function GetBrandView($uid = '')
    {
        $uidKey = parent::GetServiceCacheKey('brand', $uid);
        $result = parent::GetCacheFile($uidKey);
        if (empty($result)) {
            $vo = $this->GetBrandVo($uid);
            $result = $this->GetBrandDao()->GetView($vo);
            if (! empty($result)) {
                parent::SetCacheFile($uidKey, $result);
            } else {
                $this->GetException(KbmException::DATA_ERROR_VIEW);
            }
        }
        if (! empty($result)) {
            $this->GetDisplayMainRefGoods($result->recomInfo);
            $this->GetDisplayMainRefGoods($result->newInfo);
            $this->GetDisplayMainRefGoods($result->hotInfo);
            $this->GetDisplayMainRefGoods($result->saleInfo);
            $this->GetDisplayMainRefGoods($result->eventInfo);
            $this->GetDisplayMainRefGoods($result->bestInfo);
            $result->bannerListVo = Array();
            if (! empty($result->bannerIds) && is_array($result->bannerIds)) {
                $policyService = $this->GetServicePolicy();
                foreach ($result->bannerIds as $bannerId) {
                    try {
                        $result->bannerListVo[] = $policyService->GetBannerView($bannerId);
                    } catch (Exception $ex) {}
                }
            }
        }
        return $result;
    }

    /**
     * 카테고리 아이디 중복 확인
     *
     * @param string $newId
     * @param string $oldId
     * @return boolean
     */
    public function GetCategoryIdCheck($newId = '', $oldId = '')
    {
        return $this -> GetCategoryBrandIdCheck($newId , $oldId);
    }
    
    /**
     * 카테고리 아이디 중복 확인
     *
     * @param string $newId
     * @param string $oldId
     * @return boolean
     */
    public function GetCategoryBrandIdCheck($newId = '', $oldId = '')
    {
        $vo = new CategoryVo();
        $vo->checkId = $newId;
        $vo->categoryId = $oldId;
        $vo->mallId = $this->mallId;
        if (empty($vo->checkId)) {
            return true;
        } else if (! preg_match('#^[a-zA-Z][A-Za-z0-9_]{2,40}$#', $vo->checkId)) {
            $this->GetException(\KbmException::DATA_ERROR_UNKNOWN, 'MSG_ERROR_ID_PATTERN');
        } else {
            if (preg_match('#^[a-zA-Z][a-zA-Z0-9_]{2,40}$#', $vo->checkId)) {
                if ($this->GetCategoryDao()->GetIdCheck($vo) > 0) {
                    $this->GetException(\KbmException::DATA_ERROR_UNKNOWN, 'MSG_ERROR_ID_USED');
                } else {
                    $vo = new BrandVo();
                    $vo->checkId = $newId;
                    $vo->brandId = $oldId;
                    $vo->mallId = $this->mallId;
                    if ($this->GetBrandDao()->GetIdCheck($vo) > 0) {
                        $this->GetException(\KbmException::DATA_ERROR_UNKNOWN, 'MSG_ERROR_ID_USED');
                    } else {
                        return true;
                    }
                }
            } else {
                return true;
            }
        }
    }
    

    /**
     * 카테고리 아이디 중복 확인
     *
     * @param string $newId
     * @param string $oldId
     * @return boolean
     */
    public function GetBrandIdCheck($newId = '', $oldId = '')
    {
        return $this -> GetCategoryBrandIdCheck($newId , $oldId);
    }

    /**
     * 상품 브랜드 생성하기
     *
     * @param RequestVo $request
     * @return BrandVo
     */
    public function GetBrandCreate(RequestVo $request = null)
    {
        $vo = $this->GetBrandVo(uniqid("G"), $request);
        $this->GetBrandParse($vo, $request, null);
        if (! empty($vo->checkId)) {
            $vo->brandId = $vo->checkId;
            $vo->checkId = '';
        }
        if ($this->GetBrandDao()->SetCreate($vo)) {
            $oldVo = new BrandVo();
            $oldVo->brandId = $vo->brandId;
            $oldVo->mallId = $vo->mallId;
            $this->UnsetBrandCache($oldVo, $vo);
            return $vo;
        } else {
            parent::GetException(KbmException::DATA_ERROR_CREATE);
        }
    }

    /**
     * 상품 브랜드 정보 파싱하기
     *
     * @param BrandVo $vo
     * @param RequestVo $request
     * @param BrandVo $oldView
     * @return BrandVo
     */
    public function GetBrandParse(BrandVo $vo, RequestVo $request, BrandVo $oldView = null)
    {
        if ($request->hasKey('seoTag')) {
            $vo->seoTag = $this->GetFill($request->GetRequestVo('seoTag'), 'SeoTagVo');
        }
        if ($request->hasKey('brandNmLocale')) {
            $vo->brandNmLocale = $this->GetLocaleTextVoRequest($request, 'brandNmLocale');
        }
        if ($request->hasKey('recomInfo')) {
            $vo->recomInfo = $request->GetFill(new DisplayMainItemVo(), 'recomInfo');
        }
        if ($request->hasKey('newInfo')) {
            $vo->newInfo = $request->GetFill(new DisplayMainItemVo(), 'newInfo');
        }
        if ($request->hasKey('hotInfo')) {
            $vo->hotInfo = $request->GetFill(new DisplayMainItemVo(), 'hotInfo');
        }
        if ($request->hasKey('saleInfo')) {
            $vo->saleInfo = $request->GetFill(new DisplayMainItemVo(), 'saleInfo');
        }
        if ($request->hasKey('eventInfo')) {
            $vo->eventInfo = $request->GetFill(new DisplayMainItemVo(), 'eventInfo');
        }
        if ($request->hasKey('bestInfo')) {
            $vo->bestInfo = $request->GetFill(new DisplayMainItemVo(), 'bestInfo');
        }
        if ($request->hasKey('bannerIds')) {
            $vo->bannerIds = $request->GetItemArray("bannerIds");
        }
        if ($request->hasKey('topDispImgPc')) {
            $vo->topDispImgPc = $this->GetUploadFile($request->topDispImgPc, '', 'category-pc');
        }
        if ($request->hasKey('topDispImgMobile')) {
            $vo->topDispImgMobile = $this->GetUploadFile($request->topDispImgMobile, '', 'category-mobile');
        }
        if ($request->hasKey("brandId")) {
            $vo->checkId = $request->brandId;
        }
        if (! empty($vo->checkId) && $vo->brandId != $vo->checkId) {
            $this->GetBrandIdCheck($vo->checkId, $vo->brandId);
        } else {
            $vo->checkId = '';
        }
        return $vo;
    }

    /**
     * 상품 브랜드 정보 업데이트
     *
     * @param string $uid
     * @param RequestVo $request
     * @return BrandVo
     */
    public function GetBrandUpdate($uid = '', RequestVo $request = null)
    {
        $oldView = $this->GetBrandView($uid);
        $vo = $this->GetBrandVo($uid, $request, $this->GetDeepClone($oldView));
        $this->GetBrandParse($vo, $request, $oldView);
        if ($this->GetBrandDao()->SetUpdate($vo)) {
            $this->UnsetBrandCache($oldView, $vo);
            return $vo;
        } else {
            parent::GetException(KbmException::DATA_ERROR_UPDATE);
        }
    }

    /**
     * 상품 브랜드 삭제하기
     *
     * @param string $uid
     * @return BrandVo
     */
    public function GetBrandDelete($uid = '')
    {
        $vo = $this->GetBrandVo($uid);
        if ($this->GetBrandDao()->SetDelete($vo)) {
            $this->UnsetBrandCache($vo, new BrandVo());
            return $vo;
        } else {
            parent::GetException(KbmException::DATA_ERROR_DELETE);
        }
    }

    /**
     * 상품 브랜드 트리 가져오기
     *
     * @param RequestVo $request
     * @return TreeVo[]
     */
    public function GetBrandTreeList(RequestVo $request = null)
    {
        $vo = parent::GetSearchVo($request);
        return $this->GetBrandDao()->GetTreeList($vo, 2000, 0);
    }

    /**
     * 로드된 상품 브랜드 목록
     *
     * @var TreeVo[]
     */
    private $loadedBrandList = null;

    /**
     * 상품 브랜드 목록 가져오기
     *
     * @return TreeVo[]
     */
    public function GetBrandTreeVoList()
    {
        if (empty($this->loadedBrandList)) {
            $brandVoList = $this->GetBrandTreeList();
            $brandVos = Array();
            foreach ($brandVoList as $treeVo) {
                $id = $treeVo->id;
                $parentId = $treeVo->parentId;
                if (! empty($parentId)) {
                    $treeVo->parentTreeVo = $brandVos[$parentId];
                }
                $brandVos[$id] = $treeVo;
            }
            $this->loadedBrandList = $brandVos;
        }
        return $this->loadedBrandList;
    }

    /**
     * 상품 브랜드 트리 정보 업데이트
     *
     * @param RequestVo $request
     * @return TreeVo
     */
    public function GetBrandTreeUpdate(RequestVo $request = null)
    {
        $oldList = $this->GetBrandTreeList();
        $oldVos = Array();
        foreach ($oldList as $vo) {
            $cateId = $vo->id;
            $oldVos[$cateId] = $vo;
        }
        $newList = $request->GetItemArray('brand', new TreeVo());
        $newVos = Array();
        foreach ($newList as $ord => $vo) {
            $cateId = $vo->id;
            $cateName = $vo->value;
            $cateNameLocale = $this->GetLocaleTextVo($vo->valueLocale);
            if (! empty($cateId) && ! empty($cateName)) {
                $cateVo = new TreeVo();
                $cateVo->mallId = $this->mallId;
                $cateVo->id = $cateId;
                $cateVo->parentId = isset($vo->parentId) ? $vo->parentId : '';
                $cateVo->value = $cateName;
                $cateVo->valueLocale = $cateNameLocale;
                $cateVo->ord = $ord;
                $newVos[$cateId] = $cateVo;
            }
        }
        $brandDao = $this->GetBrandDao();
        foreach ($newVos as $key => $vo) {
            if (isset($oldVos[$key])) {
                $brandDao->SetTreeUpdate($vo);
            } else {
                $oldId = $vo->id;
                $newId = $this->GetUniqId("B" . date("Y"));
                $vo->id = $newId;
                foreach ($newVos as $cvo) {
                    if ($cvo->parentId == $oldId) {
                        $cvo->parentId = $newId;
                    }
                }
                $brandDao->SetTreeCreate($vo);
            }
        }
        foreach ($oldVos as $key => $vo) {
            if (! isset($newVos[$key])) {
                $brandDao->SetTreeDelete($vo);
            }
        }
        $this->UnsetBrandCache();
        return true;
    }

    /**
     * 상품 테마 정보 DAO 가져오기
     *
     * @return \Dao\DisplayThemeDao
     */
    public function GetDisplayThemeDao()
    {
        return parent::GetDao('DisplayThemeDao');
    }

    /**
     * 상품 테마 정보 VO 가져오기
     *
     * @param string $uid
     * @param RequestVo $request
     * @param DisplayThemeVo $vo
     * @return DisplayThemeVo
     */
    public function GetDisplayThemeVo($uid = '', RequestVo $request = null, $vo = null)
    {
        $vo = parent::GetFill($request, empty($vo) ? 'DisplayThemeVo' : $vo);
        if (! empty($uid)) {
            $vo->themeId = $uid;
        }
        return $vo;
    }

    /**
     * 상품 테마 정보 캐시 삭제하기
     *
     * @param DisplayThemeVo $oldVo
     * @param DisplayThemeVo $newVo
     */
    private function UnsetDisplayThemeCache(DisplayThemeVo $oldVo = null, DisplayThemeVo $newVo = null)
    {
        if (! empty($oldVo) || ! empty($oldVo)) {
            if ($oldVo !== $newVo) {
                $log = $this->getAccessLog($oldVo, $newVo, 'displayTheme.');
                $this->setAccessLog('DisplayTheme.' . $oldVo->themeId, $log);
            }
            parent::UnSetCacheFile(parent::GetServiceCacheKey('DisplayTheme', 'tree', '*'));
            parent::UnSetCacheFile(parent::GetServiceCacheKey('DisplayTheme', $oldVo->themeId));
        } else {
            parent::UnSetCacheFile(parent::GetServiceCacheKey('DisplayTheme', '*'));
        }
        parent::UnSetCacheFile(parent::GetServiceCacheKey('siteconf', '*'));
    }

    /**
     * 상품 테마 페이징 가져오기
     *
     * @param RequestVo $request
     * @return \Vo\PagingVo
     */
    public function GetDisplayThemePaging(RequestVo $request)
    {
        $vo = parent::GetSearchVo($request);
        return $this->GetDisplayThemeDao()->GetPaging($vo, $request->GetPerPage(10), $request->GetOffset());
    }

    /**
     * 상품 테마 목록 가져오기
     *
     * @param RequestVo $request
     * @return DisplayThemeVo[]
     */
    public function GetDisplayThemeList(RequestVo $request)
    {
        $vo = parent::GetSearchVo($request);
        return $this->GetDisplayThemeDao()->GetList($vo, $request->GetPerPage(10), $request->GetOffset());
    }

    /**
     * 상품 테마 정보 가져오기
     *
     * @param string $uid
     * @return DisplayThemeVo
     */
    public function GetDisplayThemeView($uid = '')
    {
        $uidKey = parent::GetServiceCacheKey('DisplayTheme', $uid);
        $result = parent::GetCacheFile($uidKey);
        if (! empty($result) && $result instanceof DisplayThemeVo) {
            return $result;
        } else {
            $vo = $this->GetDisplayThemeVo($uid);
            $result = $this->GetDisplayThemeDao()->GetView($vo);
            if (! empty($result)) {
                parent::SetCacheFile($uidKey, $result);
                return $result;
            } else {
                $this->GetException(KbmException::DATA_ERROR_VIEW);
            }
        }
    }

    /**
     * 상품 테마 정보 생성하기
     *
     * @param RequestVo $request
     * @return DisplayThemeVo
     */
    public function GetDisplayThemeCreate(RequestVo $request = null)
    {
        $vo = $this->GetDisplayThemeVo(uniqid("T"), $request);
        $this->GetDisplayThemeParse($vo, $request, null);
        if ($this->GetDisplayThemeDao()->SetCreate($vo)) {
            return $vo;
        } else {
            parent::GetException(KbmException::DATA_ERROR_CREATE);
        }
    }

    /**
     * 상품 테마정보 파상하기
     *
     * @param DisplayThemeVo $vo
     * @param RequestVo $request
     * @param DisplayThemeVo $oldView
     * @return DisplayThemeVo
     */
    public function GetDisplayThemeParse(DisplayThemeVo $vo, RequestVo $request, DisplayThemeVo $oldView = null)
    {
        if ($request->hasKey('themeNmLocale')) {
            $vo->themeNmLocale = $this->GetLocaleTextVoRequest($request, 'themeNmLocale');
        }
        return $vo;
    }

    /**
     * 상품 테마 정보 업데이트
     *
     * @param string $uid
     * @param RequestVo $request
     * @return DisplayThemeVo
     */
    public function GetDisplayThemeUpdate($uid = '', RequestVo $request = null)
    {
        $oldView = $this->GetDisplayThemeView($uid);
        $vo = $this->GetDisplayThemeVo($uid, $request, clone $oldView);
        $this->GetDisplayThemeParse($vo, $request, $oldView);
        if ($this->GetDisplayThemeDao()->SetUpdate($vo)) {
            $this->UnsetDisplayThemeCache($oldView, $vo);
            return $vo;
        } else {
            parent::GetException(KbmException::DATA_ERROR_UPDATE);
        }
    }

    /**
     * 상품 테마 정보 삭제
     *
     * @param string $uid
     * @return DisplayThemeVo
     */
    public function GetDisplayThemeDelete($uid = '')
    {
        $vo = $this->GetDisplayThemeVo($uid);
        if ($this->GetDisplayThemeDao()->SetDelete($vo)) {
            $this->UnsetDisplayThemeCache($vo, new DisplayThemeVo());
            return $vo;
        } else {
            parent::GetException(KbmException::DATA_ERROR_DELETE);
        }
    }

    /**
     * 상품 테마 트리 목록 가져오기
     *
     * @param RequestVo $request
     * @return TreeVo[]
     */
    public function GetDisplayThemeTreeList(RequestVo $request = null)
    {
        $uidKey = parent::GetServiceCacheKey('DisplayTheme', 'tree', '*');
        $result = parent::GetCacheFile($uidKey, true);
        if (empty($result)) {
            $vo = parent::GetSearchVo($request);
            $result = $this->GetDisplayThemeDao()->GetTreeList($vo, 2000, 0);
            parent::SetCacheFile($uidKey, $result);
        }
        return $result;
    }

    /**
     * 상품 트리 정보 업데이트
     *
     * @param RequestVo $request
     * @return TreeVo
     */
    public function GetDisplayThemeTreeUpdate(RequestVo $request = null)
    {
        $oldList = $this->GetDisplayThemeTreeList();
        $oldVos = Array();
        foreach ($oldList as $vo) {
            $cateId = $vo->id;
            $oldVos[$cateId] = $vo;
        }
        $newList = $request->GetItemArray('displayTheme');
        $newVos = Array();
        foreach ($newList as $ord => $vo) {
            $cateId = $vo->id;
            $cateName = $vo->value;
            $cateNameLocale = $this->GetLocaleTextVo($vo->valueLocale);
            if (! empty($cateId) && ! empty($cateName)) {
                $cateVo = new TreeVo();
                $cateVo->mallId = $this->mallId;
                $cateVo->id = $cateId;
                $cateVo->parentId = isset($vo->parentId) ? $vo->parentId : '';
                $cateVo->value = $cateName;
                $cateVo->valueLocale = $cateNameLocale;
                $cateVo->ord = $ord;
                $newVos[$cateId] = $cateVo;
            }
        }
        $displayThemeDao = $this->GetDisplayThemeDao();
        foreach ($newVos as $key => $vo) {
            if (isset($oldVos[$key])) {
                $displayThemeDao->SetTreeUpdate($vo);
            } else {
                $oldId = $vo->id;
                $newId = $this->GetUniqId("T" . date("Y"));
                $vo->id = $newId;
                foreach ($newVos as $cvo) {
                    if ($cvo->parentId == $oldId) {
                        $cvo->parentId = $newId;
                    }
                }
                $displayThemeDao->SetTreeCreate($vo);
            }
        }
        foreach ($oldVos as $key => $vo) {
            if (! isset($newVos[$key])) {
                $displayThemeDao->SetTreeDelete($vo);
            }
        }
        $this->UnsetDisplayThemeCache();
        return true;
    }

    /**
     * 자주 사용하는 상품 옵션정보 DAO 가져오기
     *
     * @return \Dao\GoodsFavOptionsDao
     */
    public function GetGoodsFavOptionsDao()
    {
        return parent::GetDao('GoodsFavOptionsDao');
    }

    /**
     * 자주 사용하는 상품 옵션 정보 VO 가져오기
     *
     * @param string $uid
     * @param RequestVo $request
     * @param GoodsFavOptionsVo $vo
     * @return GoodsFavOptionsVo
     */
    public function GetGoodsFavOptionsVo($uid = '', RequestVo $request = null, $vo = null)
    {
        $vo = parent::GetFill($request, empty($vo) ? 'GoodsFavOptionsVo' : $vo);
        $vo->optionsId = $uid;
        return $vo;
    }

    /**
     * 자주 사용하는 상품 옵션 정보 캐쉬 삭제
     *
     * @param GoodsFavOptionsVo $oldVo
     * @param GoodsFavOptionsVo $newVo
     */
    private function UnsetGoodsFavOptionsCache(GoodsFavOptionsVo $oldVo, GoodsFavOptionsVo $newVo)
    {
        if ($oldVo !== $newVo) {
            $log = $this->getAccessLog($oldVo, $newVo, 'goodsFavOptions.');
            $this->setAccessLog('GoodsFavOptions.' . $oldVo->optionsId, $log);
        }
        parent::UnSetCacheFile(parent::GetServiceCacheKey('goodsFavOptions', '*'));
    }

    /**
     * 자주 사용하는 상품 옵션 정보 페이징 가져오기
     *
     * @param RequestVo $request
     * @return \Vo\PagingVo
     */
    public function GetGoodsFavOptionsPaging(RequestVo $request)
    {
        $vo = parent::GetSearchVo($request, 'GoodsFavOptionsSearchVo');
        return $this->GetGoodsFavOptionsDao()->GetPaging($vo, $request->GetPerPage(10), $request->GetOffset());
    }

    /**
     * 자주 사용하는 상품 옵션 정보 목록 가져오기
     *
     * @param RequestVo $request
     * @return GoodsFavOptionsVo[]
     */
    public function GetGoodsFavOptionsList(RequestVo $request)
    {
        $vo = parent::GetSearchVo($request, 'GoodsFavOptionsSearchVo');
        return $this->GetGoodsFavOptionsDao()->GetList($vo, $request->GetPerPage(10), $request->GetOffset());
    }

    /**
     * 자주 사용하는 상품 옵션 정보 가져오기
     *
     * @param string $uid
     * @param boolean $isCopy
     * @return GoodsFavOptionsVo
     */
    public function GetGoodsFavOptionsView($uid = '', $isCopy = false)
    {
        $uidKey = parent::GetServiceCacheKey('goodsFavOptions', $uid);
        $result = parent::GetCacheFile($uidKey);
        if (empty($result)) {
            $vo = $this->GetGoodsFavOptionsVo($uid);
            $result = $this->GetGoodsFavOptionsDao()->GetView($vo);
            if (! empty($result)) {
                $result->contentsTree = $this->GetGoodsInfoOptionTree($result->contents, '', 'Y', 99999);
                parent::SetCacheFile($uidKey, $result);
            } else {
                $this->GetException(KbmException::DATA_ERROR_VIEW);
            }
        }
        if ($isCopy && ! empty($result)) {
            return $this->GetGoodsFavOptionsClone($result);
        } else {
            return $result;
        }
    }

    /**
     * 자주 사용하는 상품 옵션 정보 복제 가져오기
     *
     * @param GoodsFavOptionsVo $vo
     * @return GoodsFavOptionsVo
     */
    public function GetGoodsFavOptionsClone(GoodsFavOptionsVo $vo)
    {
        $cloneVo = clone $vo;
        if (! empty($cloneVo->contents)) {
            foreach ($cloneVo->contents as $treeVo) {
                if (! empty($treeVo->optionImage) && $this->IsUploadImage($treeVo->optionImage)) {
                    $treeVo->optionImage = $this->GetUploadFileCopy($treeVo->optionImage);
                }
            }
        }
        return $cloneVo;
    }

    /**
     * 자주 사용하는 상품 옵션 정보 생성하기
     *
     * @param RequestVo $request
     * @return GoodsFavOptionsVo
     */
    public function GetGoodsFavOptionsCreate(RequestVo $request = null)
    {
        $vo = $this->GetGoodsFavOptionsVo($this->GetUniqId("O" . date("Ymd")), $request);
        $this->GetGoodsFavOptionsParse($vo, $request, null);
        if ($this->GetGoodsFavOptionsDao()->SetCreate($vo)) {
            $oldView = new GoodsFavOptionsVo();
            $oldView->mallId = $vo->mallId;
            $oldView->optionsId = $vo->optionsId;
            $this->UnsetGoodsFavOptionsCache($oldView, $vo);
            return $vo;
        } else {
            parent::GetException(KbmException::DATA_ERROR_CREATE);
        }
    }

    /**
     * 자주 사용하는 상품 옵션 정보 파싱하기
     *
     * @param GoodsFavOptionsVo $vo
     * @param RequestVo $request
     * @param GoodsFavOptionsVo $oldView
     * @return GoodsFavOptionsVo
     */
    public function GetGoodsFavOptionsParse(GoodsFavOptionsVo $vo, RequestVo $request, GoodsFavOptionsVo $oldView = null)
    {
        if ($request->hasKey('contents')) {
            switch ($vo->optionsType) {
                case 'R':
                    $vo->contents = $this->GetOptionTreeVoItemArrayFill($request, 'contents', 'ESS');
                    break;
                case 'E':
                    $vo->contents = $this->GetOptionTreeVoItemArrayFill($request, 'contents', 'EXT');
                    break;
                case 'T':
                    $vo->contents = $this->GetOptionTreeVoItemArrayFill($request, 'contents', 'TEXT');
                    break;
                case 'G':
                    $vo->contents = $this->GetOptionTreeVoItemArrayFill($request, 'contents', 'REF');
                    break;
            }
            foreach ($vo->contents as $item) {
                if (! empty($item->optionImage) && $this->IsUploadImage($item->optionImage)) {
                    $item->optionImage = $this->GetUploadFile($item->optionImage, '', 'fav-option');
                }
            }
        }
        return $vo;
    }

    /**
     * 자주 사용하는 상품 옵션 정보 업데이트 하기 *
     *
     * @param string $uid
     * @param RequestVo $request
     * @return GoodsFavOptionsVo
     */
    public function GetGoodsFavOptionsUpdate($uid = '', RequestVo $request = null)
    {
        $oldView = $this->GetGoodsFavOptionsView($uid);
        $vo = $this->GetGoodsFavOptionsVo($uid, $request, clone $oldView);
        $this->GetGoodsFavOptionsParse($vo, $request, $oldView);
        if ($this->GetGoodsFavOptionsDao()->SetUpdate($vo)) {
            $this->UnsetGoodsFavOptionsCache($oldView, $vo);
            return $vo;
        } else {
            parent::GetException(KbmException::DATA_ERROR_UPDATE);
        }
    }

    /**
     * 자주 사용하는 상품 옵션 정보 복제하기
     *
     * @param string $uid
     * @return GoodsFavOptionsVo
     */
    public function GetGoodsFavOptionsCopy($uid = '')
    {
        $vo = $this->GetGoodsFavOptionsClone($this->GetGoodsFavOptionsView($uid));
        $reqCopy = new RequestVo($vo);
        return $this->GetGoodsFavOptionsCreate($reqCopy);
    }

    /**
     * 자주 사용하는 상품 옵션 정보 삭제하기
     *
     * @param string $uid
     * @return GoodsFavOptionsVo
     */
    public function GetGoodsFavOptionsDelete($uid = '')
    {
        $vo = $this->GetGoodsFavOptionsView($uid);
        if ($this->GetGoodsFavOptionsDao()->SetDelete($vo)) {
            $this->UnsetGoodsFavOptionsCache($vo, new GoodsFavOptionsVo());
            return $vo;
        } else {
            parent::GetException(KbmException::DATA_ERROR_DELETE);
        }
    }

    /**
     * 자주 사용하는 상품 옵션 정보 변경하기
     *
     * @param string[] $uidList
     * @param string $mode
     * @return boolean
     */
    public function GetGoodsFavOptionsChange($uidList = Array(), $mode = 'delete')
    {
        $searchVo = new SearchVo();
        $searchVo->mallId = $this->mallId;
        $searchVo->codes = $uidList;
        if (count($searchVo->codes) > 0) {
            switch ($mode) {
                case 'copy':
                    try {
                        foreach ($searchVo->codes as $optionsId) {
                            $goodsFavOptionsVo = $this->GetGoodsFavOptionsView($optionsId);
                            $goodsFavOptionsVo->optionsId = $this->GetUniqId("O" . date("Ymd"));
                            $this->GetGoodsFavOptionsDao()->SetCreate($goodsFavOptionsVo);
                        }
                    } catch (Exception $ex) {}
                    break;
                case 'delete':
                    try {
                        foreach ($searchVo->codes as $optionsId) {
                            $goodsFavOptionsVo = $this->GetGoodsFavOptionsView($optionsId);
                            $this->GetGoodsFavOptionsDao()->SetDelete($goodsFavOptionsVo);
                        }
                    } catch (Exception $ex) {}
                    break;
            }
        }
        return true;
    }

    /**
     * 자주 사용하는 상품 옵션 Excel 파일로 가져오기
     *
     * @param RequestVo $request
     * @param string $isHidden
     * @return ExcelVo
     */
    public function GetGoodsFavOptionsListExcel(RequestVo $request = null)
    {
        $downloadFormVo = $this->GetDownloadFormVo($request, 'goodsCodes');
        $dataList = $this->GetGoodsFavOptionsList($downloadFormVo->searchRequest);
        $excelData = Array();
        foreach ($dataList as $data) {
            $parentItem = clone $data;
            $parentItem->contents = Array();
            foreach ($data->contents as $item) {
                $item->parent = $parentItem;
                $excelData[] = $item;
            }
        }
        $excelVo = new ExcelVo($downloadFormVo, $excelData);
        $excelVo->AddHeaderList($downloadFormVo->fieldList, true);
        return $excelVo;
    }

    /**
     * 자주 사용하는 상품 필수 정보 DAO 가져오기
     *
     * @return \Dao\GoodsFavMustInfoDao
     */
    public function GetGoodsFavMustInfoDao()
    {
        return parent::GetDao('GoodsFavMustInfoDao');
    }

    /**
     * 자주 사용하는 상품 필수 정보 VO 가져오기
     *
     * @param string $uid
     * @param RequestVo $request
     * @param GoodsFavMustInfoVo $vo
     * @return GoodsFavMustInfoVo
     */
    public function GetGoodsFavMustInfoVo($uid = '', RequestVo $request = null, $vo = null)
    {
        $vo = parent::GetFill($request, empty($vo) ? 'GoodsFavMustInfoVo' : $vo);
        $vo->infoId = $uid;
        return $vo;
    }

    /**
     * 자주 사용하는 상품 필수 정보 캐쉬 삭제하기
     *
     * @param GoodsFavMustInfoVo $oldVo
     * @param GoodsFavMustInfoVo $newVo
     */
    private function UnsetGoodsFavMustInfoCache(GoodsFavMustInfoVo $oldVo, GoodsFavMustInfoVo $newVo)
    {
        if ($oldVo !== $newVo) {
            $log = $this->getAccessLog($oldVo, $newVo, 'goodsFavMustInfo.');
            $this->setAccessLog('GoodsFavMustInfo.' . $oldVo->infoId, $log);
        }
        parent::UnSetCacheFile(parent::GetServiceCacheKey('goodsFavMustInfo', '*'));
    }

    /**
     * 자주 사용하는 상품 필수 정보 페이징 가져오기
     *
     * @param RequestVo $request
     * @return \Vo\PagingVo
     */
    public function GetGoodsFavMustInfoPaging(RequestVo $request)
    {
        $vo = parent::GetSearchVo($request, 'GoodsSearchVo');
        return $this->GetGoodsFavMustInfoDao()->GetPaging($vo, $request->GetPerPage(10), $request->GetOffset());
    }

    /**
     * 자주 사용하는 상품 필수 정보 목록 가져오기
     *
     * @param RequestVo $request
     * @return GoodsFavMustInfoVo[]
     */
    public function GetGoodsFavMustInfoList(RequestVo $request)
    {
        $vo = parent::GetSearchVo($request, 'GoodsSearchVo');
        return $this->GetGoodsFavMustInfoDao()->GetList($vo, $request->GetPerPage(10), $request->GetOffset());
    }

    /**
     * 자주 사용하는 상품 필수 정보 가져오기
     *
     * @param string $uid
     * @return GoodsFavMustInfoVo
     */
    public function GetGoodsFavMustInfoView($uid = '')
    {
        $uidKey = parent::GetServiceCacheKey('goodsFavMustInfo', $uid);
        $result = parent::GetCacheFile($uidKey);
        if (! empty($result)) {
            return $result;
        } else {
            $vo = $this->GetGoodsFavMustInfoVo($uid);
            $result = $this->GetGoodsFavMustInfoDao()->GetView($vo);
            if (! empty($result)) {
                parent::SetCacheFile($uidKey, $result);
                return $result;
            } else {
                $this->GetException(KbmException::DATA_ERROR_VIEW);
            }
        }
    }

    /**
     * 자주 사용하는 상품 필수 정보 생성하기
     *
     * @param RequestVo $request
     * @return GoodsFavMustInfoVo
     */
    public function GetGoodsFavMustInfoCreate(RequestVo $request = null)
    {
        $vo = $this->GetGoodsFavMustInfoVo($this->GetUniqId("I" . date("Ymd")), $request);
        $this->GetGoodsFavMustInfoParse($vo, $request, null);
        if ($this->GetGoodsFavMustInfoDao()->SetCreate($vo)) {
            $oldView = new GoodsFavMustInfoVo();
            $oldView->mallId = $vo->mallId;
            $oldView->infoId = $vo->infoId;
            $this->UnsetGoodsFavMustInfoCache($oldView, $vo);
            return $vo;
        } else {
            parent::GetException(KbmException::DATA_ERROR_CREATE);
        }
    }

    /**
     * 자주 사용하는 상품 필수 정보 파싱하기
     *
     * @param GoodsFavMustInfoVo $vo
     * @param RequestVo $request
     * @param GoodsFavMustInfoVo $oldView
     * @return GoodsFavMustInfoVo
     */
    public function GetGoodsFavMustInfoParse(GoodsFavMustInfoVo $vo, RequestVo $request, GoodsFavMustInfoVo $oldView = null)
    {
        if ($request->hasKey('contents')) {
            $titleConents = new TitleContentVo();
            $titleConents->titleLocale = new LocaleTextVo();
            $titleConents->contentsLocale = new LocaleTextVo();
            $vo->contents = $request->GetItemArray('contents', $titleConents);
        }
        return $vo;
    }

    /**
     * 자주 사용하는 상품 필수 정보 업데이트
     *
     * @param string $uid
     * @param RequestVo $request
     * @return GoodsFavMustInfoVo
     */
    public function GetGoodsFavMustInfoUpdate($uid = '', RequestVo $request = null)
    {
        $oldView = $this->GetGoodsFavMustInfoView($uid);
        $vo = $this->GetGoodsFavMustInfoVo($uid, $request, clone $oldView);
        $this->GetGoodsFavMustInfoParse($vo, $request, $oldView);
        if ($this->GetGoodsFavMustInfoDao()->SetUpdate($vo)) {
            $this->UnsetGoodsFavMustInfoCache($oldView, $vo);
            return $vo;
        } else {
            parent::GetException(KbmException::DATA_ERROR_UPDATE);
        }
    }

    /**
     * 자주 사용하는 상품 필수 정보 복제하기
     *
     * @param string $uid
     * @return GoodsFavMustInfoVo
     */
    public function GetGoodsFavMustInfoCopy($uid = '')
    {
        $reqCopy = new RequestVo($this->GetGoodsFavMustInfoView($uid));
        return $this->GetGoodsFavMustInfoCreate($reqCopy);
    }

    /**
     * 자주 사용하는 상품 필수 정보 삭제하기
     *
     * @param string $uid
     * @return GoodsFavMustInfoVo
     */
    public function GetGoodsFavMustInfoDelete($uid = '')
    {
        $vo = $this->GetGoodsFavMustInfoView($uid);
        if ($this->GetGoodsFavMustInfoDao()->SetDelete($vo)) {
            $this->UnsetGoodsFavMustInfoCache($vo, new GoodsFavMustInfoVo());
            return $vo;
        } else {
            parent::GetException(KbmException::DATA_ERROR_DELETE);
        }
    }

    /**
     * 자주 사용하는 상품 필수 정보 변경하기
     *
     * @param string[] $uidList
     * @param string $mode
     * @return boolean
     */
    public function GetGoodsFavMustInfoChange($uidList = Array(), $mode = 'delete')
    {
        $searchVo = new SearchVo();
        $searchVo->mallId = $this->mallId;
        $searchVo->codes = $uidList;
        if (count($searchVo->codes) > 0) {
            switch ($mode) {
                case 'copy':
                    try {
                        foreach ($searchVo->codes as $infoId) {
                            $goodsFavMustInfoVo = $this->GetGoodsFavMustInfoView($infoId);
                            $goodsFavMustInfoVo->infoId = $this->GetUniqId("I" . date("Ymd"));
                            $this->GetGoodsFavMustInfoDao()->SetCreate($goodsFavMustInfoVo);
                        }
                    } catch (Exception $ex) {}
                    break;
                case 'delete':
                    try {
                        foreach ($searchVo->codes as $infoId) {
                            $goodsFavMustInfoVo = $this->GetGoodsFavMustInfoView($infoId);
                            $this->GetGoodsFavMustInfoDao()->SetDelete($goodsFavMustInfoVo);
                        }
                    } catch (Exception $ex) {}
                    break;
            }
        }
        return true;
    }

    /**
     * 자주 사용하는 상품 옵션 Excel 파일로 가져오기
     *
     * @param RequestVo $request
     * @param string $isHidden
     * @return ExcelVo
     */
    public function GetGoodsFavMustInfoListExcel(RequestVo $request = null)
    {
        $downloadFormVo = $this->GetDownloadFormVo($request, 'goodsCodes');
        $dataList = $this->GetGoodsFavMustInfoList($downloadFormVo->searchRequest);
        $excelData = Array();
        foreach ($dataList as $data) {
            $parentItem = clone $data;
            $parentItem->contents = Array();
            foreach ($data->contents as $item) {
                $item->parent = $parentItem;
                $excelData[] = $item;
            }
        }
        $excelVo = new ExcelVo($downloadFormVo, $excelData);
        $excelVo->AddHeaderList($downloadFormVo->fieldList, true);
        return $excelVo;
    }

    /**
     * 자주 사용하는 상품 컨텐츠 정보 DAO 가져오기
     *
     * @return \Dao\GoodsFavContentsDao
     */
    public function GetGoodsFavContentsDao()
    {
        return parent::GetDao('GoodsFavContentsDao');
    }

    /**
     * 자주 사용하는 상품 컨텐츠 정보 VO 가져오기
     *
     * @param string $uid
     * @param RequestVo $request
     * @param GoodsFavContentsVo $vo
     * @return GoodsFavContentsVo
     */
    public function GetGoodsFavContentsVo($uid = '', RequestVo $request = null, $vo = null)
    {
        $vo = parent::GetFill($request, empty($vo) ? 'GoodsFavContentsVo' : $vo);
        $vo->contentsId = $uid;
        return $vo;
    }

    /**
     * 자주 사용하는 상품 컨텐츠 정보 캐쉬 삭제하기
     *
     * @param GoodsFavContentsVo $oldVo
     * @param GoodsFavContentsVo $newVo
     */
    private function UnsetGoodsFavContentsCache(GoodsFavContentsVo $oldVo, GoodsFavContentsVo $newVo)
    {
        if ($oldVo !== $newVo) {
            $log = $this->getAccessLog($oldVo, $newVo, 'goodsFavContents.');
            $this->setAccessLog('GoodsFavContents.' . $oldVo->contentsId, $log);
        }
        parent::UnSetCacheFile(parent::GetServiceCacheKey('goodsFavContents', '*'));
    }

    /**
     * 자주 사용하는 상품 컨텐츠 정보 페이징 가져오기
     *
     * @param RequestVo $request
     * @return \Vo\PagingVo
     */
    public function GetGoodsFavContentsPaging(RequestVo $request)
    {
        $vo = parent::GetSearchVo($request, 'GoodsSearchVo');
        return $this->GetGoodsFavContentsDao()->GetPaging($vo, $request->GetPerPage(10), $request->GetOffset());
    }

    /**
     * 자주 사용하는 상품 컨텐츠 정보 목록 가져오기
     *
     * @param RequestVo $request
     * @return GoodsFavContentsVo[]
     */
    public function GetGoodsFavContentsList(RequestVo $request)
    {
        $vo = parent::GetSearchVo($request, 'GoodsSearchVo');
        return $this->GetGoodsFavContentsDao()->GetList($vo, $request->GetPerPage(10), $request->GetOffset());
    }

    /**
     * 자주 사용하는 상품 컨텐츠 정보 가져오기
     *
     * @param string $uid
     * @param boolean $isCopy
     * @return GoodsFavContentsVo
     */
    public function GetGoodsFavContentsView($uid = '', $isCopy = false)
    {
        $vo = $this->GetGoodsFavContentsVo($uid);
        $result = $this->GetGoodsFavContentsDao()->GetView($vo);
        if (! empty($result)) {
            if ($isCopy) {
                return $this->GetGoodsFavContentsClone($result);
            } else {
                return $result;
            }
        } else {
            $this->GetException(KbmException::DATA_ERROR_VIEW);
        }
    }

    /**
     * 자주 사용하는 상품 컨텐츠 정보 생성하기
     *
     * @param RequestVo $request
     * @return GoodsFavContentsVo
     */
    public function GetGoodsFavContentsCreate(RequestVo $request = null)
    {
        $vo = $this->GetGoodsFavContentsVo($this->GetUniqId("C" . date("Ymd")), $request);
        $this->GetGoodsFavContentsParse($vo, $request, null);
        if ($this->GetGoodsFavContentsDao()->SetCreate($vo)) {
            $oldView = new GoodsFavContentsVo();
            $oldView->mallId = $vo->mallId;
            $oldView->contentsId = $vo->contentsId;
            $this->UnsetGoodsFavContentsCache($oldView, $vo);
            return $vo;
        } else {
            parent::GetException(KbmException::DATA_ERROR_CREATE);
        }
    }

    /**
     * 자주 사용하는 상품 컨텐츠 정보 파싱하기
     *
     * @param GoodsFavContentsVo $vo
     * @param RequestVo $request
     * @param GoodsFavContentsVo $oldView
     * @return GoodsFavContentsVo
     */
    public function GetGoodsFavContentsParse(GoodsFavContentsVo $vo, RequestVo $request, GoodsFavContentsVo $oldView = null)
    {
        if ($request->hasKey('contents')) {
            $vo->contents = $this->GetEditorParse($vo->contents, ! empty($oldView) ? $oldView->contents : '', 'contents');
            $vo->contentsText = strip_tags($vo->contents);
        }
        if (! empty($vo->contents)) {
            $vo->contentsRaw = $this->GetEditorParseRaw($vo->contents);
        }
        return $vo;
    }

    /**
     * 자주 사용하는 상품 컨텐츠 정보 업데이트 하기
     *
     * @param string $uid
     * @param RequestVo $request
     * @return GoodsFavContentsVo
     */
    public function GetGoodsFavContentsUpdate($uid = '', RequestVo $request = null)
    {
        $oldView = $this->GetGoodsFavContentsView($uid);
        $vo = $this->GetGoodsFavContentsVo($uid, $request, clone $oldView);
        $this->GetGoodsFavContentsParse($vo, $request, $oldView);
        if ($this->GetGoodsFavContentsDao()->SetUpdate($vo)) {
            $this->UnsetGoodsFavContentsCache($oldView, $vo);
            return $vo;
        } else {
            parent::GetException(KbmException::DATA_ERROR_UPDATE);
        }
    }

    /**
     * 자주 사용하는 상품 컨텐츠 정보 가져오기
     *
     * @param string $uid
     * @return GoodsFavContentsVo
     */
    public function GetGoodsFavContentsCopy($uid = '')
    {
        $vo = $this->GetGoodsFavContentsView($uid);
        $reqCopy = new RequestVo($this->GetGoodsFavContentsClone($vo));
        return $this->GetGoodsFavContentsCreate($reqCopy);
    }

    /**
     * 자주 사용하는 상품 컨텐츠 정보 복제 가져오기
     *
     * @param GoodsFavContentsVo $vo
     * @return GoodsFavContentsVo
     */
    public function GetGoodsFavContentsClone(GoodsFavContentsVo $vo)
    {
        $cloneVo = clone $vo;
        if (! empty($cloneVo->contents)) {
            $cloneVo->contents = $this->GetEditorParseRaw($cloneVo->contents);
        }
        return $cloneVo;
    }

    /**
     * 자주 사용하는 상품 컨텐츠 정보 목록 가져오기
     *
     * @param string $uid
     * @return GoodsFavContentsVo
     */
    public function GetGoodsFavContentsDelete($uid = '')
    {
        $vo = $this->GetGoodsFavContentsView($uid);
        if ($this->GetGoodsFavContentsDao()->SetDelete($vo)) {
            $this->UnsetGoodsFavContentsCache($vo, new GoodsFavContentsVo());
            return $vo;
        } else {
            parent::GetException(KbmException::DATA_ERROR_DELETE);
        }
    }

    /**
     * 자주 사용하는 상품 컨텐츠 정보 변경하기
     *
     * @param string[] $uidList
     * @param string $mode
     * @return boolean
     */
    public function GetGoodsFavContentsChange($uidList = Array(), $mode = 'delete')
    {
        $searchVo = new SearchVo();
        $searchVo->mallId = $this->mallId;
        $searchVo->codes = $uidList;
        if (count($searchVo->codes) > 0) {
            switch ($mode) {
                case 'copy':
                    try {
                        foreach ($searchVo->codes as $contentsId) {
                            $goodsFavContentsVo = $this->GetGoodsFavContentsView($contentsId);
                            $goodsFavContentsVo->contentsId = $this->GetUniqId("C" . date("Ymd"));
                            $this->GetGoodsFavContentsDao()->SetCreate($goodsFavContentsVo);
                        }
                    } catch (Exception $ex) {}
                    break;
                case 'delete':
                    try {
                        foreach ($searchVo->codes as $contentsId) {
                            $goodsFavContentsVo = $this->GetGoodsFavContentsView($contentsId);
                            $this->GetGoodsFavContentsDao()->SetDelete($goodsFavContentsVo);
                        }
                    } catch (Exception $ex) {}
                    break;
            }
        }
        return true;
    }

    /**
     * 자주 사용하는 상품 컨텐츠 Excel 파일로 가져오기
     *
     * @param RequestVo $request
     * @return ExcelVo
     */
    public function GetGoodsFavContentsListExcel(RequestVo $request = null)
    {
        $downloadFormVo = $this->GetDownloadFormVo($request, 'goodsCodes');
        $excelData = $this->GetGoodsFavContentsList($downloadFormVo->searchRequest);
        $excelVo = new ExcelVo($downloadFormVo, $excelData);
        $excelVo->AddHeaderList($downloadFormVo->fieldList, true);
        return $excelVo;
    }

    /**
     * 상품 관련 QNA DAO 가져오기
     *
     * @return \Dao\GoodsQnaDao
     */
    public function GetGoodsQnaDao()
    {
        return parent::GetDao('GoodsQnaDao');
    }

    /**
     * 상품 관련 QNA 캐시 삭제하기
     *
     * @param GoodsQnaVo $vo
     * @param GoodsQnaVo $oldView
     */
    public function UnsetGoodsQnaCache(GoodsQnaVo $vo, GoodsQnaVo $oldView = null)
    {
        if ($oldView != $vo) {
            $log = $this->getAccessLog($oldView, $vo);
            $this->setAccessLog('goodsqna.' . $vo->uid, $log);
        }
        if (! empty($vo->memNo)) {
            $memberService = $this->GetServiceMember();
            try {
                $memInfo = $memberService->GetMemberView($vo->memNo, '', true);
                if (! empty($memInfo)) {
                    $sendMode = '';
                    if ($vo->isAnswered == 'Y' && $oldView->isAnswered == 'N') {
                        $sendMode = 'M';
                    }
                    if ($vo->isAnswered == 'N' && empty($oldView->uid)) {
                        $sendMode .= 'A';
                    }
                    if (! empty($sendMode)) {
                        $smsService = $this->GetServiceSms();
                        $smsService->SendSmsByCode($memInfo->cellPhone, 'board_b2', $memInfo->memLocale, Array(
                            'memNm' => $memInfo->memNm
                        ), $sendMode, '', true);
                    }
                }
            } catch (Exception $ex) {}
        }
    }

    /**
     * 상품 관련 QNA 파싱
     *
     * @param GoodsQnaVo $vo
     * @param RequestVo $request
     * @param GoodsQnaVo $oldView
     * @param LoginInfoVo $loginInfo
     * @return GoodsQnaVo
     */
    public function GetGoodsQnaParse(GoodsQnaVo $vo, RequestVo $request, GoodsQnaVo $oldView = null, LoginInfoVo $loginInfo = null)
    {
        if (! empty($vo->answerContents)) {
            $vo->isAnswered = 'Y';
            if ($this->isDateEmpty($vo->answerDate)) {
                $vo->answerDate = date("Y-m-d H:i:s");
            }
            if (! empty($loginInfo)) {
                if ($loginInfo->memLevel == 'E' && $request->hasKey('scmNo')) {
                    $scmNo = $request->scmNo;
                    try {
                        if (! empty($scmNo)) {
                            $scmService = $this->GetServiceScm();
                            $scmInfo = $scmService->GetScmInfoView($request->scmNo);
                            if (! empty($scmInfo->scmMemNo)) {
                                $vo->answerMemNo = $scmInfo->scmMemNo;
                            }
                        }
                    } catch (\Exception $ex) {}
                }
                if (empty($vo->answerMemNo)) {
                    $vo->answerMemNo = $loginInfo->memNo;
                }
                if (empty($vo->answerMemNm)) {
                    $vo->answerMemNm = $loginInfo->memNm;
                }
            }
        } else {
            $vo->isAnswered = 'N';
            $vo->answerDate = '';
        }
        $vo->contents = $this->GetSafeHtmlContents($vo->contents, true);
        $vo->answerContents = $this->GetSafeHtmlContents($vo->answerContents, true);
        if (! empty($loginInfo)) {
            $this->GetMyArticle($loginInfo, $vo);
        } else {
            $this->GetMyArticle($this->loginInfo, $vo);
        }
        return $vo;
    }

    /**
     * 상품 관련 QNA 생성하기
     *
     * @param LoginInfoVo $loginInfoVo
     * @param RequestVo $request
     * @return GoodsQnaVo
     */
    public function GetGoodsQnaCreate(LoginInfoVo $loginInfoVo, RequestVo $request)
    {
        $vo = new GoodsQnaVo();
        $this->GetFill($request, $vo);
        if (! $this->IsUserAdmin($loginInfoVo, 'A')) {
            $vo->memNo = $loginInfoVo->memNo;
            $vo->memNm = $loginInfoVo->memNm;
        } else {
            if (empty($vo->memNo)) {
                $vo->memNo = $loginInfoVo->memNo;
                $vo->memNm = $loginInfoVo->memNm;
            }
        }

        $vo->goodsCode = $request->goodsCode;
        $this->GetGoodsQnaParse($vo, $request, null, $loginInfoVo);
        if ($this->GetGoodsQnaDao()->SetCreate($vo)) {
            $oldVo = new GoodsQnaVo();
            $oldVo->uid = $vo->uid;
            $this->UnsetGoodsQnaCache($vo, $oldVo);
            $this->AddGoodsInfoLog('Q', $vo->goodsCode, 1);
            return $vo;
        } else {
            parent::GetException(KbmException::DATA_ERROR_CREATE);
        }
    }

    /**
     * 상품 관련 QNA 검색 가져오기
     *
     * @param RequestVo $request
     * @return \Vo\GoodsQnaSearchVo
     */
    public function GetGoodsQnaSearchVo(RequestVo $request = null)
    {
        $vo = new GoodsQnaSearchVo();
        $this->GetSearchVo($request, $vo);
        $vo->scmNo = $this->GetUserAdminScmNo();
        return $vo;
    }

    /**
     * 상품 관련 QNA 페이지 가져오기
     *
     * @param LoginInfoVo $loginInfoVo
     * @param RequestVo $request
     * @return \Vo\PagingVo
     */
    public function GetGoodsQnaPaging(LoginInfoVo $loginInfoVo = null, RequestVo $request = null)
    {
        $vo = $this->GetGoodsQnaSearchVo($request);
        $voPaging = $this->GetGoodsQnaDao()->GetPaging($vo, $request->GetPerPage(10), $request->GetOffset());
        $result = $this->SetMyArticlesPaging($loginInfoVo, $voPaging);
        foreach ($result->items as $item) {
            $item->goodsItemVo = $this->GetGoodsInfoSimpleView($item->goodsCode, '', $loginInfoVo->groupSno);
        }
        return $result;
    }

    /**
     * 상품 관련 QNA 목록 가져오기
     *
     * @param LoginInfoVo $loginInfoVo
     * @param RequestVo $request
     * @return GoodsQnaVo[]
     */
    public function GetGoodsQnaList(LoginInfoVo $loginInfoVo, RequestVo $request)
    {
        $vo = $this->GetGoodsQnaSearchVo($request);
        $voList = $this->GetGoodsQnaDao()->GetList($vo, $request->GetPerPage(10), $request->GetOffset());
        foreach ($voList as $item) {
            $item->goodsItemVo = $this->GetGoodsInfoSimpleView($item->goodsCode, '', $loginInfoVo->groupSno);
        }
        return $this->SetMyArticles($loginInfoVo, $voList);
    }

    /**
     * 상품 관련 QNA Excel 파일 가져오기
     *
     * @param RequestVo $request
     * @param LoginInfoVo $loginInfoVo
     * @return ExcelVo
     */
    public function GetGoodsQnaListExcel(RequestVo $request = null, LoginInfoVo $loginInfoVo = null)
    {
        $downloadFormVo = $this->GetDownloadFormVo($request, 'uids');
        $excelData = $this->GetGoodsQnaList($loginInfoVo, $downloadFormVo->searchRequest);
        $excelVo = new ExcelVo($downloadFormVo, $excelData);
        $excelVo->AddHeaderList($downloadFormVo->fieldList, true);
        return $excelVo;
    }

    /**
     * 상품 관련 마이 QNA 페이징 가져오기
     *
     * @param LoginInfoVo $loginInfoVo
     * @param RequestVo $request
     * @return \Vo\PagingVo
     */
    public function GetGoodsMyQnaPaging(LoginInfoVo $loginInfoVo, RequestVo $request)
    {
        $vo = new GoodsQnaSearchVo();
        $this->GetSearchVo($request, $vo);
        $vo->goodsCode = $request->goodsCode;
        $vo->memNo = $loginInfoVo->memNo;
        if (! empty($vo->memNo)) {
            $voList = $this->GetGoodsQnaDao()->GetPaging($vo, $request->GetPerPage(10), $request->GetOffset());
            if (! empty($voList)) {
                foreach ($voList->items as $item) {
                    $item->goodsItemVo = $this->GetGoodsInfoSimpleView($item->goodsCode, '', $loginInfoVo->groupSno);
                }
                $this->SetMyArticlesGoodsQna($loginInfoVo, $voList->items);
                return $voList;
            }
        }
        return new PagingVo();
    }

    /**
     * 상품 관련 마이 QNA 목록 가져오기
     *
     * @param LoginInfoVo $loginInfoVo
     * @param RequestVo $request
     * @return GoodsQnaVo[]
     */
    public function GetGoodsMyQnaList(LoginInfoVo $loginInfoVo, RequestVo $request)
    {
        $vo = new GoodsQnaSearchVo();
        $this->GetSearchVo($request, $vo);
        $vo->goodsCode = $request->goodsCode;
        $vo->memNo = $loginInfoVo->memNo;
        if (! empty($vo->memNo)) {
            $voList = $this->GetGoodsQnaDao()->GetList($vo, $request->GetPerPage(10), $request->GetOffset());
            if (! empty($voList)) {
                foreach ($voList as $item) {
                    $item->goodsItemVo = $this->GetGoodsInfoSimpleView($item->goodsCode, '', $loginInfoVo->groupSno);
                }
                return $this->SetMyArticlesGoodsQna($loginInfoVo, $voList);
            }
        }
        return Array();
    }

    /**
     * 리스크 나의 아이템 여부 체크
     *
     * @param LoginInfoVo $loginInfoVo
     * @param GoodsQnaVo[] $voList
     * @param boolean $isAdmin
     * @return GoodsQnaVo[]
     */
    public function SetMyArticlesGoodsQna(LoginInfoVo $loginInfoVo = null, $voList = Array(), $isAdmin = false)
    {
        foreach ($voList as $vo) {
            $this->SetMyArticle($loginInfoVo, $vo, $isAdmin);
            if (! $vo->isMyArticle && $vo->isSecret == 'Y') {
                $vo->contents = 'Secret Contents';
                $vo->answerContents = '';
            }
        }
        return $voList;
    }

    /**
     * 상품 관련 QNA 보기
     *
     * @param LoginInfoVo $loginInfoVo
     * @param integer $uid
     * @return GoodsQnaVo
     */
    public function GetGoodsQnaView(LoginInfoVo $loginInfoVo, $uid = 0)
    {
        $vo = new GoodsQnaVo();
        $vo->mallId = $this->mallId;
        $vo->uid = $uid;
        $result = $this->GetGoodsQnaDao()->GetView($vo);
        if (! empty($result)) {
            $result->goodsItemVo = $this->GetGoodsInfoSimpleView($result->goodsCode);
            $this->SetMyArticlesGoodsQna($loginInfoVo, Array(
                $result
            ));
            return $result;
        } else {
            $this->GetException(KbmException::DATA_ERROR_VIEW);
        }
    }

    /**
     * 상품 관련 마이 QNA 보기 정보 가져오기
     *
     * @param LoginInfoVo $loginInfoVo
     * @param integer $uid
     * @param RequestVo $request
     * @param boolean $isScm
     * @return GoodsQnaVo
     */
    public function GetGoodsQnaUpdate(LoginInfoVo $loginInfoVo, $uid = 0, RequestVo $request, $isScm = false)
    {
        $oldView = $this->GetGoodsQnaView($loginInfoVo, $uid);

        if ($oldView->isMyArticle || $isScm) {
            /**
             *
             * @var GoodsQnaVo $vo
             */
            $vo = clone $oldView;
            $this->GetFill($request, $vo);
            $vo->mallId = $this->mallId;
            $vo->uid = $uid;
            $this->GetGoodsQnaParse($vo, $request, $oldView, $loginInfoVo);
            if ($this->GetGoodsQnaDao()->SetUpdate($vo)) {
                $this->UnsetGoodsQnaCache($vo, $oldView);
            }
            return $vo;
        } else {
            $this->GetException(KbmException::DATA_ERROR_AUTH);
        }
    }

    /**
     * 상품 관련 마이 QNA 정보 삭제
     *
     * @param LoginInfoVo $loginInfoVo
     * @param integer $uid
     * @return boolean
     */
    public function GetGoodsQnaDelete(LoginInfoVo $loginInfoVo, $uid)
    {
        $vo = $this->GetGoodsQnaView($loginInfoVo, $uid);
        if ($vo->isMyArticle) {
            if ($this->GetGoodsQnaDao()->SetDelete($vo)) {
                $newVo = new GoodsQnaVo();
                $newVo->uid = $vo->uid;
                $this->UnsetGoodsQnaCache($newVo, $vo);
                return true;
            }
        } else {
            $this->GetException(KbmException::DATA_ERROR_AUTH);
        }
        return false;
    }

    /**
     * 회원 정보 싱크하기
     *
     * @param MemberVo $memInfo
     */
    public function SetChangeMemberInfo(MemberVo $memInfo)
    {
        $this->GetGoodsQnaDao()->SetUpdateMemInfo($memInfo);
        $this->GetGoodsReviewDao()->SetUpdateMemInfo($memInfo);
    }

    /**
     * 상품 리뷰 DAO 가져오기
     *
     * @return \Dao\GoodsReviewDao
     */
    public function GetGoodsReviewDao()
    {
        return parent::GetDao('GoodsReviewDao');
    }

    /**
     * 상품 리뷰 캐시 삭제하기
     *
     * @param GoodsReviewVo $vo
     * @param GoodsReviewVo $oldView
     */
    public function UnsetGoodsReviewCache(GoodsReviewVo $vo, GoodsReviewVo $oldView)
    {
        if ($oldView != $vo) {
            $log = $this->getAccessLog($oldView, $vo);
            $this->setAccessLog('goodsreview.' . $vo->reviewUid, $log);
            $this->SetFileLogUpdate($vo->attachImage, $oldView->attachImage, 'goodsreview', $vo->reviewUid, 'attachImage');
        }
        if (! empty($vo->memNo)) {
            $memberService = $this->GetServiceMember();
            try {
                $memInfo = $memberService->GetMemberView($vo->memNo, '', true);
                if (! empty($memInfo)) {
                    $sendMode = '';
                    if ($vo->isReview == 'Y' && $oldView->isReview == 'N') {
                        $sendMode .= 'A';
                    }
                    if (! empty($sendMode)) {
                        $smsService = $this->GetServiceSms();
                        $smsService->SendSmsByCode($memInfo->cellPhone, 'board_b1', $memInfo->memLocale, Array(
                            'memNm' => $memInfo->memNm
                        ), $sendMode, '', true);
                    }
                }
                if (! empty($memInfo) && ! empty($memInfo->memNo) && ! in_array('code17' . md5($vo->goodsCode . ' ' . $vo->orderOption), $memInfo->mileageLog) && $this->isDateEmpty($oldView->regDate) && $vo->isReview == 'Y') {
                    $mileageBasic = $this->GetServicePolicy()->GetMemberMileageBasicView();
                    if (! empty($mileageBasic) && $mileageBasic->payUsableFl == 'Y') {
                        $mileageGive = $this->GetServicePolicy()->GetMemberMileageGiveView();
                        if (! empty($mileageGive) && $mileageGive->goodsReviewFl == 'Y' && $mileageGive->goodsReviewAmount > 0) {
                            $this->GetServiceMember()->GetMemberMileageChange($memInfo->memNo, $mileageGive->goodsReviewAmount, 'code17', null, Array(
                                'mileageReasonCode' => 'code17' . md5($vo->goodsCode . ' ' . $vo->orderOption)
                            ), 'O');
                        }
                    }
                }
            } catch (Exception $ex) {}
        }
        $goodsCode = $vo->goodsCode;
        if (! empty($goodsCode)) {
            $goodsVo = $this->GetGoodsInfoVo($goodsCode);
            $this->GetGoodsInfoDao()->SetReviewUpdate($goodsVo);
            parent::UnSetCacheFile(parent::GetServiceCacheKey('goodsInfo', $goodsCode, '*'));
        }
        $goodsCodeOld = $oldView->goodsCode;
        if (! empty($goodsCodeOld) && $goodsCode != $goodsCodeOld) {
            $goodsVo = $this->GetGoodsInfoVo($goodsCodeOld);
            $this->GetGoodsInfoDao()->SetReviewUpdate($goodsVo);
            parent::UnSetCacheFile(parent::GetServiceCacheKey('goodsInfo', $goodsCodeOld, '*'));
        }
    }

    /**
     * 상품 리뷰 파싱하기
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
     * 상품 리뷰 등록하기
     *
     * @param LoginInfoVo $loginInfoVo
     * @param RequestVo $request
     * @param boolean $isMobile
     * @return GoodsReviewVo
     */
    public function GetGoodsReviewCreate(LoginInfoVo $loginInfoVo, RequestVo $request, $isMobile = false)
    {
        $vo = new GoodsReviewVo();
        $this->GetFill($request, $vo);
        $vo->memNo = $loginInfoVo->memNo;
        $vo->memNm = $loginInfoVo->memNm;
        $vo->goodsCode = $request->goodsCode;
        $vo->isMobile = $isMobile ? 'Y' : 'N';
        $vo->reviewUid = $this->GetUniqId('REVIEW');
        $vo = $this->GetGoodsReviewParse($vo, $request, null, $loginInfoVo);
        if ($this->GetGoodsReviewDao()->SetCreate($vo)) {
            $this->UnsetGoodsReviewCache($vo, new GoodsReviewVo());
            $this->AddGoodsInfoLog('R', $vo->goodsCode, 1);
            return $vo;
        } else {
            $this->GetException(KbmException::DATA_ERROR_CREATE);
        }
    }

    /**
     * 상품 리뷰 페이징 가져오기
     *
     * @param LoginInfoVo $loginInfo
     * @param RequestVo $request
     * @return \Vo\PagingVo
     */
    public function GetGoodsReviewSearchVo(LoginInfoVo $loginInfo, RequestVo $request, $bestOnly = false)
    {
        $vo = new GoodsReviewSearchVo();
        $this->GetSearchVo($request, $vo);
        $vo->scmNo = $this->GetUserAdminScmNo();
        if ($bestOnly) {
            $vo->isRecommended = 'Y';
        }
        return $vo;
    }

    /**
     * 상품 리뷰 페이징 가져오기
     *
     * @param LoginInfoVo $loginInfo
     * @param RequestVo $request
     * @return \Vo\PagingVo
     */
    public function GetGoodsReviewPaging(LoginInfoVo $loginInfo, RequestVo $request, $bestOnly = false)
    {
        $vo = $this->GetGoodsReviewSearchVo($loginInfo, $request, $bestOnly);
        $result = $this->GetGoodsReviewDao()->GetPaging($vo, $request->GetPerPage(10), $request->GetOffset());
        foreach ($result->items as $item) {
            if (! empty($item->attachImage)) {
                $item->attachImageList = explode('@!@', $item->attachImage);
            }
            $item->goodsItemVo = $this->GetGoodsInfoSimpleView($item->goodsCode, '', $loginInfo->groupSno);
        }
        return $this->SetMyArticlesPaging($loginInfo, $result);
    }

    /**
     * 전체범위 상품 리뷰 페이징 가져오기
     *
     * @param LoginInfoVo $loginInfo
     * @param RequestVo $request
     * @return \Vo\PagingVo
     */
    public function GetGoodsReviewTotalPaging(LoginInfoVo $loginInfo, RequestVo $request, $bestOnly = false)
    {
        $vo = $this->GetGoodsReviewSearchVo($loginInfo, $request, $bestOnly);
        $result = $this->GetGoodsReviewDao()->GetPaging($vo, $request->GetPerPage(10), $request->GetOffset());

        foreach ($result->items as $item) {
            if (! empty($item->attachImage)) {
                $item->attachImageList = explode('@!@', $item->attachImage);
            }
            $item->goodsItemVo = $this->GetGoodsInfoSimpleView($item->goodsCode, '', $loginInfo->groupSno);
        }
        return $this->SetMyArticlesPaging($loginInfo, $result);
    }

    /**
     * 상품 리뷰 목록 가져오기
     *
     * @param LoginInfoVo $loginInfoVo
     * @param RequestVo $request
     * @return GoodsReviewVo[]
     */
    public function GetGoodsReviewList(LoginInfoVo $loginInfoVo, RequestVo $request, $bestOnly = false)
    {
        $vo = $this->GetGoodsReviewSearchVo($loginInfoVo, $request, $bestOnly);
        $voList = $this->GetGoodsReviewDao()->GetList($vo, $request->GetPerPage(10), $request->GetOffset());
        if (! empty($voList)) {
            foreach ($voList as $item) {
                if (! empty($item->attachImage)) {
                    $item->attachImageList = explode('@!@', $item->attachImage);
                }
            }
            $result = $this->SetMyArticles($loginInfoVo, $voList);
            foreach ($result as $item) {
                $item->goodsItemVo = $this->GetGoodsInfoSimpleView($item->goodsCode, '', $loginInfoVo->groupSno);
            }
            return $result;
        } else {
            return Array();
        }
    }

    /**
     * 상품 리뷰 Excel 목록 가져오기
     *
     * @param RequestVo $request
     * @param LoginInfoVo $loginInfoVo
     * @return ExcelVo
     */
    public function GetGoodsReviewListExcel(RequestVo $request = null, LoginInfoVo $loginInfoVo = null)
    {
        $downloadFormVo = $this->GetDownloadFormVo($request, 'uids');
        $excelData = $this->GetGoodsReviewList($loginInfoVo, $downloadFormVo->searchRequest);
        $excelVo = new ExcelVo($downloadFormVo, $excelData);
        $excelVo->AddHeaderList($downloadFormVo->fieldList, true);
        return $excelVo;
    }

    /**
     * 상품 리뷰 페이징 가져오기
     *
     * @param LoginInfoVo $loginInfoVo
     * @param RequestVo $request
     * @return \Vo\PagingVo
     */
    public function GetGoodsMyReviewPaging(LoginInfoVo $loginInfoVo, RequestVo $request)
    {
        $vo = new GoodsReviewSearchVo();
        $this->GetSearchVo($request, $vo);
        $vo->goodsCode = $request->goodsCode;
        if ($this->CheckLogin($loginInfoVo)) {
            $vo->memNo = $loginInfoVo->memNo;
        }
        $vo->reviewUid = $request->reviewUid;
        $voList = $this->GetGoodsReviewDao()->GetMyPaging($vo, $request->GetPerPage(10), $request->GetOffset());
        if (! empty($voList)) {
            foreach ($voList->items as $item) {
                if (! empty($item->attachImage)) {
                    $item->attachImageList = explode('@!@', $item->attachImage);
                }
            }
            return $this->SetMyArticlesPaging($loginInfoVo, $voList);
        } else {
            return $voList;
        }
    }

    /**
     * 상품 리뷰 목록 가져오기
     *
     * @param LoginInfoVo $loginInfoVo
     * @param RequestVo $request
     * @return GoodsReviewVo
     */
    public function GetGoodsMyReviewList(LoginInfoVo $loginInfoVo, RequestVo $request)
    {
        $vo = new GoodsReviewSearchVo();
        $this->GetSearchVo($request, $vo);
        $vo->goodsCode = $request->goodsCode;
        if ($this->CheckLogin($loginInfoVo)) {
            $vo->memNo = $loginInfoVo->memNo;
        }
        $vo->reviewUid = $request->reviewUid;
        $voList = $this->GetGoodsReviewDao()->GetMyList($vo, $request->GetPerPage(10), $request->GetOffset());
        if (! empty($voList)) {
            foreach ($voList as $item) {
                if (! empty($item->attachImage)) {
                    $item->attachImageList = explode('@!@', $item->attachImage);
                }
            }
            return $this->SetMyArticles($loginInfoVo, $voList);
        } else {
            return $voList;
        }
    }

    /**
     * 상품 리뷰 정보 보기 가져오기
     *
     * @param LoginInfoVo $loginInfoVo
     * @param string $uid
     * @return GoodsReviewVo
     */
    public function GetGoodsReviewView(LoginInfoVo $loginInfoVo, $uid)
    {
        $vo = new GoodsReviewVo();
        $vo->mallId = $this->mallId;
        $vo->reviewUid = $uid;
        $result = $this->GetGoodsReviewDao()->GetView($vo);
        if (! empty($result)) {
            if (! empty($result->attachImage)) {
                $result->attachImageList = explode('@!@', $result->attachImage);
            }
            $result->goodsItemVo = $this->GetGoodsInfoSimpleView($result->goodsCode, '', $loginInfoVo->groupSno);
            return $this->SetMyArticle($loginInfoVo, $result);
        } else {
            $this->GetException(KbmException::DATA_ERROR_VIEW);
        }
    }

    /**
     * 상품 리뷰 정보 보기 가져오기
     *
     * @param LoginInfoVo $loginInfoVo
     * @param string $uid
     * @return GoodsReviewVo
     */
    public function GetGoodsReviewBatch(RequestVo $request)
    {
        $mode = $request->mode;
        switch ($mode) {
            case 'recommend':
                $vo = new GoodsReviewSearchVo();
                $vo->mallId = $this->mallId;
                $vo->reviewUids = $request->GetItemArray('reviewUids');
                $vo->isRecommended = $request->isRecommended;
                $this->GetGoodsReviewDao()->SetRecommendedUpdate($vo);
                return true;
                break;
            default:
                $this->GetException(KbmException::DATA_ERROR_UNKNOWN);
                break;
        }
    }

    /**
     * 상품 리뷰 보기 가져오기
     *
     * @param LoginInfoVo $loginInfoVo
     * @param string $uid
     * @param RequestVo $request
     * @return GoodsReviewVo
     */
    public function GetGoodsReviewUpdate(LoginInfoVo $loginInfoVo, $uid, RequestVo $request)
    {
        $oldView = $this->GetGoodsReviewView($loginInfoVo, $uid);
        if ($oldView->isMyArticle) {
            $vo = $this->GetFill($request, clone $oldView);
            $vo = $this->GetGoodsReviewParse($vo, $request, $oldView, $loginInfoVo);
            if ($this->GetGoodsReviewDao()->SetUpdate($vo)) {
                $this->UnsetGoodsReviewCache($vo, $oldView);
            }
            return $vo;
        } else {
            $this->GetException(KbmException::DATA_ERROR_AUTH);
        }
    }

    /**
     * 상품 리뷰 보기 삭제하기
     *
     * @param LoginInfoVo $loginInfoVo
     * @param string $uid
     * @return boolean
     */
    public function GetGoodsReviewDelete(LoginInfoVo $loginInfoVo, $uid)
    {
        $vo = $this->GetGoodsReviewView($loginInfoVo, $uid);
        if ($vo->isMyArticle) {
            $this->GetGoodsReviewParse(null, null, $vo);
            if ($this->GetGoodsReviewDao()->SetDelete($vo)) {
                $newVo = new GoodsReviewVo();
                $newVo->reviewUid = $vo->reviewUid;
                $this->UnsetGoodsReviewCache($newVo, $vo);
            }
            return true;
        } else {
            $this->GetException(KbmException::DATA_ERROR_AUTH);
        }
    }

    /**
     * 상품 카트 DAO 가져오기
     *
     * @return \Dao\GoodsCartDao
     */
    public function GetGoodsCartDao()
    {
        return parent::GetDao('GoodsCartDao');
    }

    /**
     * 상품 카트 VO 파싱하기
     *
     * @param GoodsCartVo $vo
     * @param RequestVo $request
     * @param GoodsCartVo $oldView
     * @return GoodsCartVo
     */
    public function GetGoodsCartParse(GoodsCartVo $vo, RequestVo $request, GoodsCartVo $oldView = null)
    {
        // $vo->sumPrice = $vo->goodsSumPrice - $vo->goodsDiscountPrice - $vo->goodsDiscountDeliveryPrice;
        return $vo;
    }

    /**
     * 상품 카트 상품 유효성 확인하기
     *
     * @param GoodsCartVo[] $cartItems
     * @param LoginInfoVo $loginInfoVo
     * @return GoodsCartPayInfoVo
     */
    public function GetGoodsCartPayInfoVo($cartItems = Array(), LoginInfoVo $loginInfoVo, $checkOption = false)
    {
        $payInfo = new GoodsCartPayInfoVo();
        $payInfo->isDeliverable = true;
        try {
            $sumCart = $this->CheckGoodsStockPrice($cartItems, $loginInfoVo, $checkOption);
            foreach ($cartItems as $item) {
                $payInfo->goodsPrice += $item->goodsPrice;
                $payInfo->goodsScmPrice += $item->goodsScmPrice;
                $payInfo->goodsWeight += $item->goodsWeight;
                $payInfo->goodsCntWeight += $item->goodsCntWeight;
                $payInfo->goodsCnt += $item->goodsCnt;
                $payInfo->goodsOptionCnt += $item->goodsOptionCnt;
                $payInfo->goodsOptionPrice += $item->goodsOptionPrice;
                $payInfo->goodsExtPrice += $item->goodsExtPrice;
                $payInfo->goodsTextPrice += $item->goodsTextPrice;
                $payInfo->goodsRefPrice += $item->goodsRefPrice;
                $payInfo->goodsExtCnt += $item->goodsExtCnt;
                $payInfo->goodsTextCnt += $item->goodsTextCnt;
                $payInfo->goodsRefCnt += $item->goodsRefCnt;
                $payInfo->goodsOptionScmPrice += $item->goodsOptionScmPrice;
                $payInfo->goodsExtScmPrice += $item->goodsExtScmPrice;
                $payInfo->goodsTextScmPrice += $item->goodsTextScmPrice;
                $payInfo->goodsRefScmPrice += $item->goodsRefScmPrice;
                $payInfo->goodsViewSumPrice += $item->goodsViewSumPrice;
                $payInfo->goodsSumPrice += $item->goodsSumPrice;
                $payInfo->goodsSumScmPrice += $item->goodsSumScmPrice;
                $payInfo->goodsAddPrice += $item->goodsAddPrice;
                $payInfo->goodsAddScmPrice += $item->goodsAddScmPrice;
                $payInfo->goodsCntPrice += $item->goodsCntPrice;
                $payInfo->goodsCntScmPrice += $item->goodsCntScmPrice;
                $payInfo->goodsDeliveryPrice += $item->goodsDeliveryPrice;
                $payInfo->goodsDeliveryRawPrice += $item->goodsDeliveryRawPrice;
                $payInfo->goodsPackingPrice += $item->goodsPackingPrice;
                $payInfo->goodsDiscountDeliveryPrice += $item->goodsDiscountDeliveryPrice;
                $payInfo->goodsSumDeliveryPrice += $item->goodsSumDeliveryPrice;
                $payInfo->goodsCodDeliveryPrice += $item->goodsCodDeliveryPrice;
                $payInfo->goodsDiscountPrice += $item->goodsDiscountPrice;
                $payInfo->goodsSumDiscountPrice += $item->goodsSumDiscountPrice;
                $payInfo->goodsMileage += $item->goodsMileage;
                $payInfo->goodsCouponMileage += $item->goodsCouponMileage;
                $payInfo->goodsSumMileage += $item->goodsSumMileage;
                $payInfo->goodsTaxPrice += $item->goodsTaxPrice;
                $payInfo->goodsTaxSupplyPrice += $item->goodsTaxSupplyPrice;
                $payInfo->goodsTaxServicePrice += $item->goodsTaxServicePrice;
                $payInfo->goodsTaxScmPrice += $item->goodsTaxScmPrice;
                $payInfo->goodsTaxScmSupplyPrice += $item->goodsTaxScmSupplyPrice;
                $payInfo->goodsTaxScmServicePrice += $item->goodsTaxScmServicePrice;
                $payInfo->goodsCouponPrice += $item->goodsCouponPrice;
                $payInfo->sumPrice += $item->sumPrice;
                $payInfo->sumScmPrice += $item->sumScmPrice;
                $payInfo->taxDeliveryPercent += $item->taxDeliveryPercent;
                $payInfo->taxPrice += $item->taxPrice;
                $payInfo->taxSupplyPrice += $item->taxSupplyPrice;
                $payInfo->taxServicePrice += $item->taxServicePrice;
                $payInfo->taxDeliveryPrice += $item->taxDeliveryPrice;
                $payInfo->taxDeliverySupplyPrice += $item->taxDeliverySupplyPrice;
                $payInfo->taxDeliveryServicePrice += $item->taxDeliveryServicePrice;
                $payInfo->taxDeliveryScmPrice += $item->taxDeliveryScmPrice;
                $payInfo->taxDeliveryScmSupplyPrice += $item->taxDeliveryScmSupplyPrice;
                $payInfo->taxDeliveryScmServicePrice += $item->taxDeliveryScmServicePrice;
                $payInfo->taxScmPrice += $item->taxScmPrice;
                $payInfo->taxScmSupplyPrice += $item->taxScmSupplyPrice;
                $payInfo->taxScmServicePrice += $item->taxScmServicePrice;
                if (! $item->isDeliverable) {
                    $payInfo->isDeliverable = false;
                }
            }
            $payInfo->taxPercent = 10;
            $payInfo->goodsItemCnt = count($cartItems);
            $payInfo->isDeliverable = $sumCart->isDeliverable;
            $payInfo->orderDeliveryType = $sumCart->goodsDeliveryType;
            $payInfo->orderDeliveryPrice += $sumCart->goodsDeliveryPrice;
            $payInfo->orderDeliveryRawPrice = $sumCart->goodsDeliveryRawPrice;
            $payInfo->orderCodDeliveryPrice = $sumCart->goodsCodDeliveryPrice;
            $payInfo->taxDeliveryPercent = $sumCart->taxDeliveryPercent;
            $payInfo->taxDeliveryPrice = $sumCart->taxDeliveryPrice;
            $payInfo->taxDeliverySupplyPrice = $sumCart->taxDeliverySupplyPrice;
            $payInfo->taxDeliveryServicePrice = $sumCart->taxDeliveryServicePrice;
            $payInfo->orderPackingPrice = $sumCart->goodsPackingPrice;
            $payInfo->orderDeliveryPrice = $sumCart->goodsDeliveryPrice;
            $payInfo->orderSumDeliveryPrice = $sumCart->goodsDeliveryPrice;
            $payInfo->goodsSumDeliveryPrice = max(0, $payInfo->goodsSumDeliveryPrice);
            $payInfo->codDeliveryPrice += max(0, $payInfo->orderCodDeliveryPrice + $payInfo->goodsCodDeliveryPrice);
            $payInfo->goodsAddPrice = max(0, $payInfo->goodsOptionPrice + $payInfo->goodsExtPrice + $payInfo->goodsTextPrice + $payInfo->goodsRefPrice);
            $payInfo->orderSumDeliveryPrice = max(0, $payInfo->orderDeliveryPrice - $payInfo->orderDeliveryDiscountPrice);
            $payInfo->sumDeliveryPrice = max(0, $payInfo->goodsSumDeliveryPrice + $payInfo->orderSumDeliveryPrice);
            list ($payInfo->orderSumDeliveryPrice, $payInfo->taxOrderDeliverySupplyPrice, $payInfo->taxOrderDeliveryPrice, $payInfo->taxOrderDeliveryServicePrice) = $this->GetTaxPrice($payInfo->orderSumDeliveryPrice, $payInfo->taxDeliveryPercent);
            list ($payInfo->orderDiscountPrice, $payInfo->taxOrderDiscountSupplyPrice, $payInfo->taxOrderDiscountPrice, $payInfo->taxOrderDeliveryServicePrice) = $this->GetTaxPrice($payInfo->orderDiscountPrice, $payInfo->taxPercent);
            list ($payInfo->useMileage, $payInfo->taxMileageSupplyPrice, $payInfo->taxMileagePrice, $payInfo->taxMileageServicePrice) = $this->GetTaxPrice($payInfo->useMileage, $payInfo->taxPercent);
            $payInfo->sumPrice = max(0, $payInfo->goodsSumPrice - $payInfo->goodsDiscountPrice + $payInfo->goodsSumDeliveryPrice + $payInfo->orderSumDeliveryPrice - $payInfo->orderDiscountPrice - $payInfo->useMileage);
            $payInfo->taxPrice = max(0, $payInfo->goodsTaxPrice + $payInfo->taxDeliveryPrice + $payInfo->taxOrderDeliveryPrice - $payInfo->taxOrderDiscountPrice - $payInfo->taxMileagePrice);
            $payInfo->taxSupplyPrice = max(0, $payInfo->goodsTaxSupplyPrice + $payInfo->taxDeliverySupplyPrice + $payInfo->taxOrderDeliverySupplyPrice - $payInfo->taxOrderDiscountSupplyPrice - $payInfo->taxMileageSupplyPrice);
            $payInfo->taxServicePrice = max(0, $payInfo->goodsTaxServicePrice + $payInfo->taxDeliveryServicePrice + $payInfo->taxOrderDeliveryServicePrice - $payInfo->taxOrderDiscountServicePrice - $payInfo->taxMileageServicePrice);
            $payInfo->goodsSumScmPrice = max(0, $payInfo->goodsCntScmPrice + $payInfo->goodsAddScmPrice);
            $payInfo->sumScmPrice = max(0, $payInfo->goodsSumScmPrice + $payInfo->goodsSumDeliveryPrice);
            $payInfo->taxScmPrice = max(0, $payInfo->goodsTaxScmPrice + $payInfo->taxDeliveryScmPrice);
            $payInfo->taxScmSupplyPrice = max(0, $payInfo->goodsTaxScmSupplyPrice + $payInfo->taxDeliveryScmSupplyPrice);
            $payInfo->taxScmServicePrice = max(0, $payInfo->goodsTaxScmServicePrice + $payInfo->taxDeliveryScmServicePrice);
            $payInfo->sumMileage = max(0, $payInfo->goodsMileage + $payInfo->goodsCouponMileage + $payInfo->orderMileagePrice);
            if (! empty($loginInfoVo)) {
                $payInfo->deliveryAddress = $loginInfoVo->address;
            }
        } catch (\Exception $ex) {
            $payInfo->isDeliverable = false;
        }
        return $payInfo;
    }

    /**
     * 상품 카트 상태 정보 가져오기
     *
     * @param LoginInfoVo $loginInfoVo
     * @param RequestVo $request
     * @return GoodsCartPayInfoVo
     */
    public function GetGoodsCartStatus(LoginInfoVo $loginInfoVo, RequestVo $request)
    {
        $vo = new GoodsCartSearchVo();
        $vo->mallId = $this->mallId;
        $vo->cartId = $loginInfoVo->sharedToken;
        if ($request->hasKey('goodsCodes')) {
            $vo->goodsCodes = $request->goodsCodes;
        }
        $voList = $this->GetGoodsCartDao()->GetList($vo, 1000, 0);
        return $this->GetGoodsCartPayInfoVo($voList, $loginInfoVo);
    }

    /**
     * 상품 카트정보 관리자 가져오기
     *
     * @param LoginInfoVo $loginInfoVo
     * @param RequestVo $request
     * @return GoodsCartVo
     */
    public function GetGoodsCartCreateAdmin(LoginInfoVo $loginInfoVo, RequestVo $request)
    {
        $vo = new GoodsCartVo();
        $vo->goodsOption = new GoodsCartOptionVo();
        $vo->goodsOption->options[] = new GoodsCartOptionItemVo();
        $vo->goodsOption->optionsText[] = new GoodsCartOptionItemVo();
        $vo->goodsOption->optionsExt[] = new GoodsCartOptionItemVo();
        $vo->goodsOption->optionsRef[] = new GoodsCartOptionItemVo();
        $request->GetFill($vo);
        $vo = $this->GetGoodsCartVo($vo, $loginInfoVo);
        if (! empty($vo)) {
            $vo->cartId = $loginInfoVo->sharedToken;
            $vo->goodsCode = $request->goodsCode;
            $vo = $this->GetGoodsCartParse($vo, $request, null);
            return $vo;
        } else {
            return null;
        }
    }

    /**
     * 장바구니 / 관심상품
     *
     * @param LoginInfoVo $loginInfoVo
     * @return \Vo\OrderCartVo
     */
    public function GetOrderCartVo(LoginInfoVo $loginInfoVo)
    {
        $orderCartVo = $this->GetServicePolicy()->GetOrderCartView();
        $orderCartVo->sharedToken = $loginInfoVo->sharedToken;
        return $orderCartVo;
    }

    /**
     * 상품 카트정보 옵션 병합
     *
     * @param GoodsCartOptionItemVo[] $newOptions
     * @param GoodsCartOptionItemVo[] $oldOptions
     */
    public function GetGoodsCartOptionMerge($newOptions = Array(), $oldOptions = Array())
    {
        if (is_null($newOptions)) {
            $newOptions = Array();
        }
        if (is_null($oldOptions)) {
            $oldOptions = Array();
        }
        $newOptionMap = Array();
        foreach ($newOptions as $option) {
            $optionId = $option->id;
            $newOptionMap[$optionId] = $option;
        }
        foreach ($oldOptions as $option) {
            $optionId = $option->id;
            if (isset($newOptionMap[$optionId])) {
                $oldOption = $newOptionMap[$optionId];
                if ($oldOption->optionTextDuplCart != 'Y') {
                    $oldOption->optionCnt += $option->optionCnt;
                } else {
                    $newOptions[] = $option;
                }
            } else {
                $newOptions[] = $option;
            }
        }
        return $newOptions;
    }

    /**
     * 상품 카트정보 생성하기
     *
     * @param LoginInfoVo $loginInfoVo
     * @param RequestVo $request
     * @return GoodsCartVo
     */
    public function GetAuctionCartCreate(LoginInfoVo $loginInfoVo, RequestVo $request)
    {
        $vo = new GoodsCartVo();
        $goodsCartOptionItemVo = new GoodsCartOptionItemVo();
        $goodsCartOptionItemVo->selectedChildren = [];
        $vo->goodsOption = new GoodsCartOptionVo();
        $vo->goodsOption->options[] = $this->GetDeepClone($goodsCartOptionItemVo);
        $vo->goodsOption->optionsText[] = $this->GetDeepClone($goodsCartOptionItemVo);
        $vo->goodsOption->optionsExt[] = $this->GetDeepClone($goodsCartOptionItemVo);
        $vo->goodsOption->optionsRef[] = $this->GetDeepClone($goodsCartOptionItemVo);
        $request->GetFill($vo);
        $vo->goodsType = 'A';
        $orderCartVo = $this->GetOrderCartVo($loginInfoVo);
        $vo->mallId = $this->mallId;
        $vo->goodsCode = $request->goodsCode;
        $vo->auctionCode = $request->auctionCode;
        $vo->auctionPrice = $request->auctionPrice;
        $vo->cartId = $loginInfoVo->memNo;
        $vo = $this->GetGoodsCartVo($vo, $loginInfoVo);
        $vo = $this->GetGoodsCartParse($vo, $request);
        if (! empty($vo->goodsPrice) || $orderCartVo->zeroPriceOrderFl == 'Y') {
            if ($this->GetAuctionInfoDao()->SetBidderCreateByCart($vo)) {
                $this->GetAuctionInfoUpdateStatus($vo->auctionCode);
            }
        }
        return $vo;
    }

    /**
     * 상품 카트정보 생성하기
     *
     * @param LoginInfoVo $loginInfoVo
     * @param RequestVo $request
     * @return GoodsCartVo
     */
    public function GetGoodsCartCreate(LoginInfoVo $loginInfoVo, RequestVo $request)
    {
        $vo = new GoodsCartVo();

        $goodsCartOptionItemVo = new GoodsCartOptionItemVo();
        $goodsCartOptionItemVo->selectedChildren = [];
        $vo->goodsOption = new GoodsCartOptionVo();
        $vo->goodsOption->options[] = $this->GetDeepClone($goodsCartOptionItemVo);
        $vo->goodsOption->optionsText[] = $this->GetDeepClone($goodsCartOptionItemVo);
        $vo->goodsOption->optionsExt[] = $this->GetDeepClone($goodsCartOptionItemVo);
        $vo->goodsOption->optionsRef[] = $this->GetDeepClone($goodsCartOptionItemVo);
        $request->GetFill($vo);
        $orderCartVo = $this->GetOrderCartVo($loginInfoVo);
        $vo->mallId = $this->mallId;
        $vo->cartId = $loginInfoVo->sharedToken;
        $vo->goodsCode = $request->goodsCode;
        $oldCart = $this->GetGoodsCartDao()->GetView($vo);
        if ($orderCartVo->sameGoodsFl == 'Y' && $request->updateCart != 'Y') {
            if (! empty($oldCart)) {
                $vo->goodsCnt += $oldCart->goodsCnt;
                $oldOption = $oldCart->goodsOption;
                $newOption = $vo->goodsOption;
                if (! empty($oldOption)) {
                    if (empty($newOption)) {
                        $newOption = new GoodsCartOptionVo();
                    }
                    $newOption->options = $this->GetGoodsCartOptionMerge($newOption->options, $oldOption->options);
                    $newOption->optionsExt = $this->GetGoodsCartOptionMerge($newOption->optionsExt, $oldOption->optionsExt);
                    $newOption->optionsText = $this->GetGoodsCartOptionMerge($newOption->optionsText, $oldOption->optionsText);
                    $newOption->optionsRef = $this->GetGoodsCartOptionMerge($newOption->optionsRef, $oldOption->optionsRef);
                }
                if (! empty($oldCart->goodsCoupon)) {
                    $vo->goodsCoupon = $oldCart->goodsCoupon;
                }
            }
        }
        $vo = $this->GetGoodsCartVo($vo, $loginInfoVo);
        $vo = $this->GetGoodsCartParse($vo, $request);
        if (! empty($vo->goodsPrice) || $orderCartVo->zeroPriceOrderFl == 'Y') {
            if (! empty($oldCart)) {
                $this->GetGoodsCartDao()->SetUpdate($vo);
            } else {
                $this->GetGoodsCartDao()->SetCreate($vo);
            }
            if ($orderCartVo->periodFl == 'Y' || $orderCartVo->goodsLimitFl == 'Y') {
                $this->GetGoodsCartDao()->SetDeleteCron($orderCartVo);
            }
            if ($orderCartVo->moveWish2CartPageFl == 'N') {
                $this->GetServiceMember()->GetMemberFavorDelete($loginInfoVo, $vo->goodsCode);
            }
            $this->GetGoodsCartCouponUpdate($loginInfoVo, $vo);
        }

        $goodsCode = $vo->goodsCode;
        if (! empty($goodsCode) && strlen($goodsCode) > 10) {
            $uidKey = $this->GetServiceCacheKey('_cartuser_', $loginInfoVo->sharedToken, md5($goodsCode));
            $result = parent::GetCacheFile($uidKey, true);
            if (empty($result)) {
                parent::SetCacheFile($uidKey, $goodsCode);
                $this->GetServiceStatistics()->SetLogUpdate($loginInfoVo->memLocale, 'goodscart', $goodsCode, 1, '', true);
            }
        }
        return $vo;
    }

    /**
     * 카트 비우기
     *
     * @param LoginInfoVo $loginInfoVo
     * @return boolean
     */
    public function GetGoodsCartDeleteAll(LoginInfoVo $loginInfoVo)
    {
        $vo = new GoodsCartVo();
        $vo->mallId = $this->mallId;
        $vo->cartId = $loginInfoVo->sharedToken;
        return $this->GetGoodsCartDao()->SetDeleteAll($vo);
    }

    /**
     * 카트에서 특정 상품 삭제하기
     *
     * @param LoginInfoVo $loginInfoVo
     * @param string $goodsCode
     * @return boolean
     */
    public function GetGoodsCartDelete(LoginInfoVo $loginInfoVo, $goodsCode = '')
    {
        $vo = new GoodsCartVo();
        $vo->mallId = $this->mallId;
        $vo->cartId = $loginInfoVo->sharedToken;
        $vo->goodsCode = $goodsCode;
        return $this->GetGoodsCartDao()->SetDelete($vo);
    }

    /**
     * 카트의 특정상품 업데이트 하기
     *
     * @param LoginInfoVo $loginInfoVo
     * @param string $goodsCode
     * @param RequestVo $request
     * @return boolean
     */
    public function GetGoodsCartUpdate(LoginInfoVo $loginInfoVo, $goodsCode = '', RequestVo $request)
    {
        $vo = $this->GetGoodsCartView($loginInfoVo, $goodsCode);
        if (empty($vo)) {
            $request->goodsCode = $goodsCode;
            $result = $this->GetGoodsCartCreate($loginInfoVo, $request);
            if (! empty($result)) {
                return true;
            } else {
                return false;
            }
        } else {
            if ($request->hasKey('options')) {
                $vo->goodsOption = new GoodsCartOptionVo();
                $vo->goodsOption->options[] = new GoodsCartOptionItemVo();
                $vo->goodsOption->optionsText[] = new GoodsCartOptionItemVo();
                $vo->goodsOption->optionsExt[] = new GoodsCartOptionItemVo();
                $vo->goodsOption->optionsRef[] = new GoodsCartOptionItemVo();
            }
            $request->GetFill($vo);
            if ($request->hasKey('goodsCoupon')) {
                $vo->goodsCoupon = $request->goodsCoupon;
                $vo->goodsCouponVo = null;
            }
            $vo = $this->GetGoodsCartVo($vo, $loginInfoVo);
            if (! empty($vo)) {
                if ($this->GetGoodsCartDao()->SetUpdate($vo)) {
                    $this->GetGoodsCartCouponUpdate($loginInfoVo, $vo);
                }
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * 카트 새로 고침
     *
     * @param LoginInfoVo $loginInfoVo
     * @return boolean
     */
    public function GetGoodsCartRefresh(LoginInfoVo $loginInfoVo)
    {
        $tmpRequest = new RequestVo();
        $tmpRequest->limit = 1000;
        $cartList = $this->GetGoodsCartList($loginInfoVo, $tmpRequest);
        foreach ($cartList as $item) {
            $resetRequest = new RequestVo();
            $resetRequest->goodsCode = $item->goodsCode;
            try {
                $this->GetGoodsCartUpdate($loginInfoVo, $item->goodsCode, $resetRequest);
            } catch (Exception $ex) {}
        }
    }

    /**
     * 카트의 특정상품 업데이트 하기
     *
     * @param LoginInfoVo $loginInfoVo
     * @param GoodsCartVo $vo
     * @return boolean
     */
    public function GetGoodsCartCouponUpdate(LoginInfoVo $loginInfoVo, GoodsCartVo $vo)
    {
        if (! empty($vo->goodsCoupon)) {
            $tmpRequest = new RequestVo();
            $tmpRequest->limit = 1000;
            $cartList = $this->GetGoodsCartList($loginInfoVo, $tmpRequest);
            foreach ($cartList as $item) {
                if ($item->goodsCode != $vo->goodsCode && $item->goodsCoupon == $vo->goodsCoupon) {
                    $resetRequest = new RequestVo();
                    $resetRequest->goodsCoupon = '';
                    $this->GetGoodsCartUpdate($loginInfoVo, $item->goodsCode, $resetRequest);
                }
            }
        }
    }

    /**
     * 상품 네이버 페이 주문하기
     *
     * @param LoginInfoVo $loginInfoVo
     * @param RequestVo $request
     * @return string
     */
    public function GetGoodsNaverOrder(LoginInfoVo $loginInfoVo, RequestVo $request)
    {
        $goodsCartPriceVo = $this->GetGoodsCartPrice($loginInfoVo, $request);
        if (! empty($goodsCartPriceVo)) {
            $naverShopService = new NaverShopService($this->mallId);
            $naverShopOrder = $naverShopService->GetPayOrderInfo($goodsCartPriceVo);
            if (! empty($naverShopOrder)) {
                $naverShopOrder->goodsCartPriceVo = $goodsCartPriceVo;
                $uidKey = parent::GetServiceCacheKey('naverOrder', $naverShopOrder->orderId);
                parent::SetCacheFile($uidKey, $naverShopOrder);
                return $naverShopOrder->orderId;
            }
        } else {
            return null;
        }
    }

    /**
     * 상품 네이버 페이지 주문 정보
     *
     * @param string $orderId
     * @return \Vo\NaverShopOrderVo
     */
    public function GetGoodsNaverOrderInfo($orderId = '')
    {
        $uidKey = parent::GetServiceCacheKey('naverOrder', $orderId);
        return parent::GetCacheFile($uidKey);
    }

    /**
     * 상품 네이버 관심 상품 정보
     *
     * @param LoginInfoVo $loginInfoVo
     * @param RequestVo $request
     * @return string
     */
    public function GetGoodsNaverWish(LoginInfoVo $loginInfoVo, RequestVo $request)
    {
        $goodsCodes = $request->GetItemArray('goodsCodes');
        $voList = Array();
        foreach ($goodsCodes as $goodsCode) {
            try {
                $vo = $this->GetGoodsInfoView($goodsCode, $loginInfoVo);
                $voList[] = $vo;
            } catch (Exception $ex) {}
        }
        if (! empty($voList)) {
            $naverShopService = new NaverShopService($this->mallId);
            $naverShopWish = $naverShopService->GetPayWishInfo($voList);
            if (! empty($naverShopWish)) {
                $itemId = md5($naverShopWish->itemId);
                $naverShopWish->voList = $voList;
                $uidKey = parent::GetServiceCacheKey('naverWish', $itemId);
                parent::SetCacheFile($uidKey, $naverShopWish);
                return $itemId;
            }
        } else {
            return null;
        }
    }

    /**
     * 네이버 관심 상품 정보 가져오기
     *
     * @param string $itemId
     * @return \Vo\NaverShopWishVo
     */
    public function GetGoodsNaverWishInfo($itemId = '')
    {
        $uidKey = parent::GetServiceCacheKey('naverWish', $itemId);
        return parent::GetCacheFile($uidKey);
    }

    /**
     * 상품 네이버 상품 정보 XML
     *
     * @param string $queryString
     * @return string
     */
    public function GetGoodsNaverGoodsInfo($queryString = '')
    {
        $xmlLine = Array();
        $voList = Array();
        foreach (explode('&', $queryString) as $pair) {
            list ($key, $value) = explode('=', $pair . '=');
            if ($key == 'ITEM_ID') {
                list ($goodsCode) = explode('_', $value . '_');
                if (! empty($goodsCode)) {
                    try {
                        $vo = $this->GetGoodsInfoView($goodsCode);
                        $voList[$value] = $vo;
                    } catch (Exception $ex) {}
                    ;
                }
            }
        }
        if (! empty($voList)) {
            $naverShopService = new NaverShopService($this->mallId);
            $xmlLine = $naverShopService->GetPayGoodsInfo($voList, $this);
        }
        return '<?xml version="1.0" encoding="utf-8"?>' . "\n<response>\n" . implode("\n", $xmlLine) . "\n</response>";
    }

    /**
     * 상품 카트 특정 상품 정보 가져오기
     *
     * @param LoginInfoVo $loginInfoVo
     * @param string $goodsCode
     * @return GoodsCartVo
     */
    public function GetGoodsCartView(LoginInfoVo $loginInfoVo, $goodsCode = '')
    {
        $vo = new GoodsCartVo();
        $vo->mallId = $this->mallId;
        $vo->cartId = $loginInfoVo->sharedToken;
        $vo->goodsCode = $goodsCode;
        return $this->GetGoodsCartDao()->GetView($vo);
    }

    /**
     * 상품 카트 상품 목록 가져오기
     *
     * @param LoginInfoVo $loginInfoVo
     * @param RequestVo $request
     * @return GoodsCartVo[]
     */
    public function GetGoodsCartList(LoginInfoVo $loginInfoVo, RequestVo $request)
    {
        $vo = new GoodsCartVo();
        $this->GetSearchVo($request, $vo);
        $vo->cartId = $loginInfoVo->sharedToken;
        $voList = $this->GetGoodsCartDao()->GetList($vo, $request->GetPerPage(10), $request->GetOffset());
        $globalCartItem = $this->CheckGoodsStockPrice($voList, $loginInfoVo);
        foreach ($voList as $item) {
            if ($item->useDeliver != 'Y') {
                $item->goodsDeliveryPrice += $globalCartItem->goodsDeliveryPrice;
                $item->goodsSumDeliveryPrice += $globalCartItem->goodsSumDeliveryPrice;
                $item->goodsDeliveryRawPrice += $globalCartItem->goodsDeliveryRawPrice;
                $item->goodsPackingPrice += $globalCartItem->goodsPackingPrice;
                $item->sumPrice += $globalCartItem->sumPrice;
                break;
            }
        }
        return $voList;
    }

    /**
     * 상품 카트 상품 유효성 확인하기
     *
     * @param GoodsCartVo[][] $items
     * @param LoginInfoVo $loginInfoVo
     */
    public function CheckGoodsDeliveryScmCart(PolicyService $policyService, $goodsSumByScmDelivery = Array(), LoginInfoVo $loginInfoVo)
    {
        $sumCartDeliveryInfo = Array();
        foreach ($goodsSumByScmDelivery as $deliveryOptionKey => $items) {
            if (count($items) > 1) {
                foreach ($items as $seqn => $item) {
                    if ($seqn == 0) {
                        $goodsCartVo = new GoodsCartVo();
                        $sumCartDeliveryInfo[$deliveryOptionKey] = $goodsCartVo;
                        $goodsCartVo->masterCart = $item;
                    } else {
                        $item->goodsDeliveryPrice = 0;
                        $item->deliveryPackingPrice = 0;
                        $item->goodsDiscountDeliveryPrice = 0;
                        $item->goodsSumDeliveryPrice = 0;
                        $item->goodsCodDeliveryPrice = 0;
                        $item->taxDeliverySupplyPrice = 0;
                        $item->taxDeliveryServicePrice = 0;
                    }
                    $goodsCartVo->goodsWeight += $item->goodsWeight;
                    $goodsCartVo->goodsPrice += $item->goodsPrice;
                    $goodsCartVo->goodsSumPrice += $item->goodsSumPrice;
                    $goodsCartVo->goodsCnt += $item->goodsCnt;
                    $goodsCartVo->goodsCntPrice += $item->goodsCntPrice;
                    $goodsCartVo->goodsCntWeight += $item->goodsCntWeight;
                    $goodsCartVo->goodsAddPrice += $item->goodsAddPrice;
                    $goodsCartVo->goodsOptionPrice += $item->goodsOptionPrice;
                    $goodsCartVo->goodsExtPrice += $item->goodsExtPrice;
                    $goodsCartVo->goodsTextPrice += $item->goodsTextPrice;
                    $goodsCartVo->goodsRefPrice += $item->goodsRefPrice;
                    $goodsCartVo->goodsOptionCnt += $item->goodsOptionCnt;
                    $goodsCartVo->goodsTextCnt += $item->goodsTextCnt;
                    $goodsCartVo->goodsExtCnt += $item->goodsExtCnt;
                    $goodsCartVo->goodsRefCnt += $item->goodsRefCnt;
                    $this->CheckGoodsCartItem($item);
                }
            }
        }
        foreach ($sumCartDeliveryInfo as $deliveryOptionKey => $item) {
            $matches = Array();
            if (preg_match("#^([0-9]+)_(.+)#", $deliveryOptionKey, $matches)) {
                $goodsInfo = new GoodsInfoVo();
                $cartInfo = $item->masterCart;
                $goodsInfo->goodsCode = $cartInfo->goodsCode;
                $goodsInfo->deliveryFl = 'U';
                $goodsInfo->deliveryOption = $matches[2];
                $policyService->GetDeliveryConfigGoodsCart($goodsInfo, $item, $loginInfoVo);
                $cartInfo->goodsDeliveryType = $item->goodsDeliveryType;
                $cartInfo->deliveryPackingPrice = $item->deliveryPackingPrice;
                $cartInfo->goodsDeliveryPrice = $item->goodsDeliveryPrice;
                $cartInfo->goodsDiscountDeliveryPrice = $item->goodsDiscountDeliveryPrice;
                $cartInfo->goodsSumDeliveryPrice = $item->goodsSumDeliveryPrice;
                $cartInfo->goodsSumDeliveryPrice = 0;
                $cartInfo->goodsCodDeliveryPrice = $item->goodsCodDeliveryPrice;
                $cartInfo->taxDeliverySupplyPrice = $item->taxDeliverySupplyPrice;
                $cartInfo->taxDeliveryServicePrice = $item->taxDeliveryServicePrice;
                $cartInfo->isDeliverable = $item->isDeliverable;
                $this->CheckGoodsCartItem($cartInfo);
            }
        }
        foreach ($goodsSumByScmDelivery as $deliveryOptionKey => $items) {
            if (count($items) > 0) {
                $dumyGoodsInfo = new GoodsInfoVo();
                $packingCartInfo = null;
                foreach ($items as $seqn => $item) {
                    if (! empty($item->goodsInfo) && $item->goodsInfo->deliveryPackingRule == 'O') {
                        if (empty($packingCartInfo)) {
                            $packingCartInfo = $item;
                        }
                        $goodsInfo = new GoodsInfoVo();
                        $goodsInfo->goodsCode = $item->goodsCode;
                        $goodsInfo->deliveryPackingPrice = $item->goodsInfo->deliveryPackingPrice;
                        $goodsInfo->deliveryPackingRule = $item->goodsInfo->deliveryPackingRule;
                        $policyService->GetDeliveryPriceRuleMerge($dumyGoodsInfo, $goodsInfo, $item->goodsSumPrice, $item->goodsCnt);
                    }
                }
                if (! empty($dumyGoodsInfo) && ! empty($packingCartInfo)) {
                    $dumyGoodsInfo->deliveryPackingRule = 'Y';
                    $dumyGoodsInfo->goodsCode = $packingCartInfo->goodsCode;
                    $tmpCartInfo = new GoodsCartVo();
                    $tmpCartInfo->goodsSumPrice = $dumyGoodsInfo->goodsPrice;
                    $tmpCartInfo->goodsCnt = $dumyGoodsInfo->stockCnt;
                    $policyService->GetDeliveryConfigGoodsCart($dumyGoodsInfo, $tmpCartInfo, $loginInfoVo, false);
                    $packingCartInfo->goodsPackingPrice = $tmpCartInfo->goodsPackingPrice;
                    $packingCartInfo->goodsDeliveryPrice += $packingCartInfo->goodsPackingPrice;
                    $this->CheckGoodsCartItem($packingCartInfo);
                }
            }
        }
    }

    /**
     * 상품 카트 상품 유효성 확인하기
     *
     * @param OptionTreeVo[] $items
     * @return mixed[]
     */
    public function getGoodsOptionTreeMapAll($items = Array(), $inputItem = Array(), $optType = 'OPT', $parentName = '', $parentPrice = 0)
    {
        if (! empty($items)) {
            foreach ($items as $item) {
                $optionKey = $item->id;
                $optionName = strtolower(trim($parentName . ' ' . $item->value));
                $optionPrice = $parentPrice + $item->optionPrice;
                $inputItem[$optionKey] = Array(
                    $optType,
                    $item,
                    $optionPrice
                );
                $inputItem[$optionName] = Array(
                    $optType,
                    $item,
                    $optionPrice
                );
                if (! empty($item->optionChildren)) {
                    $inputItem = $this->getGoodsOptionTreeMapAll($item->optionChildren, $inputItem, $optionName, $optionPrice);
                }
            }
        }
        return $inputItem;
    }

    /**
     * 상품 카트 상품 유효성 확인하기
     *
     * @param GoodsCartVo[] $items
     * @param LoginInfoVo $loginInfoVo
     */
    public function CheckGoodsCart($items = Array(), LoginInfoVo $loginInfoVo, $checkOption = false)
    {
        if (count($items) > 0) {
            $policyService = $this->GetServicePolicy();
            $memberService = $this->GetServiceMember();
            $goodsSumByScmDelivery = Array();
            foreach ($items as $item) {
                if ($checkOption) {
                    if (! empty($loginInfoVo) && (! empty($loginInfoVo->memId) || ! empty($loginInfoVo->memNo))) {
                        try {
                            $memInfo = $memberService->GetMemberView($loginInfoVo->memNo, $loginInfoVo->memId, true);
                            if (! empty($memInfo->memNo)) {
                                $loginInfoVo->memNo = $memInfo->memNo;
                                $loginInfoVo->memId = $memInfo->memId;
                                $loginInfoVo->groupSno = $memInfo->groupSno;
                                $loginInfoVo->memNm = $memInfo->memNm;
                                $loginInfoVo->memLocale = $memInfo->memLocale;
                                $loginInfoVo->nickNm = $memInfo->nickNm;
                                $loginInfoVo->memLevel = 'M';
                                if (! empty($memInfo->address)) {
                                    $loginInfoVo->nationCode = $memInfo->address->nationCode;
                                    $loginInfoVo->address = $memInfo->address;
                                }
                                if (empty($loginInfoVo->nationCode)) {
                                    $loginInfoVo->nationCode = 'kr';
                                }
                                $loginInfoVo->siteOptions = $memInfo;
                            } else {
                                $loginInfoVo->memId = '';
                                $loginInfoVo->memNo = 0;
                                $loginInfoVo->memLevel = 'G';
                            }
                        } catch (\Exception $ex) {
                            $loginInfoVo->memId = '';
                            $loginInfoVo->memNo = 0;
                            $loginInfoVo->memLevel = 'G';
                        }
                    } else if (! empty($loginInfoVo)) {
                        $loginInfoVo->memId = '';
                        $loginInfoVo->memNo = 0;
                        $loginInfoVo->memLevel = 'G';
                    }
                }
                $item->goodsMileage = 0;
                $goodsInfo = $this->GetGoodsInfoView($item->goodsCode, $loginInfoVo);
                if (! empty($goodsInfo)) {
                    $item->goodsInfo = $this->GetGoodsInfoAsSimpleView($goodsInfo);
                }
                if ($checkOption) {
                    if (! empty($item->goodsOption)) {
                        $optionMap = Array();
                        $optionMap = $this->getGoodsOptionTreeMapAll($goodsInfo->optionsTree, $optionMap, 'OPT');
                        $optionMap = $this->getGoodsOptionTreeMapAll($goodsInfo->optionsExtTree, $optionMap, 'EXT');
                        $optionMap = $this->getGoodsOptionTreeMapAll($goodsInfo->optionsTextTree, $optionMap, 'TEXT');
                        $optionMap = $this->getGoodsOptionTreeMapAll($goodsInfo->optionsRefTree, $optionMap, 'REF');
                        $optionsAll = $item->goodsOption->options;
                        $options = Array();
                        $optionsExt = Array();
                        $optionsText = Array();
                        $optionsRef = Array();
                        foreach ($optionsAll as $opt) {
                            $optionType = '';
                            $optionInfo = null;
                            $optionPrice = 0;
                            if (! empty($opt->id) && isset($optionMap[$opt->id])) {
                                list ($optionType, $optionInfo, $optionPrice) = $optionMap[$opt->id];
                            } else if (! empty($opt->value)) {
                                $optionKeyword = $opt->value;
                                $matches = Array();
                                if (preg_match('#(.+)\((.*)\)#', $optionKeyword, $matches)) {
                                    $optionKeyword = $matches[1];
                                }
                                $optionKeyword = trim($optionKeyword);
                                if (! empty($optionKeyword)) {
                                    $optionKeywordList = explode(" ", strtolower($optionKeyword));
                                    foreach ($optionMap as $key => $info) {
                                        $allChecked = true;
                                        foreach ($optionKeywordList as $txt) {
                                            if (! empty($txt) && strpos($key, $txt) === false) {
                                                $allChecked = false;
                                                break;
                                            }
                                        }
                                        if ($allChecked) {
                                            list ($optionType, $optionInfo, $optionPrice) = $info;
                                            break;
                                        }
                                    }
                                }
                            }
                            if (! empty($optionType) && ! empty($optionInfo)) {
                                $opt->id = $optionInfo->id;
                                $opt->optionPrice = $optionPrice;
                                $opt->optionScmPrice = $optionInfo->optionScmPrice;
                                $opt->optionCnt = max(1, $opt->optionCnt);
                                $opt->optionSumPrice = $opt->optionCnt * $opt->optionPrice;
                                $opt->optionSumScmPrice = $opt->optionCnt * $opt->optionScmPrice;
                                switch ($optionType) {
                                    case 'OPT':
                                        $options[] = $opt;
                                        break;
                                    case 'EXT':
                                        $optionsExt[] = $opt;
                                        break;
                                    case 'TEXT':
                                        $optionsText[] = $opt;
                                        break;
                                    case 'REF':
                                        $optionsRef[] = $opt;
                                        break;
                                }
                            }
                        }
                        $item->goodsOption->options = $options;
                        $item->goodsOption->optionsExt = $optionsExt;
                        $item->goodsOption->optionsText = $optionsText;
                        $item->goodsOption->optionsRef = $optionsRef;
                    }
                }
                $policyService->GetDeliveryConfigGoodsCart($goodsInfo, $item, $loginInfoVo);
                $item->goodsImageMaster = $goodsInfo->goodsImageMaster;
                $item->goodsNm = $goodsInfo->goodsNm;
                $item->goodsNmLocale = $goodsInfo->goodsNmLocale;
                $item->goodsPrice = $goodsInfo->goodsPrice;
                if (! empty($item->goodsOption)) {
                    $options = $item->goodsOption->options;
                    $optionsExt = $item->goodsOption->optionsExt;
                    $optionsText = $item->goodsOption->optionsText;
                    $optionsRef = $item->goodsOption->optionsRef;
                    if ((count($options) + count($optionsExt) + count($optionsText) + count($optionsRef)) > 0) {
                        $item->goodsHasOption = 'Y';
                    }
                }
                $item->deliveryNation = $goodsInfo->deliveryNation;
                $item->deliveryPackingRule = $goodsInfo->deliveryPackingRule;
                $item->deliveryPackingPrice = $goodsInfo->deliveryPackingPrice;
                if (! empty($goodsInfo->sellerMemNo) && $goodsInfo->deliveryFl == 'U' && ! empty($goodsInfo->deliveryOption)) {
                    $deliveryOptionKey = $goodsInfo->sellerMemNo . '_' . $goodsInfo->deliveryOption;
                    if (! isset($goodsSumByScmDelivery[$deliveryOptionKey])) {
                        $goodsSumByScmDelivery[$deliveryOptionKey] = Array();
                    }
                    $goodsSumByScmDelivery[$deliveryOptionKey][] = $item;
                } else {
                    $deliveryOptionKey = 'GLOBAL';
                    if (! isset($goodsSumByScmDelivery[$deliveryOptionKey])) {
                        $goodsSumByScmDelivery[$deliveryOptionKey] = Array();
                    }
                    $goodsSumByScmDelivery[$deliveryOptionKey][] = $item;
                }
                $this->CheckGoodsCartItem($item);
            }
            $this->CheckGoodsDeliveryScmCart($policyService, $goodsSumByScmDelivery, $loginInfoVo);
        }
    }

    /**
     * 상품 카트 상품 유효성 확인하기
     *
     * @param GoodsCartVo $item
     */
    public function CheckGoodsCartItem(GoodsCartVo $item)
    {
        $item->goodsSumDiscountPrice = $item->goodsDiscountPrice;
        $item->goodsCntPrice = $item->goodsPrice * $item->goodsCnt;
        $item->goodsAddPrice = $item->goodsOptionPrice + $item->goodsExtPrice + $item->goodsTextPrice + $item->goodsRefPrice;
        $item->goodsSumDeliveryPrice = $item->goodsDeliveryPrice - $item->goodsDiscountDeliveryPrice;
        $item->goodsDeliveryRawPrice = $item->goodsDeliveryPrice - $item->goodsPackingPrice;
        $item->goodsSumPrice = $item->goodsCntPrice + $item->goodsAddPrice;
        $item->goodsViewSumPrice = $item->goodsSumPrice - $item->goodsDiscountPrice;
        $item->goodsSumMileage = $item->goodsMileage + $item->goodsCouponMileage;
        $item->sumPrice = $item->goodsSumPrice + $item->goodsSumDeliveryPrice - $item->goodsDiscountPrice;
    }

    /**
     * 상품 카트 상품 유효성 확인하기
     *
     * @param GoodsCartVo[] $cartItems
     * @param LoginInfoVo $loginInfoVo
     * @return GoodsCartVo
     */
    public function CheckGoodsStockPrice($cartItems = Array(), LoginInfoVo $loginInfoVo, $checkOption = false)
    {
        $globalCartItem = new GoodsCartVo();

        if (count($cartItems) > 0) {
            $policyService = $this->GetServicePolicy();
            $memberService = $this->GetServiceMember();
            $goodsSumByScmDelivery = Array();
            foreach ($cartItems as $item) {
                if ($checkOption) {
                    if (! empty($loginInfoVo) && (! empty($loginInfoVo->memId) || ! empty($loginInfoVo->memNo))) {
                        try {
                            $memInfo = $memberService->GetMemberView($loginInfoVo->memNo, $loginInfoVo->memId, true);
                            if (! empty($memInfo->memNo)) {
                                $loginInfoVo->memNo = $memInfo->memNo;
                                $loginInfoVo->memId = $memInfo->memId;
                                $loginInfoVo->groupSno = $memInfo->groupSno;
                                $loginInfoVo->memNm = $memInfo->memNm;
                                $loginInfoVo->memLocale = $memInfo->memLocale;
                                $loginInfoVo->nickNm = $memInfo->nickNm;
                                $loginInfoVo->memLevel = 'M';
                                if (! empty($memInfo->address)) {
                                    $loginInfoVo->nationCode = $memInfo->address->nationCode;
                                    $loginInfoVo->address = $memInfo->address;
                                }
                                if (empty($loginInfoVo->nationCode)) {
                                    $loginInfoVo->nationCode = 'kr';
                                }
                                $loginInfoVo->siteOptions = $memInfo;
                            } else {
                                $loginInfoVo->memId = '';
                                $loginInfoVo->memNo = 0;
                                $loginInfoVo->memLevel = 'G';
                            }
                        } catch (\Exception $ex) {
                            $loginInfoVo->memId = '';
                            $loginInfoVo->memNo = 0;
                            $loginInfoVo->memLevel = 'G';
                        }
                    } else if (! empty($loginInfoVo)) {
                        $loginInfoVo->memId = '';
                        $loginInfoVo->memNo = 0;
                        $loginInfoVo->memLevel = 'G';
                    }
                }
                $item->goodsMileage = 0;
                $goodsInfo = $this->GetGoodsInfoView($item->goodsCode, $loginInfoVo);
                if (! empty($goodsInfo)) {
                    $item->goodsInfo = $this->GetGoodsInfoAsSimpleView($goodsInfo);
                }
                if ($checkOption) {
                    if (! empty($item->goodsOption) && ((count($item->goodsOption->options) + count($item->goodsOption->optionsExt) + count($item->goodsOption->optionsText) + count($item->goodsOption->optionsRef)) > 0)) {
                        $optionMap = Array();
                        $optionMap = $this->getGoodsOptionTreeMapAll($goodsInfo->optionsTree, $optionMap, 'OPT');
                        $optionMap = $this->getGoodsOptionTreeMapAll($goodsInfo->optionsExtTree, $optionMap, 'EXT');
                        $optionMap = $this->getGoodsOptionTreeMapAll($goodsInfo->optionsTextTree, $optionMap, 'TEXT');
                        $optionMap = $this->getGoodsOptionTreeMapAll($goodsInfo->optionsRefTree, $optionMap, 'REF');
                        $optionsAll = Array();
                        if (! empty($item->goodsOption->options)) {
                            $optionsAll = array_merge($optionsAll, $item->goodsOption->options);
                        }
                        if (! empty($item->goodsOption->optionsExt)) {
                            $optionsAll = array_merge($optionsAll, $item->goodsOption->optionsExt);
                        }
                        if (! empty($item->goodsOption->optionsText)) {
                            $optionsAll = array_merge($optionsAll, $item->goodsOption->optionsText);
                        }
                        if (! empty($item->goodsOption->optionsRef)) {
                            $optionsAll = array_merge($optionsAll, $item->goodsOption->optionsRef);
                        }
                        $options = Array();
                        $optionsExt = Array();
                        $optionsText = Array();
                        $optionsRef = Array();
                        foreach ($optionsAll as $opt) {
                            $optionType = '';
                            $optionInfo = null;
                            $optionPrice = 0;
                            if (! empty($opt->id) && isset($optionMap[$opt->id])) {
                                list ($optionType, $optionInfo, $optionPrice) = $optionMap[$opt->id];
                            } else if (! empty($opt->value)) {
                                $optionKeyword = $opt->value;
                                $matches = Array();
                                if (preg_match('#(.+)\((.*)\)#', $optionKeyword, $matches)) {
                                    $optionKeyword = $matches[1];
                                }
                                $optionKeyword = trim($optionKeyword);
                                if (! empty($optionKeyword)) {
                                    $optionKeywordList = explode(" ", strtolower($optionKeyword));
                                    foreach ($optionMap as $key => $info) {
                                        $allChecked = true;
                                        foreach ($optionKeywordList as $txt) {
                                            if (! empty($txt) && strpos($key, $txt) === false) {
                                                $allChecked = false;
                                                break;
                                            }
                                        }
                                        if ($allChecked) {
                                            list ($optionType, $optionInfo, $optionPrice) = $info;
                                            break;
                                        }
                                    }
                                }
                            }
                            if (! empty($optionType) && ! empty($optionInfo)) {
                                $opt->id = $optionInfo->id;
                                $opt->optionPrice = $optionPrice;
                                $opt->optionScmPrice = $optionInfo->optionScmPrice;
                                $opt->optionCnt = max(1, $opt->optionCnt);
                                $opt->optionSumPrice = $opt->optionCnt * $opt->optionPrice;
                                $opt->optionSumScmPrice = $opt->optionCnt * $opt->optionScmPrice;
                                switch ($optionType) {
                                    case 'OPT':
                                        $options[] = $opt;
                                        break;
                                    case 'EXT':
                                        $optionsExt[] = $opt;
                                        break;
                                    case 'TEXT':
                                        $optionsText[] = $opt;
                                        break;
                                    case 'REF':
                                        $optionsRef[] = $opt;
                                        break;
                                }
                            }
                        }
                        $item->goodsOption->options = $options;
                        $item->goodsOption->optionsExt = $optionsExt;
                        $item->goodsOption->optionsText = $optionsText;
                        $item->goodsOption->optionsRef = $optionsRef;
                    }
                }
                $policyService->GetDeliveryConfigGoodsCart($goodsInfo, $item, $loginInfoVo);
                $item->goodsImageMaster = $goodsInfo->goodsImageMaster;
                $item->goodsNm = $goodsInfo->goodsNm;
                $item->goodsNmLocale = $goodsInfo->goodsNmLocale;
                $item->goodsPrice = $goodsInfo->goodsPrice;
                if (! empty($item->goodsOption)) {
                    $options = $item->goodsOption->options;
                    $optionsExt = $item->goodsOption->optionsExt;
                    $optionsText = $item->goodsOption->optionsText;
                    $optionsRef = $item->goodsOption->optionsRef;
                    if ((count($options) + count($optionsExt) + count($optionsText) + count($optionsRef)) > 0) {
                        $item->goodsHasOption = 'Y';
                    }
                }
                $item->deliveryNation = $goodsInfo->deliveryNation;
                $item->deliveryPackingRule = $goodsInfo->deliveryPackingRule;
                $item->deliveryPackingPrice = $goodsInfo->deliveryPackingPrice;
                if (! empty($goodsInfo->sellerMemNo) && $goodsInfo->deliveryFl == 'U' && ! empty($goodsInfo->deliveryOption)) {
                    $deliveryOptionKey = $goodsInfo->sellerMemNo . '_' . $goodsInfo->deliveryOption;
                    if (! isset($goodsSumByScmDelivery[$deliveryOptionKey])) {
                        $goodsSumByScmDelivery[$deliveryOptionKey] = Array();
                    }
                    $goodsSumByScmDelivery[$deliveryOptionKey][] = $item;
                } else {
                    $deliveryOptionKey = 'GLOBAL';
                    if (! isset($goodsSumByScmDelivery[$deliveryOptionKey])) {
                        $goodsSumByScmDelivery[$deliveryOptionKey] = Array();
                    }
                    $goodsSumByScmDelivery[$deliveryOptionKey][] = $item;
                }
            }
            foreach ($goodsSumByScmDelivery as $deliveryOptionKey => $itemList) {
                $matches = Array();
                $deliveyGoodsInfo = new GoodsInfoVo();
                $packingGoodsInfo = new GoodsInfoVo();
                if (preg_match("#^([0-9]+)_(.+)#", $deliveryOptionKey, $matches)) {
                    $deliveyGoodsInfo->deliveryFl = 'U';
                    $deliveyGoodsInfo->deliveryOption = $matches[2];
                    $packingGoodsInfo->deliveryFl = 'U';
                    $packingGoodsInfo->deliveryOption = $matches[2];
                } else {
                    $deliveyGoodsInfo->deliveryFl = 'G';
                    $deliveyGoodsInfo->deliveryOption = '';
                    $packingGoodsInfo->deliveryFl = 'G';
                    $packingGoodsInfo->deliveryOption = '';
                }
                $deliveyCartSum = new GoodsCartVo();
                $packingCartSum = new GoodsCartVo();
                $deliveyFirstCart = null;
                $packingFirstCart = null;
                foreach ($itemList as $item) {
                    if (empty($deliveyFirstCart)) {
                        $deliveyGoodsInfo->goodsCode = $item->goodsCode;
                        $packingGoodsInfo->goodsCode = $item->goodsCode;
                        if ($item->goodsDeliveryType != 'L') {
                            $deliveyFirstCart = $item;
                        }
                    }
                    $item->goodsSumDeliveryPrice = 0;
                    $item->goodsDeliveryPrice = 0;
                    $deliveyCartSum->goodsWeight += $item->goodsWeight;
                    $deliveyCartSum->goodsPrice += $item->goodsPrice;
                    $deliveyCartSum->goodsSumPrice += $item->goodsSumPrice;
                    $deliveyCartSum->goodsCnt += $item->goodsCnt;
                    $deliveyCartSum->goodsCntPrice += $item->goodsPrice * $item->goodsCnt;
                    $deliveyCartSum->goodsCntWeight += $item->goodsCntWeight;
                    $deliveyCartSum->goodsAddPrice += $item->goodsAddPrice;
                    $deliveyCartSum->goodsOptionPrice += $item->goodsOptionPrice;
                    $deliveyCartSum->goodsExtPrice += $item->goodsExtPrice;
                    $deliveyCartSum->goodsTextPrice += $item->goodsTextPrice;
                    $deliveyCartSum->goodsRefPrice += $item->goodsRefPrice;
                    $deliveyCartSum->goodsOptionCnt += $item->goodsOptionCnt;
                    $deliveyCartSum->goodsTextCnt += $item->goodsTextCnt;
                    $deliveyCartSum->goodsExtCnt += $item->goodsExtCnt;
                    $deliveyCartSum->goodsRefCnt += $item->goodsRefCnt;
                    if (! empty($item->goodsInfo) && $item->goodsInfo->deliveryPackingRule == 'O') {
                        $item->goodsPackingPrice = 0;
                        if (empty($packingFirstCart)) {
                            $packingFirstCart = $item;
                        }
                        $packingCartSum->goodsWeight += $item->goodsWeight;
                        $packingCartSum->goodsPrice += $item->goodsPrice;
                        $packingCartSum->goodsSumPrice += $item->goodsSumPrice;
                        $packingCartSum->goodsCnt += $item->goodsCnt;
                        $packingCartSum->goodsCntPrice += $item->goodsPrice * $item->goodsCnt;
                        $packingCartSum->goodsCntWeight += $item->goodsCntWeight;
                        $packingCartSum->goodsAddPrice += $item->goodsAddPrice;
                        $packingCartSum->goodsOptionPrice += $item->goodsOptionPrice;
                        $packingCartSum->goodsExtPrice += $item->goodsExtPrice;
                        $packingCartSum->goodsTextPrice += $item->goodsTextPrice;
                        $packingCartSum->goodsRefPrice += $item->goodsRefPrice;
                        $packingCartSum->goodsOptionCnt += $item->goodsOptionCnt;
                        $packingCartSum->goodsTextCnt += $item->goodsTextCnt;
                        $packingCartSum->goodsExtCnt += $item->goodsExtCnt;
                        $packingCartSum->goodsRefCnt += $item->goodsRefCnt;
                        $tmpGoodsInfo = new GoodsInfoVo();
                        $tmpGoodsInfo->deliveryPackingRule = $item->goodsInfo->deliveryPackingRule;
                        $tmpGoodsInfo->deliveryPackingPrice = $item->goodsInfo->deliveryPackingPrice;
                        $policyService->GetDeliveryPriceRuleMerge($packingGoodsInfo, $tmpGoodsInfo, $item->goodsSumPrice, $item->goodsCnt);
                    }
                }
                if (! empty($deliveyFirstCart)) {
                    $policyService->GetDeliveryConfigGoodsCart($deliveyGoodsInfo, $deliveyCartSum, $loginInfoVo, $deliveyGoodsInfo->deliveryFl == 'G');
                    $deliveyFirstCart->goodsDeliveryPrice = $deliveyCartSum->goodsDeliveryPrice;
                }
                if (! empty($packingFirstCart)) {
                    $packingGoodsInfo->deliveryPackingRule = 'Y';
                    $policyService->GetDeliveryConfigGoodsCart($packingGoodsInfo, $packingCartSum, $loginInfoVo);
                    $packingFirstCart->goodsPackingPrice = $packingCartSum->goodsPackingPrice;
                }
            }
            foreach ($cartItems as $item) {
                $item->goodsDeliveryPrice += $item->goodsPackingPrice;
                $this->CheckGoodsCartItem($item);
                if (! $item->isDeliverable) {
                    $globalCartItem->isDeliverable = false;
                }
                if ($item->useDeliver != 'Y') {
                    $globalCartItem->goodsSumDiscountPrice += $item->goodsSumDiscountPrice;
                    $globalCartItem->goodsOptionPrice += $item->goodsOptionPrice;
                    $globalCartItem->goodsExtPrice += $item->goodsExtPrice;
                    $globalCartItem->goodsTextPrice += $item->goodsTextPrice;
                    $globalCartItem->goodsRefPrice += $item->goodsRefPrice;
                    $globalCartItem->goodsCntPrice += $item->goodsCntPrice;
                    $globalCartItem->goodsAddPrice += $item->goodsAddPrice;
                    $globalCartItem->goodsDeliveryPrice += $item->goodsDeliveryPrice;
                    $globalCartItem->goodsSumDeliveryPrice += $item->goodsSumDeliveryPrice;
                    $globalCartItem->goodsDeliveryRawPrice += $item->goodsDeliveryRawPrice;
                    $globalCartItem->goodsSumPrice += $item->goodsSumPrice;
                    $globalCartItem->goodsViewSumPrice += $item->goodsViewSumPrice;
                    $globalCartItem->goodsSumMileage += $item->goodsSumMileage;
                    $globalCartItem->goodsPackingPrice += $item->goodsPackingPrice;
                    $globalCartItem->sumPrice += $item->sumPrice;
                    if ($item->goodsInfo->deliveryPackingRule != 'Y') {
                        $item->goodsPackingPrice = 0;
                    } else {
                        $globalCartItem->goodsPackingPrice -= $item->goodsPackingPrice;
                        $globalCartItem->goodsSumDeliveryPrice -= $item->goodsPackingPrice;
                        $globalCartItem->goodsDeliveryRawPrice -= $item->goodsPackingPrice;
                        $globalCartItem->goodsDeliveryPrice -= $item->goodsPackingPrice;
                    }
                    $item->goodsDeliveryPrice = $item->goodsPackingPrice;
                    $item->goodsDeliveryRawPrice = 0;
                    $item->goodsSumDeliveryPrice = 0;
                    $this->CheckGoodsCartItem($item);
                }
            }
        }
        $this->CheckGoodsCartItem($globalCartItem);
        return $globalCartItem;
    }

    /**
     * 상품 카트 상품 페이징 가져오기
     *
     * @param LoginInfoVo $loginInfoVo
     * @param RequestVo $request
     * @return \Vo\PagingVo
     */
    public function GetGoodsCartPaging(LoginInfoVo $loginInfoVo, RequestVo $request)
    {
        $vo = new GoodsCartSearchVo();
        $this->GetSearchVo($request, $vo);
        $vo->cartId = $loginInfoVo->sharedToken;
        $voList = $this->GetGoodsCartDao()->GetPaging($vo, $request->GetPerPage(10), $request->GetOffset());
        try {
            $globalCartItem = $this->CheckGoodsStockPrice($voList->items, $loginInfoVo);
            foreach ($voList->items as $item) {
                if ($item->useDeliver != 'Y') {
                    $item->goodsDeliveryPrice += $globalCartItem->goodsDeliveryPrice;
                    $item->goodsSumDeliveryPrice += $globalCartItem->goodsSumDeliveryPrice;
                    $item->goodsDeliveryRawPrice += $globalCartItem->goodsDeliveryRawPrice;
                    $item->goodsPackingPrice += $globalCartItem->goodsPackingPrice;
                    $item->sumPrice += $globalCartItem->sumPrice;
                    break;
                }
            }
            return $voList;
        } catch (\Exception $ex) {
            $this->GetGoodsCartDeleteAll($loginInfoVo);
            return new PagingVo();
        }
    }

    /**
     * 상품 옵션 아이템 가져오기
     *
     * @param RequestVo $request
     * @return GoodsCartVo[]
     */
    public function GetGoodsCartPriceItemList(RequestVo $request, $itemType = "G")
    {
        $items = Array();

        if ($request->hasKey('items')) {
            
            $reqItems = $request->items;
            if (is_array($reqItems)) {
                
                foreach ($reqItems as $item) {
                    $vo = new GoodsCartVo();
                    $vo->goodsOption = new GoodsCartOptionVo();
                    $vo->goodsOption->options = Array();
                    $vo->goodsOption->optionsExt = Array();
                    $vo->goodsOption->optionsText = Array();
                    $vo->goodsOption->optionsRef = Array();

                    $vo->goodsOption->optionsRef1 = "11111";
                    
                    $vo->goodsType = $itemType;
                    $vo->goodsCouponVo = new MemberCouponVo();
                    $itemReq = new RequestVo($item, false);
                    if ($itemReq->hasKey('goodsOption')) {
                        $goodsOption = $itemReq->GetRequestVo('goodsOption');
                        if ($goodsOption->hasKey('options')) {
                            $vo->goodsOption->options = $goodsOption->GetItemArray('options', new GoodsCartOptionItemVo());
                        }
                        if ($goodsOption->hasKey('optionsExt')) {
                            $vo->goodsOption->optionsExt = $goodsOption->GetItemArray('optionsExt', new GoodsCartOptionItemVo());
                        }
                        if ($goodsOption->hasKey('optionsText')) {
                            $vo->goodsOption->optionsText = $goodsOption->GetItemArray('optionsText', new GoodsCartOptionItemVo());
                        }
                        if ($goodsOption->hasKey('optionsRef')) {
                            $vo->goodsOption->optionsRef = $goodsOption->GetItemArray('optionsRef', new GoodsCartOptionItemVo());
                        }
                    }
                    if (empty($vo->goodsCoupon)) {
                        $vo->goodsCouponVo = null;
                    }
                    $items[] = $itemReq->GetFill($vo);
                }
            }
        }
        
        return $items;
    }

    /**
     * 상품 옵션 맵 가져오기
     *
     * @param OptionTreeVo[] $optionList
     * @return OptionTreeVo[]
     */
    public function GetGoodsInfoOptionMap($optionList = Array())
    {
        $hashOptionList = Array();
        foreach ($optionList as $item) {
            $item->valueList = Array();
            $item->valueLocaleList = Array();
            $item->valueList[] = $item->value;

            if (empty($item->valueLocale)) {
                $item->valueLocale = new LocaleTextVo();
                $item->valueLocale->ko = $item->value;
                $item->valueLocale->en = $item->value;
                $item->valueLocale->cn = $item->value;
                $item->valueLocale->jp = $item->value;
            }
            $item->valueLocaleList[] = $item->valueLocale;
            if ($item->isRequiredChild) {
                $childList = $this->GetGoodsInfoOptionMap($item->optionChildren);
                foreach ($childList as $key => $child) {
                    $child->valueList[] = $item->value;
                    $child->valueLocaleList[] = clone $item->valueLocale;
                    $child->optionPrice += $item->optionPrice;
                    $child->optionScmPrice += $item->optionScmPrice;
                    $hashOptionList[$key] = $child;
                }
            } else {
                $key = $item->id;
                $hashOptionList[$key] = $item;
            }
        }
        return $hashOptionList;
    }

    /**
     * 상품 카트 상품 가져오기
     *
     * @param GoodsCartPriceVo $vo
     * @return GoodsCartPriceVo
     */
    public function CheckGoodsStock(GoodsCartPriceVo $vo)
    {
        /**
         *
         * @var GoodsInfoVo[] $errorGoodsInfo
         */
        $errorGoodsInfo = Array();
        foreach ($vo->items as $item) {
            try {
                $goodsInfo = $this->GetGoodsInfoView($item->goodsCode, $vo->loginInfoVo);
                if (! $goodsInfo->goodsHasStock) {
                    $errorGoodsInfo[] = $goodsInfo;
                } else if ($goodsInfo->stockFl == 'Y') {}
            } catch (\Exception $ex) {
                $errorGoods = new GoodsInfoVo();
                $errorGoods->goodsNm = "Unknown Goods";
                $errorGoods->goodsCode = $item->goodsCode;
                $errorGoodsInfo[] = $errorGoods;
            }
        }

        if (count($errorGoodsInfo)) {
            $errorMsg = Array();
            foreach ($errorGoodsInfo as $goodsInfo) {
                $errorMsg[] = $goodsInfo->goodsNm;
            }
            $this->GetException(KbmException::DATA_ERROR_STOCK, implode("\n", $errorMsg));
        }
    }

    /**
     * 상품 카트 상품 가져오기
     *
     * @param GoodsCartVo $item
     * @param LoginInfoVo $loginInfoVo
     * @return GoodsCartVo
     */
    public function GetGoodsCartPriceVo(GoodsCartVo $item, LoginInfoVo $loginInfoVo = null, GoodsInfoVo $goodsInfo = null)
    {
        $goodsOptionPrice = 0;
        $goodsTextPrice = 0;
        $goodsExtPrice = 0;
        $goodsRefPrice = 0;
        $goodsTextCnt = 0;
        $goodsExtCnt = 0;
        $goodsRefCnt = 0;
        $goodsOptionScmPrice = 0;
        $goodsTextScmPrice = 0;
        $goodsExtScmPrice = 0;
        $goodsRefScmPrice = 0;
        $goodsCnt = 0;

        if (! empty($item->goodsOption)) {
            if (! empty($item->goodsOption->options)) {
                $goodsCnt = 0;
                foreach ($item->goodsOption->options as $options) {
                    $options->optionSumPrice = $options->optionCnt * $options->optionPrice;
                    $options->optionSumScmPrice = $options->optionCnt * $options->optionScmPrice;
                    $goodsOptionPrice += $options->optionSumPrice;
                    $goodsOptionScmPrice += $options->optionSumScmPrice;
                    $goodsCnt += $options->optionCnt;
                }
            } else {
                $goodsCnt = $item->goodsCnt;
            }
            foreach ($item->goodsOption->optionsExt as $options) {
                $options->optionSumPrice = $options->optionCnt * $options->optionPrice;
                $options->optionSumScmPrice = $options->optionCnt * $options->optionScmPrice;
                $goodsExtPrice += $options->optionSumPrice;
                $goodsExtScmPrice += $options->optionSumScmPrice;
                $goodsExtCnt += $options->optionCnt;
            }
            foreach ($item->goodsOption->optionsText as $options) {
                $options->optionSumPrice = $options->optionCnt * $options->optionPrice;
                $options->optionSumScmPrice = $options->optionCnt * $options->optionScmPrice;
                $goodsTextPrice += $options->optionSumPrice;
                $goodsTextScmPrice += $options->optionSumScmPrice;
                $goodsTextCnt += $options->optionCnt;
            }
            foreach ($item->goodsOption->optionsRef as $options) {
                $options->optionSumPrice = $options->optionCnt * $options->optionPrice;
                $options->optionSumScmPrice = $options->optionCnt * $options->optionScmPrice;
                $goodsRefPrice += $options->optionSumPrice;
                $goodsRefScmPrice += $options->optionSumScmPrice;
                $goodsRefCnt += $options->optionCnt;
            }
        } else {
            $goodsCnt = $item->goodsCnt;
        }
        if (! empty($item->goodsOption)) {
            $options = $item->goodsOption->options;
            $optionsExt = $item->goodsOption->optionsExt;
            $optionsText = $item->goodsOption->optionsText;
            $optionsRef = $item->goodsOption->optionsRef;
            if ((count($options) + count($optionsExt) + count($optionsText) + count($optionsRef)) > 0) {
                $item->goodsHasOption = 'Y';
            }
        }
        $item->goodsOptionPrice = $goodsOptionPrice;
        $item->goodsTextPrice = $goodsTextPrice;
        $item->goodsExtPrice = $goodsExtPrice;
        $item->goodsRefPrice = $goodsRefPrice;
        $item->goodsTextCnt = $goodsTextCnt;
        $item->goodsExtCnt = $goodsExtCnt;
        $item->goodsRefCnt = $goodsRefCnt;
        $item->goodsCnt = $goodsCnt;
        $item->goodsCntPrice = $item->goodsPrice * $item->goodsCnt;
        $item->goodsCntWeight = $item->goodsWeight * $item->goodsCnt;
        $item->goodsSumPrice = $item->goodsCntPrice + $item->goodsAddPrice;
        $item->goodsOptionScmPrice = $goodsOptionScmPrice;
        $item->goodsTextScmPrice = $goodsTextScmPrice;
        $item->goodsExtScmPrice = $goodsExtScmPrice;
        $item->goodsRefScmPrice = $goodsRefScmPrice;
        $item->goodsSumDeliveryPrice = $item->goodsDeliveryPrice - $item->goodsDiscountDeliveryPrice;
        $item->goodsAddPrice = $item->goodsOptionPrice + $item->goodsExtPrice + $item->goodsTextPrice + $item->goodsRefPrice;
        $item->goodsSumPrice = $item->goodsCntPrice + $item->goodsAddPrice;
        $item->sumPrice = $item->goodsSumPrice - $item->goodsDiscountPrice + $item->goodsSumDeliveryPrice;
        $item->goodsViewSumPrice = $item->goodsSumPrice - $item->goodsDiscountPrice;
        list ($item->goodsViewSumPrice, $item->goodsTaxSupplyPrice, $item->goodsTaxPrice, $item->goodsTaxServicePrice) = $this->GetTaxPrice($item->goodsViewSumPrice, $item->taxPercent);
        list ($item->goodsSumDeliveryPrice, $item->taxDeliverySupplyPrice, $item->taxDeliveryPrice, $item->taxDeliveryServicePrice) = $this->GetTaxPrice($item->goodsSumDeliveryPrice, $item->taxDeliveryPercent);
        $item->taxPrice = $item->goodsTaxPrice + $item->taxDeliveryPrice;
        $item->taxSupplyPrice = $item->goodsTaxSupplyPrice + $item->taxDeliverySupplyPrice;
        $item->taxServicePrice = $item->goodsTaxServicePrice + $item->taxDeliveryServicePrice;
        if (! empty($item->scmNo)) {
            $item->goodsCntScmPrice = $item->goodsScmPrice * $item->goodsCnt;
            $item->goodsAddScmPrice = $item->goodsOptionScmPrice + $item->goodsExtScmPrice + $item->goodsTextPrice + $item->goodsRefScmPrice;
            $item->goodsSumScmPrice = $item->goodsCntScmPrice + $item->goodsAddScmPrice;
            $item->sumScmPrice = $item->goodsSumScmPrice + $item->goodsSumDeliveryPrice;
            list ($item->goodsSumScmPrice, $item->goodsTaxScmSupplyPrice, $item->goodsTaxScmPrice, $item->goodsTaxScmServicePrice) = $this->GetTaxPrice($item->goodsSumScmPrice, $item->taxPercent);
            list ($item->goodsDeliveryPrice, $item->taxDeliveryScmSupplyPrice, $item->taxDeliveryScmPrice, $item->taxDeliveryScmServicePrice) = $this->GetTaxPrice($item->goodsDeliveryPrice, $item->taxDeliveryPercent);
            $item->taxScmPrice = $item->goodsTaxScmPrice + $item->taxDeliveryScmPrice;
            $item->taxScmSupplyPrice = $item->goodsTaxScmSupplyPrice + $item->taxDeliveryScmSupplyPrice;
            $item->taxScmServicePrice = $item->goodsTaxScmServicePrice + $item->taxDeliveryScmServicePrice;
            $item->scmCommission = max(0, min(100, $item->scmCommission));
        } else {
            $item->goodsOptionScmPrice = 0;
            $item->goodsTextScmPrice = 0;
            $item->goodsExtScmPrice = 0;
            $item->goodsRefScmPrice = 0;
            $item->goodsCntScmPrice = 0;
            $item->goodsAddScmPrice = 0;
            $item->goodsSumScmPrice = 0;
            $item->sumScmPrice = 0;
            $item->goodsTaxScmPrice = 0;
            $item->goodsTaxScmSupplyPrice = 0;
            $item->goodsTaxScmServicePrice = 0;
            $item->taxDeliveryScmPrice = 0;
            $item->taxDeliveryScmSupplyPrice = 0;
            $item->taxDeliveryScmServicePrice = 0;
            $item->taxScmPrice = 0;
            $item->taxScmSupplyPrice = 0;
            $item->taxScmServicePrice = 0;
            $item->scmCommission = 0;
        }
        if (! empty($goodsInfo)) {
            $item->useDeliver = $goodsInfo->deliveryFl == 'G' ? 'N' : 'Y';
        }
        if ($item->useDeliver == 'Y') {
            if (empty($item->deliveryInfo) || ! ($item->deliveryInfo instanceof GoodsCartDeliveryInfoVo)) {
                $item->deliveryInfo = new GoodsCartDeliveryInfoVo();
            }
        }
        return $item;
    }

    /**
     * 상품 카트 상품 가져오기
     *
     * @param GoodsCartVo $item
     * @param LoginInfoVo $loginInfoVo
     * @return GoodsCartVo
     */
    public function GetOptionValueLocale(OptionTreeVo $option, $optionTextList = Array())
    {
        $value = $option->value;
        $valueLocale = new LocaleTextVo();
        if (! empty($option->valueLocale)) {
            if (empty($option->valueLocale->ko)) {
                $valueLocale->ko = $option->valueLocale->ko;
            }
            if (empty($option->valueLocale->en)) {
                $valueLocale->en = $option->valueLocale->en;
            }
            if (empty($option->valueLocale->cn)) {
                $valueLocale->cn = $option->valueLocale->cn;
            }
            if (empty($option->valueLocale->jp)) {
                $valueLocale->jp = $option->valueLocale->jp;
            }
        }
        if (empty($valueLocale->ko)) {
            $valueLocale->ko = $value;
        }
        if (empty($valueLocale->en)) {
            $valueLocale->en = $value;
        }
        if (empty($valueLocale->cn)) {
            $valueLocale->cn = $value;
        }
        if (empty($valueLocale->jp)) {
            $valueLocale->jp = $value;
        }
        $defValue = Array();
        $defValueKr = Array();
        $defValueEn = Array();
        $defValueCn = Array();
        $defValueJp = Array();
        if (! empty($optionTextList)) {
            foreach ($option->valueList as $seqn => $txt) {
                $optionText = array_pop($optionTextList);
                if (! empty($optionText)) {
                    $txt = $txt . '(' . $optionText . ')';
                }
                $defValue[] = $txt;
                $defLocale = (isset($option->valueLocaleList[$seqn])) ? $option->valueLocaleList[$seqn] : new LocaleTextVo();
                if (! empty($optionText)) {
                    $defValueKr[] = empty($defLocale->kr) ? $txt : $defLocale->kr . '(' . $optionText . ')';
                    $defValueEn[] = empty($defLocale->en) ? $txt : $defLocale->en . '(' . $optionText . ')';
                    $defValueCn[] = empty($defLocale->cn) ? $txt : $defLocale->cn . '(' . $optionText . ')';
                    $defValueJp[] = empty($defLocale->jp) ? $txt : $defLocale->jp . '(' . $optionText . ')';
                } else {
                    $defValueKr[] = empty($defLocale->kr) ? $txt : $defLocale->kr;
                    $defValueEn[] = empty($defLocale->en) ? $txt : $defLocale->en;
                    $defValueCn[] = empty($defLocale->cn) ? $txt : $defLocale->cn;
                    $defValueJp[] = empty($defLocale->jp) ? $txt : $defLocale->jp;
                }
            }
        } else {
            foreach ($option->valueList as $seqn => $txt) {
                $defValue[] = $txt;
                $defLocale = (isset($option->valueLocaleList[$seqn])) ? $option->valueLocaleList[$seqn] : new LocaleTextVo();
                $defValueKr[] = empty($defLocale->kr) ? $txt : $defLocale->kr;
                $defValueEn[] = empty($defLocale->en) ? $txt : $defLocale->en;
                $defValueCn[] = empty($defLocale->cn) ? $txt : $defLocale->cn;
                $defValueJp[] = empty($defLocale->jp) ? $txt : $defLocale->jp;
            }
        }
        ;
        $value = implode(' / ', array_reverse($defValue));
        $valueLocale->ko = implode(' / ', array_reverse($defValueKr));
        $valueLocale->en = implode(' / ', array_reverse($defValueEn));
        $valueLocale->cn = implode(' / ', array_reverse($defValueCn));
        $valueLocale->jp = implode(' / ', array_reverse($defValueJp));
        return Array(
            $value,
            parent::GetLocaleTextVo($valueLocale)
        );
    }

    /**
     * 상품 카트 상품 가져오기
     *
     * @param GoodsCartVo $item
     * @param LoginInfoVo $loginInfoVo
     * @return GoodsCartVo
     */
    public function GetGoodsCartVo(GoodsCartVo $item, LoginInfoVo $loginInfoVo = null, $preDeliveryPay = true)
    {
        $goodsCode = $item->goodsCode;
        $goodsType = $item->goodsType;
                
        if (! empty($goodsCode)) {

            try {

                switch ($goodsType) {
                    case 'A':
                        $goodsInfo = $this->GetAuctionInfoView($item->auctionCode, $loginInfoVo);
                        $goodsPrice = $item->auctionPrice;
                        break;
                    default:
                        $goodsInfo = $this->GetGoodsInfoView($goodsCode, $loginInfoVo);
                        $goodsPrice = $goodsInfo->goodsPrice;
                        break;
                }

                $goodsScmPrice = $goodsInfo->goodsScmPrice;
                $goodsCnt = $item->goodsCnt;
                if (empty($goodsCnt) || $goodsCnt < 1) {
                    $goodsCnt = 1;
                }
                
                $item->mallId = $this->mallId;
                $item->goodsPrice = $goodsPrice;
                $item->goodsScmPrice = $goodsScmPrice;
                $item->goodsNm = $goodsInfo->goodsNm;
                $item->goodsNmLocale = $goodsInfo->goodsNmLocale;
                $item->goodsImageMaster = $goodsInfo->goodsImageMaster;
                                            
                if ($goodsInfo->optionFl == 'Y' && count($goodsInfo->optionsTree) > 0) {

                    $optionMap = $this->GetGoodsInfoOptionMap($goodsInfo->optionsTree);
                
                    $newOptionsList = Array();
                    // $item->goodsCnt = 0; // 20210203 결제시 결제가 되었다 안되었다 하는 문제 수정가 발생해서 이부분 주석해재 함 
                    $optionCartMap = Array();
                                    
                    if ($goodsInfo->optionDisplayFl == 'M') {
                        
                        foreach ($item->goodsOption->options as $options) {
                            $key = $options->id;
                            $options->value = '';
                            $options->valueLocale = '';
                            $options->optionPrice = 0;
                            $options->optionScmPrice = 0;
                            $options->optionCode = '';
                            $options->optionImage = '';
                            $optionsIds = explode(",", $key);
                            $optionValues = Array();
                            $optionCode = Array();
                            
                            foreach ($optionsIds as $txt) {
                                list (, $optionkey) = explode("#", $txt . '#');
                                if (! empty($optionkey) && isset($optionMap[$optionkey])) {
                                    $goodsOption = $optionMap[$optionkey];
                                    $options->optionPrice += $goodsOption->optionPrice;
                                    $options->optionScmPrice += $goodsOption->optionScmPrice;
                                    $optionCode[] = $goodsOption->optionCode;
                                    $optionValues[] = $goodsOption->value;
                                }
                            }
                            
                            $options->optionCode = implode(", ", $optionCode);
                            $options->value = implode(", ", $optionValues);
                            $newOptionsList[] = $options;
                            
                        }
                        
                    }
                    else{
                        
                        foreach ($item->goodsOption->options as $options) {
                            $key = $options->id;
                            
                            if (isset($optionMap[$key])) {
                                
                                $goodsOption = $optionMap[$key];
                                list ($options->value, $options->valueLocale) = $this->GetOptionValueLocale($goodsOption, $options->optionTextList);
                                $options->optionPrice = $goodsOption->optionPrice;
                                $options->optionScmPrice = $goodsOption->optionScmPrice;
                                $options->optionCode = $goodsOption->optionCode;
                                $options->optionImage = $goodsOption->optionImage;
                                $cartKey = md5($options->id . '-' . implode('-', $options->optionTextList));
                                
                                if (! isset($optionCartMap[$cartKey])) {
                                    $optionCartMap[$cartKey] = $options;
                                    $newOptionsList[] = $options;
                                }
                                
                            }
                        }
                        
                    }
                    $item->goodsOption->options = $newOptionsList;
                }
                else{
                    $item->goodsOption->options = Array();
                }

                if ($goodsInfo->optionsExtFl == 'Y' && count($goodsInfo->optionsExt) > 0) {
                    
                    $optionMap = $this->GetGoodsInfoOptionMap($goodsInfo->optionsExtTree);
                    $newOptionsList = Array();
                    $optionCartMap = Array();
                    
                    foreach ($item->goodsOption->optionsExt as $options) {
                        $key = $options->id;
                        if (isset($optionMap[$key])) {
                            
                            $goodsOption = $optionMap[$key];
                            list ($options->value, $options->valueLocale) = $this->GetOptionValueLocale($goodsOption, $options->optionTextList);
                            $options->optionPrice = $goodsOption->optionPrice;
                            $options->optionScmPrice = $goodsOption->optionScmPrice;
                            $options->optionCode = $goodsOption->optionCode;
                            $options->optionImage = $goodsOption->optionImage;
                            $cartKey = md5($options->id . '-' . implode('-', $options->optionTextList));
                            if (! isset($optionCartMap[$cartKey])) {
                                $optionCartMap[$cartKey] = $options;
                                $newOptionsList[] = $options;
                            }
                        }
                    }
                    
                    $item->goodsOption->optionsExt = $newOptionsList;
                    
                }
                else {
                    $item->goodsOption->optionsExt = Array();
                }
                               
                if ($goodsInfo->optionTextFl == 'Y') {
                    
                    $optionMap = $this->GetGoodsInfoOptionMap($goodsInfo->optionsTextTree);
                    $newOptionsList = Array();
                    $optionCartMap = Array();
                    
                    foreach ($item->goodsOption->optionsText as $options) {
                        $key = $options->id;
                        
                        if (isset($optionMap[$key])) {
                            
                            $goodsOption = $optionMap[$key];
                            list ($options->value, $options->valueLocale) = $this->GetOptionValueLocale($goodsOption, $options->optionTextList);
                            $options->optionPrice = $goodsOption->optionPrice;
                            $options->optionScmPrice = $goodsOption->optionScmPrice;
                            $options->optionCode = $goodsOption->optionCode;
                            $options->optionImage = $goodsOption->optionImage;
                            $cartKey = md5($options->id . '-' . implode('-', $options->optionTextList));
                            if (! isset($optionCartMap[$cartKey])) {
                                $optionCartMap[$cartKey] = $options;
                                $newOptionsList[] = $options;
                            }
                            
                        }
                    }
                    
                    $item->goodsOption->optionsText = $newOptionsList;
                    
                }
                else {
                    $item->goodsOption->optionsText = Array();
                }
                
                if ($goodsInfo->optionRefFl == 'Y') {
                    
                    $optionMap = $this->GetGoodsInfoOptionMap($goodsInfo->optionsRefTree);
                    $newOptionsList = Array();
                    $optionCartMap = Array();
                    
                    foreach ($item->goodsOption->optionsRef as $options) {
                        
                        $key = $options->id;
                        if (isset($optionMap[$key])) {
                            $goodsOption = $optionMap[$key];
                            list ($options->value, $options->valueLocale) = $this->GetOptionValueLocale($goodsOption, $options->optionTextList);
                            $options->optionPrice = $goodsOption->optionPrice;
                            $options->optionScmPrice = $goodsOption->optionScmPrice;
                            $options->optionCode = $goodsOption->optionCode;
                            $options->optionImage = $goodsOption->optionImage;
                            $cartKey = md5($options->id . '-' . implode('-', $options->optionTextList));
                            if (! isset($optionCartMap[$cartKey])) {
                                $optionCartMap[$cartKey] = $options;
                                $newOptionsList[] = $options;
                            }
                        }
                    }
                    $item->goodsOption->optionsRef = $newOptionsList;
                }
                else {
                    $item->goodsOption->optionsRef = Array();
                }
                
                
                if (! empty($goodsInfo->sellerMemNo)) {
                    if (empty($item->scmNo)) {
                        $item->scmNo = $goodsInfo->sellerMemNo;
                    }
                }
                
                if (! empty($item->scmNo)) {
                    if ($item->scmCommission < 0) {
                        if ($goodsInfo->useCommission == 'Y') {
                            $item->scmCommission = $goodsInfo->commission;
                        } else {
                            try {
                                $scmInfo = $this->GetServiceScm()->GetScmInfoView($item->scmNo);
                                $item->scmCommission = $scmInfo->scmCommission;
                            } catch (\Exception $ex) {
                                $item->scmCommission = 0;
                            }
                        }
                    }
                }
                else {
                    $item->scmCommission = 0;
                }
                
                $item->goodsDiscountPrice = 0;
                $item->taxPercent = $goodsInfo->taxPercent;
                $this->GetGoodsCartPriceVo($item, $loginInfoVo, $goodsInfo);
                $policyService = $this->GetServicePolicy();
                $policyService->GetDeliveryConfigGoodsCart($goodsInfo, $item, $loginInfoVo, false, $preDeliveryPay);
                $item->goodsDiscountPrice = 0;
                if (! empty($item->goodsCoupon)) {
                    $memberService = $this->GetServiceMember();
                    $memberService->GetMemberCouponApply($item->goodsCoupon, $loginInfoVo, $item, $goodsInfo);
                } else {
                    $item->goodsCoupon = '';
                    $item->goodsCouponVo = null;
                }

                if ($item->goodsCnt > 0) {
                    return $item;
                }
                
            } catch (Exception $ex) {
                // echo $ex;
            }
        
        }
        return null;
        
    }

    /**
     * 상품 카트 아이템 가져오기
     *
     * @param LoginInfoVo $loginInfoVo
     * @param RequestVo $request
     * @return GoodsCartPriceVo
     */
    public function GetGoodsCartPrice(LoginInfoVo $loginInfoVo, RequestVo $request)
    {
        $goodsCartPrice = new GoodsCartPriceVo();
        $goodsCartPrice->items = $this->GetGoodsCartPriceItemList($request, 'G');
        $checkedItems = Array();
        $addressVo = new AddressVo();
        if ($request->hasKey('address')) {
            $request->GetFill($addressVo, 'address');
            $loginInfoVo->address = $addressVo;
        } else if (! empty($loginInfoVo) && ! empty($loginInfoVo->address)) {
            $addressVo = clone $loginInfoVo->address;
        }
        $preDeliveryPay = true;
        if ($request->hasKey('deliveryType')) {
            switch ($request->deliveryType) {
                case 'N':
                    $preDeliveryPay = false;
                    break;
                case 'Y':
                default:
                    $preDeliveryPay = true;
                    break;
            }
        }
        foreach ($goodsCartPrice->items as $item) {
            $item = $this->GetGoodsCartVo($item, $loginInfoVo, $preDeliveryPay);
            if (! empty($item)) {
                $checkedItems[] = $item;
            }
        }
        $goodsCartPrice->items = $checkedItems;
        $goodsCartPrice->orderInfo = new GoodsCartOrderInfoVo();
        $goodsCartPrice->orderInfo->recvAddress = $addressVo;
        $selectedCurrency = $request->selectedCurrency;
        $payInfo = new GoodsCartPayInfoVo();
        if (! empty($selectedCurrency)) {
            $goodsCartPrice->selectedCurrency = $selectedCurrency;
        } else {
            $goodsCartPrice->selectedCurrency = MST_CURRENCY;
        }
        if ($request->hasKey('deliveryCoupon')) {
            $goodsCartPrice->deliveryCoupon = $request->GetFill(new MemberCouponVo(), 'deliveryCoupon');
        }
        if ($request->hasKey('orderCoupon')) {
            $goodsCartPrice->orderCoupon = $request->GetFill(new MemberCouponVo(), 'orderCoupon');
        }
        if ($request->hasKey('useMileage')) {
            $payInfo->useMileage = doubleval($request->useMileage);
        }
        $goodsCartPrice->payInfo = $payInfo;
        $orderService = $this->GetServiceOrder();
        $goodsCartPrice->loginInfoVo = $loginInfoVo;
        $goodsCartPrice->selectedPayType = $request->GetFill(new CodeVo(), 'selectedPayType');
        $policyService = $this->GetServicePolicy();
        $policyService->GetSettleSettlekindPgType($goodsCartPrice);
        $result = $orderService->GetOrderInfoOrderStatus($orderService->GetOrderInfoParse($goodsCartPrice), true, $preDeliveryPay, $request->checkStock == 'Y' ? true : false);
        $globalCartItem = $result->payInfo;
        foreach ($result->items as $item) {
            if ($item->useDeliver != 'Y') {
                $item->goodsDeliveryPrice = $globalCartItem->orderDeliveryPrice;
                $item->goodsDeliveryRawPrice = $globalCartItem->orderDeliveryRawPrice;
                $item->goodsDiscountDeliveryPrice = $globalCartItem->orderDeliveryDiscountPrice;
                $item->goodsPackingPrice = $item->goodsDeliveryPrice - $globalCartItem->orderDeliveryRawPrice;
                $item->goodsSumDeliveryPrice = $globalCartItem->orderSumDeliveryPrice;
                $item->sumPrice += $item->goodsDeliveryPrice - $globalCartItem->goodsDiscountPrice;
                break;
            }
        }
        return $result;
    }

    /**
     * 상품 카트 아이템 가져오기
     *
     * @param LoginInfoVo $loginInfoVo
     * @param RequestVo $request
     * @return GoodsCartPriceVo
     */
    public function GetGoodsCartPriceItem(LoginInfoVo $loginInfoVo = null, RequestVo $request)
    {
        $goodsCart = new GoodsCartVo();
        $goodsCart->goodsNmLocale = new LocaleTextVo();
        $goodsCart->deliveryInfo = new GoodsCartDeliveryInfoVo();
        $goodsCart->goodsOption = new GoodsCartOptionVo();
        $goodsCart->goodsOption->options = [];
        $goodsCart->goodsOption->optionsExt = [];
        $goodsCart->goodsOption->optionsRef = [];
        $goodsCart->goodsOption->optionsText = [];
        $request->GetFill($goodsCart);
        $goodsOption = $request->GetRequestVo('goodsOption');
        $goodsCart->goodsOption->options = $goodsOption->GetItemArray('options', new GoodsCartOptionItemVo());
        $goodsCart->goodsOption->optionsExt = $goodsOption->GetItemArray('optionsExt', new GoodsCartOptionItemVo());
        $goodsCart->goodsOption->optionsRef = $goodsOption->GetItemArray('optionsRef', new GoodsCartOptionItemVo());
        $goodsCart->goodsOption->optionsText = $goodsOption->GetItemArray('optionsText', new GoodsCartOptionItemVo());
        $this->GetGoodsCartPriceVo($goodsCart, $loginInfoVo);
        return $goodsCart;
    }

    /**
     * 상품 카트 결제정보 가져오기
     *
     * @param LoginInfoVo $loginInfoVo
     * @param RequestVo $request
     * @return GoodsCartPriceVo
     */
    public function GetGoodsCartPay(LoginInfoVo $loginInfoVo, RequestVo $request)
    {
        $goodsCartPrice = new GoodsCartPriceVo();
        $goodsCartPrice->loginInfoVo = $loginInfoVo;
        $goodsCartPrice->items = $this->GetGoodsCartPriceItemList($request, 'G');
        $preDeliveryPay = true;
        if ($request->hasKey('deliveryType')) {
            switch ($request->deliveryType) {
                case 'N':
                    $preDeliveryPay = false;
                    break;
                case 'Y':
                default:
                    $preDeliveryPay = true;
                    break;
            }
        }
                
        //LOGITEM 테스트 2021-02-03
        //$goodsCartPrice->log = $goodsCartPrice->items;
                
        $checkedItems = Array();
        foreach ($goodsCartPrice->items as $item) {
            $item = $this->GetGoodsCartVo($item, $loginInfoVo, $preDeliveryPay);
            if (!empty($item)) {
                $checkedItems[] = $item;
            }
        }
        $goodsCartPrice->items = $checkedItems;
        $goodsCartOrderInfoVo = new GoodsCartOrderInfoVo();
        $goodsCartOrderInfoVo->orderAddress = new AddressVo();
        $goodsCartOrderInfoVo->recvAddress = new AddressVo();
        $goodsCartPayInfoVo = new GoodsCartPayInfoVo();
        $selectedCurrency = $request->selectedCurrency;
        if (! empty($selectedCurrency)) {
            $goodsCartPrice->selectedCurrency = $selectedCurrency;
        } else {
            $goodsCartPrice->selectedCurrency = MST_CURRENCY;
        }
        $goodsCartPrice->payInfo = $request->GetFill($goodsCartPayInfoVo, "payInfo");
        $goodsCartPrice->orderInfo = $request->GetFill($goodsCartOrderInfoVo, "orderInfo");
        $goodsCartPrice->receiptInfo = $request->GetFill(new GoodsCartReceiptInfoVo(), 'receiptInfo');
        $goodsCartPrice->selectedPayType = $request->GetFill(new CodeVo(), 'selectedPayType');
        $goodsCartPrice->orderCoupon = $request->GetFill(new MemberCouponVo(), 'orderCoupon');
        $goodsCartPrice->deliveryCoupon = $request->GetFill(new MemberCouponVo(), 'deliveryCoupon');
                
        $orderService = $this->GetServiceOrder();
        $orderService->GetOrderInfoCreateByCart($goodsCartPrice, $preDeliveryPay);
        return $goodsCartPrice;
    }

    /**
     * 네이버 삽 카테고리 DAO 가져오기
     *
     * @return \Dao\NaverShopCategoryDao
     */
    public function GetNaverShopCategoryDao()
    {
        return parent::GetDao('NaverShopCategoryDao');
    }

    /**
     * 네이버 삽 카테고리 VO 가져오기
     *
     * @param string $uid
     * @param RequestVo $request
     * @param NaverShopCategoryVo $vo
     * @return NaverShopCategoryVo
     */
    public function GetNaverShopCategoryVo($uid = '', RequestVo $request = null, $vo = null)
    {
        $vo = empty($vo) ? new NaverShopCategoryVo() : $vo;
        parent::GetFill($request, $vo);
        $vo->cateId = $uid;
        return $vo;
    }

    /**
     * 네이버 삽 카테고리 캐시 삭제하기
     *
     * @param NaverShopCategoryVo $oldVo
     * @param NaverShopCategoryVo $newVo
     */
    private function UnsetNaverShopCategoryCache(NaverShopCategoryVo $oldVo, NaverShopCategoryVo $newVo)
    {
        if ($oldVo !== $newVo) {
            $log = $this->getAccessLog($oldVo, $newVo);
            $this->setAccessLog('vaverShopCategory.' . $oldVo->cateId, $log);
        }
    }

    /**
     * 네이버 삽 카테고리 페이징 가져오기
     *
     * @param RequestVo $request
     * @return \Vo\PagingVo
     */
    public function GetNaverShopCategoryPaging(RequestVo $request)
    {
        $vo = parent::GetSearchVo($request);
        return $this->GetNaverShopCategoryDao()->GetPaging($vo, $request->GetPerPage(10), $request->GetOffset());
    }

    /**
     * 네이버 삽 카테고리 목록 가져오기
     *
     * @param RequestVo $request
     * @return NaverShopCategoryVo[]
     */
    public function GetNaverShopCategoryList(RequestVo $request)
    {
        $vo = parent::GetSearchVo($request);
        return $this->GetNaverShopCategoryDao()->GetList($vo, $request->GetPerPage(10), $request->GetOffset());
    }

    /**
     * 네이버 삽 카테고리 정보 보기
     *
     * @param string $uid
     * @return NaverShopCategoryVo
     */
    public function GetNaverShopCategoryView($uid = '')
    {
        $vo = $this->GetNaverShopCategoryVo($uid);
        $result = $this->GetNaverShopCategoryDao()->GetView($vo);
        if (! empty($result)) {
            return $result;
        } else {
            $this->GetException(KbmException::DATA_ERROR_VIEW);
        }
    }

    /**
     * 네이버 삽 카테고리 생성하기
     *
     * @param RequestVo $request
     * @return NaverShopCategoryVo
     */
    public function GetNaverShopCategoryCreate(RequestVo $request = null)
    {
        $vo = $this->GetNaverShopCategoryVo($request->cateId, $request);
        $this->GetNaverShopCategoryParse($vo, $request, null);
        if ($this->GetNaverShopCategoryDao()->SetCreate($vo)) {
            $oldVo = new NaverShopCategoryVo();
            $oldVo->cateId = $vo->cateId;
            $this->UnsetNaverShopCategoryCache($oldVo, $vo);
            return $vo;
        } else {
            parent::GetException(KbmException::DATA_ERROR_CREATE);
        }
    }

    /**
     * 파일박스 관리
     */

    /**
     * 파일박스 검색 VO 가져오기
     *
     * @param RequestVo $request
     * @return FileLogSearchVo
     */
    public function GetFileLogSearchVo(RequestVo $request = null)
    {
        $vo = new FileLogSearchVo();
        $this->GetSearchVo($request, $vo);
        if ($this->IsScmAdmin()) {
            $vo->scmNo = $this->loginInfo->scmNo;
        }
        if (! empty($vo->query) && $vo->so == 'SIZE') {
            list ($fileSizeStart, $fileSizeEnd) = explode("~", $vo->query . "~");
            $vo->fileSizeStart = intval($fileSizeStart);
            $vo->fileSizeEnd = intval($fileSizeEnd);
            $vo->query = '';
        }
        return $vo;
    }

    /**
     * 파일 박스 페이지 가져오기
     *
     * @param RequestVo $request
     * @return \Vo\PagingVo
     */
    public function GetFileLogPaging(RequestVo $request = null)
    {
        $vo = $this->GetFileLogSearchVo($request);
        $result = $this->GetFileLogDao()->GetViewPaging($vo, $request->GetPerPage(10), $request->GetOffset());
        $this->GetFileLogListParse($result->items);
        return $result;
    }

    /**
     * 파일 박스 페이지 가져오기
     *
     * @param RequestVo $request
     * @return \Vo\PagingVo
     */
    public function GetFileLogStatus(RequestVo $request = null)
    {
        $vo = $this->GetFileLogSearchVo($request);
        return $this->GetFileLogDao()->GetViewStatus($vo);
    }

    /**
     * 파일 박스 페이지 가져오기
     *
     * @param RequestVo $request
     * @return \Vo\PagingVo
     */
    public function GetFileLogDownload(RequestVo $request = null, \AbstractKbmController $controller)
    {
        $fileList = $this->GetFileLogList($request);
        if (! empty($fileList)) {
            if (count($fileList) > 1) {
                $fileZipList = Array();
                foreach ($fileList as $fileItem) {
                    if (file_exists(FILE_DIR . '/' . $fileItem->fileServer)) {
                        $fileVo = new FileVo();
                        $fileVo->fileName = $fileItem->fileName;
                        $fileVo->fileSize = $fileItem->fileSize;
                        $fileVo->fileServer = $fileItem->fileServer;
                        $fileVo->fileType = $fileItem->fileType;
                        $fileZipList[] = $fileVo;
                    }
                }
                return $this->GetServiceCommon()->GetDownloadList($fileZipList, 'filebox', $controller);
            } else {
                foreach ($fileList as $fileItem) {
                    if (file_exists(FILE_DIR . '/' . $fileItem->fileServer)) {
                        return $this->GetServiceCommon()->GetDownload($fileItem->fileServer, $fileItem->fileType, $controller);
                    }
                }
            }
        } else {
            $this->GetException(KbmException::DATA_ERROR_AUTH);
        }
    }

    /**
     * 파일로그 샘플 Excel 파일 만들기
     *
     * @param number $size
     * @return \Vo\FileLogVo[]
     */
    public function GetFileLogSampleList($size = 10)
    {
        $excelData = Array();
        $timeNow = time();
        $sampleImage = explode("@!@", $this->GetGoodsInfoSampleImage($size, true));
        for ($i = 0; $i < $size; $i ++) {
            $vo = new FileLogVo();
            if (isset($sampleImage[$i])) {
                $fileInfo = $sampleImage[$i];
                list ($fileName, $fileSize, $fileType, $fileUrl) = explode('#', $fileInfo . '####');
                $vo->fileName = $fileName;
                $vo->fileSize = intval($fileSize);
                $vo->fileType = $fileType;
                $vo->fileServer = $fileUrl;
            }
            $vo->fileUid = md5($vo->fileServer);
            $vo->fileRefCnt = rand(0, 3);
            $vo->regDate = $this->getDateNow($timeNow - rand(5, 1000) * 60);
            $vo->modDate = $this->getDateNow($timeNow - rand(5, 1000) * 60);
            $excelData[] = $vo;
        }
        return $excelData;
    }

    /**
     * 파일 박스 페이지 가져오기
     *
     * @param FileLogVo[] $list
     * @return \Vo\FileLogVo[]
     */
    public function GetFileLogListParse($list = Array())
    {
        foreach ($list as $item) {
            $item->fileUid = md5($item->fileServer);
            switch ($item->fileType) {
                case 'image/jpg':
                case 'image/jpeg':
                case 'image/png':
                case 'image/gif':
                    $item->thumbImage = $item->fileName . '#' . $item->fileSize . '#' . $item->fileType . '#' . $item->fileServer;
                    break;
                default:
                    $item->thumbImage = '';
                    break;
            }
        }
        return $list;
    }

    /**
     * 파일 박스 페이지 목록 리스트 가져오기
     *
     * @param RequestVo $request
     * @return \Vo\FileLogVo[]
     */
    public function GetFileLogList(RequestVo $request = null)
    {
        $vo = $this->GetFileLogSearchVo($request);
        $result = $this->GetFileLogDao()->GetViewList($vo, $request->GetPerPage(10), $request->GetOffset());
        return $this->GetFileLogListParse($result);
    }

    /**
     * 네이버 삽 카테고리 캐시 삭제하기
     *
     * @param FileLogVo $oldVo
     * @param FileLogVo $newVo
     */
    private function UnsetFileLogCache(FileLogVo $oldVo, FileLogVo $newVo)
    {
        if ($oldVo !== $newVo) {
            $log = $this->getAccessLog($oldVo, $newVo);
            $this->setAccessLog('filelog.' . $oldVo->fileUid, $log);
        }
    }

    /**
     * 파일 박스 VO 가져오기
     *
     * @param RequestVo $request
     * @param FileLogVo $vo
     * @return FileLogVo
     */
    public function GetFileLogVo($uid = '', RequestVo $request = null, $vo = null)
    {
        $vo = parent::GetFill($request, empty($vo) ? 'FileLogVo' : $vo);
        if (! empty($uid)) {
            $vo->fileUid = $uid;
        }
        return $vo;
    }

    /**
     * 파일 박스 VO 파싱하기
     *
     * @param FileLogVo $vo
     * @param RequestVo $request
     * @param FileLogVo $oldView
     * @return FileLogVo
     */
    public function GetFileLogParse(FileLogVo $vo, RequestVo $request, FileLogVo $oldView = null)
    {
        return $vo;
    }

    /**
     * 파일 박스 생성하기
     *
     * @param RequestVo $request
     * @return \Vo\FileLogVo[]
     */
    public function GetFileLogCreate(RequestVo $request = null)
    {
        $vo = $this->GetFileLogVo('', $request);
        if ($this->IsScmAdmin()) {
            $vo->fileGroup = 'filebox-' . $this->loginInfo->scmNo;
        } else {
            $vo->fileGroup = 'filebox-admin';
        }
        $vo->fileStatus = 'Y';
        $vo->children = Array();
        $this->GetFileLogParse($vo, $request, null);
        $uploadFileList = Array();
        if ($request->hasKey("uploadFile")) {
            $uploadFileList = explode('@!@', $this->GetUploadFiles($request->uploadFile, '', 'filebox'));
        }
        foreach ($uploadFileList as $fileInfo) {
            list ($fileName, $fileSize, $fileType, $fileUrl) = explode('#', $fileInfo . '#####');
            $uploadVo = new FileVo();
            $uploadVo->mallId = $vo->mallId;
            $uploadVo->fileName = $fileName;
            $uploadVo->fileSize = intval($fileSize);
            $uploadVo->fileType = $fileType;
            $uploadVo->fileServer = $fileUrl;
            $vo->children[] = $uploadVo;
        }
        if (count($vo->children) > 0 && $this->GetFileLogDao()->SetCreate($vo)) {
            $fileName = Array();
            $fileSize = 0;
            $fileType = Array();
            $fileUrl = Array();
            $fileRefCnt = 0;
            foreach ($vo->children as $item) {
                $fileName[] = $item->fileName;
                $fileType[] = $item->fileType;
                $fileUrl[] = $item->fileServer;
                $fileSize += $item->fileSize;
                $fileRefCnt ++;
            }
            $vo->fileUid = $vo->fileGroup . '/' . date("ymd");
            $oldView = new FileLogVo();
            $oldView->mallId = $vo->mallId;
            $oldView->fileUid = $vo->fileUid;
            $oldView->fileGroup = $vo->fileGroup;
            $oldView->fileStatus = $vo->fileStatus;
            $this->UnsetFileLogCache($oldView, $vo);
            $vo->fileName = implode(',', $fileName);
            $vo->fileSize = $fileSize;
            $vo->fileType = implode(',', $fileType);
            $vo->fileServer = implode(',', $fileUrl);
            $vo->fileRefCnt = $fileRefCnt;
            $vo->regDate = $this->getDateNow();
            return $vo;
        } else {
            parent::GetException(KbmException::DATA_ERROR_CREATE);
        }
    }

    /**
     * 파일 박스 보기
     *
     * @param string $uid
     * @param boolean $isCopy
     * @return \Vo\FileLogVo
     */
    public function GetFileLogView($uid = '', $isCopy = false)
    {
        $vo = $this->GetFileLogVo($uid);
        $result = $this->GetFileLogDao()->GetViewView($vo);
        if (! empty($result)) {
            return $result;
        } else {
            $this->GetException(KbmException::DATA_ERROR_VIEW);
        }
    }

    /**
     * 파일 박스 삭제
     *
     * @param string $uid
     * @return FileLogVo
     */
    public function GetFileLogDelete($uid = '')
    {
        $vo = $this->GetFileLogVo($uid);
        if ($this->GetFileLogDao()->SetDeleteView($vo)) {
            $this->UnsetFileLogCache($vo, new FileLogVo());
            return $vo;
        } else {
            parent::GetException(KbmException::DATA_ERROR_DELETE);
        }
    }

    /**
     * 파일 박스 Excel 파일로 가져오기
     *
     * @param RequestVo $request
     * @return ExcelVo
     */
    public function GetFileLogListExcel(RequestVo $request = null)
    {
        $downloadFormVo = $this->GetDownloadFormVo($request, 'fileUids');
        $excelData = $this->GetFileLogList($downloadFormVo->searchRequest);
        $excelVo = new ExcelVo($downloadFormVo, $excelData);
        $excelVo->AddHeaderList($downloadFormVo->fieldList, true);
        return $excelVo;
    }

    /**
     * 네이버 삽 카테고리 파싱하기
     *
     * @param NaverShopCategoryVo $vo
     * @param RequestVo $request
     * @param NaverShopCategoryVo $oldView
     * @return NaverShopCategoryVo
     */
    public function GetNaverShopCategoryParse(NaverShopCategoryVo $vo, RequestVo $request, NaverShopCategoryVo $oldView = null)
    {
        return $vo;
    }

    /**
     * 네이버 삽 카테고리 업데이트 하기
     *
     * @param string $uid
     * @param string $locale
     * @param RequestVo $request
     * @return NaverShopCategoryVo
     */
    public function GetNaverShopCategoryUpdate($uid = '', $locale = '', RequestVo $request = null)
    {
        $oldView = $this->GetNaverShopCategoryView($uid);
        $vo = $this->GetNaverShopCategoryVo($uid, $request, clone $oldView);
        $this->GetNaverShopCategoryParse($vo, $request, $oldView);
        if ($this->GetNaverShopCategoryDao()->SetUpdate($vo)) {
            $this->UnsetNaverShopCategoryCache($oldView, $vo);
            return $vo;
        } else {
            parent::GetException(KbmException::DATA_ERROR_UPDATE);
        }
    }

    /**
     * 네이버 삽 카테고리 삭제하기
     *
     * @param string $uid
     * @return NaverShopCategoryVo
     */
    public function GetNaverShopCategoryDelete($uid = '')
    {
        $vo = $this->GetNaverShopCategoryVo($uid);
        if ($this->GetNaverShopCategoryDao()->SetDelete($vo)) {
            $this->UnsetNaverShopCategoryCache($vo, new NaverShopCategoryVo());
            return $vo;
        } else {
            parent::GetException(KbmException::DATA_ERROR_DELETE);
        }
    }

    /**
     * 상품 로그 정보 DAO 가져오기
     *
     * @return \Dao\GoodsInfoLogDao
     */
    public function GetGoodsInfoLogDao()
    {
        return parent::GetDao('GoodsInfoLogDao');
    }

    /**
     * 상품 로그 정보 추가하기
     *
     * @param string $logType
     * @param string $goodsCode
     * @param integer $logCnt
     * @return GoodsInfoLogVo
     */
    public function AddGoodsInfoLog($logType, $goodsCode = '', $logCnt = 1)
    {
        $vo = new GoodsInfoLogVo();
        $vo->mallId = $this->mallId;
        $vo->goodsCode = $goodsCode;
        if (! empty($vo->goodsCode)) {
            $logVo = new GoodsInfoLogVo();
            $logVo->logDate = date("Ymd");
            $logVo->mallId = $this->mallId;
            $logVo->goodsCode = $goodsCode;
            $logVo->logType = $logType;
            $logVo->logCnt = $logCnt;
            $this->DebugLogObject($logVo);
            $vo->logDate = date("Ymd");
            $vo->logType = $logType;
            $oldVo = $this->GetGoodsInfoLogDao()->GetView($vo);
            if (empty($oldVo)) {
                $vo->logCnt = $logCnt;
                $this->GetGoodsInfoLogDao()->SetCreate($vo);
            } else {
                $oldVo->logCnt += $logCnt;
                $this->GetGoodsInfoLogDao()->SetUpdate($oldVo);
            }
            return $vo;
        } else {
            return null;
        }
    }

    /**
     * 상품 로그 정보 추가하기
     *
     * @param OptionTreeVo[] $options1
     * @param GoodsCartOptionItemVo[] $options2
     */
    public function SetGoodsInfoStockOptionChange($options1 = Array(), $options2 = Array(), $reHashMap = false, GoodsInfoVo $goodsInfo = null, &$debugLine = Array())
    {
        $isHasStock = true;
        if (! empty($options1) && ! empty($options2)) {
            if ($reHashMap) {
                foreach ($options2 as $option) {
                    $optionId = $option->id;
                    if (! isset($options2[$optionId])) {
                        $tmpOptionVo = new GoodsCartOptionItemVo();
                        $tmpOptionVo->id = $optionId;
                        $tmpOptionVo->optionCnt = 0;
                        $tmpOptionVo->value = $option->value;
                        $options2[$optionId] = $tmpOptionVo;
                    }
                    $options2[$optionId]->optionCnt += $option->optionCnt;
                }
            }
            foreach ($options1 as $option) {
                $id = $option->id;
                if (isset($options2[$id])) {
                    $soldOption = $options2[$id];
                    if (! empty($soldOption->optionCnt)) {
                        $debugLine[] = 'Sold Option Check : ' . $id . ' (' . $option->value . ')' . $soldOption->optionCnt;
                        if ($option->optionSellFl == 'G') {
                            $debugLine[] = 'Sold Option Minus Goods Stock : ' . $id . ' (' . $goodsInfo->stockCnt . ' + ' . ($soldOption->optionCnt * - 1) . ')';
                            if ($goodsInfo->stockFl == 'Y' && ($goodsInfo->stockCnt - $soldOption->optionCnt) < 0) {
                                $isHasStock = false;
                            } else {
                                $goodsInfo->stockCnt -= $soldOption->optionCnt;
                            }
                        } else {
                            $debugLine[] = 'Sold Option Minus Options Stock : ' . $id . ' (' . $option->stockCnt . ' + ' . ($soldOption->optionCnt * - 1) . ')';
                            if (($option->stockCnt - $soldOption->optionCnt) < 0) {
                                $isHasStock = false;
                            }
                            $option->stockCnt = max(0, $option->stockCnt - $soldOption->optionCnt);
                            $debugLine[] = 'Sold Option Cnt Result : ' . $id . ' (' . $option->stockCnt . ')';
                        }
                    } else {
                        $debugLine[] = 'Sold Option Cnt Zero : ' . $id . ' (' . $option->value . ')';
                    }
                }
            }
        }
        return $isHasStock;
    }

    /**
     * 상품 로그 정보 추가하기
     *
     * @param string $goodsCode
     * @param integer $goodsCnt
     * @param GoodsCartOptionVo $cartInfo
     * @return GoodsInfoVo
     */
    public function GetGoodsInfoStockCheck($goodsCode = '', $goodsCnt = 0, GoodsCartOptionVo $cartInfo = null, GoodsInfoVo $dumyGoodsInfo = null, $goodsSumPrice = 0)
    {
        $hasError = false;
        $vo = null;
        if (! empty($goodsCode) && ! empty($goodsCnt)) {
            try {
                $vo = $this->GetGoodsInfoView($goodsCode);
                if ($vo->stockFl == 'Y') {
                    if ($vo->stockCnt - $goodsCnt < 0) {
                        $hasError = true;
                    } else if (! empty($cartInfo)) {
                        if ($vo->optionFl == 'Y') {
                            switch ($vo->optionDisplayFl) {
                                case 'C':
                                    if (! empty($cartInfo->options)) {
                                        if (! empty($vo->optionsTree)) {
                                            $cOption = $this->GetDeepClone($cartInfo->options);
                                            $optionTree = Array();
                                            foreach ($vo->optionsTree as $options) {
                                                $optionTree[$options->id] = $options;
                                                if (! empty($options->optionChildren)) {
                                                    foreach ($options->optionChildren as $childOptions) {
                                                        $optionTree[$childOptions->id] = $childOptions;
                                                        if (! empty($childOptions->optionChildren)) {
                                                            foreach ($childOptions->optionChildren as $grandChildOptions) {
                                                                $optionTree[$grandChildOptions->id] = $grandChildOptions;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                            foreach ($cOption as $options) {
                                                $id = $options->id;
                                                if (isset($optionTree[$id])) {
                                                    $optionRef = $optionTree[$id];
                                                    if (! empty($optionRef->refId)) {
                                                        foreach ($optionRef->refId as $refId) {
                                                            if (! empty($refId)) {
                                                                if (! isset($cartInfo->options[$refId])) {
                                                                    $refOptionVo = new GoodsCartOptionItemVo();
                                                                    $refOptionVo->id = $refId;
                                                                    $refOptionVo->optionCnt = 0;
                                                                    $cOption[$refId] = $refOptionVo;
                                                                }
                                                                $oldRefOptionVo = $cOption[$refId];
                                                                $oldRefOptionVo->optionCnt += $options->optionCnt;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        if (! $this->SetGoodsInfoStockOptionChange($optionTree, $cOption, false, $vo)) {
                                            $hasError = true;
                                        }
                                    }
                                    break;
                                default:
                                    if (! $this->SetGoodsInfoStockOptionChange($vo->options, $cartInfo->options, true, $vo)) {
                                        $hasError = true;
                                    }
                                    break;
                            }
                        }
                    }
                }
                if (! empty($dumyGoodsInfo)) {
                    if (! empty($dumyGoodsInfo) && ! empty($vo->deliveryPackingPrice) && $vo->deliveryPackingRule == 'O') {
                        $this->GetServicePolicy()->GetDeliveryPriceRuleMerge($dumyGoodsInfo, $vo, $goodsSumPrice, $goodsCnt);
                    }
                }
            } catch (\Exception $ex) {
                $this->GetException(KbmException::DATA_ERROR_STOCK, '해당 상품이 판매 종료되었습니다.[' . $goodsCode . ']');
            }
        }
        if ($hasError && ! empty($vo)) {
            $this->GetException(KbmException::DATA_ERROR_STOCK, '해당 상품의 재고가 없습니다.[' . $vo->goodsNm . ']');
        }
        return $vo;
    }

    /**
     * 상품 로그 정보 추가하기
     *
     * @param string $logType
     * @param string $goodsCode
     * @param integer $logCnt
     * @return GoodsInfoLogVo
     */
    public function AddGoodsInfoStockChangeLog($line = '')
    {
        error_log($line . "\n", 3, LOG_DIR . '/stock_' . $this->mallId . '_' . date('Ymd') . '.txt');
    }

    /**
     * 상품 로그 정보 추가하기
     *
     * @param string $logType
     * @param string $goodsCode
     * @param integer $logCnt
     * @return GoodsInfoLogVo
     */
    public function SetGoodsInfoStockChange($goodsCode = 0, $goodsCnt = 0, GoodsCartOptionVo $cartInfo = null, $orderCd = '')
    {
        if (! empty($goodsCode)) {
            if (! empty($goodsCnt)) {
                $this->AddGoodsInfoLog('O', $goodsCode, $goodsCnt);
            }
            $debugLines = Array();
            try {
                $debugLines[] = '----------------- ' . $orderCd;
                $debugLines[] = 'stock change start = ' . $goodsCode . ' - (' . $goodsCnt . ') ' . date('YmdHis');

                $vo = $this->GetGoodsInfoView($goodsCode);
                if ($vo->stockFl == 'Y') {
                    $vo->stockCnt = max(0, $vo->stockCnt - $goodsCnt);
                    if (! empty($cartInfo)) {
                        switch ($vo->optionDisplayFl) {
                            case 'C':
                                if (! empty($cartInfo->options)) {
                                    if (! empty($vo->optionsTree)) {
                                        $optionTree = Array();
                                        foreach ($vo->optionsTree as $options) {
                                            $optionTree[$options->id] = $options;
                                            if (! empty($options->optionChildren)) {
                                                foreach ($options->optionChildren as $childOptions) {
                                                    $optionTree[$childOptions->id] = $childOptions;
                                                    if (! empty($childOptions->optionChildren)) {
                                                        foreach ($childOptions->optionChildren as $grandChildOptions) {
                                                            $optionTree[$grandChildOptions->id] = $grandChildOptions;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        foreach ($cartInfo->options as $options) {
                                            $id = $options->id;
                                            if (isset($optionTree[$id])) {
                                                $optionRef = $optionTree[$id];
                                                if (! empty($optionRef->refId)) {
                                                    foreach ($optionRef->refId as $refId) {
                                                        if (! empty($refId)) {
                                                            if (! isset($cartInfo->options[$refId])) {
                                                                $refOptionVo = new GoodsCartOptionItemVo();
                                                                $refOptionVo->id = $refId;
                                                                $refOptionVo->optionCnt = 0;
                                                                $cartInfo->options[$refId] = $refOptionVo;
                                                            }
                                                            $oldRefOptionVo = $cartInfo->options[$refId];
                                                            $oldRefOptionVo->optionCnt += $options->optionCnt;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    $this->SetGoodsInfoStockOptionChange($vo->options, $cartInfo->options, false, null, $debugLines);
                                }
                                break;
                            default:
                                $this->SetGoodsInfoStockOptionChange($vo->options, $cartInfo->options, false, null, $debugLines);
                                break;
                        }
                        $this->SetGoodsInfoStockOptionChange($vo->optionsExt, $cartInfo->optionsExt, false, null, $debugLines);
                        $this->SetGoodsInfoStockOptionChange($vo->optionsText, $cartInfo->optionsText, false, null, $debugLines);
                        $this->SetGoodsInfoStockOptionChange($vo->optionsRef, $cartInfo->optionsRef, false, null, $debugLines);
                    }
                    if ($this->GetGoodsInfoDao()->SetStockSold($vo)) {
                        $debugLines[] = 'stock Sold Db Success : Remind ' . $vo->stockCnt . ' EA';
                        parent::UnSetCacheFile(parent::GetServiceCacheKey('scm-goodsInfo', $goodsCode, '*'));
                        parent::UnSetCacheFile(parent::GetServiceCacheKey('goodsInfo', $goodsCode, '*'));
                    } else {
                        $debugLines[] = 'stock Sold Db Error ';
                    }
                } else {
                    $debugLines[] = 'stock flag no ' . $goodsCode;
                }
            } catch (\Exception $ex) {
                $debugLines[] = 'stock change error = ' . json_encode($ex);
            }
            if (! empty($debugLines)) {
                $this->DebugLog($debugLines, 'stock_' . $this->mallId);
            }
        }
    }

    /**
     * 주문 정보 유효성 확인
     *
     * @param GoodsInfoVo $item
     * @return \Vo\GoodsInfoVo
     */
    public function GetGoodsInfoVersionCheck(GoodsInfoVo $item)
    {
        $item->goodsNmLocale = $this->GetVersionCheckVo(new LocaleTextVo(), $item->goodsNmLocale);
        $item->addInfo = $this->GetVersionCheckVoList(new TitleContentVo(), $item->addInfo);
        $item->addMustInfo = $this->GetVersionCheckVoList(new TitleContentVo(), $item->addMustInfo);
        $item->options = $this->GetVersionCheckVoList(new OptionTreeVo(), $item->options);
        $item->optionsExt = $this->GetVersionCheckVoList(new OptionTreeVo(), $item->optionsExt);
        $item->optionsText = $this->GetVersionCheckVoList(new OptionTreeVo(), $item->optionsText);
        $item->optionsRef = $this->GetVersionCheckVoList(new OptionTreeVo(), $item->optionsRef);
        $item->shortDescriptionLocale = $this->GetVersionCheckVo(new LocaleTextVo(), $item->shortDescriptionLocale);
        $item->eventDescriptionLocale = $this->GetVersionCheckVo(new LocaleTextVo(), $item->eventDescriptionLocale);
        $item->goodsDescriptionLocale = $this->GetVersionCheckVo(new LocaleTextVo(), $item->goodsDescriptionLocale);
        $item->goodsDescriptionMobileLocale = $this->GetVersionCheckVo(new LocaleTextVo(), $item->goodsDescriptionMobileLocale);
        $item->goodsDescriptionMobileLocale = $this->GetVersionCheckVo(new LocaleTextVo(), $item->goodsDescriptionMobileLocale);
        $item->externalVideoVo = $this->GetVersionCheckVo(new ExternalVideoVo(), $item->externalVideoVo);
        $item->seoTag = $this->GetVersionCheckVo(new SeoTagVo(), $item->seoTag);
        $item->naverOptions = $this->GetVersionCheckVo(new RefShopVo(), $item->naverOptions);
        // $item->facebookOptions = $this->GetVersionCheckVo(new RefShopVo(), $item->facebookOptions);
    }

    /**
     * 버전 확인
     */
    public function GetVersionCheck()
    {
        $queryVo = new GoodsSearchVo();
        $queryVo->mallId = $this->mallId;
        $result = $this->GetGoodsInfoDao()->GetList($queryVo, 1000);
        $checkedList = Array();
        foreach ($result as $item) {
            $this->GetGoodsInfoVersionCheck($item);

            $this->GetGoodsInfoDao()->SetUpdate($item);
            $checkedList[] = 'GOODS : ' . $item->goodsCode;
        }
        parent::UnSetCacheFile(parent::GetServiceCacheKey('goodsInfo', '*'));
        return $checkedList;
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
        switch ($controller->controllerType) {
            case 'admin':
                switch ($cmd) {
                    case 'objfileInfo.json':
                        return $this->GetGoodsInfoOptionsFromObjFile($request->fileInfo);
                        break;
                    case 'cart.json':
                        switch ($request->GetMethod()) {
                            case 'POST':
                                return $this->GetGoodsCartCreateAdmin($controller->GetSharedLoginVo($request, $this), $request);
                            case 'GET':
                            default:
                                return $this->GetGoodsCartPaging($controller->GetSharedLoginVo($request, $this), $request);
                        }
                    case 'cartprice.json':
                        return $this->GetGoodsCartPrice($controller->GetSharedLoginVo($request, $this), $request);
                    case 'cartpriceitem.json':
                        return $this->GetGoodsCartPriceItem($controller->GetSharedLoginVo($request, $this), $request->GetRequestVo('item'));
                    case 'cartpay.json':
                        return $this->GetGoodsCartPay($controller->loginInfoVo, $request);
                    case 'goodsInfo.json':
                        switch ($request->GetMethod()) {
                            case 'POST':
                                if ($this->IsScmAdmin()) {
                                    return $this->GetServiceScm()->GetGoodsInfoCreate($request, $this->loginInfo);
                                } else {
                                    return $this->GetGoodsInfoCreate($request);
                                }
                            case 'GET':
                            default:
                                return $this->GetGoodsInfoPaging($request);
                        }
                        break;
                    case 'goodsInfoList.json':
                        return $this->GetGoodsInfoList($request);
                    case 'goodsInfo':
                        $uid = $controller->GetJsonKey($subItemKey);
                        if ($this->IsScmAdmin()) {
                            $scmService = $this->GetServiceScm();
                            switch ($request->GetMethod()) {
                                case 'POST':
                                    return $scmService->GetGoodsInfoUpdate($uid, $request, $this->loginInfo);
                                case 'DELETE':
                                case 'PATCH':
                                    break;
                                case 'GET':
                                default:
                                    return $scmService->GetGoodsInfoView($uid, $controller->GetSharedLoginVo($request, $this), $request->isCopy == 'Y');
                            }
                        } else {
                            switch ($request->GetMethod()) {
                                case 'POST':
                                    return $this->GetGoodsInfoUpdate($uid, $request);
                                case 'DELETE':
                                    return $this->GetGoodsInfoHidden($uid);
                                case 'PATCH':
                                    switch ($request->method) {
                                        case 'copy':
                                        default:
                                            return $this->GetGoodsInfoCopy($uid);
                                            break;
                                    }
                                    break;
                                case 'GET':
                                default:
                                    return $this->GetGoodsInfoView($uid, $controller->GetSharedLoginVo($request, $this), $request->isCopy == 'Y');
                            }
                        }
                        break;
                    case 'goodsInfoExcel.json':
                        return $controller->GetExcelDown('goods_info', $this->GetGoodsInfoListExcel($request, 'N'));
                    case 'goodsSearchOption.json':
                        return $this->GetGoodsInfoSearchOptionList($request, $controller->GetSharedLoginVo($request, $this));
                    case 'deleteGoodsInfo.json':
                        switch ($request->GetMethod()) {
                            case 'GET':
                            default:
                                return $this->GetGoodsInfoPaging($request, null, '', 'Y');
                        }
                        break;
                    case 'goodsDiscountInfo':
                        $uid = $controller->GetJsonKey($subItemKey);
                        return $this->GetGoodsInfoDiscountView($uid, $controller->GetSharedLoginVo($request, $this));
                    case 'deleteGoodsInfo':
                        $uid = $controller->GetJsonKey($subItemKey);
                        switch ($request->GetMethod()) {
                            case 'DELETE':
                                return $this->GetGoodsInfoDelete($uid);
                            case 'GET':
                            default:
                                return $this->GetGoodsInfoView($uid, $controller->GetSharedLoginVo($request, $this), $request->isCopy == 'Y');
                        }
                        break;
                    case 'deleteGoodsInfoExcel.json':
                        return $controller->GetExcelDown('goods_info_delete', $this->GetGoodsInfoListExcel($request, 'Y'));
                        break;
                    case 'goodsInfoSimple':
                        $uid = $controller->GetJsonKey($subItemKey);
                        return $this->GetGoodsInfoSimpleView($uid);
                    case 'goodsInfoPdf.json':
                        $data = $this->GetGoodsInfoListPdf($request);
                        return $controller->GetPdfDown('goods_info', $data);
                        break;
                    case 'auctionInfo.json':
                        switch ($request->GetMethod()) {
                            case 'POST':
                                return $this->GetAuctionInfoCreate($request);
                            case 'GET':
                            default:
                                return $this->GetAuctionInfoPaging($request);
                        }
                        break;
                    case 'auctionInfoList.json':
                        return $this->GetAuctionInfoList($request);
                    case 'auctionInfo':
                        if (empty($extraKey)) {
                            $uid = $controller->GetJsonKey($subItemKey);
                            switch ($request->GetMethod()) {
                                case 'POST':
                                    return $this->GetAuctionInfoUpdate($uid, $request);
                                case 'PATCH':
                                    switch ($request->method) {
                                        case 'copy':
                                        default:
                                            return $this->GetAuctionInfoCopy($uid);
                                            break;
                                    }
                                    break;
                                case 'DELETE':
                                    return $this->GetAuctionInfoDelete($uid);
                                case 'GET':
                                default:
                                    return $this->GetAuctionInfoView($uid, $controller->GetSharedLoginVo($request, $this), $request->isCopy == 'Y');
                            }
                        } else {
                            $auctionUid = $subItemKey;
                            switch ($extraKey) {
                                case 'bidderList.json':
                                    switch ($request->GetMethod()) {
                                        case 'POST':
                                            return $this->GetAuctionInfoBidderCreate($auctionUid, $request, null, true);
                                        case 'GET':
                                        default:
                                            return $this->GetAuctionInfoBidderList($auctionUid, $request, true);
                                    }
                                    break;
                                case 'bidderBatch.json':
                                    switch ($request->mode) {
                                        case 'delete':
                                            return $this->GetAuctionInfoBidderDelete($auctionUid, $request->selected, true);
                                        case 'win':
                                        default:
                                            return $this->GetAuctionInfoBidderWin($auctionUid, $request->selected, true);
                                    }
                                    break;
                            }
                        }
                        break;
                    case 'auctionInfoExcel.json':
                        return $controller->GetExcelDown('goods_info', $this->GetAuctionInfoListExcel($request, 'N'));
                        break;
                    case 'auctionInfoSimple':
                        $uid = $controller->GetJsonKey($subItemKey);
                        return $this->GetAuctionInfoSimpleView($uid);
                    case 'auctionInfoPdf.json':
                        $data = $this->GetAuctionInfoListPdf($request);
                        return $controller->GetPdfDown('goods_info', $data);
                        break;
                    case 'favOptions.json':
                        switch ($request->GetMethod()) {
                            case 'POST':
                                return $this->GetGoodsFavOptionsCreate($request);
                            case 'GET':
                            default:
                                return $this->GetGoodsFavOptionsPaging($request);
                        }
                        break;
                    case 'favOptions':
                        $uid = $controller->GetJsonKey($subItemKey);
                        switch ($request->GetMethod()) {
                            case 'POST':
                                return $this->GetGoodsFavOptionsUpdate($uid, $request);
                            case 'DELETE':
                                return $this->GetGoodsFavOptionsDelete($uid);
                            case 'PATCH':
                                switch ($request->method) {
                                    case 'copy':
                                    default:
                                        return $this->GetGoodsFavOptionsCopy($uid);
                                        break;
                                }
                                break;
                            case 'GET':
                            default:
                                return $this->GetGoodsFavOptionsView($uid, $request->isCopy == 'Y');
                        }
                        break;
                    case 'favOptionsBatch.json':
                        switch ($request->mode) {
                            case 'copy':
                            case 'delete':
                                return $this->GetGoodsFavOptionsChange($request->codes, $request->mode);
                            default:
                                break;
                        }
                        break;
                    case 'goodsFavOptionsExcel.json':
                        return $controller->GetExcelDown('goods_fav_options', $this->GetGoodsFavOptionsListExcel($request));
                    case 'favMustInfo.json':
                        switch ($request->GetMethod()) {
                            case 'POST':
                                return $this->GetGoodsFavMustInfoCreate($request);
                            case 'GET':
                            default:
                                return $this->GetGoodsFavMustInfoPaging($request);
                        }
                        break;
                    case 'favMustInfo':
                        $uid = $controller->GetJsonKey($subItemKey);
                        switch ($request->GetMethod()) {
                            case 'POST':
                                return $this->GetGoodsFavMustInfoUpdate($uid, $request);
                            case 'DELETE':
                                return $this->GetGoodsFavMustInfoDelete($uid);
                            case 'PATCH':
                                switch ($request->method) {
                                    case 'copy':
                                    default:
                                        return $this->GetGoodsFavMustInfoCopy($uid);
                                        break;
                                }
                                break;
                            case 'GET':
                            default:
                                return $this->GetGoodsFavMustInfoView($uid, $request->isCopy == 'Y');
                        }
                        break;
                    case 'favMustInfoBatch.json':
                        switch ($request->mode) {
                            case 'copy':
                            case 'delete':
                                return $this->GetGoodsFavMustInfoChange($request->codes, $request->mode);
                            default:
                                break;
                        }
                        break;
                    case 'goodsFavMustInfoExcel.json':
                        return $controller->GetExcelDown('goods_fav_must_info', $this->GetGoodsFavMustInfoListExcel($request));
                    case 'favContents.json':
                        switch ($request->GetMethod()) {
                            case 'POST':
                                return $this->GetGoodsFavContentsCreate($request);
                            case 'GET':
                            default:
                                return $this->GetGoodsFavContentsPaging($request);
                        }
                        break;
                    case 'favContents':
                        $uid = $controller->GetJsonKey($subItemKey);
                        switch ($request->GetMethod()) {
                            case 'POST':
                                return $this->GetGoodsFavContentsUpdate($uid, $request);
                            case 'DELETE':
                                return $this->GetGoodsFavContentsDelete($uid);
                            case 'PATCH':
                                switch ($request->method) {
                                    case 'copy':
                                    default:
                                        return $this->GetGoodsFavContentsCopy($uid);
                                        break;
                                }
                                break;
                            case 'GET':
                            default:
                                return $this->GetGoodsFavContentsView($uid, $request->isCopy == 'Y');
                        }
                        break;
                    case 'favContentsBatch.json':
                        switch ($request->mode) {
                            case 'copy':
                            case 'delete':
                                return $this->GetGoodsFavContentsChange($request->codes, $request->mode);
                        }
                        break;
                    case 'goodsFavContentsExcel.json':
                        return $controller->GetExcelDown('goods_fav_contents', $this->GetGoodsFavContentsListExcel($request));
                    case 'goodsExcelCreate.json':
                        return $this->GetGoodsInfoCreateExcel($request);
                    case 'goodsExcelUpload.json':
                        switch ($request->GetMethod()) {
                            case 'POST':
                                $goodsInfoVo = new GoodsInfoVo();
                                $goodsInfoVo->options = Array();
                                $goodsInfoVo->optionsExt = Array();
                                $goodsInfoVo->optionsText = Array();
                                $goodsInfoVo->optionsRef = Array();
                                $goodsInfoVo->optionsTree = Array();
                                $goodsInfoVo->optionsExtTree = Array();
                                $goodsInfoVo->optionsTextTree = Array();
                                $goodsInfoVo->optionsRefTree = Array();
                                $optionChild = new OptionTreeVo();
                                $optionChild->optionChildren = Array();
                                $optionChild->optionChildren[] = new OptionTreeVo();
                                $goodsInfoVo->options[] = $this->GetDeepClone($optionChild);
                                $goodsInfoVo->optionsExt[] = $this->GetDeepClone($optionChild);
                                $goodsInfoVo->optionsText[] = $this->GetDeepClone($optionChild);
                                $goodsInfoVo->optionsRef[] = $this->GetDeepClone($optionChild);
                                $goodsInfoVo->optionsTree[] = $this->GetDeepClone($optionChild);
                                $goodsInfoVo->optionsExtTree[] = $this->GetDeepClone($optionChild);
                                $goodsInfoVo->optionsTextTree[] = $this->GetDeepClone($optionChild);
                                $goodsInfoVo->optionsRefTree[] = $this->GetDeepClone($optionChild);
                                return $this->GetGoodsInfoListValid($controller->GetExcelUp('goods_01', $request->excelFile, $goodsInfoVo, $request->attachFile));
                            case 'GET':
                            default:
                                return $controller->GetExcelDown('goods_info_sample', $this->GetGoodsInfoListSampleExcel($request));
                        }
                        break;
                    case 'batch.json':
                        switch ($request->GetMethod()) {
                            case 'POST':
                                return $this->GetGoodsInfoBatch($request, $request->goodsCodes, $request->mode);
                            case 'GET':
                            default:
                                return $this->GetGoodsInfoPaging($request);
                        }
                        break;
                    case 'category.json':
                        return $this->GetCategoryPaging($request);
                    case 'categoryList.json':
                        return $this->GetCategoryList($request);
                    case 'category':
                        switch ($subItemKey) {
                            case 'tree.json':
                                switch ($request->GetMethod()) {
                                    case 'POST':
                                        return $this->GetCategoryTreeUpdate($request);
                                    case 'GET':
                                    default:
                                        return $this->GetCategoryTreeList($request);
                                }
                                break;
                            default:
                                $uid = $controller->GetJsonKey($subItemKey);
                                switch ($request->GetMethod()) {
                                    case 'POST':
                                        return $this->GetCategoryUpdate($uid, $request);
                                    case 'GET':
                                    default:
                                        return $this->GetCategoryView($uid, $request->isCopy == 'Y');
                                }
                        }
                        break;
                    case 'categoryExcel.json':
                        return $controller->GetExcelDown('category', $this->GetCategoryListExcel($request));
                        break;
                    case 'categoryPdf.json':
                        $data = $this->GetCategoryListPdf($request);
                        return $controller->GetPdfDown('category_info', $data);
                        break;
                    case 'brand.json':
                        return $this->GetBrandPaging($request);
                    case 'brandList.json':
                        return $this->GetBrandList($request);
                    case 'brand':
                        switch ($subItemKey) {
                            case 'tree.json':
                                switch ($request->GetMethod()) {
                                    case 'POST':
                                        return $this->GetBrandTreeUpdate($request);
                                    case 'GET':
                                    default:
                                        return $this->GetBrandTreeList($request);
                                }
                                break;
                            default:
                                $uid = $controller->GetJsonKey($subItemKey);
                                switch ($request->GetMethod()) {
                                    case 'POST':
                                        return $this->GetBrandUpdate($uid, $request);
                                    case 'GET':
                                    default:
                                        return $this->GetBrandView($uid, $request->isCopy == 'Y');
                                }
                        }
                        break;
                    case 'brandExcel.json':
                        return $controller->GetExcelDown('brand', $this->GetBrandListExcel($request));
                        break;
                    case 'brandPdf.json':
                        $data = $this->GetBrandListPdf($request);
                        return $controller->GetPdfDown('brand_info', $data);
                        break;
                    case 'goodsidnick.json':
                        switch ($request->mode) {
                            case 'categorycheck':
                                return $this->GetCategoryIdCheck($request->newId, $request->oldId);
                            case 'brandcheck':
                                return $this->GetBrandIdCheck($request->newId, $request->oldId);
                        }
                        break;
                    case 'theme':
                        switch ($subItemKey) {
                            case 'tree.json':
                                switch ($request->GetMethod()) {
                                    case 'POST':
                                        return $this->GetDisplayThemeTreeUpdate($request);
                                    case 'GET':
                                    default:
                                        return $this->GetDisplayThemeTreeList($request);
                                }
                                break;
                            default:
                                $uid = $controller->GetJsonKey($subItemKey);
                                switch ($request->GetMethod()) {
                                    case 'POST':
                                        return $this->GetDisplayThemeUpdate($uid, $request);
                                    case 'GET':
                                    default:
                                        return $this->GetDisplayThemeView($uid, $request->isCopy == 'Y');
                                }
                        }
                        break;
                    case 'qna.json':
                        switch ($request->GetMethod()) {
                            case 'POST':
                                return $this->GetGoodsQnaCreate($controller->loginInfoVo, $request);
                            case 'GET':
                            default:
                                return $this->GetGoodsQnaPaging($controller->loginInfoVo, $request);
                        }

                    case 'qnaList.json':
                        switch ($request->GetMethod()) {
                            case 'POST':
                                return $this->GetGoodsQnaCreate($controller->loginInfoVo, $request);
                            case 'GET':
                            default:
                                return $this->GetGoodsQnaList($controller->loginInfoVo, $request);
                        }
                    case 'qna':
                        $uid = $controller->GetJsonKey($subItemKey);
                        switch ($request->GetMethod()) {
                            case 'POST':
                                return $this->GetGoodsQnaUpdate($controller->loginInfoVo, $uid, $request);
                            case 'DELETE':
                                return $this->GetGoodsQnaDelete($controller->loginInfoVo, $uid);
                            case 'GET':
                            default:
                                return $this->GetGoodsQnaView($controller->loginInfoVo, $uid, $request->isCopy == 'Y');
                        }
                    case 'qnaExcel.json':
                        return $controller->GetExcelDown('goods_qna', $this->GetGoodsQnaListExcel($request, $controller->loginInfoVo));

                    case 'review.json':
                        switch ($request->GetMethod()) {
                            case 'POST':
                                return $this->GetGoodsReviewCreate($controller->loginInfoVo, $request, false);
                            case 'GET':
                            default:
                                return $this->GetGoodsReviewPaging($controller->loginInfoVo, $request);
                        }
                    case 'reviewList.json':
                        switch ($request->GetMethod()) {
                            case 'POST':
                                return $this->GetGoodsReviewCreate($controller->loginInfoVo, $request, false);
                            case 'GET':
                            default:
                                return $this->GetGoodsReviewList($controller->loginInfoVo, $request);
                        }
                    case 'review':
                        $uid = $controller->GetJsonKey($subItemKey);
                        switch ($request->GetMethod()) {
                            case 'POST':
                                return $this->GetGoodsReviewUpdate($controller->loginInfoVo, $uid, $request);
                            case 'DELETE':
                                return $this->GetGoodsReviewDelete($controller->loginInfoVo, $uid);
                            case 'GET':
                            default:
                                return $this->GetGoodsReviewView($controller->loginInfoVo, $uid, $request->isCopy == 'Y');
                        }
                    case 'reviewBatch.json':
                        return $this->GetGoodsReviewBatch($request);
                    case 'reviewExcel.json':
                        return $controller->GetExcelDown('goods_review', $this->GetGoodsReviewListExcel($request, $controller->loginInfoVo));
                    case 'display':
                        $uid = $controller->GetJsonKey($subItemKey);
                        switch ($request->GetMethod()) {
                            case 'POST':
                                return $this->GetGoodsInfoCategoryUpdate($uid, $request);
                            case 'GET':
                            default:
                                return $this->GetGoodsInfoCategoryPaging($uid, $request);
                        }
                        break;
                    case 'search.json':
                        switch ($request->GetMethod()) {
                            case 'POST':
                                return $this->GetGoodsInfoSearchUpdate($request);
                            case 'GET':
                            default:
                                return $this->GetGoodsInfoSearchPaging($request);
                        }
                        break;
                    case 'display.json':
                        return new PagingVo();
                    case 'naverShopCategory.json':
                        switch ($request->GetMethod()) {
                            case 'GET':
                            default:
                                return $this->GetNaverShopCategoryPaging($request);
                        }
                        break;
                    case 'naverShopCategory':
                        $uid = $controller->GetJsonKey($subItemKey);
                        switch ($request->GetMethod()) {
                            case 'GET':
                            default:
                                return $this->GetNaverShopCategoryView($uid, $request->isCopy == 'Y');
                        }
                        break;
                    case 'filebox.json':
                        switch ($request->GetMethod()) {
                            case 'POST':
                                return $this->GetFileLogCreate($request);
                            case 'GET':
                            default:
                                return $this->GetFileLogPaging($request);
                        }
                        break;
                    case 'fileboxStatus.json':
                        return $this->GetFileLogStatus($request);
                    case 'fileboxDownload.json':
                        return $this->GetFileLogDownload($request, $controller);
                    case 'fileboxList.json':
                        return $this->GetFileLogList($request);
                        break;
                    case 'fileboxExcel.json':
                        return $controller->GetExcelDown('filebox', $this->GetFileLogListExcel($request));
                    case 'filebox':
                        $uid = $controller->GetJsonKey($subItemKey);
                        switch ($request->GetMethod()) {
                            case 'DELETE':
                                return $this->GetFileLogDelete($uid);
                            case 'GET':
                            default:
                                return $this->GetFileLogView($uid, $request->isCopy == 'Y');
                        }
                        break;
                }
                break;
            case 'front':
                $displayType = $controller->isMobile ? 'app' : 'web';
                switch ($cmd) {
                    case 'eventgoods':
                        $uid = $controller->GetJsonKey($subItemKey);
                        return $this->GetGoodsInfoEventList($uid, $request, $controller->loginInfoVo);
                    case 'goodsInfo.json':
                        return $this->GetGoodsInfoPaging($request, $controller->loginInfoVo, $displayType);
                    case 'goodsInfoList.json':
                        return $this->GetGoodsInfoList($request, $controller->loginInfoVo, $displayType);
                    case 'goodsInfo':
                        $uid = $controller->GetJsonKey($subItemKey);
                        return $this->GetGoodsInfoView($uid, $controller->loginInfoVo, false, $displayType);
                    case 'goodsSearchOption.json':
                        return $this->GetGoodsInfoSearchOptionList($request, $controller->loginInfoVo, $displayType, 'N');
                    case 'goodsInfoSimple':
                        $uid = $controller->GetJsonKey($subItemKey);
                        return $this->GetGoodsInfoSimpleView($uid, $displayType, $controller->GetGroupSno());
                    case 'auctionInfo.json':
                        return $this->GetAuctionInfoPaging($request, $controller->loginInfoVo, $displayType);
                    case 'auctionInfoList.json':
                        return $this->GetAuctionInfoList($request, $controller->loginInfoVo, $displayType);
                    case 'auctionInfo':
                        $uid = $controller->GetJsonKey($subItemKey);
                        return $this->GetAuctionInfoView($uid, $controller->loginInfoVo, false, $displayType);
                    case 'auctionInfoSimple':
                        $uid = $controller->GetJsonKey($subItemKey);
                        return $this->GetAuctionInfoSimpleView($uid, $displayType, $controller->GetGroupSno());
                    case 'auctionprice.json':
                        return $this->GetAuctionCartPrice($controller->loginInfoVo, $request);
                    case 'auction.json':
                        switch ($request->GetMethod()) {
                            case 'POST':
                                return $this->GetAuctionCartCreate($controller->loginInfoVo, $request);
                            case 'DELETE':
                                return $this->GetGoodsCartDeleteAll($controller->loginInfoVo);
                            case 'GET':
                            default:
                                return $this->GetGoodsCartPaging($controller->loginInfoVo, $request);
                        }
                    case 'auctionList.json':
                        switch ($request->GetMethod()) {
                            case 'POST':
                                return $this->GetGoodsCartCreate($controller->loginInfoVo, $request);
                            case 'DELETE':
                                return $this->GetGoodsCartDeleteAll($controller->loginInfoVo);
                            case 'GET':
                            default:
                                return $this->GetGoodsCartList($controller->loginInfoVo, $request);
                        }
                    case 'auction':
                        $uid = $controller->GetJsonKey($subItemKey);
                        switch ($request->GetMethod()) {
                            case 'POST':
                                return $this->GetGoodsCartUpdate($controller->loginInfoVo, $uid, $request);
                            case 'DELETE':
                                return $this->GetGoodsCartDelete($controller->loginInfoVo, $uid);
                            case 'GET':
                            default:
                                return $this->GetGoodsCartView($controller->loginInfoVo, $uid);
                        }
                        break;
                    case 'goodsDiscountInfo':
                        $uid = $controller->GetJsonKey($subItemKey);
                        return $this->GetGoodsInfoDiscountView($uid, $controller->loginInfoVo, $displayType);
                    case 'category.json':
                        return $this->GetCategoryPaging($request);
                    case 'categoryList.json':
                        return $this->GetCategoryList($request);
                    case 'category':
                        try {
                            $uid = $controller->GetJsonKey($subItemKey);
                            return $this->GetCategoryView($uid);
                        } catch (Exception $ex) {
                            return new CategoryVo();
                        }
                        break;
                    case 'brand.json':
                        return $this->GetBrandPaging($request);
                    case 'brandList.json':
                        return $this->GetBrandList($request);
                    case 'brand':
                        try {
                            $uid = $controller->GetJsonKey($subItemKey);
                            return $this->GetBrandView($uid);
                        } catch (Exception $ex) {
                            return new BrandVo();
                        }
                        break;
                    case 'qna.json':
                        switch ($request->GetMethod()) {
                            case 'POST':
                                return $this->GetGoodsQnaCreate($controller->loginInfoVo, $request);
                            case 'GET':
                            default:
                                return $this->GetGoodsQnaPaging($controller->loginInfoVo, $request);
                        }
                    case 'qnaList.json':
                        switch ($request->GetMethod()) {
                            case 'POST':
                                return $this->GetGoodsQnaCreate($controller->loginInfoVo, $request);
                            case 'GET':
                            default:
                                return $this->GetGoodsQnaList($controller->loginInfoVo, $request);
                        }
                    case 'myqna':
                    case 'qna':
                        $uid = $controller->GetJsonKey($subItemKey);
                        switch ($request->GetMethod()) {
                            case 'POST':
                                return $this->GetGoodsQnaUpdate($controller->loginInfoVo, $uid, $request);
                            case 'DELETE':
                                return $this->GetGoodsQnaDelete($controller->loginInfoVo, $uid);
                            case 'GET':
                            default:
                                return $this->GetGoodsQnaView($controller->loginInfoVo, $uid);
                        }
                    case 'myqna.json':
                        switch ($request->GetMethod()) {
                            case 'POST':
                                return $this->GetGoodsQnaCreate($controller->loginInfoVo, $request);
                            case 'GET':
                            default:
                                return $this->GetGoodsMyQnaPaging($controller->loginInfoVo, $request);
                        }
                    case 'myqnaList.json':
                        return $this->GetGoodsMyQnaList($controller->loginInfoVo, $request);
                    case 'oftenGoods.json':
                        return $this->GetGoodsMyOftenPaging($controller->loginInfoVo, $request);
                    case 'oftenGoodsList.json':
                        return $this->GetGoodsMyOftenList($controller->loginInfoVo, $request, $displayType);
                    case 'wish.json':
                        switch ($request->GetMethod()) {
                            case 'DELETE':
                                return $this->GetGoodsInfoWishDelete($controller->loginInfoVo, $request);
                            case 'GET':
                            default:
                                return $this->GetGoodsInfoWishPaging($controller->loginInfoVo, $request, $displayType);
                        }
                    case 'wishList.json':
                        switch ($request->GetMethod()) {
                            case 'DELETE':
                                return $this->GetGoodsInfoWishDelete($controller->loginInfoVo, $request);
                            case 'GET':
                            default:
                                return $this->GetGoodsInfoWishList($controller->loginInfoVo, $request, $displayType);
                        }
                    case 'recent.json':
                        return $this->GetGoodsInfoRecentPaging($controller->loginInfoVo, $request, $displayType);
                    case 'recentList.json':
                        return $this->GetGoodsInfoRecentList($controller->loginInfoVo, $request, $displayType);
                    case 'review.json':
                        switch ($request->GetMethod()) {
                            case 'POST':
                                return $this->GetGoodsReviewCreate($controller->loginInfoVo, $request, $controller->isMobile);
                            case 'GET':
                            default:
                                return $this->GetGoodsReviewPaging($controller->loginInfoVo, $request);
                        }
                    case 'reviewTotal.json':
                        switch ($request->GetMethod()) {
                            default:
                                return $this->GetGoodsReviewTotalPaging($controller->loginInfoVo, $request);
                        }
                    case 'reviewList.json':
                        switch ($request->GetMethod()) {
                            case 'POST':
                                return $this->GetGoodsReviewCreate($controller->loginInfoVo, $request, $controller->isMobile);
                            case 'GET':
                            default:
                                return $this->GetGoodsReviewList($controller->loginInfoVo, $request);
                        }
                    case 'myreview':
                    case 'review':
                        $uid = $controller->GetJsonKey($subItemKey);
                        switch ($request->GetMethod()) {
                            case 'POST':
                                return $this->GetGoodsReviewUpdate($controller->loginInfoVo, $uid, $request);
                            case 'DELETE':
                                return $this->GetGoodsReviewDelete($controller->loginInfoVo, $uid);
                            case 'GET':
                            default:
                                return $this->GetGoodsReviewView($controller->loginInfoVo, $uid);
                        }
                    case 'myreview.json':
                        switch ($request->GetMethod()) {
                            case 'POST':
                                return $this->GetGoodsReviewCreate($controller->loginInfoVo, $request, $controller->isMobile);
                            case 'GET':
                            default:
                                return $this->GetGoodsMyReviewPaging($controller->loginInfoVo, $request);
                        }
                    case 'myreviewList.json':
                        return $this->GetGoodsMyReviewList($controller->loginInfoVo, $request);
                    case 'bestreview.json':
                        return $this->GetGoodsReviewPaging($controller->loginInfoVo, $request, true);
                    case 'bestreviewList.json':
                        return $this->GetGoodsReviewList($controller->loginInfoVo, $request, true);
                    case 'cart.json':
                        switch ($request->GetMethod()) {
                            case 'POST':
                                return $this->GetGoodsCartCreate($controller->loginInfoVo, $request);
                            case 'DELETE':
                                return $this->GetGoodsCartDeleteAll($controller->loginInfoVo);
                            case 'GET':
                            default:
                                return $this->GetGoodsCartPaging($controller->loginInfoVo, $request);
                        }
                    case 'cartList.json':
                        switch ($request->GetMethod()) {
                            case 'POST':
                                return $this->GetGoodsCartCreate($controller->loginInfoVo, $request);
                            case 'DELETE':
                                return $this->GetGoodsCartDeleteAll($controller->loginInfoVo);
                            case 'GET':
                            default:
                                return $this->GetGoodsCartList($controller->loginInfoVo, $request);
                        }
                    case 'cart':
                        $uid = $controller->GetJsonKey($subItemKey);
                        switch ($request->GetMethod()) {
                            case 'POST':
                                return $this->GetGoodsCartUpdate($controller->loginInfoVo, $uid, $request);
                            case 'DELETE':
                                return $this->GetGoodsCartDelete($controller->loginInfoVo, $uid);
                            case 'GET':
                            default:
                                return $this->GetGoodsCartView($controller->loginInfoVo, $uid);
                        }
                    case 'cartstatus.json':
                        return $this->GetGoodsCartStatus($controller->loginInfoVo, $request);
                    case 'cartprice.json':
                        return $this->GetGoodsCartPrice($controller->loginInfoVo, $request);
                    case 'cartpay.json':
                        return $this->GetGoodsCartPay($controller->loginInfoVo, $request);
                    case 'naverOrder.json':
                        return $this->GetGoodsNaverOrder($controller->loginInfoVo, $request);
                    case 'naverWish.json':
                        return $this->GetGoodsNaverWish($controller->loginInfoVo, $request);
                    case 'favOptions.json':
                    case 'favOptions':
                    case 'favMustInfo.json':
                    case 'favMustInfo':
                    case 'favContents.json':
                    case 'favContents':
                    case 'category.json':
                    case 'brand.json':
                        if (! empty($controller->loginInfoVo->scmNo)) {
                            switch ($cmd) {
                                case 'favOptions.json':
                                    return $this->GetGoodsFavOptionsPaging($request);
                                case 'favOptions':
                                    $uid = $controller->GetJsonKey($subItemKey);
                                    return $this->GetGoodsFavOptionsView($uid, $request->isCopy == 'Y');
                                case 'favMustInfo.json':
                                    return $this->GetGoodsFavMustInfoPaging($request);
                                case 'favMustInfo':
                                    $uid = $controller->GetJsonKey($subItemKey);
                                    return $this->GetGoodsFavMustInfoView($uid, $request->isCopy == 'Y');
                                case 'favContents.json':
                                    return $this->GetGoodsFavContentsPaging($request);
                                case 'favContents':
                                    $uid = $controller->GetJsonKey($subItemKey);
                                    return $this->GetGoodsFavContentsView($uid, $request->isCopy == 'Y');
                                case 'category.json':
                                    return $this->GetCategoryPaging($request);
                                case 'brand.json':
                                    return $this->GetBrandPaging($request);
                            }
                        }
                }
                break;
            case 'erp':
                switch ($cmd) {
                    case 'list.json':
                    case 'list.xml':
                        return $this->GetGoodsInfoPaging($controller->GetErpPageRequest($request));
                    case 'detail':
                        return $this->GetGoodsInfoView($controller->GetJsonKey($subItemKey));
                    case 'register.json':
                    case 'register.xml':
                        return $this->GetGoodsInfoCreate($controller->GetImageRequestList($request, 'goodsImageAdded,goodsImageMaster'));
                    case 'modify':
                        return $this->GetGoodsInfoUpdate($controller->GetJsonKey($subItemKey), $controller->GetImageRequestList($request, 'goodsImageAdded,goodsImageMaster'));
                    case 'category.xml':
                    case 'category.json':
                        return $this->GetCategoryList($controller->GetErpPageRequest($request, 1000));
                    case 'brand.json':
                    case 'brand.xml':
                        return $this->GetBrandList($controller->GetErpPageRequest($request));
                    case 'qnalist.json':
                    case 'qnalist.xml':
                        return $this->GetGoodsQnaPaging($controller->loginInfoVo, $controller->GetErpPageRequest($request));
                    case 'qnadetail':
                        return $this->GetGoodsQnaView($controller->loginInfoVo, $controller->GetJsonKey($subItemKey));
                    case 'qnamodify':
                        return $this->GetGoodsQnaUpdate($controller->loginInfoVo, $controller->GetJsonKey($subItemKey), $request);
                    case 'code.json':
                    case 'code.xml':
                        $commonService = $controller->GetServiceCommon();
                        $codeList = new stdClass();
                        $codeKeys = Array(
                            'groupSno',
                            'useDeliverer',
                            'goodsIconTime',
                            'goodsIconFix',
                            'goodsColor',
                            'goodsIcon',
                            'brandCd',
                            'categoryCd',
                            'deliveryOption'
                        );
                        foreach ($codeKeys as $key) {
                            $codeList->$key = $commonService->GetCodeDataList($key);
                        }
                        return $codeList;
                        break;
                }
                break;
        }
        $this->GetException(\KbmException::DATA_ERROR_UNKNOWN);
    }
}

?>