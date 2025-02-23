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
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
    protected function initializeAction()
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
                if (is_array($typoScriptArray[$key . '.'])) {
                    $this->settings[$key] = $this->configurationManager->getContentObject()->stdWrap(
                        $typoScriptArray[$key],
                        $typoScriptArray[$key . '.']
                    );
                }
            }
        }

        $this->feuserUid = $this->request->getAttribute('frontend.user')->user['uid'] ?? null;
    }
}
