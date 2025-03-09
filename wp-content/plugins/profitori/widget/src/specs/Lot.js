'Lot'.datatype({exportable: true})
'product'.field({refersTo: 'products', indexed: true})
'lotNumber'.field({key: true, nonUnique: true, indexed: true})
'expiryDate'.field({date: true, allowEmpty: true})
'supplierLotNumber'.field()
//'quantityPackable2'.field({numeric: true})
//'circumstances'.field()
/*
'receipt'.field({refersTo: ['WOReceipt', 'POReceiptLine']})
'createdDate'.field({date: true})
'supplier'.field({refersTo: 'Supplier'})
'supplierName'.field()
*/

/* eslint-disable no-cond-assign */

'Lot'.cruxFields(['product', 'lotNumber'])

/*
'receipt'.calculate(async lot => {
  let allotment; let allotments = await 'Allotment'.bring({lot: lot})
  while ( allotment = allotments.__() ) {
    let parent = await allotment.parent(); if ( ! parent ) continue
    if ( (parent.datatype() === 'WOReceipt') || (parent.datatype() === 'POReceiptLine') )
      return parent.reference()
  }
})

'createdDate'.calculate(async lot => {
  let receipt = await lot.referee('receipt'); if ( ! receipt ) return null
  return receipt.receivedDate
})

'supplier'.calculate(async lot => {
  let receipt = await lot.referee('receipt'); if ( ! receipt ) return null
  if ( receipt.datatype() !== 'POReceiptLine' ) return null
  return receipt.supplier
})

'supplierName'.calculate(async lot => {
  let supplier = await lot.referee('supplier'); if ( ! supplier ) return null
  return supplier.name
})
*/

'Lot'.method('toLotsUsedIn', async function(opt) {
  let res = []

  let addParentLots = async lot => {
    let lots = await lot.toLotsUsedIn(opt)
    res.appendArray(lots)
  }

  let addAllotments = async as => {
    let a
    while ( a = as.__() ) {
      let lot = await a.toLot(); if ( ! lot ) continue
      res.push(lot)
      if ( opt && opt.recursive )
        await addParentLots(lot)
    }
  }

  let a, as = await 'Allotment'.bring({lot: this})
  while ( a = as.__() ) {
    let parent = await a.toParent(); if ( ! parent ) continue
    if ( parent.datatype() !== 'WOLine' ) continue
    let woLine = parent
    let woReceipt = await woLine.toWOReceipt(); if ( ! woReceipt ) continue
    let was = await woReceipt.toAllotments()
    await addAllotments(was)
  }
  return res
}) 

'Lot'.method('toPOReceipt', async function() {
  let a, as = await this.toAllotments()
  while ( a = as.__() ) {
    let rl = await a.toParent(); if ( ! rl ) continue
    if ( rl.datatype() !== 'POReceiptLine' ) continue
    return rl.toPOReceipt()
  }
  return null
})

'Lot'.method('toAllotments', async function() {
  return await 'Allotment'.bring({lot: this})
})

'Lot'.method('toLotNumber', async function() {
  return this.lotNumber
})

'Lot'.method('locationToClump', async function(loc) {
  return await 'Clump'.bringFirst({lot: this, location: loc}, {useIndexedField: 'lot'})
})

'Lot'.method('toClumps', async function() {
  return await 'Clump'.bring({lot: this})
})

'Lot'.method('toQuantityOnHand', async function() {
  let cs = await this.toClumps()
  let res = 0
  for ( var i = 0; i < cs.length; i++ ) {
    let c = cs[i]
    res += c.quantityOnHand
  }
  return res
})

'Lot'.method('toProduct', async function() {
  return await this.referee('product')
})

'Lot'.method('toInventory', async function() {
  let p = await this.toProduct(); if ( ! p ) return null
  return await p.toInventory()
})

'Lot'.method('toProductDescription', async function() {
  let product = await this.toProduct(); if ( ! product ) return null
  return product.uniqueName
})

'Lot'.beforeSaving(async function() {
  let product = await this.referee('product'); if ( ! product ) return
  await product.refreshLotsString()
})

'expiryDate'.default(global.emptyYMD())
