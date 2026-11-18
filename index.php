<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use iutnc\deefy\action as act;
use iutnc\deefy\dispatch\Dispatcher;
use iutnc\deefy\repository\DeefyRepository;

session_start();

try {
    DeefyRepository::setConfig(__DIR__ . '/config/deefy.db.ini');

    $app = (new Dispatcher())
        ->addRoute('default', act\DefaultAction::class)
        ->addRoute('mes-playlists', act\DisplayPlaylistsAction::class)   // 1
        ->addRoute('display-playlist', act\DisplayPlaylistAction::class) // 2 (?action=display-playlist&id=N)
        ->addRoute('add-track', act\AddTrackAction::class)               // 3
        ->addRoute('add-playlist', act\AddPlaylistAction::class)         // 4
        ->addRoute('playlist', act\DisplayPlaylistAction::class)         // 5 (playlist courante en session)
        ->addRoute('add-user', act\AddUserAction::class)                 // 6
        ->addRoute('signin', act\SigninAction::class)                    // 7
        ->addRoute('signout', act\SignoutAction::class);

    $app->run();
} catch (\Throwable $e) {
    http_response_code(500);
   
    
}
