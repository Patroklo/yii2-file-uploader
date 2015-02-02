<?php

namespace cyneek\yii2\fileupload\models;

use cyneek\yii2\fileupload\helpers\Filemanager;
use cyneek\yii2\fileupload\helpers\File;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use yii\base\Exception;
use yii\base\Event;
use yii\base\InvalidCallException;
use yii\base\InvalidParamException;
use yii\base\Model;
use yii\behaviors\BlameableBehavior;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;
use Yii;

/**
 * Class FileModel
 * @package cyneek\yii2\fileupload\models
 */

/**
 * Class FileModel
 * @package cyneek\yii2\fileupload\models
 * 
 * @property integer $id
 *
 * class name of the parent object that will hold the file
 * @property string $class_id
 *
 * manually defined in each instantiation of the class
 * will define an additional filter to the parent object
 * because objects can hold more than one type of image
 * @property string $file_type
 *
 * Id of the object that holds the file
 * @property integer $record_id
 *
 * only has values if a file has more than one copy of itself
 * like images of different sizes, all will reference the main
 * file additionally to its object id
 * @property integer $parent_id
 * @property string $upload_date
 * @property integer $file_order
 * @property string $dir
 * @property string $original_file_name
 * @property string $file_name
 * @property string $mime_type
 * @property string $extension
 * @property integer $file_size
 * @property string $exif
 * @property integer $user_id
 * @property boolean $updated
 */
class FileModel extends ActiveRecord
{

    /**
	 * Holds the uploaded file from the form field
     * @var UploadedFile|Null file attribute
     */
	public $uploaded_file;

	/**
	 * @var File
	 */
	protected $temporary_file;

	/**
	 * Object that holds the file reference, it's the responsible of the $this->record_id value
	 * it must be always loaded.
	 * @var  FileOwnerActiveRecord
	 */
	public $owner_object;

	/**
	 * Sets the path name of the file where it will be uploaded inside app/uploads/{$upload_path}/year/month/day
	 *
	 * @var string
	 */
	public $upload_path;

	/**
	 * @var string
	 */
	public $base_dir = 'uploads';

	/**
	 * Declares if the model requires a file upload each time or not.
	 * If it doesn't require a file upload each time, if there is no file upload, the model
	 * will launch a beforeInsert or beforeUpdate event as isValid = false, so it won't save anything
	 * and also won't throw any error.
	 *
	 * @var boolean
	 */
	public $file_required = TRUE;


	/**
	 * Stores operations when saving the file into it's final destination.
	 * 
	 * It will be used mostly for images, like croping, resizing, etc...
	 * 
	 * 
	 * array['action'	=> 'resize' (crop, etc... there must be a valid yurkinx/image method)
	 *		'height'	=> NULL/pixels,
	 *		'width'		=> NULL/pixels,
	 * 		'master'	=> NULL/int, (constrain reduction, defined like:
	 * 									const NONE    = no constrain
										const WIDTH   = reduces by width
										const HEIGHT  = reduces by height
										const AUTO    = max reduction
										const INVERSE = minimum reduction
										const PRECISE = doesn't keep image ratio)
	 * 		'offset_x'	=> NULL/int (offset for cropping only),
	 * 		'offset_y'	=> NULL/int (offset for cropping only),
	 * 		'size'		=> NULL/bytes
	 *
	 * it will be stored this way:
	 *
	 *  $file_operations = [
	 * 							'file' => [[operation_1], [operation_2]],
	 * 							'file2' => [[operation_3]],
	 * 							...
	 * 						]
	 *
	 *
	 *
	 * @var array
	 */
	protected $file_operations;

	/**
	 * Defines if this file is a copy, if it's TRUE then the model wont make
	 * copies of the file.
	 * 
	 * @var boolean
	 */
	protected $is_copy = FALSE;

	/**
	 * Returns an array with the default values for this implementation of FileModel
	 * 
	 * @return array
	 */
	public static function default_values()
	{
		return [
			'file_type'		=> NULL,
			'upload_path'	=> NULL
		];
	}


	/**
	 * Initialization of all basic values, throws exception if any of the
	 * basic values that needs to run the model it's not defined
	 *
	 * @throws Exception
	 */
	function init()
	{
		parent::init();

		$this->loadDefaultValues();
		
		if (is_null($this->file_type)) {
			throw new Exception('[File object construct()] File Upload objects must be instantiated with a defined file type string.');
		}

		if (is_null($this->upload_path)) {
			throw new Exception('[File object construct()] File Upload objects must have a defined upload path.');
		}


		// list of automatic events needed
		$this->on($this::EVENT_BEFORE_VALIDATE, [$this, '_prepareValidationData']);
		
		$this->on($this::EVENT_BEFORE_INSERT, [$this, '_prepareFileData']);
		
		$this->on($this::EVENT_BEFORE_UPDATE, [$this, '_preUpdateActions']);

		$this->on($this::EVENT_AFTER_INSERT, [$this, '_makeCopies']);

		$this->on($this::EVENT_AFTER_UPDATE, [$this, '_makeCopies']);

		$this->on($this::EVENT_AFTER_INSERT, [$this, '_deployFile']);

		$this->on($this::EVENT_AFTER_UPDATE, [$this, '_deployFile']);

	}

	/**
	 * TableName
	 *
	 * If for any reason you don't want to use the same table for all files, here you can change it.
	 *
	 * @return string
	 */
	public static function tableName()
	{
		return static::getDb()->tablePrefix . "file_uploads";
	}

	/**
	 * Define the upload_date (as allways it's the same, we will surely can use the sweet yii 2 behaviors) and userid
	 * @return array
	 */
	public function behaviors()
	{
		return [
			'upload_date' => [
				'class' => TimestampBehavior::className(),
				'attributes' => [
					ActiveRecord::EVENT_BEFORE_INSERT => 'upload_date',
					ActiveRecord::EVENT_BEFORE_UPDATE => 'upload_date',
				],
				'value' => function () {
					return date('Y-m-d H:i:s');
				},
			],
			'upload_userid' => [
				'class' => BlameableBehavior::className(),
				'createdByAttribute' => 'user_id',
				'updatedByAttribute' => 'user_id',
			]
		];
	}


	/**
	 * @inheritdoc
	 */
	public function loadDefaultValues($skipIfSet = true)
	{
		parent::loadDefaultValues($skipIfSet);

		foreach ($this::default_values() as $key => $value)
		{
			$this->$key = $value;
		}
	}

	/**
	 * Returns the necessary adapter for dealing with file uploading
	 *
	 * Basic is for local file handling, as it uses Flysystem it can define
	 * amazon aws, ftp, etc... The only thing that changes it's the adapter
	 * for the file handling and that's defined here.
	 *
	 *
	 * @return \League\Flysystem\Adapter\AbstractAdapter;
	 */
	protected function fileModelAdapter()
	{
		return new Local(Yii::getAlias('@webroot'));
	}

	/**
	 * Changes the uploaded file from it's initial destination to a temp directory
	 * where we will be able to work with it more comfortably using the Flysystem
	 *
	 * @return NULL
	 */
	protected function moveUploadedToTemporary()
	{

		$base_address = Yii::getAlias('@webroot/'.$this->base_dir.'/temp/');
		$file_name = $this->uploaded_file->getBaseName().'.'.$this->uploaded_file->getExtension();

		// creates the temp directory in case it doesn't exist
		FileHelper::createDirectory($base_address);

		$i = 1;

		while (is_file($base_address.$file_name))
		{
			$file_name = $this->uploaded_file->getBaseName().'_'.$i.'.'.$this->uploaded_file->getExtension();
			$i++;
		}

		$this->uploaded_file->saveAs($base_address.$file_name, FALSE);

		$this->temporary_file = new File($file_name, new Local($base_address));

	}


	/**
	 * As we can have secondary files (usually for images that have more than one size when uploaded),
	 * returns the parent file that it's the original file and parent of the loaded object
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getParent()
	{
		if ( is_null($this->parent_id))
		{
			return NULL;
		}

		return $this->hasOne($this::className(), [ 'parent_id' => 'id' ]);
	}

	/**
	 * Returns full path to file (including extension)
	 *
	 * @return string
	 */
	public function getFile()
	{
		return $this->dir.'/'.$this->file_name.'.'.$this->extension;
	}

	/**
	 * Same as getFile but using the old attributes of the object.
	 * 
	 * @return string
	 */
	public function getOldFile()
	{
		return $this->getOldAttribute('dir').'/'.$this->getOldAttribute('file_name').'.'.$this->getOldAttribute('extension');
	}

	/**
	 * Returns a copy that the file might have in the system.
	 * Not very useful, only when you are totally sure that there is only one copy of the file, 
	 * you probably want to use getCopies() instead
	 * 
	 * @return \yii\db\ActiveQuery
	 */
	public function getCopy()
	{
		return $this->hasOne($this::className(), [ 'parent_id' => 'id' ]);
	}

	/**
	 * Returns all copies that the file have in the system.
	 * 
	 * 
	 * @return \yii\db\ActiveQuery
	 */
	public function getCopies()
	{
		return $this->hasMany($this::className(), [ 'parent_id' => 'id' ]);
	}
	
	/**
	 * Sets the owner of the file, it lets the model getting it's primary key and class_id to add it into the
	 * table.
	 *
	 * If the owner_object aready have data will be loaded into this object, if not,
	 * it will wait until the EVENT_BEFORE_VALIDATE it's called to try it again.
	 * 
	 * @param FileOwnerActiveRecord $owner_object
	 * @throws Exception
	 */
	public function linkOwner($owner_object)
	{
		if (!($owner_object instanceof Model) or !($owner_object instanceof ActiveRecord))
		{
			throw new Exception('[File object linkOwner] Owner object must be instance of Model or ActiveRecord');
		}

		$this->owner_object = $owner_object;

		$reflect = new \ReflectionClass($this->owner_object);

		$this->class_id = $reflect->getShortName();

		if ( ! $this->owner_object->getIsNewRecord())
		{
			$this->record_id = $this->owner_object->getPrimaryKey();
		}
	}

	/**
	 * Rules
	 *
	 * @return array
	 */
	function rules()
	{
		$rules = [
			[['class_id', 'file_type', 'dir', 'file_name', 'mime_type', 'extension', 'file', 'exif'], 'string'],
			[['class_id', 'upload_path'], 'required'],
			[['record_id', 'parent_id', 'file_order', 'user_id', 'file_size'], 'integer'],
			[['uploaded_file'], 'file', 'maxFiles' => 1],
			[['class_id','file_type','record_id','parent_id','upload_date','file_order','dir','file_name','mime_type','extension','file_size','exif','user_id','updated'], 'safe']
		];

		if ($this->file_required)
		{
			$rules[] = [['uploaded_file'], 'required'];
		}

		return $rules;
	}

	/**
	 * Launched every EVENT_BEFORE_VALIDATE event.
	 *
	 * Loads automatically the uploaded file before validating the model it
	 * there is not already defined
	 *
	 * @param Event $event
	 * @return NULL
	 */
	protected function _prepareValidationData(Event $event)
	{
		// gets automatically the uploaded data
		if (is_null($this->uploaded_file) or !($this->uploaded_file instanceof UploadedFile))
		{
			$this->uploaded_file =  UploadedFile::getInstance($this, 'uploaded_file');
		}
	}
	


	/**
	 * Launched every EVENT_BEFORE_INSERT event.
	 * 
	 * Updates the fields previously to inserting them into the database
	 *
	 * If there is no uploaded file returns a not valid event and the save() will stop
	 * 
	 * Also, if $record_id it's not defined, tries again to give it a value.
	 *
	 * @param Event $event
	 * @throws \Exception
	 * @return bool|NULL
	 */
	protected function _prepareFileData(Event $event)
	{
		if (is_null($this->uploaded_file))
		{
			$event->isValid = FALSE;
			return FALSE;
		}
		
		if (is_null($this->record_id) && !is_null($this->owner_object) && $this->owner_object->getIsNewRecord() == FALSE)
		{
			$this->record_id = $this->owner_object->getPrimaryKey();
		}
		elseif(is_null($this->record_id))
		{
			throw new Exception('[File object _prepareFileData()] File Upload objects must have a defined record_id.');
		}
		
		// move the file to a temporary directory
		$this->moveUploadedToTemporary();

		// get the directory where it will be stored the file in the form of upload_path/year/month/day
		$this->dir = Filemanager::getDirectory( [$this->base_dir , $this->upload_path]);

		$this->original_file_name =  $this->temporary_file->getName();

		$this->mime_type = $this->temporary_file->getMimeType();

		$this->extension = $this->temporary_file->getExtension();

		// we generate a random string for the file name if file_name it's not defined
		// if it has been previously defined, it can overwrite an existing file
		// if not, we will make a new unique fileName.
		if (is_null($this->file_name))
		{
			$this->file_name = Filemanager::getRandomFileName();
			$file_manager = new Filesystem($this->fileModelAdapter());
			
			if ($file_manager->has($this->file))
			{
				while ($file_manager->has($this->file) == TRUE)
				{
					$this->file_name = Filemanager::getRandomFileName();
				}
			}
		}

		$this->file_size = $this->temporary_file->getSize();

		$this->exif = $this->temporary_file->getExif();
		
		// define the file_order, if it's null, will set it to the max amount of files plus one
		if (is_null($this->file_order))
		{
			$class = $this::className();

			$max_file_order = $class::find()->where(['class_id'	=> $this->class_id, 
											'file_type'	=> $this->file_type,
											'record_id'	=> $this->record_id,
											'parent_id'	=> 0
										])->select('max(file_order)')->scalar();
			
			$this->file_order = $max_file_order + 1;
		}
		
		$this->_executeOperations();
		
	}

	/**
	 * Called before inserting or updating a file.
	 * 
	 * Executes the operations stored at $file_operations
	 * 
	 * 
	 * @throws \yii\base\ErrorException
	 */
	protected function _executeOperations()
	{
		// if there is an operation defined for the file, it will be launched here.
		if (!is_null($this->file_operations) and $this->temporary_file->getIsImage())
		{

			if (!is_array(reset($this->file_operations)))
			{
				$this->file_operations = [$this->file_operations];
			}


			foreach ($this->file_operations as $operation)
			{
				// checks if image magick is installed on the server
				// it will use it preferably over GD
				if(extension_loaded('imagick'))
				{
					$image = new \yii\image\drivers\Image_Imagick($this->temporary_file->getKey());
				}
				else
				{
					$image = new \yii\image\drivers\Image_GD($this->temporary_file->getKey());
				}

				if ($operation['action'] == 'resize')
				{
					$height = ((array_key_exists('height', $operation))?$operation['height']:NULL);
					$width = ((array_key_exists('width', $operation))?$operation['width']:NULL);
					$master = ((array_key_exists('master', $operation))?$operation['master']:$image::INVERSE);

					$image->resize($width, $height, $master);
				}
				elseif ($operation['action'] == 'crop')
				{
					$height = ((array_key_exists('height', $operation))?$operation['height']:$image->height);
					$width = ((array_key_exists('width', $operation))?$operation['width']:$image->width);
					$offset_x = ((array_key_exists('offset_x', $operation))?$operation['offset_x']:0);
					$offset_y = ((array_key_exists('offset_y', $operation))?$operation['offset_y']:0);

					$image->crop($width, $height, $offset_x, $offset_y);
				}
				elseif ($operation['action'] == 'size')
				{
					// it will change the weight of the file, this code will be executed later
					// so this if clause it's empty
				}
				elseif ($operation['action'] == 'crop_middle')
				{
					// it will crop the image just in the middle, using only width and height

					$height = ((array_key_exists('height', $operation))?$operation['height']:$image->height);
					$width = ((array_key_exists('width', $operation))?$operation['width']:$image->width);
					$offset_x = round(($image->width - $width) / 2);
					$offset_y = round(($image->height - $height) / 2);

					$image->crop($width, $height, $offset_x, $offset_y);
				}
				else
				{
					throw new InvalidCallException('[_deployFile] The method '.$operation['action'].' does not exist.');
				}

				$image->save();


				if (array_key_exists('size', $operation))
				{
					// PHP doesn't update file sizes after croping/resizing, it weights the same. So we have to do a tweak
					// to change the file to force PHP to update the file weight by that

					$fileWeightResize = new File($this->temporary_file->getFileName(), new Local($this->temporary_file->getPath()));

					$quality = 95;

					if ($fileWeightResize->getSize() > $operation['size'])
					{
						while (($fileWeightResize->getSize() > $operation['size']) and $quality > 10)
						{
							$image->save(NULL, $quality);
							$quality = $quality - 5;

							$fileWeightResize = new File($this->temporary_file->getFileName(), new Local($this->temporary_file->getPath()));
						}

						$this->temporary_file = $fileWeightResize;
					}
				}

				// probably the file size will have changed, so we recalculate it

				$this->file_size = $this->temporary_file->getSize();
			}
		}
	}

	/**
	 * Launched every EVENT_BEFORE_UPDATE event.
	 *
	 * If there is no uploaded file returns a not valid event and the save() will stop
	 *
	 * First calls _prepareFileData and then deletes the old file.
	 *
	 * @param Event $event
	 * @return bool|NULL
	 */
	protected function _preUpdateActions(Event $event)
	{

		if ($this->_prepareFileData($event) === FALSE)
		{
			return FALSE;
		}

		$this->updated = 1;

		$old_file = new File($this->oldFile, $this->fileModelAdapter());

		$old_file->delete();
	}

	/**
	 * Launched every EVENT_AFTER_INSERT and EVENT_AFTER_UPDATE event.
	 *
	 * Called once the data of the object has been inserted into the database
	 * we move the file from it's uploaded directory into it's final destination
	 *
	 * @param Event $event
	 * @return NULL
	 */
	protected function _deployFile(Event $event)
	{
		// move the file changing it's name to the id of the file Object
		$file_manager = new Filesystem($this->fileModelAdapter());
		
		$file_manager->write($this->file, $this->temporary_file->getContent());
		$this->temporary_file->delete();
		$this->temporary_file = NULL;
	}

	/**
	 * If there is defined in the _copies_data() method that this file has copies
	 * then there will be made here. 
	 * 
	 * If we are making an update instead of a insert, then the old copies will be deleted.
	 * 
	 * @param Event $event
	 */
	protected function _makeCopies(Event $event)
	{
		$copies_data = $this->_copies_data();
		
		if (is_null($copies_data) or !is_array($copies_data) or $this->is_copy == TRUE)
		{
			return;
		}
		
		// delete all the old copies.
		$this->_deleteCopies();

		if (array_key_exists('number', $copies_data))
		{
			for ($i = 0; $i < $copies_data['number']; $i++)
			{
				$this->makeCopy();
			}
		}
		elseif (array_key_exists('operations', $copies_data))
		{
			foreach ($copies_data['operations'] as $key => $operation)
			{
				$this->makeCopy($operation);
			}
		}
		

	}

	/**
	 * Makes a copy of the file and adds it into the system as a child of the original object
	 * If the object is still not saved, then will throw an exception.
	 * 
	 * The operation can be a crop or resize operations, as defined at $file_operations
	 * 
	 * @param null|array $operation
	 * @return FileModel
	 */
	public function makeCopy($operation = NULL)
	{
		$class = self::className();
		$original_data = $this->getAttributes();
		
		if (is_null($this->id))
		{
			throw new InvalidParamException('[makeCopy] The object must be first loaded/inserted before making a copy from it.');
		}
		
		/** @var FileModel $model */
		$model = new $class();
		
		$model->is_copy = TRUE;
		
		foreach ($original_data as $key => $data)
		{
			$model->{$key} = $data;
		}
		
		$model->file_name = Filemanager::getRandomFileName();
		
		$model->parent_id = $this->id;
		
		$model->uploaded_file = $this->uploaded_file;
		
		$model->file_order = 0;
		
		$model->updated = 0;
		
		$model->id = NULL;
		
		if (is_null($operation))
		{
			$model->save(FALSE);
		}
		else
		{
			$model->saveAs(['validation' => FALSE, 'operation' => $operation]);
		}
		
		return $model;
	}

	/**
	 * Deletes the file and data from database
	 *
	 * @throws \Exception
	 */
	public function delete()
	{
		$file = new File($this->file, $this->fileModelAdapter());

		$file->delete();
		
		// deletes all the copies of the file
		$this->_deleteCopies();

		parent::delete();
	}

	/**
	 * Deletes all the copies of the loaded file
	 */
	protected function _deleteCopies()
	{
		$copies = $this->copies;
		
		if (!empty($copies))
		{
			foreach ($copies as $copy)
			{
				$copy->delete();
			}
		}
	}

	/**
	 * Defines if the insert/upload operations will make copies of the uploaded file
	 * They can be defined in two ways
	 * 
	 * array['number' =>  number_of_copies];
	 * 
	 * or
	 * 
	 * array['operations' => [
	 * 							'file' => [[operation_1], [operation_2]],
	 * 							'file2' => [[operation_3]],
	 * 							...
	 * 						]
	 * 
	 * The operations are crop or resize operations, as defined at $file_operations
	 * 
	 * 
	 * @return array|NULL
	 */
	public function _copies_data()
	{
		return NULL;
	}


	/**
	 * When we want improved options on top of only saving the file
	 * With this you can crop or resize an uploaded image as defined
	 * at $file_operations in this class
	 * 
	 * @param array|boolean $validation
	 * @param string $file_name
	 * @param array $operation
	 */
	public function saveAs($runValidation = true, $file_name = NULL, $operation = NULL)
	{
		if (is_array($runValidation))
		{
			extract($runValidation);
		}
		
		if (! is_null($file_name))
		{
			$this->file_name = $file_name;
		}
		
		if (! is_null($operation))
		{
			$this->file_operations = $operation;
		}

		$this->save($runValidation);
	}
	

}