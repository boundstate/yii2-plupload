<?php

namespace boundstate\plupload;

use yii\base\Action;

/**
 * PluploadAction class file.
 *
 * Copyright 2009, Moxiecode Systems AB
 * Released under GPL License.
 *
 * License: http://www.plupload.com/license
 * Contributing: http://www.plupload.com/contributing
 */
class PluploadAction extends Action {

	/**
	 * The directory to upload files to.
	 * @var string
	 */
	public $targetDir;

	/**
	 * Maximum execution time in seconds.
	 * Default is five seconds.
	 * @var integer
	 */
	public $maxExecutionTime = 300;

	/**
	 * Whether to remove old files.
	 * @var boolean
	 */
	public $cleanup = true;

	/**
	 * Success callback with signature: success($filepath, $params)
	 * @var callable
	 */
	public $success;

	/**
	 * Maximum file age in seconds (if cleanup is enabled).
	 * Default is 5 minutes.
	 * @var integer
	 */
	public $maxFileAge = 18000;

	/**
	 * The filename
	 * @var string
	 */
	private $_filename;

	/**
	 * The current chunk number.
	 * @var integer
	 */
	private $_chunk;

	/**
	 * The total number of chunks.
	 * @var integer
	 */
	private $_chunks;

	private $_params = array();

	/**
	 * Runs the action.
	 * This method displays the view requested by the user.
	 * @throws CHttpException if the view is invalid
	 */
	public function run() {

		if (!$this->targetDir)
			$this->targetDir = ini_get("upload_tmp_dir") . DIRECTORY_SEPARATOR . "plupload";

		$this->_filename = $this->cleanFilename(isset($_REQUEST["name"]) ? $_REQUEST["name"] : '');
		$this->_chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
		$this->_chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;
		$this->_params = $_REQUEST;

		@set_time_limit($this->maxExecutionTime);
		$this->handleUpload();
	}

	/**
	 * Cleans the filename and renames it if necessary.
	 * @param string $value a filename
	 * @return string
	 */
	public function cleanFilename($value)
	{
		// Clean the fileName for security reasons
		$value = preg_replace('/[^\w\._]+/', '_', $value);

		// Make sure the fileName is unique but only if chunking is disabled
		if ($this->_chunks < 2 && file_exists($this->targetDir . DIRECTORY_SEPARATOR . $value)) {
			$ext = strrpos($value, '.');
			$fileName_a = substr($value, 0, $ext);
			$fileName_b = substr($value, $ext);

			$count = 1;
			while (file_exists($this->targetDir . DIRECTORY_SEPARATOR . $fileName_a . '_' . $count . $fileName_b))
				$count++;

			return $fileName_a . '_' . $count . $fileName_b;
		} else
			return $value;
	}

	/**
	 * Returns the full path of the file.
	 * @return string
	 */
	public function getFilePath()
	{
		return $this->targetDir . DIRECTORY_SEPARATOR . $this->_filename;
	}

	/**
	 * Returns the header content type.
	 * @return string
	 */
	public function getContentType()
	{
		if (isset($_SERVER["CONTENT_TYPE"]))
			return $_SERVER["CONTENT_TYPE"];
		if (isset($_SERVER["HTTP_CONTENT_TYPE"]))
			return $_SERVER["HTTP_CONTENT_TYPE"];
		return null;
	}

	/**
	 * Handles the file upload.
	 */
	protected function handleUpload() {

		$this->outputHeaders();

		// Create target dir
		if (!file_exists($this->targetDir))
			@mkdir($this->targetDir);

		// Remove old temp files
		if ($this->cleanup)
			$this->removeOldFiles();

		// Handle non multipart uploads older WebKit versions didn't support multipart in HTML5
		if (strpos($this->contentType, "multipart") !== false) {
			if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
				// Open temp file
				$out = fopen("{$this->filePath}.part", $this->_chunk == 0 ? "wb" : "ab");
				if ($out) {
					// Read binary input stream and append it to temp file
					$in = fopen($_FILES['file']['tmp_name'], "rb");

					if ($in) {
						while ($buff = fread($in, 4096))
							fwrite($out, $buff);
					} else
						$this->outputError(101, 'Failed to open input stream.');
					fclose($in);
					fclose($out);
					@unlink($_FILES['file']['tmp_name']);
				} else
					$this->outputError(102, 'Failed to open output stream.');
			} else
				$this->outputError(103, 'Failed to move uploaded file.');
		} else {
			// Open temp file
			$out = fopen("{$this->filePath}.part", $this->_chunk == 0 ? "wb" : "ab");
			if ($out) {
				// Read binary input stream and append it to temp file
				$in = fopen("php://input", "rb");

				if ($in) {
					while ($buff = fread($in, 4096))
						fwrite($out, $buff);
				} else
					$this->outputError(101, 'Failed to open input stream.');

				fclose($in);
				fclose($out);
			} else
				$this->outputError(102, 'Failed to open output stream.');
		}

		// Check if file has been uploaded
		if (!$this->_chunks || $this->_chunk == $this->_chunks - 1) {
			// Strip the temp .part suffix off
			rename("{$this->filePath}.part", $this->filePath);
		}

		// Run success callback if specified
		if ($this->success) {
            try {
                $response = call_user_func($this->success, $this->filePath, $this->_params);
                echo CJSON::encode($response);
                Yii::app()->end();
            } catch (Exception $e) {
                echo CJSON::encode(array(
                    'success'=>FALSE,
                    'errors'=>array('Exception: ' . $e->getMessage()),
                ));
                Yii::app()->end();
            }
		}

		// Return JSON response
		echo CJSON::encode(array(
			'filepath'=>$this->filePath,
			'params'=>$this->_params,
		));
		Yii::app()->end();
	}

	/**
	 * Removes old files from the destination directory.
	 */
	protected function removeOldFiles()
	{
		if (is_dir($this->targetDir) && ($dir = @opendir($this->targetDir))) {
			while (($file = readdir($dir)) !== false) {
				$tmpfilePath = $this->targetDir . DIRECTORY_SEPARATOR . $file;

				// Remove temp file if it is older than the max age and is not the current file
				if (preg_match('/\.part$/', $file) && (filemtime($tmpfilePath) < time() - $this->maxFileAge) && ($tmpfilePath != "{$this->filePath}.part")) {
					@unlink($tmpfilePath);
				}
			}

			closedir($dir);
		} else
			$this->outputError(100, 'Failed to open temp directory "'.$this->targetDir.'".');
	}

	/**
	 * Outputs HTML headers.
	 */
	protected function outputHeaders()
	{
		// HTTP headers for no cache etc
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
	}

	/**
	 * Outputs an error in JSON.
	 * @param integer $code error code
	 * @param string $message error message
	 */
	protected function outputError($code, $message)
	{
		echo CJSON::encode(array(
			'error' => array(
				'code'=>$code,
				'message'=>$message,
			)
		));
		Yii::app()->end();
	}
}
