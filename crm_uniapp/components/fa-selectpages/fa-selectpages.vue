<template>
	<view class="lselect">
		<view class="" v-if="checkeType == 'lselect'">
			<u-input :type="disabled ?  'text'  : 'select' " :select-open="show" :border="!disabled" v-model="page_lable" :disabled="disabled" :placeholder=" disabled ?  '暂无'  : '请选择' + title" @click=" disabled ?  ''  : show = true "></u-input>
		</view>
		<view class="" v-if="checkeType == 'lselects'">
			<view class="select-pages u-flex u-flex-wrap" @click="disabled ?  ''  : show = true">
				<view class="u-m-r-10" v-for="(tag, tak) in pagesLable" :key="tak">
					<u-tag :text="tag[showField]"  type="success" />
				</view>
				<view class="u-light-color" v-text=" disabled ?  '暂无'  : '请选择' + title" v-if="!pagesLable.length"></view>
			</view>
		</view>
		<u-popup v-model="show" :popup="false" @close="close" mode="bottom" height="700">
			<view class="u-flex u-flex-column">
				<view class="fa-column u-p-l-30 u-p-r-30 u-p-t-20 u-p-b-20 u-border-bottom">
					<u-search placeholder="搜索" v-model="q_word" :show-action="false"></u-search>
				</view>
				<!-- 范围切换 tabs（仅 popup_selection + 后端返回 scopes 时显示） -->
				<view class="fa-column scope-tabs u-border-bottom" v-if="href && scopeKeys.length > 0">
					<scroll-view scroll-x="true" class="scope-scroll">
						<view class="scope-tab u-flex">
							<view
								v-for="(label, key) in scopes"
								:key="key"
								class="scope-item"
								:class="{ 'scope-item-active': activeScope == key }"
								@click="switchScope(key)"
							>{{ label }}</view>
						</view>
					</scroll-view>
				</view>
				<view class="fa-column u-flex-1 u-flex fa-scroll">
					<scroll-view scroll-y="true" :style="{ height: scrollHg + 'px', width: '100vw' }" @scrolltolower="goLower">
						<!-- 多选 -->
						<view v-if="checkeType == 'lselects'">
							<checkbox-group>
								<u-cell-item :arrow="false" v-for="(item, index) in list" :key="index" :title="item[showField]" @click.self="selectCell(index)">
									<checkbox
										slot="right-icon"
										shape="square"
										
										:value="item[keyField] + ''"
										:checked="item.checked"
									></checkbox>
								</u-cell-item>
							</checkbox-group>
						</view>
						<!-- 单选 -->
						<view class="" v-else>
							<u-radio-group v-model="radio_value">
								<u-cell-item :arrow="false" v-for="(item, index) in list" :key="index" :title="item[showField]" @click.self="selectCell(index)">
									<u-radio slot="right-icon"  :name="item[keyField] + ''"></u-radio>
								</u-cell-item>
							</u-radio-group>
						</view>
						<view class="u-p-10"><u-loadmore :status="status" /></view>
					</scroll-view>
				</view>
				<view class="fa-column select-footer u-text-center">
					<u-gap height="10" bg-color="#eaeaec"></u-gap>
					<view class="u-p-10 u-flex u-row-around">
						<view class="u-flex-1" v-if="checkeType == 'lselects'" @click="clearAll"><text>清空</text></view>
						<!-- <view class="u-flex-1" @click="allSelect"> -->
						<!-- <text>全选</text> -->
						<!-- </view> -->
						<view class="u-flex-1" @click="confirm">
							<text>{{ checkeType == 'lselects' ? '确定' : '取消' }}</text>
						</view>
					</view>
				</view>
			</view>
		</u-popup>
	</view>
</template>

<script>
import Emitter from '@/uview-ui/libs/util/emitter.js';
export default {
	name: 'fa-selects',
	mixins: [Emitter],
	props: {
		value:{
			type:[String,Number],
			default:''
		},
		//查询id
		faId: {
			type: [Number, String],
			default: ''
		},
		// 自定义请求地址（popup_selection 类型使用），优先级高于 faId
		href: {
			type: String,
			default: ''
		},
		//显示字段
		showField: {
			type: String,
			default: ''
		},
		//保存的键
		keyField: {
			type: String,
			default: ''
		},
		//提示
		title: {
			type: String,
			default: ''
		},		
		checkeType: {
			type: String,
			default: 'lselect'
		},
		//默认的值
		showValue: {
			type: [String, Number],
			default: ''
		},
		// 是否禁止选择
		disabled: {
			type: [Boolean],
			default: false,
		}
	},
	watch: {
		//弹出高度
		show(newValue, oldValue) {
			if (newValue) {
				this.$nextTick(() => {
					setTimeout(() => {
						uni.createSelectorQuery()
							.in(this)
							.select('.fa-scroll')
							.boundingClientRect(rect => {
								console.log(rect);
								if (rect) {
									this.scrollHg = rect.height;
								}
							})
							.exec();
					}, 100); //在百度直接获取不到，需要延时
				});
				if (!this.list.length) {
					this.page = 1;
					// popup_selection 模式：每次打开弹窗重置 scopes 加载状态
					if (this.href) {
						this.scopesLoaded = false;
						this.activeScope = '';
					}
					this.getlselects();
				}
			} else {
				this.sendChange();
			}
		},
		//默认的数据
		showValue: {
			immediate: true,
			handler(val) {
				//第一次渲染默认就好
				if (val && !this.isFirst) {
					this.isFirst = true;
					this.getInitSelect();
				}
			}
		},
		//搜索
		q_word(newValue, oldValue) {
			this.list = [];
			this.page = 1;
			this.getlselects();
		},
		// scope tabs 出现后重算滚动区域高度，确保所有 tab 下列表布局一致
		scopeKeys(newKeys) {
			if (newKeys.length > 0 && this.show) {
				this.$nextTick(() => {
					setTimeout(() => {
						uni.createSelectorQuery()
							.in(this)
							.select('.fa-scroll')
							.boundingClientRect(rect => {
								if (rect) {
									this.scrollHg = rect.height;
								}
							})
							.exec();
					}, 100);
				});
			}
		}
	},
	data() {
		return {
			show: false,
			list: [],
			radio_value: '',
			scrollHg: 0,
			q_word: '',
			pageNum: 0,
			page: 1,
			totalPage: 0,
			status: 'loadmore',
			isFirst: false,
			page_lable: '',
			pagesLable: [], //初始化的值
			ids_ing: [], //已经加载的值
			ids: [],
			// popup_selection 范围切换
			scopes: {},           // 后端返回的范围配置 {'1':'我的','2':'下属的','3':'全部'}
			activeScope: '',      // 当前选中的范围 key
			scopesLoaded: false   // 是否已从后端加载过 scopes 配置
		};
	},
	computed: {
		// scopes 的 key 列表，用于 v-if 判断和默认值
		scopeKeys() {
			return Object.keys(this.scopes);
		}
	},
	methods: {
		close() {
			this.show = false;
		},
		// 切换数据范围 tab（popup_selection 模式）
		switchScope(key) {
			if (this.activeScope == key) return;
			this.activeScope = key;
			// 重置分页和列表，重新加载
			this.list = [];
			this.page = 1;
			this.pageNum = 0;
			this.totalPage = 0;
			this.status = 'loadmore';
			this.getByHref();
		},
		//初始化值
		getInitSelect() {
			if (this.showValue) {
				// popup_selection 模式：通过 href 获取初始显示值
				if (this.href) {
					this.getInitByHref();
					return;
				}
				let param = {
					id: this.faId,
					pageNumber: this.page,
					q_word: this.q_word,
					keyValue: this.showValue
				};
				this.$u.get('fields/selectpage', param).then(res => {
					if (res.data.list && res.data.list.length > 0) {
						if (this.checkeType == 'lselect') {
							this.page_lable = res.data.list[0][this.showField];
							this.radio_value = res.data.list[0][this.keyField] + '';
						} else {
							this.pagesLable = res.data.list;
							let ids = [];
							res.data.list.forEach(item => {
								ids.push(item[this.keyField]);
							});
							this.ids = ids;
						}
					}
				});
			}
		},
		// popup_selection 模式：通过 href 获取初始显示值
		getInitByHref() {
			const [basePath, queryStr] = this.href.split('?');
			let params = {
				offset: 0,
				limit: 1
			};
			if (queryStr) {
				queryStr.split('&').forEach(pair => {
					const [k, v] = pair.split('=');
					if (k) params[k] = decodeURIComponent(v || '');
				});
			}
			// 通过 filter 按 keyField 精确查找初始值
			params.filter = JSON.stringify({ [this.keyField]: this.showValue });
			params.op = JSON.stringify({ [this.keyField]: '=' });
			this.$u.get(basePath, params).then(res => {
				if (res.code != 1) return;
				let item = null;
				if (res.data && res.data.rows && res.data.rows.length > 0) {
					item = res.data.rows[0];
				} else if (res.data && res.data.list && res.data.list.length > 0) {
					item = res.data.list[0];
				} else if (Array.isArray(res.data) && res.data.length > 0) {
					item = res.data[0];
				}
				if (item) {
					this.page_lable = item[this.showField];
					this.radio_value = item[this.keyField] + '';
				}
			});
		},
		//获取数据
		getlselects() {
			// popup_selection 模式：使用 href 请求
			if (this.href) {
				this.getByHref();
				return;
			}
			if (!this.faId) {
				return;
			}			
			let param = { id: this.faId, pageNumber: this.page, q_word: this.q_word };
			this.$u.get('fields/selectpage', param).then(res => {
				this.status = res.data.total == 0 || this.page >= this.totalPage ? 'nomore' : 'loadmore';
				let list = [];
				if (this.checkeType == 'lselects') {
					res.data.list.forEach(item => {
						item.checked = this.ids.indexOf(item[this.keyField]) != -1;
						list.push(item);
						//已选的
						this.pagesLable.forEach(it => {
							if (item[this.keyField] == it[this.keyField]) {
								this.ids_ing.push(it); //在已选的已经加载的数据
							}
						});
					});
				} else {
					list = res.data.list;
				}
				//一页的数量，取第一次就好
				if (!this.pageNum) {
					this.pageNum = list.length;
				}
				this.totalPage = Math.ceil(res.data.total / this.pageNum);
				this.list = [...this.list, ...list];
			});
		},
		// popup_selection 模式：通过 href 获取数据
		getByHref() {
			const pageSize = 10;
			const offset = (this.page - 1) * pageSize;
			let params = {
				offset: offset,
				limit: pageSize,
				sort_by: 'id',
				sort_order: 'desc'
			};
			// 传递当前选中的范围 scope
			if (this.activeScope) {
				params.scope = this.activeScope;
			}
			if (this.q_word) {
				params.search = this.q_word;
			}
			// 从 href 中提取已存在的查询参数并合并
			const [basePath, queryStr] = this.href.split('?');
			if (queryStr) {
				queryStr.split('&').forEach(pair => {
					const [k, v] = pair.split('=');
					if (k && !params[k]) {
						params[k] = decodeURIComponent(v || '');
					}
				});
			}
			this.$u.get(basePath, params).then(res => {
				if (res.code != 1) return;
				// 首次加载时提取 scopes 范围配置
				if (!this.scopesLoaded && res.scopes) {
					this.scopes = res.scopes;
					this.scopesLoaded = true;
					// 默认选中第一个 scope
					if (!this.activeScope) {
						const keys = Object.keys(res.scopes);
						if (keys.length > 0) {
							this.activeScope = keys[0];
						}
					}
				}
				// 兼容多种返回格式：data.rows、data.list、data 直接为数组
				let list = [];
				let total = 0;
				if (res.data && res.data.rows) {
					list = res.data.rows;
					total = res.data.count || res.data.total || 0;
				} else if (res.data && res.data.list) {
					list = res.data.list;
					total = res.data.total || 0;
				} else if (Array.isArray(res.data)) {
					list = res.data;
					total = res.count || 0;
				}
				if (!this.pageNum) {
					this.pageNum = list.length || pageSize;
				}
				this.totalPage = Math.ceil(total / this.pageNum);
				this.status = total == 0 || this.page >= this.totalPage ? 'nomore' : 'loadmore';
				this.list = [...this.list, ...list];
			});
		},
		//选择
		selectCell(index) {
			if (this.checkeType == 'lselects') {
				this.$set(this.list[index], 'checked', !this.list[index].checked);
			} else {
				//单选
				this.radio_value = this.list[index][this.keyField];
				this.page_lable = this.list[index][this.showField];
				this.$emit('input', this.radio_value);				
				this.close();
				setTimeout(() => {
					this.dispatch('u-form-item', 'on-form-blur', this.radio_value);
				}, 50);
			}
		},
		//加载更多
		goLower(e) {
			if (this.page == this.totalPage) {
				return;
			}
			this.status = 'loading';
			this.page++;
			this.getlselects();
		},
		//多选确定
		confirm() {
			if (this.checkeType == 'lselects') {
				//先取未加载的数据的集合
				let data = this.pagesLable.filter(item => {					
					if (
						this.$u.test.empty(this.ids_ing) || this.ids_ing.find(it => {
							return item[this.keyField] == it[this.keyField];
						})
					) {
						return false;
					} else {
						return true;
					}
				});
				
				let ids = [];
				let res = [];
				
				this.list.forEach(item => {
					if (item.checked) {
						ids.push(item[this.keyField]);
						res.push(item);
					}
				});
				
				//追加未加载的选项
				data.forEach(item => {
					ids.push(item[this.keyField]);
					res.push(item);
				});
				
				this.pagesLable = res;
				this.ids = ids;
								
				this.$emit('input', ids.join(','));
				setTimeout(() => {
					this.dispatch('u-form-item', 'on-form-blur', ids.join(','));
				}, 50);
			}
			this.close();
		},
		//全选
		allSelect() {
			this.list.map(item => {
				item.checked = true;
			});
		},
		//清空
		clearAll() {
			this.list.map(item => {
				item.checked = false;
			});
			this.pagesLable = [];
		},
		//派发事件
		sendChange() {
			setTimeout(() => {
				if (this.checkeType == 'select') {
					this.dispatch('u-form-item', 'on-form-change', this.radio_value);
				} else {
					this.dispatch('u-form-item', 'on-form-change', this.checkbox_value);
				}
			}, 50);
		}
		// upPage(){
		// 	if(this.page==1){
		// 		return;
		// 	}
		// 	this.page--;
		// },
		// nextPage(){
		// 	if(this.page == this.totalPage){
		// 		return;
		// 	}
		// 	this.page++;

		// 	if(!this.list[this.page-1]){
		// 		this.getlselects();
		// 	}
		// }
	}
};
</script>

<style lang="scss" scoped>
.lselect {
	width: 100%;
}
.select-pages {
	width: 100%;
	border: 1px solid #dcdfe6;
	padding: 5rpx 10rpx;
}
.u-flex-column {
	flex-direction: column;
	height: 100%;
	.fa-column {
		width: 100%;
	}
}
// 修复 u-radio-group 的 inline-flex 导致 u-cell-item 宽度塌陷，标题与图标紧贴
.fa-scroll {
	::v-deep .u-radio-group {
		display: flex;
		flex-direction: column;
		width: 100%;
	}
}
// popup_selection 范围切换 tabs
.scope-tabs {
	background-color: #ffffff;
	.scope-scroll {
		white-space: nowrap;
		.scope-tab {
			.scope-item {
				display: inline-block;
				padding: 16rpx 32rpx;
				font-size: 28rpx;
				color: #606266;
				position: relative;
				&.scope-item-active {
					color: #2979ff;
					font-weight: 600;
					&::after {
						content: '';
						position: absolute;
						bottom: 0;
						left: 50%;
						transform: translateX(-50%);
						width: 48rpx;
						height: 4rpx;
						background-color: #2979ff;
						border-radius: 2rpx;
					}
				}
			}
		}
	}
}
</style>
