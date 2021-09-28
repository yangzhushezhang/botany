<?php


namespace App\HttpController\User;


use App\Model\AccountNumberModel;
use App\Model\FarmModel;
use EasySwoole\ORM\DbManager;

/**
 * Class FarmInformationController
 * @package App\HttpController\User
 * 农场操作
 */
class FarmInformationController extends UserBase
{


    #刷单个植物或者全部的植物信息
    function refresh_botany()
    {
        $id = $this->request()->getParsedBody('id'); #需要刷新的 账号
        if (!$this->check_parameter($id, "账号id")) {
            return false;
        }
        try {
            return DbManager::getInstance()->invoke(function ($client) use ($id) {
                # 获取 农场的 接口
                $one = AccountNumberModel::invoke($client)->get(['id' => $id]);
                if (!$one) {
                    $this->writeJson(-101, [], "账户id 不存在");
                    return false;
                }
                $token_value = $one['token_value'];
                $client = new \EasySwoole\HttpClient\HttpClient('https://backend-farm.plantvsundead.com/farms?limit=10&offset=0');
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
                    'if-none-match' => 'W/"1bf5-RySZLkdJ7uwQuWZ+zLfe+hxM36c"',
                );
                $client->setHeaders($headers, false, false);
                $response = $client->get();
                $result = $response->getBody();
                $data = json_decode($result, true);
                if (!$data) {
                    $this->WriteLogger($this->who['id'], 2, "接口 refresh_botany json 解析失败");
                    $this->writeJson(-101, [], "接口  refresh_botany json 解析失败");
                    return false;
                }

                if ($data['status'] != 0) {
                    $this->WriteLogger($this->who['id'], 2, "接口 refresh_botany json status!=0");
                    $this->writeJson(-101, [], "接口 refresh_botany son status!=0");
                    return false;
                }

                #开始遍历 参数
                return DbManager::getInstance()->invoke(function ($client) use ($data, $id) {
                    foreach ($data['data'] as $k => $value) {
                        # 判断 农场 没有有 这个 种子 id
                        $one = FarmModel::invoke($client)->get(['account_number_id' => $id, 'farm_id' => $value['_id']]);
                        $unix = str_replace(array('T', 'Z'), ' ', $value['harvestTime']);
                        $needWater = 2;
                        $hasSeed = 2;
                        if ($value['needWater']) {
                            # 需要浇水  让进程去做这件事情

                            $needWater = 1;
                        }
                        if ($value['hasSeed']) {
                            #需要 放种子
                            $hasSeed = 1;
                        }
                        # 这里需要判断 有没有乌鸦    如果有乌鸦 我需要 仍在 进程里面来做这件事!!!!
                        $add = [
                            'account_number_id' => $id,
                            'farm_id' => $value['_id'],
                            'harvestTime' => strtotime($unix),
                            'needWater' => $needWater,
                            'hasSeed' => $hasSeed,
                            'updated_at' => time()
                        ];
                        #存在 只需要 做更新操作
                        if ($one) {
                            $two = FarmModel::invoke($client)->where(['account_number_id' => $id, 'farm_id' => $value['_id']])->update($add);
                            if (!$two) {
                                $this->WriteLogger($this->who['id'], 2, "接口 refresh_botany 更新数据的时候出错误");
                            }
                        } else {
                            # 插入操作
                            $add['created_at'] = time();
                            $two = FarmModel::invoke($client)->data($add)->save();
                            if (!$two) {
                                $this->WriteLogger($this->who['id'], 2, "接口 refresh_botany 插入数据的时候出错误");
                            }
                        }
                    }
                    $this->writeJson(200, [], "刷新完毕");
                    return true;
                });


            });
        } catch (\Throwable $e) {
            $this->writeJson(-1, [], "异常:" . $e->getMessage());
            $this->WriteLogger($this->who['id'], 2, "接口 refresh_botany 抛出了异常:" . $e->getMessage());
            return false;
        }
    }




}