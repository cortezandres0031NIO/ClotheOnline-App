// Ejecuta la función de estadísticas real, sin navegador ni datos personales.
import fs from 'node:fs';
import vm from 'node:vm';
import assert from 'node:assert/strict';
const source=fs.readFileSync(new URL('../public/app.js',import.meta.url),'utf8');
const func=source.slice(source.indexOf('function statsData()'),source.indexOf('\nfunction bars('));
const fixture={garments:[{id:'a',name:'Camisa',category:'Camisas',price_minor:2500,currency:'USD',purchase_date:'2026-07-01'},{id:'b',name:'Pantalón',category:'Pantalones',price_minor:180000,currency:'NIO',purchase_date:'2026-07-01'},{id:'c',name:'Sin fecha',category:'Camisas',price_minor:500,currency:'USD',purchase_date:null}],outfits:[{id:'o',name:'Oficina'}],wears:[{id:'w1',date:'2026-07-02',outfit_id:'o',items:[{id:'a',category:'Camisas'}]},{id:'w2',date:'2026-09-01',outfit_id:'o',items:[{id:'a',category:'Camisas'}]}]};
function run(f={from:'',to:'',category:''},data=fixture){const ctx={data,statsFilter:f,session:{today:'2026-09-27'}};vm.createContext(ctx);return JSON.parse(JSON.stringify(vm.runInContext(func+';statsData()',ctx)));}
let checks=0;let s=run();assert.equal(s.ws.length,2);checks++;assert.equal(s.counts.find(x=>x.g.id==='b').n,0);checks++;assert.deepEqual(s.months,{'2026-07':1,'2026-08':0,'2026-09':1});checks++;assert.deepEqual(s.expenses,{'2026-07|USD':2500,'2026-07|NIO':180000});checks++;assert.equal(s.repeats[0].n,2);checks++;
s=run({from:'2026-09-01',to:'2026-09-27',category:''});assert.equal(s.ws.length,1);assert.equal(s.purchases.length,0);checks++;
s=run({from:'',to:'',category:'Pantalones'});assert.equal(s.ws.length,0);assert.equal(s.gs.length,1);assert.deepEqual(s.expenses,{'2026-07|NIO':180000});checks++;
const edited=structuredClone(fixture);edited.garments[0].category='Otros';s=run({from:'',to:'',category:'Camisas'},edited);assert.equal(s.ws.length,2);assert.equal(s.counts.find(x=>x.g.id==='a').n,2);assert.deepEqual(s.expenses,{});checks++;
s=run({from:'',to:'',category:''},{garments:[],outfits:[],wears:[]});assert.equal(s.counts.length,0);assert.deepEqual(s.months,{});checks++;
console.log(`${checks} comprobaciones de estadísticas superadas: periodos, categorías históricas, meses sin uso, monedas separadas y estado vacío.`);
