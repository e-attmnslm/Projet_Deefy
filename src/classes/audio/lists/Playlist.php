<?php

declare(strict_types=1);

namespace iutnc\deefy\audio\lists;

use iutnc\deefy\audio\tracks\AudioTrack;

/**
 * Playlist modifiable : ajout/suppression de pistes.
 */
class Playlist extends AudioList
{
    public function ajouterPiste(AudioTrack $piste): void
    {
        $this->pistes[] = $piste;
        $this->recalculer();
    }

    public function supprimerPiste(int $indice): void
    {
        if (isset($this->pistes[$indice])) {
            unset($this->pistes[$indice]);
            $this->recalculer();
        }
    }

    /** Ajoute une liste de pistes en évitant les doublons. */
    public function ajouterListe(array $pistes): void
    {
        foreach ($pistes as $piste) {
            $dejaPresente = false;
            foreach ($this->pistes as $p) {
                if ($p === $piste) {
                    $dejaPresente = true;
                }
            }
            if (!$dejaPresente) {
                $this->pistes[] = $piste;
            }
        }
        $this->recalculer();
    }
}
