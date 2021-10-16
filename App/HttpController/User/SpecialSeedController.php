<?php


namespace App\HttpController\User;


use App\Model\AccountNumberModel;
use App\Model\SpecialSeedModel;
use EasySwoole\ORM\DbManager;
use EasySwoole\ORM\Exception\Exception;
use EasySwoole\Pool\Exception\PoolEmpty;
use EasySwoole\RedisPool\RedisPool;

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
            $growthTime = $this->request()->getParsedBody('growthTime');
            DbManager::getInstance()->invoke(function ($client) use ($status) {
                if (isset($status)) {
                    $res = SpecialSeedModel::invoke($client)->all(['status' => $status]);
                } else {
                    $res = SpecialSeedModel::invoke($client)->all();
                }
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


    #是否要种植
    function IfPlanted()
    {
        try {
            $id = $this->request()->getRequestParam('id');
            $status = $this->request()->getRequestParam('status'); #0未种植  2 以种植  3让他准备种植
            if (!$this->check_parameter($id, "id") ) {
                return false;
            }
            DbManager::getInstance()->invoke(function ($client) use ($id, $status) {
                $one = SpecialSeedModel::invoke($client)->get(['id' => $id]);
                if (!$one) {
                    $this->writeJson(-101, [], "此种子不存在!");
                    return false;
                }

                if ($one['status'] != 0 && $one['status'] != 3) {
                    $this->writeJson(-101, [], "此种状态不对!");
                    return false;
                }

                $two = SpecialSeedModel::invoke($client)->where(['id' => $id])->update(['updated_at' => time(), 'status' => $status]);
                if (!$two) {
                    $this->writeJson(-101, [], "修改失败!");
                    return false;
                }

                $redis = RedisPool::defer('redis');
                $redis->hSet("SpecialSeed_" . $one['account_number_id'], $one['id'], $status);
                $redis->hSet("SpecialSeed_" . $one['account_number_id'], 'value', "value");

                $this->writeJson(200, [], "修改成功!");
                return true;

            });
        } catch (\Throwable $e) {
            $this->writeJson(-1, [], "异常:" . $e->getMessage());
            return false;
        }
    }


}