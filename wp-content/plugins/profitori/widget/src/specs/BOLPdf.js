'BOLPdf'.output({rowsPerPage: 25, topMargin: 20, leftMargin: 12, rightMargin: 12, pageHeadingBottomMargin: 2, version: 2})
'shipment'.datatype()

'Banner1'.section({pageHeading: true, height: 10})
'Sub1.1'.subsection({captionPosition: "none", width: 130})
'topText'.field({value: 'STRAIGHT BILL OF LADING FOR EXEMPT COMMODITIES  ORIGINAL NON-NEGOTIABLE', fontStyle: 'bold', left: 25})

'Banner2'.section({outputHeading: true, height: 40, fontSize: 7, lineHeight: 4})

'Sub2.1'.subsection({captionPosition: "none", width: 43})
'shipperText'.field({value: 'Shipper:'})
'ourCompanyName'.field()
'ourAddress1'.field()
'ourAddress2'.field()
'ourAddress3'.field()
'ourPhoneWithCaption'.field()
'ourFaxWithCaption'.field()
'loadedAtWithCaption'.field()
'ourContactName'.field()
'ourContactEmail'.field({showAsLink: true, fontSize: 8})

'Sub2.2'.subsection({captionPosition: "none", width: 38})
'pdfLogoUrl'.field({image: true, height: 29.5, width: 38})
'webSite'.field({showAsLink: true, fontSize: 8})
'bolSerialNumberWithCaption'.field({fontSize: 8})

'Sub2.3'.subsection({captionPosition: "left", width: 45, captionWidth: 8})
'shipmentDate'.field({caption: 'Ship', date: true})
'loadDateTime'.field({caption: 'Load'})
'outDateTime'.field({caption: 'Out'})
'deliverBy'.field({caption: 'Dlvr By'})
'carrier'.field()
'driver'.field()
'driverLicense'.field({caption: 'Driver Lic'})

'Sub2.4'.subsection({captionPosition: "left", width: 45, captionWidth: 8})
'shipmentNumber'.field({caption: 'Order#'})
'customerReference'.field({caption: 'Cust PO'})
'terms'.field()
'agentNames'.field({caption: 'Slsprsn'})
'truckLicensePlate'.field({caption: 'Truck Lic'})
'trailerLicensePlate'.field({caption: 'Trailer Lic'})

'Banner3'.section({outputHeading: true, height: 5, fontSize: 7})
'billOfLadingMessage'.field({caption: '', left: 42})

'Banner4'.section({outputHeading: true, height: 8, fontSize: 7, lineHeight: 4})
'Sub4.1'.subsection({captionPosition: "above", width: 80})
'billingNameAndCompany'.field({caption: 'To (Consignee)'})
'Sub4.2'.subsection({captionPosition: "above", width: 60})
'shippingAddress'.field({caption: 'Destination'})
'Sub4.3'.subsection({captionPosition: "above", width: 20})
'billingPhone'.field({caption: 'Telephone'})

'Lines'.manifest({headingBGColor: 'black', fontSize: 8, rowHeight: 5.5})
'BOLLine'.datatype({transient: true})
'shipment'.field({refersToParent: 'SOShipment', hidden: true})
'shippedQuantity'.field({width: 25, caption: 'Shipped Qty', centerAlign: true})
'description'.field({width: 99})
'lotNumber'.field({width: 50})

'Totals'.section({outputFooter: true, width: 100, fontSize: 9, height: 7})
'SOShipment'.datatype()

'TotalsSub1'.subsection({captionPosition: "left", width: 40, captionWidth: 15, leftMargin: 5})
'palletCount'.field({caption: 'Pallets Out', width: 15})

'TotalsSub2'.subsection({captionPosition: "left", width: 40, captionWidth: 15})
'chepPalletCount'.field({caption: 'Chep Pallets Out', width: 15})

'PalletMessage'.section({outputFooter: true, width: 100, fontSize: 9, height: 10})
'Pallet1'.subsection({captionPosition: "none", width: 100, leftMargin: 10})
'palletChargeMessage'.field()

'Footer'.section({pageFooter: true, width: 130, height: 24, bottomMargin: 5})
'SOShipment'.datatype()

'FooterSub1'.subsection({captionPosition: "none", width: 40, lineHeight: 30, inBox: true})
'receivedNotice'.field({fontSize: 5, 
  value: 'RECEIVED from the shipper named herein, the perishable property described in good order ' +
    'and condition, except as noted, marked, consigned and destined as indicated, pursuant to an agreement ' +
    '(arranged by the truck broker, name herein, if any), whereby the carrier, in consideration of the ' +
    'transportation charges to be paid, agrees to carry and deliver said property to the consignee, ' + 
    'subject only to the terms and conditions of this contract, which may be printed or written on the ' +
    'face or back hereof, which are hereby agreed to by the carrier, shipper, and the truck broker if any.'
})
'FooterSub2'.subsection({captionPosition: "left", width: 77, fontSize: 8, leftMargin: 9, captionWidth: 15})
'shipperSignature'.field({caption: 'Shipper', value: '___________________________________'})
'carrierSignature'.field({caption: 'Carrier', value: '___________________________________',
  subscript: 'Received above in good shipping condition and verified count'})
'consigneeSignature'.field({caption: 'Consignee', value: '___________________________________',
  subscript: 'Received above perishable property in good order, except as noted'})

'FooterSub3'.subsection({captionPosition: "left", width: 50, fontSize: 8, leftMargin: 2, captionWidth: 5})
'shipperDate'.field({caption: 'Date', value: '_________________'})
'carrierDate'.field({caption: 'Date', value: '_________________'})
'consigneeDate'.field({caption: 'Date', value: '_________________'})

'BOLPdf'.getCasts(async output => {
  let c = await 'Configuration'.bringFirst()
  return await c.getBOLPdfCasts(output)
})

