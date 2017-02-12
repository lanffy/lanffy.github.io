---
layout: post
title: "Java Standalone Jar Application Example"
categories: [编程语言]
tags: [JAVA]
author_name: R_Lanffy
---
---

# 概述

代码发布打包的时候，有很多种方式，其中一种方式在打包的时候可以将整个项目中所用到的依赖包全部一起打包。一般叫做``Standalone Application``.j即可独立运行的应用。

这种打包方式的优点是显而易见的，即打包之后一个Jar即可运行。迁移快，成本低。相反的，其缺点是如果依赖包升级，则需要重新发布打包。所以这种方式适用于小项目，依赖包少的项目。

## 配置

要生成Standalone Application 的Jar包，需要在``pom.xml``中添加一些配置，配置如下

```xml
<?xml version="1.0" encoding="UTF-8"?>
<project xmlns="http://maven.apache.org/POM/4.0.0"
         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:schemaLocation="http://maven.apache.org/POM/4.0.0 http://maven.apache.org/xsd/maven-4.0.0.xsd">
    <modelVersion>4.0.0</modelVersion>

    <groupId>com.test.esb</groupId>
    <artifactId>test-message-transfer</artifactId>
    <version>1.0</version>
    <packaging>jar</packaging>
    <name>test-message-transfer</name>
    <description>Test Project Description</description>

    <!-- 参数-->
    <properties>
        <project.build.sourceEncoding>UTF-8</project.build.sourceEncoding>
        <!--jar 包入口-->
        <main.class>com.test.esb.Main</main.class>
    </properties>

    <!--打包参数-->
    <build>
        <plugins>
            <plugin>
                <groupId>org.apache.maven.plugins</groupId>
                <artifactId>maven-compiler-plugin</artifactId>
                <!--编译的maven的版本-->
                <version>3.1</version>
                <configuration>
                    <!--jdk 版本,默认是最新的jdk版本-->
                    <source>1.7</source>
                    <target>1.7</target>
                </configuration>
            </plugin>

            <!--for standalone application, standalone jar包打包需要添加以下配置-->
            <plugin>
                <groupId>org.apache.maven.plugins</groupId>
                <artifactId>maven-shade-plugin</artifactId>
                <version>2.4.3</version>
                <executions>
                    <execution>
                        <phase>package</phase>
                        <goals>
                            <goal>shade</goal>
                        </goals>
                        <configuration>
                            <transformers>
                                <transformer implementation="org.apache.maven.plugins.shade.resource.ManifestResourceTransformer">
                                    <!--这里指定jar包执行的入口,该值来自于上面的参数中定义的变量-->
                                    <mainClass>${main.class}</mainClass>
                                </transformer>
                            </transformers>
                            <!--包名是否追加指定的后缀名称,true:包名=项目名加shadedClassifierName的值,false:包名=original-项目名-->
                            <shadedArtifactAttached>true</shadedArtifactAttached>
                            <!--standalone 包名后缀-->
                            <shadedClassifierName>with-dependencies</shadedClassifierName>
                        </configuration>
                    </execution>
                </executions>
            </plugin>
        </plugins>
    </build>

    <dependencies>
        <dependency>
            <groupId>log4j</groupId>
            <artifactId>log4j</artifactId>
            <version>1.2.17</version>
        </dependency>
    </dependencies>

</project>
```

配置好后打包生成的包中有一个以``with-dependencies``为后缀的的包就是``standalone`` jar 包.

### 运行JAR包

``java -jar project_name-with-dependencies.jar`` 就可以运行起来了。

