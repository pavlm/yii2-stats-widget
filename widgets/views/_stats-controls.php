<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
$widget = $this->context;
?>
<div class="stwg-zoom-controls">
</div>
<div class="stwg-controls clearfix">
	<div class="pull-left">
<?php 
?>	
	</div>
	<div class="pull-right">
	
<?= Html::a('<i class="glyphicon glyphicon-home"></i>', null, ['class' => 'stwg-btn-home btn btn-default btn-xs']); ?>

<?= Html::a('<i class="glyphicon glyphicon-chevron-left"></i>', null, ['class' => 'stwg-btn-prev btn btn-default btn-xs']); ?>

<?= Html::a('<i class="glyphicon glyphicon-chevron-right"></i>', null, ['class' => 'stwg-btn-next btn btn-default btn-xs']); ?>

<?php /* ?>
<?= Html::a('<i class="glyphicon glyphicon-plus"></i>', null, ['class' => 'stwg-btn-zoomin btn btn-default btn-xs']); ?>

<?= Html::a('<i class="glyphicon glyphicon-minus"></i>', null, ['class' => 'stwg-btn-zoomout btn btn-default btn-xs']); ?>
<?php */ ?>

<?= Html::tag('span', '&nbsp;', ['class' => 'stwg-range-label btn btn-default btn-xs']); ?>

<div class="btn-group">
<?php foreach ($widget->levels as list($period, $range, $label)): ?>
<?php
echo Html::a($widget->t($label), null, ['class' => 'stwg-btn-range btn btn-default btn-xs', 'data-period' => $period, 'data-range' => $range]);
?>
<?php endforeach; ?>
</div>
<?php 
echo Html::beginTag('span', ['class' => 'stwg-total-label label label-success']);
echo $widget->t('Total') . ': ';
echo Html::tag('span', '', ['class' => 'stwg-total-value']);
echo Html::endTag('span');
?>
	</div>
</div>