<?php

declare(strict_types=1);

namespace iutnc\deefy\render;

use iutnc\deefy\audio\lists\AudioList;
use iutnc\deefy\audio\tracks\AlbumTrack;
use iutnc\deefy\audio\tracks\PodcastTrack;

/**
 * Affichage d'une liste : nom, pistes en mode compact, nombre de pistes et durée totale.
 */
class AudioListRenderer implements Renderer
{
    public function __construct(protected AudioList $list)
    {
    }

    public function render(int $selector = Renderer::COMPACT): string
    {
        $html = '<h2>' . $this->list->nom . '</h2>';
        if ($this->list->nbPistes === 0) {
            $html = $html . '<p><em>Cette playlist ne contient encore aucune piste.</em></p>';
        } else {
            $html = $html . '<ol>';
            foreach ($this->list->pistes as $piste) {
                $html = $html . '<li>' . $this->trackRenderer($piste)->render($selector) . '</li>';
            }
            $html = $html . '</ol>';
        }
        $html = $html . '<p>' . $this->list->nbPistes . ' piste(s) — durée totale : '
            . $this->list->dureeTotale . ' secondes</p>';
        return $html;
    }

    private function trackRenderer(object $piste): AudioTrackRenderer
    {
        if (property_exists($piste, 'auteur')) { // PodcastTrack
            return new PodcastRenderer($piste);
        }
        return new AlbumTrackRenderer($piste);
    }
}
