CMF_Core - Extended core
========================

Common difficulties you'll face while doing addon development in XenForo
------------------------------------------------------------------------
Developers often face these problems during the development process in XenForo:

 1. It's not possible to extend basic classes in XenForo, especially static helpers.
 2. It's not possible to extend multiple XenForo classes with a single third party class due to limitation of a single class repetitious declaring (**Cannot redeclare class** error).
 3. Pushing input data from controller to data writer is really hard to do when you're dealing with an extension of common data types (nodes, messages, threads).
 4. Changing or adding any event listener requires configuring through admin control panel.


This core solves the issues stated above.

Contents
--------
#### 1. [Autoloader CMF_Core_Autoloader.](autoloader.md)
#### 2. [CMF_Core_Listener class. Extended event handling. Dynamic extending any XenForo class.](listeners.md)
