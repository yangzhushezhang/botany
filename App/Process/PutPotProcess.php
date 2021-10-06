<?php


namespace App\Process;


use App\Model\AccountNumberModel;
use App\Model\FarmModel;
use App\Model\ToolsModel;
use App\Tools\Tools;
use EasySwoole\Component\Process\AbstractProcess;
use EasySwoole\ORM\DbManager;

class PutPotProcess extends AbstractProcess
{


    /**
     * @param $arg
     * @return mixed
     */
    protected function run($arg)
    {
        go(function () {
            var_dump("放花盆进程");
            while (true) {
                try {
                    \EasySwoole\RedisPool\RedisPool::invoke(function (\EasySwoole\Redis\Redis $redis) {
                        # 监听 赶乌鸦的接口
                        $id = $redis->rPop("PutPot");
                        if ($id) {
                            #  已经 收获过了  改移除种子了
                            DbManager::getInstance()->invoke(function ($client) use ($id, $redis) {
                                $id_array = explode('@', $id);  # 农场id 账户id  用户 id
                                if (count($id_array) == 3) {
                                    # 属于那个 账号
                                    $one = AccountNumberModel::invoke($client)->get(['id' => $id_array[1]]); #farm_id   account_number_id user_id
                                    $two = FarmModel::invoke($client)->get(['id' => $id_array[0]]);
                                    $three = ToolsModel::invoke($client)->get(['account_number_id' => $id_array[1]]);  #查询工具
                                    if ($three && $three['samll_pot'] < 1) {
                                        Tools::WriteLogger($id_array[2], 2, "进程 PutPotProcess 花盆的数量不足 ", $id_array[1], 3);
                                        return false;
                                    }
                                    # 判断是花盆个数够吗?
                                    if ($three['samll_pot'] && $three['samll_pot'] < 1) {  # 这里的逻辑进行改变下  主动去买花盆
                                        Tools::WriteLogger($id_array[2], 2, "进程 PutPotProcess 账号的花盆不足,无法放盆", $id_array[1], 1);
                                        return false;
                                    }
                                    if (!$one || !$two) {
                                        Tools::WriteLogger($id_array[2], 2, "进程 PutPotProcess 账户不存在 ", $id_array[1], 3);
                                        return false;
                                    }
                                    if ($two['stage'] != "new") {
                                        Tools::WriteLogger($id_array[2], 2, "进程 PutPotProcess 种子:" . $one['farm_id'] . " 不要重复放花盆", $id_array[1], 3);
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
                                    $client_http->setHeaders($headers, false, false);
                                    $data = '{"farmId":"' . $two['farm_id'] . '","toolId":1,"token":{"challenge":"default","seccode":"default","validate":"default"}}';
                                    $response = $client_http->post($data);
                                    $client_http->setTimeout(5);
                                    $client_http->setConnectTimeout(10);
                                    $response = $response->getBody();
                                    $data = json_decode($response, true);
                                    if (!$data) {
                                        # 解析失败 收获失败
                                        #重新压进redis  进行
                                        \EasySwoole\Component\Timer::getInstance()->after(10 * 1000, function () use ($id, $redis) {
                                            $redis->rPush("PutPot", $id);  # account_number_id  种子类型 user_id
                                        });
                                        Tools::WriteLogger($id_array[2], 2, "进程 PutPotProcess 种子:" . $one['farm_id'] . " 放花盆失败了 原因:json 解析失败 result:" . $response, $id_array[1], 3);
                                        return false;
                                    }

                                    if ($data['status'] != 0) {
                                        #  这里需要 是否存在 验证码
                                        if ($data['status'] == 556) {
                                            $IfDoingVerification = $redis->get("IfDoingVerification");
                                            if (!$IfDoingVerification) {
                                                #  不存在 就去处理
                                                $redis->rPush("DecryptCaptcha", $id_array[1] . "@" . $id_array[2]);
                                                $redis->set("IfDoingVerification", 1, 600);# 10分钟
                                            }
                                            Tools::WriteLogger($id_array[2], 2, "进程 PutPotProcess 种子:" . $two['farm_id'] . " 放花盆失败了 原因: 验证码 result:" . $response, $id_array[1], 3);
                                            return false;
                                        } else {
                                            Tools::WriteLogger($id_array[2], 2, "进程 PutPotProcess 种子:" . $two['farm_id'] . " 放花盆失败了 原因: result:" . $response, $id_array[1], 3);
                                            return false;
                                        }
                                    }
                                    var_dump("放花盆成功");
                                    # 更新 农作物状态
                                    FarmModel::invoke($client)->where(['id' => $id_array[0]])->update(['stage' => 'farming', 'updated_at' => time()]);
                                    # 放 花盆成功
                                    Tools::WriteLogger($id_array[2], 2, "进程 PutPotProcess 种子:" . $one['farm_id'] . "放花盆成功" . $response, $id_array[1], 3);
                                    #
                                    $redis->rPush("Watering", $id);  # account_number_id  种子类型 user_id
                                    $new = $three['samll_pot'] - 1;
                                    ToolsModel::invoke($client)->where(['account_number_id' => $id_array[1]])->update(['updated_at' => time(), 'samll_pot' => $new]); # 更新工具
                                    \EasySwoole\Component\Timer::getInstance()->after(60 * 1000, function () use ($id, $redis) {
                                        $redis->rPush("Watering", $id);  # account_number_id  种子类型 user_id
                                    });
                                }
                            });

                        }
                    }, 'redis');
                    \co::sleep(5); # 五秒循环一次
                } catch (\Throwable $exception) {
                    Tools::WriteLogger(0, 2, "PutPotProcess 进程 异常:" . $exception->getMessage(), "", 5);

                }

            }

        });
    }

    protected function onException(\Throwable $throwable, ...$args)
    {
        Tools::WriteLogger(0, 2, "进程 PutPotProcess 异常:" . $throwable->getMessage());
        parent::onException($throwable, $args); // TODO: Change the autogenerated stub
    }


    protected function onShutDown()
    {
        Tools::WriteLogger(0, 2, "进程 PutPotProcess  onShutDown");
        parent::onShutDown(); // TODO: Change the autogenerated stub
    }
}