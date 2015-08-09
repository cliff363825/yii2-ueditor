<?php
namespace cliff363825\ueditor;

use Yii;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\InputWidget;

class UEditorWidget extends InputWidget
{
    /**
     * The name of this widget.
     */
    const PLUGIN_NAME = 'UEditor';

    const TOOLBARS_SIMPLE = 'simple';
    const TOOLBARS_MULTIPLE = 'multiple';
    const TOOLBARS_FULL = 'full';

    const LANG_ZH_CN = 'zh-cn';
    const LANG_EN = 'en';

    const THEME_DEFAULT = 'default';
    /**
     * @var array the UEditor plugin options.
     * @see http://fex.baidu.com/ueditor/
     */
    public $clientOptions = [];
    /**
     * @var array
     */
    public $serverParam = [];

    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->registerClientScript();
        if ($this->hasModel()) {
            echo Html::activeTextarea($this->model, $this->attribute, $this->options);
        } else {
            echo Html::textarea($this->name, $this->value, $this->options);
        }
    }

    /**
     * Registers the needed client script and options.
     */
    public function registerClientScript()
    {
        $view = $this->getView();
        $this->initClientOptions();
        $asset = UEditorAsset::register($view);
        $toolBars = !empty($this->clientOptions['toolbars']) ? $this->clientOptions['toolbars'] : self::TOOLBARS_FULL;
        if (is_string($toolBars)) {
            switch ($toolBars) {
                case self::TOOLBARS_SIMPLE:
                    $this->clientOptions['toolbars'] = [
                        ['fullscreen', 'source', 'undo', 'redo', 'bold']
                    ];
                    break;
                case self::TOOLBARS_MULTIPLE:
                    $this->clientOptions['toolbars'] = [
                        ['fullscreen', 'source', 'undo', 'redo'],
                        ['bold', 'italic', 'underline', 'fontborder', 'strikethrough', 'superscript', 'subscript', 'removeformat', 'formatmatch', 'autotypeset', 'blockquote', 'pasteplain', '|', 'forecolor', 'backcolor', 'insertorderedlist', 'insertunorderedlist', 'selectall', 'cleardoc']
                    ];
                    break;
                case self::TOOLBARS_FULL:
                default:
                    unset($this->clientOptions['toolbars']);
                    break;
            }
        }
        $lang = $this->clientOptions['lang'];
        $view->registerJsFile($asset->baseUrl . '/lang/' . $lang . '/' . $lang . '.js', ['depends' => '\cliff363825\ueditor\UEditorAsset']);
        $id = $this->options['id'];
        $varName = self::PLUGIN_NAME . '_' . str_replace('-', '_', $id);
        $js = "
var {$varName} = UE.getEditor('{$id}'," . Json::encode($this->clientOptions) . ");
{$varName}.ready(function() {
    {$varName}.execCommand('serverparam'," . Json::encode($this->serverParam) . " );
});
";
        $view->registerJs($js);
    }

    /**
     * Initializes client options
     */
    protected function initClientOptions()
    {
        $this->clientOptions = array_merge($this->defaultOptions(), $this->clientOptions);
        // $_POST['_csrf'] = ...
        $this->serverParam[Yii::$app->request->csrfParam] = Yii::$app->request->getCsrfToken();
        // $_POST['PHPSESSID'] = ...
        $this->serverParam[Yii::$app->session->name] = Yii::$app->session->id;
    }

    /**
     * Default client options
     * @return array
     */
    protected function defaultOptions()
    {
        return [
            'theme' => self::THEME_DEFAULT,
            'lang' => self::LANG_ZH_CN,
        ];
    }
}