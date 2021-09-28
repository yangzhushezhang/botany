<?php


namespace App\Process;


use EasySwoole\Component\Process\AbstractProcess;

/**
 * Class ExpelRavenProcess
 * @package App\Process
 *   驱赶乌鸦
 */
class ExpelRavenProcess extends AbstractProcess
{

    protected function run($arg)
    {
        var_dump("这里一个驱逐乌鸦的进程");
        go(function () {


        });
    }
}