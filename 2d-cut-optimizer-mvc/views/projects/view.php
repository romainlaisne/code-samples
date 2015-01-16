<?php
/* @var $this ProjectsController */
/* @var $model Projects */

$this->breadcrumbs=array(
	'Projects'=>array('index'),
	$model->id,
);

$this->menu=array(
	array('label'=>'List Projects', 'url'=>array('index')),
	array('label'=>'Create Projects', 'url'=>array('create')),
	array('label'=>'Update Projects', 'url'=>array('update', 'id'=>$model->id)),
	array('label'=>'Delete Projects', 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->id),'confirm'=>'Are you sure you want to delete this item?')),
	array('label'=>'Manage Projects', 'url'=>array('admin')),
	array('label'=>'Add parts', 'url'=>array('parts/create', 'project_id'=>$model->project_id)),
);
?>

<h1>View Projects #<?php echo $model->id."-".$model->project_desc; ?></h1>

<?php $this->widget('zii.widgets.CDetailView', array(
	'data'=>$model,
	'attributes'=>array(
		'id',
		'project_id',
		'project_desc',
	),
)); ?>

<br />


<div class="button">
	<?php echo CHtml::link(CHtml::encode('+ Add parts for '.$model->project_desc), array('parts/create', 'project_id'=>$model->project_id)); ?>
	<br />

</div>




