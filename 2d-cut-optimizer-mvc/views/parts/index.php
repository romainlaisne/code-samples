<?php
/* @var $this PartsController */
/* @var $dataProvider CActiveDataProvider */

$this->breadcrumbs=array(
	'Parts',
);

$this->menu=array(
	array('label'=>'Create Parts', 'url'=>array('create')),
	array('label'=>'Manage Parts', 'url'=>array('admin')),
);
?>

<h1>Parts</h1>

<?php $this->widget('zii.widgets.CListView', array(
	'dataProvider'=>$dataProvider,
	'itemView'=>'_view',
)); ?>
