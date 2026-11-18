<?php

declare(strict_types=1);

namespace iutnc\deefy\auth;

/**
 * Profil de l'utilisateur authentifié, conservé en session.
 * Le hash du mot de passe n'y est jamais stocké.
 */
class User
{
    public const STANDARD_USER = 1;
    public const ADMIN_USER = 100;

    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly int $role
    ) {
    }

    public function isAdmin(): bool
    {
        return $this->role >= self::ADMIN_USER;
    }
}
