<?php
error_reporting(E_ALL^E_NOTICE);
use Slim\Http\Response as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Router;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class EmployeeController
{
    protected $employee, $session, $db, $view, $router, $twig;

    public function __construct($session, $employee, $view){
        $this->employee = $employee;
        $this->session = $session;
        $this->db = $this->employee->getConn();
        $loader = new FilesystemLoader(__DIR__ . '/../../public/tpl/employee');
        $this->twig = new Environment($loader, ['debug' => true]);
        $this->twig->addExtension(new \Twig\Extension\DebugExtension());
    }

    public function __get($name){
        // TODO: Implement __get() method.
        return $this->value[$name];
    }

    # Function to generate unique alpha numeric code
    public function generateUniqueEmployeeID($len = 5){
        $randomString = substr(MD5(time()),$len);
        return $randomString;
    }

    public function generateUniqueEducationID($len = 8){
        $randomString = substr(MD5(time()), $len);
        return $randomString;
    }

    public function generateUniqueApplicantID($len = 5){
        $randomString = substr(MD5(time()), $len);
        return $randomString;
    }

    public function registerEmployee(Request $request, Response $response){
        $user = $request->getParsedBody();
        $first_name = $user['first_name'];
        $last_name = $user['last_name'];
        $email = $user['email'];
        $contact = $user['contact_no'];
        $password = $user['password'];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) === false){
            $sql_get_info = $this->db->prepare("SELECT * FROM employee WHERE email = ?");
            $sql_get_info->execute(array($email));
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
            else {   #email doesn't already exist in DB, proceed
                $password = password_hash($password, PASSWORD_BCRYPT);
                $hash = md5(rand(0, 1000));
                $id = $this->generateUniqueEmployeeID();
                //$this->session->set('education_id', $this->generateRandomNumber());
                $education_id = $this->generateUniqueEducationID();

                $sql_register = $this->db->prepare("INSERT INTO employee 
                    (id, first_name, last_name, email, contact, password, hash) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $result = $sql_register->execute(array($id, $first_name, $last_name, $email, $contact, $password, $hash));

                if ($result == true) {
                    $sql_register_profile = $this->db->prepare("INSERT INTO employee_profile (id, education_id) VALUES 
                        (?,?)");
                    $result_profile = $sql_register_profile->execute(array($id, $education_id));

                    if ($result_profile == true){
                        $sql_register_education = $this->db->prepare("INSERT INTO employee_education (education_id, id) 
                        VALUES (?,?)");
                        $result_education = $sql_register_education->execute(array($education_id, $id));

                        if ($result_education == true){
                            $this->session->set('active', 1);
                            $message = "User account created successfully";
                            return $response->withRedirect('/');
                        }
                        else {
                            $message = "Profile education ID error.";
                            try {
                                echo $this->twig->render("page_error.twig", ['message' => $message]);
                            } catch (\Twig\Error\LoaderError $e) {
                            } catch (\Twig\Error\RuntimeError $e) {
                            } catch (\Twig\Error\SyntaxError $e) {
                            }
                        }
                    }
                    else {
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
                    $message = "Error, user not registered!";
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
            $message = "Invalid email entered!";
            try {
                echo $this->twig->render("page_error.twig", ['message' => $message]);
            } catch (\Twig\Error\LoaderError $e) {
            } catch (\Twig\Error\RuntimeError $e) {
            } catch (\Twig\Error\SyntaxError $e) {
            }
        }
    }

    public function loginEmployee(Request $request, Response $response){
        try{
            $user = $request->getParsedBody();
            $email = $user['email'];
            $password = $user['password'];
            $user = $this->employee->getInfoAssoc($email);

            if ($user == null){
                $data = 'Employee with that email does not exist.';
                try {
                    echo $this->twig->render("page_error.twig", ['message' => $data]);
                } catch (\Twig\Error\LoaderError $e) {
                    echo "a";
                } catch (\Twig\Error\RuntimeError $e) {
                    echo "b";
                } catch (\Twig\Error\SyntaxError $e) {
                    echo "c";
                }
            }
            else{
                if (password_verify($password, $user['password'])){    #if password is correct
                    $this->session->set('id', $user['id']);
                    $this->session->set('email', $user['email']);
                    $this->session->set('first_name', $user['first_name']);
                    $this->session->set('last_name', $user['last_name']);
                    $this->session->set('contact', $user['contact']);
                    //$this->session->set('active', $user['active']);

                    #this is how we'll know the user is logged in
                    $this->session->set('logged_in', true);
                    $this->session->set('is_login', false);
                    return $response->withRedirect('/');

                }
                else{    #if password is incorrect
                    $data = 'Incorrect password entered!';
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

    public function logoutEmployee(Request $request, Response $response){
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
            return $response->withRedirect('/');

        } catch (exception $e){
            $data = 'Oops, something went wrong!';
            try {
                echo $this->twig->render("page_error.twig", ['message' => $data]);
            } catch (\Twig\Error\LoaderError $e) {
            } catch (\Twig\Error\RuntimeError $e) {
            } catch (\Twig\Error\SyntaxError $e) {
            }
        }
    }

    public function viewProfileEmployee(Request $request, Response $response){
        if ($this->session->get('logged_in') == true) {
            $sql_get_profile = $this->db->prepare
                ("SELECT employee_profile.gender, employee_profile.experience, employee_profile.skills, 
                employee_profile.language, employee_profile.expected_salary, employee_profile.location, 
                employee_education.institute, employee_education.grad_month, employee_education.grad_year, 
                employee_education.qualification, employee_education.major,
                employee_education.grade FROM employee_profile INNER JOIN employee_education ON 
                employee_profile.education_id = employee_education.education_id WHERE employee_profile.id = ? ");
            $result = $sql_get_profile->execute(array($this->session->get('id')));
            //$result = $this->db->query($sql_get_profile);

            if ($result == true) {
                while ($row = $sql_get_profile->fetch(PDO::FETCH_ASSOC)) {
                    $data[] = $row;
                    $this->session->set('employee_profile', $data);
                }
                try {
                    echo $this->twig->render("profile.twig", ['name' => $this->session->get('first_name'),
                        'last_name' => $this->session->get('last_name'), 'ep' => $this->session->get('employee_profile'),
                        'id' => $this->session->get('id'), 'email' => $this->session->get('email')]);
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
            return $response->withRedirect('/v1/employee/error');
        }
    }

    public function editProfileEducation(Request $request, Response $response){
        if ($this->session->get('logged_in') == true) {
            $user = $request->getParsedBody();
            $institute = $user['edit-profile-uni'];
            $grad_month = $user['edit-profile-grad-month'];
            $grad_year = $user['edit-profile-grad-year'];
            $qualification = $user['edit-profile-qualification'];
            $major = $user['edit-profile-major'];
            $grade = $user['edit-profile-grade'];
            $id = $this->session->get('id');

            $sql_edit_education = $this->db->prepare
            ("UPDATE employee_education SET institute = ?, grad_month = ?, grad_year = ?, qualification = ?, major = ?, grade = ?
                WHERE id = ?");
            $result = $sql_edit_education->execute([$institute, $grad_month, $grad_year, $qualification, $major, $grade, $id]);

            if ($result == true){
                try {
                    echo $this->twig->render("edit_profile.twig", ['name' => $this->session->get('first_name'),
                        'last_name' => $this->session->get('last_name'), 'ep' => $this->session->get('employee_profile'),
                        'id' => $this->session->get('id')]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
            else{
                $message = "Unable to edit education.";
                try {
                    echo $this->twig->render("page_error.twig", ['message' => $message]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
        }
        else{
            return $response->withRedirect('/v1/employee/error');
        }
    }

    public function editProfileExperience(Request $request, Response $response){
        if ($this->session->get('logged_in') == true){
            $user = $request->getParsedBody();
            $experience = $user['edit-profile-experience'];
            $id = $this->session->get('id');

            $sql_edit_experience = $this->db->prepare
            ("UPDATE employee_profile SET experience = ? WHERE id = ?");
            $result = $sql_edit_experience->execute([$experience, $id]);

            if ($result == true){
                try {
                    echo $this->twig->render("edit_profile.twig", ['name' => $this->session->get('first_name'),
                        'last_name' => $this->session->get('last_name'), 'ep' => $this->session->get('employee_profile'),
                        'id' => $this->session->get('id')]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
            else{
                $message = "Unable to edit experience.";
                try {
                    echo $this->twig->render("page_error.twig", ['message' => $message]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
        }
        else{
            return $response->withRedirect('/v1/employee/error');
        }
    }

    public function editProfileSkills(Request $request, Response $response){
        if ($this->session->get('logged_in') == true) {
            $user = $request->getParsedBody();
            $skills = $user['edit-profile-skills'];
            $id = $this->session->get('id');

            $sql_edit_experience = $this->db->prepare
            ("UPDATE employee_profile SET skills = ? WHERE id = ?");
            $result = $sql_edit_experience->execute([$skills, $id]);

            if ($result == true){
                try {
                    echo $this->twig->render("edit_profile.twig", ['name' => $this->session->get('first_name'),
                        'last_name' => $this->session->get('last_name'), 'ep' => $this->session->get('employee_profile'),
                        'id' => $this->session->get('id')]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
            else{
                $message = "Unable to edit skills.";
                try {
                    echo $this->twig->render("page_error.twig", ['message' => $message]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
        }
        else{
            return $response->withRedirect('/v1/employee/error');
        }
    }

    public function editProfileLanguage(Request $request, Response $response){
        if ($this->session->get('logged_in') == true){
            $user = $request->getParsedBody();
            $language = $user['edit-profile-language'];
            $id = $this->session->get('id');

            $sql_edit_experience = $this->db->prepare
            ("UPDATE employee_profile SET language = ? WHERE id = ?");
            $result = $sql_edit_experience->execute([$language, $id]);

            if ($result == true){
                try {
                    echo $this->twig->render("edit_profile.twig", ['name' => $this->session->get('first_name'),
                        'last_name' => $this->session->get('last_name'), 'ep' => $this->session->get('employee_profile'),
                        'id' => $this->session->get('id')]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
            else{
                $message = "Unable to edit language.";
                try {
                    echo $this->twig->render("page_error.twig", ['message' => $message]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
        }
        else{
            return $response->withRedirect('/v1/employee/error');
        }
    }

    public function editProfileAboutMe(Request $request, Response $response){
        if ($this->session->get('logged_in') == true){
            $user = $request->getParsedBody();
            $first_name = $user['edit-profile-first-name'];
            $last_name = $user['edit-profile-last-name'];
            $gender = $user['edit-profile-gender'];
            $email = $user['edit-profile-email'];
            $contact = $user['edit-profile-contact-no'];
            $expected_salary = $user['edit-profile-expected-salary'];
            $work_location = $user['edit-profile-work-location'];
            $id = $this->session->get('id');

            $sql_edit_education = $this->db->prepare
            ("UPDATE employee, employee_profile SET employee.first_name = ?, employee.last_name = ?, employee.email = ?, 
                employee.contact = ?, employee_profile.gender = ?, employee_profile.expected_salary = ?, 
                employee_profile.location = ? WHERE employee.id = employee_profile.id AND employee_profile.id = ?");
            $result = $sql_edit_education->execute([$first_name, $last_name, $email, $contact, $gender, $expected_salary,
                $work_location, $id ]);

            if ($result == true){
                try {
                    echo $this->twig->render("edit_profile.twig", ['name' => $this->session->get('first_name'),
                        'last_name' => $this->session->get('last_name'), 'ep' => $this->session->get('employee_profile'),
                        'id' => $this->session->get('id')]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
            else{
                $message = "Unable to edit about me.";
                try {
                    echo $this->twig->render("page_error.twig", ['message' => $message]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
        }
        else{
            return $response->withRedirect('/v1/employee/error');
        }
    }

    public function viewVacancies(Request $request, Response $response){
        $this->checkVacanciesExpiryDate($request, $response);
        $sql_view_vacancies = "SELECT employer_id, vacancy_id, company_name, v_name, v_state, v_salary, v_desc, 
            v_closing_date FROM vacancies WHERE v_status = 1";
        $result = $this->db->query($sql_view_vacancies);
        $count = 0;

        if ($result == true) {
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $vacancies[] = $row;
                $count++;
            }

            if ($this->session->get('logged_in') == true){
                try {
                    echo $this->twig->render("vacancies.twig", ['name' => $this->session->get('first_name'),
                        'last_name' => $this->session->get('last_name'), 'vacancies' => $vacancies,
                        'count' => $count]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
            else {
                try {
                    echo $this->twig->render("a_vacancies.twig", ['vacancies' => $vacancies,
                        'count' => $count]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
        }
        else {
            $data = "No jobs found.";
            try {
                echo $this->twig->render("page_error.twig", ['message' => $data]);
            } catch (\Twig\Error\LoaderError $e) {
            } catch (\Twig\Error\RuntimeError $e) {
            } catch (\Twig\Error\SyntaxError $e) {
            }
        }
    }

    public function viewFullVacancy(Request $request, Response $response, array $args){
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
            if ($this->session->get('logged_in') == true){
                try {
                    echo $this->twig->render("vacancy_details.twig", ['name' => $this->session->get('first_name'),
                        'last_name' => $this->session->get('last_name'), 'vacancy_details' => $vacancy_details]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
            else {
                try {
                    echo $this->twig->render("a_vacancy_details.twig", ['name' => $this->session->get('first_name'),
                        'last_name' => $this->session->get('last_name'), 'vacancy_details' => $vacancy_details]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
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

    public function vacancyApplication(Request $request, Response $response, array $args){
        if ($this->session->get('logged_in') == true){
            $vacancy_id = $args['vacancy_id'];
            $sql_vacancy_details = $this->db->prepare
                ("SELECT vacancies.vacancy_id, vacancies.company_name, vacancies.v_name, vacancies.v_desc, vacancies.v_address, 
                vacancies.v_requirements, vacancies.v_position, vacancies.v_state, vacancies.v_salary, 
                vacancies.v_closing_date, employer_profile.* FROM vacancies INNER JOIN employer_profile on 
                employer_profile.employer_id = vacancies.employer_id WHERE vacancy_id = ?");
            $result = $sql_vacancy_details->execute(array($vacancy_id));

            if ($result == true) {
                while ($row = $sql_vacancy_details->fetch(PDO::FETCH_ASSOC)) {
                    $vacancy_application[] = $row;
                }
                try {
                    echo $this->twig->render("apply_vacancy.twig", ['name' => $this->session->get('first_name'),
                        'last_name' => $this->session->get('last_name'), 'vacancy_application' => $vacancy_application,
                        'email' => $this->session->get('email'), 'contact' => $this->session->get('contact')]);
                } catch (\Twig\Error\LoaderError $e) {
                    echo "error";
                } catch (\Twig\Error\RuntimeError $e) {
                    echo "error2";
                } catch (\Twig\Error\SyntaxError $e) {
                    echo "error3";
                }
            }
            else {      //query failed
                $data = "User data missing!";
                try {
                    echo $this->twig->render("page_error.twig", ['message' => $data]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
        }
        else {      //if not logged in
            return $response->withRedirect('/v1/employee/error');
        }
    }

    public function applyVacancy(Request $request, Response $response, array $args){
        if ($this->session->get('logged_in') == true) {
            $get_pitch = $request->getParsedBody();
            $pitch = $get_pitch['pitch'];
            $application_id = $this->generateUniqueApplicantID();
            $vacancy_id = $args['vacancy_id'];
            $employee_id = $this->session->get('id');
            $date_submitted = date("Y-m-d");

            $sql_apply_vacancy = $this->db->prepare
                ("INSERT INTO vacancy_applicants (application_id, vacancy_id, id, pitch, date_submitted) VALUES 
                    (?, ?, ?, ?, ?)");
            $result = $sql_apply_vacancy->execute(array($application_id, $vacancy_id, $employee_id, $pitch, $date_submitted));

            if ($result == true){
                $data = "Application sent! Please check your application status (under the 'My Applications' tab) again soon and be alert to avoid any missed contact attempts from the employer.";
                try {
                    echo $this->twig->render("vacancy_applied.twig", ['name' => $this->session->get('first_name'),
                        'last_name' => $this->session->get('last_name'), 'data' => $data]);
                } catch (\Twig\Error\LoaderError $e) {
                    echo "error";
                } catch (\Twig\Error\RuntimeError $e) {
                    echo "error2";
                } catch (\Twig\Error\SyntaxError $e) {
                    echo "error3";
                }
            }
            else{
                $data = "Application for job not sent!";
                try {
                    echo $this->twig->render("page_error.twig", ['message' => $data]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
        }
        else {
            return $response->withRedirect('/v1/employee/error');
        }
    }

    public function viewApplications(Request $request, Response $response, array $args){
        if ($this->session->get('logged_in') == true) {
            $count = 0;
            $employee_id = $this->session->get('id');
            $sql_view_applications = $this->db->prepare
                ("SELECT vacancy_id FROM vacancy_applicants WHERE id = ?");
            $result = $sql_view_applications->execute(array($employee_id));

            if ($result == true){
                $sql_vacancy_info = $this->db->prepare
                    ("SELECT vacancies.vacancy_id, vacancies.employer_id, vacancies.company_name, vacancies.v_name, vacancy_applicants.date_submitted, 
                        vacancy_applicants.application_status FROM vacancy_applicants INNER JOIN vacancies 
                        ON vacancy_applicants.vacancy_id = vacancies.vacancy_id WHERE vacancy_applicants.id = ?");
                $result = $sql_vacancy_info->execute(array($employee_id));

                if ($result == true) {
                    while ($row = $sql_vacancy_info->fetch(PDO::FETCH_ASSOC)) {
                        $applications[] = $row;
                        $count++;
                    }
                    $this->session->set('count', $count);
                    try {
                        echo $this->twig->render("applications.twig", ['name' => $this->session->get('first_name'),
                            'last_name' => $this->session->get('last_name'), 'applications' =>$applications,
                            'count' => $count]);
                    } catch (\Twig\Error\LoaderError $e) {
                        echo "error";
                    } catch (\Twig\Error\RuntimeError $e) {
                        echo "error2";
                    } catch (\Twig\Error\SyntaxError $e) {
                        echo "error3";
                    }
                }
                else {
                    $data = "No applications found. Apply for a job now!";
                    try {
                        echo $this->twig->render("page_error.twig", ['message' => $data]);
                    } catch (\Twig\Error\LoaderError $e) {
                    } catch (\Twig\Error\RuntimeError $e) {
                    } catch (\Twig\Error\SyntaxError $e) {
                    }
                }
            }
        }
        else {
            return $response->withRedirect('/v1/employee/error');
        }
    }

    public function search(Request $request, Response $response, array $args){
        $user = $request->getQueryParams();
        $keyword = $user['job-keyword'];
        //$location = $user['job-location'];
        //$salary = $user['job-salary'];

        $sql_search_keyword = $this->db->prepare
        ("SELECT * FROM vacancies WHERE v_name LIKE ? OR v_requirements LIKE ? OR v_state LIKE ? OR v_salary LIKE ?");
        $result = $sql_search_keyword->execute(["%$keyword%", "%$keyword%", "%$keyword%", "%$keyword%"]);
        $count = 0;

        if ($result == true){
            while ($row = $sql_search_keyword->fetch(PDO::FETCH_ASSOC)) {
                $vacancies_search[] = $row;
                $count++;
            }
            if ($this->session->get('logged_in') == true){
                try {
                    echo $this->twig->render("vacancies.twig", ['name' => $this->session->get('first_name'),
                        'last_name' => $this->session->get('last_name'), 'vacancies' => $vacancies_search, 'count' => $count]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
            else {
                try {
                    echo $this->twig->render("a_vacancies.twig", ['vacancies' => $vacancies_search, 'count' => $count]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
        }
        else{
            $message = "No jobs found that match your criteria :(";
            try {
                echo $this->twig->render("page_error.twig", ['message' => $message]);
            } catch (\Twig\Error\LoaderError $e) {
            } catch (\Twig\Error\RuntimeError $e) {
            } catch (\Twig\Error\SyntaxError $e) {
            }
        }
    }

    public function employerProfile(Request $request, Response $response, array $args){
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
            if($this->session->get('logged_in') == true){
                try {
                    echo $this->twig->render("employer_profile.twig", ['name' => $this->session->get('first_name'),
                        'last_name' => $this->session->get('last_name'), 'employer' => $employer, 'count' => $count]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }
            else {
                try {
                    echo $this->twig->render("a_employer_profile.twig", ['employer' => $employer, 'count' => $count]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
            }

        }
        else {
            $data = "No data found, edit profile now";
            try {
                echo $this->twig->render("page_error.twig", ['message' => $data]);
            } catch (\Twig\Error\LoaderError $e) {
            } catch (\Twig\Error\RuntimeError $e) {
            } catch (\Twig\Error\SyntaxError $e) {
            }
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

    protected function checkVacanciesExpiryDate(Request $request, Response $response){
        $date = date('Y-m-d');
        $sql_update_status = $this->db->prepare("UPDATE vacancies SET v_status = 0 WHERE vacancy_id = ?");
        $sql_check_date = "SELECT vacancy_id, v_closing_date, v_status FROM vacancies WHERE v_status = 1";
        $result = $this->db->query($sql_check_date);
        $date_count = 0;

        if ($result == true){
            while ($row = $result->fetch(PDO::FETCH_ASSOC)){
                $v_date[] = $row;
                $date_count++;
            }
            for ($i = 0; $i < $date_count; $i++){
                if(strtotime($date) > strtotime($v_date[$i]['v_closing_date'])){
                    $result_update_status = $sql_update_status->execute([$v_date[$i]['vacancy_id']]);
                    if ($result_update_status == true){
                        $message = "Vacancy status updated";
                    }
                    else {
                        $message = "Oops, unable to update vacancy active status";
                        return $response->withJson($message, 400);
                    }
                }
                else{
                    $message = "Vacancy not yet expired";
                }
            }
        }
        else{
            $message = "Problem fetching data";
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