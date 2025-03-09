'UseExistingProduct'.maint({panelStyle: "titled"})
'Link Unlisted Product to Existing Product'.title()
'Back'.action({act: 'cancel'})
'OK'.action({icon: 'CheckDouble'})
'Embryo'.datatype({transient: true})

'Order Details'.panel()
'supplier'.field({refersTo: 'Supplier', showAsLink: true, readOnly: true})
'embryoNotes'.field({caption: 'Notes', readOnly: true, indexed: true})
'quantityOnSalesOrders'.field({readOnly: true})
'quantityOnPurchaseOrders'.field({readOnly: true})

'Existing Product Details'.panel()
'existingProduct'.field({caption: 'Link to Product', refersTo: 'products'})

/* eslint-disable no-cond-assign */

'OK'.act(async (maint, embryo) => {

  let product
  let inv

  let copySOLine = async fromLine => {
    let so = await fromLine.toSO()
    so.manageInProfitori = 'Yes'
    //so.suppressEmail = 'Yes'
    so.linesVersion++
    let newLine = await 'SOLine'.create({parentCast: so}, {salesOrder: so.reference()})
    for ( var prop in fromLine ) {
      if ( fromLine.propIsSystemProp(prop) ) continue
      if ( ! Object.prototype.hasOwnProperty.call(fromLine, prop) ) continue
      if ( (prop === 'id') || (prop === 'salesOrder') || (prop === 'order_item_id') || (prop === 'product') ) continue
      fromLine.copyProp(prop, fromLine, newLine)
    }
    return newLine
  }

  let convertSOLines = async () => {
    let supplier = await embryo.referee('supplier'); if ( ! supplier ) return
    let soLine; let soLines = await 'SOLine'.bring({product: supplier.embryoProduct})
    while ( soLine = soLines.__() ) {
      if ( soLine.notes !== embryo.embryoNotes ) continue
      if ( ! soLine.isActive() ) continue
      if ( soLine.quantityShipped !== 0 ) continue
      let newLine = await copySOLine(soLine)
      newLine.product = product.reference()
      newLine.description = embryo.productName
      newLine.refreshIndexes()
      await soLine.trash()
    }
  }

  let copyPOLine = async fromLine => {
    let po = await fromLine.toPO()
    let newLine = await 'POLine'.create({parentCast: po}, {purchaseOrder: po.reference()})
    for ( var prop in fromLine ) {
      if ( fromLine.propIsSystemProp(prop) ) continue
      if ( ! Object.prototype.hasOwnProperty.call(fromLine, prop) ) continue
      if ( (prop === 'id') || (prop === 'purchaseOrder') || (prop === 'product') ) continue
      fromLine.copyProp(prop, fromLine, newLine)
    }
    return newLine
  }

  let convertPOLines = async () => {
    let supplier = await embryo.referee('supplier'); if ( ! supplier ) return
    let poLine; let poLines = await 'POLine'.bring({product: supplier.embryoProduct})
    while ( poLine = poLines.__() ) {
      if ( poLine.embryoNotes !== embryo.embryoNotes ) continue
      if ( poLine.receivedQuantity !== 0 ) continue
      if ( ! poLine.outstandingQuantity ) continue
      let newLine = await copyPOLine(poLine)
      newLine.product = product.reference()
      newLine.description = inv.productName
      newLine.refreshIndexes()
      await poLine.trash()
    }
  }

  let validate = async () => {
    if ( ! embryo.existingProduct ) 
      throw(new Error('Link to Product is required'.translate()))
  }

  try {
    await validate()
    await global.app.save()
    product = await embryo.referee('existingProduct'); if ( ! product ) return
    inv = await product.toInventory({allowCreate: true})
    await convertSOLines()
    await convertPOLines()
    await global.app.save()
    maint.showMessage("Customer and supplier order lines have been converted to the specified product")
    maint.back()
  } catch(e) {
    maint.showMessage(e.message)
  }
})

