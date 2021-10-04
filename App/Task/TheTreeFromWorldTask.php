<?php


namespace App\Task;


use App\Model\AccountNumberModel;
use App\Tools\Tools;
use EasySwoole\ORM\DbManager;
use EasySwoole\ORM\Exception\Exception;
use EasySwoole\Pool\Exception\PoolEmpty;
use EasySwoole\Task\AbstractInterface\TaskInterface;
use function Composer\Autoload\includeFile;

class TheTreeFromWorldTask implements TaskInterface
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
     *
     */
    function run(int $taskId, int $workerIndex)
    {
        try {
            DbManager::getInstance()->invoke(function ($client) {
                $res = AccountNumberModel::invoke($client)->all(['status' => 1]);
                if ($res) {
                    foreach ($res as $re) {
                        for ($i = 0; $i < 5; $i++) {
                            $client_http = new \EasySwoole\HttpClient\HttpClient('https://backend-farm.plantvsundead.com/world-tree/datas');
                            $headers = array(
                                'authority' => 'backend-farm.plantvsundead.com',
                                'sec-ch-ua' => '"Google Chrome";v="93", " Not;A Brand";v="99", "Chromium";v="93"',
                                'accept' => 'application/json, text/plain, */*',
                                'authorization' => $re['token_value'],
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
                            $response = $client_http->get();
                            $result = $response->getBody();
                            $data = json_decode($result, true);
                            if (!$data || $data['status'] != 0) {
                                Tools::WriteLogger($re['user_id'], 2, "任务 TheTreeFromWorldTask 请求数据失败,result:" . $result, $re['id'], 10);
                                continue;
                            }

                            if ($data['yesterdayReward']) {
                                # 可以去 一键收取










                            }


                        }
                    }
                }

            });

        } catch (\Throwable $e) {
            Tools::WriteLogger($this->data['user_id'], 2, "任务 TheTreeFromWorldTask 异常:" . $e->getMessage());
        }


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
    }
}