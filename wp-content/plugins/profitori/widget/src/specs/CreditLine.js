'CreditLine'.datatype()
'credit'.field({refersToParent: 'Credit', parentIsHeader: true})
'shipmentLine'.field({refersTo: 'SOShipmentLine', indexed: true})
'product'.field({refersTo: 'products'})
'productUniqueName'.field()
'productSku'.field()
'quantity'.field({numeric: true})
'unitPriceExclTaxFX'.field({numeric: true, decimals: 2})
'unitPriceFX'.field({numeric: true, decimals: 2})
'lineValueExclTaxFX'.field({numeric: true, decimals: 2})
'lineValueFX'.field({numeric: true, decimals: 2})
'thumbnailImage'.field({postImage: true, postImageType: 'thumbnail', postIdField: 'product', caption: 'Image'})
'uom'.field({refersTo: 'UOM'})
'lineTaxFX'.field({numeric: true, decimals: 2})

'lineTaxFX'.calculate(creditLine => {
  return creditLine.lineValueFX - creditLine.lineValueExclTaxFX
})

'uom'.calculate(async creditLine => {
  let inv = await creditLine.toInventory(); if ( ! inv ) return null
  return inv.stockingUOM ? inv.stockingUOM : global.confVal('defaultStockingUOM')
})

'CreditLine'.method('toInventory', async function() {
  let p = await this.toProduct(); if ( ! p ) return null
  return await p.toInventory()
})

'CreditLine'.method('toProduct', async function() {
  return await this.referee('product')
})
