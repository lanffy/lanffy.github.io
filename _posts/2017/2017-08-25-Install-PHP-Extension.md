---
layout: post
title: "安装PHP扩展"
categories: [编程语言]
tags: [PHP]
author_name: R_Lanffy
---
---
在[记MAC OS X 编译安装PHP7.0.16 中遇到的异常情况](http://lanffy.github.io/2017/03/01/mac-install-php7-with-source-code)一文中，介绍了编译PHP7源码安装PHP的方式，其中的第四个步骤，命令如下：

        ./configure --prefix=/usr/local/php/php7 \
            --with-config-file-path=/usr/local/php/php7/etc \
            --with-config-file-scan-dir=/usr/local/php/php7/etc/conf.d \
            --enable-bcmath \
            --with-bz2 \
            --with-curl \
            --enable-filter \
            --enable-fpm \
            --with-gd \
            --enable-gd-native-ttf \
            --with-freetype-dir \
            --with-jpeg-dir \
            --with-png-dir \
            --enable-intl \
            --enable-mbstring \
            --with-mcrypt \
            --enable-mysqlnd \
            --with-mysql-sock=/tmp/mysql.sock \
            --with-mysqli=mysqlnd \
            --with-pdo-mysql=mysqlnd \
            --with-pdo-sqlite \
            --disable-phpdbg \
            --disable-phpdbg-webhelper \
            --enable-opcache \
            --with-openssl=/usr/local/Cellar/openssl/1.0.2l \
            --enable-simplexml \
            --with-sqlite3 \
            --enable-xmlreader \
            --enable-xmlwriter \
            --enable-zip \
            --enable-sockets \
            --with-xmlrpc

命令后面的一些配置参数主要是用来打开或者关闭PHP的某些特性，如果这里有一些特性忘记打开，或者当时编译安装时不需要但后面的工作中需要这个特性，那么就需要单独编译安装这个特性了。

下面以pcntl扩展为例，介绍一下安装的步骤

1. 进入之前安装的PHP的源码，根据上面给出的文章，我的PHP源码路径是：``cd /usr/local/src/php-7.x.x``
2. 进入扩展目录：``cd ext/pcntl``
3. 执行：``phpize``
4. 执行：``./configure --with-php-config=/usr/local/bin/php-config`` 这里的路径通过执行``which php-config``得到
5. 编译安装：``make && make install``
6. 在``/usr/local/php/php7/etc/conf.d/modules.ini``文件中添加：``extension=pcntl.so``
7. 重启FPM：kill -USR2 `cat /usr/local/php/php7/var/run/php-fpm.pid`
8. 检查是否安装成功：``php -m | grep pcntl``

done.

