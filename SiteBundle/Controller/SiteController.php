<?php

namespace Magiseo\SiteBundle\Controller;

use Exception;
use Magiseo\SiteBundle\Entity\Archive;
use Magiseo\SiteBundle\Entity\ContactForm;
use Magiseo\SiteBundle\Form\ContactFormType;
use Swift_Message;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class SiteController extends Controller
{

    public function indexAction()
    {
        return $this->render('MagiseoSiteBundle:Site:index.html.twig');
    }

    public function contactAction()
    {
        $formSent = false;
        $contact = new ContactForm;
        $formContact = $this->createForm(new ContactFormType, $contact);

        $request = $this->getRequest();
        if ($request->isMethod('POST'))
        {
            $formSent = true;
            $data = $request->request->get('magiseo_sitebundle_contactform');

            $mail = Swift_Message::newInstance()
                    ->setFrom('contactmagiseo@gmail.com')
                    ->setTo('eip@magiseo.com')
                    ->setBody("Un utilisateur cherche à nous contacter. mail: " . $data['email'] . ". Voici son message: " . $data['content'] . " Son numéro de tel: " . $data['phoneNumber']);
            $this->get('mailer')->send($mail);
        }

        return $this->render('MagiseoSiteBundle:Site:contact.html.twig', array('form' => $formContact->createView(), 'formSent' => $formSent));
    }

    public function teamAction()
    {
        return $this->render('MagiseoSiteBundle:Site:team.html.twig');
    }

    public function formulesAction()
    {
        return $this->render('MagiseoSiteBundle:Site:formules.html.twig');
    }

    public function diagnosticAction()
    {
        $archive = new Archive();
        $form = $this->createFormBuilder($archive)->add('url')
                ->add('file', 'file', array('label' => 'Archive'))
                ->getForm();

        // form already filled and post
        if ($this->getRequest()->isMethod('POST'))
        {
            $form->bind($this->getRequest());
            // response array
            $resp = array('status' => 'OK',
                'msg' => 'L\'archive est bien téléchargée.',
                'id' => '-1');
            if ($form->isValid())
            {
                // check website validity
                $url_check = parse_url($archive->getUrl());
                $url_check = 'http://' . (isset($url_check['host']) ? $url_check['host'] : $url_check['path']);
                $headers = @get_headers($url_check);
                if ($headers == true)
                {
                    $httpCode = substr($headers[0], 9, 3);
                    if ($httpCode == '200' || $httpCode == '301' || $httpCode != '302')
                    {
                        $em = $this->getDoctrine()->getManager();

                        // getting current user (registered or not)
                        $user = $this->container->get('security.context')->getToken()->getUser();
                        $username = 'GUEST';
                        if (true === $this->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY'))
                        {
                            $username = $user->getUsername();
                            $archive->setUser($user);
                        }
                        // uploads user's archive
                        $archive->upload($username);

                        $em->persist($archive);
                        $em->flush();
                        $resp['id'] = $archive->getId();
                    }
                    else
                    {
                        $resp['status'] = 'KO';
                        $resp['msg'] = $httpCode . ' Le site web indiqu&eacute; n\'a pas pu &ecirc;tre joint.<br/>Veuillez indiquer un site web joignable.';
                    }
                }
                else
                {
                    $resp['status'] = 'KO';
                    $resp['msg'] = 'Le site web indiqu&eacute; n\'a pas pu &ecirc;tre joint.<br/>Veuillez indiquer un site web joignable.';
                }
            }
            else
            {
                $resp['status'] = 'KO';

                $errors = $this->get('validator')->validate($archive);
                $resp['msg'] = '';
                foreach ($errors as $error)
                    $resp['msg'] .= ucfirst(strtolower($error->getPropertyPath())) . ' : ' . $error->getMessage() . '<br>'; // the field that caused the error
            }
            return new JsonResponse($resp);
        }

        return $this->render('MagiseoSiteBundle:Site:diagnostic.html.twig', array('form' => $form->createView()));
    }

    public function diagnosticResultAction()
    {
        // TEST NICO HTML2PDF
        $content = "
            <page>
                <h1>Exemple d'utilisation 2 !!!</h1>
                <br>
                Ceci est un <b>exemple d'utilisation</b>
                de <a href='http://html2pdf.fr/'>HTML2PDF</a>.<br>
            </page>";

        //$html2pdf = $this->get('html2pdf')->get('P', 'A4', 'fr');
        //$html2pdf->WriteHTML($content);
        /* $pdfName = 'exemple-'
          . date("d_m_Y")
          . '.pdf';
          $html2pdf->Output($pdfName, 'F'); */

        return $this->render(
                        'MagiseoSiteBundle:Site:diagnosticResult.html.twig'
                        , array("pdfName" => "Test"/* $pdfName */));
    }

    public function alertesAction()
    {
        $user = $this->get('security.context')->getToken()->getUser();
        if (!$this->get('security.context')->isGranted('ROLE_USER'))
            throw new AccessDeniedHttpException();

        return $this->render('MagiseoSiteBundle:Site:alertes.html.twig', array('alertes' => $user->getNotifications()));
    }

    private function getErrors()
    {
        
    }

    public function rapportAction($id)
    {
        $rapportInfo = $this->getRapportResult($id);
        
        // finally, render web page with rapport and generated PDF
        return $this->render('MagiseoSiteBundle:Site:rapport.html.twig', array(
            'rapportContent'    => $rapportInfo->rapportContent,
            'websiteName'       => $rapportInfo->websiteName,
            'errorMsg'          => $rapportInfo->errorMsg,
            'rapportId'         => $id
        ));
    }

    private function getRapportResult($id)
    {
        $rapportInfos = new RapportInfos;
        $pageURLParsed = "";
        $errorMsg = "";
        $content = "";
        $websiteUrl = "";

        if ($id == null || $id <= 0)
        {
            $errorMsg = "Pas de site web sélectionné.";
        }
        else
        {
            // get repo: Running State
            $repoRunningState = $this->getDoctrine()->getRepository('MagiseoCrawlerBundle:runningState');
            if (empty($repoRunningState))
            {
                throw new Exception("Database not set up");
            }
            // get current site web info
            $runningState = $repoRunningState->find($id);
            if (empty($runningState))
            {
                $errorMsg = "Le site web sp&eacute;cifi&eacute; n'existe pas.";
            }
            else
            {
                // get "links" rapport
                $pageURLParsed = $runningState->getPageURLParsed();

                $pageURLFound = $runningState->getPageURLFound();

                // get website's name
                $websiteUrl = $runningState->getUrl();

                // get repo: Webpage
                $repoWebPage = $this->getDoctrine()->getRepository('MagiseoCrawlerBundle:webPage');

                $queryB = $repoWebPage->createQueryBuilder('wp');

                $WPresults = $queryB->select()
                                ->where('wp.url LIKE \'' . $websiteUrl . '%.css\'')
                                ->getQuery()->getResult();

                $results = array();
                $oneTabActivated = "";
                if (isset($WPresults))
                {
                    foreach ($WPresults as $webPage)
                    {
                        $modifs = $webPage->getModifications();
                        //$cssRes[$value] = $modifField;

                        if ($modifs != null && !empty($modifs))
                        {
                            foreach ($modifs as $modifType => $modifStr)
                            {
                                if ($modifType == "css_errors")
                                {
                                    foreach (json_decode($modifStr) as $type => $val)
                                    {
                                        if ($type == 'remove_selector')
                                            $str = '<strong>Sélecteurs inutilisés:</strong> ';
                                        else if ($type == 'merge_selectors')
                                            $str = '<strong>Sélecteur fusionnés:</strong> ';
                                        else if ($type == 'remove_rule')
                                            $str = '<strong>Règles supprimées:</strong> ';
                                        else
                                            $str = $type;

                                        foreach ($val as $n)
                                        {
                                            if (gettype($n) == 'array')
                                                foreach ($n as $rule)
                                                    $str .= $rule . ', ';
                                            else
                                                $str .= $n . ', ';
                                        }
                                        $results["linksOut"]["css"][str_replace($websiteUrl, "", $webPage->getUrl())][$type] = $str;
                                        if ($oneTabActivated == "")
                                            $oneTabActivated = "css";
                                    }
                                }
                                else
                                {
                                    $results["linksOut"]["html"][str_replace($websiteUrl, "", $webPage->getUrl())] = $modifStr;
                                    if ($oneTabActivated == "")
                                        $oneTabActivated = "html";
                                }
                            }
                        }
                    }
                }

                if (isset($pageURLParsed))
                {
                    foreach ($pageURLParsed as $url => $runStateArray)
                    {
                        if (!isset($runStateArray))
                            continue;

                        // total time > 5s
                        if (array_key_exists("total_time", $runStateArray))
                        {
                            $total_time = floatval($runStateArray["total_time"]);
                            if ($total_time > 5.0)
                            {
                                $results["total_time"][$url] = $runStateArray["total_time"];
                            }
                        }
                        // depth > 4
                        if (array_key_exists("depth", $runStateArray))
                        {
                            $depth = intval($runStateArray["depth"]);
                            if ($depth > 4)
                            {
                                $results["depth"][$url] = $runStateArray["depth"];
                                if ($oneTabActivated == "")
                                    $oneTabActivated = "depth";
                            }
                        }

                        // status_code != 200
                        if (array_key_exists("status_code", $runStateArray))
                        {
                            $status_code = intval($runStateArray["status_code"]);
                            if ($status_code != 200 && $status_code != 301 && $status_code != 302)
                            {
                                $results["status_code"][$runStateArray["status_code"]][] = str_replace(strtolower($websiteUrl), "", $url);
                                if ($oneTabActivated == "")
                                    $oneTabActivated = "links";
                            }
                        }

                        // links out
                        if (array_key_exists("linksOut", $runStateArray))
                        {
                            $repo = $this->getDoctrine()->getRepository('MagiseoCrawlerBundle:webPage');
                            foreach ($runStateArray["linksOut"] as $linkOut)
                            {
                                $webPage = $repo->findOneBy(array("url" => $linkOut));
                                if ($webPage != null && !empty($webPage))
                                {
                                    $modifs = $webPage->getModifications();
                                    if ($modifs != null && !empty($modifs))
                                    {
                                        foreach ($modifs as $modifType => $modifStr)
                                        {
                                            if ($modifType == "css_errors")
                                            {
                                                $results["linksOut"]["css"][$modifStr][] = str_replace(strtolower($websiteUrl), "", $linkOut);
                                                if ($oneTabActivated == "")
                                                    $oneTabActivated = "css";
                                            }
                                            else
                                            {
                                                $results["linksOut"]["html"][$modifStr][] = str_replace(strtolower($websiteUrl), "", $linkOut);
                                                if ($oneTabActivated == "")
                                                    $oneTabActivated = "html";
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                // first, render rapport for PDF and web page
                $content = $this->renderView('MagiseoSiteBundle:Site:diagnosticResult.html.twig', 
                        array(
                            "results" => $results, 
                            'oneTabActivated' => $oneTabActivated, 
                            'id' => $id));

            }
        }
        
        $rapportInfos->errorMsg = $errorMsg;
        $rapportInfos->rapportContent = $content;
        $rapportInfos->websiteName = $websiteUrl;
        
        return $rapportInfos;
    }
    
    // Ajax calls
    public function getRapportAsPdfAction()
    {
        $request = $this->getRequest();
        $id = $request->request->get('id');
        
        $rapportInfos = $this->getRapportResult($id);
        $resp = array(
            'status'    => 'OK',
            'msg'       => '',
            'pdfPath'   => '');
        
        if ($rapportInfos->errorMsg != "")
        {
            $resp['status'] = 'KO';
            $resp['msg'] = "id = [" . $id . "] " . $rapportInfos->errorMsg;
        }
        else
        {
        
            // get user's name
            $user = $this->container->get('security.context')->getToken()->getUser();
            $username = "GUEST";
            if (true === $this->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY'))
                $username = $user->getUsername();

            // create PDF from rapport
            //$html2pdf = new \Html2Pdf_Html2Pdf('P', 'A4', 'fr');
            $html2pdf = $this->get('html2pdf')->get('P', 'A4', 'fr');

            $toPDF0 = "<html><head>
                <style>
                fieldset { min-height: 400px; border:solid 1px black; padding:15px; width:80%; }
                legend {width:auto;padding:0 10px;border-bottom:none;margin-bottom: 0px;background-color:white;}
                </style>
            </head><body>";

            $toPDF1 = "<img src='bundles/magiseosite/images/magiseo_banner_smaller.png'>"
                    . "<h2>Rapport</h2>"
                    . "<h3>Site web diagnostiqu&eacute; : " . $rapportInfos->websiteName . "</h3>";

            $contentForPDF = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $rapportInfos->rapportContent);
            $contentForPDF = preg_replace('/<!--(.|\s)*?-->/', '', $contentForPDF);

            $toPDF2 = "</body></html>";

            set_time_limit(300);
            $pdfPath = $username . '-' . date("Y-m-d_H_i_s") . '.pdf';

            try
            {
                $html2pdf->WriteHTML($toPDF0 . $toPDF1 . $contentForPDF . $toPDF2);
                $html2pdf->Output($pdfPath, 'F');
                $resp['pdfPath'] = "/" . $pdfPath;
            }
            catch (Exception $ex)
            {
                $resp['status'] = 'KO';
                $resp['msg'] = $ex->getMessage();
            }
        }
        
        return new JsonResponse($resp, 200);
    }

}

class RapportInfos
{
    public $rapportContent;
    public $websiteName;
    public $errorMsg;
}