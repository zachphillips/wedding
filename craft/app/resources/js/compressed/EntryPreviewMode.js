/*!
 * Craft by Pixel & Tonic
 *
 * @package   Craft
 * @author    Pixel & Tonic, Inc.
 * @copyright Copyright (c) 2013, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 */
(function(a){Craft.EntryPreviewMode=Garnish.Base.extend({$form:null,$btn:null,$spinner:null,$shade:null,$editor:null,$iframeContainer:null,$iframe:null,postUrl:null,basePostData:null,inPreviewMode:false,fields:null,lastPostData:null,updateIframeInterval:null,loading:false,checkAgain:false,init:function(d){if(d){this.postUrl=d}else{this.postUrl=Craft.baseSiteUrl}this.$form=a("#entry-form");this.$btn=a("#previewmode-btn");this.$spinner=a("#previewmode-spinner");this.basePostData={action:"entries/previewEntry"};var c=this.$form.children("input[type=hidden]");for(var b=0;b<c.length;b++){var e=a(c[b]);this.basePostData[e.attr("name")]=e.val()}this.addListener(this.$btn,"click","togglePreviewMode")},togglePreviewMode:function(){if(this.inPreviewMode){this.hidePreviewMode()}else{this.showPreviewMode()}},showPreviewMode:function(){a(document.activeElement).blur();if(!this.$editor){this.$shade=a('<div class="modal-shade dark"></div>').appendTo(Garnish.$bod).css("z-index",2);this.$editor=a('<div id="previewmode-editor"></div>').appendTo(Garnish.$bod);this.$iframeContainer=a('<div id="previewmode-iframe-container" />').appendTo(Garnish.$bod);this.$iframe=a('<iframe id="previewmode-iframe" frameborder="0" />').appendTo(this.$iframeContainer);var b=a('<header class="header"></header>').appendTo(this.$editor),g=a('<div class="btn" data-icon="x" title="'+Craft.t("Close")+'"></div>').appendTo(b),f=a("<h1>"+Craft.t("Live Preview")+"</h1>").appendTo(b);this.addListener(g,"click","hidePreviewMode")}this.fields=[];var c=this.$form.children(".field").add(this.$form.children(":not(#entry-settings)").children(".field"));for(var d=0;d<c.length;d++){var e=a(c[d]),h=e.clone().insertAfter(e);e.appendTo(this.$editor);this.fields.push({$field:e,$clone:h})}if(this.updateIframe()){this.$spinner.removeClass("hidden");this.addListener(this.$iframe,"load",function(){this.slideIn();this.removeListener(this.$iframe,"load")})}else{this.slideIn()}this.inPreviewMode=true},slideIn:function(){a("html").addClass("noscroll");this.$spinner.addClass("hidden");this.$iframeContainer.css("left",Garnish.$win.width());this.$iframeContainer.show();this.addListener(Garnish.$win,"resize","setIframeWidth");this.setIframeWidth();this.$shade.fadeIn();this.$editor.show().stop().animate({left:0},"slow");this.$iframeContainer.stop().animate({left:Craft.EntryPreviewMode.formWidth},"slow",a.proxy(function(){this.updateIframeInterval=setInterval(a.proxy(this,"updateIframe"),1000);this.addListener(Garnish.$bod,"keyup",function(b){if(b.keyCode==Garnish.ESC_KEY){this.hidePreviewMode()}})},this))},hidePreviewMode:function(){a("html").removeClass("noscroll");this.removeListener(Garnish.$win,"resize");this.removeListener(Garnish.$bod,"keyup");if(this.updateIframeInterval){clearInterval(this.updateIframeInterval)}for(var c=0;c<this.fields.length;c++){var d=this.fields[c];d.$newClone=d.$field.clone().insertAfter(d.$field);d.$clone.replaceWith(d.$field)}var b=Garnish.$win.width();this.$shade.delay(200).fadeOut();this.$editor.stop().animate({left:-Craft.EntryPreviewMode.formWidth},"slow",a.proxy(function(){for(var e=0;e<this.fields.length;e++){this.fields[e].$newClone.remove()}this.$editor.hide()},this));this.$iframeContainer.stop().animate({left:b},"slow",a.proxy(function(){this.$iframeContainer.hide()},this));this.inPreviewMode=false},setIframeWidth:function(){this.$iframeContainer.width(Garnish.$win.width()-Craft.EntryPreviewMode.formWidth)},updateIframe:function(){if(this.loading){this.checkAgain=true;return false}var b=Garnish.getPostData(this.$editor);if(!this.lastPostData||!Craft.compare(b,this.lastPostData)){this.lastPostData=b;this.loading=true;var c=a.extend({},b,this.basePostData),d=a(this.$iframe[0].contentWindow.document).scrollTop();a.post(this.postUrl,c,a.proxy(function(e){var f=e+'<script type="text/javascript">document.body.scrollTop = '+d+";<\/script>";this.$iframe.css("background",a(this.$iframe[0].contentWindow.document.body).css("background"));this.$iframe[0].contentWindow.document.open();this.$iframe[0].contentWindow.document.write(f);this.$iframe[0].contentWindow.document.close();this.loading=false;if(this.checkAgain){this.checkAgain=false;this.updateIframe()}},this));return true}else{return false}}},{formWidth:400})})(jQuery);