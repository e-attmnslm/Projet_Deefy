<?php

declare(strict_types=1);

namespace iutnc\deefy\action;

use iutnc\deefy\exception\InvalidPropertyValueException;

use iutnc\deefy\audio\lists\Playlist;
use iutnc\deefy\auth\AuthnProvider;
use iutnc\deefy\auth\Authz;
use iutnc\deefy\auth\User;
use iutnc\deefy\repository\DeefyRepository;

/**
 * Fonctionnalité 4 : créer une playlist vide, stockée en BD,
 * qui devient la playlist courante.
 */
class AddPlaylistAction extends Action
{
    private const MAX_NOM = 100; // playlist.nom varchar(100)

    public function execute(): string
    {
        $user = AuthnProvider::getSignedInUser();
        (new Authz($user))->checkRole(User::STANDARD_USER);

        if (!$this->isPost()) {
            return $this->form();
        }
        try {
            $nom = self::champ('nom', self::MAX_NOM, 'Nom de la playlist');
        } catch (InvalidPropertyValueException $e) {
            return $this->form($e->getMessage());
        }
        if ($nom === '') {
            return $this->form('Le nom de la playlist est obligatoire');
        }

        $playlist = DeefyRepository::getInstance()->saveEmptyPlaylist(new Playlist($nom), $user->id);
        self::setCurrentPlaylist($playlist);

        return DisplayPlaylistAction::renderPlaylist($playlist, 'Playlist créée, c\'est maintenant votre playlist courante.');
    }

    private function form(string $error = ''): string
    {
        $err = $error === '' ? '' : '<p class="error" role="alert">' . $error . '</p>';
        return '<h1>Créer une playlist vide</h1>' . $err
            . '<form method="post" action="?action=add-playlist">'
            . '<label for="nom">Nom de la playlist</label>'
            . '<input type="text" id="nom" name="nom" required maxlength="' . self::MAX_NOM . '" placeholder="Ma playlist">'
            . '<button type="submit">Créer</button>'
            . '</form>';
    }
}
