<?php
error_reporting(E_ALL^E_NOTICE);
use Slim\Http\Response as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class EmployerController
{
    protected $employer, $session, $db;

    public function __construct($session, $employer){
        $this->employer = $employer;
        $this->session = $session;
        $this->db =$this->employer->getConn();
    }

    public function __get($name){
        // TODO: Implement __get() method.
        return $this->value[$name];
    }

    # Function to generate unique alpha numeric code
    public function generateUniqueEmployerID($len = 5){
        $randomString = substr(MD5(time()), $len);
        return $randomString;
    }

    public function generateUniqueVacancyID($len = 8){
        $randomString = substr(MD5(time()), $len);
        return $randomString;
    }

    public function registerEmployer(Request $request, Response $response){
        $company_name = json_decode($request->getBody())->company_name;
        $company_contact_person = json_decode($request->getBody())->company_contact_person;
        $company_contact_num = json_decode($request->getBody())->company_contact_num;
        $company_email = json_decode($request->getBody())->company_email;
        $company_password = json_decode($request->getBody())->company_password;

        if (!filter_var($company_email, FILTER_VALIDATE_EMAIL) === false){
            $sql_get_info = $this->db->prepare("SELECT * FROM employer WHERE company_email = ?");
            $sql_get_info->execute(array($company_email));
            $result = $sql_get_info->fetch(PDO::FETCH_ASSOC);

            #we know user email exists if the rows returned are > 0
            if ($result != null){
                $message = "User with that email exists";
                return $response->withJson($message, 501);
            }
            else{   #email doesn't already exist in DB, proceed
                $company_password = password_hash($company_password, PASSWORD_BCRYPT);
                $company_hash = md5(rand(0, 1000));
                $id = $this->generateUniqueEmployerID();

                $sql_register = $this->db->prepare("INSERT INTO employer 
                    (id, company_name, company_contact_person, company_contact_num, company_email, company_password, company_hash) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)");
                $result = $sql_register->execute(array($id, $company_name, $company_contact_person, $company_contact_num,
                    $company_email, $company_password, $company_hash));

                if ($result == true){
                    $sql_register_profile = $this->db->prepare("INSERT INTO employer_profile (id) VALUES 
                        (?)");
                    $result_profile = $sql_register_profile->execute(array($id));

                    if ($result_profile == true){
                        $this->session->set('active', 1);
                        $message = "User account created successfully";
                        return $response->withJson($message, 201);
                    }
                    else{
                        $message = "Profile ID error";
                        return $response->withJson($message, 400);
                    }
                }
                else{
                    $message = "User not registered";
                    return $response->withJson($message, 500);
                }
            }
        }
        else{
            $message = "Invalid email";
            return $response->withJson($message, 400);
        }
    }

    public function loginEmployer(Request $request, Response $response){
        try{
            $company_email = json_decode($request->getBody())->company_email;
            $employer = $this->employer->getInfoAssoc($company_email);

            if ($employer == null){
                $data = 'Employee does not exist';
                return $response->withJson($data, 404);
            }
            else{   #if password is correct
                if (password_verify(json_decode($request->getBody())->company_password, $employer['company_password'])){
                    $this->session->set('id', $employer['id']);
                    $this->session->set('company_name', $employer['company_name']);
                    $this->session->set('company_contact_person', $employer['company_contact_person']);
                    $this->session->set('company_contact_num', $employer['company_contact_num']);
                    $this->session->set('company_email', $employer['company_email']);

                    //$this->session->set('active', $employer['active']);

                    #this is how we'll know the employer is logged in
                    $this->session->set('logged_in', true);
                    $data = 'Successfully logged in';
                    return $response->withJson($data, 200);
                }
                else{    #if password is incorrect
                    $data = 'Incorrect password';
                    return $response->withJson($data, 500);
                }
            }
        }
        catch (exception $e){
            $data = 'Oops, something went wrong!';
            return $response->withJson($data, 300);
        }
    }

    public function logoutEmployer(Request $request, Response $response){
        try{
            # Initialize the session.
            # If you are using session_name("something"), don't forget it now!
            session_start();

            # Unset all of the session variables.
            $_SESSION = array();

            # If it's desired to kill the session, also delete the session cookie.
            # Note: This will destroy the session, and not just the session data!
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }

            # Finally, destroy the session.
            session_destroy();
            $this->session->set('logged_in', false);
            //$this->session->destroySession();
            $message = 'Successfully logged out!';
            return $response->withJson($message, 200);

        } catch (exception $e){
            $data = 'Oops, something went wrong!';
            return $response->withJson($data, 300);
        }
    }

    public function viewProfileEmployer(Request $request, Response $response){
        if ($this->session->get('logged_in') == true) {
            $sql_get_profile = $this->db->prepare
            ("SELECT background, location FROM employer_profile WHERE id = ?");
            $result = $sql_get_profile->execute(array($this->session->get('id')));
            //$result = $this->db->query($sql_get_profile);

            if ($result == true) {
                while ($row = $sql_get_profile->fetch(PDO::FETCH_ASSOC)) {
                    $data[] = $row;
                    //echo json_encode($data);
                }
                return $response->withStatus(200);
            }
            else {
                $data = "No data found, edit profile now";
                return $response->withJson($data, 400);
            }
        }
        else {
            $message = "Please log in to view this profile";
            return $response->withJson($message, 403);
        }
    }

    public function editProfileEmployer(Request $request, Response $response){
        if ($this->session->get('logged_in') == true) {
            $background = json_decode($request->getBody())->background;
            $company_size = json_decode($request->getBody())->company_size;
            $company_website = json_decode($request->getBody())->company_website;
            $company_benefits = json_decode($request->getBody())->company_benefits;
            $dress_code = json_decode($request->getBody())->dress_code;
            $spoken_language = json_decode($request->getBody())->spoken_language;
            $company_work_hours = json_decode($request->getBody())->company_work_hours;
            $location = json_decode($request->getBody())->location;
            $id = $this->session->get('id');
            var_dump($id);
            #update employer_profile table
            $sql_edit_profile = $this->db->prepare
                ("UPDATE employer_profile SET background = ?, company_size = ?, company_website = ?, 
                        company_benefits = ?, dress_code = ?, spoken_language = ?, company_work_hours = ?, 
                        location = ? WHERE id = ?");
            $result = $sql_edit_profile->execute([$background, $company_size, $company_website, $company_benefits,
                $dress_code, $spoken_language, $company_work_hours, $location, $id]);

            #if insert into employer_profile successful
            if ($result == true){
                $message = "Profile edited!";
                return $response->withJson($message, 200);
            }
            else{
                $message = "Unable to edit profile";
                return $response->withJson($message, 500);
            }
        }
        else {
            $message = "Please log in to edit your profile";
            return $response->withJson($message, 403);
        }
    }

    public function addVacancy(Request $request, Response $response){
        if ($this->session->get('logged_in') == true) {
            $v_name = json_decode($request->getBody())->v_name;
            $v_desc = json_decode($request->getBody())->v_desc;
            $v_salary = json_decode($request->getBody())->v_salary;
            $v_location = json_decode($request->getBody())->v_location;
            $v_requirements = json_decode($request->getBody())->v_requirements;
            $v_position = json_decode($request->getBody())->v_position;
            $v_closing_date = json_decode($request->getBody())->v_closing_date;
            $vacancy_id = $this->generateUniqueVacancyID();
            $id = $this->session->get('id');
            $company_name = $this->session->get('company_name');

            $sql_add_vacancy = $this->db->prepare
            ("INSERT INTO vacancies (id, vacancy_id, company_name, v_name, v_desc, v_salary, v_location, 
                v_requirements, v_position, v_closing_date) VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $result = $sql_add_vacancy->execute
            (array($id, $vacancy_id, $company_name, $v_name, $v_desc, $v_salary, $v_location, $v_position,
                $v_requirements, $v_closing_date));

            if ($result == true){
                $sql_set_vacancies_true = $this->db->prepare("UPDATE employer_profile SET vacancies = 1 WHERE id = ?");
                $result_vacancies = $sql_set_vacancies_true->execute(array($id));

                if ($result_vacancies == true){
                    $message = "Job added successfully!";
                    return $response->withJson($message, 201);
                }
                else{
                    $message = "Vacancy profile error";
                    return $response->withJson($message, 500);
                }
            }
            else{
                $message = "Job not added!";
                return $response->withJson($message, 500);
            }
        }
        else{
            $message = "Please log in to add a vacancy";
            return $response->withJson($message, 403);
        }
    }

    public function editVacancy(Request $request, Response $response, array $args){
        if ($this->session->get('logged_in') == true) {
            $v_name = json_decode($request->getBody())->v_name;
            $v_desc = json_decode($request->getBody())->v_desc;
            $v_salary = json_decode($request->getBody())->v_salary;
            $v_location = json_decode($request->getBody())->v_location;
            $v_requirements = json_decode($request->getBody())->v_requirements;
            $v_position = json_decode($request->getBody())->v_position;
            $v_closing_date = json_decode($request->getBody())->v_closing_date;
            $vacancy_id = $args['vacancy_id'];
            $id = $this->session->get('id');
            $company_name = $this->session->get('company_name');

            $sql_edit_vacancy = $this->db->prepare
                ("UPDATE vacancies SET v_name = ?, v_desc = ?, v_salary = ?, v_location = ?, v_requirements = ?,
                v_position = ?, v_closing_date = ? WHERE vacancy_id = ?");
            $result = $sql_edit_vacancy->execute(array($v_name, $v_desc, $v_salary, $v_location, $v_requirements,
                $v_position, $v_closing_date, $vacancy_id));

            if ($result == true){
                $message = "Vacancy edited!";
                return $response->withJson($message, 200);
            }
            else{
                $message = "Unable to edit vacancy";
                return $response->withJson($message, 500);
            }
        }
        else{
            $message = "Please log in to edit this vacancy";
            return $response->withJson($message, 403);
        }
    }

    /*public function deleteVacancy(Request $request, Response $response, array $args){
        if ($this->session->get('logged_in') == true) {
            $vacancy_id = $args['vacancy_id'];
            $sql_delete_vacancy = $this->db->prepare("DELETE FROM vacancies WHERE id = ?");
            $result = $sql_delete_vacancy->execute(array($vacancy_id));

            if ($result == true){
                $message = "Vacancy deleted!";
                return $response->withJson($message, 200);
            }
            else{
                $message = "Unable to delete vacancy";
                return $response->withJson($message, 500);
            }
        }
        else{
            $message = "Please log in to delete this vacancy";
            return $response->withJson($message, 403);
        }
    }*/

    public function viewEmployerVacancies(Request $request, Response $response){
        if ($this->session->get('logged_in') == true) {
            $sql_get_posted_vacancies = $this->db->prepare
            ("SELECT v_name, v_position FROM vacancies WHERE id = ?");
            $result = $sql_get_posted_vacancies->execute(array($this->session->get('id')));
            //$result = $this->db->query($sql_get_profile);

            if ($result == true) {
                while ($row = $sql_get_posted_vacancies->fetch(PDO::FETCH_ASSOC)) {
                    $data[] = $row;
                    echo json_encode($data);
                }
                return $response->withStatus(200);
            }
            else {
                $data = "No data found, edit profile now";
                return $response->withJson($data, 400);
            }
        }
        else{
            $message = "Please log in to view your posted vacancies";
            return $response->withJson($message, 403);
        }
    }

    public function viewVacancyApplicants(Request $request, Response $response, array $args){
        if ($this->session->get('logged_in') == true) {
            $vacancy_id = $args['vacancy_id'];
            $sql_view_applicants = $this->db->prepare
            ("SELECT employee.id, employee.first_name, employee.last_name, employee.email FROM employee INNER JOIN 
                vacancy_applicants ON employee.id = vacancy_applicants.id WHERE vacancy_applicants.vacancy_id = ?");
            $result = $sql_view_applicants->execute(array($vacancy_id));

            if ($result == true){
                while ($row = $sql_view_applicants->fetch(PDO::FETCH_ASSOC)) {
                    $data[] = $row;
                    echo json_encode($data);
                }
                return $response->withStatus(200);
            }
            else{
                $data = "No applicants has applied for this vacancy";
                return $response->withJson($data, 400);
            }
        }
        else{
            $message = "Please log in to view applicants for vacancies";
            return $response->withJson($message, 403);
        }
    }

    public function viewApplicantDetails(Request $request, Response $response, array $args){
        if ($this->session->get('logged_in') == true) {
            $employee_id = $args['employee_id'];
            $sql_applicant_details = $this->db->prepare
            ("SELECT employee.id, employee.first_name, employee.last_name, employee.email, employee.contact
                FROM employee WHERE id = ?");
            $result = $sql_applicant_details->execute(array($employee_id));

            while ($row = $sql_applicant_details->fetch(PDO::FETCH_ASSOC)) {
                    $data[] = $row;
                    //echo json_encode($data);
            }

            if ($result == true) {
                $sql_applicants_profile = $this->db->prepare
                ("SELECT employee_profile.id, employee_profile.experience, employee_profile.skills, employee_profile.language,
                employee_profile.expected_salary, employee_profile.location, employee_profile.resume,
                employee_education.institute, employee_education.graduation_time, employee_education.qualification,
                employee_education.major, employee_education.grade FROM employee_profile
                INNER JOIN employee_education ON employee_profile.education_id = employee_education.education_id
                WHERE employee_profile.id = ?");
                $result_profile = $sql_applicants_profile->execute(array($employee_id));

                if ($result_profile == true) {
                    while ($row = $sql_applicants_profile->fetch(PDO::FETCH_ASSOC)) {
                        $data[] = $row;
                        echo json_encode($data);
                    }
                    return $response->withStatus(200);
                } else {
                    $message = "Unable to get applicant profile & education";
                    return $response->withJson($message, 500);
                }
            }
            else{
                $message = "Unable to get applicant info";
                return $response->withJson($message, 500);
            }
        }
        else{
            $message = "Please log in to view applicants for vacancies";
            return $response->withJson($message, 403);
        }
    }
}