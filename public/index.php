<?php

require __DIR__ . '/../vendor/autoload.php';

use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;
use App\Validator;

// Старт PHP сессии
session_start();

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

AppFactory::setContainer($container);
$app = AppFactory::create();

$app->addErrorMiddleware(true, true, true);

$app->add(MethodOverrideMiddleware::class);

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) use ($router) {
    //Аутентификация
    print_r("Превед медвед работает!)))");
    $flash = $this->get('flash')->getMessages();

    $loginUser = $_SESSION['user'] ?? null;

    $params = [
        'loginUser' => $loginUser,
        'flash' => $flash
    ];
    return $this->get('renderer')->render($response, "/users/login.phtml", $params);
})->setName('/');

$app->post('/session', function ($request, $response) {

    $users = json_decode($request->getCookieParam('user', json_encode([])), true);

    $user = $request->getParsedBodyParam('user');

    foreach ($users as $value) {
        if ($user['name'] === $value['nickname'] && $user['email'] === $value['email']) {
            $_SESSION['user'] = $user;
            return $response->withRedirect('/');
        }
    }
    $this->get('flash')->addMessage('success', 'Wrong password or name');
    return $response->withRedirect('/');
});

$app->delete('/session', function ($request, $response) {
    $_SESSION = [];
    session_destroy();
    return $response->withRedirect('/');
});

$app->get('/users', function ($request, $response, $args) use ($router) {

    $messages = $this->get('flash')->getMessages();
    //$this->get('flash')->addMessage('success', 'User has been created!'); отправка flash

    $users = json_decode($request->getCookieParam('user', json_encode([])), true); // file_get_contents('dataFile'), true);
    //$users = json_decode(file_get_contents('dataFile'), true);

    print_r($users);

    $term = $request->getQueryParam('term');

    foreach ($users as $user) {
        if (strpos(strtolower($user['nickname']), strtolower($term)) !== false) {
            $resultUsers[] = $user['nickname'];
        }
    }

    $params = ['users' => $users, 'resultUsers' => $resultUsers, 'term' => $term, 'flash' => $messages];
    return $this->get('renderer')->render($response, "users/index.phtml", $params);
})->setName('users.index');

$app->get('/courses/{id}', function ($request, $response, array $args) use ($router) {
    $id = $args['id'];
    return $response->write("Course id: {$id}");
})->setName('course.show');

$app->get('/users/new', function ($request, $response) {
    $params = [
        'user' => ['nickname' => '', 'email' => '', 'id' => ''],
        'errors' => []
    ];
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
})->setName('users.new');

$app->post('/users', function ($request, $response) use ($router) {

    $validator = new Validator();
    $user = $request->getParsedBodyParam('user');
    print_r($user); // проверка параметров запроса
    $errors = $validator->validate($user);

    if (count($errors) === 0) {

        $dataResult = json_decode($request->getCookieParam('user', json_encode([])), true); // file_get_contents('dataFile'), true);
        $dataResult[] = $user;
        $encodedUser = json_encode($dataResult);

        $this->get('flash')->addMessage('success', 'User has been created!');

        return $response->withHeader('Set-Cookie', "user={$encodedUser}")->withRedirect($router->urlFor('users.index'), 302);

        //[{"nickname":"1","email":"1@1","id":"1"},{"nickname":"2","email":"2@2","id":"2"}]
        //$app->setCookie('foo', 'bar', '2 days');
        //---------------------------------------------------------------------------------
        //$item = $request->getParsedBodyParam('item');
        // Данные корзины
        //$cart = json_decode($request->getCookieParam('cart', json_encode([])));
        // Добавление нового товара
        //$cart[] = $item;
        // Кодирование корзины
        //$encodedCart = json_encode($cart);
        // Установка новой корзины в куку
        //return $response->withHeader('Set-Cookie', "cart={$encodedCart}")->withRedirect('/');
        //-----------------------------------------------------------------------------------
        //if (!file_exists('dataFile')) {
        //    $dataJson = json_encode(["1" => $user]);
        //    file_put_contents('dataFile', $dataJson);
        //} else {
        //    $dataResult = json_decode(file_get_contents('dataFile'), true);
        //    $dataResult[] = $user;
        //    file_put_contents('dataFile', json_encode($dataResult));
        //}
        //-----------------------------------------------------------------------------------
        // Добавление флеш-сообщения. Оно станет доступным на следующий HTTP-запрос.
        // 'success' — тип флеш-сообщения. Используется при выводе для форматирования.
        //$this->get('flash')->addMessage('success', 'User has been created!');

        //return $response->withRedirect($router->urlFor('users.index'), 302);
    }

    $params = [
        'user' => $user,
        'errors' => $errors
    ];

    // Если возникли ошибки, то устанавливаем код ответа в 422 и рендерим форму с указанием ошибок
    $response = $response->withStatus(422);
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
})->setName('user.store');

$app->get('/users/{id}', function ($request, $response, $args) use ($router) {

    $dataResult = json_decode($request->getCookieParam('user', json_encode([])), true); // file_get_contents('dataFile'), true);
    //$dataResult = json_decode(file_get_contents('dataFile'), true);

    foreach ($dataResult as $value) {
        if ($value['nickname'] === $args['id']) {
            print_r("Такой пользователь есть!)))");
            $params = ['id' => $args['id'], 'nickname' => 'user-' . $args['id']];
            return $this->get('renderer')->render($response, 'users/show.phtml', $params);
        }
    }
    return $response->withStatus(404);
})->setName('user.show');

$app->get('/users/{id}/edit', function ($request, $response, array $args) {

    $messages = $this->get('flash')->getMessages();

    $id = $args['id'];
    //$user = $repo->find($id);

    $users = json_decode($request->getCookieParam('user', json_encode([])), true); // file_get_contents('dataFile'), true);
    //$users = json_decode(file_get_contents('dataFile'), true);

    //    $dataResult = json_decode($request->getCookieParam('user', json_encode([]))); // file_get_contents('dataFile'), true);
    //    $dataResult[] = $user;
    //    $encodedUser = json_encode($dataResult);
    //    $this->get('flash')->addMessage('success', 'User has been created!');
    //    return $response->withHeader('Set-Cookie', "user={$encodedUser}")->withRedirect($router->urlFor('users.index'), 302);

    foreach ($users as $value) {
        if ($id === $value['id']) {
            //var_dump($value);
            $user = $value;
        }
        print_r($value);
    }

    $params = [
        'user' => $user,
        'errors' => [],
        'flash' => $messages
    ];
    return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
})->setName('user.edit');

$app->patch('/users/{id}', function ($request, $response, array $args) use ($router) {

    print_r($id = $args['id']);

    //    $dataResult = json_decode($request->getCookieParam('user', json_encode([]))); // file_get_contents('dataFile'), true);
    //    $dataResult[] = $user;
    //    $encodedUser = json_encode($dataResult);
    //    $this->get('flash')->addMessage('success', 'User has been created!');
    //    return $response->withHeader('Set-Cookie', "user={$encodedUser}")->withRedirect($router->urlFor('users.index'), 302);

    $users = json_decode($request->getCookieParam('user', json_encode([])), true); // file_get_contents('dataFile'), true);
    //$users = json_decode(file_get_contents('dataFile'), true);

    foreach ($users as $value) {
        if ($id === $value['id']) {
            var_dump($value);
            $user = $value;
        }
        print_r($value);
    }

    $data = $request->getParsedBodyParam('user');

    $validator = new Validator();
    $errors = $validator->validate($data);

    if (count($errors) === 0) {

        $users = json_decode($request->getCookieParam('user', json_encode([])), true); // file_get_contents('dataFile'), true);

        foreach ($users as $key => $value) {
            if ($id === $value['id']) {
                $dataResult[$key]['nickname'] = $data['nickname'];
                $dataResult[$key]['email'] = $data['email'];
                $dataResult[$key]['id'] = $data['id'];
            } else {
                $dataResult[$key] = $value;
            }
        }

        $this->get('flash')->addMessage('success', 'User has been updated!');

        //Обновление куки
        $encodedUser = json_encode($dataResult);

        $url = $router->urlFor('user.edit', ['id' => $user['id']]);
        return $response->withHeader('Set-Cookie', "user={$encodedUser};Path=/")->withRedirect($url);
    }

    $params = [
        'user' => $user,
        'errors' => $errors
    ];

    $response = $response->withStatus(422);
    return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
});

$app->get('/users/{id}/delete', function ($request, $response, array $args) use ($router) {

    $deleteId = $args['id'];

    $users = json_decode($request->getCookieParam('user', json_encode([])), true); // file_get_contents('dataFile'), true);
    //$users = json_decode(file_get_contents('dataFile'), true);

    print_r($users);

    print_r($users[$deleteId]);

    $params = [
        'deleteId' => $deleteId,
        //'flash' => $messages
    ];
    return $this->get('renderer')->render($response, 'users/delete.phtml', $params);
})->setName('user.delete');


$app->delete('/users/{id}', function ($request, $response, array $args) use ($router) {

    $deleteId = $args['id'];

    $users = json_decode($request->getCookieParam('user', json_encode([])), true); // file_get_contents('dataFile'), true);
    //$users = json_decode(file_get_contents('dataFile'), true);

    foreach ($users as $key => $user) {
        if ($deleteId === $user['id']) {
            $fixKey = $key;
            break;
        }
    }

    unset($users[$fixKey]);

    //file_put_contents('dataFile', json_encode($users));

    $this->get('flash')->addMessage('success', 'User has been deleted');

    $encodedUser = json_encode($users);
    $url = $router->urlFor('user.edit', ['id' => $user['id']]);
    return $response->withHeader('Set-Cookie', "user={$encodedUser};Path=/")->withRedirect($router->urlFor('users.index'));

    //return $response->withRedirect($router->urlFor('users.index'));
});

$app->run();
