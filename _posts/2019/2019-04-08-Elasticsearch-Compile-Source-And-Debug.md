---
layout: post
title: "ElasticSearch源码解读一：源码编译和Debug环境搭建"
categories: [搜索引擎]
tags: [ElasticSearch]
author_name: R_Lanffy
---
---

## 环境准备
说明：本文章使用的ES版本是：``6.7.0``
### JDK

Elastisearch 6.7.0编译需要JDK版本10.0及以上，我直接安装了JDK12.JDK下载地址：[https://www.oracle.com/technetwork/java/javase/downloads/index.html](https://www.oracle.com/technetwork/java/javase/downloads/index.html)

### [Gradle](https://gradle.org/)

``brew install gradle``

### Elastisearch源码

```
git clone https://github.com/elastic/elasticsearch.git
git tag
git checkout v6.7.0
```

## 使用IDEA DEBUG 源码

### 将工程Import到IDEA

进入Elastisearch根目录，把源码编译为IDEA工程：``./gradlew idea``

![-w553](/images/posts/2019/15547293535112.jpg)

选择Elasticsearch目录进入：

![-w562](/images/posts/2019/15547293865629.jpg)

选择Gradle导入后，下一步：

![-w769](/images/posts/2019/15547294233274.jpg)


选择如上的选项，点击Finish，导入源码到IDEA完成。

### 本地Debug代码

使用IntelliJ在本地调试ES，有两种方式，一种是直接在IntelliJ上运行ES进行调试，但需要很多繁杂得配置。
配置方法：进入IDEA，``Run -> Edit Configurations``

![-w886](/images/posts/2019/15547294919385.jpg)

其中VM options如下：

![-w927](/images/posts/2019/15547295079079.jpg)

其中，elasticsearch.policy如下：

![-w955](/images/posts/2019/15547295247177.jpg)

最后，运行org.elasticsearch.bootstrap.Elasticsearch::main(java.lang.String[]) 方法就可以调试了。

### 远程调试

另一种是远程调试，先用debug模式，在本地启动ES服务：``./gradlew run --debug-jvm``

![-w959](/images/posts/2019/15547295567821.jpg)

可以看到，debug模式监听的端口是8000

![-w898](/images/posts/2019/15547295714840.jpg)

然后在IDE代码中设置断点，点击debug按钮：

![-w728](/images/posts/2019/15547295885453.jpg)

同时也可以在浏览器中通过访问：[http://127.0.0.1:9200](http://127.0.0.1:9200) 查看ES状态

![-w670](/images/posts/2019/15547296031162.jpg)

[http://127.0.0.1:9200/_cat/health?v](http://127.0.0.1:9200/_cat/health?v)

![-w962](/images/posts/2019/15547296183839.jpg)

下一篇文章将说一下ES的启动过程。


## 系列文章

1. [ElasticSearch源码解读一：源码编译和Debug环境搭建](https://lanffy.github.io/2019/04/08/Elasticsearch-Compile-Source-And-Debug)
2. [ElasticSearch源码解读二：启动过程详解](https://lanffy.github.io/2019/04/09/ElasticSearch-Start-Up-Process)
3. [Elasticsearch源码解读三：创建索引过程详解](https://lanffy.github.io/2019/04/16/How-Elasticsearch-Create-Index)
4. [Elasticsearch源码解读四：搜索过程详解](https://lanffy.github.io/2019/04/30/ElasticSearch-Search-Process)
5. [Elasticsearch源码解读五：搜索相关性排序算法详解](https://lanffy.github.io/2019/05/08/Elasticsearch-Search-Score-Algorithm)
6. [Elasticsearch源码解读六：ES中的倒排索引](https://lanffy.github.io/2019/05/10/Inverted-Index-In-Elasticsearch)
7. [Elasticsearch源码解读七：常见用法手册](https://lanffy.github.io/2019/07/10/Elasticsearch-Common-Usage-Manual)

