<?php

declare(strict_types=1);

namespace iutnc\deefy\render;

use iutnc\deefy\audio\tracks\PodcastTrack;

class PodcastRenderer extends AudioTrackRenderer
{
    public function __construct(PodcastTrack $podcast)
    {
        parent::__construct($podcast);
    }

    protected function renderCompact(): string
    {
        $titre = $this->at->titre;
        $auteur = $this->at->auteur;
        $duree = $this->at->duree . ' secondes';
        return "<strong>{$titre}</strong> — podcast de {$auteur} <small>({$duree})</small> {$this->lecteur()}";
    }

    protected function renderLong(): string
    {
        $t = $this->at;
        return '<article><h3>' . $t->titre . '</h3><ul>'
            . '<li>Auteur : ' . $t->auteur . '</li>'
            . '<li>Date : ' . $t->date . '</li>'
            . '<li>Genre : ' . $t->genre . '</li>'
            . '<li>Durée : ' . $t->duree . ' secondes</li>'
            . '</ul>' . $this->lecteur() . '</article>';
    }
}
