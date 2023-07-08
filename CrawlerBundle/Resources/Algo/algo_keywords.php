<?php

function optimizeKeywords($fileName, &$dom)
{
  $metaKeyword = $dom->find('meta[name=keywords]');

  if ($metaKeyword == NULL)
    {
      $head = $dom->find('head', 0);
      $addMeta = '<meta name="keywords" content="';

      foreach ($dom->find('h1') as $hKeyword)
	{
	  $metaTag = $dom->find('meta');
	  $addMeta .= $hKeyword->innertext . ', ';
	}

      $addMeta .= '" />';

      if ($head)
	$head->innertext = $addMeta . $head->innertext;
    }
  else
    {
      $metaContent = explode(', ', $metaKeyword[0]->content);

      foreach ($dom->find('h1') as $titleTag)
	{
	  if (in_array($titleTag->innertext, $metaContent) == FALSE)
	    $metaKeyword[0]->content .= ', ' . $titleTag->innertext;
	}
    }
  //  file_put_contents($fileName, $dom);
}

function optimizeKeywordsFromSynonyms($fileName, &$dom, $synonymList)
{
  $metaKeyword = $dom->find('meta[name=keywords]');

  if ($metaKeyword == NULL)
    {
      $head = $dom->find('head', 0);
      $addMeta = '<meta name="keywords" content="';

      foreach ($synonymList as $value)
	{
	  $metaTag = $dom->find('meta');
	  $addMeta .= $value . ', ';
	}

      $addMeta .= '" />';

      if ($head)
	$head->innertext = $addMeta . $head->innertext;
    }
  else
    {
      $metaContent = explode(', ', $metaKeyword[0]->content);

      foreach ($synonymList as $synonym)
	{
	  if (in_array($synonym, $metaContent) == FALSE)
	    $metaKeyword[0]->content .= ', ' . $synonym;
	}
    }

  //file_put_contents($fileName, $dom);
}
?>