<?php

namespace Magiseo\UserBundle\Controller;

use FOS\UserBundle\Controller\SecurityController as BaseController;

/**
 * Our own implementation of FOSUserBundle's SecurityController
 */
class SecurityController extends BaseController {

    protected function renderLogin(array $data) {

        // si la requête est en AJAX
        // on envoie juste le formulaire simple
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
                && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            $template = sprintf('MagiseoUserBundle:Security:login.html.twig');
        // si requête normale :
        // on envoie toute la page avec headers, menu etc.
        } else {
            $template = sprintf('MagiseoUserBundle:Security:loginPage.html.twig');
        }
        return $this->container->get('templating')->renderResponse($template, $data);
    }

}
