---
layout: post
title: "Java中的POST与GET请求"
categories: [编程语言]
tags: [JAVA]
author_name: R_Lanffy
---
---

在服务请求中，通过HTTP的方式发送POST或者GET是最常见的请求方式。下面介绍JAVA中常用的POST、GET请求方式。

### POST

实现代码

```java
public static void sendMessage(String url, String message) {
    try {
        HttpURLConnection conn = (HttpURLConnection) new URL(url).openConnection();
        conn.setDoOutput(true); //表示只写数据
        conn.setRequestProperty("Content-Type", "application/json"); //设置请求头
        conn.setRequestProperty("Accept", "application/json"); //设置请求头
        conn.setRequestMethod("POST"); //POST请求方式
        OutputStream stream = conn.getOutputStream();
        stream.write(message.getBytes()); //请求数据
        stream.flush();
        stream.close();
        int responseCode = conn.getResponseCode();
        if (responseCode == HttpURLConnection.HTTP_OK) {
            System.out.println("SUCCESS");
        } else {
            System.out.println("SUCCESS");
        }
    } catch (IOException e) {
        e.printStackTrace();
        System.out.println("EXCEPTION");
    }
}
```

上面的方法是先了一下的``curl``语句：

``curl -H "Content-Type:application/json" -H "Accept:application/json" -d "messge" "url"``

### GET

实现``conn.setRequestMethod("GET");``即可。

