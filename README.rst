MAL-api
======

Anime API for MyAnimeList.net written in PHP

Installation
------------

.. code-block:: bash

    $ composer require animelist/mal-api

Usage
-----

Obtain anime information:


.. code-block:: php

    require __DIR__ . '/vendor/autoload.php';
    $api = new MalApi\Api;
    $url = 'https://myanimelist.net/anime/1/Cowboy_Bebop';
    $anime = $api->getAnime($url);
    echo 'Anime id: ' . $anime->getExternalId() . '<br>';
    var_dump($anime);



Add and update:


.. code-block:: php

    require __DIR__ . '/vendor/autoload.php';
    $api = new MalApi\Api;
    $api->setAuth('user', 'password');
    $api->add(['id' => 1, 'status' => $api::STATUS_PLAN_TO_WATCH]);
    $api->update(['id' => 1, 'status' => $api::STATUS_WATCHING, 'episode' => 1]);


Delete:


.. code-block:: php

    require __DIR__ . '/vendor/autoload.php';
    $api = new MalApi\Api;
    $api->setAuth('user', 'password');
    $api->delete(1);
    