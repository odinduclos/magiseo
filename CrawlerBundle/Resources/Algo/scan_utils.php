<?php

include_once 'simpleHtmlDom.php';
include_once 'algo_css.php';
include_once 'algo_html.php';
include_once 'algo_deprecated_tags.php';
include_once 'algo_title.php';
include_once 'algo_keywords.php';
require 'vendor/autoload.php';

$selectors = array();
$currentDirectory = null;

// take a partial url and return the full one
function getFullURL($href, $currentUC = null)
{
  $separator = '\\/';
  if (DEBUG) error_log('get full url in: '.$href);
  $normalized = preg_replace('#\p{C}+|^\./#u', '', $href);
  // Path remove self referring paths ("/./").
  $normalized = preg_replace('#/\.(?=/)|^\./|\./$#', '', $normalized);
  // Regex for resolving relative paths
  $regex = '#\/*[^/\.]+/\.\.#Uu';
  while (preg_match($regex, $normalized)) {
    $normalized = preg_replace($regex, '', $normalized);
  }
  if (preg_match('#/\.{2}|\.{2}/#', $normalized)) {
    throw new LogicException('Path is outside of the defined root, path: [' . $href . '], resolved: [' . $normalized . ']');
  }
  $url = trim($normalized, $separator);
  if (DEBUG) error_log('get full url out: '.$url);
  $urlComponent = parse_url($url);
  return isset($urlComponent['path']) ? $urlComponent['path'] : '';
  }

function getPathURL($dir, $curDir, $url) {
  if (strpos($url, '//') === 0 || strpos($url, 'http://') === 0 || strpos($url, 'www') === 0) {
    return $url;
  } else if (strpos($url, '/') === 0) {
    $url = substr($url, 1);
    return '/' . getFullURL($dir . $url);
  } else {
    return getFullURL($curDir . '/' . $url);
  }
  return $url;
}

function getCssIncluded($rootDir, &$dom)
{
  global $currentDirectory;
  $cssFiles = array();

  if ($dom->find('link[rel="stylesheet"]') != NULL)
  {
    foreach ($dom->find('link[rel="stylesheet"]') as $result)
    {
      array_push($cssFiles, getPathURL($rootDir, $currentDirectory, $result->href));
    }
    return $cssFiles;
  }
}

function write_temp_dom_file($filename, $data)
{
    return file_put_contents($filename.'.obj', serialize($data));
}

function manageFile($directory, $entry) {
    global $step, $selectors, $currentDirectory;
    $currentDirectory = $directory;
    $css = NULL;
    if ($entry != '.' && $entry != '..') {
        $extension = strrchr($entry,'.');
        $filename = $directory . '/' . $entry;
        @$html = php_strip_whitespace($filename);
        if ($extension == '.html' || $extension == '.php') {
            switch ($step) {
                case 0:
                $dom = str_get_html($html);
                // exportCss($directory, $filename, $dom);
                $selectors[]["filename"] = $filename;
                $selectors[count($selectors) - 1]["includes"] = getCssIncluded($dom);
                write_temp_dom_file($filename, $dom);
                break;
                case 1:
                break;
                default:
                break;
            }
        }
        elseif ($extension == '.css') {
            switch ($step) {
                case 0:
                break;
                case 1:
                $oCssParser = new Sabberworm\CSS\Parser($html);
                $oCssDocument = $oCssParser->parse();
                $css = optimizeCss($oCssDocument, null, $filename);
                    // echo 'Put ' . $css . ' in ' . $filename . '<br />';
                file_put_contents('tu/' . $filename, $css);
                default:
                break;
            }
        }
    }
    return $css;
}

function scanDirectory($directory){
    $MyDirectory = @opendir($directory) or die('Erreur');
    while($entry = @readdir($MyDirectory)) {
        if(is_dir($directory.'/'.$entry) && $entry != '.' && $entry != '..') {
            // error_log('Directory: '.$directory.'/'.$entry);
            scanDirectory($directory.'/'.$entry);
            // error_log('End of directory: '.$directory.'/'.$entry);
        }
        else {
            // error_log('File: '.$entry);
            $ret = manageFile($directory, $entry);
        }
    }
    closedir($MyDirectory);
    return $ret;
}

?>