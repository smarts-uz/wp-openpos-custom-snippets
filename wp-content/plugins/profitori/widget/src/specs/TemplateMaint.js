'TemplateMaint'.maint({panelStyle: "titled"})
'Labels Layout'.title()
'Back'.action({act: 'cancel'})
'OK'.action({act: 'ok'})
'Save'.action({act: 'save'})
'Duplicate'.action()
'Template'.datatype()

'Layout'.panel()
'purpose'.field({caption: 'Label Purpose'})
'purposeDisplay'.field({readOnly: true, caption: 'Label Purpose'})

''.panel()

'Page Dimensions'.panel()
'specification'.field({hidden: true, key: true})
'pageWidthMm'.field()
'pageWidthInches'.field()
'pageHeightMm'.field()
'pageHeightInches'.field()
'pageLeftMarginMm'.field({numeric: true, caption: 'Page Left Margin (mm)'})
'pageLeftMarginInches'.field({numeric: true, caption: 'Page Left Margin (in)'})
'pageTopMarginMm'.field({numeric: true, caption: 'Page Top Margin (mm)'})
'pageTopMarginInches'.field({numeric: true, caption: 'Page Top Margin (in)'})
'columnCount'.field({numeric: true, decimals: 0, caption: 'Number of Labels Across Page'})
'rowsPerPage'.field({numeric: true, decimals: 0, caption: 'Number of Labels Down Page'})

'Label Dimensions'.panel()
'labelWidthMm'.field({numeric: true, caption: 'Label Width (mm)'})
'labelWidthInches'.field({numeric: true, caption: 'Label Width (in)'})
'labelHeightMm'.field({numeric: true, caption: 'Label Height (mm)'})
'labelHeightInches'.field({numeric: true, caption: 'Label Height (in)'})
'labelHGapMm'.field({numeric: true, caption: 'Horizontal Gap Between Labels (mm)'})
'labelHGapInches'.field({numeric: true, caption: 'Horizontal Gap Between Labels (in)'})
'labelVGapMm'.field({numeric: true, caption: 'Vertical Gap Between Labels (mm)'})
'labelVGapInches'.field({numeric: true, caption: 'Vertical Gap Between Labels (in)'})

'Fields'.manifest()
'Add Field'.action({act: 'add'})
'Facet'.datatype()
'template'.field({refersToParent: 'Template', hidden: true})
'source'.field({caption: "Get Value From"})
'caption'.field()
'left'.field()
'leftInches'.field({caption: 'Left (in)'})
'top'.field()
'topInches'.field({caption: 'Top (in)'})
'Edit'.action({place: 'row', act: 'edit'})
'Trash'.action({place: 'row', act: 'trash'})
'FacetMaint.js'.maintSpecname()

/* eslint-disable no-cond-assign */

'Fields'.defaultSort({field: "sequence"})

'Duplicate'.act(async (maint, template) => {

  let duplicateFields = async dup => {
    let facet; let facets = await 'Facet'.bringChildrenOf(template)
    while ( facet = facets.__() ) {
      let dupFacet = await 'Facet'.create({parentCast: dup}, {template: dup.reference(), sequence: facet.sequence, caption: facet.caption})
      dupFacet.englishCaption = facet.englishCaption
      dupFacet.source = facet.source
      dupFacet.disposition = facet.disposition
      dupFacet.minimumDecimals = facet.minimumDecimals
      dupFacet.maximumDecimals = facet.maximumDecimals
      dupFacet.specification = facet.specification
      dupFacet.left = facet.left
      dupFacet.leftInches = facet.leftInches
      dupFacet.top = facet.top
      dupFacet.topInches = facet.topInches
      dupFacet.width = facet.width
      dupFacet.widthInches = facet.widthInches
      dupFacet.fontSize = facet.fontSize
      dupFacet.bold = facet.bold
      dupFacet.showTotal = facet.showTotal
      dupFacet.height = facet.height
      dupFacet.heightInches = facet.heightInches
      dupFacet.barcodeFormat = facet.barcodeFormat
    }
  }

  let generateUniquePurpose = async () => {
    let basePurpose = template.purpose
    if ( ! basePurpose )
      basePurpose = 'General Purpose'
    let seq = 2
    while ( true ) {
      let purpose = basePurpose + ' ' + seq
      let temp = await 'Template'.bringFirst({specification: template.specification, purpose: purpose})
      if ( ! temp ) 
        return purpose
      seq++
    }
  }

  await global.app.save()
  let dup = await 'Template'.create()
  dup.specification = template.specification
  dup.purpose = await generateUniquePurpose()
  dup.pageWidthMm = template.pageWidthMm
  dup.pageWidthInches = template.pageWidthInches
  dup.pageHeightMm = template.pageHeightMm
  dup.pageHeightInches = template.pageHeightInches
  dup.pageLeftMarginMm = template.pageLeftMarginMm
  dup.pageLeftMarginInches = template.pageLeftMarginInches
  dup.pageTopMarginMm = template.pageTopMarginMm
  dup.pageTopMarginInches = template.pageTopMarginInches
  dup.columnCount = template.columnCount
  dup.rowsPerPage = template.rowsPerPage
  dup.labelWidthMm = template.labelWidthMm
  dup.labelWidthInches = template.labelWidthInches
  dup.labelHeightMm = template.labelHeightMm
  dup.labelHeightInches = template.labelHeightInches
  dup.labelHGapMm = template.labelHGapMm
  dup.labelHGapInches = template.labelHGapInches
  dup.labelVGapMm = template.labelVGapMm
  dup.labelVGapInches = template.labelVGapInches
  await duplicateFields(dup)
  await global.app.save()
  await maint.segue('edit', 'TemplateMaint.js', dup)
})

'purposeDisplay'.visibleWhen((maint, template) => {
  return ! template.purpose
})

'purpose'.visibleWhen((maint, template) => {
  return template.purpose
})

'TemplateMaint'.substituteCast(async (template, maint) => {
  if ( template ) 
    return template
  let specname = 'LabelsPdf.js'
  let res = await 'Template'.bringOrCreate({specification: specname})
  return res
})

'left'.labelMmUnit({inchField: 'leftInches'})

'top'.labelMmUnit({inchField: 'topInches'})

'pageWidthMm'.labelMmUnit({inchField: 'pageWidthInches'})

'pageHeightMm'.labelMmUnit({inchField: 'pageHeightInches'})

'pageLeftMarginMm'.labelMmUnit({inchField: 'pageLeftMarginInches'})

'pageTopMarginMm'.labelMmUnit({inchField: 'pageTopMarginInches'})

'labelWidthMm'.labelMmUnit({inchField: 'labelWidthInches'})

'labelHeightMm'.labelMmUnit({inchField: 'labelHeightInches'})

'labelHGapMm'.labelMmUnit({inchField: 'labelHGapInches'})

'labelVGapMm'.labelMmUnit({inchField: 'labelVGapInches'})

