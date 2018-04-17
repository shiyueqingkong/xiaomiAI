<?php
/**
 * Author: shiyueqingkong@139.com
 * CreateDate: 2018/4/17
 */

require_once 'MiAIBase.php';
require_once 'Music.php';

class MiXiaoAi
{
    protected $key_id = "";//填入自己的keyid
    protected $secret = "";//填入自己的secret
    protected $mediaPath = "/media";//媒体地址

    protected $MiAIBase;//小米AI基础Model
    protected $request = "";//获取到的请求
    protected $respond = [//响应数据
        'returnMsg' => '',
        'isEnd' => true,
        'openMic' => false,
        'directives' => array(),
        'sessionAttr' => array(),
        'register_events' => array(),
    ];

    public function __construct()
    {
        $option = [
            "responseText" => [
                "start" => [
                    "主人，请问有什么吩咐",
                    "你好呀，有什么可以帮您",
                ],
                "end" => ["那小爱退下了,有事记得找我哦"],
                "empty" => [
                    "主人，你还在吗",
                    "小爱好像没听到，麻烦在说一下吧",
                ],
                "default" => [
                    "哎呀,小爱听不懂，麻烦在说一下",
                    "小爱听到了，但是没听懂,请换个说法吧",
                    "刚刚你说了啥，小爱没明白",
                ],
            ],
        ];
        $this->MiAIBase = new MiAIBase($option);
    }

    public function index()
    {
        //检查签名
        if (!$this->MiAIBase->checkSign($this->key_id, $this->secret)) {
            $this->MiAIBase->writelog("签名失败");
            return;
        }

        //获取数据
        $xiaoaiRequest = file_get_contents('php://input');
        $this->MiAIBase->writelog("请求数据：" . $xiaoaiRequest);
        $this->request = json_decode($xiaoaiRequest, true);

        //处理响应
        $this->handleRequest();
    }

    /**
     * 处理请求
     */
    private function handleRequest()
    {
        $requestType = $this->request['request']['type'];

        switch ($requestType) {
            case 0:
                $this->respond['returnMsg'] = $this->MiAIBase->getDefaultResponseText("start");
                $this->respond['isEnd'] = false;
                $this->respond['openMic'] = true;
                break;
            //处理意图
            case 1:
                if ($this->request['request']['event_type']) {
                    $this->handleRquestEvent();
                } else {
                    $this->handleIntent();
                }

                break;
            case 2:
                $this->respond['returnMsg'] = $this->MiAIBase->getDefaultResponseText("end");
                $this->respond['isEnd'] = true;
                $this->respond['openMic'] = false;
                break;
        }

        //响应回答
        if (!$this->respond['returnMsg']) {
            $this->respond['returnMsg'] = $this->MiAIBase->getDefaultResponseText("default");;
            $this->respond['isEnd'] = false;
            $this->respond['openMic'] = true;
        }
        $this->MiAIBase->response($this->request, $this->respond);
    }


    /**
     * 处理意图
     */
    private function handleIntent()
    {
        $query = $this->request['request']['intent']['query'];//意图文字

        //空意图处理
        if (empty($query)) {
            $this->respond['returnMsg'] = $this->MiAIBase->getDefaultResponseText("empty");
            $this->respond['isEnd'] = false;
            $this->respond['openMic'] = true;
            return;
        }

        //是否为直接唤醒
        $isDirectWakeup = $this->request['request']['intent']['is_direct_wakeup'];
        if (!$isDirectWakeup) {
            $this->respond['isEnd'] = false;
            $this->respond['openMic'] = true;
        }

        $this->parseIntent($query);//分析意图

    }


    /**
     * 处理请求事件
     */
    private function handleRquestEvent()
    {

        $eventType = $this->request['request']['event_type'];//事件来源
        switch ($eventType) {
            case  $this->MiAIBase->miEvent[2]:
                $this->handleIntent();
                break;
        }

    }

    /**
     * 分析意图 todo 这里自己发挥，这里只是个简单的用法
     * @param $intent
     * @return array 分析到的结果
     */
    private function parseIntent($intent)
    {
        $result = array();

        switch ($intent) {
            case "放首歌":

                //获取资源url
                $Music = new Music($this->mediaPath);
                $musicName = $Music->play(1);
                $musicUrl = $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['SERVER_NAME'] . stristr($musicName, $this->mediaPath);
                $this->MiAIBase->writelog("当前播放歌曲:" . $musicUrl);

                //构造数据
                $result['returnMsg'] = "即将为您播放音乐";
                $result['openMic'] = false;
                $result['directives'] = [
                    $this->MiAIBase->formatAudioDirective($musicUrl, 0),
                ];
                $result['register_events'] = [
                    ["event_name" => $this->MiAIBase->miEvent[2]],
                ];
                break;
        }

        $this->respond = array_merge($this->respond, $result);
    }

}