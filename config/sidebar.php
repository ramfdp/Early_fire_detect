<?php

return [

    /*
    |--------------------------------------------------------------------------
    | View Storage Paths
    |--------------------------------------------------------------------------
    |
    | Most templating systems load templates from disk. Here you may specify
    | an array of paths that should be checked for your views. Of course
    | the usual Laravel view path has already been registered for you.
    |
    */
  'menu' => [
    [
      'icon' => 'fa fa-sitemap',
      'title' => 'Dashboard',
      'url' => '/dashboard/v2',
      'route-name' => 'dashboard-v2'
    ],
    [
      'icon' => 'fa fa-table',
      'title' => 'History',
      'url' => '/History',
      'route-name' => 'History'
    ]
  ]
];
