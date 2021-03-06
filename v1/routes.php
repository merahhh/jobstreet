<?php
use Slim\App;
date_default_timezone_set('Asia/Kuala_Lumpur');
$loader = new \Twig\Loader\FilesystemLoader(__DIR__);
$twig = new \Twig\Environment($loader);

/*--------------------------------routes-------------------------------------*/

$app->get('/', Home::class . ':index');

$app->get('/about', Home::class . ':about');

$app->group('/v1', function (App $app) {

    $app->group('/employee', function (App $app){
        #login error page
        $app->get('/error', EmployeeController::class . ':error');

        #error page
        $app->get('/page_error', EmployeeController::class . ':pageError');

        #register employee
        $app->post('/register', EmployeeController::class . ':registerEmployee');

        #login employee
        $app->post('/login', EmployeeController::class . ':loginEmployee');

        #view profile employee
        $app->get('/profile', EmployeeController::class . ':viewProfileEmployee');

        #open edit profile page
        $app->get('/profile/{id}/edit', Home::class . ':editProfileEmployee');
        #save edit profile employee education
        $app->post('/profile/edit/education', EmployeeController::class . ':editProfileEducation');
        #save edit profile employee experience
        $app->post('/profile/edit/experience', EmployeeController::class . ':editProfileExperience');
        #save edit profile employee skills
        $app->post('/profile/edit/skills', EmployeeController::class . ':editProfileSkills');
        #save edit profile employee language
        $app->post('/profile/edit/language', EmployeeController::class . ':editProfileLanguage');
        #save edit profile employee about
        $app->post('/profile/edit/about', EmployeeController::class . ':editProfileAboutMe');

        #apply for job
        $app->post('/vacancies/{vacancy_id}/applied', EmployeeController::class . ':applyVacancy'); #after pressing submit
        $app->get('/vacancies/{vacancy_id}/application', EmployeeController::class . ':vacancyApplication');
        //$app->get('/vacancies/apply', Home::class . ':applyVacancy');

        #view company profile
        $app->get('/vacancies/profile/{employer_id}', EmployeeController::class . ':employerProfile');

        #view job applications
        $app->get('/profile/vacancies_applied', EmployeeController::class . ':viewApplications');
        $app->get('/applications', EmployeeController::class . ':viewApplications');

        #logout employee
        $app->post('/logout', EmployeeController::class . ':logoutEmployee');
    });

    $app->group('/employer', function (App $app){
        #index
        $app->get('/', Home::class . ':employersIndex');

        #error page
        $app->get('/error', EmployerController::class . ':error');

        #error page
        $app->get('/page_error', EmployerController::class . ':pageError');

        #register employer
        $app->post('/register', EmployerController::class . ':registerEmployer');

        #login employer
        $app->post('/login', EmployerController::class . ':loginEmployer');

        #logout employer
        $app->post('/logout', EmployerController::class . ':logoutEmployer');

        #view profile employer
        $app->get('/profile', EmployerController::class . ':viewProfileEmployer');

        #edit profile employer
        $app->post('/profile/edit', EmployerController::class . ':editProfileEmployer');

        #add vacancy
        $app->post('/vacancy/add', EmployerController::class . ':addVacancy');

        #edit vacancy
        $app->post('/vacancy/{vacancy_id}/edit', EmployerController::class . ':editVacancy');

        #delete vacancy
        $app->post('/vacancy/{vacancy_id}/delete', EmployerController::class . ':deleteVacancy');

        #view posted vacancies
        $app->get('/vacancy/all', EmployerController::class . ':viewEmployerVacancies');

        #view posted vacancy details
        $app->get('/vacancy/{vacancy_id}', EmployerController::class . ':viewFullVacancy');

        #view applicants vacancy list
        $app->get('/applicants/vacancy/all', EmployerController::class . ':viewVacanciesForApplicants');

        #view vacancy applicants
        $app->get('/applicants/vacancy/{vacancy_id}/view', EmployerController::class . ':viewVacancyApplicants');

        #set application status
        $app->post('/applicants/vacancy/{application_id}/status', EmployerController::class . ':setApplicationStatus');

        #view applicant details
        $app->get('/applicants/vacancy/{vacancy_id}/{employee_id}', EmployerController::class . ':viewApplicantProfile');
    });

    $app->group('/vacancies', function (App $app){
        #view vacancies
        $app->get('/all', EmployeeController::class . ':viewVacancies');

        #search vacancies
        $app->get('/search', EmployeeController::class . ':search');

        #view vacancy details
        $app->get('/{vacancy_id}', EmployeeController::class . ':viewFullVacancy');
    });

    $app->group('/admin', function (App $app){
        #index
        $app->get('/', AdminController::class . ':index');

        #error
        $app->get('/error', AdminController::class . ':error');

        #error page
        $app->get('/page_error', AdminController::class . ':pageError');

        #register
        $app->post('/register', AdminController::class . ':registerAdmin');

        #login admin
        $app->post('/login', AdminController::class . ':loginAdmin');

        #logout admin
        $app->post('/logout', AdminController::class . ':logoutAdmin');

        #view vacancies
        $app->get('/vacancies', AdminController::class . ':viewAllVacancies');

        #search vacancies
        $app->get('/vacancies/search', AdminController::class . ':searchVacancies');

        #view vacancy details
        $app->get('/vacancies/{vacancy_id}', AdminController::class . ':viewVacancyDetails');

        #delete vacancies
        $app->post('/vacancies/{vacancy_id}/delete', AdminController::class . ':deleteVacancy');

        #view employers
        $app->get('/employers', AdminController::class . ':viewAllEmployers');

        #search employer
        $app->get('/employers/search', AdminController::class . ':searchEmployers');

        #view employer profile
        $app->get('/employers/{employer_id}', AdminController::class . ':viewEmployerProfile');

        #delete employer
        $app->post('/employers/{employer_id}/delete', AdminController::class . ':deleteEmployer');

        #view employees
        $app->get('/employees', AdminController::class . ':viewAllEmployees');

        #search employee
        $app->get('/employees/search', AdminController::class . ':searchEmployees');

        #view employee profile
        $app->get('/employees/{employee_id}', AdminController::class . ':viewEmployeeProfile');

        #delete employee
        $app->post('/employees/{employee_id}/delete', AdminController::class . ':deleteEmployee');
    });
});



