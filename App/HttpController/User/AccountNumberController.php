<?php


namespace App\HttpController\User;


use App\Model\AccountNumberModel;
use App\Tools\Tools;
use EasySwoole\ORM\DbManager;
use EasySwoole\ORM\Exception\Exception;
use EasySwoole\Pool\Exception\PoolEmpty;

/**
 * Class AccountNumberController
 * @package App\HttpController\User
 * 账户 控制台
 */
class AccountNumberController extends UserBase
{

    #添加 植物账号
    function add_account_number()
    {
        try {
            return DbManager::getInstance()->invoke(function ($client) {
                $remark = $this->request()->getParsedBody('remark');
                $token_value = $this->request()->getParsedBody('token_value');  #协议头的 token
                if (!$this->check_parameter($remark, "remark") || !$this->check_parameter($token_value, "$token_value")) {
                    return false;
                }
                #判断协议头md5 是否存在
                $token_md5 = md5($token_value);
                $one = AccountNumberModel::invoke($client)->get(['token_md5' => $token_md5]);
                if ($one) {
                    $this->writeJson(-101, [], "不要重复添加");
                    return false;
                }

                $data = [
                    'user_id' => $this->who['id'],
                    'token_value' => $token_value,
                    'token_md5' => md5($token_value),
                    'status' => 1,
                    'remark' => $remark,
                    'created_at' => time(),
                    'updated_at' => time(),

                ];

                $two = AccountNumberModel::invoke($client)->data($data)->save();
                if (!$two) {
                    $this->writeJson(-101, [], "添加失败");
                    return false;
                }
                $this->writeJson(200, $two, "添加成功");
                return true;

            });
        } catch (\Throwable $e) {
            $this->writeJson(-1, [], "添加异常:" . $e->getMessage());
            return false;
        }
    }


    # 删除植物账号  更新
    # 获取农场植物信息
    function get_account_numberInformation()
    {
        try {
            $limit = $this->request()->getParsedBody('limit');
            $page = $this->request()->getParsedBody('page');
            $action = $this->request()->getParsedBody('action');
            if (!$this->check_parameter($limit, "limit") || !$this->check_parameter($page, "page") || !$this->check_parameter($page, "action")) {
                return false;
            }
            DbManager::getInstance()->invoke(function ($client) use ($limit, $page, $action) {
                if ($action == "select") {
                    $model = AccountNumberModel::invoke($client)->limit($limit * ($page - 1), $limit)->withTotalCount();
                    $list = $model->all(['user_id' => $this->who['id'], "status" => 1]);
                    $result = $model->lastQueryResult();
                    $total = $result->getTotalCount();
                    $return_data = [
                        "code" => 0,
                        "msg" => '',
                        'count' => $total,
                        'data' => $list
                    ];
                    $this->response()->write(json_encode($return_data));
                    return true;
                }

                $data = [
                    'updated_at' => time()
                ];
                if ($action == "update") {
                    $id = $this->request()->getParsedBody('id');
                    if (!$this->check_parameter($id, "账户id")) {
                        return false;
                    }

                    $token_value = $this->request()->getParsedBody('token_value');
                    if (isset($token_value) && !empty($token_value)) {
                        $data['token_value'] = $token_value;
                        $data['token_md5'] = md5($token_value);
                    }

                    $remark = $this->request()->getParsedBody('remark');
                    if (isset($remark) && !empty($remark)) {
                        $data['remark'] = $remark;
                    }


                    $status = $this->request()->getParsedBody('status');
                    if (isset($status) && !empty($status)) {
                        $data['status'] = $status;
                    }

                    $one = AccountNumberModel::invoke($client)->where(['id' => $id])->update($data);
                    if (!$one) {
                        $this->writeJson(-101, [], "修改失败");
                        return false;
                    }
                    $this->writeJson(200, [], "修改成功");
                    return true;
                }

                $this->writeJson(-101, [], "无效访问");
                return false;


            });

        } catch (\Throwable $e) {
            $this->writeJson(-1, [], "获取异常:" . $e->getMessage());
            return;
        }
    }


    #更新能量总的能量
    function updated_leWallet()
    {

        try {

            $id = $this->request()->getRequestParam('id');
            if (!$this->check_parameter($id, "账户id")) {
                return false;
            }

            return DbManager::getInstance()->invoke(function ($client) use ($id) {
                $one = AccountNumberModel::invoke($client)->get(['id' => $id]);
                if (!$one) {
                    $this->writeJson(-101, [], "非法账户");
                    return false;
                }


                $data = Tools::getLeWallet($one['token_value']);
                if (!$data) {
                    $this->writeJson(-101, [], "获取失败");
                    return false;

                }

                if ($data['status'] != 0) {
                    $this->writeJson(-101, [], "获取失败 status:" . $data['status']);
                    return false;
                }

                $two = AccountNumberModel::invoke($client)->where(['id' => $id])->update(['updated_at' => time(), 'leWallet' => $data['data']['leWallet']]);
                if (!$two) {
                    $this->writeJson(-101, [], "更新 leWallet 失败");
                    return false;
                }

                $this->writeJson(200, $data, "获取成功");
                return true;

            });
        } catch (\Throwable $e) {
            $this->writeJson(-1, [], "updated_leWallet 异常:" . $e->getMessage());
            return false;
        }
    }


    # 更新 种子 和向日葵 个数
    function update_sunflowers()
    {

        try {

            $id = $this->request()->getRequestParam('id');
            if (!$this->check_parameter($id, "账户id")) {
                return false;
            }

            return DbManager::getInstance()->invoke(function ($client) use ($id) {
                $one = AccountNumberModel::invoke($client)->get(['id' => $id]);
                if (!$one) {
                    $this->writeJson(-101, [], "非法账户");
                    return false;
                }


                $data = Tools::getSunflowers($one['token_value']);
                if (!$data) {
                    $this->writeJson(-101, [], "获取失败");
                    return false;

                }

                if ($data['status'] != 0) {
                    $this->writeJson(-101, [], "获取失败 status:" . $data['status']);
                    return false;
                }


                $update = [
                    'updated_at' => time()
                ];
                foreach ($data['data'] as $datum) {
                    if ($datum['plantType'] = 1) {
                        $update['all_sapling'] = $datum['usages'];
                        $update['already_sapling'] = $datum['total'];

                    }

                    if ($datum['plantType'] == 2) {
                        $update['all_sunflower'] = $datum['usages'];
                        $update['already_sunflower'] = $datum['total'];
                    }
                }


                $two = AccountNumberModel::invoke($client)->where(['id' => $id])->update(['updated_at' => time(), 'all_sapling' => $data['data']['leWallet']]);
                if (!$two) {
                    $this->writeJson(-101, [], "更新 leWallet 失败");
                    return false;
                }

                $this->writeJson(200, $data, "获取成功");
                return true;

            });
        } catch (\Throwable $e) {
            $this->writeJson(-1, [], "updated_leWallet 异常:" . $e->getMessage());
            return false;
        }
    }


}