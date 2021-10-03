<?php


namespace App\Process;


use App\Model\AccountNumberModel;
use App\Model\ToolsModel;
use App\Tools\Tools;
use EasySwoole\Component\Process\AbstractProcess;
use EasySwoole\HttpClient\Exception\InvalidUrl;
use EasySwoole\ORM\DbManager;

class MonitorTools extends AbstractProcess
{

    /**
     * @param $arg
     * @return mixed
     */
    protected function run($arg)
    {
        go(function () {
            var_dump("工具自行检查进程");
            while (true) {
                DbManager::getInstance()->invoke(function ($client) {
                    $res = ToolsModel::invoke($client)->all();
                    foreach ($res as $re) {
                        $one = AccountNumberModel::invoke($client)->get(['id' => $re['account_number_id']]);
                        if ($one && $one['status'] == 1) {
                            # 检查
                            if ($re['water'] < 12) {
                                # 购买水
                                $this->Shop_tools(3, $one['token_value'], $one['user_id'], $one['id']);
                            }
                            if ($re['samll_pot'] < 6) {
                                #购买盆
                                $this->Shop_tools(1, $one['token_value'], $one['user_id'], $one['id']);
                            }
                            if ($re['scarecrow'] < 10) {
                                # 购买乌鸦
                                $this->Shop_tools(4, $one['token_value'], $one['user_id'], $one['id']);
                            }
                            # 请求工具接口
                        }
                    }
                    $fix = AccountNumberModel::invoke($client)->all(['status' => 1]);
                    if ($fix) {
                        # 更新 鼠标 和 向日葵 个数
                        foreach ($fix as $six) {
                            $one = ToolsModel::invoke($client)->get(['account_number_id' => $six['id']]);
                            if (!$one) {
                                # 不存在这个 账号的工具 就 插入
                                # 更新 我的工具
                                $client_http = new \EasySwoole\HttpClient\HttpClient('https://backend-farm.plantvsundead.com/my-tools');
                                $headers = array(
                                    'authority' => 'backend-farm.plantvsundead.com',
                                    'sec-ch-ua' => '^\\^Google',
                                    'accept' => 'application/json, text/plain, */*',
                                    'authorization' => $six['token_value'],
                                    'sec-ch-ua-mobile' => '?0',
                                    'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/93.0.4577.82 Safari/537.36',
                                    'sec-ch-ua-platform' => '^\\^Windows^\\^',
                                    'origin' => 'https://marketplace.plantvsundead.com',
                                    'sec-fetch-site' => 'same-site',
                                    'sec-fetch-mode' => 'cors',
                                    'sec-fetch-dest' => 'empty',
                                    'referer' => 'https://marketplace.plantvsundead.com/',
                                    'accept-language' => 'zh-CN,zh;q=0.9',
                                    #  'if-none-match' => 'W/^\\^32c-sAwO7sU/nng0IT4QwrYVX61WsEY^\\^',
                                );
                                $client_http->setHeaders($headers, false, false);
                                $response = $client_http->get();
                                $result = $response->getBody();
                                $data_json = json_decode($result, true);
                                if (!$data_json) {
                                    Tools::WriteLogger($six['user_id'], 2, "进程 MonitorTools   解析失败  result:" . $result);
                                    return false;
                                }
                                if ($data_json['status'] != 0) {
                                    Tools::WriteLogger($six['user_id'], 2, "MonitorTools refresh_tools json status!=0  :" . $result, $six['id'], 9);
                                    return false;
                                }

                                $update_data = [
                                    'updated_at' => time()
                                ];
                                foreach ($data_json['data'] as $k => $value) {
                                    if ($value['type'] == "WATER") {
                                        $update_data['water'] = $value['usages'];
                                    }
                                    if ($value['type'] == "POT") {
                                        $update_data['samll_pot'] = $value['usages'];
                                    }
                                    if ($value['type'] == "SCARECROW") {
                                        $update_data['scarecrow'] = $value['usages'];
                                    }
                                }
                                ToolsModel::invoke($client)->data($update_data)->save();

                            } else {
                                # 更新
                                $token_value = $six['token_value'];
                                $client_http = new \EasySwoole\HttpClient\HttpClient('https://backend-farm.plantvsundead.com/my-tools');
                                $headers = array(
                                    'authority' => 'backend-farm.plantvsundead.com',
                                    'sec-ch-ua' => '^\\^Google',
                                    'accept' => 'application/json, text/plain, */*',
                                    'authorization' => $six['token_value'],
                                    'sec-ch-ua-mobile' => '?0',
                                    'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/93.0.4577.82 Safari/537.36',
                                    'sec-ch-ua-platform' => '^\\^Windows^\\^',
                                    'origin' => 'https://marketplace.plantvsundead.com',
                                    'sec-fetch-site' => 'same-site',
                                    'sec-fetch-mode' => 'cors',
                                    'sec-fetch-dest' => 'empty',
                                    'referer' => 'https://marketplace.plantvsundead.com/',
                                    'accept-language' => 'zh-CN,zh;q=0.9',
                                    # 'if-none-match' => 'W/^\\^32c-sAwO7sU/nng0IT4QwrYVX61WsEY^\\^',
                                );
                                $client_http->setHeaders($headers, false, false);
                                $response = $client_http->get();
                                $result = $response->getBody();
                                $data_json = json_decode($result, true);
                                if (!$data_json) {
                                    Tools::WriteLogger($six['user_id'], 2, "进程 MonitorTools   解析失败  result:" . $result, $six['id'], 9);
                                    return false;
                                }
                                if ($data_json['status'] != 0) {
                                    Tools::WriteLogger($six['user_id'], 2, "MonitorTools refresh_tools json status!=0  :" . $result, $six['id'], 9);
                                    return false;
                                }
                                $update_data = [
                                    'updated_at' => time()
                                ];
                                foreach ($data_json['data'] as $k => $value) {
                                    if ($value['type'] == "WATER") {
                                        $update_data['water'] = $value['usages'];
                                    }
                                    if ($value['type'] == "POT") {
                                        $update_data['samll_pot'] = $value['usages'];
                                    }
                                    if ($value['type'] == "SCARECROW") {
                                        $update_data['scarecrow'] = $value['usages'];
                                    }
                                }
                                ToolsModel::invoke($client)->where(['account_number_id' => $six['id']])->update($update_data);


                            }


                            $token_value = $six['token_value'];
                            # 更新我的 种子 个数
                            $data = Tools::getSunflowers($token_value);
                            if (!$data) {
                                Tools::WriteLogger($six['user_id'], 2, "进程 MonitorTools   解析失败  result:" . $data, $six['id'], 9);
                                return false;

                            }
                            if ($data['status'] == 0) {
                                Tools::WriteLogger($six['user_id'], 2, "进程 MonitorTools     result: status ", $six['id'], 9);
                                return false;
                            }
                            $update = [
                                'updated_at' => time()
                            ];
                            foreach ($data['data'] as $datum) {
                                if ($datum['plantType'] = 1) {
                                    $update['all_sapling'] = $datum['usages'];
                                    $update['already_sapling'] = $datum['total'];
                                }
                                if ($datum['plantType'] == 2) {
                                    $update['all_sunflower'] = $datum['usages'];
                                    $update['already_sunflower'] = $datum['total'];
                                }
                            }
                            $two = AccountNumberModel::invoke($client)->where(['id' => $six['id']])->update(['updated_at' => time(), 'all_sapling' => $data['data']['leWallet']]);


                        }
                        \co::sleep(3); # 每个账号之间 间隔 5 秒钟
                    }


                });
                \co::sleep(60 * 10); # 10 分钟执行一次
            }


        });
    }


    function Shop_tools($id, $token_value, $user_id, $account_number_id)
    {

        try {
            # 判断 自己的能量值是否 足够
            $client = new \EasySwoole\HttpClient\HttpClient('https://backend-farm.plantvsundead.com/buy-tools');
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
            $client->setHeaders($headers, false, false);
            $data = '{"amount":1,"toolId":' . $id . '}';
            $response = $client->post($data);
            $result = $response->getBody();
            $data_json = json_decode($result, true);
            if (!$data_json) {
                Tools::WriteLogger($user_id, 2, "MonitorTools  购买工具:" . $id . " 失败  原因:解析失败", $account_number_id, 6);
                return false;
            }
            if ($data_json['status'] != 0) {
                Tools::WriteLogger($user_id, 2, "MonitorTools  购买工具:" . $id . " 失败  原因:" . $result, $account_number_id, 6);
                return false;
            }
            Tools::WriteLogger($user_id, 1, "购买工具 :" . $id . "成功", $account_number_id, 6);
        } catch (InvalidUrl $e) {
            Tools::WriteLogger($user_id, 2, "购买 异常:" . $e->getMessage(), $account_number_id, 6);

        }

    }


}