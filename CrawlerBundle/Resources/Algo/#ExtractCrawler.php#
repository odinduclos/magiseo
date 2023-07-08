<?php

// changer les includes a terme pour ./simpleHtmlDom.php
include_once (__DIR__.'/simpleHtmlDom.php');
include_once (__DIR__.'/scan_utils.php');
include_once (__DIR__.'/Algorithme.class.php');
require_once (__DIR__.'/robotsTxtParser.php');

// GLOBALS
define('DEBUG', false);

// MAIN VARIABLES
$step		= 0;

$baseUrl	= null;
$rootDir	= '/home/site/';
$urlStackDone	= array();
$urlStack	= array();
$robot		= null;
$cssFound	= array();

// FUNCTIONS
function getWebPage($url)
{
  if (DEBUG) error_log('getting: '.$url);

  $user_agent = 'Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';
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

function loadRobotTXT($url)
{
  $robots = getWebPage($url.'/robots.txt');
  if ($robots['errno'] != 0)
    return null;

  return new robotsTxtParser($robots['content']);
}

function getPageStats($page, $depth)
{
  global $baseUrl;
  global $urlStackDone;

  $statPage['download_time'] = round($page['total_time'], 4); //round: numbers too long
  $statPage['size']   = $page['size_download'];

  $statPage['depth'] = $depth;

  $statPage['status_code'] = $page['http_code'];
  if ($page['redirect_count'] > 0)
    {
      $statPage['status_code'] = '301/302';
      $statPage['redirect_url'] = $page['url'];
      if ($depth == 0)
	{
	  $baseUrl = $page['url'];
	  $urlStackDone[$page['url']] = '1';
	}
    }
  return $statPage;
}

function getFullURL($href, $currentUC)
{
  global $baseUrl;

  $urlComponent = parse_url(preg_replace('/^\.\//', '', $href));
  $burlC = parse_url($baseUrl);

  if (isset($urlComponent['scheme']) && !preg_match('/^http.?/', $urlComponent['scheme']))
    return null;
  if (isset($urlComponent['host']) && isset($burlC['host']) &&
      $urlComponent['host'] != $burlC['host'])
    return null;

  $url =  $burlC['scheme'].'://'.$burlC['host'];
  if (isset($urlComponent['host']) && isset($urlComponent['scheme']))
    $url = $urlComponent['scheme'].'://'.$urlComponent['host'];
  else if (!isset($urlComponent['host']) && !isset($urlComponent['scheme']) &&
	   isset($urlComponent['path']) && !empty($urlComponent['path']) &&
	   $urlComponent['path'][0] != '/')
    $url = $currentUC['scheme'].'://'.$currentUC['host'];

  if (!empty($urlComponent['path']) && ($urlComponent['path'] != '/') && ($urlComponent['path'] != '#'))
    {
      if ($urlComponent['path'][0] == '/')
	$url .= $urlComponent['path'];
      else if (isset($currentUC['path']) && substr($currentUC['path'], -1 * strlen($urlComponent['path'])) != $urlComponent['path'] &&
	       substr($currentUC['path'], -1) == '/')
	$url .= $currentUC['path'].$urlComponent['path'];
      else if (isset($currentUC['path']) && $currentUC['path'] != '/' && strrpos($currentUC['path'], '/') > 0)
	$url .= substr($currentUC['path'], 0, strrpos($currentUC['path'], '/') + 1).$urlComponent['path'];
      else
	$url .= '/'.$urlComponent['path'];
    }

  if (strstr($url, '../'))
    {
      $uc = parse_url($url);
      $sp = explode('/', $uc['path']);
      for ($i = 0; isset($sp[$i]); $i++)
	if ($sp[$i] == '..' && isset($sp[$i - 1]))
	  {
	    unset($sp[$i]);
	    unset($sp[$i - 1]);
	  }
      $sp = implode('/', $sp);
      $url = $uc['scheme'].'://'.$uc['host'].($sp[0] == '/' ? $sp : '/'.$sp);
    }

  $urlComponent = parse_url($url);
  return array('fullUrl' => $url, 'path' => (isset($urlComponent['path']) ? $urlComponent['path'] : ''));
}

function checkExtensions($url)
{
  return !preg_match('/.*\.(jpg|jpeg|png|tif|gif|avi|wmv|webm|mov|xvid|mkv|flv|rmvb|doc|xls|xlsx|docx|ppt|zip|rar|tar|tar\.gz|exe|run|sh|7z|pdf|odt)$/i', $url);
}

// find all css in html and download them
function findAllCss($html, $urlComponent)
{
  global $cssFound;
  global $rootDir;

  foreach ($html->find('link') as $css)
    {
      if (isset($css->rel) && $css->rel == 'stylesheet' &&
	  !empty($css->href) &&
	  !isset($cssFound[$css->href]))
	{
	  $url = getFullURL($css->href, $urlComponent);
	  if ($url !== null)
	    {
	      $cssPage = getWebPage($url['fullUrl']);
	      if ($cssPage['http_code'] == 200)
		{
		  $uc = parse_url($cssPage['url']);

		  $file = $rootDir.'/'.(substr($uc['path'], 1) == '' ? 'index' : substr($uc['path'], 1));
		  $file = pathinfo($file);
		  if (!file_exists($file['dirname']))
		    mkdir($file['dirname'], 0777, true);

		  file_put_contents($file['dirname'].'/'.$file['basename'], $cssPage['content']);
		  $cssFound[$css->href] = '1';
		}
	    }
	}
    }
}

function findAllLinks($html, $depth, $urlComponent)
{
  global $robot;
  global $rootDir;
  global $urlStack;
  global $urlStackDone;

  $linksOut = array();
  foreach ($html->find('a') as $e)
    {
      if (empty($e->href))
	continue;
      if ($e->href[0] == '#' || (substr($e->href, 0, 2) == '//'))
	continue;
      $url = getFullURL(preg_replace('/#.*/i', '', $e->href), $urlComponent);
      if ($url === null)
	continue;

      if (!isset($urlStack[$url['fullUrl']]) && !isset($urlStackDone[$url['fullUrl']]) &&
	  $robot && !$robot->isDisallowed($url['path']) &&
	  checkExtensions($url['fullUrl']))
	{
	  $urlStack[$url['fullUrl']] = array('url' => $url['fullUrl'], 'depth' => $depth);

	  file_put_contents($rootDir.'.status-urlFound', $url['fullUrl']."\n", FILE_APPEND | LOCK_EX);
	  // set File Status add a found url
	  // set file status total page found += 1

	  $linksOut[] = $url['fullUrl'];
	}
    }
  return $linksOut;
}

function optimizeHtml($file, $html, $url)
{
    global $selectors;
    global $currentDirectory;

    $currentDirectory = $file['dirname'];

    $modifications = array();
    $modifications['export_css'] = exportCss($file['dirname'], $file['basename'], $html);
    $modifications['delete_deprecated_balise'] = deleteDeprecatedTags($file['dirname'], $file['basename'], $html);
    reduceTitleNumber($file['basename'], $html);
    optimizeKeywords($file['dirname'], $html);

    file_put_contents($file['dirname'].'/'.$file['basename'], $html);
    file_put_contents($file['dirname'].'/'.$file['basename'].'.obj', serialize($html));

    $selectors[]['filename'] = $file['dirname'].'/'.$file['basename'];
    $selectors[count($selectors) - 1]['includes'] = getCssIncluded($html);
    $selectors[count($selectors) - 1]['includes'][] = $file['dirname'].'/css/'.$file['filename'].'.magiseo.css';

    return $modifications;
}

function ParseUrl($url, $depth)
{
  global $rootDir;
  global $urlStackDone;

  $urlStackDone[$url] = '';

  $page = getWebPage($url);
  if (DEBUG) error_log('type: '.$page['content_type'].' status: '.$page['http_code']);

  if (($page['errno'] != 0) || !preg_match('/^text\/html.*$/', $page['content_type']))
    return ;

  $pageStats = getPageStats($page, $depth);
  if ($page['http_code'] != 200)
    return $pageStats;

  $html = str_get_html(preg_replace('#<!--(.|\s)*?-->#', '', $page['content']));
  if ($html)
    {
      $urlComponent = parse_url($page['url']);
      $file = pathinfo($rootDir.'/'.(!isset($urlComponent['path']) || substr($urlComponent['path'], 1) == '' ? 'index' : substr($urlComponent['path'], 1)));
      if (!isset($file['basename']) && empty($file['basename']) || ($file['basename'] == '/') || ($file['basename'] == '.'))
	$file['basename'] = 'index';

      if (!file_exists($file['dirname']))
	mkdir($file['dirname'], 0777, true);

      findAllCss($html,  $urlComponent);
      //findAllJs($html,  $urlComponent);
      $pageStats['linksOut'] = findAllLinks($html, ($depth + 1), $urlComponent);

      $pageStats['html_optimization'] = optimizeHtml($file, $html, $page['url']);
    }
  return $pageStats;
}

function launchCrawler($url, $timer)
{
  global $baseUrl;
  global $rootDir;
  global $robot;
  global $step;
  global $selectors;
  global $urlStack;

  $baseUrl = $url;
  $urlComponent = parse_url($url);

  $rootDir .= $urlComponent['host'];

  @unlink($rootDir.'.status-urlFound');
  @unlink($rootDir.'.status-pageParsed');

  $robot = loadRobotTXT($url);
  $urlStack[] = ['url' => $url, 'depth' => 0];
  while (!empty($urlStack))
    {
      $urlToParse = array_shift($urlStack);
      $pageStats  = parseUrl($urlToParse['url'], $urlToParse['depth']);

      file_put_contents($rootDir.'.status-pageParsed', $urlToParse['url']."\n", FILE_APPEND | LOCK_EX);
      sleep($timer);
    }

  $step++;
  scanDirectory($rootDir);

  $algo = new \Algorithme();
  $algo->execute();
}

if (isset($argv[1]) && isset($argv[2]))
  {
    $timer = 0;
    if (isset($argv[3]))
      {
	$timer = intval($argv[3]);
	if ($timer > 600 || $timer < 0)
	  die('Error: Interval must be between 0 and 600 (seconds)'."\n");
      }

    if ($argv[1] == 'site')
      launchCrawler($argv[2], $timer);
    else if ($argv[1] == 'arch')
      {
	if (!is_dir($argv[2]))
	  {
	    $info = pathinfo($argv[2]);
	    if ($info['extension'] == 'zip')
	      shell_exec('cd '.dirname($argv[2]).' && unzip '.$argv[2].' && cd -');
	    else
	      shell_exec('cd '.dirname($argv[2]).' && tar -xf '.$argv[2].' && cd -');
	    $dir = dirname($argv[2]).'/'.$info['filename'];
	  }
	else
	  $dir = $argv[2];

	scanDirectory($dir);
	$step++;
	scanDirectory($dir);
      }
  }
else
  die('Usage: php5 ExtractCrawler.php [site|arch] [www.example.org | /home/archiveToCheck] (interval)'."\n");