<?php

namespace boundstate\plupload;

use Yii;
use yii\base\Widget;
use yii\base\Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

/**
 * Wrapper for Plupload
 * A multiple file upload utility using Flash, Silverlight, Google Gears, HTML5 or BrowserPlus.
 * @url http://www.plupload.com/
 * @version 1.0
 * @author Bound State Software
 */
class Plupload extends Widget
{
	/**
	 * Page URL or action to where the files will be uploaded to.
	 * @var mixed
	 */
	public $url;
	
	public $htmlOptions = [];
	
	/**
	 * The label to display on the browse link.
	 * @var string
	 */	
	public $browseLabel = 'Select Files';
	
	/**
	 * HTML options for the browse link.
	 * @var array
	 */
	public $browseOptions = [];
	
	/**
	 * ID of the error container.
	 * @var string
	 */
	public $errorContainer;
	
	/**
	 * Options to pass directly to the JavaScript plugin.
	 * Please refer to the Plupload documentation:
	 * @link http://www.plupload.com/documentation.php
	 * @var array
	 */
	public $options = [];
	
	/**
	 * The JavaScript event callbacks to attach to Plupload object.
	 * @link http://www.plupload.com/example_events.php
	 * In addition to the standard events, this widget adds a "FileSuccess"
	 * event that is fired when a file is uploaded without error.
	 * NOTE: events signatures should all have a first argument for event, in 
	 * addition to the arguments documented on the Plupload website.
	 * @var array
	 */
	public $events = [];

	/**
	 * @return int the max upload size in MB
	 */
	public static function getPHPMaxUploadSize()
	{
		$max_upload = (int)(ini_get('upload_max_filesize'));
		$max_post = (int)(ini_get('post_max_size'));
		$memory_limit = (int)(ini_get('memory_limit'));
		return min($max_upload, $max_post, $memory_limit);
	}

    /**
     * @inheritdoc
     */
    public function init()
	{
		// Make sure URL is provided
		if (empty($this->url))
			throw new Exception(Yii::t('yii','{class} must specify "url" property value.',array('{class}'=>get_class($this))));

		if (!isset($this->htmlOptions['id']))
			$this->htmlOptions['id'] = $this->getId();

        $id = $this->htmlOptions['id'];
		
		if (!isset($this->browseOptions['id']))
			$this->browseOptions['id'] = "plupload_{$id}_browse";
			
		if (!isset($this->errorContainer))
			$this->errorContainer = "plupload_{$id}_em";

		if (!isset($this->options['multipart_params']))
			$this->options['multipart_params'] = [];

		$this->options['multipart_params'][Yii::$app->request->csrfParam] = Yii::$app->request->csrfToken;

        $bundle = PluploadAsset::register($this->view);
			
		$defaultOptions = [
            'browse_button' => $this->browseOptions['id'],
			'url' => Url::to($this->url),
			'container' => $id,
			'runtimes' => 'gears,html5,flash,silverlight,browserplus',
			'flash_swf_url' => "{$bundle->baseUrl}/Moxie.swf",
			'silverlight_xap_url' => "{$bundle->baseUrl}/Moxie.xap",
			'max_file_size' => self::getPHPMaxUploadSize() . 'mb',
			'error_container' => "#{$this->errorContainer}",
		];

		$options = ArrayHelper::merge($defaultOptions, $this->options);
		$options = Json::encode($options);
		
		// Output
		echo Html::beginTag('div', $this->htmlOptions);
		echo Html::a($this->browseLabel, '#', $this->browseOptions);
		echo Html::endTag('div');
		
		// Generate event JavaScript
		$events = '';
		foreach ($this->events as $event => $callback)
            $events .= "uploader.bind('$event', $callback);\n";

		$this->view->registerJs("var uploader = new plupload.Uploader($options);\nuploader.init();\n$events");
	}
}
