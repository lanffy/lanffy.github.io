---
layout: post
title: "PHP内核详解"
categories: [编程语言]
tags: [PHP]
author_name: R_Lanffy
---
---

## 前提

阅读源码的前提：

1. 双井号（``##``）：``##`` 被称为 连接符（concatenator），它是一种预处理运算符， 用来把两个语言符号(Token)组合成单个语言符号。
2. 单井号（``#``）： ``#`` 是一种预处理运算符，它的功能是将其后面的宏参数进行 字符串化操作
3. 宏定义中的do-while循环：保证代码会执行且只执行一次

## 生命周期

PHP代码的简要执行过程：

![-w422](/images/posts/2019/15501319258173.jpg)

### PHP 程序执行生命周期

单进程SAPI生命周期

![-w422](/images/posts/2019/15501346823825.jpg)

### FASTCGI

了解FASTCGI之前，先了解一下CGI的工作原理：[CGI 1.1 协议](https://datatracker.ietf.org/doc/rfc3875/)

通过CGI的工作原理，我们可以看到他的一个缺点就是每当一个请求到来时，实现CGI的程序都会Fork一个进程来处理请求。Fork进程会做全局初始化等操作，而大多情况下，所有的初始化操作都是相同的，这样就浪费了资源和时间。

FastCGI从名字上可以看出来，他比CGI更快，他是常驻进程的CGI，请求到达时不会Fork一个进程来处理。FastCGI进程初始化时，会启动多个CGI进程，可以理解为FastCGI是CGI的进程管理器。

> 题外话：关于CGI、FastCGI、FPM的关系：[https://segmentfault.com/q/1010000000256516](https://segmentfault.com/q/1010000000256516)

