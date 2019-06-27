<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use utility\Session;
require_once dirname(__FILE__) . "/../v1/model/Employee.php";
require_once dirname(__FILE__) . "/../v1/model/Employer.php";
require_once dirname(__FILE__) . "/../v1/library/Session.php";
require_once dirname(__FILE__) . "/../v1/controller/EmployeeController.php";
require_once dirname(__FILE__) . "/../v1/controller/EmployerController.php";
require '../vendor/autoload.php';

$app = new Slim\App();
$container = $app->getContainer();

$container['Session'] = function () {
    $session = new Session();
    return $session;
};

$container['Employee'] = function () {
    $employee = new Employee();
    return $employee;
};

$container['Employer'] = function () {
    $employer = new Employer();
    return $employer;
};

$container['EmployeeController'] = function ($container) {
    $session = $container->get('Session');
    $employee = $container->get('Employee');
    $employee_controller = new EmployeeController($session, $employee);
    return $employee_controller;
};

$container['EmployerController'] = function ($container) {
    $session = $container->get('Session');
    $employer = $container->get('Employer');
    $employer_controller = new EmployerController($session, $employer);
    return $employer_controller;
};

require_once dirname(__FILE__) . "/../v1/routes.php";

// Run app
$app->run();

