<?php defined('SCAFFOLD') or die;

trait Events {

    protected $events = [];

    public function on($event, $callback = null) {
        if (is_null($callback)) {
            $callback = $event;
            $event    = '';
        }

        if (!array_key_exists($event, $this->events)) {
            $this->events[$event] = [];
        }

        $this->events[$event][] = $callback;

        return $this;
    }

    public function off($event = null, $callback = null) {
        if (is_callable($event)) {
            $callback = $event;
            $event    = '';
        } elseif (is_null($event)) {
            $event = '';
        }

        $events = $this->getEvents($event);

        if (is_null($callback)) {
            foreach ($events as $event) {
                unset($this->events[$event]);
            }
        } else {
            foreach ($events as $event) {
                $key = array_search($callback, $this->events[$event]);

                unset($this->events[$event][$key]);
            }
        }

        return $this;
    }

    public function getEvents($search = null) {
        $events = array_keys($this->events);

        if (is_null($search)) return $events;

        // find sub events
        $events = array_filter($events, function ($event) use ($search) {
            return strpos($event, $search . '.') === 0;
        });

        // add main event
        if (array_key_exists($search, $this->events)) {
            array_unshift($events, $search);
        }

        return $events;
    }

    public function getCallbacks($search = null) {
        $callbacks = [];
        $events    = $this->getEvents($search);

        foreach ($events as $event) {
            $callbacks = array_merge($callbacks, $this->events[$event]);
        }

        return $callbacks;
    }

    public function trigger($event = null) {
        $arguments = func_get_args();

        // remove event name from arguments
        $arguments = array_slice($arguments, 1);

        $callbacks = $this->getCallbacks($event);

        foreach ($callbacks as $callback) {
            call_user_func_array($callback, $arguments);
        }

        return $this;
    }

}
