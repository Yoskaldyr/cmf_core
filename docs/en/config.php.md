Production mode
===============
All addons stored in /library/ (default location)
Add to config.php:

~~~php
CMF_Core_Autoloader::getProxy();
~~~

-------------

Development mode
================
All addons (with CMF_Core add-on) stored in /addons/ (path configurable)
Add to config.php:

~~~php
include('addons/cmf_core/upload/library/CMF/Core/Autoloader.php');
CMF_Core_Autoloader::getProxy()
	->setAddonDir('addons')
		//Additional external library with different namespace stored with TMS addon
	->addAddonMap(
		array(
	        'Diff' => 'tms'
		)
	);
~~~
