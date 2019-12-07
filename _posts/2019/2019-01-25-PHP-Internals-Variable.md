---
layout: post
title: "PHP内核详解-变量及其实现"
categories: [编程语言]
tags: [PHP]
author_name: R_Lanffy
---
---

# PHP内核详解-变量

本文章是PHP内核详解系列的第二篇：变量。

介绍PHP源码中变量的各种类型及其实现。

## 前提

PHP源码版本：7.1.6

## 数据的存储-变量

PHP中的变量，在源码中都定义在一个文件中：``Zend/zend_types.h``。通过阅读该文件，可以知道，PHP7中的变量类型有20种，这里只介绍我们常规理解并应用的几种变量。

PHP中定义变量的结构体定义为：

```c
typedef struct _zval_struct     zval;

// _zval_struct 结构体内容
struct _zval_struct {
	zend_value        value;			/* value 8 bite */
	union {
		struct {
			ZEND_ENDIAN_LOHI_4(
				zend_uchar    type,         /* active type 记录变量类型*/
				zend_uchar    type_flags,   // 对应的变量类型的特有标记，不同类型的变量对应的flag也不同
				zend_uchar    const_flags,  // 常量类型标记
				zend_uchar    reserved)     /* call info for EX(This) 保留字段*/
		} v;                                // 4 bite
		uint32_t type_info;                 // 4 bite
	} u1;                                   // 内存对齐后，最终该结构占用4 bite,
	union {
		uint32_t     next;                 /* hash collision chain */
		uint32_t     cache_slot;           /* literal cache slot */
		uint32_t     lineno;               /* line number (for ast nodes) */
		uint32_t     num_args;             /* arguments number for EX(This) */
		uint32_t     fe_pos;               /* foreach position */
		uint32_t     fe_iter_idx;          /* foreach iterator index 针对对象的遍历*/
		uint32_t     access_flags;         /* class constant access flags. such as:public protected private*/
		uint32_t     property_guard;       /* single property guard. 防止类中魔术方法的循环调用，如：__get __set */
		uint32_t     extra;                /* not further specified */
	} u2;                                  // 内存对齐后，最终该结构占用4 bite,
}; 
```

其中，u1和u2是两个很重要的联合体类型字段。

其中的value字段，顾名思义，是用来储存值的，它是一个联合体，其定义为：

```c
typedef union _zend_value {
	zend_long         lval;	 /* long value 8个字节 整型 */
	double            dval;	 /* double value 8 bite 浮点型 */
	zend_refcounted  *counted; // 引用计数，后面所有的指针类型都是 8 bite
	zend_string      *str; // 字符串类型指针，指向字符串类型结构体
	zend_array       *arr; // 数组类型指针，指向数组类型结构体
	zend_object      *obj; // 对象类型指针，指向对象类型结构体
	zend_resource    *res; // 资源类型指针，指向资源类型结构体
	zend_reference   *ref; // 引用类型（源码内部使用）
	zend_ast_ref     *ast; // 常量类型
	zval             *zv;  // _zval_struct 类型
	void             *ptr; // 指针
	zend_class_entry *ce;  // 类
	zend_function    *func;// 函数
	struct {
		uint32_t w1;         // 4 bite
		uint32_t w2;         // 4 bite
	} ww;                   // 8 biye
} zend_value;              // 内存对齐后，最终该结构占用8 bite,存放变量真实的值，
```

PHP7的一大改动就是_zval_struct._zend_value的结构，通过指针，指向复杂的数据类型。

下面我们看一看该结构体是如何存储不同类型变量的。

### 整形和浮点型

因为其占用空间小，其值是直接存储在zval结构当中的，如：

```php
$number = 1; // $number = zval(u1.v.type=IS_LONG, value.lval=1)
```

### 字符串类型

字符串相比整型和浮点型来说较复杂，对比整型，它多了长度，引用等内容。所以在源码中专门为其定义了一个结构体如下：

```c
struct _zend_string {
	zend_refcounted_h gc; //垃圾回收信息 8bite
	zend_ulong        h;  // 在这里保存字符串hash信息，避免重复计算，提高效率 8bite
	size_t            len;// 字符串长度
	char              val[1];// 字符串的值
};
```

字符串定义，zval通过如下的方式指向字符串结构体：

```php
$str = "1"; // $str = zval(u1.v.type=IS_STRING, value.*str=zend_string)
```

### 数组类型

数组类型在源码当中通过HashTable来实现，其中数据的存储结构为key-value形式。既然是Hash表来实现的，那么源码当中是通过什么样的方式来避免Hash冲突的呢？这就需要我们来看一看数组的结构定义了：

```c
typedef struct _Bucket {
	zval              val;
	zend_ulong        h;                /* hash value (or numeric index)   */
	zend_string      *key;              /* string key or NULL for numerics */
} Bucket;

typedef struct _zend_array HashTable;

struct _zend_array {
	zend_refcounted_h gc;  //垃圾回收信息 8bite
	union {
		struct {
			ZEND_ENDIAN_LOHI_4(
				zend_uchar    flags,
				zend_uchar    nApplyCount,
				zend_uchar    nIteratorsCount,
				zend_uchar    consistency)
		} v;
		uint32_t flags;
	} u;
	uint32_t          nTableMask;
	Bucket           *arData;
	uint32_t          nNumUsed;
	uint32_t          nNumOfElements;
	uint32_t          nTableSize;
	uint32_t          nInternalPointer;
	zend_long         nNextFreeElement;
	dtor_func_t       pDestructor;
};
```

其中，具体的key-value存放在Bucket中。


### 引用类型

引用类型通常在引用传递过程中使用，其好处是多个引用类型指向的值在内存当中始终只有一份。其中一个引用变量对其更改，其他的引用变量也会接收改变后的值。在恰当的应用场景中会带来很大的便利。可以认为，面向对象编程也是其应用之一。

引用数据结构的定义如下：

```c
struct _zend_reference { // 间接zval类型
	zend_refcounted_h gc;
	zval              val;
};
```

虽然我们经常在各种场景下接触和使用应用类型，能认知和感受到他的存在，但在实际应用中它却是隐式的，即在PHP源码中它是一个间接的zval类型。当我们对普通常量使用“&”时，源码内部会创建一种新的中间变量结构体，即_zend_reference。

其简单应用如下：

```php
$str1 = "hello"; // str1指向"hello"字符串，其引用计数为1
$str2 = $str1;   // str2 和 str1 同时指向"hello"字符串，其引用计数变为2
$str3 = &$str2; // str1指向"hello"字符串，其引用计数为2, $b和$c指向新生成的中间变量_zend_reference，其引用计数为2，该变量指向"hello"字符串，其引用计数变为2
```


### 对象类型

相比于PHP5，对象类型的结构定义简化了很多，其结构如下:

```c
struct _zend_object { // 对象类型
	zend_refcounted_h gc; // 引用计数
	uint32_t          handle; 
	zend_class_entry *ce;
	const zend_object_handlers *handlers;
	HashTable        *properties; // 对象的属性列表，key：属性名，value：属性的值在properties_table中的偏移量
	zval              properties_table[1]; // 存储对象的属性值列表
};
```

## 变量的结构及其关系

![变量的结构及其关系](/images/posts/2019/10/PHP变量结构及其关系.png)

## 变量的作用域

* 全局变量：全局变量在PHP生命周期中任何地方都是可用的，它存储在全局符号表（symbol_table）中，该符号表通过HashTable实现，PHP中通过global修饰。
* 局部变量：可以理解为在函数或者类中声明的变量，只能在其声明的范围内部访问。
* 中间变量：在平常编写PHP代码时是无感知的，略。
* 静态变量：静态变量在声明的范围代码退出执行后，也不会被销毁，其值可以修改，在整个生命周期中存在，PHP中通过static修饰。
* 常量：分为全局常量和局部常量，作用域同全局变量和局部变量，其值不能修改。通过define或者const修饰。