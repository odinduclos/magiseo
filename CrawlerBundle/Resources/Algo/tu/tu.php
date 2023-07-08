<!doctype html>
<html lang="fr">
<head>
	<meta charset="utf-8">
	<title>TU - CSS - MagiSEO</title>
</head>
<style>
table td {
	border: 1px solid black;
}
table {
	border-collapse: collapse;
}
.header {
	background-color: grey;
}
.ok {
	background-color: green;
}
.not_ok {
	background-color: red;
}
.separator {
	border: none;
}
</style>
<body>
<table>
<?php

include_once '../scan_utils.php';
const DEBUG = false;

function clean($str) {
	$str = str_replace(' ', '', $str);
	$str = str_replace("\r\n", '', $str);
	$str = str_replace("\n", '', $str);
	$str = str_replace("\t", '', $str);
	return $str;
}

function test($filename) {
	echo '<tr><td colspan="2" class="header">Test - ' . $filename . '</td></tr>';
	@$html = php_strip_whitespace('test/' . $filename);
	$oCssParser = new Sabberworm\CSS\Parser($html);
	$oCssDocument = $oCssParser->parse();
	$css = optimizeCss($oCssDocument, null, 'test/' . $filename);
	@$html = php_strip_whitespace('cmp/' . $filename);
	$css_cmp = new Sabberworm\CSS\Parser($html);
	// $css_cmp = clean(php_strip_whitespace('cmp/' . $filename));
	file_put_contents('tmp/' . $filename, $css);
	@$html = php_strip_whitespace('tmp/' . $filename);
	$css = new Sabberworm\CSS\Parser($html);
	/*var_dump($css);
	var_dump($css_cmp);*/
	// echo $css_cmp;
	if (strcmp(clean($css->sText), clean($css_cmp->sText)) == 0) {
		echo '<tr><td colspan="2" class="ok">Test OK</td></tr>';
	} else {
		echo '<tr><td colspan="2" class="not_ok">Test NOT ok</td></tr>';
		echo '<tr>';
		echo '<td>' . str_replace('}', '}<br>', str_replace("\r\n", '<br>', $css->sText)) . '</td>';
		echo '<td>' . str_replace("\r\n", '<br>', $css_cmp->sText) . '</td>';
		echo '</tr>';
	}
	echo '<tr><td colspan="2" class="separator">&nbsp;</td></tr>';
}

$filename = 'test/index.html';
@$html = php_strip_whitespace($filename);
$dom = str_get_html($html);
$selectors[]["filename"] = $filename;
/*var_dump($dom);
die();*/
$currentDirectory = 'test';
$selectors[count($selectors) - 1]["includes"] = getCssIncluded('magiseo/CrawlerBundle/Resources/Algo/tu/', $dom);
write_temp_dom_file($filename, $dom);

$filename = 'merge_rules.css';
test($filename);

$filename = 'merge_selectors.css';
test($filename);

$filename = 'remove_selectors.css';
test($filename);

$filename = 'absolute_path.css';
test($filename);

$filename = 'relative_path.css';
test($filename);

$filename = 'server_path.css';
test($filename);


?>
</table>
</body>
</html>