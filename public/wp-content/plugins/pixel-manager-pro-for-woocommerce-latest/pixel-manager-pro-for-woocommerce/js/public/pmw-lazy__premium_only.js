/*! Copyright 2024 SweetCode. All rights reserved. */(()=>{document.querySelectorAll('script[type="text/pmw-lazy"]').forEach((e=>{let t=document.createElement("link");t.href=e.src,t.rel="preload",t.as="script",document.head.appendChild(t)}));function e(){document.querySelectorAll('script[type="text/pmw-lazy"]').forEach((e=>{e.remove();let t=document.createElement("script");t.src=e.src,document.body.appendChild(t)}))}["keydown","mousedown","mousemove","touchmove","touchstart","touchend","wheel"].forEach((t=>{document.addEventListener(t,(function o(){e(),document.removeEventListener(t,o)}))})),document.addEventListener("DOMContentLoaded",(()=>{"undefined"!=typeof wpmDataLayer&&("cart"!==wpmDataLayer?.shop?.page_type&&"checkout"!==wpmDataLayer?.shop?.page_type||e())}))})();
//# sourceMappingURL=pmw-lazy__premium_only.js.map