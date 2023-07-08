<?php

namespace Magiseo\CrawlerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * runningState
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class runningState
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
     * @ORM\ManyToOne(targetEntity="Magiseo\UserBundle\Entity\User", inversedBy="repports")
     * @ORM\JoinColumn(nullable=true)
     */
    private $user;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255)
     */
    private $url;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetime")
     */
    private $date;

    /**
     * @var integer
     *
     * @ORM\Column(name="pageParsed", type="bigint")
     */
    private $pageParsed;

    /**
     * @var integer
     *
     * @ORM\Column(name="pageFound", type="bigint")
     */
    private $pageFound;

    /**
     * @var array
     *
     * @ORM\Column(name="pageURLParsed", type="array")
     */
    private $pageURLParsed;

    /**
     * @var array
     *
     * @ORM\Column(name="pageURLFound", type="array")
     */
    private $pageURLFound;

    /**
     * @var string
     *
     * @ORM\Column(name="state", type="string", length=255)
     */
    private $state;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=10)
     */
    private $type;

    /**
     * @var boolean
     *
     * @ORM\Column(name="end", type="boolean")
     */
    private $end;

    /**
     * @var integer
     *
     * @ORM\Column(name="numberErrors", type="bigint")
     */
    private $numberErrors;

    public function __contruct()
    {
      $this->pageURLParsed = array();
      $this->pageURLFound = array();
      $this->end = false;
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
     * Set user
     *
     * @param Magiseo\UserBundle\Entity\User $user
     */
    public function setUser(\Magiseo\UserBundle\Entity\User $user)
    {
      $this->user = $user;
      $user->addRepport($this);
    }

    /**
     * Get user
     *
     * @return Magiseo\UserBundle\Entity\User
     */
    public function getUser()
    {
      return $this->user;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return runningState
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
     * Set pageParsed
     *
     * @param integer $pageParsed
     * @return runningState
     */
    public function setPageParsed($pageParsed)
    {
        $this->pageParsed = $pageParsed;

        return $this;
    }

    /**
     * Get pageParsed
     *
     * @return integer
     */
    public function getPageParsed()
    {
        return $this->pageParsed;
    }

    /**
     * Set pageFound
     *
     * @param integer $pageFound
     * @return runningState
     */
    public function setPageFound($pageFound)
    {
        $this->pageFound = $pageFound;

        return $this;
    }

    /**
     * Get pageFound
     *
     * @return integer
     */
    public function getPageFound()
    {
        return $this->pageFound;
    }

    /**
     * Set numberErrors
     *
     * @param integer $numberErrors
     * @return runningState
     */
    public function setNumberErrors($numberErrors)
    {
        $this->numberErrors = $numberErrors;

        return $this;
    }

    /**
     * Get numberErrors
     *
     * @return integer
     */
    public function getNumberErrors()
    {
        return $this->numberErrors;
    }

    /**
     * Set end
     *
     * @param boolean $end
     * @return runningState
     */
    public function setEnd($end)
    {
        $this->end = $end;

        return $this;
    }

    /**
     * Get end
     *
     * @return boolean
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * Set state
     *
     * @param string $state
     * @return runningState
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get state
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return runningType
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Add pageURLParsed
     *
     * @param string $pageURLParsed
     * @return test
     */
    public function addPageURLParsed($pageURLParsed, $value)
    {
        $this->pageURLParsed[$pageURLParsed] = $value;

        return $this;
    }

    /**
     * Del pageURLParsed
     *
     * @param string $pageURLParsed
     * @return test
     */
    public function delPageURLParsed($pageURLParsed)
    {
      unset($this->pageURLParsed[$pageURLParsed]);

      return $this;
    }

    /**
     * Get pageURLParsed
     *
     * @return array
     */
    public function getPageURLParsed()
    {
        return $this->pageURLParsed;
    }

    /**
     * Add pageURLFound
     *
     * @param string $pageURLFound
     * @return test
     */
    public function addPageURLFound($pageURLFound)
    {
        $this->pageURLFound[] = $pageURLFound;

        return $this;
    }

    /**
     * Del pageURLFound
     *
     * @param string $pageURLFound
     * @return test
     */
    public function delPageURLFound($pageURLFound)
    {
      $this->pageURLFound->removeElement($pageURLFound);

      return $this;
    }

    /**
     * Get pageURLFound
     *
     * @return array
     */
    public function getPageURLFound()
    {
        return $this->pageURLFound;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     * @return WebPage
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }
}
