---
layout: post
title: "PHP内核详解-概括"
categories: [编程语言]
tags: [PHP]
author_name: R_Lanffy
---
---

# PHP内核详解-概括

本文章是PHP内核详解系列的第一篇：概括。

该系列文章旨在介绍PHP内核，为PHP内核学习者做一个大概的介绍。

在这篇文章中，你将对PHP程序执行有一个大概的了解。在后面的系列文章中，将会详细介绍各个环节的细节。

## 前提

阅读源码的前提：

1. 双井号（``##``）：``##`` 被称为 连接符（concatenator），它是一种预处理运算符， 用来把两个语言符号(Token)组合成单个语言符号。
2. 单井号（``#``）： ``#`` 是一种预处理运算符，它的功能是将其后面的宏参数进行 字符串化操作
3. 宏定义中的do-while循环：保证代码会执行且只执行一次
4. ``#line 838 "Zend/zend_language_scanner.c"``

    \#line预处理用于改变当前的行号（__LINE__）和文件名（__FILE__）。 如上所示代码，将当前的行号改变为838，文件名Zend/zend_language_scanner.c 它的作用体现在编译器的编写中，我们知道编译器对C 源码编译过程中会产生一些中间文件，通过这条指令， 可以保证文件名是固定的，不会被这些中间文件代替，有利于进行调试分析。
5. 目录结构介绍
    1. sapi：输入输出抽象
    2. zend：内存管理、垃圾回收、数组实现
    3. main：链接sapi和zend。解析分析sapi的请求，在调用zend之前，完成初始化工作
    4. ext：扩展
    5. tsrm：Thread safe resource manager，线程安全管理

## 编译型语言和解释型语言

* 编译型语言：应用程序在执行之前，将源代码编译成汇编语言，然后根据硬件环境，编译成硬件可执行的目标文件。这个过程在程序的执行中只做一次。常见语言有：C\C++、Go、Java等
* 解释型语言：在程序运行时被编译为机器语言，程序每执行一次，该过程就会执行一次。

PHP就属于解释型语言。

### PHP简要生命周期

PHP代码的简要执行过程，模块流程图：

![-w422](/images/posts/2019/15501319258173.jpg)

一个PHP程序的大致流程图如下，如果没有使用opcache，流程图下面的opcache部分的流程可以忽略

![PHP程序的执行过程](/images/posts/2019/15506482368568.jpg)

其中：

* 词法解析Re2c：[http://re2c.org/](http://re2c.org/)
* 词法分析Lemon:[http://www.sqlite.org/src/doc/trunk/doc/lemon.html](http://www.sqlite.org/src/doc/trunk/doc/lemon.html)
* Yacc 与Lex 快速入门：[http://www.ibm.com/developerworks/cn/linux/sdk/lex/index.html](http://www.ibm.com/developerworks/cn/linux/sdk/lex/index.htmlt) 这篇文章很有意思，初步介绍了语言的语义解析和语法解析

### PHP程序详细的生命周期

单进程SAPI生命周期

![-w422](/images/posts/2019/15501346823825.jpg)

### FASTCGI执行过程

介绍PHP-FPM在web请求过程中的执行流程。

了解FASTCGI之前，先了解一下CGI的工作原理：[CGI 1.1 协议](https://datatracker.ietf.org/doc/rfc3875/)

通过CGI的工作原理，我们可以看到他的一个缺点就是每当一个请求到来时，实现CGI的程序都会Fork一个进程来处理请求。Fork进程会做全局初始化等操作，而大多情况下，所有的初始化操作都是相同的，这样就浪费了资源和时间。

FastCGI从名字上可以看出来，他比CGI更快，他是常驻进程的CGI，请求到达时不会Fork一个进程来处理。FastCGI进程初始化时，会启动多个CGI进程，可以理解为FastCGI是CGI的进程管理器。

> 题外话：关于CGI、FastCGI、FPM的关系：[https://segmentfault.com/q/1010000000256516](https://segmentfault.com/q/1010000000256516)

PHP的FPM实现了FastCGI协议。一个完整的FPM响应一个请求的时序如下图所示：

![TCP上客户-服务器事务的时序](/images/posts/2019/15505557841498.jpg)图片来自：[深入理解PHP内核](http://www.php-internals.com/book/?p=chapt02/02-02-03-fastcgi)

PHP源码中，FASTCGI的实现：``main/fastcgi.c``

后续介绍计划：

## PHP变量及其类型

变量存储结构： ``Zend/zend_types.h：_zval_struct``

```c
struct _zval_struct {
	zend_value        value;			/* value */
	union {
		struct {
			ZEND_ENDIAN_LOHI_3(
				zend_uchar    type,			/* active type */
				zend_uchar    type_flags,
				union {
					uint16_t  call_info;    /* call info for EX(This) */
					uint16_t  extra;        /* not further specified */
				} u)
		} v;
		uint32_t type_info;
	} u1;
	union {
		uint32_t     next;                 /* hash collision chain */
		uint32_t     cache_slot;           /* cache slot (for RECV_INIT) */
		uint32_t     opline_num;           /* opline number (for FAST_CALL) */
		uint32_t     lineno;               /* line number (for ast nodes) */
		uint32_t     num_args;             /* arguments number for EX(This) */
		uint32_t     fe_pos;               /* foreach position */
		uint32_t     fe_iter_idx;          /* foreach iterator index */
		uint32_t     access_flags;         /* class constant access flags */
		uint32_t     property_guard;       /* single property guard */
		uint32_t     constant_flags;       /* constant flags */
		uint32_t     extra;                /* not further specified */
	} u2;
};
```

## PHP内存管理

在说PHP内存管理之前，先了解一下操作系统是如何管理内存的。通常情况下，计算机中通过[内存管理单元(MMU)](http://zh.wikipedia.org/wiki/%E5%86%85%E5%AD%98%E7%AE%A1%E7%90%86%E5%8D%95%E5%85%83)来处理CPU对内存的访问。非系统的应用程序在有需要访问内存的时候，通过库函数malloc向操作系统申请内存。普通的应用发起内存访问，会涉及到CPU在用户态和内核态之间的转换，这个转换的成本很大。所以大多数的应用在启动的时候会向操作系统多申请一部分内存备用，我们常见的Java中的JVM和PHP中的Zend引擎就是这样做的。

在PHP中，在全局配置文件``php.ini``中通过 ``memory_limit=32M`` 来设置启动内存。也可以通过下面的方式在运行时设置启动内存：

```php
<?php
ini_set('memory_limit', '100M');
```

同时，PHP还提供了内存暂用情况的查看函数：

1. [memory_get_usage()](http://www.php.net/manual/en/function.memory-get-usage.php)：获取 目前PHP脚本所用的内存大小
2. [memory_get_peak_usage()](http://www.php.net/manual/en/function.memory-get-peak-usage.php)：当前脚本到目前为止所占用的内存峰值

PHP内存管理器如下：
![PHP内存管理器](/images/posts/2019/PHP_cache.jpeg)

未完待续！