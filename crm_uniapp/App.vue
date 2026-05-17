<script>
import {scene_decode} from '@/common/mUtils'

	export default {
		onLaunch: function(options) {
			console.log('App Launch')
			// 获取系统基础配置
			this.getInit(options.query.preview ? options.query.preview : '')
		},
		onShow: function(options) {
			console.log('App Show',options)
			// 小程序启动场景
    	// this.onStartupScene(options.query);
			// 小程序版本更新
			this.getUpdateManager()
		},
		onHide: function() {
		},
		methods: {
			getInit(val){
				// 获取基础配置信息
				this.$u.get('login/config', {preview:val}).then(res => {
          console.log(res);
					if(res.code == 1) {
						//主题做缓存
						this.$u.vuex('vuex_theme',res.data.themeconfig.theme ? res.data.themeconfig.theme : {})
						//系统配置做缓存
						this.$u.vuex('vuex_config',res.data.themeconfig ? res.data.themeconfig : {})
						this.$u.vuex('vuex_upload',res.data.upload ? res.data.upload : {})
						this.$u.vuex('vuex_system',res.data.system ? res.data.system : {})
						//支付配置和公众号 app_id 做缓存
						this.$u.vuex('vuex_payConfig', res.data.payConfig || {})
						this.$u.vuex('vuex_app_id', res.data.app_id || '')
					} else {
						this.$u.toast(res.msg);
					}
				})
			},
			// 消息订阅接口
			getNoticeTpl() {
				this.$u.get('crm.common/getNoticeTpl').then(res => {
					if(res.code == 1) {
						// 存储订阅消息数据
						this.$u.vuex('vuex_notice_tpl', res.data)
					}
				})
			},
			// 微信小程序启动场景
			onStartupScene(query) {
				
			},
			//获取场景值(scene)
			getSceneData(query) {
				return query.scene ? scene_decode(query.scene) : {};
			},
			// 小程序版本更新
			getUpdateManager() {
				//新版本更新
				if (uni.canIUse('getUpdateManager')) {
					//判断当前微信版本是否支持版本更新
					const updateManager = uni.getUpdateManager();
					updateManager.onCheckForUpdate(function (res) {
						if (res.hasUpdate) {
							// 请求完新版本信息的回调
							updateManager.onUpdateReady(function () {
								uni.showModal({
									title: '更新提示',
									content: '新版本已经准备好，是否重启应用？',
									success: function (res) {
										if (res.confirm) {
											// 新的版本已经下载好，调用 applyUpdate 应用新版本并重启
											updateManager.applyUpdate();
										}
									},
								});
							});
							updateManager.onUpdateFailed(function () {
								uni.showModal({
									// 新的版本下载失败
									title: '已经有新版本了哟~',
									content: '新版本已经上线啦~，请您删除当前小程序，重新搜索打开哟~',
								});
							});
						} else {

						}
					});
				} else {
					uni.showModal({
						// 如果希望用户在最新版本的客户端上体验您的小程序，可以这样子提示
						title: '提示',
						content: '当前微信版本过低，无法使用该功能，请升级到最新微信版本后重试。',
					});
				}
			}
		}
		
	}
</script>

<style lang="scss">
	@import "uview-ui/index.scss";
	@import "common/iconfont.css";
	@import "common/common.scss";
	/*每个页面公共css */
</style>
