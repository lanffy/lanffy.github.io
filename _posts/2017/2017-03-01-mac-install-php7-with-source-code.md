---
layout: post
title: "记MAC OS X 编译安装PHP7.0.16 中遇到的异常情况"
categories: [编程语言]
tags: [PHP]
author_name: R_Lanffy
---
---

## MAC OS X 编译安装PHP7.0.16

主要内容包括:php7以上版本的编译,安装,php-fpm配置。

### 下载源代码

下载地址：[http://www.php.net/downloads.php](http://www.php.net/downloads.php)

### 安装

#### 编译源码

1. 从压缩包解压，得到源码。复制到：``/usr/local/src/`` 目录
2. 执行 ``cd /usr/local/src/php-7.x.x``
3. 执行 ``./buildconf --force``
4. 执行

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


5. 执行 ``make`` 进行编译,在编译过程中,遇到了这个报错:

        Undefined symbols for architecture x86_64:
          "_PKCS5_PBKDF2_HMAC", referenced from:
              _zif_openssl_pbkdf2 in openssl.o
          "_SSL_CTX_set_alpn_protos", referenced from:
              _php_openssl_setup_crypto in xp_ssl.o
          "_SSL_CTX_set_alpn_select_cb", referenced from:
              _php_openssl_setup_crypto in xp_ssl.o
          "_SSL_get0_alpn_selected", referenced from:
              _php_openssl_sockop_set_option in xp_ssl.o
          "_SSL_select_next_proto", referenced from:
              _server_alpn_callback in xp_ssl.o
          "_TLSv1_1_client_method", referenced from:
              _php_openssl_setup_crypto in xp_ssl.o
          "_TLSv1_1_server_method", referenced from:
              _php_openssl_setup_crypto in xp_ssl.o
          "_TLSv1_2_client_method", referenced from:
              _php_openssl_setup_crypto in xp_ssl.o
          "_TLSv1_2_server_method", referenced from:
              _php_openssl_setup_crypto in xp_ssl.o
        ld: symbol(s) not found for architecture x86_64
        clang: error: linker command failed with exit code 1 (use -v to see invocation)
        make: *** [sapi/cli/php] Error 1

    **解决办法**

    MakeFile 里面找到有 **EXTRA_LIBS** 的一行,删除值中所有的 -lssl 和 -lcrypto,然后在该行的末尾添加 libssl.dylib 和 libcrypto.dylib 的路径: ``/usr/local/opt/openssl/lib/libssl.dylib`` 、 ``/usr/local/opt/openssl/lib/libcrypto.dylib``

    更改后的内容为:

        EXTRA_LIBS = -lz -lresolv -lmcrypt -lstdc++ -liconv -liconv -lpng -lz -ljpeg -lcurl -lbz2 -lm -lxml2 -lz -licucore -lm -lz -lcurl -lxml2 -lz -licucore -l     m -lfreetype -licui18n -licuuc -licudata -licuio -lxml2 -lz -licucore -lm -lxml2 -lz -licucore -lm -lxml2 -lz -licucore -lm -lxml2 -lz -licucore -lm -lz      /usr/local/opt/openssl/lib/libssl.dylib /usr/local/opt/openssl/lib/libcrypto.dylib

    继续执行make命令

6. make命令执行完成没有报错后,执行 ``make install``

到这里就编译完成并安装了php7了



#### 配置


1. 配置FPM和ini
	* ``sudo mkdir -pv /usr/local/php/php7/etc/conf.d``
	* ``sudo cp -v ./php.ini-production /usr/local/php/php7/lib/php.ini``
	* ``sudo cp -v ./sapi/fpm/www.conf /usr/local/php/php7/etc/php-fpm.d/www.conf``
	* ``sudo cp -v ./sapi/fpm/php-fpm.conf /usr/local/php/php7/etc``
	* ``sudo ln -s /usr/local/php/php7/sbin/php-fpm /usr/sbin/php-fpm`` 如果已经存在,要先删除原来的php-fpm
	* ``sudo vim /usr/local/php/php7/etc/php-fpm.conf`` 去掉 ``pid = run/php-fpm.pid``前面的注释

2. 配置OPcache
``sudo vim /usr/local/php/php7/etc/conf.d/modules.ini``

	输入以下内容后保存:
	```
	# Zend OPcache
	zend_extension=opcache.so
	```

3. 配置php命令

    执行``where php`` 查看当前环境中的php在哪些路径,替换掉路径中的``phar`` 、``phar.phar`` 、 ``php`` 、 ``php-cgi`` 、 ``php-config`` 、``phpize``。通过以下步骤来完成

    * 先删掉路径下的``phar`` 、``phar.phar`` 、 ``php`` 、 ``php-cgi`` 、 ``php-config`` 、``phpize``,如果不放心,也可以先mv替换名字
    * 执行以下命令

            sudo ln -s /usr/local/php/php7/bin/phar /usr/local/bin/phar
            sudo ln -s /usr/local/php/php7/bin/phar.phar /usr/local/bin/phar.phar
            sudo ln -s /usr/local/php/php7/bin/php /usr/local/bin/php
            sudo ln -s /usr/local/php/php7/bin/php-cgi /usr/local/bin/php-cgi
            sudo ln -s /usr/local/php/php7/bin/php-config /usr/local/bin/php-config
            sudo ln -s /usr/local/php/php7/bin/phpize /usr/local/bin/phpize

4. 完成上诉步骤后,执行: ``php -v``,校验版本



#### 启动、停止、重启php-pfm


1. 启动: ``php-fpm --fpm-config /usr/local/php/php7/etc/php-fpm.conf``
2. 停止: ``kill -INT `cat /usr/local/php/php7/var/run/php-fpm.pid``
3. 重启: ``kill -USR2 `cat /usr/local/php/php7/var/run/php-fpm.pid``

如果觉得每次执行这么长的命令很麻烦，可以将其写入脚本或者在.zshrc文件中定义为alias。例如：

```bash
alias phpfpm-start="php-fpm --fpm-config /usr/local/php/php7/etc/php-fpm.conf"
alias phpfpm-stop=/usr/local/etc/php/fpm-stop.sh
alias phpfpm-restart=/usr/local/etc/php/fpm-restart.sh
```

``cat /usr/local/etc/php/fpm-stop.sh``

```bash
#!/bin/sh

echo "Stopping php-fpm..."

kill -INT `cat /usr/local/php/php7/var/run/php-fpm.pid`

echo "php-fpm stoped"
exit 0
```

``cat /usr/local/etc/php/fpm-restart.sh``

```bash
#!/bin/sh

echo "ReStarting php-fpm..."

kill -USR2 `cat /usr/local/php/php7/var/run/php-fpm.pid`

echo "php-fpm restarted"
exit 0
```



