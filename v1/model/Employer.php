<?php


class Employer
{
    public function getConn()
    {
        return $conn = new PDO('mysql:host=localhost;dbname=jobstreet;charset=utf8mb4', 'root', 'root');
    }

    public function getInfoAssoc($email){
        $stmt = $this->getEmployerInfoByEmail($email);
        $result_assoc = $stmt->fetch(PDO::FETCH_ASSOC);     #returns array

        return $result_assoc;
    }

    public function getEmployerInfoByEmail($email){
        $conn = $this->getConn();
        $sql_get_info = $conn->prepare("SELECT * FROM employer WHERE company_email = ?");
        $sql_get_info->execute(array($email));      #return PDOStatement

        return $sql_get_info;
    }

    public function getEmployerInfoByID($userID){
        $conn = $this->getConn();
        $sql_get_info = $conn->prepare("SELECT * FROM employer WHERE id = ?");
        $sql_get_info->execute(array($userID));                     #return PDOStatement
        $get_employer_info =  $sql_get_info->fetch(PDO::FETCH_ASSOC);     #returns array

        return $get_employer_info;
    }

    public function getEmployerCompanyName($userID){
        $employer = $this->getEmployerInfoByID($userID);
        $company_name = $employer['company_name'];

        return $company_name;
    }

    public function getEmployerContactPerson($userID){
        $employer = $this->getEmployerInfoByID($userID);
        $contact_person = $employer['company_contact_person'];

        return $contact_person;
    }

    public function getEmployerContactNum($userID){
        $employer = $this->getEmployerInfoByID($userID);
        $contact_num = $employer['company_contact_num'];

        return $contact_num;
    }

    public function getEmployerEmail($userID){
        $employer = $this->getEmployerInfoByID($userID);
        $company_email = $employer['company_email'];

        return $company_email;
    }
}