<?php

namespace app\models;

use cyneek\yii2\fileupload\models\FileOwnerActiveRecord;


/**
 * Class Examplemodel
 * @package app\models
 *
 * @property integer $id
 * @property string $name
 */
class ExampleModel extends FileOwnerActiveRecord
{

	/**
	 * @return array the validation rules.
	 */
	public function rules()
	{
		return [

			[['name'], 'required'],
			[['name'], 'string'],
			[['name'], 'safe'],

		];
	}

	protected function linkedFiles()
	{
		return ['file' => ExampleUploadModel::className()];
	}
}
