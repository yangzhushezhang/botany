<?php


namespace App\HttpController\User;

use App\Model\LoggerModel;
use App\Model\UserModel;
use EasySwoole\Http\AbstractInterface\Controller;
use EasySwoole\ORM\DbManager;
use EasySwoole\ORM\Exception\Exception;
use EasySwoole\Pool\Exception\PoolEmpty;


class UserBase extends Controller
{


    protected $who;
    protected $white_router = array('/user/login');

    protected function onRequest(?string $action): ?bool
    {


        #token 校验
        $router_url = $this->request()->getServerParams()['request_uri'];;
        #白名单 不需要检查token
        if (in_array($router_url, $this->white_router)) {
            return true;
        } else {
            #判断 token 是否存在
            try {

                $token = $this->request()->getRequestParam('token');
                if (!isset($token) || empty($token)) {
                    $this->writeJson(-1, [], "token 不可以为空");
                    return false;
                }

                return DbManager::getInstance()->invoke(function ($client) use ($token) {
                    $one = UserModel::invoke($client)->get(['token' => $token]);
                    if ($one) {
                        # 赋值给  who
                        $this->who = $one->toArray();
                        return true;

                    }

                    var_dump("1111");
                    $this->writeJson(-1, [], "token 非法");
                    return false;
                });

            } catch (\Throwable $e) {
                $this->writeJson(-1, [], "非法参数!");
                return false;
            }
        }


    }


    #检查 参数是否缺少
    function check_parameter($parameter, $str)
    {
        if (isset($parameter) && !empty($parameter)) {
            return true;
        }

        $this->writeJson(-101, [], "参数缺少:" . $str);
        return false;
    }


    function WriteLogger($user_id, $kind, $content)
    {
        try {
            DbManager::getInstance()->invoke(function ($client) use ($user_id, $kind, $content) {

                $data = [
                    'content' => $content,
                    'user_id' => $user_id,
                    'kind' => $kind,
                    'updated_at' => time(),
                    'created_at' => time()
                ];
                LoggerModel::invoke($client)->data($data)->save();
            });
        } catch (\Throwable $e) {
            log("写日志异常:" . $e->getMessage());
        }
    }

}