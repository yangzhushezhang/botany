<?php


namespace App\Process;

use App\Model\AccountNumberModel;
use App\Model\FarmModel;
use App\Tools\Tools;
use EasySwoole\Component\Process\AbstractProcess;
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

                \EasySwoole\RedisPool\RedisPool::invoke(function (\EasySwoole\Redis\Redis $redis) {

                    $id = $redis->rPop("Harvest_Fruit");
                    $id_array = explode("@", $id);  # farm_id   account_number_id user_id
                    if (count($id_array) == 3) {  # 数组长度为2
                        if ($id) {
                            DbManager::getInstance()->invoke(function ($client) use ($id_array, $redis,$id) {
                                # 获取这个  农作物的详情
                                $one = FarmModel::invoke($client)->get(['id' => $id_array[0]]);
                                $two = AccountNumberModel::invoke($client)->get(['id' => $id_array[1]]);
                                if (!$one || !$two) {
                                    Tools::WriteLogger($id_array[2], 2, "HarvestFruitProcess 账户id:" . $id_array[1] . "不存在 ",$id_array[1],8);
                                    return false;
                                }

                                if ($one['status'] != 1) {
                                    # 说明这个种子已经 收获过了
                                    Tools::WriteLogger($id_array[2], 2, "HarvestFruitProcess 账户id:" . $id_array[1] . " 不要重复收获种子id:" . $one['farm_id'],$id_array[1],8);
                                    return false;
                                }

                                #准备去收获 种子
                                $client_http = new \EasySwoole\HttpClient\HttpClient('https://backend-farm.plantvsundead.com/farms/' . $one['farm_id'] . '/harvest');
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
//                                var_dump($result);
                                $data = json_decode($result, true);
                                if (!$data) {
                                    # 解析失败 收获失败
                                    \EasySwoole\Component\Timer::getInstance()->after(10 * 1000, function () use ($id, $redis) {
                                        $redis->rPush("Harvest_Fruit", $id);  # account_number_id  种子类型 user_id
                                    });

                                    Tools::WriteLogger($id_array[2], 2, "账户id:" . $id_array[1] . " 种子id:" . $one['farm_id'] . "收获失败.....json解析失败",$id_array[1],8);
                                    return false;
                                }

                                if ($data['status'] != 0) {
                                    Tools::WriteLogger($id_array[2], 2, "账户id:" . $id_array[1] . " 种子id:" . $one['farm_id'] . "收获失败....." . $result,$id_array[1],8);
                                    return false;
                                }

                                $old = $two['leWallet'];
                                $new = $old + $data['data']['amount'];
                                AccountNumberModel::invoke($client)->where(['id' => $id_array[1]])->update(['leWallet' => $new]);
                                # 收获成功
                                FarmModel::invoke($client)->where(['id' => $id_array[0]])->update(['status' => 2, 'updated_at' => time()]);
                                Tools::WriteLogger($id_array[2], 1, "账户id:" . $id_array[1] . " 种子id:" . $one['farm_id'] . "收获成功....." . $result . "获取能量值:" . $data['data']['amount'],$id_array[1],8);


                                # 收获成功 需要去 铲除 废物  交给 后勤去处理 这件事
                                $redis->rPush("RemoveSeed", $id_array[0] . "@" . $id_array[1] . "@" . $id_array[2]); #farm_id   account_number_id user_id

                            });
                        }
                    }


                }, 'redis');
                \co::sleep(5); # 五秒循环一次

            }
        });

    }

}