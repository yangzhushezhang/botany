<?php


namespace App\HttpController\User;


use App\Model\AccountNumberModel;
use App\Model\SpecialSeedModel;
use EasySwoole\ORM\DbManager;
use EasySwoole\ORM\Exception\Exception;
use EasySwoole\Pool\Exception\PoolEmpty;

class SpecialSeedController extends UserBase
{

    #  获取等待孵化了
    function getWaitingToHatch()
    {
        try {
            DbManager::getInstance()->invoke(function ($client) {
                $res = AccountNumberModel::invoke($client)->all(['user_id' => $this->who['id'], 'claimSeeds' => 2]);
                $this->writeJson(200, $res, "获取成功");
                return true;
            });
        } catch (\Throwable $e) {
            $this->writeJson(-1, [], "获取异常");
        }

    }


    # 获取正在孵化的
    function getDoingToHatch()
    {
        try {
            $status = $this->request()->getParsedBody('status');
            DbManager::getInstance()->invoke(function ($client) use ($status) {
                $res = SpecialSeedModel::invoke($client)->all(['status' => $status]);
                if ($res) {
                    foreach ($res as $k => $re) {
                        $one = AccountNumberModel::invoke($client)->get(['id' => $re['account_number_id']]);
                        if ($one) {
                            $res[$k]['remark'] = $one['remark'];
                        }
                    }
                }


                $this->writeJson(200, $res, "获取成功");
                return true;
            });
        } catch (\Throwable $e) {
            var_dump($e->getMessage());
            $this->writeJson(-1, [], "获取异常:" . $e->getMessage());
        }
    }


}