<?php


namespace App\Process;

use App\Model\AccountNumberModel;
use App\Model\UserModel;
use App\Task\GetAnswerTask;
use App\Tools\Tools;
use EasySwoole\Component\Process\AbstractProcess;
use EasySwoole\ORM\DbManager;
use EasySwoole\RedisPool\RedisPool;

/**
 * Class DecryptCaptchaProcess
 * @package App\Process
 * 破解验证码
 */
class DecryptCaptchaProcess extends AbstractProcess
{

    /**
     * @param $arg
     * @return mixed
     */
    protected function run($arg)
    {
        var_dump("监听验证码进程");
        $redis = RedisPool::defer("redis");
        $redis->del("DecryptCaptcha");
        go(function () {

            while (true) {

                \EasySwoole\RedisPool\RedisPool::invoke(function (\EasySwoole\Redis\Redis $redis) {
                    $id = $redis->rPop("DecryptCaptcha");  # 账户id  用户id
                    if ($id) {
                        $array_ids = explode("@", $id);
                        if (count($array_ids) == 2) {
                            DbManager::getInstance()->invoke(function ($client) use ($array_ids, $redis, $id) {
                                $one = AccountNumberModel::invoke($client)->get(['id' => $array_ids[0]]);
                                if (!$one) {
                                    Tools::WriteLogger($one['user_id'], 2, "DecryptCaptchaProcess 进程请求 账号:" . $array_ids[0] . " 不存在", $one['id'], 5);
                                    return false;
                                }

                                $two = UserModel::invoke($client)->get(['id' => $array_ids[1]]);
                                if (!$two || !isset($two['API_KEY']) || empty($two['API_KEY'])) {
                                    Tools::WriteLogger($one['user_id'], 2, "DecryptCaptchaProcess 进程请求 用户:" . $array_ids[1] . " 不存在", $one['id'], 5);
                                    return false;
                                }

                                $client_http = new \EasySwoole\HttpClient\HttpClient('https://backend-farm.plantvsundead.com/captcha/register');
                                #请求验证码
                                $headers = array(
                                    'authority' => 'backend-farm.plantvsundead.com',
                                    'sec-ch-ua' => '"Chromium";v="94", "Google Chrome";v="94", ";Not A Brand";v="99"',
                                    'accept' => 'application/json, text/plain, */*',
                                    'authorization' => $one['token_value'],
                                    'sec-ch-ua-mobile' => '?0',
                                    'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.61 Safari/537.36',
                                    'sec-ch-ua-platform' => '"Windows"',
                                    'origin' => 'https://marketplace.plantvsundead.com',
                                    'sec-fetch-site' => 'same-site',
                                    'sec-fetch-mode' => 'cors',
                                    'sec-fetch-dest' => 'empty',
                                    'referer' => 'https://marketplace.plantvsundead.com/',
                                    'accept-language' => 'zh-CN,zh;q=0.9',
                                    #  'if-none-match' => 'W/"8b-dEn/wjw5llRe+ho4bIKY6gjytcs"',
                                );
                                $client_http->setHeaders($headers, false, false);
                                $response = $client_http->get();
                                $response = $response->getBody();
                                $data = json_decode($response, true);
                                if (!$data) {
                                    Tools::WriteLogger($one['user_id'], 2, "DecryptCaptchaProcess 进程请求  获取验证码 错误 账号:" . $one['id'] . " 请求返回的解析参数失败 result:" . $response, $one['id'], 5);
                                    # 重新吧 值 推到 进程重新解析
                                    \EasySwoole\Component\Timer::getInstance()->after(10 * 1000, function () use ($id, $redis) {  # 10 秒后
                                        $redis->rpush("DecryptCaptcha", $id);
                                        $redis->set("IfDoingVerification", 1, 600);# 时间重置
                                    });
                                    # 这个地方再做处理
                                    return false;
                                }


                                if ($data['status'] != 0) {
                                    Tools::WriteLogger($one['user_id'], 2, "DecryptCaptchaProcess 进程请求  获取验证码 错误 账号:" . $one['id'] . " 请求返回的解析参数失败 result:" . $response, $one['id'], 5);
                                    # 重新吧 值 推到 进程重新解析
                                    \EasySwoole\Component\Timer::getInstance()->after(10 * 1000, function () use ($id, $redis) {  # 10 秒后
                                        $redis->rpush("DecryptCaptcha", $id);
                                        $redis->set("IfDoingVerification", 1, 600);# 时间重置
                                    });
                                    # 这个地方再做处理
                                    return false;
                                }


                                Tools::WriteLogger($one['user_id'], 2, "DecryptCaptchaProcess 进程请求  请求验证码成功  result:" . $response, $one['id'], 5);

                                # 把值 传给 2captcha
                                $gt = $data['data']['gt'];
                                $challenge = $data['data']['challenge'];
                                $API_KEY = $two['API_KEY'];  #
                                $method = "geetest";
                                $pageUrl = "https://marketplace.plantvsundead.com/#/farm/";
                                $api_server = "yumchina.geetest.com";
                                $captcha_url = "https://2captcha.com/in.php?key=" . $API_KEY . "&method=" . $method . "&gt=" . $gt . "&challenge=" . $challenge . "&json=1&pageurl=" . $pageUrl . "&api_server=" . $api_server;
                                $client_http = new \EasySwoole\HttpClient\HttpClient($captcha_url);
                                $response_two = $client_http->get();
                                $response_two = $response_two->getBody();
                                #解析
                                $data_two = json_decode($response_two, true);
                                if (!$data_two || $data_two['status'] != 1) {
                                    # 验证码上传失败
                                    Tools::WriteLogger($one['user_id'], 2, "DecryptCaptchaProcess 进程请求  上传验证码 错误 账号:" . $one['id'] . " 请求返回的解析参数失败 result:" . $response_two, $one['id'], 5);
                                    # 验证码 错误 重新请求
                                    \EasySwoole\Component\Timer::getInstance()->after(10 * 1000, function () use ($id, $redis) {  # 10 秒后
                                        $redis->rpush("DecryptCaptcha", $id);
                                        $redis->set("IfDoingVerification", 1, 600);# 时间重置
                                    });
                                    return false;
                                }


                                # 上传验证码 成功   推进一个  一个是 任务
                                #  var_dump("验证码上传成功...........".$response);
                                # var_dump("验证码上传成功...........".$response_two);
                                Tools::WriteLogger($one['user_id'], 2, "DecryptCaptchaProcess 进程请求  验证码上传成功 :" . $response_two . $response_two, $one['id'], 5);
                                $task = \EasySwoole\EasySwoole\Task\TaskManager::getInstance();
                                $task->async(new GetAnswerTask([
                                    'user_id' => $one['user_id'],
                                    'api_key' => $API_KEY,
                                    'id' => $data_two['request'],
                                    'token_value' => $one['token_value'],
                                    'account_number_id' => $one['id']
                                ]));


                            });


                        }
                    }


                }, "redis");
                \co::sleep(5); # 五秒循环一次
            }
        });
    }
}