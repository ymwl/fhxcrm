<?php
/**
 * 合同回款相关状态常量定义
 * 
 * 使用示例：
 *   ContractStatusConst::IN_PROGRESS  // 进行中 (0)
 *   ContractStatusConst::getText(1)  // "已完成"
 *   ContractStatusConst::getList()   // ['0'=>'进行中', '1'=>'已完成', '-1'=>'已作废']
 */

namespace app\service;

class CrmContractReceivablesService
{
    // ========== 合同回款状态 ==========
    const IN_PROGRESS = 0;    // 进行中
    const COMPLETED = 1;      // 已完成
    const VOIDED = -1;        // 已作废

    public static function getContractStatus($status=''){
//        0 进行中 1 已完成  -1 已作废
        $status_text=['0'=>'进行中','1'=>'已完成','-1'=>'已作废'];
        if($status==''){
            return $status_text;
        }
        return $status_text[$status]?:'未知状态';
    }

    public static function getCheckStatus($status=''){
//        -1审核未通过0待审核、1草稿、2审核中、3审核通过
//        $status_text=['-1'=>'审核未通过','0'=>'待审核','1'=>'草稿','2'=>'审核中','3'=>'审核通过'];
        $status_text= ['-1'=>'审核未通过','0'=>'待审核','3'=>'审核通过'];
        if($status==''){
            return $status_text;
        }
        return $status_text[$status]?:'未知状态';
    }
//    返回可以编辑合同回款状态
    public static function getEditStatus()
    {
        return ['-1','1'];
    }

    /**
     * 获取状态对应的提示信息（用于业务逻辑判断）
     * @param int $status 状态值
     * @return string 提示信息
     */
    public static function getMsg($status)
    {
        $msgList = [
            self::IN_PROGRESS => '该合同回款正在进行中',
            self::COMPLETED   => '该合同回款已完成',
            self::VOIDED      => '该合同回款已作废',
        ];
        return $msgList[$status] ?? '该合同回款状态未知';
    }
}
