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

    public function __construct($session, $employee, $view, Router $router){
        $this->employee = $employee;
        $this->view = $view;
        $this->session = $session;
        $this->db = $this->employee->getConn();
        //$this->router = $router;
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

        //Check newly generated Code exist in DB table or not.
//        $query = $this->db->prepare("SELECT * FROM employee WHERE id = ?");
//        $query->execute(array($randomString));
//        $resultCount = count($query->fetchAll());
//
//        if($resultCount > 0){
//            //IF code is already exist then function will call it self until unique code has been generated and inserted in Db.
//            $this->generateRandomNumber();
//        }
//        else{
//            //Unique generated code will be inserted in DB.
//            return $randomString;
//        }
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
                //return $response->withJson($message, 501);
                return $response->withRedirect('/');
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
                            //return $response->withJson($message, 201);
                            return $response->withRedirect('/');
                        }
                        else {
                            $message = "Profile education ID error";
                            //return $response->withJson($message, 400);
                            return $response->withRedirect('/');
                        }
                    }
                    else {
                        $message = "Profile ID error";
                        //return $response->withJson($message, 400);
                        return $response->withRedirect('/');
                    }
                }
                else{
                    $message = "User not registered";
                    //return $response->withJson($message, 500);
                    return $response->withRedirect('');
                }
            }
        }
        else{
            $message = "Invalid email";
            return $response->withJson($message, 400);
        }
    }

    public function loginEmployee(Request $request, Response $response){
        try{
            $allPostPutVars = $request->getParsedBody();
            $email = $allPostPutVars['email'];
            $password = $allPostPutVars['password'];
            //$email = json_decode($request->getBody())->email;
            $user = $this->employee->getInfoAssoc($email);

            if ($user == null){
                $data = 'Employee does not exist';
                return $response->withRedirect('/');
            }
            else{
                if (password_verify($password, $user['password'])){    #if password is correct
                    $this->session->set('id', $user['id']);
                    $this->session->set('email', $user['email']);
                    $this->session->set('first_name', $user['first_name']);
                    $this->session->set('last_name', $user['last_name']);
                    //$this->session->set('active', $user['active']);

                    #this is how we'll know the user is logged in
                    $this->session->set('logged_in', true);
                    $data = 'Successfully logged in';
                    $response->withRedirect('/v1/home');
                    //return $response->withJson($data, 200);
                    //return $this->view->render($response, 'home.twig');
                    //return $this->view->render($response,
                    //    'home.twig', ['name' => $this->session->get('first_name')]);
                    return $response->withRedirect('/');

                }
                else{    #if password is incorrect
                    $data = 'Incorrect password';
                    //return $this->view->render($response, 'home.twig');
                    return $response->withRedirect('/');
                }
            }
        }
        catch (exception $e){
            $data = 'Oops, something went wrong!';
            return $response->withRedirect('/');
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
            $message = 'Successfully logged out!';
            //return $response->withJson($message, 200);
            return $response->withRedirect('/');

        } catch (exception $e){
            $data = 'Oops, something went wrong!';
            return $response->withJson($data, 300);
        }
    }

    public function viewProfileEmployee(Request $request, Response $response){
        if ($this->session->get('logged_in') == true) {
            $sql_get_profile = $this->db->prepare
                ("SELECT employee_profile.gender, employee_profile.age, employee_profile.nationality, 
                employee_profile.experience, employee_profile.skills, employee_profile.language,
                employee_profile.expected_salary, employee_profile.location, employee_education.institute, 
                employee_education.graduation_time, employee_education.qualification, employee_education.major,
                employee_education.grade FROM employee_profile INNER JOIN employee_education ON 
                employee_profile.education_id = employee_education.education_id WHERE employee_profile.id = ?");
            $result = $sql_get_profile->execute(array($this->session->get('id')));
            //$result = $this->db->query($sql_get_profile);

            if ($result == true) {
                while ($row = $sql_get_profile->fetch(PDO::FETCH_ASSOC)) {
                    $data[] = $row;
                    //echo json_encode($data);
                    $this->session->set('employee_profile', $data);
                }
                try {
                    echo $this->twig->render("profile.twig", ['name' => $this->session->get('first_name'),
                        'last_name' => $this->session->get('last_name'), 'ep' => $this->session->get('employee_profile') ]);
                } catch (\Twig\Error\LoaderError $e) {
                } catch (\Twig\Error\RuntimeError $e) {
                } catch (\Twig\Error\SyntaxError $e) {
                }
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

    public function editProfileEmployee(Request $request, Response $response){
        if ($this->session->get('logged_in') == true) {
            $gender = json_decode($request->getBody())->gender;
            $age = json_decode($request->getBody())->age;
            $nationality = json_decode($request->getBody())->nationality;
            $experience = json_decode($request->getBody())->experience;
            $skills = json_decode($request->getBody())->skills;
            $language = json_decode($request->getBody())->language;
            $expected_salary = json_decode($request->getBody())->expected_salary;
            $location = json_decode($request->getBody())->location;
            $resume = json_decode($request->getBody())->resume;
            $institute = json_decode($request->getBody())->institute;
            $graduation_time = json_decode($request->getBody())->graduation_time;
            $qualification = json_decode($request->getBody())->qualification;
            $major = json_decode($request->getBody())->major;
            $grade = json_decode($request->getBody())->grade;

            //$this->session->set('education_id', $this->generateUniqueEmployeeID());
            //$education_id = $this->session->get('education_id');
            $id = $this->session->get('id');

            #update employee_profile table
            $sql_edit_profile = $this->db->prepare
                ("UPDATE employee_profile SET gender = ?, age = ?, nationality = ?, experience = ?, skills = ?, 
                language = ?, expected_salary = ?, location = ?, resume = ? WHERE id = ?");
            $result = $sql_edit_profile->execute
                ([$gender, $age, $nationality, $experience, $skills, $language, $expected_salary, $location, $resume, $id]);

            #if insert into employee_profile successful
            if ($result == true){
                #update employee_education table
                $sql_edit_education = $this->db->prepare
                    ("UPDATE employee_education SET institute = ?, graduation_time = ?, qualification = ?, 
                    major = ?, grade = ? WHERE id = ?");
                $result_education = $sql_edit_education->execute
                    ([$institute, $graduation_time, $qualification, $major, $grade, $id]);

                #if insert into employee_education successful
                if ($result_education == true){
                    $message = "Profile edited!";
                    return $response->withJson($message, 200);
                }
                else {
                    $message = "Unable to edit education profile";
                    return $response->withJson($message, 500);
                }
            }
            else {
                $message = "Unable to edit profile";
                return $response->withJson($message, 500);
            }
        }
        else {
            $message = "Please log in to edit your profile";
            return $response->withJson($message, 403);
        }
    }

    public function viewVacancies(Request $request, Response $response){
        $sql_view_vacancies = "SELECT company_name, v_name, v_location, v_salary, v_desc, v_closing_date FROM vacancies";
        $result = $this->db->query($sql_view_vacancies);
        $count = 0;

        if ($result == true) {
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $vacancies[] = $row;
                $count++;
                //$this->session->set('vacancies', $vacancies);
            }
            //$this->session->set('count', $count);
            try {
                echo $this->twig->render("vacancies.twig", ['name' => $this->session->get('first_name'),
                    'last_name' => $this->session->get('last_name'), 'vacancies' => $vacancies, 'count' => $count]);
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

    public function viewFullVacancy(Request $request, Response $response, array $args){
        $vacancy_id = $args['vacancy_id'];

        $sql_vacancy_details = $this->db->prepare
            ("SELECT company_name, v_name, v_desc, v_requirements, v_position, v_location, v_salary 
                FROM vacancies WHERE vacancy_id = ?");
        $result = $sql_vacancy_details->execute(array($vacancy_id));

        if ($result == true) {
            while ($row = $sql_vacancy_details->fetch(PDO::FETCH_ASSOC)) {
                $data[] = $row;
                //echo json_encode($data);
            }
            return $response->withStatus(200);
        }
        else {
            $data = "No data found for this job";
            return $response->withJson($data, 400);
        }
    }

    public function applyVacancy(Request $request, Response $response, array $args){
        if ($this->session->get('logged_in') == true) {
            $pitch = json_decode($request->getBody())->pitch;
            $applicant_id = $this->generateUniqueApplicantID();
            $vacancy_id = $args['vacancy_id'];
            $employee_id = $this->session->get('id');
            $date_submitted = date("F d, Y");

            $sql_apply_vacancy = $this->db->prepare
                ("INSERT INTO vacancy_applicants (application_id, vacancy_id, id, pitch, date_submitted) VALUES 
                    (?, ?, ?, ?, ?)");
            $result = $sql_apply_vacancy->execute(array($applicant_id, $vacancy_id, $employee_id, $pitch, $date_submitted));

            if ($result == true){
                $data = "Application sent! Please check your application status again soon and be alert 
                    to avoid any missed contact attempts from the employer.";
                return $response->withJson($data, 400);
            }
            else{
                $data = "Application for job not sent!";
                return $response->withJson($data, 500);
            }
        }
        else {
            $message = "Please log in to apply for this job";
            return $response->withJson($message, 403);
        }
    }

    public function viewApplications(Request $request, Response $response, array $args){
        if ($this->session->get('logged_in') == true) {
            $count = 0;
            $employee_id = $this->session->get('id');
            $sql_view_applications = $this->db->prepare
                ("SELECT vacancy_id FROM vacancy_applicants WHERE id = ?");
            $result = $sql_view_applications->execute(array($employee_id));
            //$vacancy_id = $sql_view_applications->fetchAll(PDO::FETCH_COLUMN);

            if ($result == true){
                $sql_vacancy_info = $this->db->prepare
                    ("SELECT vacancies.company_name, vacancies.v_name, vacancy_applicants.date_submitted, 
                        vacancy_applicants.application_status FROM vacancy_applicants INNER JOIN vacancies 
                        ON vacancy_applicants.vacancy_id = vacancies.vacancy_id WHERE vacancy_applicants.id = ?");
                $result = $sql_vacancy_info->execute(array($employee_id));

                if ($result == true) {
                    while ($row = $sql_vacancy_info->fetch(PDO::FETCH_ASSOC)) {
                        $applications[] = $row;
                        $count++;
                        $this->session->set('applications', $applications);
                    }
                    $this->session->set('count', $count);
                    //return $response->withJson($applications, 200);
                    return $response->withRedirect('/v1/employee/applications');
//                    try {
//                        echo $this->twig->render("applications.twig", ['name' => $this->session->get('first_name'),
//                            'last_name' => $this->session->get('last_name'), 'applications' => $this->session->get('applications') ]);
//                    } catch (\Twig\Error\LoaderError $e) {
//                        echo "error";
//                    } catch (\Twig\Error\RuntimeError $e) {
//                        echo "error2";
//                    } catch (\Twig\Error\SyntaxError $e) {
//                        echo "error3";
//                    }
                }
                else {
                    $data = "No applications found. Apply for a job now!";
                    return $response->withJson($data, 400);
                }
            }
        }
        else {
            $message = "Please log in to view your job applications";
            return $response->withJson($message, 403);
        }
    }
}