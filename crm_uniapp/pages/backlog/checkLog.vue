<template>
	<view>
		<block v-if="tdList.length > 0">
			<scroll-view  scroll-x class="sv" show-scrollbar="true">
				<view class="table">
					<u-table align="left" padding="10rpx">
						<u-tr>
							<u-th v-for="(item,index) in thList" :key="index">{{item}}</u-th>
						</u-tr>
						<u-tr v-for="(item,index) in tdList" :key="index">
							<u-td class="table_one">
								<view class="td-on">
									{{item.id}}
								</view>
							</u-td>
							<u-td>
								<view class="td-on">
									{{item.realname}}
								</view>
							</u-td>
							<u-td>
								<view class="td-item">
									{{item.admin_id}}
								</view>
							</u-td >
							<u-td> 
								<view class="td-item">
									{{item.remark ? item.remark : '结束'}}
								</view>
							</u-td>
							<u-td> 
								<view class="td-item">
									{{timeFormats(item.create_time)}} 
								</view>
							</u-td>
							<u-td> 
								<view class="td-item">
									<text :class="item.status == 1 ? 'su' : 'err' ">{{item.status | changeStatus}} </text>
								</view>
							</u-td>
						</u-tr>
					</u-table>
				</view>
			</scroll-view>
		</block>
		<u-empty v-else text="暂无数据"  margin-top="100" mode="list"></u-empty>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				thList: ['ID','审批人','审批人ID','意见','审批时间','状态'],
				tdList: [],
				logId: '',
				flow_id: '',
			};
		},
		onLoad(e) {
			this.logId = e.id
			this.flow_id = e.flow_id
		},
		onUnload() {
			// 页面销毁，清除筛选数据
			this.$u.vuex('vuex_pfilter', {})
		},
		onShow(){
			this.getGroupdata()
		},
		filters: {
			changeStatus(val){
				switch (val) {
					case 0:
						return '驳回'
						break;
					case 1:
						return '通过'
						break;
					default:
						return '--'
						break;
				}
			}
		},
		methods: {
			// 格式化时间
			timeFormats(val) {
				if(val) {
					return this.$u.timeFormat(val, 'yyyy/mm/dd hh:MM');
				} else {
					return '--'
				}
			},
			// 获取数据
			getGroupdata(){
				this.$u.get('crm.flow.log/index', {
					flow_id: this.flow_id,
					types_id: this.logId,
				}).then(res => {
					if(res.code == 1 ) {
						this.tdList = res.data.rows
						// console.log(this.tdList)
					}
				})
			},
			// 快速设置
			setClick(index) {
				this.$u.route('pages/performance/celeritySet',{
					type: index
				});
			},
			// 筛选
			onFilter() {
				this.$u.route('pages/performance/filter',{});
			},
		}
	}
</script>

<style lang="scss">
.tap {
	justify-content: space-between;
  padding: 10rpx 20rpx;
}
.table {
	min-width: 1000px;
	padding: 30rpx 15rpx;
}
.table_one {
	border-top: 0;
	border-left: 0;
	position: relative;
}
.td-on {
	padding: 25rpx 0;
}
.td-item {
	padding: 25rpx 0;
	// color: #2979ff;
	.su {
		color: #19be6b
	}
	.err {
		color: #fa3534
	}
}



</style>
