<?php

declare(strict_types=1);

namespace iutnc\deefy\action;

use iutnc\deefy\auth\AuthnProvider;

class SignoutAction extends Action
{
    public function execute(): string
    {
        AuthnProvider::signout();
        return '<h1>Déconnexion</h1><p>Vous êtes déconnecté. À bientôt !</p>'
            . '<p><a href="?action=signin">Se reconnecter</a></p>';
    }
}
