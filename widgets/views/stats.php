<?php
use pavlm\yii\stats\assets\StatsWidgetAsset;
use yii\helpers\Json;
use yii\helpers\Html;

/* @var $this yii\web\View */
$widget = $this->context;

StatsWidgetAsset::register($this);

$id = $widget->getId();
?>
<?= Html::beginTag('div', array_merge(['id' => $id], $widget->options))?>
<?= Html::tag('canvas', null, array_merge(['id' => $id . '-canvas'], $widget->canvasOptions)) ?>
<?= $this->render($widget->viewStatsControls, []) ?>
<?= Html::endTag('div') ?>
<?php
$optionsJson = Json::encode($widget->getClientOptions());
$this->registerJs("
$('#{$id}').statsWidget({$optionsJson});
");
?>