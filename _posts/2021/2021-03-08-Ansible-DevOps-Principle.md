---
layout: post
title: "Ansible部署原理"
categories: [开发工具]
tags: [ansible]
author_name: R_Lanffy
published: true
---
---

## 原理简介

一句话介绍，[Ansible](https://www.ansible.com/)是一款支持企业级的、简单的自动化运维、部署工具，通过ssh协议来对服务器进行统一的管理、自动化命令执行以及服务快速部署。

## Ansible的内部构成

Ansible由一下几个重要部分组成

* ansible：执行命令
* ansible.cfg：ansible本身的配置文件，位于：/etc/ansible/ansible.cfg
* Inventory：Ansible可同时管理操作一台或多台主机，主机可分组管理，其中组和主机之间的关系就通过Inventory文件配置，默认的配置文件是：/etc/ansible/hosts
* Ad-Hoc：如果我们执行一些命令去比较快的完成一些事情,而不需要将这些执行的命令特别保存下来, 这样的命令就叫做 ad-hoc 命令.
* Playbooks：Playbooks 是 Ansible的配置,部署,编排语言.他们可以被描述为一个需要希望远程主机执行命令的方案,或者一组IT程序运行的命令集合.简单来说就是远程主机执行的命令的集合，遵循YAML语法。
* Modules：ansible自带了很多可以直接在远程主机执行的模块，可在playbooks中直接调用，也可以写出属于自己的模块.这些模块可以控制系统的资源 ,像服务,包管理,文件,或执行系统命令.

另外还有Plugins和API，但如果只是日常运维和部署服务用的话，熟悉上面几个模块就可以了。

## 快速上手

### 安装

参考：[http://www.ansible.com.cn/docs/intro_installation.html](http://www.ansible.com.cn/docs/intro_installation.html)

### Ad-Hoc体验

使用ad-hoc可以执行一些简单的命令

安装完成后，可以打开/etc/ansible/hosts文件看看，里面有默认的配置介绍以及如何用组管理主机。在里面添加一行配置：

>9.134.xxx.xxx ansible_ssh_port=36000 ansible_ssh_user=root ansible_ssh_pass="xxxxxxxxx"


然后执行命令：“ansible 9.134.xxx.xxx -m ping”，执行成功的结果如下：
![](/images/posts/2021/3/20210308001.png)

更多命令可以通过 ansible -h 或者 man ansible 查看。

### Playbooks体验

Playbooks由play组成，每个play可以定义一系列task。在task中就可以引用module了。

可以为 playbook 中的每一个 play,个别地选择操作的目标机器是哪些,以哪个用户身份去完成要执行的步骤（called tasks）.

创建playbook.yml文件，内容如下：

```
- hosts: 127.xxx.xxx.xxx
  vars:
      http_port: 80      # 定义变量
  remote_user: root
  tasks:
      - name: "just ping {{ http_port }}, http_port is a var test."  # 第一个task，执行ping
         ping:
         remote_user: root
 
      - name: exec commond  # 第二个task，执行commond命令
         shell: /usr/bin/uptime
```

然后执行命令：``ansible-playbook ./playbook.yml``

输出如下，表示执行成功：

![](/images/posts/2021/3/20210308002.png)


查看更多Ansible的应用示例：[https://github.com/ansible/ansible-examples](https://github.com/ansible/ansible-examples)

