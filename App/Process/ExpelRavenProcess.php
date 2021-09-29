<?php


namespace App\Process;


use App\Model\AccountNumberModel;
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
                        # 说明 已经有 出现了  乌鸦了
                        DbManager::getInstance()->invoke(function ($client) use ($id, $redis) {
                            $array_data = explode('@', $id);
                            if (count($array_data) == 3) {
                                # 属于那个 账号
                                $one = AccountNumberModel::invoke($client)->get(['id' => $array_data[0]]);
                                if ($one) {
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
                                    $data = '{"farmId":"' . $array_data[1] . '","toolId":4,"token":{"challenge":"default","seccode":"default","validate":"default"}}';
                                    $response = $client_http->post($data);






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