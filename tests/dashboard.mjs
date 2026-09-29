// Synthetic fixtures exist only in this isolated test, never in application storage.
import fs from 'node:fs';
import vm from 'node:vm';
import assert from 'node:assert/strict';
const source=fs.readFileSync(new URL('../public/app.js',import.meta.url),'utf8');
const code=source.slice(source.indexOf('function isPants('),source.indexOf('\nconst placeholder='));
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

// Period boundaries, same-day aggregation, sparse dates and currencies.
const spendingFixture=[
 {purchase_date:'2026-08-30',price_minor:100,currency:'USD'},
 {purchase_date:'2026-08-31',price_minor:200,currency:'USD'},
 {purchase_date:'2026-09-29',price_minor:300,currency:'USD'},
 {purchase_date:'2026-09-29',price_minor:50,currency:'USD'},
 {purchase_date:'2026-09-29',price_minor:900,currency:'NIO'},
 {purchase_date:'2026-09-30',price_minor:500,currency:'USD'},
 {purchase_date:null,price_minor:600,currency:'USD'},
 {purchase_date:'2026-09-01',price_minor:null,currency:'USD'},
 {purchase_date:'2026-07-01',price_minor:0,currency:'USD'},
 {purchase_date:'2025-10-01',price_minor:1,currency:'USD'},
 {purchase_date:'2025-09-30',price_minor:2,currency:'USD'}
];
function spending(period,today='2026-09-29',garments=spendingFixture){const ctx={period,today,garments};vm.createContext(ctx);return JSON.parse(JSON.stringify(vm.runInContext(code+';spendingData(garments,today,period)',ctx)));}
assert.deepEqual(spending('30'),[{currency:'NIO',points:[{date:'2026-09-29',value:900}]},{currency:'USD',points:[{date:'2026-08-31',value:200},{date:'2026-09-29',value:350}]}]);
assert.deepEqual(spending('3')[1].points,[{date:'2026-07',value:0},{date:'2026-08',value:300},{date:'2026-09',value:350}]);
assert.deepEqual(spending('6'),spending('3'));
assert.equal(spending('12')[1].points[0].date,'2025-10');
assert.equal(spending('all')[1].points[0].date,'2025-09');
assert.deepEqual(spending('30','2027-01-01'),[]);
assert.deepEqual(spending('all','2026-09-29',[]),[]);
assert.deepEqual(spending('30','2024-03-01',[{purchase_date:'2024-02-01',price_minor:10,currency:'USD'},{purchase_date:'2024-01-31',price_minor:20,currency:'USD'}])[0].points,[{date:'2024-02-01',value:10}]);
const measured=structuredClone(fixture);measured.garments[0].waist='30';measured.garments[0].length='36';measured.garments[0].pants_type='Mi corte';
s=run(measured);assert.equal(s.sizes.find(x=>x.name==='30x36').n,1);assert.equal(s.pants.find(x=>x.name==='Mi corte').n,1);
console.log('Spending/pants checks passed: five periods, leap dates, sparse points, currency isolation, custom types and separate measurements.');
const renderContext={session:{currencies:{USD:2}},spendingPeriod:'30',esc:v=>String(v).replaceAll('<','&lt;'),money:(n,c)=>`${n/100} ${c}`};vm.createContext(renderContext);
let svg=vm.runInContext(code+`;spendingChart({currency:'USD',points:[{date:'2026-09-01',value:100},{date:'2026-09-05',value:300},{date:'2026-09-08',value:200}]})`,renderContext);
assert.equal((svg.match(/class="spending-point"/g)||[]).length,3);assert.ok(svg.includes('class="spending-line"'));assert.ok(svg.includes('2026-09-05'));
svg=vm.runInContext(`spendingChart({currency:'USD',points:[{date:'2026-09-01',value:0}]})`,renderContext);assert.equal((svg.match(/class="spending-point"/g)||[]).length,1);assert.ok(!svg.includes('class="spending-line"'));assert.ok(!svg.includes('NaN'));
console.log('Chart rendering passed: smooth paths, exact tables, single and zero-valued points.');
