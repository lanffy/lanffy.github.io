---
layout: post
title: "Python中常见编码问题处理"
categories: [编程语言]
tags: [Python]
author_name: R_Lanffy
published: true
---
---


使用Python发起一个Http请求时，得到的结果是乱码。这种情况和Response的编码方式有关。

Http请求：

```python
r = requests.post(url = NLPC_URL, data = json.dumps(p), headers=headers)
```

查看Response的编码方式：``r.encoding``：输出：``ISO-8859-1``
指定Response的编码方式：``r.encoding=utf-8``