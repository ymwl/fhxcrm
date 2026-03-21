<template>
	<!-- 选择地区 -->
	<u-select v-model="selectValue" :mask-close-able="maskCloseAble" :safe-area-inset-bottom="true"  mode="mutil-column-auto" label-name="name" :list="regionList" @cancel="close" @confirm="addressChange" ></u-select>
</template>

<script>
	/**
	 * city-select 省市区级联选择器
	 * @property {String Number} z-index 弹出时的z-index值（默认1075）
	 * @property {Boolean} mask-close-able 是否允许通过点击遮罩关闭Picker（默认true）
	 * @property {String} default-region 默认选中的地区，中文形式
	 * @property {String} default-code 默认选中的地区，编号形式
	 */
	export default {
		name: 'fa-citys',
		props: {
			// 通过双向绑定控制组件的弹出与收起
			value: {
				type: Boolean,
				default: false
			},
			// 默认显示的地区，可传类似["河北省", "秦皇岛市", "北戴河区"]
			defaultRegion: {
				type: Array,
				default () {
					return [];
				}
			},
			// 默认显示地区的编码，defaultRegion和areaCode同时存在，areaCode优先，可传类似["13", "1303", "130304"]
			areaCode: {
				type: Array,
				default () {
					return [];
				}
			},
			// 是否允许通过点击遮罩关闭Picker
			maskCloseAble: {
				type: Boolean,
				default: true
			},
			// 弹出的z-index值
			zIndex: {
				type: [String, Number],
				default: 0
			}
		},
		data() {
			return {
				regionList: [],//地址数据
				selectValue: false,
			}
		},
		mounted() {
			this.getAllarea()
		},
		computed: {
			
		},
		watch: {
			value(newValue, oldValue) {  
        this.selectValue = newValue
    	},
			selectValue(newValue, oldValue) {
				if(newValue == false ) {
					this.close()
				}
			}
		},
		methods: {
			//获取所有省市区数据 用于下拉选择
			getAllarea(){
				let arrList = uni.getStorageSync('storage_getAllarea')
				if(this.$u.test.array(arrList)) {
					this.regionList = arrList
				} else {
					this.$u.api.getAllarea().then((res) => {
						if(res.code == 1){
							this.regionList = (res.data);
							uni.setStorageSync('storage_getAllarea',res.data);
						}
					})
				}
			},
			// 选择
			addressChange(result){
				this.$emit('city-change', result);
				this.close();
			},
			close() {
				this.$emit('input', false);
			},
		}

	}
</script>
<style lang="scss">
	.area-box {
		width: 100%;
		overflow: hidden;
		height: 800rpx;

		>.container {
			width: 150%;
			transition: transform 0.3s ease-in-out 0s;
			transform: translateX(0);

			&.change {
				transform: translateX(-33.3333333%);
			}
		}

		.area-item {
			width: 33.3333333%;
			height: 800rpx;
		}
	}
</style>
