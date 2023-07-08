<?php

namespace Magiseo\UserBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * User
 *
 * @ORM\Entity
 * @ORM\Table(name="user")
 */
class User extends BaseUser
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\COlumn(name="company", type="string")
     */
    private $company;

    /**
     * @ORM\OneToMany(targetEntity="Magiseo\CrawlerBundle\Entity\runningState", mappedBy="user")
     * @ORM\OrderBy({"date" = "DESC"})
     */
    private $repports;

    /**
     * @ORM\OneToMany(targetEntity="Magiseo\UserBundle\Entity\Notification", mappedBy="user")
     */
    private $notifications;

    public function __contruct()
    {
      $this->repports = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Get Company
     *
     * @return string
     */
    public function getCompany()
    {
      return $this->company;
    }

    /**
     * Set Company
     *
     * @param string company
     * @return this
     */
    public function setCompany($company)
    {
      $this->company = $company;
    }

    public function addRepport(\Magiseo\CrawlerBundle\Entity\runningState $repport)
    {
      $this->repports[] = $repport;
      return $this;
    }

    public function removeRepport(\Magiseo\CrawlerBundle\Entity\runningState $repport)
    {
      $this->repports->removeElement($repport);
    }

    public function getRepports()
    {
      return $this->repports;
    }

    public function getLastRepport()
    {
      return $this->repports[0];
    }

    public function addNotification(\Magiseo\UserBundle\Entity\Notification $notification)
    {
      $notification->setDate(new \DateTime());
      $notification->setUser($this);
      $this->notifications[] = $notifiation;
      return $this;
    }

    public function removeNotification(\Magiseo\UserBundle\Entity\Notification $notification)
    {
      $this->notifications->removeElement($notifiation);
    }

    public function getNotifications()
    {
      return $this->notifications;
    }
}
