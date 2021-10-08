<?php

namespace App\Process;

use App\Model\AccountNumberModel;
use App\Model\FarmModel;
use App\Model\ToolsModel;
use App\Tools\Tools;
use EasySwoole\Component\Process\AbstractProcess;
use EasySwoole\ORM\DbManager;


/**
 * Class WateringProcess
 * @package App\Process
 * 浇水
 */
class WateringProcess extends AbstractProcess
{

    protected function run($arg)
    {
        go(function () {
            var_dump("这是一个浇水的进程");
            while (true) {
                try {
                    \EasySwoole\RedisPool\RedisPool::invoke(function (\EasySwoole\Redis\Redis $redis) {
                        # 监听 赶乌鸦的接口
                        $id = $redis->rPop("Watering");
                        if ($id) {
                            #  已经 收获过了  改移除种子了
                            DbManager::getInstance()->invoke(function ($client) use ($id, $redis) {
                                $id_array = explode('@', $id);  # 农场id 账户id  用户 id
                                if (count($id_array) == 3) {
                                    # 属于那个 账号
                                    $one = AccountNumberModel::invoke($client)->get(['id' => $id_array[1]]); #farm_id   account_number_id user_id
                                    $two = FarmModel::invoke($client)->get(['id' => $id_array[0]]);
                                    $three = ToolsModel::invoke($client)->get(['account_number_id' => $id_array[1]]);  #查询工具
                                    # 判断水滴 够吗?
                                    if (!$one || !$two) {
                                        Tools::WriteLogger($id_array[2], 2, "进程 WateringProcess 没有找到该账号 ", $id_array[1], 1);
                                        return false;
                                    }

                                    if ($three['water'] && $three['water'] < 41) {
                                        Tools::WriteLogger($id_array[2], 2, "进程 WateringProcess 账号的水量不足,无法浇水,水还剩下:".$three['water'], $id_array[1], 1, $two['farm_id']);
                                        return false;
                                    }


                                    # 种子放花盆
                                    $client_http = new \EasySwoole\HttpClient\HttpClient('https://backend-farm.plantvsundead.com/farms/apply-tool');
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
                                    $client_http->setTimeout(5);
                                    $client_http->setConnectTimeout(10);
                                    $client_http->setHeaders($headers, false, false);
                                    $data = '{"farmId":"' . $two['farm_id'] . '","toolId":3,"token":{"challenge":"default","seccode":"default","validate":"default"}}';
                                    $response = $client_http->post($data);
                                    $response = $response->getBody();
                                    $data = json_decode($response, true);
                                    if (!$data) {
                                        # 解析失败 收获失败
                                        \EasySwoole\Component\Timer::getInstance()->after(15 * 1000, function () use ($id, $redis) {
                                            $redis->rPush("Watering", $id);  # account_number_id  种子类型 user_id
                                        });
                                        Tools::WriteLogger($id_array[2], 2, "进程 WateringProcess 浇水失败了,数据解析失败,15秒后重试" . $response, $id_array[1], 1, $two['farm_id']);
                                        return false;
                                    }
                                    if ($data['status'] != 0) {
                                        if ($data['status'] == 556) {
                                            # 说明出验证码
                                            $IfDoingVerification = $redis->get("IfDoingVerification");
                                            if (!$IfDoingVerification) {
                                                #  不存在 就去处理
                                                $redis->rPush("DecryptCaptcha", $id_array[1] . "@" . $id_array[2]);
                                                $redis->set("IfDoingVerification", 1, 600);# 10分钟
                                                # 重新 2 分钟后浇水
                                                \EasySwoole\Component\Timer::getInstance()->after(120 * 1000, function () use ($id, $redis) {
                                                    $redis->rPush("Watering", $id);  # account_number_id  种子类型 user_id
                                                });
                                            }
                                            Tools::WriteLogger($id_array[2], 2, "进程 WateringProcess 种子浇水失败了,出现了验证码 result:" . $response, $id_array[1], 1, $two['farm_id']);
                                            return false;
                                        } else if ($data['status'] == 20) {
                                            Tools::WriteLogger($id_array[2], 2, "进程 WateringProcess 种子id浇水失败了,该种子没有放花盆" . $response, $id_array[1], 1, $two['farm_id']);
                                            return false;
                                        } else {
                                            \EasySwoole\Component\Timer::getInstance()->after(10 * 1000, function () use ($id, $redis) {
                                                $redis->rPush("Watering", $id);  # account_number_id  种子类型 user_id
                                            });
                                            Tools::WriteLogger($id_array[2], 2, "进程 WateringProcess 种子浇水失败,15秒后重试,result" . $response, $id_array[1], 1, $two['farm_id']);
                                            return false;
                                        }
                                    }
                                    # 更新 农作物状态
                                    FarmModel::invoke($client)->where(['id' => $id_array[0]])->update(['stage' => 'farming', 'updated_at' => time()]);
                                    # 浇水成功
                                    Tools::WriteLogger($id_array[2], 1, "进程 WateringProcess 种子浇水成功" . $response, $id_array[1], 1, $two['farm_id']);
                                    $new = $three['water'] - 1;
                                    ToolsModel::invoke($client)->where(['account_number_id' => $id_array[1]])->update(['updated_at' => time(), 'water' => $new]); # 更新工具
                                }
                            });

                        }
                    }, 'redis');
                    \co::sleep(5); # 五秒循环一次
                } catch (\Throwable $exception) {
                    Tools::WriteLogger(0, 2, "WateringProcess 进程 异常:" . $exception->getMessage(), "", 5);
                }
            }

        });
    }


    protected function onException(\Throwable $throwable, ...$args)
    {
        Tools::WriteLogger(0, 2, "进程 WateringProcess 异常:" . $throwable->getMessage());
        parent::onException($throwable, $args); // TODO: Change the autogenerated stub
    }


    protected function onShutDown()
    {
        Tools::WriteLogger(0, 2, "进程 WateringProcess  onShutDown");
        parent::onShutDown(); // TODO: Change the autogenerated stub
    }
}