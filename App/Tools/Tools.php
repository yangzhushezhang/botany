<?php

namespace App\Tools;
class Tools
{


    # 获取账号的 能量
    static function getLeWallet()
    {

        try {
            $client = new \EasySwoole\HttpClient\HttpClient('https://backend-farm.plantvsundead.com/farming-stats');
            $headers = array(
                'authority' => 'backend-farm.plantvsundead.com',
                'sec-ch-ua' => '^\\^Google',
                'accept' => 'application/json, text/plain, */*',
                'authorization' => 'Bearer Token: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJwdWJsaWNBZGRyZXNzIjoiMHg1YzY3NjBhMGUxZTBlMzQzMzk3OGIwMDBhNmQ4YWFiNDkyY2M1M2Q5IiwibG9naW5UaW1lIjoxNjMyNzM5ODEzNjQ5LCJjcmVhdGVEYXRlIjoiMjAyMS0wOS0yMiAwMToyMjoyOSIsImlhdCI6MTYzMjczOTgxM30.hm94dqSSSvQ-e95wCqElkER281EMRmyEBEbsvw6UAHo',
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
            $client->setHeaders($headers);
            $response = $client->get();
            $data = json_decode($response, true);
            if (!$data) {
                return false;
            }
            return $data;
        } catch (\Throwable $e) {
            return false;
        }
    }

}