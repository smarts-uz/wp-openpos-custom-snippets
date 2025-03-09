'BundleCobundlesMaint'.maint({panelStyle: 'titled'})
'Bundle Co-products'.title()
'Back'.action({act: 'cancel'})
'OK'.action({act: 'ok'})
'Save'.action({act: 'save'})
'Bundle'.datatype()

'Co-products'.manifest()
'Add Co-product'.action({act: 'addNoSave'})
'Cobundle'.datatype()
'bundle'.field({hidden: true})
'product'.field({showAsLink: true})
'Trash'.action({place: 'row', act: 'trash'})
'CobundleMaint.js'.maintSpecname()

'BundleCobundlesMaint'.substituteCast(async (cast, maint) => {
  let res
  if ( ! cast ) {
    res = maint.callerCast({immediateOnly: true})
    if ( res && res.datatype && (res.datatype() === 'Bundle') )
      return res
    return null
  }
  if ( cast && cast.datatype && (cast.datatype() === 'Bundle') ) return cast
  return res
})


