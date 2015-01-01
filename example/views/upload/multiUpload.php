<?php
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use kartik\widgets\FileInput;


$form = ActiveForm::begin([
    'options'=>['enctype'=>'multipart/form-data'] // important
]);



	echo $form->field($object, 'name')->textInput();
	// With model & without ActiveForm
	// Note for multiple file upload, the attribute name must be appended with
	// `[]` for PHP to be able to read an array of files
	echo '<label class="control-label">Add Attachments</label>';
	echo FileInput::widget([
		'model' => $file,
		'attribute' => 'uploaded_file[]',
		'options' => ['multiple' => true],
	]);


echo Html::submitButton($object->isNewRecord ? 'Upload' : 'Update', [
    'class'=>$object->isNewRecord ? 'btn btn-success' : 'btn btn-primary']
);
ActiveForm::end();