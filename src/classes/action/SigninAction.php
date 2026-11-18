<?php

declare(strict_types=1);

namespace iutnc\deefy\action;

use iutnc\deefy\auth\AuthnProvider;
use iutnc\deefy\exception\AuthnException;

/**
 * Fonctionnalité 7 : authentification d'un utilisateur enregistré.
 */
class SigninAction extends Action
{
    public function execute(): string
    {
        if (!$this->isPost()) {
            return $this->form();
        }

        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $pass = $_POST['passwd'] ?? '';
        try {
            $user = AuthnProvider::signin($email, $pass);
        } catch (AuthnException $e) {
            return $this->form($e->getMessage(), $email);
        }
        return '<h1>Connexion réussie</h1>'
            . '<p>Bonjour <strong>' . $user->email . '</strong> !</p>'
            . '<p><a href="?action=mes-playlists" role="button">Voir mes playlists</a></p>';
    }

    private function form(string $error = '', string $email = ''): string
    {
        $err = $error === '' ? '' : '<p class="error" role="alert">' . $error . '</p>';
        return '<h1>Connexion</h1>' . $err
            . '<form method="post" action="?action=signin">'
            . '<label for="email">Email</label>'
            . '<input type="email" id="email" name="email" required maxlength="256" autocomplete="username" value="' . $email . '">'
            . '<label for="passwd">Mot de passe</label>'
            . '<input type="password" id="passwd" name="passwd" required autocomplete="current-password">'
            . '<button type="submit">Se connecter</button>'
            . '</form>'
            . '<p>Pas encore de compte ? <a href="?action=add-user">S\'inscrire</a></p>';
    }
}
