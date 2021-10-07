<?php


namespace App\Process;


use App\Model\AccountNumberModel;
use App\Model\FarmModel;
use App\Tools\Tools;
use EasySwoole\Component\Process\AbstractProcess;
use EasySwoole\HttpClient\Exception\InvalidUrl;
use EasySwoole\ORM\DbManager;
use EasySwoole\RedisPool\RedisPool;
use function Couchbase\basicEncoderV1;


/**
 * Class CheckIfRipe
 * @package App\Process
 * 检查是否  有已经成熟的种子  该进程 只检查是否有  成熟的  活着 乌鸦的存在
 */
class CheckIfRipe extends AbstractProcess
{

    /**
     * @param $arg
     * @return mixed
     */
    protected function run($arg)
    {
        go(function () {
            var_dump("CheckIfRipe 进程 检查成熟果实和乌鸦");
            while (true) {
                try {
                    DbManager::getInstance()->invoke(function ($client) {
                        $res = FarmModel::invoke($client)->all(['status' => 1]);
                        if ($res) {
                            foreach ($res as $k => $re) {
                                if ($re['harvestTime'] < time()) {
                                    # 说明这个 种子已经成熟了  理论上可以收获了    可以收获了  但是要先请求下  接口是否有乌鸦的 导致 收获验证
                                    var_dump($re['account_number_id']);
                                    $one = AccountNumberModel::invoke($client)->get(['id' => $re['account_number_id']]);
                                    if (!$one) {
                                        Tools::WriteLogger(0, 2, "CheckIfRipe 进程请求 账号:" . $one['id'] . " 不存在", $variety = 12);
                                        continue;
                                    }
                                    $data = $this->getFarms($one['token_value'], $one['user_id'], $one['id'], $re['farm_id']);
                                    if (!$data) {
                                        continue;
                                    }
                                    if ($data['status'] != 0) {
                                        Tools::WriteLogger($one['user_id'], 2, "进程 CheckIfRipe 数据状态错误 result:" . json_encode($data), $one['id'], 12, $re['farm_id']);
                                        continue;
                                    }
                                    foreach ($data['data'] as $datum) {  # 对所有种子 进行 遍历
                                        if ($datum['_id'] == $re['farm_id']) {
                                            # 说明可以收获了
                                            if ($datum['stage'] == "cancelled" && $datum['totalHarvest'] != 0) {
                                                # 可以收获了
                                                $redis = RedisPool::defer('redis');
                                                $redis->rPush("Harvest_Fruit", $re['id'] . "@" . $re['account_number_id'] . "@" . $one['user_id']);  #种子的 id 种子的  账户id
                                                Tools::WriteLogger($one['user_id'], 2, "进程 CheckIfRipe 种子已经成熟,准备摘取,将其推出HarvestFruitProcess 进程", $one['id'], 12, $re['farm_id']);
                                            } else if ($datum['stage'] == "paused") {
                                                # 有乌鸦
                                                $redis = RedisPool::defer('redis');
                                                $redis->rPush("CROW_IDS", $re['id'] . "@" . $re['account_number_id'] . "@" . $one['user_id']);  #种子的 id 种子的  账户id
                                                Tools::WriteLogger($one['user_id'], 2, "进程 CheckIfRipe 种子发现乌鸦,将其推出HarvestFruitProcess 进程", $one['id'], 12, $re['farm_id']);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    });
                    \co::sleep(85); # 85 秒执行一次 检查 是否有成熟的种子
                } catch (\Throwable $exception) {
                    Tools::WriteLogger(0, 2, "CheckIfRipe 进程请求 异常:" . $exception->getMessage());
                }
            }
        });
    }


    function getFarms($token_value, $user_id, $account_number_id, $farm_id)
    {
        try {
            $result = "";
            for ($i = 0; $i < 5; $i++) {
                $client_http = new \EasySwoole\HttpClient\HttpClient('https://backend-farm.plantvsundead.com/farms?limit=10&offset=0');
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
                    #  'if-none-match' => 'W/"1bf5-RySZLkdJ7uwQuWZ+zLfe+hxM36c"',
                );
                $client_http->setHeaders($headers, false, false);
                $client_http->setTimeout(5);
                $client_http->setConnectTimeout(10);
                $response = $client_http->get();
                $result = $response->getBody();
                $data = json_decode($result, true);
                if ($data && $data['status'] == 0) {
                    return $data;
                }
            }
            Tools::WriteLogger($user_id, 2, "进程 CheckIfRipe 数据错误 result:" . $result, $account_number_id, 12, $farm_id);
            return false;
        } catch (InvalidUrl $e) {
            Tools::WriteLogger($user_id, 2, "进程 CheckIfRipe 数据异常:" . $e->getMessage(), $account_number_id, 12, $farm_id);
            return false;
        }
    }


    protected function onException(\Throwable $throwable, ...$args)
    {
        Tools::WriteLogger(0, 2, "进程 CheckIfRipe 异常:" . $throwable->getMessage());
        parent::onException($throwable, $args); // TODO: Change the autogenerated stub
    }


    protected function onShutDown()
    {
        Tools::WriteLogger(0, 2, "进程 CheckIfRipe  onShutDown");
        parent::onShutDown(); // TODO: Change the autogenerated stub
    }
}