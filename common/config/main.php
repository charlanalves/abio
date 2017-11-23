<?php
return [
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'xml' => [
            'class' => 'common\components\dataDumpComponent',
        ],
    ],
];
