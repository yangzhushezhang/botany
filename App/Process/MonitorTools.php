<?php


namespace App\Process;


use App\Model\AccountNumberModel;
use App\Model\ToolsModel;
use App\Tools\Tools;
use Cassandra\Date;
use EasySwoole\Component\Process\AbstractProcess;
use EasySwoole\HttpClient\Exception\InvalidUrl;
use EasySwoole\ORM\DbManager;
use PHPUnit\Framework\Constraint\IsFalse;


/**
 * Class MonitorTools
 * @package App\Process
 * 获取  能量 工具
 */
class MonitorTools extends AbstractProcess
{

    /**
     * @param $arg
     * @return mixed
     */
    protected function run($arg)
    {
        go(function () {
            var_dump("工具自行检查进程");
            while (true) {
                try {
                    Tools::WriteLogger(0, 2, "MonitorTools   开始 ", "", 7);
                    DbManager::getInstance()->invoke(function ($client) {
                        $fix = AccountNumberModel::invoke($client)->all(['status' => 1]);
                        if ($fix) {
                            # 更新 鼠标 和 向日葵 个数
                            foreach ($fix as $six) {
                                $one = ToolsModel::invoke($client)->get(['account_number_id' => $six['id']]);
                                if (!$one) {
                                    # 不存在这个 账号的工具 就 插入
                                    #获取 工具
                                    $data_json = $this->ToolsGet($six['token_value'], $six['user_id'], $six['id']);
                                    if (!$data_json) {
                                        continue;
                                    }
                                    $update_data = [
                                        'updated_at' => time(),
                                        'account_number_id' => $six['id']
                                    ];
                                    $update_data = $this->IfShopTools($data_json['data'], $six['token_value'], $six['user_id'], $six['id'], $six['leWallet']);
                                    ToolsModel::invoke($client)->data($update_data)->save();
                                    Tools::WriteLogger($six['user_id'], 2, "MonitorTools 工具插入成功", $six['id'], 7);

                                } else {
                                    # 更新
                                    $data_json = $this->ToolsGet($six['token_value'], $six['user_id'], $six['id']);
                                    if (!$data_json) {
                                        continue;
                                    }
                                    $update_data = [
                                        'updated_at' => time()
                                    ];
                                    $update_data = $this->IfShopTools($data_json['data'], $six['token_value'], $six['user_id'], $six['id'], $six['leWallet']);
                                    ToolsModel::invoke($client)->where(['account_number_id' => $six['id']])->update($update_data);
                                    Tools::WriteLogger($six['user_id'], 2, "MonitorTools refresh_tools  更新成功", $six['id'], 7);
                                }

                                $token_value = $six['token_value'];
                                # 更新我的 种子 个数
                                $data = Tools::getSunflowers($token_value);
                                if (!$data) {
                                    Tools::WriteLogger($six['user_id'], 2, "进程 MonitorTools   解析失败  result:" . $data, $six['id'], 7);
                                    continue;
                                }
                                if ($data['status'] != 0) {
                                    Tools::WriteLogger($six['user_id'], 2, "进程 MonitorTools     result: status ", $six['id'], 7);
                                    continue;
                                }
                                $update = [
                                    'updated_at' => time()
                                ];
                                foreach ($data['data'] as $datum) {  # 向日葵 有优先级
                                    if ($datum['plantType'] == 1) {
                                        $update['all_sapling'] = $datum['usages'];
                                        $update['already_sapling'] = $datum['total'];

                                    }
                                    if ($datum['plantType'] == 2) {
                                        $update['all_sunflower'] = $datum['usages'];
                                        $update['already_sunflower'] = $datum['total'];
                                    }
                                }
                                #  调整向日葵的优先级
                                if (!isset($update['already_sapling']) || $update['already_sapling'] == 0) {
                                    if (isset($update['already_sunflower']) || $update['already_sunflower'] > 0) {
                                        $po = $this->shopSeed($token_value, 1, $six['user_id'], $six['id']); #$token_value, $sunflowerId,$user_id,$account_number_id
                                        if ($po) {
                                            $update['already_sapling'] = 1;
                                        }
                                    }
                                }
                                # 向日葵 为0  购买  不存在需要购买
                                if (!isset($update['already_sunflower']) || $update['already_sunflower'] == 0) {
                                    $po = $this->shopSeed($token_value, 2, $six['user_id'], $six['id']); #$token_value, $sunflowerId,$user_id,$account_number_id
                                    if ($po) {
                                        $update['already_sunflower'] = 1;
                                    }
                                }
                                $two = AccountNumberModel::invoke($client)->where(['id' => $six['id']])->update($update);
                                # 获取 账号的能量
                                $data = Tools::getLeWallet($token_value);
                                if (!$data) {
                                    Tools::WriteLogger($six['user_id'], 2, "进程 MonitorTools   解析失败  result:" . $data, $six['id'], 7);
                                    continue;
                                }
                                if ($data['status'] != 0) {
                                    Tools::WriteLogger($six['user_id'], 2, "进程 MonitorTools     result: status " . $data['status'], $six['id'], 7);
                                    continue;
                                }
                                $two = AccountNumberModel::invoke($client)->where(['id' => $six['id']])->update(['updated_at' => time(), 'leWallet' => $data['data']['leWallet'], 'usagesSunflower' => $data['data']['usagesSunflower']]);
                                Tools::WriteLogger($six['user_id'], 2, "进程 MonitorTools     更新leWallet usagesSunflower  成功", $six['id'], 7);
                            }
                            \co::sleep(3); # 每个账号之间 间隔 5 秒钟
                        }
                    });
                    Tools::WriteLogger(0, 2, "MonitorTools   结束 ", "", 7);
                    \co::sleep(60 * 10); # 10 分钟执行一次
                } catch (\Throwable $exception) {
                    Tools::WriteLogger(0, 2, "MonitorTools 异常:" . $exception->getMessage(), "", 7);
                }

            }


        });
    }


    function Shop_tools($id, $token_value, $user_id, $account_number_id, $leWallet)
    {
        try {

            # 判断 自己的能量值是否 足够
            if ($id == 1 || $id == 3) {
                if ($leWallet < 50) {
                    Tools::WriteLogger($user_id, 2, "MonitorTools  购买工具:" . $id . " 失败  能量不够", $account_number_id, 6);
                    return false;
                }
            }
            if ($id == 4) {
                if ($leWallet < 20) {
                    Tools::WriteLogger($user_id, 2, "MonitorTools  购买工具:" . $id . " 失败  能量不够", $account_number_id, 6);
                    return false;
                }
            }
            for ($i = 0; $i < 5; $i++) {
                $client = new \EasySwoole\HttpClient\HttpClient('https://backend-farm.plantvsundead.com/buy-tools');
                $headers = array(
                    'authority' => 'backend-farm.plantvsundead.com',
                    'sec-ch-ua' => '"Google Chrome";v="93", " Not;A Brand";v="99", "Chromium";v="93"',
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
                $client->setHeaders($headers, false, false);
                $data = '{"amount":1,"toolId":' . $id . '}';
                $client->setTimeout(5);
                $client->setConnectTimeout(10);
                $response = $client->post($data);
                $result = $response->getBody();
                $data_json = json_decode($result, true);
                if (!$data_json) {
                    Tools::WriteLogger($user_id, 2, "MonitorTools  购买工具:" . $id . " 失败  原因:解析失败", $account_number_id, 6);
                    continue;
                }

                if ($data_json['status'] != 0) {
                    if ($data_json['status'] == 9) {
                        Tools::WriteLogger($user_id, 2, "MonitorTools  购买工具:" . $id . " 失败  原因:能量不足了" . $result, $account_number_id, 6);
                        break;
                    }
                    Tools::WriteLogger($user_id, 2, "MonitorTools  购买工具:" . $id . " 失败  原因:" . $result, $account_number_id, 6);
                    continue;
                }
                Tools::WriteLogger($user_id, 1, "购买工具 :" . $id . "成功", $account_number_id, 6);
                break;
            }


        } catch (InvalidUrl $e) {
            Tools::WriteLogger($user_id, 2, "购买 异常:" . $e->getMessage(), $account_number_id, 6);
            return false;

        }
    }


    protected function onException(\Throwable $throwable, ...$args)
    {
        Tools::WriteLogger(0, 2, "进程 MonitorTools 异常:" . $throwable->getMessage());
        parent::onException($throwable, $args); // TODO: Change the autogenerated stub
    }


    protected function onShutDown()
    {
        Tools::WriteLogger(0, 2, "进程 MonitorTools  onShutDown");
        parent::onShutDown(); // TODO: Change the autogenerated stub
    }


    function ToolsGet($token_value, $user_id, $account_number_id)
    {
        try {
            $result = "";
            for ($i = 0; $i < 5; $i++) {
                $client = new \EasySwoole\HttpClient\HttpClient('https://backend-farm.plantvsundead.com/my-tools');
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
                    #   'if-none-match' => 'W/^\\^32c-sAwO7sU/nng0IT4QwrYVX61WsEY^\\^',
                );
                $client->setHeaders($headers, false, false);
                $client->setTimeout(5);
                $client->setConnectTimeout(10);
                $response = $client->get();
                $result = $response->getBody();
                $data_json = json_decode($result, true);
                if ($data_json) {
                    if ($data_json['status'] != 0) {
                        Tools::WriteLogger($user_id, 2, "进程 MonitorTools    获取工具数据状态错误 " . $result, $account_number_id, 7);
                        return false;
                    }
                    return $data_json;
                }

            }
            Tools::WriteLogger($user_id, 2, "进程 MonitorTools    获取工具数据失败 result " . $result, $account_number_id, 7);
            return false;
        } catch (InvalidUrl $e) {
            return false;
        }
    }

    /**
     * @param $token_value
     * @param $sunflowerId
     * @param $user_id
     * @param $account_number_id
     * @return bool
     * @throws InvalidUrl  购买向日葵种子
     */
    function shopSeed($token_value, $sunflowerId, $user_id, $account_number_id)
    {
        for ($i = 0; $i < 5; $i++) {
            $client = new \EasySwoole\HttpClient\HttpClient('https://backend-farm.plantvsundead.com/buy-sunflowers');
            $headers = array(
                'authority' => 'backend-farm.plantvsundead.com',
                'sec-ch-ua' => '"Chromium";v="94", "Google Chrome";v="94", ";Not A Brand";v="99"',
                'accept' => 'application/json, text/plain, */*',
                'content-type' => 'application/json;charset=UTF-8',
                'authorization' => $token_value,
                'sec-ch-ua-mobile' => '?0',
                'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.61 Safari/537.36',
                'sec-ch-ua-platform' => '"Windows"',
                'origin' => 'https://marketplace.plantvsundead.com',
                'sec-fetch-site' => 'same-site',
                'sec-fetch-mode' => 'cors',
                'sec-fetch-dest' => 'empty',
                'referer' => 'https://marketplace.plantvsundead.com/',
                'accept-language' => 'zh-CN,zh;q=0.9',
            );
            $client->setHeaders($headers, false, false);
            $data = '{"amount":1,"sunflowerId":' . $sunflowerId . '}';
            $client->setTimeout(5);
            $client->setConnectTimeout(10);
            $response = $client->post($data);
            $result = $response->getBody();
            $data_json = json_decode($result, true);
            if ($data_json && $data_json['status'] == 0) {
                Tools::WriteLogger($user_id, 1, "购买向日葵/或者向日葵宝宝 成功", $account_number_id, 6);
                return true;
            }
        }
        Tools::WriteLogger($user_id, 2, "购买向日葵/或者向日葵宝宝 失败", $account_number_id, 6);
        return false;
    }


    function IfShopTools($data, $token_value, $user_id, $account_number_id, $leWallet)
    {

        $update_data = [
            'updated_at' => time()
        ];
        $one = false;
        $two = false;
        $three = false;
        foreach ($data as $k => $value) {
            if ($value['type'] == "WATER") { #水
                $one = true;
                $update_data['water'] = $value['usages'];
                if ($value['usages'] < 46) { #水小 25 直接就买水
                    $this->Shop_tools(3, $token_value, $user_id, $account_number_id, $leWallet);
                }
            }
            if ($value['toolId'] == 1) {
                $two = true;
                $update_data['samll_pot'] = $value['usages'];
                if ($value['usages'] < 1) {
                    $this->Shop_tools(1, $token_value, $user_id, $account_number_id, $leWallet);
                }
            }
            if ($value['type'] == "SCARECROW") {
                $three = true;
                $update_data['scarecrow'] = $value['usages'];
                if ($value['usages'] < 1) {
                    $this->Shop_tools(4, $token_value, $user_id, $account_number_id, $leWallet);
                }
            }
        }

        if (!$one) {
            $this->Shop_tools(3, $token_value, $user_id, $account_number_id, $leWallet);
        }
        if (!$three) {
            $this->Shop_tools(4, $token_value, $user_id, $account_number_id, $leWallet);
        }

        if (!$two) {
            $this->Shop_tools(1, $token_value, $user_id, $account_number_id, $leWallet);
        }
        return $update_data;
    }

}