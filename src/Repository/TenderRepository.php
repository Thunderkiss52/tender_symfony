<?php

namespace App\Repository;

use App\Entity\Tender;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tender>
 */
class TenderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tender::class);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function findTendersWithFilters(array $filters): array
    {
        $queryBuilder = $this->createQueryBuilder('t')
            ->select('t.id, t.externalCode, t.number, t.status, t.name, t.date, t.createdAt, t.updatedAt');

        if (!empty($filters['externalCode'])) {
            $queryBuilder->andWhere('t.externalCode = :externalCode')
                ->setParameter('externalCode', $filters['externalCode']);
        }

        if (!empty($filters['number'])) {
            $queryBuilder->andWhere('t.number LIKE :number')
                ->setParameter('number', '%' . $filters['number'] . '%');
        }

        if (!empty($filters['status'])) {
            $queryBuilder->andWhere('t.status LIKE :status')
                ->setParameter('status', '%' . $filters['status'] . '%');
        }

        if (!empty($filters['name'])) {
            $queryBuilder->andWhere('t.name LIKE :name')
                ->setParameter('name', '%' . $filters['name'] . '%');
        }

        if (!empty($filters['date'])) {
            try {
                $date = new \DateTime($filters['date']);
                $queryBuilder->andWhere('t.date = :date')
                    ->setParameter('date', $date);
            } catch (\Exception $e) {
            }
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param int $externalCode
     * @param string $number
     * @param string $status
     * @param string $name
     * @param \DateTime $date
     * @return Tender|null
     */
    public function findOneByAllFields(int $externalCode, string $number, string $status, string $name, \DateTime $date): ?Tender
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.externalCode = :externalCode')
            ->andWhere('t.number = :number')
            ->andWhere('t.status = :status')
            ->andWhere('t.name = :name')
            ->andWhere('t.date = :date')
            ->setParameter('externalCode', $externalCode)
            ->setParameter('number', $number)
            ->setParameter('status', $status)
            ->setParameter('name', $name)
            ->setParameter('date', $date)
            ->getQuery()
            ->getOneOrNullResult();
    }
}