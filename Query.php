<?php
/**
 * Created by PhpStorm.
 * User: simialbi
 * Date: 12.10.2017
 * Time: 10:34
 */

namespace simialbi\yii2\rest;

use yii\db\QueryInterface;
use Yii;

/**
 * Class Query
 * @package apexwire\restclient
 */
class Query extends \yii\db\Query implements QueryInterface {
	/**
	 * @var string the model to be selected from
	 * @see from()
	 */
	public $from;

	/**
	 * @var ActiveRecord
	 */
	public $searchModel;

	/**
	 * Prepares for building query.
	 * This method is called by [[QueryBuilder]] when it starts to build SQL from a query object.
	 * You may override this method to do some final preparation work when converting a query into a SQL statement.
	 *
	 * @param QueryBuilder $builder
	 *
	 * @return $this a prepared query instance which will be used by [[QueryBuilder]] to build the SQL
	 */
	public function prepare($builder) {
		return $this;
	}

	/**
	 * Creates a DB command that can be used to execute this query.
	 * @param Connection $db the connection used to generate the statement.
	 * If this parameter is not given, the `rest` application component will be used.
	 * @return Command the created DB command instance.
	 */
	public function createCommand($db = null) {
		if ($db === null) {
			$db = Yii::$app->get(Connection::getDriverName());
		}

		$commandConfig = $db->getQueryBuilder()->build($this);

		return $db->createCommand($commandConfig);
	}

	/**
	 * Returns the number of records.
	 *
	 * @param string $q the COUNT expression. Defaults to '*'.
	 * @param Connection $db the database connection used to execute the query.
	 * If this parameter is not given, the `db` application component will be used.
	 *
	 * @return int number of records.
	 */
	public function count($q = '*', $db = null) {
		if ($this->emulateExecution) {
			return 0;
		}

		$result = $this->createCommand($db)->execute('head');

		/* @var $result \yii\web\HeaderCollection */

		return $result->get('x-pagination-total-count');
	}

	/**
	 * @inheritdoc
	 */
	public function exists($db = null) {
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
	public function from($tables) {
		$this->from = $tables;

		return $this;
	}
}
