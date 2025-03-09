'AllotmentMaint'.maint({panelStyle: "titled"})
'Back'.action({act: 'cancel'})
'OK'.action({act: 'okNoSave'})
'Add another'.action({act: 'addNoSave'})

'Allotment'.datatype()

'Lot Details'.panel()
'allotmentParent'.field({key: true, nonUnique: true, 
  refersToParent: ['POReceiptLine', 'POLine', 'SOLine', 
    'Adjustment', 'Transfer', 'WO', 'WOReceipt', 'WOLine',
    'SOShipmentLine', 'Coproduct'], 
  hidden: true})
'product'.field({refersTo: 'products', readOnly: true, indexed: true})
'lot'.field({refersTo: 'Lot', caption: 'Lot Number', allowAny: true, showAsLink: true, indexed: true})
'quantity'.field({numeric: true, maxDecimals: 6})
'expiryDate'.field({date: true, allowEmpty: true})
'expiryDateRequired'.field({yesOrNo: true, hidden: true})
'supplierLotNumber'.field()
'quantityUpdatedToOnHand'.field({numeric: true, maxDecimals: 6, hidden: true, preserveOnTrash: true})
'quantityUpdatedToTransaction'.field({numeric: true, maxDecimals: 6, hidden: true, preserveOnTrash: true})

/* eslint-disable no-cond-assign */

'Allotment'.method('toPOReceiptLine', async function() {
  let res
  let parent = this.parentFast()
  if( global.fastFail(parent) )
    parent = await this.parent()
  if ( ! parent ) return null
  if ( parent._datatype === 'POReceiptLine' )
    return parent
  if ( ! parent.toPOReceiptLine ) return null
  res = await parent.toPOReceiptLine()
  return res
})

'Allotment'.method('toParent', async function() {
  return await this.parent()
})

'Allotment'.method('maybeUpdateTransactionAllotment', async function(toAllotment) {
  let t, ts = await 'Transaction'.bring({allotment: this})
  while ( t = ts.__() ) {
    t.allotment = toAllotment.reference()
  }
})

'lot'.dropdownValue(async (maint, lot) => {

  let getQuantityPackable = async () => {
    let allotment = maint.mainCast(); if ( ! allotment ) return 0
    let loc = await allotment.toLocation(); if ( ! loc ) return 0
    let clump = await lot.locationToClump(loc); if ( ! clump ) return 0
    return clump.quantityPackable2
  }

  let dateStr = ''
  if ( global.ymdIsSet(lot.expiryDate) )
    dateStr = lot.expiryDate.toLocalDateDisplayText()
  let quantityPackable = await getQuantityPackable()
  lot._tmpQuantityPackable = quantityPackable // used in sortDropdown
  if ( (! quantityPackable) && (! dateStr) )
    return lot.lotNumber
  return lot.lotNumber + ' ' + quantityPackable + ' ' + dateStr
})

'lot'.sortDropdown(async (maint, lots) => {
  lots.sort((a, b) => {

    let key = lot => {
      let inStockKey = lot._tmpQuantityPackable > 0 ? '0' : '1'
      let days = 999990
      if ( global.ymdIsSet(lot.expiryDate) ) {
        if ( lot.expiryDate > global.todayYMD() )
          days = lot.expiryDate.dateSubtract(global.todayYMD())
        else
          days = 999999
      }
      days = global.padWithZeroes(days, 6)
      return inStockKey + days + lot.lotNumber
    }

    let keyA = key(a)
    let keyB = key(b)
    return keyA > keyB ? 1 : -1
  })
})

'Allotment'.method('toLotDescription', async function() {
  let lot = await this.toLot(); if ( ! lot ) return ''
  return await lot.toProductDescription()
})

'lot'.excludeChoiceWhen(async (maint, lot, allotment) => {
  if ( ! lot.product ) return true
  if ( ! allotment.product ) return true
  if ( lot.lotNumber === 'Unspecified' ) return true
  return lot.product.id !== allotment.product.id
})

'expiryDate'.inception(async wo => {
  let parent = await wo.toParent()
  if ( parent && parent.expiryDate && global.ymdIsSet(parent.expiryDate) ) {
    return parent.expiryDate
  }
  return global.emptyYMD()
})

'Allotment'.afterRetrieving(async function() {
  let lot = await this.toLot(); if ( ! lot ) return
  if ( lot.expiryDate )
    this.expiryDate = lot.expiryDate
  this.supplierLotNumber = lot.supplierLotNumber
})

'Allotment'.method('toInventory', async function() {
  let product = await this.toProduct(); if ( ! product ) return null
  return await product.toInventory()
})

'expiryDateRequired'.calculate(async allotment => {
  let inv = await allotment.toInventory(); if ( ! inv ) return 'No'
  return inv.trackExpiryDates
})

'expiryDate'.visibleWhen((maint, allotment) => {
  return allotment.expiryDateRequired === 'Yes'
})

'lot'.readOnlyWhen((maint, allotment) => {
  return ! allotment.isNew()
})

'Lot Details'.dynamicTitle(function() {
  let parent = this.parentCast()
  if ( parent && (parent.lotsAreSerials === 'Yes') )
    return 'Serial Number Details'
  else
    return 'Lot Details'
})

'quantity'.visibleWhen(maint => {
  let parent = maint.parentCast()
  if ( parent && (parent.lotsAreSerials === 'Yes') )
    return false
  return true
})

'lot'.dynamicCaption(maint => {
  let parent = maint.parentCast()
  if ( parent && (parent.lotsAreSerials === 'Yes') )
    return 'Serial Number'
  else
    return 'Lot Number'
})

'Allotment'.cruxFields(['allotmentParent', 'lot'])

'AllotmentMaint'.dynamicTitle(function() {
  let parent = this.parentCast()
  let verb = this.isAdding() ? 'Add'.translate() : 'Edit'.translate()
  if ( parent && (parent.lotsAreSerials === 'Yes') )
    return verb + ' ' + 'Serial Number'.translate()
  else
    return verb + ' ' + 'Lot Quantity'.translate()
})

'Allotment'.method('toClump', async function() {
  let cluster = await this.toCluster(); if ( ! cluster ) return null
  let lot = await this.toLot(); if ( ! lot ) return null
  let res = await 'Clump'.bringSingle({cluster: cluster, lot: lot})
  return res
})

'Allotment'.method('toLotNumber', async function() {
  let lot = await this.toLot(); if ( ! lot ) return null
  return lot.lotNumber
})

'Allotment'.method('toLot', async function() {
  let res = await this.referee('lot')
  return res
})

'Allotment'.method('toProduct', async function() {
  let parent = await this.parent();
  if ( ! parent ) {
    return null
  }
  let res = await parent.referee('product')
  return res
})

'product'.calculate(async allotment => {
  let product = await allotment.toProduct(); if ( ! product ) return null
  return product.reference()
})

'lot'.inception(async allotment => {
  let c = await 'Configuration'.bringFirst(); if ( ! c ) return null
  let product = await allotment.toProduct(); if ( ! product ) return null
  let lot = await c.prodRefToUnspecifiedLot(product.reference())
  return lot.reference()
})

'quantity'.inception(async allotment => {
  let parent = await allotment.parent(); if ( ! parent ) return 0
  let mainQty = await parent.toMainQuantity()
  if ( parent.lotsAreSerials === 'Yes' )
    return global.sign(mainQty) // 1 or -1
  let res = mainQty
  let allotments = await 'Allotment'.bringChildrenOf(parent)
  for ( var i = 0; i < allotments.length; i++ ) {
    let a = allotments[i]
    if ( a.id === allotment.id ) continue
    let lot = await a.referee('lot')
    if ( lot && (lot.lotNumber === 'Unspecified') ) continue
    res -= a.quantity
  }
  return global.roundToXDecimals(res, 6)
})

'Allotment'.beforeValidating(async function() {
  if ( ! this.lot ) return
  let lot = await 'Lot'.bringOrCreate({product: this.product, lotNumber: this.lot.keyval}); 
  if ( ! lot ) return
  this.lot = lot.reference()
  if ( this.expiryDateRequired && global.ymdIsSet(this.expiryDate) )
    lot.expiryDate = this.expiryDate
  if ( this.supplierLotNumber )
    lot.supplierLotNumber = this.supplierLotNumber
})

'Allotment'.validate(async function() {

  let parent = await this.parent(); if ( ! parent ) return

  let checkSerialNotDuplicated = async () => {
    if ( this._markedForDeletion ) return
    if ( parent.lotsAreSerials !== 'Yes' ) return
    if ( ! parent.checkSerialNotDuplicated ) return
    await parent.checkSerialNotDuplicated(this)
  }

  let checkLotNotDuplicatedOnThisParent = async () => {
    if ( this._markedForDeletion ) return
    if ( ! this.isNew() ) return
    let lot = this.lot; if ( ! lot ) return
    let as = await 'Allotment'.bringChildrenOf(parent)
    let thisLotNumber = lot.keyval
    for ( var i = 0; i < as.length; i++ ) {
      let a = as[i]
      if ( a.id === this.id ) continue
      let lotNumber = await a.toLotNumber()
      if ( lotNumber === thisLotNumber ) {
        throw(new Error('Duplicate Lot Number: has already been specified on this document'.translate() + ' (' + lotNumber + ')'))
      }
    }
  }

  await checkSerialNotDuplicated()
  await checkLotNotDuplicatedOnThisParent()
  let lot = await this.toLot()
  let mainQty = await parent.toMainQuantity()
  if ( lot && (lot.lotNumber === 'Unspecified') && (global.sign(this.quantity) !== global.sign(mainQty)) &&
    (parent.allowNegativeUnspecified !== 'Yes') ) {
    let product = await this.toProduct()
    let productName = product ? product.uniqueName : ''
    console.log('Cannot have an Unspecified lot with a negative quantity mainQty = ' + mainQty + "; this.quantity = " + this.quantity)
    console.log(lot)
    console.log(this)
    throw(new Error(productName + ': ' + 'Cannot have an Unspecified lot with a negative quantity'.translate()))
  }
})

'Allotment'.method('refreshParentUnspecifiedLot', async function() {
  let parent = await this.parent(); if ( ! parent ) return
  if ( parent.refreshUnspecifiedLot ) {
    await parent.refreshUnspecifiedLot()
  }
})

'Allotment'.method('isUnspecified', function() {
  return this.lot && (this.lot.keyval === 'Unspecified')
})

'Allotment'.afterTrash(async function() {
  let parent = await this.parent(); if ( ! parent ) return
  if ( parent._markedForDeletion ) return
  if ( ! this.isUnspecified() )
    await this.refreshParentUnspecifiedLot()
})

'Allotment'.beforeSaving(async function() {

  let maybeUpdateMaxSerialSeq = async () => {
    let inv = await this.toInventory(); if ( ! inv ) return
    if ( inv.lotTracking !== 'Serial' ) return
    let lot = await this.toLot(); if ( ! lot ) return
    let suffix = lot.lotNumber.afterLast('-')
    if ( ! global.isNumeric(suffix) ) return
    let seq = parseInt(suffix, 10)
    if ( seq > inv.maxSerialSeq )
      inv.maxSerialSeq = seq
  }

  let cluster = await this.toCluster(); if ( ! cluster ) return
  let clump = await 'Clump'.bringOrCreate({cluster: cluster.reference(), lot: this.lot})
  let oldClump
  if ( this.getOld().lot )
    oldClump = await 'Clump'.bringOrCreate({cluster: cluster.reference(), lot: this.getOld().lot})
  await clump.refreshQuantityOnPurchaseOrders()
  await clump.refreshQuantityBeingManufactured()
  await clump.refreshQuantityOnWOLines()
  if ( oldClump ) {
    await oldClump.refreshQuantityOnPurchaseOrders()
    await oldClump.refreshQuantityBeingManufactured()
    await oldClump.refreshQuantityOnWOLines()
  }
  let parent = await this.parent()
  if ( parent && parent.createTransactionForAllotment )
    await parent.createTransactionForAllotment(this)
  await maybeUpdateMaxSerialSeq()
})

'quantity'.afterUserChange(async (oldInputValue, newInputValue, allotment, maint) => {
  await allotment.setParentToManuallyAllotted()
  await allotment.balanceAllotments()
  await allotment.maybeRefreshParent()
})

'lot'.afterUserChange(async (oldInputValue, newInputValue, allotment, maint) => {
  await allotment.setParentToManuallyAllotted()
  await allotment.balanceAllotments()
  await allotment.maybeRefreshParent()
})

'Allotment'.method('setParentToManuallyAllotted', async function() {
  let parent = await this.parent()
  parent.manuallyAllotted = 'Yes'
})

'Allotment'.method('maybeRefreshParent', async function() {
  let parent = await this.parent()
  if ( parent && parent.refreshOnLotChange )
    await parent.refreshOnLotChange({calledByAllotment: this})
  parent.makeDirty()
})

'Allotment'.method('balanceAllotments', async function() {
  let c = await 'Configuration'.bringFirst(); if ( ! c ) return
  let parent = await this.parent(); if ( ! parent ) return
  await c.balanceAllotments(parent, {calledByAllotment: this})
})

'Allotment'.method('toLocationFast', function() {
  if ( this.__location ) return this.__location
  let res
  let parent = this.parentFast()
  if( global.fastFail(parent) )
    return 'na'
  if ( ! parent ) return null
  if ( ! parent.toLocationFast ) return 'na'
  res = parent.toLocationFast(); if ( global.fastFail(res) ) return 'na'
  this.__location = res
  return res
})

'Allotment'.method('toLocation', async function() {
  if ( this.__location ) return this.__location // performance tweak
  let res
  let parent = this.parentFast()
  if( global.fastFail(parent) )
    parent = await this.parent() 
  if ( ! parent ) return null
  if ( ! parent.toLocation ) return null
  if ( ! global.isFunction(parent.toLocation) ) {
    res = await parent.getLocation()
    this.__location = res
    return res
  }
  res = await parent.toLocation()
  this.__location = res
  return res
})

'Allotment'.method('toCluster', async function() {
  let parent = await this.parent(); if ( ! parent ) return null
  if ( ! parent.toCluster ) return null
  let res = await parent.toCluster()
  return res
})
