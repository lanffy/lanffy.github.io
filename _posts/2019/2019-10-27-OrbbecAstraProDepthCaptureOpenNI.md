---
layout: post
title: "OpenNI2采集奥比中光AstraPro深度图RGB彩色图和点云数据"
categories: [编程语言]
tags: [C++]
author_name: R_Lanffy
published: true
---
---

奥比中光双目摄像头[AstraPro](http://www.orbbec.com.cn/sys/37.html)利用双目成像原理，可以很方便的采集深度图像。下面介绍再Windows平台使用OpenNI2、OpenCV2采集奥比中光双目摄像头AstraPro的深度图RGB彩色图和点云数据的大概操作。

## 软件安装准备

1. 安装摄像头的驱动，下载地址：[https://orbbec3d.com/develop/#windows](https://orbbec3d.com/develop/#windows)，这里要注意的是要选择操作系统位数对应的版本，32为操作系统选择x86版本，64为系统选择x64版本，否则会出现不兼容问题
2. 下载最新[OpenNI](https://github.com/OpenNI/OpenNI2/releases)
3. 下载OpenCV2.4及以上版本：[https://opencv.org/releases/](https://opencv.org/releases/)，其中还用到了TBB，不想自己编译的话，可以用这个编译好的包：[https://download.csdn.net/download/rlanffy/11929225](https://download.csdn.net/download/rlanffy/11929225)
4. 安装Visual Studio 2019

## 创建项目、系统配置 

在VisualStudio中创建项目，并做如下的配置：

1. 引用头文件和源码
    ![](/images/posts/2019/10/astra1.jpg)

2. 引用包
    ![](/images/posts/2019/10/astra2.jpg)
    
3. 附加依赖项
    ![](/images/posts/2019/10/astra3.jpg)

## 编写采集代码

大致的思路是：

1. 识别设备
2. 初始化OpenNI环境，打开设备
3. 打开深度摄像头，初始化配置
4. 打开RGB摄像头，初始化配置
5. 将两个摄像头对齐
6. 不断循环从深度摄像头的Stream采集图像，显示到指定的窗口中
7. 不断循环，从RGB摄像头的Stream中采集图像， 显示到执行的窗口中
8. 按下指定按键，采集点云数据，保存彩色图像
9. 按下ESC，退出程序

代码详见：[https://github.com/lanffy/VideoDataCapturer](https://github.com/lanffy/VideoDataCapturer)

代码中的注释有详细的讲解。

## 点云数据的处理

采集的点云数据在应用中被用来计算采集对象的尺寸，一般使用CloudCompare来处理点云数据，标注数据，测量尺寸。

