<?php
namespace cliff363825\ueditor;

use Yii;
use yii\web\AssetBundle;

class UEditorAsset extends AssetBundle
{
    public $sourcePath = '@cliff363825/ueditor/assets';
    public $js = [
        'ueditor.config.js',
    ];
    public $css = [];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if (YII_DEBUG) {
            $this->js[] = 'ueditor.all.js';
        } else {
            $this->js[] = 'ueditor.all.min.js';
        }
    }
}