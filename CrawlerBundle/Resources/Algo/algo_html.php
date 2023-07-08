<?php
//include 'simplehtmldom/simple_html_dom.php';

$magiseoId = 1;

function exportCss($directory, $fileName, &$dom)
{
  global $magiseoId;

  $modifications = array();
  $cssDir = $directory.'/css';
  $fileInfo = pathinfo($fileName);
  $cssFile = $fileInfo['filename'] . '.magiseo.css';
  $classContent = '';

  if (!file_exists($cssDir))
    mkdir($cssDir, 0777, true);

  $fp = fopen($cssDir . '/' . $cssFile, 'w+');
  // chmod($cssDir . '/' . $cssFile, 0777);

  // Look for <style> and <style type="text/css"> tags
  foreach ($dom->find('style') as $element)
    {
      // ERROR_LOG: <style> and </style> shouldn't be used in a HTML file
      $modifications['html_styleInDom'][] = 'moving <style> content in css file';
      // print the text inside the css file
      fputs($fp, $element->innertext);
      $element->outertext = '';
    }

  // look for the inline css
  foreach ($dom->find('*[style]') as $element)
    {
      // ERROR_LOG: inline css shouldn't be used
      $modifications['html_inlineCss'][] = 'inline css shouldn\'t be used';

      if ($element->class == NULL)
        {
	  $element->class = $element->tag/* . '_' . $magiseoId*/;
	  $magiseoId++;
        }

        // write content in css file
        if ($element->style != '1')
	  fputs($fp, '.' . $element->class . ' {' . $element->style . ';} ');
        foreach ($dom->find('*[style=' . $element->style . ']') as $value)
        {
            $value->class = $element->class;
            $value->style = null;
        }

        // delete style attribute
        $element->style = null;
    }
  if ($dom->find('link[href="./css/' . $cssFile . '"]') == NULL)
    {
      $inject = '<link rel="stylesheet" href="./css/' . $cssFile . '" type="text/css"/>';
      $head = $dom->find('head', 0);
      if ($head)
	$head->innertext = $head->innertext . $inject;
    }

  // override the html file with the new optimized content
  //file_put_contents($fileName, $dom);
  fclose($fp);

  return $modifications;
}

?>