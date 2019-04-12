---
layout: post
title: "搜索引擎ElasticSearch的启动过程"
categories: [编程语言]
tags: [Java]
author_name: R_Lanffy
---
---

[上一篇文章](http://lanffy.github.io/2019/04/08/Elasticsearch-Compile-Source-And-Debug)说了ES的源码编译以及如何在本地编译。这一篇文章主要说明ES的启动过程。

## 环境准备

参考[ElasticSearch源码编译和Debug](http://lanffy.github.io/2019/04/08/Elasticsearch-Compile-Source-And-Debug)。

说明：本文章使用的ES版本是：6.7.0

启动函数：**org.elasticsearch.bootstrap.ElasticSearch**

设置如下断点：
![-w531](/images/posts/2019/15548127651067.jpg)


启动在[上一篇文章](http://lanffy.github.io/2019/04/08/Elasticsearch-Compile-Source-And-Debug)中介绍的Debug模式中的一种，这里我用的远程Debug模式。

## ElasticSearch的启动过程

跟着Debug流程走一遍，可以看出ES启动流程大概分为以下几个阶段：
1. **org.elasticsearch.bootstrap.Elasticsearch#main(java.lang.String[])** 解析命令参数，加载配置，权限验证
2. **org.elasticsearch.bootstrap.Bootstrap** 初始化，资源检查
3. **org.elasticsearch.node.Node** 启动单机节点，创建keepAlive线程
    1. 为创建Node对象做准备，并最终创建Node对象
        1. 创建Node对象
            1. 如何加载模块和插件
            2. 创建模块和插件的线程池
    2. 启动Node实例

### 一、**org.elasticsearch.bootstrap.Elasticsearch#main(java.lang.String[])**解析命令参数，加载配置，权限验证

程序入口代码如下：

![-w531](/images/posts/2019/15548127651067.jpg)

1. 如果通过启动命令传入了DNS Cache时间，则重写DNS Cache时间
2. 创建 **SecurityManager** 安全管理器
    
    > [SecurityManager](https://docs.oracle.com/javase/7/docs/api/java/lang/SecurityManager.html)：安全管理器在Java语言中的作用就是检查操作是否有权限执行，通过则顺序进行，否则抛出一个异常
3. **LogConfigurator.registerErrorListener();** 注册错误日志监听器
4. **new Elasticsearch();** 创建 **Elasticsearch** 对象
    
    **Elasticsearch**类继承了**EnvironmentAwareCommand**、**Command**，其完整的继承关系如下
    ![](/images/posts/2019/15548775000513.jpg)
    所以**Elasticsearch**也可以解析命令行参数。
5. **elasticsearch.main(args, terminal);** 这里的main方法是其父类中的main方法，这里因为继承关系，方法执行的顺序如下：
    1. **org.elasticsearch.cli.Command#main** 注册shutdownHook，当程序异常关闭时打印异常信息
    2. **org.elasticsearch.cli.Command#mainWithoutErrorHandling** 解析命令行参数
    3. **org.elasticsearch.cli.EnvironmentAwareCommand#execute** 加载配置路径：home、data、logs
    4. **org.elasticsearch.cli.EnvironmentAwareCommand#createEnv** 加载**elasticsearch.yaml**配置文件，创建command运行的环境
    5. **org.elasticsearch.bootstrap.Elasticsearch#execute** 配置验证，进入**Bootstrap.init**阶段

### 二、**org.elasticsearch.bootstrap.Bootstrap** 初始化，资源检查

Bootstrap阶段做的事情比较多，主要方法如下：

```java
    /**
     * This method is invoked by {@link Elasticsearch#main(String[])} to startup elasticsearch.
     */
    static void init(
            final boolean foreground,
            final Path pidFile,
            final boolean quiet,
            final Environment initialEnv) throws BootstrapException, NodeValidationException, UserException {
        // force the class initializer for BootstrapInfo to run before
        // the security manager is installed
        BootstrapInfo.init();

        INSTANCE = new Bootstrap();

        final SecureSettings keystore = loadSecureSettings(initialEnv);
        final Environment environment = createEnvironment(foreground, pidFile, keystore, initialEnv.settings(), initialEnv.configFile());

        if (Node.NODE_NAME_SETTING.exists(environment.settings())) {
            LogConfigurator.setNodeName(Node.NODE_NAME_SETTING.get(environment.settings()));
        }
        try {
            LogConfigurator.configure(environment);
        } catch (IOException e) {
            throw new BootstrapException(e);
        }
        if (environment.pidFile() != null) {
            try {
                PidFile.create(environment.pidFile(), true);
            } catch (IOException e) {
                throw new BootstrapException(e);
            }
        }

        final boolean closeStandardStreams = (foreground == false) || quiet;
        try {
            if (closeStandardStreams) {
                final Logger rootLogger = LogManager.getRootLogger();
                final Appender maybeConsoleAppender = Loggers.findAppender(rootLogger, ConsoleAppender.class);
                if (maybeConsoleAppender != null) {
                    Loggers.removeAppender(rootLogger, maybeConsoleAppender);
                }
                closeSystOut();
            }

            // fail if somebody replaced the lucene jars
            checkLucene();

            // install the default uncaught exception handler; must be done before security is
            // initialized as we do not want to grant the runtime permission
            // setDefaultUncaughtExceptionHandler
            Thread.setDefaultUncaughtExceptionHandler(new ElasticsearchUncaughtExceptionHandler());

            INSTANCE.setup(true, environment);

            try {
                // any secure settings must be read during node construction
                IOUtils.close(keystore);
            } catch (IOException e) {
                throw new BootstrapException(e);
            }

            INSTANCE.start();

            if (closeStandardStreams) {
                closeSysError();
            }
        } catch (NodeValidationException | RuntimeException e) {
            // disable console logging, so user does not see the exception twice (jvm will show it already)
            final Logger rootLogger = LogManager.getRootLogger();
            final Appender maybeConsoleAppender = Loggers.findAppender(rootLogger, ConsoleAppender.class);
            if (foreground && maybeConsoleAppender != null) {
                Loggers.removeAppender(rootLogger, maybeConsoleAppender);
            }
            Logger logger = LogManager.getLogger(Bootstrap.class);
            // HACK, it sucks to do this, but we will run users out of disk space otherwise
            if (e instanceof CreationException) {
                // guice: log the shortened exc to the log file
                ByteArrayOutputStream os = new ByteArrayOutputStream();
                PrintStream ps = null;
                try {
                    ps = new PrintStream(os, false, "UTF-8");
                } catch (UnsupportedEncodingException uee) {
                    assert false;
                    e.addSuppressed(uee);
                }
                new StartupException(e).printStackTrace(ps);
                ps.flush();
                try {
                    logger.error("Guice Exception: {}", os.toString("UTF-8"));
                } catch (UnsupportedEncodingException uee) {
                    assert false;
                    e.addSuppressed(uee);
                }
            } else if (e instanceof NodeValidationException) {
                logger.error("node validation exception\n{}", e.getMessage());
            } else {
                // full exception
                logger.error("Exception", e);
            }
            // re-enable it if appropriate, so they can see any logging during the shutdown process
            if (foreground && maybeConsoleAppender != null) {
                Loggers.addAppender(rootLogger, maybeConsoleAppender);
            }

            throw e;
        }
    }
```

详细流程如下：

1. **INSTANCE = new Bootstrap();**, 创建Bootstrap对象实例，该类构造函数会创建一个用户线程，添加到Runtime Hook中，进行 countDown 操作
    
    > [CountDownLatch](https://www.jianshu.com/p/4b6fbdf5a08f)是一个同步工具类，它允许一个或多个线程一直等待，直到其他线程执行完后再执行。例如，应用程序的主线程希望在负责启动框架服务的线程已经启动所有框架服务之后执行。
2. **loadSecureSettings(initialEnv);**:加载 keystore 安全配置，keystore文件不存在则创建，保存；存在则解密，更新keystore
3. **createEnvironment**：根据配置，创建Elasticsearch 运行的必须环境
4. **setNodeName**：设置节点名称，这个在日志中打印的时候会使用
5. **LogConfigurator.configure(environment);**：根据``log4j2.properties``配置文件加载日志相关配置
6. **PidFile.create(environment.pidFile(), true);**：检查PID文件是否存在，不存在则创建，同时写入程序进程ID
7. **checkLucene** 检查Lucene jar包版本
8. **setDefaultUncaughtExceptionHandler**：设置程序中产生的某些未捕获的异常的处理方式
    
    > [UncaughtExceptionHandler](https://blog.csdn.net/u013256816/article/details/50417822):在多线程中，有时无法捕获其他线程产生的异常，这时候需要某种机制捕获并处理异常，UncaughtExceptionHandler就是来做这件事情的
9. **INSTANCE.setup(true, environment);** 为创建Node对象做准备，并最终创建Node对象
10. **INSTANCE.start();** 启动Node实例

### 三、**org.elasticsearch.node.Node** 启动单机节点，创建keepAlive线程

在第二个阶段中的最后两步都就是和创建节点相关的。

#### 1、 **INSTANCE.setup(true, environment);** 为创建Node对象做准备，并最终创建Node对象

在**Bootstrap.init**中调用该方法。

setup方法如下：

```java

    private void setup(boolean addShutdownHook, Environment environment) throws BootstrapException {
        Settings settings = environment.settings();

        try {
            spawner.spawnNativeControllers(environment);
        } catch (IOException e) {
            throw new BootstrapException(e);
        }

        initializeNatives(
                environment.tmpFile(),
                BootstrapSettings.MEMORY_LOCK_SETTING.get(settings),
                BootstrapSettings.SYSTEM_CALL_FILTER_SETTING.get(settings),
                BootstrapSettings.CTRLHANDLER_SETTING.get(settings));

        // initialize probes before the security manager is installed
        initializeProbes();

        if (addShutdownHook) {
            Runtime.getRuntime().addShutdownHook(new Thread() {
                @Override
                public void run() {
                    try {
                        IOUtils.close(node, spawner);
                        LoggerContext context = (LoggerContext) LogManager.getContext(false);
                        Configurator.shutdown(context);
                    } catch (IOException ex) {
                        throw new ElasticsearchException("failed to stop node", ex);
                    }
                }
            });
        }

        try {
            // look for jar hell
            final Logger logger = LogManager.getLogger(JarHell.class);
            JarHell.checkJarHell(logger::debug);
        } catch (IOException | URISyntaxException e) {
            throw new BootstrapException(e);
        }

        // Log ifconfig output before SecurityManager is installed
        IfConfig.logIfNecessary();

        // install SM after natives, shutdown hooks, etc.
        try {
            Security.configure(environment, BootstrapSettings.SECURITY_FILTER_BAD_DEFAULTS_SETTING.get(settings));
        } catch (IOException | NoSuchAlgorithmException e) {
            throw new BootstrapException(e);
        }

        node = new Node(environment) {
            @Override
            protected void validateNodeBeforeAcceptingRequests(
                final BootstrapContext context,
                final BoundTransportAddress boundTransportAddress, List<BootstrapCheck> checks) throws NodeValidationException {
                BootstrapChecks.check(context, boundTransportAddress, checks);
            }

            @Override
            protected void registerDerivedNodeNameWithLogger(String nodeName) {
                LogConfigurator.setNodeName(nodeName);
            }
        };
    }
```

1. **spawner.spawnNativeControllers(environment);**：读取modules文件夹下的所有模块，遍历所有模块，为每个模块生成native Controller。
2. **initializeNatives(Path tmpFile, boolean mlockAll, boolean systemCallFilter, boolean ctrlHandler)**：初始化本地资源
    1. 如果是root用户，抛出异常
    2. 尝试启动 系统调用过滤器 system call filter
    3. 尝试调用mlockall
    4. 如果是Windows关闭事件监听器
3. **initializeProbes();** 初始化进程和系统探针。//TODO：这里探针的作用是？权限验证？
4. 添加一个ShutdownHook，当ES退出时用于关闭必要的IO流，日志器上下文和配置器等
5. **JarHell.checkJarHell(logger::debug);**：Checks the current classpath for duplicate classes
6. **IfConfig.logIfNecessary();**：Debug模式，打印IfConfig信息
7. **Security.configure()**:加载SecurityManager，权限验证
8. **new Node(environment)**：根据运行环境，创建Node对象

<br />

#### 1.1、**new Node(environment)** 创建Node对象

Node的创建过程很复杂，这里只大概说一下里面做了哪些事情，详细的过程还需读者细度源码。其部分代码如下：

```java

    /**
     * Constructs a node
     *
     * @param environment                the environment for this node
     * @param classpathPlugins           the plugins to be loaded from the classpath
     * @param forbidPrivateIndexSettings whether or not private index settings are forbidden when creating an index; this is used in the
     *                                   test framework for tests that rely on being able to set private settings
     */
    protected Node(
            final Environment environment, Collection<Class<? extends Plugin>> classpathPlugins, boolean forbidPrivateIndexSettings) {
        logger = LogManager.getLogger(Node.class);
        final List<Closeable> resourcesToClose = new ArrayList<>(); // register everything we need to release in the case of an error
        boolean success = false;
        try {
            Settings tmpSettings = Settings.builder().put(environment.settings())
                .put(Client.CLIENT_TYPE_SETTING_S.getKey(), CLIENT_TYPE).build();

            /*
             * Create the node environment as soon as possible so we can
             * recover the node id which we might have to use to derive the
             * node name. And it is important to get *that* as soon as possible
             * so that log lines can contain it.
             */
            boolean nodeNameExplicitlyDefined = NODE_NAME_SETTING.exists(tmpSettings);
            try {
                Consumer<String> nodeIdConsumer = nodeNameExplicitlyDefined ?
                        nodeId -> {} : nodeId -> registerDerivedNodeNameWithLogger(nodeIdToNodeName(nodeId));
                nodeEnvironment = new NodeEnvironment(tmpSettings, environment, nodeIdConsumer);
                resourcesToClose.add(nodeEnvironment);
            } catch (IOException ex) {
                throw new IllegalStateException("Failed to create node environment", ex);
            }
            if (nodeNameExplicitlyDefined) {
                logger.info("node name [{}], node ID [{}]",
                        NODE_NAME_SETTING.get(tmpSettings), nodeEnvironment.nodeId());
            } else {
                tmpSettings = Settings.builder()
                        .put(tmpSettings)
                        .put(NODE_NAME_SETTING.getKey(), nodeIdToNodeName(nodeEnvironment.nodeId()))
                        .build();
                logger.info("node name derived from node ID [{}]; set [{}] to override",
                        nodeEnvironment.nodeId(), NODE_NAME_SETTING.getKey());
            }


            final JvmInfo jvmInfo = JvmInfo.jvmInfo();
            logger.info(
                "version[{}], pid[{}], build[{}/{}/{}/{}], OS[{}/{}/{}], JVM[{}/{}/{}/{}]",
                Version.displayVersion(Version.CURRENT, Build.CURRENT.isSnapshot()),
                jvmInfo.pid(),
                Build.CURRENT.flavor().displayName(),
                Build.CURRENT.type().displayName(),
                Build.CURRENT.shortHash(),
                Build.CURRENT.date(),
                Constants.OS_NAME,
                Constants.OS_VERSION,
                Constants.OS_ARCH,
                Constants.JVM_VENDOR,
                Constants.JVM_NAME,
                Constants.JAVA_VERSION,
                Constants.JVM_VERSION);
            logger.info("JVM arguments {}", Arrays.toString(jvmInfo.getInputArguments()));
            warnIfPreRelease(Version.CURRENT, Build.CURRENT.isSnapshot(), logger);

            if (logger.isDebugEnabled()) {
                logger.debug("using config [{}], data [{}], logs [{}], plugins [{}]",
                    environment.configFile(), Arrays.toString(environment.dataFiles()), environment.logsFile(), environment.pluginsFile());
            }
            ...
        }
```

Node 实例化对象过程如下：

1. **new Lifecycle();**：生命周期Lifecycle设置为 初始化状态 INITIALIZED
2. **new NodeEnvironment(tmpSettings, environment, nodeIdConsumer);**：创建node运行环境
3. **JvmInfo.jvmInfo();**：读取JVM信息，Debug模式打印该信息
4. **new PluginsService(tmpSettings,environment.configFile(),environment.modulesFile(),environment.pluginsFile(),classpathPlugins);**：加载扩展服务PluginService，预加载模块、插件
5. **new LocalNodeFactory(settings, nodeEnvironment.nodeId());**：创建本地Node工厂
6. **Environment.assertEquivalent(environment, this.environment);**：保证启动过程中，配置没有被更改
7. **new ThreadPool()**：创建模块和插件的线程池
8. **new NodeClient**：创建Node客户端
9. 加载模块：集群管理，Indices（什么用处？）、搜索模块
10. **new MetaDataCreateIndexService()**：创建索引服务
11. **modules.createInjector();**：加载其他所有剩余模块并注入模块管理器中
12. **clusterModule.getAllocationService().setGatewayAllocator(injector.getInstance(GatewayAllocator.class));**：加载网关模块

<br />

##### 1.1.1、**new PluginsService** 如何加载模块和插件


在**new PluginsService**中有代码：**Set<Bundle> modules = getModuleBundles(modulesDirectory);**，用来加载模块和插件，跟进代码来到**org.elasticsearch.plugins.PluginsService#readPluginBundle**方法如下：

```java
    // get a bundle for a single plugin dir
    private static Bundle readPluginBundle(final Set<Bundle> bundles, final Path plugin, String type) throws IOException {
        LogManager.getLogger(PluginsService.class).trace("--- adding [{}] [{}]", type, plugin.toAbsolutePath());
        final PluginInfo info;
        try {
            info = PluginInfo.readFromProperties(plugin);
        } catch (final IOException e) {
            throw new IllegalStateException("Could not load plugin descriptor for " + type +
                                            " directory [" + plugin.getFileName() + "]", e);
        }
        final Bundle bundle = new Bundle(info, plugin);
        if (bundles.add(bundle) == false) {
            throw new IllegalStateException("duplicate " + type + ": " + info);
        }
        return bundle;
    }
```

其中的**info = PluginInfo.readFromProperties(plugin);**就是从指定目录加载模块或者插件，代码如下：

```java

    /**
     * Reads the plugin descriptor file.
     *
     * @param path           the path to the root directory for the plugin
     * @return the plugin info
     * @throws IOException if an I/O exception occurred reading the plugin descriptor
     */
    public static PluginInfo readFromProperties(final Path path) throws IOException {
        final Path descriptor = path.resolve(ES_PLUGIN_PROPERTIES);

        final Map<String, String> propsMap;
        {
            final Properties props = new Properties();
            try (InputStream stream = Files.newInputStream(descriptor)) {
                props.load(stream);
            }
            propsMap = props.stringPropertyNames().stream().collect(Collectors.toMap(Function.identity(), props::getProperty));
        }

        final String name = propsMap.remove("name");
        if (name == null || name.isEmpty()) {
            throw new IllegalArgumentException(
                    "property [name] is missing in [" + descriptor + "]");
        }
        final String description = propsMap.remove("description");
        if (description == null) {
            throw new IllegalArgumentException(
                    "property [description] is missing for plugin [" + name + "]");
        }
        final String version = propsMap.remove("version");
        if (version == null) {
            throw new IllegalArgumentException(
                    "property [version] is missing for plugin [" + name + "]");
        }

        final String esVersionString = propsMap.remove("elasticsearch.version");
        if (esVersionString == null) {
            throw new IllegalArgumentException(
                    "property [elasticsearch.version] is missing for plugin [" + name + "]");
        }
        final Version esVersion = Version.fromString(esVersionString);
        final String javaVersionString = propsMap.remove("java.version");
        if (javaVersionString == null) {
            throw new IllegalArgumentException(
                    "property [java.version] is missing for plugin [" + name + "]");
        }
        JarHell.checkVersionFormat(javaVersionString);
        final String classname = propsMap.remove("classname");
        if (classname == null) {
            throw new IllegalArgumentException(
                    "property [classname] is missing for plugin [" + name + "]");
        }

        final String extendedString = propsMap.remove("extended.plugins");
        final List<String> extendedPlugins;
        if (extendedString == null) {
            extendedPlugins = Collections.emptyList();
        } else {
            extendedPlugins = Arrays.asList(Strings.delimitedListToStringArray(extendedString, ","));
        }

        final String hasNativeControllerValue = propsMap.remove("has.native.controller");
        final boolean hasNativeController;
        if (hasNativeControllerValue == null) {
            hasNativeController = false;
        } else {
            switch (hasNativeControllerValue) {
                case "true":
                    hasNativeController = true;
                    break;
                case "false":
                    hasNativeController = false;
                    break;
                default:
                    final String message = String.format(
                            Locale.ROOT,
                            "property [%s] must be [%s], [%s], or unspecified but was [%s]",
                            "has_native_controller",
                            "true",
                            "false",
                            hasNativeControllerValue);
                    throw new IllegalArgumentException(message);
            }
        }

        if (esVersion.before(Version.V_6_3_0) && esVersion.onOrAfter(Version.V_6_0_0_beta2)) {
            propsMap.remove("requires.keystore");
        }

        if (propsMap.isEmpty() == false) {
            throw new IllegalArgumentException("Unknown properties in plugin descriptor: " + propsMap.keySet());
        }

        return new PluginInfo(name, description, version, esVersion, javaVersionString,
                              classname, extendedPlugins, hasNativeController);
    }

```

**PluginInfo**类有两个全局常量：

```java
    public static final String ES_PLUGIN_PROPERTIES = "plugin-descriptor.properties";
    public static final String ES_PLUGIN_POLICY = "plugin-security.policy";
```

这是两个配置模板，每个插件和模块都会按照**plugin-descriptor.properties**中的模板读取响应的配置：name、description、version、elasticsearch.version、java.version、classname、has.native.controller、require.keystore。用这些配置，最终封装成一个**PluginInfo**对象。最终返回给PluginsService的数据结构如下：``Set<Bundle(PluginInfo, path)>``


##### 1.1.2、**new ThreadPool()**：创建模块和插件的线程池


ES的线程池类型：

```java
    public enum ThreadPoolType {
        DIRECT("direct"),
        FIXED("fixed"),
        FIXED_AUTO_QUEUE_SIZE("fixed_auto_queue_size"),
        SCALING("scaling");

        private final String type;

        public String getType() {
            return type;
        }

        ThreadPoolType(String type) {
            this.type = type;
        }

        private static final Map<String, ThreadPoolType> TYPE_MAP;

        static {
            Map<String, ThreadPoolType> typeMap = new HashMap<>();
            for (ThreadPoolType threadPoolType : ThreadPoolType.values()) {
                typeMap.put(threadPoolType.getType(), threadPoolType);
            }
            TYPE_MAP = Collections.unmodifiableMap(typeMap);
        }

        public static ThreadPoolType fromType(String type) {
            ThreadPoolType threadPoolType = TYPE_MAP.get(type);
            if (threadPoolType == null) {
                throw new IllegalArgumentException("no ThreadPoolType for " + type);
            }
            return threadPoolType;
        }
    }
```

如上，四种类型分别为：

* fixed（固定）：fixed线程池拥有固定数量的线程来处理请求，在没有空闲线程时请求将被挂在队列中。queue_size参数可以控制在没有空闲线程时，能排队挂起的请求数
* fixed_auto_queue_size：此类型为实验性的，将被更改或删除，不关注
* scaling（弹性）：scaling线程池拥有的线程数量是动态的，这个数字介于core和max参数的配置之间变化。keep_alive参数用来控制线程在线程池中空闲的最长时间
* direct：此类线程是一种不支持关闭的线程,就意味着一旦使用,则会一直存活下去.

这一步当中，ThreadPool()创建了很多线程池，线程池的名称如下：

```java

    public static class Names {
        public static final String SAME = "same";
        public static final String GENERIC = "generic";
        public static final String LISTENER = "listener";
        public static final String GET = "get";
        public static final String ANALYZE = "analyze";
        public static final String INDEX = "index";
        public static final String WRITE = "write";
        public static final String SEARCH = "search";
        public static final String SEARCH_THROTTLED = "search_throttled";
        public static final String MANAGEMENT = "management";
        public static final String FLUSH = "flush";
        public static final String REFRESH = "refresh";
        public static final String WARMER = "warmer";
        public static final String SNAPSHOT = "snapshot";
        public static final String FORCE_MERGE = "force_merge";
        public static final String FETCH_SHARD_STARTED = "fetch_shard_started";
        public static final String FETCH_SHARD_STORE = "fetch_shard_store";
    }
```

参考[官方文档](https://www.elastic.co/guide/en/elasticsearch/reference/current/modules-threadpool.html)可以查看各个线程池的作用，线程池类型，线程数量，等待队列数量等。


#### 2、 **INSTANCE.start();** 启动Node实例


在**Bootstrap.init**中调用该方法。

完成上面的步骤之后，如果是控制台启动服务，可以再控制台看到输出如下：
![](/images/posts/2019/15549766091914.jpg)

如果看到日志：

```
[elasticsearch] [2019-04-09T20:01:12,428][INFO ][o.e.n.Node               ] [node-0] starting ...
```

就说明Node已经开始启动了。

Node 的启动步骤，大概做了这些事情：

1. 启动各种服务：
    * IndicesService：索引管理
    * IndicesClusterStateService：跨集群同步
    * SnapshotsService：负责创建快照
    * SnapshotShardsService：此服务在数据和主节点上运行，并控制这些节点上当前快照的分片。 它负责启动和停止分片级别快照
    * RoutingService：侦听集群状态，当它收到ClusterChangedEvent（集群改变事件）将验证集群状态，路由表可能会更新
    * SearchService：搜索服务
    * ClusterService：集群管理
    * NodeConnectionsService：此组件负责在节点添加到群集状态后连接到节点，并在删除它们时断开连接。 此外，它会定期检查所有连接是否仍处于打开状态，并在需要时还原它们。 请注意，如果节点断开/不响应ping，则此组件不负责从群集中删除节点。 这是由NodesFaultDetection完成的。 主故障检测由链接MasterFaultDetection完成。
    * ResourceWatcherService：通用资源观察器服务
    * GatewayService：网关服务
    * Discovery：节点发现？
    * TransportService：节点间数据同步网络服务
    * TaskResultsService：
    * HttpServerTransport：外部网络服务
2. 将node连接服务（NodeConnectionsService）绑定到集群服务上（ClusterService）
3. TransportService启动后，验证节点，验证通过后，改服务用于node间的数据同步提供网络支持
4. 开启线程，去探测发现是否有集群，有则加入集群，这里也会启动一个CountDownLatch进行等待，直到集群选举出master
5. 开启HttpServerTransport，接受外部网络请求

当看到控制台如下输出则说明该节点启动成功：

```
[elasticsearch] [2019-04-09T20:04:16,388][INFO ][o.e.n.Node               ] [node-0] started
```

## 总结

从上面的步骤可以看出Elasticsearch的单节点启动过程还是很复杂的，而且文章只是列出了大概的启动步骤，还有很多细节没有深挖，比如节点和集群的相互发现与加入，节点间的数据同步，集群master是如何选举的等。细节还需各位读者深读源码。

参考：[http://laijianfeng.org/2018/09/Elasticsearch-6-3-2-%E5%90%AF%E5%8A%A8%E8%BF%87%E7%A8%8B/](http://laijianfeng.org/2018/09/Elasticsearch-6-3-2-%E5%90%AF%E5%8A%A8%E8%BF%87%E7%A8%8B/)