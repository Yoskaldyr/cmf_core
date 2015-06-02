X-Accel-Redirect Nginx Configuration 
====================================

For correct eTag headers sent by XenForo nginx must be properly configured.
`add_header Etag` must be added to the `internal_data` location and `fastcgi_pass_header Etag` to the location for the php files.

Example config if forum located at the root of site: 

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

Example config if forum located at `forum` directory: 

~~~
    location ^~ /forum/internal_data/ {
		if ($upstream_http_etag != "") {
	        add_header Etag $upstream_http_etag;
		}
		internal;
    }
    location ~ ^/forum/[^/]+\.php$ {
		try_files $fastcgi_script_name =404;
		include fastcgi.conf;
		fastcgi_pass_header Etag;
    }
~~~
