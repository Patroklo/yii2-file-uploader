# Yii2 File Uploader Manager

File upload manager for yii2 that links ActiveRecord objets to files and galleries.

## What's File Uploader Manager?

This module adds a new extension to ActiveRecord that let developers connecting files or it's copies to another ActiveRecord objects.

Developed by Joseba Juániz ([@Patroklo](http://twitter.com/Patroklo))

[Spanish Readme version](https://github.com/Patroklo/yii2-file-uploader/blob/master/README_spanish.md)

## Minimum requirements

* Yii2
* Php 5.4 or above

## Future plans

* None right now.

## License

This is free software. It is released under the terms of the following BSD License.

Copyright (c) 2014, by Cyneek
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions
are met:
1. Redistributions of source code must retain the above copyright
   notice, this list of conditions and the following disclaimer.
2. Redistributions in binary form must reproduce the above copyright
   notice, this list of conditions and the following disclaimer in the
   documentation and/or other materials provided with the distribution.
3. Neither the name of Cyneek nor the names of its contributors
   may be used to endorse or promote products derived from this software
   without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDER "AS IS" AND ANY
EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER BE LIABLE FOR ANY
DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

## Instalation

* Install [Yii 2](http://www.yiiframework.com/download)
* Install package via [composer](http://getcomposer.org/download/) 
		
		`"cyneek/yii2-fileupload": "dev-master", "yurkinx/yii2-image": "@dev"`
		 
		(I haven't managed to install the library in stable versions of Yii 2 because image library and the strange way of working composer has. If someone knows how to do it without adding this superfluous reference to the principal composer.json file, please tell :sweat_smile:).
		
* Update config file _'config/web.php'_

		
			'modules' => [
				'fileupload' => [
					'class' => 'cyneek\yii2\fileupload\Module'
				]
				// set custom modules here
			],
		

* Apply the migration in the migrations directory
	* ```php yii migrate --migrationPath=@vendor/cyneek/yii2-fileupload/migrations```
* Be sure that Php can write on the "web/" directory of your Yii 2 installation.
* Profit!


## Definition

The library will let users to upload or manage files through objects that extend from the FileModel.php model. This file extends ActiveRecord and can be used in database operations and web forms.

These objects must be linked to another object which will be capable of getting all files linked to it through database operations.

This package contains 2 different models:

* FileModel.php : it's the main model. All file objects must extend this archive. To be able to use it properly the developer has to fill some basic configuration data that will be explained later in the readme.

* FileOwnerActiveRecord.php : it's a model that extends ActiveRecord. It can be used to be extended by objects that have files linked to them. It provides syntactic sugar to retrieve one or more files that are linked to them and to link this objects to already existing file objects.

The package uses the "flysystem" library, that let's users to work with files in local servers, via ftp, dropbox, and so on, so all this configurations are also potentially available in the FileUploader package to store the files linked to objects. Given that each extended model can have it's own configuration the system can work at the same time with files from AmazonAWS, local, ftp, and so on.

The fileUpload package also uses a image package that let's developers to manage while uploading image sizing, cropping and so on using GD or Imagick php libraries (depending on your Php installation).

### Setting a File model

To be able to use the package, first you must define a model that will manage a kind of files. Each of these object models will hold a different file that can be linked to any system's model object. But to do this first there must be done some basic configurations.

* Make a new file with a class extending "FileModel".

* Add the following basic configuration data:

	* The "public static function default_values()" method. Must have inside of it a return of an array with a "file_type" string different of every other FileModel that you have ever made in this Yii2 installation and an "upload_path" string that will define the directory where the files will be stored. It's not necessary to be unique, but it's recomended. 
	
	For example:
	
		return [
			'file_type'	=> 'name_that_defines_the_model_in_the_database',
			'upload_path'	=> 'path_where_files_will_be_stored'
		];
		
	This code will make file objects linked to the database with the "name_that_defines_the_model_in_the_database" string and will be stored at the "path_where_files_will_be_stored" directory path. Remember that inside this directory will be a series of subfolders defined as "YYYY"/"MM"/"DD" to keep the files stored in a more tidy way. 
			
			
	* Optional parameter "file_required". It's a boolean that sets the file upload in a web form as required or not. When it's value is "False" Yii2 won't make in the forms obligatory to upload a file in each insert or update operations. It's defined as TRUE by default.

	* Optional method "_copies_data()". Defines if the system will make copies of the uploaded file when the "save()" method it's called.
	
	For example:
	
		return [
				'operations' => [
									['action' => 'resize', 
									 'height' => '100', 
									 'width' => 100, 
									 'size' => 5000]
								]
				];
				
	This code will make a copy of the uploaded file (it should be an image) that will be resized to 100x100 mantaining it's ratio (one of the sides won't be exactly of 100 pixels) and will drop it's color quality until it reaches 5000 bytes of weight.

### Setting a FileOwnerActiveRecord type class

It's recomendable to use objects extending FileOwnerActiveRecord when linking them with file objects, the reason it's that this class brings a group of methods that help this object linking and accessing its linked files. If you want to use another type of class with this library, then you'll have to manually make this links and accesses for every different class.

To be able to use a FileOwnerActiveRecord extended object then you'll have to:

* Make a new file with a class extending "FileOwnerActiveRecord".

* Add a "linkedFiles()" method that will hold an array containing configuration for all the files that could be linked to this object.

		protected function linkedFiles()
		{
			return ['nameOfFieldToAccessFiles' => ExampleFileModel::className(),
						'file' => AnotherFileModel::className()];
		}
		
Important: You can't have methods inside of this class called "getNameOfFieldToAccessFiles()" or "getFile()" because the system will automatically use them for getting the files via Yii2 relationship between tables.

## Basic usage

Once the File model it's made, it's time to start using it.

### Linking a File model to an Owner model

When a file is linked to another object, then this object it's called its "owner", given that now it can access all its files at any time.

The most simple way to handle this is that the other model is also extending FileOwnerActiveRecord class. That way you can use the syntactic sugar attached to it (remember that it's only an ActiveRecord extension with a couple additions).

	// we make a new empty file object
	$file = new ExampleFileModel();
	$file2 = new AnotherFileModel();
	
	// get a loaded object from a class that extends FileOwnerActiveRecord
	$object = new ImportantObject::findOne(1);
	
	// file linked to object. This will be made into the database when launching a save() method for "$file". But remember that you can't save an empty file object.
	$object->linkFile($file);
	$object->linkFile($file2);
	
Another way of doing a link, recommended when the object doesn't extend FileOwnerActiveRecord class is by:

	// we make a new empty file object
	$file = new ExampleFileModel();
	$file2 = new AnotherFileModel();
	
	// get a loaded object from a class that extends FileOwnerActiveRecord
	$object = new ImportantObject::findOne(1);
	
	// Inverse linking a file with an object. 
	$file->linkOwner($object);
	$file2->linkOwner($object);
	
Also, as seen above, an Owner can have different file object types from different classes linked at the same time.

### Accessing a single file linked to an Owner object (only for FileOwnerActiveRecord extended objects)

To access a single file linked to an object (if there are more than one files of a kind linked at the same time, only the first will be retrieved) we will use it's defined name in the class object as a parameter (exactly like when dealing with database relationships in Yii2)

	// get a loaded object from a class that extends FileOwnerActiveRecord
	$object = new ImportantObject::findOne(1);
	
	$file = $object->nameOfFieldToAccessFilesOne;
	$file2 = $object->fileOne;
	
Also it's possible to use:
	
	// get a loaded object from a class that extends FileOwnerActiveRecord
	$object = new ImportantObject::findOne(1);
	
	$file = $object->nameOfFieldToAccessFiles;
	$file2 = $object->file;

### Accessing all files linked to an Owner object (only for FileOwnerActiveRecord extended objects)

This will get an array with all the files linked to an owner object:

	// get a loaded object from a class that extends FileOwnerActiveRecord
	$object = new ImportantObject::findOne(1);
	
	$file = $object->nameOfFieldToAccessFilesAll;
	$file2 = $object->fileAll;

### Simple web form in Yii 2 with file uploading

(See example directory)

### Multiupload

It's possible to use this library alongside multiupload libraries thanks to the method "Filemanager::multiUpload" that will return an array with all the files uploaded to a certain FileModel class passed as parameter.

(See example directory for more information).

### Saving a file object

Besides the "save()" method inherited from ActiveRecord class, there is a new method that saves the file object data called "saveAs" that has two additional parameters:

* FileName: string that will define the file name of the file once it's uploaded into its final destination. Be aware that using this option will overwrite previously existing files.
* Operations: an array defining the operations that will be made to the file once it has been inserted / updated.

### Automatic file operations

Defined as copy operations or in the "saveAs" method, these are automatic operations that will change the file once it has been inserted / updated.

They can be defined as an array:

	  array['action'	=> 'resize' (crop, etc... there must be a valid yurkinx/image method)
	 		'height'	=> NULL/pixels,
	 		'width'		=> NULL/pixels,
	  		'master'	=> NULL/int, (constrain reduction, defined like:
	  									const NONE    = no constrain
										const WIDTH   = reduces by width
										const HEIGHT  = reduces by height
										const AUTO    = max reduction
										const INVERSE = minimum reduction
										const PRECISE = doesn't keep image ratio)
	  		'offset_x'	=> NULL/int (offset for cropping only),
	  		'offset_y'	=> NULL/int (offset for cropping only),
	  		'size'		=> NULL/bytes
	  	];