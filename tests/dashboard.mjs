// Synthetic fixtures exist only in this isolated test, never in application storage.
import fs from 'node:fs';
import vm from 'node:vm';
import assert from 'node:assert/strict';
const source=fs.readFileSync(new URL('../public/app.js',import.meta.url),'utf8');
const code=source.slice(source.indexOf('function dashboardData('),source.indexOf('\nconst placeholder='));
function run(data){const context={data};vm.createContext(context);return JSON.parse(JSON.stringify(vm.runInContext(code+`;dashboardData(data,'2026-09-29')`,context)));}
const empty={garments:[],outfits:[],wears:[]};
let s=run(empty);assert.equal(s.total,0);assert.equal(s.usedMonth,0);assert.equal(s.tagCount,0);assert.deepEqual(s.spend,[]);assert.deepEqual(s.cost,[]);assert.deepEqual(s.sizes,[]);assert.deepEqual(s.best,[]);
const fixture={garments:[
 {id:'a',name:'A',category:'Pantalones',size:'42',pants_type:'Cargo',price_minor:1000,currency:'USD',purchase_date:'2026-09-01'},
 {id:'b',name:'B',category:'Pantalones',size:'Talla antigua',price_minor:30000,currency:'NIO',purchase_date:'2026-08-01'},
 {id:'c',name:'C',category:'Camisas',archived:true,price_minor:0,currency:'USD',purchase_date:null},
 {id:'d',name:'D',category:'Jeans',price_minor:null,currency:''}
],outfits:[{id:'o',tags:['Casual','Formal']},{id:'p',tags:['casual']},{id:'q'}],wears:[
 {id:'w',date:'2026-09-02',outfit_id:'o',items:[{id:'a',category:'Jeans'},{id:'c',category:'Camisas'}]},
 {id:'x',date:'2026-09-03',outfit_id:'o',items:[{id:'a',category:'Jeans'}]},
 {id:'y',date:'2026-01-02',outfit_id:null,items:[{id:'b',category:'Pantalones'}]}
]};
s=run(fixture);assert.equal(s.total,4);assert.equal(s.active,3);assert.equal(s.usedMonth,2);assert.equal(s.tagCount,2);
assert.deepEqual(s.spend,[['USD',1000],['NIO',30000]]);assert.deepEqual(s.cost,[['USD',1000/3],['NIO',30000]]);
assert.deepEqual(s.monthly,[['2026-08|NIO',30000],['2026-09|USD',1000]]);
assert.equal(s.categories[0].name,'Jeans');assert.equal(s.categories[0].n,2);
assert.equal(s.pants.find(x=>x.name==='Sin especificar').n,2);assert.equal(s.sizes.find(x=>x.name==='Talla antigua').n,1);
assert.equal(s.tags.find(x=>x.name==='Casual').n,2);assert.equal(s.tagUses.find(x=>x.name==='Casual').n,2);
assert.equal(s.never,1);assert.equal(s.neglected[0].g.id,'b');assert.equal(s.best[0][1].g.id,'c');
const correction=structuredClone(fixture);correction.wears[1].items=[{id:'b',category:'Pantalones'}];
s=run(correction);assert.equal(s.most.find(x=>x.g.id==='a').n,1);assert.equal(s.most.find(x=>x.g.id==='b').n,2);
correction.wears=[];s=run(correction);assert.deepEqual(s.best,[]);assert.deepEqual(s.cost,[]);assert.equal(s.never,3);assert.deepEqual(s.tagUses,[]);
console.log('Dashboard checks passed: empty data, currencies, zero prices, archived garments, legacy sizes, tags, history and corrections.');
