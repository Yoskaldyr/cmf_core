Autoloader CMF_Core_Autoloader
==============================
Description
-----------
The core for development and building functional uses own class autoloader which is very similar XenForo_Autoloader but has special features.

To activate full functional of the core you need to proceed standart installation process and enable replacement of standard XenForo autoloader.

The earliest method without edition of original files is initialization of autoloader in `config.php`. This method doesn't affect on forum update or installing any other addons. The core is initialized is early enough to catch almost any class after its initialization.

Standard mode of autoloader. Production.
----------------------------------------
To activate core's autoloader insert the following into beginning of `config.php`:

~~~php
<?php
CMF_Core_Autoloader::getProxy();
~~~

Using such method `CMF_Core_Autoloader` is loaded before `XenForo_FrontController`. It allows to extend almost every class except `XenForo_Application`:

~~~
1. index.php
2. library/XenForo/Autoloader.php
3. library/XenForo/Application.php
4. library/Zend/Registry.php
5. library/Lgpl/utf8.php
6. library/Zend/Config.php
7. library/config.php
8. library/CMF/Core/Autoloader.php
.....
.. library/XenForo/FrontController.php
~~~
