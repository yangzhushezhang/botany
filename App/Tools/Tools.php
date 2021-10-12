<?php

namespace App\Tools;

use App\Model\LoggerModel;
use EasySwoole\HttpClient\Exception\InvalidUrl;
use EasySwoole\ORM\DbManager;
use EasySwoole\RedisPool\RedisPool;

class Tools
{


    # 获取账号的 能量
    static function getLeWallet($token_value)
    {
        try {
            for ($i = 0; $i < 5; $i++) {
                $client = new \EasySwoole\HttpClient\HttpClient('https://backend-farm.plantvsundead.com/farming-stats');
                $headers = array(
                    'authority' => 'backend-farm.plantvsundead.com',
                    'sec-ch-ua' => '^\\^Google',
                    'accept' => 'application/json, text/plain, */*',
                    'authorization' => $token_value,
                    'sec-ch-ua-mobile' => '?0',
                    'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/93.0.4577.82 Safari/537.36',
                    'sec-ch-ua-platform' => '^\\^Windows^\\^',
                    'origin' => 'https://marketplace.plantvsundead.com',
                    'sec-fetch-site' => 'same-site',
                    'sec-fetch-mode' => 'cors',
                    'sec-fetch-dest' => 'empty',
                    'referer' => 'https://marketplace.plantvsundead.com/',
                    'accept-language' => 'zh-CN,zh;q=0.9',
                );
                $client->setHeaders($headers, false, false);
                $client->setTimeout(5);
                $client->setConnectTimeout(10);
                $response = $client->get();
                $response = $response->getBody();
                $data = json_decode($response, true);
                if (!$data) {
                    continue;
                }
                return $data;
            }
            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }


    # 获取 种子 向日葵 个数
    static function getSunflowers($token_value)
    {
        try {
            for ($i = 0; $i < 5; $i++) {
                $client = new \EasySwoole\HttpClient\HttpClient('https://backend-farm.plantvsundead.com/my-sunflowers');
                $headers = array(
                    'authority' => 'backend-farm.plantvsundead.com',
                    'sec-ch-ua' => '^\\^Google',
                    'accept' => 'application/json, text/plain, */*',
                    'authorization' => $token_value,
                    'sec-ch-ua-mobile' => '?0',
                    'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/93.0.4577.82 Safari/537.36',
                    'sec-ch-ua-platform' => '^\\^Windows^\\^',
                    'origin' => 'https://marketplace.plantvsundead.com',
                    'sec-fetch-site' => 'same-site',
                    'sec-fetch-mode' => 'cors',
                    'sec-fetch-dest' => 'empty',
                    'referer' => 'https://marketplace.plantvsundead.com/',
                    'accept-language' => 'zh-CN,zh;q=0.9',
                );
                $client->setHeaders($headers, false, false);
                $client->setTimeout(5);
                $client->setConnectTimeout(10);
                $response = $client->get();
                $response = $response->getBody();
                $data = json_decode($response, true);
                if (!$data) {
                    continue;
                }
                return $data;
            }
            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }


    #   购买工具
    static function shoppingTools($tool_id, $token_value, $farm_id, $account_number_id, $user_id, $leWallet)   #1 花盆  2水  4 稻草人
    {
        if ($tool_id == 1 || $tool_id == 3) {
            if ($leWallet < 50) {
                if ($tool_id == 1) {
                    Tools::WriteLogger($user_id, 2, "能量不够,无法购买花盆", $account_number_id, 6, $farm_id);
                } else {
                    Tools::WriteLogger($user_id, 2, "能量不够,无法购买水滴", $account_number_id, 6, $farm_id);
                }
                return false;
            }
        }
        if ($tool_id == 4) {
            if ($leWallet < 20) {
                Tools::WriteLogger($user_id, 2, "能量不够,无法购买稻草人", $account_number_id, 6, $farm_id);
                return false;
            }
        }
        $result = "";
        for ($i = 0; $i < 5; $i++) {
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
            $client->setTimeout(5);
            $client->setConnectTimeout(10);
            $data = '{"amount":1,"toolId":' . $tool_id . '}';
            $response = $client->post($data);
            $result = $response->getBody();
            $data_json = json_decode($result, true);
            if ($data_json && $data_json['status'] == 0) {
                #工具购买成功
                Tools::WriteLogger($user_id, 2, "购买工具:" . $tool_id . "成功", $account_number_id, 6, $farm_id);
                return true;
            }
            \co::sleep(1); # 10 分钟执行一次
        }
        Tools::WriteLogger($user_id, 2, "购买工具:" . $tool_id . "失败  原因:" . $result, $account_number_id, 6, $farm_id);
        return false;
    }


    # 写日志
    static function WriteLogger($user_id, $kind, $content, $account_number_id = 0, $variety = 0, $farm_id = 0)
    {
        try {

            DbManager::getInstance()->invoke(function ($client) use ($user_id, $kind, $content, $account_number_id, $variety, $farm_id) {
                $data = [
                    'content' => $content,
                    'user_id' => $user_id,
                    'kind' => $kind,
                    'updated_at' => time(),
                    'created_at' => time(),
                    'account_number_id' => $account_number_id,
                    'variety' => $variety,
                    'farm_id' => $farm_id
                ];

                $kk = LoggerModel::invoke($client)->data($data)->save();
                if (!$kk) {
                    var_dump("WriteLogger 日志插入失败");
                }
            });
        } catch (\Throwable $e) {
            log("写日志异常  $variety  :" . $e->getMessage());
            var_dump("写日志异常  $variety  :" . $e->getMessage());
        }
    }


    # 昨日一键收取
    function OneKey($token_value, $user_id, $account_number_id)
    {
        try {

            $redis = RedisPool::defer("redis");
            for ($i = 0; $i < 5; $i++) {
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
                );
                $client_http_two = new \EasySwoole\HttpClient\HttpClient('https://backend-farm.plantvsundead.com/world-tree/claim-yesterday-reward');
                $client_http_two->setHeaders($headers, false, false);
                $client_http_two->setTimeout(5);
                $client_http_two->setConnectTimeout(10);
                $response = $client_http_two->post();
                $result = $response->getBody();
                $data = json_decode($result, true);
                if ($data && $data['status'] == 0) {
                    # 收取成功
                    $redis_data = $redis->hGet(Date("Y-m-d", time()) . "_worldTree", "account_" . $account_number_id);
                    if ($redis_data) {
                        $redis_array = json_decode($redis_data, true);
                        $redis_array['present'] = 1;
                        $redis->hSet(Date("Y-m-d", time()) . "_worldTree", "account_" . $account_number_id, json_encode($redis_array));
                    }
                    var_dump("账号:" . $account_number_id . "一键收取昨日成功");
                    Tools::WriteLogger($user_id, 1, "一键收取昨日成功", $account_number_id, 10);
                    return true;
                }
                \co::sleep(2); # 五秒循环一次
            }
            Tools::WriteLogger($user_id, 2, "一键收取昨日失败", $account_number_id, 10);
            return false;
        } catch (InvalidUrl $e) {
            Tools::WriteLogger($user_id, 2, "一键收取异常:" . $e->getMessage(), $account_number_id, 10);
            return false;
        }
    }


    # 世界树浇水
    function Watering($token_value, $user_id, $account_number_id)
    {
        try {
            for ($i = 0; $i < 5; $i++) {
                $headers = array(
                    'authority' => 'backend-farm.plantvsundead.com',
                    'sec-ch-ua' => '"Chromium";v="94", "Google Chrome";v="94", ";Not A Brand";v="99"',
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
                $client_http_two = new \EasySwoole\HttpClient\HttpClient('https://backend-farm.plantvsundead.com/world-tree/give-waters');
                $client_http_two->setHeaders($headers, false, false);
                $client_http_two->setTimeout(5);
                $client_http_two->setConnectTimeout(10);
                $data = '{"amount":20}';
                $response = $client_http_two->post($data);
                $result = $response->getBody();
                $data = json_decode($result, true);
                if ($data && $data['status'] == 0) {
                    $redis = RedisPool::defer("redis");
                    $redis_data = $redis->hGet(Date("Y-m-d", time()) . "_worldTree", "account_" . $account_number_id);
                    var_dump("?????");
                    if ($redis_data) {
                        var_dump("浇水成功");
                        $redis_array = json_decode($redis_data, true);
                        $redis_array['water'] = 1;
                        $redis->hSet(Date("Y-m-d", time()) . "_worldTree", "account_" . $account_number_id, json_encode($redis_array));
                    }
                    Tools::WriteLogger($user_id, 1, "世界树浇水成功¬", $account_number_id, 10);
                    return true;
                }
                \co::sleep(2); # 五秒循环一次
            }
            Tools::WriteLogger($user_id, 2, "世界树浇水失败", $account_number_id, 10);
            return false;
        } catch (InvalidUrl $e) {
            Tools::WriteLogger($user_id, 2, "世界树浇水异常" . $e->getMessage(), $account_number_id, 10);
            return false;
        }
    }


    # 获取工具信息
    function GteNewTools($token_value)
    {
        try {
            for ($i = 0; $i < 5; $i++) {
                $client = new \EasySwoole\HttpClient\HttpClient('https://backend-farm.plantvsundead.com/my-tools');
                $headers = array(
                    'authority' => 'backend-farm.plantvsundead.com',
                    'sec-ch-ua' => '^\\^Google',
                    'accept' => 'application/json, text/plain, */*',
                    'authorization' => $token_value,
                    'sec-ch-ua-mobile' => '?0',
                    'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/93.0.4577.82 Safari/537.36',
                    'sec-ch-ua-platform' => '^\\^Windows^\\^',
                    'origin' => 'https://marketplace.plantvsundead.com',
                    'sec-fetch-site' => 'same-site',
                    'sec-fetch-mode' => 'cors',
                    'sec-fetch-dest' => 'empty',
                    'referer' => 'https://marketplace.plantvsundead.com/',
                    'accept-language' => 'zh-CN,zh;q=0.9',
                    #   'if-none-match' => 'W/^\\^32c-sAwO7sU/nng0IT4QwrYVX61WsEY^\\^',
                );
                $client->setHeaders($headers, false, false);
                $client->setTimeout(5);
                $client->setConnectTimeout(10);
                $response = $client->get();
                $result = $response->getBody();
                $data_json = json_decode($result, true);
                if ($data_json && $data_json['status'] == 0) {
                    return $data_json;
                }
            }
            return false;
        } catch (InvalidUrl $e) {
            return false;
        }
    }





}