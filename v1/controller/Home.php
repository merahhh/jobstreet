<?php
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class Home
{
    protected $view, $session, $twig_user, $twig_anon;

    public function __construct($view, $session)
    {
        $this->view = $view;
        $this->session = $session;
        $loader = new FilesystemLoader(__DIR__ . '/../../public/tpl/employee');
        $this->twig_user = new Environment($loader, ['debug' => true]);
        $this->twig_user->addExtension(new \Twig\Extension\DebugExtension());

        $loader = new FilesystemLoader(__DIR__ . '/../../public/tpl/anonymous');
        $this->twig_anon = new Environment($loader, ['debug' => true]);
    }

    public function index(){
        if ($this->session->get('logged_in') == true){
            try {
                echo $this->twig_user->render("home.twig", ['name' => $this->session->get('first_name')]);
            } catch (\Twig\Error\LoaderError $e) {
            } catch (\Twig\Error\RuntimeError $e) {
            } catch (\Twig\Error\SyntaxError $e) {
            }
        }
        else {
            try {
                echo $this->twig_user->render("index.twig");
            } catch (\Twig\Error\LoaderError $e) {
            } catch (\Twig\Error\RuntimeError $e) {
            } catch (\Twig\Error\SyntaxError $e) {
            }
        }
    }

    public function applications(){
        try {
            echo $this->twig_user->render("applications.twig", ['name' => $this->session->get('first_name'),
                'last_name' => $this->session->get('last_name'), 'applications' => $this->session->get('applications'),
                'count' => $this->session->get('count')]);
        } catch (\Twig\Error\LoaderError $e) {
            echo "error";
        } catch (\Twig\Error\RuntimeError $e) {
            echo "error2";
        } catch (\Twig\Error\SyntaxError $e) {
            echo "error3";
        }
    }

    public function about(){
        if ($this->session->get('logged_in') == true){
            try {
                echo $this->twig_user->render("about.twig");
            } catch (\Twig\Error\LoaderError $e) {
                echo "error";
            } catch (\Twig\Error\RuntimeError $e) {
                echo "error2";
            } catch (\Twig\Error\SyntaxError $e) {
                echo "error3";
            }
        }
        else {
            try {
                echo $this->twig_anon->render("a_about.twig");
            } catch (\Twig\Error\LoaderError $e) {
            } catch (\Twig\Error\RuntimeError $e) {
            } catch (\Twig\Error\SyntaxError $e) {
            }
        }
    }

//    public function viewAllVacancies(){
//        if ($this->session->get('logged_in') == true){
//            try {
//                echo $this->twig_user->render("vacancies.twig", ['name' => $this->session->get('first_name'),
//                    'last_name' => $this->session->get('last_name'), 'vacancies' => $this->session->get('vacancies'),
//                    'count' => $this->session->get('count')]);
//            } catch (\Twig\Error\LoaderError $e) {
//            } catch (\Twig\Error\RuntimeError $e) {
//            } catch (\Twig\Error\SyntaxError $e) {
//            }
//        }
//        else {
//            try {
//                echo $this->twig_anon->render("a_vacancies.twig", ['vacancies' => $this->session->get('vacancies'),
//                    'count' => $this->session->get('count')]);
//            } catch (\Twig\Error\LoaderError $e) {
//            } catch (\Twig\Error\RuntimeError $e) {
//            } catch (\Twig\Error\SyntaxError $e) {
//            }
//        }
//    }
}