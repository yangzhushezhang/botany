<?php

namespace App\Process;

use EasySwoole\Component\Process\AbstractProcess;


/**
 * Class WateringProcess
 * @package App\Process
 * 浇水
 */
class WateringProcess extends AbstractProcess
{

    protected function run($arg)
    {
        var_dump("这是一个浇水的进程");
        go(function () {


        });
    }
}