<?php

class CMF_Core_Install
{
	public static function install(/** @noinspection PhpUnusedParameterInspection */
		$existingAddOn, $addOnData)
	{
		if (XenForo_Application::$versionId < 1020470)
		{
		    throw new XenForo_Exception('This Add-On requires XenForo version 1.2.4 or higher.');
		}
		/** @var XenForo_Application $app */
		$app = XenForo_Application::getInstance();
		$configFile = $app->getConfigDir() . '/config.php';
		$config = file_get_contents($configFile);
		$pos = strpos($config, '::getProxy(');
		if (!$pos)
		{
			if (is_writable($configFile))
			{
				try
				{
					$tempFile = tempnam(XenForo_Helper_File::getTempDir(), 'xf');
					if ($tempFile)
					{
						$config .= "\nCMF_Core_Autoloader::getProxy();";
						file_put_contents($tempFile, $config);
						$pos = XenForo_Helper_File::safeRename($tempFile, $configFile);
					}
				} catch (Exception $e)
				{
				}
			}
			if (!$pos)
			{
				throw new XenForo_Exception('config.php is not writable. Please add "CMF_Core_Autoloader::getProxy();" to the end of config.php manually before run install.');
			}
		}
	}

	public static function uninstall()
	{
		/** @var XenForo_Application $app */
		$app = XenForo_Application::getInstance();
		$configFile = $app->getConfigDir() . '/config.php';
		$config = file_get_contents($configFile);
		$pos = strpos($config, '::getProxy(');
		if ($pos)
		{
			if (is_writable($configFile))
			{
				try
				{
					$tempFile = tempnam(XenForo_Helper_File::getTempDir(), 'xf');
					if ($tempFile)
					{
						$config = preg_replace('#\n[^\n]+::getProxy\([^\n]*\);#u', '', $config);
						file_put_contents($tempFile, $config);
						if (XenForo_Helper_File::safeRename($tempFile, $configFile))
						{
							$pos = strpos($config, '::getProxy(');
						}
					}
				} catch (Exception $e)
				{
				}
			}
			if ($pos)
			{
				throw new XenForo_Exception('config.php is not writable. Please remove any "getProxy()" calls from config.php manually before run uninstall.');
			}
		}
		$classDir = XenForo_Helper_File::getInternalDataPath() . '/proxy_classes';
		if (is_dir($classDir) && is_writable($classDir))
		{
			$files = glob($classDir . '/*.php');
			if (is_array($files))
			{
				foreach ($files AS $file)
				{
					@unlink($file);
				}
			}
			@rmdir($classDir);
		}

		XenForo_Application::setSimpleCacheData('cmfAddOns', false);
	}
}