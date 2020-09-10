---
layout: post
title: "Elasticsearch源码解读七：常见用法手册"
categories: [搜索引擎]
tags: [ElasticSearch]
author_name: R_Lanffy
published: true
---
---

前面几篇文章介绍了搜索引擎ElasticSearch的内部原理，这篇文章总结了在ElasticSearch使用过程中常见的用法。

## 1、查看集群信息

ElasticSearch 查看集群健康信息，常用命令如下：

### 1.1、查看集群状态

ElasticSearch查看集群状态命令：

``curl 'localhost:9200/_cat/health?v'``

![](/images/posts/2019/15627415453158.jpg)

> 其中，status为绿色表示一切正常, 黄色表示所有的数据可用但是部分副本还没有分配,红色表示部分数据因为某些原因不可用.

### 1.2、查看索引分片情况

ElasticSearch查看索引分片情况命令：

``curl 'localhost:9200/_cat/shards?v'``

![](/images/posts/2019/15627417388285.jpg)

> 可以看到例子中有两个索引分别是：people和product，他们各有一个主分片和一个尚未分配的副分片。

### 1.3、查看节点信息

ElasticSearch查看节点信息命令：

* 主节点：``curl 'localhost:9200/_cat/master?v'``
* 所有节点：``curl 'localhost:9200/_cat/nodes?v'``

## 2、索引相关命令

### 2.1、创建索引

ElasticSearch创建索引命令。

7.0版本去掉了type的概念。

#### 2.1.1、7.0之前版本创建索引

```json
curl -X PUT 'localhost:9200/product' -H 'Content-Type: application/json' -d '
{
  "mappings": {
    "type_name": {
      "properties": {
        "price": {
          "type": "integer"
        },
        "name": {
          "type": "text"
        }
      }
    }
  }
}'
```

#### 2.1.2、7.0及其之后的版本创建索引

```json
curl -X PUT 'localhost:9200/product' -H 'Content-Type: application/json' -d '
{
  "mappings": {
      "properties": {
        "price": {
          "type": "integer"
        },
        "name": {
          "type": "text"
        }
      }
    }
}'
```


### 2.2、查询索引

ElasticSearch查询所有的索引命令。

``curl 'localhost:9200/_cat/indices?v'``

![](/images/posts/2019/15627422429126.jpg)


> 可以看到上面的例子中，集群中有两个索引。他们的名字分别为：people、product。且分别有一个主分片和副分片。

### 2.3、查询索引结构

#### 2.3.1、查看所有的索引mapping数据结构

ElasticSearch查询索引mapping结构命令：

``curl 'localhost:9200/_mapping?pretty'``

![](/images/posts/2019/15627428428459.jpg)

> 上面的例子查询了索引people和product的mapping数据结构。

#### 2.3.2、查看指定的索引mapping数据结构

##### 2.3.2.1、7.0之前版本

查看索引名为indexName且type名为typeName的索引mapping数据结构：

``curl -XGET "127.0.0.1:9200/indexName/typeName/_mapping?pretty"``

##### 2.3.2.2、7.0及其之后版本

查看索引名为indexName的索引mapping数据结构：

``curl -XGET "127.0.0.1:9200/indexName/_mapping?pretty"``

### 2.4、给索引添加字段

ElasticSearch在索引中添加字段命令

#### 2.4.1、7.0之前版本

```json
curl -XPOST "127.0.0.1:9200/indexName/typeName/_mapping?pretty" -H 'Content-Type: application/json' -d '{
 "typeName": {
            "properties": {
                 "tags":{
                    "type":"text"
               }
            }
        }
}'
```

> 上面的例子，表示给索引名为indexName且type名为typeName的mapping数据结构添加tags字段

#### 2.4.2、7.0及其之后版本

```json
curl -XPOST "127.0.0.1:9200/product/_mapping?pretty" -H 'Content-Type: application/json' -d '{
    "properties": {
         "tags":{
            "type":"text"
       }
    }
}'
```

> 上面的例子，表示给索引名为product的mapping数据结构添加tags字段

### 2.5、索引中删除字段

ElasticSearch现在不支持

### 2.6、删除索引

ElasticSearch 删除某个索引命令。

``curl -X DELETE 'localhost:9200/product'``

> 上面的例子表示删除名为product的索引。

## 3、数据文档搜索查询相关命令

下面提到的数据即索引中的文档。

### 3.1、写入数据命令

ElasticSearch写入数据命令.

7.0版本去掉了type的概念。

#### 3.1.1、7.0之前版本写入数据

```json
curl -X PUT 'localhost:9200/product/type_name/1' -H 'Content-Type: application/json' -d '
{
    "price":1,
    "name":"富士山苹果"
}'
```

#### 3.1.2、7.0及其之后的版本写入数据

```json
curl -X PUT 'localhost:9200/product/_doc/1' -H 'Content-Type: application/json' -d '
{
    "price":1,
    "name":"富士山苹果"
}'
```

### 3.2、搜索查询数据命令

ElasticSearch搜索数据命令

#### 3.2.1、7.0之前版本搜索数据

##### 3.2.1.1、主键搜索

``curl -X GET 'localhost:9200/product/type_name/1?pretty'``

##### 3.2.1.2、关键字搜索

```json
curl -X GET 'localhost:9200/product/_search?pretty' -H 'Content-Type: application/json' -d '
{
  "query": {
    "match": {
      "name": "苹果"
    }
  },
  "from":0,
  "size":1
}'
```

#### 3.2.2、7.0及其之后的版本搜索数据

##### 3.2.2.1、主键搜索

``curl -X GET 'localhost:9200/product/_doc/1?pretty'``

##### 3.2.2.2、关键字搜索

```json
curl -X GET 'localhost:9200/product/_search?pretty' -H 'Content-Type: application/json' -d '
{
  "query": {
    "match": {
      "name": "苹果"
    }
  },
  "from":0,
  "size":1
}'
```

#### 3.2.3、对多值的字段搜索

查询name包含“苹果”且tags有“苹果”或者“富士山”的文档数据：

```json
curl -X GET 'localhost:9200/product/_search?pretty' -H 'Content-Type: application/json' -d '{
    "query":{
        "bool":{
            "must":[
                {
                    "bool":{
                        "should":[
                            {"match":{"name":"苹果"}},
                            {"terms": {"tags": ["苹果","富士山"]}}
                        ]
                    }
                }
            ]
        }
    },
    "sort":{
        "_score":{"order":"desc"}
    },
    "from":0,
    "size":10
}'
```

#### 3.2.4、多字段联合查询

查询name包含“苹果”且price为1的文档数据：

```json
curl -X GET 'localhost:9200/product/_search?pretty' -H 'Content-Type: application/json' -d '{
    "query":{
        "bool":{
            "must":[
                {
                    "bool":{
                        "should":[
                            {"match":{"name":"苹果"}},
                            {"match":{"price":1}}
                        ]
                    }
                }
            ]
        }
    },
    "sort":{
        "_score":{"order":"desc"}
    },
    "from":0,
    "size":10
}'
```

#### 3.2.5、查询有安全认证的ES
当ES设置了用户名和密码时，通过命令行查询需要带上用户名和密码，命令如下：

```json
curl --user elastic:123456 -X GET http://localhost:9200/product/_doc/1?pretty
{
  "_index" : "noah",
  "_type" : "_doc",
  "_id" : "1",
  "_version" : 1,
  "_seq_no" : 1,
  "_primary_term" : 1,
  "found" : true,
  "_source" : {
    "name" : "王义凯",
    "age" : 28,
    "email" : "wangyikai@csdn.com",
    "company" : "CSDN"
  }
```

其中，--user传的时用户名(elastic)和密码(123456)


### 3.3、更新数据命令

ElasticSearch中更新数据命令：

```json
curl -X PUT 'localhost:9200/product/_doc/1' -H 'Content-Type: application/json' -d '
{
    "price":1,
    "name":"富士山苹果",
    "tags":["富士山","名牌","苹果"]
}'
```

### 3.4、删除数据

ElasticSearch中删除文档数据命令：

#### 3.4.1、7.0之前版本

删除一个文档

``curl -XDELETE 'http://localhost:9200/indexName/typeName/1'``

> 上面的例子表示删除索引名为indexName且type名为typeName的索引中文档ID为1的文档。

#### 3.4.2、7.0及其之后的版本

``curl -XDELETE 'http://localhost:9200/indexName/_doc/1'``

### 4、ElasticSearch中分词器分析器在命令行中的用法

ElasticSearch支持不同的分词插件，在下面的例子中我们使用了[analysis-ik](https://github.com/medcl/elasticsearch-analysis-ik)分词插件。

通过ElasticSearch的API接口，可以分析不同分词器的分词结果[^analyze]。具体的步骤如下：

#### 4.1、添加两个字符类型的字段，并指定不同的分词器：

```json
curl -XPOST "127.0.0.1:9200/product/_mapping?pretty" -H 'Content-Type: application/json' -d '{
    "properties": {
        "pNameIkMaxWord":{
            "type":"text",
            "analyzer":"ik_max_word"
        },
        "pNameIkSmart":{
            "type":"text",
            "analyzer":"ik_smart"
        }
    }
}'
```

#### 4.2、使用ik_max_word分词分析

```json
curl -XPOST 'http://localhost:9200/product/_analyze?pretty' -H 'Content-Type: application/json' -d '
{
    "field": "pNameIkMaxWord",
    "text": "中华人民共和国国歌"
}'
```

分词结果如下：

```json
{
  "tokens" : [
    {
      "token" : "中华人民共和国",
      "start_offset" : 0,
      "end_offset" : 7,
      "type" : "CN_WORD",
      "position" : 0
    },
    {
      "token" : "中华人民",
      "start_offset" : 0,
      "end_offset" : 4,
      "type" : "CN_WORD",
      "position" : 1
    },
    {
      "token" : "中华",
      "start_offset" : 0,
      "end_offset" : 2,
      "type" : "CN_WORD",
      "position" : 2
    },
    {
      "token" : "华人",
      "start_offset" : 1,
      "end_offset" : 3,
      "type" : "CN_WORD",
      "position" : 3
    },
    {
      "token" : "人民共和国",
      "start_offset" : 2,
      "end_offset" : 7,
      "type" : "CN_WORD",
      "position" : 4
    },
    {
      "token" : "人民",
      "start_offset" : 2,
      "end_offset" : 4,
      "type" : "CN_WORD",
      "position" : 5
    },
    {
      "token" : "共和国",
      "start_offset" : 4,
      "end_offset" : 7,
      "type" : "CN_WORD",
      "position" : 6
    },
    {
      "token" : "共和",
      "start_offset" : 4,
      "end_offset" : 6,
      "type" : "CN_WORD",
      "position" : 7
    },
    {
      "token" : "国",
      "start_offset" : 6,
      "end_offset" : 7,
      "type" : "CN_CHAR",
      "position" : 8
    },
    {
      "token" : "国歌",
      "start_offset" : 7,
      "end_offset" : 9,
      "type" : "CN_WORD",
      "position" : 9
    }
  ]
}
```

#### 4.3、使用ik_smart分词分析

```json
curl -XPOST 'http://localhost:9200/product/_analyze?pretty' -H 'Content-Type: application/json' -d '
{
    "field": "pNameIkSmart",
    "text": "中华人民共和国国歌"
}'
```

分词结果如下：

```json
{
  "tokens" : [
    {
      "token" : "中华人民共和国",
      "start_offset" : 0,
      "end_offset" : 7,
      "type" : "CN_WORD",
      "position" : 0
    },
    {
      "token" : "国歌",
      "start_offset" : 7,
      "end_offset" : 9,
      "type" : "CN_WORD",
      "position" : 1
    }
  ]
}
```

[^analyze]: https://elasticsearch.cn/article/771


## 系列文章

1. [ElasticSearch源码解读一：源码编译和Debug环境搭建](https://lanffy.github.io/2019/04/08/Elasticsearch-Compile-Source-And-Debug)
2. [ElasticSearch源码解读二：启动过程详解](https://lanffy.github.io/2019/04/09/ElasticSearch-Start-Up-Process)
3. [Elasticsearch源码解读三：创建索引过程详解](https://lanffy.github.io/2019/04/16/How-Elasticsearch-Create-Index)
4. [Elasticsearch源码解读四：搜索过程详解](https://lanffy.github.io/2019/04/30/ElasticSearch-Search-Process)
5. [Elasticsearch源码解读五：搜索相关性排序算法详解](https://lanffy.github.io/2019/05/08/Elasticsearch-Search-Score-Algorithm)
6. [Elasticsearch源码解读六：ES中的倒排索引](https://lanffy.github.io/2019/05/10/Inverted-Index-In-Elasticsearch)
7. [Elasticsearch源码解读七：常见用法手册](https://lanffy.github.io/2019/07/10/Elasticsearch-Common-Usage-Manual)
