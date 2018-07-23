# PHP mfenc Decompiler

PHP mfenc 反编译器

目前不保证反编译结果的正确性，仅供参考。

反汇编和结构化之后的汇编指令应该没什么问题。

## Usage

```php
use Ganlv\MfencDecompiler\AutoDecompiler;
use Ganlv\MfencDecompiler\Helper;

require __DIR__ . '/../vendor/autoload.php';

file_put_contents(
    $output_file,
    Helper::prettyPrintFile(
        AutoDecompiler::autoDecompileAst(
            Helper::parseCode(
                file_get_contents($input_file)
            )
        )
    )
);
```

## Source Files

```text
DfsDisassembler.php  主反汇编器（DFS算法）
Disassembler1.php    一级指令反汇编器
Disassembler2.php    二级指令反汇编器
instructions.php     二级指令匹配列表

GraphViewer.php                       反汇编指令列表->有向图转换器
DirectedGraph.php                     有向图类
DirectedGraphSimplifier.php           用于简化有向图的抽象类
DirectedGraphSimpleSimplifier.php     简单地合并1进1出和没有指令的节点
DirectedGraphStructureSimplifier.php  分析流程结构生成if、loop、break等语句

BaseDecompiler.php  基础反编译器
Decompiler.php      反编译指令
Beautifier.php      反编译后代码美化

VmDecompiler.php    自动将从ast中找到VM，并对其进行反编译的类
AutoDecompiler.php  全自动反汇编器

Helper.php                       助手函数
Formatter.php                    测试过程中用于把乱码变量名替换成英文
instructions_display_format.php  指令翻译
```

## LICENSE

暂时保留所有权利

All Right Reserved.
