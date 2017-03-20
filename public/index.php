<?php
/**
 *
 */

include __DIR__ . '/../init.php';

$stack = [
    'auth' => function($request, $response, $next)
    {
        session_start();

        $request['session'] = &$_SESSION;

        isset($_SESSION['user']) &&
            $request['user'] = $_SESSION['user'];

        return $next($request, $response);
    },

    'logout' => function($request, $response, $next)
    {
        if ('POST' == $request['method'] && isset($request['data']['logout'])) {
            $request['session']['user'] = null;
            return App::redirect('/');
        }

        return $next($request, $response);
    },

    'login' => function($request, $response, $next)
    {

        if (!empty($request['user'])) {
            return $next($request, $response);
        }

        $errors = [];
        $form = [
            'username' => $request['data']['username'] ?? 'phpdev',
            'password' => $request['data']['password'] ?? 'home'
        ];

        if ('POST' == $request['method']) {
            if ('phpdev' == $form['username'] && 'home' == $form['password']) {
                $request['session']['user'] = $form;
                return App::redirect('/');
            }

            $errors[] = 'Invalid login';
        }

        $response['body'] = App::layout(
            App::render(App::config('templates.login'), ['errors' => $errors, 'form' => $form])
        );

        return $response;
    },

    'welcome' => function($request, $response, $next)
    {
        $user = $request['user'];

        $response['body'] = App::layout(
            App::render(App::config('templates.welcome'), ['user' => $user])
        );

        return $next($request, $response);
    }
];

(new App(include __DIR__ . '/../config/config.php'))(new Middleware($stack));
