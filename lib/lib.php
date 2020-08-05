<?php
require_once "MySQL.php";
require_once "config.php";
require_once "phpmailer/class.phpmailer.php";

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

/**
 * @return string
 */
function get_client_ip()
{
    if (getenv('HTTP_CLIENT_IP'))
        $ipaddress = getenv('HTTP_CLIENT_IP');
    else if (getenv('HTTP_X_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
    else if (getenv('HTTP_X_FORWARDED'))
        $ipaddress = getenv('HTTP_X_FORWARDED');
    else if (getenv('HTTP_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_FORWARDED_FOR');
    else if (getenv('HTTP_FORWARDED'))
        $ipaddress = getenv('HTTP_FORWARDED');
    else if (getenv('REMOTE_ADDR'))
        $ipaddress = getenv('REMOTE_ADDR');
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

/**
 * @param int $length
 * @return string
 */
function get_random_string($length = 25)
{
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_!&%';
    $string = '';
    for ($i = 0; $i < $length; $i++) {
        $string .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $string;
}

/**
 * @param $to
 * @param $subject
 * @param $message
 * @param null $attachment
 * @param null $stringAttachment
 * @return bool
 */
function mail_html($to, $subject, $message, $attachment = NULL, $stringAttachment = NULL)
{
    $mail = new PHPMailer();
    $mail->addAddress($to);
    $mail->setFrom(MAIL, MAIL_NAMEk);
    $mail->msgHTML($message);
    $mail->Subject = $subject;

    if($attachment){
        if($stringAttachment){
            $mail->addStringAttachment($stringAttachment, $attachment);
        }else{
            $mail->addAttachment($attachment);
        }
    }

    $return = $mail->send();
    if(!$return){
        $msg = $mail->ErrorInfo;
        throw_error("Mail non inviata: $msg");
    }
    return $return;
}

/**
 * @param $message
 * @param int $duration
 * @param bool $plainText
 */
function throw_success($message, $duration = 5000, $plainText = true)
{
    if($plainText){
        $message = htmlspecialchars($message, ENT_QUOTES);
    }
    $_SESSION['msg'][] = ['message' => $message, 'duration' => $duration, 'style' => "green"];

	return;
}

/**
 * @param $message
 * @param int $duration
 * @param bool $plainText
 */
function throw_error($message, $duration = 5000, $plainText = true)
{
    if($plainText){
        $message = htmlspecialchars($message, ENT_QUOTES);
    }
    $_SESSION['msg'][] = ['message' => $message, 'duration' => $duration, 'style' => "red"];

	return;
}

/**
 * @param $message
 * @param int $duration
 * @param bool $plainText
 */
function throw_msg($message, $duration = 5000, $plainText = true)
{
	if($plainText){
		$message = htmlspecialchars($message, ENT_QUOTES);
	}
	$_SESSION['msg'][] = ['message' => $message, 'duration' => $duration, 'style' => ""];

	return;
}

/**
 * @return mixed
 */
function get_msg()
{
    $r = $_SESSION['msg'];
    $_SESSION['msg'] = [];


    return $r;
}

/**
 * @return bool
 */
function check_params(){
    for ($i = 0; $i < func_num_args(); $i++) {
        if(!func_get_arg($i))
        {
            throw_error("E_INVALID_ARGS: $i");
            return false;
        }
    }
    return true;
}

/**
 * @param $string
 * @return mixed
 */
function cleanString($string)
{
    $string = preg_replace("/[^.A-Za-z0-9]/", "", $string);
    return $string;
}

function checkPostAjaxCall($r, $data = NULL, $getMsgs = true)
{
    if($_POST['ajax']){
        $json['r'] = $r;
        $json['msgs'] = $getMsgs ? get_msg() : NULL;
        $json['data'] = $data;
        header('Content-Type: application/json');
        echo json_encode($json);
        exit(0);

        return true;
    }

    return false;
}

function translateParams($sql, &$params){
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


	$where = "WHERE ";
	if(is_array($params['where'])){
		foreach ($params['where'] as $key => $value){
		    $cmp = "=";
		    if(strpos($key, '%') === 0){
		        $cmp = "LIKE";
		        $key = substr($key, 1);
            }
			if(is_array($value)){
			    if($cmp == "="){
                    $where.= "$key IN(";
                    foreach ($value as $v){
                        $v = $sql->real_escape_string($v);
                        $where.="'$v',";
                    }
                    $where = substr($where, 0, -1);

                }
                else {
                    $where.= "(";
                    foreach ($value as $v){
                        $v = $sql->real_escape_string($v);
                        $where.="$key $cmp '$v' OR ";
                    }
                    $where = substr($where, 0, -3);
                }
                $where.= ')';
			}
			else {
                $value = $sql->real_escape_string($value);
                $where.= "$key $cmp '$value'";
            }
			$where.=" AND ";
		}
		$where = substr($where, 0, -5);
	}
	else{
		$where.= "1=1";
	}
	if($params['group']){
		$where.= " GROUP BY ";
		foreach ($params['group'] as $group){
			$where.= $group.',';
		}
		$where = substr($where, 0, -1);
	}
	$params['where'] = $where;
	$params['fields'] = $fields;
	return true;
}

define("EQUAL", 0);
define("NOT_EQUAL", 1);
define("LIKE", 2);
define("LESS_THAN", 3);
define("MORE_THAN", 4);
define("LESS_EQUAL_THAN", 5);
define("MORE_EQUAL_THAN", 6);

function translateParamsEx($sql, &$params){
	$MYSQL_OPERATORS = [
		["='*'", "IN(*)", "'*',"], ["!='*'", 'NOT IN(*)', "'*',"], ['LIKE %*%', '*', 'LIKE %*% OR '],
		["<'*'", "*", "* AND"], [">'*'", "*", "* AND"],["<='*'", "*", "* AND"], [">='*'", "*", "* AND"]
	];

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


	$where = "WHERE ";
	if(is_array($params['where'])){
		foreach ($params['where'] as $w){
			$where.= $w[0].' ';
			if(is_array($w[2])){
				$q = "";
				foreach($w[2] as $val){
					$val = $sql->real_escape_string($val);
					$q.= str_replace('*', $val, $MYSQL_OPERATORS[$w[1]][2]);
				}
				$q = substr($q, 0, strlen($q) - strpos('*', $MYSQL_OPERATORS[$w[1]][2]));
				$where.= str_replace('*', $q, $MYSQL_OPERATORS[$w[1]][1]);
			}
			else {
				$w[2] = $sql->real_escape_string($w[2]);
				$where.= str_replace('*', $w[2], $MYSQL_OPERATORS[$w[1]][0]);
			}
			$where.=" AND ";
		}
		$where = substr($where, 0, -5);
	}
	else{
		$where.= "1=1";
	}
	if($params['group']){
		$where.= " GROUP BY ";
		foreach ($params['group'] as $group){
			$where.= $group.',';
		}
		$where = substr($where, 0, -1);
	}
	$params['where'] = $where;
	$params['fields'] = $fields;
	return true;
}

class Resource
{
	private $resource;

	public function __construct($res)
	{
		$this->resource = $res;
	}

	public function set($res){
		$this->resource = $res;
	}

	public function next(){
		if(is_array($this->resource)){
			$r = current($this->resource);
			next($this->resource);
		}
		else {
			$r = $this->resource->fetch_assoc();
		}

		return $r;
	}

	public function size() {
		if(is_array($this->resource)){
			return count($this->resource);
		}
		else {
			return $this->resource->num_rows;
		}
	}

	public function reset() {
		if(is_array($this->resource)){
			reset($this->resource);
		}
		else {
			$this->resource->data_seek(0);
		}
	}
}