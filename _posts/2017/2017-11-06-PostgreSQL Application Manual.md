---
layout: post
title: "PostgreSQL应用手册"
categories: [数据存储]
tags: [postgresql]
author_name: R_Lanffy
---
---

# PostgreSQL应用手册

## 简介

1. 官网：[https://www.postgresql.org/](https://www.postgresql.org/?from=http://lanffy.github.io/)
2. [PostgreSQL中文文档](http://www.yiibai.com/manual/postgresql/?from=http://lanffy.github.io/)

## 常用命令
### 常用登录命令
#### 登录服务器

``psql -h 127.18.2.246 -U userName -p 5432 -W``

回车之后输入密码即可登录

* -h：服务所在IP
* -U：登录用户名
* -p：服务端口(小写p)
* -W：密码

#### 直接登录服务器上的指定数据库

``psql -h 127.18.2.246 -U userName -p 5432 -d dbName -W``

### 库表操作命令
#### 导出数据库表

``pg_dump --host 127.18.2.246 --port 5432 --username userName -t tableName > file.sql dbName``

* --host：数据库所在IP
* --port：服务端口
* --username：数据库用户名
* --t：要导出的数据表名称
* file.sql：导出的数据本地文件
* dbName：表所在库名

#### 数据库表导入

1. ``psql -h 127.18.2.246 -U userName -p 5432 -d dbName -f /path/to/file.sql``
2. 通过登录服务器操作
    
    1. 登录服务器
    2. 输入：``\c dbName``，链接指定的数据库
    3. 输入：``\i /path/to/file.sql``，执行指定的SQL文件

#### 导出指定数据库

``pg_dump --host 127.18.2.246 --port 5432 --username userName -f dbData.sql dbName``

* dbData.sql：导出到本地的数据库文件

其他参数含义同上

#### 数据库导入

通过上面的步骤，可以得到指定数据库的SQL文件。
在导入整个数据库及其数据之前，需要**先创建要导入的库**，完整步骤如下

1. 链接服务器：``psql -h 127.18.2.246 -U userName -p 5432 -W``
2. 创建数据库：``createdb dbName``
3. 进入数据库：``\c dbName``
4. 导入数据：``\i /path/to/dbName.sql``

### 表操作命令

#### 字段添加、删除

略

#### 更改字段默认值

```sql
ALTER TABLE tableName ALTER COLUMN columnName SET DEFAULT 0;
```

#### 更改字段类型及其默认值

```sql
ALTER TABLE tableName ALTER COLUMN columnName 
SET data type int4 USING columnName::int4,
ALTER COLUMN columnName SET DEFAULT 0;
```

## 常见错误

有的时候在更改表字段类型时，会出现以下报错：

![error](http://7xjh09.com1.z0.glb.clouddn.com/postgresqlError.png-Lanffy)

这是因为字段字段类型改变前和改变后的默认值不能互相转换，所以在更改字段类型前，需要先更改字段的默认值。


