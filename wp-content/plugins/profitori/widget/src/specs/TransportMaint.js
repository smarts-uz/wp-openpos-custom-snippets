'TransportMaint'.maint({panelStyle: "titled", icon: "Boxes"})
'SOShipment'.datatype()
'Shipment Transport'.title()
'Back'.action({act: 'cancel'})
'OK'.action({act: 'ok'})
'Save'.action({act: 'save'})
'Bill of Lading PDF'.action()
'Settings'.action({spec: 'TransportConfigMaint.js'})

'Shipment Details'.panel()
'shipmentNumber'.field({readOnly: true, showAsLink: true})
'order'.field({readOnly: true, showAsLink: true})
'shipmentDate'.field({readOnly: true})

''.panel()

'Transport Details'.panel()
'bolSerialNumber'.field({caption: 'BOL Serial Number'})
'terms'.field()
'loadedAt'.field()
'loadDate'.field({date: true})
'loadTime'.field()
'outDate'.field({date: true})
'outTime'.field()
'palletCount'.field({numeric: true, readOnly: true})
'chepPalletCount'.field({caption: 'CHEP Pallet Count', numeric: true, readOnly: true})

'Carrier'.panel()
'deliverBy'.field()
'driver'.field({refersTo: 'Driver', allowAny: true})
'driverLicense'.field()
'truckLicensePlate'.field({refersTo: 'Truck', allowAny: true})
'trailerLicensePlate'.field({refersTo: 'Trailer', allowAny: true})
'carrier'.field({refersTo: 'Carrier', allowAny: true})

'ourCompanyName'.field({hidden: true})
'ourAddress1'.field({hidden: true})
'ourAddress2'.field({hidden: true})
'ourAddress3'.field({hidden: true})
'ourCity'.field({hidden: true})
'ourState'.field({hidden: true})
'ourPostalCode'.field({hidden: true})
'ourContactName'.field({hidden: true})
'ourContactEmail'.field({hidden: true})
'ourPhone'.field({hidden: true})
'ourPhoneWithCaption'.field({hidden: true})
'ourFax'.field({hidden: true})
'ourFaxWithCaption'.field({hidden: true})
'billOfLadingMessage'.field({hidden: true})
'webSite'.field({hidden: true})
'pdfLogoUrl'.field({hidden: true})
'bolSerialNumberWithCaption'.field({hidden: true})
'loadedAtWithCaption'.field({hidden: true})
'loadDateTime'.field({hidden: true})
'outDateTime'.field({hidden: true})
'palletChargeMessage'.field({hidden: true})

'Pallets'.manifest()
'Add Pallet'.action({act: 'add'})
'Pallet'.datatype()
'palletSerialNumber'.field()
'lineCount'.field()
'totalQuantity'.field()
'Edit'.action({place: 'row', act: 'edit'})
'Trash'.action({place: 'row', act: 'trash'})
'PalletMaint.js'.maintSpecname()

'ourCompanyName'.calculate(async s => { return await s.confVal2('companyName') })
'ourAddress1'.calculate(async s => { return await s.confVal2('address1') })
'ourAddress2'.calculate(async s => { return await s.confVal2('address2') })
'ourCity'.calculate(async s => { return await s.confVal2('city') })
'ourState'.calculate(async s => { return await s.confVal2('state') })
'ourPostalCode'.calculate(async s => { return await s.confVal2('postalCode') })
'ourContactName'.calculate(async s => { return await s.confVal2('contactName') })
'ourContactEmail'.calculate(async s => { return await s.confVal2('contactEmail') })
'ourPhone'.calculate(async s => { return await s.confVal2('phone') })
'ourFax'.calculate(async s => { return await s.confVal2('fax') })
'pdfLogoUrl'.calculate(async s => { return await s.confVal2('pdfLogoUrl') })
'webSite'.calculate(async s => { return await s.confVal2('webSite') })
'billOfLadingMessage'.calculate(async s => { return await s.confVal2('billOfLadingMessage') })
'palletChargeMessage'.calculate(async s => { return await s.confVal2('palletChargeMessage') })

'palletCount'.calculate(async s => { 
  let res = 0
  let pallets = await s.toPallets()
  for ( var i = 0; i < pallets.length; i++ ) {
    let pallet = pallets[i]
    if ( pallet.chep !== 'Yes' )
      res++
  }
  return res
})

'chepPalletCount'.calculate(async s => { 
  let res = 0
  let pallets = await s.toPallets()
  for ( var i = 0; i < pallets.length; i++ ) {
    let pallet = pallets[i]
    if ( pallet.chep === 'Yes' )
      res++
  }
  return res
})

'outDateTime'.calculate(s => { 
  if ( ! s.outDate ) return null
  return s.outDate.toLocalDateDisplayText() + ' ' + s.outTime
})

'loadDateTime'.calculate(s => { 
  if ( ! s.loadDate ) return null
  return s.loadDate.toLocalDateDisplayText() + ' ' + s.loadTime
})

'ourAddress3'.calculate(s => { 
  return s.ourCity + ' ' + s.ourState + ' ' + s.ourPostalCode
})

'ourPhoneWithCaption'.calculate(s => { 
  return 'Phone'.translate() + ': ' + s.ourPhone
})

'loadedAtWithCaption'.calculate(s => { 
  return 'Loaded at'.translate() + ': ' + s.loadedAt
})

'bolSerialNumberWithCaption'.calculate(s => { 
  return 'SN: ' + s.bolSerialNumber
})

'ourFaxWithCaption'.calculate(s => { 
  return 'Fax'.translate() + ': ' + s.ourFax
})

'Bill of Lading PDF'.act(async (maint, shipment) => {
  maint.downloadPDF({spec: "BOLPdf.js", docName: 'Bill of Lading ' + shipment.shipmentNumber + ".PDF"})
})

'SOShipment'.beforeInception(async function() {
  await 'TransportConfig'.bringOrCreate()
})

'SOShipment'.method('confVal2', async function(fieldName) {
  let tc = await 'TransportConfig'.bringSingle(); if ( ! tc ) return null;
  return tc[fieldName]
})

'SOShipment'.method('confVal', function(fieldName) {
  let tc = 'TransportConfig'.bringSingleFast(); if ( (! tc) || (tc === 'na') ) return null;
  return tc[fieldName]
})

'terms'.options(['FOB', 'Delivered'])

'loadedAt'.inception(s => s.confVal('defaultLoadedAt'))

'deliverBy'.inception(s => s.confVal('defaultDeliverBy'))

'carrier'.inception(s => s.confVal('defaultCarrier'))

'driver'.inception(s => s.confVal('defaultDriver'))

'driverLicense'.inception(s => s.confVal('defaultDriverLicense'))

'terms'.inception(s => s.confVal('defaultTerms'))

'truckLicensePlate'.inception(s => s.confVal('defaultTruckLicensePlate'))

'trailerLicensePlate'.inception(s => s.confVal('defaultTrailerLicensePlate'))

'driver'.afterUserChange(async (oldInputValue, newInputValue, shipment, maint) => {
  if ( ! shipment ) return
  let driver = await shipment.referee('driver'); if ( ! driver ) return
  shipment.driverLicense = driver.driverLicense
  if ( driver.defaultTruck )
    shipment.truckLicensePlate = driver.defaultTruck
  await shipment.refreshCarrier()
  await shipment.refreshTrailer()
})

'truckLicensePlate'.afterUserChange(async (oldInputValue, newInputValue, shipment, maint) => {
  await shipment.refreshCarrier()
  await shipment.refreshTrailer()
})

'SOShipment'.method('refreshTrailer', async function() {
  let truck = await this.referee('truckLicensePlate'); if ( ! truck ) return
  this.trailerLicensePlate = truck.defaultTrailerLicensePlate
})

'SOShipment'.method('refreshCarrier', async function() {
  let truck = await this.referee('truckLicensePlate'); if ( ! truck ) return
  this.carrier = truck.carrier
})
