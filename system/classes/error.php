<?php defined('SCAFFOLD') or die;

class Error {

    const ALIAS_DEFAULT = 'global';

    /**
     * Has the error class been registered with SPL?
     */
    public static $loaded = false;

    /**
     * All of our handlers, sorted by alias.
     */
    public static $handlers = [];

    /**
     * Our config
     */
    public static $config;

    /**
     * Global instance
     */
    public static $instance;

    /**
     * Our alias
     */
    public $alias;

    /**
     * Register with SPL
     */
    public static function register() {
        // We should only register once...
        if (static::$loaded) return;

        // We need to turn off default display of errors. They should still be logged, though.
        // This is to catch global errors.
        ini_set('display_errors', 0);

        // Register
        set_error_handler(['Error', 'uncatchable']);
        set_exception_handler(function($exc) {
            Error::get()->handle($exc);
        });
        Shutdown::register();
        Shutdown::set([get_called_class(), 'uncatchable']);

        // Tell the class we have loaded
        static::$loaded = true;
    }

    /**
     * Get a global instance
     */
    public static function get() {
        if (!static::$instance) {
            $class = get_called_class();
            static::$instance = new $class;
        }

        return static::$instance;
    }

    public static function get_response() {
        // We need to get the response, but it may not have been assigned yet.
        try {
            $response = Service::get('response');
        } catch (ExceptionService $e) {
            $response = new Response();
        }

        return $response;
    }

    /**
     * Send the default error.
     *
     * @todo Logging
     * @todo Customisable error.
     */
    public static function uncatchable($err = false) {
        // If it's a supressed error
        if (0 == (error_reporting() & $err)) return;

        // If we haven't got config yet, load it
        if (!static::$config) {
            static::$config = Service::get('config')->get('errors');
        }

        if (static::$config['ignore'] & $err) {
            return;
        }

        return call_user_func_array([get_called_class(), 'send'], func_get_args());
    }

    /** Actually throw the error in response **/
    protected static function send() {
        static::get_response()->error(500, false, static::$config['debug'] ? func_get_args() : false)->send();
        die;
    }

    /**
     * Try to handle exceptions
     */
    public function handle($exc, $alias = false) {
        if (!$alias) $alias = $this->alias;

        if (!isset(static::$handlers[static::ALIAS_DEFAULT])) {
            static::$handlers[static::ALIAS_DEFAULT] = [];
        }

        if (!isset(static::$handlers[$alias])) {
            static::$handlers[$alias] = [];
        }

        // Get all assigned handlers
        $all = static::$handlers[static::ALIAS_DEFAULT];
        if ($alias !== static::ALIAS_DEFAULT) {
            $all = array_merge_recursive($all, static::$handlers[$alias]);
        }

        // Filter to the ones associated with this exception
        $handlers = [];
        foreach ($all as $class => $class_handlers) {
            if ($exc instanceof $class) {
                $handlers = array_merge($handlers, $class_handlers);
            }
        }

        $response = static::get_response();

        // Generate the object passed to handlers
        $obj = new Dynamic([
            'exc' => $exc,
            'rethrow' => function() {
                $this->stop();
                throw $this->exc;
            },
            'send' => function($obj, $value, $code = 200) use ($response) {
                $response = $response->data($value);
                $response->code = $code;
                $response->send();
                die;
            },
            'type' => get_class($exc),
            'running' => true,
            'stop' => function() {
                $this->running = false;
            },
            'catched' => false,
            'catch' => function() {
                $this->catched = true;    
            },
            'uncatch' => function() {
                $this->catched = false;
            }
        ]);

        for ($i = 0, $l = count($handlers); $i < $l && $obj->running; $i++) {
            $handler = $handlers[$i];
            $handler($obj);
        }


        // If we have no handlers, we should rethrow.
        if (empty($handlers) || !$obj->catched) {
            return $obj->rethrow();
        }
    }

    /**
     * Attach a handler
     */
    public function attach($type, $handler, $alias = false) {

        if (!$alias) {
            $alias = $this->alias;
        }

        if (!isset(static::$handlers[$alias])) {
            static::$handlers[$alias] = [];
        }

        if (!isset(static::$handlers[$alias][$type])) {
            static::$handlers[$alias][$type] = [];
        }

        static::$handlers[$alias][$type][] = $handler;

        return $this;
    }

    /**
     * Instaniate
     */
    public function __construct($alias = false) {
        static::register();
        $this->alias($alias);
    }

    /**
     * Set our alias
     */
    public function alias($alias = false) {
        if (!$alias) $alias = static::ALIAS_DEFAULT;

        $this->alias = $alias;

        return $this;
    }

}