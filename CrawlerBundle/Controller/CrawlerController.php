<?php

namespace Magiseo\CrawlerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Cookie;

use ZipArchive;

use Sabberworm\CSS\Parser;

use Magiseo\CrawlerBundle\Entity\runningState;
use Magiseo\CrawlerBundle\Entity\webPage;

include_once (__DIR__.'/../Resources/Extern/simpleHtmlDom.php');
include_once (__DIR__.'/../Resources/Algo/scan_utils.php');
include_once (__DIR__.'/../Resources/Algo/Algorithme.class.php');

require_once 'Services/W3C/HTMLValidator.php';

$selectors = array();
$step = 0;

define('DEBUG', false);

class CrawlerController extends Controller
{
  // prepare l'instance d'un crawler dans la bdd pour le site donnee
  public function startCrawlerAction($id)
  {
    $user = $this->get('security.context')->getToken()->getUser();

    if (!$user)
      throw new AccessDeniedException();

    $em = $this->getDoctrine()->getManager();

    $rs = new runningState();
    if ($this->container->get('security.context')->isGranted('ROLE_USER'))
      $rs->setUser($user);


    if ($id != -1)
      {
	$archive = $em->getRepository('MagiseoSiteBundle:Archive')->find($id);
	$filename = explode('_', pathinfo($archive->getPath(), PATHINFO_FILENAME));
	$url = $filename[2];
	$date = $filename[1];
	$rs->setState('zip');

	$extractDir = $this->get('kernel')->getRootDir().'/../web/crawl/'.$date.'/'.$url;
	$date = explode('-', $date);
	$zip = new \ZipArchive();
	//error_log($archive->getAbsolutePath());
	if ($zip->open($archive->getAbsolutePath()) === true)
	  {
	    $zip->extractTo($extractDir);
	    $zip->close();
	  }
	else
	  return new Response('fail open zip archive', 400);
	$rs->setType('ZIP');
      }
    else
      {
	$rs->setType('URL');
	$rs->setState('checking url validity');
	$url = $this->get('request')->query->get('_url');

        // check website validity
        $url_check = parse_url($url);
        $url_check = 'http://'.(isset($url_check['host']) ? $url_check['host'] : $url_check['path']);

        $headers = $this->get_web_page($url_check);
        if ($headers)
	  {
            $httpCode = $headers['http_code'];
            if (($httpCode != '200') && ($httpCode != '301') && ($httpCode != '302')) // if x != 200 || x != 301 || x != 302 -> never worked
	      return new JsonResponse(array('msg' => 'Le site web indiqu&eacute; n\'a pas pu &ecirc;tre joint.<br/>Veuillez indiquer un site web joignable.'
					    ,'value' => '')
				      , 400);
	  }
	else
	  {
            return new JsonResponse(array('msg' => 'Le site web indiqu&eacute; n\'a pas pu &ecirc;tre joint.<br/>Veuillez indiquer un site web joignable.',
					  'value' => '')
				    , 400);
	  }
      }

    $url = parse_url($url);
    $url = 'http://'.(isset($url['host']) ? $url['host'] : $url['path']);
    $baseUrl = $url;

    $rs->setUrl($baseUrl);
    $rs->setDate(new \DateTime(isset($date) ? ($date[2].'-'.$date[1].'-'.$date[0]) : ''));
    $rs->setPageParsed(0);
    $rs->setPageFound(1);
    $rs->setState('');
    $rs->setEnd(false);
    $rs->setNumberErrors(0);

    $em->persist($rs);
    $em->flush();

    $options = array(CURLOPT_CUSTOMREQUEST  => "GET",        //set request type post or get
		     CURLOPT_POST           => false,        //set to GET
		     CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0',
		     CURLOPT_TIMEOUT_MS     => 1000,      // timeout on response
		     );

    $url = 'http://'.$this->getRequest()->getHost().$this->get('router')->generate('magiseo_crawler_launch', array('id' => $rs->getId()));

    $ch = curl_init($url);
    curl_setopt_array( $ch, $options );

    $rep = curl_exec($ch);
    curl_close($ch);

    //if (!$this->container->get('security.context')->isGranted('ROLE_USER'))
    //  $this->get('session')->set('magiseo_repport', $rs->getId());

    return new Response($rs->getId());
  }

  // renvoi l'etat actuel du crawler par rapport au site
  public function stateAction($id)
  {
    $user = $this->get('security.context')->getToken()->getUser();
    if (!$user)
      throw new AccessDeniedException();

    /*if ($id === null && !$this->container->get('security.context')->isGranted('ROLE_USER'))
      $id = $this->get('session')->get('magiseo_repport');
    else if ($this->container->get('security.context')->isGranted('ROLE_USER'))
    $id = $user->getLastRepport()->getId();*/

    if ($id <= 0)
      throw new AccessDeniedException();

    $em = $this->getDoctrine()->getManager();
    $rs = $em->getRepository('MagiseoCrawlerBundle:runningState')->find($id);
    if (!$rs)
      throw new AccessDeniedException();

    $pageParsed = '';
    if ($rs->getPageURLParsed())
      foreach (array_reverse($rs->getPageURLParsed()) as $url => $val)
	$pageParsed .= $url.'</br>';

    return new JsonResponse(array('id'		=> $rs->getId(),
				  'state'	=> $rs->getState(),
				  'parsed'	=> $rs->getPageParsed(),
				  'found'	=> $rs->getPageFound(),
				  'end'		=> $rs->getEnd(),
				  'page_parsed' => $pageParsed,
				  //'page found' => $rs->getPageURLFound(),
				  ));
  }

  // lance le crawler a partir de l'instance donnees en parametre
  public function launchCrawlerAction($id)
  {
    set_time_limit(0);

    $this->em = $this->getDoctrine()->getManager();
    $this->rs = $this->em->getRepository('MagiseoCrawlerBundle:runningState')->find($id);
    if (!$this->rs)
      throw new AccessDeniedException();

    $this->baseUrl = $this->rs->getUrl();
    $urlComponent = parse_url($this->baseUrl);
    $this->rootDir = $this->get('kernel')->getRootDir().'/../web/crawl/'.$this->rs->getDate()->format('d-m-y').'/'.$urlComponent['host'];

    if ($this->rs->getType() == 'URL')
      {
	//error_log('to parse: '.$this->baseUrl);
	$this->robots = $this->loadRobotsTxt();

	$this->cssFound = array();
	$this->jsFound = array();
	$this->urlStackDone = array();
	$this->urlStack = array($this->baseUrl => array('url' => $this->baseUrl, 'depth' => 0));

	//error_log($this->rootDir);
	if (!file_exists($this->rootDir))
	  mkdir($this->rootDir, 0777, true);

	global $step, $selectors;

	$this->rs->setState("getting web pages and optimize html");
	$this->em->flush();

	while (!empty($this->urlStack))
	  {
	    $url = array_shift($this->urlStack);
	    if (DEBUG) error_log('start url: '.$url['url']);
	    $sp = $this->startPageParsing($url);
	    $this->rs->setPageParsed($this->rs->getPageParsed() + 1);
	    $this->rs->addPageURLParsed($url['url'], $sp);
	    $this->em->flush();
	    if (DEBUG) error_log('end url: '.$url['url']);
	  }
      }
    else
      $this->scanDirectory($this->rootDir, true);

    $this->rs->setState("css optimization");
    $this->em->flush();

    $this->scanDirectory($this->rootDir, false);

    $this->rs->setState("SEO optimization");
    $this->em->flush();
    // ICI JULIEN!

    //$algo = new \Algorithme('mysql');
    //$algo->execute();
    //$ret = $algo->rapport(false);

    //error_log(print_r($ret, true));

    $this->rs->setEnd(true);
    $this->em->flush();

    //error_log('over');
    return new Response();
  }

  public function glob_recursive($pattern, $flags = 0)
  {
    $files = glob($pattern, $flags);
    foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir)
      {
	$files = array_merge($files, $this->glob_recursive($dir.'/'.basename($pattern), $flags));
      }
    return $files;
  }

  // CSS OPTIMIZATION
  private function scanDirectory($dir, $parseHtml = false)
  {
    //error_log('scan directory');
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $files = glob($dir.'/*');
    $cssFiles = $this->glob_recursive($dir.'/*.css');
    if (!$parseHtml)
      {
	$i = 0;
	foreach ($cssFiles as $file)
	    if ($this->isCssFile($file))
	      $i++;
	$this->rs->setPageParsed(0);
	$this->rs->setPageFound($i);
	$this->em->flush();
	$i = 0;
	foreach ($cssFiles as $file)
	  {
	    if (!$this->isCssFile($file))
	      continue;

	    //error_log($file);
	    //$this->rs->setState('css optimization: '.$file);
	    //$this->em->flush();
	    $wp = $this->em->getRepository('MagiseoCrawlerBundle:webPage')->findOneBy(array('filename' => $file));
	    if (!$wp && false)
	      {
		$wp = new webPage();

		$ar = explode('/', $this->rootDir);
		$host = end($ar);
		$wp->setUrl('http://'.$host.str_replace($this->rootDir, '', $file));
		$wp->setFilename($file);
		$wp->setContent("");
		$this->em->persist($wp);

		//$this->rs->setPageParsed($this->rs->getPageParsed() + 1);
		//$this->rs->addPageURLParsed(str_replace($this->rootDir, '', $file), array('page_size' => filesize($file), 'depth' => 0, 'status_code' => 200));
	      }
	    $oCssParser = new \Sabberworm\CSS\Parser(file_get_contents($file));
	    $oCssDocument = $oCssParser->parse();
	    $css = optimizeCss($oCssDocument, null, $file);

	    //$wp->addModification('css_errors', json_encode($css));

	    //$wp->setContent($oCssDocument->__toString());

	    file_put_contents($file, $oCssDocument->__toString());
	    $this->rs->setPageParsed(++$i);
	    $this->em->flush();
	  }
	return ;
      }

    foreach ($files as $file)
      {
	//error_log($file);
	if (is_dir($file))
	  $this->scanDirectory($file);
	else if ($parseHtml && finfo_file($finfo, $file) == 'text/html')
	  {
	    $html = str_get_html(preg_replace('#<!--(.|\s)*?-->#', '', file_get_contents($file)));
	    $this->optimizeHtml(pathinfo($file), $html, str_replace($this->rootDir, '', $file));
	    $sp =  array('page_size' => filesize($file), 'depth' => 0, 'status_code' => 200);
	    $this->rs->setPageParsed($this->rs->getPageParsed() + 1);
	    $this->rs->addPageURLParsed(str_replace($this->rootDir, '', $file), $sp);
	  }
      }
  }

  // check if file is a css
  public function isCssFile($file)
  {
    return (pathinfo($file, PATHINFO_EXTENSION) == 'css');
  }

 //  // setting page stat
  private function setPageStat($page, $url)
  {
    if ($page['errno'] != 0)
      return ;

    $statPage['total_time'] = round($page['total_time'], 4); //round: numbers too long
    $statPage['pageSize']   = $page['size_download'];

    $statPage['depth'] = $url['depth'];

    $statPage['status_code'] = $page['http_code'];
    if ($page['redirect_count'] > 0)
      {
	$statPage['status_code'] = '301/302';
	$statPage['redirect_url'] = $page['url'];
	if ($url['depth'] == 0)
	  {
	    $this->baseUrl = $page['url'];
	    $this->urlStackDone[$page['url']] = '1';
	  }
      }
    return $statPage;
  }

  // optimize html
  private function optimizeHtml($file, $html, $url)
  {
    global $selectors;
    global $currentDirectory;

    if (DEBUG) error_log('current directory: '.$file['dirname']);
    $currentDirectory = $file['dirname'];

    $modifications = array();
    $modifications['export_css'] = exportCss($file['dirname'], $file['basename'], $html);
    $modifications['delete_deprecated_balise'] = deleteDeprecatedTags($file['dirname'], $file['basename'], $html);
    reduceTitleNumber($file['basename'], $html);
    optimizeKeywords($file['dirname'], $html);

    error_log('file:'.$file['dirname'].'/'.$file['basename']);

    $v = new \Services_W3C_HTMLValidator();
    $w3c = $v->validateFragment(html_entity_decode($html->__toString()));

    if ($w3c && !$w3c->isValid())
      foreach ($w3c->errors as $e) {
	$modifications['w3c'][] = array('line' => $e->line, 'message' => $e->message);
      }

    $wp = $this->em->getRepository('MagiseoCrawlerBundle:webPage')->findOneBy(array('url' => $url));
    if (!$wp)
      {
	$wp = new webPage();
	$wp->setUrl($url);
	$this->em->persist($wp);
      }

    if (DEBUG) error_log('start saving all modifications');
    foreach ($modifications as $key => $values)
      if (!empty($values))
	foreach ($values as $k => $val)
	  foreach ($val as $v)
	  $wp->addModification($key.'_'.$k, $v);
    if (DEBUG) error_log('over');

    $wp->setFilename($file['dirname'].'/'.$file['basename']);
    $wp->setContent($html->__toString());
    $this->em->flush();

    if (DEBUG) error_log('saving informations');

    //error_log('put in file: '.$file['dirname'].'/'.$file['basename']);
    if (file_exists($file['dirname'].'/'.$file['basename']) && is_dir($file['dirname'].'/'.$file['basename']))
      file_put_contents($file['dirname'].'/'.$file['basename'].'.file', $html);
    else
      file_put_contents($file['dirname'].'/'.$file['basename'], $html);

    file_put_contents($file['dirname'].'/'.$file['basename'].'.obj', serialize($html));

    if (DEBUG) error_log('saving in selectors');

    $selectors[]['filename'] = $file['dirname'].'/'.$file['basename'];
    var_dump('Root Dir = ' . $rootDir);
    $selectors[count($selectors) - 1]['includes'] = getCssIncluded($this->rootDir, $html);
    $selectors[count($selectors) - 1]['includes'][] = $file['dirname'].'/css/'.$file['filename'].'.magiseo.css';
  }

  // getting html page/css and apply hmlt parsing
  private function startPageParsing($url)
  {
    //error_log('get: '.$url['url']);
    $page = $this->get_web_page($url['url']);

    if (DEBUG) error_log('type: '.$page['content_type']."\n".'status: '.$page['http_code']);
    if (!preg_match('/^text\/html.*$/', $page['content_type']))
      return ;

    $this->urlStackDone[$url['url']] = '1';
    $statPage = $this->setPageStat($page, $url);

    if (DEBUG) error_log($url['url'].' => '.$statPage['status_code']);

    $urlComponent = parse_url($page['url']);
    if ($page['http_code'] == '200')
      {
	try {
	  $html = str_get_html(preg_replace('#<!--(.|\s)*?-->#', '', $page['content']));

	  if ($html)
	    {
	      $this->findAllCss($html,  $urlComponent);
	      //$this->findAllJs($html, $urlComponent);

	      $statPage['linksOut'] = $this->findAllLinks($html, ($url['depth'] + 1), $urlComponent);

	      $file = $this->rootDir.'/'.(!isset($urlComponent['path']) || substr($urlComponent['path'], 1) == '' ? 'index' : substr($urlComponent['path'], 1));

	      if (DEBUG) error_log($file);
	      $file = pathinfo($file);
	      if (!isset($file['basename']) && empty($file['basename']) || ($file['basename'] == '/') || ($file['basename'] == '.'))
		$file['basename'] = 'index';
	      if (!file_exists($file['dirname']))
		mkdir($file['dirname'], 0777, true);

	      if (DEBUG) error_log('starting optimize html');
	      $this->optimizeHtml($file, $html, $page['url']);
	      if (DEBUG) error_log('ending optimize html');
	    }
	}
	catch (Exception $e) {
	  error_log('FAIL: parse page: '.$e->getMessage());
	}
      }

    $this->urlStackDone[$url['url']] = '';
    return $statPage;
  }

  // find all css in html and download them
  private function findAllCss($html, $urlComponent)
  {
    try {
      foreach ($html->find('link') as $css)
	{
	  if (isset($css->rel) && $css->rel == 'stylesheet' &&
	      !empty($css->href) &&
	      !isset($this->cssFound[$css->href]))
	    {
	      $url = $this->getFullURL($css->href);
	      if ($url !== null)
		{
		  $cssPage = $this->get_web_page($url['fullUrl']);
		  if ($cssPage['http_code'] == 200)
		    {
		      $uc = parse_url($cssPage['url']);

		      $file = $this->rootDir.'/'.(substr($uc['path'], 1) == '' ? 'index' : substr($uc['path'], 1));
		      $file = pathinfo($file);
		      if (!file_exists($file['dirname']))
			mkdir($file['dirname'], 0777, true);

		      $wp = $this->em->getRepository('MagiseoCrawlerBundle:webPage')->findOneBy(array('url' => $cssPage['url']));
		      if (!$wp)
			{
			  $wp = new webPage();
			  $wp->setUrl($cssPage['url']);
			  $this->em->persist($wp);
			}

		      //error_log($cssPage['url'].' - '.$file['dirname'].'/'.$file['basename']);
		      $wp->setFilename($file['dirname'].'/'.$file['basename']);
		      $wp->setContent($cssPage['content']);
		      $this->em->flush();

		      file_put_contents($file['dirname'].'/'.$file['basename'], $cssPage['content']);
		      $this->cssFound[$css->href] = '1';
		    }
		}
	    }
	}
    }
    catch (Exception $e) {
      error_log('fail getting css: '.$e->getMessage());
    }
  }

  // find all JS in html and download them
  private function findAllJs($html, $urlComponent)
  {
    foreach ($html->find('script') as $js)
      {
	if (isset($js->src) &&
	    !isset($this->jsFound[$js->src]))
	  {
	    $url = $this->getFullURL($js->src);
	    if ($url === null)
	      $url = array('fullUrl' => $js->src);

	    $jsPage = $this->get_web_page($url['fullUrl']);
	    if ($jsPage['http_code'] == 200)
	      {
		$this->jsFound[$js->src] = '1';
	      }
	  }
      }
  }

  // find all autorise links in html and download them
  private function findAllLinks($html, $depth, $urlComponent)
  {
    if (DEBUG) error_log('start of findAllLinks');
    $linksOut = array();
    try
      {
	foreach ($html->find('a') as $e)
	  {
	    if (empty($e->href))
	      continue;
	    if ($e->href[0] == '#' || (substr($e->href, 0, 2) == '//'))
	      continue;
	    $url = $this->getFullURL(preg_replace('/#.*/i', '', $e->href));
	    if (DEBUG) error_log('findAllLinks getFullUrl: '.$url['fullUrl']);
	    if ($url === null)
	      continue;

	    if (!isset($this->urlStack[$url['fullUrl']]) && !isset($this->urlStackDone[$url['fullUrl']]) &&
		!$this->robots->isDisallowed($url['path']) &&
		$this->checkExtensions($url['fullUrl']))
	      {
		$this->urlStack[$url['fullUrl']] = array('url' => $url['fullUrl'], 'depth' => $depth);
		$this->rs->addPageURLFound($url['fullUrl']);
		$this->rs->setPageFound($this->rs->getPageFound() + 1);
		$linksOut[] = $url['fullUrl']; // mal placer car ne prend que les liens autoriser et non parser...
	      }
	  }
      }
    catch (Exception $e) {
      error_log('fail getting all links: '.$e->getMessage());
    }
    if (DEBUG) error_log('end of findAllLinks');
    return $linksOut;
  }

  // take a partial url and return the full one
  public function getFullURL($href)
  {
    if (DEBUG) error_log('get full url in: '.$href);
    $urlComponent = parse_url(preg_replace('/^\.\//', '', $href));
    $burlC = parse_url($this->baseUrl);

    if (isset($urlComponent['scheme']) && !preg_match('/^http.?/', $urlComponent['scheme']))
      return null;
    if (isset($urlComponent['host']) && isset($burlC['host']) &&
	$urlComponent['host'] != $burlC['host'])
      return null;

    if (isset($urlComponent['path'])) {
	$urlComponent['path'] =
	    preg_replace('#^(\/)?(\.\.\/)*(.*)$#',
			 '${1}${3}', $urlComponent['path']);
    }
    $href = (isset($urlComponent['scheme']) ?
	     $urlComponent['scheme'] : $burlC['scheme']).
	'://'.$burlC['host'].
	(isset($urlComponent['path']) ?
	 ($urlComponent['path'][0] != '/' ? '/' : '').$urlComponent['path'] : '');

    $normalized = preg_replace('#\p{C}+|^\./#u', '', $href);
    // Path remove self referring paths ("/./").
    $normalized = preg_replace('#/\.(?=/)|^\./|\./$#', '', $normalized);

    // Regex for resolving relative paths
    $regex = '#\/*[^/\.]+/\.\.#Uu';

    while (preg_match($regex, $normalized)) {
      $normalized = preg_replace($regex, '', $normalized);
    }

    if (preg_match('#/\.{2}|\.{2}/#', $normalized)) {
      error_log('Path is outside of the defined root, path: [' . print_r($href, true) . '], resolved: [' . print_r($normalized, true) . ']');
      return array('fullUrl' => $this->baseUrl, 'path' => '');
    }

    $url = trim($normalized, '\\/');
    if (DEBUG) error_log('get full url out: '.$url);

    $urlComponent = parse_url($url);

    return array('fullUrl' => $url,
		 'path' => (isset($urlComponent['path']) ? $urlComponent['path'] : ''));
  }

  // true => if extension is possibly a web page/ false => if extension matches a video/image/pdf/archive/...
  public function checkExtensions($url)
  {
    return !preg_match('/.*\.(css|gz|iso|flv|mpeg|qdd|dwg|pub|aif|pps|bmp|'.
		       'wmvb|m4a||ai|cdr|eps|sitx|sit|wma|psd|mpg|jpg|jpeg|png|tif|gif|avi|'.
		       'wmv|webm|mov|xvid|mkv|flv|rmvb|doc|xls|xlsx|docx|ppt|zip|rar|'.
		       'exe|run|sh|7z|pdf|odt|mp3|ogv|ogg|mp4|aac|mpeg4|riff|wav|bwf|aiff|'.
		       'tar|tar\.gz|caf|flac|alac|ac3)$/i', $url);
  }

  // load robots.txt if available
  private function loadRobotsTxt()
  {
    $robots = $this->get_web_page($this->baseUrl.'/robots.txt');
    if ($robots['errno'] != 0)
      return new Response($robots['errmsg'], 400);

    return new robotsTxtParser($robots['content']);
  }

  // curl call on web page
  private function get_web_page($url)
  {
    if (DEBUG) error_log('getting: '.$url);

    $user_agent='Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';
    $options = array(CURLOPT_CUSTOMREQUEST  => "GET",        //set request type post or get
		     CURLOPT_POST           => false,        //set to GET
		     CURLOPT_USERAGENT      => $user_agent, //set user agent
		     CURLOPT_RETURNTRANSFER => true,     // return web page
		     CURLOPT_HEADER         => false,    // don't return headers
		     CURLOPT_FOLLOWLOCATION => true,     // follow redirects
		     CURLOPT_ENCODING       => "",       // handle all encodings
		     CURLOPT_AUTOREFERER    => true,     // set referer on redirect
		     CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
		     CURLOPT_TIMEOUT        => 120,      // timeout on response
		     CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
		     );

    $ch      = curl_init( $url );
    curl_setopt_array( $ch, $options );
    $content = curl_exec( $ch );

    $err     = curl_errno( $ch );
    $errmsg  = curl_error( $ch );
    $header  = curl_getinfo( $ch );
    curl_close( $ch );

    $header['errno']   = $err;
    $header['errmsg']  = $errmsg;
    $header['content'] = $content;

    if (DEBUG && $err) error_log('CURL ERROR['.$err.']: '.$errmsg);

    if ($err == 7)
      {
	sleep(5);
	$this->get_web_page($url);
      }
    return $header;
  }
}
