<?php


namespace App\Task;


use App\Model\AccountNumberModel;
use App\Tools\Tools;
use Cassandra\Date;
use EasySwoole\HttpClient\Exception\InvalidUrl;
use EasySwoole\ORM\DbManager;
use EasySwoole\ORM\Exception\Exception;
use EasySwoole\Pool\Exception\PoolEmpty;
use EasySwoole\RedisPool\RedisPool;
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
            var_dump("世界模式启动");
            DbManager::getInstance()->invoke(function ($client) {
                $res = AccountNumberModel::invoke($client)->all(['status' => 1]);
                if ($res) {
                    foreach ($res as $re) {
                        $redis = RedisPool::defer('redis');
                        $redis_data = $redis->hGet(Date("Y-m-d", time()) . "_worldTree", "account_" . $re['id']);
                        if (!$redis_data) {
                            $redis->hSet(Date("Y-m-d", time()) . "_worldTree", "account_" . $re['id'], json_encode(['water' => 0, 'present' => 0]));
                        }


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
                            if ($data['data']['yesterdayReward']) {
                                $this->OneKey($re['token_value'], $re['user_id'], $re['id']);
                            }
                            \co::sleep(2); # 五秒循环一次
                            break;
                        }
                        if ($redis_data) {
                            $redis_array = json_decode($redis_data, true);
                            if ($redis_array['water'] == 0) {
                                # 说明今天没有浇过水
                                $this->Watering($re['token_value'], $re['user_id'], $re['id']);
                            }
                        } else {
                            $this->Watering($re['token_value'], $re['user_id'], $re['id']);
                        }

                        \co::sleep(2); # 五秒循环一次
                    }
                }
            });
        } catch (\Throwable $e) {
            Tools::WriteLogger($this->data['user_id'], 2, "任务 TheTreeFromWorldTask 异常:" . $e->getMessage());
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

    # 浇水
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
                $data = '{"amount":20}';
                $response = $client_http_two->post($data);
                $result = $response->getBody();
                $data = json_decode($result, true);
                if ($data && $data['status'] == 0) {
                    $redis = RedisPool::defer("redis");
                    $redis_data = $redis->hGet(Date("Y-m-d", time()) . "_worldTree", "account_" . $account_number_id);
                    if ($redis_data) {
                        $redis_array = json_decode($redis_data, true);
                        $redis_array['water'] = 1;
                        $redis->hSet(Date("Y-m-d", time()) . "_worldTree", "account_" . $account_number_id, json_encode($redis_array));
                    }
                    Tools::WriteLogger($user_id, 1, "世界树浇水成功¬", $account_number_id, 10);
                    return false;
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