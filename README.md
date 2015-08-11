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

### Action

```php
public function actions() {
    return [
        'upload' => [
            'class' => PluploadAction::className(),
            'onComplete' => function ($filename, $params) {
                // Do something with file
            }
        ],
    ];
}
```

### Widget

```php
<?= Plupload::widget([
    'url' => ['upload'],
    'browseLabel' => 'Upload',
    'browseOptions' => ['id' => 'browse', 'class' => 'btn btn-success'],
    'options' => [
        'filters' => [
            'mime_types' => [
                ['title' => 'Excel files', 'extensions' => 'csv,xls,xlsx'],
            ],
        ],
    ],
    'events' => [
        'FilesAdded' => 'function(uploader, files){
            $("#error-container").hide();
            $("#browse").button("loading");
            uploader.start();
        }',
        'FileUploaded' => 'function(uploader, file, response){
            $("#browse").button("reset");
        }',
        'Error' => 'function (uploader, error) {
            $("#error-container").html(error.message).show();
            $("#browse").button("reset");
        }'
    ],
]); ?>
```