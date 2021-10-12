<?php


namespace App\Process;


use App\Model\AccountNumberModel;
use App\Model\FarmModel;
use App\Tools\Tools;
use Cassandra\Date;
use EasySwoole\Component\Process\AbstractProcess;
use EasySwoole\ORM\DbManager;
use EasySwoole\RedisPool\RedisPool;


/**
 * Class MonitorFarmProcess
 * @package App\Process
 *   检测 进程
 *
 *  该进程 负责 监听  乌鸦  收获 铲除
 */
class MonitorFarmProcess extends AbstractProcess
{

    protected function run($arg)
    {
        var_dump("检测进程开启");
        go(function () {
            $time = 0;
            while (true) {
                # 遍历所有的
                try {
                    DbManager::getInstance()->invoke(function ($client) {
                        # 查询所有 账户
                        $res = AccountNumberModel::invoke($client)->all(['status' => 1]);
                        if ($res) {
                            $success = 0;
                            Tools::WriteLogger(0, 2, "进程 MonitorFarmProcess 开始,本次检查的账号 总共有 " . count($res) . "个", "", 11);
                            foreach ($res as $k => $re) {
                                $data = $this->GetFarms($re['token_value'], $re['user_id'], $re['id']);
                                if ($data) {
                                    $if_add_new = false;
                                    $if_sunflowerId_2 = false;
                                    if (isset($data['total']) && $data['total'] != 6) {
                                        $if_add_new = true;
                                    }
                                    foreach ($data['data'] as $value) {  #对数据进行遍历
                                        if (isset($value['plant']['sunflowerId']) && $value['plant']['sunflowerId'] == 2) {
                                            $if_sunflowerId_2 = true;
                                        }
                                        # 判断 农场 没有有 这个 种子 id
                                        $one = FarmModel::invoke($client)->get(['account_number_id' => $re['id'], 'farm_id' => $value['_id']]);
                                        $harvestTime = 0;
                                        if (isset($value['harvestTime'])) {
                                            $unix = str_replace(array('T', 'Z'), ' ', $value['harvestTime']);
                                            $harvestTime = strtotime($unix) + 8 * 60 * 60;
                                        }
                                        if ($value['stage'] == "new") { #放花盆
                                            if (isset($one['id'])) {
                                                $redis = RedisPool::defer('redis');
                                                $redis->rPush("PutPot", $one['id'] . "@" . $one['account_number_id'] . "@" . $re['user_id']); #
                                                Tools::WriteLogger($re['user_id'], 1, "进程 MonitorFarmProcess  种子需要花盆,将其推入PutPotProcess 进程", $re['id'], 11, $value['_id']);
                                            } else {
                                                Tools::WriteLogger($re['user_id'], 1, "进程 MonitorFarmProcess  种子需要花盆,将其推入PutPotProcess 进程 失败,因为改种子还没有入库", $re['id'], 11, $value['_id']);
                                            }

                                        }
                                        $plantId = "";
                                        $iconUrl = "";
                                        if (isset($value['plantId']) && $value['plantId'] != 0) {
                                            $plantId = $value['plantId'];
                                            $iconUrl = $value['plant']['iconUrl'];
                                            if (isset($value['totalHarvest']) && $value['totalHarvest'] > 0) { # 特殊种子成熟
                                                $redis = RedisPool::defer("redis");
                                                $redis->rPush("Harvest_Fruit", $one['id'] . "@" . $one['account_number_id'] . "@" . $re['user_id'] . "@" . "999");  #种子的 id 种子的  账户id
                                                Tools::WriteLogger($re['user_id'], 1, "进程 MonitorFarmProcess  种子已经成熟,将其推入HarvestFruitProcess 进程", $re['id'], 11, $value['_id']);
                                            }
                                        }

                                        if ($value['stage'] == "cancelled") {
                                            # 判断种子 是否可以 收获
                                            if (isset($value['plantId']) && $value['plantId'] != 0) {   # 特殊的种子
                                                if ($value['totalHarvest'] != 0) {
                                                    $redis = RedisPool::defer("redis");
                                                    $redis->rPush("Harvest_Fruit", $one['id'] . "@" . $one['account_number_id'] . "@" . $re['user_id'] . "@" . "999");  #种子的 id 种子的  账户id
                                                    Tools::WriteLogger($re['user_id'], 1, "进程 MonitorFarmProcess  种子已经成熟,将其推入HarvestFruitProcess 进程", $re['id'], 11, $value['_id']);
                                                }
                                            } else {
                                                if ($value['totalHarvest'] == 0) {
                                                    # 这个 已经收获过了 直接去铲除
                                                    $redis = RedisPool::defer("redis");
                                                    $redis->rPush("RemoveSeed", $one['id'] . "@" . $one['account_number_id'] . "@" . $re['user_id']);  #种子的 id 种子的  账户id
                                                    Tools::WriteLogger($re['user_id'], 1, "进程 MonitorFarmProcess  种子已经成熟,将其推入RemoveSeedProcess 进程", $re['id'], 11, $value['_id']);
                                                } else {
                                                    # 说明这个 可以去收获  直接 push  到  收获进程去
                                                    $redis = RedisPool::defer("redis");
                                                    $redis->rPush("Harvest_Fruit", $one['id'] . "@" . $one['account_number_id'] . "@" . $re['user_id']);  #种子的 id 种子的  账户id
                                                    Tools::WriteLogger($re['user_id'], 1, "进程 MonitorFarmProcess  种子已经成熟,将其推入HarvestFruitProcess 进程", $re['id'], 11, $value['_id']);
                                                }
                                            }

                                        }
                                        $needWater = 2;
                                        $hasSeed = 2;  # 暂停
                                        if ($value['stage'] == "paused") {
                                            # 这个种子的时间停止了   说明已经有乌鸦了 .我怕需要 用 稻草人去吓退乌鸦
                                            $redis = RedisPool::defer("redis");
                                            $redis->rPush("CROW_IDS", $one['id'] . "@" . $one['account_number_id'] . "@" . $re['user_id']);  #种子的 id 种子的  账户id
                                            Tools::WriteLogger($re['user_id'], 1, "进程 MonitorFarmProcess  种子停止运作了,将其推入ExpelRavenProcess 进程", $re['id'], 11, $value['_id']);
                                        }
                                        if ($value['needWater']) {
                                            # 需要浇水  让进程去做这件事情     需要给浇水的进程去
                                            $needWater = 1;
                                            #判断需要浇 几滴水
                                            if (count($value['activeTools']) == 1) { #需要浇两滴水
                                                if ($one) {
                                                    $redis = RedisPool::defer('redis');
                                                    $redis->rPush("Watering", $one['id'] . "@" . $re['id'] . "@" . $re['user_id']);  # account_number_id   user_id
                                                    Tools::WriteLogger($re['user_id'], 1, "进程 MonitorFarmProcess  需要浇水,将其推出WateringProcess 进程 First", $re['id'], 11, $value['_id']);
                                                    $farm_id = $value['_id'];
                                                    \EasySwoole\Component\Timer::getInstance()->after(10 * 6 * 30 * 1000, function () use ($one, $re, $redis, $farm_id) { # 30秒后进行
                                                        $redis->rPush("Watering", $one['id'] . "@" . $re['id'] . "@" . $re['user_id']);  # account_number_id   user_id
                                                        Tools::WriteLogger($re['user_id'], 1, "进程 MonitorFarmProcess  需要浇水,将其推出WateringProcess 进程 First", $re['id'], 11, $farm_id);
                                                    });
                                                }
                                            } else if (count($value['activeTools']) == 2) {  # 需要浇1滴水
                                                $redis = RedisPool::defer('redis');
                                                $redis->rPush("Watering", $one['id'] . "@" . $re['id'] . "@" . $re['user_id']);  # account_number_id   user_id
                                                Tools::WriteLogger($re['user_id'], 1, "进程 MonitorFarmProcess  需要浇水,将其推出WateringProcess 进程 First", $re['id'], 11, $value['_id']);
                                            } else if (count($value['activeTools']) == 3) {  #特殊种子 浇1滴水  特殊种子
                                                $redis = RedisPool::defer('redis');
                                                $redis->rPush("Watering", $one['id'] . "@" . $re['id'] . "@" . $re['user_id']);  # account_number_id   user_id
                                                Tools::WriteLogger($re['user_id'], 1, "进程 MonitorFarmProcess  需要浇水,将其推出WateringProcess 进程 First", $re['id'], 11, $value['_id']);
                                            }
                                        }
                                        if ($value['hasSeed']) {
                                            #需要 放种子
                                            $hasSeed = 1;
                                        }
                                        # 这里需要判断 有没有乌鸦    如果有乌鸦 我需要 仍在 进程里面来做这件事!!!!
                                        $add = [
                                            'account_number_id' => $re['id'],
                                            'farm_id' => $value['_id'],
                                            'harvestTime' => $harvestTime,
                                            'needWater' => $needWater,
                                            'hasSeed' => $hasSeed,
                                            'plant_type' => $value['plant']['type'],
                                            'updated_at' => time(),
                                            'stage' => $value['stage'], #paused 说明暂停 了 有乌鸦,
                                            'plantId' => $plantId,
                                            'iconUrl' => $iconUrl
                                        ];
                                        if ($one) {
                                            #存在 只需要 做更新操作
                                            $two = FarmModel::invoke($client)->where(['account_number_id' => $re['id'], 'farm_id' => $value['_id']])->update($add);
                                            if (!$two) {
                                                Tools::WriteLogger($re['user_id'], 2, "进程 MonitorFarmProcess  更新种子失败", $re['id'], 11, $value['_id']);
                                            }
                                            Tools::WriteLogger($re['user_id'], 1, "进程 MonitorFarmProcess  更新种子成功", $re['id'], 11, $value['_id']);
                                        } else {
                                            # 插入操作
                                            $add['created_at'] = time();
                                            $two = FarmModel::invoke($client)->data($add)->save();
                                            if (!$two) {
                                                Tools::WriteLogger($re['user_id'], 2, "进程 MonitorFarmProcess  插入种子失败", $re['id'], 11, $value['_id']);
                                            }
                                            Tools::WriteLogger($re['user_id'], 2, "进程 MonitorFarmProcess  插入种子成功", $re['id'], 11, $value['_id']);
                                        }
                                    }
                                    if ($if_add_new) {
                                        # 去添加 播种进程
                                        $p = 6 - $data['total'];
                                        for ($i = 0; $i < $p; $i++) {
                                            $redis = RedisPool::defer('redis');
                                            if ($i == 0 && !$if_sunflowerId_2) {
                                                # 添加向日葵
                                                var_dump("账号:" . $re['id'] . "需要种向日葵");
                                                $redis->rPush("Seed_Fruit", $re['id'] . "@" . 2 . "@" . $re['user_id']);  # account_number_id  种子类型 user_id
                                                Tools::WriteLogger($re['user_id'], 1, '进程 MonitorFarmProcess 发现需要播种向日葵 并且 把账号推入到 PlantSeedProcess进程', $re['id'], 11);
                                            } else {
                                                # 添加普通种子
                                                var_dump("账号:" . $re['id'] . "需要种向日葵宝宝");
                                                $redis->rPush("Seed_Fruit", $re['id'] . "@" . 1 . "@" . $re['user_id']);  # account_number_id  种子类型 user_id
                                                Tools::WriteLogger($re['user_id'], 1, '进程 MonitorFarmProcess 发现需要播种普通种子 并且 把账号推入到 PlantSeedProcess进程', $re['id'], 11);
                                            }
                                        }
                                    }
                                    $success++;
                                }
                                \co::sleep(3);   # 每个账号直接 休息时间是  5秒
                            }
                            $pp = count($res) - $success;
                            Tools::WriteLogger(0, 2, "进程 MonitorFarmProcess 本轮检查 成功:" . $success . "个 ,失败:" . $pp . "个", "", 11);
                        }
                    });
                    \co::sleep(5 * 60);  # 每次轮训等待5分钟
                } catch (\Throwable $exception) {
                    Tools::WriteLogger(0, 2, "进程 MonitorFarmProcess 异常:" . $exception->getMessage(), "", 11);

                }
            }
        });
    }


    /**
     * @param $token_value
     * @param $user_id
     * @param $account_number_id
     */
    function GetFarms($token_value, $user_id, $account_number_id)
    {

        try {
            for ($i = 0; $i < 5; $i++) {
                $client_http = new \EasySwoole\HttpClient\HttpClient('https://backend-farm.plantvsundead.com/farms?limit=10&offset=0');
                $headers = array(
                    'authority' => 'backend-farm.plantvsundead.com',
                    'sec-ch-ua' => '"Google Chrome";v="93", " Not;A Brand";v="99", "Chromium";v="93"',
                    'accept' => 'application/json, text/plain, */*',
                    'authorization' => $token_value,
                    'sec-ch-ua-mobile' => '?0',
                    'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/93.0.4577.82 Safari/537.36',
                    'sec-ch-ua-platform' => '"Windows"',
                    'origin' => 'https://marketplace.plantvsundead.com',
                    'sec-fetch-site' => 'same-site',
                    'sec-fetch-mode' => 'cors',
                    'sec-fetch-dest' => 'empty',
                    'referer' => 'https://marketplace.plantvsundead.com/',
                    'accept-language' => 'zh-CN,zh;q=0.9',
                    # 'if-none-match' => 'W/"1bf5-RySZLkdJ7uwQuWZ+zLfe+hxM36c"',
                );
                $client_http->setHeaders($headers, false, false);
                $client_http->setTimeout(5);
                $client_http->setConnectTimeout(10);
                $response = $client_http->get();
                $result = $response->getBody();
                $data = json_decode($result, true);
                if ($data && $data['status'] == 0) {   #返回了 0
                    Tools::WriteLogger($user_id, 2, "进程 MonitorFarmProcess 方法 GetFarms 返回数据成功", $account_number_id, 11);
                    return $data;
                }
                \co::sleep(1);
            }
            Tools::WriteLogger($user_id, 2, "进程 MonitorFarmProcess 方法 GetFarms 返回数据格式错误:", $account_number_id, 11);
            return false;
        } catch (\Throwable $exception) {
            Tools::WriteLogger($user_id, 2, "进程 MonitorFarmProcess 方法 GetFarms 异常:" . $exception->getMessage(), $account_number_id, 11);
            return false;
        }
    }

    protected function onException(\Throwable $throwable, ...$args)
    {
        Tools::WriteLogger(0, 2, "进程 MonitorFarmProcess 异常:" . $throwable->getMessage());
        parent::onException($throwable, $args); // TODO: Change the autogenerated stub
    }

    protected function onShutDown()
    {
        Tools::WriteLogger(0, 2, "进程 MonitorFarmProcess  onShutDown");
        var_dump("进程 MonitorFarmProcess  onShutDown");
        parent::onShutDown(); // TODO: Change the autogenerated stub
    }
}