<?php
use Slim\App;
date_default_timezone_set('Asia/Kuala_Lumpur');

/*--------------------------------routes-------------------------------------*/

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

        #view profile employee
        $app->get('/profile', EmployerController::class . ':viewProfileEmployer');

        #edit profile employer
        $app->put('/profile/edit', EmployerController::class . ':editProfileEmployer');
    });
});
