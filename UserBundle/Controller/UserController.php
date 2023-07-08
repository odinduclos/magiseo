<?php

namespace Magiseo\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Magiseo\UserBundle\Entity\Archive;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserController extends Controller {

//    public function loginAction() {
//        return $this->render('MagiseoUserBundle::Security::login.html.twig');
//    }

    public function profilAction() {
        $user = $this->get('security.context')->getToken()->getUser();
        if (!$user 
            || !$this->container->get('security.context')
                ->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw new NotFoundHttpException("Pas connecté.");
        }
        $id = $user->getId();

        
        $repository = $this->getDoctrine()->getRepository('MagiseoUserBundle:User');
        $userInDB = $repository->find($id);
        
        $reports = $userInDB->getRepports();
        
        return $this->render('MagiseoUserBundle::profil.html.twig'
//                ,  array( "reports" => var_dump($reports))
                );
    }

    public function changeCompanyAction() {
        return $this->changeField("company", $this->getPostData());
    }

    public function changePasswordAction() {
        
        $request = $this->getRequest();
        $pass1 = $request->request->get('First');
        $pass2 = $request->request->get('Second');
        if ($pass1 != $pass2) {
            return new JsonResponse(array(
                "msg" => "Les deux mots de passe ne sont pas identiques.",
                "value" => "")
                    , 400);
        }
        
        return $this->changeField("password", $pass1);
    }

    public function changeEmailAction() {
        $data = $this->getPostData();
        
        $emailConstraint = new EmailConstraint();
        $emailConstraint->message = 'Veuillez entrer un email valide';
        $errors = $this->get('validator')->validateValue(
                $data, $emailConstraint);

        if (count($errors) != 0) {
                return new JsonResponse(array(
                    "msg" => $errors[0]->getMessage(),
                    "value" => $data)
                        , 400);
        }
        return $this->changeField("email", $data);
    }

    private function changeField($field, $data) {
        $code = 200;
        $resp = array();
        $resp["msg"] = "";
        $resp["value"] = "";
        if (!$this->getRequest()->isMethod('POST')
            || !isset($data)) {
            $code = 400;
            $resp["msg"] = "Requête erronée. Veuillez réessayer.";
            $resp["value"] = $data;
        } else {
            if ($data == "") {
                $code = 400;
                $resp["msg"] = "Veuillez renseigner le champs requis.";
                $resp["value"] = $data;
            } else {
                $user = $this->container->get('security.context')
                                ->getToken()->getUser();
                if (false === $this->get('security.context')->isGranted(
                                'IS_AUTHENTICATED_FULLY')) {
                    $code = 401;
                    $resp["msg"] = "Veuillez vous connecter pour accéder à cette "
                            . "fonctionnalité.";
                } else {
                    $res = $this->updateGoodField($field, $user, $data, $resp);
                    if ($res)
                        $this->get('fos_user.user_manager')->updateUser($user);
                    else
                        $code = "400";

                }
            }
        }
        
        
        
        return new JsonResponse($resp, $code);
    }

    private function updateGoodField($field, $user, $data, &$resp) {
        switch ($field) {
            case "company" :
                $user->setCompany($data);
                break;
            case "email" :
                try {
                    $user->setEmail($data);
                }
                catch (PDOException $e) {
                    $resp["msg"] = $e->getMessage();
                    $resp["value"] = "";
                    return false;
                }
                break;
            case "password" :
                $user->setPlainPassword($data);
                break;
            default:
                break;
        }
        $resp["msg"] = "Ok";
        $resp["value"] = $data;
        return true;
    }
    
    public function getPostData() {
        $request = $this->getRequest();
        return $request->request->get('data');
    }
    
}