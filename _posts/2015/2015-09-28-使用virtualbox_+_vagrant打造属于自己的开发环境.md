---
layout: post
title: " 使用VirtualBox + Vagrant打造属于自己的开发环境"
tags: [VirtualBox, Vagrant]
author_name: R_Lanffy
---

![vagrant](http://sfault-image.b0.upaiyun.com/c1/eb/c1eb8c927b0b255d6de2532ae2564877)

很多新进入公司的小伙伴估计都有这样的经历，刚进公司必定会配置公司产品运行所需的开发环境。

配置环境对于刚入职场的新手来说，还是很有难度的。

Vagrant就是为了解决这个问题而存在的，通过Vagrant可以打造专有的开发环境。

通过Vagrant配置好开发环境后，就可以打包开发环境进行分发了。

新手只需拿到Vagrant包，运行几个脚本就完成了环境的配置。

而且这一切都是在虚拟机中完成的，所以也不用担心配置失败而带来的从装系统的风险.

我曾经就因为数据库安装失败而重装系统过。我想肯定不止我一个人有这样的经历。

##配置步骤

###安装VirtualBox
虚拟系统运行在VirtualBox中，类似的工具还有VMware，但后者是收费的。

VirtualBox下载地址: [https://www.virtualbox.org/wiki/Downloads](https://www.virtualbox.org/wiki/Downloads).

它支持多个平台，请根据自己的情况选择对应的版本。

###安装Vagrant
Vagrant下载地址：[https://www.vagrantup.com/downloads.html](https://www.vagrantup.com/downloads.html).

选择最新的版本即可。
检查安装是否成功，运行命令：```vagrant```

查看安装版本，运行命令：```vagrant －v```

至此，基本的工具已经安装完成了。

###初始化Vagrant
安装完成后，在想要搭建环境的目录下执行命令```vagrant init```

在当前目录下会生成一个Vagrantfile配置文件，内容如下：


```
    
        # -*- mode: ruby -*-
    # vi: set ft=ruby :
    
    # All Vagrant configuration is done below. The "2" in Vagrant.configure
    # configures the configuration version (we support older styles for
    # backwards compatibility). Please don't change it unless you know what
    # you're doing.
      Vagrant.configure(2) do |config|
    # The most common configuration options are documented and commented below.
    # For a complete reference, please see the online documentation at
    # https://docs.vagrantup.com.
  
    # Every Vagrant development environment requires a box. You can search for
    # boxes at https://atlas.hashicorp.com/search.
    config.vm.box = "base"
  
    # Disable automatic box update checking. If you disable this, then
    # boxes will only be checked for updates when the user runs
    # `vagrant box outdated`. This is not recommended.
    # config.vm.box_check_update = false
  
    # Create a forwarded port mapping which allows access to a specific port
    # within the machine from a port on the host machine. In the example below,
    # accessing "localhost:8080" will access port 80 on the guest machine.
    # config.vm.network "forwarded_port", guest: 80, host: 8080
  
    # Create a private network, which allows host-only access to the machine
    # using a specific IP.
    # config.vm.network "private_network", ip: "192.168.33.10"
  
    # Create a public network, which generally matched to bridged network.
    # Bridged networks make the machine appear as another physical device on
    # your network.
    # config.vm.network "public_network"
  
    # Share an additional folder to the guest VM. The first argument is
    # the path on the host to the actual folder. The second argument is
    # the path on the guest to mount the folder. And the optional third
    # argument is a set of non-required options.
    # config.vm.synced_folder "../data", "/vagrant_data"
  
    # Provider-specific configuration so you can fine-tune various
    # backing providers for Vagrant. These expose provider-specific options.
    # Example for VirtualBox:
    #
    # config.vm.provider "virtualbox" do |vb|
    #   # Display the VirtualBox GUI when booting the machine
    #   vb.gui = true
    #
    #   # Customize the amount of memory on the VM:
    #   vb.memory = "1024"
    # end
    #
    # View the documentation for the provider you are using for more
    # information on available options.
  
    # Define a Vagrant Push strategy for pushing to Atlas. Other push strategies
    # such as FTP and Heroku are also available. See the documentation at
    # https://docs.vagrantup.com/v2/push/atlas.html for more information.
    # config.push.define "atlas" do |push|
    #   push.app = "YOUR_ATLAS_USERNAME/YOUR_APPLICATION_NAME"
    # end
  
    # Enable provisioning with a shell script. Additional provisioners such as
    # Puppet, Chef, Ansible, Salt, and Docker are also available. Please see the
    # documentation for more information about their specific syntax and use.
    # config.vm.provision "shell", inline: <<-SHELL
    #   sudo apt-get update
    #   sudo apt-get install -y apache2
    # SHELL
  end
```

修改配置参数：
**config.vm.box = "base"** 修改为**config.vm.box = "hashicorp/precise32"**

    该参数的含义是虚拟机使用什么样的镜像，默认是base，我们将它修改为Ubuntu precise 32 VirtualBox
    
打开配置：
**# config.vm.synced_folder "../data", "/vagrant_data"** 

将其修改如下：
**config.vm.synced_folder "./projects", "/projects"**

    该参数的含义是：使用当前目录下的projects目录作为项目目录，它与虚拟机的/projects目录相对应，并且内容保持同步。
    
###创建项目目录projects
配置文件中用到了当前目录下的projects目录，所以需要创建该目录，不然启动vagrant时会报错。

```mkdir ./projects```

***注：如果你安装Vagrant时使用了sudo，那么之后的所有vagrant命令前都需要加上sudo!!!***

###启动Vagrant
经过上面的步骤后，就可以启动Vagrant了，但是一般不推荐这样做。


因为之前没有下载好镜像，所以第一次启动时，会下载镜像的，而且镜像是从国外的服务器下载，这样会需要一个漫长等待的过程。

下面给出推荐的方法：

###下载镜像
官方封装好的Ubuntu基础镜像：

Ubuntu precise 32 VirtualBox [http://files.vagrantup.com/precise32.box](http://files.vagrantup.com/precise32.box)

Ubuntu precise 64 VirtualBox [http://files.vagrantup.com/precise64.box](http://files.vagrantup.com/precise64.box)

如果你要其他系统的镜像，可以来这里下载：[http://www.vagrantbox.es/](http://www.vagrantbox.es/)

将下载下来的镜像文件放在与Vagrantfile文件同级目录的file(如果没有需要创建)文件夹中.

目录如下：```file/box/precise32.box```

**添加配置：**

在Vagrantfile文件中的config.vm.box配置之后添加如下配置：

```config.vm.box_url = "./files/boxes/precise32.box"```

配置完成之后，就可以启动Vagrant了。
在当前目录执行命令：```vagrant up```

至此，vagrant虚拟环境就启动起来了。在当面目录执行```vagrant ssh```就能登陆到虚拟系统中。

在虚拟系统的```/projects```中路中执行```touch testfile```。
创建一个测试文件，然后进入到你自己的系统，Vagrantfile所在的目录.
进入到projects目录中，你会发现存在一个testfile文件。

我想你已经明白了vagrant的精髓之处，你可以在自己的系统中编辑代码.
而在vagrant的虚拟系统中去运行代码，也就是说，你不用在自己的系统中安装运行环境！

***Windows 用户注意：Windows 终端并不支持 ssh，所以需要安装第三方 SSH 客户端，比如：Putty、Xshell 等。***

###常用命令
```
vagrant init  # 初始化

vagrant up  # 启动虚拟机

vagrant halt  # 关闭虚拟机

vagrant reload  # 重启虚拟机

vagrant ssh  # SSH 至虚拟机

vagrant status  # 查看虚拟机运行状态

vagrant destroy  # 销毁当前虚拟机
```

更多内容请查阅官方文档 [http://docs.vagrantup.com/v2/cli/index.html](http://docs.vagrantup.com/v2/cli/index.html)


至此，一个简单的，垮平台的vagrant开发环境就配置好了。但虚拟机中却什么都没有安装，比如php，mysql，apache等。

在后面的文章中，我会准备一些shell脚本，使得在第一次启动虚拟机时，安装好常用的或者是你所需要的所有软件。
当然，你也可以参考官方文档自己动手。

