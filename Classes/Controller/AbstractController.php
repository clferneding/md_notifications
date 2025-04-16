<?php

declare(strict_types=1);

namespace Mediadreams\MdNotifications\Controller;

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

use Mediadreams\MdNotifications\Domain\Repository\NotificationRepository;
use TYPO3\CMS\Core\Pagination\SlidingWindowPagination;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;

/**
 * Class AbstractController
 * @package Mediadreams\MdNotifications\Controller
 */
abstract class AbstractController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    /**
     * User Id of the logged in user
     *
     * @var int|null
     */
    protected ?int $feuserUid = null;

    public function __construct(protected NotificationRepository $notificationRepository)
    {
    }

    /**
     * Initialize actions
     */
    protected function initializeAction(): void
    {
        // Use stdWrap for given defined settings
        // Thanks to Georg Ringer:
        // https://github.com/georgringer/news/blob/976fe5930cea9693f6cd56b650abe4e876fc70f0/Classes/Controller/NewsController.php#L627
        if (
            isset($this->settings['useStdWrap'])
            && !empty($this->settings['useStdWrap'])
        ) {
            $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
            $typoScriptArray = $typoScriptService->convertPlainArrayToTypoScriptArray($this->settings);
            $stdWrapProperties = GeneralUtility::trimExplode(',', $this->settings['useStdWrap'], true);
            foreach ($stdWrapProperties as $key) {
                if (is_array($typoScriptArray[$key . '.'] ?? null)) {
                    $this->settings[$key] = $this->request->getAttribute('currentContentObject')->stdWrap(
                        $typoScriptArray[$key] ?? '',
                        $typoScriptArray[$key . '.']
                    );
                }
            }
        }

        $this->feuserUid = $this->request->getAttribute('frontend.user')->user['uid'] ?? null;
    }

    /**
     * Get paginated items and paginator for query result
     *
     * @param QueryResult $items
     * @return array
     */
    protected function getPaginatedItems(QueryResult $items): array
    {
        $currentPage = $this->request->hasArgument('currentPageNumber')
            ? (int)$this->request->getArgument('currentPageNumber')
            : 1;

        $itemsPerPage = isset($this->settings['pagination']['itemsPerPage'])? (int)$this->settings['pagination']['itemsPerPage'] : 10;
        $maxNumPages = isset($this->settings['pagination']['maxNumPages'])? (int)$this->settings['pagination']['maxNumPages'] : 5;

        $paginator = new QueryResultPaginator(
            $items,
            $currentPage,
            $itemsPerPage,
        );
        $pagination = new SlidingWindowPagination(
            $paginator,
            $maxNumPages,
        );

        return [
            'notifications' => $pagination->getPaginator()->getPaginatedItems(),
            'pagination' => $pagination,
            'paginator' => $paginator,
            'currentPageNumber' => $paginator->getCurrentPageNumber(),
        ];
    }
}
