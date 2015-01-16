<?php
/* @var $this PartsController */
/* @var $model Parts */

$this->breadcrumbs=array(
	'Parts'=>array('index'),
	$model->id,
);

$this->menu=array(
	array('label'=>'List Parts', 'url'=>array('index')),
	array('label'=>'Create Parts', 'url'=>array('create')),
	array('label'=>'Update Parts', 'url'=>array('update', 'id'=>$model->id)),
	array('label'=>'Delete Parts', 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->id),'confirm'=>'Are you sure you want to delete this item?')),
	array('label'=>'Manage Parts', 'url'=>array('admin')),
);
?>

<h1>View Parts #<?php echo $model->id; ?></h1>

<?php $this->widget('zii.widgets.CDetailView', array(
	'data'=>$model,
	'attributes'=>array(
		'id',
		'project_id',
		'width',
		'length',
	),
)); ?>


