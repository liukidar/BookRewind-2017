<?php
/**
 * Created by PhpStorm.
 * User: Luca
 * Date: 11/04/2018
 * Time: 14.41
 */

/**
 * Class MySQL
 */
class MySQL extends mysqli
{
	public static $t_debug = "_MySQL_debug";
	public static $OPERATORS = [["='*'", "IN(*)", "'*',"], ["!='*'", 'NOT IN(*)', "'*',"], ['LIKE %*%', '*', 'LIKE %*% OR ']];

	/**
	 * MySQL constructor.
	 * @param string $db_host
	 * @param string $db_user
	 * @param string $db_password
	 * @param string $db_name
	 */
	public function __construct($db_host = DB_HOST, $db_user = DB_USER, $db_password = DB_PASSWORD, $db_name = DB_NAME)
	{
		mysqli::__construct($db_host, $db_user, $db_password, $db_name);
		if ($this->connect_error) {
			die('Connect Error (' . $this->connect_errno . ')'
				. $this->connect_error);
		}
		$this->set_charset("utf8");
	}

	/**
	 *
	 */
	public function reset()
	{
		if (!$this->checkTable(self::$t_debug)) {
			$this->query('CREATE TABLE `'.self::$t_debug.'` ( `ID` int(11) NOT NULL AUTO_INCREMENT, 
            `date` datetime NOT NULL, `query` text COLLATE utf8_bin NOT NULL, 
            PRIMARY KEY (`ID`) ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin');
		} else {
			$this->query('TRUNCATE TABLE '.self::$t_debug);
		}
	}

	/**
	 * @param $table
	 * @return bool
	 */
	public function checkTable($table)
	{
		$table = $this->real_escape_string($table);
		if ($this->safequery("SHOW TABLES LIKE '$table'")->num_rows == 1) {
			return true;
		}

		return false;
	}

	/**
	 * @param $query
	 * @return bool|mysqli_result
	 */
	public function safequery($query)
	{
		if (!($result = $this->query($query))) {
			$queryEscaped = $this->real_escape_string($query);

			$log = 'INSERT INTO '.self::$t_debug." VALUES (NULL, NOW(),'$queryEscaped')";
			$this->query($log);
			$this->query($query);
		}

		return $result;
	}

	/**
	 * @param $str
	 * @param int $wait_for
	 * @return bool
	 */
	public function lock($str, $wait_for = 1000)
	{
		$res = $this->query("SELECT GET_LOCK('$str', $wait_for)");
		if ($res) {
			$arr = $res->fetch_row();
			return $arr[0];
		}

		return false;
	}

	/**
	 * @param $str
	 */
	public function unlock($str)
	{
		$str = $this->real_escape_string($str);
		$this->query("SELECT RELEASE_LOCK('$str')");
	}

	public function unlock_wait($str, $wait_for, $random = 1000){
		usleep($wait_for + rand(0, $random));
		$this->unlock($str);
	}

	/**
	 * @param $table
	 * @return mixed
	 */
	public function showTable($table)
	{
		$table = $this->real_escape_string($table);
		$res = $this->safequery("SHOW CREATE TABLE $table");
		$res = $res->fetch_array();

		return $res[1];
	}

	function querySelect($params){

		//Fields
		//-------------------------------------------------------------

		$fields = "";
		if(is_array($params['fields'])){
			foreach ($params['fields'] as $key => $value){
				$fields.= "$key";
				if($value){
					$fields.= " as `$value`";
				}
				$fields.= ",";
			}
			$fields = substr($fields, 0, -1);
		}
		else {
			$fields = "*";
		}

		//-------------------------------------------------------------

		//Where
		//-------------------------------------------------------------

		$where = "";
		if(is_array($params['where'])){
			foreach ($params['where'] as $whereQuery){
				$where.= $whereQuery[0].' ';
				if(is_array($whereQuery[2])){
					$separator = self::$OPERATORS[$whereQuery[1]][2];
					$operator = self::$OPERATORS[$whereQuery[1]][1];
					$q = "";
					foreach($whereQuery[2] as $val){
						$val = $this->real_escape_string($val);
						$q.= str_replace('*', $val, $separator);
					}
					$q = substr($q, 0, strlen($q) - strpos('*', $separator));
					$where.= str_replace('*', $q, $operator);
				}
				else {
					$operator = self::$OPERATORS[$whereQuery[1]][0];
					$w[2] = $this->real_escape_string($whereQuery[2]);
					$where.= str_replace('*', $w[2], $operator);
				}
				$where.=" AND ";
			}
			$where = substr($where, 0, -5);
		}
		else{
			$where.= "1=1";
		}

		//-------------------------------------------------------------

		if($params['group']){
			$where.= " GROUP BY ";
			foreach ($params['group'] as $group){
				$where.= $group.',';
			}
			$where = substr($where, 0, -1);
		}

		return "SELECT $fields WHERE $where";
	}
}