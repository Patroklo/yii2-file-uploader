<?php

namespace cyneek\yii2\fileUpload\helpers;

use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Cache\Adapter;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;

class File {

	/** @var Filesystem  */
	var $filesystem;

	/** @var \League\Flysystem\Handler  */
	var $file;

	/** @var string */
	var $extension;

	/** @var string */
	var $name;

	/** @var string */
	var $mime_type;

	/** @var string */
	var $exif;

	/** @var integer */
	var $timeStamp;

	/** @var string */
	var $path;

	/** @var string */
	var $content;
	
	/** @var boolean */
	var $is_image;

	/**
	 * Constructor
	 * @param string $key
	 * @param AbstractAdapter|Adapter $adapter
	 * @throws FileNotFoundException
	 */
	public function __construct($key, AbstractAdapter $adapter)
	{

		$this->filesystem = new Filesystem($adapter);

		if ( ! $this->filesystem->has($key))
		{
			throw new FileNotFoundException($key);
		}

		// TODO eliminar de $key el prefix de adapter si es que existe.
		// $adapter->applyPathPrefix()

		$this->file = $this->filesystem->get($key);

		$this->key = $this->filesystem->getAdapter()->applyPathPrefix($this->file->getPath());

		$info = new \SplFileInfo($this->key);

		$this->extension = $info->getExtension();

		$this->name = $info->getBasename('.'.$this->extension);

		$this->path = $info->getPath();
		
		$size = getimagesize($this->key);
		
		$this->is_image = ((is_array($size))?TRUE:FALSE);

	}

	/**
	 * Returns the file with its extension
	 *
	 * @return string
	 */
	public function getFileName()
	{
		return $this->file->getPath();
	}

	/**
	 * Returns the full file key
	 *
	 * @return string
	 */
	public function getKey()
	{
		return $this->key;
	}

	/**
	 * @return string name of the file
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Get the extension of the file
	 *
	 * @return string
	 */
	public function getExtension()
	{
		return $this->extension;
	}


	/**
	 * Get the mime Type of the file
	 *
	 * @return string
	 */
	public function getMimeType()
	{
		if (is_null($this->mime_type))
		{
			$this->mime_type = $this->filesystem->getMimetype($this->file->getPath());
		}

		return $this->mime_type;
	}


	/**
	 * Get the exif data from the file, if available
	 *
	 * @return string
	 */
	public function getExif()
	{
		if (is_null($this->exif))
		{
			$exif_data =  (function_exists('exif_read_data') ? @exif_read_data($this->getKey()) : FALSE);

			if ($exif_data != FALSE)
			{
				$this->exif = json_encode($exif_data);
			}
			else
			{
				$this->exif = '';
			}
		}

		return $this->exif;
	}


	/**
	 * @return int size of the file
	 */
	public function getSize()
	{
		// given that file size can change, let's not save the data
		
		$data = $this->filesystem->getAdapter()->getSize($this->file->getPath());
		$size = $data['size'];
		
		return $size;
	}

	/**
	 * Returns the file modified time
	 *
	 * @return int
	 */
	public function getTimestamp()
	{
		if (is_null($this->timeStamp))
		{
			$this->timeStamp = $this->filesystem->getAdapter()->getTimestamp($this->file->getPath());
		}

		return $this->timeStamp;
	}

	/**
	 * returns the full path to the file
	 *
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * Indicates whether the file exists in the filesystem
	 *
	 * @return boolean
	 */
	public function exists()
	{
		return $this->file->isFile();
	}


	public function delete()
	{
		return $this->filesystem->delete($this->file->getPath());
	}

	/**
	 * Returns the content
	 *
	 * @return string
	 */
	public function getContent()
	{
		if (isset($this->content)) {
			return $this->content;
		}

		return $this->content = $this->filesystem->read($this->file->getPath());
	}

	/**
	 * Returns a boolean defining if the file is an image
	 * 
	 * @return bool
	 */
	public function getIsImage()
	{
		return $this->is_image;
	}
}
