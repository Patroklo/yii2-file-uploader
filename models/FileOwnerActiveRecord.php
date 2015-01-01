<?php

namespace cyneek\yii2\fileupload\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Class FileOwnerActiveRecord
 * @package cyneek\yii2\fileupload\models
 */
class FileOwnerActiveRecord extends ActiveRecord
{

	/**
	 * List with all the FileModel files that will be linked automatically to this
	 * ActiveRecord.
	 *
	 * Example:
	 *
	 * return ['desiredParamName' => targetClassName::className()];
	 *
	 * return ['avatar' => avatarFileModel::className()];
	 *
	 * Then, to access the data you'll need to use:
	 *
	 * $model->avatar; // for only one file
	 * $model->avatarOne; // same as above
	 * $model->avatarAll; // for all files
	 *
	 * $model->avatar($id); // will get a file filtering with the parameter
	 * $model->avatarOne($id); // same as above
	 * $model->avatarAll($id); // will get all files matching with the filter passed as parameter
	 *
	 *
	 * @return array
	 */
	protected function linkedFiles()
	{
		return [];
	}


	/**
	 * First checks if $name it's a defined linkedFiled. If so, it will
	 * emulate a get{Name} method and return a hasOne or hasMany FileModel rows
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name)
	{
		$data = $this->_get_linkedFiles($name);

		if (!is_null($data)) {
			$value = $this->_get_File($data);

			return $value->findFor($name, $this);
		}

		return parent::__get($name);
	}

	/**
	 * Calculates if the $key retrieved by _get it's a defined FileModel in this activerecord.
	 * If so it will return the FileModel className and the type of calling that must be done (one or multiple)
	 *
	 * @param $key
	 * @return array|null
	 */
	protected function _get_linkedFiles($key)
	{
		$return_data = [];

		$linkedFiles = $this->linkedFiles();

		if (preg_match('/one$/i', $key) or array_key_exists($key, $linkedFiles)) {
			$return_data['method'] = 'one';
			$key = preg_replace('/one$/i', '', $key);
		} elseif (preg_match('/all$/i', $key)) {
			$return_data['method'] = 'all';
			$key = preg_replace('/all$/i', '', $key);
		} else {
			return NULL;
		}

		if (array_key_exists($key, $linkedFiles)) {
			$return_data['class'] = $linkedFiles[$key];
			return $return_data;
		}

		return NULL;
	}

	/**
	 * Method that emulates callings to get{$name} as if they were parameters
	 *
	 * @param $data
	 * @return ActiveQuery
	 */
	protected function _get_File($data)
	{
		$class = $data['class'];

		$_primary_key_arr = $this->primaryKey();

		$primary_key = reset($_primary_key_arr);

		$reflect = new \ReflectionClass($this);

		$file_type = $class::default_values()['file_type'];

		if ($data['method'] == 'one') {
			$return = $this->hasOne($class, ['record_id' => $primary_key]);
		} else {
			$return = $this->hasMany($class, ['record_id' => $primary_key]);
		}

		return $return->where(['class_id' => $reflect->getShortName(), 'file_type' => $file_type, 'parent_id' => 0]);

	}

	/**
	 * Checks if the passed $name is a defined FileModel in this activeRecord. If so, it will return
	 * a query using the $params as filter
	 *
	 * @param string $name
	 * @param array $params
	 * @return mixed
	 */
	public function __call($name, $params)
	{
		$data = $this->_get_linkedFiles($name);

		if (!is_null($data)) {
			return $this->_call_File($data, $params);
		}

		return parent::__call($name, $params);
	}

	/**
	 * Emulates a join that will return one or all rows that correspond with the FileModel defined in __call
	 * using $params as a filter.
	 *
	 * If $params is null it won't use anything as filter.
	 *
	 * @param $data
	 * @param $params
	 * @return mixed
	 */
	protected function _call_File($data, $params)
	{
		$class = $data['class'];
		$file_type = $class::default_values()['file_type'];

		if (is_null($params)) {
			$params = [];
		} elseif (!is_array($params) and is_integer($params)) {
			$params = ['id' => $params];
		}

		$reflect = new \ReflectionClass($this);

		$filter = array_merge(['record_id' => $this->getPrimaryKey(), 'class_id' => $reflect->getShortName(), 'file_type' => $file_type, 'parent_id' => 0], $params);

		if ($data['method'] == 'one') {
			return $class::findOne($filter);
		} else {
			return $class::findAll($filter);
		}
	}

	/**
	 * Links this object with a FileModel passed as parameter.
	 * If the object it's still not loaded, the FileModel will wait until
	 * EVENT_BEFORE_VALIDATE to gather the data. If it's still not loaded
	 * will throw a validation error.
	 *
	 * @param FileModel $model
	 * @throws \yii\base\Exception
	 */
	public function linkFile(FileModel $model)
	{
		$model->linkOwner($this);
	}
	
	
	/**
	 * Adds the possibility of deleting the files that are linked to this object when deleting the object itself 
	 * 
	 * @param bool $deleteFiles
	 * @return bool|int
	 * @throws \Exception
	 */
	public function delete($deleteFiles = FALSE)
	{
		if ($deleteFiles == TRUE)
		{
			$file_types = $this->linkedFiles();
			
			foreach ($file_types as $type => $class)
			{
				$fieldName = $type.'All';
				$file_list = $this->{$fieldName};
				
				foreach ($file_list as $file)
				{
					$file->delete();
				}
				
			}
		}
		
		return parent::delete();
	}
}