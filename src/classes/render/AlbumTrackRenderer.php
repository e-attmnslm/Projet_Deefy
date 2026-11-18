<?php

declare(strict_types=1);

namespace iutnc\deefy\render;

use iutnc\deefy\audio\tracks\AlbumTrack;

class AlbumTrackRenderer extends AudioTrackRenderer
{
    public function __construct(AlbumTrack $at)
    {
        parent::__construct($at);
    }

    protected function renderCompact(): string
    {
        $titre = $this->at->titre;
        $artiste = $this->at->artiste;
        $duree = $this->at->duree . ' secondes';
        return "<strong>{$titre}</strong> — {$artiste} <small>({$duree})</small> {$this->lecteur()}";
    }

    protected function renderLong(): string
    {
        $t = $this->at;
        return '<article><h3>' . $t->titre . '</h3><ul>'
            . '<li>Artiste : ' . $t->artiste . '</li>'
            . '<li>Album : ' . $t->album . ' (piste n°' . $t->numero . ')</li>'
            . '<li>Année : ' . $t->annee . '</li>'
            . '<li>Genre : ' . $t->genre . '</li>'
            . '<li>Durée : ' . $t->duree . ' secondes</li>'
            . '</ul>' . $this->lecteur() . '</article>';
    }
}
