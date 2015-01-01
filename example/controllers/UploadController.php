<?php

namespace app\controllers;

use app\models\ExampleModel;
use app\models\ExampleUploadModel;
use Yii;
use yii\base\Model;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class UploadController extends Controller
{


	public function actions()
	{
		return [
			'error' => [
				'class' => 'yii\web\ErrorAction',
			],
		];
	}

	/**
	 * Inserts / updates a new object with a file linked to it.
	 * 
	 * @param integer|bool $id
	 * @return string
	 * @throws NotFoundHttpException
	 */
	public function actionIndex($id = FALSE)
	{
		if ($id !== FALSE)
		{
			// loads the object
			$object = ExampleModel::findOne($id);
			
			// if it doesn't exist in the database, will throw an exception
			if (is_null($object))
			{
				throw new NotFoundHttpException(\Yii::t('yii', 'Page not found.'));
			}
			
			// loads the file linked to the object
			$file = $object->file;
			
			// if there is no file linked to the object, we make one empty file object.
			// if we don't upload any file, it won't be linked at the database, because
			// file object only are inserted / updated when there is a file upload
			if (is_null($file))
			{
				$file = new ExampleUploadModel();
				$object->linkFile($file);
			}
			
		}
		else
		{
			// if we are inserting a new object, we make a new object, a new file object
			// and link them together.
			$object = new ExampleModel();
			$file = new ExampleUploadModel();
			$object->linkFile($file);
		}
	
		// validation for both models.
		// $file object doesn't need to get data from a file upload, it gets it automatically 
		// while validating the model.
		if ($object->load(Yii::$app->request->post()) && Model::validateMultiple([$object, $file])) {
			
			$object->save();
			$file->save();
			
			return 'File uploaded successfully!';
		
		} else {
			return $this->render('index', ['file' => $file, 'object' => $object]);
		}
	}

	/**
	 * Multiupload version of the library
	 * 
	 * @return string
	 */
	public function actionMultiupload()
	{
		// only inserting in this example, so creating new objects
		$object = new ExampleModel();
		$file = new ExampleUploadModel();
		
		
		$files = [];
		
		if (Yii::$app->request->getIsPost())
		{
			// calling the method multiUpload will get all the files uploaded to the ExampleUploadModel
			// model class.
			
			$files = Filemanager::multiUpload(ExampleUploadModel::className());
			
			// link all files to the newly created object
			foreach ($files as $file)
			{
				$file->linkOwner($object);
			}
			
			// add the new object in the first position of the array, we will use the array
			// to call validateMultiple
			array_unshift($files, $object);
		}
		
		if ($object->load(Yii::$app->request->post()) && Model::validateMultiple($files)) {
			
			foreach ($files as $file)
			{
				$file->save();
			}
			
			return 'File uploaded successfully!';
		
		} else {
			return $this->render('multiUpload', ['file' => $file, 'object' => $object]);
		}
	}
	
	
	/**
	 * Deletes an object with id from $id parameter and all the files linked to it.
	 * 
	 * @param $id
	 * @return string
	 * @throws NotFoundHttpException
	 */
	public function actionDelete($id)
	{
		if ($id)
		{
			$object = ExampleModel::findOne($id);
			
			if (is_null($object))
			{
				throw new NotFoundHttpException(\Yii::t('yii', 'Page not found.'));
			}
			
			
			$object->delete(TRUE);
			
			return 'Object with id '.$id.' deleted!';
		}
		else
		{
			throw new NotFoundHttpException(\Yii::t('yii', 'Page not found.'));
		}
	}
}
