<?php
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Home
{
    public function index(){
        $loader = new FilesystemLoader(dirname(__DIR__, 2) . 'public/tpl');
        $twig = new Environment($loader);

        try {
            echo $twig->render("index.twig");
        } catch (\Twig\Error\LoaderError $e) {
        } catch (\Twig\Error\RuntimeError $e) {
        } catch (\Twig\Error\SyntaxError $e) {
        }
    }
}