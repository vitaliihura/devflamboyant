import{f as o,e as i,u}from"./links.37929787.js";import{a as c}from"./addons.dacbad81.js";import{C as d}from"./Index.9e89d7ca.js";import{S as l}from"./Caret.4d98c50a.js";import{_ as t,s as a}from"./default-i18n.3881921e.js";const h={computed:{features(){return[...this.$constants.WIZARD_FEATURES]},getSelectedUpsellFeatures(){const e=o();return e.features?e.features.filter(n=>this.needsUpsell(this.features.find(r=>r.value===n))).map(n=>this.features.find(r=>r.value===n)):[]}},methods:{needsUpsell(e){return e.pro?i().isUnlicensed?!0:e.upgrade&&c.requiresUpgrade(e.upgrade):!1}},mounted(){const e=o();e.currentStage=this.stage}},_={components:{CoreModal:d,SvgClose:l},data(){return{loading:!1,showModal:!1}},methods:{processOptIn(){const e=o();this.setupWizardStore.smartRecommendations.usageTracking=!0,this.loading=!0,e.saveWizard("smartRecommendations").then(()=>{const n=u();window.location.href=n.aioseo.urls.aio.dashboard})}}},s="all-in-one-seo-pack",W=()=>({strings:{skipThisStep:t("Skip this Step",s),goBack:t("Go Back",s),saveAndContinue:t("Save and Continue",s),closeAndExit:t("Close and Exit Wizard Without Saving",s),buildABetterAioseo:a(t("Build a Better %1$s",s),"AIOSEO"),getImprovedFeatures:a(t("Get improved features and faster fixes by sharing non-sensitive data via usage tracking that shows us how %1$s is being used. No personal data is tracked or stored.",s),"AIOSEO"),noThanks:t("No thanks",s),yesCountMeIn:t("Yes, count me in!",s)}});export{h as W,_ as a,W as u};
