---
layout: post
title: "Vagrant中Nginx配置"
categories: [开发环境]
tags: [Vagrant]
author_name: R_Lanffy
---
---

![vagrant](http://sfault-image.b0.upaiyun.com/c1/eb/c1eb8c927b0b255d6de2532ae2564877)

在[系列文章1](http://lanffy.github.io/2015/09/28/使用virtualbox_+_vagrant打造属于自己的开发环境1/)和[系列文章2](http://lanffy.github.io/2015/10/04/使用virtualbox_+_vagrant打造属于自己的开发环境2/)文章中,介绍了Vagrant的安装和开发环境软件的自动安装。

这篇文章将写点关于虚拟机中Nginx的配置，以及在真实机中访问Nginx的方法。

打开Vagrantfile文件中，找到如下配置：

```
config.vm.network "forwarded_port", guest: 80, host: 8080
```

该配置的意思就是将虚拟机的80端口映射到真实机的8080端口。

使用`vagrant ssh`命令进入虚拟机

### 备份默认nginx配置文件

```
sudo cp /etc/nginx/nginx.conf /etc/nginx/nginx.conf.back
```

### 修改配置

打开`/etc/nginx/nginx.conf`,将里面的内容更改如下：

    user www-data;
    worker_processes 4;
    pid /var/run/nginx.pid;
    
    events {
	    worker_connections 768;
	    # multi_accept on;
    }
    
    http {

	    ##
    	# Basic Settings
    	##
    
	    sendfile on;
    	tcp_nopush on;
	    tcp_nodelay on;
    	keepalive_timeout 65;
	    types_hash_max_size 2048;
    	# server_tokens off;
    
	    # server_names_hash_bucket_size 64;
    	# server_name_in_redirect off;
    
	    include /etc/nginx/mime.types;
    	default_type application/octet-stream;
    
	    ##
    	# Logging Settings
	    ##
    
	    access_log /var/log/nginx/access.log;
    	error_log /var/log/nginx/error.log;
    
	    ##
    	# Gzip Settings
	    ##
    
	    gzip on;
    	gzip_disable "msie6";
    
	    gzip_vary on;
    	gzip_proxied any;
	    gzip_comp_level 6;
    	gzip_buffers 16 8k;
	    gzip_http_version 1.1;
    	gzip_types text/plain text/css application/json application/x-javascript text/xml application/xml application/xml+rss text/javascript;
    
	    ##
    	# nginx-naxsi config
	    ##
    	# Uncomment it if you installed nginx-naxsi
	    ##
    
	    #include /etc/nginx/naxsi_core.rules;
    
	    ##
    	# nginx-passenger config
	    ##
    	# Uncomment it if you installed nginx-passenger
	    ##
    
	    #passenger_root /usr;
    	#passenger_ruby /usr/bin/ruby;
    
	    ##
    	# Virtual Host Configs
	    ##
    
	    include /etc/nginx/conf.d/*.conf;
    	include /etc/nginx/sites-enabled/*;
	    include /vagrant/files/nginx/conf.d/*.conf;
    
    
	    server {
		    listen 80;
    		server_name lanffy.com www.lanffy.com;
	    	charset utf-8;
    
	    	index index.html index.htm;
		    root /projects/;
	    }
    }

### 添加HTML页面

在虚拟机中：`cd /projects`

在该目录下新建index.html或者index.htm文件，内容如下：

    <html>
        <head>
            <title>R_Lanffy</title>
        </head>
        <body>
            Hello World
        </body>
    </html>

## 重启Vagrant

`vagrant reload`

## 修改hosts
`sudo vim /etc/hosts`
添加：`127.0.0.1	www.lanffy.com`

### 访问测试
在真实机浏览器中输入地址：`lanffy.com:8080`或者`www.lanffy.com:8080`即可访问到虚拟机中的nginx相关配置。

**如果想达到输入test.com就能访问的目的，是需要将Vagrantfile文件中的8080修改为80**

***注：如果出现不能访问的情况，很有可能是在启动虚拟机之前，8080端口被占用了。解决办法就是将端口修改为没有被占用的端口。***

查看端口是否被监听:`netstat -an | grep 8080`

