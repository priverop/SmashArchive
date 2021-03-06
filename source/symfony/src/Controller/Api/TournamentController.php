<?php

declare(strict_types = 1);

namespace App\Controller\Api;

use App\Bus\Command\Tournament\DetailsCommand;
use App\Bus\Command\Tournament\OverviewCommand;
use App\Bus\Command\Tournament\StandingsCommand;
use App\Entity\Rank;
use App\Entity\Tournament;
use App\Form\TournamentJobType;
use App\Form\TournamentType;
use App\Service\Smashgg\Smashgg;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\RequestException;
use League\Tactician\CommandBus;
use MediaMonks\RestApi\Exception\FormValidationException;
use MediaMonks\RestApi\Response\PaginatedResponseInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Pheanstalk\Pheanstalk;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Sensio;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @author Rutger Mensch <rutger@rutgermensch.com>
 *
 * @Sensio\Route("/api/tournaments")
 */
class TournamentController extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var CommandBus
     */
    protected $bus;

    /**
     * @var Pheanstalk
     */
    protected $pheanstalk;

    /**
     * @var Smashgg
     */
    protected $smashgg;

    /**
     * @param EntityManagerInterface $entityManager
     * @param CommandBus             $bus
     * @param Pheanstalk             $pheanstalk
     * @param Smashgg                $smashgg
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        CommandBus $bus,
        Pheanstalk $pheanstalk,
        Smashgg $smashgg
    ) {
        $this->entityManager = $entityManager;
        $this->bus = $bus;
        $this->pheanstalk = $pheanstalk;
        $this->smashgg = $smashgg;
    }

    /**
     * Returns a list of tournaments.
     *
     * @param Request $request
     *
     * @return PaginatedResponseInterface
     *
     * @Sensio\Route("/", name="api_tournaments_overview")
     * @Sensio\Method("GET")
     *
     * @SWG\Tag(name="Tournaments")
     * @SWG\Response(
     *     response=200,
     *     description="Returned when the tournaments were successfully retrieved.",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Tournament::class, groups={"tournaments_overview"}))
     *     )
     * )
     */
    public function indexAction(Request $request)
    {
        $name = $request->query->get('name');
        $location = $request->query->get('location');
        $page = $request->query->getInt('page', null);
        $limit = $request->query->getInt('limit', null);

        $command = new OverviewCommand($name, $location, $page, $limit, 'dateStart', 'desc');
        $pagination = $this->bus->handle($command);

        $this->setSerializationGroups('tournaments_overview');

        return $this->buildPaginatedResponse($pagination);
    }

    /**
     * Returns a list of available event IDs for a tournament/provider combination.
     *
     * This currently only supports smash.gg. The Challonge API does not support multiple events
     * per tournament at the time of writing.
     *
     * @param Request $request
     *
     * @return array
     *
     * @Sensio\Route("/available-events/", name="api_tournaments_available_events")
     * @Sensio\Method("GET")
     *
     * @SWG\Tag(name="Tournaments")
     * @SWG\Response(
     *     response=200,
     *     description="Returned when the available events were successfully retrieved.",
     *     @SWG\Schema(type="object", example={"123456": "Melee Singles"})
     * )
     */
    public function availableEvents(Request $request)
    {
        $provider = $request->query->getAlnum('provider');
        $slug = $request->query->filter('provider-slug', null, FILTER_SANITIZE_URL);

        if (!$provider === 'smashgg') {
            throw new \InvalidArgumentException('The given provider is not valid.');
        }

        try {
            $availableEvents = $this->smashgg->getTournamentEvents($slug, true);
        } catch (RequestException $error) {
            throw new BadRequestHttpException('Could not retrieve the event information from smash.gg.');
        }

        return array_reduce($availableEvents, function ($carrier, $event) {
            $carrier[$event['id']] = $event['name'];

            return $carrier;
        }, []);
    }

    /**
     * Returns detailed information about an individual tournament.
     *
     * @param string $slug
     *
     * @return Tournament
     *
     * @Sensio\Route("/{slug}/", name="api_tournaments_details")
     * @Sensio\Method("GET")
     *
     * @SWG\Tag(name="Tournaments")
     * @SWG\Response(
     *     response=200,
     *     description="Returned when the tournament was successfully retrieved.",
     *     @SWG\Items(ref=@Model(type=Tournament::class, groups={"tournaments_details"}))
     * )
     */
    public function detailsAction($slug)
    {
        $command = new DetailsCommand($slug);
        $tournament = $this->bus->handle($command);

        $this->setSerializationGroups('tournaments_details');

        return $tournament;
    }

    /**
     * Returns the standings of a single tournament event.
     *
     * @param string $eventId
     *
     * @return array
     *
     * @Sensio\Route("/events/{eventId}/standings/", name="api_tournaments_standings")
     * @Sensio\Method("GET")
     *
     * @SWG\Tag(name="Tournaments")
     * @SWG\Response(
     *     response=200,
     *     description="Returned when the standings were successfully retrieved.",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Rank::class, groups={"tournaments_standings"}))
     *     )
     * )
     */
    public function standingsAction($eventId)
    {
        $command = new StandingsCommand(null, intval($eventId));
        $standings = $this->bus->handle($command);

        $this->setSerializationGroups('tournaments_standings');

        return $standings;
    }

    /**
     * Add a job to the work queue to import the tournament.
     *
     * This endpoint doesn't actually do any importing. Instead it adds the tournament information to a job and puts it
     * in a work queue. A separate process, running in the background, then picks up the job and does the actual
     * importing. Therefore, it may take some time (a few minutes at most) before the results of the import are
     * actually visible in the API.
     *
     * @param Request $request
     *
     * @return bool
     *
     * @Sensio\Method("POST")
     * @Sensio\Route("/", name="api_tournaments_import")
     * @Sensio\IsGranted("ROLE_ADMIN")
     *
     * @SWG\Tag(name="Tournaments")
     * @SWG\Parameter(
     *     in="body",
     *     name="tournament",
     *     @Model(type=TournamentJobType::class)
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returned when the tournament import job was successfully added."
     * )
     */
    public function importAction(Request $request)
    {
        $form = $this->createForm(TournamentJobType::class);
        $form->submit($request->request->all());

        if (!$form->isValid()) {
            throw new FormValidationException($form);
        }

        $data = $form->getData();

        $this->pheanstalk->put(\GuzzleHttp\json_encode([
            'type'   => 'tournament-import',
            'source' => $data['provider'],
            'slug'   => $data['slug'],
            'events' => $data['events'],
        ]));

        return true;
    }

    /**
     * Updates specific properties of an existing tournament.
     *
     * @param Request $request
     * @param string  $slug
     *
     * @return Tournament
     *
     * @Sensio\Method("PATCH")
     * @Sensio\Route("/{slug}/", name="api_tournaments_update")
     * @Sensio\IsGranted("ROLE_ADMIN")
     *
     * @SWG\Tag(name="Tournaments")
     * @SWG\Parameter(
     *     in="body",
     *     name="status",
     *     @Model(type=TournamentType::class)
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returned when the tournament details were successfully updated.",
     *     @SWG\Items(ref=@Model(type=Tournament::class, groups={"tournaments_details"}))
     * )
     */
    public function updateAction(Request $request, $slug)
    {
        $command = new DetailsCommand($slug);
        $tournament = $this->bus->handle($command);

        $this->validateForm($request, TournamentType::class, $tournament);

        $this->entityManager->flush();

        $this->setSerializationGroups('tournaments_details');

        return $tournament;
    }

    /**
     * Deletes an existing tournament.
     *
     * @param string $slug
     *
     * @return bool
     *
     * @Sensio\Method("DELETE")
     * @Sensio\Route("/{slug}/", name="api_tournaments_delete")
     * @Sensio\IsGranted("ROLE_ADMIN")
     *
     * @SWG\Tag(name="Tournaments")
     * @SWG\Response(
     *     response=200,
     *     description="Returned when the tournament was successfully deleted."
     * )
     */
    public function deleteAction($slug)
    {
        $command = new DetailsCommand($slug);
        $tournament = $this->bus->handle($command);

        $this->entityManager->remove($tournament);
        $this->entityManager->flush();

        return true;
    }
}
