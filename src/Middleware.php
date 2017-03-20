<?php
/**
 *
 */

class Middleware
{
    /**
     * @var
     */
    public $stack;

    /**
     * @param array $stack
     */
    function __construct(array $stack)
    {
        $this->stack = $stack;
    }

    /**
     * @return \Closure
     */
    function next()
    {
        return function($request, $response) {
            return ($next = next($this->stack)) ? $next($request, $response, $this->next()) : $response;
        };
    }

    /**
     * @param $request
     * @param $response
     * @return mixed
     */
    function __invoke($request, $response)
    {
        return !$this->stack ? $response : (reset($this->stack))($request, $response, $this->next());
    }
}
