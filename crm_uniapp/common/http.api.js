// 如果没有通过拦截器配置域名的话，可以在这里写上完整的URL(加上域名部分)
let wxlogin = 'index/wxlogin',// 微信登录
mplogin = 'index/mplogin',// 第三方微信公众号登录
init = 'login/config',//  基础配置
upload = 'crm.common/upload', // 上传文件
bind = 'index/bind',// 绑定原有账号
logout	= 'index/logout',// 退出登录
adminList = 'auth.admin/index',// 查看管理员列表
groupdata = 'auth.admin/getGroupdata',//获取员工组
adminAdd = 'auth.admin/add',//添加
adminEdit = 'auth.admin/edit',//编辑
adminDel = 'auth.admin/delete', // 删除
selectpage = 'auth.admin/selectpage',// 下拉搜素管理员
customerList = 'crm.customer/index',// 客户列表
violationList = 'violation/index',// 客户列表
violationMore = 'violation/more',// 客户列表
customerAdd = 'crm.customer/add',// 添加客户
applyCustomerAdd = 'apply.customer/add',// 添加客户
applyOrderAdd = 'apply.Order/add',// 添加客户
customerEdit = 'crm.customer/edit',// 编辑客户 "get是获取，post是修改，修改的内容参数参考电脑版"
applyCustomerEdit = 'apply.customer/edit',// 编辑客户 "get是获取，post是修改，修改的内容参数参考电脑版"
customerDel = 'crm.customer/delete',// 删除客户
common = 'crm.customer/seas',// 公海客户
discard = 'crm.customer.index/discard',// 放入公海
receive = 'crm.liberum/robClient',// 领取客户
divert = 'crm.customer/alter_pr_user',// 转移客户
contractlist = 'crm.customer.index/contractlist',// 合同列表
receivableslist = 'crm.customer.index/receivableslist', // 回款列表
openAdd = 'open.customer/add',// 添加客户
updateProfile = 'general.profile/update',// POST修改个人信息
profileGetInfo = 'general.profile/getInfo',// 获取个人信息
productUnit = 'crm.product.unit/index',// 商品-单位列表
productUnitEdit = 'crm.product.unit/edit',// 商品-编辑单位
productUnitAdd = 'crm.product.unit/add',// 商品-添加单位
productType = 'crm.product.type/index', // 商品分类列表
productTypeEdit = 'crm.product.type/edit',// 编辑商品分类
productTypeAdd = 'crm.product.type/add',// 添加商品分类
productList = 'crm.product.product/index',//  产品列表
productAdd = 'crm.product.product/add',// 添加产品
productEdit = 'crm.product.product/edit',// 编辑产品
violationEdit = 'violation/edit',// 编辑记录
violationAdd = 'violation/add',// 增加记录
productDel = 'crm.product.product/delete',// 删除产品
productTypeList = 'crm.product.product/get_type_list',// 获取商品类型列表
productTypeProp = 'crm.product.product/get_type_prop',// 获取当前商品选择类型的属性
productUnitList = 'crm.product.product/get_unit_list',// 获取单位列表
fields = 'fields/get_fields',// 获取自定义字段
home = 'crm.dashboard/index',// 获取首页数据
achievement = 'crm.dashboard/getAchievement',// 业绩筛选
record = 'crm.customer.record/index',// 客户跟进记录
recordAdd = 'crm.record/add',// 添加客户跟进记录
recordDel = 'crm.customer.record/delete',// 删除客户跟进记录
receivables = 'crm.contract.receivables/index',// 回款列表
receivablesAdd = 'crm.contract.receivables/receivablesadd',// 【我和下属】回款添加 get获取审批流程config为1是固定审批0是授权。flow_admin_id是固定审批时候的用户ID
receivablesEdit = 'crm.contract.receivables/edit', // 回款修改，post修改 ，get获取
receivablesDel = 'crm.contract.receivables/delete',// 回款删除
analysis = 'crm.analysis.admin/index', // 客户量分析
analysisRecord = 'crm.analysis.admin/record',// 跟进分析
achievementAnalysis = 'crm.analysis.admin/achievement',// 业绩分析
contractIndexList = 'crm.contract.index/lists',// 合同列表
contractAdd = 'crm.contract.index/contractadd',// 合同添加【我和下属】
contractEdit = 'crm.contract.index/edit',// 合同修改 get是获取, post修改
contractDel = 'crm.contract.index/delete', // 合同删除
contractSelectpage = 'crm.contract.index/selectpage', // 合同选择
commonSelectpage = 'ajax/selectpage',// 下拉选择（）
subscribe = 'crm.common/subscribe',//POST|GET是获取 消息订阅修改
businessRecord = 'crm.business.record/index',//[商机]跟进记录
businessRecordAdd = 'crm.business.record/add',//[商机]添加跟进
businessRecordDel = 'crm.business.record/delete',//[商机]删除跟进记录
businessList = 'crm.business.index/index',//商机列表
businessAdd = 'crm.business.index/add',//商机列表添加
businessEdit = 'crm.business.index/edit',//商机列表post修改，get获取
businessDel = 'crm.business.index/delete',//商机列表删除
businessContacts = 'crm.business.contacts/index',//联系人列表[商机]
contactsCorrelation = 'crm.business.contacts/correlation',// 关联联系人
businessContactsDel = 'crm.business.contacts/delete',// 删除关联联系人
contactsList = 'crm.customer.contacts/index',// 联系人列表
contactsAdd = 'crm.customer.contacts/add',// 添加联系人
contactsEdit = 'crm.customer.contacts/edit',// 修改联系人.get获取 post修改
contactsDel = 'crm.customer.contacts/delete',// 删除联系人
backlog = 'crm.backlog/index',//待审批列表
backlogVerify = 'crm.backlog/verify',// 审批 get是获取审批信息，post是审批
logList = 'crm.flow.log/index',// 获取审批日志
contractRanking = 'crm.analysis.ranking/contract',// 合同排行榜
receivablestRanking= 'crm.analysis.ranking/receivables',// 回款排行榜
customerRanking = 'crm.analysis.ranking/addCustomer',// 新客排名
recordRanking = 'crm.analysis.ranking/record',// 跟进排名
productRanking = 'crm.analysis.ranking/product',// 产品排名
customerIndex = 'crm.analysis.customer/index',// 客户地区分析
industry = 'crm.analysis.customer/industry',// 客户行业分析
level = 'crm.analysis.customer/level',// 客户级别分析
source = 'crm.analysis.customer/source',// 客户来源分析
area = 'crm.common/area',//读取省市区数据,联动列表
allarea = 'ajax/getAllArea',//获取所有省市区，children
baseConfig = 'crm.common/baseConfig',// 基础配置[客户等级、行业类型、来源等
getInfo = 'general.profile/getInfo',// 获取个人信息
customerSelectpage = 'crm.customer.index/selectpage', // 下拉选择客户
achievementIndex = 'crm.achievement/index', // 业绩列表
achievementAdmin = 'crm.achievement/admin',// 员工业绩列表
achievementEdit	= 'crm.achievement/edit',// POST 团队业绩编辑
achievementAedit = 'crm.achievement/aedit',// 成员业绩编辑
fieldsSelectpage = 'fields/selectpage', // 自定义字段关联表联动
unbind = 'index/unbind', // 解绑微信
noticeTpl = 'crm.common/getNoticeTpl', // 消息提醒模板
batchTeam = 'crm.achievement/batchTeam', // 团队批量设置业绩
batchAdmin = 'crm.achievement/batchAdmin', // 成员批量设置业绩
allAdmin = 'crm.common/selectpage/model/admin/type/all', // 所有员工
thirdlist = 'index/thirdlist', // 获取第三方列表
cluesList = 'crm.clues.index/index', // 获取线索列表
cluesAdd = 'crm.clues.index/add',// 添加线索
cluesEdit = 'crm.clues.index/edit', // 编辑线索，get获取 ，post修改
cluesDel = 'crm.clues.index/delete',// 删除线索
cluesTransform = 'crm.clues.index/transform',// 线索转化为客户
shiftDivert = 'crm.clues.index/divert',// 线索转移
cluesRecord = 'crm.clues.record/add', // 线索添加跟进
cluesRecordList = 'crm.clues.record/index', // 线索跟进数据
cluesCommon = 'crm.clues.index/common', // 公共线索池
cluesReceive = 'crm.clues.index/receive',// 线索领取
cluesDiscard = 'crm.clues.index/discard', // 线索丢弃
qywxH5 = 'qywx/login', // 企业微信H5登录
qywxMp = 'qywx/wxlogin', // 企业微信小程序登录
reduplicate = 'crm.customer.index/reduplicate', // 客户查重
sms = 'crm.apps.sms/selectpage', // 查看短信模板列表
emailList = 'crm.apps.email/selectpage', // 查看邮箱模板列表
sendSms = 'crm.apps.sms/send', // 发送短信
email = 'crm.apps.email/send', // post发邮件|get获取邮件模板
cloudcall = 'crm.setting.cloudcall/call',//云呼叫
cloudcallMode = 'crm.setting.cloudcall/setup',//云呼方式 POST是提交修改，GET获取数据
signin = 'crm.customer.record/signin', //外访签到跟进
payurl = 'crm.contract.receivables/payurl',// 生成在线收款单
geocoder = 'crm.common/geocoder', // 腾讯地图经纬度返回地址
renewPayurl = 'crm.contract.index/payurl',// 生成续费链接
invoiceList = 'crm.invoice/index', // 我的发票
allInvoice = 'crm.invoice/lists', // 全部发票
invoiceAdd = 'crm.invoice/add', // 申请发票 GET获取参数，【flow审核配置，setting发票配置】,POST提交申请，参数参考PC
invoiceHistory = 'crm.invoice/history', // 获取客户历史发票资料
invoiceEdit = 'crm.invoice/edit', // 修改发票资料
invoiceOpener = 'crm.invoice/opener', // 开具发票
invoiceDel = 'crm.invoice/delete', // 删除发票
getNotice = 'crm.dashboard/getNotice' // 首页通知提醒












// 此处第二个参数vm，就是我们在页面使用的this，你可以通过vm获取vuex等操作，更多内容详见uView对拦截器的介绍部分：
// https://uviewui.com/js/http.html#%E4%BD%95%E8%B0%93%E8%AF%B7%E6%B1%82%E6%8B%A6%E6%88%AA%EF%BC%9F
const install = (Vue, vm) => {
	// 此处没有使用传入的params参数
	let getSearch = (params = {}) => vm.$u.get(hotSearchUrl, {
		id: 2
	});
	// 此处使用了传入的params参数，一切自定义即可
	let onWxlogin = (params = {}) => vm.$u.post(wxlogin, params),
	onMplogin = (params = {}) => vm.$u.get(mplogin, params),
	onBind = (params = {}) => vm.$u.post(bind, params),
	getInit = (params = {}) => vm.$u.get(init, params),
	goUpload = (params = {}) => vm.$u.post(upload, params),
	onLogout = (params = {}) => vm.$u.get(logout, params),
	lookAdmin = (params = {}) => vm.$u.get(admin, params),
	getGroupdata = (params = {}) => vm.$u.get(groupdata, params),
	onAdminAdd = (params = {}) => vm.$u.post(adminAdd, params),
	onAdminEdit  = (params = {}) => vm.$u.post(adminEdit, params),
	getAdminEdit  = (params = {}) => vm.$u.get(adminEdit, params),
	onAdminDel = (params = {}) => vm.$u.get(adminDel, params),
	onSelectpage = (params = {}) => vm.$u.get(selectpage, params),
	getCustomerList = (params = {}) => vm.$u.get(customerList, params),
	getViolationList = (params = {}) => vm.$u.get(violationList, params),
	getViolationMore = (params = {}) => vm.$u.get(violationMore, params),
	onCustomerAdd = (params = {}) => vm.$u.post(customerAdd, params),
	onApplyCustomerAdd = (params = {}) => vm.$u.post(applyCustomerAdd, params),
	onApplyOrderAdd = (params = {}) => vm.$u.post(applyOrderAdd, params),
	getCustomer = (params = {}) => vm.$u.get(customerEdit, params),
	getApplyCustomer = (params = {}) => vm.$u.get(applyCustomerEdit, params),
	onCustomerEdit = (params = {}) => vm.$u.post(customerEdit, params),
	onApplyCustomerEdit = (params = {}) => vm.$u.post(applyCustomerEdit, params),
	onCustomerDel = (params = {}) => vm.$u.post(customerDel, params),
	getCommon = (params = {}) => vm.$u.get(common, params),
	onDiscard = (params = {}) => vm.$u.post(discard, params),
	getReceive = (params = {}) => vm.$u.post(receive, params),
	onDivert = (params = {}) => vm.$u.post(divert, params),
	getContractlist = (params = {}) => vm.$u.get(contractlist, params),
	getReceivableslist = (params = {}) => vm.$u.get(receivableslist, params),
	onOpenAdd = (params = {}) => vm.$u.get(openAdd, params),
	getProfile = (params = {}) => vm.$u.get(profileGetInfo, params),
	postProfile = (params = {}) => vm.$u.post(updateProfile, params),
	getProductUnit = (params = {}) => vm.$u.get(productUnit, params),
	onProductUnitEdit = (params = {}) => vm.$u.get(productUnitEdit, params),
	onProductUnitAdd = (params = {}) => vm.$u.post(productUnitAdd, params),
	getProductType = (params = {}) => vm.$u.get(productType, params),
	onProductTypeEdit = (params = {}) => vm.$u.get(productTypeEdit, params),
	onProductTypeAdd = (params = {}) => vm.$u.post(productTypeAdd, params),
	getProductList = (params = {}) => vm.$u.get(productList, params),
	onProductAdd = (params = {}) => vm.$u.post(productAdd, params),
	onProductEdit = (params = {}) => vm.$u.post(productEdit, params),
	onViolationEdit = (params = {}) => vm.$u.post(violationEdit, params),
	onViolationAdd = (params = {}) => vm.$u.post(violationAdd, params),
	getProductEdit = (params = {}) => vm.$u.get(productEdit, params),
	getViolationEdit = (params = {}) => vm.$u.get(violationEdit, params),
	getViolationAdd = (params = {}) => vm.$u.get(violationAdd, params),
	onProductDel = (params = {}) => vm.$u.get(productDel, params),
	getProductTypeList = (params = {}) => vm.$u.get(productTypeList, params),
	getProductTypeProp = (params = {}) => vm.$u.get(productTypeProp, params),
	getProductUnitList = (params = {}) => vm.$u.get(productUnitList, params),
	getFields = (params = {}) => vm.$u.get(fields, params),
	getHome = (params = {}) => vm.$u.get(home, params),
	getAchievement = (params = {}) => vm.$u.get(achievement, params),
	getRecord = (params = {}) => vm.$u.get(record, params),
	onRecordAdd = (params = {}) => vm.$u.post(recordAdd, params),
	onRecordDel = (params = {}) => vm.$u.get(recordDel, params),
	getReceivables = (params = {}) => vm.$u.get(receivables, params),
	onReceivablesAdd = (params = {}) => vm.$u.post(receivablesAdd, params),
	getReceivablesAdd = (params = {}) => vm.$u.get(receivablesAdd, params),
	onReceivablesEdit = (params = {}) => vm.$u.post(receivablesEdit, params),
	getReceivablesEdit = (params = {}) => vm.$u.get(receivablesEdit, params),
	onReceivablesDel = (params = {}) => vm.$u.post(receivablesDel, params),
	onAnalysis = (params = {}) => vm.$u.get(analysis, params),
	onAnalysisRecord = (params = {}) => vm.$u.get(analysisRecord, params),
	onAchievementAnalysis = (params = {}) => vm.$u.get(achievementAnalysis, params),
	getContractIndexList = (params = {}) => vm.$u.get(contractIndexList, params),
	onContractAdd = (params = {}) => vm.$u.post(contractAdd, params),
	getContractAdd = (params = {}) => vm.$u.get(contractAdd, params),
	onContractEdit = (params = {}) => vm.$u.post(contractEdit, params),
	getContractEdit = (params = {}) => vm.$u.get(contractEdit, params),
	onContractDel = (params = {}) => vm.$u.post(contractDel, params),
	onContractSelectpage = (params = {}) => vm.$u.get(contractSelectpage, params),
	onCommonSelectpage = (params = {}) => vm.$u.get(commonSelectpage, params),
	getSubscribe = (params = {}) => vm.$u.get(subscribe, params),
	postSubscribe = (params = {}) => vm.$u.post(subscribe, params),
	onBusinessRecord = (params = {}) => vm.$u.get(businessRecord, params),
	onBusinessRecordAdd = (params = {}) => vm.$u.post(businessRecordAdd, params),
	onBusinessRecordDel = (params = {}) => vm.$u.get(businessRecordDel, params),
	onBusinessList = (params = {}) => vm.$u.get(businessList, params),
	onBusinessAdd = (params = {}) => vm.$u.post(businessAdd, params),
	onBusinessEdit = (params = {}) => vm.$u.post(businessEdit, params),
	getBusinessEdit = (params = {}) => vm.$u.get(businessEdit, params),
	onBusinessDel = (params = {}) => vm.$u.get(businessDel, params),
	getBusinessContacts = (params = {}) => vm.$u.get(businessContacts, params),
	onContactsCorrelation = (params = {}) => vm.$u.post(contactsCorrelation, params),
	onBusinessContactsDel = (params = {}) => vm.$u.post(businessContactsDel, params),
	getContactsList = (params = {}) => vm.$u.get(contactsList, params),
	onContactsAdd = (params = {}) => vm.$u.post(contactsAdd, params),
	getContactsEdit = (params = {}) => vm.$u.get(contactsEdit, params),
	onContactsEdit = (params = {}) => vm.$u.post(contactsEdit, params),
	onContactsDel = (params = {}) => vm.$u.post(contactsDel, params),
	getBacklog = (params = {}) => vm.$u.get(backlog, params),
	onBacklogVerify = (params = {}) => vm.$u.post(backlogVerify, params),
	getBacklogVerify = (params = {}) => vm.$u.get(backlogVerify, params),
	getlogList = (params = {}) => vm.$u.get(logList, params),
	getContractRanking = (params = {}) => vm.$u.get(contractRanking, params),
	getReceivablesRanking = (params = {}) => vm.$u.get(receivablestRanking, params),
	getCustomerRanking = (params = {}) => vm.$u.get(customerRanking, params),
	getRecordRanking = (params = {}) => vm.$u.get(recordRanking, params),
	getProductRanking = (params = {}) => vm.$u.get(productRanking, params),
	getCustomerIndex = (params = {}) => vm.$u.get(customerIndex, params),
	getIndustry = (params = {}) => vm.$u.get(industry, params),
	getLevel = (params = {}) => vm.$u.get(level, params),
	getSource = (params = {}) => vm.$u.get(source, params),
	getArea = (params = {}) => vm.$u.get(area, params),
	getAllarea = (params = {}) => vm.$u.get(allarea, params),
	getBaseConfig = (params = {}) => vm.$u.get(baseConfig, params),
	onGetInfo = (params = {}) => vm.$u.get(getInfo, params),
	getCustomerSelectpage = (params = {}) => vm.$u.post(customerSelectpage, params),
	getAdminList = (params = {}) => vm.$u.get(adminList, params),
	getAchievementIndex = (params = {}) => vm.$u.get(achievementIndex, params),
	getAchievementAdmin = (params = {}) => vm.$u.get(achievementAdmin, params),
	onAchievementEdit = (params = {}) => vm.$u.post(achievementEdit, params),
	onAchievementAedit = (params = {}) => vm.$u.post(achievementAedit, params),
	getFieldsSelectpage = (params = {}) => vm.$u.get(fieldsSelectpage, params),
	onUnbind = (params = {}) => vm.$u.post(unbind, params),
	getNoticeTpl = (params = {}) => vm.$u.get(noticeTpl, params),
	onBatchTeam = (params = {}) => vm.$u.post(batchTeam, params),
	onBatchAdmin = (params = {}) => vm.$u.post(batchAdmin, params),
	getAllAdmin = (params = {}) => vm.$u.get(allAdmin, params),
	getThirdlist = (params = {}) => vm.$u.get(thirdlist, params),
	getCluesList = (params = {}) => vm.$u.get(cluesList, params),
	onCluesAdd = (params = {}) => vm.$u.post(cluesAdd, params),
	onCluesEdit	= (params = {}) => vm.$u.post(cluesEdit, params),
	getCluesEdit = (params = {}) => vm.$u.get(cluesEdit, params),
	onCluesDel = (params = {}) => vm.$u.post(cluesDel, params),
	getCluesTransform = (params = {}) => vm.$u.get(cluesTransform, params),
	postCluesTransform = (params = {}) => vm.$u.post(cluesTransform, params),
	onShiftDivert = (params = {}) => vm.$u.post(shiftDivert, params),
	onCluesRecord = (params = {}) => vm.$u.post(cluesRecord, params),
	getCluesRecordList = (params = {}) => vm.$u.get(cluesRecordList, params),
	onQywxH5 = (params = {}) => vm.$u.post(qywxH5, params),
	onQywxMp = (params = {}) => vm.$u.post(qywxMp, params),
	getReduplicate = (params = {}) => vm.$u.get(reduplicate, params),
	getCluesCommon = (params = {}) => vm.$u.get(cluesCommon, params),
	onCluesReceive = (params = {}) => vm.$u.post(cluesReceive, params),
	onCluesDiscard = (params = {}) => vm.$u.post(cluesDiscard, params),
	getSmsList = (params = {}) => vm.$u.get(sms, params),
	getEmailList = (params = {}) => vm.$u.get(emailList, params),
	onSendSms = (params = {}) => vm.$u.post(sendSms, params),
	getSendSms = (params = {}) => vm.$u.get(sendSms, params),
	onSendEmail = (params = {}) => vm.$u.post(email, params),
	getEmail = (params = {}) => vm.$u.get(email, params),
	onCloudcall = (params = {}) => vm.$u.post(cloudcall, params),
	getCloudcallMode = (params = {}) => vm.$u.get(cloudcallMode, params),
	postCloudcallMode = (params = {}) => vm.$u.post(cloudcallMode, params),
	onSignin = (params = {}) => vm.$u.post(signin, params),
	getPayurl = (params = {}) => vm.$u.get(payurl, params),
	getGeocoder = (params = {}) => vm.$u.get(geocoder, params),
	getRenewPayurl = (params = {}) => vm.$u.get(renewPayurl, params),
	getInvoiceList = (params = {}) => vm.$u.get(invoiceList, params),
	getAllInvoice = (params = {}) => vm.$u.get(allInvoice, params),
	getInvoiceAdd = (params = {}) => vm.$u.get(invoiceAdd, params),
	postInvoiceAdd = (params = {}) => vm.$u.post(invoiceAdd, params),
	getInvoiceHistory = (params = {}) => vm.$u.get(invoiceHistory, params),
	onInvoiceEdit = (params = {}) => vm.$u.post(invoiceEdit, params),
	getInvoiceEdit = (params = {}) => vm.$u.get(invoiceEdit, params),
	onInvoiceOpener = (params = {}) => vm.$u.post(invoiceOpener, params),
	onInvoiceDel = (params = {}) => vm.$u.get(invoiceDel, params),
	onGetNotice = (params = {}) => vm.$u.get(getNotice, params)






	// 将各个定义的接口名称，统一放进对象挂载到vm.$u.api(因为vm就是this，也即this.$u.api)下
	vm.$u.api = {
		getSearch,
		onWxlogin,
		onMplogin,
		getInit,
		onBind,
		goUpload,
		onLogout,
		lookAdmin,
		getGroupdata,
		onAdminAdd,
		onAdminEdit,
		getAdminEdit,
		onAdminDel,
		onSelectpage,
		getCustomerList,
		getViolationList,
		getViolationMore,
		onCustomerAdd,
		onApplyCustomerAdd,
		onApplyOrderAdd,
		onCustomerEdit,
		onApplyCustomerEdit,
		getCustomer,
		getApplyCustomer,
		onCustomerDel,
		getCommon,
		onDiscard,
		getReceive,
		onDivert,
		getContractlist,
		getReceivableslist,
		onOpenAdd,
		getProfile,
		postProfile,
		getProductUnit,
		onProductUnitEdit,
		onProductUnitAdd,
		getProductType,
		onProductTypeEdit,
		onProductTypeAdd,
		getProductList,
		onProductAdd,
		onProductEdit,
		onViolationEdit,
		onViolationAdd,
		getProductEdit,
		getViolationEdit,
		getViolationAdd,
		onProductDel,
		getProductTypeList,
		getProductTypeProp,
		getProductUnitList,
		getFields,
		getHome,
		getAchievement,
		getRecord,
		onRecordAdd,
		onRecordDel,
		getReceivables,
		onReceivablesAdd,
		getReceivablesAdd,
		onReceivablesEdit,
		getReceivablesEdit,
		onReceivablesDel,
		onAnalysis,
		onAnalysisRecord,
		onContractSelectpage,
		onAchievementAnalysis,
		getContractIndexList,
		onContractAdd,
		getContractAdd,
		onContractEdit,
		getContractEdit,
		onContractDel,
		onCommonSelectpage,
		getSubscribe,
		postSubscribe,
		onBusinessRecord,
		onBusinessRecordAdd,
		onBusinessRecordDel,
		onBusinessList,
		onBusinessAdd,
		onBusinessEdit,
		getBusinessEdit,
		onBusinessDel,
		getBusinessContacts,
		onContactsCorrelation,
		onBusinessContactsDel,
		getContactsList,
		onContactsAdd,
		getContactsEdit,
		onContactsEdit,
		onContactsDel,
		getBacklog,
		onBacklogVerify,
		getBacklogVerify,
		getlogList,
		getContractRanking,
		getCustomerRanking,
		getReceivablesRanking,
		getRecordRanking,
		getProductRanking,
		getCustomerIndex,
		getIndustry,
		getLevel,
		getSource,
		getArea,
		getAllarea,
		getBaseConfig,
		onGetInfo,
		getCustomerSelectpage,
		getAdminList,
		getAchievementIndex,
		getAchievementAdmin,
		onAchievementEdit,
		onAchievementAedit,
		getFieldsSelectpage,
		onUnbind,
		getNoticeTpl,
		onBatchTeam,
		onBatchAdmin,
		getAllAdmin,
		getThirdlist,
		getCluesList,
		onCluesAdd,
		onCluesEdit,
		getCluesEdit,
		onCluesDel,
		postCluesTransform,
		onShiftDivert,
		getCluesTransform,
		onCluesRecord,
		getCluesRecordList,
		onQywxH5,
		onQywxMp,
		getReduplicate,
		getCluesCommon,
		onCluesReceive,
		onCluesDiscard,
		getSmsList,
		onSendEmail,
		onSendSms,
		getSendSms,
		getEmail,
		getEmailList,
		onCloudcall,
		getCloudcallMode,
		postCloudcallMode,
		onSignin,
		getPayurl,
		getGeocoder,
		getRenewPayurl,
		getInvoiceList,
		getAllInvoice,
		getInvoiceAdd,
		postInvoiceAdd,
		getInvoiceHistory,
		onInvoiceEdit,
		getInvoiceEdit,
		onInvoiceOpener,
		onInvoiceDel,
		onGetNotice

	};
}

export default {
	install
}
