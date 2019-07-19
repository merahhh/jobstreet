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
        #register employee
        $app->post('/register', EmployeeController::class . ':registerEmployee');

        #login employee
        $app->post('/login', EmployeeController::class . ':loginEmployee');

        #view profile employee
        $app->get('/profile', EmployeeController::class . ':viewProfileEmployee');

        #edit profile employee
        $app->put('/profile/edit', EmployeeController::class . ':editProfileEmployee');

        #apply for job
        $app->post('/vacancies/{vacancy_id}/applied', EmployeeController::class . ':applyVacancy'); #after pressing submit
        $app->get('/vacancies/{vacancy_id}/application', EmployeeController::class . ':vacancyApplication');
        //$app->get('/vacancies/apply', Home::class . ':applyVacancy');

        #view job applications
        $app->get('/profile/vacancies_applied', EmployeeController::class . ':viewApplications');
        $app->get('/applications', Home::class . ':applications');

        #logout employee
        $app->post('/logout', EmployeeController::class . ':logoutEmployee');
    });

    $app->group('/employer', function (App $app){
        #register employer
        $app->post('/register', EmployerController::class . ':registerEmployer');

        #login employer
        $app->post('/login', EmployerController::class . ':loginEmployer');

        #logout employer
        $app->post('/logout', EmployerController::class . ':logoutEmployer');

        #view profile employer
        $app->get('/profile', EmployerController::class . ':viewProfileEmployer');

        #edit profile employer
        $app->put('/profile/edit', EmployerController::class . ':editProfileEmployer');

        #add vacancy
        $app->post('/vacancy/add', EmployerController::class . ':addVacancy');

        #edit vacancy
        $app->put('/vacancy/{vacancy_id}/edit', EmployerController::class . ':editVacancy');

        #delete vacancy
        $app->delete('/vacancy/{vacancy_id}/delete', EmployerController::class . ':deleteVacancy');

        #view posted vacancies
        $app->get('/vacancy/all', EmployerController::class . ':viewEmployerVacancies');

        #view vacancy applicants
        $app->get('/vacancy/{vacancy_id}/view_applicants', EmployerController::class . ':viewVacancyApplicants');

        #view applicant details
        $app->get('/vacancy/{vacancy_id}/applicants/{employee_id}', EmployerController::class . ':viewApplicantDetails');
    });

    $app->group('/vacancies', function (App $app){
        #view vacancies
        $app->get('/all', EmployeeController::class . ':viewVacancies');
        #$app->get('/view/all', Home::class . ':viewVacancies');

        #view vacancy details
        $app->get('/{vacancy_id}', EmployeeController::class . ':viewFullVacancy');
    });
});



