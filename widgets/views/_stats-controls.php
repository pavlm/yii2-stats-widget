<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
$widget = $this->context;

$tag = function($tag, $space = ' ') {
    if (!$tag) return;
    return call_user_func_array([Html::class, 'tag'], $tag) . $space;
}
?>
<div class="stwg-zoom-controls">
</div>
<div class="stwg-controls clearfix">
	<div class="pull-left">
<?php 
?>	
	</div>
	<div class="pull-right">
<?php
echo $tag($widget->btnHome);
echo $tag($widget->rangeLabel);
echo $tag($widget->btnPrev);
echo $tag($widget->btnNext);
echo $tag($widget->btnZoomIn);
echo $tag($widget->btnZoomOut);
list($ttag, $tlabel, $topts) = $widget->totalLabel;
echo Html::beginTag($ttag, $topts);
echo Yii::t('app', $tlabel) . ': ';
echo Html::tag('span', '', ['class' => 'stwg-total-value']);
echo Html::endTag($ttag);
?>
	</div>
</div>