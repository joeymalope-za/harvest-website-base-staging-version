<?php

namespace MABEL_WOF\Core\Common
{

	/**
	 * Register all actions and filters for the plugin.
	 *
	 * Maintain a list of all hooks that are registered throughout
	 * the plugin, and register them with the WordPress API. Call the
	 * run function to execute the list of actions and filters.
	 *
	 * @package MABEL_WOF\Includes\Core
	 */
	class Loader
	{
		protected $fired_actions;
		protected $fired_filters;

		protected $actions;
		protected $filters;

		public function __construct()
		{
			$this->reset();
			$this->fired_action = [];
			$this->fired_filters = [];
		}

		/**
		 * Add a new action to the collection to be registered with WordPress.
		 * @param $hook
		 * @param $component
		 * @param $callback
		 * @param int $priority
		 * @param int $accepted_args
		 */
		public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 )
		{
			$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
		}


		/**
		 * Add a new filter to the collection to be registered with WordPress.
		 * @param $hook
		 * @param $component
		 * @param $callback
		 * @param int $priority
		 * @param int $accepted_args
		 */
		public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 )
		{
			$this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
		}

		/**
		 * A utility function that is used to register the actions and hooks into a single collection.
		 *
		 * @param $hooks
		 * @param $hook
		 * @param $component
		 * @param $callback
		 * @param $priority
		 * @param $accepted_args
		 *
		 * @return array
		 */
		private function add( $hooks, $hook, $component, $callback, $priority, $accepted_args )
		{
			$hooks[] = [
				'hook'          => $hook,
				'component'     => $component,
				'callback'      => $callback,
				'priority'      => $priority,
				'accepted_args' => $accepted_args
			];
			return $hooks;
		}

		/**
		 * Register the filters and actions with WordPress.
		 */
		public function run()
		{

			foreach ( $this->filters as $hook ) {
				if(! method_exists($hook['component'],$hook['callback'])){
					throw new \Exception("Can't add filter. Method ". $hook['callback'] . " doesn't exist.");
				}
				add_filter( $hook['hook'], ($hook['component'] === null? $hook['callback'] : [ $hook['component'], $hook['callback'] ] ), $hook['priority'], $hook['accepted_args'] );
				array_push($this->fired_filters, $hook['hook']);
			}

			foreach ( $this->actions as $hook ) {
				if(! method_exists($hook['component'],$hook['callback'])){
					throw new \Exception("Can't add action. Method ". $hook['callback'] . "doesn't exist.");
				}
				add_action( $hook['hook'], ($hook['component'] === null? $hook['callback'] : [ $hook['component'], $hook['callback'] ] ), $hook['priority'], $hook['accepted_args'] );
				array_push($this->fired_action, $hook['hook']);
			}

			$this->reset();

		}

		private function reset()
		{

			$this->filters = [];
			$this->actions = [];

		}

	}

}