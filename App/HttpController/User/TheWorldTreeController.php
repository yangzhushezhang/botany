<?php


namespace App\HttpController\User;


use App\Model\AccountNumberModel;
use App\Task\TheTreeFromWorldTask;
use EasySwoole\Http\AbstractInterface\Controller;
use EasySwoole\ORM\DbManager;
use EasySwoole\ORM\Exception\Exception;
use EasySwoole\Pool\Exception\PoolEmpty;

class TheWorldTreeController extends UserBase
{


    # 世界树 接口
    function TheWorldTree()
    {
        # 获取所有的行号
        try {

            $task = \EasySwoole\EasySwoole\Task\TaskManager::getInstance();
            $task->async(new TheTreeFromWorldTask(['user' => 'custom']));
            $this->writeJson(200, [], "调用成功");

        } catch (\Throwable $e) {


        }


    }

}