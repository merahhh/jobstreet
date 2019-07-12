<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use Slim\Router;
use utility\Session;
require_once dirname(__FILE__) . "/../v1/model/Employee.php";
require_once dirname(__FILE__) . "/../v1/model/Employer.php";
require_once dirname(__FILE__) . "/../v1/library/Session.php";
require_once dirname(__FILE__) . "/../v1/controller/Home.php";
require_once dirname(__FILE__) . "/../v1/controller/EmployeeController.php";
require_once dirname(__FILE__) . "/../v1/controller/EmployerController.php";
require '../vendor/autoload.php';

$loader = new \Twig\Loader\FilesystemLoader(__DIR__);
$twig = new \Twig\Environment($loader);

$app = new Slim\App(['settings' => ['displayErrorDetails' => true]]);

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

$container['Home'] = function ($container) {
    $session = $container->get('Session');
    $view = $container->get('View');
    $home = new Home($view, $session);
    return $home;
};

$container['EmployeeController'] = function ($container) {
    $session = $container->get('Session');
    $employee = $container->get('Employee');
    $view = $container->get('View');
    $router = new Router();
    $employee_controller = new EmployeeController($session, $employee, $view, $router);
    return $employee_controller;
};

$container['EmployerController'] = function ($container) {
    $session = $container->get('Session');
    $employer = $container->get('Employer');
    $employer_controller = new EmployerController($session, $employer);
    return $employer_controller;
};

$container['View'] = function ($container) {
    $templatesPath = __DIR__.'/tpl';
    $view = new \Slim\Views\Twig($templatesPath, [
        'cache' => false
    ]);

    // Instantiate and add Slim specific extension
    $router = $container->get('router');
    $uri = \Slim\Http\Uri::createFromEnvironment(new \Slim\Http\Environment($_SERVER));
    $view->addExtension(new \Slim\Views\TwigExtension($router, $uri));

    return $view;
};

require_once dirname(__FILE__) . "/../v1/routes.php";
//echo $app->getContainer()->get('router')->pathFor('login.employee');

// Run app
$app->run();



