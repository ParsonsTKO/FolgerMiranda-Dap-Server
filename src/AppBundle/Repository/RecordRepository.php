<?php declare(strict_types=1);

namespace AppBundle\Repository;

use AppBundle\Entity\Record;
use Doctrine\ORM\EntityRepository;

class RecordRepository extends EntityRepository
{
    /**
     * @param int $remoteId
     * @return Record|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByRemoteId(string $remoteId) : ?Record
    {
        $queryBuilder = $this->createQueryBuilder('record');

        return $queryBuilder
            ->where('REMOTE_ID_EQUALS(record.metadata) = :remoteId')
            ->setParameter('remoteId', $remoteId)

            ->setMaxResults(1)

            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param int $remoteId
     * @return Record|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByRemoteIdRemoteSystem(string $remoteId, string $remoteSystem) : ?Record
    {
        $queryBuilder = $this->createQueryBuilder('record');
        $assetRemoteSystem = $remoteSystem;

        return $queryBuilder
            ->andWhere('REMOTE_ID_EQUALS(record.metadata) = :remoteId')
            ->setParameter('remoteId', $remoteId)
            ->andWhere('REMOTE_SYSTEM_EQUALS(record.metadata) LIKE :remoteSystem')
            ->setParameter('remoteSystem', '%'.$assetRemoteSystem.'%')

            ->setMaxResults(1)

            ->getQuery()
            ->getOneOrNullResult();
    }
}
