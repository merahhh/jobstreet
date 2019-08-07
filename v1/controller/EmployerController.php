<?php
error_reporting(E_ALL^E_NOTICE);
use Slim\Http\Response as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class EmployerController
{
    protected $employer, $session, $db, $twig;

    public function __construct($session, $employer){
        $this->employer = $employer;
        $this->session = $session;
        $this->db =$this->employer->getConn();
        $loader = new FilesystemLoader(__DIR__ . '/../../public/tpl/employer');
        $this->twig = new Environment($loader, ['debug' => true]);
        $this->twig->addExtension(new \Twig\Extension\DebugExtension());
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
                try {
                    echo $this->twig->render("page_error.twig", ['message' => $message]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
            else{   #email doesn't already exist in DB, proceed
                $company_password = password_hash($company_password, PASSWORD_BCRYPT);
                $company_hash = md5(rand(0, 1000));
                $id = $this->generateUniqueEmployerID();

                $sql_register = $this->db->prepare("INSERT INTO employer 
                    (employer_id, company_name, company_contact_person, company_contact_num, company_email, company_password, company_hash) 
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
                        return $response->withRedirect('/v1/employer/');
                    }
                    else{
                        $message = "Profile ID error.";
                        try {
                            echo $this->twig->render("page_error.twig", ['message' => $message]);
                        } catch (\Twig\Error\LoaderError $e) {
                        } catch (\Twig\Error\RuntimeError $e) {
                        } catch (\Twig\Error\SyntaxError $e) {
                        }
                    }
                }
                else{
                    $message = "Error, user not registered.";
                    try {
                        echo $this->twig->render("page_error.twig", ['message' => $message]);
                    } catch (\Twig\Error\LoaderError $e) {
                    } catch (\Twig\Error\RuntimeError $e) {
                    } catch (\Twig\Error\SyntaxError $e) {
                    }
                }
            }
        }
        else{
            $message = "Invalid email.";
            try {
                echo $this->twig->render("page_error.twig", ['message' => $message]);
            } catch (\Twig\Error\LoaderError $e) {
            } catch (\Twig\Error\RuntimeError $e) {
            } catch (\Twig\Error\SyntaxError $e) {
            }
        }
    }

    public function loginEmployer(Request $request, Response $response){
        try{
            $allPostPutVars = $request->getParsedBody();
            $email = $allPostPutVars['company_email'];
            $password = $allPostPutVars['company_password'];
            $employer = $this->employer->getInfoAssoc($email);

            if ($employer == null){
                $data = 'Employee does not exist';
                return $response->withJson($data, 404);
            }
            else{   #if password is correct
                if (password_verify($password, $employer['company_password'])){
                    $this->session->set('id', $employer['employer_id']);
                    $this->session->set('company_name', $employer['company_name']);
                    $this->session->set('company_contact_person', $employer['company_contact_person']);
                    $this->session->set('company_contact_num', $employer['company_contact_num']);
                    $this->session->set('company_email', $employer['company_email']);

                    //$this->session->set('active', $employer['active']);

                    #this is how we'll know the employer is logged in
                    $this->session->set('logged_in', true);
                    $data = 'Successfully logged in';
                    return $response->withRedirect('/v1/employer/');
                }
                else{    #if password is incorrect
                    $data = 'Incorrect password.';
                    try {
                        echo $this->twig->render("page_error.twig", ['message' => $data]);
                    } catch (\Twig\Error\LoaderError $e) {
                    } catch (\Twig\Error\RuntimeError $e) {
                    } catch (\Twig\Error\SyntaxError $e) {
                    }
                }
            }
        }
        catch (exception $e){
            $data = 'Oops, something went wrong!';
            try {
                echo $this->twig->render("page_error.twig", ['message' => $data]);
            } catch (\Twig\Error\LoaderError $e) {
            } catch (\Twig\Error\RuntimeError $e) {
            } catch (\Twig\Error\SyntaxError $e) {
            }
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
            return $response->withRedirect('/v1/employer/');

        } catch (exception $e){
            $data = 'Oops, something went wrong!';
            return $response->withJson($data, 300);
        }
    }

    public function viewProfileEmployer(Request $request, Response $response){
        if ($this->session->get('logged_in') == true) {
            $sql_get_profile = $this->db->prepare
            ("SELECT employer_profile.*, vacancies.* FROM employer_profile INNER JOIN vacancies ON employer_profile.employer_id = 
                vacancies.employer_id WHERE employer_profile.employer_id = ?");
            $result = $sql_get_profile->execute(array($this->session->get('id')));
            $count = 0;

            if ($result == true) {
                while ($row = $sql_get_profile->fetch(PDO::FETCH_ASSOC)) {
                    $employer[] = $row;
                    $count++;
                }
                try {
                    echo $this->twig->render("profile.twig", ['name' => $this->session->get('company_name'),
                        'employer' => $employer, 'company_name' => $this->session->get('company_name'), 'count' => $count]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
            else {
                $data = "No data found, edit profile now!";
                try {
                    echo $this->twig->render("page_error.twig", ['message' => $data]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
        }
        else {
            return $response->withRedirect('/v1/employer/error');
        }
    }

    public function editProfileEmployer(Request $request, Response $response){
        if ($this->session->get('logged_in') == true) {
            $user = $request->getParsedBody();
            $company_name = $user['company_name'];
            $background = $user['background'];
            $company_size = $user['company_size'];
            $company_website = $user['company_website'];
            $company_industry = $user['company_industry'];
            $company_benefits = $user['company_benefits'];
            $dress_code = $user['dress_code'];
            $spoken_language = $user['spoken_language'];
            $company_work_hours = $user['company_work_hours'];
            $location = $user['location'];
            $id = $this->session->get('id');

            #update employer_profile table
            $sql_edit_profile = $this->db->prepare
                ("UPDATE employer_profile, employer SET employer.company_name = ?, employer_profile.background = ?, 
                        employer_profile.company_size = ?, employer_profile.company_website = ?, employer_profile.company_industry = ?,
                        employer_profile.company_benefits = ?, employer_profile.dress_code = ?, employer_profile.spoken_language = ?, 
                        employer_profile.company_work_hours = ?, employer_profile.location = ? 
                        WHERE employer.employer_id = employer_profile.employer_id AND employer_profile.employer_id = ?");
            $result = $sql_edit_profile->execute([$company_name, $background, $company_size, $company_website,
                $company_industry, $company_benefits, $dress_code, $spoken_language, $company_work_hours, $location, $id]);

            #if insert into employer_profile successful
            if ($result == true){
                $message = "Profile edited!";
                return $response->withRedirect('/v1/employer/profile');
            }
            else{
                $message = "Unable to edit profile.";
                try {
                    echo $this->twig->render("page_error.twig", ['message' => $message]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
        }
        else {
            return $response->withRedirect('/v1/employer/error');
        }
    }

    public function addVacancy(Request $request, Response $response){
        if ($this->session->get('logged_in') == true) {
            $user = $request->getParsedBody();
            $v_name = $user['v_name'];
            $v_desc = $user['v_desc'];
            $v_salary = $user['v_salary'];
            $v_address = $user['v_address'];
            $v_state = $user['v_state'];
            $v_requirements = $user['v_requirements'];
            $v_position = $user['v_position'];
            $v_date_posted = date('Y-m-d');
            $v_closing_date = $user['v_closing_date'];
            $vacancy_id = $this->generateUniqueVacancyID();
            $id = $this->session->get('id');
            $company_name = $this->session->get('company_name');

            $sql_add_vacancy = $this->db->prepare
            ("INSERT INTO vacancies (employer_id, vacancy_id, company_name, v_name, v_desc, v_salary, v_address, v_state, 
                v_date_posted, v_requirements, v_position, v_closing_date) VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $result = $sql_add_vacancy->execute(array($id, $vacancy_id, $company_name, $v_name, $v_desc, $v_salary,
                $v_address, $v_state, $v_date_posted, $v_requirements, $v_position, $v_closing_date));

            if ($result == true){
                $message = "Job created successfully!";
                return $response->withRedirect('/v1/employer/vacancy/all');
            }
            else{
                $message = "Job not added!";
                try {
                    echo $this->twig->render("page_error.twig", ['message' => $message]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
        }
        else{
            return $response->withRedirect('/v1/employer/error');
        }
    }

    public function editVacancy(Request $request, Response $response, array $args){
        if ($this->session->get('logged_in') == true) {
            $user = $request->getParsedBody();
            $v_name = $user['v_name'];
            $v_position = $user['v_position'];
            $v_salary = $user['v_salary'];
            $v_state = $user['v_state'];
            $v_address = $user['v_address'];
            $v_desc = $user['v_desc'];
            $v_requirements = $user['v_requirements'];
            $v_closing_date = $user['v_closing_date'];
            $vacancy_id = $args['vacancy_id'];
            $id = $this->session->get('id');
            $company_name = $this->session->get('company_name');

            $sql_edit_vacancy = $this->db->prepare
                ("UPDATE vacancies SET v_name = ?, v_desc = ?, v_salary = ?, v_state = ?, v_address = ?, v_requirements = ?,
                v_position = ?, v_closing_date = ? WHERE vacancy_id = ?");
            $result = $sql_edit_vacancy->execute(array($v_name, $v_desc, $v_salary, $v_state, $v_address, $v_requirements,
                $v_position, $v_closing_date, $vacancy_id));

            if ($result == true){
                $message = "Vacancy edited!";
                return $response->withRedirect('/v1/employer/vacancy/all');
            }
            else{
                $message = "Unable to edit vacancy.";
                try {
                    echo $this->twig->render("page_error.twig", ['message' => $message]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
        }
        else{
            return $response->withRedirect('/v1/employer/error');
        }
    }

    public function deleteVacancy(Request $request, Response $response, array $args){
        if ($this->session->get('logged_in') == true) {
            $vacancy_id = $args['vacancy_id'];
            $sql_delete_vacancy = $this->db->prepare("DELETE FROM vacancies WHERE vacancy_id = ?");
            $result = $sql_delete_vacancy->execute(array($vacancy_id));

            if ($result == true){
                $message = "Vacancy deleted!";
                return $response->withRedirect('/v1/employer/vacancy/all');
            }
            else{
                $message = "Unable to delete vacancy";
                try {
                    echo $this->twig->render("page_error.twig", ['message' => $message]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
        }
        else{
            return $response->withRedirect('/v1/employer/error');
        }
    }

    public function viewEmployerVacancies(Request $request, Response $response){
        if ($this->session->get('logged_in') == true) {
            $sql_get_posted_vacancies = $this->db->prepare
            ("SELECT vacancy_id, company_name, v_name, v_position, v_state, v_address, v_requirements, v_salary, v_desc,
                v_closing_date FROM vacancies WHERE employer_id = ? AND v_status = 1");
            $result = $sql_get_posted_vacancies->execute(array($this->session->get('id')));
            $count = 0;

            if ($result == true) {
                while ($row = $sql_get_posted_vacancies->fetch(PDO::FETCH_ASSOC)) {
                    $vacancies[] = $row;
                    $count++;
                }
                try {
                    echo $this->twig->render("employer_vacancies.twig", ['name' => $this->session->get('company_name'),
                        'vacancies' => $vacancies, 'count' => $count]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
            else {
                $data = "No data found, edit profile now.";
                try {
                    echo $this->twig->render("page_error.twig", ['message' => $data]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
        }
        else{
            return $response->withRedirect('/v1/employer/error');
        }
    }

    public function viewFullVacancy(Request $request, Response $response, array $args){
        $vacancy_id = $args['vacancy_id'];
        $sql_vacancy_details = $this->db->prepare
        ("SELECT vacancies.vacancy_id, vacancies.company_name, vacancies.v_name, vacancies.v_desc, vacancies.v_address, 
            vacancies.v_requirements, vacancies.v_position, vacancies.v_state, vacancies.v_salary, 
            vacancies.v_closing_date, employer_profile.* FROM vacancies INNER JOIN employer_profile ON 
            employer_profile.employer_id = vacancies.employer_id WHERE vacancy_id = ?");
        $result = $sql_vacancy_details->execute(array($vacancy_id));

        if ($result == true) {
            while ($row = $sql_vacancy_details->fetch(PDO::FETCH_ASSOC)) {
                $vacancy_details[] = $row;
            }
            try {
                echo $this->twig->render("vacancy_details.twig", ['name' => $this->session->get('company_name'),
                    'vacancy_details' => $vacancy_details]);
            } catch (\Twig\Error\LoaderError $e) {
                echo 'a';
            } catch (\Twig\Error\RuntimeError $e) {
                echo 'b';
            } catch (\Twig\Error\SyntaxError $e) {
                echo 'c';
            }
        }
        else {
            $data = "No data found for this job.";
            try {
                echo $this->twig->render("page_error.twig", ['message' => $data]);
            } catch (\Twig\Error\LoaderError $e) {
            } catch (\Twig\Error\RuntimeError $e) {
            } catch (\Twig\Error\SyntaxError $e) {
            }
        }
    }

    public function viewVacanciesForApplicants(Request $request, Response $response){
        if ($this->session->get('logged_in') == true) {
            $sql_get_vacancies = $this->db->prepare
            ("SELECT vacancy_id, company_name, v_name, v_position, v_state, v_address, v_requirements, v_salary, v_desc, v_closing_date FROM vacancies
                WHERE employer_id = ?");
            $result = $sql_get_vacancies->execute(array($this->session->get('id')));
            $count = 0;

            if ($result == true) {
                while ($row = $sql_get_vacancies->fetch(PDO::FETCH_ASSOC)) {
                    $vacancies[] = $row;
                    $count++;
                }
                try {
                    echo $this->twig->render("applicants_job_list.twig", ['name' => $this->session->get('company_name'),
                        'vacancies' => $vacancies, 'count' => $count]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
            else {
                $data = "No data found, edit profile now.";
                try {
                    echo $this->twig->render("page_error.twig", ['message' => $data]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
        }
        else {
            return $response->withRedirect('/v1/employer/error');
        }
    }

    public function viewVacancyApplicants(Request $request, Response $response, array $args){
        if ($this->session->get('logged_in') == true) {
            $vacancy_id = $args['vacancy_id'];
            $sql_view_applicants = $this->db->prepare
            ("SELECT * FROM vacancy_applicants JOIN employee ON (vacancy_applicants.id = employee.id) JOIN vacancies 
            ON (vacancies.vacancy_id = vacancy_applicants.vacancy_id) WHERE vacancy_applicants.vacancy_id = ?");
            $result = $sql_view_applicants->execute(array($vacancy_id));
            $count = 0;

            if ($result == true){
                while ($row = $sql_view_applicants->fetch(PDO::FETCH_ASSOC)) {
                    $applicants[] = $row;
                    $count++;
                }
                try {
                    echo $this->twig->render("applicants.twig", ['name' => $this->session->get('company_name'),
                        'applicants' => $applicants, 'count' => $count]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
            else{
                $data = "No applicants has applied for this vacancy";
                try {
                    echo $this->twig->render("page_error.twig", ['message' => $data]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
        }
        else{
            return $response->withRedirect('/v1/employer/error');
        }
    }

    public function setApplicationStatus(Request $request, Response $response, array $args){
        if ($this->session->get('logged_in') == true) {
            $application_id = $args['application_id'];
            $user = $request->getParsedBody();
            $application_status = $user['application_status'];
            $sql_set_application_status = $this->db->prepare
            ("UPDATE vacancy_applicants SET application_status = ? WHERE application_id = ?");
            $result = $sql_set_application_status->execute([$application_status, $application_id]);

            if ($result == true){
                while ($row = $sql_set_application_status->fetch(PDO::FETCH_ASSOC)) {
                    $applicants[] = $row;
                }
                return $response->withRedirect('/v1/employer/applicants/vacancy/all');
            }
            else{
                $data = "No applicants has applied for this vacancy.";
                try {
                    echo $this->twig->render("page_error.twig", ['message' => $data]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
        }
        else {
            return $response->withRedirect('/v1/employer/error');
        }
    }

    public function viewApplicantProfile(Request $request, Response $response, array $args){
        if ($this->session->get('logged_in') == true) {
            $employee_id = $args['employee_id'];
            $sql_applicants = $this->db->prepare
            ("SELECT * FROM employee JOIN employee_profile ON (employee_profile.id = employee.id)
            JOIN employee_education ON (employee_education.id = employee.id) WHERE employee.id = ?");
            $result = $sql_applicants->execute(array($employee_id));

            if ($result == true) {
                while ($row = $sql_applicants->fetch(PDO::FETCH_ASSOC)) {
                    $applicant_profile[] = $row;
                }
                try {
                    echo $this->twig->render("applicant_profile.twig", ['name' => $this->session->get('company_name'),
                        'applicant_profile' => $applicant_profile]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
            else{
                $message = "Unable to get applicant info";
                try {
                    echo $this->twig->render("page_error.twig", ['message' => $message]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
        }
        else{
            return $response->withRedirect('/v1/employer/error');
        }
    }

    public function error(Request $request, Response $response){
        try {
            echo $this->twig->render("error.twig");
        } catch (\Twig\Error\LoaderError $e) {
        } catch (\Twig\Error\RuntimeError $e) {
        } catch (\Twig\Error\SyntaxError $e) {
        }
    }

    public function pageError(){
        try {
            echo $this->twig->render("page_error.twig");
        } catch (\Twig\Error\LoaderError $e) {
        } catch (\Twig\Error\RuntimeError $e) {
        } catch (\Twig\Error\SyntaxError $e) {
        }
    }
}