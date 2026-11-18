<?php

declare(strict_types=1);

namespace iutnc\deefy\action;

use iutnc\deefy\auth\AuthnProvider;
use iutnc\deefy\auth\Authz;
use iutnc\deefy\auth\User;
use iutnc\deefy\repository\DeefyRepository;

/**
 * Fonctionnalité 1 : mes playlists (utilisateur authentifié).
 * Chaque playlist est cliquable (fonctionnalité 2).
 */
class DisplayPlaylistsAction extends Action
{
    public function execute(): string
    {
        $user = AuthnProvider::getSignedInUser();
        (new Authz($user))->checkRole(User::STANDARD_USER);

        $repo = DeefyRepository::getInstance();
        $playlists = $user->isAdmin() ? $repo->findAllPlaylists() : $repo->findPlaylistsByUser($user->id);

        $html = '<h1>' . ($user->isAdmin() ? 'Toutes les playlists' : 'Mes playlists') . '</h1>';
        if (count($playlists) === 0) {
            return $html . '<p>Vous n\'avez encore aucune playlist.</p>'
                . '<p><a href="?action=add-playlist" role="button">Créer une playlist</a></p>';
        }

        $html = $html . '<ul>';
        foreach ($playlists as $pl) {
            $html = $html . '<li><a href="?action=display-playlist&amp;id=' . $pl->id . '">' . $pl->nom . '</a>'
                . ' <small>(' . $pl->nbPistes . ' piste(s), '
                . $pl->dureeTotale . ' secondes)</small></li>';
        }
        return $html . '</ul><p><a href="?action=add-playlist">+ Créer une nouvelle playlist</a></p>';
    }
}
