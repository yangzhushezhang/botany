<?php


namespace App\Process;


use App\Model\AccountNumberModel;
use App\Model\SpecialSeedModel;
use App\Tools\Tools;
use EasySwoole\Component\Process\AbstractProcess;
use EasySwoole\HttpClient\Exception\InvalidUrl;
use EasySwoole\ORM\DbManager;

class SpecialSeedProcess extends AbstractProcess
{

    /**
     * @param $arg
     * @return mixed
     */
    protected function run($arg)
    {
        var_dump("检查特殊种子进程.....");
        go(function () {
            while (true) {
                DbManager::getInstance()->invoke(function ($client) {
                    $res = AccountNumberModel::invoke($client)->all(['status' => 1]);
                    if ($res) {
                        foreach ($res as $re) {
                            # 检查是否存在可以 孵化的种子
                            $one = $this->ClaimSeeds($re['token_value'], $re['id'], $re['user_id']);   #$token_value, $account_number_id, $user_id
                            if ($one) {
                                AccountNumberModel::invoke($client)->where(['id' => $re['id']])->update(['updated_at' => time(), 'claimSeeds' => 2]);
                                Tools::WriteLogger($re['user_id'], 2, "进程 SpecialSeedProcess  更新成功 发现了需要孵化的种子", $re['id'], 12);
                            } else {
                                AccountNumberModel::invoke($client)->where(['id' => $re['id']])->update(['updated_at' => time(), 'claimSeeds' => 1]);
                                Tools::WriteLogger($re['user_id'], 2, "进程 SpecialSeedProcess  更新成功 没有发现了需要孵化的种子", $re['id'], 12);
                            }
                            #更新需要孵化时间的
                            $this->GetSeedsInventory($re['token_value'], $re['id'], $re['user_id']);
                        }
                    }
                });
                \co::sleep(60 * 60); # 一小时检查一次
            };
        });
    }


    # 是否发现有 需要孵化前的种子
    function ClaimSeeds($token_value, $account_number_id, $user_id)
    {
        try {
            for ($i = 0; $i < 5; $i++) {
                $client = new \EasySwoole\HttpClient\HttpClient('https://backend-farm.plantvsundead.com/v3/claim-seeds');
                $headers = array(
                    'authority' => 'backend-farm.plantvsundead.com',
                    'sec-ch-ua' => '"Chromium";v="94", "Google Chrome";v="94", ";Not A Brand";v="99"',
                    'accept' => 'application/json, text/plain, */*',
                    'authorization' => $token_value,
                    'sec-ch-ua-mobile' => '?0',
                    'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.71 Safari/537.36',
                    'sec-ch-ua-platform' => '"Windows"',
                    'origin' => 'https://marketplace.plantvsundead.com',
                    'sec-fetch-site' => 'same-site',
                    'sec-fetch-mode' => 'cors',
                    'sec-fetch-dest' => 'empty',
                    'referer' => 'https://marketplace.plantvsundead.com/',
                    'accept-language' => 'zh-CN,zh;q=0.9',
                );
                $client->setHeaders($headers, false, false);
                $client->setTimeout(5);
                $client->setConnectTimeout(10);
                $response = $client->get();
                $result = $response->getBody();
                $data_json = json_decode($result, true);
                if ($data_json && $data_json['status'] == 0) {
                    if (count($data_json['data']['claimSeed']) != 0) {
                        Tools::WriteLogger($user_id, 2, "进程 SpecialSeedProcess  方法 发现了新的带孵化的中种子 ", $account_number_id, 12);
                        return true;
                    }
                    Tools::WriteLogger($user_id, 2, "进程 SpecialSeedProcess  方法 ClaimSeeds 数据获取成功 ", $account_number_id, 12);
                    return false;
                }
            }
            Tools::WriteLogger($user_id, 2, "进程 SpecialSeedProcess  方法 ClaimSeeds 数据获取失败 ", $account_number_id, 12);
            return false;
        } catch (InvalidUrl $e) {
            Tools::WriteLogger($user_id, 2, "进程 SpecialSeedProcess  方法 ClaimSeeds 异常:" . $e->getMessage(), $account_number_id, 12);
            return false;
        }
    }

    # 正在孵化的种子
    function GetSeedsInventory($token_value, $account_number_id, $user_id)
    {
        try {
            for ($i = 0; $i < 5; $i++) {
                $client = new \EasySwoole\HttpClient\HttpClient('https://backend-farm.plantvsundead.com/get-seeds-inventory?index=0&limit=15');
                $headers = array(
                    'authority' => 'backend-farm.plantvsundead.com',
                    'sec-ch-ua' => '"Chromium";v="94", "Google Chrome";v="94", ";Not A Brand";v="99"',
                    'accept' => 'application/json, text/plain, */*',
                    'authorization' => $token_value,
                    'sec-ch-ua-mobile' => '?0',
                    'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.71 Safari/537.36',
                    'sec-ch-ua-platform' => '"Windows"',
                    'origin' => 'https://marketplace.plantvsundead.com',
                    'sec-fetch-site' => 'same-site',
                    'sec-fetch-mode' => 'cors',
                    'sec-fetch-dest' => 'empty',
                    'referer' => 'https://marketplace.plantvsundead.com/',
                    'accept-language' => 'zh-CN,zh;q=0.9',
                );
                $client->setHeaders($headers, false, false);
                $client->setTimeout(5);
                $client->setConnectTimeout(10);
                $response = $client->get();
                $result = $response->getBody();
                $data_json = json_decode($result, true);
                if ($data_json && $data_json['status'] == 0) {
                    if (count($data_json['data']['seeds']) != 0) {
                        # 说明有正在孵化的种子  更新到数据库
                        DbManager::getInstance()->invoke(function ($client) use ($data_json, $account_number_id, $user_id) {
                            foreach ($data_json['data']['seeds'] as $datum) {
                                $one = SpecialSeedModel::invoke($client)->get(['plantId' => $datum['plantId'], 'account_number_id' => $account_number_id]);
                                if (!$one) {
                                    $add = [
                                        'account_number_id' => $account_number_id,
                                        'tokenId' => $datum['tokenId'],
                                        'growthTime' => $datum['growthTime'],
                                        'icon_URL' => $datum['icon_URL'],
                                        'plantId' => $datum['plantId'],
                                        'created_at' => time(),
                                        'updated_at' => time()
                                    ];
                                    $two = SpecialSeedModel::invoke($client)->data($add)->save();
                                    if (!$two) {
                                        Tools::WriteLogger($user_id, 2, "进程 SpecialSeedProcess  方法 GetSeedsInventory 插入数据失败", $account_number_id, 12);
                                    } else {
                                        Tools::WriteLogger($user_id, 2, "进程 SpecialSeedProcess  方法 GetSeedsInventory 插入数据成功", $account_number_id, 12);
                                    }
                                }
                            }
                        });
                    }
                    Tools::WriteLogger($user_id, 2, "进程 SpecialSeedProcess  方法 GetSeedsInventory 获取数据成功", $account_number_id, 12);
                    return $data_json;
                }
            }
            Tools::WriteLogger($user_id, 2, "进程 SpecialSeedProcess  方法 GetSeedsInventory 获取数据失败", $account_number_id, 12);
            return false;
        } catch (\Throwable $e) {
            Tools::WriteLogger($user_id, 2, "进程 SpecialSeedProcess  方法 GetSeedsInventory 异常:" . $e->getMessage(), $account_number_id, 12);
            return false;
        }
    }


}