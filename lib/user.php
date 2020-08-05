<?php

require_once "lib.php";
require_once "pushbullet.php";

class User
{
    public static $t_users;
    public static $t_classes;
    public static $t_users_data;
	public static $permissions = ["SUPER_USER" => "su", "ADMIN" => "a", "ADMIN_LIBRI" => "al"];

    public $data = null;
    private $db = null;

	/**
	 * User constructor.
	 * @param MySQL $sql
	 */
	public function __construct($sql)
    {
    	$this->db = $sql;
	    $this->data = $_SESSION['user'];
	    if($this->hasAccess(self::$permissions['SUPER_USER'], false)){
		    return;
	    }
	    if($this->data){
		    $res = $this->db->safequery('SELECT users.*, data.* FROM '.self::$t_users." as users INNER JOIN ".TABLE_DATA." as data ON data.key = 'login' WHERE ID = '{$this->data['ID']}'");
		    if($res){
		        $this->data = $res->fetch_assoc();
			    if($this->data){
			        if(!$this->data['key']){
                        $this->data = NULL;
                        throw_error('Server in manutenzione');
                    }
                    $_SESSION['user'] = $this->data;
			    } else {
                    $this->data = null;
				    $_SESSION['user'] = null;
			    }
		    } else {
			    throw_error('Setup non eseguito');
		    }
	    }

	    return;
    }

    public function accessPage($token, $redirect = true)
    {
        $access = true;
        if(!$this->data){
            throw_error("Sessione scaduta. Rieffettua il login.");
            $access = false;
        } else if($token){
            if(is_array($token)){
                foreach ($token as $i){
                    if(!$this->hasAccess($i)){
                        $access = false;
                        break;
                    }
                }
            }
            else {
                if(!$this->hasAccess($token)){
                    $access = false;
                }
            }
        }
        if(!$access){
            header("Location: /v2_0/index.php?logout");
            exit(0);
        }

        return;
    }

    //ADMIN FUNCTIONS
	//--------------------------------------------------------------------------------------

	/**
	 * Esegue il setup/reset del database
	 */
	public function reset()
	{
		if (!$this->db->checkTable(self::$t_users)) {
            $this->db->safequery('CREATE TABLE `'.self::$t_users.'` ( `ID` int(11) NOT NULL, `mail` varchar(128) NOT NULL, `parentmail` varchar(128) NOT NULL, `name` varchar(32) NOT NULL, `surname` varchar(32) NOT NULL, `psw` varchar(256) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL, `class` varchar(3) NOT NULL, `flag` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL, PRIMARY KEY (`ID`), UNIQUE KEY `mail` (`mail`) ) ENGINE=MyISAM DEFAULT CHARSET=utf8');
		} else {
            $this->db->safequery('TRUNCATE TABLE '.self::$t_users);
		}
        if (!$this->db->checkTable(self::$t_classes)) {
            $this->db->safequery('CREATE TABLE `'.self::$t_classes.'` ( `class` varchar(2) NOT NULL, PRIMARY KEY (`class`), UNIQUE KEY `class` (`class`) ) ENGINE=MyISAM DEFAULT CHARSET=utf8');
        } else {
            $this->db->safequery('TRUNCATE TABLE '.self::$t_classes);
        }
	}

	/**
	 * @param $name
	 * @param $surname
	 * @param $mail
	 * @param $class
	 * @return bool|int
	 */
	public function register($name, $surname, $mail, $parentmail, $class)
	{
		if ($this->hasAccess(self::$permissions['ADMIN'])) {
			$surname = $this->db->real_escape_string($surname);
			$name = $this->db->real_escape_string($name);
			$class = $this->db->real_escape_string($class);
			$mail = $this->db->real_escape_string($mail);
			$flag = $this->generatePermissionFlag([]);

			$psw = get_random_string(8);
			$hashed_psw = password_hash($psw, PASSWORD_DEFAULT);

			do {
				$ID = rand(10, 1000000);
				$user = $this->db->safequery('SELECT ID FROM '.self::$t_users.' WHERE ID = '.$ID);
			} while ($user->fetch_assoc());

			$this->db->safequery('INSERT INTO '.self::$t_users." (ID, mail, parentmail, name, surname, psw, class, flag)
				VALUES('$ID', '$mail', '$parentmail', '$name', '$surname','$hashed_psw', '$class', '$flag') ON DUPLICATE KEY UPDATE ID=ID");
			if ($this->db->affected_rows == 1) {
				//$pb = new PushBullet();
				//$pb->pushMsg("#MONEY +1", "New user: $mail");

				$body = "Benvenuto nel portale BookRewind!<br><a href='http://www.bookrewind.altervista.org/'>http://www.bookrewind.altervista.org/</a><br>Nome utente: $mail<br>Password: $psw";

				mail_html($mail, "Calibri - Registrazione", $body);

				return $ID;
			} elseif($this->db->error == 2) {
                throw_error("Dati utente non validi");
			}
			else {
                throw_error("Utente $mail già registrato");
            }
		}

		return false;
	}

	/**
	 * @param string $query
	 * @param bool $msg
	 * @return array|bool
	 */
	public function find($query, $msg = false)
	{
		if ($this->hasAccess(self::$permissions['ADMIN'])) {
			$query = $this->db->real_escape_string($query);

			if (is_numeric($query)) {
				$where = "ID = '$query'";
			} else {
				$where = "mail = '$query'";
			}

			$res = $this->db->safequery('SELECT ID,mail,parentmail,name,surname,class,flag FROM '.self::$t_users." WHERE $where");
			$user = $res->fetch_assoc();
			if ($user) {
			    $user['flag'] = $this->getPermissionFromFlag($user['flag']);
				return $user;
			} else if ($msg) {
				throw_error("Studente non trovato: $query");
			}
		}

		return false;
	}

    public function getUsers($params, $msg = false)
    {
        if ($this->hasAccess(self::$permissions['ADMIN'])) {
            $tUsers = self::$t_users;

            translateParams($this->db, $params);
            $select = "SELECT {$params['fields']} FROM $tUsers as user ";
            $res = $this->db->safequery("$select{$params['where']}");

            if (!$res->num_rows && $msg) {
                throw_error("Nessuna corrispondenza");
            }

            return new Resource($res);
        }

        return false;
    }

	public function search($query){
        $r = [];
        if ($this->hasAccess(self::$permissions['ADMIN'])) {
            $keywords = explode(' ', $query);
            $query = 'SELECT ID,mail,name,surname,class,flag FROM '.self::$t_users.' WHERE ';
            foreach ($keywords as $key){
                if($key){
                    $key = $this->db->real_escape_string($key);
                    $query.= "name LIKE '%$key%' OR surname LIKE '%$key%' OR mail LIKE '%$key%' OR ";
                }
            }
            $query = substr($query, 0, -3);

            $res = $this->db->safequery($query);

            while($u = $res->fetch_assoc()){
                $r[] = $u;
            }
        }

        return $r;
    }

	/**
	 * @param $target
	 * @param null $text
	 * @return bool
	 */
	public function resetPsw($target, $text = NULL)
	{
		$text = $text ? $text : "Calibri - Reset Password";

		if ($user = $this->find($target, true)) {
			$psw = get_random_string(8);
			$hash_psw = password_hash($psw, PASSWORD_DEFAULT);

			$this->db->safequery("UPDATE " . self::$t_users . " SET psw = '$hash_psw' WHERE ID='{$user['ID']}'");
			mail_html($user['mail'], "Calibri - Credenziali", "$text Username: {$user['mail']} Password: $psw");

			return true;
		}
		return false;
	}

	/**
	 * @param $target
	 * @param $data
	 * @return bool|mixed
	 */
	public function edit($target, $data)
	{
		if ($target = $this->find($target, true)) {
			$update = 'SET ';
			foreach ($data as $key => $value) {
				if (isset($value)) {
					$value = $this->db->real_escape_string($value);
					if ($this->hasAccess(self::$t_users_data[$key][1], false)) {
                        $update .= '`'.self::$t_users_data[$key][0]."` = '$value',";
					}
				}
			}
			$update = substr($update, 0, -1);
			if (strlen($update) > 5) {
				$this->db->safequery('UPDATE '.self::$t_users." $update WHERE ID='{$target['ID']}'");
				if (strpos($update, self::$permissions['SUPER_USER'])) {
					//$pb = new PushBullet();
					//$pb->pushMsg("Warning", "Modified user: {$target['mail']} - $update");
				}
			}

			return $target['ID'];
		}

		return false;
	}

	/**
	 * @param $target
	 * @return bool
	 */
	public function delete($target)
	{
		/*if ($target = $this->find($target)) {
			$this->db->safequery('DELETE FROM '.self::$t_users." WHERE ID='{$target['ID']}'");
			if (!$this->db->affected_rows) {
				throw_error("Utente non trovato: {$target['mail']}");
			}

			return true;
		}*/

		return false;
	}

	/**
	 * @param $target
	 * @return bool
	 */
	public function unregister($target)
	{
		if ($target = $this->find($target)) {
			$this->db->safequery('UPDATE '.self::$t_users." SET psw ='', class='00' WHERE ID='{$target['ID']}'");
			if (!$this->db->affected_rows) {
				throw_error("Utente non trovato: {$target['mail']}");
			}

			return true;
		}

		return false;
	}

	public function getClasses()
    {
        $res = $this->db->safequery("SELECT * FROM ".self::$t_classes);
        $return = [];

        while($r = $res->fetch_assoc()){
            $return[] = $r['class'];
        }

        return $return;
    }

    public function addClasses($classes)
    {
        $values = "";
        foreach ($classes as $class){
            $class = $this->db->real_escape_string($class);
            $values.= "('$class'),";
        }
        $values = substr($values, 0, -1);
        $this->db->safequery("INSERT INTO ".self::$t_classes." (class) VALUES $values ON DUPLICATE KEY UPDATE class=class");
        if($this->db->affected_rows){
            return true;
        }

        return false;
    }

	//END ADMIN FUNCTIONS
	//--------------------------------------------------------------------------------------------

    /**
     * @param $username
     * @param $password
     * @return bool
     */
    public function login($username, $password)
    {
        usleep(10000);

        $_SESSION['bookrewind']['user'] = null;
        if ($username == SU_USER && $password == SU_PSW) {
            $this->data = ["ID" => -1, "flag" => $this->generatePermissionFlag(["SUPER_USER"]), "name" => "Grande", "surname" => "Capo", "mail" => "pincoluca1@gmail.com", "class" => "00"];
            $_SESSION['user'] = $this->data;

            return true;
        }
        $username = $this->db->real_escape_string($username);
        $res = $this->db->safequery('SELECT users.*, data.value FROM '.self::$t_users." as users INNER JOIN ".TABLE_DATA." as data ON data.key = 'login' WHERE mail='$username'");
        if ($res && $res->num_rows < 2) {
	        $this->data = $res->fetch_assoc();
            if(!$this->data['value'] && $this->data['mail']){
                $this->data = NULL;
                throw_error('Server in manutenzione');

                return false;
            }
            if (password_verify($password, $this->data['psw'])) {
	                $_SESSION['user'] = $this->data;

                return true;
            }
        } else {
            //$pb = new PushBullet();
            //$pb->pushMsg("#DOUBLE_USER", "Duplicate user detected: $username");
        }

        throw_error("Mail o password non validi");

        return false;
    }

    /**
     *
     */
    public function logout()
    {
        $_SESSION = null;
        $this->data = null;

        return;
    }

    /**
     * @return bool
     */
    public function is()
    {
        return !!$this->data;
    }

    /**
     * @param $index
     * @return mixed
     */
    public function get($index)
    {
        return $this->data[$index];
    }

    /**
     * @param $index
     * @param $val
     */
    public function set($index, $val)
    {
        $this->data[$index] = $val;

        return;
    }

    /**
     * @param $permission
     * @param bool $msg
     * @return bool
     */
    public function hasAccess($permission, $msg = true)
    {
        if(!$permission){
	        return false;
        }

        $try = '-' . $permission . '-';
        if (strpos($this->data['flag'], $try) !== false ||
                strpos($this->data['flag'], "-" . self::$permissions['SUPER_USER'] . "-") !== false) {
            return true;
        }
        if ($msg && $this->data['flag']) {
            throw_error("You lack the permission required: " . strtoupper($permission));
        }

        return false;
    }

    /**
 * @param $array
 * @return string
 */
    public function generatePermissionFlag($array)
    {
        $flag = '-';
        foreach ($array as $p) {
            if(self::$permissions[$p]){
                $flag .= self::$permissions[$p] . "-";
            }
        }

        return $flag;
    }

    /**
     * @param string
     * @return array
     */
    public function getPermissionFromFlag($flag)
    {
        $r = [];
        $flags = explode('-', $flag);
        foreach ($flags as $flag) {
            $key = array_search($flag, self::$permissions);
            if($key){
                $r[] = $key;
            }
        }

        return $r;
    }

    /**
     * @param $old
     * @param $new
     * @return bool
     */
    public function changePsw($old, $new)
    {
        if($this->data){
            if (password_verify($old, $this->data['psw'])) {
                $hash_psw = password_hash($new, PASSWORD_DEFAULT);
                $this->db->safequery("UPDATE ".self::$t_users." SET psw='$hash_psw' WHERE ID='{$this->data['ID']}'");
                if ($this->db->affected_rows) {
                    return true;
                }
            } else {
                throw_error("Password errata. Riprova");
                return false;
            }
        }
        throw_error("Impossibile modificare la password. Riprova");

        return false;
    }

    public function toString()
    {
    	return ucfirst($this->get('name'))." ".ucfirst($this->get('surname'))." - ".$this->get('class');
    }

    public function mail()
    {
    	return $this->get('mail');
    }
};

User::$t_users = "_2_0_bookrewind_users";
User::$t_classes = "_2_0_bookrewind_classes";
User::$t_users_data = [["ID", 0], ["typology", User::$permissions['SUPER_USER']],
    ["flag", User::$permissions['SUPER_USER']], ["mail", User::$permissions['ADMIN']], ["parentmail", User::$permissions['ADMIN']],
    ["name", User::$permissions['ADMIN']],  ["surname", User::$permissions['ADMIN']],
    ["psw", 0], ["class", User::$permissions['ADMIN']]];