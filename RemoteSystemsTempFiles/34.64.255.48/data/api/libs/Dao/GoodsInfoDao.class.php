<?php

/**
 * Project:	KBMALL 1.0 Project
 * File:	libs/Dao/GoodsInfoDao.class.php
 * kbm_goods_info(상품정보) Table Dao
 * 상품정보
 * 
 * @link http://www.hanbiz.kr/
 * @author Kim Jong-gab <outmind0@naver.com>
 * @version 1.0
 * @since 1.0
 * @copyright 2001-2017 Hanbiz, Inc.
 * @package kbmall
 */
namespace Dao;

use Vo\GoodsCategoryInfoVo;
use Vo\GoodsCategoryVo;
use Vo\GoodsInfoVo;
use Vo\GoodsSearchVo;
use Vo\TreeSearchVo;
use Vo\DashBoardStatusVo;

/**
 * 상품정보
 */
class GoodsInfoDao extends AbstractDao
{

    /**
     * kbm_goods_info Query Id
     *
     * @var string
     */
    const QUERY_ID = 'goods_info';

    /**
     * 목록가져오기
     *
     * @param \Vo\RequestVo $queryVo
     * @param integer $limit
     * @param integer $offset
     * @return GoodsInfoVo[]
     */
    public function GetList($queryVo, $limit = 10, $offset = 0)
    {
        return parent::ExecuteSqlId(self::QUERY_ID . '.list', $queryVo, $limit, $offset);
    }

    /**
     * 목록가져오기
     *
     * @param \Vo\RequestVo $queryVo
     * @param integer $limit
     * @param integer $offset
     * @return GoodsInfoVo[]
     */
    public function GetListSimple($queryVo, $limit = 10, $offset = 0)
    {
        return parent::ExecuteSqlId(self::QUERY_ID . '.list-simple', $queryVo, $limit, $offset);
    }

    /**
     * 페이지가져오기
     *
     * @param \Vo\RequestVo $queryVo
     * @param integer $limit
     * @param integer $offset
     * @return \Vo\PagingVo
     */
    public function GetPaging($queryVo, $limit = 10, $offset = 0)
    {
        
        return parent::ExecuteSqlId(self::QUERY_ID . '.paging', $queryVo, $limit, $offset);
    }

    /**
     * 목록가져오기
     *
     * @param \Vo\RequestVo $queryVo
     * @param integer $limit
     * @param integer $offset
     * @return GoodsCategoryVo[]
     */
    public function GetCategoryGoodsList($queryVo, $limit = 10, $offset = 0)
    {
        return parent::ExecuteSqlId(self::QUERY_ID . '.category-goods-list', $queryVo, $limit, $offset);
    }

    /**
     * 신규입력
     *
     * @param GoodsInfoVo $vo
     * @return boolean
     */
    public function SetGoodsCategoryCreate(GoodsInfoVo $vo)
    {
        $result = parent::ExecuteSqlId(self::QUERY_ID . '.goods-category-insert', $vo);
        if ($result > 0) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 목록가져오기
     *
     * @param \Vo\RequestVo $queryVo
     * @param integer $limit
     * @param integer $offset
     * @return GoodsInfoVo[]
     */
    public function GetCategoryList($queryVo, $limit = 10, $offset = 0)
    {
        return parent::ExecuteSqlId(self::QUERY_ID . '.category-list', $queryVo, $limit, $offset);
    }

    /**
     * 페이지가져오기
     *
     * @param \Vo\RequestVo $queryVo
     * @param integer $limit
     * @param integer $offset
     * @return \Vo\PagingVo
     */
    public function GetCategoryPaging($queryVo, $limit = 10, $offset = 0)
    {
        return parent::ExecuteSqlId(self::QUERY_ID . '.category-paging', $queryVo, $limit, $offset);
    }

    /**
     * 신규입력
     *
     * @param GoodsInfoVo $vo
     * @return boolean
     */
    public function SetGoodsSearchCreate(GoodsInfoVo $vo)
    {
        $result = parent::ExecuteSqlId(self::QUERY_ID . '.goods-search-insert', $vo);
        if ($result > 0) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 목록가져오기
     *
     * @param \Vo\RequestVo $queryVo
     * @param integer $limit
     * @param integer $offset
     * @return GoodsCategoryVo[]
     */
    public function GetSearchGoodsList($queryVo, $limit = 10, $offset = 0)
    {
        return parent::ExecuteSqlId(self::QUERY_ID . '.search-goods-list', $queryVo, $limit, $offset);
    }
    
    /**
     * 목록가져오기
     *
     * @param \Vo\RequestVo $queryVo
     * @param integer $limit
     * @param integer $offset
     * @return GoodsInfoVo[]
     */
    public function GetSearchList($queryVo, $limit = 10, $offset = 0)
    {
        return parent::ExecuteSqlId(self::QUERY_ID . '.search-list', $queryVo, $limit, $offset);
    }
    
    /**
     * 페이지가져오기
     *
     * @param \Vo\RequestVo $queryVo
     * @param integer $limit
     * @param integer $offset
     * @return \Vo\PagingVo
     */
    public function GetSearchPaging($queryVo, $limit = 10, $offset = 0)
    {
        return parent::ExecuteSqlId(self::QUERY_ID . '.search-paging', $queryVo, $limit, $offset);
    }
    
    /**
     * 목록가져오기
     *
     * @param \Vo\RequestVo $queryVo
     * @param integer $limit
     * @param integer $offset
     * @return GoodsInfoVo[]
     */
    public function GetRecentList($queryVo, $limit = 10, $offset = 0)
    {
        return parent::ExecuteSqlId(self::QUERY_ID . '.recentlist', $queryVo, $limit, $offset);
    }

    /**
     * 목록가져오기
     *
     * @param \Vo\RequestVo $queryVo
     * @param integer $limit
     * @param integer $offset
     * @return \Vo\PagingVo
     */
    public function GetRecentPaging($queryVo, $limit = 10, $offset = 0)
    {
        return parent::ExecuteSqlId(self::QUERY_ID . '.recentpaging', $queryVo, $limit, $offset);
    }

    /**
     * 목록가져오기
     *
     * @param \Vo\RequestVo $queryVo
     * @param integer $limit
     * @param integer $offset
     * @return GoodsInfoVo[]
     */
    public function GetWishList($queryVo, $limit = 10, $offset = 0)
    {
        return parent::ExecuteSqlId(self::QUERY_ID . '.wishlist', $queryVo, $limit, $offset);
    }

    /**
     * 목록가져오기
     *
     * @param \Vo\RequestVo $queryVo
     * @param integer $limit
     * @param integer $offset
     * @return \Vo\PagingVo
     */
    public function GetWishPaging($queryVo, $limit = 10, $offset = 0)
    {
        return parent::ExecuteSqlId(self::QUERY_ID . '.wishpaging', $queryVo, $limit, $offset);
    }

    /**
     * 삭제
     *
     * @param GoodsInfoVo $vo
     * @return boolean
     */
    public function SetWishDelete(GoodsSearchVo $vo)
    {
        if (parent::ExecuteSqlId(self::QUERY_ID . '.wishdelete', $vo) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 보기
     *
     * @param GoodsInfoVo $vo
     * @return GoodsInfoVo
     */
    public function GetView(GoodsInfoVo $vo)
    {
        return parent::ExecuteSqlIdOne(self::QUERY_ID . '.view', $vo);
    }
    
    /**
     * 최근 배지 카운터 가져오기
     * 
     * @param \stdClass $vo
     * @return number
     */
    public function GetTabBadge($vo = null)
    {
        return parent::ExecuteSqlIdOne(self::QUERY_ID . '.view-badge', $vo);
    }
    
    
    /**
     * 대시 보드 데이타 가져오기
     *
     * @param DashBoardStatusVo $vo
     * @return DashBoardStatusVo
     */
    public function GetDashBoardStatus(DashBoardStatusVo $vo)
    {
        return parent::ExecuteSqlIdOne(self::QUERY_ID . '.view-status', $vo);
    }

    /**
     * 신규입력
     *
     * @param GoodsInfoVo $vo
     * @return boolean
     */
    public function SetCreate(GoodsInfoVo $vo)
    {
        if (parent::ExecuteSqlId(self::QUERY_ID . '.insert', $vo) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 수정
     *
     * @param GoodsInfoVo $vo
     * @return boolean
     */
    public function SetUpdate(GoodsInfoVo $vo)
    {
        if (parent::ExecuteSqlId(self::QUERY_ID . '.update', $vo) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 수정
     *
     * @param GoodsInfoVo $vo
     * @return boolean
     */
    public function SetDisplayUpdate(GoodsSearchVo $vo)
    {
        if (parent::ExecuteSqlId(self::QUERY_ID . '.displayupdate', $vo) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 수정
     *
     * @param GoodsInfoVo $vo
     * @return boolean
     */
    public function SetReviewUpdate(GoodsInfoVo $vo)
    {
        if (parent::ExecuteSqlId(self::QUERY_ID . '.reviewupdate', $vo) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 수정일 수정
     *
     * @param GoodsSearchVo $vo
     * @return boolean
     */
    public function SetModDateNow(GoodsSearchVo $vo)
    {
        if (parent::ExecuteSqlId(self::QUERY_ID . '.updatemoddate', $vo) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 가격설정
     *
     * @param GoodsSearchVo $vo
     * @return boolean
     */
    public function SetPrice(GoodsSearchVo $vo)
    {
        if (parent::ExecuteSqlId(self::QUERY_ID . '.updateprice', $vo) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 가격설정
     *
     * @param GoodsSearchVo $vo
     * @return boolean
     */
    public function SetCommission(GoodsSearchVo $vo)
    {
        if (parent::ExecuteSqlId(self::QUERY_ID . '.updatecommission', $vo) > 0) {
            return true;
        } else {
            return false;
        }
    }
    
    
    /**
     * 마일리지 일괄 설정
     *
     * @param GoodsSearchVo $vo
     * @return boolean
     */
    public function SetMileage(GoodsSearchVo $vo)
    {
        if (parent::ExecuteSqlId(self::QUERY_ID . '.updatemileage', $vo) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 기타 정보 일괄 설정
     *
     * @param GoodsSearchVo $vo
     * @return boolean
     */
    public function SetInfoOthers(GoodsSearchVo $vo)
    {
        if (parent::ExecuteSqlId(self::QUERY_ID . '.updateinfo', $vo) > 0) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 색상 아이콘 설정
     *
     * @param GoodsSearchVo $vo
     * @return boolean
     */
    public function SetIconColor(GoodsSearchVo $vo)
    {
        if (parent::ExecuteSqlId(self::QUERY_ID . '.updateiconcolor', $vo) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 재고설정
     *
     * @param GoodsSearchVo $vo
     * @return boolean
     */
    public function SetStock(GoodsSearchVo $vo)
    {
        if (parent::ExecuteSqlId(self::QUERY_ID . '.updatestock', $vo) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 재고설정
     *
     * @param GoodsSearchVo $vo
     * @return boolean
     */
    public function SetStockSold(GoodsInfoVo $vo)
    {
        if (parent::ExecuteSqlId(self::QUERY_ID . '.updatestock-sold', $vo) > 0) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 품절처리
     *
     * @param GoodsSearchVo $vo
     * @return boolean
     */
    public function SetSoldout(GoodsSearchVo $vo)
    {
        if (parent::ExecuteSqlId(self::QUERY_ID . '.updatesoldout', $vo) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 숨김처리
     *
     * @param GoodsSearchVo $vo
     * @return boolean
     */
    public function SetHidden(GoodsSearchVo $vo)
    {
        if (parent::ExecuteSqlId(self::QUERY_ID . '.updatehidden', $vo) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 숨김처리 해제
     *
     * @param GoodsSearchVo $vo
     * @return boolean
     */
    public function SetShow(GoodsSearchVo $vo)
    {
        if (parent::ExecuteSqlId(self::QUERY_ID . '.updateshow', $vo) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 삭제
     *
     * @param GoodsInfoVo $vo
     * @return boolean
     */
    public function SetDelete(GoodsInfoVo $vo)
    {
        if (parent::ExecuteSqlId(self::QUERY_ID . '.delete', $vo) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 목록가져오기
     *
     * @param \Vo\RequestVo $queryVo
     * @param integer $limit
     * @param integer $offset
     * @return GoodsCategoryVo[]
     */
    public function GetGoodsCategoryList($queryVo, $limit = 10, $offset = 0)
    {
        return parent::ExecuteSqlId(self::QUERY_ID . '.goods_category_list', $queryVo, $limit, $offset);
    }

    /**
     * 마지막 진열순서 가져오기
     *
     * @param GoodsCategoryVo $vo
     * @return GoodsCategoryVo
     */
    public function GetCategoryMax(GoodsCategoryVo $vo)
    {
        return parent::ExecuteSqlIdOne(self::QUERY_ID . '.goods_category_max', $vo);
    }

    /**
     * 카테고리 삭제
     *
     * @param GoodsInfoVo $vo
     * @return boolean
     */
    public function SetCategoryDelete(GoodsInfoVo $vo)
    {
        if (parent::ExecuteSqlId(self::QUERY_ID . '.goods_category_delete', $vo) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 카테고리 신규입력
     *
     * @param GoodsInfoVo $vo
     * @return boolean
     */
    public function SetCategoryCreate(GoodsInfoVo $vo)
    {
        if (parent::ExecuteSqlId(self::QUERY_ID . '.goods_category_insert', $vo) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 검색옵션 삭제
     *
     * @param GoodsInfoVo $vo
     * @return boolean
     */
    public function SetSearchOptionDelete(GoodsInfoVo $vo)
    {
        if (parent::ExecuteSqlId(self::QUERY_ID . '.goods_search_delete', $vo) > 0) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 검색 옵션 목록가져오기
     *
     * @param \Vo\RequestVo $queryVo
     * @param integer $limit
     * @param integer $offset
     * @return \Vo\TreeVo[]
     */
    public function GetSearchOptionList($queryVo, $limit = 10, $offset = 0)
    {
        return parent::ExecuteSqlId(self::QUERY_ID . '.goods_search_list', $queryVo, $limit, $offset);
    }
    
    
    /**
     * 검색옵션 신규입력
     *
     * @param GoodsInfoVo $vo
     * @return boolean
     */
    public function SetSearchOptionCreate(GoodsInfoVo $vo)
    {
        if (parent::ExecuteSqlId(self::QUERY_ID . '.goods_search_insert', $vo) > 0) {
            return true;
        } else {
            return false;
        }
    }
    
    
    /**
     * 카테고리 정보 삭제
     *
     * @param GoodsCategoryInfoVo $vo
     * @return boolean
     */
    public function SetCategoryInfoDelete(GoodsCategoryInfoVo $vo)
    {
        if (parent::ExecuteSqlId(self::QUERY_ID . '.goods_category_info_delete', $vo) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 카테고리 정보 신규입력
     *
     * @param GoodsInfoVo $vo
     * @return boolean
     */
    public function SetCategoryInfoCreate(GoodsCategoryInfoVo $vo)
    {
        if (parent::ExecuteSqlId(self::QUERY_ID . '.goods_category_info_insert', $vo) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 카테고리 정보 수정
     *
     * @param GoodsCategoryInfoVo $vo
     * @return boolean
     */
    public function SetCategoryInfoUpdate(GoodsCategoryInfoVo $vo)
    {
        if (parent::ExecuteSqlId(self::QUERY_ID . '.goods_category_info_update', $vo) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 브랜드 상품수
     *
     * @param GoodsSearchVo $searchVo
     * @param integer $limit
     * @param integer $offset
     * @return \Vo\TreeVo[]
     */
    public function GetBrandCntList(GoodsSearchVo $searchVo, $limit = 10, $offset = 0)
    {
        return parent::ExecuteSqlId(self::QUERY_ID . '.brandCntlist', $searchVo, $limit, $offset);
    }

    /**
     * 카테고리 상품수
     *
     * @param GoodsSearchVo $searchVo
     * @param integer $limit
     * @param integer $offset
     * @return \Vo\TreeVo[]
     */
    public function GetCategoryCntList(GoodsSearchVo $searchVo, $limit = 10, $offset = 0)
    {
        return parent::ExecuteSqlId(self::QUERY_ID . '.categoryCntlist', $searchVo, $limit, $offset);
    }

    /**
     * 카테고리 상품수 Cleanup
     *
     * @param GoodsSearchVo $searchVo
     * @return boolean
     */
    public function GetCategoryCntClean(GoodsSearchVo $searchVo, $limit = 10, $offset = 0)
    {
        if (parent::ExecuteSqlId(self::QUERY_ID . '.categoryCntClean', $searchVo) > 0) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 카테고리 상품수
     *
     * @param GoodsSearchVo $searchVo
     * @param integer $limit
     * @param integer $offset
     * @return \Vo\TreeVo[]
     */
    public function GetColorIconTreeList(TreeSearchVo $searchVo, $limit = 10, $offset = 0)
    {
        return parent::ExecuteSqlId(self::QUERY_ID . '.iconColorTreelist', $searchVo, $limit, $offset);
    }
}

?>