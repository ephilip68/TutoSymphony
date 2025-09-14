<?php

namespace App\Repository;

use App\Entity\Recipe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Recipe>
 * 
 * @method Recipe[]  findAll()
 * @method Recipe[]  findBy(array $creteria, array $orderBy = null, $limit = null, $offset = null)
 */

class RecipeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recipe::class);
    }

    public function findTotalDuration():int { //Afficher la durée total des recettes
          
        return $this->createQueryBuilder('r')
            ->select('SUM(r.duration) as total')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
    * @return Recipe[] 
     */

    public function findWithDurationLowerThan(int $duration): array {

        return $this->createQueryBuilder('r')
            ->select('r')
            ->where('r.duration <= :duration')
            ->orderBy('r.duration', 'ASC')
            ->setMaxResults(10)
            ->setParameter('duration', $duration)
            ->getQuery()
            ->getResult();
    }

    public function findLatest(int $limit = 5): array
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByCategorySlug(string $slug): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.category', 'c')
            ->where('c.slug = :slug')
            ->setParameter('slug', $slug)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }



    //    /**
    //     * @return Recipe[] Returns an array of Recipe objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Recipe
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
