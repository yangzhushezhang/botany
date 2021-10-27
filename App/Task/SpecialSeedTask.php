<?php


namespace App\Task;


use App\Model\AccountNumberModel;
use App\Model\FarmModel;
use App\Tools\Tools;
use EasySwoole\RedisPool\RedisPool;
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
     * @throws \EasySwoole\ORM\Exception\Exception
     * @throws \Throwable
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
            $data_json = json_decode($result, true);
            if ($data_json && $data_json['status'] == 0) {
                #浇水的功能先不 做  浇水  买大花盘
                #首先删除
                $redis = RedisPool::defer('redis');
                $redis->hDel("SpecialSeed_" . $this->data['account_number_id'], $this->data['plantId']);

                $add = [
                    'account_number_id' => $this->data['account_number_id'],
                    'farm_id' => $data_json['data']['_id'],
                    'harvestTime' => 0,
                    'needWater' => 2,
                    'hasSeed' => 2,
                    'plant_type' => 1,  #  向日葵宝宝
                    'updated_at' => time(),
                    'stage' => $data_json['data']['stage'],
                    'created_at' => time(),
                    'plantId' => $data_json['data']['plantId'],
                    'status' => 1,
                    'remove' => 1,
                    'iconUrl' => $data_json['data']['plant']['iconUrl']
                ];
                $res = FarmModel::create()->data($add)->save();
                if (!$res) {
                    Tools::WriteLogger($this->data['user_id'], 2, "任务 SpecialSeedTask  插入数据失败 种子id: " . $add['farm_id'], $this->data['account_number_id'], 2, $data_json['data']['_id']);
                    return false;
                } else {
                    Tools::WriteLogger($this->data['user_id'], 1, "任务 SpecialSeedTask 插入数据成功,成功种植!" . $result, $this->data['account_number_id'], 2, $data_json['data']['_id']);
                }

                Tools::WriteLogger($this->data['user_id'], 1, '特殊种子种植成功 ' . $result, $this->data['account_number_id'], 2);


                #先购买大花盘
                $one = AccountNumberModel::create()->get(['id' => $this->data['account_number_id']]);
                $leWallet = $one['leWallet'];
                $result = Tools::shoppingTools(2, $this->data['token_value'], $data_json['data']['_id'], $this->data['account_number_id'], $this->data['user_id'], $leWallet);#($id, $token_value, $user_id, $account_number_id, $leWallet)
                if (!$result) {
                    #G 购买成功  大花
                    Tools::WriteLogger($this->data['user_id'], 2, "为特殊种子购买大的花盆失败", $this->data['account_number_id'], 2, $data_json['data']['_id']);
                    return false;  #到此为止

                }


                #推入浇水 进程
                $redis->rPush("Watering", $res . "@" . $this->data['account_number_id'] . "@" . $this->data['user_id']);  # 农场id 账户id  用户 id
                Tools::WriteLogger($this->data['user_id'], 2, "进程 PutPotProcess 放大花盆成功,并推入 WateringProcess 进程 First result:" . $response, $this->data['account_number_id'], 2, $data_json['data']['_id']);
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