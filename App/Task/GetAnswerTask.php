<?php


namespace App\Task;


use App\Tools\Tools;
use EasySwoole\RedisPool\RedisPool;
use EasySwoole\Task\AbstractInterface\TaskInterface;

class GetAnswerTask implements TaskInterface
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
     * @throws \EasySwoole\HttpClient\Exception\InvalidUrl
     * @throws \EasySwoole\Redis\Exception\RedisException
     */
    function run(int $taskId, int $workerIndex)
    {
        // TODO: Implement run() method.
        for ($i = 0; $i < 30; $i++) {
            # 在这里 获取答案
            $client_http = new \EasySwoole\HttpClient\HttpClient("http://2captcha.com/res.php?key=" . $this->data['api_key'] . "&action=get&id=" . $this->data['id'] . "&json=1");
            $response_two = $client_http->get();
            $response_two = $response_two->getBody();
            $data_two = json_decode($response_two, true);
            if ($data_two && $data_two['status'] == 1) {
                Tools::WriteLogger($this->data['user_id'], 2, "GetAnswerTask 获取答案成功 result:" . $response_two, $this->data['account_number_id'], 5);
                # 获取答案成功
                $geetest_challenge = $data_two['request']['geetest_challenge'];
                $validate = $data_two['request']['geetest_validate'];
                $seccode = $data_two['request']['geetest_seccode'];
                # 去请求
                $client_http = new \EasySwoole\HttpClient\HttpClient("https://backend-farm.plantvsundead.com/captcha/validate");
                $headers = array(
                    'authority' => 'backend-farm.plantvsundead.com',
                    'sec-ch-ua' => '"Chromium";v="94", "Google Chrome";v="94", ";Not A Brand";v="99"',
                    'accept' => 'application/json, text/plain, */*',
                    'content-type' => 'application/json;charset=UTF-8',
                    'sec-ch-ua-mobile' => '?0',
                    'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.61 Safari/537.36',
                    'authorization' => $this->data['token_value'],
                    'sec-ch-ua-platform' => '"Windows"',
                    'origin' => 'https://marketplace.plantvsundead.com',
                    'sec-fetch-site' => 'same-site',
                    'sec-fetch-mode' => 'cors',
                    'sec-fetch-dest' => 'empty',
                    'referer' => 'https://marketplace.plantvsundead.com/',
                    'accept-language' => 'zh-CN,zh;q=0.9',
                );
                $client_http->setHeaders($headers, false, false);
                $data = '{"challenge":"' . $geetest_challenge . '","seccode":"' . $validate . '","validate":"' . $validate . '"}';
                $client_http->setTimeout(5);
                $client_http->setConnectTimeout(10);
                $response = $client_http->post($data);
                $response = $response->getBody();
                var_dump("上传答案:" . $response);


                $response_json = json_decode($response, true);
                if (!$response_json || $response_json['status'] != 0) {
                    Tools::WriteLogger($this->data['user_id'], 2, "GetAnswerTask 上传验证码 返回错误或者没有破解数据...." . $response);
                    # 需要重新 过验证
                    $redis = RedisPool::defer("redis");
                    $redis->rPush("DecryptCaptcha", $this->data['account_number_id'] . "@" . $this->data['user_id']); # 账户名    用户名
                    $redis->set("IfDoingVerification", 1, 600);# 时间重置
                    Tools::WriteLogger($this->data['user_id'], 2, "GetAnswerTask 验证码打码失败..." . $response, $this->data['account_number_id'], 5);
                    return false;
                }


                # 验证码 破解成功
                $redis = RedisPool::defer("redis");
                $redis->del("IfDoingVerification");
                Tools::WriteLogger($this->data['user_id'], 2, "GetAnswerTask 验证码打码成功..." . $response, $this->data['account_number_id'], 5);
                return true;
            }
            \co::sleep(1); # 30 秒
        }
        # 打码超时了  重新打码
        $redis = RedisPool::defer("redis");
        $redis->rPush("DecryptCaptcha", $this->data['account_number_id'] . "@" . $this->data['user_id']); # 账户名    用户名
        $redis->set("IfDoingVerification", 1, 600);# 时间重置
        Tools::WriteLogger($this->data['user_id'], 2, "GetAnswerTask 打码超时 重新打码", $this->data['account_number_id'], 5);
        return false;
    }

    /**
     * @param \Throwable $throwable
     * @param int $taskId
     * @param int $workerIndex
     * @return mixed
     */
    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {

        Tools::WriteLogger($this->data['user_id'], 2, "任务 GetAnswerTask 异常:" . $throwable->getMessage());
    }
}