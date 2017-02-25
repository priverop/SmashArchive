<?php

declare(strict_types=1);

namespace ApiBundle\Controller;

use CoreBundle\Controller\AbstractDefaultController;
use Domain\Command\Player\HeadToHeadCommand;
use Domain\Command\Player\OverviewCommand;
use Domain\Command\Player\ResultsCommand;
use MediaMonks\RestApiBundle\Response\PaginatedResponseInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Rutger Mensch <rutger@rutgermensch.com>
 *
 * @Route("/players", service="api.controller.player")
 */
class PlayerController extends AbstractDefaultController
{
    /**
     * @param Request $request
     * @return PaginatedResponseInterface
     *
     * @Route("/", name="api_players_overview")
     */
    public function indexAction(Request $request)
    {
        $tag = $request->get('tag');
        $page = $request->get('page');
        $limit = $request->get('limit');

        $command = new OverviewCommand($tag, $page, $limit);
        $result = $this->commandBus->handle($command);

        return $this->buildPaginatedResponse($result['players'], $result['pagination']);
    }

    /**
     * @param string $slug
     * @return array
     *
     * @Route("/{slug}/results/")
     */
    public function resultsAction($slug)
    {
        $command = new ResultsCommand($slug);

        return $this->commandBus->handle($command);
    }

    /**
     * @param string $playerOneSlug
     * @param string $playerTwoSlug
     * @return array
     *
     * @Route("/head-to-head/{playerOneSlug}/{playerTwoSlug}/")
     */
    public function headToHeadAction($playerOneSlug, $playerTwoSlug)
    {
        $command = new HeadToHeadCommand($playerOneSlug, $playerTwoSlug);

        return $this->commandBus->handle($command);
    }
}
