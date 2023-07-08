<?php

function reduceTitleNumber($fileName, &$dom)
{
  $i = 0;
  $titlePos = 0;

  if ($dom->find('h1') != NULL)
    {
      // Get the label of the first h1
      $firstTitle = $dom->find('h1', 0);
      $titleLabel = $firstTitle->innertext;

      // Replace all h1 by h2 tags
      foreach ($dom->find('h1') as $element)
	{
	  $element->tag = 'h2';
	}

      // Replace all h1 by h2 tags
      foreach ($dom->find('h1') as $element)
	{
	  // ERROR_LOG: Using only one h1 is better
	  $element->tag = 'h2';
	}

      // Get the h2 containing the label previously stored
      if ($dom->find('h2') != NULL)
	{
	  while ($dom->find('h2', $titlePos)->innertext != $titleLabel)
	    {
	      $titlePos++;
	    }

	  // Replace this h2 with a h1
	  $newTitle = $dom->find('h2', $titlePos);
	  $newTitle->tag = 'h1';
	}
    }
  //	file_put_contents($fileName, $dom);
}

?>