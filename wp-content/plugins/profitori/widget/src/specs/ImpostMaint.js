'ImpostMaint'.maint()
'Add Impost'.title({when: 'adding'})
'Edit Impost'.title({when: 'editing'})
'Back'.action({act: 'cancel'})
'OK'.action({act: 'ok'})
'Save'.action({act: 'save'})
'Add another'.action({act: 'add'})
'Impost'.datatype({plex: true})

'description'.field({key: true, caption: 'Description'})
'customerMetaFieldName'.field({allowEmpty: false})
'script'.field({jsEditor: true, caption: 'Javascript Function To Calculate Impost'})
'scriptAltered'.field({yesOrNo: true, hidden: true})

'ImpostMaint'.makeDestinationFor('Impost')

'Classifiers'.manifest()
'Add Classifier'.action({act: 'add'})
'Classifier'.datatype()
'classifierDescription'.field({caption: 'Description'})
'productMetaFieldName'.field()
'defaultValue'.field()
'Edit'.action({place: 'row', act: 'edit'})
'Trash'.action({place: 'row', act: 'trash'})
'ClassifierMaint.js'.maintSpecname()

'Impost'.beforeSaving(async function() {
  let users = await 'users'.bring()
  for ( var i = 0; i < users.length; i++ ) {
    let user = users[i]
    user.__afterRetrieveDone = false // so that empty impost flags will be defaulted
  }
  let invs = await 'Inventory'.bring()
  for ( i = 0; i < invs.length; i++ ) {
    let inv = invs[i]
    inv.__afterRetrieveDone = false
  }
})

'Impost'.method('generateDefaultScript', async function() {

  let generateFieldComments = async () => {
    let res = ''
    let classifiers = await 'Classifier'.bring({impost: this})
    if ( classifiers.length === 0 )
      return res
    res = '  // Classification Fields:\n'
    for ( var i = 0; i < classifiers.length; i++ ) {
      let classifier = classifiers[i]
      res += '  // product.' + classifier.productMetaFieldName + '\n'
    }
    return res
  }

  let res =
    '(product, quantity) => {\n' +
       await generateFieldComments() +
    '  return 0\n' +
    '}\n'
  return res
})

'Impost'.method('refreshScript', async function() {
  if ( this.scriptAltered === 'Yes' ) return
  this.script = await this.generateDefaultScript()
})

'script'.inception(async impost => {
  return await impost.generateDefaultScript()
})

'Impost'.validate(async function() {
  if ( /\s/.test(this.customerMetaFieldName) )
    throw(new Error('Customer Meta Field Name cannot contain spaces'.translate()))
})

'script'.afterUserChange(async (oldInputValue, newInputValue, impost) => {
  impost.scriptAltered = 'Yes'
})
