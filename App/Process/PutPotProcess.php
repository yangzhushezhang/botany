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
                                if ($three['samll_pot'] < 1) {
                                    var_dump("已经没有 pot 了");
                                    return false;
                                }
                                # 判断是花盆个数够吗?
                                if (!$one || !$two) {
                                    Tools::WriteLogger($id_array[2], 2, "PutPotProcess 账户id:" . $id_array[1] . "不存在 ");
                                    return false;
                                }
                                if ($two['stage'] != "new") {
                                    # 说明这个种子还没有收获 不可以移除
                                    Tools::WriteLogger($id_array[2], 2, "账户id:" . $id_array[1] . "  不要重复的放花盆 种子id:" . $one['farm_id']);
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
                                $response = $response->getBody();
                                $data = json_decode($response, true);

                                if (!$data) {
                                    # 解析失败 收获失败
                                    #重新压进redis  进行
                                    \EasySwoole\Component\Timer::getInstance()->after(10 * 1000, function () use ($id, $redis) {
                                        $redis->rPush("PutPot", $id);  # account_number_id  种子类型 user_id
                                    });


                                    Tools::WriteLogger($id_array[2], 2, "账户id:" . $id_array[1] . " 种子id:" . $two['farm_id'] . "放花盆.....json解析失败");
                                    return false;
                                }

                                if ($data['status'] != 0) {
                                    #  这里需要 是否存在 验证码


                                    Tools::WriteLogger($id_array[2], 2, "账户id:" . $id_array[1] . " 种子id:" . $two['farm_id'] . "放花盆....." . $response);
                                    return false;
                                }

                                var_dump("放花盆成功");
                                # 更新 农作物状态
                                FarmModel::invoke($client)->where(['id' => $id_array[0]])->update(['stage' => 'farming', 'updated_at' => time()]);
                                # 放 花盆成功
                                Tools::WriteLogger($id_array[2], 1, "账户id:" . $id_array[1] . " 种子id:" . $two['farm_id'] . "放花盆....." . $response);
                                #
                                $redis->rPush("Watering", $id);  # account_number_id  种子类型 user_id

                                $new = $three['samll_pot'] - 1;
                                ToolsModel::invoke($client)->where(['account_number_id' => $id_array[1]])->update(['updated_at' => time(), 'water' => $new]); # 更新工具
                                \EasySwoole\Component\Timer::getInstance()->after(10 * 6 * 30 * 1000, function () use ($id, $redis) {
                                    $redis->rPush("Watering", $id);  # account_number_id  种子类型 user_id
                                });


                            }

                        });

                    }
                }, 'redis');
                \co::sleep(5); # 五秒循环一次
            }

        });
    }
}