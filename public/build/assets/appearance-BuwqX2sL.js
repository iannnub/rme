import{j as e,L as m}from"./vendor-react-CdKlnFN4.js";import{u as d}from"./app-BlkAdyTE.js";import{c as t,a as n}from"./createLucideIcon-Ca-88Kj8.js";import{S as u,H as h}from"./layout-Makk8r0Q.js";import{A as x}from"./app-layout-BCBBThMx.js";import"./vendor-radix-BGpy1hrt.js";import"./flash-toaster-BAUyXFi2.js";import"./check-I7fy-UIv.js";/**
 * @license lucide-react v0.475.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const g=[["rect",{width:"20",height:"14",x:"2",y:"3",rx:"2",key:"48i651"}],["line",{x1:"8",x2:"16",y1:"21",y2:"21",key:"1svkeh"}],["line",{x1:"12",x2:"12",y1:"17",y2:"21",key:"vw1qmm"}]],y=t("Monitor",g);/**
 * @license lucide-react v0.475.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const k=[["path",{d:"M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z",key:"a7tn18"}]],f=t("Moon",k);/**
 * @license lucide-react v0.475.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const b=[["circle",{cx:"12",cy:"12",r:"4",key:"4exip2"}],["path",{d:"M12 2v2",key:"tus03m"}],["path",{d:"M12 20v2",key:"1lh1kg"}],["path",{d:"m4.93 4.93 1.41 1.41",key:"149t6j"}],["path",{d:"m17.66 17.66 1.41 1.41",key:"ptbguv"}],["path",{d:"M2 12h2",key:"1t8f8n"}],["path",{d:"M20 12h2",key:"1q8mjw"}],["path",{d:"m6.34 17.66-1.41 1.41",key:"1m8zz5"}],["path",{d:"m19.07 4.93-1.41 1.41",key:"1shlcs"}]],j=t("Sun",b);function v({className:o="",...r}){const{appearance:s,updateAppearance:i}=d(),p=[{value:"light",icon:j,label:"Terang"},{value:"dark",icon:f,label:"Gelap"},{value:"system",icon:y,label:"Sistem"}];return e.jsx("div",{className:n("bg-muted inline-flex max-w-full flex-wrap gap-1 rounded-lg p-1",o),...r,children:p.map(({value:a,icon:c,label:l})=>e.jsxs("button",{type:"button",onClick:()=>i(a),className:n("flex min-h-10 items-center rounded-md px-3.5 py-1.5 transition-colors",s===a?"bg-card text-foreground shadow-xs":"text-muted-foreground hover:bg-background/60 hover:text-foreground"),children:[e.jsx(c,{className:"-ml-1 h-4 w-4"}),e.jsx("span",{className:"ml-1.5 text-sm",children:l})]},a))})}const M=[{title:"Pengaturan Tampilan",href:"/pengaturan/tampilan"}];function q(){return e.jsxs(x,{breadcrumbs:M,children:[e.jsx(m,{title:"Pengaturan Tampilan"}),e.jsx(u,{children:e.jsxs("div",{className:"space-y-6",children:[e.jsx(h,{title:"Tampilan Aplikasi",description:"Pilih tema tampilan terang, gelap, atau mengikuti pengaturan perangkat Anda."}),e.jsx(v,{})]})})]})}export{q as default};
