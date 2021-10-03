<?php


namespace App\HttpController\User;


use App\Model\LoggerModel;
use EasySwoole\ORM\DbManager;

class LoggerController extends UserBase
{


    # 获取日志
    function getLogger()
    {
        # 只能获取自己的日志
        $limit = $this->request()->getParsedBody('limit');
        $page = $this->request()->getParsedBody('page');
        if (!$this->check_parameter($limit, "limit") || !$this->check_parameter($page, "page")) {
            return false;
        }
        try {
            return DbManager::getInstance()->invoke(function ($client) use ($limit, $page) {

                $model = LoggerModel::invoke($client)->limit($limit * ($page - 1), $limit)->withTotalCount();
                $list = $model->all(['user_id' => $this->who['id']]);
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
            });

        } catch (\Throwable $e) {
            $this->writeJson(-1, [], "异常:" . $e->getMessage());
            return false;
        }

    }

}