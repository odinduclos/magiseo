<?php

namespace Magiseo\UserBundle\Handler;

use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface,
    Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface,
    Symfony\Component\Security\Core\Exception\AuthenticationException,
    Symfony\Component\Security\Core\Authentication\Token\TokenInterface,
    Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Routing\RouterInterface,
    Symfony\Component\Routing\Router;
use Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\HttpFoundation\JsonResponse,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\Request;


class Authentification implements AuthenticationSuccessHandlerInterface, AuthenticationFailureHandlerInterface
{
  protected $router;
  protected $security;
  protected $userManager;
  protected $service_container;

  public function __construct(RouterInterface $router, SecurityContext $security, $userManager, $service_container)
  {
    $this->router = $router;
    $this->security = $security;
    $this->userManager = $userManager;
    $this->service_container = $service_container;
  }

  public function onAuthenticationSuccess(Request $request, TokenInterface $token)
  {
    if ($request->isXmlHttpRequest())
      {
	$response = new JsonResponse(array('success' => true));
	$response->headers->set('Content-Type', 'application/json');
	return $response;
      }
    else
      {
//	$request->getSession()->getFlashBag()->set('error', $exception->getMessage());
	$url = $this->router->generate('fos_user_security_login');

	return new RedirectResponse($url);
      }

    return new RedirectResponse($this->router->generate('magiseo_site_homepage'));
  }

  public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
  {
    if ($request->isXmlHttpRequest())
      {
	$response = new JsonResponse(array('success' => false,
					   'message' => $exception->getMessage()));

	$response->headers->set('Content-Type', 'application/json');
	return $response;
      }
    return new Response();
  }
}