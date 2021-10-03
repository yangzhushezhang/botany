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
                                var_dump($re['water']);
                                var_dump("购买水");
                                # 购买水
                                $this->Shop_tools(3, $one['token_value'], $one['user_id']);
                            }


                            if ($re['samll_pot'] < 6) {
                                #购买盆
                                var_dump($re['samll_pot']);
                                var_dump("samll_pot");
                                $this->Shop_tools(1, $one['token_value'], $one['user_id']);
                            }


                            if ($re['scarecrow'] < 10) {
                                # 购买乌鸦
                                var_dump($re['scarecrow']);
                                var_dump("scarecrow");
                                $this->Shop_tools(4, $one['token_value'], $one['user_id']);
                            }


                            # 请求工具接口
                            $token_value = $one['token_value'];
                            $client_http = new \EasySwoole\HttpClient\HttpClient('https://backend-farm.plantvsundead.com/my-tools');
                            $headers = array(
                                'authority' => 'backend-farm.plantvsundead.com',
                                'sec-ch-ua' => '^\\^Google',
                                'accept' => 'application/json, text/plain, */*',
                                'authorization' => $one['token_value'],
                                'sec-ch-ua-mobile' => '?0',
                                'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/93.0.4577.82 Safari/537.36',
                                'sec-ch-ua-platform' => '^\\^Windows^\\^',
                                'origin' => 'https://marketplace.plantvsundead.com',
                                'sec-fetch-site' => 'same-site',
                                'sec-fetch-mode' => 'cors',
                                'sec-fetch-dest' => 'empty',
                                'referer' => 'https://marketplace.plantvsundead.com/',
                                'accept-language' => 'zh-CN,zh;q=0.9',
                                'if-none-match' => 'W/^\\^32c-sAwO7sU/nng0IT4QwrYVX61WsEY^\\^',
                            );
                            $client_http->setHeaders($headers, false, false);
                            $response = $client_http->get();
                            $result = $response->getBody();
                            $data_json = json_decode($result, true);
                            if (!$data_json) {
                                Tools::WriteLogger($one['user_id'], 2, "MonitorTools 进程  解析失败  result:" . $result);
                                return false;
                            }
                            if ($data_json['status'] != 0) {
                                Tools::WriteLogger($one['user_id'], 2, "MonitorTools refresh_tools json status!=0  :" . $result);
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
                            ToolsModel::invoke($client)->where(['account_number_id' => $re['account_number_id']])->update($update_data);

                        }

                        \co::sleep(5); # 每个账号之间 间隔 5 秒钟
                    }
                });
                \co::sleep(60 * 10); # 10 分钟执行一次
            }


        });
    }


    function Shop_tools($id, $token_value, $user_id)
    {

        try {
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
                Tools::WriteLogger($user_id, 2, "MonitorTools  购买工具:" . $id . " 失败  原因:解析失败");
                return false;
            }
            if ($data_json['status'] != 0) {
                Tools::WriteLogger($user_id, 2, "MonitorTools  购买工具:" . $id . " 失败  原因:" . $result);
                return false;
            }
            Tools::WriteLogger($user_id, 1, "购买工具 :" . $id . "成功");
        } catch (InvalidUrl $e) {
            Tools::WriteLogger($user_id, 1, "购买 异常:" . $e->getMessage());

        }

    }


}