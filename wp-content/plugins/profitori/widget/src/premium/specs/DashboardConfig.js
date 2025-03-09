'DashboardConfig'.maint({panelStyle: "titled"})
'Dashboard Configuration'.title()
'Back'.action({act: 'cancel'})
'OK'.action({act: 'ok'})
'Custom Specs'.action({spec: 'CustomSpecList.js'})
'Configuration'.datatype()
'defaultDashboardTitle'.field({caption: 'Default Dashboard'})
'drSecs'.field()

'Custom Dashboards'.manifest()
'Add Custom Dashboard'.action({act: 'add'})
'CustomDashboard'.datatype()
'configuration'.field({refersToParent: 'Configuration', hidden: true})
'name'.field({key: true})
'title'.field()
'status'.field()
'Edit'.action({place: 'row', act: 'edit'})
'Trash'.action({place: 'row', act: 'trash'})
'CustomDashboardMaint.js'.maintSpecname()

'defaultDashboardTitle'.dynamicOptions(async maint => {
  let res = ['Main Dashboard']
  let customDashboards = await 'CustomDashboard'.bring()
  for ( var i = 0; i < customDashboards.length; i++ ) {
    let customDashboard = customDashboards[i]
    res.push(customDashboard.title)
  }
  return res
})

