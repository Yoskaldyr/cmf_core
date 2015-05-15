<?php

/**
 * Class CMF_Core_Autoloader
 * CMF Autoloader class
 *
 * @package CMF_Core
 * @author Yoskaldyr <yoskaldyr@gmail.com>
 */
class CMF_Core_Autoloader extends XenForo_Autoloader
{
	protected $_eval;

	/**
	 * CMF_Core_Listener cache.
	 *
	 * @var CMF_Core_Listener
	 */
	protected $_events;

	/**
	 * Path to directory for storing the proxy classes.
	 *
	 * @var string
	 */
	protected $_classDir;

	/**
	 * On autoload this class init_listeners event will be fired.
	 *
	 * @var bool
	 */
	protected $_fireInit = false;

	/**
	 * If true loads core events lately.
	 *
	 * @var bool
	 */
	protected $_lateLoad = true;

	/**
	 * Public setter for _eval
	 *
	 * @param boolean $eval Fail-safe proxy loader with php 'eval'.
	 * @return $this
	 */
	public function setEval($eval = true)
	{
		$this->_eval = (bool) $eval;
		return $this;
	}

	/**
	 * Manually reset the new autoloader instance. Use this to inject a modified version.
	 *
	 * @param XenForo_Autoloader|null|boolean|string
	 * @return $this|CMF_Core_Autoloader
	 */
	public static function getProxy($newInstance = null)
	{
		$instance = XenForo_Autoloader::getInstance();
		if (!($instance instanceof CMF_Core_Autoloader))
		{
			$lateLoad = false;
			if (!$newInstance)
			{
				/** @noinspection CallableParameterUseCaseInTypeContextInspection */
				$newInstance = new self();
				$lateLoad = true;
			}
			/** @noinspection CallableParameterUseCaseInTypeContextInspection */
			elseif (is_bool($newInstance))
			{
				/** @noinspection CallableParameterUseCaseInTypeContextInspection */
				$newInstance = new self();
			}
			/** @noinspection CallableParameterUseCaseInTypeContextInspection */
			elseif (is_string($newInstance) && class_exists($newInstance))
			{
				$newInstance = new $newInstance();
			}
			$newInstance->setupAutoloader($instance->getRootDir());
			XenForo_Autoloader::setInstance($newInstance);
			//autoload not working yet
			//TODO fix to __DIR__ Right now is for php 5.2 compatibility
			/** @noinspection dirnameCallOnFileConstantInspection */
			include(dirname(__FILE__) . '/Listener.php');
			$newInstance->_events = CMF_Core_Listener::getInstance();
			$newInstance->_lateLoad = $lateLoad;
			if (!$lateLoad)
			{
				$newInstance->_fireInit = true;
			}

			return $newInstance;
		}
		return $instance;
	}

	/**
	* Internal method that actually applies the autoloader. See {@link setupAutoloader()}
	* for external usage.
	*/
	protected function _setupAutoloader()
	{
		spl_autoload_unregister(array(XenForo_Autoloader::getInstance(), 'autoload'));
		parent::_setupAutoloader();
	}

	/**
	 * Autoload the specified class.
	 *
	 * @param string $class Name of class to autoload
	 *
	 * @throws Exception
	 * @return boolean
	 */
	public function autoload($class)
	{
		//first class load after xenforo listeners load
		if ($this->_fireInit
			|| ($this->_lateLoad && $class == 'XenForo_Options' && !class_exists('XenForo_Options', false)))
		{
			$this->_fireInit = false;
			$this->_events->fireInitListeners($this->_lateLoad);
		}

		if (class_exists($class, false) || interface_exists($class, false))
		{
			return true;
		}

		// Multi dynamic proxy class
		if (strpos($class, '__'))
		{
			list($baseClass, $counter) = explode('__', $class);
			$counter = intval($counter);
			if (!$counter || ($class != $baseClass . '__' . $counter))
			{
				return false;
			}

			$baseFile = $this->autoloaderClassToFile($baseClass);
			$timestamp = @filemtime($baseFile);
			if (!$baseFile || !$timestamp)
			{
				return false;
			}
			$proxyFile = $this->_proxyFile($class, $timestamp);
			//slow but safe eval fallback
			if ($this->_isEval())
			{
				if ($body = $this->_getDynamicBody($baseClass, $counter, $baseFile))
				{
					eval('?>' . $body);
				}
			}
			//if ready or created
			elseif (
				file_exists($proxyFile)
				|| $this->_saveClass($proxyFile, $this->_getDynamicBody($baseClass, $counter, $baseFile))
			)
			{
				/** @noinspection PhpIncludeInspection */
				include($proxyFile);
			}
		}
		elseif (isset($this->_events->listeners['load_class_proxy_class'][$class]))
		{
			$baseFile = $this->autoloaderClassToFile($class);
			$timestamp = @filemtime($baseFile);
			if (!$baseFile || !$timestamp)
			{
				return false;
			}

			$extend = array();
			XenForo_CodeEvent::fire('load_class_proxy_class', array($class, &$extend), $class);

			if ($extend)
			{
				$createClass = 'XFProxy_' . $class;
				$proxyFile = $this->_proxyFile($class, $timestamp);
				//slow but safe eval fallback
				if ($this->_isEval())
				{
					if ($body = $this->_getProxyBody($class, $baseFile))
					{
						eval('?>'.$body);
					}
				}
				//if ready or created
				elseif (
					file_exists($proxyFile)
					|| $this->_saveClass($proxyFile, $this->_getProxyBody($class, $baseFile))
				)
				{
					/** @noinspection PhpIncludeInspection */
					include($proxyFile);
				}
				//dynamic resolve only if proxy class loaded
				if (($isInterface = interface_exists($createClass, false)) || class_exists($createClass, false))
				{
					try
					{
						$type = 'class';

						if ($isInterface)
						{
							$type = 'interface';
						}
						//convention over configuration: word "Abstract" must preset in abstract class names
						elseif (strpos($createClass, 'Abstract') || strpos(reset($extend), 'Abstract'))
						{
							$type = 'abstract class';
						}

						foreach ($extend AS $dynamicClass)
						{
							// XenForo Class Proxy, in case you're wondering
							$proxyClass = 'XFCP_' . $dynamicClass;
							eval($type . ' ' . $proxyClass . ' extends ' . $createClass . ' {}');
							$this->autoload($dynamicClass);
							$createClass = $dynamicClass;
						}
						eval($type . ' ' . $class . ' extends ' . $createClass . ' {}');
					}
					catch (Exception $e)
					{
						throw $e;
					}
				}
			}
			else
			{
				/** @noinspection PhpIncludeInspection */
				include($baseFile);
			}
			return (class_exists($class, false) || interface_exists($class, false));
		}
		return parent::autoload($class);
	}

	/**
	 * Gets the path to the proxy class directory (internal_data/proxy_classes).
	 * This directory can be moved above the web root.
	 *
	 * @return string Absolute path
	 */
	protected function _getProxyPath()
	{
		if ($this->_classDir)
		{
			return $this->_classDir;
		}
		if (XenForo_Application::isRegistered('config'))
		{
			return $this->_classDir = XenForo_Helper_File::getInternalDataPath() . '/proxy_classes';
		}
		else
		{
			return $this->_rootDir . '/../internal_data/proxy_classes';
		}
	}

	/**
	 * Creates directory for proxy classes
	 *
	 * @return boolean   Returns true on success
	 */
	protected function _createProxyDirectory()
	{
		if (!is_dir($path = $this->_getProxyPath()))
		{
			if (XenForo_Helper_File::createDirectory($path))
			{
				return XenForo_Helper_File::makeWritableByFtpUser($path);
			}
			else
			{
				return false;
			}
		}
		return true;
	}

	/**
	 * Generates body of proxy class for base XenForo class
	 *
	 * @param string  $class    Original class name
	 * @param string  $baseFile Path to original class file
	 *
	 * @return boolean|string     Returns body of class or false if error
	 */
	protected function _getProxyBody($class, $baseFile = '')
	{
		if (!$baseFile)
		{
			/** @noinspection CallableParameterUseCaseInTypeContextInspection */
			$baseFile = $this->autoloaderClassToFile($class);
		}
		if ($body = file_get_contents($baseFile))
		{
			$body = preg_replace('#([\s\n](class|interface)[\s\n]+)(' . $class . ')([\s\n\{])#u', '$1XFProxy_$3$4', $body, 1, $count);
			return ($count) ? $body : false;
		}
		return false;
	}

	/**
	 * Generates body of dynamic class for multi declarations of XFCP_ proxy classes
	 *
	 * @param string  $class    Original class name
	 * @param integer $counter  Class declaration counter (used as postfix)
	 * @param string  $baseFile Path to original class file
	 *
	 * @return boolean|string     Returns body of class or false if error
	 */
	protected function _getDynamicBody($class, $counter, $baseFile = '')
	{
		if (!$counter)
		{
			return false;
		}
		if (!$baseFile)
		{
			/** @noinspection CallableParameterUseCaseInTypeContextInspection */
			$baseFile = $this->autoloaderClassToFile($class);
		}
		if ($body = file_get_contents($baseFile))
		{
			$body = preg_replace('#([\s\n]class[\s\n]+)(' . $class . ')([\s\n]+extends[\s\n]+XFCP_)(' . $class . ')([\s\n\{])#u', '$1$2__' . $counter . '$3$4__' . $counter . '$5', $body, 1, $count);
			return ($count) ? $body : false;
		}
		return false;
	}

	/**
	 * Saves proxy class
	 *
	 * @param string  $proxyFile Path to proxy class file
	 * @param string  $body      Body of class
	 *
	 * @return boolean  Returns true on success
	 */
	protected function _saveClass($proxyFile, $body)
	{
		return (
			$this->_createProxyDirectory()
				&& !empty($body)
				&& ($tempFile = tempnam(XenForo_Helper_File::getTempDir(), 'xf'))
				&& file_put_contents($tempFile, $body)
				&& XenForo_Helper_File::safeRename($tempFile, $proxyFile)
		);
	}

	/**
	 * Resolves a class name to an proxy load path.
	 *
	 * @param string         $class     Name of class to proxy load
	 * @param string|integer $timestamp Modify time of base class
	 *
	 * @return string|boolean False if the class contains invalid characters.
	 */
	protected function _proxyFile($class, $timestamp)
	{
		if (!$timestamp || preg_match('#[^a-zA-Z0-9_]#', $class))
		{
			return false;
		}

		return $this->_getProxyPath() . '/' . $class . '__' . $timestamp . '.php';
	}

	protected function _isEval()
	{
		return $this->_eval === null ? ($this->_eval = file_exists($this->_getProxyPath() . '/eval.txt')) : $this->_eval;
	}

}