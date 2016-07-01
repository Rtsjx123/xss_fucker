# xss_fucker

### **xss_fucker**是一个HTML的过滤器，用于在某些必须开放用户使用HTML，但又不想让用户插来插去最终身体被掏空的地方。

目前，**xss_fucker**是由**世界上最好的语言PHP**写成，经过测试可以很好地支持PHP7，且无需复杂的依赖组件。

过滤器的工作实体是名为htmlFilter的类，该类释出三个方法：

`safeHTML($html_string)`
`setAutoClosing($switch)`
`getLastError($intext=false)`

其中：

`safeHTML`方法接受一个字符串参数，表示需要过滤的字符串；返回值为string，表示已经完成过滤的字符串。

`setAutoClosing`方法接受一个布尔类型参数，表示**【是否在输入数据中无法找到任意有效HTML标签时尝试自动闭合】**，自动闭合开启可能会造成一定程度的误报，但关闭自动闭合则会产生某些特定条件下的漏报。（详见代码）

`getLastError`方法并没有什么卵用，我只是觉得声明它会显得很牛逼。

一个常见的使用例子已经在[`example.php`](example.php)：

``` php
include_once 'htmlFilter.php';

header("Content-Type: text/html;charset=utf8");

$test = <<<'EOF'
<script>alert(/xss1/);</script>
<img src=x onerror=alert(/xss2/) />
<input type="text" style="width: 100%; height: 100%;" onmouserover='alert(/xss3/)' value="click me" />
EOF;

$hf = new htmlFilter();

echo $hf->safeHTML($test);

```

> **注意：**目前对基于CSS表达式的XSS载荷尚无过滤功能，正在编写相关逻辑。
