<?php
/* @var $this ProjectsController */
/* @var $model Projects */

$this->breadcrumbs=array(
	'Projects'=>array('index'),
	'Create',
);

$this->menu=array(
	array('label'=>'List Projects', 'url'=>array('index')),
	array('label'=>'Manage Projects', 'url'=>array('admin')),
);
?>

<h1>Create Projects</h1>

<?php $this->renderPartial('_form', array('model'=>$model)); ?>