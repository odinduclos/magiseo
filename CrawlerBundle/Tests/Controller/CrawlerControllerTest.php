<?php
namespace Magiseo\CrawlerBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;



class CrawlerControllerTest extends WebTestCase
{

    public function testCheckExtension()
    {
	$crawler = new \Magiseo\CrawlerBundle\Controller\CrawlerController();
	$urls = [[true, NULL], [true, ''], [true, '.php'], [true, '.html'],
		 [false, '.zip'], [false, '.pdf'], [false, '.mp3'],
		 [false, '.jpg'], [false, '.rar'], [false, '.exe'],
		 [false, '.wmv'], [false, '.doc'], [true, '.aspx'],
		 [false, '.avi'], [false, '.ppt'], [false, '.mpg'],
		 [false, '.tif'], [false, '.wav'], [false, '.mov'],
		 [false, '.psd'], [false, '.wma'], [false, '.sitx'],
		 [false, '.sit'], [false, '.eps'], [false, '.cdr'],
		 [false, '.ai'], [false, '.xls'], [false, '.mp4'],
		 [true, '.txt'], [true, '.xhtml'], [false, '.m4a'],
		 [false, '.wmvb'], [false, '.bmp'], [false, '.pps'],
		 [false, '.aif'], [false, '.pub'], [false, '.dwg'],
		 [false, '.gif'], [false, '.qdd'], [false, '.mpeg'],
		 [false, '.flv'], [false, '.iso'], [false, '.7z'],
		 [false, '.gz'], [false, '.tar.gz'], [false, '.css']];

	foreach ($urls as $url) {
	    $this->assertEquals($url[0], $crawler->checkExtensions($url[1]));
	}
    }

    public function testGetFullUrl()
    {
	$crawler = new \Magiseo\CrawlerBundle\Controller\CrawlerController();
	$crawler->baseUrl = 'http://www.google.fr';

	$this->assertEquals(null, $crawler->getFullURL('https://www.google.com')['fullUrl']);
	$this->assertEquals('http://www.google.fr', $crawler->getFullURL('/')['fullUrl']);
	$this->assertEquals('http://www.google.fr/toto', $crawler->getFullURL('//www.google.fr/toto')['fullUrl']);
	$this->assertEquals('http://www.google.fr/toto', $crawler->getFullURL('http://www.google.fr/toto')['fullUrl']);
	$this->assertEquals('http://www.google.fr/toto', $crawler->getFullURL('/zxca/../toto')['fullUrl']);
	$this->assertEquals('http://www.google.fr/zxca/toto', $crawler->getFullURL('//www.google.fr/toto/../zxca/e/f/../../toto')['fullUrl']);
	$this->assertEquals('https://www.google.fr/policies/technologies/ads', $crawler->getFullURL('https://www.google.fr/../../../policies/technologies/ads/')['fullUrl']);
    }

    public function testGlobRecursive()
    {
	shell_exec('mkdir -p /tmp/test/a/ /tmp/test/b/c');
	shell_exec('touch /tmp/test/1.css');
	shell_exec('touch /tmp/test/1.html');
	shell_exec('touch /tmp/test/2.css');
	shell_exec('touch /tmp/test/a/1.css');
	shell_exec('touch /tmp/test/b/c/1.css');
	shell_exec('touch /tmp/test/b/c/2.html');

	$cssFiles = ['/tmp/test/1.css',
		     '/tmp/test/2.css',
		     '/tmp/test/b/c/1.css',
		     '/tmp/test/a/1.css'];
	$htmlFiles = ['/tmp/test/1.html',
		      '/tmp/test/b/c/2.html'];

	$crawler = new \Magiseo\CrawlerBundle\Controller\CrawlerController();
	$this->assertEquals($cssFiles, $crawler->glob_recursive('/tmp/test/*.css'));
	$this->assertEquals($htmlFiles, $crawler->glob_recursive('/tmp/test/*.html'));
	$this->assertEquals([], $crawler->glob_recursive('/tmp/test/*.toto'));

	shell_exec('rm -rf /tmp/test');
    }

    public function testIsCssFile()
    {
	$crawler = new \Magiseo\CrawlerBundle\Controller\CrawlerController();
	$this->assertTrue($crawler->isCssFile('/tmp/toto.css'));
	$this->assertFalse($crawler->isCssFile('/tmp/toto.html'));
    }
}
