'BulkAdd'.list()
'Bulk Add PO Lines'.title()
'Cancel'.action({act: 'cancel', icon: 'AngleLeft'})
'OK'.action({icon: 'CheckDouble'})

'BulkPOLine'.datatype({transient: true})
'product'.field({refersTo: 'products', key: true, readOnly: true, hidden: true})
'uniqueName'.field({caption: "Product", showAsLink: true, useForTotalCaption: true, readOnly: true})
'sku'.field({caption: 'SKU', readOnly: true})
'supplierSku'.field({caption: 'Supplier SKU', readOnly: true})
'thumbnailImage'.field({postImage: true, postImageType: 'thumbnail', postIdField: 'product', caption: 'Image'})
'embryoNotes'.field({caption: 'Unlisted Notes', readOnly: true})
'embryoQuantityOnSalesOrders'.field({caption: 'Unlisted Qty on Sales Orders', numeric: true, readOnly: true})
'quantityOnHand'.field({numeric: true, readOnly: true})
'quantityOnPurchaseOrders'.field({numeric: true, caption: "On Purchase Orders", readOnly: true})
'bulkAddQuantity'.field({numeric: true, readOnly: false, caption: 'Order Quantity', refreshOnChange: true})
'warningMsg'.field({caption: '', readOnly: true})
'priorWeeksSalesUnits'.field({numeric: true, decimals: 0, readOnly: true})
'priorWeeksSalesValue'.field({numeric: true, decimals: 2, readOnly: true})
'lastSaleDate'.field({date: true, allowEmpty: true, readOnly: true})
'lastPurchaseDate'.field({date: true, allowEmpty: true, readOnly: true})
'lastReceivedDate'.field({date: true, allowEmpty: true, readOnly: true})
'avenue'.field({refersTo: 'Avenue', hidden: true})
'inventory'.field({refersTo: 'Inventory', hidden: true})

/* eslint-disable no-cond-assign */

'embryoNotes'.columnVisibleWhen(() => {
  return global.confVal('emb') === 'Yes'
})

'embryoQuantityOnSalesOrders'.columnVisibleWhen(() => {
  return global.confVal('emb') === 'Yes'
})

'warningMsg'.color( (list, bulkPOLine) => {
  if ( ! bulkPOLine.warningMsg ) return null
  return {fg: 'red'}
})

'warningMsg'.calculate(async bulkPOLine => {
  if ( ! bulkPOLine.bulkAddQuantity ) return null
  let inventory = await bulkPOLine.toInventory(); if ( ! inventory ) return
  if ( inventory.situation && inventory.situation.startsWith('Discontinued') ) {
    return 'This product is discontinued'.translate()
  }
})

'priorWeeksSalesUnits'.calculate(async bulkPOLine => {
  let inventory = await bulkPOLine.toInventory()
  return inventory && inventory.priorWeeksSalesUnits
})

'priorWeeksSalesValue'.calculate(async bulkPOLine => {
  let inventory = await bulkPOLine.toInventory()
  let res = inventory && inventory.priorWeeksSalesValue
  let configuration = 'Configuration'.bringSingleFast()
  if ( ! configuration )
    return res
  if ( configuration.enterPurchasePricesInclusiveOfTax === 'Yes' )
    res = inventory && inventory.priorWeeksSalesValueExclTax
  return res
})

'lastSaleDate'.calculate(async bulkPOLine => {
  let inventory = await bulkPOLine.toInventory()
  return inventory && inventory.lastSaleDate
})

'lastPurchaseDate'.calculate(async bulkPOLine => {
  let inventory = await bulkPOLine.toInventory()
  return inventory && inventory.lastPurchaseDate
})

'lastReceivedDate'.calculate(async bulkPOLine => {
  let inventory = await bulkPOLine.toInventory()
  return inventory && inventory.lastReceivedDate
})

'lastPurchaseDate'.calculate(async bulkPOLine => {
  let inventory = await bulkPOLine.toInventory()
  return inventory && inventory.lastPurchaseDate
})

'priorWeeksSalesUnits'.caption(async maint => {
  let config = await 'Configuration'.bringSingleFast(); if ( ! config ) return null
  let priorWeekCount = config.salesProjectionPriorWeeks
  return "Last".translate() + " " + priorWeekCount + " " + "Weeks Sales Units".translate()
})

'priorWeeksSalesValue'.caption(async maint => {
  let config = await 'Configuration'.bringSingleFast(); if ( ! config ) return null
  let priorWeekCount = config.salesProjectionPriorWeeks
  return "Last".translate() + " " + priorWeekCount + " " + "Weeks Sales Value".translate()
})

'OK'.act(async list => {

  let getDefaultTaxPct = async () => {
    let config = await 'Configuration'.bringSingle(); if ( ! config ) return 0
    return config.taxPct
  }

  let convertToPU = (inv, qty) => {
    let ratio = inv.quantityPerPurchasingUOM; if ( ! ratio ) return qty
    return Math.ceil(qty / ratio)
  }

  let po = list.callerCast(); if ( ! po ) return
  let currency = await po.toCurrency()
  let bulkLines = await 'BulkPOLine'.bring()
  for ( var i = 0; i < bulkLines.length; i++ ) {
    let bulkLine = bulkLines[i]
    if ( bulkLine.bulkAddQuantity <= 0 ) continue
    let inv = await bulkLine.toInventory(); if ( ! inv ) continue
    let poLine = await 'POLine'.create({parentCast: po}, {purchaseOrder: po.reference()})
    poLine.product = inv.product
    poLine.description = inv.productName
    poLine.taxPct = await getDefaultTaxPct()
    let cost = await inv.getDefaultPurchaseUnitCostIncTax()
    if ( poLine.includeTaxOption === 'Yes' ) {
      poLine.unitCostIncTaxFX = await global.localAmountToForeign(cost, currency)
      poLine.unitCostIncTaxFX = global.roundTo2Decimals(poLine.unitCostIncTaxFX)
    } else {
      let costExclTax = cost / ( 1 + (poLine.taxPct / 100) )
      poLine.unitCostExclTaxFX = await global.localAmountToForeign(costExclTax, currency)
      poLine.unitCostExclTaxFX = global.roundTo2Decimals(poLine.unitCostExclTaxFX)
    }
    poLine.quantity = bulkLine.bulkAddQuantity
    poLine.uom = global.copyObj(inv.purchasingUOM)
    poLine.embryoNotes = bulkLine.embryoNotes
    await poLine.refreshCalculations({force: true, includeDefers: true})
    if ( poLine.purchasingUOMDiffers() ) {
      poLine.quantityPU = convertToPU(inv, poLine.quantity)
      await poLine.maybeRefreshQuantity()
    }
    if ( poLine.refreshAutoAllotments )
      await poLine.refreshAutoAllotments()
  }

  list.ok()
})

'BulkAdd'.defaultSort({field: 'uniqueName'})

'BulkAdd'.beforeLoading(async list => {

  let product
  let avenue
  let inventory
  let objs

  let createBulkPOLineForEmbryo = async obj => {
    let line = await 'BulkPOLine'.create(null, {product: product}) 
    line.uniqueName = product.uniqueName
    line.sku = product._sku
    line.supplierSku = avenue.sku
    line.thumbnailImage = inventory.thumbnailImage
    line.quantityOnHand = inventory.quantityOnHand
    line.quantityOnPurchaseOrders = 0
    line.embryoNotes = obj.notes
    line.embryoQuantityOnSalesOrders = obj.quantity
  }

  let createBulkPOLinesForEmbryos = async () => {
    let keys = Object.keys(objs)
    for ( var i = 0; i < keys.length; i++ ) {
      let notes = keys[i]
      let obj = objs[notes]
      obj.notes = notes
      await createBulkPOLineForEmbryo(obj)
    }
  }

  let doEmbryos = async () => {
    let soLine; let soLines = await 'SOLine'.bring({product: product})
    objs = {}
    while ( soLine = soLines.__() ) {
      if ( ! await soLine.isActive() ) continue
      let qty = soLine.quantityRemainingToShip
      if ( ! qty ) continue
      if ( ! soLine.notes ) continue
      let obj = objs[soLine.notes]
      if ( ! obj ) {
        obj = {quantity: 0}
        objs[soLine.notes] = obj
      }
      obj.quantity += qty
    }
    await createBulkPOLinesForEmbryos()
  }

  let maybeDoEmbryos = async () => {
    if ( global.confVal('emb') !== 'Yes' ) return false
    if ( ! supplier.embryoProduct ) return false
    if ( supplier.embryoProduct.id !== product.id ) return false
    await doEmbryos()
    return true
  }

  await 'BulkPOLine'.clear()
  let po = list.callerCast(); if ( ! po ) return
  let supplier = await po.toSupplier(); if ( ! supplier ) return
  let avenues = await 'Avenue'.bring({supplier: supplier})
  for ( var i = 0; i < avenues.length; i++ ) {
    avenue = avenues[i]
    if ( avenue.hideFromPOBulkLines === 'Yes' ) continue
    product = await avenue.toProduct(); if ( ! product ) continue
    inventory = await avenue.toInventory(); if ( ! inventory ) continue
    let didEmbryos = await maybeDoEmbryos()
    if ( didEmbryos )
      continue
    let line = await 'BulkPOLine'.bringFirst({product: product}); if ( line ) continue
    line = await 'BulkPOLine'.create(null, {product: product}) 
    line.avenue = avenue.reference()
    line.inventory = inventory.reference()
    line.uniqueName = product.uniqueName
    line.sku = product._sku
    line.supplierSku = avenue.sku
    line.thumbnailImage = inventory.thumbnailImage
    line.quantityOnHand = inventory.quantityOnHand
    line.quantityOnPurchaseOrders = inventory.quantityOnPurchaseOrders
  }
  list.startAlter({skipFieldCheck: true})
})

'BulkPOLine'.method('toInventory', async function() {
  let product = await this.referee('product'); if ( ! product ) return null
  return await product.toInventory()
})

