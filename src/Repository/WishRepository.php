<?php

namespace App\Repository;

use App\Entity\Wish;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Wish>
 */
class WishRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Wish::class);
    }

    /**
     * Retourne les idées publiées, éventuellement filtrées par un terme et un auteur,
     * triées par date de création.
     *
     * @param string|null $term Terme à chercher dans titre/description/auteur
     * @param string|null $author Auteur exact à filtrer (optionnel)
     * @param string $dateOrder "ASC" ou "DESC"
     * @return Wish[]
     */
    public function searchPublished(?string $term, ?string $author = null, string $dateOrder = 'DESC'): array
    {
        $dateOrder = strtoupper($dateOrder) === 'ASC' ? 'ASC' : 'DESC';

        $qb = $this->createQueryBuilder('w')
            ->leftJoin('w.category', 'c')
            ->addSelect('c')
            ->andWhere('w.isPublished = :published')
            ->setParameter('published', true)
            ->orderBy('w.dateCreated', $dateOrder);

        if ($term !== null && trim($term) !== '') {
            $qb->andWhere('(w.title LIKE :t OR w.description LIKE :t OR w.author LIKE :t)')
               ->setParameter('t', '%' . trim($term) . '%');
        }

        if ($author !== null && trim($author) !== '') {
            $qb->andWhere('w.author = :a')
               ->setParameter('a', trim($author));
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Retourne la liste des auteurs (distinct) des idées publiées.
     * @return string[]
     */
    public function getPublishedAuthors(): array
    {
        $rows = $this->createQueryBuilder('w')
            ->select('DISTINCT w.author AS author')
            ->andWhere('w.isPublished = :published')
            ->setParameter('published', true)
            ->orderBy('w.author', 'ASC')
            ->getQuery()
            ->getScalarResult();

        return array_values(array_map(static fn(array $r) => $r['author'], $rows));
    }
}
