<?php


namespace App\Process;


use App\Model\AccountNumberModel;
use App\Model\FarmModel;
use App\Tools\Tools;
use EasySwoole\Component\Process\AbstractProcess;
use EasySwoole\ORM\DbManager;

/**
 * Class RemoveSeedProcess
 * @package App\Process
 * 进程  移除 种子进程
 */
class RemoveSeedProcess extends AbstractProcess
{


    /**
     * @param $arg
     * @return mixed
     */
    protected function run($arg)
    {
        var_dump("移除废弃种子进程");
        go(function () {
            while (true) {
                \EasySwoole\RedisPool\RedisPool::invoke(function (\EasySwoole\Redis\Redis $redis) {
                    # 监听 赶乌鸦的接口
                    $id = $redis->rPop("RemoveSeed");
                    if ($id) {
                        #  已经 收获过了  改移除种子了
                        DbManager::getInstance()->invoke(function ($client) use ($id, $redis) {
                            $id_array = explode('@', $id);
                            if (count($id_array) == 3) {
                                # 属于那个 账号
                                $one = AccountNumberModel::invoke($client)->get(['id' => $id_array[1]]); #farm_id   account_number_id user_id
                                $two = FarmModel::invoke($client)->get(['id' => $id_array[0]]);
                                if (!$one || !$two) {
                                    Tools::WriteLogger($id_array[2], 2, "进程 RemoveSeedProcess 账号不存在", $id_array[1], 4);
                                    return false;
                                }
                                if ($two['status'] != 2) {
                                    # 说明这个种子还没有收获 不可以移除
                                    Tools::WriteLogger($id_array[2], 2, "进程 RemoveSeedProcess 种子:" . $one['farm_id'] . " 不可以移除,原因:改种子还没有收获", $id_array[1], 4);
                                    return false;
                                }
                                if ($two['remove'] == 2) {
                                    Tools::WriteLogger($id_array[2], 2, "进程 RemoveSeedProcess 种子:" . $one['farm_id'] . " 不可以移除,原因:已经移除过了", $id_array[1], 4);
                                    return false;
                                }
                                #准备去收获 种子
                                $client_http = new \EasySwoole\HttpClient\HttpClient('https://backend-farm.plantvsundead.com/farms/' . $two['farm_id'] . '/deactivate');
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
                                $data = '{}';
                                $response = $client_http->post($data);
                                $result = $response->getBody();
                                $data = json_decode($result, true);

                                if (!$data) {
                                    # 解析失败 收获失败
                                    \EasySwoole\Component\Timer::getInstance()->after(10 * 1000, function () use ($id, $redis) {
                                        $redis->rPush("RemoveSeed", $id);  # account_number_id  种子类型 user_id
                                    });
                                    Tools::WriteLogger($id_array[2], 2, "进程 RemoveSeedProcess 种子:" . $one['farm_id'] . " 移除失败,原因:json解析失败 result:" . $result, $id_array[1], 4);
                                    return false;
                                }

                                if ($data['status'] != 0) {
                                    Tools::WriteLogger($id_array[2], 2, "进程 RemoveSeedProcess 种子:" . $one['farm_id'] . " 移除失败,原因: result:" . $result, $id_array[1], 4);
                                    return false;
                                }

                                FarmModel::invoke($client)->where(['id' => $id_array[0]])->update(['remove' => 2, 'updated_at' => time()]);
                                # 移除成功   # 准备去种种子
                                Tools::WriteLogger($id_array[2], 1, "进程 RemoveSeedProcess 种子:" . $one['farm_id'] . " 移除成功 result:" . $result, $id_array[1], 4);
                                # 铲除成功后 需要 去 推入 放种子  浇水的 进程
                                $redis->rPush("Seed_Fruit", $id_array[1] . "@" . $two['plant_type'] . "@" . $one['user_id']);  # account_number_id  种子类型 user_id
                            }

                        });

                    }
                }, 'redis');
                \co::sleep(5); # 五秒循环一次
            }

        });

    }
}