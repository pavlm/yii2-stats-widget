<?php

namespace pavlm\yii\stats\gii;

use Yii;
use yii\gii\CodeFile;
use yii\helpers\Html;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use pavlm\yii\stats\data\QueryStatsProvider;

/**
 * This generator will generate a controller and view files.
 *
 * @property string $controllerFile The controller class file path. This property is read-only.
 * @property string $controllerID The controller ID. This property is read-only.
 * @property string $controllerNamespace The namespace of the controller class. This property is read-only.
 */
class Generator extends \yii\gii\Generator
{
    /**
     * @var string the controller class name
     */
    public $controllerClass = 'app\controllers\StatsController';
    /**
     * @var string the controller's view path
     */
    public $viewPath;
    /**
     * @var string the base class of the controller
     */
    public $baseClass = 'yii\web\Controller';
    /**
     * @var array
     */
    public $tables;

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'Stats controller generator';
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['controllerClass', 'baseClass'], 'filter', 'filter' => 'trim'],
            [['controllerClass', 'baseClass'], 'required'],
            ['controllerClass', 'match', 'pattern' => '/^[\w\\\\]*Controller$/', 'message' => 'Only word characters and backslashes are allowed, and the class name must end with "Controller".'],
            ['controllerClass', 'validateNewClass'],
            ['baseClass', 'match', 'pattern' => '/^[\w\\\\]*$/', 'message' => 'Only word characters and backslashes are allowed.'],
            ['viewPath', 'safe'],
            ['tables', 'filter', 'filter' => 'array_filter'],
            ['tables', 'required'],
            ['tables', 'each', 'rule' => ['string']],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'baseClass' => 'Base Class',
            'controllerClass' => 'Controller Class',
            'viewPath' => 'View Path',
        ];
    }

    /**
     * @inheritdoc
     */
    public function requiredTemplates()
    {
        return [
            'controller.php',
            'view.php',
        ];
    }

    /**
     * @inheritdoc
     */
    public function stickyAttributes()
    {
        return ['baseClass'];
    }

    /**
     * @inheritdoc
     */
    public function hints()
    {
        return [
            'controllerClass' => 'This is the name of the controller class to be generated. You should
                provide a fully qualified namespaced class (e.g. <code>app\controllers\PostController</code>),
                and class name should be in CamelCase ending with the word <code>Controller</code>. Make sure the class
                is using the same namespace as specified by your application\'s controllerNamespace property.',
            'actions' => 'Provide one or multiple action IDs to generate empty action method(s) in the controller. Separate multiple action IDs with commas or spaces.
                Action IDs should be in lower case. For example:
                <ul>
                    <li><code>index</code> generates <code>actionIndex()</code></li>
                    <li><code>create-order</code> generates <code>actionCreateOrder()</code></li>
                </ul>',
            'viewPath' => 'Specify the directory for storing the view scripts for the controller. You may use path alias here, e.g.,
                <code>/var/www/basic/controllers/views/order</code>, <code>@app/views/order</code>. If not set, it will default
                to <code>@app/views/ControllerID</code>',
            'baseClass' => 'This is the class that the new controller class will extend from. Please make sure the class exists and can be autoloaded.',
        ];
    }

    /**
     * @inheritdoc
     */
    public function successMessage()
    {
        $route = $this->getControllerID() . '/index';
        $link = Html::a('try it now', Yii::$app->getUrlManager()->createUrl($route), ['target' => '_blank']);

        return "The controller has been generated successfully. You may $link.";
    }
    
    public function getTablesColumns()
    {
        $schema = Yii::$app->db->getSchema();
        $columns = [];
        foreach ($schema->tableNames as $table) {
            $columns[$table] = $schema->getTableSchema($table)->columnNames;
        }
        return $columns;
    }

    /**
     * @inheritdoc
     */
    public function generate()
    {
        $files = [];
        $tables = [];
        $typeMapper = function ($type) {
            static $targetTypes = [
                QueryStatsProvider::DATETYPE_DATETIME,
                QueryStatsProvider::DATETYPE_INT,
                QueryStatsProvider::DATETYPE_TIMESTAMP,
            ];
            $type = strtoupper(preg_replace('#\(.*#', '', $type));
            return in_array($type, $targetTypes) ? $type : null;
        };
        foreach (array_filter($this->tables) as $table => $dateColumn) {
            $ts = Yii::$app->db->getTableSchema($table);
            $cs = $ts->getColumn($dateColumn);
            $type = $typeMapper($cs->dbType);
            if (!$type) {
                continue;
            }
            $tables[$table] = [
                'dateColumn' => $dateColumn,
                'type' => $type,
            ];
        }
        $params = [
            'tables' => $tables,
        ];
        
        $files[] = new CodeFile(
            $this->getControllerFile(),
            $this->render('controller.php', $params)
        );
        
        $files[] = new CodeFile(
            $this->getViewFile('index'),
            $this->render('view.php', $params)
        );
        
        return $files;
    }

    /**
     * @return string the controller class file path
     */
    public function getControllerFile()
    {
        return Yii::getAlias('@' . str_replace('\\', '/', $this->controllerClass)) . '.php';
    }

    /**
     * @return string the controller ID
     */
    public function getControllerID()
    {
        $name = StringHelper::basename($this->controllerClass);
        return Inflector::camel2id(substr($name, 0, strlen($name) - 10));
    }

    /**
     * @param string $action the action ID
     * @return string the action view file path
     */
    public function getViewFile($action)
    {
        if (empty($this->viewPath)) {
            return Yii::getAlias('@app/views/' . $this->getControllerID() . "/$action.php");
        } else {
            return Yii::getAlias($this->viewPath . "/$action.php");
        }
    }

    /**
     * @return string the namespace of the controller class
     */
    public function getControllerNamespace()
    {
        $name = StringHelper::basename($this->controllerClass);
        return ltrim(substr($this->controllerClass, 0, - (strlen($name) + 1)), '\\');
    }
}
