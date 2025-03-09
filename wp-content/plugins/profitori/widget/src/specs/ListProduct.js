'ListProduct'.maint({panelStyle: "titled"})
'List Unlisted Product'.title()
'Back'.action({act: 'cancel'})
'OK'.action({icon: 'CheckDouble'})
'Embryo'.datatype({transient: true})

'Order Details'.panel()
'supplier'.field({refersTo: 'Supplier', showAsLink: true, readOnly: true})
'embryoNotes'.field({caption: 'Notes', readOnly: true, indexed: true})
'quantityOnSalesOrders'.field({readOnly: true})
'quantityOnPurchaseOrders'.field({readOnly: true})

'New Product Details'.panel()
'productName'.field()
'sku'.field()
'brand'.field({refersTo: 'brand', allowEmpty: true})
'imageContents'.field({caption: "Image", file: true})
'imageFileName'.field()

/* eslint-disable no-cond-assign */

'imageFileName'.visibleWhen((maint, embryo) => {
  return embryo.imageFileName ? true : false
})

'imageContents'.afterChoosingFile(async (maint, files) => {
  if ( files.length === 0 ) return
  if ( files.length > 1 ) {
    global.app().showMessage('Please select a single file')
    return
  }
  let embryo = maint.mainCast(); if ( ! embryo ) return
  let f = files[0]
  let reader = new FileReader()
  let attachment = maint.__attachment
  if ( ! attachment )
    attachment = await 'Attachment'.create()
  attachment.attachmentType = 'Image'
  attachment.description = 'Main product image'
  attachment.attachedDate = global.todayYMD()

  reader.onload = function(e) {
    attachment.contents = btoa(e.target.result)
    attachment.__fileName = f.name
    attachment.fileName = f.name
    maint.__attachment = attachment
    maint.__attachmentContents = attachment.contents
  }

  reader.readAsBinaryString(f);
}) 

'OK'.act(async (maint, embryo) => {

  let product
  let inv

  let maybeAttachImage = async inv => {
    let att = maint.__attachment
    if ( ! att ) return
    await att.reretrieve()
    att.datatype = 'Inventory'
    att.theParentId = inv.id
    att.parentReference = inv.reference()
    att.contents = maint.__attachmentContents
  }

  let createProduct = async () => {
    let uniqueName = embryo.productName
    if ( embryo.sku )
      uniqueName += ' (' + embryo.sku + ')'
    product = await 'products'.bringOrCreate({uniqueName: uniqueName})
    product._sku = embryo.sku
    await global.app.save()
    inv = await product.toInventory({allowCreate: true})
    inv.primaryBrand = embryo.brand
    await global.app.save()
    await maybeAttachImage(inv)
  }

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
    if ( ! embryo.sku )
      throw(new Error('SKU'.translate() + ' is required'.translate()))
    let p = await 'products'.bringSingle({_sku: embryo.sku})
    if ( p ) 
      throw(new Error('SKU'.translate() + ' ' + embryo.sku + ' ' + 'is already in use.'.translate()))
  }

  try {
    await global.app.save()
    await validate()
    await createProduct()
    await convertSOLines()
    await convertPOLines()
    await global.app.save()
    maint.showMessage("New product was created, and customer and supplier order lines converted")
    maint.back()
  } catch(e) {
    maint.showMessage(e.message)
  }
})

