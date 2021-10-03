<?php


namespace App\Task;


use EasySwoole\Task\AbstractInterface\TaskInterface;

class GetFarmsTask implements TaskInterface
{


    protected $data;

    public function __construct($data)
    {
        // 保存投递过来的数据
        $this->data = $data;

    }

    /**
     * @param int $taskId
     * @param int $workerIndex
     * @return mixed
     */
    function run(int $taskId, int $workerIndex)
    {


    }

    /**
     * @param \Throwable $throwable
     * @param int $taskId
     * @param int $workerIndex
     * @return mixed
     */
    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        // TODO: Implement onException() method.
    }
}