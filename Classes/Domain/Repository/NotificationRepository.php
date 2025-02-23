<?php

declare(strict_types=1);

namespace Mediadreams\MdNotifications\Domain\Repository;

/**
 * This file is part of the "Notifications" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 *
 * (c) 2025 Christoph Daecke <typo3@mediadreams.org>
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use function Symfony\Component\DependencyInjection\Loader\Configurator\expr;

/**
 * The repository for Notifications
 */
class NotificationRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    /**
     * The name of the notification table in the database
     */
    const TABLE_NAME = 'tx_mdnotifications_domain_model_notification';

    /**
     * Set default ordering for repository
     */
    protected $defaultOrderings = [
        'recordDate' => QueryInterface::ORDER_DESCENDING,
        'uid' => QueryInterface::ORDER_DESCENDING,
    ];

    /**
     * Get list of notification records for given user
     *
     *
     * @param int $feuserUid
     * @param string|null $recordKeys Comma separated string of table names, eg. `pages, tx_news_domain_model_news`
     * @return QueryResult
     */
    public function getList(int $feuserUid, string $recordKeys = null): QueryResult
    {
        $query = $this->createQuery();
        $constraints[] = $query->equals('feuser', $feuserUid);

        if ($recordKeys !== null) {
            $types = explode(',', $recordKeys);

            $orStatements = [];
            foreach ($types as $type) {
                $orStatements[] = $query->equals('record_key', trim($type));
            }

            if (count($orStatements) > 0) {
                $constraints[] = $query->logicalOr(...$orStatements);
            }
        }

        $query->matching($query->logicalAnd(...$constraints));

        //$queryParser = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbQueryParser::class);
        //debug($queryParser->convertQueryToDoctrineQueryBuilder($query)->getSQL());
        //debug($queryParser->convertQueryToDoctrineQueryBuilder($query)->getParameters());

        return $query->execute();
    }

    /**
     * Indicates. whether the user has seen the given item
     *
     * @param string $recordKey The record key (table name)
     * @param int $recordUid Uid of the record
     * @param int $feuserUid Frontend user Uid
     * @return int
     */
    public function hasSeen(string $recordKey, int $recordUid, int $feuserUid): int
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable(static::TABLE_NAME);

        $queryBuilder = $queryBuilder
            ->count('uid')
            ->from(static::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq(
                    'record_key',
                    $queryBuilder->createNamedParameter($recordKey, Connection::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'record_id',
                    $queryBuilder->createNamedParameter($recordUid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'feuser',
                    $queryBuilder->createNamedParameter($feuserUid, Connection::PARAM_INT)
                )
            );

        return $queryBuilder->executeQuery()->fetchOne();
    }

    /**
     * Get number of notifications for user
     *
     * @param int $feuserUid Frontend user Uid
     * @param string|null $recordKeys Comma separated string of table names, eg. `pages, tx_news_domain_model_news`
     * @return int
     * @throws \Doctrine\DBAL\Exception
     */
    public function countItems(int $feuserUid, string $recordKeys = null): int
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable(static::TABLE_NAME);

        $queryBuilder = $queryBuilder
            ->count('uid')
            ->from(static::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq(
                    'feuser',
                    $queryBuilder->createNamedParameter($feuserUid, Connection::PARAM_INT)
                )
            );

        if ($recordKeys !== null) {
            $types = explode(',', $recordKeys);

            $orStatements = [];
            foreach ($types as $type) {
                $orStatements[] = $queryBuilder->expr()->eq(
                    'record_key',
                    $queryBuilder->createNamedParameter(trim($type), Connection::PARAM_STR)
                );
            }

            if (count($orStatements) > 0) {
                $queryBuilder->andWhere($queryBuilder->expr()->or(...$orStatements));
            }
        }

        return $queryBuilder->executeQuery()->fetchOne();
    }

    /**
     * Delete entry for given record and user
     *
     * @param string $recordKey The record key (table name)
     * @param int $recordUid Uid of the record
     * @param int $feuserUid Uid of feuser record
     * @return void
     */
    public function deleteEntry(string $recordKey, int $recordUid, int $feuserUid): void
    {
        $databaseConnection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(static::TABLE_NAME);

        $arrayWhere = [
            'record_key' => $recordKey,
            'record_id' => $recordUid,
            'feuser' => $feuserUid,
        ];

        $databaseConnection->delete(static::TABLE_NAME, $arrayWhere);
    }
}
