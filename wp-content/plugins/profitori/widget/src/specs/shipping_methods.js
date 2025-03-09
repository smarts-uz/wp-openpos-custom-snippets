'shipping_methods'.datatype({source: 'WC', exportable: true, caption: 'Shipping Method'})
'uniqueName'.field({key: true})
'zone_id'.field({numeric: true})
'zone_name'.field()
'zone_order'.field({numeric: true})
'location_codes'.field()
'instance_id'.field({numeric: true, indexed: true})
'shipping_id'.field()
'method_title'.field()
'title'.field()
'tax_status'.field()
'cost'.field({numeric: true, decimals: 2})
'min_amount'.field({numeric: true})

'shipping_methods'.method('appliesToCountryCode', async function(countryCode) {
  if ( ! this.location_codes ) return false
  let codeArray = this.location_codes.split(',')
  return (codeArray.indexOf(countryCode) >= 0)
})
