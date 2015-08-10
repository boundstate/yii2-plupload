# yii2-plupload

A [plupload](http://www.plupload.com/) extension for the Yii2 framework

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```sh
php composer.phar require --prefer-dist boundstate/yii2-plupload "*"
```

or add

```
"boundstate/yii2-plupload": "*"
```

to the require section of your `composer.json` file.


## Usage

Once the extension is installed, simply create an upload button with:

```php
<?= \boundstate\plupload\Plupload::widget(); ?>
```