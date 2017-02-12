---
layout: post
title: "Laravel Homestead的安装和使用"
categories: [开发环境]
tags: [Laravel]
author_name: R_Lanffy
---
---

# 一. 介绍

Laravel致力于完善整个PHP开发过程,使得本地开发环境的搭建和分发更加简单.Vagrant提供了一种简单并且优雅的方式来管理和配置虚拟机.

Laravel Homestead 是一个官方的Vagrant "box" 安装包,它提供了一个完美的开发环境,它不需要在你的本地机器安装PHP, HHVM, web服务器, 和其他任何服务软件.有了它,你再也不用担心搞乱自己的操作系统了! Vagrant boxes完全是一次性了,如果环境出了问题,你可以在几分钟之内重新构建开发环境.

Homestead可以运行在Windows,Mac和Linux系统上,它需要 Vagrant1.7.\* 及以上的版本.

Homestead包含了以下的软件:


* Ubuntu 14.04
* Git
* PHP 5.6 / 7.0
* Xdebug
* HHVM
* Nginx
* MySQL
* Sqlite3
* Postgres
* Composer
* Node (With PM2, Bower, Grunt, and Gulp)
* Redis
* Memcached (PHP 5.x Only)
* Beanstalkd
* [Laravel Envoy](http://www.golaravel.com/laravel/docs/5.1/envoy/)
* [Blackfire Profiler](http://www.golaravel.com/laravel/docs/5.1/homestead/#blackfire-profiler)

# 二. 安装

## 安装VirtualBox 和 Vagrant

可以参考: [VirtualBox 和 Vagrant的安装](http://lanffy.github.io/2015/09/28/%E4%BD%BF%E7%94%A8virtualbox_+_vagrant%E6%89%93%E9%80%A0%E5%B1%9E%E4%BA%8E%E8%87%AA%E5%B7%B1%E7%9A%84%E5%BC%80%E5%8F%91%E7%8E%AF%E5%A2%831/)

## 安装Homestead Vagrant Box

原本安装Homestead Vagrant Box只需要使用简单的几行命令就可以完成,但是因为"长城"的存在,使得安装会很艰难. 下面我提供我的解决方法:

1. 如果你的Vagrant版本小于1.7.\*,运行命令:

    ``vagrant box add laravel/homestead https://atlas.hashicorp.com/laravel/boxes/homestead``
    
    Vagrant版本在1.7及以上的可以运行命令:``vagrant box add laravel/homestead``

2. 执行完第一步你就会进入Homestead Vagrant Box的下载阶段,但是这个Box很大,有一个多G吧.就像上面说的,在国内没有翻墙的情况下,根本不可能通过这个方式正常的把Box下载下来.这个时候请按下按键:``Ctrl+C``.这时候你会看到一个类似[https://atlas.hashicorp.com/laravel/boxes/homestead/versions/0.4.3/providers/virtualbox.box](https://atlas.hashicorp.com/laravel/boxes/homestead/versions/0.4.3/providers/virtualbox.box)的下载链接,推荐使用迅雷,将其下载下来;

3. 添加Box到Vagrant Box:

    ``vagrant box add --name laravel/homestead path/to/boxname.box``
    
    添加成功提示:
    
    ``==> box: Successfully added box 'laravel/homestead' (v0) for 'virtualbox'!``

4. 检查box列表: ``vagrant box list``后应该可以看到有一个box是``laravel/homestead``,说明box add 成功了.

## 部署Homestead环境

1. 获取Homestead环境:``git clone https://github.com/laravel/homestead.git Homestead``
2. 在Homestead目录下运行``bash init.sh``来创建Homestead的配置文件``Homestead.yaml``,默认这个文件会被创建在``~/.homestead``目录下.
3. 准备你的SSH KEY,如果你没有\~/.ssh 目录,执行:``ssh-keygen -t rsa -C "you@email.com"``
4. ``~/.homestead/Homestead.yaml``配置文件里的``folders``属性列出了所有你想共享给虚拟机的文件夹,``map``和``to``分别对应的文件夹是同步的,你可以根据需哟配置更多的共享文件夹,``map``对应的是本机,``to``对应的是虚拟机里的文件夹,如果要支持 [NFS](https://www.vagrantup.com/docs/synced-folders/nfs.html),可以加一行``type:"nfs"``在``to``那一行的后面:

        folders:
        - map: ~/Code
          to: /home/vagrant/Code


5. 设置Nginx配置:

    ``~/.homestead/Homestead.yaml``文件里应该已经有一个配置了,可以做为样例来参考.

        sites:
        - map: homestead.app
          alias: square.app
          to: /home/vagrant/Code/Laravel/public

    意思是,如果有访问``homestead.app``这个域名的请求,虚拟机会直接将请求接引到``/home/vagrant/Code/Laravel/public``目录,而这个目录实际上和本地环境有映射,被映射到了``~/Code/Laravel/public``. 默认是``php-fpm``来解析PHP,如果你喜欢,也可以选择用[HHVM](http://hhvm.com/)来解析,例如:

        sites:
        - map: homestead.app
          to: /home/vagrant/Code/Laravel/public
          hhvm: true

    默认情况下,本地HTTP的8000端口被映射到了虚拟机的80端口,HTTPS的44300端口被映射到了虚拟机的443端口.
    
5. 配置Hosts

    为了方便本地开发和测试,我们一般会修改我们的hosts文件,让相应的域名不出去,直接在本机上被解析指向指定的IP,如果这个被制定的IP是虚拟机的IP地址,那就实现了,浏览器访问``homestead.app``这个网站,实际上访问的是虚拟机上的该网站,这个结果正是我们需要的. 这么修改:
    
    获取虚拟机的ip:``~/.homestead/Homestead.yaml``,查看ip选项并修改:
    
    * Linxu & Mac OS:sudo vim /etc/hosts
    * windows: notepad C:\Windows\System32\drivers\etc\hosts
    * 改为类似格式:192.168.10.10 homestead.app
    * 确定一下你的配置文件``~/.homestead/Homestead.yaml``里的IP是否是hosts里的IP.如果以上都没有问题,虚拟机用vagrant启动起来后,此时应该可以访问这个你刚添加的网站了:[http://homestead.app](http://homestead.app)

经过以上步骤,就基本上完成了配置文件的修改了.

# 三. 启动

进入Homestead目录下,执行命令:``vagrant up``,就可以正常启动了.

``注``:在启动的过程中,Vagrant可能不会发现 ``laravel/homestead`` 这个Box虚拟机,这是因为版本的问题,解决办法如下:

1. 进入Homestead目录下,``vim scripts/homestead.rb``
2. 修改:``config.vm.box_version = settings["version"] ||= ">= 0.4.0"`` 为 ``config.vm.box_version = settings["version"] ||= ">= 0"``
3. 再次执行``vagrant up``就能发现``laravel/homestead`` 这个Box了

访问:[http://homestead.app/](http://homestead.app/),如果能看到``No input file specified.``就说明说有配置都OK了.

进入虚拟机:``ssh vagrant@127.0.0.1 -p 2222``

链接数据库:``mysql -h127.0.0.1 -P33060 -uhomestead -p``,提示数据密码:``secret``即可

# 四. 项目引入Homestead

``composer require laravel/homestead --dev``

# 五. 配置打包:

所有环境配置好后,可以运行命令生成一些配置文件,将这些配置文件分发给其他的开发者,那么你们的开发环境就是一模一样的了.

如果你的``Homestead``已经安装和部署了,可以在项目目录下,用``make``命令来生成:``Vagrantfile``和``Homestead.yaml``,生成文件一般在你项目根目录.``make``命令会自动将``sites``和``folders``配置指令导入``Homestead.yaml``:

* Mac or Linux:

        php vendor/bin/homestead make

* Windows:

        vendor\bin\homestead make
