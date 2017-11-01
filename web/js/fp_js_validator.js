;function FpJsFormElement(){this.id='';this.name='';this.type='';this.invalidMessage='';this.bubbling=!1;this.disabled=!1;this.transformers=[];this.data={};this.children={};this.parent=null;this.domNode=null;this.callbacks={};this.errors={};this.groups=function(){return['Default']};this.validate=function(){if(this.disabled){return!0};var e=this,r='form-error-'+String(this.id).replace(/_/g,'-');e.errors[r]=FpJsFormValidator.validateElement(e);var t=FpJsFormValidator.getErrorPathElement(e),n=t.domNode;if(!n){for(var a in t.children){var i=t.children[a].domNode;if(i){n=i;break}}};t.showErrors.apply(n,[e.errors[r],r]);return e.errors[r].length==0};this.validateRecursively=function(){this.validate();for(var e in this.children){this.children[e].validateRecursively()}};this.isValid=function(){for(var t in this.errors){if(this.errors[t].length>0){return!1}};for(var e in this.children){if(!this.children[e].isValid()){return!1}};return!0};this.showErrors=function(e,t){if(!(this instanceof HTMLElement)){return};var a=this,r=FpJsFormValidator.getDefaultErrorContainerNode(a);if(r){var i=r.childNodes.length;while(i--){if(t==r.childNodes[i].className){r.removeChild(r.childNodes[i])}}};if(!e.length){if(r&&!r.childNodes){r.parentNode.removeChild(r)};return};if(!r){r=document.createElement('ul');r.className=FpJsFormValidator.errorClass;a.parentNode.insertBefore(r,a)};var n;for(var o in e){n=document.createElement('li');n.className=t;n.innerHTML=e[o];r.appendChild(n)}};this.onValidate=function(e,t){};this.submitForm=function(e){e.submit()}};function FpJsAjaxRequest(){this.queue=0;this.callbacks=[];this.sendRequest=function(e,t,n){var a=this,r=this.createRequest();try{r.open('POST',e,!0);r.setRequestHeader('Content-Type','application/x-www-form-urlencoded');r.onreadystatechange=function(){if(4==r.readyState&&200==r.status){n(r.responseText);a.queue--;a.checkQueue()}};r.send(this.serializeData(t,null));a.queue++}catch(i){console.log(i.message)}};this.checkQueue=function(){if(0==this.queue){for(var e in this.callbacks){this.callbacks[e]()}}};this.serializeData=function(e,t){var a=[];for(var n in e){var i=t?t+'['+n+']':n,r=e[n];a.push((typeof r=='object')?this.serializeData(r,i):encodeURIComponent(i)+'='+encodeURIComponent(r))};return a.join('&')};this.createRequest=function(){var e=null;if(window.XMLHttpRequest){e=new XMLHttpRequest()}
else{try{e=new ActiveXObject('Microsoft.XMLHTTP')}catch(t){};try{e=new ActiveXObject('Msxml2.XMLHTTP')}catch(t){};try{e=new ActiveXObject('Msxml2.XMLHTTP.6.0')}catch(t){};try{e=new ActiveXObject('Msxml2.XMLHTTP.3.0')}catch(t){}};return e}};function FpJsCustomizeMethods(){this.init=function(e){FpJsFormValidator.each(this,function(t){if(!t.jsFormValidator){t.jsFormValidator={}};for(var r in e){switch(r){case'customEvents':e[r].apply(t);break;default:t.jsFormValidator[r]=e[r];break}}},!1);return this};this.validate=function(e){var t=!0;FpJsFormValidator.each(this,function(r){var s=(e&&!0===e['recursive'])?'validateRecursively':'validate',l=(!e||!1!==e['findUniqueConstraint']);if(l&&r.jsFormValidator.parent){var n=r.jsFormValidator.parent.data;if(n['entity']&&n['entity']['constraints']){for(var o in n['entity']['constraints']){var i=n['entity']['constraints'][o];if(i instanceof FpJsFormValidatorBundleFormConstraintUniqueEntity&&i.fields.indexOf(r.jsFormValidator.name)>-1){var a=r.jsFormValidator.parent;i.validate(null,a)}}}};if(!r.jsFormValidator[s]()){t=!1}});return t};this.showErrors=function(e){FpJsFormValidator.each(this,function(t){t.jsFormValidator.errors[e['sourceId']]=e['errors'];t.jsFormValidator.showErrors.apply(t,[e['errors'],e['sourceId']])})};this.submitForm=function(e){FpJsFormValidator.each(this,function(t){var r=t.jsFormValidator;if(e){e.preventDefault()};r.validateRecursively();if(FpJsFormValidator.ajax.queue){if(e){e.preventDefault()};FpJsFormValidator.ajax.callbacks.push(function(){r.onValidate.apply(r.domNode,[FpJsFormValidator.getAllErrors(r,{}),e]);if(r.isValid()){r.submitForm.apply(t,[t])}})}
else{r.onValidate.apply(r.domNode,[FpJsFormValidator.getAllErrors(r,{}),e]);if(r.isValid()){r.submitForm.apply(t,[t])}}})};this.get=function(){var e=[];FpJsFormValidator.each(this,function(t){e.push(t.jsFormValidator)});return e};this.addPrototype=function(e){FpJsFormValidator.each(this,function(t){var r=FpJsFormValidator.preparePrototype(FpJsFormValidator.cloneObject(t.jsFormValidator.prototype),e,t.jsFormValidator.id+'_'+e);t.jsFormValidator.children[e]=FpJsFormValidator.createElement(r);t.jsFormValidator.children[e].parent=t.jsFormValidator})};this.delPrototype=function(e){FpJsFormValidator.each(this,function(t){delete(t.jsFormValidator.children[e])})}};var FpJsBaseConstraint={prepareMessage:function(e,t,r){var n=e,i=e.split('|');if(i.length>1){if(r==1){n=i[0]}
else{n=i[1]}};for(var a in t){var o=new RegExp(a,'g');n=n.replace(o,t[a])};return n},formatValue:function(e){switch(Object.prototype.toString.call(e)){case'[object Date]':return e.format('Y-m-d H:i:s');case'[object Object]':return'object';case'[object Array]':return'array';case'[object String]':return'"'+e+'"';case'[object Null]':return'null';case'[object Boolean]':return e?'true':'false';default:return String(e)}}};var FpJsFormValidator=new function(){this.forms={};this.errorClass='form-errors';this.config={};this.ajax=new FpJsAjaxRequest();this.customizeMethods=new FpJsCustomizeMethods();this.constraintsCounter=0;this.addModel=function(e,t){var r=this;if(!e)return;if(t!==!1){this.onDocumentReady(function(){r.forms[e.id]=r.initModel(e)})}
else{r.forms[e.id]=r.initModel(e)}};this.onDocumentReady=function(e){var r=document.addEventListener||document.attachEvent,n=document.removeEventListener||document.detachEvent,t=document.addEventListener?'DOMContentLoaded':'onreadystatechange';r.call(document,t,function(){n.call(this,t,arguments.callee,!1);e()},!1)};this.initModel=function(e){var t=this.createElement(e),r=this.findFormElement(t);t.domNode=r;this.attachElement(t);if(r){this.attachDefaultEvent(t,r)};return t};this.createElement=function(e){var t=new FpJsFormElement();t.domNode=this.findDomElement(e);if(e.children instanceof Array&&!e.length&&!t.domNode){return null};for(var n in e){if('children'==n){for(var i in e.children){var l=this.createElement(e.children[i]);if(l){t.children[i]=l;t.children[i].parent=t}}}
else if('transformers'==n){t.transformers=this.parseTransformers(e[n])}
else{t[n]=e[n]}};for(var r in t.data){var s=[];if(t.data[r].constraints){s=this.parseConstraints(t.data[r].constraints)};t.data[r].constraints=s;var o={};if(t.data[r].getters){for(var a in t.data[r].getters){o[a]=this.parseConstraints(t.data[r].getters[a])}};t.data[r].getters=o};this.attachElement(t);return t};this.validateElement=function(e){var n=[],o=this.getElementValue(e);for(var t in e.data){if('entity'==t&&e.parent&&!this.shouldValidEmbedded(e)){continue};if('parent'==t&&e.parent&&e.parent.parent&&!this.shouldValidEmbedded(e.parent)){continue};var r=e.data[t]['groups'];if(typeof r=='string'){r=this.getParentElementById(r,e).groups.apply(e.domNode)};n=n.concat(this.validateConstraints(o,e.data[t]['constraints'],r,e));for(var i in e.data[t]['getters']){if(typeof e.callbacks[i]=='function'){var a=e.callbacks[i].apply(e.domNode);n=n.concat(this.validateConstraints(a,e.data[t]['getters'][i],r,e))}}};return n};this.shouldValidEmbedded=function(e){if(this.getElementValidConstraint(e)){return!0}
else if(e.parent&&'Symfony\\Component\\Form\\Extension\\Core\\Type\\CollectionType'==e.parent.type){var t=this.getElementValidConstraint(e);return!t||t.traverse};return!1};this.getElementValidConstraint=function(e){if(e.data&&e.data.form){for(var t in e.data.form.constraints){if(e.data.form.constraints[t]instanceof SymfonyComponentValidatorConstraintsValid){return e.data.form.constraints[t]}}}};this.validateConstraints=function(e,t,r,a){var n=[],i=t.length;while(i--){if(this.checkValidationGroups(r,t[i])){n=n.concat(t[i].validate(e,a))}};return n};this.checkValidationGroups=function(e,t){var r=!1,n=e.length,i=t.groups||['Default'];while(n--){if(-1!==i.indexOf(e[n])){r=!0;break}};return r};this.getElementValue=function(e){var r=e.transformers.length,t=this.getInputValue(e);if(r&&undefined===t){t=this.getMappedValue(e)}
else if('Symfony\\Component\\Form\\Extension\\Core\\Type\\CollectionType'==e.type){t={};for(var n in e.children){t[n]=this.getMappedValue(e.children[n])}}
else{t=this.getSpecifiedElementTypeValue(e)}
while(r--){t=e.transformers[r].reverseTransform(t,e)};return t};this.getInputValue=function(e){return e.domNode?e.domNode.value:undefined};this.getMappedValue=function(e){var t=this.getSpecifiedElementTypeValue(e);if(undefined===t){t={};for(var n in e.children){var r=e.children[n];t[r.name]=this.getMappedValue(r)}};return t};this.getSpecifiedElementTypeValue=function(e){if(!e.domNode){return undefined};var t;if('Symfony\\Component\\Form\\Extension\\Core\\Type\\CheckboxType'==e.type||'Symfony\\Component\\Form\\Extension\\Core\\Type\\RadioType'==e.type){t=e.domNode.checked}
else if('select'===e.domNode.tagName.toLowerCase()){t=[];var r=e.domNode,n=r.length;while(n--){if(r.options[n].selected){t.push(r.options[n].value)}}}
else{t=this.getInputValue(e)};return t};this.parseConstraints=function(e){var o=[];for(var r in e){var a=r.replace(/\\/g,'');if(undefined!==window[a]){var n=e[r].length;while(n--){var t=new window[a]();for(var i in e[r][n]){t[i]=e[r][n][i]};t.uniqueId=this.constraintsCounter;this.constraintsCounter++;if(typeof t.onCreate==='function'){t.onCreate()};o.push(t)}}};return o};this.parseTransformers=function(e){var a=[],r=e.length;while(r--){var i=String(e[r]['name']).replace(/\\/g,'');if(undefined!==window[i]){var t=new window[i]();for(var n in e[r]){t[n]=e[r][n]};if(undefined!==t.transformers){t.transformers=this.parseTransformers(t.transformers)};a.push(t)}};return a};this.getParentElementById=function(e,t){if(e==t.id){return t}
else if(t.parent){return this.getParentElementById(e,t.parent)}
else{return null}};this.attachElement=function(e){if(!e.domNode){return};if(undefined!==e.domNode.jsFormValidator){for(var t in e.domNode.jsFormValidator){e[t]=e.domNode.jsFormValidator[t]}};e.domNode.jsFormValidator=e};this.attachDefaultEvent=function(e,t){t.addEventListener('submit',function(e){FpJsFormValidator.customize(t,'submitForm',e)})};this.findDomElement=function(e){var t=document.getElementById(e.id);if(!t){var r=document.getElementsByName(e.name);if(r.length){t=r[0]}};return t};this.findFormElement=function(e){var t=null;if(e.domNode&&'form'==e.domNode.tagName.toLowerCase()){t=e.domNode}
else{var r=this.findRealChildElement(e);if(r){t=this.findParentForm(r)}};return t};this.findRealChildElement=function(e){var t=e.domNode;if(!t){for(var r in e.children){t=e.children[r].domNode;if(t){break}}};return t};this.findParentForm=function(e){if(e.tagName&&'form'==e.tagName.toLowerCase()){return e}
else if(e.parentNode){return this.findParentForm(e.parentNode)}
else{return null}};this.getDefaultErrorContainerNode=function(e){var t=e.previousSibling;if(!t||t.className!==this.errorClass){return null}
else{return t}};this.getErrorPathElement=function(e){if(!e.bubbling){return e}
else{return this.getRootElement(e)}};this.getRootElement=function(e){if(e.parent){return this.getRootElement(e.parent)}
else{return e}};this.customize=function(e,t){if(!Array.isArray(e)){e=[e]};if(!t){return this.customizeMethods.get.apply(e,Array.prototype.slice.call(arguments,1))}
else if(typeof t==='object'){return this.customizeMethods.init.apply(e,Array.prototype.slice.call(arguments,1))}
else if(this.customizeMethods[t]){return this.customizeMethods[t].apply(e,Array.prototype.slice.call(arguments,2))}
else{$.error('Method '+t+' does not exist');return this}};this.each=function(e,t,r){r=(undefined==r)?!0:r;var n=e.length;while(n--){if(r&&(!e[n]||!e[n].jsFormValidator)){continue};t(e[n])}};this.getRealCallback=function(e,t){var n=null,r=null;if(typeof t=='string'){r=t}
else if(Array.isArray(t)){if(1==t.length){r=t[0]}
else{n=t[0];r=t[1]}};var i=null;if(!e.callbacks[n]&&typeof e.callbacks[r]=='function'){i=e.callbacks[r]}
else if(e.callbacks[n]&&typeof e.callbacks[n][r]=='function'){i=e.callbacks[n][r]}
else if(typeof e.callbacks[r]=='function'){i=e.callbacks[r]};return i};this.getAllErrors=function(e,t){if(t==null||typeof t!=='object'){t={}};var r=!1;for(var i in e.errors){if(e.errors[i].length){r=!0;break}};if(r){t[e.id]=e.errors};for(var n in e.children){t=this.getAllErrors(e.children[n],t)};return t};this.preparePrototype=function(e,t,r){e.name=e.name.replace(/__name__/g,t);e.id=e.id.replace(/__name__/g,r);if(typeof e.children=='object'){for(var n in e.children){e[n]=this.preparePrototype(e.children[n],t,r)}};return e};this.cloneObject=function(e){var r={};for(var t in e){if(typeof e[t]=='object'&&!(e[t]instanceof Array)){r[t]=this.cloneObject(e[t])}
else{r[t]=e[t]}};return r};this.isValueEmty=function(e){return[undefined,null,!1].indexOf(e)>=0||0===this.getValueLength(e)};this.isValueArray=function(e){return e instanceof Array};this.isValueObject=function(e){return typeof e=='object'&&null!==e};this.getValueLength=function(e){var t=null;if(typeof e=='number'||typeof e=='string'||this.isValueArray(e)){t=e.length}
else if(this.isValueObject(e)){var r=0;for(var n in e){if(e.hasOwnProperty(n)){r++}};t=r};return t}}();
;function SymfonyComponentValidatorConstraintsBlank(){this.message='';this.validate=function(a){var t=[],s=FpJsFormValidator;if(!s.isValueEmty(a)){t.push(this.message.replace('{{ value }}',FpJsBaseConstraint.formatValue(a)))};return t}};
;function SymfonyComponentValidatorConstraintsCallback(){this.callback=null;this.methods=[];this.validate=function(i,t){if(!this.callback){this.callback=[]};if(!this.methods.length){this.methods=[this.callback]};for(var l in this.methods){var a=FpJsFormValidator.getRealCallback(t,this.methods[l]);if(null!==a){a.apply(t.domNode)}
else{throw new Error('Can not find a "'+this.callback+'" callback for the element id="'+t.id+'" to validate the Callback constraint.')}};return[]}};
;function SymfonyComponentValidatorConstraintsChoice(){this.choices=[];this.callback=null;this.max=null;this.min=null;this.message='';this.maxMessage='';this.minMessage='';this.multiple=!1;this.multipleMessage='';this.strict=!1;this.validate=function(i,a){var s=[];i=this.getValue(i);if(null===i){return s};var e=this.getInvalidChoices(i,this.getChoicesList(a)),t=e.length;if(this.multiple){if(t){s.push(this.multipleMessage.replace('{{ value }}',FpJsBaseConstraint.formatValue(e[0])))};if(!isNaN(this.min)&&i.length<this.min){s.push(this.minMessage)};if(!isNaN(this.max)&&i.length>this.max){s.push(this.maxMessage)}}
else if(t){while(t--){s.push(this.message.replace('{{ value }}',FpJsBaseConstraint.formatValue(e[t])))}};return s};this.onCreate=function(){this.min=parseInt(this.min);this.max=parseInt(this.max);this.minMessage=FpJsBaseConstraint.prepareMessage(this.minMessage,{'{{ limit }}':FpJsBaseConstraint.formatValue(this.min)},this.min);this.maxMessage=FpJsBaseConstraint.prepareMessage(this.maxMessage,{'{{ limit }}':FpJsBaseConstraint.formatValue(this.max)},this.max)};this.getValue=function(i){if(-1!==[undefined,null,''].indexOf(i)){return null}
else if(!(i instanceof Array)){return[i]}
else{return i}};this.getChoicesList=function(i){var s=null;if(this.callback){var t=FpJsFormValidator.getRealCallback(i,this.callback);if(null!==t){s=t.apply(i.domNode)}
else{throw new Error('Can not find a "'+this.callback+'" callback for the element id="'+i.id+'" to get a choices list.')}};if(null==s){s=(null==this.choices)?[]:this.choices};return s};this.getInvalidChoices=function(i,s){var t=function(i){return s.indexOf(i)==-1};if(this.strict){t=function(i){var t=!1;for(var e in s){if(i!==s[e]){t=!0}};return t}};return i.filter(t)}};
;function SymfonyComponentValidatorConstraintsCount(){this.maxMessage='';this.minMessage='';this.exactMessage='';this.max=null;this.min=null;this.validate=function(t){var s=[],a=FpJsFormValidator;if(!a.isValueArray(t)&&!a.isValueObject(t)){return s};var i=a.getValueLength(t);if(null!==i){if(this.max===this.min&&i!==this.min){s.push(this.exactMessage);return s};if(!isNaN(this.max)&&i>this.max){s.push(this.maxMessage)};if(!isNaN(this.min)&&i<this.min){s.push(this.minMessage)}};return s};this.onCreate=function(){this.min=parseInt(this.min);this.max=parseInt(this.max);this.minMessage=FpJsBaseConstraint.prepareMessage(this.minMessage,{'{{ limit }}':FpJsBaseConstraint.formatValue(this.min)},this.min);this.maxMessage=FpJsBaseConstraint.prepareMessage(this.maxMessage,{'{{ limit }}':FpJsBaseConstraint.formatValue(this.max)},this.max);this.exactMessage=FpJsBaseConstraint.prepareMessage(this.exactMessage,{'{{ limit }}':FpJsBaseConstraint.formatValue(this.min)},this.min)}};
;function SymfonyComponentValidatorConstraintsDate(){this.message='';this.validate=function(t){var s=/^(\d{4})-(\d{2})-(\d{2})$/,a=[],e=FpJsFormValidator;if(!e.isValueEmty(t)&&!s.test(t)){a.push(this.message.replace('{{ value }}',FpJsBaseConstraint.formatValue(t)))};return a}};
;function SymfonyComponentValidatorConstraintsDateTime(){this.message='';this.validate=function(t){var e=/^(\d{4})-(\d{2})-(\d{2}) (0[0-9]|1[0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/,a=[],s=FpJsFormValidator;if(!s.isValueEmty(t)&&!e.test(t)){a.push(this.message.replace('{{ value }}',FpJsBaseConstraint.formatValue(t)))};return a}};
;function SymfonyComponentValidatorConstraintsEmail(){this.message="";this.validate=function(a){var e=/^[-a-z0-9~!$%^&*_=+}{'?]+(\.[-a-z0-9~!$%^&*_=+}{'?]+)*@([a-z0-9_][-a-z0-9_]*(\.[-a-z0-9_]+)*\.(aero|arpa|biz|com|coop|edu|gov|info|int|mil|museum|name|net|org|pro|travel|mobi|[a-z][a-z])|([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}))(:[0-9]{1,5})?$/i,t=[],o=FpJsFormValidator;if(!o.isValueEmty(a)&&!e.test(a)){t.push(this.message.replace("{{ value }}",FpJsBaseConstraint.formatValue(a)))};return t}};
;function SymfonyComponentValidatorConstraintsEqualTo(){this.message='';this.value=null;this.validate=function(a){var e=[],t=FpJsFormValidator;if(!t.isValueEmty(a)&&this.value!=a){e.push(this.message.replace('{{ value }}',FpJsBaseConstraint.formatValue(a)).replace('{{ compared_value }}',FpJsBaseConstraint.formatValue(this.value)).replace('{{ compared_value_type }}',FpJsBaseConstraint.formatValue(this.value)))};return e}};
;var SymfonyComponentValidatorConstraintsFalse=SymfonyComponentValidatorConstraintsIsFalse;
;function SymfonyComponentValidatorConstraintsGreaterThan(){this.message='';this.value=null;this.validate=function(a){var e=FpJsFormValidator;if(e.isValueEmty(a)||a>this.value){return[]}
else{return[this.message.replace('{{ value }}',FpJsBaseConstraint.formatValue(a)).replace('{{ compared_value }}',FpJsBaseConstraint.formatValue(this.value))]}}};
;function SymfonyComponentValidatorConstraintsGreaterThanOrEqual(){this.message='';this.value=null;this.validate=function(a){var e=FpJsFormValidator;if(e.isValueEmty(a)||a>=this.value){return[]}
else{return[this.message.replace('{{ value }}',FpJsBaseConstraint.formatValue(a)).replace('{{ compared_value }}',FpJsBaseConstraint.formatValue(this.value))]}}};
;function SymfonyComponentValidatorConstraintsIdenticalTo(){this.message='';this.value=null;this.validate=function(a){var e=[];if(''!==a&&this.value!==a){e.push(this.message.replace('{{ value }}',FpJsBaseConstraint.formatValue(a)).replace('{{ compared_value }}',FpJsBaseConstraint.formatValue(this.value)).replace('{{ compared_value_type }}',FpJsBaseConstraint.formatValue(this.value)))};return e}};
;function SymfonyComponentValidatorConstraintsIp(){this.message='';this.validate=function(t){var s=/^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/,a=[],e=FpJsFormValidator;if(!e.isValueEmty(t)&&!s.test(t)){a.push(this.message.replace('{{ value }}',FpJsBaseConstraint.formatValue(t)))};return a}};
;function SymfonyComponentValidatorConstraintsIsFalse(){this.message='';this.validate=function(a){var s=[];if(''!==a&&!1!==a){s.push(this.message.replace('{{ value }}',FpJsBaseConstraint.formatValue(a)))};return s}};
;function SymfonyComponentValidatorConstraintsIsNull(){this.message='';this.validate=function(a){var s=[];if(null!==a){s.push(this.message.replace('{{ value }}',FpJsBaseConstraint.formatValue(a)))};return s}};
;function SymfonyComponentValidatorConstraintsIsTrue(){this.message='';this.validate=function(t){if(''===t){return[]};var a=[];if(!0!==t){a.push(this.message.replace('{{ value }}',FpJsBaseConstraint.formatValue(t)))};return a}};
;function SymfonyComponentValidatorConstraintsLength(){this.maxMessage='';this.minMessage='';this.exactMessage='';this.max=null;this.min=null;this.validate=function(t){var s=[],a=FpJsFormValidator,i=a.getValueLength(t);if(''!==t&&null!==i){if(this.max===this.min&&i!==this.min){s.push(this.exactMessage);return s};if(!isNaN(this.max)&&i>this.max){s.push(this.maxMessage)};if(!isNaN(this.min)&&i<this.min){s.push(this.minMessage)}};return s};this.onCreate=function(){this.min=parseInt(this.min);this.max=parseInt(this.max);this.minMessage=FpJsBaseConstraint.prepareMessage(this.minMessage,{'{{ limit }}':FpJsBaseConstraint.formatValue(this.min)},this.min);this.maxMessage=FpJsBaseConstraint.prepareMessage(this.maxMessage,{'{{ limit }}':FpJsBaseConstraint.formatValue(this.max)},this.max);this.exactMessage=FpJsBaseConstraint.prepareMessage(this.exactMessage,{'{{ limit }}':FpJsBaseConstraint.formatValue(this.min)},this.min)}};
;function SymfonyComponentValidatorConstraintsLessThan(){this.message='';this.value=null;this.validate=function(a){var e=FpJsFormValidator;if(e.isValueEmty(a)||a<this.value){return[]}
else{return[this.message.replace('{{ value }}',FpJsBaseConstraint.formatValue(a)).replace('{{ compared_value }}',FpJsBaseConstraint.formatValue(this.value))]}}};
;function SymfonyComponentValidatorConstraintsLessThanOrEqual(){this.message='';this.value=null;this.validate=function(a){var e=FpJsFormValidator;if(e.isValueEmty(a)||a<=this.value){return[]}
else{return[this.message.replace('{{ value }}',FpJsBaseConstraint.formatValue(a)).replace('{{ compared_value }}',FpJsBaseConstraint.formatValue(this.value))]}}};
;function SymfonyComponentValidatorConstraintsNotBlank(){this.message='';this.validate=function(a){var t=[],s=FpJsFormValidator;if(s.isValueEmty(a)){t.push(this.message.replace('{{ value }}',FpJsBaseConstraint.formatValue(a)))};return t}};
;function SymfonyComponentValidatorConstraintsNotEqualTo(){this.message='';this.value=null;this.validate=function(a){var e=[];if(''!==a&&this.value==a){e.push(this.message.replace('{{ value }}',FpJsBaseConstraint.formatValue(a)).replace('{{ compared_value }}',FpJsBaseConstraint.formatValue(this.value)).replace('{{ compared_value_type }}',FpJsBaseConstraint.formatValue(this.value)))};return e}};
;function SymfonyComponentValidatorConstraintsNotIdenticalTo(){this.message='';this.value=null;this.validate=function(a){var e=[];if(''!==a&&this.value===a){e.push(this.message.replace('{{ value }}',FpJsBaseConstraint.formatValue(a)).replace('{{ compared_value }}',FpJsBaseConstraint.formatValue(this.value)).replace('{{ compared_value_type }}',FpJsBaseConstraint.formatValue(this.value)))};return e}};
;function SymfonyComponentValidatorConstraintsNotNull(){this.message='';this.validate=function(t){var a=[];if(null===t){a.push(this.message.replace('{{ value }}',FpJsBaseConstraint.formatValue(t)))};return a}};
;var SymfonyComponentValidatorConstraintsNull=SymfonyComponentValidatorConstraintsIsNull;
;function SymfonyComponentValidatorConstraintsRange(){this.maxMessage='';this.minMessage='';this.invalidMessage='';this.max=null;this.min=null;this.validate=function(a){var s=[],i=FpJsFormValidator;if(i.isValueEmty(a)){return s};if(isNaN(a)){s.push(this.invalidMessage.replace('{{ value }}',FpJsBaseConstraint.formatValue(a)))};if(!isNaN(this.max)&&a>this.max){s.push(this.maxMessage.replace('{{ value }}',FpJsBaseConstraint.formatValue(a)).replace('{{ limit }}',FpJsBaseConstraint.formatValue(this.max)))};if(!isNaN(this.min)&&a<this.min){s.push(this.minMessage.replace('{{ value }}',FpJsBaseConstraint.formatValue(a)).replace('{{ limit }}',FpJsBaseConstraint.formatValue(this.min)))};return s};this.onCreate=function(){this.min=parseInt(this.min);this.max=parseInt(this.max)}};
;function SymfonyComponentValidatorConstraintsRegex(){this.message='';this.pattern='';this.match=!0;this.validate=function(t){var a=[],e=FpJsFormValidator;if(!e.isValueEmty(t)&&!this.pattern.test(t)){a.push(this.message.replace('{{ value }}',FpJsBaseConstraint.formatValue(t)))};return a};this.onCreate=function(){var t=this.pattern.match(/[\/#](\w*)$/);this.pattern=new RegExp(this.pattern.trim().replace(/(^[\/#])|([\/#]\w*$)/g,''),t[1])}};
;function SymfonyComponentValidatorConstraintsTime(){this.message='';this.validate=function(t){var s=/^(0[0-9]|1[0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/,a=[],e=FpJsFormValidator;if(!e.isValueEmty(t)&&!s.test(t)){a.push(this.message.replace('{{ value }}',FpJsBaseConstraint.formatValue(t)))};return a}};
;var SymfonyComponentValidatorConstraintsTrue=SymfonyComponentValidatorConstraintsTrue;
;function SymfonyComponentValidatorConstraintsType(){this.message='';this.type='';this.validate=function(e){if(''===e){return[]};var t=[],a=!1;switch(this.type){case'array':a=(e instanceof Array);break;case'bool':case'boolean':a=(typeof e==='boolean');break;case'callable':a=(typeof e==='function');break;case'float':case'double':case'real':a=typeof e==='number'&&e%1!=0;break;case'int':case'integer':case'long':a=(e===parseInt(e));break;case'null':a=(null===e);break;case'numeric':a=!isNaN(e);break;case'object':a=(null!==e)&&(typeof e==='object');break;case'scalar':a=(/boolean|number|string/).test(typeof e);break;case'':case'string':a=(typeof e==='string');break;case'resource':a=!0;break;default:throw'The wrong "'+this.type+'" type was passed to the Type constraint'};if(!a){t.push(this.message.replace('{{ value }}',FpJsBaseConstraint.formatValue(e)).replace('{{ type }}',this.type))};return t}};
;function FpJsFormValidatorBundleFormConstraintUniqueEntity(){this.message='This value is already used.';this.service='doctrine.orm.validator.unique';this.em=null;this.repositoryMethod='findBy';this.fields=[];this.errorPath=null;this.ignoreNull=!0;this.entityName=null;this.groups=[];this.validate=function(t,e){var s=this,i=null,r=FpJsFormValidator.config,o=this.getErrorPathElement(e);if(r['routing']&&r['routing']['check_unique_entity']){i=r['routing']['check_unique_entity']};if(!i){return[]};FpJsFormValidator.ajax.sendRequest(i,{message:this.message,service:this.service,em:this.em,repositoryMethod:this.repositoryMethod,fields:this.fields,errorPath:this.errorPath,ignoreNull:this.ignoreNull?1:0,groups:this.groups,entityName:this.entityName,data:this.getValues(e,this.fields)},function(t){t=JSON.parse(t);var e=[];if(!1===t){e.push(s.message)};FpJsFormValidator.customize(o.domNode,'showErrors',{errors:e,sourceId:'unique-entity-'+s.uniqueId})});return[]};this.onCreate=function(){if(typeof this.fields==='string'){this.fields=[this.fields]}};this.getValues=function(e,r){var t,s={};for(var i=0;i<r.length;i++){t=FpJsFormValidator.getElementValue(e.children[this.fields[i]]);t=t?t:'';s[r[i]]=t};return s};this.getErrorPathElement=function(t){var e=this.fields[0];if(this.errorPath){e=this.errorPath};return t.children[e]}};
;function SymfonyComponentValidatorConstraintsUrl(){this.message='';this.validate=function(t,s){var e=/(ftp|https?):\/\/(www\.)?[\w\-\.@:%_\+~#=]+\.\w{2,3}(\/\w+)*(\?.*)?/,a=[],o=FpJsFormValidator;if(!o.isValueEmty(t)&&!e.test(t)){s.domNode.value='http://'+t;a.push(this.message.replace('{{ value }}',FpJsBaseConstraint.formatValue('http://'+t)))};return a}};
;function SymfonyComponentValidatorConstraintsValid(){this.validate=function(n,t){return[]}};
;function SymfonyComponentFormExtensionCoreDataTransformerArrayToPartsTransformer(){this.partMapping={};this.reverseTransform=function(n){if(typeof n!=='object'){throw new Error('Expected an object.')};var a={};for(var r in this.partMapping){if(undefined!==n[r]){var e=this.partMapping[r].length;while(e--){var t=this.partMapping[r][e];if(undefined!==n[r][t]){a[t]=n[r][t]}}}};return a}};
;function SymfonyComponentFormExtensionCoreDataTransformerBooleanToStringTransformer(){this.trueValue=null;this.reverseTransform=function(e){if(typeof e==='boolean'){return e}
else if(e===this.trueValue){return!0}
else if(!e){return!1}
else{throw new Error('Wrong type of value')}}};
;function SymfonyComponentFormExtensionCoreDataTransformerChoiceToBooleanArrayTransformer(){this.choiceList={};this.placeholderPresent=!1;this.reverseTransform=function(r){if(typeof r!=='object'){throw new Error('Unexpected value type')};for(var e in r){if(r[e]){if(undefined!==this.choiceList[e]){return this.choiceList[e]===''?null:this.choiceList[e]}
else if(this.placeholderPresent&&'placeholder'==e){return null}
else{throw new Error('The choice "'+e+'" does not exist')}}};return null}};
;function SymfonyComponentFormExtensionCoreDataTransformerChoiceToValueTransformer(){this.choiceList={};this.reverseTransform=function(r){for(var o in r){if(''===r[o]){r.splice(o,1)}};return r}};
;function SymfonyComponentFormExtensionCoreDataTransformerChoicesToBooleanArrayTransformer(){this.choiceList={};this.reverseTransform=function(o){if(typeof o!=='object'){throw new Error('Unexpected value type')};var n=[],r=[];for(var e in o){if(o[e]){if(undefined!==this.choiceList[e]){n.push(this.choiceList[e])}
else{r.push(e)}}};if(r.length){throw new Error('The choices "'+r.join(', ')+'" were not found.')};return n}};
;function SymfonyComponentFormExtensionCoreDataTransformerChoicesToValuesTransformer(){this.choiceList={};this.reverseTransform=function(r){for(var o in r){if(''===r[o]){r.splice(o,1)}};return r}};
;function SymfonyComponentFormExtensionCoreDataTransformerDataTransformerChain(r){this.transformers=r;this.reverseTransform=function(r,s){var e=this.transformers.length;for(var n=0;n<e;n++){r=this.transformers[n].reverseTransform(r,s)};return r}};
;function SymfonyComponentFormExtensionCoreDataTransformerDateTimeToArrayTransformer(){this.dateFormat='{0}-{1}-{2}';this.timeFormat='{0}:{1}:{2}';this.reverseTransform=function(t){var i=[];if(t['year']||t['month']||t['day']){i.push(this.formatDate(this.dateFormat,[t['year']?t['year']:'1970',t['month']?this.twoDigits(t['month']):'01',t['day']?this.twoDigits(t['day']):'01']))};if(t['hour']||t['minute']||t['second']){i.push(this.formatDate(this.timeFormat,[t['hour']?this.twoDigits(t['hour']):'00',t['minute']?this.twoDigits(t['minute']):'00',t['second']?this.twoDigits(t['second']):'00']))};return i.join(' ')};this.twoDigits=function(t){return('0'+t).slice(-2)};this.formatDate=function(t,i){return t.replace(/{(\d+)}/g,function(t,o){return typeof i[o]!='undefined'?i[o]:t})}};
;function SymfonyComponentFormExtensionCoreDataTransformerValueToDuplicatesTransformer(){this.keys=[];this.reverseTransform=function(e,o){var r=undefined,i=[];for(var n in e){if(undefined===r){r=e[n]};var s=o.children[this.keys[0]];if(e[n]!==r){i.push(o.invalidMessage);break}};FpJsFormValidator.customize(s.domNode,'showErrors',{errors:i,sourceId:'value-to-duplicates-'+s.id});return r}};
;if(window.jQuery){(function(e){e.fn.jsFormValidator=function(r){if(!r){return FpJsFormValidator.customizeMethods.get.apply(e.makeArray(this),arguments)}
else if(typeof r==='object'){return e(FpJsFormValidator.customizeMethods.init.apply(e.makeArray(this),arguments))}
else if(FpJsFormValidator.customizeMethods[r]){return FpJsFormValidator.customizeMethods[r].apply(e.makeArray(this),Array.prototype.slice.call(arguments,1))}
else{e.error('Method '+r+' does not exist');return this}}})(jQuery)};