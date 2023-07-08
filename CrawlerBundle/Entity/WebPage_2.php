<?php

namespace Magiseo\CrawlerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * WebPage_2
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class WebPage_2
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
     * @ORM\Column(name="path", type="string", length=255)
     */
    private $path;

    /**
     * @var string
     *
     * @ORM\Column(name="filename", type="string", length=4096)
     */
    private $filename;

    /**
     * @var integer
     *
     * @ORM\Column(name="size", type="integer")
     */
    private $size;

    /**
     * @var integer
     *
     * @ORM\Column(name="responseTime", type="integer")
     */
    private $responseTime;

    /**
     * @var integer
     *
     * @ORM\Column(name="statusCode", type="integer")
     */
    private $statusCode;

    /**
     * @var integer
     *
     * @ORM\Column(name="depth", type="integer")
     */
    private $depth;

    /**
     * @var array
     *
     * @ORM\Column(name="incomingLinks", type="array")
     */
    private $incomingLinks;

    /**
     * @var array
     *
     * @ORM\Column(name="outgoingLinks", type="array")
     */
    private $outgoingLinks;

    /**
     * @var array
     *
     * @ORM\Column(name="errors", type="array")
     */
    private $errors;

    /**
     * @var string
     *
     * @ORM\Column(name="originalContent", type="text")
     */
    private $originalContent;

    /**
     * @var string
     *
     * @ORM\Column(name="ModifiedContent", type="text")
     */
    private $modifiedContent;


    public function __construct()
    {
      $this->incomingLinks = array();
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
     * Set path
     *
     * @param string $path
     * @return WebPage_2
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set filename
     *
     * @param string $filename
     * @return WebPage_2
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
     * Set size
     *
     * @param integer $size
     * @return WebPage_2
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Get size
     *
     * @return integer
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Set responseTime
     *
     * @param integer $responseTime
     * @return WebPage_2
     */
    public function setResponseTime($responseTime)
    {
        $this->responseTime = $responseTime;

        return $this;
    }

    /**
     * Get responseTime
     *
     * @return integer
     */
    public function getResponseTime()
    {
        return $this->responseTime;
    }

    /**
     * Set statusCode
     *
     * @param integer $statusCode
     * @return WebPage_2
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * Get statusCode
     *
     * @return integer
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Set depth
     *
     * @param integer $depth
     * @return WebPage_2
     */
    public function setDepth($depth)
    {
        $this->depth = $depth;

        return $this;
    }

    /**
     * Get depth
     *
     * @return integer
     */
    public function getDepth()
    {
        return $this->depth;
    }

    /**
     * Set incomingLinks
     *
     * @param array $incomingLinks
     * @return WebPage_2
     */
    public function setIncomingLinks($incomingLinks)
    {
        $this->incomingLinks = $incomingLinks;

        return $this;
    }

    /**
     * Add incomingLinks
     *
     * @param array $incomingLinks
     * @return WebPage_2
     */
    public function addIncomingLink($incomingLink)
    {
        $this->incomingLinks[] = $incomingLinks;

        return $this;
    }

    /**
     * Get incomingLinks
     *
     * @return \stdClass
     */
    public function getIncomingLinks()
    {
        return $this->incomingLinks;
    }

    /**
     * Set outgoingLinks
     *
     * @param \stdClass $outgoingLinks
     * @return WebPage_2
     */
    public function setOutgoingLinks($outgoingLinks)
    {
        $this->outgoingLinks = $outgoingLinks;

        return $this;
    }

    /**
     * Get outgoingLinks
     *
     * @return \stdClass
     */
    public function getOutgoingLinks()
    {
        return $this->outgoingLinks;
    }

    /**
     * Add error
     *
     * @param array $errors
     * @return WebPage_2
     */
    public function addError($error)
    {
        $this->errors[] = $error;

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

    /**
     * Set originalContent
     *
     * @param string $originalContent
     * @return WebPage_2
     */
    public function setOriginalContent($originalContent)
    {
        $this->originalContent = $originalContent;

        return $this;
    }

    /**
     * Get originalContent
     *
     * @return string
     */
    public function getOriginalContent()
    {
        return $this->originalContent;
    }

    /**
     * Set modifiedContent
     *
     * @param string $modifiedContent
     * @return WebPage_2
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
}
