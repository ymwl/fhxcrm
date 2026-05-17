// 如果没有通过拦截器配置域名的话，可以在这里写上完整的URL(加上域名部分)

const install = (Vue, vm) => {
	// API 调用已全部迁移到各页面文件中直接使用 this.$u.get/post('url', params) 进行调用
	// 不再使用 this.$u.api.xxx() 中间层封装形式

	// 保留空 install 函数以保持 Vue 插件接口兼容
};

export default {
	install
}
