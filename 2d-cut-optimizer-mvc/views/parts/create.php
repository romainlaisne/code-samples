<?php
/* @var $this PartsController */
/* @var $model Parts */

$this->breadcrumbs=array(
	'Parts'=>array('index'),
	'Create',
);

$this->menu=array(
	array('label'=>'List Parts', 'url'=>array('index')),
	array('label'=>'Manage Parts', 'url'=>array('admin')),
);
?>

<h1>Current Parts</h1>
<?php $this->widget('zii.widgets.grid.CGridView', array(
	'id'=>'projects-grid',
	'dataProvider'=>$model->search($_GET['project_id']),
	'filter'=>$model,
	'columns'=>array(
		'id',
		'project_id',
		'width',
		'length',
		array(
			'class'=>'CButtonColumn',
		),
	),
)); ?>
<p></p>
<h1 class='view'>Create Parts</h1>

<?php $this->renderPartial('_form', array('model'=>$model)); ?>