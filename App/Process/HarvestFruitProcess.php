<?php


namespace App\Process;

use App\Model\AccountNumberModel;
use App\Model\FarmModel;
use App\Tools\Tools;
use EasySwoole\Component\Process\AbstractProcess;
use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\DbManager;

/**
 * Class HarvestFruitProcess
 * @package App\Process
 *  收获果实
 */
class HarvestFruitProcess extends AbstractProcess
{


    protected function run($arg)
    {
        var_dump("种子收获进程开启..");
        go(function () {
            while (true) {
                try {
                    \EasySwoole\RedisPool\RedisPool::invoke(function (\EasySwoole\Redis\Redis $redis) {
                        $id = $redis->rPop("Harvest_Fruit");
                        $id_array = explode("@", $id);  # farm_id   account_number_id user_id
                        if (count($id_array) > 2) {  # 数组长度为2
                            if ($id) {
                                DbManager::getInstance()->invoke(function ($client) use ($id_array, $redis, $id) {
                                    # 获取这个  农作物的详情
                                    $one = FarmModel::invoke($client)->get(['id' => $id_array[0]]);
                                    $two = AccountNumberModel::invoke($client)->get(['id' => $id_array[1]]);
                                    if (!$one || !$two) {
                                        Tools::WriteLogger($id_array[2], 2, "进程 HarvestFruitProcess 账户不存在 ", $id_array[1], 8);
                                        return false;
                                    }
                                    if ($one['status'] != 1 && count($id_array) == 3) {  #普通种子才可以这样
                                        # 说明这个种子已经 收获过了
                                        Tools::WriteLogger($id_array[2], 2, "进程 HarvestFruitProcess 丰收失败,请不要重复摘取果实", $id_array[1], 8, $one['farm_id']);
                                        return false;
                                    }
                                    #准备去收获 种子
                                    $client_http = new \EasySwoole\HttpClient\HttpClient('https://backend-farm.plantvsundead.com/farms/' . $one['farm_id'] . '/harvest');
                                    $client_http->setTimeout(5);
                                    $client_http->setConnectTimeout(10);
                                    $headers = array(
                                        'authority' => 'backend-farm.plantvsundead.com',
                                        'sec-ch-ua' => '"Google Chrome";v="93", " Not;A Brand";v="99", "Chromium";v="93"',
                                        'accept' => 'application/json, text/plain, */*',
                                        'content-type' => 'application/json;charset=UTF-8',
                                        'authorization' => $two['token_value'],
                                        'sec-ch-ua-mobile' => '?0',
                                        'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/93.0.4577.82 Safari/537.36',
                                        'sec-ch-ua-platform' => '"Windows"',
                                        'origin' => 'https://marketplace.plantvsundead.com',
                                        'sec-fetch-site' => 'same-site',
                                        'sec-fetch-mode' => 'cors',
                                        'sec-fetch-dest' => 'empty',
                                        'referer' => 'https://marketplace.plantvsundead.com/',
                                        'accept-language' => 'zh-CN,zh;q=0.9',
                                    );
                                    $client_http->setHeaders($headers, false, false);
                                    $data = '{}';
                                    $response = $client_http->post($data);
                                    $result = $response->getBody();
                                    $data = json_decode($result, true);
                                    if (!$data) {
                                        \EasySwoole\Component\Timer::getInstance()->after(15 * 1000, function () use ($id, $redis) {
                                            $redis->rPush("Harvest_Fruit", $id);  # account_number_id  种子类型 user_id
                                        });
                                        Tools::WriteLogger($id_array[2], 2, "进程 HarvestFruitProcess 丰收失败 ,返回数据失败,15后重新摘取!", $id_array[1], 8, $one['farm_id']);
                                        return false;
                                    }
                                    if ($data['status'] != 0) {
                                        if ($data['status'] == 556) {
                                            #判断是否已经在处理验证码了!
                                            $IfDoingVerification = $redis->get("IfDoingVerification");
                                            if (!$IfDoingVerification) {
                                                #  不存在 就去处理
                                                $redis->rPush("DecryptCaptcha", $id_array[1] . "@" . $id_array[2]);
                                                $redis->set("IfDoingVerification", 1, 600);# 10分钟
                                            }
                                            \EasySwoole\Component\Timer::getInstance()->after(120 * 1000, function () use ($id, $redis) {
                                                $redis->rPush("Harvest_Fruit", $id);  # 赶乌鸦  2分钟
                                            });
                                            Tools::WriteLogger($id_array[2], 2, "进程 HarvestFruitProcess 账号出现验证,丰收失败,2分钟后重试" . $response, $id_array[1], 9, $one['farm_id']);
                                            return false;
                                        }
                                        Tools::WriteLogger($id_array[2], 2, "进程 HarvestFruitProcess  丰收失败,返回数据状态错误,result:" . $result, $id_array[1], 8, $one['farm_id']);
                                        return false;
                                    }
                                    # 修改数据的能量值
                                    $old = $two['leWallet'];
                                    $new = $old + $data['data']['amount'];
                                    # 收获成功
                                    FarmModel::invoke($client)->where(['id' => $id_array[0]])->update(['status' => 2, 'updated_at' => time(), 'harvest_times' => QueryBuilder::inc(1)]);
                                    Tools::WriteLogger($id_array[2], 1, "进程 HarvestFruitProcess 丰收成功,能量值:+" . $data['data']['amount'], $id_array[1], 8, $one['farm_id']);
                                    # 收获成功 需要去 铲除 废物  交给 后勤去处理 这件事
                                    if (count($id_array) == 3) {  #  4 是特殊种子
                                        $redis->rPush("RemoveSeed", $id_array[0] . "@" . $id_array[1] . "@" . $id_array[2]); #farm_id   account_number_id user_id
                                        Tools::WriteLogger($id_array[2], 1, "进程 HarvestFruitProcess 收获完毕,将种子推送到 RemoveSeedProcess 进行处理", $id_array[1], 8, $one['farm_id']);
                                    } else {
                                        # 这边是 特殊种子 不可以铲除
                                        Tools::WriteLogger($id_array[2], 1, "进程 HarvestFruitProcess 收获完毕,该种子是特殊种子,不需要将其铲除", $id_array[1], 8, $one['farm_id']);
                                    }
                                });
                            }
                        }
                    }, 'redis');
                    \co::sleep(5); # 五秒循环一次
                } catch (\Throwable $exception) {
                    Tools::WriteLogger(0, 2, "HarvestFruitProcess 进程 异常:" . $exception->getMessage(), "", 8);

                }


            }
        });

    }


    protected function onException(\Throwable $throwable, ...$args)
    {
        Tools::WriteLogger(0, 2, "进程 HarvestFruitProcess 异常:" . $throwable->getMessage());
        parent::onException($throwable, $args); // TODO: Change the autogenerated stub
    }


    protected function onShutDown()
    {
        Tools::WriteLogger(0, 2, "进程 HarvestFruitProcess  onShutDown");
        parent::onShutDown(); // TODO: Change the autogenerated stub
    }
}