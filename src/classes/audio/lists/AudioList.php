<?php

declare(strict_types=1);

namespace iutnc\deefy\audio\lists;

use iutnc\deefy\exception\InvalidPropertyNameException;

/**
 * Liste de pistes audio : nom, pistes, nombre de pistes, durée totale.
 */
class AudioList
{
    protected ?int $id = null;
    protected string $nom;
    protected array $pistes = [];
    protected int $nbPistes = 0;
    protected int $dureeTotale = 0;

    public function __construct(string $nom, array $pistes = [])
    {
        $this->nom = $nom;
        $this->pistes = $pistes;
        $this->recalculer();
    }

    public function __get(string $name): mixed
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        throw new InvalidPropertyNameException("invalid property : {$name}");
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    protected function recalculer(): void
    {
        $this->nbPistes = count($this->pistes);
        $this->dureeTotale = 0;
        foreach ($this->pistes as $piste) {
            $this->dureeTotale += $piste->duree;
        }
    }
}
