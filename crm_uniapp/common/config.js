/**
 * 配置编译环境和线上环境之间的切换
 *
 * baseUrl: 域名地址
 * routerMode: 路由模式
 * attBaseUrl: 图片所在域名地址
 * api_v1:接口地址
 *
 */

let attBaseUrl = 'http://crm.laikephp.cn'
let baseUrl = 'http://crm.laikephp.cn'
// #ifdef H5
// attBaseUrl = baseUrl = window.location.protocol + "//" + window.location.host;
// #endif
let api_v1 = '/api.php'
let store_v1 = '' // 备用接口地址
let token_key = '' // 登录凭证
export {
	baseUrl,
	attBaseUrl,
	api_v1,
	store_v1,
	token_key,
}
