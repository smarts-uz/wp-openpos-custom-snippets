'ShipmentNote'.output()
'SOShipment'.datatype()

'Banner'.section({pageHeading: true, rightAlign: true})
'BannerMain'.subsection({captionPosition: "none"})
'businessName'.field()

'Banner2'.section({pageHeading: true, height: 16})
'BannerTitle'.subsection({captionPosition: "none"})
'pdfTitle'.field({fontSize: 28})

'Header'.section({pageHeading: true})
'shipmentNumber'.field()
'order'.field()
'shipmentDate'.field({date: true})
'shippingNameAndCompany'.field()
'shippingAddress'.field({caption: 'Address'})
'billingCompany'.field()

'Lines'.manifest()
'SOShipmentLine'.datatype()
'descriptionAndSKU'.field({caption: 'Product', width: 59})
'quantityOrdered'.field({caption: 'Ordered', width: 16})
'quantityShipped'.field({caption: "Delivered", width: 16})
'quantityRemaining'.field({caption: "Remaining", width: 16})

'ShipmentNote'.getCasts(async output => {
  if ( output.cast ) 
    return [output.cast] // individual, set by Maint
  return await 'SOShipment'.bring({include: 'Yes'})
})
