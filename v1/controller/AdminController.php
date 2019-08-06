<?php
error_reporting(E_ALL^E_NOTICE);
use Slim\Http\Response as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Router;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class AdminController
{
    protected $admin, $twig_admin, $session, $db, $employee, $employer;

    public function __construct($session, $admin, $employee, $employer)
    {
        $this->session = $session;
        $this->admin = $admin;
        $this->employee = $employee;
        $this->employer = $employer;
        $this->db = $this->employee->getConn();
        $loader = new FilesystemLoader(__DIR__ . '/../../public/tpl/admin');
        $this->twig_admin = new Environment($loader, ['debug' => true]);
        $this->twig_admin->addExtension(new \Twig\Extension\DebugExtension());
    }

    public function __get($name){
        // TODO: Implement __get() method.
        return $this->value[$name];
    }

    public function generateUniqueAdminID($len = 5)
    {
        $randomString = substr(MD5(time()), $len);
        return $randomString;
    }

    public function index(Request $request, Response $response){
        if ($this->session->get('admin_logged_in') == true){
            try {
                echo $this->twig_admin->render("home.twig", ['name' => $this->session->get('admin_first_name')]);
            } catch (\Twig\Error\LoaderError $e) {
            } catch (\Twig\Error\RuntimeError $e) {
            } catch (\Twig\Error\SyntaxError $e) {
            }
        }
        else {
            try {
                echo $this->twig_admin->render("index.twig");
            } catch (\Twig\Error\LoaderError $e) {
                echo "a";
            } catch (\Twig\Error\RuntimeError $e) {
                echo "b";
            } catch (\Twig\Error\SyntaxError $e) {
                echo "c";
            }
        }
    }

    public function registerAdmin(Request $request, Response $response){
        $user = $request->getParsedBody();
        $first_name = $user['first_name'];
        $last_name = $user['last_name'];
        $email = $user['email'];
        $password = $user['password'];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $sql_get_info = $this->db->prepare("SELECT * FROM admin WHERE a_email = ?");
            $sql_get_info->execute(array($email));
            $result = $sql_get_info->fetch(PDO::FETCH_ASSOC);

            #we know user email exists if the rows returned are > 0
            if ($result != null) {
                $message = "User with that email exists";
                return $response->withRedirect('/v1/admin/');
            } else {   #email doesn't already exist in DB, proceed
                $password = password_hash($password, PASSWORD_BCRYPT);
                $hash = md5(rand(0, 1000));
                $id = $this->generateUniqueAdminID();

                $sql_register = $this->db->prepare("INSERT INTO admin 
                    (admin_id, a_first_name, a_last_name, a_email, a_password, a_hash) VALUES (?, ?, ?, ?, ?, ?)");
                $result = $sql_register->execute(array($id, $first_name, $last_name, $email, $password, $hash));

                if ($result == true) {
                    $message = "Admin account created successfully";
                    return $response->withRedirect('/v1/admin/');
                } else {
                    $message = "User not registered";
                    //return $response->withJson($message, 500);
                    return $response->withRedirect('/');
                }
            }
        }
        else {
            $message = "Invalid email";
            return $response->withJson($message, 400);
        }
    }

    public function loginAdmin(Request $request, Response $response){
        try{
            $user = $request->getParsedBody();
            $email = $user['email'];
            $password = $user['password'];
            $user = $this->admin->getInfoAssoc($email);

            if ($user == null){
                //$data = 'Employee does not exist';
                return $response->withRedirect('/v1/admin/');
            }
            else{
                if (password_verify($password, $user['a_password'])){    #if password is correct
                    $this->session->set('admin_id', $user['admin_id']);
                    $this->session->set('admin_email', $user['a_email']);
                    $this->session->set('admin_first_name', $user['a_first_name']);
                    $this->session->set('admin_last_name', $user['a_last_name']);

                    #this is how we'll know the user is logged in
                    $this->session->set('admin_logged_in', true);
                    return $response->withRedirect('/v1/admin/');

                }
                else{    #if password is incorrect
                    //$data = 'Incorrect password';
                    return $response->withRedirect('/v1/admin/');
                }
            }
        }
        catch (exception $e){
            $data = 'Oops, something went wrong!';
            return $response->withRedirect('/v1/admin/');
        }
    }

    public function logoutAdmin(Request $request, Response $response){
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
            //return $response->withJson($message, 200);
            return $response->withRedirect('/v1/admin/');

        } catch (exception $e){
            $data = 'Oops, something went wrong!';
            return $response->withJson($data, 300);
        }
    }

    public function viewVacancyDetails(Request $request, Response $response, array $args){
        if ($this->session->get('admin_logged_in') == true){
            $vacancy_id = $args['vacancy_id'];
            $sql_vacancy_details = $this->db->prepare
            ("SELECT vacancies.vacancy_id, vacancies.company_name, vacancies.v_name, vacancies.v_desc, vacancies.v_address, 
            vacancies.v_requirements, vacancies.v_position, vacancies.v_state, vacancies.v_salary, 
            vacancies.v_closing_date, employer_profile.* FROM vacancies INNER JOIN employer_profile on 
            employer_profile.employer_id = vacancies.employer_id WHERE vacancy_id = ?");
            $result = $sql_vacancy_details->execute(array($vacancy_id));

            if ($result == true) {
                while ($row = $sql_vacancy_details->fetch(PDO::FETCH_ASSOC)) {
                    $vacancy_details[] = $row;
                }
                try {
                    echo $this->twig_admin->render("vacancy_details.twig", ['name' => $this->session->get('admin_first_name'),
                        'vacancy_details' => $vacancy_details]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
            else {
                $data = "No data found for this job";
                return $response->withJson($data, 400);
            }
        }
        else {
            return $response->withRedirect('/v1/admin/error');
        }
    }

    public function viewAllVacancies(Request $request, Response $response){
        if ($this->session->get('admin_logged_in') == true){
            $sql_view_vacancies = "SELECT employer_id, vacancy_id, company_name, v_name, v_state, v_salary, v_desc, 
            v_closing_date FROM vacancies";
            $result = $this->db->query($sql_view_vacancies);
            $count = 0;

            if ($result == true) {
                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    $vacancies[] = $row;
                    $count++;
                }
                try {
                    echo $this->twig_admin->render("vacancies.twig", ['name' => $this->session->get('admin_first_name'),
                        'last_name' => $this->session->get('admin_last_name'), 'vacancies' => $vacancies,
                        'count' => $count]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
            else {
                $data = "No jobs found";
                return $response->withJson($data, 400);
            }
        }
        else {
            $message = "Please log in";
            return $response->withRedirect('/v1/admin/error');
        }
    }

    public function deleteVacancy(Request $request, Response $response, array $args){
        if ($this->session->get('admin_logged_in') == true) {
            $vacancy_id = $args['vacancy_id'];
            $sql_delete_vacancy = $this->db->prepare("DELETE FROM vacancies WHERE vacancy_id = ?");
            $result = $sql_delete_vacancy->execute([$vacancy_id]);

            if ($result == true){
                $message = "Vacancy deleted!";
                return $response->withRedirect('/v1/admin/vacancies');
            }
            else{
                $message = "Unable to delete vacancy";
                return $response->withJson($message, 500);
            }
        }
        else{
            $message = "Please log in to delete this vacancy";
            return $response->withRedirect('/v1/admin/error');
        }
    }

    public function searchVacancies(Request $request, Response $response, array $args){
        if ($this->session->get('admin_logged_in') == true) {
            $user = $request->getQueryParams();
            $keyword = $user['job-keyword'];

            $sql_search_keyword = $this->db->prepare
            ("SELECT * FROM vacancies WHERE v_name LIKE ? OR v_requirements LIKE ?");
            $result = $sql_search_keyword->execute(["%$keyword%", "%$keyword%"]);
            $count = 0;

            if ($result == true) {
                while ($row = $sql_search_keyword->fetch(PDO::FETCH_ASSOC)) {
                    $vacancies[] = $row;
                    $count++;
                }
                try {
                    echo $this->twig_admin->render("vacancies.twig", ['name' => $this->session->get('admin_first_name'),
                        'vacancies' => $vacancies, 'count' => $count]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            } else {
                $message = "No employees with that name found";
                return $response->withJson($message, 400);
            }
        }
        else {
            $message = "Please log in to delete this employee";
            return $response->withRedirect('/v1/admin/error');
        }
    }

    public function viewEmployerProfile(Request $request, Response $response, array $args){
        if ($this->session->get('admin_logged_in') == true) {
            $employer_id = $args['employer_id'];
            $sql_get_profile = $this->db->prepare
            ("SELECT employer_profile.*, vacancies.* FROM employer_profile INNER JOIN vacancies ON employer_profile.employer_id = 
                vacancies.employer_id WHERE employer_profile.employer_id = ?");
            $result = $sql_get_profile->execute([$employer_id]);
            $count = 0;

            if ($result == true) {
                while ($row = $sql_get_profile->fetch(PDO::FETCH_ASSOC)) {
                    $employer[] = $row;
                    $count++;
                }
                try {
                    echo $this->twig_admin->render("employer_profile.twig", ['name' => $this->session->get('admin_first_name'),
                        'employer' => $employer, 'count' => $count]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
            else {
                $message = "No data found, edit profile now";
                return $response->withJson($message, 400);
            }
        }
        else {
            return $response->withRedirect('/v1/admin/error');
        }
    }

    public function viewAllEmployers(Request $request, Response $response){
        if ($this->session->get('admin_logged_in') == true) {
            $sql_view_employers = "SELECT employer_id, company_name, company_contact_person, company_contact_num, company_email
                FROM employer";
            $result = $this->db->query($sql_view_employers);
            $count = 0;

            if ($result == true) {
                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    $employers[] = $row;
                    $count++;
                }
                try {
                    echo $this->twig_admin->render("employers.twig", ['name' => $this->session->get('admin_first_name'),
                        'employers' => $employers, 'count' => $count]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
            else {
                $data = "No employers found";
                return $response->withJson($data, 400);
            }
        }
        else {
            $message = "Please log in to delete this vacancy";
            return $response->withRedirect('/v1/admin/error');
        }
    }

    public function deleteEmployer(Request $request, Response $response, array $args){
        if ($this->session->get('admin_logged_in') == true) {
            $employer_id = $args['employer_id'];
            $sql_delete_employer = $this->db->prepare("DELETE FROM employer WHERE employer_id = ?");
            $result = $sql_delete_employer->execute([$employer_id]);

            if ($result == true){
                $message = "Employer deleted!";
                return $response->withRedirect('/v1/admin/employers');
            }
            else{
                $message = "Unable to delete employer";
                return $response->withJson($message, 500);
            }
        }
        else{
            $message = "Please log in to delete this employer";
            return $response->withRedirect('/v1/admin/error');
        }
    }

    public function searchEmployers(Request $request, Response $response, array $args){
        if ($this->session->get('admin_logged_in') == true) {
            $user = $request->getQueryParams();
            $keyword = $user['employer-keyword'];

            $sql_search_keyword = $this->db->prepare
            ("SELECT * FROM employer WHERE company_name LIKE ?");
            $result = $sql_search_keyword->execute(["%$keyword%"]);
            $count = 0;

            if ($result == true) {
                while ($row = $sql_search_keyword->fetch(PDO::FETCH_ASSOC)) {
                    $employers[] = $row;
                    $count++;
                }
                try {
                    echo $this->twig_admin->render("employers.twig", ['name' => $this->session->get('admin_first_name'),
                        'employers' => $employers, 'count' => $count]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            } else {
                $message = "No employees with that name found";
                return $response->withJson($message, 400);
            }
        }
        else {
            $message = "Please log in to delete this employee";
            return $response->withRedirect('/v1/admin/error');
        }
    }

    public function viewEmployeeProfile(Request $request, Response $response, array $args){
        if ($this->session->get('admin_logged_in') == true) {
            $employee_id = $args['employee_id'];
            $sql_applicants = $this->db->prepare
            ("SELECT * FROM employee JOIN employee_profile ON (employee_profile.id = employee.id)
            JOIN employee_education ON (employee_education.id = employee.id) WHERE employee.id = ?");
            $result = $sql_applicants->execute(array($employee_id));

            if ($result == true) {
                while ($row = $sql_applicants->fetch(PDO::FETCH_ASSOC)) {
                    $employee_profile[] = $row;
                }
                try {
                    echo $this->twig_admin->render("employee_profile.twig", ['name' => $this->session->get('admin_first_name'),
                        'employee_profile' => $employee_profile]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
            else{
                $message = "Unable to get employee info";
                return $response->withJson($message, 500);
            }
        }
        else{
            $message = "Please log in to view employees' profile";
            return $response->withRedirect('/v1/admin/error');
        }
    }

    public function viewAllEmployees(Request $request, Response $response){
        if ($this->session->get('admin_logged_in') == true) {
            $sql_view_employees = "SELECT id, first_name, last_name, email, contact FROM employee";
            $result = $this->db->query($sql_view_employees);
            $count = 0;

            if ($result == true) {
                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    $employees[] = $row;
                    $count++;
                }
                try {
                    echo $this->twig_admin->render("employees.twig", ['name' => $this->session->get('admin_first_name'),
                        'employees' => $employees, 'count' => $count]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
            else {
                $data = "No employees found";
                return $response->withJson($data, 400);
            }
        }
        else {
            $message = "Please log in to view the employees list";
            return $response->withRedirect('/v1/admin/error');
        }
    }

    public function deleteEmployee(Request $request, Response $response, array $args){
        if ($this->session->get('admin_logged_in') == true) {
            $employee_id = $args['employee_id'];
            $sql_delete_employee = $this->db->prepare("DELETE FROM employee WHERE employee_id = ?");
            $result = $sql_delete_employee->execute([$employee_id]);

            if ($result == true){
                $message = "Employee deleted!";
                return $response->withRedirect('/v1/admin/employees');
            }
            else{
                $message = "Unable to delete employee";
                return $response->withJson($message, 500);
            }
        }
        else{
            $message = "Please log in to delete this employee";
            return $response->withRedirect('/v1/admin/error');
        }
    }

    public function searchEmployees(Request $request, Response $response, array $args){
        if ($this->session->get('admin_logged_in') == true) {
            $user = $request->getQueryParams();
            $keyword = $user['employee-name'];

            $sql_search_keyword = $this->db->prepare
            ("SELECT * FROM employee WHERE first_name LIKE ? OR last_name LIKE ?");
            $result = $sql_search_keyword->execute(["%$keyword%", "%$keyword%"]);
            $count = 0;

            if ($result == true) {
                while ($row = $sql_search_keyword->fetch(PDO::FETCH_ASSOC)) {
                    $employees[] = $row;
                    $count++;
                }
                try {
                    echo $this->twig_admin->render("employees.twig", ['name' => $this->session->get('admin_first_name'),
                        'employees' => $employees, 'count' => $count]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            } else {
                $message = "No employees with that name found";
                return $response->withJson($message, 400);
            }
        }
        else {
            $message = "Please log in to delete this employee";
            return $response->withRedirect('/v1/admin/error');
        }
    }

    public function error(Request $request, Response $response){
        try {
            echo $this->twig_admin->render("error.twig");
        } catch (\Twig\Error\LoaderError $e) {
        } catch (\Twig\Error\RuntimeError $e) {
        } catch (\Twig\Error\SyntaxError $e) {
        }
    }
}