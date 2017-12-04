<?php
namespace project5;
/**
 *
 *
 */
trait Observable
{
    private $_listeners = [];


    public function addListener($event, callable $callback)
    {
        if (!isset($this->_listeners[$event])) $this->_listeners[$event] = [$callback];
        else $this->_listeners[$event][] = $callback;

        return $this;
    }





    /*public function __sleep()
    {
        foreach($this->callbacks as $k => $info)
        {
            list($callback, $event, $observer) = $info;
            if ($observer) $this->detach($observer, $event);
            $this->callbacks[$k][2] = null;
        }

        $sleep_vars  = array_keys((array)$this);
        return $sleep_vars;
    }

    public function __wakeup()
    {
        $callbacks = (array)$this->callbacks;
        $this->callbacks = array();
        foreach($callbacks as $k => $info)
        {
            list($callback, $event, ) = $info;
            $this->attachCallback($callback, $event);
        }
    }*/

    /**
	 * Notify attached observers
	 *
	 * @return Observable
     */
    public function notify($event)
    {
        if (isset($this->_listeners[$event])) {
            array_walk($this->_listeners[$event], function(callable $callback) use($event) {
                call_user_func($callback, $this);
            });
        }


        /*if ($this->observersCount === 0) return $this;

        $args = func_get_args ();
        array_unshift ( $args, $this );

        if (is_string ( $event ) && isset ( $this->eventObservers [$event] ))
        {
            foreach ( $this->eventObservers [$event] as $observer )
            {
                call_user_func_array ( array ($observer, 'update' ), $args );
            }
        }

        foreach ( $this->globalObservers as $observer )
            call_user_func_array ( array ($observer, 'update' ), $args );

        */

        return $this;
    }


}
