<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
$widget = $this->context;
?>
<div class="stwg-zoom-controls">
</div>
<div class="stwg-controls clearfix">
	<div class="float-left">
<?php 
?>	
	</div>
	<div class="float-right">
	
<?= Html::a('<i class="fas fa-home"></i>', null, ['class' => 'stwg-btn-home btn btn-light btn-xs']); ?>

<?= Html::a('<i class="fas fa-arrow-left"></i>', null, ['class' => 'stwg-btn-prev btn btn-light btn-xs']); ?>

<?= Html::a('<i class="fas fa-arrow-right"></i>', null, ['class' => 'stwg-btn-next btn btn-light btn-xs']); ?>

<?= Html::tag('span', '&nbsp;', ['class' => 'stwg-range-label btn btn-light btn-xs']); ?>

<div class="btn-group">
<?php foreach ($widget->levels as list($period, $range, $label)): ?>
<?php
echo Html::a($widget->t($label), null, ['class' => 'stwg-btn-range btn btn-light btn-xs', 'data-period' => $period, 'data-range' => $range]);
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