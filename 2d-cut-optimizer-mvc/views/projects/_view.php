<?php
/* @var $this ProjectsController */
/* @var $data Projects */
?>

<div class="view">
	<b><?php echo CHtml::encode($data->getAttributeLabel('project_desc')); ?>:</b>
	<?php echo CHtml::link(CHtml::encode($data->project_desc), array('view', 'id'=>$data->id)); ?>
	<br />
	
	<b><?php echo CHtml::encode($data->getAttributeLabel('id')); ?>:</b>
	<?php echo CHtml::link(CHtml::encode($data->id), array('view', 'id'=>$data->id)); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('project_id')); ?>:</b>
	<?php echo CHtml::encode($data->project_id); ?>
	<br />

</div>