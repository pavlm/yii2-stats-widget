<?php
use yii\helpers\Html;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $form yii\widgets\ActiveForm */
/* @var $generator pavlm\yii\stats\gii\Generator */

echo $form->field($generator, 'controllerClass');
//echo $form->field($generator, 'tables')->listBox($generator->getTablesList(), ['multiple' => true]);
echo $form->field($generator, 'viewPath');
echo $form->field($generator, 'baseClass');


$field = $form->field($generator, 'tables');
echo $field->begin();
echo Html::activeLabel($generator, 'tables');
echo Html::beginTag('div', ['style' => 'height: 250px; overflow-y: auto']);
?>
<table class="table table-striped">
	<tr>
		<th>Name</th>
		<th>Date column</th>
	</tr>
<?php foreach ($generator->getTablesColumns() as $table => $columns): ?>
	<tr>
		<td>
			<?= $table ?>
		</td>
		<td>
			<?php
			echo Html::dropDownList($generator->formName() . "[tables][{$table}]", ArrayHelper::getValue($generator, "tables.{$table}"), 
                array_combine($columns, $columns), ['prompt' => '', 'class' => 'form-control table-columns']);
			?>
		</td>
	</tr>
<?php endforeach; ?>
</table>
<?php 
echo Html::endTag('div');

echo Html::error($generator, 'tables');
echo $field->end();

?>
<a onclick="javascript:$('.table-columns').val('')" class="btn btn-default btn-xs">clear all</a>
<a onclick="javascript:$('.table-columns').val('created_at')" class="btn btn-default btn-xs">select "created_at"</a>
