<?php

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Book>
 *
 * @method Book|null find($id, $lockMode = null, $lockVersion = null)
 * @method Book|null findOneBy(array $criteria, array $orderBy = null)
 * @method Book[]    findAll()
 * @method Book[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    /**
     * @return Book[] Returns an array of Book objects ordered by read_date DESC
     */
    public function findAllOrderedByReadDateDesc(): array
    {
        return $this->createQueryBuilder("b")
            ->orderBy("b.readDate", "DESC")
            ->getQuery()
            ->getResult();
    }

    /**
     * @param int $userId
     * @return Book[] Returns an array of Book objects for a specific user, ordered by read_date DESC
     */
    public function findByUserOrderedByReadDateDesc(int $userId): array
    {
        return $this->createQueryBuilder("b")
            ->andWhere("b.user = :userId")
            ->setParameter("userId", $userId)
            ->orderBy("b.readDate", "DESC")
            ->getQuery()
            ->getResult();
    }
}

