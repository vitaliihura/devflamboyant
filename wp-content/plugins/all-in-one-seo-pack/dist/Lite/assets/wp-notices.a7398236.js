const n=window.wp||{};typeof n.data<"u"&&n.data.subscribe(function(){const t=n.data.select("core/editor"),a=n.data.select("core/notices").getNotices(),o=t==null?void 0:t.getCurrentPostAttribute("aioseo_notices");t!=null&&t.isSavingPost()&&!t.isAutosavingPost()&&o&&o.forEach(e=>{var r;!e.message||a.find(s=>s.id===e.options.id)||(e.message=e.message.replace(/(<([^>]+)>)/gi,""),(r=e.options)!=null&&r.actions&&e.options.actions.map(s=>((s==null?void 0:s.target)==="_blank"&&(s.onClickUrl=s.url,s.onClick=function(i){i.preventDefault(),window.open(s.onClickUrl),n.data.dispatch("core/notices").removeNotice(e.options.id)},delete s.url,delete s.target),s!=null&&s.class&&(s.className=s.class,delete s.class),s)),n.data.dispatch("core/notices").createNotice(e.status||"warning",e.message,e.options||[]),window.aioseoBus&&window.aioseoBus.$emit("wp-core-notice-created"))})});
