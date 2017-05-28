<?php

namespace boundstate\plupload;

use yii\web\AssetBundle;

/**
 * Asset bundle for the Plupload script files.
 */
class PluploadAsset extends AssetBundle
{
    public $sourcePath = '@vendor/moxiecode/plupload/js';
    public $js = [
        'plupload.min.js',
    ];
    public $depends = [
        'yii\web\JqueryAsset',
    ];
}
