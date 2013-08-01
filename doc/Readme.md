# DXINFO for TP 基础模块 #

## 功能介绍 ##
1. 快速构建 数据列表、新增、修改、查询 等数据操作基本功能
2. 数据权限控制

## 总体说明 ##
系统扩展了TP的Model类、Action类，增加了一些基础功能，来完成一些通用的功能。

引入组件：

1. sigma grid 数据列表，特色：自定义Grid头，分组显示数据，前端排序，列锁定
2. formvalidator 字段规则验证
3. min js和css合并压缩，后端完成
4. artDialog5 js对话框
5. bootstrap 全局风格
6. DatePicker 日期选择组件
7. jquery-upload-file 带进度条的文件上传
8. zTree 树

## 使用 ##
1. 将组件复制到Web服务器，并将DxWebRoot配置到Web服务器某目录
2. TP项目的index.php文件增加 define('DXINFO\_PATH','/job/DxInfo')；配置目录所在的路径
3. 拷贝doc目录下的config.php到项目应用的Conf目录下。
4. 拷贝doc目录下的alias.php文件到项目应用的Conf目录下
5. 修改项目的config.php,修改 DX_PUBLIC 为DxWebRoot的Web路径
6. 项目的Action继承DataOpeAction
7. 项目的Model继承DxExtCommonModel

## 目录结构
1. doc 系统文档
2. DxTpl 公共的TP模板文件
3. DxWebRoot 公共的js和css文件。其中 basic 为模块所属的文件，min为js、css压缩组件，public为引入的第三方组件（`不允许修改public目录的任何文件，可采用功能覆盖的方式改写其行为`）
4. DxWidget 框架提供的Widget插件
5. Vendor 框架引入的第三方PHP组件
6. php文件，框架的基本文件，通过TP的 alias.php，别名自动引入，所以一般都以 .class.php 为扩展名


## 组件基本功能 ##
### TP auto功能扩展 ###
效果：将用户登录的Session值自动添加到某个字段，比如：create\_userid

原理：在 DxExtCommonModel 的构造函数中，解析配置，将有效配置添加到TP的 _auto 中，如果Model有配置的字段，自动加到_auto中，如果没有则忽略

例子：
    
	'DP_POWER_FIELDS' => array (
        array (
            'field_name' => 'create_userid',
            'auto_type' => 1,
            'type' => 0,
            'session_field' => "_id" 
        )
    )

DP_POWER_FIELDS:TP的配置字段，数组形式，每个配置项作为数组的一个值

field_name：_auto要自动填充的数据库字段名

auto_type：TP的 _auto 类型，1:INSERT 2:UPDATE 3:BOTH，但是在配置文件中不能使用Model常量，所以只能用 1、2、3

type：DxExtCommonMode增加3个常量，3个类型都会增加到 _auto 中

- const DP_TYPE_ENABLE = 1;//数据权限控制字段，不仅增加到 _auto 中，同时作为 SELECT 注入的字段
- const DP_TYPE_PUBLIC = 2;//数据权限控制，不仅增加到 _auto 中，同时作为 SELECT 注入的字段，数据的此字段等于1，则此数据所有人都可查看到，比如：全系统共享的公司手册
- const DP_TYPE_STATIC_AUTO = 4;	//\_auto的静态设定，自动填充，不使用Session值，而是用 auto_type 配置固定值，比如：同步状态要求，新增时全为1，更新时全为4

session_field：填充字段的session下标，留空则使用 field_name 为下标

operator：对于自动填充无效，用户数据过滤时，确定过滤的方法，默认为“like”，可选“eq”

### 数据权限控制 ###
效果：
1. 郑州市的员工只能查看郑州的数据，洛阳的员工只能查看洛阳的数据，河南省的员工能查看全省数据

原理：

1. 每个用户增加字段 canton\_fdn，表示自己所处的区域
2. 在每个需要数据权限控制的表中增加字段 canton\_fdn（此值通过auto扩展，自动追加到每个用户添加的数据中，谁创建的数据，此数据属于创建人所在的区域）
3. 查询数据时，自动注入本人的 canton\_fdn 到查询语句中

配置：

1. 超级人员不进行数据权限控制，设置其 Session["DP_ADMIN"] = true
2. 某些Module、Action不进行数据权限控制，'DP_NOT_CHECK_ACTION'	=> array("MODULE"=>array("Action"),"MODULE"=>1);比如：Public、SyncData等Module，Module等于1，则此Module的全部Action都不进行数据权限控制

例子：

- 郑州的canton\_fdn为：03520.01673.01674.
- 开封的canton\_fdn为：03520.01673.01688.
- 河南省的canton\_fdn为：03520.01673.
- 河南省员工创建的所有数据的canton\_fdn为：03520.01673.
- 郑州员工创建的所有数据的canton\_fdn为：03520.01673.01674.
- ...
- 自动注入的SQL条件为 canton\_fdn LIKE '查看人员的canton\_fdn%'

### 增删改查自动实现：listFields属性详解 ###
效果：通过配置Model，完成基本的增、删、改、查功能。

配置：

1. Model 继承 DxExtCommonModel，即便是没有任何属性方法的Model，也需要继承。否则TP创建的Model实例来自于TP的Model。
2. 配置Model的listFields属性、modelInfo属性
3. 在Action中动态修改 listFields属性、modelInfo属性，实现界面显示的动态化，比如：根据用户的不同选择，显示或隐藏某个字段

listFields属性详解：

1. type:字段类型，string、int、float、t
2. ime[时间]、date[日期]、y\_m(只显示年份和月份)、datetime[日期时间]、enum[枚举,单选框]、select[枚举,下拉框]、set[集合]、uploadFile[文件上传]、canton[区域]、cutPhoto[剪切头像]

		type用于3个地方：
		1. grid生成时，sigma支持 string int float（排序结果不同）
		2. 数据新增和修改时，生成不同的样式（input输入框、时间选择框、下拉选择框、单选框等）
		3. 查询框中也可以使用这些属性生成

		uploadFile的附属参数:"upload"=>array("filetype"=>".gif、.jpeg、.jpg、.png、.pdf、.doc、.xls、.mp4、.mov","maxNum"=>0,"buttonValue"=>"文件上传","maxSize"=>1024*1024)),
    	uploadFile存储的数据不能直接显示，在model中配置 'data_change'=>array("file_name"=>"uploadFilesToGrid"), 指定字段进行内容转义
        set的附加参数:valFormat=json,douhao   分别表示，以json 或 逗号隔开 存储多选数据
2. title:中文标题
3. hide:是否在前台生成此字段（此值使用位运算）。见常量 HIDE\_FIELD\_*，确定字段在某个场景下不生成html。如果在多个位置不生成，等于各个值的和。比如：列表新增都不生成则为3
4. display\_none:是否在界面上显示，hide控制是否在前端生成此html，display\_none控制是否显示此html。有些字段，需要在新增、修改中存在，但是用户不可见
5. pk:重定义主键字段。
6. width:输入框宽度，查询框的宽度
7. textTo:将某个字典关联字段的id值对应的数据保存到某字段，比如：设置到org\_id上的textTo="org\_name", 表示，存储org\_id对应的机构名称到org\_name，org\_name为冗余字段，主要方便实现，通过 机构名称 模糊查询
8. valChange:数据转换，将表存储的key转换为对应的vaule，将userid转换显示为username，[固定转换\关联表转换\SQL语句转换]，比如：

	     "valChange"=>array("1"=>"客户",'4'=>'超级管理员')  将1显示为客户，4显示为超级管理员
	     "valChange"=>array("model"=>"user","type"=>"basic_data_type") 注意:这里对应的model必须设置modelInfo的dictTable值才有效，原理：从数据库获取数据（modelInfo的dictTable决定字段），生成和上面一样的数据格式，树状数据时启用type属性,使用逗号隔开，取多维数据的具体值
     	 "valChange"=>array("sql"=>"select xxx,yyy,zzz from user")，使用SQL语句替代上面的Model，增加灵活性     	      	 

9. total:数据总计的字符覆盖，某些字段不需要总机结构，比如：区域，则设置此值，替代统计行的显示，一般设为空，但必须设置，不设置则使用计算结构显示
10. danwei:数据框后面的字符描述，一般是单位，也可以是说明
9. frozen:是否锁定列（sigma相关）
10. grouped:字段是否进行分组合并显示（sigma相关）
11. renderer:数据转换，此处为js代码（sigma相关），比如：
	
		var valChange=function valChangeCCCC(value ,record,columnObj,grid,colNo,rowNo){ var valChange=%s;return valChange[value];}
12. field:字段实例，可以使用函数改变字段值，例如：DATE_FORMAT(subsidy_end_date,"%Y-%m") subsidy_end_date        解决带函数字段问题。
13. editor:数据添加、修改时，自定义输入框。例子（实现selectselect功能）:

		 "editor"=>"<div id='selectCanton'></div> 																			<input type='hidden' id='canton_fdn' name='canton_fdn' value='' /> 		     							<input type='hidden' id='canton_id' name='canton_id' value='' />
					 <script type='text/javascript'>
					 (function($){
					 	$.selectselectselect(0,'selectCanton',0,ROOT_CANTON_ID,function(t){
					 	$('#canton_id').val($(t).find('option:selected').attr('key'));
					 	$('#canton_fdn').val($(t).val());
					 	});
					 })(jQuery);
					 </script>
					 ",
14. readOnly:字段数据为只读....一种情况：字典表的维护，添加时需要类型显示为不能改，但是又需要将类型值追加到数据中，则再model中重新定义save方法，如果是新增，则将type字段readOnly改为false,注：readOnly的字段，还是会从前端post过来，但是在后端会被忽略掉
15. default:默认值(String|Array), 
	
		字符串:直接做为默认值. 为防止数据出现混乱,默认值在readonly及编辑时不生效.
		数组:最少两个元素,第一个元素表示值的类型,为func时,第二个值为函数名,之后的数据为函数的参数.如array('func', 'test', 'p1', 'p2'),则调用test('p1', 'p2');

例子：参考Demo程序

### 导出为Excel ###
listFields的hide属性，来确定导出字段

### 数据打印 ###
listFields的hide属性，来确定打印字段，使用了打印组件：[Lodop]("http://mtsoftware.v053.gokao.net/download.html")
    	 
### modelInfo属性详解 ###
1. title:中文说明
2. addTitle:新增按钮的中文说明,默认为：新增+title内容
3. editTitle:修改的中文说明，默认为：修改+title内容
4. readOnly:本Model是否不需要新增数据
5. otherManageAction:本model除新增外的其他操作
6. searchHTML:操作框的html信息，一般为搜索框和查询按钮，搜索框支持的特性：
	
		1.id对应model数据字段 
		2.class="dataOpeSearch"表示是查询条件
		3.class="likeLeft likeRight"表示模糊查询的左相似或右相似
		4.支持radio类型input
		5.查询按钮执行js函数触发查询 dataOpeSearch(是否使用条件)
		6.支持model字段生成输入框，例如：区域选择框。{$editFields.canton_fdn|DxFunction::getFieldInput=###,array(),0,true}  直报、实地检查应用
        例子:
	    姓名:<input id='name' class='likeLeft likeRight dataOpeSearch' value='' />
	    性别:{:DxFunction::W_FIELD(\"FormEnum\", array(\"fieldSet\"=>\$listFields['sex']))}
		<input onclick='javascript:dataOpeSearch(true);' type='button' class='d-button d-state-highlight' value='查询' id='item_query_items' />
		<input onclick='javascript:dataOpeSearch(false);' type='button' class='d-button d-state-highlight' value='全部数据' id='item_query_all' />
		<input onclick='javascript:dataOpeExport(false);' type='button' class='d-button d-state-highlight' value='导出' id='item_export' />
7. dictTable:字典表值字段,格式1. dictTable="title"（生成以主键为key，值字段数据为值的 数组) 格式2:dictTable="keyField,keyField,..,valueField");(其中keyField是作为数据关联的字段)   根据这个配置，程序自动将字典表转换为valChange的普通模式，这些数组会被缓存到Runtime得Data目录
8. dictType:字典表的类型，可以是 "mySelf" 和 公共(默认)。公共的字典缓存是大家共享的，比如：老人类型，私有的缓存是各自单独存放，比如:职工信息,每个养老院添加老人选择护理员时，只选自己的职工
9. toString:提供给toString方法，整合数据的个是，   toString=array("%s %s 生于%s",array("real\_name","sex","birthday"))
10. helpInfo:帮助提示信息，sigma grid底部
11. data\_change:数据在后台就进行数据字典转换，尽量少用，valChange是将数据转换的工作交给js，减少后台php的执行时间，但是某些特殊转换无法使用valChange完成，则可以使用data\_change，在后台获取到数据后，调用函数对数据进行转换，这样会耗费大量的php执行，离子：补贴状态转换 'data\_change'=>array("aysn\_state"=>"subsidyStateChange"),
12. enablePage:grid是否提供分页。（sigma相关）
13. enablePrint:grid是否提供打印
13. gridHeader:自定义报表的个性化表头，不要写table标签，只写TR标签即可。系统会自动追加Table标签。（sigma相关）
14. order:默认的数据排序（sigma相关）
15. hasCheckBox:数据列表是否有checkbox（sigma相关）
16. total:是否在数据最后一行，增加总计行
17. leftArea:左边增加的内容，比如：左边可以加一个区域树等，此变量为html代码
	
### 全文索引 ###
效果：通过一个查询框，能够查询多个Model的主要属性，比如：机构名称、老人名称、员工名称等。类似google的效果。

1. 配置各个Model的toString，则此Model将进入全文索引支持
2. 在需要查询所有的地方，直接查询Model：FulltextSearch，表结构为：fulltext_search(object,pkid,content,object_title)
3. 全局配置变量  FULLTEXT_SEARCH  来开启全文索引功能

原理：在Model的insert update delete方法中，更新全文检索表数据。

### 标记删除 ###
效果：将数据做删除标记，而不进行物理删除，但前台依然无法查看此数据

配置：DELETE_TAGS => array("field"=>value,"field"=>value)，field为删除标记的字段名，value为标记为删除的值。注意：任何一个字段标记为删除值，就表示已删除（同步删除标记 和 系统删除标记 共存的需要）
	
### TP的数据验证规则自动转换为Js验证 ###
效果：后台书写TP的数据验证规则，前台js自动有此验证规则。

组件：http://www.position-relative.net/creation/formValidator/
	
	注意：
	前端验证的提示信息，是在js中指定的。来自于框架的定义，无法变动。。比如：唯一性，统计是：* 此处不可为空;
	框架不支持，对每个input指定，不同的必填说明。
	所以，后台验证规则的错误描述语言应尽量与前台保持一致。参考：jquery.validationEngine-zh_CN.js

### 操作日志 ###
效果：系统自动记录操作日志到表operation_log中，表格式固定

### 数据变更日志 ###
效果:数据变动日志，系统自动记录数据变动日志到表DataChangeLog中，表格式固定，目前问题：时间不长，表就较大了。

### 自定义Action的Model ###
1. 支持表关联，通过设置Model的变量：protected $viewTableName ='(SELECT e.*,eg.name group_name FROM employee e LEFT JOIN employee\_group\_member egm ON egm.employee\_id=e.employee\_id LEFT JOIN employee\_group eg ON eg.employee\_group\_id=egm.employee\_group\_id) emp'; 来支持多表连结，不用创建视图，写多个Model了。
2. Action使用非同名Model，通过设置Action的变量：theModelName，来指定此Action使用的Model

### 自定义布局 ###
复制组件DxTpl下的模板到项目中，人工修改，可以实现自定义布局，比如：重新排列新增页面的组件等。

### 数据过滤 ###
通过URL传递过滤项，比如：SysDic的实现，通过在URL（SysDic/index/type/SubsidyRank/modelTitle/补贴类型）增加type（过滤数据） modelTitle（改变页面标题：SysDic对应的标题应该是数据字典），可以将一个Model分隔为不同的功能：员工类型管理、房间类型管理等。

系统支持，一次性过滤（默认），固定化过滤（url增加此参数InitSearchPara=1）

## 其他功能扩展 ##
原理：在组件中实现基础的功能Action和Model，让项目去继承使用，比如：角色管理、用户管理、登录等。

## 消息提示 ##
在Action中使用 $this->success("消息内容","showmsg");会出现提示信息，并不跳转页面。注意：如果需要结束执行代码，则在此语句随后增加 exit; 语句

*die显示的内容，会因为编码问题，在浏览器上显示为乱码

## 常见问题 ##
1. Model:getCacheDictTableData您所请求的方法不存在！ 原因：1.没有定义字典表的Model 2.使用的model中的"valChange"=>array("model"=>"Role")中的model要使用大写，例如Role，不能写作role。
2. ModeI变量listFields的改变，必须在构造函数中, 使用setListField完成。因为系统会缓存ListFieIds数据

## 新手注意事项
1. 除非框架本身无法支持的功能，请勿随意在项目中重写 add.html data_edit.html data_list.html 各种父类的方法（DxExtCommonAction、DxExtCommonModel）
2. 需要增加功能时，请使用类继承的方式，重写Action或Model的方法，请勿随意改动框架代码（框架代码仅支持公共功能）

## 版本历程
1. 0.1版:为了实现简单代码重用和客户自定义界面，构建了FormAuto
2. 0.2版:将功能限定为简单代码重用，构建DxExtForTP雏形
3. 0.3版:将分散的各个模块文件集中在一个独立的项目中，与实际项目隔离（1.对模块实现源码控制 2.简单的项目引用 3.简单的代码升级）