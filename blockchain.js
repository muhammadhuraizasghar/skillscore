(() => {
  const enc = new TextEncoder();
  async function sha256(s){ const buf = await crypto.subtle.digest('SHA-256', enc.encode(s)); const arr = Array.from(new Uint8Array(buf)); return arr.map(b=>b.toString(16).padStart(2,'0')).join(''); }
  const storeKey = (uid, aid) => `bs_chain_${uid}_${aid}`;
  const Blockchain = {
    _uid: null,
    _aid: null,
    _chain: [],
    async init(uid, attemptId){ this._uid=uid; this._aid=attemptId; this._chain=[]; await this.record('genesis', { uid, attemptId }); },
    async record(type, payload){ const prev = this._chain.length ? this._chain[this._chain.length-1].hash : 'GENESIS'; const entry = { index:this._chain.length, ts:Date.now(), type, payload, prevHash:prev, hash:null }; const raw = JSON.stringify({index:entry.index,ts:entry.ts,type,prevHash:prev,payload}); entry.hash = await sha256(raw); this._chain.push(entry); try { localStorage.setItem(storeKey(this._uid,this._aid), JSON.stringify(this._chain)); } catch(e){} return entry.hash; },
    async finalize(){ return this._chain.length ? this._chain[this._chain.length-1].hash : null; },
    length(){ return this._chain.length; },
    async validate(){ for(let i=0;i<this._chain.length;i++){ const e=this._chain[i]; const raw=JSON.stringify({index:e.index,ts:e.ts,type:e.type,prevHash:e.prevHash,payload:e.payload}); const h=await sha256(raw); if(h!==e.hash) return false; if(i>0 && this._chain[i-1].hash!==e.prevHash) return false; } return true; },
    policyForMcqs(profile, attemptNo){ const base=300; const extra=50*Math.max(0,attemptNo-1); const iq=Math.round(0.25*(base+extra)); const eq=Math.round(0.20*(base+extra)); const personality=Math.round(0.20*(base+extra)); const field=(base+extra)-iq-eq-personality; return { total:base+extra, iq, eq, personality, field, fieldName:(profile && profile.degree_field)||'general' }; },
    computeScore(answers){ let total=0, correct=0; for(const a of answers){ total++; if(a.selected && a.selected===a.correct) correct++; } const grade = total ? Math.round((correct/total)*100)+'%' : null; return { total, correct, grade }; },
    async aiSuggestPolicy(prompt){ const r = await fetch('test.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:new URLSearchParams({ action:'ai_policy', prompt }) }); const j = await r.json(); return j.text || null; }
  };
  window.Blockchain = Blockchain;
})();



