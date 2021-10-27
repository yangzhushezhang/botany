<?php


namespace App\Task;


use App\Tools\Tools;
use EasySwoole\Task\AbstractInterface\TaskInterface;

class SpecialSeedTask implements TaskInterface
{


    protected $data;

    public function __construct($data)
    {
        // 保存投递过来的数据
        $this->data = $data;

    }

    /**
     * @param int $taskId
     * @param int $workerIndex
     * @return mixed
     * @throws \EasySwoole\HttpClient\Exception\InvalidUrl
     */
    function run(int $taskId, int $workerIndex)
    {

        $result = "";
        for ($i = 0; $i < 5; $i++) {
            $client_http = new \EasySwoole\HttpClient\HttpClient('https://backend-farm.plantvsundead.com/farms');
            $headers = array(
                'authority' => 'backend-farm.plantvsundead.com',
                'sec-ch-ua' => '"Google Chrome";v="93", " Not;A Brand";v="99", "Chromium";v="93"',
                'accept' => 'application/json, text/plain, */*',
                'content-type' => 'application/json;charset=UTF-8',
                'authorization' => $this->data['token_value'],
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
            $client_http->setTimeout(5);
            $client_http->setConnectTimeout(10);
            $data = '{"landId":0,"plantId":' . $this->data['plantId'] . '}';
            $response = $client_http->post($data);
            $result = $response->getBody();
            $data = json_decode($result, true);
            if ($data && $data['us'] == 0) {  #浇水的功能先不 做
                Tools::WriteLogger($this->data['user_id'], 1, '特殊种子种植成功 ' . $result, $this->data['account_number_id'], 2);
                return true;
            }
        }
        Tools::WriteLogger($this->data['user_id'], 2, '特殊种子种植失败种子' . $result, $this->data['account_number_id'], 2);
        return false;
    }

    /**
     * @param \Throwable $throwable
     * @param int $taskId
     * @param int $workerIndex
     * @return mixed
     */
    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        // TODO: Implement onException() method.
        Tools::WriteLogger($this->data['user_id'], 2, 'SpecialSeedTask 异常:' . $throwable->getMessage(), $this->data['account_number_id'], 2);

    }
}