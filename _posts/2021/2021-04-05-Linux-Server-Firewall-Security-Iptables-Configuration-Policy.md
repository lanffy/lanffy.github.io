---
layout: post
title: "Linux服务器防火墙安全iptables配置策略"
categories: [操作系统]
tags: [iptables]
author_name: R_Lanffy
published: true
---
---

## 先禁用/停止自带的firewalld服务(如果有)

```bash
## 停止firewalld服务
systemctl stop firewalld
## 禁用firewalld服务
systemctl mask firewalld
```

## 安装iptables iptables-services

```bash
## 先检查是否安装了iptables 
service iptables status
## 安装iptables
yum install -y iptables
## 升级iptables
yum update iptables 
## 安装iptables-services
yum install iptables-services
```

## 基本安全配置

```bash
## 查看iptables现有规则
iptables -L -n

## 允许来自于lo接口的数据包(本地访问)
iptables -A INPUT -i lo -j ACCEPT

##设置IP白名单
iptables -A INPUT -p tcp -s xxx.xxx.xxx.xxx -j ACCEPT

## 开放22端口
iptables -A INPUT -p tcp --dport 22 -j ACCEPT

## 关闭22端口
iptables -A INPUT -p tcp --dport 22 -j DROP

## 其他入站一律丢弃,白名单中的IP除外
iptables -P INPUT DROP

## 保存上述规则
service iptables save
```


## 其他规则设定

```bash
## 如果要添加内网ip信任（接受其所有TCP请求）
## ***.***.***.***为ip地址
iptables -A INPUT -p tcp -s ***.***.***.*** -j ACCEPT
## 过滤所有非以上规则的请求
iptables -P INPUT DROP
## 要封停一个IP，使用下面这条命令：
iptables -I INPUT -s ***.***.***.*** -j DROP
## 要解封一个IP，使用下面这条命令:
iptables -D INPUT -s ***.***.***.*** -j DROP
```

## 服务的启动与禁用相关命令

```bash
## 注册iptables服务开机启动
systemctl enable iptables.service
## 在开机时禁用服务
systemctl disable iptables.service
## 查看服务是否开机启动
systemctl is-enabled iptables
## 启动服务
systemctl start iptables
## 关闭服务
systemctl stop iptables 
## 重启服务
systemctl restart iptables
## 服务状态
systemctl status iptables
```

## 禁用指定端口，但开放给指定IP

```bash
# 禁止6379端口被访问,但允许指定的ip访问该端口
-I INPUT -p tcp --dport 6379 -j DROP
-I INPUT -s xxx.xxx.xxx.xxx -ptcp --dport 6379 -j ACCEPT
```