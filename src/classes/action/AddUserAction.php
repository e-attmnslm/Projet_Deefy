<?php

declare(strict_types=1);

namespace iutnc\deefy\action;

use iutnc\deefy\auth\AuthnProvider;
use iutnc\deefy\exception\AuthnException;

/**
 * Fonctionnalité 6 (TD14, action add-user) : création d'un compte utilisateur (rôle STANDARD).
 */
class AddUserAction extends Action
{
    public function execute(): string
    {
        if (!$this->isPost()) {
            return $this->form();
        }

        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $pass = $_POST['passwd'] ?? '';
        $pass2 = $_POST['passwd2'] ?? '';

        if ($pass !== $pass2) {
            return $this->form('Les deux mots de passe ne correspondent pas', $email);
        }
        try {
            AuthnProvider::register($email, $pass);
            AuthnProvider::signin($email, $pass);
        } catch (AuthnException $e) {
            return $this->form($e->getMessage(), $email);
        }
        return '<h1>Compte créé</h1>'
            . '<p>Votre compte <strong>' . $email . '</strong> a été créé, vous êtes maintenant connecté.</p>'
            . '<p><a href="?action=add-playlist" role="button">Créer ma première playlist</a></p>';
    }

    private function form(string $error = '', string $email = ''): string
    {
        $err = $error === '' ? '' : '<p class="error" role="alert">' . $error . '</p>';
        $min = AuthnProvider::MIN_PASSWORD_LENGTH;
        return '<h1>Inscription</h1>' . $err
            . '<form method="post" action="?action=add-user">'
            . '<label for="email">Email</label>'
            . '<input type="email" id="email" name="email" required maxlength="256" autocomplete="email" value="' . $email . '">'
            . '<label for="passwd">Mot de passe</label>'
            . '<input type="password" id="passwd" name="passwd" required minlength="' . $min . '" autocomplete="new-password" aria-describedby="pwd-help">'
            . '<small id="pwd-help">Au moins ' . $min . ' caractères, avec une minuscule, une majuscule, un chiffre et un caractère spécial.</small>'
            . '<label for="passwd2">Confirmer le mot de passe</label>'
            . '<input type="password" id="passwd2" name="passwd2" required minlength="' . $min . '" autocomplete="new-password">'
            . '<button type="submit">Créer mon compte</button>'
            . '</form>'
            . '<p>Déjà inscrit ? <a href="?action=signin">Se connecter</a></p>';
    }
}
