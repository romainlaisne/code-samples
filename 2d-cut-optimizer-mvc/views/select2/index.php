Hello <?php
// Working with selector
$tags=array('Satu','Dua','Tiga');
echo CHtml::textField('test','',array('id'=>'test','style'=>'width:300px'));
$this->widget('ext.select2.ESelect2',array(
  'selector'=>'#test',
  'options'=>array(
    'tags'=>$tags,
  ),
));


?>