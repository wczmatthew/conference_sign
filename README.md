﻿编码设计思路：

系统可按月 按周 按日展示
	实例： 	  2017-11  |   2017-12  |   2018-01
			 2017 46周 |  2017 47周 |  2017 48周
			2017-11-29 | 2017-11-30 | 2017-12-01
	如果在 config.php 中配置了item_interval，即条目的间隔值
	按月 item_interval 设置为12，即每隔12小时为一条目，列表的左边名称 1日00点 ~ 1日12点 ......
	按周 item_interval 设置为8， 即每隔 8小时为一条目，列表的左边名称 星期1 00点 ~ 星期1 08点 ~ 星期1 16点 ......
	按日 item_interval 设置为30，即每隔30分钟为一条目，列表的左边名称 08:00 ~ 08:30 

1.先查询出对应tab的申请列表，比如按月有一个申请 2017-11-28 08:00 ~ 2018-01-08 23:00
2.检查该申请在该tab下的起止值，因为存在4种情况
 	 *  1.  |___*****___|   => |___*****___|
     *  2.  **|***______|   => |***______|   2018-01-01 00:00 ~ 2018-01-08 23:00
     *  3.  |______***|**   => |______***|__ 2017-11-28 08:00 ~ 2017-11-30-23:59
     *  4.  ***|*****|***   => ___|*****|___ 2017-12-01 00:00 ~ 2017-12-31 23:59
3.根据每隔申请在该tab下的起止值，计算在各item下的height top值
4.listPage进行展示，对循环进行适配