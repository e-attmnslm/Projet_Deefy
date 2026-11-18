<?php

declare(strict_types=1);

namespace iutnc\deefy\action;

use iutnc\deefy\exception\InvalidPropertyValueException;

use iutnc\deefy\audio\lists\Playlist;

/**
 * Classe de base des actions : chaque fonctionnalité est une sous-classe
 * dont la méthode execute() retourne un bloc HTML.
 */
abstract class Action
{
    protected ?string $http_method = null;
    protected ?string $hostname = null;
    protected ?string $script_name = null;

    public function __construct()
    {
        $this->http_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->hostname = $_SERVER['HTTP_HOST'] ?? '';
        $this->script_name = $_SERVER['SCRIPT_NAME'] ?? '';
    }

    abstract public function execute(): string;

    protected function isPost(): bool
    {
        return $this->http_method === 'POST';
    }

    /* --------------------- playlist courante ---------------------- */

    protected static function setCurrentPlaylist(Playlist $pl): void
    {
        $_SESSION['playlist'] = serialize($pl);
    }

    protected static function getCurrentPlaylist(): ?Playlist
    {
        if (!isset($_SESSION['playlist'])) {
            return null;
        }
        return unserialize($_SESSION['playlist']);
    }

    /**
     * Récupère un champ texte de $_POST, filtré avec FILTER_SANITIZE_SPECIAL_CHARS
     * (les caractères spéciaux HTML sont encodés : parade XSS).
     * @throws InvalidPropertyValueException si le champ dépasse la longueur maximale
     */
    protected static function champ(string $name, int $maxLength, string $label): string
    {
        $value = filter_var($_POST[$name] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
        if (strlen($value) > $maxLength) {
            throw new InvalidPropertyValueException("{$label} : {$maxLength} caractères maximum");
        }
        return $value;
    }

    /** Valeur déjà saisie, filtrée, pour pré-remplir un formulaire. */
    protected static function saisie(string $name): string
    {
        return filter_var($_POST[$name] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
    }
}
