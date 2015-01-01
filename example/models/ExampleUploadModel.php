<?php

namespace app\models;

use cyneek\yii2\fileupload\models\FileModel;

class ExampleUploadModel extends FileModel
{

	/**
	 * class name of the parent object that will hold the file
	 * @var string
	 */


	/**
	 * manually defined in each instantiation of the class
	 * will define an additional filter to the parent object
	 * because objects can hold more than one type of image
	 * @var string
	 */

	public $file_required = TRUE;

	/**
	 * Sets the path name of the file where it will be uploaded inside app/uploads/{$uploadPath}/year/month/day
	 *
	 * @var string
	 */


	/**
	 * Returns an array with the default values for this implementation of FileModel
	 *
	 * @return array
	 */
	public static function default_values()
	{
		return [
			'file_type' => 'exampleUpload',
			'upload_path' => 'exampleUpload'
		];
	}


	public function _copies_data()
	{
		return ['operations' => [['action' => 'resize', 'height' => '100', 'width' => 100, 'size' => 5000]]];
	}

}