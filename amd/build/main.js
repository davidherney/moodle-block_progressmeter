define(["jquery"],function(e){M.cfg.wwwroot;var r=function(e,t,s){t<=s&&s<101&&(e.addClass("progressmeter_level-"+t),setTimeout(function(){r(e,t+1,s)},100))};return{init:function(t,s){var n=e("#progressmeter_personal .progressmeter_level"),o=e("#progressmeter_team .progressmeter_level");r(n,1,t),o.length>0&&r(o,1,s)}}});