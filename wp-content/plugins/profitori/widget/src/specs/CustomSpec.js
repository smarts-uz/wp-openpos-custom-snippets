'CustomSpec'.datatype({plex: true})
'configuration'.field({refersToParent: 'Configuration'})
'name'.field({key: true})
'canon'.field({refersTo: 'canon', caption: 'Based On'})
'blueprint'.field()
'status'.field()
'errorMessage'.field()
'canonBlueprint'.field()

'CustomSpec'.afterCreating(async function () {
  let config = await 'Configuration'.bringFirst()
  this.configuration = config.reference()
  this.status = 'Invalid'
  this.errorMessage = 'No javascript code has been entered'.translate()
})

'canonBlueprint'.calculate(async customSpec => {
  if ( ! customSpec.canon ) return ''
  let canon = await customSpec.referee('canon'); if ( ! canon ) return ''
  return canon.blueprint
})

'CustomSpec'.beforeSaving(async function() {
  let db = await 'CustomDashboard'.bringFirst({name: this.name})
  if ( db )
    throw(new Error("Name '" + this.name + "' is already in use"))
  let canon = await 'canon'.bringFirst({path: 'specs/' + this.name + '.js'})
  if ( ! canon )
    canon = await 'canon'.bringFirst({path: 'premium/specs/' + this.name + '.js'})
  if ( canon )
    throw(new Error(this.name + ' already exists - please specify a different list name'.translate()))
})

'CustomSpec'.allowTrash(async function() {
  let dbs = await 'CustomDashboard'.bring()
  for ( var i = 0; i < dbs.length; i++ ) {
    let db = dbs[i]
    if ( 
      (db.blueprint.indexOf('"' + this.name + '"') >= 0 ) ||
      (db.blueprint.indexOf("'" + this.name + "'") >= 0 )
    ) {
      return "This Custom Spec cannot be trashed as it is used in Dashboard".translate() + " '" + db.name + "'"
    }
  }
})
