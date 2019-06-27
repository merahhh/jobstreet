<?php

class Employee
{
    public function getConn()
    {
        return $conn = new PDO('mysql:host=localhost;dbname=jobstreet;charset=utf8mb4', 'root', 'root');
    }

    public function getInfoAssoc($email){
        $stmt = $this->getEmployeeInfoByEmail($email);
        $result_assoc = $stmt->fetch(PDO::FETCH_ASSOC);     #returns array

        return $result_assoc;
    }

    public function getEmployeeInfoByEmail($email){
        $conn = $this->getConn();
        $sql_get_info = $conn->prepare("SELECT * FROM employee WHERE email= ?");
        $sql_get_info->execute(array($email));      #return PDOStatement

        return $sql_get_info;
    }

    public function getEmployeeInfoByID($userID){
        $conn = $this->getConn();
        $sql_get_info = $conn->prepare("SELECT * FROM employee WHERE id = ?");
        $sql_get_info->execute(array($userID));                     #return PDOStatement
        $get_employee_info =  $sql_get_info->fetch(PDO::FETCH_ASSOC);     #returns array

        return $get_employee_info;
    }

    public function getEmployeeFirstName($userID){
        $employee = $this->getEmployeeInfoByID($userID);
        $first_name = $employee['first_name'];

        return $first_name;
    }

    public function getEmployeeLastName($userID){
        $employee = $this->getEmployeeInfoByID($userID);
        $last_name = $employee['last_name'];

        return $last_name;
    }

    public function getEmployeeFullName($userID){
        $employee = $this->getEmployeeInfoByID($userID);
        $full_name = $employee['first_name'] . ' ' . $employee['last_name'];

        return $full_name;
    }

    public function getEmployeeEmail($userID){
        $employee = $this->getEmployeeInfoByID($userID);
        $email = $employee['email'];

        return $email;
    }

    public function getEmployeeContact($userID){
        $employee = $this->getEmployeeInfoByID($userID);
        $contact = $employee['contact'];

        return $contact;
    }
}