<?php

define("DISPONIBILE", 0);
define("PRENOTATO", 1);
define("ACQUISTATO", 2);
define("PAGATO", 3);
define("RITIRATO", 4);
define("INVALIDATO", 5);

/**
 * Class Bookrewind 2.0
 */
class BookRewind
{
    public static $t_books = "_2_0_bookrewind_books";
    public static $t_classbooks = "_2_0_bookrewind_classbooks";
    public static $t_categories = "_2_0_bookrewind_categories";
    public $STATUS_INFO = [[0, "Disponibile", "green-text", ["shopping_cart", "shopping_cart"]],
        [1, "Prenotato", "yellow-text text-darken-3", ["book", "delete", 0]],
        [2, "Acquistato/Venduto", "blue-text", ["shopping_basket", "shopping_basket", 0]],
        [3, "Pagato", "black-text", ["shopping_basket", "shopping_basket", 0]],
        [4, "Restituito", "red-text text-lighten-2", [0, 0, "replay"]],
        [5, "Da Restituire", "red-text", ["replay", 0, 0]]];

    public $db = null;


    /**
     * BookRewind constructor.
     * @param MySQL $sql
     */
    public function __construct($sql)
    {
        $this->db = $sql;
    }

    /**
     * @param MySQL $sql
     */
    public function reset($sql)
    {
        if (!$sql->checkTable(self::$t_categories)) {
            $sql->safequery("CREATE TABLE `".self::$t_categories."` ( `ID` int(11) NOT NULL AUTO_INCREMENT, `ISBN` varchar(13) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL, `title` varchar(128) NOT NULL, `subtitle` varchar(128) NOT NULL, `author` varchar(256) NOT NULL, `publisher` varchar(256) NOT NULL, `max` varchar(128) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL, `price` int(11) NOT NULL, PRIMARY KEY (`ID`), UNIQUE KEY `ISBN` (`ISBN`) ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        } else {
            $sql->safequery("TRUNCATE TABLE ".self::$t_categories);
        }
        if (!$sql->checkTable(self::$t_classbooks)) {
            $sql->safequery("CREATE TABLE `".self::$t_classbooks."` ( `class` varchar(3) CHARACTER SET utf8 NOT NULL, `typology` int(11) NOT NULL, `status` int(11) NOT NULL, PRIMARY KEY (`class`,`typology`) ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin");
        } else {
            $sql->safequery("TRUNCATE TABLE " . self::$t_classbooks);
        }
        if (!$sql->checkTable(self::$t_books)) {
            $sql->safequery("CREATE TABLE `".self::$t_books."` ( `ID` int(11) NOT NULL AUTO_INCREMENT, `ID_adozione` int(11) NOT NULL, `status` int(11) NOT NULL, `owner` int(11) NOT NULL, `buyer` int(11) NOT NULL, `info` int(11) NOT NULL, `modify_date` date NOT NULL, `date_in` date NOT NULL, `date_out` date NOT NULL, PRIMARY KEY (`ID`) ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin");
        } else {
            $sql->safequery("TRUNCATE TABLE ".self::$t_books);
        }
    }

    /**
     * @param $title
     * @param $subtitle
     * @param $ISBN
     * @param $price
     * @param $classes
     * @param $author
     * @param $publisher
     * @return bool
     */
    public function addCategory($title, $subtitle, $ISBN, $price, $classes, $author, $publisher)
    {
        $title = $this->db->real_escape_string($title);
        $subtitle = $this->db->real_escape_string($subtitle);
        $ISBN = $this->db->real_escape_string($ISBN);
        $price = $this->db->real_escape_string($price);
        $author = $this->db->real_escape_string($author);
        $publisher = $this->db->real_escape_string($publisher);

        if($this->db->safequery("INSERT INTO " . self::$t_categories . " VALUES (NULL, '$ISBN', '$title',
	        '$subtitle', '$author', '$publisher', '0', '$price') ON DUPLICATE KEY UPDATE price = '$price', title='$title', subtitle = '$subtitle',
	        author='$author', publisher='$publisher'")) {
            $ID = $this->db->safequery("SELECT ID FROM ".self::$t_categories." WHERE ISBN ='$ISBN' LIMIT 1")->fetch_row()[0];
            $this->db->safequery("DELETE FROM ".self::$t_classbooks." where typology = '$ID'");
            $query = "INSERT INTO " . self::$t_classbooks . " VALUES ";
            foreach ($classes as $class) {
                $class = $this->db->real_escape_string($class);
                $query .= "('$class', '$ID', '1'),";
            }
            $this->db->safequery(substr($query, 0, -1));

            return true;
        }
        else {
            throw_error($this->db->affected_rows." Si è verificato un errore: ".$ISBN);
        }

	    return false;
    }

    public function moveCategory($ISBN_from, $ISBN_to) {
    	$ISBN_from = $this->db->real_escape_string($ISBN_from);
    	$ISBN_to = $this->db->real_escape_string($ISBN_to);

    	$res = $this->db->safequery("SELECT ID FROM ".self::$t_categories." WHERE ISBN = '$ISBN_from'");
    	if(!$res || !$res->num_rows){
    		return false;
	    }
    	$ID_from = $res->fetch_assoc()['ID'];
    	$res = $this->db->safequery("SELECT ID FROM ".self::$t_categories." WHERE ISBN = '$ISBN_to'");
	    if(!$res || !$res->num_rows){
		    return false;
	    }
    	$ID_to = $res->fetch_assoc()['ID'];

    	$this->db->safequery("UPDATE FROM ".self::$t_books." SET ID_adozione = '$ID_to' WHERE ID_adozione = '$ID_from'");

    	return true;
    }

    /**
     * @param $ID
     * @return bool
     */
    public function deleteCategory($ID){
        $r = false;
        $ID = $this->db->real_escape_string($ID);
        $this->db->safequery("DELETE FROM ".self::$t_categories." WHERE ID = '$ID'");
        if ($this->db->affected_rows == 1){
            $this->db->safequery("DELETE FROM ".self::$t_classbooks." WHERE typology = '$ID'");

            $r = true;
        }
        elseif($this->db->affected_rows > 1){
            throw_error("Errore grave. Contatta l'amministratore. Davvero!!!");
        }

        return $r;
    }

    /**
     * @param $ID
     * @param $ISBN
     * @param $title
     * @param $subtitle
     * @param $classes
     * @param $author
     * @param $publisher
     * @param $price
     * @return bool
     */
    public function editCategory($ID, $ISBN, $title, $subtitle, $classes, $author, $publisher, $price){
        $ID = $this->db->real_escape_string($ID);
        $title = $this->db->real_escape_string($title);
        $subtitle = $this->db->real_escape_string($subtitle);
        $ISBN = $this->db->real_escape_string($ISBN);
        $price = $this->db->real_escape_string($price);
        $author = $this->db->real_escape_string($author);
        $publisher = $this->db->real_escape_string($publisher);

        $this->db->safequery("UPDATE ".self::$t_categories." SET `ISBN`='$ISBN',`title`='$title',`subtitle`='$subtitle',`author`='$author',`publisher`='$publisher',`price`='$price', max=max+1 WHERE ID='$ID'");
        if ($this->db->affected_rows == 1){
            $this->db->safequery("DELETE FROM ".self::$t_classbooks." WHERE typology = '$ID'");
            if(count($classes)){
                $query = "INSERT INTO " . self::$t_classbooks . " VALUES ";
                foreach ($classes as $class) {
                    $class = $this->db->real_escape_string($class);
                    $query .= "('$class', '$ID', '1'),";

                }
                $this->db->safequery(substr($query, 0, -1));
            }

            return true;
        }
        elseif($this->db->affected_rows > 1){
            throw_error("Errore grave. Contatta l'amministratore. Davvero!!!");
        }

	    return false;
    }

    /**
     * @param User $admin
     * @param $hFile
     * @return bool
     */
    public function loadCategories($admin, $hFile)
    {
        $categories = [];
        if ($admin->hasAccess(User::$permissions['SUPER_USER'])) {
            while (($data = fgetcsv($hFile, 0, ";")) !== FALSE) {
                $class = utf8_encode(strtoupper(substr($data[0], 0, 2)));
                $ISBN = utf8_encode(substr($data[1], 0, 13));
                if(!isset($categories[$ISBN])){
                    $title = utf8_encode(ucwords(strtolower($data[2])));
                    $subtitle = utf8_encode(ucwords(strtolower($data[3])));
                    $author = utf8_encode(ucwords(strtolower($data[4])));
                    $publisher = utf8_encode(ucwords(strtolower($data[5])));
                    $price = intval(floatval($data[6]) / 2);
                    $categories[$ISBN] = ["title" => $title, "subtitle" => "".$subtitle, "author" => "".$author, "publisher" => "".$publisher, "price" => $price, "classes" => [$class]];
                }
                else {
                    $categories[$ISBN]['classes'][] = $class;
                }
            }

            foreach ($categories as $isbn => $book) {
                if(strlen($isbn) == 13 and is_numeric($isbn)) {
                    $this->addCategory($book['title'], $book['subtitle'], $isbn, $book['price'], $book['classes'], $book['author'], $book['publisher']);
                }
            }

            return true;
        }

        print_r($categories);

        return false;
    }

    /**
     * @param User $admin
     * @return bool
     */
    public function clearCategories($admin)
    {
        if ($admin->hasAccess(User::$permissions['SUPER_USER'])) {
            $this->db->safequery("TRUNCATE TABLE ".self::$t_categories);
            $this->db->safequery("TRUNCATE TABLE ".self::$t_classbooks);

            return true;
        }
        return false;
    }

    /**
     * @param $owner
     * @param $ISBN
     * @param $duplicate
     * @param bool $msg
     * @return array
     */
    public function addBook($owner, $ISBN, $duplicate, $msg = true)
    {
        $category = $this->getCategory([
            'where' => ['ISBN' => $ISBN],
            'fields' => ['ID' => '', 'title' => '']
        ], $msg);
        if($category = $category->next()) {
            if (!$duplicate) {
                $res = $this->db->safequery("SELECT ID FROM ".self::$t_books." WHERE ID_adozione = '{$category['ID']}' AND owner = '$owner' LIMIT 1");
                if ($res->fetch_assoc()) {
                    throw_error("Libro già posseduto dall'utente");

                    return [NULL, NULL];
                }
            }
            $this->db->safequery("INSERT INTO ".self::$t_books." VALUES(NULL, '{$category['ID']}', 0, '$owner', 0, 0, CURDATE(), CURDATE(), 0)");
            $title = ucwords($category['title']);

            return [$this->db->insert_id, $title];
        }

        return [NULL, NULL];
    }

    /**
     * @param User $admin
     * @param $ID_libro
     * @param $ID_acquirente
     * @return bool
     */
    public function buyBook($admin, $ID_libro, $ID_acquirente)
    {
        if ($admin->hasAccess(User::$permissions["ADMIN"])) {
            $ID_libro = $this->db->real_escape_string($ID_libro);
            $ID_acquirente = $this->db->real_escape_string($ID_acquirente);
            $this->db->safequery("UPDATE ".self::$t_books." booked 
                INNER JOIN ".self::$t_books." target ON target.ID = '$ID_libro' AND target.status < '".ACQUISTATO."'
                SET booked.buyer = target.buyer, booked.status = target.status, booked.modify_date = target.modify_date, target.buyer = '$ID_acquirente', target.status = '".ACQUISTATO."',
                target.modify_date = CURDATE(), target.date_out = CURDATE()
                WHERE target.ID AND booked.ID IN 
                    (SELECT ID FROM 
                        (SELECT ID FROM ".self::$t_books." WHERE buyer = '$ID_acquirente' AND status = ".PRENOTATO." AND ID_adozione = 
                            (SELECT ID_adozione FROM 
                                (SELECT ID_adozione FROM ".self::$t_books." WHERE ID = '$ID_libro') 
                             tmp1) 
                         LIMIT 1) tmp2) 
                ");
            if (!$this->db->affected_rows) {
                throw_error("Il libro $ID_libro non risulta prenotato dall'utente o non è più disponibile, riprova.");
                return false;
            } else {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $params
     * @param bool $msg
     * @return mixed
     */
    public function getCategory($params, $msg = false)
    {
        $tCategories = self::$t_categories;

        translateParams($this->db, $params);
        $select = "SELECT {$params['fields']} FROM $tCategories as category ";

        $res = $this->db->safequery("$select{$params['where']}");
        if (!$res->num_rows && $msg) {
            throw_error("Libro non in adozione");
        }

        return new Resource($res);
    }

	/**
	 * Selection: book, owner, buyer
	 * @param $t_users
	 * @param $params
	 * @param bool $msg
	 * @return Resource
	 */
	public function getBooks($t_users, $params, $msg = false){
		$tBooks = self::$t_books;
		$tCategories = self::$t_categories;
		$tUsers = $t_users;

		translateParams($this->db, $params);
		$select = "SELECT {$params['fields']} FROM $tBooks as book ";
		if($params['join'] & 1){
			$select.= "LEFT JOIN $tUsers as owner ON owner.ID = book.owner ";
		}
		if($params['join'] & 2){
			$select.= "LEFT JOIN $tUsers as buyer ON buyer.ID = book.buyer ";
		}
		if($params['join'] & 4){
			$select.= "LEFT JOIN $tCategories as category ON category.ID = book.ID_adozione ";
		}
		$res = $this->db->safequery("$select{$params['where']}");

		if (!$res->num_rows && $msg) {
			throw_error("Nessuna corrispondenza");
		}

		return new Resource($res);
	}

	public function getBooksEx($t_users, $params, $msg = false){
		$tBooks = self::$t_books;
		$tCategories = self::$t_categories;
		$tUsers = $t_users;

		translateParamsEx($this->db, $params);
		$select = "SELECT {$params['fields']} FROM $tBooks as book ";
		if($params['join'] & 1){
			$select.= "LEFT JOIN $tUsers as owner ON owner.ID = book.owner ";
		}
		if($params['join'] & 2){
			$select.= "LEFT JOIN $tUsers as buyer ON buyer.ID = book.buyer ";
		}
		if($params['join'] & 4){
			$select.= "LEFT JOIN $tCategories as category ON category.ID = book.ID_adozione ";
		}
		$res = $this->db->safequery("$select{$params['where']}");

		if (!$res->num_rows && $msg) {
			throw_error("Nessuna corrispondenza");
		}

		return new Resource($res);
	}

    /**
     * Selection: classbook, category
     * @param $params
     * @param bool $msg
     * @return Resource
     */
    public function getClassbooks($params, $msg = false){
        $tCategories = self::$t_categories;
        $tClassbooks = self::$t_classbooks;

        translateParams($this->db, $params);
        $select = "SELECT {$params['fields']} FROM $tClassbooks as classbook ";
        if($params['join'] & 1){
            $select.= "INNER JOIN $tCategories as category ON classbook.typology = category.ID ";
        }
        $res = $this->db->safequery("$select{$params['where']}");

        if (!$res->num_rows && $msg) {
            throw_error("Nessuna corrispondenza");
        }

        return new Resource($res);
    }

    /**
     * @param $typology
     * @param $class
     * @return mixed
     */
    public function getClassbook($typology, $class)
    {
        $typology = $this->db->real_escape_string($typology);
        $class = $this->db->real_escape_string($class);
        $res = $this->db->safequery("SELECT EXISTS(SELECT * FROM `".self::$t_classbooks."` WHERE class='$class' AND typology='$typology'");
        $adozione = $res->fetch_array();

        return $adozione[0];
    }

    /**
     * @param $userID
     * @param $userClass
     * @param $categoryID
     * @param $allow
     * @return bool
     */
    public function reserveBook($userID, $userClass, $categoryID, $allow)
    {
        $return = false;
        $categoryID = $this->db->real_escape_string($categoryID);
        $userID = $this->db->real_escape_string($userID);
        $userClass = $this->db->real_escape_string($userClass);
        if ($this->db->lock($userID)) {
           //Se non è Admin allora controllo che l'adozione sia assegnata alla classe del acquirente e che non si sia già prenotato un libro uguale.
            if (!$allow) {
                $res = $this->db->safequery("SELECT (EXISTS(SELECT typology FROM `".self::$t_classbooks."` WHERE class='$userClass' AND typology = '$categoryID')
				AND NOT EXISTS(SELECT ID FROM ".self::$t_books." WHERE buyer ='$userID' AND ID_adozione ='$categoryID'))");
                $allow = $res->fetch_row()[0];
            }
            if ($allow) {
                //Fallisce se non vi è un libro disponibile (status = 0)
                $this->db->safequery("UPDATE ".self::$t_books." SET status = '1', buyer = '$userID', modify_date = CURDATE() WHERE ID_adozione = '$categoryID' AND status = 0 ORDER BY ID LIMIT 1");
                if ($this->db->affected_rows) {
                    $return = true;
                } else {
                    throw_error("$categoryID. Libro non disponibile");
                }
            } else {
                throw_error("Adozione non valida o libro già prenotato/acquistato");
            }

            $this->db->unlock($userID);
        }

        return $return;
    }

    /**
     * @param $userID
     * @param $categoryID
     * @return bool
     */
    public function freeBook($userID, $categoryID)
    {
        $return = false;
        $categoryID = $this->db->real_escape_string($categoryID);
        $userID = $this->db->real_escape_string($userID);
        if ($this->db->lock($userID)) {
            $this->db->safequery("UPDATE " . self::$t_books . " SET status = '" . DISPONIBILE . "', buyer = '0', modify_date = CURDATE() WHERE ID_adozione = '$categoryID' AND status = 1 AND buyer ='$userID' ORDER BY ID LIMIT 1");
            if (!$this->db->affected_rows) {
                throw_error("Prenotazione non annullata");
            } else {
                $return = true;
            }
        }

        return $return;
    }

    public function freeAllByBuyer($owner)
    {
        $ID = $this->db->real_escape_string($owner);
        $this->db->safequery("UPDATE ".self::$t_books." SET status = '".DISPONIBILE."', buyer = '0', modify_date = CURDATE() WHERE status = 1 AND buyer ='$ID'");
        if ($this->db->affected_rows) {
	        return true;
        }
        return false;
    }

    public function freeAllByDate($day)
    {
        $day = intval($day);
        $this->db->safequery("UPDATE ".self::$t_books." SET status = '".DISPONIBILE."', buyer = '0', modify_date = CURDATE() WHERE status = 1 AND modify_date < CURDATE() - $day");
        if (!$this->db->affected_rows) {
            throw_error("Nessuna prenotazione annullata");
        }
        else {
            return true;
        }

        return false;
    }

    public function invalidateBook($book){
        $book = $this->db->real_escape_string($book);
        $this->db->safequery("UPDATE ".self::$t_books." SET status = '".INVALIDATO."', buyer = 0, modify_date = CURDATE() WHERE status IN(".DISPONIBILE.",".INVALIDATO.") AND ID='$book' ORDER BY ID LIMIT 1");
        if ($this->db->affected_rows) {
            return true;
        }
        else {
            $book = $this->getBooksEx("", [
                'where' => [['ID', EQUAL, $book], ['status', EQUAL, PRENOTATO]]
            ])->next();
            if($book){
                $this->db->safequery("UPDATE ".self::$t_books." SET status = '".PRENOTATO."', buyer = '{$book['buyer']}', modify_date = CURDATE() 
                        WHERE status = ".DISPONIBILE." AND ID_adozione='{$book['ID_adozione']}' ORDER BY ID LIMIT 1");
                $this->db->safequery("UPDATE ".self::$t_books." SET status = '".INVALIDATO."', buyer = owner, modify_date = CURDATE()  WHERE ID='{$book['ID']}'");
                if ($this->db->affected_rows) {
                    return true;
                }
                else {
                    throw_error("Fatl Error 116");
                }
            }
            else {
                throw_error("Il libro non risulta invalidabile. Contattami");
            }
        }

        return false;
    }

    public function validateBook($book){
        $book = $this->db->real_escape_string($book);
        $this->db->safequery("UPDATE ".self::$t_books." SET status = '".DISPONIBILE."', buyer = 0, modify_date = CURDATE() WHERE status =".INVALIDATO." AND ID='$book' ORDER BY ID LIMIT 1");
        if ($this->db->affected_rows) {
            return true;
        }
        else {
            throw_error("Il libro non risulta invalidato");
        }

        return false;
    }

    public function returnBook($book){
        $book = $this->db->real_escape_string($book);
        $this->db->safequery("UPDATE " . self::$t_books . " SET status = '" . RITIRATO . "', buyer = 0, modify_date = CURDATE(), date_out = CURDATE() WHERE status = ".INVALIDATO." AND ID='$book' ORDER BY ID LIMIT 1");
        if ($this->db->affected_rows) {
            return true;
        } else {
            throw_error("Il libro non risulta invalidato");
        }

        return false;
    }

    public function returnBooksByOwner($owner, $list){
				$owner = $this->db->real_escape_string($owner);
				$in = join(",", array_map(function($el) { return intval($el); }, $list));
        $this->db->safequery("UPDATE " . self::$t_books . " SET status = '" . RITIRATO . "', buyer = 0, modify_date = CURDATE(), date_out = CURDATE() WHERE (status = '0' OR status = '1') AND owner='$owner' AND ID IN(".$in.") ORDER BY ID");
        if ($this->db->affected_rows == count($list)) {
            return true;
        } else {
            throw_error("Errore. Non tutti i libri selezionati sono ritirabili");
        }

        return false;
    }

    public function deleteBook($book){
        if($this->invalidateBook($book)){
            $book = $this->db->real_escape_string($book);
            $this->db->safequery("DELETE FROM ".self::$t_books." WHERE status = ".INVALIDATO." AND ID='$book' ORDER BY ID LIMIT 1");
            if ($this->db->affected_rows) {
                return true;
            } else {
                throw_error("Fatal Error 151");
            }
        }

        return false;
    }

    public function getProceeds($user){
        $ricavo = [ACQUISTATO => 0, PAGATO => 0];
        $user = $this->db->real_escape_string($user);
        $res = $this->db->safequery("SELECT SUM(category.price), book.status FROM ".self::$t_books." AS book INNER JOIN ".self::$t_categories." AS category ON category.ID = book.ID_adozione WHERE book.owner = '$user' AND book.status IN(".ACQUISTATO.",".PAGATO.") GROUP BY book.status ORDER BY book.status");
        $r = $res->fetch_row();
        if($r)$ricavo[$r[1]] = $r[0];
        $r = $res->fetch_row();
        if($r)$ricavo[$r[1]] = 0 + $r[0];

        return $ricavo;
    }

    public function payProceeds($user){
        $books = $this->getBooksEx("", [
            'where' => [['book.status', EQUAL, ACQUISTATO], ['book.owner', EQUAL, $user]],
            'fields' => ['category.*' => '', 'book.ID' => 'bookID'],
            'join' => 4
        ]);
        $user = $this->db->real_escape_string($user);
        if($books->size()){
            $r = [];
            while($book = $books->next()){
                $r[] = $book;
            }
            $this->db->safequery("UPDATE ".self::$t_books." AS book SET status = '".PAGATO."', modify_date = CURDATE() WHERE owner= '$user' AND status = '".ACQUISTATO."'");
            if($this->db->affected_rows == $books->size()){
                return $r;
            }
            else {
                throw_error("Fatal Error 514");
            }
        }
        else {
            throw_error("Nessun libro da pagare");
        }

        return [];
    }
}
