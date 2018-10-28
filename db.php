<?php

namespace ZDB ;

/**
 * Синглетон  инкапсулирующий   конект  к  БД
 */
class DB
{

    private static $db = null, $driver = null;
    private static $dbhost, $dbname, $dbuser, $dbpassword;
    private $conn = null;

    private function __construct()
    {

    }

    /**
     * Установка  параметров   коннекта
     *
     * @param mixed $dbhost
     * @param mixed $dbname
     * @param mixed $dbuser
     * @param mixed $dbpassword
     * @param string $driver
     */
    public static function config($dbhost, $dbname, $dbuser, $dbpassword, $driver = "mysqli")
    {
        global $ADODB_FETCH_MODE, $ADODB_QUOTE_FIELDNAMES;
        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
        $ADODB_QUOTE_FIELDNAMES = true;

        self::$dbhost = $dbhost;
        self::$dbname = $dbname;
        self::$dbuser = $dbuser;
        self::$dbpassword = $dbpassword;
        self::$driver = $driver;
    }

    /**
     * Открывает  конект  к  БД  и  возвращает  соотаветствующий  ресурс
     *
     */
    public static function getConnect()
    {
        $db = DB::getDB();
        $db->open();
     
        return $db->conn;
    }

    /**
     * Возвращает  инстанс
     *
     */
    public static function getDB()
    {
        if (self::$db == null) {
            self::$db = new DB();
        }
        return self::$db;
    }

    private function open()
    {
        if ($this->conn instanceof \ADOConnection) {
            return;
        }
        $this->conn = \ADONewConnection(self::$driver);
        $this->conn->Connect(self::$dbhost, self::$dbuser, self::$dbpassword, self::$dbname);
        if(DB::$driver == "mysqli"){
            $this->conn->Execute("SET NAMES 'utf8'");
        }

    }

    /**
     * Закрывает  конект  к  БД
     *
     */
    public static function Close()
    {
        $db = DB::getDB();
        if ($db->conn instanceof \ADOConnection) {
            $db->conn->Close();
        }
    }

}
