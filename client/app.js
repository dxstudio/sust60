App({
  onLaunch: function() {
    wx.checkSession({
      success: function(){
        console.log('未过期')
      },
      fail: function(){
        // session_key 已经失效，需要重新执行登录流程
        wx.login({
          success: function(res) {
            if (res.code) {
              console.log(res.code)
              //发起网络请求
              wx.request({
                url: 'https://makergyt.com/sust60/server/public/api/user/saveSession/',
                data: {
                  code: res.code
                },
                success: function(res) {
                  console.log(res.data)
                }
              })
            } else {
              console.log('登录失败！' + res.errMsg)
            }
          }
        });
      }
    })
    
  }
})