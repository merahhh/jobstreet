<?php
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class Home
{
    protected $view, $session, $twig_user, $twig_anon, $twig_employer;

    public function __construct($view, $session)
    {
        $this->view = $view;
        $this->session = $session;
        $loader = new FilesystemLoader(__DIR__ . '/../../public/tpl/employee');
        $this->twig_user = new Environment($loader, ['debug' => true]);
        $this->twig_user->addExtension(new \Twig\Extension\DebugExtension());

        $loader = new FilesystemLoader(__DIR__ . '/../../public/tpl/employer');
        $this->twig_employer = new Environment($loader, ['debug' => true]);
        $this->twig_employer->addExtension(new \Twig\Extension\DebugExtension());
    }

    public function index(){
        if ($this->session->get('logged_in') == true){
            try {
                echo $this->twig_user->render("home.twig", ['name' => $this->session->get('first_name'),
                    'is_login' => $this->session->get('is_login'), 'email_exist' => $this->session->get('email_exist'),
                    'correct_pw' => $this->session->get('correct_pw')]);
            } catch (\Twig\Error\LoaderError $e) {
            } catch (\Twig\Error\RuntimeError $e) {
            } catch (\Twig\Error\SyntaxError $e) {
            }
        }
        else {
            try {
                echo $this->twig_user->render("a_index.twig", ['is_login' => $this->session->get('is_login'), 'email_exist' => $this->session->get('email_exist'),
                    'correct_pw' => $this->session->get('correct_pw'), 'data' => $this->session->get('data'),
                    'session' => $this->session]);
            } catch (\Twig\Error\LoaderError $e) {
            } catch (\Twig\Error\RuntimeError $e) {
            } catch (\Twig\Error\SyntaxError $e) {
            }
        }
    }

    public function about(){
        if ($this->session->get('logged_in') == true){
            try {
                echo $this->twig_user->render("about.twig", ['name' => $this->session->get('first_name'),
                    'last_name' => $this->session->get('last_name')]);
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
                echo $this->twig_user->render("a_about.twig");
            } catch (\Twig\Error\LoaderError $e) {
            } catch (\Twig\Error\RuntimeError $e) {
            } catch (\Twig\Error\SyntaxError $e) {
            }
        }
    }

    public function applyVacancy(){
        try {
            echo $this->twig_user->render("apply_vacancy.twig", ['name' => $this->session->get('first_name'),
                'last_name' => $this->session->get('last_name')]);
        } catch (\Twig\Error\LoaderError $e) {
            echo "error";
        } catch (\Twig\Error\RuntimeError $e) {
            echo "error2";
        } catch (\Twig\Error\SyntaxError $e) {
            echo "error3";
        }
    }

    public function editProfileEmployee(){
        if ($this->session->get('logged_in') == true){
            try {
                echo $this->twig_user->render("edit_profile.twig", ['name' => $this->session->get('first_name'),
                    'last_name' => $this->session->get('last_name'), 'ep' => $this->session->get('employee_profile'),
                    'email' => $this->session->get('email'), 'contact' => $this->session->get('contact')]);
            } catch (\Twig\Error\LoaderError $e) {
            } catch (\Twig\Error\RuntimeError $e) {
            } catch (\Twig\Error\SyntaxError $e) {
            }
        }
        else {
            try {
                echo $this->twig_user->render("a_index.twig");
            } catch (\Twig\Error\LoaderError $e) {
            } catch (\Twig\Error\RuntimeError $e) {
            } catch (\Twig\Error\SyntaxError $e) {
            }
        }
    }

#---------------------------- Employers ----------------------------------#

    public function employersIndex(){
        if ($this->session->get('logged_in') == true){
            try {
                echo $this->twig_employer->render("e_home.twig", ['name' => $this->session->get('company_name')]);
            } catch (\Twig\Error\LoaderError $e) {
            } catch (\Twig\Error\RuntimeError $e) {
            } catch (\Twig\Error\SyntaxError $e) {
            }
        }
        else {
            try {
                echo $this->twig_employer->render("e_index.twig");
            } catch (\Twig\Error\LoaderError $e) {
            } catch (\Twig\Error\RuntimeError $e) {
            } catch (\Twig\Error\SyntaxError $e) {
            }
        }
    }
}