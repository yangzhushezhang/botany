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

                $data_json = (new  Tools())->GteNewTools($one['token_value']);
                if (!$data_json) {
                    $this->writeJson(-101, [], "获取数据失败");
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
                        if ($value['toolId'] == 1) {
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
                            return false;
                        }

                    } else {
                        # 插入
                        $update_data['created_at'] = time();
                        $one = ToolsModel::invoke($client)->data($update_data)->save();
                        if (!$one) {
                            $this->WriteLogger($this->who['id'], 2, "接口 refresh_tools 插入失败");
                            return false;
                        }
                    }

                    $this->response()->write(json_encode($data_json));
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


    # 获取能量
    function getEnergy()
    {
        $id = $this->request()->getParsedBody('id'); #需要刷新的 账号
        if (!$this->check_parameter($id, "账号id")) {
            return false;
        }
        try {
            return DbManager::getInstance()->invoke(function ($client) use ($id) {
                $res = AccountNumberModel::invoke($client)->get(['id' => $id, 'user_id' => $this->who['id']]);
                if (!$res) {
                    $this->writeJson(-101, [], "查询的账号不存在");
                    return false;
                }
                $res = Tools::getLeWallet($res['token_value']);
                if (!$res) {
                    $this->writeJson(-101, [], "获取失败");
                    return false;
                }
                $this->response()->write(json_encode($res));
                return true;
            });
        } catch (\Throwable $e) {
            $this->writeJson(-1, [], $e->getMessage());
            return false;
        }
    }


}