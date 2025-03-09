'CustomDashboardMaint'.maint()
'Add Dashboard'.title({when: 'adding'})
'Edit Dashboard'.title({when: 'editing'})
'Back'.action({act: 'cancel'})
'OK'.action({act: 'ok'})
'Save'.action({act: 'save'})
'CustomDashboard'.datatype({plex: true})

'Main'.panel()
'configuration'.field({hidden: true})
'name'.field({caption: 'Dashboard Name'})
'title'.field()

''.panel()

'Status'.panel()
'status'.field({readOnly: true})
'errorMessage'.field({readOnly: true, caption: 'Error'})

''.panel()

'Blueprint'.panel({fullWidth: true})
'blueprint'.field({caption: 'Javascript Code', jsEditor: true})

/* eslint-disable no-eval */

'blueprint'.afterUserChange(async (oldInputValue, newInputValue, customDashboard) => {

  let cookMsg = msg => {
    let orig = msg
    let remObj = {}
    msg = msg.stripAfterLast(' (', remObj)
    let coords = remObj.str
    let brackPos = coords.indexOf('(')
    if ( brackPos < 0 ) return orig
    let colonPos = coords.indexOf(':')
    if ( colonPos < 0 ) return orig
    let closeBrackPos = coords.indexOf(')')
    if ( closeBrackPos < 0 ) return orig
    let row = coords.substr(brackPos + 1, colonPos - brackPos - 1)
    row = parseInt(row, 10) - 2 // first two lines aren't shown in the editor
    let col = coords.substr(colonPos + 1, closeBrackPos - colonPos - 1)
    msg = "Error on line " + row + " (character " + col + "): " + msg
    return msg
  }

  let code = await customDashboard.constructCode()
  customDashboard.errorMessage = ''
  try {
    code.jsParse()
  } catch (e) {
    let msg = cookMsg(e.message)
    customDashboard.errorMessage = msg
  }
  if ( ! customDashboard.errorMessage ) {
    try {
      global.currentSpecname = null
      eval(code)
    } catch(e) {
      customDashboard.errorMessage = e.message
    }
  }
  if ( customDashboard.errorMessage )
    customDashboard.status = 'Invalid'
  else
    customDashboard.status = 'Valid'
})

'status'.visibleWhen((maint, customDashboard) => {
  return customDashboard.errorMessage ? false : true
})

'errorMessage'.visibleWhen((maint, customDashboard) => {
  return customDashboard.errorMessage ? true : false
})
