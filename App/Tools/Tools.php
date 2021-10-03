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
                'if-none-match' => 'W/^\\^99-2xqEFdktsE4xMb9duc5cLOCwO+c^\\^',
            );
            $client->setHeaders($headers, false, false);
            $response = $client->get();
            $response = $response->getBody();

//            var_dump($response);

            $data = json_decode($response, true);
            if (!$data) {
                return false;
            }
            return $data;
        } catch (\Throwable $e) {
            return false;
        }
    }



    static function getSunflowers($token_value)
    {

        try {
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
                return false;
            }
            return $data;
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
                LoggerModel::invoke($client)->data($data)->save();
            });
        } catch (\Throwable $e) {
            log("写日志异常  $variety  :" . $e->getMessage());
            var_dump("写日志异常  $variety  :" . $e->getMessage());
        }
    }

}