---
layout: post
title: "【译】使用java.util.concurrent.ThreadFactory类创建线程"
categories: [编程语言]
tags: [JAVA]
author_name: R_Lanffy
---
---

## 使用java.util.concurrent.ThreadFactory类创建线程

[工厂设计模式](http://howtodoinjava.com/2012/10/23/implementing-factory-design-pattern-in-java/)是``Java``中最常用的设计模式之一。它是一种[创建型设计模式](http://howtodoinjava.com/category/design-patterns/creational/)，能够用于创建一个或多个类所需要的对象。有了这个工厂，我们就能集中的创建对象。

集中创建方式给我们带来了一些好处，例如：

1. 能够很容易的改变类创建的对象或者创建对象的方式；
2. 能够很容易限制对象的创建，例如：我们只能为a类创建N个对象；
3. 能够很容易的生成有关对象创建的统计数据。

在Java中，我们通常使用两种方式来创建线程：[继承Thread类和实现Runnable接口](http://howtodoinjava.com/2013/03/12/difference-between-implements-runnable-and-extends-thread-in-java/)。Java还提供了一个接口，既``ThreadFactory``接口，用于创建你自己的线程对象工厂。

很多类中，例如：[ThreadPoolExecutor](http://docs.oracle.com/javase/6/docs/api/java/util/concurrent/ThreadPoolExecutor.html#ThreadPoolExecutor%28int,%20int,%20long,%20java.util.concurrent.TimeUnit,%20java.util.concurrent.BlockingQueue,%20java.util.concurrent.ThreadFactory%29)，使用构造函数来接收``ThreadFactory``来作为参数。这个工厂参数将会在程序执行时创建新的线程。使用``ThreadFactory``，你能够自定义执行程序如何创建线程，例如为线程定义适当的名称、优先级，或者你甚至可以将它设定为守护线程。

``ThreadFactory``例子
在这个例子中，我们将学习如何通过实现一个``ThreadFactory``接口来创建一个有个性化名称的线程对象，同时，我们保存了线程对象的创建信息。

``Task.java``

```java
class Task implements Runnable
{
	@Override
	public void run() {
		try {
			TimeUnit.SECONDS.sleep(2);
		} catch (InterruptedException e) {
			e.printStackTrace();
		}
	}
}
```

``CustomThreadFactory.java``

```java
public class CustomThreadFactory implements ThreadFactory
{
	private intcounter;
	private String name;
	private List<String> stats;

	public CustomThreadFactory(String name) {
		counter = 1;
		this.name = name;
		stats = new ArrayList<String>();
	}

	@Override
	public Thread newThread(Runnable runnable) {
		Thread t = new Thread(runnable, name + "-Thread_" + counter);
		counter++;
		stats.add(String.format("Created thread %d with name %s on %s \n", t.getId(), t.getName(), new Date()));
		return t;
	}

	public String getStats() {
		StringBuffer buffer = new StringBuffer();
		Iterator<String> it = stats.iterator();
		while (it.hasNext()) {
			buffer.append(it.next());
		}
		return buffer.toString();
	}
}
```

为了使用上面的线程工厂，请看下面的执行程序：

```java
public static void main(String[] args) {
	CustomThreadFactory factory = new CustomThreadFactory("CustomThreadFactory");
	Task task = new Task();
	Thread thread;
	System.out.printf("Starting the Threads\n\n");
	for (int i = 1; i <= 10; i++) {
		thread = factory.newThread(task);
		thread.start();
	}
	System.out.printf("All Threads are created now\n\n");
	System.out.printf("Give me CustomThreadFactory stats:\n\n" + factory.getStats());
}
```

程序执行结果：

```
Output :
 
Starting the Threads
 
All Threads are created now
 
Give me CustomThreadFactory stats:
 
Created thread 9 with name CustomThreadFactory-Thread_1 on Tue Jan 06 13:18:04 IST 2015
Created thread 10 with name CustomThreadFactory-Thread_2 on Tue Jan 06 13:18:04 IST 2015
Created thread 11 with name CustomThreadFactory-Thread_3 on Tue Jan 06 13:18:04 IST 2015
Created thread 12 with name CustomThreadFactory-Thread_4 on Tue Jan 06 13:18:04 IST 2015
Created thread 13 with name CustomThreadFactory-Thread_5 on Tue Jan 06 13:18:04 IST 2015
Created thread 14 with name CustomThreadFactory-Thread_6 on Tue Jan 06 13:18:04 IST 2015
Created thread 15 with name CustomThreadFactory-Thread_7 on Tue Jan 06 13:18:04 IST 2015
Created thread 16 with name CustomThreadFactory-Thread_8 on Tue Jan 06 13:18:04 IST 2015
Created thread 17 with name CustomThreadFactory-Thread_9 on Tue Jan 06 13:18:04 IST 2015
Created thread 18 with name CustomThreadFactory-Thread_10 on Tue Jan 06 13:18:04 IST 2015
```

上面的代码中，``ThreadFactory``接口只有一个叫做``newThread()``的方法，它接收一个``Runnable``对象作为参数，同时返回一个``Thread``对象。当你实现``ThreadFactory``接口时，你必须重写这个方法。

Happy Learning !!

原文连接：[Creating Threads Using java.util.concurrent.ThreadFactory](http://howtodoinjava.com/2015/01/06/creating-threads-using-java-util-concurrent-threadfactory/)
