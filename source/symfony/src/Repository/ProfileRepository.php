<?php

declare(strict_types = 1);

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * @author Rutger Mensch <rutger@rutgermensch.com>
 */
class ProfileRepository extends EntityRepository
{
    /**
     * @param string $slug
     *
     * @return int
     */
    public function exists(string $slug)
    {
        $result = $this
            ->createQueryBuilder('p')
            ->select('p.id')
            ->where('p.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $result !== null;
    }
}
