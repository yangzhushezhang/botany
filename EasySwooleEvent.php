<?php


namespace EasySwoole\EasySwoole;


use EasySwoole\EasySwoole\AbstractInterface\Event;
use EasySwoole\EasySwoole\Swoole\EventRegister;
use EasySwoole\ORM\Db\Config;
use EasySwoole\ORM\Db\Connection;
use EasySwoole\ORM\DbManager;
use EasySwoole\RedisPool\Exception\Exception;
use EasySwoole\RedisPool\RedisPoolException;

class EasySwooleEvent implements Event
{
    public static function initialize()
    {
        date_default_timezone_set('Asia/Shanghai');

        \EasySwoole\Component\Di::getInstance()->set(\EasySwoole\EasySwoole\SysConst::HTTP_GLOBAL_ON_REQUEST, function (\EasySwoole\Http\Request $request, \EasySwoole\Http\Response $response): bool {
            ###### 处理请求的跨域问题 ######
            $response->withHeader('Access-Control-Allow-Origin', '*');
            $response->withHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
            $response->withHeader('Access-Control-Allow-Credentials', 'true');
            $response->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
            if ($request->getMethod() === 'OPTIONS') {
                $response->withStatus(\EasySwoole\Http\Message\Status::CODE_OK);
                return false;
            }
            return true;
        });

    }

    public static function mainServerCreate(EventRegister $register)
    {

        #注册mysql连接池
        $mysql_config = new Config(\EasySwoole\EasySwoole\Config::getInstance()->getConf('MYSQL'));
        DbManager::getInstance()->addConnection(new Connection($mysql_config));

        #注册redis连接池
        $redis_config = new \EasySwoole\Redis\Config\RedisConfig(\EasySwoole\EasySwoole\Config::getInstance()->getConf('REDIS'));
        try {
            \EasySwoole\RedisPool\RedisPool::getInstance()->register($redis_config, 'redis');
        } catch (Exception $e) {
        } catch (RedisPoolException $e) {

        }



        /**
         * 注册 浇水 进程
         */
        $processConfig = new  \EasySwoole\Component\Process\Config([
            'processName' => 'WateringProcess', // 设置 进程名称为 TickProcess
            'processGroup' => 'Custom_one', // 设置 进程组名称为 Tick
            'arg' => [

            ], // 传递参数到自定义进程中
            'enableCoroutine' => true, // 设置 自定义进程自动开启协程环境
        ]);
        $PeriodsProcess = (new \App\Process\WateringProcess($processConfig));
        \EasySwoole\Component\Di::getInstance()->set('WateringProcess', $PeriodsProcess->getProcess());
        \EasySwoole\Component\Process\Manager::getInstance()->addProcess($PeriodsProcess);



        /**
         * 注册 驱逐乌鸦 进程
         * ExpelRavenProcess
         */
        $processConfig = new  \EasySwoole\Component\Process\Config([
            'processName' => 'ExpelRavenProcess', // 设置 进程名称为 TickProcess
            'processGroup' => 'Custom_one', // 设置 进程组名称为 Tick
            'arg' => [

            ], // 传递参数到自定义进程中
            'enableCoroutine' => true, // 设置 自定义进程自动开启协程环境
        ]);
        $PeriodsProcess = (new \App\Process\ExpelRavenProcess($processConfig));
        \EasySwoole\Component\Di::getInstance()->set('ExpelRavenProcess', $PeriodsProcess->getProcess());
        \EasySwoole\Component\Process\Manager::getInstance()->addProcess($PeriodsProcess);









    }
}