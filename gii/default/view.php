<?php
/**
 * This is the template for generating an action view file.
 */

/* @var $this yii\web\View */
/* @var $generator yii\gii\generators\controller\Generator */
/* @var $tables array */

echo "<?php\n";
?>
use pavlm\yii\stats\widgets\StatsWidget;

/* @var $this yii\web\View */
<?= "?>" ?>

<h1>Application db stats</h1>

<?php foreach ($tables as $table => $data): ?>
<h3><?= $table ?> stats</h3>
<?php echo "<?php\n"; ?>
echo StatsWidget::widget([
    'statsAction' => ['stats-<?= $table ?>'],
]);
<?php echo "?>\n"; ?>

<?php endforeach; ?>