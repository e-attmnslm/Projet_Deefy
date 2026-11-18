<?php

declare(strict_types=1);

namespace iutnc\deefy\render;

use iutnc\deefy\audio\tracks\AudioTrack;

abstract class AudioTrackRenderer implements Renderer
{
    public function __construct(protected AudioTrack $at)
    {
    }

    public function render(int $selector = Renderer::COMPACT): string
    {
        return match ($selector) {
            Renderer::LONG => $this->renderLong(),
            default => $this->renderCompact(),
        };
    }

    /** Lecteur audio, uniquement si un fichier est associé à la piste. */
    protected function lecteur(): string
    {
        if ($this->at->chemin === '') {
            return '';
        }
        return "<audio controls preload=\"none\" src=\"{$this->at->chemin}\"></audio>";
    }

    abstract protected function renderCompact(): string;

    abstract protected function renderLong(): string;
}
