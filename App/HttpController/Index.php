<?php


namespace App\HttpController;


use App\Model\AccountNumberModel;
use App\Task\GetAnswerTask;
use App\Tools\Tools;
use EasySwoole\Http\AbstractInterface\Controller;

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


        $res = AccountNumberModel::create()->all();
        foreach ($res as $re) {

            $add = [
                'token_value' => trim($re['token_value'])
            ];


            AccountNumberModel::create()->where(['id' => $re['id']])->update($add);

        }

        $this->writeJson(200, [], []);


    }

    protected function actionNotFound(?string $action)
    {
        $this->response()->withStatus(404);
        $file = EASYSWOOLE_ROOT . '/vendor/easyswoole/easyswoole/src/Resource/Http/404.html';
        if (!is_file($file)) {
            $file = EASYSWOOLE_ROOT . '/src/Resource/Http/404.html';
        }
        $this->response()->write(file_get_contents($file));
    }
}