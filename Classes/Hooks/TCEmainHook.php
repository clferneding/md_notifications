<?php

declare(strict_types=1);

namespace Mediadreams\MdNotifications\Hooks;

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

use Mediadreams\MdNotifications\Utility\RootlineUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * TCE main hook
 *
 * This will hook into saving, updating and deleting process of records and
 * adds notifications according to site configuration
 */
class TCEmainHook
{
    /**
     * Add notification information for new record
     *
     * @param string $action The performed action
     * @param string $table The table name of the record
     * @param string $recordUid Temporary id of the record, eg `NEW67b5f96849921638839656`
     * @param array $fieldArray The data array, which holds all information on the record
     * @param DataHandler $pObj Parent Object
     * @throws \TYPO3\CMS\Core\Exception
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function processDatamap_afterDatabaseOperations(
        string $action,
        string $table,
        string $recordUid,
        array $fieldArray,
        DataHandler &$pObj
    ): void
    {
        if ($action == 'new') {
            $siteConfig = $this->getSiteConfig($fieldArray['pid']);
            if ($this->inCharge($siteConfig, $fieldArray['pid'], $table)) {
                // get uid of new record
                $recordId = $pObj->substNEWwithIDs[$recordUid];

                if (!$recordId) {
                    $this->enqueueFlashmessage('Notification for record could not be saved!');
                    return;
                }

                $this->saveNotificationInfo($recordId, $table, $fieldArray, $siteConfig);
            }
        } else if ($action == 'update') {
            // TODO: Check, if there is another way, to get the `pid` of the record
            $siteConfig = $this->getSiteConfig($pObj->checkValue_currentRecord['pid']);
            if ($this->inCharge($siteConfig, $pObj->checkValue_currentRecord['pid'], $table)) {
                $this->updateNotificationInfo($recordUid, $table, $fieldArray);
            }
        }
    }

    /**
     *  Delete notification if record gets deleted
     *
     * @param string $table Table name of record
     * @param int $id Id of record
     * @param array $recordToDelete Array of all data of record
     * @param bool $recordWasDeleted
     * @param DataHandler $pObj Parent Object
     * @return void
     * @throws \TYPO3\CMS\Core\Exception\SiteNotFoundException
     */
    public function processCmdmap_deleteAction(
        string $table,
        int $id,
        array $recordToDelete,
        bool $recordWasDeleted,
        DataHandler &$pObj
    ): void
    {
        $siteConfig = $this->getSiteConfig($recordToDelete['pid']);
        if ($this->inCharge($siteConfig, $recordToDelete['pid'], $table)) {
            $databaseConnection = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('tx_mdnotifications_domain_model_notification');

            $databaseConnection->delete(
                'tx_mdnotifications_domain_model_notification',
                ['record_key' => $table, 'record_id' => $id]
            );
        }
    }

    /**
     * Save notification info for the record
     *
     * @param int $recordUid Uid of record
     * @param string $recordKey The key of the record (database table name)
     * @param array $fieldArray Data of news entry
     * @param array $siteConfig Site configuration
     * @return void
     * @throws \Doctrine\DBAL\Exception
     */
    protected function saveNotificationInfo(
        int $recordUid,
        string $recordKey,
        array $fieldArray,
        array $siteConfig = []
    ): void
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

        // find users
        $queryBuilderFeusers = $connectionPool->getQueryBuilderForTable('fe_users');
        $feuserData = $queryBuilderFeusers
            ->select('fe_users.uid')
            ->from('fe_users');

        // if $feGroup is set, just find users with given group
        if (!empty($siteConfig['md_notifications']['feGroup']) && is_int($siteConfig['md_notifications']['feGroup'])) {
            $feuserData = $feuserData->where(
                $queryBuilderFeusers->expr()->inSet(
                    'usergroup',
                    $queryBuilderFeusers->createNamedParameter($siteConfig['md_notifications']['feGroup'], \PDO::PARAM_INT)
                )
            );
        }

        // finally get data
        $feuserData = $feuserData
            ->executeQuery()
            ->fetchAllAssociative();

        // if there is some data, prepare and save it
        if (count($feuserData) > 0) {
            // prepare data to save
            $timestamp = time();
            foreach ($feuserData as $data) {
                $dataArray[] = [
                    'pid'           => $siteConfig['md_notifications']['storagePid'] ?? 0,
                    'record_key'    => $recordKey,
                    'record_id'     => $recordUid,
                    'record_date'   => $timestamp,
                    'feuser'        => $data['uid'],
                    'data'          => json_encode($fieldArray),
                    'tstamp'        => $timestamp,
                    'crdate'        => $timestamp,
                    'hidden'        => $fieldArray['hidden'],
                    'starttime'     => $fieldArray['starttime'],
                    'endtime'       => $fieldArray['endtime'],
                ];
            }

            $colNamesArray = ['pid', 'record_key', 'record_id', 'record_date', 'feuser', 'data', 'tstamp', 'crdate', 'hidden', 'starttime', 'endtime'];

            $dbConnectionNotification = $connectionPool->getConnectionForTable('tx_mdnotifications_domain_model_notification');
            $dbConnectionNotification->bulkInsert(
                'tx_mdnotifications_domain_model_notification',
                $dataArray,
                $colNamesArray
            );
        }
    }

    /**
     * Update notification info for record
     *
     * @param string $recordId Id of record
     * @param string $recordKey Key of record (table name)
     * @param array $fieldArray Data of news entry
     * @return void
     */
    protected function updateNotificationInfo(string $recordId, string $recordKey, array $fieldArray)
    {
        // TODO: Update `data` as well?!
        $databaseConnection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_mdnotifications_domain_model_notification');

        $arrayUpdateData = ['tstamp' => time()];

        if (isset($fieldArray['hidden'])) {
            $arrayUpdateData = array_merge($arrayUpdateData, ['hidden' => $fieldArray['hidden']]);
        }

        if (isset($fieldArray['starttime'])) {
            $arrayUpdateData = array_merge($arrayUpdateData, ['starttime' => $fieldArray['starttime']]);
        }

        if (isset($fieldArray['endtime'])) {
            $arrayUpdateData = array_merge($arrayUpdateData, ['endtime' => $fieldArray['endtime']]);
        }

        $databaseConnection->update(
            'tx_mdnotifications_domain_model_notification',
            $arrayUpdateData,
            ['record_key' => $recordKey, 'record_id' => $recordId]
        );
    }

    /**
     * Get site configuration
     *
     * @param int $storageId
     * @return array
     * @throws \TYPO3\CMS\Core\Exception\SiteNotFoundException
     */
    protected function getSiteConfig(int $storageId): array
    {
        if ($storageId > 0) {
            $siteFinder = GeneralUtility::makeInstance(SiteFinder:: class);
            $site = $siteFinder->getSiteByPageId($storageId);
            return $site->getConfiguration();
        }

        return [];
    }

    /**
     * Check, if given record should be respected
     *
     * @param array $siteConfig Array with settings from site configuration
     * @param int $storageId Storage Id of record
     * @param string $recordKey Record key (table name)
     * @return bool
     */
    protected function inCharge(array $siteConfig, int $storageId, string $recordKey): bool
    {
        if (isset($siteConfig['md_notifications'][$recordKey])) {
            $rootlineUtility = GeneralUtility::makeInstance(RootlineUtility::class);
            return $rootlineUtility->isInRootline($storageId, $siteConfig['md_notifications'][$recordKey]);
        }

        return false;
    }

    /**
     * Show flash message
     *
     * @param string $message
     * @throws \TYPO3\CMS\Core\Exception
     */
    protected function enqueueFlashmessage(string $message): void
    {
        /** @var FlashMessage $flashMessage */
        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            $message,
            'EXT:md_notifications',
            \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::WARNING,
            true
        );

        /** @var FlashMessageService $flashMessageService */
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        /** @var \TYPO3\CMS\Core\Messaging\FlashMessageQueue $defaultFlashMessageQueue */
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }
}
