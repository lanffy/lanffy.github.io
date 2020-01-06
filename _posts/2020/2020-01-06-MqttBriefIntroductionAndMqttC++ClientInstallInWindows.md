---
layout: post
title: "Mqtt简介以及在Windows中编译安装Mqtt C++客户端"
categories: [编程语言]
tags: [C++,MQTT]
author_name: R_Lanffy
published: true
---
---

## MQTT简介

[MQTT](http://mqtt.org/)(Message Queue Telemetry Transport 消息队列遥测传输)是一种客户端服务端发布订阅消息传输的协议。它具有轻量级、开源、简单以及易于接入的特点。正是因为这些特点，使得其可以应用于各种使用场景，包括远程端对端的交互，物联网等。

MQTT用于收发消息的主要组件有：

* Publisher（发布者）
* Broker（代理）
* Subscriber（订阅者）

三者的关系如下图：
![](/images/posts/2020/1/15782955889503.jpg)

和AQMP一样，MQTT也是基于TCP/IP协议进行通信的。

> 思考点：
>    1. MQTT和AQMP有哪些差异？
>    2. MQTT和HTTP又有哪些差异？

## 在Windows中编译安装MQTT C++客户端

本章主要介绍在Windwos平台下编译安装MQTT的C++库，只介绍编译安装过程，开发教程有时间再另开文章介绍

### 安装工具及依赖

相关依赖：
* MQTT C语言库：[paho.mqtt.c](https://github.com/eclipse/paho.mqtt.c)，C++库依赖这个库
* MQTT C++ 语言库：[paho.mqtt.cpp](https://github.com/eclipse/paho.mqtt.cpp)
* C++ json解析库：[nlohmann/json](https://github.com/eclipse/paho.mqtt.cpp)，用于序列化反序列化消息

需要用到的工具有：
* Cmake：https://cmake.org/， 下载最新版本的CmakeGUI即可；
* Git：https://gitforwindows.org/，安装最新版，需要clone Github的仓库
* Visual Studio Code：https://visualstudio.microsoft.com/zh-hans/downloads/，推荐使用2017
* 文中举例的安装目录：E:\github

### 下载MQTT C和MQTT C++源码

1. 进入到E:\github目录
2. 克隆MQTT C++源码到：E:\github\paho.mqtt.cpp
3. 进入E:\github\paho.mqtt.cpp，克隆MQTT C源码到：E:\github\paho.mqtt.cpp\paho.mqtt.c
4. 下载步骤如图：![](/images/posts/2020/1/15782961628986.jpg)

### 编译安装MQTT C库

打开Cmake GUI，，设置源码目录：”Where is the source code“；设置编译目录：”Where to build the binaries”。点击“Configure”按钮，

设置如下图：
![](/images/posts/2020/1/15782961925866.jpg)

点击Finish后会编译一次，但是这里有一些配置不合适，更改配置如下：
![](/images/posts/2020/1/15782962060017.jpg)

接着点击Configure，然后点击Generate，如下图：
![](/images/posts/2020/1/15782962667468.jpg)

点击Open Project开打项目，生成项目，如下图：
![](/images/posts/2020/1/15782962838554.jpg)

MQTT C库编译完成。

进入MQTT C源码目录，执行命令 cmake --build build/ --target install，安装MQTT C库，如下图：
![](/images/posts/2020/1/15782962978161.jpg)

### 编译安装MQTT C++库

打开Cmake GUI，，设置源码目录：”Where is the source code“；设置编译目录：”Where to build the binaries”。点击“Configure”按钮，

设置如下图：
![](/images/posts/2020/1/15782963183799.jpg)

点击Finish后会编译一次，这里会报错，因为没有配置依赖的C语言库的路径，更改配置如下：
![](/images/posts/2020/1/15782963286875.jpg)

配置完成后，点击Configure和Generate。然后点击Open Project开打项目，生成项目，如下图：
![](/images/posts/2020/1/15782963452490.jpg)

MQTT C++库编译完成。

进入MQTT C++源码目录，执行命令 cmake --build build/ --target install，安装MQTT C++库

至此，MQTT C语言库和MQTT C++库都完成编译并安装。

### 验证

1. 拷贝文件E:\github\paho.mqtt.cpp\build\src\Release\paho-mqttpp3.dll 到目录 E:\github\paho.mqtt.cpp\build\src\samples\Release
2. 下载[MQTT BOX](http://workswithweb.com/mqttbox.html)，mqtt收发消息的桌面客户端
3. 使用MQTT BOX链接Server如下图
4. ![-w1429](/images/posts/2020/1/15782964617286.jpg)
5. 使用MQTT BOX创建发布者如下图：
6. ![-w467](/images/posts/2020/1/15782965126678.jpg)
7. 把async_subscribe项目设为启动项，如下图：
8. ![](/images/posts/2020/1/15782965598934.jpg)
9. 修改async_subscribe.cpp如下图：
10. ![](/images/posts/2020/1/15782965758978.jpg)
11. 启动项目，并通过mqtt box发送消息，如果能收到消息，则安装成功。收到消息的界面如下图：
12. ![](/images/posts/2020/1/15782965973872.jpg)

### 安装源码下载：

项目：[MqttClient](https://github.com/lanffy/MqttClient)

项目是Private的，需要的可以找我。
