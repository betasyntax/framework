<?php namespace Betasyntax\Db\Adapter;

use PDO;
use Betasyntax\Db\DatabaseConfig;

/**
 * MySQLi Pdo
 */
class Mysql implements AdapterInterface
{
  private $_dbh;
  public $_rec_set;

  public function connect(DatabaseConfig $config)
  {
    $app = app();
    $dsn = sprintf('mysql:dbname=%s;host=%s', $config->dbscheme, $config->host);
    try {
      $this->_dbh = new PDO($dsn, $config->user, $config->password, array(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION,PDO::MYSQL_ATTR_INIT_COMMAND => 'SET sql_mode="ALLOW_INVALID_DATES"'));
      $app->pdo = $this->_dbh;
    } catch(PDOException $e) {
      $debug = $app->debugbar;
      $debug::$debugbar['exceptions']->addException($e);
      echo 'ERROR: ' . $e->getMessage();
    }
  }
  public function fetch($sql,$data=null)
  {
    $app = app();
    $started = microtime(true);
    $sth = $this->_dbh->prepare($sql);

    if(!$data) {
      $sth->execute();
    } else {
      $sth->execute(array($data));
    }
    $sth->setFetchMode(PDO::FETCH_OBJ);
    $this->_rec_set = $sth->fetchAll();
    if( ! $app->isProd()) {
      $end = microtime(true);
      $difference = $end - $started;
      $queryTime = number_format($difference, 10);
      app()->debugbar->addCollector(new \Betasyntax\DebugBar\DbCollector());
      $app->pdo_queries[] = [$sql,$queryTime,'test'];
      $app->pdo_records[] = $this->_rec_set;
    }
    return $this->_rec_set;  
  }
  public function execute($sql)
  {
    return $this->_dbh->exec($sql);
  }
  public function columnMeta() 
  {
    return $this->_dbh->getColumnMeta(0);
  }
  public function columnCount() 
  {
    return $this->_dbh->columnCount();
  }
}