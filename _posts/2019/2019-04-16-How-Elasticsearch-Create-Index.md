---
layout: post
title: "Elasticsearch如何创建索引?"
categories: [编程语言]
tags: [Java]
author_name: R_Lanffy
---
---

## 前言

在上一篇文章[搜索引擎ElasticSearch的启动过程](http://lanffy.github.io/2019/04/09/ElasticSearch-Start-Up-Process)中，介绍了ES的启动过程。

由此可知，在ES启动过程中，创建Node对象（new Node(environment)）时，初始化了RestHandler，由其名字可以知道这是用来处理Rest请求的。

在ES源码中，RestHandlerAction如下图：
![](media/15553976546886.jpg)

其中：

* admin
    * cluster：处理集群相关请求
    * indices：处理索引相关请求
* cat：日常查询
* document：文档处理
* ingest：pipeline处理。pipeline？干嘛的
* search：搜索

接下来我们具体的看一下ES是如何创建索引的：``org.elasticsearch.rest.action.document.RestIndexAction``

## 数据概念和结构
一个完整的ES集群由以下几个基本元素组成

名称|概念|对应关系型数据库概念|说明
---|---|---|---
Cluster|集群||一个或多个节点的集合，通过启动时指定名字作为唯一标识，默认elasticsearch
node|节点||启动的ES的单个实例，保存数据并具有索引和搜索的能力，通过名字唯一标识，默认node-n
index|索引|Database|具有相似特点的文档的集合，可以对应为关系型数据库中的数据库，通过名字在集群内唯一标识
type|文档类别|Table|索引内部的逻辑分类，可以对应为Mysql中的表，ES 6.x 版本中，一个索引只允许一个type，不再支持多个type。7.x版本中，type将废弃。
document|文档|Row|构成索引的最小单元，属于一个索引的某个类别，从属关系为： Index -> Type -> Document，通过id 在Type 内唯一标识
field|字段|Column|构成文档的单元
mapping|索引映射（约束）|Schema|用来约束文档字段的类型，可以理解为索引内部结构
shard|分片||将索引分为多个块，每块叫做一个分片。索引定义时需要指定分片数且不能更改，默认一个索引有5个分片，每个分片都是一个功能完整的Index，分片带来规模上（数据水平切分）和性能上（并行执行）的提升，是ES数据存储的最小单位
replicas|分片的备份||每个分片默认一个备份分片，它可以提升节点的可用性，同时能够提升搜索时的并发性能（搜索可以在全部分片上并行执行）

一个ES集群的结构如下：
![](media/15554009231695.jpg)

每个节点默认有5个分片，每个分片有一个备分片。

6.x版本之前的索引的内部结构：
![](media/15554016904328.jpg)

说明：ES 6.x 版本中，相同索引只允许一个type，不再支持多个type。7.x版本中，type将废弃。

所以，6.x版本的索引结构如下：

![](media/15558305452333.jpg)

7.x版本的索引结构如下：
![](media/15558305708268.jpg)

