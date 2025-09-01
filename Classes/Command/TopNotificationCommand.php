<?php

declare(strict_types=1);

namespace Mediadreams\MdNotifications\Command;

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
use Mediadreams\MdNotifications\Service\MailService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[AsCommand(
    name: 'mdNotifications:topnotification',
    description: 'This command will send emails about one open (top-)notification.',
)]
class TopNotificationCommand extends Command
{
    public function __construct(
        protected readonly NotificationRepository $notificationRepository
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This command will send emails about one open (top-)notification')
            ->addArgument(
                'mailTemplate',
                InputArgument::REQUIRED,
                'Add the name of the HTML file, which is used for the e-mail.'
            )->addArgument(
                'toprecordkey',
                InputArgument::REQUIRED,
                'Tablename of the top-notification. tx_news_domain_model_news/pages'
            )->addArgument(
                'toprecordid',
                InputArgument::REQUIRED,
                'Uid of the top-notification.'
            )->addArgument(
                'mailSubject',
                InputArgument::OPTIONAL,
                'The subject of the email. If empty: title of news/page'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $subject = $input->getArgument('mailSubject');
        $record_key = $input->getArgument('toprecordkey');
        $record_id = (int)$input->getArgument('toprecordid');
        $users = $this->notificationRepository->getUsersWithTopNotification($record_key, $record_id);
        foreach ($users as $data) {
            if (GeneralUtility::validEmail($data['user']['email']) === true) {
                if ($subject == '') {
                    $subject = isset($data['notification_records'][0]['record_data']['title']) ? $data['notification_records'][0]['record_data']['title'] : 'neue Mitteilung';
                }
                MailService::sendMail(
                    $data['user']['email'],
                    $subject,
                    array_merge(
                        $data['user'],
                        ['notificationItems' => count($data['notification_records'])],
                        ['notificationData' => $data['notification_records']]
                    ),
                    !empty($input->getArgument('mailTemplate'))? $input->getArgument('mailTemplate'):'TopNotification'
                );
            }
        }

        return Command::SUCCESS;
    }   
}
