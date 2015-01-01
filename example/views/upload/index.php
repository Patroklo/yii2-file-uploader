<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;


	$form = ActiveForm::begin([
		'options' => ['enctype' => 'multipart/form-data'] // important
	]);


	echo $form->field($object, 'name')->textInput();
	echo $form->field($file, 'uploaded_file')->fileInput();
		
		
	echo Html::submitButton($object->isNewRecord ? 'Upload' : 'Update', [
			'class' => $object->isNewRecord ? 'btn btn-success' : 'btn btn-primary']
	);
	ActiveForm::end();