# ZDArchive
做着玩的 PHP 简易压缩软件

## Features
- 采用哈夫曼压缩算法
- 也应用了 GZIP 压缩，对文本类文件进行优化
- 支持 AES 加密，可自定义加密算法

## Preview
| 文件类型 | 压缩前大小 | 压缩后大小 | ZIP 压缩后大小 |
| :-----: | :-------: | :-------: | :-----------: |
| TXT 重复文本 | 260KB | 37KB | 22KB |
| TXT 随机文本 | 280KB | 105KB | 91KB |
| EXE 文件 | 4,420KB | 4,377KB | 4,322KB |
| JPG 文件 | 22,210KB | 21,103KB | 20,787KB |

## Usage
参考 `example.php` 内容。

**基本压缩文件**
```php
$file = file_get_contents("104.jpg"); // 取得源文件内容
$zda->compress($file, "104.zda");     // 压缩并写入新文件
```

**基本解压文件**
```php
$file   = file_get_contents("104.zda"); // 得到压缩后的文件内容
$decomp = $zda->decompress($file);      // 解压
file_put_contents("104.jpg", $decomp);  // 写入新文件
```

## TODO
- 支持压缩文件夹
- 支持压缩包注释
- 优化压缩效率

## Credits
哈夫曼算法：https://juejin.cn/post/6844903988282785799

参考代码：https://www.jianshu.com/p/62d755548d7b

## License
MIT 协议开源
