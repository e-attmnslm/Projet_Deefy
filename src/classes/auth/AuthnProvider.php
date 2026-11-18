<?php

declare(strict_types=1);

namespace iutnc\deefy\auth;

use iutnc\deefy\exception\AuthnException;
use iutnc\deefy\repository\DeefyRepository;

/**
 * Authentification : inscription, connexion, déconnexion, profil en session.
 */
class AuthnProvider
{
    public const MIN_PASSWORD_LENGTH = 10;

    public static function signin(string $email, string $passwd2check): User
    {
        $row = DeefyRepository::getInstance()->findUserByEmail($email);
        // même message dans tous les cas : on ne révèle pas si l'email existe
        if ($row === null || !password_verify($passwd2check, $row['passwd'])) {
            throw new AuthnException("Identifiants invalides");
        }

        $user = new User($row['id'], $row['email'], $row['role']);
        $_SESSION['user'] = serialize($user);
        unset($_SESSION['playlist']);
        return $user;
    }

    public static function register(string $email, string $pass): User
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new AuthnException("Adresse email invalide");
        }
        if (!self::checkPasswordStrength($pass, self::MIN_PASSWORD_LENGTH)) {
            throw new AuthnException(
                "Mot de passe trop faible : au moins " . self::MIN_PASSWORD_LENGTH
                . " caractères, dont une minuscule, une majuscule, un chiffre et un caractère spécial"
            );
        }
        $repo = DeefyRepository::getInstance();
        if ($repo->findUserByEmail($email) !== null) {
            throw new AuthnException("Un compte existe déjà avec cette adresse email");
        }

        $hash = password_hash($pass, PASSWORD_DEFAULT, ['cost' => 12]);
        $id = $repo->createUser($email, $hash, User::STANDARD_USER);
        return new User($id, $email, User::STANDARD_USER);
    }

    public static function checkPasswordStrength(string $pass, int $minimumLength): bool
    {
        $length = strlen($pass) >= $minimumLength;  // longueur minimale
        $digit = preg_match('#\d#', $pass);           // au moins un chiffre
        $special = preg_match('#\W#', $pass);         // au moins un caractère spécial
        $lower = preg_match('#[a-z]#', $pass);        // au moins une minuscule
        $upper = preg_match('#[A-Z]#', $pass);        // au moins une majuscule
        return $length && $digit && $special && $lower && $upper;
    }

    public static function getSignedInUser(): User
    {
        if (!isset($_SESSION['user'])) {
            throw new AuthnException("Vous devez être connecté pour accéder à cette fonctionnalité");
        }
        return unserialize($_SESSION['user']);
    }

    public static function isSignedIn(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function signout(): void
    {
        // cours 10 : la session existe jusqu'à l'appel de session_destroy()
        unset($_SESSION['user']);
        unset($_SESSION['playlist']);
        session_destroy();
    }
}
