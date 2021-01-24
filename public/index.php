<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;

use function Differ\Formatters\Json\render;

$users = ['mike', 'mishel', 'adel', 'keks', 'kamila'];

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    $response->write('Welcome to Slim!');
    return $response;
});

$app->get('/users', function ($request, $response) use ($users) {
    $term = $request->getQueryParam('term');
    foreach ($users as $user) {
        if (strpos($user, $term) !== false) {
            $resultUsers[] = $user;
        }
    }
    $params = ['users' => $users, 'resultUsers' => $resultUsers, 'term' => $term];
    return $this->get('renderer')->render($response, "users/index.phtml", $params);
});

$app->get('/courses/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    return $response->write("Course id: {$id}");
});

$app->get('/users/new', function ($request, $response) {
    $params = [
        'user' => ['nickname' => '', 'email' => '', 'id' => ''],
        'errors' => []
    ];
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
});

$app->post('/users', function ($request, $response) {

    $user = $request->getParsedBodyParam('user');

    if (!file_exists('dataFile')) {
        $dataJson = json_encode(["1" => $user]);
        file_put_contents('dataFile', $dataJson);
    } else {
        $dataResult = json_decode(file_get_contents('dataFile'), true);
        $dataResult[count($dataResult) + 1] = $user;
        file_put_contents('dataFile', json_encode($dataResult));
    }

    return $response->withRedirect('/users', 302);
});

$app->get('/users/{id}', function ($request, $response, $args) {
    $params = ['id' => $args['id'], 'nickname' => 'user-' . $args['id']];
    // Указанный путь считается относительно базовой директории для шаблонов, заданной на этапе конфигурации
    // $this доступен внутри анонимной функции благодаря https://php.net/manual/ru/closure.bindto.php
    // $this в Slim это контейнер зависимостей
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
});

$app->run();
