<?php
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class Home
{
    protected $view, $session, $twig;

    public function __construct($view, $session)
    {
        $this->view = $view;
        $this->session = $session;
        $loader = new FilesystemLoader(__DIR__ . '/../../public/tpl/employee');
        $this->twig = new Environment($loader, ['debug' => true]);
        $this->twig->addExtension(new \Twig\Extension\DebugExtension());
    }

    public function index(){
        if ($this->session->get('logged_in') == true){
            try {
                echo $this->twig->render("home.twig", ['name' => $this->session->get('first_name')]);
            } catch (\Twig\Error\LoaderError $e) {
            } catch (\Twig\Error\RuntimeError $e) {
            } catch (\Twig\Error\SyntaxError $e) {
            }
        }
        else {
            try {
                echo $this->twig->render("index.twig");
            } catch (\Twig\Error\LoaderError $e) {
            } catch (\Twig\Error\RuntimeError $e) {
            } catch (\Twig\Error\SyntaxError $e) {
            }
        }
    }

    public function applications(){
        try {
            echo $this->twig->render("applications.twig", ['name' => $this->session->get('first_name'),
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
        try {
            echo $this->twig->render("about.twig");
        } catch (\Twig\Error\LoaderError $e) {
            echo "error";
        } catch (\Twig\Error\RuntimeError $e) {
            echo "error2";
        } catch (\Twig\Error\SyntaxError $e) {
            echo "error3";
        }
    }
}