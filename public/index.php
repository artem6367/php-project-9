<?php

use app\AnalyzerDb;
use DI\ContainerBuilder;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Factory\AppFactory;
use Slim\Flash\Messages;
use Slim\Routing\RouteContext;
use Slim\Views\PhpRenderer;
use Valitron\Validator;

require __DIR__ . '/../vendor/autoload.php';

$containerBuilder = new ContainerBuilder();

$containerBuilder->addDefinitions(
    [
        'flash' => function () {
            $storage = [];
            return new Messages($storage);
        },
        'db' => AnalyzerDb::connect()
    ]
);

AppFactory::setContainer($containerBuilder->build());

$app = AppFactory::create();

$app->add(
    function ($request, $next) {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $this->get('flash')->__construct($_SESSION);

        return $next->handle($request);
    }
);

$app->addErrorMiddleware(true, true, true);

$app->get('/', function (Request $request, Response $response, array $args) {
    $error = $this->get('flash')->getFirstMessage('error');
    $url = $this->get('flash')->getFirstMessage('url');
    $renderer = new PhpRenderer(__DIR__ . '/../templates');
    $renderer->addAttribute('error', $error);
    $renderer->addAttribute('url', $url);
    if ($error) {
        return $renderer->render($response->withStatus(422), 'index.phtml');
    }

    return $renderer->render($response, 'index.phtml');
})->setName('main page');

$app->post('/urls', function (Request $request, Response $response, array $args) {
    $content = urldecode($request->getBody()->getContents());
    $url = \Illuminate\Support\Str::after($content, 'url[name]=');

    $v = new Validator(['url' => $url]);
    $v->rule('required', 'url');
    $v->rule('url', 'url');

    if ($v->validate()) {
        $db = $this->get('db');
        try {
            $id = $db->insertUrl($url);
            $this->get('flash')->addMessage('success', 'Страница успешно добавлена');

            $redirectUrl = RouteContext::fromRequest($request)->getRouteParser()->urlFor('view url', ['id' => $id]);
            return $response->withStatus(302)->withHeader('Location', $redirectUrl);
        } catch (\Exception $e) {
            $this->get('flash')->addMessage('error', 'Страница уже существует');
        }
    } else {
        $this->get('flash')->addMessage('error', 'Некорректный URL');
    }
    $this->get('flash')->addMessage('url', $url);

    $redirectUrl = RouteContext::fromRequest($request)->getRouteParser()->urlFor('main page');

    return $response->withStatus(302)->withHeader('Location', $redirectUrl);
})->setName('create url');

$app->get('/urls/{id}', function (Request $request, Response $response, array $args) {
    $success = $this->get('flash')->getFirstMessage('success');
    $error = $this->get('flash')->getFirstMessage('error');
    $db = $this->get('db');
    $url = $db->selectOneUrl($args['id']);
    $checks = $db->selectChecks($url->id);
    $renderer = new PhpRenderer(__DIR__ . '/../templates');
    $renderer->addAttribute('url', $url);
    $renderer->addAttribute('checks', $checks);
    $renderer->addAttribute('success', $success);
    $renderer->addAttribute('error', $error);
    return $renderer->render($response, 'url_item.phtml');
})->setName('view url');

$app->get('/urls', function (Request $request, Response $response, array $args) {
    $db = $this->get('db');
    $urls = $db->selectUrls();
    $renderer = new PhpRenderer(__DIR__ . '/../templates');
    $renderer->addAttribute('urls', $urls);
    return $renderer->render($response, 'urls.phtml');
})->setName('list url');

$app->post('/urls/{id}/checks', function (Request $request, Response $response, array $args) {
    $db = $this->get('db');
    $url = $db->selectOneUrl($args['id']);

    $check = $url->check();

    if ($check) {
        $this->get('flash')->addMessage('success', 'Страница успешно проверена');
        $db->insertCheck($check);
    } else {
        $this->get('flash')->addMessage('error', 'Произошла ошибка при проверке, не удалось подключиться');
    }

    $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor('view url', ['id' => $url->id]);

    return $response->withStatus(302)->withHeader('Location', $url);
})->setName('check url');

$app->run();
