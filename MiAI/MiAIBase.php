<?php
/**
 * Author: shiyueqingkong@139.com
 * CreateDate: 2018/4/17
 */

class MiAIBase
{
    public $miEvent = [
        "leavemsg.finished",
        "leavemsg.failed",
        "mediaplayer.playbacknearlyfinished"
    ];
    protected $responseText = [
        "start" => [],
        "end" => [],
        "empty" => [],
        "default" => [],
    ];

    /**
     * 初始化
     * MiAIBase constructor.
     * @param array $option
     */
    public function __construct(array $option = [])
    {
        if (!empty($option)) {
            foreach ($option as $key => $value) {
                $this->$key = $value;
            }
        }
    }


    /**
     * 格式化音频指令
     * @param $url 音频url 支持m3u列表
     * @param int $offset 偏移秒数
     * @param string $token token
     * @return array
     */
    public function formatAudioDirective($url, $offset = 0, $token = "")
    {
        return [
            "type" => "audio",
            "audio_item" => [
                "stream" => [
                    "url" => $url,//url地址
                    "offset_in_milliseconds" => $offset, //音频起始位置
                    "token" => $token, //音频起始位置
                ],
            ],
        ];
    }


    /**
     * 格式化文本指令
     * @param $text 文本名字
     * @return array
     */
    public function formatTextDirective($text)
    {
        return [
            "type" => "tts",
            "tts_item" => [
                "type" => "text",
                "text" => $text,
            ],
        ];
    }

    /**
     * 拼接响应字符串
     * @param $request 请求的数据
     * @param $respond 响应的数据
     * @return string
     */
    public function response($request, $respond)
    {
        $return = [
            "version" => $request['version'],
            "is_session_end" => $respond['isEnd'],//是否结束请求
            "response" => [
                "open_mic" => $respond['openMic'],//是否开启麦克风
                "to_speak" => [
                    "type" => 0,
                    "text" => $respond['returnMsg'],//响应文字
                ],
                "to_display" => [
                    "type" => 0,
                    "text" => $respond['returnMsg'],//响应文字
                ],
            ],
        ];

        //如果获取到有指令，to_speak自动失效
        if (!empty($respond['directives'])) {
            $return['response']['directives'] = $respond['directives'];
        }

        //是否有持久化
        if (!empty($respond['sessionAttr'])) {
            $return['session_attributes'] = $respond['sessionAttr'];
        }

        //注册回调事件
        if (!empty($respond['register_events'])) {
            $return['response']['register_events'] = $respond['register_events'];
        }

        //写入日志
        $this->writelog("响应回答：" . json_encode($return, JSON_UNESCAPED_UNICODE));

        //输出
        $returnJson = json_encode($return);
        die($returnJson);
    }


    /**
     * 检查签名
     * @return bool true通过false未通过
     */
    public function checkSign($key, $secret)
    {

        //获取签名算法版本以及签名
        $requestHeader = apache_request_headers();
        $requestSignature = $requestHeader["Authorization"];
        $requestSignatureArr = explode(" ", $requestSignature);
        $requestSignatureKeyArr = explode("::", $requestSignatureArr[1]);
        $requestSignatureVersion = $requestSignatureArr[0];//算法版本
        $requestSignatureKey = $requestSignatureKeyArr[0];//个人key
        $requestSignatureStr = $requestSignatureKeyArr[1];//签名

        //检查key
        if ($requestSignatureKey != $key) return false;

        //构造签名包
        $signPack['Methon'] = $_SERVER['REQUEST_METHOD'];
        $signPack['UrlPath'] = $_SERVER['REQUEST_URI'];
        $signPack['Params'] = "";
        $signPack['X-Xiaomi-Date'] = $_SERVER['HTTP_X_XIAOMI_DATE'];
        $signPack['Host'] = $_SERVER['HTTP_HOST'];
        $signPack['Content-Type'] = $_SERVER['CONTENT_TYPE'];
        $signPack['Content-Md5'] = $_SERVER['HTTP_CONTENT_MD5'];
        $signPack['Other'] = "";

        //获取签名并比对
        $signature = $this->generateSign($requestSignatureVersion, $signPack, $secret);
        if ($requestSignatureStr != $signature) return false;

        return true;
    }


    /**
     * @param $signatureVersion 签名版本
     * @param $signPack 签名包
     * @return string 签名
     */
    public function generateSign($signatureVersion, $signPack, $secret)
    {
        $signature = '';//签名
        switch ($signatureVersion) {
            case "MIAI-HmacSHA256-V1":

                //拼接待签名的字符串
                $signPackStr = implode($signPack, "\n");
                //签名
                $signature = bin2hex(hash_hmac('sha256', $signPackStr, base64_decode($secret), true));
                break;
        }
        return $signature;
    }


    /**
     * 获取默认的响应文本
     * @param $type
     * @return string
     */
    public function getDefaultResponseText($type)
    {
        if (empty($type)) return;

        $msg = '';
        $msgList = $this->responseText[$type];
        if (!empty($msgList)) {
            $msg = $msgList[array_rand($msgList)];
        }

        return $msg;
    }

    /**
     * 写入日志
     * @param string $contentStr 日志内容
     */
    public function writelog($contentStr = '')
    {
        //检查日志文件夹是否存在，不存在就创建
        $logPath = dirname(__DIR__) . "/log";
        if (!is_dir($logPath)) {
            mkdir($logPath);
        }

        //构造日志数据
        $log['time'] = date('Y-m-d H:i:s');
        $log['content'] = $contentStr;
        $logstr = implode("\n", $log) . "\n\n";

        //以日期为文件名写入文件
        $log_filename = $logPath . '/' . date('Y-m-d') . 'log.txt';
        file_put_contents($log_filename, $logstr, FILE_APPEND);
    }
}