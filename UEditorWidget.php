<?php
namespace cliff363825\ueditor;

use Yii;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\InputWidget;

class UEditorWidget extends InputWidget
{
    const PLUGIN_NAME = 'UEditor';

    const LANG_ZH_CN = 'zh-cn';
    const LANG_EN = 'en';

    /**
     * UEditor client options
     * @var array
     */
    public $clientOptions = [];

    /**
     * csrf cookie param
     * @var string
     */
    public $csrfCookieParam = '_csrfCookie';

    /**
     * @var boolean
     */
    public $render = true;

    /**
     * @inheritdoc
     */
    public function run()
    {
        if ($this->render) {
            if ($this->hasModel()) {
                echo Html::activeTextarea($this->model, $this->attribute, $this->options);
            } else {
                echo Html::textarea($this->name, $this->value, $this->options);
            }
        }
        $this->registerClientScript();
    }

    /**
     * register client scripts(css and javascript)
     */
    public function registerClientScript()
    {
        $view = $this->getView();
        $this->initClientOptions();
        $asset = UEditorAsset::register($view);
        $lang = $this->clientOptions['lang'];
        $view->registerJsFile($asset->baseUrl . '/lang/' . $lang . '/' . $lang . '.js', ['depends' => '\cliff363825\ueditor\UEditorAsset']);
        $id = $this->options['id'];
        $varName = self::PLUGIN_NAME . '_' . str_replace('-', '_', $id);
        $js = "
var {$varName} = UE.getEditor('{$id}'," . Json::encode($this->clientOptions) . ");
{$varName}.ready(function() {
    {$varName}.execCommand('serverparam', {
        '" . Yii::$app->request->csrfParam . "': '" . Yii::$app->request->getCsrfToken() . "',
        '" . Yii::$app->session->name . "': '" . Yii::$app->session->id . "',
        '" . $this->csrfCookieParam . "': '" . $_COOKIE[Yii::$app->request->csrfParam] . "'
    });
});
";
        $view->registerJs($js);
    }

    /**
     * client options init
     */
    protected function initClientOptions()
    {
        $options['theme'] = 'default';
        $options['lang'] = self::LANG_ZH_CN;
        $options['serverUrl'] = '';
        $this->clientOptions = array_merge($options, $this->clientOptions);
    }
}