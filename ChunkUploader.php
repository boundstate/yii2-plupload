<?php
namespace boundstate\plupload;


use Yii;
use yii\base\Exception;
use yii\web\UploadedFile;

class ChunkUploader
{
    /**
     * Processes a chunked file upload.
     * @param UploadedFile $uploadedFile
     * @param string $path path to write chunks to
     * @returns boolean true if file upload is complete, or false if there are more chunks
     * @throws Exception
     */
    public static function process($uploadedFile, $path) {
        if (!$uploadedFile || $uploadedFile->hasError) {
            throw new Exception('Failed to upload file');
        }

        $chunk = (int)Yii::$app->request->getBodyParam('chunk', 0);
        $totalChunks = (int)Yii::$app->request->getBodyParam('chunks', 0);

        $out = fopen("$path.part", $chunk == 0 ? 'wb' : 'ab');
        if (!$out) {
            throw new Exception('Failed to open output stream');
        }

        // Read binary input stream and append it to temporary .part file
        $in = fopen($uploadedFile->tempName, 'rb');
        if ($in) {
            while ($buff = fread($in, 4096)) {
                fwrite($out, $buff);
            }
        } else {
            throw new Exception('Failed to open input stream');
        }

        fclose($in);
        fclose($out);

        unlink($uploadedFile->tempName);

        // Check if all chunks have been processed
        if (!$totalChunks || $chunk == $totalChunks - 1) {
            // Strip the temp .part suffix off
            rename("$path.part", $path);
            return true;
        }

        return false;
    }
}