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
4. ``#line 838 "Zend/zend_language_scanner.c"``

    \#line预处理用于改变当前的行号（__LINE__）和文件名（__FILE__）。 如上所示代码，将当前的行号改变为838，文件名Zend/zend_language_scanner.c 它的作用体现在编译器的编写中，我们知道编译器对C 源码编译过程中会产生一些中间文件，通过这条指令， 可以保证文件名是固定的，不会被这些中间文件代替，有利于进行调试分析。

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

PHP的FPM实现了FastCGI协议。一个完整的FPM响应一个请求的时序如下图所示：

![TCP上客户-服务器事务的时序](/images/posts/2019/15505557841498.jpg)图片来自：[深入理解PHP内核](http://www.php-internals.com/book/?p=chapt02/02-02-03-fastcgi)

PHP源码中，FASTCGI的实现：``main/fastcgi.c``

### PHP程序的执行

![PHP程序的执行过程](/images/posts/2019/15506482368568.jpg)

* 词法解析Re2c：[http://re2c.org/](http://re2c.org/)
* 词法分析Lemon:[http://www.sqlite.org/src/doc/trunk/doc/lemon.html](http://www.sqlite.org/src/doc/trunk/doc/lemon.html)
* Yacc 与Lex 快速入门：[http://www.ibm.com/developerworks/cn/linux/sdk/lex/index.html](http://www.ibm.com/developerworks/cn/linux/sdk/lex/index.htmlt) 这篇文章很有意思，初步介绍了语言的语义解析和语法解析

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

