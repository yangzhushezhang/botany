<?php


namespace App\HttpController\User;


use App\Model\UserModel;
use EasySwoole\ORM\DbManager;
use EasySwoole\ORM\Exception\Exception;
use EasySwoole\Pool\Exception\PoolEmpty;

class LoginController extends UserBase
{


    /**
     * @return bool
     *  登录
     */
    function login()
    {
        $username = $this->request()->getParsedBody('username');
        $password = $this->request()->getParsedBody('password');

        if (!$this->check_parameter($username, "用户名") || !$this->check_parameter($password, "密码")) {
            return false;
        }

        try {
            DbManager::getInstance()->invoke(function ($client) use ($username, $password) {


                $one = UserModel::invoke($client)->get(['username' => $username]);
                if (!$one) {
                    $this->writeJson(-101, [], "登录失败,账号或者密码错误");
                    return false;
                }

                if ($one['username'] != $password) {
                    $this->writeJson(-101, [], "登录失败,账号或者密码错误");
                    return false;
                }


                $this->writeJson(200, $one, "登录成功");
                return false;

            });
        } catch (\Throwable $e) {
            $this->writeJson(-1, [], "登录异常:" . $e->getMessage());
            return false;
        }
    }




    # 修改 添加 API_KEY
    function set_API_KEY()
    {
        $API_KEY = $this->request()->getParsedBody('API_KEY');

        if (!$this->check_parameter($API_KEY, "API_KEY")) {
            return false;
        }

        try {
            DbManager::getInstance()->invoke(function ($client) use ($API_KEY) {

                $one = UserModel::invoke($client)->where(['id' => $this->who['id']])->update(['API_KEY' => $API_KEY]);

                if (!$one) {
                    $this->writeJson(-101, [], "修改失败");
                    return false;
                }

                $this->writeJson(200, [], "修改成功");
                return true;
            });
        } catch (\Throwable $e) {
            $this->writeJson(-1, [], "异常:" . $e->getMessage());
            return false;
        }

    }

}