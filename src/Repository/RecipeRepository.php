<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Recipe;
use App\Entity\Season;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\Paginator as PagerPaginator;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @extends ServiceEntityRepository<Recipe>
 * 
 * @method Recipe[]  findAll()
 * @method Recipe[]  findBy(array $creteria, array $orderBy = null, $limit = null, $offset = null)
 */

class RecipeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private PaginatorInterface $paginator)
    {
        parent::__construct($registry, Recipe::class);
    }

    public function paginateRecipes(int $page): PaginationInterface
    {
        return $this->paginator->paginate(
            $this->createQueryBuilder('r')->leftJoin('r.category', 'c')->select('r','c'),
            $page,
            10,
            [
                'distinct' => false,
                'sortFieldAllowlist' => ['r.id']
            ]
        );

        //Pagination par default
        
        // return new Paginator($this
        // ->createQueryBuilder('r')
        // ->setFirstResult(($page - 1) * $limit)
        // ->setMaxResults($limit)
        // ->getQuery()
        // ->setHint(Paginator::HINT_ENABLE_DISTINCT, false), false
        // );
    }

    public function paginateRecipesUsers(int $page): PaginationInterface
    {
        return $this->paginator->paginate(
            $this->createQueryBuilder('r')->orderBy('r.createdAt', 'DESC'),
            $page,
            6,
            [
                'distinct' => false,
                'sortFieldAllowlist' => ['r.id']
            ]
        );
    }

    public function paginateByCategory (Category $category, int $page): PaginationInterface
    {
        return $this->paginator->paginate(
            $this->createQueryBuilder('r')
                ->where('r.category = :category')
                ->setParameter('category', $category)
                ->orderBy('r.createdAt', 'DESC'),
            $page,
            6,
            [
                'distinct' => false,
                'sortFieldAllowlist' => ['r.id']
            ]
        );
    }

    public function paginateBySeason (Season $season, int $page): PaginationInterface
    {
        return $this->paginator->paginate(
            $this->createQueryBuilder('r')
                ->join('r.seasons', 'season')
                ->where('season = :season')
                ->setParameter('season', $season)
                ->orderBy('r.createdAt', 'DESC'),
            $page,
            6,
            [
                'distinct' => true,
                'sortFieldAllowlist' => ['r.id', 'r.createdAt']
            ]
        );
    }

    public function paginateByUsers(User $user, int $page): PaginationInterface
    {
        return $this->paginator->paginate(
            $this->createQueryBuilder('r')
                ->where('r.author = :user')
                ->setParameter('user', $user)
                ->orderBy('r.createdAt', 'DESC'),
            $page,
            6,
            [
                'distinct' => false,
                'sortFieldAllowlist' => ['r.id']
            ]
        );
    }

    public function paginateFavoritesForUser(User $user, int $page): PaginationInterface
    {
        return $this->paginator->paginate(
            $this->createQueryBuilder('r')
                ->join('r.favoritedBy', 'u')
                ->where('u = :user')
                ->setParameter('user', $user)
                ->orderBy('r.createdAt', 'DESC'),
            $page,
            6,
            [
                'distinct' => true,
                'sortFieldAllowlist' => ['r.id']
            ]
        );
    }

    public function paginateLatest( int $maxResults = 12, int $page): PaginationInterface
    {
        // 1) Récupère les IDs des $maxResults dernières recettes
        $subQb = $this->createQueryBuilder('r')
            ->select('r.id')
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults($maxResults);

        $idsResult = $subQb->getQuery()->getScalarResult(); // retourne [['id' => 42], ...]
        $ids = array_column($idsResult, 'id');

        // Si pas d'IDs, retourne une pagination vide (KNP accepte un tableau)
        if (empty($ids)) {
            return $this->paginator->paginate([], $page);
        }

        // 3) Lance la pagination sur ce QueryBuilder (6 par page par défaut)
        return $this->paginator->paginate(
            $this->createQueryBuilder('r')
            ->where('r.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('r.createdAt', 'DESC'),
            $page,
            6,
            [
                'distinct' => false,
                'sortFieldAllowlist' => ['r.id']
            ]
        );
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

    public function countAllRecipes(): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->getQuery()
            ->getSingleScalarResult();
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
