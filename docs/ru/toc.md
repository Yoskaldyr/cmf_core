CMF_Core - Расширенное ядро
===========================

Основные проблемы при разработке дополнений под XenForo
-------------------------------------------------------
При разработке и поддержке дополнений для XenForo основные проблемы с которыми сталкивается разработчик:

 1. Невозможность расширения базовых классов XenForo, особенно статических хелперов.
 2. Невозможность расширения одним динамическим классом нескольких классов XenForo из-за невозможности повторного декларирования 1 класса
 3. Трудность пробрасывания входных данных из контроллера в датарайтер при расширении функционала основных типов данных (узлы, сообщения, темы)
 4. Неудобство в разработке когда надо изменить какой-либо обработчик в админке, вместо простой правки кода, следовательно любое добавление обработчика требует обязательного обновления хака через админку



Все эти проблемы и неудобства позволяет решить ядро

Содержание
----------
#### 1. [Автозагрузчик CMF_Core_Autoloader.](autoloader.md)
#### 2. [Класс CMF_Core_Listener. Расширенная работа с событиями. Расширение любых классов XenForo.](listeners.md)
#### 3. [Настройка X-Accel-Redirect для Nginx.](nginx.md)
