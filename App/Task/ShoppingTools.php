<?php


namespace App\Task;


use App\Model\ToolsModel;
use App\Tools\Tools;
use EasySwoole\HttpClient\Exception\InvalidUrl;
use EasySwoole\Task\AbstractInterface\TaskInterface;


/**
 * Class ShoppingTools
 * @package App\Task  购买 水 盆 稻草 的 任务
 */
class ShoppingTools implements TaskInterface
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
     */
    function run(int $taskId, int $workerIndex)
    {
        # 获取 该账号的 总能量
        $Energy = $this->getEnergy($this->data['token_value'], $this->data['user_id'], $this->data['account_number_id'], $this->data['farm_id']);
        if (!$Energy) {
            return false;
        }
        #购买工具
        Tools::shoppingTools($this->data['tool_id'], $this->data['token_value'], $this->data['farm_id'], $this->data['account_number_id'], $this->data['user_id'], $Energy);  #$tool_id, $token_value, $farm_id, $account_number_id, $user_id, $leWallet
    }

    /**
     * @param \Throwable $throwable
     * @param int $taskId
     * @param int $workerIndex
     * @return mixed
     */
    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {

    }


    # 获取能量值
    function getEnergy($token_value, $user_id, $account_number_id, $farm_id)
    {
        try {
            $data = "";
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
                $client->setTimeout(5);
                $client->setConnectTimeout(10);
                $data = json_decode($response, true);
                if ($data && $data['status'] == 0) {
                    if (!isset($data['data']['leWallet'])) {
                        Tools::WriteLogger($user_id, 2, "获取能量成功", $account_number_id, 6, $farm_id);
                        return $data['data']['leWallet'];
                    }
                }
                \co::sleep(1); #
            }
            Tools::WriteLogger($user_id, 2, "获取能量失败:" . $data, $account_number_id, 6, $farm_id);
            return false;
        } catch (InvalidUrl $e) {
            Tools::WriteLogger($user_id, 2, "获取能量异常:" . $e->getMessage(), $account_number_id, 6, $farm_id);
            return false;
        }
    }
}