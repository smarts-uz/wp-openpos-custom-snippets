'PackagingReport'.list({withHeader: true})
'Packaging Used In Shipped Goods Report'.title()
'Back'.action({act: 'cancel'})
'Download to Excel'.action({act: 'excel'})
'fromDate'.field({date: true})
'toDate'.field({date: true})
'includeCategories'.field({caption: 'Include Categories (comma-separated, or blank for all)'})

'Lines'.manifest()
'Go'.action({place: 'header', spinner: true})
'PackagingLine'.datatype({transient: true})
'sortKey'.field({hidden: true, key: true, nonUnique: true})
'product'.field({hidden: true, indexed: true})
'category'.field({caption: 'Category'})
'sku'.field({caption: 'SKU'})
'description'.field()
'recType'.field({caption: ''})
'weightPerUnit'.field({numeric: true, decimals: 3, blankWhenZero: true})
'salesUnits'.field({numeric: true, decimals: 3, blankWhenZero: true})
'salesWeight'.field({numeric: true, decimals: 3})

/* eslint-disable no-cond-assign */

'fromDate'.default(async (list) => {
  let c = await 'Configuration'.bringSingle(); if ( ! c ) return
  return c.viewFromDate
})

'toDate'.default(async (list) => {
  let c = await 'Configuration'.bringSingle(); if ( ! c ) return
  return c.viewToDate
})

'includeCategories'.default(async (list) => {
  return global.confVal('pca')
})

'Go'.act(async (list) => {

  let saveSetting = async (fieldName, settingFieldName) => {
    let c = await 'Configuration'.bringOrCreate()
    let v = list.getFieldValue(fieldName)
    c[settingFieldName] = v
  }

  let addProductComponents = async (product, units, usedProducts) => {
    let bundle = await product.toBundle(); if ( ! bundle ) return
    let component; let components = await bundle.toComponents()
    while ( component = components.__() ) {
      let compProduct = await component.toProduct(); if ( ! compProduct ) continue
      if ( usedProducts.filterSingle(up => (up.product === compProduct)) ) continue
      let compUnits = units * component.quantity
      usedProducts.push({product: compProduct, units: compUnits})
      await addProductComponents(compProduct, compUnits, usedProducts)
    }
  }

  let shipmentLineToUsedProducts = async shipmentLine => {
    let res = []
    let product = await shipmentLine.toProduct()
    if ( product )
      res.push({product: product, units: shipmentLine.quantityShipped})
    await addProductComponents(product, shipmentLine.quantityShipped, res)
    return res
  }

  let processShipmentLine = async shipmentLine => {
    let usedProduct; let usedProducts = await shipmentLineToUsedProducts(shipmentLine)
    while ( usedProduct = usedProducts.__() ) {
      await processUsedProduct(usedProduct)
    }
  }

  let productToPackagingCategoryName = async product => {
    let category; let categories = await product.toCategories()
    while ( category = categories.__() ) {
      if ( includeCategoriesArray.indexOf(category.categoryName) >= 0 )
        return category.categoryName
    }
    return null
  }

  let productToCategoriesString = async product => {
    let cat; let cats = await product.toCategories()
    let res = ''
    while ( cat = cats.__() ) {
      if ( res )
        res += ' > '
      res = cat.categoryName
    }
    return res
  }

  let processUsedProduct = async usedProduct => {
    let product = usedProduct.product
    let cat = await productToPackagingCategoryName(product)
    if ( (includeCategoriesArray.length > 0) && (! cat) )
      return
    if ( ! cat )
      cat = await productToCategoriesString(product)
    let sortKey = cat + '::' + product._sku
    let reportLine = await 'PackagingLine'.bringOrCreate({sortKey: sortKey, product: product})
    reportLine.category = cat
    reportLine.description = await product.toName()
    reportLine.sku = await product._sku
    reportLine.salesUnits += usedProduct.units
    reportLine.weightPerUnit = product.weight
    reportLine.salesWeight += usedProduct.units * product.weight
  }

  let processShipment = async shipment => {
    let shipmentLine; let shipmentLines = await shipment.toLines()
    while ( shipmentLine = shipmentLines.__() ) {
      await processShipmentLine(shipmentLine)
    }
  }

  let addCategoryLine = async (cat, weight) => {
    let sortKey = cat + '::ZZZZZZ'
    let reportLine = await 'PackagingLine'.create(null, {sortKey: sortKey, category: cat})
    reportLine.recType = 'TOTAL'
    reportLine.salesWeight = weight
  }

  let addCategoryLines = async () => {
    let line; let lines = await 'PackagingLine'.bring()
    lines.sort((a, b) => a.sortKey > b.sortKey ? 1 : -1)
    let cat = '';
    let totalWeight = 0
    while ( line = lines.__() ) {
      if ( (line.category !== cat) && cat ) {
        await addCategoryLine(cat, totalWeight)
        totalWeight = 0
      }
      cat = line.category
      totalWeight += line.salesWeight
    }
    if ( cat ) 
      await addCategoryLine(cat, totalWeight)
  }

  await saveSetting('fromDate', 'viewFromDate')
  await saveSetting('toDate', 'viewToDate')
  await saveSetting('includeCategories', 'pca')
  let fromDate = list.getFieldValue('fromDate')
  if ( (! fromDate) || fromDate.isEmptyYMD() ) fromDate = "0000-00-00"
  let toDate = list.getFieldValue('toDate')
  if ( (! toDate) || toDate.isEmptyYMD() ) toDate = "9999-99-99"
  let includeCategories = list.getFieldValue('includeCategories')
  let includeCategoriesArray = []
  if ( includeCategories )
    includeCategoriesArray = includeCategories.split(',')

  'PackagingLine'.clear()
  let shipment; let shipments = await 'SOShipment'.bring()
  while ( shipment = shipments.__() ) {
    if ( shipment.shipmentDate < fromDate ) continue
    if ( shipment.shipmentDate > toDate ) continue
    await processShipment(shipment)
  }
  await addCategoryLines()

})

