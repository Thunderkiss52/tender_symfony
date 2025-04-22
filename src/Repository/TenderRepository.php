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

    /**
     * Finds tenders with optional filters and returns data in array format.
     *
     * @param array $filters Associative array of filters (e.g., ['status' => 'Закрыто', 'name' => 'посуда'])
     * @return array
     */
    public function findTendersWithFilters(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('t')
            ->select([
                't.id',
                't.externalCode',
                't.number',
                't.status',
                't.name',
                // Форматируем дату прямо в запросе (зависит от СУБД)
                "DATE_FORMAT(t.date, '%Y-%m-%d') as date"
            ]);

        if (!empty($filters['externalCode'])) {
            $qb->andWhere('t.externalCode = :externalCode')
                ->setParameter('externalCode', (int)$filters['externalCode']);
        }

        if (!empty($filters['number'])) {
            $qb->andWhere('t.number LIKE :number')
                ->setParameter('number', '%' . $filters['number'] . '%');
        }

        if (!empty($filters['status'])) {
            $qb->andWhere('t.status = :status')
                ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['name'])) {
            $qb->andWhere('t.name LIKE :name')
                ->setParameter('name', '%' . $filters['name'] . '%');
        }

        if (!empty($filters['date'])) {
            $date = \DateTime::createFromFormat('Y-m-d', $filters['date']);
            if ($date !== false) {
                $qb->andWhere('t.date >= :dateStart')
                    ->andWhere('t.date < :dateEnd')
                    ->setParameter('dateStart', $date->setTime(0, 0, 0))
                    ->setParameter('dateEnd', $date->modify('+1 day')->setTime(0, 0, 0));
            }
        }

        return $qb->getQuery()->getArrayResult();
    }
}