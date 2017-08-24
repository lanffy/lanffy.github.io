---
layout: post
title: "使用PHP爬取并解析页面元素"
categories: [编程语言]
tags: [PHP]
author_name: R_Lanffy
---
---

![Crawler](http://7xjh09.com1.z0.glb.clouddn.com/blog/Crawler.jpg-Lanffy)

前段时间用Python爬取了一些页面元素数据。但因后端的存储系统暂时没有Python的API接口，无法将数据存储到实体载体中。于是尝试了一下用PHP爬取页面并解析DOM。这里简要记录。

在Python中，有Beautiful Soup可以解析HTML页面，[上一篇文章](2017-07-23-Python-And-BeautifulSoup4.md)做了简单的介绍。于是，就需要一个用PHP实现的可以解析HTML页面的工具了。

在网上找了一下，还真有一个简单的实现可以解析DOM。主页介绍：[PHP Simple HTML DOM Parser](http://microphp.us/plugins/public/microphp_res/simple_html_dom/manual.htm)

代码下载：[https://github.com/samacs/simple_html_dom](https://github.com/samacs/simple_html_dom)

源码中有很多example，在实际应用中，只需要include simple_html.php 文件就可以了。

应用例子如下：

```php
include '/path/to/simple_html.php';
$page_html = file_get_html('page_url'); //获取要爬取的页面的dom对象
$divs = $page_html->find('a[name=selectDetail]'); //查找页面中name='selectDetail' 的a标签

// 遍历页面中的a标签，获取标签中的key属性的值
foreach ($divs as $div) {
    $cid = $div->key;
    if ($cid) {
        $ids[] = $cid;
    }
}
```

可以看到，在爬取页面元素的时候，很方便，也节省了很多的时间。

还有很多功能，大家自己去探索吧。

