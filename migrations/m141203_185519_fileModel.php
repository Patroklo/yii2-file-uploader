<?php

use yii\db\Schema;
use cyneek\yii2\fileupload\models\FileModel;

class m141203_185519_fileModel extends  \yii\db\Migration
{


	public function up()
	{

		
		$tableOptions = null;
		if ($this->db->driverName === 'mysql') {
			$tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
		}
		
		$this->createTable('file_uploads', [
			'id' => Schema::TYPE_PK,
			'class_id' 	=> Schema::TYPE_STRING . ' NOT NULL',
			'file_type' => Schema::TYPE_STRING . ' NOT NULL',
			'record_id'	=> Schema::TYPE_INTEGER. ' NOT NULL',
			'parent_id' => Schema::TYPE_INTEGER. ' NOT NULL',
			'child_name' => Schema::TYPE_STRING. ' NULL DEFAULT NULL',
			'upload_date' => Schema::TYPE_DATETIME. ' NOT NULL',
			'file_order' => Schema::TYPE_INTEGER. ' NOT NULL',
			'dir' => Schema::TYPE_STRING . ' NOT NULL',
			'original_file_name' => Schema::TYPE_STRING . ' NOT NULL',
			'file_name' => Schema::TYPE_STRING . ' NOT NULL',
			'mime_type' => Schema::TYPE_STRING . ' NOT NULL',
			'extension' => Schema::TYPE_STRING . ' NOT NULL',
			'file_size' => Schema::TYPE_INTEGER . ' NOT NULL',
			'exif' => Schema::TYPE_TEXT . ' NULL DEFAULT NULL',
			'user_id' => Schema::TYPE_INTEGER . ' NULL DEFAULT NULL',
			'updated' => Schema::TYPE_SMALLINT . ' NULL DEFAULT 0'
		], $tableOptions);
		
		$this->createIndex(FileModel::tableName() . "_class_id", FileModel::tableName(), "class_id", false);
		$this->createIndex(FileModel::tableName() . "_child_name", FileModel::tableName(), "child_name", false);
		$this->createIndex(FileModel::tableName() . "_file_type", FileModel::tableName(), "file_type", false);
		$this->createIndex(FileModel::tableName() . "_record_id", FileModel::tableName(), "record_id", false);
		$this->createIndex(FileModel::tableName() . "_user_id", FileModel::tableName(), "user_id", false);
	}
	
    public function down()
    {
       $this->dropTable('file_uploads');
    }
}
