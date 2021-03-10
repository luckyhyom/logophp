<?php

/**
 * Project:     Kbmall Project
 * File:        libs/Service/LogomondoPolicyService.class.php
 *
 * @link http://hanbiz.kr/
 * @author Kim Jong-gab <outmind0@naver.com>
 * @version 1.0
 * @since 1.0
 * @copyright 2001-2017 Hanbiz, Inc.
 * @package Kbmall
 */
namespace Service;

use Vo\CodeVo;
use Vo\DisplayMainVo;
use Vo\LogomondoSubmenuVo;
use Vo\RequestVo;

/**
 * 정책 서비스
 */
class LogomondoPolicyService extends PolicyService
{

    /**
     * 몰 서버 메뉴 정보 설정
     *
     * @param DisplayMainVo $policyDataVo
     * @param RequestVo $request
     * @return mixed
     */
    public function GetSubmenuMallConfParse(RequestVo $submenuRequest = null, DisplayMainVo $policyDataVo = null)
    {
        if ($submenuRequest != null) {
            if (empty($policyDataVo -> submenuMallConf) || !($policyDataVo -> submenuMallConf instanceof LogomondoSubmenuVo)) {
                $policyDataVo -> submenuMallConf = new LogomondoSubmenuVo();
            }
            /**
             * @var LogomondoSubmenuVo $submenuMallConf
             */
            $submenuMallConf = $policyDataVo -> submenuMallConf;
            $submenuRequest -> GetFill($submenuMallConf);
            if ($submenuRequest -> hasKey("diamondGemsImg")) {
                $submenuMallConf -> diamondGemsImg = $this->GetUploadFile($submenuRequest -> diamondGemsImg, '', 'submenu-img');
            }
            if ($submenuRequest -> hasKey("engagementImg")) {
                $submenuMallConf -> engagementImg = $this->GetUploadFile($submenuRequest -> engagementImg, '', 'submenu-img');
            }
            if ($submenuRequest -> hasKey("weddingImg")) {
                $submenuMallConf -> weddingImg = $this->GetUploadFile($submenuRequest -> weddingImg, '', 'submenu-img');
            }
            if ($submenuRequest -> hasKey("jewelryImg")) {
                $submenuMallConf -> jewelryImg = $this->GetUploadFile($submenuRequest -> jewelryImg, '', 'submenu-img');
            }
            if ($submenuRequest -> hasKey("giftImg")) {
                $submenuMallConf -> giftImg = $this->GetUploadFile($submenuRequest -> giftImg, '', 'submenu-img');
            }
            if (!empty($policyDataVo)) {
                if ($submenuRequest->hasKey('topCategory')) {
                    $policyDataVo-> topCategory = $submenuMallConf->topCategory = $submenuRequest->GetItemArray('topCategory', new CodeVo());
                }
                $policyDataVo->submenuMallConf = $submenuMallConf;
            }
            return $submenuMallConf;
        } else {
            return null;
        }
    }
    
}

?>