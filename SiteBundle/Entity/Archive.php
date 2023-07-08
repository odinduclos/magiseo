<?php

namespace Magiseo\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Magiseo\SiteBundle\Entity\Archive
 *
 * @ORM\Table(name="archive")
 * @ORM\Entity
 */
class Archive
{
    /**
     * Taille max : 80Mo
     * @Assert\File(
     *      maxSize = "80000000",
     *      mimeTypes = { "application/x-tar", "application/zip", "application/x-rar-compressed" },
     *      maxSizeMessage = "La taille maximum allouée est 80Mo.",
     *      mimeTypesMessage = "Seuls les fichiers de type archive (tar, zip, rar) sont autorisés."
     * )
     * @Assert\NotBlank
     */
    private $file;
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * a virer c'est mieux : @ Assert\Url()
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Assert\NotBlank
     */
    private $url;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $path;

    /**
    * @ORM\ManyToOne(targetEntity="Magiseo\UserBundle\Entity\User")
    */
    private $user;

    /**
     * Set User
     *
     * @param \Magiseo\UserBundle\Entity\User $user
     */
    public function setUser(\Magiseo\UserBundle\Entity\User $user) {
        $this->user = $user;
    }

    /**
     *
     * @return Magiseo\UserBundle\Entity\User
     */
    public function getUser() {
        return $this->user;
    }

    public function getFile() {
        return $this->file;
    }

    public function setFile($file) {
        $this->file = $file;
    }

    public function getAbsolutePath()
    {
        return null === $this->path ? null : $this->getUploadRootDir().'/'.$this->path;
    }

    public function getWebPath()
    {
        return null === $this->path ? null : $this->getUploadDir().'/'.$this->path;
    }

    protected function getUploadRootDir()
    {
        // le chemin absolu du répertoire où les documents uploadés doivent être sauvegardés
        return __DIR__.'/../../../../web/'.$this->getUploadDir();
    }

    protected function getUploadDir()
    {
        // on se débarrasse de « __DIR__ » afin de ne pas avoir de problème lorsqu'on affiche
        // le document/image dans la vue.
        return 'archives/';
    }

    public function upload($username)
    {
        // la propriété « file » peut être vide si le champ n'est pas requis
        if (null === $this->file) {
            return;
        }

        $path_parts = pathinfo($this->file->getClientOriginalName());
        $ext = $path_parts['extension'];

        $filename = $username.'_'.date('d-m-y').'_'.$this->getCleanUrl().'.'.$ext;

        // la méthode « move » prend comme arguments le répertoire cible et
        // le nom de fichier cible où le fichier doit être déplacé
        $this->file->move($this->getUploadRootDir(), $filename);

        // définit la propriété « path » comme étant le nom de fichier où vous
        // avez stocké le fichier
        $this->path = $filename;

        // « nettoie » la propriété « file » comme vous n'en aurez plus besoin
        $this->file = null;
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
     * @return Archive
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
     * Get url
     *
     * @return string
     */
    public function getCleanUrl()
    {
        return str_replace("http://", "", $this->url);
    }

    /**
     * Set path
     *
     * @param string $path
     * @return Archive
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
}
