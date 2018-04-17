<?php

/**
 * Author: shiyueqingkong@139.com
 * CreateDate: 2018/4/17
 */
class Music
{
    protected $musicList = [];//音乐列表
    protected $defaultList = "musicList";//默认音乐列表名称

    public function __construct($mediaDir = "")
    {
        $realPath = dirname(__DIR__) . $mediaDir;
        if (is_dir($realPath)) {
            $this->musicList = $this->getFileList($realPath, 1, ['mp3']);
        }
    }

    /**
     * 获取列表
     * @return array|bool
     */
    public function getMusicList()
    {
        return $this->musicList;
    }


    /**
     * 播放
     * @param int $sortType 0正序1随机
     * @param int $number 播放序号
     * @return bool|string
     */
    public function play($sortType = 0, $number = 0)
    {
        if (empty($this->musicList)) return false;

        $musicPath = '';
        switch ($sortType) {
            case 0:
                $number = $number < 0 ? count($this->musicList) - 1 : $number;
                $playNum = $number > count($this->musicList) - 1 ? 0 : $number;

                $musicPath = ($this->musicList)[$playNum];

                //todo 需要缓存最后一次播放的Id,具体缓存机制自己实现
                break;
            case 1:
                $musicPath = $this->musicList[array_rand($this->musicList)];
                //todo 需要缓存最后一次播放的Id,具体缓存机制自己实现
                break;
        }

        return $musicPath;
    }

    /**
     * 下一首 todo 需要实现缓存机制
     * @return bool|string
     */
    public function next()
    {
        $lastPlayNumber = '';//获取最后一次播放的编号
        $playNumber = isset($lastPlayNumber) ? $lastPlayNumber + 1 : 0;
        $musicPath = $this->play(0, $playNumber);
        return $musicPath;
    }

    /**
     * 上一首 todo 需要实现缓存机制
     * @return bool|string
     */
    public function back()
    {
        $lastPlayNumber = '';//获取最后一次播放的编号
        $playNumber = isset($lastPlayNumber) ? $lastPlayNumber - 1 : 0;
        $musicPath = $this->play(0, $playNumber);
        return $musicPath;
    }

    /**
     * 获取文件夹的文件列表
     * @param $dirPath 文件夹路径
     * @param int $isRecursion 是否递归0不递归1递归
     * @param array $ext 文件夹扩展名
     * @return array|bool 文件夹列表
     */
    public function getFileList($dirPath, $isRecursion = 0, array $ext = [])
    {

        if (!is_dir($dirPath)) return false;

        $fileList = array();//文件列表
        $dirList = array();//文件夹列表
        $dirList[] = $dirPath;

        while (false != $dirList[0]) {

            $tempPath = $dirList[0];//获取临时文件夹
            $handle = opendir($tempPath);//打开文件夹句柄

            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != "..") {
                    $tempFileName = $tempPath . "/" . $file;//临时文件名
                    //文件夹是否需要递归处理
                    if (is_dir($tempFileName) && $isRecursion == 1) {
                        $dirList[] = $tempFileName;
                    } else {

                        //是否指定文件扩展名
                        if (empty($ext)) {
                            $fileList[] = $tempFileName;
                        } else {

                            //获取文件扩展名
                            $fileExt = pathinfo($tempFileName, PATHINFO_EXTENSION);
                            if (in_array($fileExt, $ext)) {
                                $fileList[] = $tempFileName;
                            }
                        }
                    }
                }
            }

            array_shift($dirList); //删除第一个
        }

        return $fileList;
    }


}