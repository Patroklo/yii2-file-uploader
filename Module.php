<?php
namespace cyneek\yii2\fileUpload;


use Yii;

/**
 * Fileupload module
 *
 * @author joseba <joseba.juaniz@gmail.com>
 */
class Module extends \yii\base\Module
{
    /**
     * @var string Module version
     */
    protected $_version = "1.0";

    /**
     * @var string Alias for module
     */
    public $alias = "@fileupload";

   
    /**
     * @var array Model classes, e.g., ["User" => "amnah\yii2\user\models\User"]
     * Usage:
     *   $user = Yii::$app->getModule("user")->model("User", $config);
     *   (equivalent to)
     *   $user = new \amnah\yii2\user\models\User($config);
     *
     * The model classes here will be merged with/override the [[getDefaultModelClasses()|default ones]]
     */
    public $modelClasses = [];

    /**
     * @var array Storage for models based on $modelClasses
     */
    protected $_models;

    /**
     * Get module version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->_version;
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        // override modelClasses
        $this->modelClasses = array_merge($this->getDefaultModelClasses(), $this->modelClasses);

        // set alias
        $this->setAliases([
            $this->alias => __DIR__,
        ]);
    }

    /**
     * Get default model classes
     */
    protected function getDefaultModelClasses()
    {
        // use single quotes so nothing gets escaped
        return [
            'FileModel'       => 'cyneek\yii2\fileUpload\models\FileModel',
            'FileOwnerActiveRecord' => 'cyneek\yii2\fileUpload\models\FileOwnerActiveRecord',
        ];
    }

    /**
     * Get object instance of model
     *
     * @param string $name
     * @param array  $config
     * @return ActiveRecord
     */
    public function model($name, $config = [])
    {
        // return object if already created
        if (!empty($this->_models[$name])) {
            return $this->_models[$name];
        }

        // create model and return it
        $className = $this->modelClasses[ucfirst($name)];
        $this->_models[$name] = Yii::createObject(array_merge(["class" => $className], $config));
        return $this->_models[$name];
    }

}