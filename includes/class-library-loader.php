<?php

/**
 * Loader for plugin actions and filters.
 *
 * @link       https://github.com/19h47/library
 * @since      1.0.0
 *
 * @package    Library
 * @subpackage Library/includes
 */

/**
 * Registers and runs actions and filters with WordPress.
 *
 * @since      1.0.0
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Library_Loader {
	
    /**
     * Registered actions.
     *
     * @since  1.0.0
     * @var    array $actions
     */
    protected $actions;

    /**
     * Registered filters.
     *
     * @since  1.0.0
     * @var    array $filters
     */
    protected $filters;

    /**
     * Instance.
     *
     * @since  1.0.0
     * @var    Library_Loader|null $instance
     */
    private static $instance;

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->actions = array();
        $this->filters = array();
    }


    /**
     * Add an action.
     *
     * @since 1.0.0
     * @param string $hook          Action name.
     * @param object $component    Object that defines the callback.
     * @param string $callback     Callback method name.
     * @param int    $priority     Priority. Default 10.
     * @param int    $accepted_args Number of arguments. Default 1.
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1)
    {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }


    /**
     * Add a filter.
     *
     * @since 1.0.0
     * @param string $hook          Filter name.
     * @param object $component    Object that defines the callback.
     * @param string $callback     Callback method name.
     * @param int    $priority     Priority. Default 10.
     * @param int    $accepted_args Number of arguments. Default 1.
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1)
    {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }


    /**
     * Add a hook to the collection.
     *
     * @since  1.0.0
     * @access private
     * @param  array  $hooks         Existing hooks.
     * @param  string $hook          Hook name.
     * @param  object $component     Object that defines the callback.
     * @param  string $callback      Callback method name.
     * @param  int    $priority      Priority.
     * @param  int    $accepted_args Number of arguments.
     * @return array Modified hooks.
     */
    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args)
    {
        $hooks[ $this->hook_index($hook, $component, $callback) ] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args,
        );
        return $hooks;
    }


    /**
     * Get loader instance.
     *
     * @since  1.0.0
     * @return Library_Loader
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new Library_Loader();
        }
        return self::$instance;
    }

    /**
     * Generate unique hook index.
     *
     * @since  1.0.0
     * @param  string $hook      Hook name.
     * @param  object $component Component object.
     * @param  string $callback  Callback name.
     * @return string
     */
    protected function hook_index($hook, $component, $callback)
    {
        return md5($hook . get_class($component) . $callback);
    }


    /**
     * Remove a previously added hook.
     *
     * @since  1.0.0
     * @param  string $hook      Hook name.
     * @param  object $component Component object.
     * @param  string $callback  Callback name.
     */
    public function remove($hook, $component, $callback)
    {
        $index = $this->hook_index($hook, $component, $callback);

        if (isset($this->filters[ $index ])) {
            remove_filter($this->filters[ $index ]['hook'], array( $this->filters[ $index ]['component'], $this->filters[ $index ]['callback'] ));
        }

        if (isset($this->actions[ $index ])) {
            remove_action($this->actions[ $index ]['hook'], array( $this->actions[ $index ]['component'], $this->actions[ $index ]['callback'] ));
        }
    }


    /**
     * Register all hooks with WordPress.
     *
     * @since 1.0.0
     * @return void
     */
    public function run()
    {
        foreach ($this->filters as $hook) {
            add_filter($hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args']);
        }
        foreach ($this->actions as $hook) {
            add_action($hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args']);
        }
    }
}
