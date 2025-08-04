<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Notifications',
    'description' => 'Notify frontend users about the creation of new data records. Every data type can be configured easily, for example pages (blog posts) or tx_news records.',
    'category' => 'fe',
    'author' => 'Christoph Daecke',
    'author_email' => 'typo3@mediadreams.org',
    'state' => 'stable',
    'version' => '1.0.2',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
