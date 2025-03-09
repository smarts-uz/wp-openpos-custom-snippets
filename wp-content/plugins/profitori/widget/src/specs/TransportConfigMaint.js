'TransportConfigMaint'.maint({panelStyle: "titled", icon: 'Cog'})
'Transport Settings'.title()
'Back'.action({act: 'cancel'})
'OK'.action({act: 'ok'})
'Carriers'.action({spec: 'CarrierList.js'})
'Trucks'.action({spec: 'TruckList.js'})
'Drivers'.action({spec: 'DriverList.js'})
'Trailers'.action({spec: 'TrailerList.js'})
'TransportConfig'.datatype({singleton: true, plex: true})

'Ship From Address'.panel()
'companyName'.field()
'address1'.field()
'address2'.field()
'city'.field({caption: 'City/Suburb/Town'})
'state'.field({caption: 'State/Province'})
'postalCode'.field({caption: 'Postal/Zip Code'})

'Our Contact Details'.panel()
'contactName'.field()
'contactEmail'.field()
'phone'.field()
'fax'.field()

'BOL Defaults'.panel()
'defaultLoadedAt'.field()
'defaultTerms'.field()
'defaultDeliverBy'.field()
'defaultDriver'.field({refersTo: 'Driver', allowAny: true})
'defaultDriverLicense'.field()
'defaultTruckLicensePlate'.field({refersTo: 'Truck', allowAny: true})
'defaultTrailerLicensePlate'.field({refersTo: 'Trailer', allowAny: true})
'defaultCarrier'.field({refersTo: 'Carrier', allowAny: true})

'BOL Extras'.panel()
'pdfLogoUrl'.field({caption: 'BOL PDF Logo URL'})
'webSite'.field()
'billOfLadingMessage'.field({caption: 'Bill of Lading Message'})
'palletChargeMessage'.field()


'defaultTerms'.options(['FOB', 'Delivered'])

'defaultDriver'.afterUserChange(async (oldInputValue, newInputValue, tc, maint) => {
  if ( ! tc ) return
  let driver = await tc.referee('defaultDriver'); if ( ! driver ) return
  tc.defaultDriverLicense = driver.driverLicense
  if ( driver.defaultTruck )
    tc.defaultTruckLicensePlate = driver.defaultTruck
  await tc.refreshCarrier()
  await tc.refreshTrailer()
})

'defaultTruckLicensePlate'.afterUserChange(async (oldInputValue, newInputValue, tc, maint) => {
  await tc.refreshCarrier()
  await tc.refreshTrailer()
})

'TransportConfig'.method('refreshTrailer', async function() {
  let tc = this
  let truck = await tc.referee('defaultTruckLicensePlate'); if ( ! truck ) return
  tc.defaultTrailerLicensePlate = truck.defaultTrailerLicensePlate
})

'TransportConfig'.method('refreshCarrier', async function() {
  let tc = this
  let truck = await tc.referee('defaultTruckLicensePlate'); if ( ! truck ) return
  tc.defaultCarrier = truck.carrier
})

