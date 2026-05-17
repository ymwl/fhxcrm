<template>
  <view class="container">
    <!-- 顶部导航 -->
    <u-navbar :is-back="true">
      <view class="slot-wrap">
        <u-search style="width:100%" placeholder="搜索联系人名称、手机" v-model="keyword" :input-style="search"
                  :show-action="false" @change="onSearch">>
        </u-search>
      </view>
    </u-navbar>
    <view class="wrap">
      <!-- 排序 -->
      <view class="sort">
        <view class="sort-order-toggle">
          <view class="sort-arrow" :class="{ active: sort_order === 'asc' }" @click.stop="setSortAsc">
            <u-icon name="arrow-up-fill" size="22" :color="sortAscColor"></u-icon>
          </view>
          <view class="sort-arrow" :class="{ active: sort_order === 'desc' }" @click.stop="setSortDesc">
            <u-icon name="arrow-down-fill" size="22" :color="sortDescColor"></u-icon>
          </view>
        </view>
        <u-dropdown :border-bottom="true">
          <u-dropdown-item class="45" v-model="value1" :title="sortName" :options="options1"
                           @change="optionsChange"></u-dropdown-item>
        </u-dropdown>

        <view class="right-text">
          <navigator url="/pages/contacts/filter" hover-class="none">
            <view class="fils">筛选/{{ scopeName }}联系人
              <u-icon name="arrow-right" color="#303133" size="30"></u-icon>
            </view>
          </navigator>
        </view>
      </view>
      <scroll-view scroll-y class="sv" :style="{height:scrollHeight+'px'}" :scroll-top="scrollTop" @scroll="scroll"
                   @scrolltolower="reachBottom">
        <view class="page-box">
          <block v-if="dataList.length > 0">
            <view class="client" v-for="(item, index) in dataList" :key="index" @click="onItem(item)">
              <view class="client-content">
                <fhx-field-display :fields="fields" :item="item"></fhx-field-display>
              </view>
              <view class="client-action">
                <view class="btn entity email-btn" v-if="itemAuth.sendEmail == 1 && item.email"
                      @click.stop="sendEmail(item)">
                  <u-icon name="email" size="26" color="#fff"></u-icon>
                  <text>发邮件</text>
                </view>
                <view class="btn entity" v-if="itemAuth.edit == 1" @click.stop="editItem(item)">
                  <u-icon name="edit-pen" size="26" color="#fff"></u-icon>
                  <text>编辑</text>
                </view>
                <view class="btn" v-if="itemAuth.delete == 1" @click.stop="deleteItem(item)">
                  <u-icon name="trash" size="26" color="#f56c6c"></u-icon>
                  <text>删除</text>
                </view>
              </view>
            </view>
            <u-loadmore :status="listStatus"></u-loadmore>
          </block>
          <u-empty text="暂无数据" v-else margin-top="100" mode="list"></u-empty>
        </view>
      </scroll-view>
    </view>
    <view class="floatBtn" @click="addUser">
      <u-icon class="u-p-b-5" name="plus" size="40" :color="vuex_theme.color"></u-icon>
      添加
    </view>
  </view>
</template>

<script>
import {processingImages, getImgUrl} from '@/common/mUtils'

export default {
  data() {
    return {
      searchTimer: null,
      keyword: '',
      scopeName: '我的',
      sort_by: 'id',
      sortName: '默认排序',
      sort_order: 'desc',
      oldScrollTop: 0,
      scrollTop: -1,
      search: {
        fontSise: '18px'
      },
      fields: [],
      filter: [],
      dataList: [],
      value1: 0,
      options1: [
        {
          label: '默认排序',
          value: 0,
          sort_by: 'id',
        },
        {
          label: '下次跟进',
          value: 1,
          sort_by: 'next_time',
        },
        {
          label: '更新时间',
          value: 2,
          sort_by: 'update_time',
        },
        {
          label: '创建时间',
          value: 3,
          sort_by: 'create_time',
        },

      ],
      dx: 0,
      pH: 0, //窗口高度
      scrollHeight: 0, //元素的所需高度
      page: 1,
      pageSize: 10,
      lastPage: false,
      listStatus: 'loadmore',
      itemAuth: {edit: 0, delete: 0, sendEmail: 0},
    };
  },
  onPageScroll(e) {
    this.scrollToph = e.scrollTop;
  },
  onUnload() {
    uni.setStorageSync('contacts_filter', '')
  },
  filters: {
    //图片地址url 拼接
    changImg(val) {
      if (val) {
        return getImgUrl(val)
      } else {
        return ''
      }
    },
  },
  onReady() {
    let that = this;
    uni.getSystemInfo({ //调用uni-app接口获取屏幕高度
      success(res) { //成功回调函数
        that.pH = res.windowHeight //windoHeight为窗口高度，主要使用的是这个
        let scrollH = uni.createSelectorQuery().select(".sv"); //想要获取高度的元素名（class/id）
        scrollH.boundingClientRect(data => {
          let pH = that.pH;
          that.scrollHeight = pH - data.top  //计算高度：元素高度=窗口高度-元素距离顶部的距离（data.top）
        }).exec()
      }
    })
  },
  onLoad(e) {
    this.getFields();

  },
  onShow() {
    const filter = uni.getStorageSync('contacts_filter');
    if (!this.$u.test.isEmpty(filter)) {
      this.page = 0,
          this.lastPage = false
      this.getList()
    }
  },
  computed: {
    sortAscColor() {
      return this.sort_order === 'asc' ? '#2979ff' : '#c0c4cc';
    },
    sortDescColor() {
      return this.sort_order === 'desc' ? '#2979ff' : '#c0c4cc';
    }
  },
  methods: {
    // 自定义字段
    getFields() {
      let arr = []
      this.$u.get('fields/get_fields', {table: 'crm_customer_contacts', source: 'index'}).then((res) => {
        if (res.code == 1) {
          this.fields = res.data.fields;
          this.getList();

          // 获取参与排序的字段（open_sort为1的就是）赋值给options1，没有则保持默认排序字段
          const sortFields = res.data.fields.filter(f => f.open_sort === 1);
          if (sortFields.length > 0) {
            // 第一项固定为默认排序
            const newOptions = [{
              label: '默认排序',
              value: 0,
              sort_by: 'id',
            }];
            // 动态添加可排序字段
            sortFields.forEach((field, index) => {
              newOptions.push({
                label: field.title,
                value: index + 1,
                sort_by: field.field,
              });
            });
            this.options1 = newOptions;
          }
        }
      })
    },
    // 设为升序
    setSortAsc() {
      if (this.sort_order === 'asc') return;
      this.sort_order = 'asc';
      this.page = 0;
      this.lastPage = false;
      this.getList();
    },
    // 设为降序
    setSortDesc() {
      if (this.sort_order === 'desc') return;
      this.sort_order = 'desc';
      this.page = 0;
      this.lastPage = false;
      this.getList();
    },
    // 排序
    optionsChange() {
      this.sort_by = this.options1[this.value1].sort_by
      this.sortName = this.options1[this.value1].label
      console.log(this.sort_by)
      this.page = 0,
          this.lastPage = false
      this.getList()
    },
    // 格式化时间
    timeFormats(val) {
      return this.$u.timeFormat(val, 'yyyy/mm/dd hh:MM');
    },
    // 页面数据
    getList(isNextPage, pages) {
      // 筛选参数
      let filterObj = {}
      let opObj = {}
      const filter = uni.getStorageSync('contacts_filter');
      if (!this.$u.test.isEmpty(filter)) {
        filterObj = filter.filter
        opObj = filter.op
        this.scopeName = filter.scopeName ? filter.scopeName : '我的'
        this.scope = filter.scope
      } else {
        this.scopeName = '我的'
        this.scope = 1
      }
      this.$u.get('crm.customer_contacts/index', {
        sort_by: this.sort_by,
        sort_order: this.sort_order,
        scope: this.scope,
        search: this.keyword,
        offset: (pages || 0) * this.pageSize,
        limit: this.pageSize,
        filter: JSON.stringify(filterObj),
        op: JSON.stringify(opObj)
      }).then(res => {
        if (res.code == 1) {
          // 不够一页
          if (res.data.rows.length < 10) {
            this.listStatus = 'nomore'
          }
          // 最后一页
          if (res.data.rows.length == 0) {
            this.lastPage = true
          }
          // 第二页开始
          if (isNextPage) {
            this.dataList = this.dataList.concat(res.data.rows)
            return
          }
          this.dataList = res.data.rows;
          // 权限配置
          if (res.auth) {
            this.itemAuth = res.auth;
          }
          //缓存场景给检索列表用
          uni.setStorageSync('contacts_scene', res.data.scene);
        }
      })
    },
    // scroll 滚动记录
    scroll(e) {
      this.oldScrollTop = e.detail.scrollTop; // 必要
    },
    // 滚动到底部
    reachBottom() {
      if (this.lastPage || this.listStatus == 'loading') return;
      this.listStatus = 'loading'
      setTimeout(() => {
        if (this.lastPage) return;
        this.getList(true, ++this.page)
        if (this.dataList.length >= 10) this.listStatus = 'loadmore';
        else this.listStatus = 'loading';
      }, 1200)
    },
    // 点击搜索
    onSearch() {
      clearTimeout(this.searchTimer)
      this.searchTimer = setTimeout(() => {
        this.page = 0
        this.lastPage = false
        this.getList()
      }, 500)
    },
    // 查看详情
    onItem(val) {
      this.$u.route('pages/contacts/detail', {id: val.id});
    },

    // 打电话
    call(id, phone) {
      //判断是否是隐藏号码
      if (phone.indexOf("*") == -1) {
        uni.makePhoneCall({phoneNumber: phone});
      } else {
        //需要后台获取号码
        this.$u.get('crm.customer_contacts/edit', {id: id}).then(res => {
          if (res.code == 1) {
            phone = res.data.mobile ? res.data.mobile : res.data.telephone
            uni.makePhoneCall({phoneNumber: phone});
          }
        })
      }
    },
    // 云呼叫
    cloudcall(id, phone) {
      //判断是否是隐藏号码
      if (phone.indexOf("*") == -1) {
        this.$u.post('crm.setting.cloudcall/call', {
          type: 'customer_contacts',
          typeid: id,
          field: phone,
          prefix: '',
        }).then(res => {
          if (res.code == 1) {
            // 提示
            uni.showToast({
              title: '操作成功',
              icon: 'success',
              duration: 2000
            })
          }
        })
      } else {
        //需要后台获取号码
        this.$u.get('crm.customer_contacts/edit', {id: id}).then(res => {
          if (res.code == 1) {
            phone = res.data.mobile ? res.data.mobile : res.data.telephone
            this.$u.post('crm.setting.cloudcall/call', {
              type: 'customer_contacts',
              typeid: id,
              field: phone,
              prefix: '',
            }).then(res => {
              if (res.code == 1) {
                // 提示
                uni.showToast({
                  title: '操作成功',
                  icon: 'success',
                  duration: 2000
                })
              }
            })
          }
        })
      }
    },

    // 添加联系人
    addUser() {
      this.$u.route('pages/contacts/addPerson', {
        type: 'add'
      });
    },
    // 编辑联系人
    editItem(item) {
      this.$u.route('pages/contacts/addPerson', {
        type: 'edit',
        id: item.id
      });
    },
    // 删除联系人
    deleteItem(item) {
      uni.showModal({
        title: '提示',
        content: '确定删除该联系人吗？',
        success: (res) => {
          if (res.confirm) {
            this.$u.post('crm.customer_contacts/delete', {id: item.id}).then(res => {
              if (res.code == 1) {
                uni.showToast({title: '删除成功', icon: 'success', duration: 2000});
                setTimeout(() => {
                  this.page = 1;
                  this.lastPage = false;
                  this.getList();
                }, 1000);
              }
            });
          }
        }
      });
    },
    // 发送邮件
    sendEmail(item) {
      this.$u.route('pages/contacts/sendEmail', {
        id: item.id,
        customer_id: item.customer_id || '',
        email: item.email || ''
      });
    }
  }
}
</script>

<style lang="scss">
.container {
  background-color: #F7F7F7;
  min-height: 100vh;
}

.slot-wrap {
  display: flex;
  align-items: center;
  padding-right: 30rpx;
  flex: 1;
}

.page-box {
  padding: 20rpx 20rpx 45rpx;
}

.client {
  background-color: #ffffff;
  margin-bottom: 20rpx;
  border-radius: 20rpx;
  box-sizing: border-box;
  padding: 20rpx;
  font-size: 28rpx;

  .client-action {
    display: flex;
    justify-content: flex-end;
    margin-top: 16rpx;
    padding-top: 16rpx;
    border-top: 1rpx solid #f0f0f0;

    .btn {
      display: inline-flex;
      align-items: center;
      gap: 6rpx;
      line-height: 56rpx;
      padding: 0 32rpx;
      border-radius: 8rpx;
      font-size: 26rpx;
      text-align: center;
      white-space: nowrap;
      margin-left: 16rpx;
      color: #f56c6c;
      background-color: #fef0f0;
      border: 1rpx solid #fde2e2;
    }

    .entity {
      color: #fff;
      background-color: #2979ff;
      border: 1rpx solid #2979ff;
    }

    .email-btn {
      background-color: #4bb368;
      border-color: #4bb368;
    }
  }

  .top {
    display: flex;
    justify-content: space-between;

    .left {
      display: flex;
      align-items: center;

      .store {
        font-size: 28rpx;
        font-weight: bold;
      }
    }

    .right {
      color: #FF7159;
    }
  }

  .item {
    display: flex;
    align-items: center;
    // margin: 20rpx 0 0;
    .content {
      flex: 1;

      .title {
        font-size: 30rpx;
        font-weight: 600;
        line-height: 50rpx;
      }

      .type {
        margin: 10rpx 0;
        font-size: 24rpx;
        color: $u-tips-color;
      }
    }

    .right {
      margin-left: 10rpx;
      text-align: right;

      .dial {
        color: #2979ff;
        font-size: 14px;
        border-bottom: 1px solid #2979ff;
        padding-bottom: 0px;
      }
    }
  }

  .total {
    margin-top: 20rpx;
    text-align: right;
    font-size: 24rpx;

    .total-price {
      font-size: 32rpx;
    }
  }

  .bottom {
    display: flex;
    margin-top: 20rpx;
    justify-content: space-between;
    align-items: center;

    .client_time {
      color: #777;
      font-size: 26rpx;
    }

    .btn {
      line-height: 60rpx;
      width: 160rpx;
      border-radius: 5px;
      font-size: 26rpx;
      text-align: center;
      color: $u-type-info-dark;
    }

    .sky {
      color: #FF6146;
      background-color: #F7F7F7;
    }

    .entity {
      color: #fff;
      background-color: #FF6146;
    }
  }
}

.wrap {
  display: flex;
  flex-direction: column;
  width: 100%;
}

.sort {
  position: relative;
  display: flex;
  align-items: center;
  background: #fff;
  height: 100%;

  .sort-order-toggle {
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 44rpx;
    height: 100%;
    padding: 0 8rpx;

    .sort-arrow {
      display: flex;
      align-items: center;
      justify-content: center;
      height: 22rpx;
      line-height: 1;
      border-radius: 4rpx;
      transition: background-color 0.2s;

      &.active {
        background-color: rgba(41, 121, 255, 0.1);
      }
    }
  }

  .right-text {
    position: absolute;
    display: flex;
    right: 10px;
    height: 100%;
    z-index: 100;

    .fils {
      display: flex;
      align-items: center;
      height: 100%;
    }
  }

  /deep/ .u-dropdown__menu__item {
    justify-content: left !important;
    padding-left: 0rpx;
  }
}

.floatBtn {
  font-size: 23rpx;
  bottom: 100px;
  right: 20px;
  border-radius: 5000px;
  z-index: 9;
  opacity: 1;
  width: 130rpx;
  height: 130rpx;
  position: fixed;
  display: flex;
  flex-direction: row;
  flex-direction: column;
  justify-content: center;
  background-color: #fff;
  color: #606266;
  align-items: center;
  transition: opacity 0.4s;
  border: 1px solid #dcdfe6;
}


</style>
