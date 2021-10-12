<?php


namespace App\HttpController\User;


use App\Model\AccountNumberModel;
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
        $variety = $this->request()->getParsedBody('variety');
        $account_number_id = $this->request()->getParsedBody('account_number_id');
        $farm_id = $this->request()->getParsedBody('farm_id');
        if (!$this->check_parameter($limit, "limit") || !$this->check_parameter($page, "page")) {
            return false;
        }
        try {
            return DbManager::getInstance()->invoke(function ($client) use ($limit, $page, $variety, $account_number_id,$farm_id) {
                $model = LoggerModel::invoke($client)->limit($limit * ($page - 1), $limit)->withTotalCount();



                if (isset($variety) && !empty($variety)) {
                    $model = $model->where(['variety' => $variety]);
                }

                if (isset($account_number_id) && !empty($account_number_id)) {
                    $model = $model->where(['account_number_id' => $account_number_id]);
                }


                if (isset($farm_id) && !empty($farm_id)) {
                    $model = $model->where(['farm_id' => $farm_id]);
                }


                $list = $model->all(['user_id' => $this->who['id']]);
                foreach ($list as $k => $item) {
                    if ($item['account_number_id'] != 0) {
                        $one = AccountNumberModel::invoke($client)->get(['id' => $item['account_number_id']]);
                        if ($one) {
                            $list[$k]['account_number_name'] = $one['remark'];
                        }
                    }
                }



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