<template>
  <view class="container">
    <!-- 地图组件 -->
    <map id="map" class="map" style="width: 100%; height: 600rpx;" scale="16" :show-location="false" :latitude="form.lat" :longitude="form.lng" :markers="covers"></map>
    <!-- 内容 -->
    <view class="content" >
      <view class="u-flex address">
        <view class="u-flex-1 name">{{form.position ? form.position : '--'}}</view>
        <view class="btn" @click="getLocation">重新定位</view>
      </view>
      <u-form :model="form" ref="uForm" :error-type="errorType" label-position="left">
				<u-form-item required label="外访客户:"  label-width="180" prop="record_type">
					<u-input v-model="customerName" type="select"  :select-open="selectShow" placeholder="请选择"  @click="selectShow = true"/>
				</u-form-item>
				<u-form-item required label="外访内容:" label-width="180" prop="content">
					<u-input type="textarea" :border="true" height="200" :auto-height="true" v-model="form.content" />
				</u-form-item>
				<u-form-item label="图片:"  label-width="180">
					<u-upload :custom-btn="true" ref="uUpload" :before-upload="beforeUpload" :form-data="param" @on-choose-complete="changeList"  :show-upload-list="true" max-count="5" :action="action" @on-uploaded="finish" :auto-upload="true">
						<view slot="addBtn" class="slot-btn" hover-class="slot-btn__hover" hover-stay-time="150">
							<u-icon name="camera" size="60" color="#606266"></u-icon>
							<view class="text">选择图片</view>
						</view>
					</u-upload>
				</u-form-item>
			</u-form>
    </view>
    <view class="u-m-t-40" style="text-align: center; padding: 0rpx 35rpx 60rpx;">
      <u-button type="success"  @click="onSubmit" :custom-style="{backgroundColor: vuex_theme.color, color: vuex_theme.bgColor}" :ripple="true">签到</u-button>
    </view>
    <!-- 选择客户弹窗 -->
		<u-popup class="popup-content" mode="bottom" border-radius="38"  v-model="selectShow" >
			<view class="popup-title u-border-bottom">
				<view class=""  style="width: 45px;">
					<!-- <u-icon name="close"  color="#909399" size="30"></u-icon> -->
				</view>
				<text class="">选择客户</text> 
				<view class="" @click="selectShow = false" style="width: 45px;">
					<u-icon name="close"  color="#909399" size="30"></u-icon>
				</view>
			</view>
			<u-search margin="30rpx 20rpx" shape="square" v-model="keyword" :show-action="false" :clearabled="true"  placeholder="输入客户名称搜索" @change="onSearch"></u-search>
			<scroll-view scroll-y style="height: 760rpx;width: 100%;" @scrolltolower="reachBottom">
				<view class="list">
					<block v-if="customerList.length > 0">
						<view class="u-m-b-45">
							<view class="item u-flex u-border-bottom" v-for="(item,index) in customerList" :key="index" @click="onItem(item,index)">
								<view class="title">{{item.name}}</view>
								<view class="check-icon">
									<u-icon v-if="item.checked" name="checkmark" color="#2979ff" size="38"></u-icon>
								</view>
							</view>
						</view>
						<u-loadmore :status="listStatus" ></u-loadmore>
					</block>
					<u-empty text="暂无数据" v-else  margin-top="100" mode="list"></u-empty>
				</view>
			</scroll-view>
		</u-popup>
  </view>
</template>

<script>
	import {baseUrl,api_v1} from '@/common/config'
  const img = '/static/icon.png';
  export default {
    data() {
      return {
        covers: [{
          id:0,
          latitude: '',
          longitude: '',
          width: 35,
          height: 35,
          iconPath: '../../static/icon.png'
        }],
        listStatus: 'loadmore',
				page: 1,
				pageSize: 10,
				keyword: '',
				lastPage: false,
				customerList: [],
        customerName: '',
				param: {
					token: '',
				},
        action: baseUrl + api_v1 + '/ajax/upload',
				selectShow: false,
        customerName: '',
				form: {
          customer_id: '',
          lng: '',
          lat: '',
          image: '',
					content: '',
          position: '',
				},
				errorType: ['message','toast'],
        lists: [],
        imageList: [],
				rules: {},
        customer_id: '',
      }
    },
    onLoad(e) {
      if(e.customer_id){
        this.customer_id = e.customer_id
        this.form.customer_id = e.customer_id
      }
      this.getData()
    },
    onShow(){
      // 上传文件参数
			this.param.token = this.vuex_token
    },
    onReady() {
      //#ifdef MP-WEIXIN
      uni.getSetting({
        success: (res) => {
          if (res.authSetting['scope.userLocation']) {
            /* 用户授权成功时走这里 */
            this.handerChooseLocation()
          } else if (res.authSetting['scope.userLocation'] === undefined) {
            console.log('未定位')
            /* 用户未授权时走这里 */
            this.handleOpenSetting()
          } else {
            /* 用户拒绝了授权后走这里 */
            this.handleOpenSetting()
          }
        }
      })
      //#endif

      //#ifdef H5
      this.getLocation()
      //#endif
    },
    methods: {
      // 获取当前地址经纬度
      getLocation(){
        console.log('获取经纬度')
        uni.showLoading({
          title: '加载中'
        });
        let _this = this
        uni.getLocation({
          type: 'wgs84',
          success: function (res) {
            uni.hideLoading()
            _this.form.lat = res.latitude
            _this.form.lng = res.longitude
            _this.covers[0].latitude = res.latitude
            _this.covers[0].longitude = res.longitude
            console.log('当前位置的经度：' + res.longitude);
            console.log('当前位置的纬度：' + res.latitude);
            _this.$u.get('crm.common/geocoder', {
              lat: _this.form.lat, //'39.984154'
              lng: _this.form.lng,//'116.307490'
            }).then(res => {
              if(res.code == 1 ) {
                _this.form.position = res.data.address
              }
            })
          },
          fail: function (res) {
            uni.hideLoading()
            console.log('失败：',res)
            uni.showToast({
              title: res.errMsg,
              icon: 'none',
              duration: 3000
            });
          },
          complete: function (res) {
            console.log(res)
          }
        });
      },
      handleOpenSetting() {
				/* 引导用户开启权限 */
				uni.openSetting({
					success: (res) => {
						if (res.authSetting["scope.userLocation"]) {
							this.handerChooseLocation()
						}
					}
				})
			},
			handerChooseLocation() {
				this.getLocation()
			},
      // 搜索
			onSearch() {
				this.page = 1
				this.lastPage = false
				this.getData()
			},
      // 获取客户列表
			getData(isNextPage,pages) {
				// 筛选参数
				let obj = {
					pageNumber: (pages || 1 ),
					pageSize: this.pageSize,
					name: this.keyword,
					keyField: 'id',
					showField: 'name',
					"q_word": this.keyword,
					"searchField": "name"
				}
				const admin_info = uni.getStorageSync('admin_info');
				if (admin_info&&admin_info['contract']) {
					obj.type='all';
				}
				if(this.customer_id) {
					obj.q_word= this.customer_id;
					obj.searchField= 'id';
				}
				this.$u.post('crm.customer.index/selectpage', obj).then(res => {
					if(res.code == 1 ) {
						// 最后一页
						if(res.data.list.length == 0) {
							this.lastPage = true
						} 
						//不够一页
						if (res.data.list.length < this.pageSize) {
							this.listStatus = 'nomore'
						}
						// 第二页开始
						if(isNextPage) {
							this.customerList = this.customerList.concat(res.data.list)
							return 
						}
						this.customerList = res.data.list
            // 添加默认客户
						if(this.customer_id) {
							this.customerList.forEach((item,index) => {
								if(this.customer_id == item.id) {
									item.checked = true
									this.customerName = item.name
								} else {
									item.checked = false
								}
							})
						}
					}
				})
			},
			// 滚动到底部加载更多
			reachBottom() {
				if(this.lastPage || this.listStatus == 'loading') return ;
				this.listStatus = 'loading'
				setTimeout(() => {
					if(this.lastPage) return ;
					this.getData(true,++this.page)
					if(this.customerList.length >= 10) this.listStatus = 'loadmore';
					else this.listStatus = 'loading';
				}, 1200)
			},
			// 选择客户
			onItem(val,i) {
				this.customerList.forEach((item,index) => {
					if(val.id == item.id) {
						item.checked = true
						val.name=val.name?val.name:item.name;
					} else {
						item.checked = false
					}
				})
				this.form.customer_id = val.id
				this.customerName = val.name ? val.name : ''
				this.selectShow = false
			},
      //上传前的钩子
			beforeUpload(index, list) {
				console.log(list)
			},
			// 每次选择图片后触发，只是让外部可以得知每次选择后，内部的文件列表
			changeList(lists, name) {
				// 往已选文件的列表lists添加额外参数
				this.lists.forEach((item,index) => {
					// 文件名称
					item.name = this.sku + this.$u.timeFormat('', 'yyyymmddhhMMss') + this.$u.random(100, 999) 
					// 用户重命名
					item.rename = '' 
				});
			},
			// 所有图片上传完成
			finish(data, index, lists, name){
				// 数据初始化，防止重复添加
				this.imageList = []
				data.forEach((item,index) => {
          if(item.progress == 100) {
					  this.imageList.push(item.response.data.fullurl)
          }
				});
			},
      // 提交签到
      onSubmit() {
        this.form.image = this.imageList.join(',')
        console.log(this.form)
        if(!this.form.customer_id){
          // 提示
          uni.showToast({
            title: "请选择客户",
            icon: 'none',
            duration: 2000
          })
          return
        }
        if(this.form.lat == '' || this.form.lng == ''){
          // 提示
          uni.showToast({
            title: "定位失败",
            icon: 'none',
            duration: 2000
          })
          return
        }
        if(this.form.content == ''){
          // 提示
          uni.showToast({
            title: "请输入外访内容",
            icon: 'none',
            duration: 2000
          })
          return
        }
        this.$u.post('crm.customer.record/signin', this.form).then(res => {
					if(res.code == 1 ) {
            uni.showToast({
              title: res.msg,
              icon: 'success',
              duration: 2000
            })
            setTimeout(() => {
              // 跳转客户详情
              this.$u.route('pages/client/customerDetails',{
                id: this.form.customer_id
              });
            }, 1000);
					}
				})
      }
    }
  }
</script>

<style lang="scss">
  page{
    background-color: #f3f4f6;
  }
  .content {
    background-color: #fff;
    margin: 25rpx 20rpx;
    border-radius: 10rpx;
    padding: 25rpx 20rpx;
    .address {
      margin-bottom: 20rpx;
      .name {
        margin-right: 15rpx;
        font-size: 30rpx;
        font-weight: 600;
      }
      .btn {
        color: #2979ff;
      }
    }
  }
  .slot-btn {
    position: relative;
    width: 200rpx;
    height: 200rpx;
    display: flex;
    justify-content: center;
    flex-direction: column;
    align-items: center;
    background: rgb(244, 245, 246);
    border-radius: 10rpx;
    .text {
      font-size: 26rpx;
      margin-top: 20rpx;
      line-height: 40rpx;
    }
  }

  .slot-btn__hover {
    background-color: rgb(235, 236, 238);
  }
  .delete-icon {
    position: absolute;
    top: 10rpx;
    right: 10rpx;
    z-index: 10;
    background-color: #fa3534;
    border-radius: 100rpx;
    width: 44rpx;
    height: 44rpx;
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: center;
  }
  .popup-content {
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
      display: flex;
      justify-content: flex-end;
      padding: 28rpx 10rpx 45rpx;
    }
  }
</style>