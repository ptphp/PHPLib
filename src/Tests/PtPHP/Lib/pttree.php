<?php
include "PtPHP/Lib/PtTree.php";
$arr = array(
	       1 => array('id'=>'1','pid'=>0,'name'=>'一级栏目一'),
	       2 => array('id'=>'2','pid'=>0,'name'=>'一级栏目二'),
	       3 => array('id'=>'3','pid'=>1,'name'=>'二级栏目一'),
	       4 => array('id'=>'4','pid'=>1,'name'=>'二级栏目二'),
	       5 => array('id'=>'5','pid'=>2,'name'=>'二级栏目三'),
	       6 => array('id'=>'6','pid'=>3,'name'=>'三级栏目一'),
	       7 => array('id'=>'7','pid'=>3,'name'=>'三级栏目二')
	       );

$tree = new PtTree($arr);

echo "<select>";
echo($tree->get_tree(0, "<option value=\$id \$selected>\$spacer\$name</option>",3));
echo "</select><hr>";
echo "<pre>";
print_r(PtTree::genTreeArr($arr));
echo "</pre><hr>";
highlight_file(__FILE__);
