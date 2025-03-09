'InventoryMaint'.maint({panelStyle: 'titled', icon: 'BoxOpen'})
'Edit Product'.title()
'Back'.action({act: 'cancel'})
'OK'.action({act: 'ok'})
'Save'.action({act: 'save'})
'Adjust Quantity On Hand'.action({act: 'add', icon: 'none', spec: 'AdjustmentMaint.js'})
'Adjust Value'.action({act: 'add', icon: 'none', spec: 'EvaluationMaint.js'})
'Recalc Avg Cost'.action()
'View History'.action({spec: 'InventoryHistory.js'})
'View Purchase Orders'.action({spec: 'POReport.js'})
'Transfer Between Locations'.action({act: 'add', spec: 'TransferMaint.js', icon: 'none'})
'Inventory by Location'.action({spec: 'ProductLocations.js'})
'View Lots'.action({spec: 'ViewProductLots.js'})
'Labels'.action({spec: 'Labels.js'})
'Attachments'.action({act: 'attachments'})
'Supercede'.action({spec: 'Supercede.js'})
'Inventory'.datatype()

'Product'.panel()
'productName'.field({readOnly: true, caption: 'Product Name'})
'sku'.field()
'bundle'.field({showAsLink: true, readOnly: true})
'notes'.field({multiLine: true})
'quantityOnHand'.field({readOnly: true, caption: 'Quantity On Hand (Available for Sale)'})
'quantityReservedForCustomerOrders'.field({readOnly: true, caption: 'Quantity Sold but not Fulfilled'})
'quantityPickable'.field({readOnly: true, caption: 'Quantity Available to Pick'})
'quantityOnPurchaseOrders'.field({readOnly: true})
'inventoryValue2'.field({readOnly: true})

'Image'.panel()
'image'.field({caption: ''})
//'bundle'.field()

'Pricing'.panel()
'lastPurchaseUnitCostIncTax'.field()
'avgUnitCost'.field({readOnly: true})
'avgUnitCostExclTax'.field({readOnly: true})
'useMarkupPricing'.field()
'baseMarkupOn'.field()
'markupPercentage'.field()
'currentMarkedUpPriceIncTax'.field({readOnly: true})
'currentMarkedUpPriceExclTax'.field({readOnly: true})
'wooCommerceRegularPrice'.field()
'wooCommerceRegularPriceIncTax'.field({readOnly: true})
'wooCommerceRegularPriceExclTax'.field({readOnly: true})
'marginPct'.field({readOnly: true})
'wcSalePrice'.field()
'wcSalePriceIncTax'.field({readOnly: true})
'wcSalePriceExclTax'.field({readOnly: true})
'saleMarginPct'.field({readOnly: true})

'Settings'.panel()
'defaultLocation'.field({refersTo: 'Location', allowEmpty: true})
'shelf'.field()
'situation'.field()
'minQuantity'.field()
'maxQuantity'.field()
'estSalesUnitsPerDay'.field({numeric: true})
'consignment'.field({yesOrNo: true, caption: "Held on Consignment?"})
'avgAlg'.field()

'Traceability'.panel()
'lotTracking'.field({caption: 'Serial/Lot Tracking'})
'trackExpiryDates'.field()
'defaultExpiryDays'.field()
'quantityPerLot'.field()

'Units of Measure'.panel()
'stockingUOM'.field({caption: 'Stocking and Sales UOM'})
'useDifferentUOMForPurchasing'.field()
'purchasingUOM'.field()
'quantityPerPurchasingUOM'.field()

'Intrastat'.panel()
'intrastatHSCode'.field({caption: 'HS Code'})

'Impost Classifications'.panel()
'impostsMsg'.field({readOnly: true, caption: ''})

'General Ledger'.panel()
'treatAsExpenseInGL'.field()
'stockOnHandAccount'.field()

'wcSalePriceIncTax'.visibleWhen((maint, inventory) => {
  return inventory.wcSalePrice > 0
})

'wcSalePriceExclTax'.visibleWhen((maint, inventory) => {
  return inventory.wcSalePrice > 0
})

'saleMarginPct'.visibleWhen((maint, inventory) => {
  return inventory.wcSalePrice > 0
})

'wcSalePrice'.modifyInputRenderValue((renderValue, inv) => {
  if ( renderValue !== '0.00' ) return renderValue
  return ''
})

'stockOnHandAccount'.visibleWhen((maint, inventory) => (inventory.treatAsExpenseInGL !== 'Yes'))

'General Ledger'.visibleWhen((maint, inventory) => (global.confVal('glEnabled') === 'Yes'))

'useMarkupPricing'.visibleWhen((maint, inventory) => (global.confVal('ump') === 'Yes'))

'currentMarkedUpPriceIncTax'.visibleWhen((maint, inventory) => (inventory.useMarkupPricing === 'Yes'))

'currentMarkedUpPriceExclTax'.visibleWhen((maint, inventory) => (inventory.useMarkupPricing === 'Yes'))

'markupPercentage'.visibleWhen((maint, inventory) => (inventory.useMarkupPricing === 'Yes'))

'baseMarkupOn'.visibleWhen((maint, inventory) => (inventory.useMarkupPricing === 'Yes'))

'impostsMsg'.calculate(inventory => {
  let slow = async () => {
    let imposts = await 'Impost'.bring()
    return imposts.length > 0 ? null : "There are no Imposts configured"
  }

  let imposts = 'Impost'.bringFast()
  if ( global.fastFail(imposts) )
    return slow()
  return imposts.length > 0 ? null : "There are no Imposts configured"
  
})

'impostsMsg'.visibleWhen((maint, inventory) => inventory.impostsMsg)

'InventoryMaint'.modifyFields(async (maint, fields) => {

  let imposts = await 'Impost'.bring()

  let getClassificationStrings = async classifier => {
    let classifications = await 'Classification'.bring({classifier: classifier})
    let res = ['']
    for ( var i = 0; i < classifications.length; i++ ) {
      let classification = classifications[i]
      res.push(classification.classificationValue)
    }
    return res
  }

  let classifierToField = async (classifier, impost) => {
    let name = classifier.productMetaFieldName
    let res
    res = fields.filterSingle(f => f.name === name)
    if ( res )
      return res
    res = maint.createField(
      {
        datatype: 'Inventory',
        name: name,
        panel: 'Impost Classifications'
      })
    res.caption = impost.description + ' ' + classifier.classifierDescription
    res.englishCaption = res.caption
    res.staticOptions = await getClassificationStrings(classifier)
    return res
  }

  let processImpost = async impost => {
    let classifiers = await 'Classifier'.bring({impost: impost})
    for ( var i = 0; i < classifiers.length; i++ ) {
      let classifier = classifiers[i]
      let f = await classifierToField(classifier, impost); if ( ! f ) continue
      newFields.push(f)
    }
  }

  let newFields = []
  for ( var i = 0; i < imposts.length; i++ ) {
    let impost = imposts[i]
    await processImpost(impost)
  }
  fields.appendArray(newFields)
})

'bundle'.visibleWhen((maint, inventory) => {
  return inventory.bundle ? true : false
})

'defaultLocation'.inception(async inventory => {
  let location = await 'Location'.bringOrCreate({locationName: 'General'})
  return location.reference()
})

'Recalc Avg Cost'.act(async (maint, inventory) => {
  await inventory.reretrieve()
  inventory.avgAlg = 'Dynamic Refresh'
  await inventory.refreshAvgUnitCost()
  let mold = global.foreman.doNameToMold('Inventory')
  mold.version++
})

'useDifferentUOMForPurchasing'.afterUserChange(async (oldInputValue, newInputValue, inv, maint) => {

  let getStockingUOMRef = async () => {
    let c = await 'Configuration'.bringFirst(); if ( ! c ) return null
    return c.defaultStockingUOM
  }

  if ( oldInputValue === newInputValue ) return
  if ( newInputValue === 'Yes' ) {
    inv.purchasingUOM = await getStockingUOMRef()
  } else {
    inv.purchasingUOM = null
  }
})

'purchasingUOM'.visibleWhen((maint, inventory) => {
  return inventory.useDifferentUOMForPurchasing === 'Yes'
})

'quantityPerPurchasingUOM'.visibleWhen((maint, inventory) => {
  if ( inventory.useDifferentUOMForPurchasing !== 'Yes' ) return false
  let c = 'Configuration'.bringSingleFast(); if ( ! c ) return false
  let stockingUOMRef = c ? c.defaultStockingUOM : null
  let stockingUOMName = stockingUOMRef ? stockingUOMRef.keyval : null
  let purchasingUOMName = inventory.purchasingUOM ? inventory.purchasingUOM.keyval : null
  if ( stockingUOMName === purchasingUOMName )
    return false
  return true
})

'View Lots'.dynamicCaption(maint => {
  let inv = maint.mainCast(); if ( ! inv ) return 'View Lots'
  if ( inv.lotTracking === 'Serial' )
    return 'View Serial Numbers'
})

'View Lots'.availableWhen(inv => {
  return inv && inv.lotTracking && (inv.lotTracking !== 'None')
})

'Suppliers'.manifest()
'Add Supplier'.action({act: 'add'})
'Avenue'.datatype()
'supplier'.field({showAsLink: true})
'productName'.field()
'sku'.field()
'isMain'.field({yesOrNo: true, caption: "Main"})
'Edit'.action({place: 'row', act: 'edit'})
'Trash'.action({place: 'row', act: 'trash'})
'AvenueMaint.js'.maintSpecname()

'trackExpiryDates'.visibleWhen((maint, inv) => {
  return (inv.lotTracking && (inv.lotTracking !== 'None'))
})

'defaultExpiryDays'.visibleWhen((maint, inv) => {
  return (inv.lotTracking && (inv.lotTracking !== 'None') && (inv.trackExpiryDates === 'Yes'))
})

'quantityPerLot'.visibleWhen((maint, inv) => {
  return (inv.lotTracking === 'Lot')
})

'lotTracking'.options(['None', 'Lot', 'Serial'])

'InventoryMaint'.makeDestinationFor('products')

'InventoryMaint'.makeDestinationFor('Inventory')

'InventoryMaint'.substituteCast(async (cast) => {
  let res = cast; if ( ! res ) return
  if ( res.datatype() === 'products' ) {
    let prodRef = res.reference()
    res = await 'Inventory'.bringOrCreate({product: prodRef})
  }
  return res
})


