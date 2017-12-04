<?php
/**
 * Created by PhpStorm.
 * User: Стас
 * Date: 20.07.14
 * Time: 15:49
 */
namespace project5\Rdbms;



class Connection
{
    private $_dsn;
    private $_driverOptions;

    private $_username;
    private $_password;

    public function __construct($dsn, $driverOptions = [])
    {
        $this->_dsn = $dsn;
        $this->_driverOptions = $driverOptions;
    }


    public function createQueryBuilder()
    {
        $config = new \Doctrine\DBAL\Configuration();

        $connectionParams = array(
            'pdo' => Connection::GetPdo($this)
        );
        $connection = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
        return $connection->createQueryBuilder();
    }

    public function getDsn()
    {
        return $this->_dsn;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->_password;
    }

    /**
     * @return mixed
     */
    public function getUsername()
    {
        return $this->_username;
    }



    public function setCredentials($username, $password)
    {
        $this->_username = $username;
        $this->_password = $password;
    }

    /**
     * @param Connection $connection
     * @return \PDO
     */
    public static function GetPdo(Connection $connection)
    {
        $pdo = new \PDO($connection->_dsn, $connection->_username, $connection->_password, $connection->_driverOptions);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }

}