'Fulfillment'.list({expose: true, beforeSpec: 'SalesList.js', icon: 'Truck'})
'Unfulfilled Sales Orders'.title({headerOnly: true})
'Back'.action({act: 'cancel', icon: "AngleLeft"})
'Refresh'.action()
'Download Packing Lists PDF'.action()
'Select All'.action()
'Select None'.action()
'Allocate'.action({spec: 'AllocationList.js'})
'Packing Lists'.action()
'View Shipments'.action({spec: 'ViewSOShipments.js'})
//'Refresh'.action({act: "refresh"})
'Manage Orders'.action({spec: 'SOList.js'})
'Download to Excel'.action({act: 'excel'})

'SO'.datatype()
'include'.field({ephemeral: true, tickbox: true, allowInput: true})
'order'.field()
'orderDate'.field()
'shippingNameAndCompany'.field({caption: 'Customer'})
'shipToStateAndCountry'.field()
'shipToPostalCode'.field()
'shipFromLocation'.field({showAsLink: true})
'packable'.field()
'fulfillStage'.field()
'wcNiceStatus'.field()
'financeStatus'.field()
'priority'.field()
'Edit'.action({place: 'row', act: 'edit'})
'SOMaint.js'.maintSpecname()

'Refresh'.act(async list => {
  await list.harmonize()
  let c = await 'Configuration'.bringOrCreate()
  await c.refreshPackables({force: true})
  let mold = global.foreman.doNameToMold('SO')
  mold.version++
})

'Allocate'.availableWhen(() => {
  try {
    let configuration = 'Configuration'.bringSingleFast(); if ( (! configuration) || (configuration === 'na') ) return true
    let res = ( configuration.prioritizeOrderCompletion !== 'Yes' )
    return res
  } catch(e) {
  }
})

'Fulfillment'.filter(async so => {
  return await so.isActive() 
})

'Fulfillment'.defaultSort({field: "orderDate"})

'Fulfillment'.beforeLoading(async list => {
  if ( global.confVal('nfa') === 'Yes' )
    return
  await list.harmonize()
  let c = await 'Configuration'.bringOrCreate()
  await c.refreshPackables()
})

'include'.visibleWhen((list) => {
  return list.situation()._doingPackingLists
})

'Packing Lists'.availableWhen((cast, list) => {
  return ! list.situation()._doingPackingLists
})

'Packing Lists'.act(async (list, cast) => {
  list.situation()._selectedAll = false
  let sos = list.casts()
  for ( var i = 0; i < sos.length; i++ ) {
    let so = sos[i]
    so.include = 'No'
  }
  let mold = global.foreman.doNameToMold('SO')
  mold.version++
  list.situation()._doingPackingLists = true
})

'Back'.act(async (list, cast) => {
  if ( list.situation()._doingPackingLists ) {
    list.situation()._doingPackingLists = false
    return
  }
  list.back()
})

'Select All'.act(async (list, cast) => {
  list.situation()._selectedAll = true
  let sos = list.casts()
  for ( var i = 0; i < sos.length; i++ ) {
    let so = sos[i]
    so.include = 'Yes'
  }
  let mold = global.foreman.doNameToMold('SO')
  mold.version++
})

'Select All'.availableWhen((cast, list) => {
  return list.situation()._doingPackingLists && (! list.situation()._selectedAll)
})

'Select None'.act(async (list, cast) => {
  list.situation()._selectedAll = false
  let sos = list.casts()
  for ( var i = 0; i < sos.length; i++ ) {
    let so = sos[i]
    so.include = 'No'
  }
  let mold = global.foreman.doNameToMold('SO')
  mold.version++
})

'Select None'.availableWhen((cast, list) => {
  return list.situation()._doingPackingLists && list.situation()._selectedAll
})

'Download Packing Lists PDF'.availableWhen((cast, list) => {
  return list.situation()._doingPackingLists
})

'Download Packing Lists PDF'.act(async list => {

  let anySOsHaveBundles = async sos => {
    for ( var i = 0; i < sos.length; i++ ) {
      let so = sos[i]
      if ( await so.hasBundles() )
        return true
    }
    return false
  }

  let sos = await 'SO'.bring({include: 'Yes'})
  if ( sos.length === 0 ) {
    global.gApp.showMessage('Please select at least one order to include')
    return
  }
  let spec = "PackingListsPdf.js"
  if ( await anySOsHaveBundles(sos) )
    spec = "PackingListsWithBundlesPdf.js"
  list.downloadPDF({spec: spec, docName: "Packing Lists.PDF"})
  let mold = global.foreman.doNameToMold('SO')
  mold.version++
})
