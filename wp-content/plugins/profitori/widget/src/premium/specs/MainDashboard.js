'MainDashboard'.dashboard({expose: true, beforeSpec: 'Modify.js'})
'Dashboard'.title()
'Short Stock'.tile({spec: "ViewLowInventory.js", widthPct: 50, heightPct: 50})
'Overdue PO Stock'.tile({spec: "OverduePOStock.js", widthPct: 50, heightPct: 50})
'Sales'.tile({spec: "SalesList.js", widthPct: 50, heightPct: 50})
'Unfulfilled Sales Orders'.tile({spec: "UnfulfilledGraph.js", widthPct: 50, heightPct: 50})

'MainDashboard'.alternativeSpec(async dashboard => {
  if ( global.willSunder() ) return null
  let c = await 'Configuration'.bringFirst(); if ( ! c ) return null
  let t = c.defaultDashboardTitle; if ( ! t ) return null
  if ( t === 'Main Dashboard' ) 
    return null
  let cdb = await 'CustomDashboard'.bringFirst({title: t}); if ( ! cdb ) return null
  return cdb.name
})
