<?php

declare(strict_types=1);

namespace iutnc\deefy\dispatch;

use iutnc\deefy\auth\AuthnProvider;
use iutnc\deefy\exception\AuthnException;
use iutnc\deefy\exception\AuthzException;
use iutnc\deefy\exception\RepositoryException;

/**
 * Aiguillage : choisit la classe Action selon le paramètre "action" de l'URL,
 * l'exécute et produit la page HTML complète.
 */
class Dispatcher
{
    protected string $action;
    protected array $actions = [];

    public function __construct()
    {
        $this->action = $_GET['action'] ?? 'default';
    }

    public function addRoute(string $action, string $action_class): self
    {
        $this->actions[$action] = $action_class;
        return $this;
    }

    public function run(): void
    {
        try {
            if (!isset($this->actions[$this->action])) {
                http_response_code(404);
                $html = '<h1>Page introuvable</h1><p>Cette fonctionnalité n\'existe pas.</p>';
            } else {
                $class = $this->actions[$this->action];
                /** @var \iutnc\deefy\action\Action $act */
                $act = new $class();
                $html = $act->execute();
            }
        } catch (AuthnException $e) {
            http_response_code(401);
            $html = '<h1>Authentification requise</h1>'
                . '<p class="error" role="alert">' . $e->getMessage() . '</p>'
                . '<p><a href="?action=signin" role="button">Se connecter</a> '
                . '<a href="?action=add-user" role="button" class="secondary">S\'inscrire</a></p>';
        } catch (AuthzException $e) {
            http_response_code(403);
            $html = '<h1>Accès non autorisé</h1>'
                . '<p class="error" role="alert">' . $e->getMessage() . '</p>';
        } catch (RepositoryException $e) {
            http_response_code(404);
            $html = '<h1>Erreur</h1><p class="error" role="alert">' . $e->getMessage() . '</p>';
        }
        $this->renderPage($html);
    }

    /** Lien du menu, marqué comme page courante si c'est l'action demandée. */
    private function item(string $action, string $label): string
    {
        $courant = $this->action === $action ? ' aria-current="page"' : '';
        return '<li><a href="?action=' . $action . '"' . $courant . '>' . $label . '</a></li>';
    }

    private function menu(): string
    {

        $html = '<ul><li><a href="?action=default" class="brand"><strong>Deefy</strong></a></li></ul><ul>';
        if (AuthnProvider::isSignedIn()) {
            $user = AuthnProvider::getSignedInUser();
            $html = $html . $this->item('mes-playlists', 'Mes playlists')
                . $this->item('playlist', 'Playlist courante')
                . $this->item('add-playlist', 'Créer une playlist')
                . '<li><small>' . $user->email . '</small></li>'
                . $this->item('signout', 'Déconnexion');
        } else {
            $html = $html . $this->item('signin', 'Se connecter') . $this->item('add-user', 'S\'inscrire');
        }
        return $html . '</ul>';
    }

    private function renderPage(string $html): void
    {
        $menu = $this->menu();
        echo <<<HTML
            <!DOCTYPE html>
            <html lang="fr">
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <title>Deefy</title>
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
                <link rel="stylesheet" href="css/deefy.css">
            </head>
            <body>
                <header class="container">
                    <nav>{$menu}</nav>
                </header>
                <main class="container">
                    {$html}
                </main>
            </body>
            </html>
            HTML;
    }
}
