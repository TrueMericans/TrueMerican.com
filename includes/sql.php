<?php

/**
* sql()
*
* Encapsulation of mysql functions
*
* @since   PHP5
* @author  DMW Industries
* @internal bindvariable simulation enables for a mysqli transition or in-house caching mechanism
*/

class sql
{
   /**
   * Cache for all statements run
   *
   * @var array $sqlStack
   */
   public  $sqlStack;

   /**
   * The last statement run
   *
   * @var string $lastSql
   */
   public  $lastSql;

   /**
   * Holds results from select statement
   *
   * @var array $results
   */
   public  $results;

   /**
   * affected row count
   *
   * @var int $count
   */
   public  $count;

   /**
   * error flag
   *
   * @var bool $error
   */
   public  $error;

   /**
   * transcation error flag
   *
   * @var bool $transError
   */
   public  $transError;

   /**
   * erorr message
   *
   * @var string $errorMsg
   */
   public  $errorMsg;

   /**
   * last auto id of the last insert
   *
   * @var int $lastInsertId
   */
   public  $lastInsertId;

   /**
   * elasped query time
   *
   * @var int $elapsed
   */
   public  $elapsed;

   /**
   * mysql connection handler
   *
   * @var resource $conn
   */
   private $conn;

   /**
   * transaction flag
   *
   * @var bool $transaction
   */
   private $transaction;

   /**
   * how you want your results formatted
   * column-row = $row['field'][]
   * row-column = $row[]['field']
   * @var string $resultMode
   */
   public $resultMode;

   /**
   * noformatting
   * mixed
   * upper
   * lower
   * @var string $caseManipulation
   */
   public $caseManipulation;

   public $useSqlStack;

   public $clearResultSet;

   public $lightMode;

   public function __construct()
   {
      global $G_DEBUG;
      $this->sqlStack         = array();
      $this->results          = array();
      $this->lastSql          = '';
      $this->errorMsg         = '';
      $this->count            = 0;
      $this->elapsed          = 0;
      $this->error            = false;
      $this->transError       = false;
      $this->transaction      = false;
      $this->conn             = false;
      $this->resultMode       = '';
      $this->caseManipulation = 'upper';
      $this->useSqlStack      = true;
      $this->clearResultSet   = false;
      $this->lightMode        = true;

      //Always off in product/default
      //
      if(! empty($G_DEBUG))
      {
         $this->lightMode = false;
      }
   }

   /**
   * Connect()
   *
   * Creates Connection Handler and selects Database
   *
   * @return bool
   */
   public function Connect($alwaysToMaster=false)
   {
      $mysql_db_user   = DB_SERVER_USERNAME;
      $mysql_db_pass   = DB_SERVER_PASSWORD;
      $this->mysql_db  = DB_DATABASE;
      $mysql_db_server = DB_SERVER;
	  $this->isSlave = false;

      if(! empty($mysql_db_server) & ! empty($this->mysql_db) & ! empty($mysql_db_pass) & ! empty($mysql_db_user))
      {
         $this->conn = mysql_connect($mysql_db_server, $mysql_db_user, $mysql_db_pass);
         if(! $this->conn)
         {
            $this->error = $this->Fail();
            throw new Exception('Unable to connect to database');
         }
         else
         {
            if(! $this->SelectDB($this->mysql_db))
            {
               throw new Exception('Unable to connect to database');
            }
            else
            {
               return $this->Success('Sucessfully connected to database: ' . $this->mysql_db);
            }
         }
      }
      else
      {
         throw new Exception('Missing Database Credentials');
      }
   }

   /**
   * CheckConnection()
   *
   * Revive stale connections
   *
   */
   public function CheckConnection()
   {
      if (! mysql_ping($this->conn) )
      {
         try
         {
            $this->Disconnect();
            $this->Connect();
         }
         catch(Exception $e)
         {
            error_log(__METHOD__.' could not connect or disconnect ('.$e.') Line: '.__LINE__);
         }
      }
   }

   public function Disconnect()
   {
      if(! $this->conn)
      {
         $this->error = $this->Fail();
         throw new Exception('Unable to connect to close database');
      }
      else
      {
         mysql_close($this->conn);
      }
   }

   /**
   * StartTransaction()
   *
   * Initiates transaction
   *
   * @return bool
   */
   public function StartTransaction()
   {
      if(mysql_query('START TRANSACTION'))
      {
          $this->transaction = true;

          return $this->Success('Successfully started transaction');
      }
      else
      {
          return $this->Fail('Failed starting transaction');
      }
   }

   private function RegisterStatistics(&$query)
   {
      if(! $this->lightMode)
      {
         $this->sqlStack[$query] = 0;
         $this->sqlStack[$query] = $this->elapsed;
      }
   }

   /**
   * EndTransaction()
   *
   * Ends transaction
   *
   * @return void
   */
   public function EndTransaction()
   {
      if($this->transError)
      {
          if($this->Rollback())
          {
               $this->transaction = false;

               $this->Success('Successfully ended transaction via ROLLBACK');
          }
      }
      else if($this->Commit())
      {
          $this->transaction = false;
          $this->Success('Successfully ended transaction via COMMIT');
      }
      else
      {
          $this->Fail('Successfully ended transaction via COMMIT');
      }
   }

   /**
   * SelectDB()
   *
   * Selects current transaction database
   *
   * @param string $db
   * @return bool
   */
   public function SelectDB($db = '')
   {
      $dbSel = false;
      if (empty($db))
      {
         $db = $this->mysql_db;
      }
      if(! empty($db))
      {
          $dbSel = mysql_select_db($db, $this->conn);
      }

      return $dbSel;
   }

   /**
   * ValidCredentials()
   *
   * Add any extra validation to the bindvars and query here
   *
   * @param string $query
   * @param array $bindVars
   * @return bool
   */
   private function ValidCredentials(&$query, &$bindVars)
   {
      $valid = false;

      if(! empty($query) && is_array($bindVars))
      {
          $valid = true;
      }

      return $valid;
   }

   /**
   * Select()
   *
   * Accepts a query and simulated bind variable(s) and performs a select
   *
   * @param string $query
   * @param mixed $bindVars
   * @return integer
   */
   public function Select($query = '', $bindVars = array(), &$resultsHolder = array(), $rowProcessor = '')
   {
      $this->CheckConnection();

      $this->count = 0;

      if($this->ValidCredentials($query, $bindVars))
      {
          $this->Bind($query, $bindVars);

          $this->elapsed = $this->GetTime();

          $result = mysql_query($query, $this->conn);

          $this->elapsed -= $this->GetTime();

          $this->RegisterStatistics($query);

          if ($result === false)
          {
               $this->count = $this->Fail();
          }
          else
          {
               $this->count = mysql_num_rows($result);

               if($this->count > 0)
               {
                   $this->results = array();

                   if($this->count > 1)
                   {
                        $i=0;
                        while ($row = mysql_fetch_assoc($result))
                        {
                           foreach($row as $columnName => $columnValue)
                           {
                              $this->buildResultRow($i,$columnName,$columnValue);
                           }
                           if (!empty($rowProcessor) && function_exists($rowProcessor))
                           {
                              $rowProcessor($row);
                           }
                           $i++;
                        }
                   }
                   else
                   {
                        $temp = mysql_fetch_assoc($result);
                        foreach($temp as $columnName => $columnValue)
                        {
                           $rowNum = 0;
                           $this->buildResultRow($rowNum,$columnName,$columnValue);
                        }
                        if (!empty($rowProcessor) && function_exists($rowProcessor))
                        {
                           $rowProcessor($temp);
                        }
                   }

                   $resultsHolder = $this->results;
               }

               mysql_free_result($result);
          }

          return $this->count;
      }
   }

   /**
   * buildResultRow()
   *
   * @param string $query
   * @param mixed $bindVars
   * @return bool
   */
   public function buildResultRow(&$rowNum,$columnName,&$columnValue)
   {
      switch($this->caseManipulation)
      {
         case 'mixed':
            $columnName = ucwords($columnName);
            break;
         case 'lower':
            $columnName = strtolower($columnName);
            break;
         case 'upper':
            $columnName = strtoupper($columnName);
            break;
      }
      switch($this->resultMode)
      {
         case 'column-row':
            $this->results[$rowNum][$columnName] = $columnValue;
            break;
         default:
            $this->results[$columnName][$rowNum] = $columnValue;
            break;
      }
   }

   /**
   * Update()
   *
   * Accepts a query and simulated bind variable(s) and performs an update
   *
   * @param string $query
   * @param mixed $bindVars
   * @return bool
   */
   public function Update($query = '', $bindVars = array())
   {
      $this->CheckConnection();
      if ($this->isSlave)
      {
         $isSlave = true;
         // we shouldnt be writing to slave if currently connected
         try
         {
            $this->Disconnect();
            $this->Connect(true);
         }
         catch(Exception $e)
         {
            error_log(__METHOD__.' could not connect or disconnect ('.$e.') Line: '.__LINE__);
         }
      }
      $this->count = 0;

      if($this->ValidCredentials($query, $bindVars))
      {
         $this->Bind($query, $bindVars);

         $this->elapsed = $this->GetTime();

         $result = mysql_query($query, $this->conn);

         $this->elapsed -= $this->GetTime();

         $this->RegisterStatistics($query);

         if ($isSlave)
         {
            // go back to slave
            try
            {
               $this->Disconnect();
               $this->Connect();
            }
            catch(Exception $e)
            {
               error_log(__METHOD__.' could not connect or disconnect ('.$e.') Line: '.__LINE__);
            }
         }

         if (! $result)
         {
            return $this->Fail('Failed Updating Records');
         }
         else
         {
            //MySQL Wont update if values are the same, so, although true, this may return 0 - also if deleting all rows
            //
            $this->count = mysql_affected_rows($this->conn);

            if (stristr($query,'insert') && stristr($query,'into'))
            {
               $this->lastInsertId = mysql_insert_id($this->conn);
            }
            return true;
         }
      }
   }

   /**
   * Insert()
   *
   * Accepts a query and simulated bind variable(s) and performs an Insert
   *
   * @param string $query
   * @param mixed $bindVars
   * @return bool
   */
   public function Insert($query = '', $bindVars = array())
   {
      return $this->Update($query,$bindVars);
   }

   /**
   * GetMaxTableId()
   *
   * Fetches the max of specified column
   *
   * @param string $tableName
   * @param string $idName
   * @return integer count
   */
   public function GetMaxTableId($tableName = '', $idName = 'id')
   {
      $query = 'SELECT MAX(:ID) FROM :TABLE';

      $bindVars = array('ID'    => $idName,
                        'TABLE' => $tableName);

      return $this->Select($query, $bindVars);
   }

   /**
   * Bind()
   *
   * Simulated bindvariable mechanism mainly for security/sanitisation and improved transition
   *
   * @param string $query
   * @param string $bindvars
   * @return void
   *
   * @todo restructure/position lastSql
   */
   public function Bind(&$query, &$bindvars, $isSQL=true)
   {
      if(count($bindvars) > 0)
      {
          foreach($bindvars as $bind => $var)
          {
            if ($isSQL === true)
            {
               // -- only escape when parsing SQL, but when parsing dodah, we want HTML bindVars to not be escaped
               $var = mysql_real_escape_string($var,$this->conn);
            }
            $query = str_replace(':' . $bind, $var , $query);
          }
      }

      if ($isSQL === true)
      {
         $this->lastSql = $query;
      }
      else
      {
         return $query;
      }
   }

   /**
   * Success()
   *
   * object specific - return-success / debugging layer
   *
   * @param string $message
   * @return integer count
   */
   private function Success($message = '')
   {
      global $G_DEBUG;

      if(isset($G_DEBUG) && $G_DEBUG && ! empty($message))
      {
          error_log('MySQL: ' . $message);
      }

      return true;
   }

   /**
   * Fail()
   *
   * object specific - return-fail / debugging layer
   * Also handles transactions
   *
   * @param string $message
   * @return integer count
   */
   private function Fail($message = '')
   {
      global $G_DEBUG;

      if($this->transaction)
      {
          $this->transError = true;
      }
      else
      {
          $this->error = true;
      }

      if(isset($G_DEBUG) && $G_DEBUG)
      {
          if(! empty($message))
          {
               error_log('MySQL: ' . $message);
          }

          error_log('MySQL ' . mysql_errno($this->conn) . ': ' . mysql_error($this->conn));
      }

      $this->errorMsg = 'MySQL ' . mysql_errno($this->conn) . ': ' . mysql_error($this->conn). ' on '. $this->lastSql;

      return false;
   }

   /**
   * Commit()
   *
   * Commits a transaction
   *
   * @return bool
   */
   public function Commit()
   {
      if(mysql_query('COMMIT'))
      {
         return $this->Success('Transaction(s) successfully Committed');
      }
      else
      {
         $this->Rollback();
         return $this->Fail('Commit failed');
      }
   }

   /**
   * Rollback()
   *
   * Rolls back a transaction
   *
   * @return bool
   */
   public function Rollback()
   {
      if(mysql_query('ROLLBACK'))
      {
          return $this->Success('Transaction(s) successfully Rolled Back');
      }
      else
      {
          return $this->Fail('Rollback failed');
      }
   }

   private function GetTime()
   {
      list($usec, $sec) = explode(" ",microtime());
      return ((float)$usec + (float)$sec);
   }

   public function __destruct()
   {
      if($this->transaction)
      {
          if($this->transError)
          {
               if($this->Rollback())
               {
                   $this->Success('Disconnecting with known error - Rolling Back');
               }
               else
               {
                   $this->Fail('Failed dissconnecting via ROLLBACK');
               }
          }
          else
          {
               if($this->Commit())
               {
                   $this->Success('Dissconnected successfully via COMMIT');
               }
               else
               {
                   $this->Fail('Failed dissconnecting via COMMIT');
               }
          }
      }
      mysql_close($this->conn);
   }
}

?>