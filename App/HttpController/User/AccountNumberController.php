<?php


namespace App\HttpController\User;


use App\Model\AccountNumberModel;
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
                    'updated_at' => time()
                ];

                $two = AccountNumberModel::invoke($client)->data($data)->save();
                if (!$two) {
                    $this->writeJson(-101, [], "添加失败");
                    return false;
                }
                $this->writeJson(-101, [], "添加成功");
                return true;

            });
        } catch (\Throwable $e) {
            $this->writeJson(-1, [], "添加异常:" . $e->getMessage());
            return;
        }
    }





    # 删除植物账号  更新


    #更新能量总的能量
    function updated_leWallet()
    {






    }


}