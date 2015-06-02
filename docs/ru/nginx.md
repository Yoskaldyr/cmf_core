Настройка X-Accel-Redirect для Nginx 
====================================

Для корректной поддержки заголовков eTag отправляемых XenForo nginx должен быть правильно настроен.
Необходимо добавить `add_header Etag` в локейшн `internal_data` и `fastcgi_pass_header Etag` в локейшн обработки php файлов.

Пример конфига если форум расположен в корне сайта: 

~~~
    location ^~ /internal_data/ {
		if ($upstream_http_etag != "") {
	        add_header Etag $upstream_http_etag;
		}
		internal;
    }
    location ~ [^/]*\.php$ {
		try_files $fastcgi_script_name =404;
		include fastcgi.conf;
		fastcgi_pass_header Etag;
    }
~~~

Пример конфига если форум расположен в папке `forum`: 

~~~
    location ^~ /forum/internal_data/ {
		if ($upstream_http_etag != "") {
	        add_header Etag $upstream_http_etag;
		}
		internal;
    }
    location ~ [^/]*\.php$ {
		try_files $fastcgi_script_name =404;
		include fastcgi.conf;
		fastcgi_pass_header Etag;
    }
~~~
