
var isfullscreen=false;var embedded_mcfh=0;var embedded_mch=0;var embedded_mcw=0;var embedded_header_height=0;var leftbar_width=0;var feedbackcentered=false;var popupcentered=false;var childpopupcentered=false;var whiteoutfull=false;var is_mobile=false;var inframe=(window.location!=window.parent.location)?true:false;$(document).on('change','.btn-file :file',function(){var input=$(this),numFiles=input.get(0).files?input.get(0).files.length:1,label=input.val().replace(/\\/g,'/').replace(/.*\//,'');input.trigger('fileselect',[numFiles,label]);});document.addEventListener('keydown',(event)=>{if(event.key==='k'&&(event.metaKey||event.ctrlKey)){event.preventDefault();window.parent.postMessage("search",window.location.origin);}});$(document).ready(function(){$('body').tooltip({selector:'.tt-bind',container:'body'});$('body').popover({selector:'.pop',html:true,container:'body'});$('body').tooltip({selector:'.tt-delay-bind',delay:{show:1200,hide:100},container:'body'});$('.sk-spinner-center').center(false);$('#mdropdown,.ext').click(function(){$(this).toggleClass('menu-expand-children')});$('body').tooltip({selector:'[rel=tooltip]'});$('body').on('click','.msg-show-details',function(){if($('.msg-details').is(':visible')){$('.msg-show-details').html(_('Show Details'));$('.msg-show-details-icon').removeClass('fa-chevron-down').addClass('fa-chevron-up');$('.msg-details').hide();}else{$('.msg-show-details').html(_('Hide Details'));$('.msg-show-details-icon').removeClass('fa-chevron-up').addClass('fa-chevron-down');$('.msg-details').show();}});$('body').on('click','.msg-close',function(){$(this).parent().hide();$('.helpsystem_icon').delay(300).fadeIn(300);});$('.btn-file :file').on('fileselect',function(event,numFiles,label){var input=$(this).parents('.input-group').find(':text');$(input).val(label);});if(!inframe){if($('body > .enterprisefeaturenotice').length>0){$('body.child').css('padding-top','31px');}}
if(!inframe&&is_neptune()){$(".enterprisefeaturenotice").css("display","flex");}
if(window==top){resize_content(false);center_content_throbbers();}
$(window).resize(function(){if(window==top){resize_content(false);}
center_content_throbbers();if($('.xi-modal:visible').length>0){$('.xi-modal:visible').center();}
if($('#child_popup_layer').is(":visible")){center_child_popup();}
if($('#popup_layer').is(":visible")){center_popup();}
if($('#whiteout').is(":visible")){whiteout(whiteoutfull);}
if($('#blackout').is(":visible")){blackout();}});$('.servicestatustable').on('change','.tablepagerselect',function(){$('.tablepagerselect').val($(this).val());});$('.servicestatustable').on('keyup','.tablepagertextfield',function(){$('.tablepagertextfield').val($(this).val());});$('.hoststatustable').on('change','.tablepagerselect',function(){$('.tablepagerselect').val($(this).val());});$('.hoststatustable').on('keyup','.tablepagertextfield',function(){$('.tablepagertextfield').val($(this).val());});$('.state_summary').on('click','.show-details',function(){var opts={"show_details":0};var longout=$(this).parents('.summary-status').find('.longtext');if(longout.is(":visible")){$(this).text("Show details");$(this).parent().find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');longout.hide();opts.show_details=0;}else{$(this).text("Hide details");$(this).parent().find('i').removeClass('fa-chevron-up').addClass('fa-chevron-down');longout.show();opts.show_details=1;}
get_ajax_data("setsessionvars",JSON.stringify(opts));});$("#closetrialnotice").click(function(){var p=this.parentNode;var gp=p.parentNode;$(gp).remove();var optsarr={"ignore_trial_notice":1};var opts=JSON.stringify(optsarr);get_ajax_data("setsessionvars",opts);$('#maincontent').css('top','inherit');var v=$('#maincontentframe').contents().find('.enterprisefeaturenotice').outerHeight();var tpad=v+$('.parenthead').outerHeight();if($('.contentheadernotice').length>0){tpad+=$('.contentheadernotice').outerHeight();$('#maincontent').css('top',tpad+'px');}else{$('#maincontent').css('top','inherit');}
resize_content();});$(".acknowledge_banner_message_btn").click(function(){var p=this.parentNode;var gp=p.parentNode;$(gp).remove();var msg_id=$(this).attr('data-id');$.post("/nagiosxi/admin/banner_message-ajaxhelper.php",{action:'acknowledge_banner_message',id:msg_id},function(){}).done(function(response){resize_content();flash_message(response.message,response.msg_type,true);}).fail(function(xhr,error_string,error_thrown){flash_message("<?php echo _('An error occured: ')?>"+error_string,'error');});});$(".dismiss_neptune_switch").click(function(){$.post("/nagiosxi/admin/banner_message-ajaxhelper.php",{action:'neptune_banner_handler',cmd:'dismiss'},function(){}).done(function(){$('#switch_neptune_banner').remove();resize_content();})});$(".never_show_neptune_switch").click(function(){$.post("/nagiosxi/admin/banner_message-ajaxhelper.php",{action:'neptune_banner_handler',cmd:'never_show'},function(){}).done(function(){$('#switch_neptune_banner').remove();resize_content();})});$(".neptune_switch").click(function(){$.post("/nagiosxi/admin/banner_message-ajaxhelper.php",{action:'neptune_banner_handler',cmd:'switch'},function(){}).done(function(){location.reload()})});$("#closefreenotice").click(function(){var p=this.parentNode;var gp=p.parentNode;$(gp).remove();var optsarr={"ignore_free_notice":1};var opts=JSON.stringify(optsarr);get_ajax_data("setsessionvars",opts);resize_content();});$("#close-25-year-celebration-notice").click(function(){var p=this.parentNode;var gp=p.parentNode;$(gp).remove();var optsarr={"ignore_promo_notice":1};var opts=JSON.stringify(optsarr);get_ajax_data("setsessionvars",opts);resize_content();});$("#close-core-services-platform-notice").click(function(){var p=this.parentNode;var gp=p.parentNode;$(gp).remove();var optsarr={"ignore_core_services_platform_notice":1};var opts=JSON.stringify(optsarr);get_ajax_data("setsessionvars",opts);resize_content();});$("#slicense-link").click(function(){var backgroundcolor="ffffff";if(theme=='xi5dark'){backgroundcolor="111111";}
var content=`
<div id='popup_header' style='margin-bottom: 20px;'>
    <b>SLICENSE.TXT</b>
</div>
<div id='popup_data'>
    <pre>
    When used with a promotional or anniversary product key,
    this product may only be used for PERSONAL, TRAINING AND NON-COMMERCIAL USE. 

    To obtain a commercial license, which entitles you to extra benefits, visit <a href="https://www.nagios.com" target="_blank">www.nagios.com</a>.

    In the event that Nagios Enterprises, LLC is no longer in business or operation,
    and as long as Nagios Enterprises,
    LLC has not transferred rights to this software to another entity,
    you are permitted to use this software for COMMERCIAL use as well as PERSONAL and EDUCATIONAL use.
    </pre>
</div>`;hide_throbber();set_popup_content(content);display_popup(850,250);});$("#topmenucontainer ul.menu li a").click(function(){show_throbber();});$('#maincontentframe').load(function(){try{if($('#maincontentframe').contents().find('.enterprisefeaturenotice').length>0){var v=$('#maincontentframe').contents().find('.enterprisefeaturenotice').outerHeight();var tpad=v+$('.parenthead').outerHeight();$('.contentheadernotice').each(function(){tpad+=$(this).outerHeight();});$('#fullscreen').css('top',tpad+'px');$('#maincontentframe').contents().find('.childpage').css('margin-top',v+'px');}else{if(isfullscreen){return;}else{$('#fullscreen').removeAttr('style');}}}catch(e){}});$("#fullscreen").click(function(){var x=1;if(!isfullscreen){$("body").css("margin","0");$("#leftnav").hide();$("#header").hide();$("#footer").hide();$("#viewtools").hide();isfullscreen=true;$("#maincontentframe").css('width',"100%");$("#maincontent").css('top','0px');$('#fullscreen').css('left','0px');$('#fullscreen').css('top','0px');$('#fullscreen').removeClass('fs-open').addClass('fs-close');do_fullscreen();}else{$("body").css("margin",bodymargin+"px");$("#leftnav").show();$("#header").show();$("#footer").show();$("#viewtools").show();isfullscreen=false;$('#fullscreen').removeAttr('style');$('#fullscreen').removeClass('fs-close').addClass('fs-open');resize_content();}});$.widget("custom.myautocomplete",$.ui.autocomplete,{_renderMenu:function(ul,items){var that=this,currentCategory="";ul.addClass('dropdown-menu');$.each(items,function(index,item){if(typeof item.category!=="undefined"&&item.category!=currentCategory){ul.append('<li class="dropdown-header">'+item.category+'</li>');currentCategory=item.category;}
that._renderItemData(ul,item);});}});$("#userList #searchBox").each(function(){$(this).myautocomplete({source:suggest_url+'?type=users',minLength:1});});$("#banner_messageList #banner_messageSearchBox").each(function(){$(this).myautocomplete({source:suggest_url+'?type=users',minLength:1});});$("#perfgraphspage #searchBox").each(function(){$(this).myautocomplete({source:suggest_url+'?type=host',minLength:1});});$("#navbarSearchBox").each(function(){$(this).myautocomplete({source:suggest_url+'?type=multi',minLength:1,select:function(e,ui){$('#maincontentframe').attr('src',ui.item.url);e.preventDefault();$("#navbarSearchBox").val('');}});});$('#perf-options-btn').on('click',function(){whiteout();$('body.child').css('overflow','hidden')
$('.neptune-drawer-options').addClass("drawer-options-visible");});$('#whiteout, button#run, #close-perf-options').on('click',function(){$('.neptune-drawer-options').removeClass("drawer-options-visible");$('body.child').css('overflow','')
clear_whiteout();});$('.btn-report-action').on('mouseup',function(e){var href=$(this).data('url');var url=location.pathname+"?"+$("form").serialize();href+="&url="+encodeURIComponent(url);if(e.which==2){window.open(href);}else if(e.which==1){window.location=href;}});var datepickerup=false;$.datepicker.setDefaults({closeText:'X',altFormat:'yy-mm-dd',dateFormat:'yy-mm-dd'});$('.reportstartdatepicker').click(function(){if(datepickerup){$('#startdatepickercontainer').datepicker('destroy');datepickerup=false;}else{datepickerup=true;$('#startdatepickercontainer').datepicker({closeText:'X',onSelect:function(dateText,inst){$('#startdateBox').val(dateText);$('#startdatepickercontainer').datepicker('destroy');$('#reportperiodDropdown').val('custom');}});}});$('.reportenddatepicker').click(function(){if(datepickerup){$('#enddatepickercontainer').datepicker('destroy');datepickerup=false;}else{datepickerup=true;$('#enddatepickercontainer').datepicker({closeText:'X',onSelect:function(dateText,inst){$('#enddateBox').val(dateText.concat(' 23:59:59'));$('#enddatepickercontainer').datepicker('destroy');$('#reportperiodDropdown').val('custom');}});}});if($('#tab_hash').val()){$("#tabs").tabs({activate:function(event,ui){var href=$(ui.newTab).find('a').prop('href');var hash=href.substring(href.indexOf('#')+1);if(history.pushState){history.pushState(null,null,'#'+hash);}else{window.location.hash=hash;}
$('#'+$('#tab_hash').val()).val(hash);$('#tab_hash').val(hash);}}).show();}
$('.btn-show-password').click(function(){var btn_parent=$(this).parent();var type=btn_parent.find('input').attr('type');if(typeof type==="undefined"){btn_parent=btn_parent.parent();type=btn_parent.find('input').attr('type');}
var tooltip_id=$(this).attr('aria-describedby');if(type=='password'){btn_parent.find('input').attr('type','text');$(this).attr('data-original-title',_("Hide password"));$('#'+tooltip_id+' .tooltip-inner').text(_("Hide password"));var icon=$(this).find('i');if(icon.hasClass('material-symbols-outlined')){icon.html('visibility_off');}
else{icon.removeClass('fa-eye').addClass('fa-eye-slash')}}else{btn_parent.find('input').attr('type','password');$(this).attr('data-original-title',_("Show password"));$('#'+tooltip_id+' .tooltip-inner').text(_("Show password"));var icon=$(this).find('i');if(icon.hasClass('material-symbols-outlined')){icon.html('visibility');}
else{icon.removeClass('fa-eye-slash').addClass('fa-eye');}}});$("#schedulepagereport a").click(function(){regexpNagiosChild=/(http[s]?:\/\/)(.*\/nagiosxi\/)(.*)/;var success=true;var baseurl=base_url+"/includes/components/scheduledreporting/schedulereport.php?type=page";try{var windowurl=$('#maincontentframe').contents()[0].URL;console.log(windowurl);}catch(err){success=false;}
if(windowurl.indexOf("components/graphexplorer")!==-1){success=false;}
var xi_basedir=1;var newwindowurl='';if(success){if(windowurl.match(regexpNagiosChild)){newwindowurl=RegExp.$3;}else{newwindowurl=windowurl;xi_basedir=0;}
var theurl=baseurl+"&url="+encodeURIComponent(newwindowurl)+"&wurl="+encodeURIComponent(windowurl)+"&xi_basedir="+xi_basedir;$("#maincontentframe").attr({src:theurl});}else{alert(_("The page you are requesting cannot be scheduled.")+"\n\n"+_("The page is either not optimized to be scheduled or is not a part of the Nagios XI system."));}});$("#permalink a").click(function(){show_throbber();theurl=get_permalink(window,$('#maincontentframe').contents()[0]);var jsurl=base_url+"/includes/js/jquery/";content="<form><div id='popup_header'><b>"+_('Permalink')+"</b></div><div id='popup_data'><p>"+_('Copy the URL below to retain a direct link to your current view.')+"</p></div><div style='margin-bottom: 10px;'><input type='text' size='50' class='textfield form-control' style='width: 100%;' name='url' id='permalinkURLBox' value='"+theurl+"'></div><div><a id='permalink-copy' href='#' data-clipboard-target='#permalinkURLBox' class='btn btn-sm btn-copy btn-primary'>"+_('Copy to Clipboard')+"</a></div></form><script>$('.btn-copy').tooltip({ trigger: 'click', placement: 'bottom' }); var clipboard = new Clipboard('.btn-copy'); clipboard.on('success', function(e) { setTooltip(e.trigger, '"+_('Copied')+"!'); hideTooltip(e.trigger); }); clipboard.on('error', function(e) { setTooltip(e.trigger, '"+_('Press Ctrl+C to copy')+"'); hideTooltip(e.trigger); });</script>";$("#popup_layer").draggable({disabled:true});hide_throbber();set_popup_content(content);display_popup(450);});$(".menusectiontitle").click(function(e){var target=$(e.target);if(target.is('a')){return;}
var menusection=$(this).parents('.menusection');if(menusection.hasClass("menusection-collapsed")){menusection.removeClass("menusection-collapsed");menusection.find('.fa.fa-chevron-up').removeClass('fa-chevron-up').addClass('fa-chevron-down').attr('title','');var optsarr={"keyname":"menu_collapse_options","menuid":$(this).data('id'),"keyvalue":1,"autoload":false};var opts=JSON.stringify(optsarr);get_ajax_data('setusermeta',opts);}else{menusection.addClass("menusection-collapsed");menusection.find('.fa.fa-chevron-down').removeClass('fa-chevron-down').addClass('fa-chevron-up').attr('title','');var optsarr={"keyname":"menu_collapse_options","menuid":$(this).data('id'),"keyvalue":0,"autoload":false};var opts=JSON.stringify(optsarr);get_ajax_data('setusermeta',opts);}});$("#popout a").click(function(){var theurl=$('#maincontentframe').contents()[0].URL;window.open(theurl);});$("#popup_layer").each(function(){$(this).draggable();});$("#close_popup_link").click(function(){close_popup();});$("#child_popup_layer").each(function(){$(this).draggable();});$("#close_child_popup_link").click(function(){close_child_popup();clear_whiteout();});$("#child_popup_layer").on('click',".close_child_popup_btn",function(){close_child_popup();clear_whiteout();});$(":submit").click(function(){hide_message();});$("#get_online_help_link").click(function(){hide_throbber();});$(".tablesorter tbody tr td a").click(function(){hide_throbber();});$("#feedbacklayer a").click(function(){hide_throbber();});$("#notices a").click(function(){hide_throbber();});$('#tabs').click(function(){$('.pop').popover('hide');});$('#tabs ul.tabnavigation a i').parent().find("span").hide();$('#tabs ul.tabnavigation li').first().find("span").show();$('#tabs ul.tabnavigation a i').parent().tooltip();$('#tabs ul.tabnavigation a').click(function(){$('#tabs ul.tabnavigation a i').parent().find("span").hide();$(this).find("span").show();});$("#notices").draggable();$("#close_notices_link").click(function(){hide_throbber();$.ajax({type:"POST",url:this.href,nsp:nsp_str});$("#notices").remove();return false;});$("#feedback_layer").each(function(){$(this).draggable();});$("#close_feedback_link").click(function(){hide_throbber();$("#feedback_layer").css("visibility","hidden");});$("#feedback a").click(function(){show_throbber();if(!feedbackcentered){center_feedback();}
hide_throbber();$("#feedback_layer").css("visibility","visible");$("#feedback_container").each(function(){if(this.origHTML){this.innerHTML=this.origHTML;}});$("#feedback_form textarea").each(function(){this.value='';});init_feedback_submit();});$("#send_feedback").click(function(){show_throbber();if(!feedbackcentered){center_feedback();}
hide_throbber();$("#feedback_layer").css("visibility","visible");$("#feedback_container").each(function(){if(this.origHTML){this.innerHTML=this.origHTML;}});$("#feedback_form textarea").each(function(){this.value='';});init_feedback_submit();});$('#open-search').click(function(){$(this).hide();$('.search-field').fadeIn('fast');$('input.search-query').focus();});$('input.search-query').blur(function(){var s=$(this).val();if(s==""){$('.search-field').hide();$('#open-search').show();}});function init_feedback_submit()
{$("#feedback_form").submit(function(){hide_throbber();var params={};$(this).find(":input, :password, :checkbox, :radio, :submit, :reset").each(function(){params[this.name||this.id||this.parentNode.name||this.parentNode.id]=this.value;});$.ajax({type:"POST",url:this.getAttribute("action"),data:params,beforeSend:function(XMLHttpRequest){$("#feedback_container").each(function(){this.origHTML=this.innerHTML;this.innerHTML="<div id='feedback_header'><b>"+_('Sending Feedback')+"...</b></div><div id='feedback_data'><p>"+_('Please Wait')+"...</p><div id='throbber' class='sk-spinner sk-spinner-center sk-spinner-three-bounce'><div class='sk-bounce1'></div><div class='sk-bounce2'></div><div class='sk-bounce3'></div></div></div>";});},success:function(msg){$("#feedback_container").each(function(){this.innerHTML="<div id='feedback_header'><b>"+_("Thank You!")+"</b></div><div id='feedback_data'><p>"+_("Thanks for helping to make this product better! We will review your comments as soon as we get a chance. Until then, kudos to you for being awesome and helping drive innovation!")+"</p></div>";});},error:function(msg){$("#feedback_container").each(function(){this.innerHTML="<div id='feedback_header'><b>"+_('Error')+"</b></div><div id='feedback_data'><p>"+_('An error occurred. Please try again later.')+"</p></div>";});}});return false;});}
$(document).keydown(function(e){if(e.keyCode==116||(e.ctrlKey&&e.keyCode==82)){if(inframe){e.preventDefault();e.stopPropagation();self.location.reload(true);}else{if($('#maincontentframe').length>0){e.preventDefault();e.stopPropagation();document.getElementById('maincontentframe').contentDocument.location.reload(true);}}}});if($('#open-settings').length>0){var p=$('#open-settings').position();var os_left=Math.floor(p.left+13)-($('#settings-dropdown').outerWidth()/2);var os_top=Math.floor(p.top+$('#open-settings').outerHeight()-13);$('#settings-dropdown').css('left',os_left+'px');$('#settings-dropdown').css('top',os_top+'px');$("#open-settings").click(function(){var d=$("#settings-dropdown").css("display");if(d=="none"){$("#settings-dropdown").fadeIn("fast");}else{$("#settings-dropdown").fadeOut("fast");}});}
$('#settings-dropdown .btn-close-settings').click(function(){$("#settings-dropdown").fadeOut("fast");});$('#settings-dropdown .btn-update-settings').click(function(){var opts=[];$('#settings-dropdown .content input[type="checkbox"]').each(function(){var val=0;if($(this).is(":checked")){val=$(this).val();}
var opt={"keyname":$(this).attr('name'),"keyvalue":val};opts.push(opt);});$('#settings-dropdown .content input[type="text"]').each(function(){var val=$(this).val();var opt={"keyname":$(this).attr('name'),"keyvalue":val};opts.push(opt);});json=JSON.stringify(opts);$.ajax({type:"GET",url:ajax_helper_url,data:{cmd:"setusermeta",opts:json,nsp:nsp_str},success:function(d){$('#settings-dropdown').trigger("ps_saved",opts);}});$("#settings-dropdown").fadeOut("fast");});});function exporting_url(exporting_object,export_type){var export_rrd_url="/nagiosxi/includes/components/xicore/export-rrd.php";window.location=export_rrd_url+'?host='+exporting_object.host+'&service='+exporting_object.service+'&start='+exporting_object.start+'&end='+exporting_object.end+'&step='+exporting_object.step+'&type='+export_type+'&nsp='+nsp_str;}
function whiteout(full)
{if((inframe||$('#maincontent').length==0)||full){whiteoutfull=true;$('#whiteout').width($(window).width());$('#whiteout').height($(window).height());$('#whiteout').css('position','fixed');$('#whiteout').css('top','0');$('#whiteout').css('left','0');}else{var w=$('#maincontent').width();var h=$('#maincontent').height();var t=$('#maincontent').css('top');var l=$('#maincontent').css('left');$('#whiteout').width(w);$('#whiteout').height(h);$('#whiteout').css('top',t);$('#whiteout').css('left',l);$('#whiteout').css('position','absolute');}
$('#whiteout').show();}
function clear_whiteout(fadeout,delay)
{if(typeof fadeout==="undefined"){fadeout=0;}
if(typeof delay==="undefined"){delay=0;}
whiteoutfull=false;if($('.perfdata-popup').is(':visible')){$('#whiteout').css('z-index',9000);return;}
if(fadeout!=0){$('#whiteout').delay(delay).fadeOut(fadeout);return;}
$('#whiteout').hide();}
function blackout()
{blackout_resize();$('#blackout').show();}
function blackout_resize()
{$('#blackout').css('width',$(window).width()+'px');$('#blackout').css('height',$(window).height()+'px');$('#blackout').css('position','fixed');$('#blackout').css('top','0');$('#blackout').css('left','0');}
function clear_blackout()
{$('#blackout').hide();}
function center_feedback()
{var l=$("#feedback_layer");var wh=$(window).height();var ww=$(window).width();var lh=$(l).height();var lw=$(l).width();var newtop=(wh-lh)/2;var newleft=(ww-lw)/2;$("#feedback_layer").css("top",newtop);$("#feedback_layer").css("left",newleft);feedbackcentered=true;}
function center_login_alert_popup()
{var l=$("#feedback_layer");var wh=$(window).height();var ww=$(window).width();var lh=$(l).height();var lw=$(l).width();var newtop=(wh-lh)/2;var newleft=(ww-lw)/2;$("#login_alert_popup").css("top",newtop);$("#login_alert_popup").css("left",newleft);feedbackcentered=true;}
function get_datetime_string(s,z)
{var r="??";$.ajax({type:"GET",async:false,url:ajax_helper_url,data:{cmd:"getdatetimestring",t:s,zs:z,nsp:nsp_str},success:function(data){r=data;}});return r;}
function submit_report(event,icon){return async(response)=>{if(response.ok){const json=await response.json();if(json.command_id>0){const checkInterval=setInterval(()=>{let ajaxData=JSON.parse(get_ajax_data("getcommandstatus",json.command_id));if(ajaxData.result!==null){clearInterval(checkInterval);if(ajaxData.result!==""){downloadurl=base_url+"/reports/managereports.php?mode=download&command_id="+json.command_id
if(event.which==2){icon.replaceWith('<i class="material-symbols-outlined md-16 md-400 md-middle">picture_as_pdf</i>')
window.open(downloadurl);}else if(event.which==1){window.location=downloadurl;}}else{icon.replaceWith('<i class="material-symbols-outlined md-16 md-400 md-middle">error</i>')}}},1000);}else{icon.replaceWith('<i class="material-symbols-outlined md-16 md-400 md-middle">error</i>')}}else{icon.replaceWith('<i class="material-symbols-outlined md-16 md-400 md-middle">error</i>')}}}
function get_ajax_data(c,o)
{var r="??";$.ajax({type:"GET",async:false,url:ajax_helper_url,data:{cmd:c,opts:o,nsp:nsp_str},success:function(data){r=data;}});return r;}
function get_ajax_data_with_callback(c,o,pfc)
{var r="??";$.ajax({type:"GET",async:true,url:ajax_helper_url,data:{cmd:c,opts:o,nsp:nsp_str},success:function(data){eval(pfc+'("'+escape(data)+'")');}});}
function get_ajax_data_innerHTML(c,o,doasync,obj)
{$.ajax({type:"GET",async:doasync,url:ajax_helper_url,data:{cmd:c,opts:o,nsp:nsp_str},success:function(data){$(obj).html(data);}});return true;}
function bind_tt(data)
{$('.a-tt-bind').tooltip();}
function get_ajax_data_innerHTML_with_callback(c,o,doasync,obj,pfc)
{$.ajax({type:"GET",async:doasync,url:ajax_helper_url,data:{cmd:c,opts:o,nsp:nsp_str},success:function(data){$(obj).html(data);var funcname=pfc+'()';var funcname=pfc+'("'+escape(data)+'")';var x=1;eval(pfc+'("'+escape(data)+'")');}});return true;}
function get_ajax_data_imagesrc(c,o,doasync,obj)
{var r="??";$.ajax({type:"GET",async:doasync,url:ajax_helper_url,data:{cmd:c,opts:o,nsp:nsp_str},success:function(data){obj.src=data;}});return true;}
function get_ajax_data_imagesrc_with_callback(c,o,doasync,obj,pfc)
{var r="??";$.ajax({type:"GET",async:doasync,url:ajax_helper_url,data:{cmd:c,opts:o,nsp:nsp_str},success:function(data){var theobj=obj;obj.src=data;var funcname=pfc+'()';eval(pfc+'()');}});return true;}
function show_throbber(){$("#throbber").css("display","block");}
function hide_throbber(){$("#throbber").css("display","none");}
function hide_message(){$("#message").css("visibility","hidden");}
function remove_message(){$("#message").remove();}
var MCT=0;var MCL=0;function resize_content()
{var h=$(window).height();var w=$(window).width();var mf=$("#mainframe");if(!mf){return;}
var hpad=0;if($('#header').is(':visible')&&$('#footer').is(':visible')){hpad=$('#header').outerHeight()+$('#footer').outerHeight()+bodymargin;}
var nh=0;$('.contentheadernotice').each(function(){nh+=$(this).outerHeight();});var height=h-hpad-nh;var location=window.location.href;if(location.indexOf('login.php')==-1){$("#mainframe").css('height',height+"px");$("#footer").css('position','absolute');}else{if(height<mf.outerHeight()){$("#footer").css('position','initial');}else{$("#footer").css('position','absolute');}}
var mfh=$("#mainframe").outerHeight();$("#leftnav").css('height',mfh-extraheightnav+"px");if($('#maincontent').length>0){$("#maincontent").css('height',mfh-extraheight+"px");}
var mcfw=w-extrawidth-bodymargin;if($('#header').is(':visible')&&$('#footer').is(':visible')){mcfw-=$('#leftnav').outerWidth();}
var mcfh=mfh;if($('#maincontent').length>0){if(nh>0){$('#maincontent').css('top',nh+'px');}
if(!$('#header').is(':visible')&&!$('#footer').is(':visible')){$('#maincontent').css('left','0px');}else{if(nh>0){if(MCT==0){var mct=$('#maincontent').css('top');MCT=parseInt(mct.substring(0,mct.length-2));}
$('#maincontent').css('top',(MCT+nh)+'px');}
if($('#header').is(':visible')){$('#maincontent').css('top',$('#header').outerHeight()+extraheadnav+nh+'px');}
if($('#leftnav').is(':visible')){$('#maincontent').css('left',$('#leftnav').outerWidth()+extrawidth+'px');}}}
try{if($('#maincontentframe').contents().find('.enterprisefeaturenotice').length>0){var tpad=$('#maincontentframe').contents().find('.enterprisefeaturenotice:not(#tabs .enterprisefeaturenotice)').outerHeight();if($('#header').is(':visible')){tpad+=$('.parenthead').outerHeight();}
$('.contentheadernotice').each(function(){tpad+=$(this).outerHeight();});$('#fullscreen').css('top',tpad+'px');}}catch(e){}
$("#maincontentframe").css('width',mcfw+"px");if($('#maincontent').length>0){$("#maincontent").css('width',mcfw+"px");}
$("#myviewoverlay").css('height',mcfh-20);$("#myviewoverlay").css('opacity',0.1);}
function do_fullscreen()
{var d=document;if(!d){return;}
var db=document.body;if(!db){return;}
resize_content();}
function check_for_mobile(){var was_redirected_to_mobile=0;var needles={0:"was_redirected_to_mobile"};var needles=JSON.stringify(needles);var is_mobile=false;if(/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)){is_mobile=true;}
if(is_mobile){if(window.location.pathname.indexOf("rr.php")!==-1){return;}
$.ajax({type:"POST",async:false,url:ajax_helper_url,data:{cmd:'getsessionvars',needles:needles,nsp:nsp_str},success:function(data){if(data!="Your session has timed out."){parsed_data=JSON.parse(data);}else{parsed_data={was_redirected_to_mobile:0};}
if(parsed_data.was_redirected_to_mobile===0&&mobile_redirects_disabled===0){var optsarr={"was_redirected_to_mobile":1};var opts=JSON.stringify(optsarr);get_ajax_data('setsessionvars',opts);window.location=base_url+'mobile';}}});}}
function display_popup(cwidth,cheight)
{$("#popup_close").each(function(){$(this).css("visibility","visible");});if(cwidth){$("#popup_content").css("width",cwidth);}
if(cheight){$("#popup_content").css("height",cheight);}
$("#popup_layer").css("opacity","1.0");$("#popup_layer").css("display","block");var lh=$("#popup_layer").height();var ch=$("#popup_content").height();var chmt=parseInt($("#popup_content").css("margin-top"));var chmb=parseInt($("#popup_content").css("margin-bottom"));height=ch+chmt+chmb;$("#popup_layer").css("height",height);var lw=$("#popup_layer").width();var cw=$("#popup_content").width();var cwmr=parseInt($("#popup_content").css("margin-right"));var cwml=parseInt($("#popup_content").css("margin-left"));width=cw+cwmr+cwml;$("#popup_layer").css("width",width);if(!popupcentered){center_popup();}
$("#popup_layer").css("visibility","visible");$("#popup_layer").each(function(){$(this).fadeIn("fast");});}
function set_popup_content(content)
{$("#popup_container").each(function(){$(this).html(content);});var c=$("#popup_container");}
function fade_popup(color,time)
{time=typeof(time)!='undefined'?time:1000;$("#popup_close").each(function(){$(this).css("visibility","hidden");});$("#popup_layer").each(function(){var c="#D0FF76";if(color=="red"){c="#FF9999";}
if(theme=='xi5dark'){$("#popup_layer").css('color','#000');}
var myColors=[{param:'background-color',cycles:"1",isFade:false,colorList:["#F1F1F1",c]}];$(this).colorBlend(myColors);$(this).oneTime(time,"popuptimer",function(i){$(this).fadeOut();$(this).oneTime(500,"popuptimer2",function(i){close_popup();});});});}
function close_popup()
{hide_throbber();$("#popup_close").each(function(){$(this).css("visibility","hidden");});$("#popup_layer").each(function(){$(this).css("visibility","hidden");});$("#popup_layer").css('color','').css('background-color','');}
function center_popup()
{var l=$("#popup_layer");var wh=$(window).height();var ww=$(window).width();var lh=$(l).height();var lw=$(l).width();var newtop=(wh-lh)/2;var newleft=(ww-lw)/2;$("#popup_layer").css("top",newtop);$("#popup_layer").css("left",newleft);popupcentered=true;}
function resize_popup()
{var lh=$("#popup_layer").height();var ch=$("#popup_content").height();var chmt=parseInt($("#popup_content").css("margin-top"));var chmb=parseInt($("#popup_content").css("margin-bottom"));height=ch+chmt+chmb;$("#popup_layer").css("height",height);var lw=$("#popup_layer").width();var cw=$("#popup_content").width();var cwmr=parseInt($("#popup_content").css("margin-right"));var cwml=parseInt($("#popup_content").css("margin-left"));width=cw+cwmr+cwml;$("#popup_layer").css("width",width);}
function resize_child_popup()
{var lh=$("#child_popup_layer").height();var ch=$("#child_popup_content").height();var chmt=parseInt($("#child_popup_content").css("margin-top"));var chmb=parseInt($("#child_popup_content").css("margin-bottom"));height=ch+chmt+chmb;$("#child_popup_layer").css("height",height);var lw=$("#child_popup_layer").width();var cw=$("#child_popup_content").width();var cwmr=parseInt($("#child_popup_content").css("margin-right"));var cwml=parseInt($("#child_popup_content").css("margin-left"));width=cw+cwmr+cwml;$("#child_popup_layer").css("width",width);}
function display_child_popup(h)
{$("#child_popup_layer").css("opacity","1.0");$("#child_popup_layer").css("display","block");$("#child_popup_layer").css("visibility","visible");if(!childpopupcentered){$("#child_popup_layer").center();}}
function set_child_popup_content(content)
{$("#child_popup_container").each(function(){$(this).html(content);});}
function fade_child_popup(color,time)
{time=typeof(time)!='undefined'?time:1000;$("#child_popup_layer").each(function(){var c="#D0FF76";if(theme=='colorblind'){c='#56B4E9';}
if(color=="red"){if(theme=='colorblind'){c='#CC79A7';}else{c="#FF9999";}}
if(theme=='xi5dark'){$("#child_popup_layer").css('color','#000');}
var myColors=[{param:'background-color',cycles:"1",isFade:false,colorList:["#F1F1F1",c]}];$(this).colorBlend(myColors);if(is_neptune()){$('#child_popup_container').colorBlend([{param:'color',cycles:"1",isFade:false,colorList:["#FFF","#000"]}]);}
$(this).oneTime(time,"child_popuptimer",function(i){$(this).fadeOut();$(this).oneTime(500,"child_popuptimer2",function(i){if(theme=='neptune'){$('#child_popup_container').css('color','#FFF');}else if(theme=='neptunelight'||'neptunecolorblind'){$('#child_popup_container').css('color','#000');}
close_child_popup();});});});}
function close_child_popup()
{hide_throbber();$("#child_popup_layer").css("visibility","hidden");$("#child_popup_layer").css('color','').css('background-color','');}
function center_child_popup()
{$("#child_popup_layer").center();childpopupcentered=true;}
function center_content_throbbers()
{$('#throbber').center(false);}
function generate_new_api_key(user_id){var optsarr={"func":"set_random_api_key","args":{"user_id":user_id}};var opts=JSON.stringify(optsarr);$('#apikey').val(get_ajax_data("getxicoreajax",opts,true));}
function generate_new_ticket(user_id){var optsarr={"func":"set_random_ticket","args":{"user_id":user_id}};var opts=JSON.stringify(optsarr);$('#insecure_login_ticket').val(get_ajax_data("getxicoreajax",opts,true));}
function setTooltip(btn,message){$(btn).tooltip('hide').attr('data-original-title',message).tooltip('show');}
function hideTooltip(btn){setTimeout(function(){$(btn).tooltip('hide');},2000);}
jQuery.fn.center=function(parent){if(parent){parent=this.parent();}else{parent=window;}
this.css({"position":"absolute","top":((($(parent).height()-this.outerHeight())/2)+$(parent).scrollTop()+"px"),"left":((($(parent).width()-this.outerWidth())/2)+$(parent).scrollLeft()+"px")});return this;}
jQuery.fn.filterByText=function(textbox,selectSingleMatch,minimumTextValue){return this.each(function(){var select=$(this);var optionsAndOptGroups=[];if(typeof selectSingleMatch==="undefined"){selectSingleMatch=false;}
if(typeof minimumTextValue==="undefined"){minimumTextValue=0;}
select.children('option, optgroup').each(function(){optionsAndOptGroups.push(jQuery(this));});select.data('optionsAndOptGroups',optionsAndOptGroups);jQuery(textbox).bind('keyup',function(e){if(textbox.val().length>minimumTextValue||e.which===46||e.which===8){var optionsAndOptGroups=select.empty().data('optionsAndOptGroups');var search=jQuery.trim(textbox.val());var regex=new RegExp(search,'gi');jQuery.each(optionsAndOptGroups,function(k,v){if(jQuery(v).is('option')){if(jQuery(v).text().match(regex)!=null){if(typeof select[0].append=='function'){select[0].append(v[0]);}else{select.append(v);}}}else{var optionGroupClone=v.clone();jQuery.each(optionGroupClone.children('option'),function(){if(v.text().match(regex)===null){v.remove();}});if(optionGroupClone.children().length){jQuery(select).append(optionGroupClone);}}});}
if(jQuery(this).val().length>0){jQuery(this).parent().find('.clear-filter').show();}else{jQuery(this).parent().find('.clear-filter').hide();}});if(selectSingleMatch===true&&select.children().length===1){select.children().get(0).selected=true;}});};function flash_message(msg,type,fade){var fade=typeof(fade)!='undefined'?fade:false;var msg_type=type;var valid_types=['info','success','error','warning'];if(valid_types.includes(msg_type)===false){msg_type='info';}
var html='<div class="flash-msg '+msg_type+'" style="z-index: 9999999; display: block;"><span class="msg-text" style="padding-top: 3px;">'+msg+'</span><span class="msg-close tt-bind fr" style="position: relative; top: 0px; right: 0px;" title="'+lang['Dismiss']+'" data-placement="left"><i class="fa fa-times"></i></span><div class="clear"></div></div>';$('.helpsystem_icon').hide();if($('.enterprisefeaturenotice').length==0){$('body.child').prepend(html);}else{var enterprise_height=$('.enterprisefeaturenotice.maincontent').outerHeight();if($('#compensator').length==0){$('.enterprisefeaturenotice').after(html);$('.enterprisefeaturenotice').after("<div id='compensator' style='height:"+enterprise_height+"px; display: block; z-index: 9999999;'></div>");}else{if($('#compensator').length==0){$('.enterprisefeaturenotice').after(html);}else{$('#compensator').after(html);}}}
if(fade===true){$('.flash-msg').delay(4000).fadeOut(1000);$('.helpsystem_icon').delay(4000).fadeIn(1000);}}
function get_permalink(exterior_window,interior_document){var parser=exterior_window.document.createElement('a');parser.href=interior_document.URL;var url=parser.pathname+parser.search+parser.hash;var lpath=exterior_window.location.pathname;if(url.lastIndexOf('/',0)===0){if(lpath.lastIndexOf('/',0)===-1){lpath='/'+lapth;}}else{if(lpath.lastIndexOf('/',0)===0){lpath=lpath.substr(1);}}
url=url.replace(lpath,'');var base=exterior_window.location.protocol+'//'+exterior_window.location.hostname+exterior_window.location.pathname;var theurl=base+"?xiwindow="+encodeURIComponent(url);return theurl;}
if(!Array.prototype.fill){Object.defineProperty(Array.prototype,'fill',{value:function(value){if(this==null){throw new TypeError('this is null or not defined');}
var O=Object(this);var len=O.length>>>0;var start=arguments[1];var relativeStart=start>>0;var k=relativeStart<0?Math.max(len+relativeStart,0):Math.min(relativeStart,len);var end=arguments[2];var relativeEnd=end===undefined?len:end>>0;var finalValue=relativeEnd<0?Math.max(len+relativeEnd,0):Math.min(relativeEnd,len);while(k<finalValue){O[k]=value;k++;}
return O;}});}