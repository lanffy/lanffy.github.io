---
layout: post
title: "Python中HTTP请求响应中文字符编码问题"
categories: [编程语言]
tags: [Python,字符编码]
author_name: R_Lanffy
published: true
---
---

最近在工作中需要用Python做大量的数据分析，在这些数据中，很大一部分都是中文。在处理过程中，中文编码处理花了一些时间。这里做一个记录。

首先是在Python脚本内的中文编码处理，这个网络上有很多教程和解决方案了，这里不再赘述。推荐参考：

1. [字符编码笔记：ASCII，Unicode 和 UTF-8](http://www.ruanyifeng.com/blog/2007/10/ascii_unicode_and_utf-8.html)  By  阮一峰
2. [解决python的中文字符编码问题](https://imlogm.github.io/%E8%87%AA%E7%84%B6%E8%AF%AD%E8%A8%80%E5%A4%84%E7%90%86/character-encoding/) By imlogm

这里重点说一下，在Python中，通过HTTP请求到数据时，如何得到正确的编码后的结果。

使用Python发起一个Http请求时，得到的结果是乱码。这种情况和Response的编码方式有关。

例如Http请求：

```python
# -*- coding: utf-8 -*-
import requests
import json
headers = {'content-type': 'application/json'}
URL = 'xxxx'
r = requests.post(url = URL, data = json.dumps(p), headers=headers)
# 查看Response的编码方式
print r.encoding #输出：ISO-8859-1
# 指定Response的编码方式
r.encoding=utf-8
res = r.json()
```

查看Response的编码方式：``r.encoding``：输出：``ISO-8859-1``
指定Response的编码方式：``r.encoding=utf-8``

通过上面的处理，就可以得到编码方式为``UTF-8``且返回格式为json字符串的结果数据。