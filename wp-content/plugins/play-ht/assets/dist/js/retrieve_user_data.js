!function(e,a,t){a(function(){a("#welcome_msg__loader").show(),wppp_retrieve&&wppp_retrieve.user_id&&a.ajax({url:"https://play.ht/api/user/fetchById",type:"POST",data:{token:"k35tyWMmBaf81CZcw4bqUuAJIgrzhENd",userId:wppp_retrieve.user_id},success:function(e,t){var l=e.data;!function(e){var t={"wp-personal-monthly":{type:"monthly",allowance:"10"},"wp-professional-monthly":{type:"monthly",allowance:"35"},"wp-premium-monthly":{type:"monthly",allowance:"100"},"wp-personal-yearly":{type:"yearly",allowance:"120"},"wp-professional-yearly":{type:"yearly",allowance:"420"},"wp-premium-yearly":{type:"yearly",allowance:"1200"},"writer-yearly":{type:"yearly",allowance:"120"},"writer-monthly":{type:"monthly",allowance:"10"},"publication-yearly":{type:"yearly",allowance:"420"},"revised-starter-monthly":{type:"monthly",allowance:"10"},"publication-monthly":{type:"monthly",allowance:"35"}};if(e&&e.packages){var l="",p="";Object.keys(e.packages).map(function(a){l=e.packages[a].name,p=a});var r=t[p]&&2e3*t[p].allowance;a("#__play_package_type, #__play_total_credits").show(),a("#__play_package_type").text()&&!a("#__play_package_type").text().indexOf("Play")>-1&&!a("#__play_package_type").text().indexOf("WP ")>-1&&l&&a("#__play_package_type").html(l),r&&a("#__play_total_credits").html(r)}(e&&!e.is_pro||e&&!e.packages)&&(a("#__play_package_type").html("Free Package"),a("#__play_total_credits").html("0")),e&&(e.imageUrl&&a("#__play_profile_pic").attr("src",e.imageUrl),e.name&&a(".user-div .welcome_msg #user_id").html(e.name),e.usage&&a(".user-div .remaining_credits").html(e.usage.words_count)),a("#welcome_msg__loader").hide()}(l),a.ajax({type:"POST",url:wppp_retrieve.ajax_url,data:{userData:l,action:"retrieve_user_data"},success:function(e){setCookie("__play_userdata_saved",!0,300)}})},error:function(e){reject(e)}})})}(window,jQuery);