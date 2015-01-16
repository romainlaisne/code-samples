<?php
/* @var $this PartsController */
/* @var $model Parts */

$this->breadcrumbs=array(
	'Parts'=>array('index'),
	$model->id=>array('view','id'=>$model->id),
	'Update',
);

$this->menu=array(
	array('label'=>'List Parts', 'url'=>array('index')),
	array('label'=>'Create Parts', 'url'=>array('create')),
	array('label'=>'View Parts', 'url'=>array('view', 'id'=>$model->id)),
	array('label'=>'Manage Parts', 'url'=>array('admin')),
);
?>

<h1>Update Parts <?php echo $model->id; ?></h1>

<?php $this->renderPartial('_form', array('model'=>$model)); ?>