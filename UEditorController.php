<?php
namespace cliff363825\ueditor;

use Yii;
use yii\web\Controller;

class UEditorController extends Controller
{
    /**
     * csrf cookie param
     * @var string
     */
    public $csrfCookieParam = '_csrfCookie';

    private $_config = false;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if (($sessionId = Yii::$app->request->get(Yii::$app->session->name)) !== null) {
            Yii::$app->session->setId($sessionId);
            Yii::$app->session->open();
        }
        if (Yii::$app->request->enableCsrfCookie) {
            $csrfParam = Yii::$app->request->csrfParam;
            $_POST[$csrfParam] = Yii::$app->request->get($csrfParam);
            if (!isset($_COOKIE[$csrfParam])) {
                $_COOKIE[$csrfParam] = Yii::$app->request->get($this->csrfCookieParam);
            }
        }
    }

    public function actionIndex()
    {
        $action = isset($_GET['action']) ? trim($_GET['action']) : 'error';
        if (strtolower($action) != 'index') {
            return $this->runAction($action);
        }
        return '';
    }

    public function actionConfig()
    {
        $result = json_encode($this->getConfig());
        return $this->output($result);
    }

    public function actionUploadimage()
    {
        $CONFIG = $this->getConfig();
        $config = [
            "pathFormat" => $CONFIG['imagePathFormat'],
            "maxSize" => $CONFIG['imageMaxSize'],
            "allowFiles" => $CONFIG['imageAllowFiles']
        ];
        $fieldName = $CONFIG['imageFieldName'];
        $base64 = "upload";
        $result = $this->upload($fieldName, $config, $base64);
        return $this->output($result);
    }

    public function actionUploadscrawl()
    {
        $CONFIG = $this->getConfig();
        $config = [
            "pathFormat" => $CONFIG['scrawlPathFormat'],
            "maxSize" => $CONFIG['scrawlMaxSize'],
            "allowFiles" => $CONFIG['scrawlAllowFiles'],
            "oriName" => "scrawl.png"
        ];
        $fieldName = $CONFIG['scrawlFieldName'];
        $base64 = "base64";
        $result = $this->upload($fieldName, $config, $base64);
        return $this->output($result);
    }

    public function actionUploadvideo()
    {
        $CONFIG = $this->getConfig();
        $config = [
            "pathFormat" => $CONFIG['videoPathFormat'],
            "maxSize" => $CONFIG['videoMaxSize'],
            "allowFiles" => $CONFIG['videoAllowFiles']
        ];
        $fieldName = $CONFIG['videoFieldName'];
        $base64 = "upload";
        $result = $this->upload($fieldName, $config, $base64);
        return $this->output($result);
    }

    public function actionUploadfile()
    {
        $CONFIG = $this->getConfig();
        $config = [
            "pathFormat" => $CONFIG['filePathFormat'],
            "maxSize" => $CONFIG['fileMaxSize'],
            "allowFiles" => $CONFIG['fileAllowFiles']
        ];
        $fieldName = $CONFIG['fileFieldName'];
        $base64 = "upload";
        $result = $this->upload($fieldName, $config, $base64);
        return $this->output($result);
    }

    public function actionListimage()
    {
        $CONFIG = $this->getConfig();
        $allowFiles = $CONFIG['imageManagerAllowFiles'];
        $listSize = $CONFIG['imageManagerListSize'];
        $path = $CONFIG['imageManagerListPath'];
        $result = $this->listRes($allowFiles, $listSize, $path);
        return $this->output($result);
    }

    public function actionListfile()
    {
        $CONFIG = $this->getConfig();
        $allowFiles = $CONFIG['fileManagerAllowFiles'];
        $listSize = $CONFIG['fileManagerListSize'];
        $path = $CONFIG['fileManagerListPath'];
        $result = $this->listRes($allowFiles, $listSize, $path);
        return $this->output($result);
    }

    public function actionCatchimage()
    {
        $CONFIG = $this->getConfig();
        $config = [
            "pathFormat" => $CONFIG['catcherPathFormat'],
            "maxSize" => $CONFIG['catcherMaxSize'],
            "allowFiles" => $CONFIG['catcherAllowFiles'],
            "oriName" => "remote.png"
        ];
        $fieldName = $CONFIG['catcherFieldName'];
        $list = [];
        if (isset($_POST[$fieldName])) {
            $source = $_POST[$fieldName];
        } else {
            $source = $_GET[$fieldName];
        }
        foreach ($source as $imgUrl) {
            $item = new Uploader($imgUrl, $config, "remote");
            $info = $item->getFileInfo();
            array_push($list, [
                "state" => $info["state"],
                "url" => $info["url"],
                "source" => $imgUrl
            ]);
        }
        $result = json_encode([
            'state' => count($list) ? 'SUCCESS' : 'ERROR',
            'list' => $list
        ]);
        return $this->output($result);
    }

    public function actionError()
    {
        $result = json_encode([
            'state' => '请求地址出错'
        ]);
        return $this->output($result);
    }

    protected function upload($fieldName, $config, $base64)
    {
        $up = new Uploader($fieldName, $config, $base64);
        return json_encode($up->getFileInfo());
    }

    protected function listRes($allowFiles, $listSize, $path)
    {
        $allowFiles = substr(str_replace(".", "|", join("", $allowFiles)), 1);
        $size = isset($_GET['size']) ? $_GET['size'] : $listSize;
        $start = isset($_GET['start']) ? $_GET['start'] : 0;
        $end = $start + $size;
        $path = Yii::getAlias('@webroot') . (substr($path, 0, 1) == "/" ? "" : "/") . $path;
        $files = $this->getfiles($path, $allowFiles);
        $len = count($files);
        if ($len > 0) {
            for ($i = min($end, $len) - 1, $list = []; $i < $len && $i >= 0 && $i >= $start; $i--) {
                $list[] = $files[$i];
            }
            //倒序
            //for ($i = $end, $list = array(); $i < $len && $i < $end; $i++){
            //    $list[] = $files[$i];
            //}
            $result = json_encode([
                "state" => "SUCCESS",
                "list" => $list,
                "start" => $start,
                "total" => $len
            ]);
        } else {
            $result = json_encode([
                "state" => "no match file",
                "list" => [],
                "start" => $start,
                "total" => $len
            ]);
        }
        return $result;
    }

    public function setConfig($config)
    {
        $this->_config = array_merge($this->defaultConfig(), $config);
    }

    public function getConfig()
    {
        if ($this->_config === false) {
            $this->_config = $this->defaultConfig();
        }
        return $this->_config;
    }

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
            'imagePathFormat' => '/uploads/image/{yyyy}{mm}{dd}/{time}{rand:6}',
            'scrawlActionName' => 'uploadscrawl',
            'scrawlFieldName' => 'upfile',
            'scrawlPathFormat' => '/uploads/image/{yyyy}{mm}{dd}/{time}{rand:6}',
            'scrawlMaxSize' => 2048000,
            'scrawlUrlPrefix' => '',
            'scrawlInsertAlign' => 'none',
            'snapscreenActionName' => 'uploadimage',
            'snapscreenPathFormat' => '/uploads/image/{yyyy}{mm}{dd}/{time}{rand:6}',
            'snapscreenUrlPrefix' => '',
            'snapscreenInsertAlign' => 'none',
            'catcherLocalDomain' => ['127.0.0.1', 'localhost', 'img.baidu.com',],
            'catcherActionName' => 'catchimage',
            'catcherFieldName' => 'source',
            'catcherPathFormat' => '/uploads/image/{yyyy}{mm}{dd}/{time}{rand:6}',
            'catcherUrlPrefix' => '',
            'catcherMaxSize' => 2048000,
            'catcherAllowFiles' => ['.png', '.jpg', '.jpeg', '.gif', '.bmp',],
            'videoActionName' => 'uploadvideo',
            'videoFieldName' => 'upfile',
            'videoPathFormat' => '/uploads/video/{yyyy}{mm}{dd}/{time}{rand:6}',
            'videoUrlPrefix' => '',
            'videoMaxSize' => 102400000,
            'videoAllowFiles' => [
                '.flv', '.swf', '.mkv', '.avi', '.rm', '.rmvb', '.mpeg', '.mpg', '.ogg', '.ogv', '.mov',
                '.wmv', '.mp4', '.webm', '.mp3', '.wav', '.mid',
            ],
            'fileActionName' => 'uploadfile',
            'fileFieldName' => 'upfile',
            'filePathFormat' => '/uploads/file/{yyyy}{mm}{dd}/{time}{rand:6}',
            'fileUrlPrefix' => '',
            'fileMaxSize' => 51200000,
            'fileAllowFiles' => [
                '.png', '.jpg', '.jpeg', '.gif', '.bmp', '.flv', '.swf', '.mkv', '.avi', '.rm', '.rmvb',
                '.mpeg', '.mpg', '.ogg', '.ogv', '.mov', '.wmv', '.mp4', '.webm', '.mp3', '.wav', '.mid',
                '.rar', '.zip', '.tar', '.gz', '.7z', '.bz2', '.cab', '.iso', '.doc', '.docx', '.xls',
                '.xlsx', '.ppt', '.pptx', '.pdf', '.txt', '.md', '.xml',
            ],
            'imageManagerActionName' => 'listimage',
            'imageManagerListPath' => '/uploads/image/',
            'imageManagerListSize' => 20,
            'imageManagerUrlPrefix' => '',
            'imageManagerInsertAlign' => 'none',
            'imageManagerAllowFiles' => ['.png', '.jpg', '.jpeg', '.gif', '.bmp',],
            'fileManagerActionName' => 'listfile',
            'fileManagerListPath' => '/uploads/file/',
            'fileManagerUrlPrefix' => '',
            'fileManagerListSize' => 20,
            'fileManagerAllowFiles' => [
                '.png', '.jpg', '.jpeg', '.gif', '.bmp', '.flv', '.swf', '.mkv', '.avi', '.rm', '.rmvb',
                '.mpeg', '.mpg', '.ogg', '.ogv', '.mov', '.wmv', '.mp4', '.webm', '.mp3', '.wav', '.mid',
                '.rar', '.zip', '.tar', '.gz', '.7z', '.bz2', '.cab', '.iso', '.doc', '.docx', '.xls',
                '.xlsx', '.ppt', '.pptx', '.pdf', '.txt', '.md', '.xml',
            ],
        ];
    }

    protected function getfiles($path, $allowFiles, &$files = array())
    {
        if (!is_dir($path)) return null;
        if (substr($path, strlen($path) - 1) != '/') {
            $path .= '/';
        }
        $handle = opendir($path);
        while (false !== ($file = readdir($handle))) {
            if ($file != '.' && $file != '..') {
                $path2 = $path . $file;
                if (is_dir($path2)) {
                    $this->getfiles($path2, $allowFiles, $files);
                } else {
                    if (preg_match("/\.(" . $allowFiles . ")$/i", $file)) {
                        $files[] = array(
                            'url' => str_replace(Yii::getAlias('@webroot'), Yii::getAlias('@web'), $path2),
                            'mtime' => filemtime($path2)
                        );
                    }
                }
            }
        }
        closedir($handle);
        return $files;
    }

    protected function output($result)
    {
        if (isset($_GET['callback'])) {
            return $this->renderContent($_GET['callback'] . '(' . $result . ')');
        } else {
            return $result;
        }
    }
}