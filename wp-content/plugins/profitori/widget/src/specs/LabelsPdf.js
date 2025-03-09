'LabelsPdf'.output({type: 'labels'})
'LabelHolder'.datatype({transient: true})

'LabelsManifest'.manifest()
'Label'.datatype({transient: true})
'holder'.field({refersToParent: 'LabelHolder', hidden: true})

'LabelsPdf'.substituteCast(async (cast, output) => {

  let populateLabelInventoryField = async (label, field, cast) => {
    let inv
    if ( cast.datatype() === "Inventory" )
      inv = cast
    else if ( cast.toInventory )
      inv = await cast.toInventory({allowCreate: true})
    else
      return
    await inv.refreshCalculations({force: true, includeDefers: true})
    label[field.name] = inv[field.name]
  }

  let populateLabelSOShipmentLineField = async (label, field, cast) => {
    let line
    if ( cast.datatype() === "SOShipmentLine" )
      line = cast
    else if ( cast.toSOShipmentLine )
      line = await cast.toSOShipmentLine()
    if ( ! line )
      return
    await line.refreshCalculations({force: true, includeDefers: true})
    label[field.name] = line[field.name]
  }

  let populateLabelSOShipmentField = async (label, field, cast) => {
    let s
    if ( cast.datatype() === "SOShipment" )
      s = cast
    else if ( cast.toShipment )
      s = await cast.toShipment()
    if ( ! s )
      return
    await s.refreshCalculations({force: true, includeDefers: true})
    label[field.name] = s[field.name]
  }

  let populateLabelPalletField = async (label, field, cast) => {
    let p
    if ( cast.datatype() === "Pallet" )
      p = cast
    else if ( cast.toPallet )
      p = await cast.toPallet()
    if ( ! p )
      return
    await p.refreshCalculations({force: true, includeDefers: true})
    label[field.name] = p[field.name]
  }

  let populateLabelLotField = async (label, field, cast) => {
    let p
    if ( cast.datatype() === "Lot" )
      p = cast
    else if ( cast.toLot )
      p = await cast.toLot()
    if ( ! p )
      return
    await p.refreshCalculations({force: true, includeDefers: true})
    label[field.name] = p[field.name]
  }

  let populateLabelPOReceiptLineField = async (label, field, cast) => {
    let line
    if ( cast.datatype() === "POReceiptLine" )
      line = cast
    else if ( cast.toPOReceiptLine )
      line = await cast.toPOReceiptLine()
    if ( ! line )
      return
    await line.refreshCalculations({force: true, includeDefers: true})
    label[field.name] = line[field.name]
  }

  let populateLabelWOField = async (label, field, cast) => {
    let wo
    if ( cast.datatype() === "WO" )
      wo = cast
    else if ( cast.toWO )
      wo = await cast.toWO()
    else {
      let parent = await cast.parent() // e.g. cast might be an Allotment
      if ( parent && (parent.datatype() === 'WO') )
        wo = parent
    }
    if ( ! wo )
      return
    await wo.refreshCalculations({force: true, includeDefers: true})
    label[field.name] = wo[field.name]
  }

  let populateLabelAllotmentField = async (label, field, cast) => {
    let a
    if ( cast.datatype() === "Allotment" )
      a = cast
    else if ( cast.toAllotment )
      a = await cast.toAllotment()
    if ( ! a )
      return
    await a.refreshCalculations({force: true, includeDefers: true})
    label[field.name] = a[field.name]
  }

  let populateLabelProductField = async (label, field, cast) => {
    let product
    if ( cast.datatype() === 'products' ) 
      product = cast
    else {
      if ( ! cast.toProduct ) return
      product = await cast.toProduct()
    }
    await product.refreshCalculations({force: true, includeDefers: true})
    label[field.name] = product[field.name]
  }

  let populateLabelField = async (label, field, cast) => {
    if ( ! field.realm ) return
    if ( field.realm === "POReceiptLine" )
      await populateLabelPOReceiptLineField(label, field, cast)
    else if ( field.realm === "SOShipmentLine" )
      await populateLabelSOShipmentLineField(label, field, cast)
    else if ( field.realm === "SOShipment" )
      await populateLabelSOShipmentField(label, field, cast)
    else if ( field.realm === "Pallet" )
      await populateLabelPalletField(label, field, cast)
    else if ( field.realm === "Lot" )
      await populateLabelLotField(label, field, cast)
    else if ( field.realm === "WO" )
      await populateLabelWOField(label, field, cast)
    else if ( field.realm === "Allotment" )
      await populateLabelAllotmentField(label, field, cast)
    else if ( field.realm === "Inventory" )
      await populateLabelInventoryField(label, field, cast)
    else if ( field.realm.startsWith("WC Product") )
      await populateLabelProductField(label, field, cast)
    else if ( field.realm === "Caption Only" ) {
      label[field.name] = field.caption
    }
  }

  let populateLabelFields = async (label, cast) => {
    let fields = output.getRowFields()
    for ( var i = 0; i < fields.length; i++ ) {
      let field = fields[i]
      await populateLabelField(label, field, cast)
    }
  }
  
  let createLabels = async () => {
    let dt = cast.datatype()
    if ( dt === "POReceipt" )
      return await createPOReceiptLabels()
    else if ( dt === "SOShipment" )
      return await createSOShipmentLabels()
    else if ( dt === "Pallet" )
      return await createPalletLabels()
    else if ( (dt === "Lot") || (dt === 'Clump') )
      return await createLotLabels()
    else if ( (dt === "WO") || (dt === 'WOReceipt') )
      return await createWOLabels()
    else if ( dt === "products" )
      return await createProductLabels()
    else if ( dt === "Inventory" )
      return await createInventoryLabels()
    else if ( dt === "BulkTransfer" )
      return await createMultiProductLabels({bulkTransfer: true})
    else if ( dt === "Labels" )
      return await createMultiProductLabels()
  }

  let createInventoryLabels = async () => {
    let inv = cast
    await 'LabelHolder'.clear()
    await 'Label'.clear()
    let holder = await 'LabelHolder'.create()
    await inv.refreshCalculations({force: true, includeDefers: true})
    let label = await 'Label'.create({parentCast: holder})
    label._labelCount = inv.quantityOnHand ? inv.quantityOnHand : 1
    await populateLabelFields(label, inv)
    return holder
  }

  let getSelectedProducts = async (opt) => {
    let products
    if ( opt && opt.bulkTransfer )
      products = await 'products'.bring({bulkTransfer: "Yes"})
    else
      products = await 'products'.bring({include: "Yes"})
    return products
  }

  let createMultiProductLabels = async (opt) => {
    let products = await getSelectedProducts(opt)
    if ( products.length === 0 ) {
      global.gApp.showMessage("Please select one or more products")
      return
    }
    await 'LabelHolder'.clear()
    await 'Label'.clear()
    let holder = await 'LabelHolder'.create()
    for ( var i = 0; i < products.length; i++ ) {
      let product = products[i]
      await product.refreshCalculations({force: true, includeDefers: true})
      let label = await 'Label'.create({parentCast: holder})
      let inv = await product.toInventory()
      let qoh = inv ? inv.quantityOnHand : 0
      label._labelCount = qoh ? qoh : 1
      await populateLabelFields(label, product)
    }
    return holder
  }

  let createProductLabels = async () => {
    let product = cast
    await 'LabelHolder'.clear()
    await 'Label'.clear()
    let holder = await 'LabelHolder'.create()
    await product.refreshCalculations({force: true, includeDefers: true})
    let label = await 'Label'.create({parentCast: holder})
    let inv = await product.toInventory()
    let qoh = inv ? inv.quantityOnHand : 0
    label._labelCount = qoh ? qoh : 1
    await populateLabelFields(label, product)
    return holder
  }

  let createPOReceiptLabels = async () => {

    let sortBySku = lines => {
      lines.sort(
        (line1, line2) => {
          if ( line1.sku > line2.sku ) return 1
          return -1
        }
      )
    }

    let createPOReceiptLineAllotmentLabels = async line => {
      let as = await line.toAllotments()
      for ( var i = 0; i < as.length; i++ ) {
        let a = as[i]
        await a.refreshCalculations({force: true, includeDefers: true})
        let label = await 'Label'.create({parentCast: holder})
        label._labelCount = a.quantity
        await populateLabelFields(label, a)
      }
    }

    let poReceipt = cast
    await 'LabelHolder'.clear()
    await 'Label'.clear()
    let holder = await 'LabelHolder'.create()
    let lines = await 'POReceiptLine'.bringChildrenOf(poReceipt)
    sortBySku(lines)
    for ( var i = 0; i < lines.length; i++ ) {
      let line = lines[i]
      await line.refreshCalculations({force: true, includeDefers: true})
      if ( line.hasLots === 'Yes' ){
        await createPOReceiptLineAllotmentLabels(line)
        continue
      }
      let label = await 'Label'.create({parentCast: holder})
      label._labelCount = line.receivedQuantity
      await populateLabelFields(label, line)
    }
    return holder
  }

  let createPalletLabels = async () => {
    let pallet = cast
    await 'LabelHolder'.clear()
    await 'Label'.clear()
    let holder = await 'LabelHolder'.create()
    await pallet.refreshCalculations({force: true, includeDefers: true})
    let label = await 'Label'.create({parentCast: holder})
    label._labelCount = 1
    await populateLabelFields(label, pallet)
    return holder
  }

  let createSOShipmentLabels = async () => {

    let sortBySku = lines => {
      lines.sort(
        (line1, line2) => {
          if ( line1.sku > line2.sku ) return 1
          return -1
        }
      )
    }

    let soShipment = cast
    await 'LabelHolder'.clear()
    await 'Label'.clear()
    let holder = await 'LabelHolder'.create()
    let lines = await 'SOShipmentLine'.bringChildrenOf(soShipment)
    sortBySku(lines)
    for ( var i = 0; i < lines.length; i++ ) {
      let line = lines[i]
      await line.refreshCalculations({force: true, includeDefers: true})
      let label = await 'Label'.create({parentCast: holder})
      label._labelCount = line.quantityShipped
      await populateLabelFields(label, line)
    }
    return holder
  }


  let createWOLabels = async () => {
    
    let createWOAllotmentLabels = async () => {
      let as
      if ( woReceipt )
        as = await woReceipt.toAllotments()
      else
        as = await wo.toAllotments()
      for ( var i = 0; i < as.length; i++ ) {
        let a = as[i]
        await a.refreshCalculations({force: true, includeDefers: true})
        let label = await 'Label'.create({parentCast: holder})
        label._labelCount = a.quantity
        await populateLabelFields(label, a)
      }
    }

    let wo = cast
    let woReceipt
    let qty = wo.fgQuantity
    if ( cast.datatype() === 'WOReceipt' ) {
      woReceipt = cast
      qty = woReceipt.receivedQuantity
      wo = await woReceipt.toWO()
    }
    await 'LabelHolder'.clear()
    await 'Label'.clear()
    let holder = await 'LabelHolder'.create()
    if ( wo.hasLots === 'Yes' ){
      await createWOAllotmentLabels()
      return
    }
    await wo.refreshCalculations({force: true, includeDefers: true})
    let label = await 'Label'.create({parentCast: holder})
    label._labelCount = qty
    await populateLabelFields(label, wo)
    return holder
  }

  let createLotLabels = async () => {
    let lot
    if ( cast.datatype() === 'Lot' )
      lot = cast
    else if ( cast.toLot )
      lot = await cast.toLot()
    if ( ! lot )
      return
    
    await 'LabelHolder'.clear()
    await 'Label'.clear()
    let holder = await 'LabelHolder'.create()
    await lot.refreshCalculations({force: true, includeDefers: true})
    let label = await 'Label'.create({parentCast: holder})
    let qtyOnHand = await lot.toQuantityOnHand()
    label._labelCount = qtyOnHand ? qtyOnHand : 1
    await populateLabelFields(label, lot)
    return holder
  }
  
  let holder = await createLabels()
  return holder
})

'LabelsPdf'.labelCount(async label => {
  return label._labelCount
})

'LabelsPdf'.modifyRowFields(async (writer, fields, template) => {

  let captionCount = 0

  let facetToField = async (facet) => {
    let source = await facet.referee('source'); if ( ! source ) return null
    let parts = source.description.split(".")
    let realm = parts[0]
    let name = parts[1]
    if ( realm === "Caption Only" ) {
      name = "caption"
      captionCount++
      if ( captionCount > 1 )
        name += captionCount
    }
    let res
    res = fields.filterSingle(f => f.name === name)
    if ( res ) 
      return res
    let disposition = await facet.referee('disposition')
    let numeric = (disposition && (disposition.description === "Number"))
    let date = (disposition && (disposition.description === "Date"))
    let barcode = (disposition && (disposition.description === "Barcode"))
    let qrcode = (disposition && (disposition.description === "QR Code"))
    let f = writer.createField({name: name})
    f.datatype = 'Label'
    f.englishCaption = facet.englishCaption ? facet.englishCaption : facet.caption
    f.caption = facet.caption
    f.realm = realm
    f.numeric = numeric
    f.minDecimals = facet.minimumDecimals
    f.maxDecimals = facet.maximumDecimals
    f.left = facet.left
    f.top = facet.top
    f.width = facet.width
    f.bold = facet.bold
    f.fontSize = facet.fontSize
    f.height = facet.height
    f.barcode = barcode
    f.barcodeFormat = facet.barcodeFormat
    f.qrcode = qrcode
    f.date = date
    f.centerAlign = (facet.justification === 'Centered')
    f.rightAlign = (facet.justification === 'Right')
    f.blankWhenZero = (facet.blankWhenZero === 'Yes')
    if ( f.centerAlign )
      f.left = facet.left + (facet.width / 2)
    if ( f.rightAlign )
      f.left = facet.left + facet.width
    return f
  }

  let newFields = []
  if ( ! template )
    template = await 'Template'.bringOrCreate({specification: "LabelsPdf.js", purpose: ''})
  let facets = await 'Facet'.bring({parentId: template.id})
  facets.sort((f1, f2) => f1.sequence > f2.sequence ? 1 : -1)
  await facets.forAllAsync(async facet => {
    let f = await facetToField(facet); if ( ! f ) return "continue"
    newFields.push(f)
  })
  fields.appendArray(newFields)

})

'LabelsPdf'.modifyRowMoldFields(async (mold) => {

  let template = await 'Template'.bringOrCreate({specification: "LabelsPdf.js"});
  let facets = await 'Facet'.bring({parentId: template.id})
  let fieldsAdded = false
  let captionCount = 0
  await facets.forAllAsync(async (facet) => {
    let source = await facet.referee('source'); if ( ! source ) return "continue"
    let parts = source.description.split(".")
    let realm = parts[0]
    let name = parts[1]
    if ( realm === "Caption Only" ) {
      name = "caption"
      captionCount++
      if ( captionCount > 1 )
        name += captionCount
    }
    let disposition = await facet.referee('disposition')
    let numeric = (disposition && (disposition.description === "Number"))
    let date = (disposition && (disposition.description === "Date"))
    let barcode = (disposition && (disposition.description === "Barcode"))
    let qrcode = (disposition && (disposition.description === "QR Code"))
    let f = mold.fields.filterSingle(f => f.name === name)
    if ( ! f ) {
      f = mold.createField(name)
      fieldsAdded = true
    }
    f.englishCaption = facet.englishCaption ? facet.englishCaption : facet.caption
    f.caption = facet.caption
    f.realm = realm
    f.numeric = numeric
    f.minDecimals = facet.minimumDecimals
    f.maxDecimals = facet.maximumDecimals
    f.left = facet.left
    f.top = facet.top
    f.width = facet.width
    f.bold = facet.bold
    f.fontSize = facet.fontSize
    f.height = facet.height
    f.barcode = barcode
    f.barcodeFormat = facet.barcodeFormat
    f.qrcode = qrcode
    f.date = date
  })
  return fieldsAdded

})

