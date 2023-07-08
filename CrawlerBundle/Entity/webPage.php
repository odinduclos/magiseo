<?php

namespace Magiseo\CrawlerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * webPage
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class webPage
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, unique=true)
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="filename", type="string", length=4096)
     */
    private $filename;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text")
     */
    private $content;

    /**
     * @var string
     *
     * @ORM\Column(name="modifiedContent", type="text", nullable=true)
     */
    private $modifiedContent;

    /**
     * @var array
     *
     * @ORM\Column(name="modifications", type="array")
     */
    private $modifications;

    /**
     * @var array
     *
     * @ORM\Column(name="errors", type="array")
     */
    private $errors;

    public function __construct()
    {
      $this->errors = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return webPage
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set filename
     *
     * @param string $filename
     * @return webPage
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get filename
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Set content
     *
     * @param string $content
     * @return webPage
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }
    /**
     * Set modifiedContent
     *
     * @param string $modifiedContent
     * @return webPage
     */
    public function setModifiedContent($modifiedContent)
    {
        $this->modifiedContent = $modifiedContent;

        return $this;
    }

    /**
     * Get modifiedContent
     *
     * @return string
     */
    public function getModifiedContent()
    {
        return $this->modifiedContent;
    }

    /**
     * Add modifications
     *
     * @param string $key, $value
     * @return webPage
     */
    public function addModification($key, $value)
    {
      //error_log('adding: '.$key.' => '.$value);
      $this->modifications[$key] = $value;

      return $this;
    }

    /**
     * Remove modifications
     *
     * @param string $key
     * @return webPage
     */
    public function removeModification($key)
    {
      unset($this->modifications[$key]);

      return $this;
    }

    /**
     * Get modifications
     *
     * @return array
     */
    public function getModifications()
    {
        return $this->modifications;
    }

    /**
     * Add error
     *
     * @param array $error
     * @return webPage
     */
    public function addError($error)
    {
        $this->error[] = $errors;

        return $this;
    }

    /**
     * Remove error
     *
     * @param array $error
     * @return webPage
     */
    public function removeError($error)
    {
      $this->error->removeElement($error);

      return $this;
    }

    /**
     * Get errors
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
