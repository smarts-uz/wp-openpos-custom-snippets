'Cobundle'.datatype()
'bundle'.field({key: true, nonUnique: true, refersToParent: 'Bundle', parentIsHeader: true})
'product'.field({refersTo: 'products', allowEmpty: false})
'notes'.field({multiLine: true})

/* eslint-disable no-cond-assign */

'Cobundle'.method('toBundle', async function() {
  return await this.toParent()
})

'Cobundle'.method('toParent', async function() {
  return await this.parent()
})

'Cobundle'.cruxFields(['bundle', 'product'])

'Cobundle'.method('toProduct', async function() {
  return await this.referee('product')
})

'Cobundle'.method('toInventory', async function() {
  let product = await this.referee('product'); if ( ! product ) return null
  return await product.toInventory()
})


