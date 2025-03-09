'ListUnlisted'.list()
'List Unlisted Products'.title()
'Back'.action({act: 'cancel', icon: 'AngleLeft'})

'Embryo'.datatype({transient: true})
'supplier'.field({refersTo: 'Supplier', showAsLink: true, readOnly: true})
'embryoNotes'.field({caption: 'Notes', readOnly: true, indexed: true})
'quantityOnSalesOrders'.field({numeric: true, readOnly: true})
'quantityOnPurchaseOrders'.field({numeric: true, readOnly: true})
'productName'.field({hidden: true, allowEmpty: false})
'sku'.field({caption: 'SKU', hidden: true})
'List this Product'.action({spec: 'ListProduct.js', place: 'row', act: 'edit'})
'Use Existing Product'.action({spec: 'UseExistingProduct.js', place: 'row', act: 'edit'})

/* eslint-disable no-cond-assign */

'ListUnlisted'.defaultSort({field: 'embryoNotes'})

'ListUnlisted'.beforeLoading(async list => {

  let processSOLine = async (line, supplier) => {
    let embryo = await 'Embryo'.bringOrCreate({embryoNotes: line.notes, supplier: supplier})
    embryo.productName = line.notes
    embryo.quantityOnSalesOrders += line.quantityRemainingToShip
  }

  let processSOLines = async () => {
    let supplier, suppliers = await 'Supplier'.bring()
    while ( supplier = suppliers.__() ) {
      if ( ! supplier.embryoProduct ) continue
      let line; let lines = await 'SOLine'.bring({product: supplier.embryoProduct})
      while ( line = lines.__() ) {
        if ( ! line.notes ) continue
        if ( ! await line.isActive() ) continue
        if ( line.quantityShipped !== 0 ) continue
        await processSOLine(line, supplier)
      }
    }
  }

  let processPOLine = async (line, supplier) => {
    let embryo = await 'Embryo'.bringOrCreate({embryoNotes: line.embryoNotes, supplier: supplier})
    embryo.productName = line.embryoNotes
    embryo.quantityOnPurchaseOrders += line.outstandingQuantity
  }

  let processPOLines = async () => {
    let supplier, suppliers = await 'Supplier'.bring()
    while ( supplier = suppliers.__() ) {
      if ( ! supplier.embryoProduct ) continue
      let line; let lines = await 'POLine'.bring({product: supplier.embryoProduct})
      while ( line = lines.__() ) {
        if ( line.receivedQuantity !== 0 ) continue
        if ( ! line.outstandingQuantity ) continue
        await processPOLine(line, supplier)
      }
    }
  }

  await 'Embryo'.clear()
  await processSOLines()
  await processPOLines()

  list.startAlter({skipFieldCheck: true})
})


