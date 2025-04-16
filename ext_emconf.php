<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Notifications',
    'description' => 'Notify frontend users about new data records.',
    'category' => '',
    'author' => 'Christoph Daecke',
    'author_email' => 'typo3@mediadreams.org',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
