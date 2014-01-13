<?php

/**
 * Fires code events and executes event listener callbacks
 * based on XenForo_CodeEvent
 *
 * @package CMF_Core
 * @author  Yoskaldyr <yoskaldyr@gmail.com>
 * @version 1000011 $Id$
 * @since   1000011
 */
class CMF_Core_Listener extends XenForo_CodeEvent
{
	/**
	 * Instance manager.
	 *
	 * @var CMF_Core_Listener
	 */
	private static $_instance;

	/**
	 * Listeners cache array - public mirror of original listeners
	 * @var array
	 */
	public $listeners = array();

	/**
	 * Global disabler/enabler of listeners core
	 *
	 * @var bool
	 */
	public static $enabled = false;

	/**
	 * Normal & proxy extenders array
	 * @var array
	 */
	protected static $_extend = array();

	/**
	 * Extender types array
	 * @var array
	 */
	protected static $_extendTypes = array(
		'all' => array(
			'event' => 'load_class',
			'method' => 'loadClass'

		),
		'proxy' => array(
			'event' => 'load_class_proxy_class',
			'method' => 'loadProxyClass'
		)
	);

	/**
	 * Dynamic extenders input array
	 * @var array
	 */
	protected static $_extendInput = array();

	/**
	 * XFCP_* classes loader counter
	 * @var array
	 */
	protected static $_counters = array();

	/**
	 * Gets the CMF Core Listener instance.
	 *
	 * @return CMF_Core_Listener
	 */
	public static final function getInstance()
	{
		if (!self::$_instance)
		{
			self::$_instance = new self();
		}

		return self::$_instance;
	}
	/**
	 * Constructor. Sets original listeners cache from parent class
	 */
	public function __construct()
	{
		if (!is_array(XenForo_CodeEvent::$_listeners))
		{
			XenForo_CodeEvent::$_listeners = array();
		}
		$this->listeners =& XenForo_CodeEvent::$_listeners;
	}

	/**
	 * Returns array of original listeners from parent (XenForo_CodeEvent)
	 * @return array
	 */
	public function getXenForoListeners()
	{
		return ($return = parent::$_listeners) ? $return : array();
	}

	/**
	 * Gets event listeners array
	 * Transforms array of extenders array to array of listener's callbacks
	 * and prepares autoloader for proxy handling only specified classes
	 *
	 * @return array
	 * */
	public function prepareDynamicListeners()
	{
		$listeners = array();
		//extenders
		foreach (self::$_extendInput as $type => $extend)
		{
			if ($extend && isset(self::$_extendTypes[$type]))
			{
				foreach ($extend as $className => $value)
				{
					if (!isset($listeners[self::$_extendTypes[$type]['event']][$className]) && !isset(self::$_extend[$type][$className]))
					{
						$listeners[self::$_extendTypes[$type]['event']][$className] = array(
							array('CMF_Core_Listener', self::$_extendTypes[$type]['method'])
						);
					}
				}
				self::$_extend[$type] = isset(self::$_extend[$type]) ? array_merge_recursive(self::$_extend[$type], $extend) : $extend;
			}
		}
		self::$_extendInput = array();

		//controller input actions
		$controllers = CMF_Core_Application::getFullKey(CMF_Core_Application::INPUT_ACTIONS);
		foreach ($controllers as $className => $value)
		{
			if (!isset($listeners['controller_pre_dispatch'][$className]))
			{
				$listeners['controller_pre_dispatch'][$className] = array(
					array('CMF_Core_Listener', 'controllerPreDispatch')
				);
			}
		}

		return $listeners;
	}

	/**
	 * Prepends a listener for the specified event. This method takes an arbitrary
	 * callback, so can be used with more advanced things like object-based
	 * callbacks (and simple function-only callbacks).
	 *
	 * @param string   $event    Event to listen to
	 * @param callback $callback Function/method to call.
	 * @param string $hint If specified (value other than an _), will only be run when the specified hint is provided
	 */
	public function prependListener($event, $callback, $hint = '_')
	{
		if (self::$enabled)
		{
			if (!isset($this->listeners[$event][$hint]))
			{
				$this->listeners[$event][$hint][] = $callback;
			}
			else
			{
				array_unshift($this->listeners[$event][$hint], $callback);
			}
		}
	}

	/**
	 * Appends a listener for the specified event. This method takes an arbitrary
	 * callback, so can be used with more advanced things like object-based
	 * callbacks (and simple function-only callbacks).
	 *
	 * @param string   $event    Event to listen to
	 * @param callback $callback Function/method to call.
	 * @param string $hint If specified (value other than an _), will only be run when the specified hint is provided
	 */
	public function appendListener($event, $callback, $hint = '_')
	{
		if (self::$enabled)
		{
			$this->listeners[$event][$hint][] = $callback;
		}
	}

	/**
	 * Prepends or appends a listeners array to current events. This method takes an arbitrary
	 * callback, so can be used with more advanced things like object-based
	 * callbacks (and simple function-only callbacks).
	 *
	 * @param array   $listeners    Event to listen to
	 * @param boolean $prepend      Add to start of listener's list if true
	 */
	public function addListeners(array $listeners, $prepend = false)
	{
		if (self::$enabled)
		{
			if ($this->listeners)
			{
				$this->listeners = $listeners;
			}
			else
			{
				$this->listeners = ($prepend) ? array_merge_recursive($listeners, $this->listeners) : array_merge_recursive($this->listeners, $listeners);
			}
		}
	}

	/**
	 * Add class lists for later class extender.
	 * Usually called from init_listeners event
	 *
	 * Example how extend
	 * XenForo_DataWriter_Page
	 *      with Some_Addon_DataWriter_Node, Some_Addon_DataWriter_Page and
	 * XenForo_ViewPublic_Page_View
	 *      with Some_Addon_ViewPublic_Page_View:
	 *
	 * $this->addExtenders(
	 *      'XenForo_DataWriter_Page' => array(
	 *            'Some_Addon_DataWriter_Node',
	 *            'Some_Addon_DataWriter_Page'
	 *      ),
	 *      'XenForo_DataWriter_Forum' => array(
	 *            'Some_Addon_DataWriter_Node',
	 *            'Some_Addon_DataWriter_Forum'
	 *      ),
	 *      'XenForo_ViewPublic_Page_View' => 'Some_Addon_ViewPublic_Page_View'
	 * );
	 *
	 * @param array $extenders Array of class list
	 * @param bool  $prepend Add to start of extenders's list if true
	 *
	 */
	public function addExtenders($extenders, $prepend = false)
	{
		$this->_addTypeExtenders($extenders, $prepend, 'all');
	}

	/**
	 * Add class lists for later proxy class extender.
	 * Usually called from init_listeners event
	 *
	 * @param array $extenders Array of class list
	 * @param bool  $prepend   Add to start of extenders's list if true
	 *
	 */
	public function addProxyExtenders($extenders, $prepend = false)
	{
		$this->_addTypeExtenders($extenders, $prepend, 'proxy');
	}

	/**
	 * Add class lists for specified class extender.
	 *
	 * @param array  $extenders Array of class list
	 * @param bool   $prepend   Add to start of extenders's list if true
	 * @param string $type
	 */
	protected function _addTypeExtenders($extenders, $prepend = false, $type = 'all')
	{
		if (self::$enabled && isset(self::$_extendTypes[$type]))
		{
			if (!isset(self::$_extendInput[$type]))
			{
				self::$_extendInput[$type] = $extenders;
			}
			else
			{
				self::$_extendInput[$type] = ($prepend) ? array_merge_recursive($extenders, self::$_extendInput[$type]) : array_merge_recursive(self::$_extendInput[$type], $extenders);
			}
		}
	}

	/**
	 * Extender method for single normal class with event autocreate
	 * Usage only after init_listeners event
	 *
	 * @param string $class   Class name to extend
	 * @param string $extend  Extend class name
	 * @param bool   $prepend Add to start of extenders's list if true
	 *
	 */
	public function extendClass($class, $extend, $prepend = false)
	{
		$this->_extendTypeClass($class, $extend, $prepend, 'all');
	}

	/**
	 * Extender method for proxy classes
	 *
	 * @param string $class   Class name to extend
	 * @param string $extend  Extend class name
	 * @param bool   $prepend Add to start of extenders's list if true
	 *
	 */
	public function extendProxyClass($class, $extend, $prepend = false)
	{
		$this->_extendTypeClass($class, $extend, $prepend, 'proxy');
	}

	/**
	 * Extender method for single normal class with event autocreate
	 * Usage only after init_listeners event
	 *
	 * @param string $class   Class name to extend
	 * @param string $extend  Extend class name
	 * @param bool   $prepend Add to start of extenders's list if true
	 * @param string $type
	 */
	protected function _extendTypeClass($class, $extend, $prepend = false, $type = 'all')
	{
		if (self::$enabled && $class && $extend && isset(self::$_extendTypes[$type]))
		{
			if (!isset(self::$_extend[$type][$class]))
			{
				$this->prependListener(self::$_extendTypes[$type]['event'], array('CMF_Core_Listener', self::$_extendTypes[$type]['method']), $class);
				self::$_extend[$type][$class] = array();
			}
			if (!is_array(self::$_extend[$type][$class]))
			{
				self::$_extend[$type][$class] = array(self::$_extend[$type][$class]);
			}

			if ($prepend)
			{
				array_unshift(self::$_extend[$type][$class], $extend);
			}
			else
			{
				self::$_extend[$type][$class][] = $extend;
			}
		}
	}

	/**
	 * Core event listener - must be first in the queue in XenForo Admin CP
	 *
	 * !!!WARNING!!! XenForo not fully loaded at this time.
	 * Only for setup event listeners by CMF_Core_Listener and CMF_Core_Application methods
	 *
	 * @param CMF_Core_Listener $events - Core listener class instance
	 */
	public static function initListeners(CMF_Core_Listener $events)
	{
		self::$enabled = true;
		CMF_Core_Application::$enabled = true;
		//Core listeners
		$events->addExtenders(
			array(
			     //datawriters
			     'XenForo_DataWriter_Node'                   => 'CMF_Core_DataWriter_Node',
			     'XenForo_DataWriter_Forum'                  => 'CMF_Core_DataWriter_Node',
			     'XenForo_DataWriter_Page'                   => 'CMF_Core_DataWriter_Node',
			     'XenForo_DataWriter_Category'               => 'CMF_Core_DataWriter_Node',
			     'XenForo_DataWriter_LinkForum'              => 'CMF_Core_DataWriter_Node',
			     //models
			     'XenForo_Model_Thread'                      => 'CMF_Core_Model_Thread',
			     'XenForo_Model_Post'                        => 'CMF_Core_Model_Post',
			     'XenForo_Model_Node'                        => 'CMF_Core_Model_Node',
			     'XenForo_Model_Forum'                       => 'CMF_Core_Model_Forum',
			)
		);
		$events->addProxyExtenders(
			array(
			     'XenForo_DataWriter'                   => 'CMF_Core_DataWriter_Abstract',
			     'XenForo_ControllerAdmin_NodeAbstract' => 'CMF_Core_ControllerAdmin_NodeAbstract'
			)
		);
		/*
		$events->configureInput('XenForo_DataWriter_Discussion_Thread', array(
			'some_field' => CMF_Core_Application::ARRAY_SIMPLE
		), 'XenForo_ControllerPublic_Forum', 'AddThread');

		$events->configureInput('XenForo_DataWriter_DiscussionMessage_Post', array(
			'some_field' => CMF_Core_Application::ARRAY_SIMPLE
		), 'XenForo_ControllerPublic_Post', 'Save');

		$events->configureInput('XenForo_DataWriter_Discussion_Thread', array(
			'some_field' => CMF_Core_Application::ARRAY_SIMPLE
		), 'XenForo_ControllerPublic_Thread', 'Save');
		*/
	}

	/**
	 * controller_pre_dispatch listener
	 *
	 * @param XenForo_Controller $controller
	 * @param string             $action
	 * @param string             $controllerName
	 */
	public static function controllerPreDispatch(XenForo_Controller $controller, $action, $controllerName)
	{
		$actions = CMF_Core_Application::getMerged(
			CMF_Core_Application::INPUT_ACTIONS,
			$controllerName
		);
		if (isset($actions[$action]))
		{
			$dwClasses = is_array($actions[$action]) ? array_unique($actions[$action]) : array($actions[$action]);
			foreach ($dwClasses as $dwName)
			{
				CMF_Core_Application::setMerged(
					CMF_Core_Application::DW_DATA,
					$dwName,
					$controller->getInput()->filter(CMF_Core_Application::getMerged(CMF_Core_Application::INPUT_FIELDS, $dwName))
				);
			}
		}
	}

	public function configureDataWriter($dwName, array $fields)
	{
		if ($dwName && $fields)
		{
			CMF_Core_Application::setMerged(CMF_Core_Application::DW_FIELDS, $dwName, $fields);
		}
	}

	public function configureInput($dwName, array $fields, $controllerName = null, $actions = null)
	{
		if ($dwName && $fields)
		{
			CMF_Core_Application::setMerged(CMF_Core_Application::INPUT_FIELDS, $dwName, $fields);
			if ($controllerName && $actions)
			{
				$actionKeys = is_array($actions) ? array_fill_keys($actions, $dwName) : array($actions => $dwName);
				CMF_Core_Application::setMerged(CMF_Core_Application::INPUT_ACTIONS, $controllerName, $actionKeys, true);
			}
		}
	}

	/**
	 * Base callback called by event listeners in load_class event
	 * Extends class with list of classes preset in init_listeners event
	 * Counts XFCP proxy class declarations
	 * and handles multi extend classes (traits emulation)
	 *
	 * @param string $class  The name of the class to be created
	 * @param array  $extend A modifiable list of classes that wish to extend the class
	 */
	public static function loadClass($class, array &$extend)
	{
		if (self::$enabled && isset(self::$_extend['all'][$class]))
		{
			if (!is_array(self::$_extend['all'][$class]))
			{
				self::$_extend['all'][$class] = array(self::$_extend['all'][$class]);
			}
			foreach(self::$_extend['all'][$class] as $dynamic)
			{
				if (empty(self::$_counters[$dynamic]))
				{
					$extend[] = $dynamic;
					self::$_counters[$dynamic] = 1;
				}
				else
				{
					$extend[] = $dynamic. '__' . self::$_counters[$dynamic];
					self::$_counters[$dynamic]++;
				}
			}
		}
	}

	/**
	 * Base callback called by event listeners in load_class_proxy_class event
	 * Extends class with list of classes preset in init_listeners event
	 * Counts XFCP proxy class declarations
	 * and handles multi extend classes (traits emulation)
	 *
	 * @param string $class  The name of the class to be created
	 * @param array  $extend A modifiable list of classes that wish to extend the class
	 */
	public static function loadProxyClass($class, array &$extend)
	{
		if (self::$enabled && isset(self::$_extend['proxy'][$class]))
		{
			if (!is_array(self::$_extend['proxy'][$class]))
			{
				self::$_extend['proxy'][$class] = array(self::$_extend['proxy'][$class]);
			}
			$extend = array_merge($extend, self::$_extend['proxy'][$class]);
		}
	}
}