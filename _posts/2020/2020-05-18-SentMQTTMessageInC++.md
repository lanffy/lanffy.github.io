---
layout: post
title: "使用Mqtt C++客户端发送和接收MQTT消息"
categories: [编程语言]
tags: [C++,MQTT]
author_name: R_Lanffy
published: true
---
---

最近学习了MQTT C++这个库，阅读了其中的部分代码。简要记录如下。
本文章主要介绍在Windwos平台下，如何接入MQTT C++客户端，主要介绍使用MQTT C++客户端接收和发送消息。MQTT C++客户端的安装可以参考上一篇文章：[Mqtt简介以及在Windows中编译安装Mqtt C++客户端](https://lanffy.github.io/2020/01/06/MqttBriefIntroductionAndMqttC++ClientInstallInWindows)

依赖库：

* MQTT C语言库：[paho.mqtt.c](https://github.com/eclipse/paho.mqtt.c)
* MQTT C++ 语言库：[paho.mqtt.cpp](https://github.com/eclipse/paho.mqtt.cpp)
* C++ json解析库：[nlohmann/json](https://github.com/nlohmann/json)，主要用来解析Json格式的消息，也可以用其他的Json解析库

这些依赖库需要提前下载安装好。

## Mqtt简介

简介这里不再赘述了，可以参考：[Mqtt简介以及在Windows中编译安装Mqtt C++客户端](https://lanffy.github.io/2020/01/06/MqttBriefIntroductionAndMqttC++ClientInstallInWindows)

## 发送消息

引入用到的库和常量：

```c
#include <iostream>
#include <cstdlib>
#include <string>
#include <map>
#include <vector>
#include <cstring>
#include "mqtt/client.h"

const std::string SERVER_ADDRESS("tcp://broker.hivemq.com:1883");
const std::string CLIENT_ID("33f1c750-01a6-4a26-9057-6a5adf0f80f5");
const std::string TOPIC("lanffy/test");
const int QOS = 1;
```

定义动作监听器，链接成功，消息发送成功后，都会回调相应的动作：

```c
class user_callback : public virtual mqtt::callback
{
void connection_lost(const std::string& cause) override {
std::cout << "\nConnection lost" << std::endl;
if (!cause.empty())
std::cout << "\tcause: " << cause << std::endl;
}
 
void delivery_complete(mqtt::delivery_token_ptr tok) override {
std::cout << "\n\t[Delivery complete for token: "
<< (tok ? tok->get_message_id() : -1) << "]" << std::endl;
}
 
public:
};
```

链接Mqtt Server并发送消息：

```c
int main(int argc, char* argv[])
{
std::cout << "Initialzing..." << std::endl;
mqtt::client client(SERVER_ADDRESS, CLIENT_ID);
 
user_callback cb;
client.set_callback(cb);
 
mqtt::connect_options connOpts;
connOpts.set_keep_alive_interval(20);
connOpts.set_clean_session(true);
std::cout << "...OK" << std::endl;
 
try {
std::cout << "\nConnecting..." << std::endl;
client.connect(connOpts);
std::cout << "...OK" << std::endl;
 
// First use a message pointer.
 
std::cout << "\nSending message..." << std::endl;
auto pubmsg = mqtt::make_message(TOPIC, "Hello World,This is a message...");
pubmsg->set_qos(QOS);
client.publish(pubmsg);
std::cout << "...OK" << std::endl;
 
// Disconnect
std::cout << "\nDisconnecting..." << std::endl;
client.disconnect();
std::cout << "...OK" << std::endl;
}
catch (const mqtt::persistence_exception& exc) {
std::cerr << "Persistence Error: " << exc.what() << " ["
<< exc.get_reason_code() << "]" << std::endl;
return 1;
}
catch (const mqtt::exception& exc) {
std::cerr << exc.what() << std::endl;
return 1;
}
 
std::cout << "\nExiting" << std::endl;
return 0;
}
```

运行代码，同时使用MQTT BOX订阅相同的topic，即可收到消息。

## 接收消息

引入用到的库：

```c
#include <iostream>
#include <cstdlib>
#include <string>
#include <cstring>
#include <cctype>
#include <thread>
#include <chrono>
#include "mqtt/async_client.h"
```

定义链接消息的常量：

```c
const std::string SERVER_ADDRESS("tcp://broker.hivemq.com:1883");
const std::string CLIENT_ID("33f1c750-01a6-4a26-9057-6a5adf0f80f5");
const std::string TOPIC("lanffy/test");

const int QOS = 1;
const int N_RETRY_ATTEMPTS = 5;
```

定义消息接收监听器：

```c
class action_listener : public virtual mqtt::iaction_listener
{
std::string name_;

void on_failure(const mqtt::token& tok) override {
std::cout << name_ << " failure";
if (tok.get_message_id() != 0)
std::cout << " for token: [" << tok.get_message_id() << "]" << std::endl;
std::cout << std::endl;
}

void on_success(const mqtt::token& tok) override {
std::cout << name_ << " success";
if (tok.get_message_id() != 0)
std::cout << " for token: [" << tok.get_message_id() << "]" << std::endl;
auto top = tok.get_topics();
if (top && !top->empty())
std::cout << "\ttoken topic: '" << (*top)[0] << "', ..." << std::endl;
std::cout << std::endl;
}

public:
action_listener(const std::string& name) : name_(name) {}
};

/////////////////////////////////////////////////////////////////////////////


class callback : public virtual mqtt::callback,
public virtual mqtt::iaction_listener

{
int nretry_;
mqtt::async_client& cli_;
mqtt::connect_options& connOpts_;
action_listener subListener_;

void reconnect() {
std::this_thread::sleep_for(std::chrono::milliseconds(2500));
try {
cli_.connect(connOpts_, nullptr, *this);
}
catch (const mqtt::exception& exc) {
std::cerr << "Error: " << exc.what() << std::endl;
exit(1);
}
}

void on_failure(const mqtt::token& tok) override {
std::cout << "Connection attempt failed" << std::endl;
if (++nretry_ > N_RETRY_ATTEMPTS)
exit(1);
reconnect();
}

void on_success(const mqtt::token& tok) override {}

void connected(const std::string& cause) override {
std::cout << "\nConnection success" << std::endl;
std::cout << "\nSubscribing to topic '" << TOPIC << "'\n"
<< "\tfor client " << CLIENT_ID
<< " using QoS" << QOS << "\n"
<< "\nPress Q<Enter> to quit\n" << std::endl;

cli_.subscribe(TOPIC, QOS, nullptr, subListener_);
}

void connection_lost(const std::string& cause) override {
std::cout << "\nConnection lost" << std::endl;
if (!cause.empty())
std::cout << "\tcause: " << cause << std::endl;

std::cout << "Reconnecting..." << std::endl;
nretry_ = 0;
reconnect();
}


void message_arrived(mqtt::const_message_ptr msg) override {
std::cout << "Message arrived" << std::endl;
std::cout << "\ttopic: '" << msg->get_topic() << "'" << std::endl;
std::cout << "\tpayload: '" << msg->to_string() << "'\n" << std::endl;
}

void delivery_complete(mqtt::delivery_token_ptr token) override {}

public:
callback(mqtt::async_client& cli, mqtt::connect_options& connOpts)
: nretry_(0), cli_(cli), connOpts_(connOpts), subListener_("Subscription") {}
};
```

发起调用：


```c
int main(int argc, char* argv[])
{
mqtt::connect_options connOpts;
connOpts.set_keep_alive_interval(20);
connOpts.set_clean_session(true);

mqtt::async_client client(SERVER_ADDRESS, CLIENT_ID);

callback cb(client, connOpts);
client.set_callback(cb);

try {
std::cout << "Connecting to the MQTT server..." << std::flush;
client.connect(connOpts, nullptr, cb);
}
catch (const mqtt::exception&) {
std::cerr << "\nERROR: Unable to connect to MQTT server: '"
<< SERVER_ADDRESS << "'" << std::endl;
return 1;
}

while (std::tolower(std::cin.get()) != 'q')
;

try {
std::cout << "\nDisconnecting from the MQTT server..." << std::flush;
client.disconnect()->wait();
std::cout << "OK" << std::endl;
}
catch (const mqtt::exception& exc) {
std::cerr << exc.what() << std::endl;
return 1;
}

return 0;
}
```

通过MQTT BOX发送消息到相同的topic即可收到消息，如下图：
![](/images/posts/2020/5/receiveMessage.png)
