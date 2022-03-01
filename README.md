# autodealimg
在线处理图片，缩略，背景色，水印<br/>


请求格式： 图片url?长X宽m[模式数值]bc[颜色值]w[水印位置值]-[水印透明度]-水印图片网络url<br/>
实例 : http://xxxx.jpg?900x900m3bc200,120,50w5-7-https://www.baidu.com/img/PCtm_d9c8750bed0b3c7d089fa7d55720d6cf.png<br/>

模式说明:<br/>
m1 : 固定大小缩略,图可能会有所变形<br/>
m2 : 等比例缩放<br/>
m3 : 缩放填充<br/>
m4 : 从左上角开始裁减指定宽高<br/>

水印位置值 ： <br/>
1 ：左上角水印<br/>
2 ：上居中水印<br/>
3 ：右上角水印<br/>
4 ：左居中水印<br/>
5 ：居中水印<br/>
6 ：右居中水印<br/>
7 ：左下角水印<br/>
8 ：下居中水印<br/>
9 ：右下角水印<br/>

水印透明度：<br/>
1-9 数值越大越明显<br/>

bc200,120,50<br/>
填充图片的背景颜色<br/>




