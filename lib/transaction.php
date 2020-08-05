<?php

require_once "lib.php";
require_once "pushbullet.php";

define("TR_REGISTRATION", 1);
define("TR_PURCHASE", 2);
define("TR_USER_PAYMENT", 3);

class Transaction
{
    public $TYPE = [0, TR_REGISTRATION, TR_PURCHASE, TR_USER_PAYMENT];
    public $INFO = ["", "Registrazione", "Vendita Libri", "Consegna Ricavo"];
    public $SIGN = [0, 1, 1, -1];
    public static $t_data = "_2_0_bookrewind_transactions";

    private $db = null;

    /**
     * User constructor.
     * @param MySQL $sql
     */
    public function __construct($sql)
    {
        $this->db = $sql;

        return;
    }

    /**
     * Esegue il setup/reset del database
     */
    public function reset()
    {
        if (!$this->db->checkTable(self::$t_data)) {
            $this->db->safequery('CREATE TABLE `'.self::$t_data.'` ( `ID` int(11) NOT NULL AUTO_INCREMENT, `type` int(11) NOT NULL, `price` int(11) NOT NULL, `info` varchar(255) NOT NULL, `date` date NOT NULL, `isCustom` int(11) NOT NULL, PRIMARY KEY (`ID`) ) ENGINE=MyISAM DEFAULT CHARSET=utf8');
        } else {
            $this->db->safequery('TRUNCATE TABLE '.self::$t_data);
        }
    }

    /**
     * @param int $type
     * @param $price
     * @param string $info
     * @param int $custom
     * @return bool
     */
    public function add($type, $price, $info, $custom = 0)
    {
        $type = intval($type);
        $price = $this->SIGN[$type] ? $this->SIGN[$type]*abs(intval($price)) : intval($price);
        $info = $this->db->real_escape_string($info);

        $this->db->safequery("INSERT INTO ".self::$t_data." VALUES(NULL, '$type', '$price', '$info', CURDATE(), '$custom')");
        if ($this->db->affected_rows != 1) {
            throw_error("Impossibile registrare la transazione");

            return false;
        }

        return $this->db->insert_id;
    }

    /**
     * @param int $ID
     * @return bool
     */
    public function remove($ID)
    {
        $ID = intval($ID);
        $this->db->safequery("DELETE FROM ".self::$t_data." WHERE ID='$ID'");
        if (!$this->db->affected_rows) {
            throw_error("Transazione non trovata: $ID");

            return false;
        }

        return true;
    }

    /**
     * @param $types
     * @param string $from
     * @param string $to
     * @param int $onlyCustom
     * @return string
     */
    public function createCSV($types, $from, $to, $onlyCustom = 0)
    {
        $where = "(";
        foreach ($types as $t){
            $t = intval($t);
            $where.= "type = '$t' OR ";
        }
        $where = substr($where, 0, -3);
        if($onlyCustom){
            $where.= ") AND isCustom = '1'";
        }
        else {
            $where.= ")";
        }
        $from = $this->db->real_escape_string($from);
        $to = $this->db->real_escape_string($to);
        $query = "SELECT * FROM ".self::$t_data." WHERE $where AND date >= '$from' AND date <= '$to' ORDER BY type, ID";
        $res = $this->db->safequery($query);

        $total = 0;
        $output = '"ID";"TRANSAZIONE";"EURO";"DESCRIZIONE";"DATA";"CUSTOM"'."\r\n";
        while($t = $res->fetch_assoc()){
            $t['date'] = str_replace('-', '\\', $t['date']);
            $output.= "\"{$t['ID']}\"".";"."\"{$this->INFO[$t['type']]}\"".";"."\"{$t['price']}\"".";"."\"{$t['info']}\"".";"."\"{$t['date']}\"".";".($t['isCustom'] ? "\"Si\"" : "\"No\"")."\r\n";
            $total += $t['price'];
        }
        $output .= "\r\n".'"";"Totale:";"'.$total.'";"";"";""';

        return $output;
    }

    /**
     * @param int $type
     * @param string $from
     * @param string $to
     * @param int $onlyCustom
     */
    public function createPDF($type, $from, $to, $onlyCustom = 0)
    {

    }
}