'TemplateList'.list()
'Label Templates'.title()
'Back'.action({act: 'cancel'})
'Template'.datatype()
'purposeDisplay'.field({readOnly: true, caption: 'Label Purpose'})
'columnCount'.field({numeric: true, decimals: 0, caption: 'Number of Labels Across Page'})
'rowsPerPage'.field({numeric: true, decimals: 0, caption: 'Number of Labels Down Page'})
'Edit'.action({place: 'row', act: 'edit'})
'TemplateMaint.js'.maintSpecname()

'TemplateList'.filter(async (template, list) => {
  return template.specification === 'LabelsPdf.js'
})

