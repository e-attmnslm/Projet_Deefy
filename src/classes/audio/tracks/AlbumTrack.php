<?php

declare(strict_types=1);

namespace iutnc\deefy\audio\tracks;

/**
 * Piste faisant partie d'un album.
 */
class AlbumTrack extends AudioTrack
{
    protected string $artiste = '';
    protected int $annee = 0;

    public function __construct(
        string $titre,
        string $chemin,
        protected string $album,
        protected int $numero
    ) {
        parent::__construct($titre, $chemin);
    }

    public function setArtiste(string $artiste): void
    {
        $this->artiste = $artiste;
    }

    public function setAnnee(int $annee): void
    {
        $this->annee = $annee;
    }
}
