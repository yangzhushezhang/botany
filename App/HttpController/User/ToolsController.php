<?php


namespace App\HttpController\User;


use App\Model\AccountNumberModel;
use App\Model\FarmModel;
use App\Model\ToolsModel;
use App\Tools\Tools;
use EasySwoole\ORM\DbManager;
use EasySwoole\ORM\Exception\Exception;
use EasySwoole\Pool\Exception\PoolEmpty;

/**
 * Class ToolsController
 * @package App\HttpController\User
 * 工具控制器
 */
class ToolsController extends UserBase
{
    #更新 工具  水 稻草  p盆
    function refresh_tools()
    {
        $id = $this->request()->getParsedBody('id'); #需要刷新的 账号
        if (!$this->check_parameter($id, "账号id")) {
            return false;
        }

        try {
            return DbManager::getInstance()->invoke(function ($client) use ($id) {
                $one = AccountNumberModel::invoke($client)->get(['id' => $id]);
                if (!$one) {
                    $this->writeJson(-101, [], "账户id 不存在");
                    return false;
                }
                $token_value = $one['token_value'];
                $client = new \EasySwoole\HttpClient\HttpClient('https://backend-farm.plantvsundead.com/my-tools');
                $headers = array(
                    'authority' => 'backend-farm.plantvsundead.com',
                    'sec-ch-ua' => '^\\^Google',
                    'accept' => 'application/json, text/plain, */*',
                    'authorization' => $one['token_value'],
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
                $response = $client->get();
                $result = $response->getBody();


                $data_json = json_decode($result, true);

                if (!$data_json) {
                    $this->WriteLogger($this->who['id'], 2, "接口 refresh_tools json 解析失败");
                    $this->writeJson(-101, [], "接口  refresh_tools json 解析失败");
                    return false;
                }
                if ($data_json['status'] != 0) {
                    $this->WriteLogger($this->who['id'], 2, "接口 refresh_tools json status!=0");
                    $this->writeJson(-101, [], "接口 refresh_tools son status!=0");
                    return false;
                }

                return DbManager::getInstance()->invoke(function ($client) use ($data_json, $id) {

                    #判断是否存在这个账号的
                    $one = ToolsModel::invoke($client)->get(['account_number_id' => $id]);
                    $update_data = [
                        'account_number_id' => $id
                    ];
                    foreach ($data_json['data'] as $k => $value) {

                        if ($value['type'] == "WATER") {
                            $update_data['water'] = $value['usages'];
                        }
                        if ($value['type'] == "POT") {
                            $update_data['samll_pot'] = $value['usages'];
                        }
                        if ($value['type'] == "SCARECROW") {
                            $update_data['scarecrow'] = $value['usages'];
                        }
                    }


                    $update_data['updated_at'] = time();
                    if ($one) {
                        # 更新
                        $one = ToolsModel::invoke($client)->where(['account_number_id' => $id])->update($update_data);
                        if (!$one) {
                            $this->WriteLogger($this->who['id'], 2, "接口 refresh_tools 更新失败");
                            $this->writeJson(-101, [], "接口 refresh_tools 更新失败");
                            return false;
                        }

                    } else {
                        # 插入
                        $update_data['created_at'] = time();
                        $one = ToolsModel::invoke($client)->data($update_data)->save();
                        if (!$one) {
                            $this->WriteLogger($this->who['id'], 2, "接口 refresh_tools 插入失败");
                            $this->writeJson(-101, [], "接口 refresh_tools 插入失败");
                            return false;
                        }
                    }
                    $this->writeJson(200, [], "调用成功");
                    return true;

                });


            });

        } catch (\Throwable $e) {
            $this->writeJson(-1, [], "异常:" . $e->getMessage());
            $this->WriteLogger($this->who['id'], 2, "接口:refresh_tools 抛出了异常:" . $e->getMessage());
            return false;

        }
    }


    # 获取工具信息
    function get_tools()
    {
        $id = $this->request()->getParsedBody('id'); #需要刷新的 账号
        if (!$this->check_parameter($id, "账号id")) {
            return false;
        }
        try {
            return DbManager::getInstance()->invoke(function ($client) use ($id) {
                $res = ToolsModel::invoke($client)->get(['account_number_id' => $id]);
                $this->writeJson(200, $res, "获取成功");
                return true;


            });

        } catch (\Throwable $e) {
            $this->writeJson(-1, [], $e->getMessage());
            return false;

        }

    }


}