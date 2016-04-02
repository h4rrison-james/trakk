$(document).ready(function(){$(".update-status").bind("click",function(){jQuery.facebox({div:"#update-status"});return false});$(".update-button").bind("click",function(){jQuery.facebox({div:"#update-box"});return false});$(".attach-button").bind("click",function(){jQuery.facebox({div:"#attach-box"});return false});loadRelatedIssues()});$(".comment-box").autoResize();closetimer=null;function closeCommentHelper(){if($(".comment-box").val().length==0){$(".comment-box-helper").slideUp()}}function cancelCommentHelperTimer(){if(closetimer){window.clearTimeout(closetimer);closetimer=null}}$(".comment-box").focusin(function(){cancelCommentHelperTimer();$(".comment-box-helper").slideDown()});$(".comment-box").focusout(function(){window.setTimeout(closeCommentHelper,600)});$("#attach-file").submit(function(){$(".upload-holder").hide();$(".throbber-holder").show();$(".button").attr("disabled","disabled")});function updateCategoriesFromFacebox(){var a=$("#facebox").find("#project").val();updateCategories(a)}function updateCategoriesFromNew(){var a=$("#project").val();updateCategories(a)}function updateCategories(a){$(".category-throbber").fadeIn();$.ajax({url:"/issues/js-get-categories",dataType:"json",type:"POST",data:({project_id:a}),success:function(d){if(d.status){if(d.categories.length>0){var b='<option value="">None</option>';for(var c=0;c<d.categories.length;c++){b+='<option value="'+d.categories[c].id+'">'+d.categories[c].name+"</option>"}$(".categories").html(b);$(".categories-holder").show()}else{$(".categories").html();$(".categories-holder").hide()}}else{$(".categories").html();$(".categories-holder").hide()}},error:function(b){$(".categories").html();$(".categories-holder").hide()},complete:function(){$(".category-throbber").fadeOut()}})}function followIssue(a){$.ajax({url:"/issues/js-follow-issue",dataType:"json",type:"POST",data:({issue_id:a}),success:function(b){if(b.status){$(".follow").find("a").removeClass("follow-icon").addClass("unfollow-icon").attr("onclick","unFollowIssue('"+a+"'); return false;").attr("title","Un-Follow Issue")}else{}},error:function(b){},complete:function(){}})}function unFollowIssue(a){$.ajax({url:"/issues/js-unfollow-issue",dataType:"json",type:"POST",data:({issue_id:a}),success:function(b){if(b.status){$(".follow").find("a").removeClass("unfollow-icon").addClass("follow-icon").attr("onclick","followIssue('"+a+"'); return false;").attr("title","Follow Issue")}else{}},error:function(b){},complete:function(){}})}function editComment(a){var b=$("#comment-"+a);jQuery.facebox({div:"#edit-comment-box"});$("#facebox #edit-comment-content").val(b.val());$("#facebox #edit-comment-id").val(a);$("#facebox #edit-comment-content").focus()}function replyToComment(){$("#comment-box").focus()}function showHistory(a){if($(".issue-history-holder").is(":hidden")){$(".issue-history-holder").fadeIn();$(".show-history-button").html("Loading...");$.ajax({url:"/issues/js-get-history",dataType:"json",type:"POST",data:({issue_id:a}),success:function(b){if(b.status){if(b.history.length>0){$(".issue-history").html(b.history)}else{$(".issue-history").html("No history found.")}}else{}},error:function(b){},complete:function(){$(".issue-history-throbber").fadeOut("fast",function(){$(".issue-history").fadeIn()});$(".show-history-button").html("Hide History")}})}else{$(".issue-history-holder").fadeOut();$(".show-history-button").html("Show History")}}var fetchedRelatedIssuesCount=0;function loadRelatedIssues(){var a=$("#issueId").val();$.ajax({url:"/issues/js-get-related-issues",dataType:"json",type:"GET",data:({issueId:a}),success:function(b){if(b.status){if(b.related&&b.related.length>0){$(".issue-overview-related-issues").html(b.related);$(".issue-overview-related-issues-box").fadeIn()}else{$(".issue-overview-related-issues-box").fadeOut()}}},error:function(b){},complete:function(){fetchedRelatedIssuesCount++;if(fetchedRelatedIssuesCount<2){setTimeout(loadRelatedIssues,1000)}}})}function removeRelationship(b,a){$.ajax({url:"/issues/js-remove-related-issue",dataType:"json",type:"POST",data:({issueId:b,relatedIssueId:a}),success:function(c){$(".related-issue-row-"+a).fadeOut();showMessage("ok",'Removed relationship with <a href="/issues/'+a+'">#'+a+"</a>.")},error:function(c){},complete:function(){setTimeout(loadRelatedIssues,500)}})};