<?php

namespace iutnc\deefy\repository;

use iutnc\deefy\audio\lists\Playlist;
use iutnc\deefy\audio\tracks\AlbumTrack;
use iutnc\deefy\audio\tracks\AudioTrack;
use iutnc\deefy\audio\tracks\PodcastTrack;
use iutnc\deefy\exception\RepositoryException;

/**
 * Repository (singleton) regroupant toutes les requêtes SQL de l'application.
 * Toutes les requêtes utilisent des requêtes préparées (parade injection SQL).
 */
class DeefyRepository
{
    /** Dossier (relatif à index.php) contenant les fichiers audio. */
    public const AUDIO_DIR = 'audio/';

    private \PDO $pdo;
    private static ?DeefyRepository $instance = null;
    private static array $config = [];

    private function __construct(array $conf)
    {
        $this->pdo = new \PDO($conf['dsn'], $conf['user'], $conf['pass'], [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_EMULATE_PREPARES => false,
            \PDO::ATTR_STRINGIFY_FETCHES => false,
        ]);
        $this->pdo->exec("SET NAMES 'utf8mb4'");
    }

    public static function setConfig(string $file): void
    {
        $conf = @parse_ini_file($file);
        if ($conf === false) {
            throw new RepositoryException("Error reading configuration file");
        }
        $driver = $conf['driver'] ?? 'mysql';
        $host = $conf['host'] ?? 'localhost';
        $port = isset($conf['port']) && $conf['port'] !== '' ? ";port={$conf['port']}" : '';
        self::$config = [
            'dsn' => "{$driver}:host={$host}{$port};dbname={$conf['database']};charset=utf8mb4",
            'user' => $conf['username'],
            'pass' => $conf['password'] ?? '',
        ];
    }

    public static function getInstance(): DeefyRepository
    {
        if (is_null(self::$instance)) {
            if (self::$config === []) {
                throw new RepositoryException("Repository not configured");
            }
            self::$instance = new DeefyRepository(self::$config);
        }
        return self::$instance;
    }

    /* ======================== utilisateurs ======================== */

    /** @return array{id:int,email:string,passwd:string,role:int}|null */
    public function findUserByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, email, passwd, role FROM User WHERE email = :email');
        $stmt->execute(['email' => $email]);
        foreach ($stmt as $row) {
            return $row;
        }
        return null;
    }

    public function createUser(string $email, string $hash, int $role): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO User (email, passwd, role) VALUES (:email, :passwd, :role)');
        $stmt->execute(['email' => $email, 'passwd' => $hash, 'role' => $role]);
        return $this->pdo->lastInsertId();
    }

    /* ========================= playlists ========================== */

    public function findPlaylistById(int $id): Playlist
    {
        $stmt = $this->pdo->prepare('SELECT id, nom FROM playlist WHERE id = :id');
        $stmt->execute(['id' => $id]);
        foreach ($stmt as $row) {
            $pl = new Playlist($row['nom'], $this->findTracksOfPlaylist($id));
            $pl->setId($row['id']);
            return $pl;
        }
        throw new RepositoryException("Playlist {$id} introuvable");
    }

    /** @return Playlist[] playlists dont l'utilisateur est propriétaire */
    public function findPlaylistsByUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.id FROM playlist p
               INNER JOIN user2playlist u ON u.id_pl = p.id
              WHERE u.id_user = :user
              ORDER BY p.nom'
        );
        $stmt->execute(['user' => $userId]);
        $playlists = [];
        foreach ($stmt as $row) {
            $playlists[] = $this->findPlaylistById($row['id']);
        }
        return $playlists;
    }

    /** @return Playlist[] toutes les playlists (utilisé pour le rôle ADMIN) */
    public function findAllPlaylists(): array
    {
        $playlists = [];
        $stmt = $this->pdo->prepare('SELECT id FROM playlist ORDER BY nom');
        $stmt->execute();
        foreach ($stmt as $row) {
            $playlists[] = $this->findPlaylistById($row['id']);
        }
        return $playlists;
    }

    /** Enregistre une playlist vide et la rattache à son propriétaire. */
    public function saveEmptyPlaylist(Playlist $pl, int $ownerId): Playlist
    {
        $stmt = $this->pdo->prepare('INSERT INTO playlist (nom) VALUES (:nom)');
        $stmt->execute(['nom' => $pl->nom]);
        $pl->setId($this->pdo->lastInsertId());

        $stmt = $this->pdo->prepare('INSERT INTO user2playlist (id_user, id_pl) VALUES (:user, :pl)');
        $stmt->execute(['user' => $ownerId, 'pl' => $pl->id]);
        return $pl;
    }

    public function isPlaylistOwner(int $userId, int $playlistId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM user2playlist WHERE id_user = :user AND id_pl = :pl');
        $stmt->execute(['user' => $userId, 'pl' => $playlistId]);
        foreach ($stmt as $row) {
            return true;
        }
        return false;
    }

    /* =========================== pistes =========================== */

    /** @return AudioTrack[] pistes de la playlist, dans l'ordre */
    private function findTracksOfPlaylist(int $playlistId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT t.* FROM track t
               INNER JOIN playlist2track p2t ON p2t.id_track = t.id
              WHERE p2t.id_pl = :pl
              ORDER BY p2t.no_piste_dans_liste'
        );
        $stmt->execute(['pl' => $playlistId]);
        $tracks = [];
        foreach ($stmt as $row) {
            $tracks[] = $this->rowToTrack($row);
        }
        return $tracks;
    }

    private function rowToTrack(array $row): AudioTrack
    {
        $chemin = is_null($row['filename']) || $row['filename'] === '' ? '' : self::AUDIO_DIR . $row['filename'];
        if ($row['type'] === 'P') {
            $t = new PodcastTrack($row['titre'], $chemin);
            $t->setAuteur($row['auteur_podcast'] ?? '');
            $t->setDate($row['date_posdcast'] ?? '');
        } else {
            $t = new AlbumTrack($row['titre'], $chemin, $row['titre_album'] ?? '', $row['numero_album'] ?? 0);
            $t->setArtiste($row['artiste_album'] ?? '');
            $t->setAnnee($row['annee_album'] ?? 0);
        }
        $t->setId($row['id']);
        $t->setGenre($row['genre'] ?? '');
        $t->setDuree($row['duree'] ?? 0);
        return $t;
    }

    /** Enregistre une nouvelle piste ; son id est mis à jour. */
    public function saveTrack(AudioTrack $t): AudioTrack
    {
        // chemin = "audio/xxx.mp3" : on n'enregistre que le nom du fichier
        $filename = $t->chemin === '' ? null : substr($t->chemin, strlen(self::AUDIO_DIR));
        $data = [
            'titre' => $t->titre, 'genre' => $t->genre, 'duree' => $t->duree, 'filename' => $filename,
            'type' => 'A', 'artiste' => null, 'album' => null, 'annee' => null, 'numero' => null,
            'auteur' => null, 'date' => null,
        ];
        if (property_exists($t, 'auteur')) { // PodcastTrack
            $data['type'] = 'P';
            $data['auteur'] = $t->auteur;
            $data['date'] = $t->date === '' ? null : $t->date;
        } else { // AlbumTrack
            $data['artiste'] = $t->artiste;
            $data['album'] = $t->album;
            $data['annee'] = $t->annee === 0 ? null : $t->annee;
            $data['numero'] = $t->numero;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO track (titre, genre, duree, filename, type, artiste_album, titre_album,
                                annee_album, numero_album, auteur_podcast, date_posdcast)
             VALUES (:titre, :genre, :duree, :filename, :type, :artiste, :album,
                     :annee, :numero, :auteur, :date)'
        );
        $stmt->execute($data);
        $t->setId($this->pdo->lastInsertId());
        return $t;
    }

    /** Ajoute une piste existante à la fin d'une playlist. */
    public function addTrackToPlaylist(int $trackId, int $playlistId): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(MAX(no_piste_dans_liste), 0) + 1 AS no FROM playlist2track WHERE id_pl = :pl'
        );
        $stmt->execute(['pl' => $playlistId]);
        $no = 1;
        foreach ($stmt as $row) {
            $no = $row['no'];
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO playlist2track (id_pl, id_track, no_piste_dans_liste) VALUES (:pl, :track, :no)'
        );
        $stmt->execute(['pl' => $playlistId, 'track' => $trackId, 'no' => $no]);
    }
}
