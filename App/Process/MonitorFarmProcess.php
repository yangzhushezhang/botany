<?php


namespace App\Process;


use App\Model\AccountNumberModel;
use App\Model\FarmModel;
use App\Tools\Tools;
use EasySwoole\Component\Process\AbstractProcess;
use EasySwoole\ORM\DbManager;


/**
 * Class MonitorFarmProcess
 * @package App\Process
 *   检测 进程
 *
 *  该进程 只负责 检查   是否有乌鸦
 */
class MonitorFarmProcess extends AbstractProcess
{

    protected function run($arg)
    {
        var_dump("检测进程开启");
        go(function () {
            while (true) {
                # 遍历所有的
                DbManager::getInstance()->invoke(function ($client) {
                    # 查询所有 账户
                    $res = AccountNumberModel::invoke($client)->all(['status' => 1]);
                    if ($res) {
                        foreach ($res as $k => $re) {
                            $token_value = $re['token_value'];
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
                                'if-none-match' => 'W/"1bf5-RySZLkdJ7uwQuWZ+zLfe+hxM36c"',
                            );
                            $client_http->setHeaders($headers, false, false);
                            $response = $client_http->get();
                            $result = $response->getBody();

                            $data = json_decode($result, true);
                            if (!$data) {
                                Tools::WriteLogger($re['user_id'], 2, "MonitorFarmProcess 进程请求 账号:" . $re['id'] . " 请求返回的解析参数失败");
                                # 这个地方再做处理
                                break;
                            }




                            # 判断是否 有乌鸦  影响 农作物
                            foreach ($data['data'] as $value) {
                                # 判断 农场 没有有 这个 种子 id
                                $one = FarmModel::invoke($client)->get(['account_number_id' => $re['id'], 'farm_id' => $value['_id']]);
                                $unix = str_replace(array('T', 'Z'), ' ', $value['harvestTime']);
                                $value['stage'] ;





                                if ($value['stage']=="cancelled"){
                                    var_dump($value['_id']);
                                    var_dump( $value['harvestTime']);
                                    var_dump(strtotime($unix)+8*60*60);
                                    var_dump(time());

                                    # 这里一说明  种子已经

                                }








                                $needWater = 2;
                                $hasSeed = 2;


                                if ($value['stage'] == "pause") {
                                    # 这个种子的时间停止了   说明已经有乌鸦了 .我怕需要 用 稻草人去吓退乌鸦
                                    var_dump("发现了 停止的种子 :" . $value['_id']);
                                    Tools::WriteLogger($re['user_id'], 2, "在种子:" . $value['_id'] . "发现了乌鸦....需要去清除他");
                                } else {
                                    var_dump("检查......" . $value['stage']);
                                }

                                if ($value['needWater']) {
                                    # 需要浇水  让进程去做这件事情     需要给浇水的进程去
                                    $needWater = 1;
                                }
                                if ($value['hasSeed']) {
                                    #需要 放种子
                                    $hasSeed = 1;
                                }


                                # 这里需要判断 有没有乌鸦    如果有乌鸦 我需要 仍在 进程里面来做这件事!!!!
                                $add = [
                                    'account_number_id' => $re['id'],
                                    'farm_id' => $value['_id'],
                                    'harvestTime' => strtotime($unix)+8*60*60,
                                    'needWater' => $needWater,
                                    'hasSeed' => $hasSeed,
                                    'plant_type' => $value['plant']['type'],
                                    'updated_at' => time(),
                                    'stage' => $value['stage']  #paused 说明暂停 了 有乌鸦
                                ];
                                #存在 只需要 做更新操作
                                if ($one) {
                                    $two = FarmModel::invoke($client)->where(['account_number_id' => $re['id'], 'farm_id' => $value['_id']])->update($add);
                                    if (!$two) {
                                        Tools::WriteLogger($this->who['id'], 2, "接口 refresh_botany 更新数据的时候出错误");
                                    }
                                } else {
                                    # 插入操作
                                    $add['created_at'] = time();
                                    $two = FarmModel::invoke($client)->data($add)->save();
                                    if (!$two) {
                                        Tools::WriteLogger($re['user_id'], 2, "接口 refresh_botany 插入数据的时候出错误");
                                    }
                                }
                            }

                            \co::sleep(5);   # 每个账号直接 休息时间是  5秒
                        }


                    }
                });
                \co::sleep(30 * 60);  # 30分钟 检查一次
            }
        });
    }
}