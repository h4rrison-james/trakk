$(document).ready(function(){$(document).bind("keyup","h",function(a){if($(a.target).not(":input")){jQuery.facebox({div:"#shortcuts"})}return false});$(document).bind("keyup","s",function(a){if($(a.target).not(":input")){$("#search").select().focus()}return false});$(document).bind("keyup","n",function(a){if($(a.target).not(":input")){window.location="/issues/new"}return false});$(document).bind("keyup","o",function(a){if($(a.target).not(":input")){window.location=$("#menu-overview").attr("href")}return false});$(document).bind("keyup","i",function(a){if($(a.target).not(":input")){window.location=$("#menu-issues-mine").attr("href")}return false});$(document).bind("keyup","p",function(a){if($(a.target).not(":input")){window.location=$("#menu-projects").attr("href")}return false});$(document).bind("keyup","m",function(a){if($(a.target).not(":input")){window.location=$("#menu-milestones").attr("href")}return false});$(document).bind("keyup","c",function(a){if($(a.target).not(":input")){$(".update-status").click()}return false});$(document).bind("keyup","e",function(a){if($(a.target).not(":input")){$(".update-button").click()}return false});$(document).bind("keyup","w",function(a){if($(a.target).not(":input")){$("#comment-box").select().focus()}return false});$(document).bind("keyup","ctrl+u",function(a){if($(a.target).not(":input")){$(".attach-button").click()}return false})});