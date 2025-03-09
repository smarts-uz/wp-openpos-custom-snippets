'ComponentLotsByFGLot'.list({withHeader: true})
'Component Lots By FG Lot'.title()
'Back'.action({act: 'cancel'})
'Download to Excel'.action({act: 'excel'})
'fgProduct'.field({refersTo: 'products', caption: 'FG Product'})
'fgLot'.field({refersTo: 'Lot', caption: 'FG Lot'})

'Lines'.manifest()
'Go'.action({place: 'header', spinner: true})
'CFGResult'.datatype({transient: true})
'componentLot'.field({refersTo: 'Lot', showAsLink: true})
'componentProduct'.field({refersTo: 'products', showAsLink: true})
'quantity'.field({numeric: true})
'woReceipt'.field({refersTo: 'WOReceipt', showAsLink: true, caption: 'WO Receipt'})
'poReceipt'.field({refersTo: 'POReceipt', showAsLink: true, caption: 'PO Receipt'})
'receivedDate'.field({date: true, caption: 'Received Date'})
'supplier'.field({refersTo: 'Supplier', showAsLink: true})
'supplierLotNumber'.field()
'parentLot'.field({refersTo: 'Lot', showAsLink: true})
'parentProduct'.field({refersTo: 'products', showAsLink: true})

/* eslint-disable no-cond-assign */

'fgLot'.excludeChoiceWhen(async (list, lot) => {
  if ( ! lot.product ) return true
  let productRef = list.getFieldValue('fgProduct')
  if ( ! productRef ) return true
  return lot.product.id !== productRef.id
})

'fgProduct'.afterUserChange(async (oldInputValue, newInputValue, headerCast, list) => {
  list.setFieldValue('fgLot', null)
})

'componentLot'.destination(async cr => {
  return 'SOsByComponentLot.js'
})

'ComponentLotsByFGLot'.beforeLoading(async list => {
  let cast = list.callerCast()
  if ( cast && cast.lot ) {
    let lot = await cast.referee('lot')
    if ( lot ) {
      list.setFieldValue('fgProduct', lot.product)
      list.setFieldValue('fgLot', lot.reference())
    }
  }
})

'ComponentLotsByFGLot'.afterLoading(async list => {
  await list.doAction('Go')
})

'Go'.act(async list => {

/*
  let addPOReceiptLineAllotment = async (a, line) => {
    let componentLot = await a.referee('lot'); if ( ! componentLot ) return
    if ( componentLot.lotNumber === 'Unspecified' ) return
    let componentProduct = await componentLot.toProduct()
    let poReceipt = await componentLot.toPOReceipt()
    let supplier = poReceipt ? await poReceipt.toSupplier() : null
    let r = await 'CFGResult'.create()
    r.componentLot = componentLot.reference()
    r.componentProduct = componentProduct ? componentProduct.reference() : null
    r.quantity = a.quantity
    r.supplier = supplier ? supplier.reference() : null
    r.supplierLotNumber = componentLot.supplierLotNumber
    r.poReceipt = poReceipt ? poReceipt.reference() : null
  }
*/

  let addAllotment = async (a, parentLot, parent) => {
    let componentLot = await a.referee('lot'); if ( ! componentLot ) return
    if ( componentLot.lotNumber === 'Unspecified' ) return
    let woReceipt, poReceiptLine
    if ( parent.datatype() === 'WOReceipt' )
      woReceipt = parent
    else if ( parent.datatype() === 'WOLine' )
      woReceipt = await parent.toWOReceipt()
    else if ( parent.datatype() === 'POReceiptLine' )
      poReceiptLine = parent
    let componentProduct = await componentLot.toProduct()
    let poReceipt = poReceiptLine ? await poReceiptLine.toPOReceipt() : null
    let supplier = poReceipt ? await poReceipt.toSupplier() : null
    let parentProduct = parentLot ? await parentLot.toProduct() : null
    let r = await 'CFGResult'.create()
    r.componentLot = componentLot.reference()
    r.componentProduct = componentProduct ? componentProduct.reference() : null
    r.woReceipt = woReceipt ? woReceipt.reference() : null
    r.receivedDate = woReceipt ? woReceipt.receivedDate : (poReceipt ? poReceipt.receivedDate : null)
    r.quantity = a.quantity
    if ( parent.datatype() === 'WOLine' )
      r.quantity = - r.quantity
    r.supplier = supplier ? supplier.reference() : null
    r.supplierLotNumber = componentLot.supplierLotNumber
    r.poReceipt = poReceipt ? poReceipt.reference() : null
    if ( parent.datatype() === 'WOLine' ) {
      r.parentLot = parentLot ? parentLot.reference() : null
      r.parentProduct = parentProduct ? parentProduct.reference() : null
      await addLot(componentLot) // Recurse down the tree
    }
  }

  let addWOLine = async (woLine, parentLot, woReceipt) => {
    let a; let as = await woLine.toAllotments()
    while ( a = as.__() ) {
      await addAllotment(a, parentLot, woLine)
    }
  }

  let addWOReceipt = async (woReceipt, parentLot) => {
    let line, lines = await woReceipt.toWOLines()
    while ( line = lines.__() ) {
      await addWOLine(line, parentLot, woReceipt)
    }
  }

  let addLot = async lot => {
    let a; let as = await 'Allotment'.bring({lot: lot})
    for ( var i = 0; i < as.length; i++ ) {
      a = as[i]
      let parent = await a.parent(); if ( ! parent ) continue
      if ( parent.datatype() === 'WOReceipt' ) {
        await addAllotment(a, lot, parent)
        await addWOReceipt(parent, lot)
      } else if ( parent.datatype() === 'POReceiptLine' ) {
        await addAllotment(a, null, parent)
      }
    }
  }

  await 'CFGResult'.clear()
  //let fgLot = await list.referee('fgLot'); if ( ! fgLot ) return
  let product = await list.referee('fgProduct'); if ( ! product ) return
  let selectedLotRef = list.getFieldValue('fgLot'); if ( ! selectedLotRef ) return
  let selectedLotNumber = selectedLotRef.keyval; if ( ! selectedLotNumber ) return
  let fgLot = await product.lotNumberToLot(selectedLotNumber); if ( ! fgLot ) return
  await addLot(fgLot)
})
