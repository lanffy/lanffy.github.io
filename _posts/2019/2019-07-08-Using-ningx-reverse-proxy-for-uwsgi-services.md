---
layout: post
title: "使用Nginx反向代理uwsgi服务-uwsgi invalid request block size"
categories: [服务]
tags: [Nginx,Uwsgi,反向代理]
author_name: R_Lanffy
published: true
---
---

今天在使用Nginx给Uwsgi服务做反向代理的时候，遇到一个问题。配置步骤如下：

## 启动Uwsgi服务

在使用Flask框架的Python项目中，配置文件：``uwsgi.ini``的内容如下：

```
[uwsgi]
master = true
processes = 16
threads = 2
chdir=/xxx
socket = 127.0.0.1:1234
# 日志输出地址
logto = /xxx/uwsgi.log
daemonize = /xxx/uwsgi.log
# reload
py-autoreload = 1
# pid
pidfile = /xxx/uwsgi.pid
```

执行命令启动Uwsgi服务：``uwsgi --ini /path/to/uwsgi.ini``，此时，Uwsgi服务已经启动，并监听1234端口

## 配置Nginx反向代理

第一次配置好的Nginx反向代理如下：

```
server {
    listen 8086;
    location ^~ / {
        proxy_pass 127.0.0.1:1234;
    }
}
```

设置好Nginx反向代理配置后，访问反向代理服务，方向代理请求Uwsgi服务时，Uwsgi服务提示以下日志：

```
...
invalid request block size: 21573 (max 4096)...skip
invalid request block size: 21573 (max 4096)...skip
...
```

同时Nginx的返回码为502

## 问题原因

查了一下，是因为Nginx和Uswgi的通信协议不一致导致的。

* Uswgi的服务协议是：[uwsgi protocol](https://uwsgi-docs.readthedocs.io/en/latest/Protocol.html)
* Nginx使用的通信协议是TCP/IP

## 解决方案

知道了问题原因后就好办了，只需要在Nginx转发请求给Uwsgi服务时，将通信协议转义即可[^Uswgi Nginx support]。Nginx配置如下：

```
server {
    listen 8086;
    location ^~ / {
        uwsgi_pass 127.0.0.1:1234;
        include uwsgi_params;
    }
}
```

另一个解决方案是安装工具（不推荐）：``uwsgi-tools``：

```
$ pip install uwsgi-tools

$ uwsgi_curl 10.0.0.1:3030
```

[^Uswgi Nginx support]: https://uwsgi-docs.readthedocs.io/en/latest/Nginx.html