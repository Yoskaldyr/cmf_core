<?php

/**
 * CMF Base DataWriter class
 *
 * @package CMF_Core
 * @author Yoskaldyr <yoskaldyr@gmail.com>
 * @version 1000011 $Id$
 * @since   1000011
 */
abstract class CMF_Core_DataWriter_Abstract extends XFCP_CMF_Core_DataWriter_Abstract
{
	/**
	 * Constructor. (changing _getFields() method)
	 *
	 * @param integer $errorHandler   Error handler. See {@link ERROR_EXCEPTION} and related.
	 * @param array|null Dependency injector. Array keys available: db, cache.
	 */
	public function __construct($errorHandler = self::ERROR_ARRAY, array $inject = null)
	{
		$this->_db = (isset($inject['db']) ? $inject['db'] : XenForo_Application::getDb());

		if (isset($inject['cache']))
		{
			$this->_cache = $inject['cache'];
		}

		$this->setErrorHandler($errorHandler);

		$fields = $this->_getFieldsCMF();

		if (is_array($fields))
		{
			$this->_fields = $fields;
		}

		$options = $this->_getDefaultOptions();
		if (is_array($options))
		{
			$this->_options = $options;
		}
		$this->_options[CMF_Core_Application::DW_EXTRA_OPTION] = false;
	}

	protected function _getFieldsCMF()
	{
		$fields = $this->_getFields();
		if ($dwCoreFields = CMF_Core_Application::getMerged(CMF_Core_Application::DW_FIELDS, $this, false, true))
		{
			$fields = XenForo_Application::mapMerge($fields, $dwCoreFields);
		}
		return $fields;
	}

	public function getFields()
	{
		return $this->_getFieldsCMF();
	}

	public function getFieldNames($tableName = null)
	{
		$tables = $this->_getFieldsCMF();

		if (!empty($tableName))
		{
			if (empty($tables[$tableName]))
			{
				$this->error("No fields are defined for table '{$tableName}'.");
			}

			return array_keys($tables[$tableName]);
		}

		$fieldNames = array();

		foreach ($tables AS $fields)
		{
			foreach ($fields AS $fieldName => $fieldInfo)
			{
				$fieldNames[] = $fieldName;
			}
		}

		return array_unique($fieldNames);
	}

	public function preSave()
	{
		// retrieving from core with remove (only single get)
		if ($coreFields = CMF_Core_Application::getMerged(CMF_Core_Application::DW_DATA, $this, false, true))
		{
			$extraFields = array();
			//manual fieldname search not buggy $this->getFieldNames()
			$fieldNames = array();
			foreach ($this->_fields AS $fields)
			{
				foreach ($fields AS $fieldName => $fieldInfo)
				{
					$fieldNames[] = $fieldName;
				}
			}
			$fieldNames = array_unique($fieldNames);

			foreach ($coreFields as $name => $field)
			{
				if (!in_array($name, $fieldNames))
				{
					$extraFields[$name] = $field;
					unset($coreFields[$name]);
				}
			}
			if ($extraFields)
			{
				// saving unparsed data in datawriter extra data
				$this->setExtraData(CMF_Core_Application::DW_EXTRA, $extraFields);
			}
			$this->bulkSet($coreFields);
		}

		parent::preSave();
	}

	/**
	 * Serialized array validator
	 * Unserialize checks with force data convert to serialized array
	 * or null if check failed
	 *
	 * @param mixed $data Validated data
	 * @return bool Always returns true
	 */
	protected function _validateSerializedArray(&$data)
	{
		if (!$data)
		{
			$data = null;
		}
		else
		{
			$dataNew = $data;

			if (!is_array($dataNew))
			{
				$dataNew = unserialize($dataNew);
				if (!is_array($dataNew))
				{
					$dataNew = null;
				}
			}
			$data = ($dataNew) ? serialize($dataNew) : null;
		}
		return true;
	}

	/**
	 * Gets data related to this object regardless of where it is defined (new or old).
	 *
	 * @param string $field     Field name
	 * @param bool   $onlyArray Returns only array if true (if data is not array returns empty array)
	 * @param string $tableName Table name, if empty loops through tables until first match
	 *
	 * @return mixed Returns null if the specified field could not be found.
	 */
	public function getUnserialize($field, $onlyArray = false, $tableName = '')
	{
		$data = $this->get($field, $tableName);
		if (is_string($data))
		{
			$data = @unserialize($data);
		}
		return $onlyArray
			? (is_array($data) ? $data : array())
			: $data;
	}

	/**
	 * Merges the new and existing data to show a "final" view of the data. This will
	 * generally reflect what is in the database.
	 *
	 * If no table is specified, all data will be flattened into one array. New data
	 * takes priority over existing data, and earlier tables in the list take priority
	 * over later tables.
	 *
	 * @param string $tableName
	 *
	 * @return array
	 */
	public function getMergedData($tableName = '')
	{
		$data = parent::getMergedData($tableName);
		if (!$tableName && $this->getOption(CMF_Core_Application::DW_EXTRA_OPTION) && ($extra = $this->getExtraData(CMF_Core_Application::DW_EXTRA)))
		{
			$data += $extra;
		}
		return $data;
	}

}