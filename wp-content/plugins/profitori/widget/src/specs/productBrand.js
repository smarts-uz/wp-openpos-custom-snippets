'productBrand'.datatype({source: 'WC', preventNative: true, exportable: true})
'productId'.field({key: true, numeric: true, nonUnique: true})
'brandId'.field({numeric: true})

'productBrand'.method('toBrand', async function() {
  return await 'brand'.bringFirst({id: this.brandId})
})

'productBrand'.method('toBrandFast', function() {
  return 'brand'.bringSingleFast({id: this.brandId})
})
