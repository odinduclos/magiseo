<?php 
include_once 'scan_utils.php';
$step = 0;
$scan_folder = 'tu/test';
$errors = array();
if (isset($_GET['folder']))
{
	$scan_folder = $_GET['folder'];
}
if (isset($argv[1])) {
	$scan_folder = $argv[1];
}
$result = scanDirectory($scan_folder);
$step++;
$result = scanDirectory($scan_folder);
?>