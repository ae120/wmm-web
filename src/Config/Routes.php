<?php
namespace Config;

class Routes
{
    public $route1 = [
        'path' => '/index/{id}',
        'defaults' => [
            '_controller' => 'Api:Index:index',
        ],
        'requirements' => [
            'id' => '\d+'
        ]
    ];

    public $route2 = [
        'path' => '/articles/{_locale}/{year}/{slug}.{_format}',
        'defaults' => [
            '_controller' => 'Api:Index:articles'
        ],
        'methods' => ['POST'],
        'requirements' => [
            '_locale' => 'en|fr',
            '_format' => 'html|rss',
            'year' => '\d+'
        ]
    ];
}