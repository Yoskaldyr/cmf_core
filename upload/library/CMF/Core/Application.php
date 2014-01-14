<?php

/**
 * CMF Core Application
 *
 * @package CMF_Core
 * @author Yoskaldyr <yoskaldyr@gmail.com>
 */
class CMF_Core_Application extends XenForo_Application
{
	/**
	 * Constant for input fields.  Use this for string input.
	 *
	 * @var string
	 */
	const STRING = 'string';

	/**
	 * Constant for input fields.  Use this for numeric input.
	 *
	 * @var string
	 */
	const NUM = 'num';

	/**
	 * Constant for input fields.  Use this for unsigned numeric input.
	 *
	 * @var string
	 */
	const UNUM = 'unum';

	/**
	 * Constant for input fields.  Use this for integer input.
	 *
	 * @var string
	 */
	const INT = 'int';

	/**
	 * Constant for input fields.  Use this for unsigned integer input.
	 *
	 * @var string
	 */
	const UINT = 'uint';

	/**
	 * Constant for input fields.  Use this for floating point number input.
	 *
	 * @var string
	 */
	const FLOAT = 'float';

	/**
	 * Constant for input fields.  Use this for boolean fields.
	 *
	 * @var string
	 */
	const BOOLEAN = 'boolean';

	/**
	 * Constant for input fields.  Use this for binary input.
	 *
	 * @var string
	 */
	const BINARY = 'binary';

	/**
	 * Constant for input fields.  Use this for array input.
	 *
	 * @var string
	 */
	const ARRAY_SIMPLE = 'array_simple';

	/**
	 * Constant for input fields.  Use this for json array input.
	 *
	 * @var string
	 */
	const JSON_ARRAY = 'json_array';

	/**
	 * Constant for input fields.  Use this for date/time input.
	 *
	 * @var string
	 */
	const DATE_TIME = 'dateTime';

	/**
	 * Constant for data fields (datawriter).  Use this for 0/1 boolean integer fields.
	 *
	 * @var string
	 */
	const TYPE_BOOLEAN = 'boolean';

	/**
	 * Constant for data fields (datawriter).  Use this for string fields. String fields are assumed
	 * to be UTF-8 and limits will refer to characters.
	 *
	 * @var string
	 */
	const TYPE_STRING = 'string';

	/**
	 * Constant for data fields (datawriter).  Use this for binary or ASCII-only fields. Limits
	 * will refer to bytes.
	 *
	 * @var string
	 */
	const TYPE_BINARY = 'binary';

	/**
	 * Constant for data fields (datawriter).  Use this for integer fields. Limits can be applied
	 * on the range of valid values.
	 *
	 * @var string
	 */
	const TYPE_INT = 'int';

	/**
	 * Constant for data fields (datawriter).  Use this for unsigned integer fields. Negative values
	 * will always fail to be valid. Limits can be applied on the range of valid values.
	 *
	 * @var string
	 */
	const TYPE_UINT = 'uint';

	/**
	 * Constant for data fields (datawriter).  Use this for unsigned integer fields. This differs from
	 * TYPE_UINT in that negative values will be silently cast to 0. Limits can be
	 * applied on the range of valid values.
	 *
	 * @var string
	 */
	const TYPE_UINT_FORCED = 'uint_forced';

	/**
	 * Constant for data fields (datawriter).  Use this for float fields. Limits can be applied
	 * on the range of valid values.
	 *
	 * @var string
	 */
	const TYPE_FLOAT = 'float';

	/**
	 * Constant for data fields (datawriter). Data is serialized. Ensures that if the data is not a string, it is serialized to ne.
	 *
	 * @var string
	 */
	const TYPE_SERIALIZED = 'serialized';

	/**
	 * Constant for data fields (datawriter). Data is serialized to JSON.
	 *
	 * @var string
	 */
	const TYPE_JSON = 'json';

	/**
	 * Constant for data fields (datawriter). Use this for fields that have a type that cannot be
	 * known statically. Use this sparingly, as you must write code to ensure that
	 * the value is a scalar before it is inserted into the DB. The behavior if you
	 * don't do this is not defined!
	 *
	 * @var string
	 */
	const TYPE_UNKNOWN = 'unknown';

	/**
	 * Constant key for storing
	 * input filters definitions
	 */
	const INPUT_FIELDS = 'inputFields';

	/**
	 * Constant key for storing
	 * input action definitions
	 */
	const INPUT_ACTIONS = 'inputActions';

	/**
	 * Constant key for storing
	 * datawriter fields definitions
	 */
	const DW_FIELDS = 'dwFields';

	/**
	 * Constant key for storing
	 * datawriter data for bulk set on presave
	 */
	const DW_DATA = 'dwData';

	/**
	 * Constant key for storing
	 * datawriter's extra data
	 */
	const DW_EXTRA = 'cmfExtraInputData';

	/**
	 * Constant key for datawriter's option
	 */
	const DW_EXTRA_OPTION = 'cmfExtraMergedData';

	/**
	 * Constant key for datawriter's option for global enable/disable
	 */
	const DW_ENABLE_OPTION = 'cmfEnableDataWriter';

	/**
	 * CMF_Core application registry store
	 *
	 * @var array
	 */
	private static $_data = array();

	/**
	 * CMF_Core application DW fields cache
	 *
	 * @var array
	 */
	private static $_fieldsCache = array();

	/**
	 * Global trigger for enable/disable CMF_Core
	 * @var bool
	 */
	public static $enabled = false;

	public static $dwDefinitions = array(
		'XenForo_DataWriter_Discussion_Thread' => array(
		    'table' => 'xf_thread',
		    'key' => 'thread_id',
		    'alias' => 'thread'
		),
		'XenForo_DataWriter_DiscussionMessage_Post' => array(
			'table' => 'xf_post',
			'key' => 'post_id',
			'alias' => 'post'
		),
		'XenForo_DataWriter_Forum' => array(
			'dwKey' => array('XenForo_DataWriter_Node', 'XenForo_DataWriter_Forum'),
			'table' => array('xf_node', 'xf_forum'),
			'key' => 'node_id',
		    'alias' => 'node'
		)
	);

	/**
	 * Gets data from core application registry by key and type/class/classname
	 *
	 * @param string              $key    Key for Data
	 * @param string|array|object $class  SubType (if array returned merged result)
	 * @param bool                $searchParents
	 *
	 * @return array
	 *
	 */
	public static function getMerged($key, $class, $searchParents = false)
	{
		if (self::$enabled && $class && $key && !empty(self::$_data[$key]))
		{
			//if merged result
			if (is_array($class))
			{
				$merged = array();
				foreach ($class as $typeItem)
				{
					$merged = XenForo_Application::mapMerge($merged, self::getMerged($key, $typeItem, $searchParents));
				}
				return $merged;
			}
			else if (is_object($class) || $searchParents)
			{
				$merged = array();
				if ($originalClassName = self::resolveOriginalClassName($class))
				{
					$classList = array($originalClassName => $originalClassName);
					if ($parents = class_parents($originalClassName))
					{
						$classList = array_merge(array_reverse($parents, true), $classList);
					}
					foreach ($classList as $className)
					{
						if (isset(self::$_data[$key][$className]))
						{
							$merged = XenForo_Application::mapMerge($merged, self::$_data[$key][$className]);
						}
					}
				}
				return $merged;
			}
			else if (isset(self::$_data[$key][$class]))
			{
				$return = self::$_data[$key][$class];
				return $return;
			}
		}
		return array();
	}

	/**
	 * Gets data from core application registry by key for all type/class/classname
	 *
	 * @param string              $key       Key for Data
	 *
	 * @return array
	 *
	 * */
	public static function getFullKey($key)
	{
		return (self::$enabled && $key && !empty(self::$_data[$key])) ? self::$_data[$key] : array();
	}

	/**
	 * Save data to core application registry by key and type/classname
	 *
	 * @param string $key       Key for Data
	 * @param string $className SubType or Class name
	 * @param array  $data      Data to save
	 * @param bool   $recursiveMerge Merge method array_merge_recursive (true) or XenForo_Application::mapMerge (false)
	 *
	 * @return array
	 */

	public static function setMerged($key, $className, array $data, $recursiveMerge = false)
	{
		if (self::$enabled && $className && $key && $data)
		{
			if ($typeName = self::resolveOriginalClassName($className))
			{
				if (!isset(self::$_data[$key][$typeName]))
				{
					self::$_data[$key][$typeName] = array();
				}
				self::$_data[$key][$typeName] = ($recursiveMerge) ? array_merge_recursive(self::$_data[$key][$typeName], $data) : XenForo_Application::mapMerge(self::$_data[$key][$typeName], $data);
			}
		}
	}

	/**
	 * Clears data in core application registry by key or type/class/classname
	 *
	 * @param string              $key       Key for Data
	 * @param string|array|object $className Single type (type/class/class name)
	 *                                       or array of types to clear
	 */

	public static function clearMerged($key='', $className='')
	{
		if (!self::$enabled)
		{
			return;
		}
		else if (!$key)
		{
			self::$_data=array();
		}
		else if (!$className)
		{
			self::$_data[$key]=array();
		}
		//if group clear
		else if (is_array($className))
		{
			foreach ($className as $typeItem)
			{
				self::clearMerged($key, $typeItem);
			}
			return;
		}
		else if ($typeName = self::resolveOriginalClassName($className))
		{
			self::$_data[$key][$typeName] = array();
		}
	}

	/**
	 * Helper for unserialize selected fields in datawriter data
	 *
	 * @param array $data      Data
	 * @param array $dwFields  Datawriter fields definitions
	 *
	 * @return array returns data with unserialized fields
	 */
	public static function unserializeDataByFields($data, $dwFields)
	{
		if ($data && $dwFields && is_array($data) && is_array($dwFields))
		{
			foreach ($dwFields as $fields)
			{
				foreach ($fields as $fieldName => $fieldOptions)
				{
					if (is_array($fieldOptions) && isset($fieldOptions['type']) && $fieldOptions['type'] == self::TYPE_SERIALIZED)
					{
						if (empty($data[$fieldName]))
						{
							$data[$fieldName] = array();
						}
						else if (!is_array($data[$fieldName]))
						{
							$data[$fieldName] = unserialize($data[$fieldName]);
						}
					}
				}
			}
		}
		return $data;
	}

	public static function unserializeDataByKey($data, $key, $useCache = true)
	{
		if ($data && $key && is_array($data))
		{
			$cacheKey = (is_array($key)) ? implode('/', $key) : $key;
			$dwFields = ($useCache && isset(self::$_fieldsCache[$cacheKey])) ? self::$_fieldsCache[$cacheKey] : self::getMerged(self::DW_FIELDS, $key);
			if ($useCache && !isset(self::$_fieldsCache[$cacheKey]))
			{
				self::$_fieldsCache[$cacheKey] = $dwFields;
			}

			return ($dwFields && is_array($dwFields)) ? self::unserializeDataByFields($data, $dwFields) : $data;
		}
		return $data;
	}

	/**
	 * Type name normalizer. Resolves type name by keys array or core registry
	 * @param  string|array|object  $class Class name / Type to resolve
	 *
	 * @return boolean|string       Returns type name as string if success
	 */
	public static function resolveOriginalClassName($class)
	{
		if (!$class || (!is_object($class) && !is_string($class)))
		{
			return false;
		}
		$className = is_object($class) ? get_class($class) : $class;

		return ($original = array_search($className, parent::$_classCache, true)) ? $original : $className;
	}

	public static function prepareFetchOptions(array $fetchOptions, $dwClass)
	{
		if (!isset($fetchOptions['selectFields'], $fetchOptions['joinTables'], self::$dwDefinitions[$dwClass]))
		{
			return $fetchOptions;
		}
		$alias = self::$dwDefinitions[$dwClass]['alias'];
		$tableName = self::$dwDefinitions[$dwClass]['table'];
		$primaryField = self::$dwDefinitions[$dwClass]['key'];
		$dwKey = (isset(self::$dwDefinitions[$dwClass]['dwKey'])) ? self::$dwDefinitions[$dwClass]['dwKey'] : $dwClass;

		//we can't use auto find parents :(
		$dwCoreFields = self::getMerged(
			self::DW_FIELDS,
			$dwKey
		);

		if (is_array($tableName))
		{
			foreach ($tableName as $name)
			{
				unset($dwCoreFields[$name]);
			}
		}
		else
		{
			unset($dwCoreFields[$tableName]);
		}

		if ($dwCoreFields && is_array($dwCoreFields))
		{
			if (!$primaryField)
			{
				$primaryField = $alias . '_id';
			}
			foreach ($dwCoreFields as $table => $fields)
			{
				unset($fields[$primaryField]);
				if ($fields && is_array($fields))
				{
					$fetchOptions['selectFields'] .= ',
					' . $table . '.' . implode(', ' . $table . '.', $fields);

					$fetchOptions['joinTables'] .= '
					LEFT JOIN ' . $table . ' AS ' . $table . ' ON
							(' . $alias . '.' . $primaryField . ' = ' . $table . '.' . $primaryField . ')';
				}
			}
		}

		return $fetchOptions;
	}
}
