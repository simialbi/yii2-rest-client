<?php
/**
 * Created by PhpStorm.
 * User: simialbi
 * Date: 12.10.2017
 * Time: 10:34
 */

namespace simialbi\yii2\rest;

use Yii;
use yii\base\NotSupportedException;
use yii\db\QueryInterface;

/**
 * Class Query
 *
 * @property string $modelClass the name of the ActiveRecord class.
 */
class Query extends \yii\db\Query implements QueryInterface
{
    /**
     * @var string the model to be selected from
     * @see from()
     */
    public $from;
    /**
     * @var mixed Value of the primary key (special where)
     */
    private $_modelClass;

    /**
     * Constructor.
     *
     * @param string $modelClass the model class associated with this query
     * @param array $config configurations to be applied to the newly created query object
     */
    public function __construct($modelClass, array $config = [])
    {
        $this->modelClass = $modelClass;
        parent::__construct($config);
    }

    /**
     * Prepares for building query.
     * This method is called by [[QueryBuilder]] when it starts to build SQL from a query object.
     * You may override this method to do some final preparation work when converting a query into a SQL statement.
     *
     * @param QueryBuilder $builder
     *
     * @return $this a prepared query instance which will be used by [[QueryBuilder]] to build the SQL
     */
    public function prepare($builder)
    {
        return $this;
    }

    /**
     * Creates a DB command that can be used to execute this query.
     *
     * @param Connection $db the connection used to generate the statement.
     * If this parameter is not given, the `rest` application component will be used.
     *
     * @return Command the created DB command instance.
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     * @throws \yii\base\NotSupportedException
     */
    public function createCommand($db = null): Command
    {
        if ($db === null) {
            $db = Yii::$app->get(Connection::getDriverName());
        }

        $commandConfig = $db->getQueryBuilder()->build($this);
        $command = $db->createCommand($commandConfig);
        $this->setCommandCache($command);

        return $command;
    }

    /**
     * Returns the number of records.
     *
     * @param string $q the COUNT expression. Defaults to '*'.
     * @param Connection $db the database connection used to execute the query.
     * If this parameter is not given, the `db` application component will be used.
     *
     * @return int number of records.
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     * @throws \yii\base\NotSupportedException
     */
    public function count($q = '*', $db = null): int
    {
        if ($this->emulateExecution) {
            return 0;
        }

        $result = $this->createCommand($db)->execute('head');

        /* @var $result \yii\web\HeaderCollection */
        return $result->get('x-pagination-total-count');
    }

    /**
     * {@inheritdoc}
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     * @throws \yii\base\NotSupportedException
     */
    public function exists($db = null): bool
    {
        if ($this->emulateExecution) {
            return false;
        }

        $result = $this->createCommand($db)->execute('head');

        /* @var $result \yii\web\HeaderCollection */
        return ($result->get('x-pagination-total-count', 0) > 0);
    }

    /**
     * Sets the model to read from / write to
     *
     * @param string $tables
     *
     * @return $this the query object itself
     */
    public function from($tables): Query
    {
        $this->from = $tables;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public static function create($from)
    {
        $modelClass = ($from->hasProperty('modelClass')) ? $from->modelClass : null;

        return new self($modelClass, [
            'where' => $from->where,
            'limit' => $from->limit,
            'offset' => $from->offset,
            'orderBy' => $from->orderBy,
            'indexBy' => $from->indexBy,
            'select' => $from->select,
            'selectOption' => $from->selectOption,
            'distinct' => $from->distinct,
            'from' => $from->from,
            'groupBy' => $from->groupBy,
            'join' => $from->join,
            'having' => $from->having,
            'union' => $from->union,
            'params' => $from->params,
        ]);
    }

    /**
     * Getter for modelClass
     * @return mixed
     */
    public function getModelClass()
    {
        return $this->_modelClass;
    }

    /**
     * Setter for modelClass
     * @param mixed $modelClass
     */
    public function setModelClass($modelClass)
    {
        $this->_modelClass = $modelClass;
    }

    /**
     * {@inheritDoc}
     * @throws NotSupportedException
     */
    public function join($type, $table, $on = '', $params = [])
    {
        throw new NotSupportedException('Joins are not supported in rest applications');
    }

    /**
     * {@inheritDoc}
     * @param Command $command
     * @return Command
     */
    protected function setCommandCache($command): Command
    {
        /** @var \yii\db\Command $command */
        $command = parent::setCommandCache($command);
        /** @var Command $command */
        return $command;
    }
}
