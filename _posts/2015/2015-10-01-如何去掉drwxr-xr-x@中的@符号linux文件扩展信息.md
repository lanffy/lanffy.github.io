---
layout: post
title: "如何去掉drwxr-xr-x@中的@符号Linux文件扩展信息"
tags: [Linux, 扩展信息]
author_name: R_Lanffy
---

最近从朋友那里拷贝了文件，执行了下```ls -lart```

    drwxrwxrwx@ 10 rlanffy  staff   340B  3  6  2015 files
    -rwxrwxrwx@  1 rlanffy  staff   630B  6 10 17:22 vagrantup.sh
    -rwxrwxrwx@  1 rlanffy  staff   4.8K  8 12 14:17 Vagrantfile
    drwxr-xr-x@  3 rlanffy  staff   102B  8 14 12:10 .vagrant
    drwxrwxrwx@ 13 rlanffy  staff   442B  9 10 11:33 .git
    -rwxrwxrwx@  1 rlanffy  staff    12K  9 14 10:38 .DS_Store
    drwxrwxrwx@ 12 rlanffy  staff   408B  9 14 10:54 projects
    -rwxrwxrwx@  1 rlanffy  staff   163B  9 24 23:48 README.md
    drwxrwxrwx@  8 rlanffy  staff   272B  9 27 14:04 scripts

前面的权限控制符中有一个@符号，它包含了文件的扩展属性。

执行命令```ls -laeO@```,就可以看到更多相关信息，如下所示：

    ls -laeO@
    total 64
    drwxrwxrwx@ 11 rlanffy  staff  -   374  9 28 00:27 .
	    com.apple.metadata:kMDItemWhereFroms	   73
	    com.apple.metadata:kMDLabel_pzd6bqzzjka6orf7oyy665upy 57
    	com.apple.quarantine	   59
    drwx------+ 16 rlanffy  staff  -   544 10  1 19:50 ..
        0: group:everyone deny delete
    -rwxrwxrwx@  1 rlanffy  staff  - 12292  9 14 10:38 .DS_Store
	    com.apple.FinderInfo	   32
    drwxrwxrwx@ 13 rlanffy  staff  -   442  9 10 11:33 .git
	    com.apple.quarantine	   59
    drwxr-xr-x@  3 rlanffy  staff  -   102  8 14 12:10 .vagrant
	    com.apple.quarantine	   59
    -rwxrwxrwx@  1 rlanffy  staff  -   163  9 24 23:48 README.md
	    com.apple.quarantine	   59
    -rwxrwxrwx@  1 rlanffy  staff  -  4904  8 12 14:17 Vagrantfile
	    com.macromates.crc32	    8
	    com.macromates.selectionRange	    5
	    com.macromates.visibleIndex	    1
    drwxrwxrwx@ 10 rlanffy  staff  -   340  3  6  2015 files
	    com.apple.quarantine	   59
    drwxrwxrwx@ 12 rlanffy  staff  -   408  9 14 10:54 projects
	    com.apple.quarantine	   59
    drwxrwxrwx@  8 rlanffy  staff  -   272  9 27 14:04 scripts
	    com.apple.quarantine	   59
   
想要去掉这个@符号，执行下面的命令即可：

```sudo xattr -d -r com.macromates.selectionRange ./*```

    其中－d就表示删除扩展属性的意思，-r 表示遍历文件夹中的文件，若权限控制符中有@也会去掉。
    右面的域名com.macromates.selectionRange为想要删除的相关信息。
