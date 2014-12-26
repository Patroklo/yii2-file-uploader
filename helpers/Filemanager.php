<?php

namespace cyneek\yii2\fileUpload\helpers;

use cyneek\yii2\fileUpload\models\FileModel;
use Yii;
use yii\web\UploadedFile;


class Filemanager
{
	/**
	 * @param string $model_name
	 * @param string $instance_name
	 */
	public static function multiUpload($model_name, $instance_name = 'uploaded_file')
	{
		$reflect = new \ReflectionClass($model_name);
		$short_model_name = $reflect->getShortName();

		$files = UploadedFile::getInstancesByName($short_model_name.'['.$instance_name.']');

		if (!is_null($files) && count($files) > 0)
		{
			$return_models = [];

			foreach ($files as $file)
			{
				$new_model = new $model_name();
				$new_model->{$instance_name} = $file;
				$return_models[] = $new_model;
			}

			return $return_models;
		}

		return NULL;
	}

	/**
	 *
	 * @param array|string $path_name
	 * @return string
	 */
	public static function getDirectory($path_name = 'uploaded')
	{
		if (is_array($path_name)) {
			$check_array = array_fill(0, count($path_name), '/');

			$arr_trimmed = array_map('trim', $path_name, $check_array);

			$path_name = implode('/', $arr_trimmed);
		}


		$path = [];

		$path[] = $path_name;

		$path[] = date('Y');

		$path[] = date('m');

		$path[] = date('d');

		$directory = implode('/', $path);


		return $directory;
	}

	/**
	 * Makes a random string for changing file naming purposes
	 *
	 * @param int $length
	 */
	public static function getRandomFileName($length = 20)
	{
		return Yii::$app->getSecurity()->generateRandomString($length);
	}
	
	
}