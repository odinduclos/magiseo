<?php
/*
**
** Un probleme ? julien.boivin@epitech.eu
**
*/

/* $algo = new Algorithme("mysql"); */
/* $algo->execute(); */
/* echo $algo->rapport(false) . "\n"; */

function hex_dump($data, $newline="\n")
{
  static $from = '';
  static $to = '';

  static $width = 16;

  static $pad = '.';

  if ($from==='')
    {
      for ($i=0; $i<=0xFF; $i++)
	{
	  $from .= chr($i);
	  $to .= ($i >= 0x20 && $i <= 0x7E) ? chr($i) : $pad;
	}
    }

  $hex = str_split(bin2hex($data), $width*2);
  $chars = str_split(strtr($data, $from, $to), $width);

  $offset = 0;
  foreach ($hex as $i => $line)
    {
      echo sprintf('%6X',$offset).' : '.implode(' ', str_split($line,2)) . ' [' . $chars[$i] . ']' . $newline;
      $offset += $width;
    }
}

/* ini_set('memory_limit', '2G'); // 2147483648 */
/* var_dump(1024 * pow(8, 7)); */
/* var_dump(1024 * pow(2, 21)); */

/* $algo->debug(true); */
/* $algo->execute(["http://www.natgeotv.com/fr/oiseaux-paradis/description", "http://www.arehn.asso.fr/dossiers/oiseauxmigr/oiseaux_migrateurs.html"]); */
/* $algo->flush(); */
/* $algo->execute("http://www.natgeotv.com/fr/grandes-batailles-seconde-guerre-mondiale/description"); */
/* $algo->flush(); */
/* var_dump($algo->flush()); */
/* (new Algorithme("pgsql"))->execute(); */

class Algorithme
{
  private $dbh, $bdd, $lim, $rap, $dat;
  static private $debug = false;

  public function __construct($bdd = "mysql")
  {
    $this->lim = 1024 * pow(2, 21); // 2GB
    $this->bdd = $bdd;
    $this->dbh = new PDO("$bdd:host=localhost;dbname=magiseo", "magiseo", "magiseo");
  }

  private function memory()
  {
    if (self::$debug)
      {
	$lim = round(memory_get_usage(true) / $this->lim) * 100;
	echo self::br("Utilisation de la RAM disponible : $lim%\n");
      }
  }

  public function rapport($echo = true)
  {
    if ($echo)
      echo self::br(implode("\n", $this->dat));
    else
      return self::br(implode("\n", $this->dat));
  }

  public function debug($mode)
  {
    echo self::br((self::$debug = (bool) $mode) ? "Debug mode : Actif\n" : "Debug mode : Inactif\n");
  }

  public function flush()
  {
    unset($this->dat);
    $this->dat = [];
  }

  static function br($string)
  {
    global $argc;
    return isset($argc) ? $string : nl2br($string, false);
  }

  public function execute($dat = [])
  {
    global $argc;
    /* if (is_string($web)) */
    /*   $web = [$web]; */
    /* foreach ($web as $url => $txt) */
    /*   { */
    /* 	if (is_int($url)) */
    /* 	  $this->dat[$txt] = @file_get_contents($txt); */
    /* 	else */
    /* 	  $this->dat[$url] = $txt; */
    /*   } */
    $this->dat = $dat;
    if (!isset($argc))
      $this->gow_duplication();
    $this->analyse_db_save();
    $this->list_words_save();
    $this->display_synonym();
    if (!isset($argc))
      $this->gow_hydratation();
    $this->update_db_save();
    return true;
  }

  private function sanitize_words($string)
  {
    preg_match_all('/\pL+/u', $string, $matches, PREG_PATTERN_ORDER);
    return isset($matches[0]) ? $matches[0] : [];
  }

  private function gow_duplication()
  {
    /* $q = $this->dbh->prepare('SELECT id, url, content, modifiedContent FROM webPage WHERE id IN (' . str_repeat('?,', count($this->dat) - 1) . '?' . ')'); */
    /* $q->execute($this->dat); */

    if (($res = $this->dbh->query('SELECT id, url, content, modifiedContent FROM webPage WHERE webPage.url NOT LIKE \'%.css\'')) !== false)
      {
  	$res->setFetchMode(2);
  	while ($row = $res->fetch())
  	  {
  	    $q = $this->dbh->prepare('INSERT INTO demo_wiki (url, msg) VALUES (:url, :msg)');
  	    $q->execute([':url' => $row['url'],
			 ':msg' => $row['content']]);
  	    $q = $this->dbh->prepare('INSERT INTO demo_wiki_after (url, msg) VALUES (:url, :msg)');
  	    $q->execute([':url' => $row['url'],
			 ':msg' => $row['content']]);
  	  }
      }
    return $res;
  }

  private function gow_hydratation()
  {
    if (($res = $this->dbh->query('SELECT id, url, msg FROM demo_wiki_after')) !== false)
      {
  	$res->setFetchMode(2);
  	while ($row = $res->fetch())
  	  {
  	    $q = $this->dbh->prepare('UPDATE webPage SET modifiedContent = :msg WHERE url = :url');
  	    $q->execute([':url' => $row['url'],
			 ':msg' => $row['msg']]);
  	  }
      }
    return $res;
  }

  private function analyse_db()
  {
    $str = [];
    if (($res = $this->dbh->query('SELECT id, url, msg FROM demo_wiki')) !== false)
      {
	$res->setFetchMode(2);
	while ($row = $res->fetch())
	  {
	    if (!is_null($row['msg']))
	      {
		$msg = $row['msg'];
		$msg = str_replace('<', " <", $msg);
		$msg = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', "", $msg);
		$msg = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $msg);
		$str[] = utf8_encode(strip_tags(str_replace('&nbsp;', ' ', urldecode($msg))));
	      }
	    else if (strlen(trim($site = @file_get_contents($row['url']))))
	      {
		preg_match("/<body.*\/body>/s", file_get_contents($row['url']), $matches);
		$msg = html_entity_decode($matches[0]);
		$msg = str_replace('<', " <", $msg);
		$msg = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', "", $msg);
		$msg = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $msg);
		$str[] = strip_tags(str_replace('&nbsp;', ' ', urldecode($msg)));
	      }
	  }
      }
    return array_count_values($this->sanitize_words(mb_strtolower(implode('', $str), 'UTF-8')));
  }

  private function analyse_db_save()
  {
    //    $this->dbh->query("SET NAMES 'UTF8';");

    foreach ($this->analyse_db() as $k => $v)
      {
	if (mb_strlen($k) > 3 && strpos(utf8_decode($k), '?') === false)
	  {
	    $q = $this->dbh->prepare('INSERT INTO demo_words (word) VALUES (:word)');
	    $q->execute([':word' => $this->bdd == "mysql" ? utf8_decode($k) : utf8_decode($k)]);
	  }
	/* $this->memory(); */
      }
  }

  private function list_words($id = 0)
  {
    $str = [];
    $q = $this->dbh->prepare('SELECT id, word FROM demo_words WHERE id > :id');
    if ($q->execute([':id' => intval($id)]))
      {
	$q->setFetchMode(2);
	while ($row = $q->fetch())
	  {
	    $str[$row['id']] = $row['word'];
	  }
      }
    return $str;
  }

  private function list_words_save()
  {
    $magic = [];
    $j = 0;
    foreach (($tab = $this->list_words()) as $k => $v)
      {
	$i = ++$j;
	foreach (array_slice($tab, ++$i, NULL, true) as $n => $p)
	  {
	    $magic[$v][$p] = levenshtein($v, $p);
	    if (strlen($v) / 4 > $magic[$v][$p] ||
		strlen($p) / 4 > $magic[$v][$p] ||
		$v . 's' == $p || $v . 'e' == $p || $v . 'x' == $p ||
		$p . 's' == $v || $p . 'e' == $v || $p . 'x' == $v)
	      {
		$this->dbh->prepare('INSERT INTO demo_synonymes (word_id_left, word_id_right) VALUES (:word_id_left, :word_id_right)')->execute([':word_id_left' => $k, ':word_id_right' => $n]);
	      }
	    unset($magic[$v][$p]); // debug RAM
	  }
      }
  }

  private function display_synonym()
  {
    $words = $this->list_words();
    $synonymes = [];
    if (($res = $this->dbh->query('SELECT * from demo_synonymes')) !== false)
      {
	$res->setFetchMode(2);
	while ($row = $res->fetch())
	  {
	    $boo = false;
	    foreach ($synonymes as $k => $v)
	      {
		if (in_array($words[$row['word_id_left']], $v) xor in_array($words[$row['word_id_right']], $v))
		  {
		    $synonymes[$k][] = in_array($words[$row['word_id_left']], $v) ? $words[$row['word_id_right']] : $words[$row['word_id_left']];
		    $boo = true;
		    break;
		  }
		else if (in_array($words[$row['word_id_left']], $v) && in_array($words[$row['word_id_right']], $v))
		  {
		    $boo = true;
		    break;
		  }
	      }
	    if (!$boo)
	      {
		$synonymes[] = [$words[$row['word_id_left']], $words[$row['word_id_right']]];
	      }
	  }
      }
    $sortie = ["Synonymes trouves :\n"];
    foreach ($synonymes as $tab)
      {
	$sortie[] = implode(' -- ', $tab);
      }
    $this->dat[] = self::br(implode("\n", $sortie));
  }

  private function update_db_save()
  {
    $str = [];
    if (($res = $this->dbh->query('SELECT id, url, msg FROM demo_wiki')) !== false)
      {
	$res->setFetchMode(2);
	while ($row = $res->fetch())
	  {
	    if (!is_null($row['msg']))
	      {
		$msg = $row['msg'];
		$msg = str_replace('<', " <", $msg);
		$msg = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', "", $msg);
		$msg = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $msg);
		$str[$row['id']][] = [$row['url'], utf8_encode(str_replace('&nbsp;', ' ', urldecode($msg)))];
	      }
	    else if (strlen(trim($site = @file_get_contents($row['url']))))
	      {
		$msg = html_entity_decode($site);
		$msg = str_replace('<', " <", $msg);
		$msg = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', "", $msg);
		$msg = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $msg);
		$str[$row['id']][] = [$row['url'], str_replace('&nbsp;', ' ', urldecode($msg))];
	      }
	  }
      }

    $rate = ['title' => 9,
	     'h1' => 8,
	     'h2' => 7,
	     'h3' => 6,
	     'h4' => 5,
	     'h5' => 4,
	     'h6' => 3,
	     'b' => 2,
	     'a' => 1];

    $mat = [];
    foreach ($str as $id => $tab)
      {
	foreach ($tab as $txt)
	  {
	    foreach ($rate as $k => $v)
	      {
		if (preg_match("/<${k}>(.*)<\/${k}>/siU", $txt[1], $matches))
		  {
		    $mat[$id][] = [$txt[0], array_count_values($this->sanitize_words(mb_strtolower(strip_tags($matches[0]), 'UTF-8')))];
		  }
	      }
	  }
      }

    $rob = [];
    if (($res = $this->dbh->query('SELECT id, url, msg FROM demo_wiki')) !== false)
      {
	$res->setFetchMode(2);
	while ($row = $res->fetch())
	  {
	    if (!is_null($row['msg']) && $row['id'] < 21)
	      {
		$rob[$row['id']] = [$row['url'], $row['msg']];
	      }
	    else if (strlen(trim($site = @file_get_contents($row['url']))))
	      {
		//	$rob[$row['id']] = [$row['url'], html_entity_decode(utf8_decode($site))];
	      }
	  }
      }

    $done = [];

    foreach ($rob as $rob_id => $rob_tab)
      {
	foreach ($mat as $id => $tab)
	  {
	    foreach ($tab as $links)
	      {
		foreach ($links[1] as $word => $count)
		  {
		    if ($id != $rob_id)
		      {
			if (strlen($word) > 4)
			  {
			    // $rob_tab[0] : url
			    // $rob_tab[1] : texte
			    //			    if (!in_array($rob_tab[0] . $word, $done))
			      $this->crosslink($rob[$rob_id][1], $word, $links[0]);
			      //			    $done[] = $rob_tab[0] . $word;
			    //echo "Lien $id : ${links[0]} == ${rob_tab[0]} == avec le mot $word <br>";
			  }
		      }
		  }
	      }
	  }
      }

    foreach ($rob as $rob_id => $rob_tab)
      $this->dbh->prepare('UPDATE demo_wiki_after SET msg = :msg WHERE id = :id LIMIT 1')->execute([':msg' => $rob_tab[1], ':id' => $rob_id]);
  }

  private function crosslink(&$str, $word, $link)
  {
    //    $str = strip_tags($str);

    if (mb_strpos($str, $link) === false)
      {
	/* var_dump($str); */
	/* var_dump($link); */

	/* $str = str_replace("${word}s", "<a title='' href='${link}' target='_blank'>${word}s</a>", $str, $nbr); */
	/* if (!$nbr) */
	/*   $str = str_replace("${word}", "<a title='' href='${link}' target='_blank'>${word}</a>", $str); */
	/* return; */
	if (preg_match_all("/<(.*)\s.*>(.*)<\/\\1>/iU", $str, $out, PREG_OFFSET_CAPTURE))
	  {
	    //	    var_dump($out);
	    //	    die();
	    for ($i = 0, $offset = 0 ; $i < count($out[0]) ; ++$i)
	      {
		$str = substr_replace($str, $out[2][$i][0], $out[0][$i][1] - $offset, mb_strlen($out[0][$i][0]));
		$out[0][$i][1] -= $offset;
		$offset += mb_strlen($out[0][$i][0]) - mb_strlen($out[2][$i][0]);
	      }
	  }
	//	return;
	$word = addslashes(htmlentities($word));
	$str = preg_replace("/${word}s/", "<a title='word' href='link' target='_blank' class='crawl'>${word}s</a>", $str, 1, $nbr);
	if (!$nbr)
	  {
	    $str = preg_replace("/${word}/", "<a title='word' href='link' target='_blank' class='crawl'>${word}</a>", $str, 1, $nbr);
	    if ($nbr && !in_array("Lien ajoute sur le mot : " . $word, $this->dat))
	      $this->dat[] = self::br("Lien ajoute sur le mot : " . $word);
	  }
	else if (!in_array("Lien ajoute sur le mot : " . $word, $this->dat))
	  $this->dat[] = self::br("Lien ajoute sur le mot : " . $word);
	for ($i = 0, $offset = 0 ; $i < count($out[0]) ; $i++)
	  {
	    //	    if (!preg_match("/<(.*)\s.*>(.*)<\/\\1>/iU", $out[2][$i][0], $in, PREG_OFFSET_CAPTURE))
	    /* if (mb_strpos($str, $out[2][$i][0] . 's')) */
	    /*   $str = str_replace($out[2][$i][0] . 's', $out[0][$i][0], $str); */
	    /* else */
	      $str = str_replace($out[2][$i][0], $out[0][$i][0], $str);
	    /* if (preg_match("/<(.*)\s.*>(.*)<\/\\1>/iU", $str, $in, PREG_OFFSET_CAPTURE, $out[0][$i][1] + $offset)) */
	    /*   { */
	    /* 	var_dump($in); */
	    /* 	die(); */
	    /* 	$offset += mb_strlen($in[0][0]) - mb_strlen($out[0][$i][0]); */
	    /*   } */
	    /* else */
	    /*   { */
	    /* 	$str = substr_replace($str, $out[0][$i][0], $out[0][$i][1] + $offset, mb_strlen($out[2][$i][0])); */
	    /*   } */

	  }
      }
  }

}

?>