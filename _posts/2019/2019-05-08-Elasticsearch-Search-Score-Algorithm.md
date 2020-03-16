---
layout: post
title: "Elasticsearch源码解读五：搜索相关性排序算法详解"
categories: [搜索引擎]
tags: [ElasticSearch]
author_name: R_Lanffy
latex: true
---
---

## 前言

说明：本文章使用的ES版本是：``6.2.4``

在上一篇文章[Elasticsearch搜索过程详解](https://lanffy.github.io/2019/04/30/ElasticSearch-Search-Process)中，介绍了ES的搜索过程。

接下来我们具体的看一下ES搜索时，是如何计算文档相关性得分并用于排序的。

## TF-IDF

在介绍ES计算文档得分之前，先来看一下``TF-IDF``算法。

``TF-IDF``（Term Frequency–Inverse Document Frequency）是一种用于信息检索与文本挖掘的常用加权算法。它是一种统计方法，用以评估一字词对于一个文件集或一个语料库中的其中一份文件的重要程度。字词的重要性随着它在文件中出现的次数成正比增加，但同时会随着它在语料库中出现的频率成反比下降。

### TF-IDF算法原理

``TF-IDF``实际上是两个算法``TF``和``IDF``的乘积。

#### 词频（Term Frequency，TF）

词频的所在对象是一个具体的文档，是指一个文档中出现某个单词（Term）的频率（Frequency）。这里用的是频率而不是次数，是为了防止文档内容过长从而导致某些单词出现过多。为了正确评价一个单词在一个文档中的重要程度，需要将出现次数归一化，其算法如下：

$$tf_i=\frac{n_i}{\sum\nolimits_{k=1}^nn_k}$$

上面式子中$n_i$是该词在文件中的出现次数，而分母$\sum _{k=1}^nn_k$则是在文件中所有字词的出现次数之和。

#### 逆向文件频率（Inverse Document Frequency，IDF）

逆向文件频率描述的对象是一个文档集合中，包含某个单词的文档数量。它表示的是一个单词在一个文档集合中的普遍重要程度。将其归一化的算法入下：

$${idf_{i}} =\lg {\frac {|D|}{1+|\{j:t_{i}\in d_{j}\}|}}$$

其中

* \|D\|：表示文档集合中的文件总数
* \|{$j:t_i\in d_j$}\| ：包含词语$t_i$的文件数目（即$n_i \neq 0$的文件数目）如果词语不在数据中，就导致分母为零，因此一般情况下使用分母加了一个1

最后

$$tfidf_i=tf_i\times idf_i$$

某一特定文件内的高词语频率，以及该词语在整个文件集合中的低文件频率，可以产生出高权重的tf-idf。因此，tf-idf倾向于过滤掉常见的词语，保留重要的词语。

#### TF—IDF总结

* TF表示的是一个单词在一段文本中的重要程度，随着单词的增加而增加
* IDF表示的是一个单词在一个文档集合中的重要程度，越稀有权重越高，所以它随着单词的增加而降低

### TF-IDF算法举例

用上面的公式，计算一个例子。

假如一篇文件的总词语数是100个，而词语“学校”出现了5次，那么“学校”一词在该文件中的词频（tf）就是

$$tf_i=5/100=0.05$$

“学校”一词在1,000份文件出现过，而文件总数是1,000,000份的话，其逆向文件频率就是

$$idf_i = lg（1,000,000 / 1,000）=3$$

最后的tf-idf的分数为

$$tfidf_i = tf_i\times idf_i = 0.05 \times 3= 0.15$$

## OKapi BM25算法原理

BM25（Best Match25）是在信息检索系统中根据提出的query对document进行评分的算法。

``TF-IDF``算法是一个可用的算法，但并不太完美。它给出了一个基于统计学的相关分数算法，而BM25算法则是在此之上做出改进之后的算法。（为什么要改进呢？``TF-IDF``不完美的地方在哪里？）

1. 当两篇描述“人工智能”的文档A和B，其中A出现“人工智能”100次，B出现“人工智能”200次。两篇文章的单词数量都是10000，那么按照``TF-IDF``算法，A的``tf``得分是：0.01，B的``tf``得分是0.02。得分上B比A多了一倍，但是两篇文章都是再说人工智能，``tf``分数不应该相差这么多。可见单纯统计的``tf``算法在文本内容多的时候是不可靠的
2. 多篇文档内容的长度长短不同，对``tf``算法的结果也影响很大，所以需要将文本的长度也考虑到算法当中去

基于上面两点，BM25算法做出了改进，最终该算法公式如下：

$${\displaystyle {\text{score}}(D,Q)=\sum _{i=1}^{n}{\text{IDF}}(q_{i})\cdot {\frac {f(q_{i},D)\cdot (k_{1}+1)}{f(q_{i},D)+k_{1}\cdot \left(1-b+b\cdot {\frac {|D|}{\text{avgdl}}}\right)}}}$$

其中:

* Q：文档集合
* D：具体的文档
* ${\text{IDF}}(q_{i})$：就是TF-IDF中的IDF，表示单词$q_{i}$在文档集合Q的IDF值
* $f(q_{i},D)$：就是TF-IDF中的TF，表示单词$q_{i}$在文档D中的TF值
* $k_{1}$：词语频率饱和度（term frequency saturation）它用于调节饱和度变化的速率。它的值一般介于 1.2 到  2.0 之间。数值越低则饱和的过程越快速。（意味着两个上面A、B两个文档有相同的分数，因为他们都包含大量的“人工智能”这个词语）。在ES应用中为1.2
* $b$：字段长度归约，将文档的长度归约化到全部文档的平均长度，它的值在 0 和 1 之间，1 意味着全部归约化，0 则不进行归约化。在ES的应用中为0.75
* $\|D\|$：文本长度
* $avgdl$：文本平均长度

## Lucene相关性算法

> 注：ES版本6.2.4所用的Lucene jar包版本是：7.2.1

在了解了``TF-IDF``算法之后，再来了解Lucene中的相关性算法就很好理解了。

Lucene中，相关性算法如下：

$$score(t, q, d)={\sum\nolimits_{t}^n (idf(t) * boost(t) * tfNorm(t, d))}$$

其中：

* q：文档集合
* d：具体的文档
* t：单词
* ``score(t, q, d)``：表示包含查询词t的文档d在文档集合q中的相关性得分
* ``idf(t)``：逆向文件频率，ES中，逆向文件频率的算法是：

$${idf_{t}} =\ln {(1 + \frac {docCount-docFreq+0.5}{docFreq+0.5})}$$ 

    docCount：表示文档总数，docFreq：表示包含单词t的文档数量。


* ``boost(t)``：查询时，指定的单词的权重，不指定时为1
* ``tfNorm(t, d)``：单词频率权重，它用BM25替代了简单的TF算法，ES中，其算法如下：

$${\displaystyle {\text{tfNorm}}(t,d)={\frac {f(t, d)\cdot (k_{1}+1)}{f(t, d)+k_{1}\cdot \left(1-b+b\cdot {\frac {|D|}{\text{avgdl}}}\right)}}}$$

* ``tfNorm(t, d)``：单词t在文档d中的频率权重
* ``f(t, d)``：单词t在文档d中的出现次数
* $k_{1}$：词语频率饱和度，用于控制词频对结果的影响，数值越低则单词数量影响越小。它的值一般介于 1.2 到  2.0 之间。。在ES应用中为1.2
* $b$：字段长度归约，用于控制文本长度对结果的影响，数值越大文本长度影响越小。它的值在 0 和 1 之间，在ES的应用中为0.75
* $\|D\|$：文档d中查询该字段的文本长度
* $avgdl$：文档集合中，所有查询该字段的平均长度

ES在搜索过程中，拿到文档ID之后，就会根据搜索词，计算每篇文档的相关性得分，用其进行排序。


## 系列文章

1. [ElasticSearch源码解读一：源码编译和Debug环境搭建](https://lanffy.github.io/2019/04/08/Elasticsearch-Compile-Source-And-Debug)
2. [ElasticSearch源码解读二：启动过程详解](https://lanffy.github.io/2019/04/09/ElasticSearch-Start-Up-Process)
3. [Elasticsearch源码解读三：创建索引过程详解](https://lanffy.github.io/2019/04/16/How-Elasticsearch-Create-Index)
4. [Elasticsearch源码解读四：搜索过程详解](https://lanffy.github.io/2019/04/30/ElasticSearch-Search-Process)
5. [Elasticsearch源码解读五：搜索相关性排序算法详解](https://lanffy.github.io/2019/05/08/Elasticsearch-Search-Score-Algorithm)
6. [Elasticsearch源码解读六：ES中的倒排索引](https://lanffy.github.io/2019/05/10/Inverted-Index-In-Elasticsearch)
7. [Elasticsearch源码解读七：常见用法手册](https://lanffy.github.io/2019/07/10/Elasticsearch-Common-Usage-Manual)


> 参考资料：

* [https://lucene.apache.org/core/7_2_1/core/org/apache/lucene/search/similarities/TFIDFSimilarity.html](https://lucene.apache.org/core/7_2_1/core/org/apache/lucene/search/similarities/TFIDFSimilarity.html)
* [https://en.wikipedia.org/wiki/Tf%E2%80%93idf](https://en.wikipedia.org/wiki/Tf%E2%80%93idf)
* [https://en.wikipedia.org/wiki/Okapi_BM25](https://en.wikipedia.org/wiki/Okapi_BM25)
* [https://github.crookster.org/Adding-MathJAX-LaTeX-MathML-to-Jekyll/](https://github.crookster.org/Adding-MathJAX-LaTeX-MathML-to-Jekyll/)