<?php

use app\AnalyzerDb;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteContext;
use Slim\Views\PhpRenderer;
use Valitron\Validator;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$app->get('/', function (Request $request, Response $response, array $args) {
    $renderer = new PhpRenderer(__DIR__ . '/../templates');
    return $renderer->render($response, 'index.phtml');
})->setName('main page');

$app->post('/urls', function (Request $request, Response $response, array $args) {
    $content = urldecode($request->getBody()->getContents());
    $url = \Illuminate\Support\Str::after($content, 'url[name]=');
    $v = new Validator(['url' => $url]);
    $v->rule('required', 'url');
    $v->rule('url', 'url');
    if ($v->validate()) {
        $db = AnalyzerDb::connect();
        $id = $db->insertUrl($url);

        $renderer = new PhpRenderer(__DIR__ . '/../templates');
        $renderer->addAttribute('url', $db->selectOneUrl($id));
        $renderer->addAttribute('success', true);
        return $renderer->render($response, 'url_item.phtml');
    } else {
        $renderer = new PhpRenderer(__DIR__ . '/../templates');
        $renderer->addAttribute('hasError', true);
        $renderer->addAttribute('url[name]', $url);
        return $renderer->render($response->withStatus(422), 'index.phtml');
    }
})->setName('create url');

$app->get('/urls/{id}', function (Request $request, Response $response, array $args) {
    $db = AnalyzerDb::connect();
    $url = $db->selectOneUrl($args['id']);
    $checks = $db->selectChecks($url['id']);
    $renderer = new PhpRenderer(__DIR__ . '/../templates');
    $renderer->addAttribute('url', $url);
    $renderer->addAttribute('checks', $checks);
    return $renderer->render($response, 'url_item.phtml');
})->setName('view url');

$app->get('/urls', function (Request $request, Response $response, array $args) {
    $db = AnalyzerDb::connect();
    $urls = $db->selectUrls();
    $newUrls = $db->selectLastCheck($urls);
    $renderer = new PhpRenderer(__DIR__ . '/../templates');
    $renderer->addAttribute('urls', $newUrls);
    return $renderer->render($response, 'urls.phtml');
})->setName('list url');

$app->post('/urls/{id}/checks', function (Request $request, Response $response, array $args) {
    $db = AnalyzerDb::connect();
    $url = $db->selectOneUrl($args['id']);
    // check url
    $db->insertCheck($url['id']);
    $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor('view url', ['id' => $url['id']]);

    return $response->withStatus(302)->withHeader('Location', $url);
})->setName('check url');

$app->run();
