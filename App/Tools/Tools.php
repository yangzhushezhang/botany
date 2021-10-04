<?php

namespace App\Tools;

use App\Model\LoggerModel;
use EasySwoole\ORM\DbManager;

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
                    # 'if-none-match' => 'W/^\\^99-2xqEFdktsE4xMb9duc5cLOCwO+c^\\^',
                );
                $client->setHeaders($headers, false, false);
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
                    #'if-none-match' => 'W/^\\^99-2xqEFdktsE4xMb9duc5cLOCwO+c^\\^',
                );
                $client->setHeaders($headers, false, false);
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


    static function WriteLogger($user_id, $kind, $content, $account_number_id = 0, $variety = 0)
    {
        try {

            DbManager::getInstance()->invoke(function ($client) use ($user_id, $kind, $content, $account_number_id, $variety) {
                $data = [
                    'content' => $content,
                    'user_id' => $user_id,
                    'kind' => $kind,
                    'updated_at' => time(),
                    'created_at' => time(),
                    'account_number_id' => $account_number_id,
                    'variety' => $variety
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

}