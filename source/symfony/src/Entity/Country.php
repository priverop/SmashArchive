<?php

declare(strict_types = 1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Table(name="country", indexes={
 *     @ORM\Index(name="name_index", columns={"name"}),
 * })
 * @ORM\Entity(repositoryClass="CoreBundle\Repository\CountryRepository")
 */
class Country
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @Serializer\Groups({"players_overview", "tournaments_overview", "tournaments_details"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255, unique=true)
     *
     * @Serializer\Groups({"players_overview", "tournaments_overview", "tournaments_details"})
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     *
     * @Serializer\Groups({"players_overview", "tournaments_overview", "tournaments_details"})
     */
    private $name;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="PlayerProfile", mappedBy="nationality")
     */
    private $playersNationalities;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="PlayerProfile", mappedBy="country")
     */
    private $playersCountries;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Tournament", mappedBy="country")
     */
    private $tournaments;

    /**
     *
     */
    public function __construct()
    {
        $this->playersNationalities = new ArrayCollection();
        $this->playersCountries = new ArrayCollection();
        $this->tournaments = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return ArrayCollection
     */
    public function getPlayersNationalities(): ArrayCollection
    {
        return $this->playersNationalities;
    }

    /**
     * @return ArrayCollection
     */
    public function getPlayersCountries(): ArrayCollection
    {
        return $this->playersCountries;
    }

    /**
     * @return ArrayCollection
     */
    public function getTournaments(): ArrayCollection
    {
        return $this->tournaments;
    }
}
