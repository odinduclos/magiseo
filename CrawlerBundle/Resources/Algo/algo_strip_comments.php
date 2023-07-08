<?php

function stripComments($fileName, &$dom)
{
  $strDom = $dom;
  $rt = preg_replace('#<!--(.|\s)*?-->#', '', $strDom);
  //file_put_contents($fileName, $rt);
}

?>