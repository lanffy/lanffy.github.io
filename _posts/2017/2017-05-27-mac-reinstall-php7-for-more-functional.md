---
layout: post
title: "重新编译PHP安装更多扩展功能"
categories: [编程语言]
tags: [PHP]
author_name: R_Lanffy
---
---

## 重新编译PHP安装更多扩展功能

如果已经编译安装过php,但是在使用过程中发现当初编译的时候有一些功能没有开启,而且在php.ini文件中也无法启动。那么唯一的办法就是重新编译安装PHP了。

为了获得新的功能,我们首先关注的是,不能把之前有的功能不小心给关闭了,所以需要找到之前的编译参数。

有两种方式可以知道PHP的编译参数

1. phpinfo()

    从phpinfo的函数中可以查到php编译参数,如下图:
    ![php_config](http://7xjh09.com1.z0.glb.clouddn.com/github_blog_php_configure.png)
    
2. php-config 文件
    
    该文件所在目录和php一起,我的是:/usr/local/php/php7/bin/php-config。里面有个参数是configure_options,这个参数的值就是当初编译PHP时候的参数。

获取参数后,只需要将新添加的配置加到之前的参数后面然后进行编译就可以了。编译步骤可以参考[之前的文章](http://raoliangblog.com/2017/03/01/mac-install-php7-with-source-code)
