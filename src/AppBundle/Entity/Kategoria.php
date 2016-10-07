<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Kategoria
 *
 * @ORM\Table(name="kategoria")
 * @ORM\Entity
 */
class Kategoria {

    /**
     * @var string
     *
     * @ORM\Column(name="nazwa", type="string", length=45, nullable=true)
     */
    private $nazwa;

    /**
     * @var integer
     *
     * @ORM\Column(name="idKategoria", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idkategoria;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Ksiazka", mappedBy="idkategoria")
     */
    protected $ksiazki;

//    /**
//     * Set nazwa
//     *
//     * @param string $nazwa
//     * @return Kategoria
//     */
//    public function setNazwa($nazwa)
//    {
//        $this->nazwa = $nazwa;
//
//        return $this;
//    }
//
//    /**
//     * Get nazwa
//     *
//     * @return string 
//     */
//    public function getNazwa()
//    {
//        return $this->nazwa;
//    }
//
//    /**
//     * Get idkategoria
//     *
//     * @return integer 
//     */
//    public function getIdkategoria()
//    {
//        return $this->idkategoria;
//    }


    /**
     * Dodałem tę metodę bo wyświetla poniższy komunikat 
     * gdy używam CRUD:
     * 
     * A "__toString()" method was not found on the objects of type
     *  "AppBundle\Entity\Kategoria" passed to the choice field. 
     * To read a custom getter instead, set the option "property" 
     * to the desired property path.
     * 
     */
    public function __toString() {
        return $this->getNazwa();
    }

    /**
     * Set nazwa
     *
     * @param string $nazwa
     * @return Kategoria
     */
    public function setNazwa($nazwa) {
        $this->nazwa = $nazwa;

        return $this;
    }

    /**
     * Get nazwa
     *
     * @return string 
     */
    public function getNazwa() {
        return $this->nazwa;
    }

    /**
     * Get idkategoria
     *
     * @return integer 
     */
    public function getIdkategoria() {
        return $this->idkategoria;
    }

    public function __construct() {
        $this->ksiazki = new ArrayCollection();
    }

    /**
     * Get ksiazki
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getKsiazki() {
        return $this->ksiazki;
    }


    /**
     * Add ksiazki
     *
     * @param \AppBundle\Entity\Ksiazka $ksiazki
     * @return Kategoria
     */
    public function addKsiazki(\AppBundle\Entity\Ksiazka $ksiazki)
    {
        $this->ksiazki[] = $ksiazki;

        return $this;
    }

    /**
     * Remove ksiazki
     *
     * @param \AppBundle\Entity\Ksiazka $ksiazki
     */
    public function removeKsiazki(\AppBundle\Entity\Ksiazka $ksiazki)
    {
        $this->ksiazki->removeElement($ksiazki);
    }
}