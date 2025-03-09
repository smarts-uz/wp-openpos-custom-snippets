'CustomDashboard'.datatype({plex: true})
'configuration'.field({refersToParent: 'Configuration'})
'name'.field({key: true})
'title'.field({allowEmpty: false})
'blueprint'.field()
'status'.field()
'errorMessage'.field()
'sourceCode'.field()

'CustomDashboard'.afterCreating(async function () {
  let config = await 'Configuration'.bringFirst()
  this.configuration = config.reference()
  //if ( this.name === 'MainDashboard' )
    //this.title = 'Main Dashboard'
  let canon = await 'canon'.bringFirst({path: 'premium/specs/MainDashboard.js'})
  this.blueprint = canon && (canon.blueprint + '')
  this.blueprint = this.blueprint.removeLine(0) /// First two lines not needed
  this.blueprint = this.blueprint.removeLine(0)
  this.blueprint = this.blueprint.removeLinesAfter(4) // 'alternativeSpec' and beyond
  this.status = 'Valid'
})

'sourceCode'.calculate(async db => {
  let res = await db.constructCode()
  return res
})

'CustomDashboard'.method('constructCode', async function () {
  let res =
    "'" + this.name + "'.dashboard()\n" +
    "'" + this.title + "'.title()\n" +
    this.blueprint
  return res
})

'CustomDashboard'.beforeSaving(async function() {
  let spec = await 'CustomSpec'.bringFirst({name: this.name})
  if ( spec )
    throw(new Error("Name '" + this.name + "' is already in use"))
  let otherDBs = await 'CustomDashboard'.bring({title: this.title})
  for ( var i = 0; i < otherDBs.length; i++ ) {
    let otherDB = otherDBs[i]
    if ( otherDB.id === this.id ) continue
    throw(new Error("A dashboard exists with this title - please enter a different title".translate()))
  }
  let canons = await 'canon'.bring()
  for ( i = 0; i < canons.length; i++ ) {
    let canon = canons[i]
    if ( canon.path.endsWith('/' + this.name + '.js') )
      throw(new Error("Name '" + this.name + "' is already in use"))
  }
})
