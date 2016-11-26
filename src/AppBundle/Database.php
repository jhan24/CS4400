<?php

namespace AppBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class Database {
    private static $instance;
    private $db;

    private function __construct() {
        $this->db = mysqli_connect('localhost','homestead','secret','cs4400');
    }

    public static function getInstance() {
        if(self::$instance === null) {
            self::$instance = new Database();
        }
        if(self::$instance->db->connect_errno > 0){
            return null;
        }
        return self::$instance->db;
    }
}
