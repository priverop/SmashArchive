<?php

declare(strict_types=1);

namespace CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Table(name="result")
 * @ORM\Entity(repositoryClass="CoreBundle\Repository\ResultRepository")
 *
 * @TODO Add an index for the rank field.
 */
class Result
{
    /**
     * @var Event
     *
     * @ORM\ManyToOne(targetEntity="Event", inversedBy="results")
     * @ORM\Id
     */
    private $event;

    /**
     * @var Entrant
     *
     * @ORM\ManyToOne(targetEntity="Entrant", inversedBy="results")
     * @ORM\Id
     */
    private $entrant;

    /**
     * @var integer
     *
     * @ORM\Column(name="rank", type="integer")
     *
     * @Serializer\Groups({"tournaments_results"})
     */
    private $rank;

    /**
     * @return Event
     */
    public function getEvent(): Event
    {
        return $this->event;
    }

    /**
     * @param Event $event
     */
    public function setEvent(Event $event)
    {
        $this->event = $event;
    }

    /**
     * @return Entrant
     */
    public function getEntrant(): Entrant
    {
        return $this->entrant;
    }

    /**
     * @Serializer\Groups({"tournaments_results"})
     * @Serializer\SerializedName("entrant")
     * @Serializer\VirtualProperty()
     */
    public function getEntrantName(): string
    {
        return $this->getEntrant()->getName();
    }

    /**
     * @Serializer\Groups({"tournaments_results"})
     * @Serializer\VirtualProperty()
     */
    public function getPlayers()
    {
        return $this->getEntrant()->getPlayers();
    }

    /**
     * @param Entrant $entrant
     */
    public function setEntrant(Entrant $entrant)
    {
        $this->entrant = $entrant;
    }

    /**
     * @return int
     */
    public function getRank(): int
    {
        return $this->rank;
    }

    /**
     * @param int $rank
     */
    public function setRank(int $rank)
    {
        $this->rank = $rank;
    }
}

