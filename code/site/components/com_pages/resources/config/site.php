<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

return [

    'identifiers' => [
        'com://site/pages.template.filter.asset' => [
            'schemes' =>  $config['aliases'] ?? array()
        ],
        'page.registry' => [
            'cache'       => $config['page_cache'] ?? (JDEBUG ? false : true),
            'cache_time'  => $config['page_cache_time'] ?? 60*60*24, //1d
            'cache_path'  => $config['page_cache_path'] ?? $base_path.'/cache/pages',
            'collections' => $config['collections'] ?? array(),
            'redirects'   => isset($config['redirects']) ? array_flip($config['redirects']) : array(),
        ],
        'data.registry' => [
            'cache'      => $config['data_cache'] ?? (JDEBUG ? false : true),
            'cache_time' => $config['data_cache_time'] ?? 60*60*24, //1d
            'cache_path' => $config['data_cache_path'] ?? $base_path.'/cache/data',
        ],
        'template.engine.factory' => [
            'cache'      => $config['template_cache'] ?? (JDEBUG ? false : true),
            'cache_path' => $config['template_cache_path'] ?? $base_path.'/cache/templates',
        ],
        'com://site/pages.dispatcher.behavior.cacheable' => [
            'cache'              => $config['http_cache'] ?? false,
            'cache_path'         => $config['http_cache_path'] ?? $base_path.'/cache/responses',
            'cache_time'         => $config['http_cache_time']       ?? 60*15,  //15min
            'cache_time_shared'  => $config['http_cache_time_proxy'] ?? 60*60*2, //2h
            'cache_invalidation' => $config['http_cache_invalidation'] ?? true,
        ],
        'com://site/pages.http.client' => [
            'cache'       => $config['http_resource_cache'] ?? (JDEBUG ? false : true),
            'cache_time'  => $config['http_resource_cache_time'] ?? 60*60*24, //1d
            'cache_path'  => $config['http_resource_cache_path'] ??  $base_path.'/cache/resources',
            'cache_force' => $config['http_resource_cache_force'] ?? false,
            'debug'       => $config['http_resource_cache_debug'] ?? (JDEBUG ? true : false),
        ],
        'com://site/pages.model.entity.page' => [
            'data' => [
                'metadata' => $config['metadata'] ?? array(),
            ]
        ],
    ],
    'extensions' => $config['extensions'] ?? array(),
];