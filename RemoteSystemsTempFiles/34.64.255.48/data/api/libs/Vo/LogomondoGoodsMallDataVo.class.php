<?php

/**
 * Project:	KBMALL 1.0 Project
 * File:	libs/Vo/LogomondoGoodsMallDataVo.class.php
 * kbm_display_theme(테마 정보) Table Vo
 * 
 * @link http://www.hanbiz.kr/
 * @author Kim Jong-gab <outmind0@naver.com>
 * @version 1.0
 * @since 1.0
 * @copyright 2001-2017 Hanbiz, Inc.
 * @package kbmall
 */
namespace Vo;

/**
 * 로고몬도 상품 추가 정보
 */
class LogomondoGoodsMallDataVo
{

    /**
     * 조절반지 여부
     *
     * @var string
     */
    public $freeSizeRingYn = "N";

    /**
     * 완제품 여부
     *
     * @var string
     */
    public $readyMadeYn = "N";

    /**
     * 제조일
     *
     * @var string
     */
    public $makeYmd = "";

    /**
     * 출시일
     *
     * @var string
     */
    public $launchYmd = "";

    /**
     * 제작기간
     *
     * @var string
     */
    public $makeDay = 0;

    /**
     * 상품 형태
     * G : 잼스톤
     * R : 반지
     * N : 목걸이
     * B : 팔찌
     * E : 귀걸이
     *
     * @var string
     */
    public $goodsType = "R";

    /**
     * 성별 F : 여자, M : 남자
     *
     * @var string[]
     */
    public $goodsGender = [];
    
    /**
     * 귀걸이/반지/팔찌/목걸이 스타일
     *
     * @var string[]
     */
    public $goodsStyle = [];
    
    /**
     * 보조 소재
     *
     * @var string[]
     */
    public $subMatter = [];
    
    /**
     * 보조 소재 기타
     *
     * @var string
     */
    public $subMatterOthers = "";
    
    /**
     * 세팅 스타일
     *
     * @var string[]
     */
    public $settingStyle = [];
    
    /**
     * 귀걸이/반지/팔찌/목걸이 잠금 스타일
     *
     * @var string[]
     */
    public $goodsLock = [];
    
    /**
     * 자체 제작 상품 여부
     *
     * @var string
     */
    public $makerSelf = "N";

    /**
     * 제조사
     *
     * @var string
     */
    public $makerNm = "";

    /**
     * 메탈 무게
     *
     * @var double
     */
    public $metalWeight = 0.0;
    
    /**
     * 메탈 함량
     * 
     * 효민
     *
     * @var string
     */
    public $metalPurity = "";
    
    

    /**
     * 스톤 색상
     *
     * @var string
     */
    public $stoneColorOne = "";

    /**
     * 스톤 형태
     *
     * @var string
     */
    public $stoneTypeOne = "";

    /**
     * 스톤 쉐입
     *
     * @var string
     */
    public $stoneShapeOne = "";

    /**
     * 스톤 크기
     *
     * @var string
     */
    public $stoneSizeOne = "";

    /**
     * 상품 대표 메탈 색상
     *
     * @var string[]
     */
    public $metalColor = [];

    /**
     * 상품 대표 메탈
     *
     * @var string[]
     */
    public $metalType = [];

    /**
     * 메탈 도금 타입 (Silver 925(Sterling silver) 의 경우)
     * P : Plated, F : Filled
     *
     * @var string
     */
    public $metalPlating = "P";

    /**
     * 상품 대표 스톤 색상
     *
     * @var string[]
     */
    public $stoneColor = [];

    /**
     * 상품 대표 스톤
     *
     * @var string[]
     */
    public $stoneType = [];

    /**
     * 상품 대표 쉐입
     *
     * @var string[]
     */
    public $stoneShape = [];

    /**
     * 3D 모델 착용 사용(3d 파일만 가능)
     *
     * @var string
     */
    public $useModel3dYn = "Y";

    /**
     * 감정서 유무
     * X : 해당 없음
     * N : 없음
     * Y : 있음
     * S : 직접입력
     *
     * @var string
     */
    public $certificationYn = "Y";

    /**
     * 감정 기관
     * AGI, AGS, GCAL, GIA, IGI, WOOSHIN, HYUNDAE
     *
     * @var string
     */
    public $certificationType = "";

    /**
     * 감정 기관 직접 입력
     *
     * @var string
     */
    public $certificationTypeOther = "";

    /**
     * 보증서 유무
     * X : 해당 없음
     * N : 없음
     * Y : 있음
     * S : 직접입력
     *
     * @var string
     */
    public $guaranteeYn = "Y";

    /**
     * 보증서 직접 입력
     *
     * @var string
     */
    public $guaranteeTypeOther = "";

    /**
     * 보증 기간
     * X : 해당 없음
     * M : 1년 미만
     * 1Y : 1년
     * 2Y : 2년
     * 5Y : 5년
     * 10Y : 10년
     * Y : 기타(10년이상)
     *
     * @var string
     */
    public $guaranteeTerm = "X";

    /**
     * 기타 보증 기간
     *
     * @var integer
     */
    public $guaranteeTermOther = 0;

    /**
     * 주문 확인후 발송 예정일
     *
     * @var integer
     */
    public $deliveryDay = 0;

    /**
     * 발송지 주소 판매자 주소 동일 여부
     *
     * @var string
     */
    public $sendAddressSame = "Y";

    /**
     * 발송 관련 지사
     *
     * @var string
     */
    public $sendBranch = "";
    
    /**
     * 발송지 주소
     *
     * @var AddressVo
     */
    public $sendAddress = null;
    
    /**
     * 방문 수령지 주소
     *
     * @var LogomondoVisitAddressVo[]
     */
    public $visitAddress = [];

    /**
     * 반품 교환지 판매자 주소 동일 여부
     *
     * @var string
     */
    public $returnAddressSame = "Y";

    /**
     * 반품 교환 관련 지사
     *
     * @var string
     */
    public $returnBranch = "";
    
    /**
     * 반품 교환지 기타
     *
     * @var AddressVo
     */
    public $returnAddress = null;

    /**
     * 스톤 속성
     *
     * @var string
     */
    public $stoneAttrName = "";

    /**
     * 스톤 속성
     *
     * @var double
     */
    public $stoneAttrWidth = 0.0;

    /**
     * 스톤 속성
     *
     * @var double
     */
    public $stoneAttrHeight = 0.0;
    
    /**
     * 스톤 속성
     *
     * @var double
     */
    public $stoneAttrDepth = 0.0;

    /**
     * 스톤 속성
     *
     * @var double
     */
    public $stoneAttrWeight = 0.0;

    /**
     * 스톤 속성
     *
     * @var string
     */
    public $stoneAttrInfo = "";

    /**
     * 스톤 속성
     *
     * @var string
     */
    public $stoneAttrDiamondCut = "";

    /**
     * 스톤 속성
     *
     * @var string
     */
    public $stoneAttrDiamondColor = "";

    /**
     * 스톤 속성
     *
     * @var string
     */
    public $stoneAttrDiamondClarity = "";
    
    /**
     * 스톤 속성
     *
     * @var string
     */
    public $stoneAttrDiamondFluorescence = "";

    /**
     * 스톤 속성
     *
     * @var string
     */
    public $stoneAttrColoredDiamondCut = "";

    /**
     * 스톤 속성
     *
     * @var string
     */
    public $stoneAttrColoredDiamondColor = "";

    /**
     * 스톤 속성
     *
     * @var string
     */
    public $stoneAttrColoredDiamondClarity = "";
    
    /**
     * 스톤 속성
     *
     * @var string
     */
    public $stoneAttrColoredDiamondFluorescence = "";

    /**
     * 스톤 속성
     *
     * @var string
     */
    public $stoneAttrGemstoneColor = "";

    /**
     * 스톤 속성
     *
     * @var string
     */
    public $stoneAttrGemstoneClarity = "";

    /**
     * 스톤 속성
     *
     * @var string
     */
    public $stoneAttrPearlLuster = "";

    /**
     * 스톤 속성
     *
     * @var string
     */
    public $stoneAttrPearlBlemish = "";

    /**
     * 스톤 속성
     *
     * @var string
     */
    public $stoneAttrPearlGrade = "";

    /**
     * 스톤 속성
     *
     * @var string
     */
    public $stoneAttrPearlDrilling = "";

    /**
     * 스톤 속성
     *
     * @var string
     */
    public $stoneAttrOrigin = "";

    /**
     * 배송 방법 P : 택배, V : 방문 수령
     *
     * @var string[]
     */
    public $deliveryMethod = [];

    /**
     * 배송방법 택배 추가 배송비
     *
     * @var integer
     */
    public $deliveryPriceParcel = 0;

    /**
     * 배송방법 방문수령 추가 배송비
     *
     * @var integer
     */
    public $deliveryPriceVisit = 0;

    /**
     * 발송 예정일 방법
     *
     * @var string
     */
    public $deliveryDayMethod = "";

    /**
     * 검색태그
     *
     * @var string
     */
    public $searchTags = "";

    /**
     * 필수 옵션
     *
     * @var OptionTreeVo[]
     */
    public $options = [];

    /**
     * 선택 옵션
     *
     * @var OptionTreeVo[]
     */
    public $optionsExt = [];

    /**
     * 텍스트 옵션
     *
     * @var OptionTreeVo[]
     */
    public $optionsText = [];
    
    /**
     * 상품 가로 길이
     * 
     * @var number
     */
    public $goodsWidth = 0.0;
    
    /**
     * 상품 세로 길이
     *  
     * @var number
     */
    public $goodsHeight = 0.0;
    
    /**
     * 상품 높이 길이
     * 
     * @var number
     */
    public $goodsLength = 0.0;
    
    /**
     * 제품에 세팅된 스톤 
     * 
     * @var LogomondoGoodsStoneInfo[]
     */
    public $settingStoneInfo = [];
    
    /**
     * 다른 판매자의 스톤 세팅 허용 유무 
     * 
     * @var boolean
     */
    public $otherSellerStoneSettingYn = false;
    
    /**
     * 다른 판매자의 스톤 세팅 0.1ct 미만 세팅비 
     * 
     * @var number
     */
    public $otherSellerStoneSettingDownPrice = 0.0;
    
    /**
     * 다른 판매자의 스톤 세팅 0.1ct 이상 세팅비 
     * 
     * @var number
     */
    public $otherSellerStoneSettingUpPrice = 0.0;
}

?>