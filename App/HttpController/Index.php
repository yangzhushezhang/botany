<?php


namespace App\HttpController;


use App\Model\AccountNumberModel;
use App\Task\GetAnswerTask;
use App\Tools\Tools;
use EasySwoole\Http\AbstractInterface\Controller;

class Index extends Controller
{

    public function index()
    {
        $file = EASYSWOOLE_ROOT . '/vendor/easyswoole/easyswoole/src/Resource/Http/welcome.html';
        if (!is_file($file)) {
            $file = EASYSWOOLE_ROOT . '/src/Resource/Http/welcome.html';
        }
        $this->response()->write(file_get_contents($file));
    }

    function test()
    {

        $id = 1;
        $client = new \EasySwoole\HttpClient\HttpClient('https://backend-farm.plantvsundead.com/buy-tools');
        $headers = array(
            'authority' => 'backend-farm.plantvsundead.com',
            'sec-ch-ua' => '"Google Chrome";v="93", " Not;A Brand";v="99", "Chromium";v="93"',
            'accept' => 'application/json, text/plain, */*',
            'content-type' => 'application/json;charset=UTF-8',
            'authorization' => 'Bearer Token: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJwdWJsaWNBZGRyZXNzIjoiMHhjNTZlNTZlM2U2MjIyZTYwY2NhYjIzN2NjZTc1ZGZkOWZmNzY3ZDQ1IiwibG9naW5UaW1lIjoxNjMzMzI2MTc0MjY3LCJjcmVhdGVEYXRlIjoiMjAyMS0xMC0wNCAwNTo0Mjo1MiIsImlhdCI6MTYzMzMyNjE3NH0.fWzaLNIE3-UuU9k4IisSm0VSz8u1DJvmO7pw2-zVd3I',
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
        $this->response()->write($result);
    }

    protected function actionNotFound(?string $action)
    {
        $this->response()->withStatus(404);
        $file = EASYSWOOLE_ROOT . '/vendor/easyswoole/easyswoole/src/Resource/Http/404.html';
        if (!is_file($file)) {
            $file = EASYSWOOLE_ROOT . '/src/Resource/Http/404.html';
        }
        $this->response()->write(file_get_contents($file));
    }
}