��������� X-Accel-Redirect ��� Nginx 
====================================

��� ���������� ��������� ���������� eTag ������������ XenForo nginx ������ ���� ��������� ��������.
���������� �������� `add_header Etag` � ������� `internal_data` � `fastcgi_pass_header Etag` � ������� ��������� php ������.

������ ������� ���� ����� ���������� � ����� �����: 

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

������ ������� ���� ����� ���������� � ����� `forum`: 

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
