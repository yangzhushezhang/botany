<?php


namespace App\Process;


use App\Model\AccountNumberModel;
use App\Model\FarmModel;
use App\Tools\Tools;
use EasySwoole\Component\Process\AbstractProcess;
use EasySwoole\ORM\DbManager;

/**
 * Class PlantSeedProcess
 * @package App\Process
 *
 * 种植种子的 进程
 */
class PlantSeedProcess extends AbstractProcess
{

    /**
     * @param $arg
     * @return mixed
     */
    protected function run($arg)
    {
        var_dump("播种进程");
        go(function () {
            while (true) {

                try {
                    \EasySwoole\RedisPool\RedisPool::invoke(function (\EasySwoole\Redis\Redis $redis) {
                        $id = $redis->rPop("Seed_Fruit");
                        if ($id) {
                            DbManager::getInstance()->invoke(function ($client) use ($id, $redis) {
                                $id_array = explode('@', $id);
                                if (count($id_array) == 3) {     # account_number_id  种子类型 user_id
                                    # 属于那个 账号
                                    $one = AccountNumberModel::invoke($client)->get(['id' => $id_array[0]]);
                                    if (!$one) {
                                        Tools::WriteLogger($id_array[2], 2, "进程 PlantSeedProcess  账户不存在 ", $id_array[0], 2);
                                        return false;
                                    }
                                    # 种子的 个数 是否 够
                                    if ($id_array[1] == 1) {
                                        if ($one['all_sapling'] < 1) {
                                            Tools::WriteLogger($id_array[2], 2, "进程 PlantSeedProcess  没有树苗可以种植了! ", $id_array[0], 2);
                                            return false;
                                        }
                                    } else if ($id_array[1] == 2) {
                                        if ($one['all_sunflower'] < 1) {
                                            Tools::WriteLogger($id_array[2], 2, "进程 PlantSeedProcess  没有向日葵可以种植了! ", $id_array[0], 2);
                                            return false;
                                        }
                                    }


                                    $client_http = new \EasySwoole\HttpClient\HttpClient('https://backend-farm.plantvsundead.com/farms');
                                    $headers = array(
                                        'authority' => 'backend-farm.plantvsundead.com',
                                        'sec-ch-ua' => '"Google Chrome";v="93", " Not;A Brand";v="99", "Chromium";v="93"',
                                        'accept' => 'application/json, text/plain, */*',
                                        'content-type' => 'application/json;charset=UTF-8',
                                        'authorization' => $one['token_value'],
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
                                    $data = '{"landId":0,"sunflowerId":' . $id_array[1] . '}';
                                    $response = $client_http->post($data);
                                    $result = $response->getBody();
                                    $data = json_decode($result, true);


                                    if (!$data) {
                                        # 解析失败 收获失败
                                        # 重新种植 10秒后
                                        \EasySwoole\Component\Timer::getInstance()->after(10 * 1000, function () use ($id, $redis) {
                                            $redis->rPush("Seed_Fruit", $id);  # account_number_id  种子类型 user_id
                                        });

                                        Tools::WriteLogger($id_array[2], 2, "进程 PlantSeedProcess  种植失败 json解析失败: result" . $result, $id_array[0], 2);
                                        return false;
                                    }

                                    if ($data['status'] != 0) {
                                        Tools::WriteLogger($id_array[2], 2, "进程 PlantSeedProcess  种植失败 : result" . $result, $id_array[0], 2);
                                        return false;
                                    }


                                    //   var_dump("种植成功");
                                    $add = [
                                        'account_number_id' => $id_array[0],
                                        'farm_id' => $data['data']['_id'],
                                        'harvestTime' => 0,
                                        'needWater' => 2,
                                        'hasSeed' => 2,
                                        'plant_type' => $id_array[1],
                                        'updated_at' => time(),
                                        'stage' => $data['data']['stage'],
                                        'created_at' => time(),
                                        'status' => 1,
                                        'remove' => 1
                                    ];
                                    $res = FarmModel::invoke($client)->data($add)->save();
                                    if (!$res) {
                                        Tools::WriteLogger($id_array[2], 2, "进程 PlantSeedProcess  插入数据失败 种子id: " . $add['farm_id'], $id_array[0], 2);
                                    }
                                    Tools::WriteLogger($id_array[2], 1, "账户id:" . $id_array[0] . " PlantSeedProcess 种植成功 种子:" . $result);
                                    #需要 去放小盆
                                    $redis->rPush("PutPot", $res . "@" . $id_array[0] . "@" . $one['user_id']);


                                }
                            });

                        }
                    }, 'redis');
                    \co::sleep(5); # 五秒循环一次
                }catch (\Throwable $exception){
                    Tools::WriteLogger(0, 2, "PlantSeedProcess 进程 异常:" . $exception->getMessage(), "", 5);

                }

            }

        });
    }

    protected function onException(\Throwable $throwable, ...$args)
    {
        Tools::WriteLogger(0, 2, "进程 PlantSeedProcess 异常:" . $throwable->getMessage());
        parent::onException($throwable, $args); // TODO: Change the autogenerated stub
    }

    protected function onShutDown()
    {
        Tools::WriteLogger(0, 2, "进程 PlantSeedProcess  onShutDown");
        parent::onShutDown(); // TODO: Change the autogenerated stub
    }
}