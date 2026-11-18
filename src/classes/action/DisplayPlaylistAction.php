<?php

declare(strict_types=1);

namespace iutnc\deefy\action;

use iutnc\deefy\auth\AuthnProvider;
use iutnc\deefy\auth\Authz;
use iutnc\deefy\auth\User;
use iutnc\deefy\render\AudioListRenderer;
use iutnc\deefy\render\Renderer;
use iutnc\deefy\repository\DeefyRepository;

/**
 * Fonctionnalité 2 : ?action=playlist&id=N affiche la playlist N
 *                    qui devient la playlist courante (en session).
 * Fonctionnalité 5 : ?action=playlist (sans id) affiche la playlist courante.
 * Autorisation : utilisateur authentifié propriétaire de la playlist.
 */
class DisplayPlaylistAction extends Action
{
    public function execute(): string
    {
        $user = AuthnProvider::getSignedInUser();
        $authz = new Authz($user);
        $authz->checkRole(User::STANDARD_USER);

        if (isset($_GET['id'])) {
            $id = filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id === false) {
                return '<p class="error" role="alert">Identifiant de playlist invalide.</p>';
            }
        } else {
            $current = self::getCurrentPlaylist();
            if ($current === null) {
                return '<h1>Playlist courante</h1>'
                    . '<p>Aucune playlist courante : choisissez-en une dans '
                    . '<a href="?action=mes-playlists">mes playlists</a> ou '
                    . '<a href="?action=add-playlist">créez-en une</a>.</p>';
            }
            $id = $current->id;
        }

        $authz->checkPlaylistOwner($id);
        // relecture en base pour afficher l'état à jour, puis mise en session
        $playlist = DeefyRepository::getInstance()->findPlaylistById($id);
        self::setCurrentPlaylist($playlist);

        return self::renderPlaylist($playlist);
    }

    /** Affichage d'une playlist avec le lien d'ajout de piste (fonctionnalité 3). */
    public static function renderPlaylist(\iutnc\deefy\audio\lists\Playlist $playlist, string $message = ''): string
    {
        $msg = $message === '' ? '' : '<p class="success" role="status">' . $message . '</p>';
        return $msg
            . '<p><small>Playlist courante</small></p>'
            . (new AudioListRenderer($playlist))->render(Renderer::COMPACT)
            . '<p><a href="?action=add-track" role="button">Ajouter une piste</a> '
            . '<a href="?action=mes-playlists" role="button" class="secondary outline">Mes playlists</a></p>';
    }
}
