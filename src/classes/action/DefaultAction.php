<?php

declare(strict_types=1);

namespace iutnc\deefy\action;

use iutnc\deefy\auth\AuthnProvider;

class DefaultAction extends Action
{
    public function execute(): string
    {
        $html = '<h1>Bienvenue sur Deefy</h1>';
        if (AuthnProvider::isSignedIn()) {
            $user = AuthnProvider::getSignedInUser();
            $html = $html . '<p>Vous êtes connecté en tant que <strong>' . $user->email . '</strong>.</p>'
                . '<ul>'
                . '<li><a href="?action=mes-playlists">Mes playlists</a></li>'
                . '<li><a href="?action=playlist">Afficher la playlist courante</a></li>'
                . '<li><a href="?action=add-playlist">Créer une playlist vide</a></li>'
                . '</ul>';
        } else {
            $html = $html . '<p>Deefy vous permet de créer et d\'écouter vos playlists.</p>'
                . '<p><a href="?action=signin" role="button">Se connecter</a> '
                . '<a href="?action=add-user" role="button" class="secondary">S\'inscrire</a></p>';
        }
        return $html;
    }
}
