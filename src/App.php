<?php
/**
 *
 */

class App
{
    /**
     * @var array
     */
    static $config = [];

    /**
     * @param array $config
     */
    function __construct(array $config = [])
    {
        static::$config = $config;
    }

    /**
     * @param null $name
     * @return array|mixed
     */
    static function config($name = null)
    {
        if (null === $name) {
            return static::$config;
        }

        $names = explode('.', $name);
        $value = static::$config[array_shift($names)];

        foreach($names as $name) {
             $value = $value[$name];
        }

        return $value;
    }

    /**
     * @param $content
     * @return string
     */
    static function layout($content)
    {
        return static::render(static::config('templates.layout'), ['content' => $content]);
    }

    /**
     * @param null $url
     * @param int $status
     * @return array
     */
    static function redirect($url, $status = 302)
    {
        return static::response(null, $status, ['Location' => $url]);
    }

    /**
     * @param $__template
     * @param array $vars
     * @return string
     */
    static function render($__template, array $vars = [])
    {
        extract($vars);
        ob_start();
        include $__template;
        return ob_get_clean();
    }

    /**
     * @param array $args
     * @param array $data
     * @param array $server
     * @param array $config
     * @return array
     */
    static function request(array $args = [], array $data = [], array $server = [], array $config = [])
    {
        return [
            'args' => $args,
            'data' => $data ?: [],
            'method' => $server['REQUEST_METHOD'],
            'session' => [],
            'user' => [],
            'version' => substr($server['SERVER_PROTOCOL'], strlen('HTTP/'))
        ] + $config;
    }

    /**
     * @param null $body
     * @param int $status
     * @param array $headers
     * @return array
     */
    static function response($body = null, $status = 200, $headers = [])
    {
        return [
            'headers' => $headers,
            'body'    => $body,
            'reason'  => 200 == $status ? 'OK' : null,
            'status'  => $status
        ];
    }

    /**
     * @param Middleware $middleware
     */
    function __invoke(Middleware $middleware)
    {
        $stack = [

            'middleware' => function($request, $response, $next) {

                return $next($request, $request['middleware']($request, $response));
            },

            'prepare/response' => function($request, $response, $next) {

                empty($response['version']) && $response['version'] = $request['version'];

                (empty($response['status']) || 200 == $response['status']) && $response['reason'] = 'OK';

                return $next($request, $response);
            },

            'send/response' => function($request, $response) {
                if (!empty($response['headers'])) {
                    foreach($response['headers'] as $name => $value) {
                        header($name . ': ' . (is_array($value) ? implode(', ', $value) : $value));
                    }

                    $statusLine = sprintf('HTTP/%s %s %s', $response['version'], $response['status'], $response['reason']);

                    header($statusLine, true, $response['status']);
                }

                !empty($response['body']) && print($response['body']);
            }
        ];

        (new Middleware($stack))(
            static::request($_GET, $_POST, $_SERVER, ['middleware' => $middleware]), static::response()
        );
    }
}
