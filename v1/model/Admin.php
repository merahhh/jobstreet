<?php


class Admin
{
    public function getConn()
    {
        return $conn = new PDO('mysql:host=localhost;dbname=jobstreet;charset=utf8mb4', 'root', 'root');
    }

    public function getInfoAssoc($email){
        $stmt = $this->getAdminInfoByEmail($email);
        $result_assoc = $stmt->fetch(PDO::FETCH_ASSOC);     #returns array

        return $result_assoc;
    }

    public function getAdminInfoByEmail($email){
        $conn = $this->getConn();
        $sql_get_info = $conn->prepare("SELECT * FROM admin WHERE a_email= ?");
        $sql_get_info->execute(array($email));      #return PDOStatement

        return $sql_get_info;
    }

    public function getAdminInfoByID($userID){
        $conn = $this->getConn();
        $sql_get_info = $conn->prepare("SELECT * FROM admin WHERE admin_id = ?");
        $sql_get_info->execute(array($userID));                     #return PDOStatement
        $get_employee_info =  $sql_get_info->fetch(PDO::FETCH_ASSOC);     #returns array

        return $get_employee_info;
    }

    public function getAdminFirstName($userID){
        $admin = $this->getAdminInfoByID($userID);
        $first_name = $admin['a_first_name'];

        return $first_name;
    }

    public function getAdminLastName($userID){
        $admin = $this->getAdminInfoByID($userID);
        $last_name = $admin['a_last_name'];

        return $last_name;
    }

    public function getAdminFullName($userID){
        $admin = $this->getAdminInfoByID($userID);
        $full_name = $admin['a_first_name'] . ' ' . $admin['a_last_name'];

        return $full_name;
    }
}