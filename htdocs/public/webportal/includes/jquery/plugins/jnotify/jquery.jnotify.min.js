/*
 * jNotify jQuery Plug-in
 *
 * Copyright 2010 Giva, Inc. (http://www.givainc.com/labs/) 
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 * 	http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * Date: 2010-09-30
 * Rev:  1.1.00
 */
(function(B){B.jnotify=function(K,M,L){return new C(K,M,L)};B.jnotify.version="1.1.00";var J,D=[],E=0,H=false,I=false,G,F,A={type:"",delay:2000,sticky:false,closeLabel:"&times;",showClose:true,fadeSpeed:1000,slideSpeed:250,classContainer:"jnotify-container",classNotification:"jnotify-notification",classBackground:"jnotify-background",classClose:"jnotify-close",classMessage:"jnotify-message",init:null,create:null,beforeRemove:null,remove:null,transition:null};B.jnotify.setup=function(K){A=B.extend({},A,K)};B.jnotify.play=function(M,N){if(H&&(M!==true)||(D.length==0)){return }H=true;var L=D.shift();F=L;var K=(arguments.length>=2)?parseInt(N,10):L.options.delay;G=setTimeout(function(){G=0;L.remove(function(){if(D.length==0){H=false}else{if(!I){B.jnotify.play(true)}}})},K)};B.jnotify.pause=function(){clearTimeout(G);if(G){D.unshift(F)}I=H=true};B.jnotify.resume=function(){I=false;B.jnotify.play(true,0)};function C(P,N){var M=this,K=typeof N;if(K=="number"){N=B.extend({},A,{delay:N})}else{if(K=="boolean"){N=B.extend({},A,{sticky:true})}else{if(K=="string"){N=B.extend({},A,{type:N,delay:((arguments.length>2)&&(typeof arguments[2]=="number"))?arguments[2]:A.delay,sticky:((arguments.length>2)&&(typeof arguments[2]=="boolean"))?arguments[2]:A.sticky})}else{N=B.extend({},A,N)}}}this.options=N;if(!J){J=B('<div class="'+A.classContainer+'" />').appendTo("body");if(B.isFunction(N.init)){N.init.apply(M,[J])}}function O(S){var R='<div class="'+N.classNotification+(N.type.length?(" "+N.classNotification+"-"+N.type):"")+'"><div class="'+N.classBackground+'"></div>'+(N.sticky&&N.showClose?('<a class="'+N.classClose+'">'+N.closeLabel+"</a>"):"")+'<div class="'+N.classMessage+'"><div>'+S+"</div></div></div>";E++;var Q=B(R);if(N.sticky){Q.find("a."+N.classClose).bind("click.jnotify",function(){M.remove()})}if(B.isFunction(N.create)){N.create.apply(M,[Q])}return Q.appendTo(J)}this.remove=function(U){var Q=L.find("."+N.classMessage),S=Q.parent();var R=E--;if(B.isFunction(N.beforeRemove)){N.beforeRemove.apply(M,[Q])}function T(){S.remove();if(B.isFunction(U)){U.apply(M,[Q])}if(B.isFunction(N.remove)){N.remove.apply(M,[Q])}}if(B.isFunction(N.transition)){N.transition.apply(M,[S,Q,R,T,N])}else{Q.fadeTo(N.fadeSpeed,0.01,function(){if(R<=1){T()}else{S.slideUp(N.slideSpeed,T)}});if(E<=0){S.fadeOut(N.fadeSpeed)}}};var L=O(P);if(!N.sticky){D.push(this);B.jnotify.play()}return this}})(jQuery);