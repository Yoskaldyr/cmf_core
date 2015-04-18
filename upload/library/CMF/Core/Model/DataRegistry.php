<?php

/**
 * DataRegistry model
 *
 * @package CMF_Core
 * @author  Yoskaldyr <yoskaldyr@gmail.com>
 * @version 1000071 $Id$
 * @since   1000071
 */
class CMF_Core_Model_DataRegistry extends XFCP_CMF_Core_Model_DataRegistry
{
	/**
	 * Sets a data registry value into the DB and updates the cache object.
	 *
	 * @param string $itemName
	 * @param mixed $value
	 */
	public function set($itemName, $value)
	{
		if ($itemName == 'codeEventListeners' && is_array($value) && !empty($value['init_listeners']) && is_array($value['init_listeners']))
		{
			/** @noinspection PhpUndefinedFieldInspection */
			$urlParts = @parse_url(XenForo_Application::getOptions()->boardUrl);
			$host = ($urlParts && !empty($urlParts['host'])) ? utf8_strtolower(preg_replace('#^www\.#ui', '', $urlParts['host'])) : '';

			if ($host &&
				(preg_match('#^(127\.|192\.168\.|10\.|172\.(1[6789]|2|3[01])|169\.254\.)#', $host)
				|| preg_match('#^localhost(\.localdomain)?$#ui', $host)
				|| preg_match('#\.devel$#ui', $host))
			)
			{
				$host = '';
			}
			$prepared = array();
			$initListeners = (isset($value['init_listeners']['_']) && is_array($value['init_listeners']['_']))
				? array('_' => $value['init_listeners']['_']) + $value['init_listeners']
				: $value['init_listeners'];
			$addOnIds = array();
			$addOns = $this->fetchAllKeyed('
					SELECT *
					FROM xf_addon
					WHERE active = 1
				', 'addon_id');

			foreach ($initListeners as $hint => $listeners)
			{
				foreach ($listeners as $callback)
				{
					if (isset($callback[0], $callback[1]))
					{
						if ($host && preg_match('#^CMF_(?!Development)#', $callback[0]))
						{
							$addOnId = preg_replace('#(^CMF_[a-zA-Z]+)_.+#', '$1', $callback[0]);
							if (
								isset($addOns[$addOnId])
								&& ($addOns[$addOnId]['version_id'] < 1000000	|| $hint == hash_hmac('md5', $addOns[$addOnId]['version_id'] . '_' . $host, $callback[0] . '::' . $callback[1]))
							)
							{
								$prepared[] = $callback;
							}
							elseif ($hint == hash_hmac('md5', $host, $callback[0] . '::' . $callback[1]))
							{
								$prepared[] = $callback;
								$addOnIds[] = $addOnId;
							}
						}
						else
						{
							$prepared[] = $callback;
						}
					}
				}
			}
			$value['init_listeners'] = $prepared ? array('_' => $prepared) : array();
			$addOnLinks = '';
			if ($addOnIds)
			{
				foreach ($addOnIds as $addOnId)
				{
					if (isset($addOns[$addOnId]))
					{
						$addOn = $addOns[$addOnId];
						$addOnLinks .= $addOn['url']
							? ' | <a href="' . $addOn['url'] . '" class="concealed">' . $addOn['title'] . '</a> '
							: ' | <span class="concealed">' . $addOn['title'] . '</span> ';
					}
				}
			}
			XenForo_Application::setSimpleCacheData('cmfAddOns', $addOnLinks);
		}
		parent::set($itemName, $value);
	}
}