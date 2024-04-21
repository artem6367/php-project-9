<?php

use app\AnalyzerDb;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Factory\AppFactory;
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
    $renderer = new PhpRenderer(__DIR__ . '/../templates');
    $renderer->addAttribute('url', $url);
    return $renderer->render($response, 'url_item.phtml');
})->setName('view url');

$app->get('/urls', function (Request $request, Response $response, array $args) {
    $db = AnalyzerDb::connect();
    $urls = $db->selectUrls();
    $renderer = new PhpRenderer(__DIR__ . '/../templates');
    $renderer->addAttribute('urls', $urls);
    return $renderer->render($response, 'urls.phtml');
})->setName('list url');

$app->run();
