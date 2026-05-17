<template>
	<view class="fa-array">
		<!-- 选择转移人 -->
		<u-popup class="slot-content" mode="bottom" border-radius="38"  v-model="companyShow" >
			<view class="popup-title u-border-bottom">
				<view class=""  style="width: 45px;">
					<!-- <u-icon name="close"  color="#909399" size="30"></u-icon> -->
				</view>
				<text class="">选择目标员工</text> 
				<view class="" @click="companyShow = false" style="width: 45px;">
					<u-icon name="close"  color="#909399" size="30"></u-icon>
				</view>
			</view>
			<u-search margin="30rpx 20rpx" shape="square" v-model="adminkeyword" :show-action="false" :clearabled="true"  placeholder="输入名称搜索" @change="adminSearch"></u-search>
			<scroll-view scroll-y style="height: 760rpx;width: 100%;" @scrolltolower="adminBottom">
				<view class="list">
					<block v-if="companyList.length > 0">
						<view class="u-m-b-45">
							<view class="item u-flex u-border-bottom" v-for="(item,index) in companyList" :key="index" @click="oncompany(item)">
								<view class="title">{{item.realname}}</view>
								<view class="check-icon">
									<u-icon v-if="admin_id == item.id" name="checkmark" color="#2979ff" size="38"></u-icon>
								</view>
							</view>
						</view>
						<u-loadmore :status="adminStatus" ></u-loadmore>
					</block>
					<u-empty text="暂无数据" v-else  margin-top="100" mode="list"></u-empty>
				</view>
			</scroll-view>
			<view class="bottom_btn u-border-top">
				<u-button size="medium" @click="onCliskDivert(false)">取消</u-button> 
				<u-button class="u-m-l-15" type="primary"  @click="onCliskDivert(true)" size="medium">转移</u-button>
			</view>
		</u-popup>
	</view>
</template>

<script>
import Emitter from '@/uview-ui/libs/util/emitter.js';
export default {
	name: 'staff-list',
	mixins: [Emitter],
	props: {
		
	},
	watch: {
		
	},
	data() {
		return {
      admin_id: '',
      companyShow: false,
      lastAdmin: false,
      adminStatus: 'nomore',
      adminkeyword: '',
      pageSize: '10',
			adminPage: 1,
      companyList: [],
		};
	},
  mounted() {
    this.onSelectpage()
	},
	methods: {
    // 确认转移 、取消
    onCliskDivert(val){
      if(val){
       if(this.admin_id == ''){
          uni.showToast({
            title: '请先选择目标员工',
            icon: 'none',
            duration: 2000
          })
          return
        }
				this.$emit('onShift',this.admin_id) 
      } else {
        this.admin_id = ''
        this.companyShow = false
      }
    },
    // 选择员工
    oncompany(val) {
      this.admin_id = val.id
    },
    // 选择搜索
    adminSearch() {
      this.lastAdmin = false
      this.onSelectpage()
    },
    // 获取负责人
    onSelectpage(isNextPage,pages) {
      this.$u.get('ajax/selectpage', {
        pageNumber: (pages || 1 ),
        pageSize: this.pageSize,
        name: this.adminkeyword,
        keyField: 'id',
        showField: 'realname',
        "q_word": this.adminkeyword,
        "searchField": "realname",
        model:'admin',
      }).then(res => {
        if(res.code == 1 ) {
          // 最后一页
          if(res.data.list.length == 0) {
            this.lastAdmin = true
          } 
          //不够一页
          if (res.data.list.length <= this.pageSize) {
            this.adminStatus = 'nomore'
          }
          // 第二页开始
          if(isNextPage) {
            this.companyList = this.companyList.concat(res.data.list)
            return 
          }
          this.companyList = res.data.list
        }
      })
    },
    // 滚动到底部加载更多
    adminBottom() {
      if(this.lastAdmin || this.adminStatus == 'loading') return ;
      this.adminStatus = 'loading'
      setTimeout(() => {
        if(this.lastAdmin) return ;
        this.onSelectpage(true,++this.adminPage)
        if(this.companyList.length >= 10) this.adminStatus = 'loadmore';
        else this.adminStatus = 'loading';
      }, 1200)
    },
	}
};
</script>

<style lang="scss" scoped>
.slot-content {
	.search {
		padding: 30rpx 25rpx;
	}
	.popup-title {
		display: flex;
		align-items: center;
		justify-content: space-between;
		position: relative;
		font-size: 35rpx;
		font-weight: 600;
		text-align: center;
		height: 50px;
		padding-right: 25rpx;
	}
	.list {
		padding-bottom: 45rpx;
		.item {
			padding: 0 25rpx;
			justify-content: space-between;
			height: 55px;
			.title {
				flex: 1;
				font-size: 28rpx;
				font-weight: 600;
			}
			.check-icon {
				text-align: center;
				width: 100rpx;
			}
		}
	}
	.bottom_btn {
		text-align: right;
		padding: 28rpx 10rpx 28rpx;
	}
}
</style>
