---
layout: post
title: "Mysql 数据类型隐式转换规则"
categories: [数据存储]
tags: [Mysql]
author_name: R_Lanffy
---
---

### 现象

今天遇到一个慢查询，查询日志找到慢查询语句是这样的：

```sql
select * from convert_test where areacode=0001 and period>='20170511' and period<='20170511';
```

``convert_test``表结构如下：

```sql
CREATE TABLE `convert_test` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `areacode` char(12) NOT NULL DEFAULT '',
    `period` int(6) unsigned NOT NULL DEFAULT 0,
    `mid_price` int(10) unsigned NOT NULL DEFAULT 0,
    `mid_change` float NOT NULL DEFAULT 0,
	`updated_datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_areacode_period` (`areacode`,`period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='隐式转换测试表';
```

表中数据42W以上。

乍一看，明明创建了一个唯一索引，正常来说，上面的查询语句应该正好命中idx_areacode_period这个索引的，不应该是慢查询的。

为了查看这个语句是怎么查询的，我们在测试库中explain一下：

```sql
mysql> explain select * from convert_test where areacode=0001 and period>='20170511' and period<='20170511';
```

结果如下：

![explain](http://7xjh09.com1.z0.glb.clouddn.com/github_blogexplain.png)

可以看到，这里是没有用到索引的。

### 原因

定义表的时候，areacode字段是字符串类型的，查询的时候传入的是0001，这里0001被Mysql当做了整数处理为1，Mysql检测到areacode这个字段的查询类型是整型，就会全表扫描，将所有行的areacode转换成整型，然后在做查询处理。

找原因了，就很好解决了，上面的sql语句修改如下：

```sql
mysql> explain select * from convert_test where areacode='0001' and period>='20170511' and period<='20170511';
```

结果如下：

![explain2](http://7xjh09.com1.z0.glb.clouddn.com/github_blogexplain2.png)

可以看到完全命中了idx_areacode_period 这个索引。

### 扩展

上面的period定义的时候是整型，但是查询传入的是字符串类型，那为什么会命中索引的呢？

看一下[官方的隐试转](https://dev.mysql.com/doc/refman/5.7/en/type-conversion.html?spm=5176.100239.blogcont47339.5.1FTben)换说明：

1. 两个参数至少有一个是 NULL 时，比较的结果也是 NULL，例外是使用 <=> 对两个 NULL 做比较时会返回 1，这两种情况都不需要做类型转换
2. 两个参数都是字符串，会按照字符串来比较，不做类型转换
3. 两个参数都是整数，按照整数来比较，不做类型转换
4. 十六进制的值和非数字做比较时，会被当做二进制串
5. 有一个参数是 TIMESTAMP 或 DATETIME，并且另外一个参数是常量，常量会被转换为 timestamp
6. 有一个参数是 decimal 类型，如果另外一个参数是 decimal 或者整数，会将整数转换为 decimal 后进行比较，如果另外一个参数是浮点数，则会把 decimal 转换为浮点数进行比较
7. **所有其他情况下，两个参数都会被转换为浮点数再进行比较**

所以,下面的几个sql语句有相同的效果：

```sql
select * from convert_test where areacode=0001 and period>='20170511' and period<='20170511';
select * from convert_test where areacode=1 and period>='20170511' and period<='20170511';
select * from convert_test where areacode=0001.0 and period>='20170511' and period<='20170511';
select * from convert_test where areacode=1.0 and period>='20170511' and period<='20170511';
```

mysql 在查询的时候，会将areacode转换成浮点型进行比较

