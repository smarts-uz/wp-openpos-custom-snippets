'PalletMaint'.maint({panelStyle: "titled", icon: 'Boxes'})
'Add Pallet'.title({when: 'adding'})
'Edit Pallet'.title({when: 'editing'})
'Back'.action({act: 'cancel'})
'OK'.action({act: 'ok'})
'Save'.action({act: 'save'})
'Add another pallet'.action({act: 'add'})
'Labels'.action({spec: 'Labels.js'})
'Pallet'.datatype()

'Pallet Details'.panel()
'shipment'.field({refersToParent: 'SOShipment', showAsLink: true, caption: 'Shipment Number'})
'palletSerialNumber'.field({key: true, nonUnique: true, allowEmpty: false})
'chep'.field({yesOrNo: true, caption: 'CHEP?'})
'lineCount'.field({numeric: true, readOnly: true})
'totalQuantity'.field({numeric: true, readOnly: true})
'lotNumber'.field({hidden: true, multi: 10})
'lotQuantity'.field({hidden: true, multi: 10, numeric: true})
'lotDescription'.field({hidden: true, multi: 10})

'Lines'.manifest()
'Add Lines'.action({noSave: true})
'PalletLine'.datatype()
'pallet'.field({refersToParent: 'Pallet', hidden: true})
'shipmentLine'.field({refersTo: 'SOShipmentLine', hidden: true, indexed: true})
'allotment'.field({refersTo: 'Allotment', hidden: true, indexed: true})
'product'.field({refersTo: 'products', showAsLink: true})
'lot'.field({refersTo: 'Lot', showAsLink: true, caption: 'Serial/Lot'})
'quantity'.field({numeric: true})
'Trash'.action({place: 'row', act: 'trash'})

'lotNumber'.calculate(async (pallet, multiNo) => {
  let a = await pallet.indexToAllotment(multiNo - 1); if ( ! a ) return ''
  return await a.toLotNumber()
})

'lotQuantity'.calculate(async (pallet, multiNo) => {
  let a = await pallet.indexToAllotment(multiNo - 1); if ( ! a ) return 0
  return await a.quantity
})

'lotDescription'.calculate(async (pallet, multiNo) => {
  let a = await pallet.indexToAllotment(multiNo - 1); if ( ! a ) return ''
  return await a.toLotDescription()
})

'Pallet'.cruxFields(['shipment', 'palletSerialNumber'])

'PalletLine'.cruxFields(['pallet', 'shipmentLine', 'allotment'])

'totalQuantity'.calculate(async pallet => {
  let lines = await pallet.toLines()
  let res = 0
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    res += line.quantity
  }
  return res
})

'lineCount'.calculate(async pallet => {
  let lines = await pallet.toLines()
  return lines.length
})

'Pallet'.method('indexToAllotment', async function(index) {
  let as = await this.toAllotments()
  if ( as.length <= index ) return null
  return as[index]
})

'Pallet'.method('toAllotments', async function() {
  let res = []
  let lines = await this.toLines()
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    if ( ! line.allotment ) continue
    let allotment = await line.referee('allotment')
    res.push(allotment)
  }
  return res
})

'Pallet'.method('toShipment', async function() {
  return await this.referee('shipment')
})

'Pallet'.method('toLines', async function() {
  let lines = await 'PalletLine'.bringChildrenOf(this)
  return lines
})

'Add Lines'.act(async (maint, shipment) => {
  await maint.segue('view', 'PalletBulkAdd.js', shipment)
})

'product'.calculate(async line => {
  let sl = await line.referee('shipmentLine'); if ( ! sl ) return null
  return global.copyObj(sl.product)
})

'lot'.calculate(async line => {
  let a = await line.referee('allotment'); if ( ! a ) return null
  return global.copyObj(a.lot)
})

