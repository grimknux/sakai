import{B as H,ad as J,c as v,l as f,b as d,h as S,D,e as R,aj as A,E as y,t as w,af as U,d as T,G as ee,w as V,aX as te,ag as ne,aY as oe,aB as le,R as se,A as ae,C as re,ak as ie,r as _,an as $,o as de,i as ce,ay as ue,F as M,ae as N,aW as pe,aZ as ge,a_ as F,Q as fe,m as me,f as be}from"./index-vuyVAbMF.js";var ve=`
    .p-fieldset {
        background: dt('fieldset.background');
        border: 1px solid dt('fieldset.border.color');
        border-radius: dt('fieldset.border.radius');
        color: dt('fieldset.color');
        padding: dt('fieldset.padding');
        margin: 0;
    }

    .p-fieldset-legend {
        background: dt('fieldset.legend.background');
        border-radius: dt('fieldset.legend.border.radius');
        border-width: dt('fieldset.legend.border.width');
        border-style: solid;
        border-color: dt('fieldset.legend.border.color');
        padding: dt('fieldset.legend.padding');
        transition:
            background dt('fieldset.transition.duration'),
            color dt('fieldset.transition.duration'),
            outline-color dt('fieldset.transition.duration'),
            box-shadow dt('fieldset.transition.duration');
    }

    .p-fieldset-toggleable > .p-fieldset-legend {
        padding: 0;
    }

    .p-fieldset-toggle-button {
        cursor: pointer;
        user-select: none;
        overflow: hidden;
        position: relative;
        text-decoration: none;
        display: flex;
        gap: dt('fieldset.legend.gap');
        align-items: center;
        justify-content: center;
        padding: dt('fieldset.legend.padding');
        background: transparent;
        border: 0 none;
        border-radius: dt('fieldset.legend.border.radius');
        transition:
            background dt('fieldset.transition.duration'),
            color dt('fieldset.transition.duration'),
            outline-color dt('fieldset.transition.duration'),
            box-shadow dt('fieldset.transition.duration');
        outline-color: transparent;
    }

    .p-fieldset-legend-label {
        font-weight: dt('fieldset.legend.font.weight');
    }

    .p-fieldset-toggle-button:focus-visible {
        box-shadow: dt('fieldset.legend.focus.ring.shadow');
        outline: dt('fieldset.legend.focus.ring.width') dt('fieldset.legend.focus.ring.style') dt('fieldset.legend.focus.ring.color');
        outline-offset: dt('fieldset.legend.focus.ring.offset');
    }

    .p-fieldset-toggleable > .p-fieldset-legend:hover {
        color: dt('fieldset.legend.hover.color');
        background: dt('fieldset.legend.hover.background');
    }

    .p-fieldset-toggle-icon {
        color: dt('fieldset.toggle.icon.color');
        transition: color dt('fieldset.transition.duration');
    }

    .p-fieldset-toggleable > .p-fieldset-legend:hover .p-fieldset-toggle-icon {
        color: dt('fieldset.toggle.icon.hover.color');
    }

    .p-fieldset-content-container {
        display: grid;
        grid-template-rows: 1fr;
    }

    .p-fieldset-content-wrapper {
        min-height: 0;
    }

    .p-fieldset-content {
        padding: dt('fieldset.content.padding');
    }
`,ye={root:function(t){var i=t.props;return["p-fieldset p-component",{"p-fieldset-toggleable":i.toggleable}]},legend:"p-fieldset-legend",legendLabel:"p-fieldset-legend-label",toggleButton:"p-fieldset-toggle-button",toggleIcon:"p-fieldset-toggle-icon",contentContainer:"p-fieldset-content-container",contentWrapper:"p-fieldset-content-wrapper",content:"p-fieldset-content"},he=H.extend({name:"fieldset",style:ve,classes:ye}),we={name:"BaseFieldset",extends:ae,props:{legend:String,toggleable:Boolean,collapsed:Boolean,toggleButtonProps:{type:null,default:null}},style:he,provide:function(){return{$pcFieldset:this,$parentInstance:this}}},W={name:"Fieldset",extends:we,inheritAttrs:!1,emits:["update:collapsed","toggle"],data:function(){return{d_collapsed:this.collapsed}},watch:{collapsed:function(t){this.d_collapsed=t}},methods:{toggle:function(t){this.d_collapsed=!this.d_collapsed,this.$emit("update:collapsed",this.d_collapsed),this.$emit("toggle",{originalEvent:t,value:this.d_collapsed})},onKeyDown:function(t){(t.code==="Enter"||t.code==="NumpadEnter"||t.code==="Space")&&(this.toggle(t),t.preventDefault())}},computed:{buttonAriaLabel:function(){return this.toggleButtonProps&&this.toggleButtonProps.ariaLabel?this.toggleButtonProps.ariaLabel:this.legend},dataP:function(){return re({toggleable:this.toggleable})}},directives:{ripple:se},components:{PlusIcon:le,MinusIcon:oe}};function j(e){"@babel/helpers - typeof";return j=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(t){return typeof t}:function(t){return t&&typeof Symbol=="function"&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},j(e)}function K(e,t){var i=Object.keys(e);if(Object.getOwnPropertySymbols){var p=Object.getOwnPropertySymbols(e);t&&(p=p.filter(function(u){return Object.getOwnPropertyDescriptor(e,u).enumerable})),i.push.apply(i,p)}return i}function x(e){for(var t=1;t<arguments.length;t++){var i=arguments[t]!=null?arguments[t]:{};t%2?K(Object(i),!0).forEach(function(p){ke(e,p,i[p])}):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(i)):K(Object(i)).forEach(function(p){Object.defineProperty(e,p,Object.getOwnPropertyDescriptor(i,p))})}return e}function ke(e,t,i){return(t=_e(t))in e?Object.defineProperty(e,t,{value:i,enumerable:!0,configurable:!0,writable:!0}):e[t]=i,e}function _e(e){var t=Pe(e,"string");return j(t)=="symbol"?t:t+""}function Pe(e,t){if(j(e)!="object"||!e)return e;var i=e[Symbol.toPrimitive];if(i!==void 0){var p=i.call(e,t);if(j(p)!="object")return p;throw new TypeError("@@toPrimitive must return a primitive value.")}return(t==="string"?String:Number)(e)}var Se=["data-p"],je=["data-p"],Be=["id"],Oe=["id","aria-controls","aria-expanded","aria-label"],Ce=["id","aria-labelledby"];function De(e,t,i,p,u,s){var B=J("ripple");return f(),v("fieldset",y({class:e.cx("root"),"data-p":s.dataP},e.ptmi("root")),[d("legend",y({class:e.cx("legend"),"data-p":s.dataP},e.ptm("legend")),[D(e.$slots,"legend",{toggleCallback:s.toggle},function(){return[e.toggleable?R("",!0):(f(),v("span",y({key:0,id:e.$id+"_header",class:e.cx("legendLabel")},e.ptm("legendLabel")),w(e.legend),17,Be)),e.toggleable?A((f(),v("button",y({key:1,id:e.$id+"_header",type:"button","aria-controls":e.$id+"_content","aria-expanded":!u.d_collapsed,"aria-label":s.buttonAriaLabel,class:e.cx("toggleButton"),onClick:t[0]||(t[0]=function(){return s.toggle&&s.toggle.apply(s,arguments)}),onKeydown:t[1]||(t[1]=function(){return s.onKeyDown&&s.onKeyDown.apply(s,arguments)})},x(x({},e.toggleButtonProps),e.ptm("toggleButton"))),[D(e.$slots,e.$slots.toggleicon?"toggleicon":"togglericon",{collapsed:u.d_collapsed,class:U(e.cx("toggleIcon"))},function(){return[(f(),T(ee(u.d_collapsed?"PlusIcon":"MinusIcon"),y({class:e.cx("toggleIcon")},e.ptm("toggleIcon")),null,16,["class"]))]}),d("span",y({class:e.cx("legendLabel")},e.ptm("legendLabel")),w(e.legend),17)],16,Oe)),[[B]]):R("",!0)]})],16,je),S(ne,y({name:"p-collapsible"},e.ptm("transition")),{default:V(function(){return[A(d("div",y({id:e.$id+"_content",class:e.cx("contentContainer"),role:"region","aria-labelledby":e.$id+"_header"},e.ptm("contentContainer")),[d("div",y({class:e.cx("contentWrapper")},e.ptm("contentWrapper")),[d("div",y({class:e.cx("content")},e.ptm("content")),[D(e.$slots,"default")],16)],16)],16,Ce),[[te,!u.d_collapsed]])]}),_:3},16)],16,Se)}W.render=De;const $e={class:"card"},Ve={class:"flex items-center justify-between mb-4"},Ie={class:"grid grid-cols-12 gap-4"},Ee={class:"col-span-12 md:col-span-3"},Le={key:0,class:"block mt-2 text-orange-600 dark:text-orange-400"},Re={key:1,class:"block mt-2 text-surface-500"},Ae={class:"col-span-12 md:col-span-9"},Me={key:0,class:"p-3 border rounded"},Ne={key:1,class:"flex flex-col gap-4"},Fe={class:"flex items-center justify-between w-full gap-3"},Ke=["onClick"],xe={class:"font-bold capitalize"},Ue={class:"text-surface-500"},Te={class:"flex items-center gap-2"},We={class:"grid grid-cols-12 gap-2"},ze={class:"text-surface-500"},Ge={class:"p-3 border rounded bg-surface-50 dark:bg-surface-900"},Qe={class:"text-sm"},Ye={__name:"RolePermissionsPage",setup(e){const t=ie(),i=_([]),p=_([]),u=_(null),s=_([]),B=_(!1),O=_(!1),k=_({}),I=$(()=>i.value.find(o=>o.id===u.value)||null),P=$(()=>{var o;return(((o=I.value)==null?void 0:o.slug)||"")==="super-admin"});async function z(){var o,n,a,c,g;try{const l=await pe.dropdown();i.value=(l.roles||[]).map(r=>({...r,id:Number(r.id)}));const m=await ge.list();p.value=(m.permissions||[]).map(r=>({...r,id:Number(r.id)}))}catch(l){const m=((n=(o=l==null?void 0:l.response)==null?void 0:o.data)==null?void 0:n.message)||((g=(c=(a=l==null?void 0:l.response)==null?void 0:a.data)==null?void 0:c.messages)==null?void 0:g.error)||(l==null?void 0:l.message)||"Failed to load data";t.add({severity:"error",summary:"Error",detail:m,life:4e3})}}async function E(o){var a,c,g,l,m;const n=o||u.value;if(!n){s.value=[],k.value={};return}B.value=!0,s.value=[];try{const r=await F.get(n);s.value=(r.permission_ids||[]).map(Number),Y()}catch(r){s.value=[],k.value={};const h=((c=(a=r==null?void 0:r.response)==null?void 0:a.data)==null?void 0:c.message)||((m=(l=(g=r==null?void 0:r.response)==null?void 0:g.data)==null?void 0:l.messages)==null?void 0:m.error)||(r==null?void 0:r.message)||"Error";t.add({severity:"error",summary:"Error",detail:h,life:4e3})}finally{B.value=!1}}de(async()=>{await z(),i.value.length&&(u.value=i.value[0].id,await E(u.value))});const L=$(()=>{const o={};for(const n of p.value){const a=n.module||"general";o[a]||(o[a]=[]),o[a].push(n)}for(const n of Object.keys(o))o[n].sort((a,c)=>a.name.localeCompare(c.name));return o});function C(o){return o.map(n=>n.id)}function G(o){const n=C(o);return n.length>0&&n.every(a=>s.value.includes(a))}function Q(o){const n=C(o),a=n.filter(c=>s.value.includes(c)).length;return a>0&&a<n.length}function X(o,n){const a=C(o);n?s.value=[...new Set([...s.value,...a])]:s.value=s.value.filter(c=>!a.includes(c))}function Y(){const o={};for(const[n,a]of Object.entries(L.value)){const c=a.some(g=>s.value.includes(g.id));o[n]=!c}k.value=o}async function Z(){var o,n,a,c,g;if(u.value){if(P.value){t.add({severity:"warn",summary:"Locked",detail:"Super Admin role permissions are locked.",life:3e3});return}O.value=!0;try{const l=s.value.map(Number);await F.set(u.value,l),t.add({severity:"success",summary:"Saved",detail:"Role permissions updated",life:3e3})}catch(l){const m=((n=(o=l==null?void 0:l.response)==null?void 0:o.data)==null?void 0:n.message)||((g=(c=(a=l==null?void 0:l.response)==null?void 0:a.data)==null?void 0:c.messages)==null?void 0:g.error)||(l==null?void 0:l.message)||"Error";t.add({severity:"error",summary:"Error",detail:m,life:4e3})}finally{O.value=!1}}}return(o,n)=>{var m;const a=ce,c=ue,g=fe,l=W;return f(),v("div",$e,[d("div",Ve,[n[3]||(n[3]=d("h4",{class:"m-0"},"Role Permissions",-1)),S(a,{label:"Save",icon:"pi pi-check",loading:O.value,disabled:!u.value||P.value,onClick:Z},null,8,["loading","disabled"])]),d("div",Ie,[d("div",Ee,[n[4]||(n[4]=d("label",{class:"block font-bold mb-2"},"Select Role",-1)),S(c,{modelValue:u.value,"onUpdate:modelValue":[n[0]||(n[0]=r=>u.value=r),E],options:i.value,optionLabel:"name",optionValue:"id",placeholder:"Select role",class:"w-full"},null,8,["modelValue","options"]),P.value?(f(),v("small",Le," Super Admin role is locked (cannot edit permissions). ")):(f(),v("small",Re," Select permissions for the chosen role. "))]),d("div",Ae,[u.value?(f(),v("div",Ne,[(f(!0),v(M,null,N(L.value,(r,h)=>(f(),T(l,{key:h,collapsed:k.value[h],"onUpdate:collapsed":b=>k.value[h]=b,class:"mb-3"},{legend:V(()=>[d("div",Fe,[d("div",{class:"flex items-center gap-2 cursor-pointer select-none hover:bg-surface-100 dark:hover:bg-surface-800 px-2 py-1 rounded",onClick:b=>k.value[h]=!k.value[h]},[d("span",{class:U(["pi",k.value[h]?"pi-chevron-right":"pi-chevron-down"]),style:{color:"var(--primary-color)"},onClick:n[1]||(n[1]=be(()=>{},["stop"]))},null,2),d("span",xe,w(h),1),d("small",Ue," ("+w(r.filter(b=>s.value.includes(b.id)).length)+"/"+w(r.length)+") ",1)],8,Ke),d("div",Te,[S(g,{modelValue:G(r),indeterminate:Q(r),binary:!0,disabled:P.value,"onUpdate:modelValue":b=>X(r,b)},null,8,["modelValue","indeterminate","disabled","onUpdate:modelValue"]),n[5]||(n[5]=d("span",{class:"text-sm"},"Select all",-1))])])]),default:V(()=>[d("div",We,[(f(!0),v(M,null,N(r,b=>(f(),v("div",{key:b.id,class:"col-span-12 md:col-span-6 flex items-center gap-2"},[S(g,{modelValue:s.value,"onUpdate:modelValue":n[2]||(n[2]=q=>s.value=q),value:b.id,disabled:P.value},null,8,["modelValue","value","disabled"]),d("span",null,[me(w(b.name)+" ",1),d("small",ze,"("+w(b.slug)+")",1)])]))),128))])]),_:2},1032,["collapsed","onUpdate:collapsed"]))),128)),d("div",Ge,[n[6]||(n[6]=d("div",{class:"font-medium"},"Debug:",-1)),d("div",Qe,"Role: "+w((m=I.value)==null?void 0:m.slug)+" | Selected IDs: "+w(s.value),1)])])):(f(),v("div",Me,"Select a role to manage permissions."))])])])}}};export{Ye as default};
