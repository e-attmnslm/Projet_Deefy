<?php

declare(strict_types=1);

namespace iutnc\deefy\action;

use iutnc\deefy\exception\InvalidPropertyValueException;

use iutnc\deefy\audio\tracks\AlbumTrack;
use iutnc\deefy\audio\tracks\AudioTrack;
use iutnc\deefy\audio\tracks\PodcastTrack;
use iutnc\deefy\auth\AuthnProvider;
use iutnc\deefy\auth\Authz;
use iutnc\deefy\auth\User;
use iutnc\deefy\repository\DeefyRepository;

/**
 * Fonctionnalité 3 : ajouter une piste (album ou podcast) à la playlist courante.
 * Autorisation : utilisateur authentifié propriétaire de la playlist courante.
 */
class AddTrackAction extends Action
{
    private const MAX_UPLOAD = 20 * 1024 * 1024; // 20 Mo

    public function execute(): string
    {
        $user = AuthnProvider::getSignedInUser();
        $authz = new Authz($user);
        $authz->checkRole(User::STANDARD_USER);

        $current = self::getCurrentPlaylist();
        if ($current === null) {
            return '<h1>Ajouter une piste</h1>'
                . '<p>Aucune playlist courante : ouvrez d\'abord une playlist depuis '
                . '<a href="?action=mes-playlists">mes playlists</a>.</p>';
        }
        $authz->checkPlaylistOwner($current->id);

        if (!$this->isPost()) {
            return $this->form($current->nom);
        }
        try {
            $track = $this->buildTrack();
        } catch (InvalidPropertyValueException $e) {
            return $this->form($current->nom, $e->getMessage());
        }

        $repo = DeefyRepository::getInstance();
        $repo->saveTrack($track);
        $repo->addTrackToPlaylist($track->id, $current->id);

        $playlist = $repo->findPlaylistById($current->id);
        self::setCurrentPlaylist($playlist);
        return DisplayPlaylistAction::renderPlaylist($playlist, 'La piste « ' . $track->titre . ' » a été ajoutée.');
    }

    /** Construit la piste à partir des données du formulaire (filtrées et validées). */
    private function buildTrack(): AudioTrack
    {
        // longueurs maximales = tailles des colonnes de la table track
        $type = ($_POST['type'] ?? 'A') === 'P' ? 'P' : 'A';
        $titre = self::champ('titre', 100, 'Titre');
        $genre = self::champ('genre', 30, 'Genre');
        $duree = filter_var($_POST['duree'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 99999]]);

        if ($titre === '') {
            throw new InvalidPropertyValueException('Le titre est obligatoire');
        }
        if ($duree === false) {
            throw new InvalidPropertyValueException('La durée doit être un nombre entier de secondes positif');
        }

        if ($type === 'P') {
            $auteur = self::champ('auteur', 100, 'Auteur');
            $date = self::champ('date', 10, 'Date');
            if ($date !== '' && !preg_match('#^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$#', $date)) {
                throw new InvalidPropertyValueException('Date de podcast invalide');
            }
            $track = new PodcastTrack($titre, $this->handleUpload());
            $track->setAuteur($auteur);
            $track->setDate($date);
        } else {
            $artiste = self::champ('artiste', 30, 'Artiste');
            $album = self::champ('album', 30, 'Album');
            $numero = filter_var($_POST['numero'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 999]]);
            $annee = ($_POST['annee'] ?? '') === '' ? 0
                : filter_var($_POST['annee'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1800, 'max_range' => 2100]]);
            if ($annee === false) {
                throw new InvalidPropertyValueException('Année invalide');
            }
            $track = new AlbumTrack($titre, $this->handleUpload(), $album, $numero === false ? 1 : $numero);
            $track->setArtiste($artiste);
            $track->setAnnee($annee);
        }
        $track->setGenre($genre);
        $track->setDuree($duree);
        return $track;
    }

    /**
     * Fichier audio optionnel (TD12) : uniquement des .mp3, enregistrés dans audio/
     * avec un nom généré aléatoirement.
     * @return string chemin du fichier enregistré, ou '' si aucun fichier
     */
    private function handleUpload(): string
    {
        if (!isset($_FILES['fichier']) || $_FILES['fichier']['name'] === '') { // aucun fichier choisi
            return '';
        }
        $file = $_FILES['fichier'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidPropertyValueException('Erreur lors du téléversement du fichier');
        }
        if ($file['size'] > self::MAX_UPLOAD) {
            throw new InvalidPropertyValueException('Fichier trop volumineux (20 Mo maximum)');
        }
        // vérification du type demandée dans le TD12 (interdit notamment les .php)
        if (substr($file['name'], -4) !== '.mp3' || $file['type'] !== 'audio/mpeg') {
            throw new InvalidPropertyValueException('Seuls les fichiers .mp3 sont acceptés');
        }

        $name = bin2hex(random_bytes(16)) . '.mp3';
        $dest = __DIR__ . '/../../../' . DeefyRepository::AUDIO_DIR . $name;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new InvalidPropertyValueException('Impossible d\'enregistrer le fichier');
        }
        return DeefyRepository::AUDIO_DIR . $name;
    }

    private function form(string $playlistName, string $error = ''): string
    {
        $err = $error === '' ? '' : '<p class="error" role="alert">' . $error . '</p>';
        $isPodcast = ($_POST['type'] ?? '') === 'P';
        $year = date('Y');

        return '<h1>Ajouter une piste</h1>'
            . '<p>Playlist courante : <strong>' . $playlistName . '</strong></p>' . $err
            . '<form method="post" action="?action=add-track" enctype="multipart/form-data">'
            . '<fieldset><legend>Type de piste</legend>'
            . '<label><input type="radio" name="type" value="A"' . ($isPodcast ? '' : ' checked') . '> Piste d\'album</label>'
            . '<label><input type="radio" name="type" value="P"' . ($isPodcast ? ' checked' : '') . '> Podcast</label>'
            . '</fieldset>'
            . '<label for="titre">Titre *</label>'
            . '<input type="text" id="titre" name="titre" required maxlength="100" value="' . self::saisie('titre') . '">'
            . '<div class="grid">'
            . '<div><label for="genre">Genre</label>'
            . '<input type="text" id="genre" name="genre" maxlength="30" value="' . self::saisie('genre') . '"></div>'
            . '<div><label for="duree">Durée (secondes)</label>'
            . '<input type="number" id="duree" name="duree" min="0" max="99999" value="' . self::saisie('duree') . '"></div>'
            . '</div>'
            . '<label for="fichier">Fichier audio (.mp3, optionnel)</label>'
            . '<input type="file" id="fichier" name="fichier" accept=".mp3,audio/mpeg">'
            . '<fieldset><legend>Piste d\'album</legend>'
            . '<div class="grid">'
            . '<div><label for="artiste">Artiste</label>'
            . '<input type="text" id="artiste" name="artiste" maxlength="30" value="' . self::saisie('artiste') . '"></div>'
            . '<div><label for="album">Album</label>'
            . '<input type="text" id="album" name="album" maxlength="30" value="' . self::saisie('album') . '"></div>'
            . '</div><div class="grid">'
            . '<div><label for="annee">Année</label>'
            . '<input type="number" id="annee" name="annee" min="1800" max="2100" placeholder="' . $year . '" value="' . self::saisie('annee') . '"></div>'
            . '<div><label for="numero">N° de piste</label>'
            . '<input type="number" id="numero" name="numero" min="1" max="999" value="' . self::saisie('numero') . '"></div>'
            . '</div></fieldset>'
            . '<fieldset><legend>Podcast</legend>'
            . '<div class="grid">'
            . '<div><label for="auteur">Auteur</label>'
            . '<input type="text" id="auteur" name="auteur" maxlength="100" value="' . self::saisie('auteur') . '"></div>'
            . '<div><label for="date">Date</label>'
            . '<input type="date" id="date" name="date" value="' . self::saisie('date') . '"></div>'
            . '</div></fieldset>'
            . '<button type="submit">Ajouter la piste</button>'
            . '</form>';
    }
}
