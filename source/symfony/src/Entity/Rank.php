<?php

declare(strict_types = 1);

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;

/**
 * @ORM\Table(name="rank", indexes={
 *     @ORM\Index(name="rank_index", columns={"rank"}),
 * })
 * @ORM\Entity(repositoryClass="App\Repository\RankRepository")
 *
 * @Serializer\ExclusionPolicy("all")
 *
 * @SWG\Definition(
 *     @SWG\Property(property="entrant", type="string", example="[A]rmada"),
 *     @SWG\Property(property="players", type="array", @SWG\Items(ref=@Model(type=Player::class))),
 * )
 */
class Rank
{
    /**
     * @var Event|null
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Event", inversedBy="ranks")
     * @ORM\JoinColumn(onDelete="CASCADE")
     *
     * @Serializer\Expose
     * @Serializer\Groups({"profiles_ranks"})
     */
    private $event;

    /**
     * @var Entrant|null
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Entrant", inversedBy="ranks")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $entrant;

    /**
     * @var integer
     *
     * @ORM\Column(name="rank", type="integer")
     *
     * @Serializer\Expose
     * @Serializer\Groups({"profiles_ranks", "tournaments_standings"})
     *
     * @SWG\Property(example=1)
     */
    private $rank;

    /**
     * @return Event|null
     */
    public function getEvent(): ?Event
    {
        return $this->event;
    }

    /**
     * @return bool
     */
    public function hasEvent(): bool
    {
        return $this->event instanceof Event;
    }

    /**
     * @param Event|null $event
     */
    public function setEvent(?Event $event): void
    {
        $this->event = $event;
    }

    /**
     * @return Entrant|null
     */
    public function getEntrant(): ?Entrant
    {
        return $this->entrant;
    }

    /**
     * @return string
     *
     * @Serializer\Groups({"profiles_ranks", "tournaments_standings"})
     * @Serializer\SerializedName("entrant")
     * @Serializer\VirtualProperty()
     */
    public function getEntrantName(): string
    {
        return $this->getEntrant()->getName();
    }

    /**
     * @param string|null $excludePlayerSlug
     *
     * @return Player[]|Collection
     *
     * @Serializer\Groups({"profiles_ranks", "tournaments_standings"})
     * @Serializer\VirtualProperty()
     */
    public function getPlayers($excludePlayerSlug = null): Collection
    {
        $players = $this->getEntrant()->getPlayers();

        if ($excludePlayerSlug) {
            return $players->filter(function (Player $player) use ($excludePlayerSlug) {
                return $player->getSlug() !== $excludePlayerSlug;
            });
        }

        return $players;
    }

    /**
     * @param Entrant|null $entrant
     */
    public function setEntrant(?Entrant $entrant): void
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
     * @return string
     */
    public function getStringifiedRank(): string
    {
        $formatter = new \NumberFormatter('en_US', \NumberFormatter::ORDINAL);

        return $formatter->format($this->rank);
    }

    /**
     * @param int $rank
     */
    public function setRank(int $rank): void
    {
        $this->rank = $rank;
    }
}
