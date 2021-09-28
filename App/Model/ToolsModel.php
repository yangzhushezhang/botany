<?php


namespace App\Model;


use EasySwoole\ORM\AbstractModel;
use EasySwoole\ORM\DbManager;
use EasySwoole\ORM\Exception\Exception;
use EasySwoole\Pool\Exception\PoolEmpty;


/**
 * Class ToolsModel
 * @package App\Model
 * 获取  账户的 工具
 */
class ToolsModel extends AbstractModel
{
    protected $tableName = 'tools';


    function GetMyTools($account_number_id)
    {
        try {
            return DbManager::getInstance()->invoke(function ($client) use ($account_number_id) {
                return ToolsModel::invoke($client)->get(['account_number_id' => $account_number_id]);
            });
        } catch (\Throwable $e) {
            return '';
        }
    }


}