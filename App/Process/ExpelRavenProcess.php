<?php


namespace App\Process;


use App\Model\AccountNumberModel;
use App\Model\FarmModel;
use App\Model\ToolsModel;
use App\Tools\Tools;
use EasySwoole\Component\Process\AbstractProcess;
use EasySwoole\ORM\DbManager;
use EasySwoole\RedisPool\RedisPool;

/**
 * Class ExpelRavenProcess
 * @package App\Process
 *   驱赶乌鸦
 */
class ExpelRavenProcess extends AbstractProcess
{

    protected function run($arg)
    {
        var_dump("这里一个驱逐乌鸦的进程");
        go(function () {
            while (true) {
                \EasySwoole\RedisPool\RedisPool::invoke(function (\EasySwoole\Redis\Redis $redis) {
                    # 监听 赶乌鸦的接口
                    $id = $redis->rPop("CROW_IDS");
                    if ($id) {
                        var_dump("有乌鸦进来了!!");
                        # 说明 已经有 出现了  乌鸦了
                        DbManager::getInstance()->invoke(function ($client) use ($id, $redis) {
                            $array_data = explode('@', $id);
                            if (count($array_data) == 3) {
                                # 属于那个 账号
                                $one = AccountNumberModel::invoke($client)->get(['id' => $array_data[1]]); #farm_id   account_number_id user_id
                                $two = ToolsModel::invoke($client)->get(['account_number_id' => $array_data[1]]);
                                $there = FarmModel::invoke($client)->get(['id' => $array_data[0]]);
                                if ($two && $two['scarecrow'] < 1) {
                                    Tools::WriteLogger($array_data[2], 2, "进程 ExpelRavenProcess 稻草人数量不足", $array_data[1], 9);
                                    return false;
                                }


                                if ($one && $two && $there) {
                                    $token_value = $one['token_value'];
                                    $client_http = new \EasySwoole\HttpClient\HttpClient('https://backend-farm.plantvsundead.com/farms/apply-tool');
                                    $headers = array(
                                        'authority' => 'backend-farm.plantvsundead.com',
                                        'sec-ch-ua' => '"Google Chrome";v="93", " Not;A Brand";v="99", "Chromium";v="93"',
                                        'accept' => 'application/json, text/plain, */*',
                                        'content-type' => 'application/json;charset=UTF-8',
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
                                    );
                                    $client_http->setHeaders($headers, false, false);
                                    $data = '{"farmId":"' . $there['farm_id'] . '","toolId":4,"token":{"challenge":"default","seccode":"default","validate":"default"}}';
                                    $response = $client_http->post($data);
                                    $response = $response->getBody();
                                    $data = json_decode($response, true);
                                    if (!$data) {
                                        # 解析失败 赶跑乌鸦失败
                                        \EasySwoole\Component\Timer::getInstance()->after(10 * 1000, function () use ($id, $redis) {
                                            $redis->rPush("CROW_IDS", $id);  # account_number_id  种子类型 user_id
                                        });
                                        Tools::WriteLogger($array_data[2], 2, "账户id:" . $array_data[1] . " 种子id:" . $one['farm_id'] . "赶走乌鸦失败.....json解析失败", $array_data[1], 9);
                                        return false;
                                    }
                                    if ($data['status'] != 0) {
                                        Tools::WriteLogger($array_data[2], 2, "账户id:" . $array_data[1] . " 种子id:" . $one['farm_id'] . "赶走乌鸦失败....." . $response, $array_data[1], 9);
                                        return false;
                                    }
                                    Tools::WriteLogger($array_data[2], 1, "账户id:" . $array_data[1] . " 种子id:" . $one['farm_id'] . "赶走乌鸦成功....." . $response, $array_data[1], 9);

                                    #


                                }

                            }

                        });

                    }
                }, 'redis');
                \co::sleep(5); # 五秒循环一次
            }


        });
    }
}