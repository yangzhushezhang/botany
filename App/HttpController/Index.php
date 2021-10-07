<?php


namespace App\HttpController;


use App\Model\AccountNumberModel;
use App\Task\GetAnswerTask;
use App\Tools\Tools;
use EasySwoole\Http\AbstractInterface\Controller;
use EasySwoole\RedisPool\RedisPool;

class Index extends Controller
{

    public function index()
    {
        $file = EASYSWOOLE_ROOT . '/vendor/easyswoole/easyswoole/src/Resource/Http/welcome.html';
        if (!is_file($file)) {
            $file = EASYSWOOLE_ROOT . '/src/Resource/Http/welcome.html';
        }
        $this->response()->write(file_get_contents($file));
    }

    function test()
    {
//        $res = AccountNumberModel::create()->all();
//        foreach ($res as $re) {
//            $add = [
//                'token_value' => trim($re['token_value'])
//            ];
//            AccountNumberModel::create()->where(['id' => $re['id']])->update($add);
//
//        }
        $res = AccountNumberModel::create()->all(['status' => 1]);
        if ($res) {
            foreach ($res as $re) {
                $redis = RedisPool::defer('redis');
                $redis_data = $redis->hGet(Date("Y-m-d", time()) . "_worldTree", "account_" . $re['id']);
                var_dump($redis_data);
                $redis_array = json_decode($redis_data, true);


//                if ($redis_array['water'] == 0) {
//                    var_dump("账号 备注:" . $re['remark'] . "浇水返回:" . $redis_array['water']);
//                    var_dump("账号 id:" . $re['id'] . "浇水返回:" . $redis_array['water']);
//
//                }
            }
        }


        $this->writeJson(200, [], []);


    }

    protected
    function actionNotFound(?string $action)
    {
        $this->response()->withStatus(404);
        $file = EASYSWOOLE_ROOT . '/vendor/easyswoole/easyswoole/src/Resource/Http/404.html';
        if (!is_file($file)) {
            $file = EASYSWOOLE_ROOT . '/src/Resource/Http/404.html';
        }
        $this->response()->write(file_get_contents($file));
    }
}