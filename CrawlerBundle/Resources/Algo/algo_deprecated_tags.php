<?php

$magiseoId = 1;

function deleteDeprecatedTags($directory, $fileName, &$dom)
{
  if (($tag = $dom->find('applet, basefont, big, center, font, frame, frameset, isindex, noframes, s, strike, tt, u')) != NULL)
    foreach ($tag as $element)
      replaceDeprecatedTags($directory, $element->tag, $dom);

  //file_put_contents($fileName, $dom);
}

function tag_strike(&$e, &$fp, &$modifications)
{
  // ERROR_LOG: <strike> is deprecated. Replaced by text-decoration: line-through in css file
  $modifications['html_replace_strike'] = '<strike> is deprecated. Replaced by text-decoration: line-through in css file';
  fputs($fp, "." . $e->class . " {text-decoration: line-through;}");
}
function tag_s(&$e, &$fp, &$modifications)
{
  // ERROR_LOG: <strike> is deprecated. Replaced by text-decoration: line-through in css file
  $modifications['html_replace_strike'] = '<strike> is deprecated. Replaced by text-decoration: line-through in css file';
  fputs($fp, "." . $e->class . " {text-decoration: line-through;}");
}
function tag_u (&$e, &$fp, &$modifications)
{
  // ERROR_LOG: <u> is deprecated. Replaced by text-decoration: underline in css file
  $modifications['html_replace_u'] = '<u> is deprecated. Replaced by text-decoration: underline in css file';
  fputs($fp, "." . $e->class . " {text-decoration: underline;}");
}
function tag_center(&$e, &$fp, &$modifications)
{
  // ERROR_LOG: <center> is deprecated. Replaced by text-align: center in css file
  $modifications['html_replace_center'] = '<center> is deprecated. Replaced by text-align: center in css file';
  fputs($fp, "." . $e->class . " {text-align: center;}");
}
function tag_big(&$e, &$fp, &$modifications)
{
  // ERROR_LOG: <big> is deprecated. Replaced by font-size: 34px in css file
  $modifications['html_replace_big'] = '<big> is deprecated. Replaced by font-size: 34px in css file';
  fputs($fp, "." . $e->class . " {font-size: 34px;}");
}
function tag_font(&$e, &$fp, &$modifications)
{
  // ERROR_LOG: <font> is deprecated. Replaced by font-family, font-size and color in css file
  $modifications['html_replace_font'] = '<font> is deprecated. Replaced by font-family, font-size and color in css file';
  $cssBuf = "";
  if (isset($e->face))
    {
      $cssBuf .= " font-family: " . $e->face . ";";
      $e->face = null;
    }
  if (isset($e->size))
    {
      $cssBuf .= " font-size: " . $e->size . "px;";
      $e->size = null;
    }
  if (isset($e->color))
    {
      $cssBuf .= " color: " . $e->color . ";";
      $e->color = null;
    }
  fputs($fp, "." . $e->class . " {" . $cssBuf . "}");
}
function tag_applet(&$e, &$fp, &$modifications)
{
  // ERROR_LOG: <applet> is deprecated. Replaced by <object>
  $modifications['html_replace_applet'] = '<applet> is deprecated. Replaced by <object>';
  $e->tag = 'object';
}
function tag_isindex(&$e, &$fp, &$modifications)
{
  // ERROR_LOG: <isindex> is deprecated. Replaced by a form
  $modifications['html_replace_isindex'] = '<isindex> is deprecated. Replaced by a form';
  $e->tag = 'form';
  $e->first_child()->tag = 'input';
  $e->first_child()->type = 'submit';
}
function tag_tt(&$e, &$fp, &$modifications)
{
  // ERROR_LOG: <tt> is deprecated. Replaced by font-family: Courier New in css file
  $modifications['html_replace_tt'] = '<tt> is deprecated. Replaced by font-family: Courier New in css file';
  fputs($fp, "." . $e->class . " {font-family: Courier New;}");
}

function replaceDeprecatedTags($directory, $tag, &$dom)
{
  global $magiseoId;

  $modifications = array();
  $cssFile = $directory . '/magiseo.css';

  if ($dom->find($tag) != NULL)
    {
      // Create the css directory if it doesn't exist
      /*if (!is_dir($cssDir))
	mkdir($cssDir);*/

      // Open the css file to write inside
      $fp = fopen($cssFile, "a+");

      // Check all the targeted tags inside the html
      foreach ($dom->find($tag) as $e)
	{
	  // Replace these deprecated tags by a div
	  $e->tag = 'div';

	  // Create an id for the new div if it doesn't exist
	  if ($e->class == NULL)
	    {
	      $e->class = $tag . '_' . $magiseoId;
	      $magiseoId++;
	    }
	  $fct = 'tag_'.$tag;

	  if (function_exists($fct))
	    $fct($e, $fp, $modifications);
	}
      fclose($fp);
    }
  return $modifications;
}

?>