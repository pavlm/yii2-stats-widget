<?php
/**
 * This is the template for generating a controller class file.
 */

use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/* @var $this yii\web\View */
/* @var $generator yii\gii\generators\controller\Generator */
/* @var $tables array */

echo "<?php\n";
?>

namespace <?= $generator->getControllerNamespace() ?>;

use pavlm\yii\stats\actions\StatsAction;
use pavlm\yii\stats\data\QueryStatsProvider;
use pavlm\yii\stats\factories\TimeSeriesProviderCallbackFactory;
use yii\db\Query;


class <?= StringHelper::basename($generator->controllerClass) ?> extends <?= '\\' . trim($generator->baseClass, '\\') . "\n" ?>
{
	public function actions()
	{
		return [
<?php foreach ($tables as $table => $data): ?>
            'stats-<?= $table ?>' => [
                'class' => StatsAction::class,
                'providerFactory' => new TimeSeriesProviderCallbackFactory(function ($rangeStart, $rangeEnd, $periodInterval, $timeZone) {
                    return new QueryStatsProvider([
                        'query' => (new Query())->from('<?= $table ?>'),
                        'dateField' => '<?= $data['dateColumn'] ?>',
                        'dateFieldType' => '<?= $data['type'] ?>',
                        'rangeStart' => $rangeStart,
                        'rangeEnd' => $rangeEnd,
                        'periodInterval' => $periodInterval,
                        'timeZone' => $timeZone,
                        //'timeZoneConnection' => 'Europe/Moscow',
                    ]);
                }),
            ],
<?php endforeach; ?>
		];
	}

    public function actionIndex()
    {
        return $this->render('index');
    }

}
