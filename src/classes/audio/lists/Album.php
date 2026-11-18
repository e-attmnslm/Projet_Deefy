<?php

declare(strict_types=1);

namespace iutnc\deefy\audio\lists;

use iutnc\deefy\exception\InvalidPropertyValueException;

/**
 * Album : liste non vide de pistes, avec artiste et date de sortie.
 */
class Album extends AudioList
{
    protected string $artiste = '';
    protected string $dateSortie = '';

    public function __construct(string $nom, array $pistes)
    {
        if (count($pistes) === 0) {
            throw new InvalidPropertyValueException('un album ne peut pas être vide');
        }
        parent::__construct($nom, $pistes);
    }

    public function setArtiste(string $artiste): void
    {
        $this->artiste = $artiste;
    }

    public function setDateSortie(string $date): void
    {
        $this->dateSortie = $date;
    }
}
