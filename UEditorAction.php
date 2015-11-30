<?php
namespace cliff363825\ueditor;

use Yii;
use yii\base\Action;

/**
 * Class UEditorAction
 * @package cliff363825\ueditor
 */
class UEditorAction extends Action
{
    /**
     * 文件保存根路径
     * @var string
     */
    public $basePath = '@webroot';
    /**
     * 文件保存根url
     * @var string
     */
    public $baseUrl = '@web';
    /**
     * 文件保存子目录
     * @var string
     */
    public $savePath = 'uploads';
    /**
     * @var array
     */
    public $config = [];

    /**
     * @inheritDoc
     */
    public function init()
    {
        parent::init();
        $this->initConfig();
    }

    /**
     * Runs the action
     */
    public function run()
    {
        $action = $_GET['action'];

        switch ($action) {
            case 'config':
                $result = $this->do_config();
                break;

            /* 上传图片 */
            case 'uploadimage':
                /* 上传涂鸦 */
            case 'uploadscrawl':
                /* 上传视频 */
            case 'uploadvideo':
                /* 上传文件 */
            case 'uploadfile':
                $result = $this->do_upload();
                break;

            /* 列出图片 */
            case 'listimage':
                $result = $this->do_list();
                break;
            /* 列出文件 */
            case 'listfile':
                $result = $this->do_list();
                break;

            /* 抓取远程文件 */
            case 'catchimage':
                $result = $this->do_crawler();
                break;

            default:
                $result = $this->do_error();
                break;
        }

        /* 输出结果 */
        if (isset($_GET["callback"])) {
            if (preg_match("/^[\w_]+$/", $_GET["callback"])) {
                echo htmlspecialchars($_GET["callback"]) . '(' . $result . ')';
            } else {
                echo json_encode([
                    'state' => 'callback参数不合法'
                ]);
            }
        } else {
            echo $result;
        }
        exit;
    }

    /**
     * @return string
     */
    protected function do_config()
    {
        return json_encode($this->config);
    }

    /**
     * @return string
     */
    protected function do_upload()
    {
        $rootPath = rtrim(Yii::getAlias($this->basePath), '\\/') . '/';
        $rootUrl = rtrim(Yii::getAlias($this->baseUrl), '\\/') . '/';
        $CONFIG = $this->config;
        /* 上传配置 */
        $base64 = "upload";
        switch (htmlspecialchars($_GET['action'])) {
            case 'uploadimage':
                $config = [
                    "pathFormat" => $CONFIG['imagePathFormat'],
                    "maxSize" => $CONFIG['imageMaxSize'],
                    "allowFiles" => $CONFIG['imageAllowFiles']
                ];
                $fieldName = $CONFIG['imageFieldName'];
                break;
            case 'uploadscrawl':
                $config = [
                    "pathFormat" => $CONFIG['scrawlPathFormat'],
                    "maxSize" => $CONFIG['scrawlMaxSize'],
                    "allowFiles" => $CONFIG['scrawlAllowFiles'],
                    "oriName" => "scrawl.png"
                ];
                $fieldName = $CONFIG['scrawlFieldName'];
                $base64 = "base64";
                break;
            case 'uploadvideo':
                $config = [
                    "pathFormat" => $CONFIG['videoPathFormat'],
                    "maxSize" => $CONFIG['videoMaxSize'],
                    "allowFiles" => $CONFIG['videoAllowFiles']
                ];
                $fieldName = $CONFIG['videoFieldName'];
                break;
            case 'uploadfile':
            default:
                $config = [
                    "pathFormat" => $CONFIG['filePathFormat'],
                    "maxSize" => $CONFIG['fileMaxSize'],
                    "allowFiles" => $CONFIG['fileAllowFiles']
                ];
                $fieldName = $CONFIG['fileFieldName'];
                break;
        }

        /* 生成上传实例对象并完成上传 */
        $up = new Uploader($fieldName, $config, $base64, $rootPath, $rootUrl);

        /**
         * 得到上传文件所对应的各个参数,数组结构
         * array(
         *     "state" => "",          //上传状态，上传成功时必须返回"SUCCESS"
         *     "url" => "",            //返回的地址
         *     "title" => "",          //新文件名
         *     "original" => "",       //原始文件名
         *     "type" => ""            //文件类型
         *     "size" => "",           //文件大小
         * )
         */

        /* 返回数据 */
        return json_encode($up->getFileInfo());
    }

    /**
     * @return string
     */
    protected function do_list()
    {
        $CONFIG = $this->config;
        /* 判断类型 */
        switch ($_GET['action']) {
            /* 列出文件 */
            case 'listfile':
                $allowFiles = $CONFIG['fileManagerAllowFiles'];
                $listSize = $CONFIG['fileManagerListSize'];
                $path = $CONFIG['fileManagerListPath'];
                break;
            /* 列出图片 */
            case 'listimage':
            default:
                $allowFiles = $CONFIG['imageManagerAllowFiles'];
                $listSize = $CONFIG['imageManagerListSize'];
                $path = $CONFIG['imageManagerListPath'];
        }
        $allowFiles = substr(str_replace(".", "|", join("", $allowFiles)), 1);

        /* 获取参数 */
        $size = isset($_GET['size']) ? intval($_GET['size']) : $listSize;
        $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
        $end = $start + $size;

        /* 获取文件列表 */
        $path = rtrim(Yii::getAlias($this->basePath), '\\/') . '/' . $path;
        $files = $this->getfiles($path, $allowFiles);
        if (!count($files)) {
            return json_encode([
                "state" => "no match file",
                "list" => [],
                "start" => $start,
                "total" => count($files)
            ]);
        }

        /* 获取指定范围的列表 */
        $len = count($files);
        for ($i = min($end, $len) - 1, $list = array(); $i < $len && $i >= 0 && $i >= $start; $i--) {
            $list[] = $files[$i];
        }
        //倒序
        //for ($i = $end, $list = array(); $i < $len && $i < $end; $i++){
        //    $list[] = $files[$i];
        //}

        /* 返回数据 */
        $result = json_encode([
            "state" => "SUCCESS",
            "list" => $list,
            "start" => $start,
            "total" => count($files)
        ]);

        return $result;
    }

    /**
     * @return string
     */
    protected function do_crawler()
    {
        $rootPath = rtrim(Yii::getAlias($this->basePath), '\\/') . '/';
        $rootUrl = rtrim(Yii::getAlias($this->baseUrl), '\\/') . '/';
        $CONFIG = $this->config;
        /* 上传配置 */
        $config = [
            "pathFormat" => $CONFIG['catcherPathFormat'],
            "maxSize" => $CONFIG['catcherMaxSize'],
            "allowFiles" => $CONFIG['catcherAllowFiles'],
            "oriName" => "remote.png"
        ];
        $fieldName = $CONFIG['catcherFieldName'];

        /* 抓取远程图片 */
        $list = [];
        if (isset($_POST[$fieldName])) {
            $source = $_POST[$fieldName];
        } else {
            $source = $_GET[$fieldName];
        }
        foreach ($source as $imgUrl) {
            $item = new Uploader($imgUrl, $config, "remote", $rootPath, $rootUrl);
            $info = $item->getFileInfo();
            array_push($list, [
                "state" => $info["state"],
                "url" => $info["url"],
                "size" => $info["size"],
                "title" => htmlspecialchars($info["title"]),
                "original" => htmlspecialchars($info["original"]),
                "source" => htmlspecialchars($imgUrl)
            ]);
        }

        /* 返回抓取数据 */
        return json_encode([
            'state' => count($list) ? 'SUCCESS' : 'ERROR',
            'list' => $list
        ]);
    }

    /**
     * @return string
     */
    protected function do_error()
    {
        return json_encode([
            'state' => '请求地址出错'
        ]);
    }

    /**
     * Initializes server config
     */
    protected function initConfig()
    {
        $this->config = array_merge($this->defaultConfig(), $this->config);
    }

    /**
     * Default server config
     * @return array
     */
    public function defaultConfig()
    {
        return [
            'imageActionName' => 'uploadimage',
            'imageFieldName' => 'upfile',
            'imageMaxSize' => 2048000,
            'imageAllowFiles' => ['.png', '.jpg', '.jpeg', '.gif', '.bmp',],
            'imageCompressEnable' => true,
            'imageCompressBorder' => 1600,
            'imageInsertAlign' => 'none',
            'imageUrlPrefix' => '',
            'imagePathFormat' => rtrim($this->savePath, '\\/') . '/image/{yyyy}{mm}{dd}/{time}{rand:6}',
            'scrawlActionName' => 'uploadscrawl',
            'scrawlFieldName' => 'upfile',
            'scrawlPathFormat' => rtrim($this->savePath, '\\/') . '/image/{yyyy}{mm}{dd}/{time}{rand:6}',
            'scrawlMaxSize' => 2048000,
            'scrawlUrlPrefix' => '',
            'scrawlInsertAlign' => 'none',
            'snapscreenActionName' => 'uploadimage',
            'snapscreenPathFormat' => rtrim($this->savePath, '\\/') . '/image/{yyyy}{mm}{dd}/{time}{rand:6}',
            'snapscreenUrlPrefix' => '',
            'snapscreenInsertAlign' => 'none',
            'catcherLocalDomain' => ['127.0.0.1', 'localhost', 'img.baidu.com',],
            'catcherActionName' => 'catchimage',
            'catcherFieldName' => 'source',
            'catcherPathFormat' => rtrim($this->savePath, '\\/') . '/image/{yyyy}{mm}{dd}/{time}{rand:6}',
            'catcherUrlPrefix' => '',
            'catcherMaxSize' => 2048000,
            'catcherAllowFiles' => ['.png', '.jpg', '.jpeg', '.gif', '.bmp',],
            'videoActionName' => 'uploadvideo',
            'videoFieldName' => 'upfile',
            'videoPathFormat' => rtrim($this->savePath, '\\/') . '/video/{yyyy}{mm}{dd}/{time}{rand:6}',
            'videoUrlPrefix' => '',
            'videoMaxSize' => 102400000,
            'videoAllowFiles' => ['.flv', '.swf', '.mkv', '.avi', '.rm', '.rmvb', '.mpeg', '.mpg', '.ogg', '.ogv', '.mov', '.wmv', '.mp4', '.webm', '.mp3', '.wav', '.mid',],
            'fileActionName' => 'uploadfile',
            'fileFieldName' => 'upfile',
            'filePathFormat' => rtrim($this->savePath, '\\/') . '/file/{yyyy}{mm}{dd}/{time}{rand:6}',
            'fileUrlPrefix' => '',
            'fileMaxSize' => 51200000,
            'fileAllowFiles' => ['.png', '.jpg', '.jpeg', '.gif', '.bmp', '.flv', '.swf', '.mkv', '.avi', '.rm', '.rmvb', '.mpeg', '.mpg', '.ogg', '.ogv', '.mov', '.wmv', '.mp4', '.webm', '.mp3', '.wav', '.mid', '.rar', '.zip', '.tar', '.gz', '.7z', '.bz2', '.cab', '.iso', '.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx', '.pdf', '.txt', '.md', '.xml',],
            'imageManagerActionName' => 'listimage',
            'imageManagerListPath' => rtrim($this->savePath, '\\/') . '/image/',
            'imageManagerListSize' => 20,
            'imageManagerUrlPrefix' => '',
            'imageManagerInsertAlign' => 'none',
            'imageManagerAllowFiles' => ['.png', '.jpg', '.jpeg', '.gif', '.bmp',],
            'fileManagerActionName' => 'listfile',
            'fileManagerListPath' => rtrim($this->savePath, '\\/') . '/file/',
            'fileManagerUrlPrefix' => '',
            'fileManagerListSize' => 20,
            'fileManagerAllowFiles' => ['.png', '.jpg', '.jpeg', '.gif', '.bmp', '.flv', '.swf', '.mkv', '.avi', '.rm', '.rmvb', '.mpeg', '.mpg', '.ogg', '.ogv', '.mov', '.wmv', '.mp4', '.webm', '.mp3', '.wav', '.mid', '.rar', '.zip', '.tar', '.gz', '.7z', '.bz2', '.cab', '.iso', '.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx', '.pdf', '.txt', '.md', '.xml',],
        ];
    }

    /**
     * 遍历获取目录下的指定类型的文件
     * @param $path
     * @param $allowFiles
     * @param array $files
     * @return array
     */
    protected function getfiles($path, $allowFiles, &$files = array())
    {
        if (!is_dir($path)) return null;
        if (substr($path, strlen($path) - 1) != '/') $path .= '/';
        $handle = opendir($path);
        while (false !== ($file = readdir($handle))) {
            if ($file != '.' && $file != '..') {
                $path2 = $path . $file;
                if (is_dir($path2)) {
                    $this->getfiles($path2, $allowFiles, $files);
                } else {
                    if (preg_match("/\.(" . $allowFiles . ")$/i", $file)) {
                        $url = str_replace(Yii::getAlias($this->basePath), '', $path2);
                        $files[] = [
                            'url' => $url,
                            'mtime' => filemtime($path2)
                        ];
                    }
                }
            }
        }
        return $files;
    }
}