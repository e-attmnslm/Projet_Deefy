<?php

declare(strict_types=1);

namespace iutnc\deefy\auth;

use iutnc\deefy\exception\AuthzException;
use iutnc\deefy\repository\DeefyRepository;

/**
 * Contrôle des autorisations de l'utilisateur authentifié.
 */
class Authz
{
    public function __construct(private User $authenticated_user)
    {
    }

    /** Le rôle de l'utilisateur doit être au moins égal au rôle requis. */
    public function checkRole(int $required): void
    {
        if ($this->authenticated_user->role < $required) {
            throw new AuthzException("droits insuffisants");
        }
    }

    /** L'utilisateur doit être propriétaire de la playlist (ou ADMIN). */
    public function checkPlaylistOwner(int $playlistId): void
    {
        if ($this->authenticated_user->isAdmin()) {
            return;
        }
        if (!DeefyRepository::getInstance()->isPlaylistOwner($this->authenticated_user->id, $playlistId)) {
            throw new AuthzException("vous n'êtes pas propriétaire de cette playlist");
        }
    }
}
